<?php
namespace TT\Modules\Activities\Frontend;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Infrastructure\Query\QueryHelpers;
use TT\Infrastructure\Tenancy\CurrentClub;
use TT\Modules\Activities\Reports\AttendanceGridQuery;
use TT\Modules\Analytics\Reports\ReportFilters;
use TT\Shared\Frontend\Components\FilterBar;
use TT\Shared\Frontend\Components\FrontendBreadcrumbs;
use TT\Shared\Frontend\Components\RecordLink;
use TT\Shared\Frontend\FrontendViewBase;

/**
 * FrontendAttendanceGridView (#2382) — the desktop attendance-entry grid
 * (epic #2381). The Excel-familiar alternative to the mark-attendance
 * wizard: players in rows, activities (training + matches) in columns, one
 * dropdown per cell, one explicit Save for the whole period.
 *
 * URL: `?tt_view=attendance-grid&team_id=N&from=YYYY-MM-DD&to=YYYY-MM-DD&type=all|training|match`
 *
 * Write-capable, so it gates on `tt_edit_activities` — the SAME capability
 * the bulk-write endpoint (`POST /attendance/bulk`) enforces, so the
 * affordance and the endpoint can't drift (CLAUDE.md §7). Rejected even via
 * a direct link when the `attendance_grid` feature is switched off.
 *
 * Desktop-first surface: the wizard stays the mobile/pitch path (§2). The
 * grid reads/writes the same `record_type='actual'` non-guest attendance
 * rows the reports sum, so the grid, the wizard and the reports reconcile.
 */
final class FrontendAttendanceGridView extends FrontendViewBase {

    protected static function enqueueAssets(): void {
        parent::enqueueAssets();
        wp_enqueue_style(
            'tt-frontend-attendance-grid',
            TT_PLUGIN_URL . 'assets/css/frontend-attendance-grid.css',
            [ 'tt-frontend-app-chrome' ],
            TT_VERSION
        );
        wp_enqueue_script(
            'tt-frontend-attendance-grid',
            TT_PLUGIN_URL . 'assets/js/frontend-attendance-grid.js',
            [],
            TT_VERSION,
            true
        );
        wp_localize_script( 'tt-frontend-attendance-grid', 'TTAttendanceGrid', [
            'restBulk' => esc_url_raw( rest_url( 'talenttrack/v1/attendance/bulk' ) ),
            'nonce'    => wp_create_nonce( 'wp_rest' ),
            'statuses' => self::statusesForJs(),
            'i18n'     => [
                'saving'    => __( 'Saving…', 'talenttrack' ),
                'saved'     => __( 'All changes saved', 'talenttrack' ),
                'error'     => __( 'Could not save — try again', 'talenttrack' ),
                'noChanges' => __( 'No unsaved changes', 'talenttrack' ),
                /* translators: %d is the number of unsaved cell changes. */
                'unsaved'   => __( '%d unsaved change(s)', 'talenttrack' ),
                /* translators: %d is the number of activities marked completed. */
                'completed' => __( 'All changes saved · %d activity/activities marked completed', 'talenttrack' ),
                // #2521 — the confirmation shown before a save that will
                // change an activity's status. Nothing is written until the
                // coach has read which sessions are affected and why.
                'completeTitle'   => __( 'These activities will be marked completed', 'talenttrack' ),
                'completeIntro'   => __( 'You recorded attendance on activities that are still planned. Saving marks them completed, because the attendance reports only count activities that have been held — left as planned, this attendance would never reach the reports.', 'talenttrack' ),
                'completeOutro'   => __( 'You can reopen an activity afterwards if you marked it by mistake. Activities dated in the future and cancelled activities are never changed.', 'talenttrack' ),
                'completeConfirm' => __( 'Save and mark completed', 'talenttrack' ),
                'completeCancel'  => __( 'Back to the grid', 'talenttrack' ),
                'confirm'   => __( 'You have unsaved changes. Leave without saving?', 'talenttrack' ),
                // #3337 — the same question for an in-place filter change,
                // which is not a navigation and so never reaches
                // `beforeunload`. Worded for what actually happens: the page
                // is not being left, the grid underneath is being replaced.
                'confirm_filter' => __( 'You have unsaved changes. Change the filter and lose them?', 'talenttrack' ),
            ],
        ] );
    }

    public static function render( int $user_id, bool $is_admin ): void {
        self::enqueueAssets();

        if ( ! current_user_can( 'tt_edit_activities' ) ) {
            self::crumbs();
            echo '<p class="tt-notice">' . esc_html__( 'You do not have permission to record attendance.', 'talenttrack' ) . '</p>';
            return;
        }

        if ( ! \TT\Core\FeatureRegistry::isEnabled( 'attendance_grid' ) ) {
            self::crumbs();
            echo '<p class="tt-notice">' . esc_html__( 'The attendance grid has been switched off for your academy.', 'talenttrack' ) . '</p>';
            return;
        }

        // #3107 — the grid is Pro, and the note matters more here than
        // anywhere else in this epic: attendance is still recordable, one
        // activity at a time, on every plan. What Standard loses is the
        // fast desktop way to enter a squad's worth at once — not the
        // ability to record attendance. A panel that did not say so would
        // read as "attendance is a paid feature", which is false.
        if ( ! \TT\Modules\License\LicenseGate::allows( 'attendance_grid' ) ) {
            self::crumbs();
            echo \TT\Modules\License\UpgradePanel::render( 'attendance_grid', [ // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — UpgradePanel returns escaped HTML
                'note' => __( 'Attendance itself is not affected: you can still mark a squad present from the activity, one activity at a time. The grid is the faster desktop way to do a whole week at once.', 'talenttrack' ),
            ] );
            return;
        }

        self::crumbs();
        self::renderHeader( __( 'Attendance grid', 'talenttrack' ) );
        echo '<p class="tt-agrid-lead">' . esc_html__(
            'Record attendance for a whole period at once. Rows are players, columns are activities; pick a status per cell and Save. This is the desktop alternative to the step-by-step wizard.',
            'talenttrack'
        ) . '</p>';

        self::renderModeNav();

        // Team scope mirrors the activities + minutes surfaces: academy-wide
        // roles see every team, coaches only the teams they coach.
        $is_scope_admin = $is_admin
            || \TT\Modules\Authorization\AllTeamsScope::canSeeAllTeamsActivities( $user_id );
        $allowed_team_ids = $is_scope_admin
            ? null
            : array_values( array_map( 'intval', array_column( QueryHelpers::get_teams_for_coach( $user_id ), 'id' ) ) );

        if ( ! $is_scope_admin && $allowed_team_ids === [] ) {
            echo '<p class="tt-notice">' . esc_html__( "You don't coach any teams yet, so there is no attendance to record. Ask an administrator to assign you to a team.", 'talenttrack' ) . '</p>';
            return;
        }

        $teams = self::listTeams( $allowed_team_ids );
        if ( empty( $teams ) ) {
            echo '<p class="tt-notice">' . esc_html__( 'No teams yet — add one to record attendance.', 'talenttrack' ) . '</p>';
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

        // Window: season default unless a period pill or manual From/To wins,
        // matching the sibling reports.
        $defaults = ReportFilters::seasonDefaultWindow();
        $period = ReportFilters::sanitizePeriod( isset( $_GET['period'] ) ? sanitize_key( (string) $_GET['period'] ) : '' );
        $has_manual_from = isset( $_GET['from'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $_GET['from'] );
        $has_manual_to   = isset( $_GET['to'] )   && preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $_GET['to'] );
        $window = $period !== '' ? ReportFilters::periodWindow( $period, gmdate( 'Y-m-d' ) ) : null;
        $from = $has_manual_from ? sanitize_text_field( wp_unslash( (string) $_GET['from'] ) ) : ( $window['from'] ?? $defaults['from'] );
        $to   = $has_manual_to   ? sanitize_text_field( wp_unslash( (string) $_GET['to'] ) )   : ( $window['to'] ?? $defaults['to'] );

        $type_options = self::typeOptions();
        $type_filter  = isset( $_GET['type'] ) ? sanitize_key( (string) wp_unslash( $_GET['type'] ) ) : 'all';
        if ( $type_filter === '' || ! isset( $type_options[ $type_filter ] ) ) $type_filter = 'all';

        // #3293 — the bar gets the period the QUERY ran on, not the raw
        // param. A manual From/To wins above; handing the raw `$period` on
        // left the pill reading "This month" over a custom window.
        $period = ReportFilters::effectivePeriod( $period, (bool) $has_manual_from, (bool) $has_manual_to, $from, $to );

        self::renderFilterForm( $teams, $team_id, $from, $to, $type_filter, $period, $type_options );

        $matrix = ( new AttendanceGridQuery() )->matrix( $team_id, $from, $to, $type_filter );

        // #3337 — everything the filters govern, both empty states included.
        printf(
            '<div data-tt-filter-region data-tt-filter-count="%d">',
            (int) $matrix['summary']['total_players']
        );

        if ( $matrix['summary']['total_players'] === 0 ) {
            echo '<p class="tt-notice">' . esc_html__( 'No active players on this team yet. Add players to the roster first.', 'talenttrack' ) . '</p>';
            echo '</div>';
            return;
        }
        if ( $matrix['summary']['total_activities'] === 0 ) {
            echo '<p class="tt-notice">' . esc_html__( 'No activities for this team in the selected window. Widen the date range or change the type filter.', 'talenttrack' ) . '</p>';
            echo '</div>';
            return;
        }

        self::renderGrid( $matrix, $team_id );
        echo '</div>'; // #3337 — /.tt-filter-region
    }

    /**
     * Segmented Attendance | Minutes toggle across the two grid surfaces.
     *
     * #2822 — this is a **mode switcher, not a record-scoped tab strip**, so
     * it is exempt from CLAUDE.md §5c's `RecordSpine` requirement: the grid
     * has no record subject, and these options change what the screen shows
     * rather than which facet of one record you are on. The debt the rule
     * was written against — two surfaces hand-rolling the same switcher — is
     * settled by the shared `SegmentedControl` instead.
     */
    private static function renderModeNav(): void {
        $dash = RecordLink::dashboardUrl();
        $team = isset( $_GET['team_id'] ) ? absint( $_GET['team_id'] ) : 0;
        $min_args = [ 'tt_view' => 'minutes-grid' ]; /* tt-xview-ok — sibling grid, gated by the minutes_grid feature below */
        if ( $team > 0 ) $min_args['team_id'] = $team;

        $options = [ [ 'label' => __( 'Attendance', 'talenttrack' ), 'current' => true ] ];
        if ( \TT\Core\FeatureRegistry::isEnabled( 'minutes_grid' ) ) {
            $options[] = [
                'label' => __( 'Minutes', 'talenttrack' ),
                'url'   => add_query_arg( $min_args, $dash ),
            ];
        }

        \TT\Shared\Frontend\Components\SegmentedControl::render( [
            'label'   => _x( 'Grid', 'segmented control label: which of the two data-entry grids', 'talenttrack' ),
            'options' => $options,
        ] );
    }

    /** Canonical breadcrumb chain — used on every code path (§5). */
    private static function crumbs(): void {
        FrontendBreadcrumbs::fromDashboard(
            __( 'Attendance grid', 'talenttrack' ),
            [ FrontendBreadcrumbs::viewCrumb( 'activities', __( 'Activities', 'talenttrack' ) ) ]
        );
    }

    /**
     * The five attendance statuses + the empty sentinel, each with the
     * dropdown label and the compact cell abbreviation. Shared by the
     * server render and the JS (localised once).
     *
     * @return list<array{value:string,label:string,short:string,mod:string}>
     */
    private static function statuses(): array {
        return [
            [ 'value' => '',        'label' => __( '—', 'talenttrack' ),          'short' => _x( '·',  'attendance status abbreviation: not set', 'talenttrack' ), 'mod' => 'empty' ],
            [ 'value' => 'present', 'label' => __( 'Present', 'talenttrack' ),    'short' => _x( 'P',  'attendance status abbreviation: present', 'talenttrack' ), 'mod' => 'present' ],
            [ 'value' => 'late',    'label' => __( 'Late', 'talenttrack' ),       'short' => _x( 'L',  'attendance status abbreviation: late', 'talenttrack' ),    'mod' => 'late' ],
            [ 'value' => 'absent',  'label' => __( 'Absent', 'talenttrack' ),     'short' => _x( 'A',  'attendance status abbreviation: absent', 'talenttrack' ),  'mod' => 'absent' ],
            [ 'value' => 'excused', 'label' => __( 'Excused', 'talenttrack' ),    'short' => _x( 'E',  'attendance status abbreviation: excused', 'talenttrack' ), 'mod' => 'excused' ],
            [ 'value' => 'injured', 'label' => __( 'Injured', 'talenttrack' ),    'short' => _x( 'I',  'attendance status abbreviation: injured', 'talenttrack' ), 'mod' => 'injured' ],
        ];
    }

    /** @return list<array{value:string,label:string,short:string,mod:string}> */
    private static function statusesForJs(): array {
        return self::statuses();
    }

    /**
     * @param array<string,mixed> $matrix
     */
    private static function renderGrid( array $matrix, int $team_id ): void {
        /** @var list<array<string,mixed>> $players */
        $players = $matrix['players'];
        /** @var list<array<string,mixed>> $activities */
        $activities = $matrix['activities'];
        /** @var array<int,array<int,string>> $cells */
        $cells = $matrix['cells'];

        $statuses = self::statuses();
        $abbr = [];
        $mods = [];
        foreach ( $statuses as $s ) {
            $abbr[ $s['value'] ] = $s['short'];
            $mods[ $s['value'] ] = $s['mod'];
        }

        echo '<div class="tt-agrid-card">';
        echo '<div class="tt-agrid-card__head">';
        echo '<h2 class="tt-agrid-card__title">' . esc_html__( 'Attendance', 'talenttrack' ) . '</h2>';
        echo '<span class="tt-agrid-card__hint">' . esc_html__( 'pick a status per cell, then Save', 'talenttrack' ) . '</span>';
        echo '</div>';

        echo '<div class="tt-agrid-scroll">';
        echo '<table class="tt-agrid">';

        // Header: player column + one column per activity + a rate column.
        echo '<thead><tr>';
        echo '<th class="tt-agrid__player" scope="col">' . esc_html__( 'Player', 'talenttrack' ) . '</th>';
        foreach ( $activities as $a ) {
            $aid   = (int) $a['activity_id'];
            $date  = $a['session_date'] !== '' ? date_i18n( 'j M', strtotime( (string) $a['session_date'] ) ) : '';
            // #2993 — icon-set glyph, not an emoji.
            $glyph = $a['is_match'] ? 'football' : 'training-cone';
            $label = (string) $a['title'] !== '' ? (string) $a['title'] : ( $a['is_match'] ? __( 'Match', 'talenttrack' ) : __( 'Training', 'talenttrack' ) );
            $cls   = 'tt-agrid__act' . ( $a['is_match'] ? ' is-match' : '' );
            // #2521 — a past-dated session that is still planned will be
            // marked completed when this grid is saved. Carry that fact and
            // the column's label into the DOM so the save step can name
            // exactly which activities are about to change status.
            $completes = ! empty( $a['completes'] );
            if ( $completes ) $cls .= ' is-incomplete';
            echo '<th class="' . esc_attr( $cls ) . '" scope="col" title="' . esc_attr( $label ) . '"'
                . ' data-activity="' . esc_attr( (string) $aid ) . '"'
                . ' data-completes="' . ( $completes ? '1' : '0' ) . '"'
                . ' data-label="' . esc_attr( trim( $date . ' · ' . $label, ' ·' ) ) . '">';
            echo '<span class="tt-agrid__act-date">' . esc_html( $date ) . '</span>';
            echo '<span class="tt-agrid__act-type" aria-hidden="true">'
                . \TT\Shared\Icons\IconRenderer::render( $glyph, [ 'width' => 14, 'height' => 14 ] ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — trusted SVG.
                . '</span>';
            echo '<button type="button" class="tt-agrid__fill" data-activity="' . esc_attr( (string) $aid ) . '">' . esc_html__( 'all present', 'talenttrack' ) . '</button>';
            echo '</th>';
        }
        echo '<th class="tt-agrid__rate" scope="col">' . esc_html__( 'Present %', 'talenttrack' ) . '</th>';
        echo '</tr></thead><tbody>';

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

            $attended = 0;
            $recorded = 0;
            foreach ( $activities as $a ) {
                $aid   = (int) $a['activity_id'];
                $value = $cells[ $pid ][ $aid ] ?? '';
                if ( ! isset( $abbr[ $value ] ) ) $value = '';
                if ( $value !== '' ) {
                    $recorded++;
                    if ( $value === 'present' || $value === 'late' ) $attended++;
                }
                $mod = $mods[ $value ] ?? 'empty';
                echo '<td class="tt-agrid-cell tt-agrid-cell--' . esc_attr( $mod ) . '" data-player="' . esc_attr( (string) $pid ) . '" data-activity="' . esc_attr( (string) $aid ) . '">';
                echo '<select class="tt-agrid-sel" data-player="' . esc_attr( (string) $pid ) . '" data-activity="' . esc_attr( (string) $aid ) . '" aria-label="' . esc_attr( sprintf(
                    /* translators: 1: player name, 2: activity date. */
                    __( 'Attendance for %1$s on %2$s', 'talenttrack' ),
                    $name,
                    $a['session_date'] !== '' ? date_i18n( 'j M', strtotime( (string) $a['session_date'] ) ) : ''
                ) ) . '">';
                foreach ( $statuses as $s ) {
                    echo '<option value="' . esc_attr( $s['value'] ) . '"' . selected( $value, $s['value'], false ) . '>' . esc_html( $s['label'] ) . '</option>';
                }
                echo '</select>';
                echo '<span class="tt-agrid-abbr" aria-hidden="true">' . esc_html( $value !== '' ? (string) $abbr[ $value ] : (string) $abbr[''] ) . '</span>';
                echo '</td>';
            }

            $rate = $recorded > 0 ? (int) round( $attended / $recorded * 100 ) : null;
            echo '<td class="tt-agrid__rate">' . ( $rate !== null ? esc_html( (string) $rate . '%' ) : '—' ) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table></div>';

        self::renderLegend( $statuses );

        // Save + Cancel (§6). Save is JS-driven (bulk upsert); Cancel returns
        // to the activities list (or the tt_back target when present).
        // Cancel is a form action (§6), not a discovery affordance: the user
        // is editing activities and reached the grid from the activities list,
        // so activities access is a given. tt-xview-ok escapes the gate.
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
     * @param list<array{value:string,label:string,short:string,mod:string}> $statuses
     */
    private static function renderLegend( array $statuses ): void {
        echo '<div class="tt-agrid-legend">';
        foreach ( $statuses as $s ) {
            if ( $s['value'] === '' ) continue;
            echo '<span><span class="tt-agrid-swatch tt-agrid-cell--' . esc_attr( $s['mod'] ) . '">' . esc_html( $s['short'] ) . '</span>' . esc_html( $s['label'] ) . '</span>';
        }
        echo '</div>';
    }

    /** @return array<string,string> */
    private static function typeOptions(): array {
        return [
            'all'      => __( 'Training + matches', 'talenttrack' ),
            'training' => __( 'Training only', 'talenttrack' ),
            'match'    => __( 'Matches only', 'talenttrack' ),
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

        $select_types = $type_options;
        unset( $select_types['all'] );

        $pill_base = [ 'tt_view' => 'attendance-grid', 'team_id' => $team_id ];
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

        $hidden = [ 'tt_view' => 'attendance-grid' ];
        if ( $period !== '' )              $hidden['period']  = $period;
        if ( ! empty( $_GET['tt_back'] ) ) $hidden['tt_back'] = sanitize_text_field( wp_unslash( (string) $_GET['tt_back'] ) );

        $active_count = 0;
        $chips = [];
        if ( $period !== '' )         { $active_count++; $chips[] = (string) ( $period_labels[ $period ] ?? '' ); }
        // #3293 — a custom window is a filter, and was counted nowhere: a
        // reader who set only a From/To saw "Filters" with no badge and no
        // chip over a grid that was filtered.
        $range_chip = ReportFilters::customRangeChip( $period, $from, $to );
        if ( $range_chip !== null ) { $active_count++; $chips[] = $range_chip; }
        if ( $type_filter !== 'all' && isset( $type_options[ $type_filter ] ) ) { $active_count++; $chips[] = $type_options[ $type_filter ]; }

        $reset_args = [ 'tt_view' => 'attendance-grid', 'team_id' => $team_id ];
        if ( ! empty( $_GET['tt_back'] ) ) $reset_args['tt_back'] = sanitize_text_field( wp_unslash( (string) $_GET['tt_back'] ) );

        FilterBar::render( [
            'hidden'       => $hidden,
            'active_count' => $active_count,
            'chips'        => $chips,
            'reset_url'    => add_query_arg( $reset_args, $dash_url ),
            // #3337 — filter in place (epic #3335). This grid holds unsaved
            // cell edits behind an explicit Save, so `frontend-attendance-grid.js`
            // registers a guard: a filter change on a dirty grid asks first,
            // and a declined answer leaves both the grid and the control alone.
            'refresh'      => true,
            'groups'       => [
                [
                    'type'     => 'select',
                    'key'      => 'team',
                    'label'    => __( 'Team', 'talenttrack' ),
                    'name'     => 'team_id',
                    'selected' => (string) $team_id,
                    'options'  => $team_options,
                ],
                // #3331 — one time control. The presets and the custom
                // From/To are the same question, so they are the same
                // control; the standalone date_range group is gone.
                ReportFilters::periodGroup( $period_options, $period, $from, $to ),
                [
                    'type'        => 'select',
                    'key'         => 'type',
                    'label'       => __( 'Type', 'talenttrack' ),
                    'name'        => 'type',
                    'selected'    => $type_filter === 'all' ? '' : $type_filter,
                    'placeholder' => __( 'Training + matches', 'talenttrack' ),
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
