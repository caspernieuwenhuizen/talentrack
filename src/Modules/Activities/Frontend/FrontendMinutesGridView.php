<?php
namespace TT\Modules\Activities\Frontend;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Infrastructure\Query\QueryHelpers;
use TT\Infrastructure\Tenancy\CurrentClub;
use TT\Modules\Activities\Reports\MinutesGridQuery;
use TT\Modules\Analytics\Reports\ReportFilters;
use TT\Shared\Frontend\Components\FilterBar;
use TT\Shared\Frontend\Components\FrontendBreadcrumbs;
use TT\Shared\Frontend\Components\RecordLink;
use TT\Shared\Frontend\FrontendViewBase;

/**
 * FrontendMinutesGridView (#2386, epic #2381) — the desktop minutes-entry
 * grid, the sibling of the attendance grid restricted to match activities.
 * Players in rows, matches in columns, a minutes box per squad cell, one
 * explicit Save. Reads/writes the same effective minutes the Minutes-audit
 * matrix and the Minutes-played report use, so all three reconcile.
 *
 * URL: `?tt_view=minutes-grid&team_id=N&from=YYYY-MM-DD&to=YYYY-MM-DD`
 *
 * Write-capable → gated on `tt_edit_activities` (the cap the bulk endpoint
 * enforces) plus the `minutes_grid` feature. Only squad players (a non-guest
 * attendance row) are editable; the bulk write routes each edit through the
 * minutes-ownership arbiter server-side (override vs. direct minutes).
 */
final class FrontendMinutesGridView extends FrontendViewBase {

    protected static function enqueueAssets(): void {
        parent::enqueueAssets();
        // Reuse the attendance grid's chrome sheet, add the minutes specifics.
        wp_enqueue_style(
            'tt-frontend-attendance-grid',
            TT_PLUGIN_URL . 'assets/css/frontend-attendance-grid.css',
            [ 'tt-frontend-app-chrome' ],
            TT_VERSION
        );
        wp_enqueue_style(
            'tt-frontend-minutes-grid',
            TT_PLUGIN_URL . 'assets/css/frontend-minutes-grid.css',
            [ 'tt-frontend-attendance-grid' ],
            TT_VERSION
        );
        wp_enqueue_script(
            'tt-frontend-minutes-grid',
            TT_PLUGIN_URL . 'assets/js/frontend-minutes-grid.js',
            [],
            TT_VERSION,
            true
        );
        wp_localize_script( 'tt-frontend-minutes-grid', 'TTMinutesGrid', [
            'restBulk' => esc_url_raw( rest_url( 'talenttrack/v1/minutes/bulk' ) ),
            // #3094 — goals + assists are a separate resource: minutes go
            // through the ownership arbiter and these do not.
            'restStats' => esc_url_raw( rest_url( 'talenttrack/v1/activities/' ) ),
            'restPrefs' => esc_url_raw( rest_url( 'talenttrack/v1/me/preferences/minutes-grid' ) ),
            'stats'     => self::visibleStats(),
            'nonce'    => wp_create_nonce( 'wp_rest' ),
            'i18n'     => [
                'saving'    => __( 'Saving…', 'talenttrack' ),
                'saved'     => __( 'All changes saved', 'talenttrack' ),
                'error'     => __( 'Could not save — try again', 'talenttrack' ),
                'noChanges' => __( 'No unsaved changes', 'talenttrack' ),
                /* translators: %d is the number of unsaved cell changes. */
                'unsaved'   => __( '%d unsaved change(s)', 'talenttrack' ),
                'confirm'   => __( 'You have unsaved changes. Leave without saving?', 'talenttrack' ),
            ],
        ] );
    }

    public static function render( int $user_id, bool $is_admin ): void {
        self::enqueueAssets();

        if ( ! current_user_can( 'tt_edit_activities' ) ) {
            self::crumbs();
            echo '<p class="tt-notice">' . esc_html__( 'You do not have permission to record minutes.', 'talenttrack' ) . '</p>';
            return;
        }
        if ( ! \TT\Core\FeatureRegistry::isEnabled( 'minutes_grid' ) ) {
            self::crumbs();
            echo '<p class="tt-notice">' . esc_html__( 'The minutes grid has been switched off for your academy.', 'talenttrack' ) . '</p>';
            return;
        }

        // #3107 — the grid is Pro; recording minutes is not. See the note.
        if ( ! \TT\Modules\License\LicenseGate::allows( 'minutes_grid' ) ) {
            self::crumbs();
            echo \TT\Modules\License\UpgradePanel::render( 'minutes_grid', [ // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — UpgradePanel returns escaped HTML
                'note' => __( 'Minutes themselves are not affected: you can still enter them per activity, and a live match writes them for you. The grid is the faster desktop way to correct a whole squad at once.', 'talenttrack' ),
            ] );
            return;
        }

        self::crumbs();
        self::renderHeader( __( 'Minutes + statistics', 'talenttrack' ) );
        echo '<p class="tt-agrid-lead">' . esc_html__(
            'Record match minutes, goals and assists for a whole period at once. Rows are players, columns are matches; fill the cells and Save. Only players in a match squad can be edited, and recording output here never changes the scoreline.',
            'talenttrack'
        ) . '</p>';

        self::renderModeNav();

        $is_scope_admin = $is_admin
            || \TT\Modules\Authorization\AllTeamsScope::canSeeAllTeamsActivities( $user_id );
        $allowed_team_ids = $is_scope_admin
            ? null
            : array_values( array_map( 'intval', array_column( QueryHelpers::get_teams_for_coach( $user_id ), 'id' ) ) );

        if ( ! $is_scope_admin && $allowed_team_ids === [] ) {
            echo '<p class="tt-notice">' . esc_html__( "You don't coach any teams yet, so there are no minutes to record. Ask an administrator to assign you to a team.", 'talenttrack' ) . '</p>';
            return;
        }

        $teams = self::listTeams( $allowed_team_ids );
        if ( empty( $teams ) ) {
            echo '<p class="tt-notice">' . esc_html__( 'No teams yet — add one to record minutes.', 'talenttrack' ) . '</p>';
            return;
        }

        $team_id = isset( $_GET['team_id'] ) ? absint( $_GET['team_id'] ) : 0;
        if ( $allowed_team_ids !== null && $team_id > 0 && ! in_array( $team_id, $allowed_team_ids, true ) ) {
            echo '<p class="tt-notice">' . esc_html__( 'That team is not in your scope.', 'talenttrack' ) . '</p>';
            return;
        }
        if ( $team_id <= 0 || ! self::teamExists( $team_id, $teams ) ) {
            $team_id = (int) $teams[0]->id;
        }

        $defaults = ReportFilters::seasonDefaultWindow();
        $period = ReportFilters::sanitizePeriod( isset( $_GET['period'] ) ? sanitize_key( (string) $_GET['period'] ) : '' );
        $has_manual_from = isset( $_GET['from'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $_GET['from'] );
        $has_manual_to   = isset( $_GET['to'] )   && preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $_GET['to'] );
        $window = $period !== '' ? ReportFilters::periodWindow( $period, gmdate( 'Y-m-d' ) ) : null;
        $from = $has_manual_from ? sanitize_text_field( wp_unslash( (string) $_GET['from'] ) ) : ( $window['from'] ?? $defaults['from'] );
        $to   = $has_manual_to   ? sanitize_text_field( wp_unslash( (string) $_GET['to'] ) )   : ( $window['to'] ?? $defaults['to'] );

        // #3293 — the bar shows the period the QUERY ran on. A manual
        // From/To wins above, so handing the raw param on left the pill
        // describing a window the grid was not using.
        $period = ReportFilters::effectivePeriod( $period, (bool) $has_manual_from, (bool) $has_manual_to, $from, $to );

        self::renderFilterForm( $teams, $team_id, $from, $to, $period );

        $matrix = ( new MinutesGridQuery() )->matrix( $team_id, $from, $to );

        if ( $matrix['summary']['total_players'] === 0 ) {
            echo '<p class="tt-notice">' . esc_html__( 'No active players on this team yet. Add players to the roster first.', 'talenttrack' ) . '</p>';
            return;
        }
        if ( $matrix['summary']['total_activities'] === 0 ) {
            echo '<p class="tt-notice">' . esc_html__( 'No matches for this team in the selected window. Widen the date range or pick another team.', 'talenttrack' ) . '</p>';
            return;
        }

        self::renderGrid( $matrix );
    }

    private static function crumbs(): void {
        FrontendBreadcrumbs::fromDashboard(
            __( 'Minutes + statistics', 'talenttrack' ),
            [ FrontendBreadcrumbs::viewCrumb( 'activities', __( 'Activities', 'talenttrack' ) ) ]
        );
    }

    /**
     * Segmented Attendance | Minutes toggle across the two grid surfaces.
     *
     * #2822 — a mode switcher, not a record-scoped tab strip, so it is
     * exempt from CLAUDE.md §5c's `RecordSpine` requirement. See the twin
     * method on `FrontendAttendanceGridView` for the reasoning; both now
     * render the same shared `SegmentedControl`.
     */
    private static function renderModeNav(): void {
        $dash = RecordLink::dashboardUrl();
        $team = isset( $_GET['team_id'] ) ? absint( $_GET['team_id'] ) : 0;
        $att_args = [ 'tt_view' => 'attendance-grid' ]; /* tt-xview-ok — sibling grid, gated by the attendance_grid feature below */
        if ( $team > 0 ) $att_args['team_id'] = $team;

        $options = [];
        if ( \TT\Core\FeatureRegistry::isEnabled( 'attendance_grid' ) ) {
            $options[] = [
                'label' => __( 'Attendance', 'talenttrack' ),
                'url'   => add_query_arg( $att_args, $dash ),
            ];
        }
        $options[] = [ 'label' => __( 'Minutes + stats', 'talenttrack' ), 'current' => true ];

        \TT\Shared\Frontend\Components\SegmentedControl::render( [
            'label'   => _x( 'Grid', 'segmented control label: which of the two data-entry grids', 'talenttrack' ),
            'options' => $options,
        ] );
    }

    /**
     * @param array<string,mixed> $matrix
     */
    private static function renderGrid( array $matrix ): void {
        /** @var list<array<string,mixed>> $players */
        $players = $matrix['players'];
        /** @var list<array<string,mixed>> $activities */
        $activities = $matrix['activities'];
        /** @var array<int,array<int,array{minutes:int,squad:bool,goals:int,assists:int}>> $cells */
        $cells = $matrix['cells'];

        echo '<div class="tt-agrid-card">';
        echo '<div class="tt-agrid-card__head">';
        echo '<h2 class="tt-agrid-card__title">' . esc_html__( 'Minutes + statistics', 'talenttrack' ) . '</h2>';
        echo '<span class="tt-agrid-card__hint">' . esc_html__( 'fill the cells per match, then Save', 'talenttrack' ) . '</span>';
        echo '</div>';

        self::renderStatChips();

        echo '<div class="tt-agrid-scroll">';
        echo '<table class="tt-agrid tt-agrid--stats">';

        // Two header rows: the match, then Min / G / A under it. A spreadsheet
        // user needs no explanation for that, which is the whole premise of
        // this surface being the alternative to the wizard.
        echo '<thead>';
        echo '<tr>';
        echo '<th class="tt-agrid__player" scope="col" rowspan="2">' . esc_html__( 'Player', 'talenttrack' ) . '</th>';
        foreach ( $activities as $a ) {
            $date  = $a['session_date'] !== '' ? date_i18n( 'j M', strtotime( (string) $a['session_date'] ) ) : '';
            $label = (string) $a['title'] !== '' ? (string) $a['title'] : __( 'Match', 'talenttrack' );
            $owned = ! empty( $a['owned_by_execution'] );
            echo '<th class="tt-agrid__act is-match" scope="colgroup" colspan="3" title="' . esc_attr( $label ) . '">';
            echo '<span class="tt-agrid__act-date">' . esc_html( $date ) . '</span>';
            // #2993 — icon-set glyph, not an emoji.
            echo '<span class="tt-agrid__act-type" aria-hidden="true">'
                . \TT\Shared\Icons\IconRenderer::render( 'football', [ 'width' => 14, 'height' => 14 ] ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — trusted SVG.
                . '</span>';
            if ( $owned ) {
                echo '<span class="tt-agrid__act-owned" title="' . esc_attr__( 'Minutes come from the match sheet; your entry is kept as a correction.', 'talenttrack' ) . '">' . esc_html__( 'live', 'talenttrack' ) . '</span>';
            }
            echo '</th>';
        }
        echo '<th class="tt-agrid__rate" scope="colgroup" colspan="3">' . esc_html__( 'Total', 'talenttrack' ) . '</th>';
        echo '</tr>';

        echo '<tr class="tt-agrid__subhead">';
        foreach ( $activities as $a ) {
            self::renderSubHeadCells();
        }
        self::renderSubHeadCells();
        echo '</tr>';
        echo '</thead><tbody>';

        foreach ( $players as $pl ) {
            $pid    = (int) $pl['player_id'];
            $jersey = $pl['jersey_number'] !== null ? (int) $pl['jersey_number'] : null;
            $name   = trim( (string) $pl['first_name'] . ' ' . (string) $pl['last_name'] );
            if ( $name === '' ) $name = '#' . $pid;

            echo '<tr>';
            echo '<th class="tt-agrid__player" scope="row"><span class="tt-agrid__who">';
            if ( $jersey !== null ) echo '<span class="tt-agrid__no">' . esc_html( (string) $jersey ) . '</span>';
            echo '<span class="tt-agrid__nm">' . esc_html( $name ) . '</span>';
            echo '</span></th>';

            $total   = 0;
            $goals   = 0;
            $assists = 0;

            foreach ( $activities as $a ) {
                $aid  = (int) $a['activity_id'];
                $cell = $cells[ $pid ][ $aid ] ?? null;
                $when = $a['session_date'] !== '' ? date_i18n( 'j M', strtotime( (string) $a['session_date'] ) ) : '';

                if ( $cell === null ) {
                    // Not in this match's squad — informational, not editable.
                    echo '<td class="tt-agrid-cell tt-agrid-cell--na tt-agrid-cell--sep" colspan="3" aria-label="' . esc_attr__( 'Not in squad', 'talenttrack' ) . '">&mdash;</td>';
                    continue;
                }

                $mins     = (int) $cell['minutes'];
                $total   += $mins;
                $goals   += (int) $cell['goals'];
                $assists += (int) $cell['assists'];

                echo '<td class="tt-agrid-cell tt-agrid-min tt-agrid-cell--sep" data-player="' . esc_attr( (string) $pid ) . '" data-activity="' . esc_attr( (string) $aid ) . '">';
                echo '<input class="tt-agrid-min-in" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="3" value="' . esc_attr( (string) $mins ) . '" data-player="' . esc_attr( (string) $pid ) . '" data-activity="' . esc_attr( (string) $aid ) . '" aria-label="' . esc_attr( sprintf(
                    /* translators: 1: player name, 2: match date. */
                    __( 'Minutes for %1$s on %2$s', 'talenttrack' ),
                    $name,
                    $when
                ) ) . '">';
                echo '</td>';

                self::renderStatCell( 'goals', $pid, $aid, (int) $cell['goals'], sprintf(
                    /* translators: 1: player name, 2: match date. */
                    __( 'Goals for %1$s on %2$s', 'talenttrack' ),
                    $name,
                    $when
                ) );
                self::renderStatCell( 'assists', $pid, $aid, (int) $cell['assists'], sprintf(
                    /* translators: 1: player name, 2: match date. */
                    __( 'Assists for %1$s on %2$s', 'talenttrack' ),
                    $name,
                    $when
                ) );
            }

            echo '<td class="tt-agrid__rate tt-agrid-cell--sep">' . esc_html( (string) number_format_i18n( $total ) ) . '</td>';
            echo '<td class="tt-agrid__rate tt-agrid-stat" data-stat="goals">' . esc_html( (string) number_format_i18n( $goals ) ) . '</td>';
            echo '<td class="tt-agrid__rate tt-agrid-stat" data-stat="assists">' . esc_html( (string) number_format_i18n( $assists ) ) . '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        self::renderReconciliationFoot( $activities );
        echo '</table></div>';

        $cancel_url = add_query_arg( [ 'tt_view' => 'activities' ], RecordLink::dashboardUrl() ); /* tt-xview-ok */
        $back = \TT\Shared\Frontend\Components\BackLink::resolve();
        if ( $back !== null ) {
            $cancel_url = $back['url'];
        }
        echo '<div class="tt-agrid-savebar">';
        echo '<span class="tt-agrid-savebar__status" data-agrid-status role="status" aria-live="polite">' . esc_html__( 'No unsaved changes', 'talenttrack' ) . '</span>';
        echo '<div class="tt-form-actions tt-agrid-savebar__actions">';
        echo '<a class="tt-btn tt-btn-secondary" href="' . esc_url( $cancel_url ) . '">' . esc_html__( 'Cancel', 'talenttrack' ) . '</a>';
        echo '<button type="button" class="tt-btn tt-btn-primary" data-agrid-save disabled>' . esc_html__( 'Save', 'talenttrack' ) . '</button>';
        echo '</div>';
        echo '</div>';

        echo '</div>'; // .tt-agrid-card
    }

    /**
     * The `Min | G | A` sub-header, repeated under every match and under the
     * totals. Abbreviated because it is repeated once per match; the full
     * word is on every cell's `aria-label`, where a screen reader needs it.
     */
    private static function renderSubHeadCells(): void {
        printf(
            '<th class="tt-agrid__sub tt-agrid-cell--sep" scope="col" title="%s">%s</th>',
            esc_attr__( 'Minutes', 'talenttrack' ),
            esc_html_x( 'Min', 'minutes column, abbreviated', 'talenttrack' )
        );
        // `_x` with the vocabulary the minutes report already uses: the bare
        // msgid "Goals" is the development-goal sense in this product and
        // comes back as "Doelen", which is not what a G column means.
        printf(
            '<th class="tt-agrid__sub tt-agrid-stat" scope="col" data-stat="goals" title="%s">%s</th>',
            esc_attr_x( 'Goals scored', 'goals scored in matches', 'talenttrack' ),
            esc_html_x( 'G', 'goals column, abbreviated', 'talenttrack' )
        );
        printf(
            '<th class="tt-agrid__sub tt-agrid-stat" scope="col" data-stat="assists" title="%s">%s</th>',
            esc_attr_x( 'Assists', 'who created a goal', 'talenttrack' ),
            esc_html_x( 'A', 'assists column, abbreviated', 'talenttrack' )
        );
    }

    private static function renderStatCell( string $stat, int $player_id, int $activity_id, int $value, string $label ): void {
        printf(
            '<td class="tt-agrid-cell tt-agrid-stat" data-stat="%1$s">'
            . '<input class="tt-agrid-stat-in" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="2" value="%2$s"'
            . ' data-stat="%1$s" data-player="%3$d" data-activity="%4$d" aria-label="%5$s"></td>',
            esc_attr( $stat ),
            esc_attr( $value > 0 ? (string) $value : '' ),
            $player_id,
            $activity_id,
            esc_attr( $label )
        );
    }

    /**
     * The Goals / Assists column switches.
     *
     * Sub-columns triple the grid's width, and a coach who only records
     * minutes should be able to get today's grid back. The choice is per
     * user (`user_meta`) rather than per club: it is how one person likes to
     * look at the screen, not a decision about the academy.
     *
     * Rendered as checkboxes rather than buttons so the state is real and
     * the control works with a keyboard before any script loads.
     */
    private static function renderStatChips(): void {
        $shown = self::visibleStats();

        echo '<div class="tt-agrid-statpick" data-agrid-statpick>';
        echo '<span class="tt-agrid-statpick__label">' . esc_html__( 'Show', 'talenttrack' ) . '</span>';

        $labels = [
            'goals'   => _x( 'Goals scored', 'goals scored in matches', 'talenttrack' ),
            'assists' => _x( 'Assists', 'who created a goal', 'talenttrack' ),
        ];

        foreach ( $labels as $stat => $label ) {
            printf(
                '<label class="tt-agrid-statchip"><input type="checkbox" data-agrid-stattoggle value="%1$s"%2$s> %3$s</label>',
                esc_attr( $stat ),
                in_array( $stat, $shown, true ) ? ' checked' : '',
                esc_html( $label )
            );
        }

        echo '</div>';
    }

    /**
     * Which stat columns this user wants. Resolved by the same class the
     * REST route uses, so the rendered page and an API consumer cannot
     * disagree about what this user asked for (CLAUDE.md §4).
     *
     * @return list<string>
     */
    private static function visibleStats(): array {
        return \TT\Infrastructure\REST\MinutesGridPreferencesRestController::forUser( get_current_user_id() );
    }

    /**
     * `Attributed / score` per match.
     *
     * Attributed goals summed against `tt_activities.home_score`, with a
     * marker on a mismatch. `2/3` says a goal in that match has no scorer
     * against anyone's name yet.
     *
     * It costs one row and makes the data self-auditing, which is exactly
     * the drift migration 0235's docblock was written about. Deliberately
     * **not** a validation gate: no cell is blocked by a mismatch, because
     * "we do not know who scored the third" is a true state of the world and
     * the grid's job is to show it, not to forbid it.
     *
     * @param list<array<string,mixed>> $activities
     */
    private static function renderReconciliationFoot( array $activities ): void {
        echo '<tfoot><tr class="tt-agrid-recon">';
        echo '<th class="tt-agrid__player" scope="row">' . esc_html__( 'Attributed / score', 'talenttrack' ) . '</th>';

        foreach ( $activities as $a ) {
            $attributed = (int) ( $a['attributed_goals'] ?? 0 );
            $score      = $a['home_score'];

            if ( $score === null ) {
                // No scoreline recorded. Not the same as 0-0, and saying
                // "0/0" would invent an agreement that was never tested.
                printf(
                    '<td class="tt-agrid-cell tt-agrid-cell--sep tt-agrid-recon__cell" colspan="3">%s</td>',
                    esc_html( sprintf(
                        /* translators: %d: goals attributed to a named scorer. */
                        _n( '%d attributed', '%d attributed', $attributed, 'talenttrack' ),
                        $attributed
                    ) )
                );
                continue;
            }

            $score   = (int) $score;
            $matches = $attributed === $score;

            printf(
                '<td class="tt-agrid-cell tt-agrid-cell--sep tt-agrid-recon__cell%1$s" colspan="3" title="%2$s">%3$s%4$s</td>',
                $matches ? '' : ' is-mismatch',
                esc_attr(
                    $matches
                        ? __( 'Every goal in this match has a scorer against it.', 'talenttrack' )
                        : __( 'Some goals in this match have no scorer against anyone yet. The scoreline is unchanged.', 'talenttrack' )
                ),
                esc_html( sprintf( '%d/%d', $attributed, $score ) ),
                $matches ? '' : ' <span class="tt-agrid-recon__flag" aria-hidden="true">!</span>'
            );
        }

        echo '<td class="tt-agrid-cell tt-agrid-cell--sep" colspan="3"></td>';
        echo '</tr></tfoot>';
    }

    /**
     * @param list<object> $teams
     */
    private static function renderFilterForm( array $teams, int $team_id, string $from, string $to, string $period ): void {
        $dash_url      = RecordLink::dashboardUrl();
        $period_labels = ReportFilters::periodLabels();

        $team_options = [];
        foreach ( $teams as $t ) {
            $team_options[ (string) (int) $t->id ] = (string) $t->name;
        }

        $pill_base = [ 'tt_view' => 'minutes-grid', 'team_id' => $team_id ];
        if ( ! empty( $_GET['tt_back'] ) ) $pill_base['tt_back'] = sanitize_text_field( wp_unslash( (string) $_GET['tt_back'] ) );

        $period_options = [];
        foreach ( $period_labels as $key => $label ) {
            $args = $pill_base;
            if ( $key !== '' ) $args['period'] = $key;
            $period_options[] = [
                'value'  => $key,
                'label'  => $label,
                'url'    => add_query_arg( $args, $dash_url ),
                'active' => ( $period === $key ),
            ];
        }

        $hidden = [ 'tt_view' => 'minutes-grid' ];
        if ( $period !== '' )              $hidden['period']  = $period;
        if ( ! empty( $_GET['tt_back'] ) ) $hidden['tt_back'] = sanitize_text_field( wp_unslash( (string) $_GET['tt_back'] ) );

        $active_count = 0;
        $chips = [];
        if ( $period !== '' ) { $active_count++; $chips[] = (string) ( $period_labels[ $period ] ?? '' ); }
        // #3293 — a custom window is a filter and was counted nowhere.
        $range_chip = ReportFilters::customRangeChip( $period, $from, $to );
        if ( $range_chip !== null ) { $active_count++; $chips[] = $range_chip; }

        $reset_args = [ 'tt_view' => 'minutes-grid', 'team_id' => $team_id ];
        if ( ! empty( $_GET['tt_back'] ) ) $reset_args['tt_back'] = sanitize_text_field( wp_unslash( (string) $_GET['tt_back'] ) );

        FilterBar::render( [
            'hidden'       => $hidden,
            'active_count' => $active_count,
            'chips'        => $chips,
            'reset_url'    => add_query_arg( $reset_args, $dash_url ),
            'groups'       => [
                [
                    'type'     => 'select',
                    'key'      => 'team',
                    'label'    => __( 'Team', 'talenttrack' ),
                    'name'     => 'team_id',
                    'selected' => (string) $team_id,
                    'options'  => $team_options,
                ],
                // #3331 — one time control: the presets and the custom
                // From/To answer the same question, so they are one
                // control. The standalone date_range group is gone.
                ReportFilters::periodGroup( $period_options, $period, $from, $to ),
            ],
        ] );
    }

    /**
     * @param list<int>|null $allowed_team_ids
     * @return list<object>
     */
    private static function listTeams( ?array $allowed_team_ids = null ): array {
        global $wpdb;
        if ( $allowed_team_ids !== null ) {
            if ( $allowed_team_ids === [] ) return [];
            $placeholders = implode( ',', array_fill( 0, count( $allowed_team_ids ), '%d' ) );
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $rows = $wpdb->get_results( $wpdb->prepare(
                "SELECT id, name FROM {$wpdb->prefix}tt_teams
                  WHERE club_id = %d AND " . \TT\Infrastructure\Archive\ArchiveRepository::filterClause( 'active' ) . " AND id IN ($placeholders)
                  ORDER BY name ASC",
                CurrentClub::id(), ...$allowed_team_ids
            ) );
        } else {
            $rows = $wpdb->get_results( $wpdb->prepare(
                "SELECT id, name FROM {$wpdb->prefix}tt_teams
                  WHERE club_id = %d AND " . \TT\Infrastructure\Archive\ArchiveRepository::filterClause( 'active' ) . "
                  ORDER BY name ASC",
                CurrentClub::id()
            ) );
        }
        return is_array( $rows ) ? $rows : [];
    }

    /**
     * @param list<object> $teams
     */
    private static function teamExists( int $team_id, array $teams ): bool {
        foreach ( $teams as $t ) {
            if ( (int) $t->id === $team_id ) return true;
        }
        return false;
    }
}
