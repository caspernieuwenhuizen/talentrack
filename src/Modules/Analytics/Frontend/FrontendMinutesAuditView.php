<?php
namespace TT\Modules\Analytics\Frontend;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Infrastructure\Query\QueryHelpers;
use TT\Infrastructure\Tenancy\CurrentClub;
use TT\Modules\Analytics\Reports\MinutesAuditQuery;
use TT\Modules\Analytics\Reports\ReportFilters;
use TT\Shared\Frontend\Components\BackLink;
use TT\Shared\Frontend\Components\FilterBar;
use TT\Shared\Frontend\Components\FrontendAppChrome;
use TT\Shared\Frontend\Components\FrontendBreadcrumbs;
use TT\Shared\Frontend\Components\RecordLink;
use TT\Shared\Frontend\FrontendViewBase;

/**
 * FrontendMinutesAuditView (#2368) — read-only Minutes-audit overview.
 *
 * URL: `?tt_view=minutes-audit&team_id=N&from=YYYY-MM-DD&to=YYYY-MM-DD&type=all|<key>&gap=all|complete|partial|none`
 *
 * The auditability companion to the Minutes-played-per-team report: a
 * games × players matrix over the season-default window that makes it
 * obvious which games have complete / partial / missing recorded minutes.
 * Rows = the team's game/match/tournament activities; columns = the squad
 * (players with attendance on those activities — NOT tt_players.team_id,
 * the #2339 bug); each cell = that player's recorded minutes for the game.
 *
 * Read-only. The per-row "Bewerk" affordance deep-links to the match's
 * activity detail; the editable grid (#2367) is held behind the
 * match-execution minutes arbiter (#2301).
 *
 * Cap-gated on `tt_view_analytics` (same as the sibling minutes report).
 * Numbers reconcile exactly with {@see MinutesQuery} because the audit
 * reads the same persisted `record_type='actual'` minutes (#2193).
 */
final class FrontendMinutesAuditView extends FrontendViewBase {

    protected static function enqueueAssets(): void {
        parent::enqueueAssets();
        wp_enqueue_style(
            'tt-frontend-minutes-audit',
            TT_PLUGIN_URL . 'assets/css/frontend-minutes-audit.css',
            [ 'tt-frontend-app-chrome' ],
            TT_VERSION
        );
    }

    public static function render( int $user_id, bool $is_admin ): void {
        self::enqueueAssets();

        if ( ! current_user_can( 'tt_view_analytics' ) ) {
            FrontendBreadcrumbs::fromDashboard(
                __( 'Not authorized', 'talenttrack' ),
                [ FrontendBreadcrumbs::viewCrumb( 'reports', __( 'Reports', 'talenttrack' ) ) ]
            );
            echo '<p class="tt-notice">' . esc_html__( 'You do not have permission to view this report.', 'talenttrack' ) . '</p>';
            return;
        }

        // #2368 — per-report toggle: reject even a direct link when the
        // Minutes-audit report has been switched off for this academy.
        if ( ! \TT\Core\FeatureRegistry::isEnabled( 'report_minutes_audit' ) ) {
            FrontendBreadcrumbs::fromDashboard(
                __( 'Minutes audit', 'talenttrack' ),
                [ FrontendBreadcrumbs::viewCrumb( 'reports', __( 'Reports', 'talenttrack' ) ) ]
            );
            echo '<p class="tt-notice">' . esc_html__( 'This report has been switched off for your academy.', 'talenttrack' ) . '</p>';
            return;
        }

        FrontendBreadcrumbs::fromDashboard(
            __( 'Minutes audit', 'talenttrack' ),
            [ FrontendBreadcrumbs::viewCrumb( 'reports', __( 'Reports', 'talenttrack' ) ) ]
        );
        self::renderHeader( __( 'Minutes audit', 'talenttrack' ) );

        echo '<p class="tt-maud-lead">' . esc_html__(
            'Check and chase recorded match minutes. Rows are games, columns are squad players; each cell shows the minutes recorded for that player in that game.',
            'talenttrack'
        ) . '</p>';

        // Team scope mirrors the minutes report (#1193 / #1942): academy-wide
        // roles see every team, everyone else only the teams they coach.
        $is_scope_admin = $is_admin
            || \TT\Modules\Authorization\AllTeamsScope::canSeeAllTeamsActivities( $user_id );
        $allowed_team_ids = $is_scope_admin
            ? null
            : array_values( array_map( 'intval', array_column( QueryHelpers::get_teams_for_coach( $user_id ), 'id' ) ) );

        if ( ! $is_scope_admin && $allowed_team_ids === [] ) {
            echo '<p class="tt-notice">' . esc_html__( "You don't coach any teams yet, so there is no minutes data to audit. Ask an administrator to assign you to a team.", 'talenttrack' ) . '</p>';
            return;
        }

        $teams = self::listTeams( $allowed_team_ids );
        if ( empty( $teams ) ) {
            echo '<p class="tt-notice">' . esc_html__( 'No teams yet — add one to enable this report.', 'talenttrack' ) . '</p>';
            return;
        }

        $team_id = isset( $_GET['team_id'] ) ? absint( $_GET['team_id'] ) : 0;
        if ( $allowed_team_ids !== null && $team_id > 0 && ! in_array( $team_id, $allowed_team_ids, true ) ) {
            echo '<p class="tt-notice">' . esc_html__( 'No data for the selected team in your scope.', 'talenttrack' ) . '</p>';
            return;
        }
        if ( $team_id <= 0 || ! self::teamExists( $team_id, $teams ) ) {
            $team_id = (int) $teams[0]->id;
        }

        // Window: season default unless a period pill or manual From/To wins,
        // matching the sibling reports (#2349).
        $defaults = ReportFilters::seasonDefaultWindow();
        $period = ReportFilters::sanitizePeriod( isset( $_GET['period'] ) ? sanitize_key( (string) $_GET['period'] ) : '' );
        $has_manual_from = isset( $_GET['from'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $_GET['from'] );
        $has_manual_to   = isset( $_GET['to'] )   && preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $_GET['to'] );
        $window = $period !== '' ? ReportFilters::periodWindow( $period, gmdate( 'Y-m-d' ) ) : null;
        $from = $has_manual_from
            ? sanitize_text_field( wp_unslash( (string) $_GET['from'] ) )
            : ( $window['from'] ?? $defaults['from'] );
        $to = $has_manual_to
            ? sanitize_text_field( wp_unslash( (string) $_GET['to'] ) )
            : ( $window['to'] ?? $defaults['to'] );

        // #3293 — the bar shows the period the query ran on, not the param.
        $period = ReportFilters::effectivePeriod( $period, (bool) $has_manual_from, (bool) $has_manual_to, $from, $to );

        $type_options = self::typeOptions();
        $type_filter  = isset( $_GET['type'] ) ? sanitize_key( (string) wp_unslash( $_GET['type'] ) ) : 'all';
        if ( $type_filter === '' || ! isset( $type_options[ $type_filter ] ) ) $type_filter = 'all';

        // Gap filter (the clickable-KPI target, #2343). Narrows the visible
        // rows to one completeness bucket; 'all' shows every game.
        $gap = isset( $_GET['gap'] ) ? sanitize_key( (string) wp_unslash( $_GET['gap'] ) ) : 'all';
        if ( ! in_array( $gap, [ 'all', 'complete', 'partial', 'none' ], true ) ) $gap = 'all';

        self::renderFilterForm( $teams, $team_id, $from, $to, $type_filter, $period, $type_options );

        $matrix = ( new MinutesAuditQuery() )->matrix( $team_id, $from, $to, $type_filter );

        // #3338 — everything the filters govern, both empty states included.
        printf(
            '<div data-tt-filter-region data-tt-filter-count="%d">',
            (int) $matrix['summary']['total_games']
        );

        // Honest empty state: distinguish "no games in window" from
        // "games present but nothing recorded yet".
        if ( $matrix['summary']['total_games'] === 0 ) {
            echo '<p class="tt-notice">' . esc_html__( 'No games for this team in the selected window. Widen the date range or pick another team.', 'talenttrack' ) . '</p>';
            echo '</div>';
            return;
        }

        self::renderKpiStrip( $matrix['summary'], $team_id, $from, $to, $type_filter, $period, $gap );

        if ( $matrix['grand_total'] === 0 ) {
            echo '<div class="tt-maud-note">';
            echo '<strong>' . esc_html__( 'Games present, no minutes recorded yet.', 'talenttrack' ) . '</strong> ';
            echo esc_html__( 'Minutes come only from recorded actual entries (via match-execution or manual minutes entry). Open a game below and record its minutes.', 'talenttrack' );
            echo '</div>';
        }

        self::renderMatrix( $matrix, $gap, $team_id );
        echo '</div>'; // #3338 — /.tt-filter-region
    }

    /**
     * KPI strip — clickable gap tiles (#2343 href passthrough). Each tile
     * sets `?gap=` to narrow the matrix; the active tile is highlighted.
     *
     * @param array{total_games:int,complete:int,partial:int,none:int} $summary
     */
    private static function renderKpiStrip( array $summary, int $team_id, string $from, string $to, string $type_filter, string $period, string $active_gap ): void {
        $tile_url = static function ( string $gap ) use ( $team_id, $from, $to, $type_filter, $period ): string {
            $args = [ 'tt_view' => 'minutes-audit', 'team_id' => $team_id, 'from' => $from, 'to' => $to ];
            if ( $type_filter !== 'all' ) $args['type']   = $type_filter;
            if ( $period !== '' )         $args['period'] = $period;
            if ( $gap !== 'all' )         $args['gap']    = $gap;
            if ( ! empty( $_GET['tt_back'] ) ) $args['tt_back'] = sanitize_text_field( wp_unslash( (string) $_GET['tt_back'] ) );
            return add_query_arg( $args, RecordLink::dashboardUrl() );
        };

        echo '<div class="tt-report-kpis tt-maud-kpis" role="group" aria-label="' . esc_attr__( 'Minutes audit summary', 'talenttrack' ) . '">';
        // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped — kpiTile escapes its own fields.
        echo FrontendAppChrome::kpiTile( [
            'label' => __( 'Games', 'talenttrack' ),
            'value' => (string) number_format_i18n( $summary['total_games'] ),
            'href'  => $tile_url( 'all' ),
            'data'  => [ 'gap' => 'all', 'active' => $active_gap === 'all' ? '1' : '0' ],
        ] );
        echo FrontendAppChrome::kpiTile( [
            'label' => __( 'Fully recorded', 'talenttrack' ),
            'value' => (string) number_format_i18n( $summary['complete'] ),
            'flag'  => 'green',
            'href'  => $tile_url( 'complete' ),
            'data'  => [ 'gap' => 'complete', 'active' => $active_gap === 'complete' ? '1' : '0' ],
        ] );
        echo FrontendAppChrome::kpiTile( [
            'label' => __( 'Incomplete', 'talenttrack' ),
            'value' => (string) number_format_i18n( $summary['partial'] ),
            'href'  => $tile_url( 'partial' ),
            'data'  => [ 'gap' => 'partial', 'active' => $active_gap === 'partial' ? '1' : '0' ],
        ] );
        echo FrontendAppChrome::kpiTile( [
            'label' => __( 'Not recorded', 'talenttrack' ),
            'value' => (string) number_format_i18n( $summary['none'] ),
            'flag'  => 'red',
            'href'  => $tile_url( 'none' ),
            'data'  => [ 'gap' => 'none', 'active' => $active_gap === 'none' ? '1' : '0' ],
        ] );
        // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '</div>';
    }

    /**
     * Render the games × players matrix. Rows narrowed to the active gap
     * bucket; a per-row status chip + "Bewerk" deep-link to the activity.
     *
     * @param array<string,mixed> $matrix
     */
    private static function renderMatrix( array $matrix, string $gap, int $team_id ): void {
        /** @var list<array<string,mixed>> $players */
        $players = $matrix['players'];
        /** @var list<array<string,mixed>> $games */
        $games = $matrix['games'];

        // Apply the gap filter to the visible rows.
        $visible = array_values( array_filter(
            $games,
            static fn( array $g ): bool => $gap === 'all' || (string) $g['status'] === $gap
        ) );

        $dash_url = RecordLink::dashboardUrl();

        echo '<div class="tt-maud-card">';
        echo '<div class="tt-maud-card__head">';
        echo '<h2 class="tt-maud-card__title">' . esc_html__( 'Game × player', 'talenttrack' ) . '</h2>';
        echo '<span class="tt-maud-card__hint">' . esc_html__( 'minutes recorded per player', 'talenttrack' ) . '</span>';
        echo '</div>';

        if ( $visible === [] ) {
            echo '<p class="tt-notice tt-maud-empty">' . esc_html__( 'No games match this filter. Clear the filter to see every game.', 'talenttrack' ) . '</p>';
            echo '</div>';
            self::renderLegend();
            return;
        }

        echo '<div class="tt-maud-scroll">';
        echo '<table class="tt-maud-matrix"><thead><tr>';
        echo '<th class="tt-maud-matrix__game" scope="col">' . esc_html__( 'Game', 'talenttrack' ) . '</th>';
        foreach ( $players as $pl ) {
            $name = self::shortName( $pl );
            echo '<th class="tt-maud-matrix__player" scope="col"><span>' . esc_html( $name ) . '</span></th>';
        }
        echo '<th scope="col">' . esc_html__( 'Total', 'talenttrack' ) . '</th>';
        echo '<th scope="col">' . esc_html__( 'Status', 'talenttrack' ) . '</th>';
        echo '<th scope="col"><span class="screen-reader-text">' . esc_html__( 'Actions', 'talenttrack' ) . '</span></th>';
        echo '</tr></thead><tbody>';

        $row_class = [ 'complete' => '', 'partial' => 'is-gap', 'none' => 'is-none' ];
        foreach ( $visible as $g ) {
            $status = (string) $g['status'];
            $cls    = $row_class[ $status ] ?? '';
            echo '<tr' . ( $cls !== '' ? ' class="' . esc_attr( $cls ) . '"' : '' ) . '>';

            $date = $g['session_date'] !== '' ? date_i18n( 'j M', strtotime( (string) $g['session_date'] ) ) : '';
            $title = (string) $g['title'];
            if ( $title === '' ) $title = __( 'Game', 'talenttrack' );
            echo '<td class="tt-maud-matrix__game"><b>' . esc_html( $title ) . '</b>';
            if ( $date !== '' ) echo '<small>' . esc_html( $date ) . '</small>';
            echo '</td>';

            /** @var array<int,int> $minutes */
            $minutes = $g['minutes'];
            /** @var array<int,bool> $on_squad */
            $on_squad = $g['on_squad'];
            foreach ( $players as $pl ) {
                $pid  = (int) $pl['player_id'];
                $mins = (int) ( $minutes[ $pid ] ?? 0 );
                $sq   = ! empty( $on_squad[ $pid ] );
                if ( ! $sq ) {
                    echo '<td class="tt-maud-cell tt-maud-cell--na" aria-label="' . esc_attr__( 'Not in squad', 'talenttrack' ) . '">&mdash;</td>';
                } elseif ( $mins > 0 ) {
                    echo '<td class="tt-maud-cell tt-maud-cell--min">' . esc_html( (string) $mins ) . '</td>';
                } else {
                    echo '<td class="tt-maud-cell tt-maud-cell--zero" aria-label="' . esc_attr__( 'On squad, 0 recorded', 'talenttrack' ) . '">0</td>';
                }
            }

            echo '<td class="tt-maud-cell tt-maud-cell--tot">' . esc_html( (string) number_format_i18n( (int) $g['total_minutes'] ) ) . '</td>';
            echo '<td>' . self::statusChip( $status ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — statusChip escapes.

            // #2367 — the per-row affordance now deep-links to the minutes
            // EDITOR (?tt_view=minutes-audit&match_id=N), not the activity
            // detail page. §7 — hidden entirely for a user who lacks the
            // write cap the editor + its endpoints enforce; they still see
            // the read-only matrix, just no edit pill.
            if ( current_user_can( 'tt_edit_activities' ) ) {
                $edit_url = BackLink::appendTo( add_query_arg(
                    [ 'tt_view' => 'minutes-audit', 'match_id' => (int) $g['activity_id'] ],
                    $dash_url
                ) );
                $edit_label = $status === 'none' ? __( 'Record', 'talenttrack' ) : __( 'Edit', 'talenttrack' );
                echo '<td class="tt-maud-matrix__editcell"><a class="tt-record-link" href="' . esc_url( $edit_url ) . '">' . esc_html( $edit_label ) . '</a></td>';
            } else {
                echo '<td class="tt-maud-matrix__editcell"></td>';
            }
            echo '</tr>';
        }

        // Column-total footer (over the visible rows so it reconciles with
        // what the operator sees). Recompute per visible set.
        $col_tot   = array_fill_keys( array_map( static fn( array $pl ): int => (int) $pl['player_id'], $players ), 0 );
        $grand_tot = 0;
        foreach ( $visible as $g ) {
            foreach ( $players as $pl ) {
                $pid = (int) $pl['player_id'];
                $m   = (int) ( $g['minutes'][ $pid ] ?? 0 );
                $col_tot[ $pid ] += $m;
                $grand_tot       += $m;
            }
        }
        echo '<tr class="tt-maud-matrix__footrow">';
        echo '<td class="tt-maud-matrix__game tt-maud-cell--tot"><b>' . esc_html__( 'Column total', 'talenttrack' ) . '</b></td>';
        foreach ( $players as $pl ) {
            $pid = (int) $pl['player_id'];
            echo '<td class="tt-maud-cell tt-maud-cell--tot">' . esc_html( (string) number_format_i18n( (int) $col_tot[ $pid ] ) ) . '</td>';
        }
        echo '<td class="tt-maud-cell tt-maud-cell--tot">' . esc_html( (string) number_format_i18n( $grand_tot ) ) . '</td>';
        echo '<td></td><td></td>';
        echo '</tr>';

        echo '</tbody></table></div>';
        self::renderLegend();
        echo '</div>';
    }

    private static function renderLegend(): void {
        echo '<div class="tt-maud-legend">';
        echo '<span><span class="tt-maud-swatch tt-maud-swatch--min"></span>' . esc_html__( 'minutes recorded', 'talenttrack' ) . '</span>';
        echo '<span><span class="tt-maud-swatch tt-maud-swatch--zero"></span>' . esc_html__( 'in squad, 0 recorded', 'talenttrack' ) . '</span>';
        echo '<span><span class="tt-maud-swatch tt-maud-swatch--na"></span>' . esc_html__( 'not in squad', 'talenttrack' ) . '</span>';
        echo '</div>';
    }

    private static function statusChip( string $status ): string {
        $map = [
            'complete' => [ 'tt-maud-chip--ok',      __( 'Complete',       'talenttrack' ) ],
            'partial'  => [ 'tt-maud-chip--partial', __( 'Incomplete',     'talenttrack' ) ],
            'none'     => [ 'tt-maud-chip--none',    __( 'Not recorded',   'talenttrack' ) ],
        ];
        [ $cls, $label ] = $map[ $status ] ?? $map['none'];
        return '<span class="tt-maud-chip ' . esc_attr( $cls ) . '"><span class="tt-maud-chip__dot"></span>' . esc_html( $label ) . '</span>';
    }

    /**
     * @param array<string,mixed> $pl
     */
    private static function shortName( array $pl ): string {
        $first = (string) ( $pl['first_name'] ?? '' );
        $last  = (string) ( $pl['last_name'] ?? '' );
        $jersey = $pl['jersey_number'] ?? null;
        $name = trim( $first . ' ' . $last );
        if ( $name === '' ) $name = '#' . (int) ( $pl['player_id'] ?? 0 );
        return $jersey !== null ? ( (int) $jersey . ' · ' . $name ) : $name;
    }

    /**
     * Match-type (game_subtype_key) filter options, mirroring the minutes
     * report so both surfaces split League / Cup / Friendly identically.
     *
     * @return array<string,string>
     */
    private static function typeOptions(): array {
        return [
            'all'      => __( 'All types', 'talenttrack' ),
            'League'   => __( 'League',    'talenttrack' ),
            'Cup'      => __( 'Cup',       'talenttrack' ),
            'Friendly' => __( 'Friendly',  'talenttrack' ),
        ];
    }

    /**
     * @param list<object> $teams
     * @param array<string,string> $type_options
     */
    private static function renderFilterForm( array $teams, int $team_id, string $from, string $to, string $type_filter, string $period, array $type_options ): void {
        $dash_url      = RecordLink::dashboardUrl();
        $period_labels = ReportFilters::periodLabels();

        $team_options = [];
        foreach ( $teams as $t ) {
            $team_options[ (string) (int) $t->id ] = (string) $t->name;
        }

        // Match-type options for the select (drop the 'all' sentinel — the
        // shared select uses a placeholder for "all").
        $select_types = $type_options;
        unset( $select_types['all'] );

        $pill_base = [ 'tt_view' => 'minutes-audit', 'team_id' => $team_id ];
        if ( $type_filter !== 'all' )      $pill_base['type']    = $type_filter;
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

        $hidden = [ 'tt_view' => 'minutes-audit' ];
        if ( $period !== '' )              $hidden['period']  = $period;
        if ( ! empty( $_GET['tt_back'] ) ) $hidden['tt_back'] = sanitize_text_field( wp_unslash( (string) $_GET['tt_back'] ) );

        $active_count = 0;
        $chips = [];
        if ( $period !== '' )         { $active_count++; $chips[] = (string) ( $period_labels[ $period ] ?? '' ); }
        // #3293 — a custom window is a filter and was counted nowhere.
        $range_chip = ReportFilters::customRangeChip( $period, $from, $to );
        if ( $range_chip !== null ) { $active_count++; $chips[] = $range_chip; }
        if ( $type_filter !== 'all' && isset( $type_options[ $type_filter ] ) ) { $active_count++; $chips[] = $type_options[ $type_filter ]; }

        $reset_args = [ 'tt_view' => 'minutes-audit', 'team_id' => $team_id ];
        if ( ! empty( $_GET['tt_back'] ) ) $reset_args['tt_back'] = sanitize_text_field( wp_unslash( (string) $_GET['tt_back'] ) );

        // #2385 — personal saved views for this report, above the filter bar.

        FilterBar::render( [
            'hidden'       => $hidden,
            'active_count' => $active_count,
            'chips'        => $chips,
            'reset_url'    => add_query_arg( $reset_args, $dash_url ),
            // #3338 — filter in place (epic #3335). A report is where
            // the pending state earns its keep: the server work is the
            // slow part, not the network hop.
            'refresh'      => true,
            // #2448 — personal saved views, rendered by FilterBar above the bar.
            'saved_views'  => [
                'key'         => 'minutes_audit',
                'base_url'    => $dash_url,
                'base_params' => [ 'tt_view' => 'minutes-audit' ],
            ],
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
                [
                    'type'        => 'select',
                    'key'         => 'type',
                    'label'       => __( 'Match type', 'talenttrack' ),
                    'name'        => 'type',
                    'selected'    => $type_filter === 'all' ? '' : $type_filter,
                    'placeholder' => __( 'All types', 'talenttrack' ),
                    'options'     => $select_types,
                ],
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
                  WHERE club_id = %d
                    AND " . \TT\Infrastructure\Archive\ArchiveRepository::filterClause( 'active' ) . "
                    AND id IN ($placeholders)
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
