<?php
namespace TT\Modules\Analytics\Frontend;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Infrastructure\Query\ActivityLifecycle;
use TT\Infrastructure\Query\QueryHelpers;
use TT\Infrastructure\Tenancy\CurrentClub;
use TT\Modules\Analytics\Reports\ReportFilters;
use TT\Shared\Frontend\Components\BackLink;
use TT\Shared\Frontend\Components\FilterBar;
use TT\Shared\Frontend\Components\FrontendAppChrome;
use TT\Shared\Frontend\Components\FrontendBreadcrumbs;
use TT\Shared\Frontend\Components\RecordLink;
use TT\Shared\Frontend\FrontendViewBase;

/**
 * FrontendAttendanceTeamReportView (v3.110.109) — standard report:
 * team attendance statistics over a date range.
 *
 * Reached via the "Standard reports" section on the central Analytics
 * surface (`?tt_view=analytics`). One row per team; columns: activities
 * in window, present %, absent %, late %, excused %, injured %.
 *
 * Date range filter (default last 90 days) via GET form, plus #2136
 * retrospective period quick-pills (Last week / This month / This season)
 * and an activity-type filter. #2137 — each team row expands inline to a
 * per-player sub-table loaded from `AttendanceRankingQuery::rows()`.
 *
 * Status %s use `LOWER(att.status)` to match the v3.110.78
 * case-insensitivity fix — legacy mixed-case rows aggregate into the same
 * bucket as the current-shape lowercase rows. Only past, actually-held
 * activities count (#2135: `session_date <= CURDATE()`).
 *
 * Cap-gated on `tt_view_analytics` (same as the parent Analytics view).
 * Scope: club-wide.
 */
final class FrontendAttendanceTeamReportView extends FrontendViewBase {

    public static function render( int $user_id, bool $is_admin ): void {
        self::enqueueAssets();
        // #1688 — chrome-aligned look: KPI summary strip + card table + bars.
        wp_enqueue_style( 'tt-attendance-report', TT_PLUGIN_URL . 'assets/css/frontend-attendance-report.css', [ 'tt-frontend-app-chrome' ], TT_VERSION );
        // #2137 — inline drill-down accordion (lazy per-player sub-table).
        wp_enqueue_script( 'tt-attendance-report', TT_PLUGIN_URL . 'assets/js/frontend-attendance-report.js', [], TT_VERSION, true );
        wp_localize_script( 'tt-attendance-report', 'TT_ATTENDANCE_REPORT', [
            'rest_url' => esc_url_raw( rest_url( 'talenttrack/v1/reports/attendance' ) ),
            'nonce'    => wp_create_nonce( 'wp_rest' ),
            'i18n'     => [
                'loading'  => __( 'Loading players…', 'talenttrack' ),
                'error'    => __( 'Could not load players. Try again.', 'talenttrack' ),
                'empty'    => __( 'No player attendance in this window.', 'talenttrack' ),
                // #2893 — distinct from `empty`: one is a fact about the
                // data, the other about the reader.
                'forbidden' => __( 'You do not have access to this team’s players.', 'talenttrack' ),
                'player'   => __( 'Player', 'talenttrack' ),
                'present'  => __( 'Present %', 'talenttrack' ),
                'flagged'  => __( 'At risk', 'talenttrack' ),
            ],
        ] );

        if ( ! current_user_can( 'tt_view_analytics' ) ) {
            FrontendBreadcrumbs::fromDashboard(
                __( 'Not authorized', 'talenttrack' ),
                [ FrontendBreadcrumbs::viewCrumb( 'reports', __( 'Reports', 'talenttrack' ) ) ]
            );
            echo '<p class="tt-notice">' . esc_html__( 'You do not have permission to view this report.', 'talenttrack' ) . '</p>';
            return;
        }

        // #2126 — per-report toggle: reject even a direct link when the
        // Team attendance report has been switched off for this academy.
        if ( ! \TT\Core\FeatureRegistry::isEnabled( 'report_attendance_report_team' ) ) {
            FrontendBreadcrumbs::fromDashboard(
                __( 'Team attendance', 'talenttrack' ),
                [ FrontendBreadcrumbs::viewCrumb( 'reports', __( 'Reports', 'talenttrack' ) ) ]
            );
            echo '<p class="tt-notice">' . esc_html__( 'This report has been switched off for your academy.', 'talenttrack' ) . '</p>';
            return;
        }

        FrontendBreadcrumbs::fromDashboard(
            __( 'Team attendance', 'talenttrack' ),
            [ FrontendBreadcrumbs::viewCrumb( 'reports', __( 'Reports', 'talenttrack' ) ) ]
        );
        self::renderHeader( __( 'Team attendance statistics', 'talenttrack' ) );

        $defaults = self::defaultWindow();
        // #2136 — a period pill resolves to the window unless the user
        // typed an explicit From/To (manual override wins).
        $period   = ReportFilters::sanitizePeriod( isset( $_GET['period'] ) ? sanitize_key( (string) $_GET['period'] ) : '' );
        $type_key = ReportFilters::sanitizeActivityType( isset( $_GET['activity_type_key'] ) ? (string) $_GET['activity_type_key'] : '' );

        $has_manual_from = isset( $_GET['from'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $_GET['from'] );
        $has_manual_to   = isset( $_GET['to'] )   && preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $_GET['to'] );

        $window = $period !== '' ? ReportFilters::periodWindow( $period, gmdate( 'Y-m-d' ) ) : null;
        $from = $has_manual_from
            ? sanitize_text_field( wp_unslash( (string) $_GET['from'] ) )
            : ( $window['from'] ?? $defaults['from'] );
        $to = $has_manual_to
            ? sanitize_text_field( wp_unslash( (string) $_GET['to'] ) )
            : ( $window['to'] ?? $defaults['to'] );

        // #2351 — when neither a period pill nor a manual From/To was chosen,
        // the report silently seeds the season-default window. Surface that as
        // an active "This season" pill instead of a blank "Custom range" when
        // the seeded window actually equals the season window, so the FilterBar
        // reflects the state the user is looking at.
        $effective_period = ReportFilters::effectivePeriod( $period, $has_manual_from, $has_manual_to, $from, $to );

        // v4.20.4 (#1147) — same team-scope pattern as the player report.
        // #1942 — academy-wide = global-scope read on `activities`; the
        // settings-admin flag stays as the WP-admin fallback.
        $is_scope_admin = $is_admin
            || \TT\Modules\Authorization\AllTeamsScope::canSeeAllTeamsActivities( $user_id );
        $allowed_team_ids = $is_scope_admin
            ? null
            : array_values( array_map( 'intval', array_column( QueryHelpers::get_teams_for_coach( $user_id ), 'id' ) ) );

        if ( ! $is_scope_admin && $allowed_team_ids === [] ) {
            echo '<p class="tt-notice">' . esc_html__( "You don't coach any teams yet, so there is no attendance to show. Ask an administrator to assign you to a team.", 'talenttrack' ) . '</p>';
            return;
        }

        self::renderFilterForm( $from, $to, $effective_period, $type_key );

        $rows = self::query( $from, $to, $allowed_team_ids, $type_key );
        if ( $rows === [] ) {
            // #2351 — when the coach only sees teams they're assigned to, say
            // so, so an empty window doesn't read as "the academy has no data".
            $scope_note = ( $allowed_team_ids !== null )
                ? ' ' . esc_html__( 'This report is limited to the teams you coach.', 'talenttrack' )
                : '';
            echo '<p class="tt-notice">' . esc_html__( 'No attendance recorded in the selected window.', 'talenttrack' ) . $scope_note . '</p>';
            return;
        }

        // #1688 — KPI summary strip computed from the already-fetched rows
        // (presentation-level aggregation only; the query stays the source).
        $team_count = count( $rows );
        $sum_activities = 0; $sum_present = 0; $sum_total = 0; $below = 0;
        foreach ( $rows as $r ) {
            $sum_activities += (int) $r->activities;
            $sum_present    += (int) $r->present;
            $sum_total      += (int) $r->total;
            $tp = (int) $r->total > 0 ? ( (int) $r->present / (int) $r->total ) * 100 : null;
            if ( $tp !== null && $tp < 70 ) $below++;
        }
        $avg = $sum_total > 0 ? number_format_i18n( $sum_present / $sum_total * 100, 1 ) . '%' : '—';

        echo '<div class="tt-report-kpis">';
        // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped — kpiTile() escapes internally.
        echo FrontendAppChrome::kpiTile( [ 'label' => __( 'Teams', 'talenttrack' ),          'value' => (string) $team_count ] );
        echo FrontendAppChrome::kpiTile( [ 'label' => __( 'Activities', 'talenttrack' ),      'value' => (string) $sum_activities ] );
        echo FrontendAppChrome::kpiTile( [ 'label' => __( 'Avg. attendance', 'talenttrack' ), 'value' => $avg ] );
        echo FrontendAppChrome::kpiTile( [ 'label' => __( 'Teams below 70%', 'talenttrack' ), 'value' => (string) $below, 'flag' => $below > 0 ? 'red' : 'green' ] );
        // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '</div>';

        echo '<div class="tt-report-card"><div class="tt-table-wrap"><table class="tt-table tt-table-sortable tt-att-team-table" data-tt-att-from="' . esc_attr( $from ) . '" data-tt-att-to="' . esc_attr( $to ) . '" data-tt-att-type="' . esc_attr( $type_key ) . '">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__( 'Team', 'talenttrack' ) . '</th>';
        echo '<th class="tt-num">' . esc_html__( 'Activities', 'talenttrack' ) . '</th>';
        echo '<th class="tt-num">' . esc_html__( 'Present %', 'talenttrack' ) . '</th>';
        echo '<th class="tt-num">' . esc_html__( 'Late %',    'talenttrack' ) . '</th>';
        echo '<th class="tt-num">' . esc_html__( 'Absent %',  'talenttrack' ) . '</th>';
        echo '<th class="tt-num">' . esc_html__( 'Excused %', 'talenttrack' ) . '</th>';
        echo '<th class="tt-num">' . esc_html__( 'Injured %', 'talenttrack' ) . '</th>';
        echo '</tr></thead><tbody>';

        foreach ( $rows as $r ) {
            $team_id = (int) $r->team_id;
            $team_url = BackLink::appendTo( add_query_arg(
                [ 'tt_view' => 'teams', 'id' => $team_id ],
                RecordLink::dashboardUrl()
            ) );
            $name = (string) ( $r->team_name ?? '' );
            if ( $name === '' ) $name = '#' . $team_id;
            $present_pct = (int) $r->total > 0 ? ( (int) $r->present / (int) $r->total ) * 100 : null;

            // #2137 — no-JS fallback: the player report pre-filtered to
            // this team (with the active window + type). JS upgrades the
            // disclosure to an in-place accordion.
            $drill_url = BackLink::appendTo( add_query_arg( array_filter( [
                'tt_view'           => 'attendance-report-player',
                'team_id'           => $team_id,
                'from'              => $from,
                'to'                => $to,
                'activity_type_key' => $type_key !== '' ? $type_key : null,
            ] ), RecordLink::dashboardUrl() ) );

            echo '<tr class="tt-att-team-row" data-tt-att-team="' . esc_attr( (string) $team_id ) . '">';
            echo '<td class="tt-att-team-cell">';
            echo '<button type="button" class="tt-att-disclosure" aria-expanded="false" aria-controls="tt-att-sub-' . esc_attr( (string) $team_id ) . '">';
            echo '<span class="tt-att-disclosure__chev" aria-hidden="true"></span>';
            echo '<span class="tt-att-disclosure__label">' . esc_html( $name ) . '</span>';
            echo '</button>';
            echo '<a class="tt-att-team-link tt-record-link" href="' . esc_url( $team_url ) . '">' . esc_html__( 'Open team', 'talenttrack' ) . '</a>';
            echo ' <a class="tt-att-team-drill" href="' . esc_url( $drill_url ) . '">' . esc_html__( 'View players', 'talenttrack' ) . '</a>';
            echo '</td>';
            echo '<td class="tt-num">' . (int) $r->activities . '</td>';
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — attendanceBar() escapes internally.
            echo '<td>' . self::attendanceBar( $present_pct ) . '</td>';
            echo '<td class="tt-num">' . esc_html( self::pct( $r->late,    $r->total ) ) . '</td>';
            echo '<td class="tt-num">' . esc_html( self::pct( $r->absent,  $r->total ) ) . '</td>';
            echo '<td class="tt-num">' . esc_html( self::pct( $r->excused, $r->total ) ) . '</td>';
            echo '<td class="tt-num">' . esc_html( self::pct( $r->injured, $r->total ) ) . '</td>';
            echo '</tr>';
            // Lazy target row — JS injects the per-player sub-table here.
            // #2893 — `data-tt-table-noop` keeps it out of the table
            // tools' row count. Every team emits one of these, so without
            // it the counter read exactly twice the number of teams.
            echo '<tr class="tt-att-sub-row" data-tt-table-noop="true" id="tt-att-sub-' . esc_attr( (string) $team_id ) . '" hidden>';
            echo '<td colspan="7" class="tt-att-sub-cell"></td>';
            echo '</tr>';
        }
        echo '</tbody></table></div></div>';
    }

    /**
     * Inline attendance bar for the Present % cell — value + a track that
     * fills proportionally, red below 70%. Returns escaped HTML.
     */
    private static function attendanceBar( ?float $pct ): string {
        if ( $pct === null ) {
            return '<span class="tt-att-bar"><span class="v">—</span></span>';
        }
        $low = $pct < 70;
        $w   = max( 0, min( 100, (int) round( $pct ) ) );
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — width is an int, the rest is static.
        return '<span class="tt-att-bar' . ( $low ? ' is-low' : '' ) . '">'
            . '<span class="v">' . esc_html( number_format_i18n( $pct, 1 ) . '%' ) . '</span>'
            . '<span class="track"><i style="width:' . (int) $w . '%;"></i></span>' /* tt-inline-ok */
            . '</span>';
    }

    /**
     * @param list<int>|null $allowed_team_ids null = unrestricted
     * @param string $activity_type_key when non-empty, narrows to one type.
     * @return list<object>  one row per team in the window with raw
     *                        counters; PHP `pct()` does the formatting.
     */
    private static function query( string $from, string $to, ?array $allowed_team_ids, string $activity_type_key = '' ): array {
        global $wpdb;
        $where_scope = '';
        if ( $allowed_team_ids !== null ) {
            if ( $allowed_team_ids === [] ) return [];
            $placeholders = implode( ',', array_fill( 0, count( $allowed_team_ids ), '%d' ) );
            $where_scope  = $wpdb->prepare( " AND t.id IN ($placeholders)", ...$allowed_team_ids );
        }
        // #2136 — optional activity-type narrowing (mirrors the player query).
        $where_type = $activity_type_key !== ''
            ? $wpdb->prepare( ' AND a.activity_type_key = %s', $activity_type_key )
            : '';
        // #2521 — the status the coach set decides whether the session
        // counted; `plan_state` defaults to 'completed' and cannot.
        $completed = ActivityLifecycle::completedClause( 'a' );
        /** @var object[] $rows */
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT
                t.id   AS team_id,
                t.name AS team_name,
                COUNT(DISTINCT a.id) AS activities,
                COUNT(att.id) AS total,
                SUM( CASE WHEN LOWER(att.status) = 'present' THEN 1 ELSE 0 END ) AS present,
                SUM( CASE WHEN LOWER(att.status) = 'late'    THEN 1 ELSE 0 END ) AS late,
                SUM( CASE WHEN LOWER(att.status) = 'absent'  THEN 1 ELSE 0 END ) AS absent,
                SUM( CASE WHEN LOWER(att.status) = 'excused' THEN 1 ELSE 0 END ) AS excused,
                SUM( CASE WHEN LOWER(att.status) = 'injured' THEN 1 ELSE 0 END ) AS injured
              FROM {$wpdb->prefix}tt_teams t
              JOIN {$wpdb->prefix}tt_activities a ON a.team_id = t.id AND a.archived_at IS NULL AND a.trashed_at IS NULL
              JOIN {$wpdb->prefix}tt_attendance att ON att.activity_id = a.id AND att.is_guest = 0
             WHERE t.club_id = %d
               AND att.record_type = 'actual'
               AND a.session_date BETWEEN %s AND %s
               AND {$completed}
               AND a.session_date <= CURDATE()
               {$where_type}
               {$where_scope}
             GROUP BY t.id, t.name
             ORDER BY t.name ASC",
            CurrentClub::id(), $from, $to
        ) );
        return is_array( $rows ) ? $rows : [];
    }

    private static function pct( $part, $total ): string {
        $total = (int) $total;
        if ( $total <= 0 ) return '—';
        return number_format_i18n( ( (int) $part / $total ) * 100, 1 ) . '%';
    }

    /**
     * Default window: current season start through today (90-day fallback).
     * @return array{from:string,to:string}
     */
    private static function defaultWindow(): array {
        return ReportFilters::seasonDefaultWindow();
    }


    /**
     * #2136 — period quick-pills + activity-type filter + From/To range,
     * rendered through the shared FilterBar for visual + a11y parity with
     * the activities list. The pills are link-based (set `?period=`); the
     * type select auto-submits; the date range is the manual override.
     */
    private static function renderFilterForm( string $from, string $to, string $period, string $type_key ): void {
        $dash_url = RecordLink::dashboardUrl();

        $period_labels = ReportFilters::periodLabels();
        $type_options  = ReportFilters::activityTypeOptions();

        // Base args every pill link preserves (type + back-target).
        $pill_base = [ 'tt_view' => 'attendance-report-team' ];
        if ( $type_key !== '' )            $pill_base['activity_type_key'] = $type_key;
        if ( ! empty( $_GET['tt_back'] ) ) $pill_base['tt_back']           = sanitize_text_field( wp_unslash( (string) $_GET['tt_back'] ) );

        $period_options = [];
        foreach ( $period_labels as $key => $label ) {
            $args = $pill_base;
            if ( $key !== '' ) {
                $args['period'] = $key;
            }
            // Picking a pill drops any manual From/To so the window follows.
            $period_options[] = [
                'value'  => $key,
                'label'  => $label,
                'url'    => add_query_arg( $args, $dash_url ),
                'active' => ( $period === $key ),
            ];
        }

        // Hidden fields the auto-submitting Type select must carry so the
        // link-based period + back-target survive a type change.
        $hidden = [ 'tt_view' => 'attendance-report-team' ];
        if ( $period !== '' )              $hidden['period']  = $period;
        if ( ! empty( $_GET['tt_back'] ) ) $hidden['tt_back'] = sanitize_text_field( wp_unslash( (string) $_GET['tt_back'] ) );

        $active_count = 0;
        $chips = [];
        if ( $period !== '' ) { $active_count++; $chips[] = (string) ( $period_labels[ $period ] ?? '' ); }
        // #3293 — a custom window is a filter and was counted nowhere.
        $range_chip = ReportFilters::customRangeChip( $period, $from, $to );
        if ( $range_chip !== null ) { $active_count++; $chips[] = $range_chip; }
        if ( $type_key !== '' && isset( $type_options[ $type_key ] ) ) { $active_count++; $chips[] = $type_options[ $type_key ]; }

        $reset_args = [ 'tt_view' => 'attendance-report-team' ];
        if ( ! empty( $_GET['tt_back'] ) ) $reset_args['tt_back'] = sanitize_text_field( wp_unslash( (string) $_GET['tt_back'] ) );

        // #2385 — personal saved views for this report, above the filter bar.

        FilterBar::render( [
            'hidden'       => $hidden,
            'active_count' => $active_count,
            'chips'        => $chips,
            'reset_url'    => add_query_arg( $reset_args, $dash_url ),
            // #2448 — personal saved views, rendered by FilterBar above the bar.
            'saved_views'  => [
                'key'         => 'attendance_team',
                'base_url'    => $dash_url,
                'base_params' => [ 'tt_view' => 'attendance-report-team' ],
            ],
            'groups'       => [
                // #3331 — one time control: the presets and the custom
                // From/To answer the same question, so they are one
                // control. The standalone date_range group is gone.
                ReportFilters::periodGroup( $period_options, $period, $from, $to ),
                [
                    'type'        => 'select',
                    'key'         => 'type',
                    'label'       => __( 'Type', 'talenttrack' ),
                    'name'        => 'activity_type_key',
                    'selected'    => $type_key,
                    'placeholder' => __( '— all types —', 'talenttrack' ),
                    'options'     => $type_options,
                ],
            ],
        ] );
    }
}
