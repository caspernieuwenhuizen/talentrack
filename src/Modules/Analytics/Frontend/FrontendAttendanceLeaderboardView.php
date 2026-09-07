<?php
namespace TT\Modules\Analytics\Frontend;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Infrastructure\Query\QueryHelpers;
use TT\Infrastructure\Tenancy\CurrentClub;
use TT\Modules\Analytics\Reports\AttendanceRankingQuery;
use TT\Modules\Analytics\Reports\ReportFilters;
use TT\Shared\Frontend\Components\BackLink;
use TT\Shared\Frontend\Components\FilterBar;
use TT\Shared\Frontend\Components\FrontendAppChrome;
use TT\Shared\Frontend\Components\FrontendBreadcrumbs;
use TT\Shared\Frontend\Components\RecordLink;
use TT\Shared\Frontend\FrontendViewBase;

/**
 * FrontendAttendanceLeaderboardView (#1488) — dedicated attendance
 * leaderboard reachable at `?tt_view=attendance-leaderboard` from the
 * Reports launcher.
 *
 * Two league tables over the window: the **bottom N** (lowest present %
 * — who needs attention) and the **top N** (best attenders). The
 * ranking + the rows themselves come from `AttendanceRankingQuery`, the
 * same service the player report and the REST surface use — the view
 * only composes (CLAUDE.md §4).
 *
 * #2350 — chrome parity with `FrontendAttendancePlayerReportView`:
 * shared FilterBar (team + retrospective period pills + activity-type +
 * date range + the leaderboard's "How many" cap), a
 * `ReportFilters::seasonDefaultWindow()` default, a KPI summary strip
 * (Players / Avg. attendance / At-risk), and the at-risk badge on
 * flagged bottom rows. The KPI values are a presentation-level
 * aggregation of the already-fetched board — no extra query.
 *
 * Cap-gated on `tt_view_analytics`; scope follows the analytics
 * team-scope rule (global-scope read on `activities` sees the club,
 * coaches see their own teams — #1942).
 */
final class FrontendAttendanceLeaderboardView extends FrontendViewBase {

    /**
     * #2205 — 0 means "all players in the window". A blank/unset "How
     * many" field ranks everyone in both columns; a supplied positive
     * number still narrows each column to that many rows.
     */
    private const DEFAULT_N = 0;

    /**
     * #1695 — pull in the 2026 green/gold leaderboard stylesheet (card
     * tables, inline present-% bars, flag chips). Depends on the
     * app-chrome handle the base view registers (which also carries the
     * shared `.tt-report-kpis` KPI-strip grid — #2350).
     */
    protected static function enqueueAssets(): void {
        parent::enqueueAssets();
        wp_enqueue_style(
            'tt-attendance-leaderboard',
            TT_PLUGIN_URL . 'assets/css/frontend-attendance-leaderboard.css',
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

        // #2126 — per-report toggle: reject even a direct link when the
        // Attendance leaderboard has been switched off for this academy.
        if ( ! \TT\Core\FeatureRegistry::isEnabled( 'report_attendance_leaderboard' ) ) {
            FrontendBreadcrumbs::fromDashboard(
                __( 'Attendance leaderboard', 'talenttrack' ),
                [ FrontendBreadcrumbs::viewCrumb( 'reports', __( 'Reports', 'talenttrack' ) ) ]
            );
            echo '<p class="tt-notice">' . esc_html__( 'This report has been switched off for your academy.', 'talenttrack' ) . '</p>';
            return;
        }

        FrontendBreadcrumbs::fromDashboard(
            __( 'Attendance leaderboard', 'talenttrack' ),
            [ FrontendBreadcrumbs::viewCrumb( 'reports', __( 'Reports', 'talenttrack' ) ) ]
        );
        self::renderHeader( __( 'Attendance leaderboard', 'talenttrack' ) );

        $defaults = self::defaultWindow();
        $team_id  = isset( $_GET['team_id'] ) ? absint( $_GET['team_id'] ) : 0;
        // #2205 — blank/unset "How many" means all players in the window
        // (n = 0). A supplied number narrows each column to that many.
        $n = ( isset( $_GET['n'] ) && $_GET['n'] !== '' ) ? absint( $_GET['n'] ) : self::DEFAULT_N;

        // #2350 — retrospective period pills + activity-type filter, shared
        // with the player + team reports via ReportFilters. A manual From/To
        // overrides the active period; the type filter flows into the shared
        // ranking query (which already accepts it).
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

        // #3293 — reassigned here rather than at each call site: this view
        // renders the bar from two places (the tamper-guard early return as
        // well as the main path) and both have to agree with the query.
        $period = ReportFilters::effectivePeriod( $period, (bool) $has_manual_from, (bool) $has_manual_to, $from, $to );

        // #1942 — academy-wide = global-scope read on `activities`; the
        // settings-admin flag stays as the WP-admin fallback.
        $is_scope_admin = $is_admin
            || \TT\Modules\Authorization\AllTeamsScope::canSeeAllTeamsActivities( $user_id );
        $allowed_team_ids = $is_scope_admin
            ? null
            : array_values( array_map( 'intval', array_column( QueryHelpers::get_teams_for_coach( $user_id ), 'id' ) ) );

        if ( ! $is_scope_admin && $allowed_team_ids === [] ) {
            echo '<p class="tt-notice">' . esc_html__( "You don't coach any teams yet, so there is no attendance to rank. Ask an administrator to assign you to a team.", 'talenttrack' ) . '</p>';
            return;
        }

        self::renderFilterForm( $from, $to, $team_id, $n, $allowed_team_ids, $period, $type_key );

        // #3338 — a URL-tamper guard is a result, not an error: a team the
        // coach may not see yields an empty board, and that empty board has
        // to live inside the region so a filter change can replace it.
        $tampered = $allowed_team_ids !== null && $team_id > 0 && ! in_array( $team_id, $allowed_team_ids, true );
        $board    = $tampered
            ? [ 'total' => 0, 'top' => [], 'bottom' => [] ]
            : ( new AttendanceRankingQuery() )->leaderboard( $from, $to, $n, $team_id, $allowed_team_ids, $type_key );

        // #3338 — everything the filters govern. The body's early returns
        // now end the BODY rather than the whole render, which is what keeps
        // the region's closing tag guaranteed.
        // No `?? 0`: the board's shape guarantees `total`, and a second dead
        // null-coalesce here is what the PHPStan baseline counts.
        printf( '<div data-tt-filter-region data-tt-filter-count="%d">', (int) $board['total'] );
        self::renderBody( $board );
        echo '</div>';
    }

    /**
     * The filtered output: KPI strip and the two ranked tables, or the
     * empty state (#3338).
     *
     * The shape is spelled out rather than `array<string,mixed>` so it still
     * satisfies `renderKpiStrip()`, which is typed on the board the ranking
     * query returns.
     *
     * @param array{bottom: array<int, array<string, mixed>>, top: array<int, array<string, mixed>>, total: int} $board
     */
    private static function renderBody( array $board ): void {
        if ( ( $board['total'] ?? 0 ) === 0 ) {
            echo '<p class="tt-notice">' . esc_html__( 'No attendance recorded in the selected window. Try widening the date range or picking another period.', 'talenttrack' ) . '</p>';
            return;
        }

        self::renderKpiStrip( $board );

        echo '<div class="tt-leaderboard-grid">';
        self::renderTable(
            __( 'Needs attention — lowest attendance', 'talenttrack' ),
            $board['bottom'],
            true
        );
        self::renderTable(
            __( 'Most reliable — highest attendance', 'talenttrack' ),
            $board['top'],
            false
        );
        echo '</div>';
    }

    /**
     * #2350 — KPI summary strip, computed from the already-fetched board
     * (presentation-level aggregation only; the ranking query stays the
     * source of truth). Players = ranked total; Avg. attendance = the
     * present-weighted mean across every ranked player; At-risk = flagged
     * count. The `bottom` slice holds every ranked player when n = 0 (the
     * default), so it doubles as the full population for these figures;
     * when n is capped, the strip describes the shown slice.
     *
     * @param array{bottom:list<array<string,mixed>>, top:list<array<string,mixed>>, total:int} $board
     */
    private static function renderKpiStrip( array $board ): void {
        $rows = $board['bottom'];
        $player_count = count( $rows );
        $sum_present = 0; $sum_total = 0; $at_risk_count = 0;
        foreach ( $rows as $r ) {
            $sum_present += (int) $r['present'];
            $sum_total   += (int) $r['total'];
            if ( ! empty( $r['flagged'] ) ) $at_risk_count++;
        }
        $avg = $sum_total > 0 ? number_format_i18n( $sum_present / $sum_total * 100, 1 ) . '%' : '—';

        echo '<div class="tt-report-kpis">';
        // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped — kpiTile() escapes internally.
        echo FrontendAppChrome::kpiTile( [ 'label' => __( 'Players', 'talenttrack' ),         'value' => (string) $player_count ] );
        echo FrontendAppChrome::kpiTile( [ 'label' => __( 'Avg. attendance', 'talenttrack' ), 'value' => $avg ] );
        echo FrontendAppChrome::kpiTile( [ 'label' => __( 'At-risk players', 'talenttrack' ), 'value' => (string) $at_risk_count, 'flag' => $at_risk_count > 0 ? 'red' : 'green' ] );
        // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '</div>';
    }

    /**
     * @param list<array<string,mixed>> $rows
     */
    private static function renderTable( string $heading, array $rows, bool $is_bottom ): void {
        echo '<section class="tt-leaderboard-card">';
        echo '<h2 class="tt-leaderboard-title">' . esc_html( $heading ) . '</h2>';
        if ( $rows === [] ) {
            echo '<p class="tt-notice">' . esc_html__( 'No players to rank yet.', 'talenttrack' ) . '</p>';
            echo '</section>';
            return;
        }
        echo '<div class="tt-table-wrap"><table class="tt-table tt-table-sortable" data-tt-table-search="off" style="width:100%;">';
        echo '<thead><tr>';
        echo '<th style="text-align:right;width:3rem;" data-tt-sort="off">' . esc_html__( '#', 'talenttrack' ) . '</th>';
        echo '<th>' . esc_html__( 'Player', 'talenttrack' ) . '</th>';
        echo '<th>' . esc_html__( 'Team', 'talenttrack' ) . '</th>';
        echo '<th style="text-align:right;">' . esc_html__( 'Activities', 'talenttrack' ) . '</th>';
        echo '<th style="text-align:right;">' . esc_html__( 'Present %', 'talenttrack' ) . '</th>';
        echo '</tr></thead><tbody>';

        $rank = 1;
        foreach ( $rows as $r ) {
            $name = trim( ( (string) $r['first_name'] ) . ' ' . ( (string) $r['last_name'] ) );
            if ( $name === '' ) $name = '#' . (int) $r['player_id'];
            $player_url = BackLink::appendTo( add_query_arg(
                [ 'tt_view' => 'players', 'id' => (int) $r['player_id'] ],
                RecordLink::dashboardUrl()
            ) );
            $team   = (string) $r['team_name'];
            $present_pct = $r['present_pct'] !== null ? (float) $r['present_pct'] : null;
            // #2350 — at-risk badge on flagged bottom rows, matching the
            // player report's inline chip markup for cross-report parity.
            $badge  = '';
            if ( $is_bottom && ! empty( $r['flagged'] ) ) {
                $badge = ' <span class="tt-flag-badge" title="'
                    . esc_attr( sprintf( /* translators: %d missed activities */ __( '%d missed', 'talenttrack' ), (int) $r['missed'] ) )
                    . '">⚠ ' . (int) $r['missed'] . '</span>';
            }
            echo '<tr' . ( $is_bottom && ! empty( $r['flagged'] ) ? ' class="is-flagged"' : '' ) . '>';
            echo '<td style="text-align:right;">' . (int) $rank . '</td>';
            echo '<td><a class="tt-record-link" href="' . esc_url( $player_url ) . '">' . esc_html( $name ) . '</a>' . $badge . '</td>';
            echo '<td>' . ( $team !== '' ? esc_html( $team ) : '<span class="tt-muted">&mdash;</span>' ) . '</td>';
            echo '<td style="text-align:right;">' . (int) $r['activities'] . '</td>';
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — attendanceBar() escapes internally.
            echo '<td>' . self::attendanceBar( $present_pct ) . '</td>';
            echo '</tr>';
            $rank++;
        }
        echo '</tbody></table></div>';
        echo '</section>';
    }

    /**
     * #2350 — Team select + retrospective period pills + activity-type
     * filter + From/To range + the leaderboard-specific "How many" cap,
     * through the shared FilterBar for visual + a11y parity with the
     * player report and the activities list. Pills + period are
     * link-based; Team and Type auto-submit; the date range and the
     * "How many" cap are the manual overrides.
     *
     * @param list<int>|null $allowed_team_ids
     */
    private static function renderFilterForm( string $from, string $to, int $team_id, int $n, ?array $allowed_team_ids, string $period = '', string $type_key = '' ): void {
        global $wpdb;
        if ( $allowed_team_ids !== null ) {
            if ( $allowed_team_ids === [] ) {
                $teams = [];
            } else {
                $placeholders = implode( ',', array_fill( 0, count( $allowed_team_ids ), '%d' ) );
                /** @var object[] $teams */
                $teams = $wpdb->get_results( $wpdb->prepare(
                    "SELECT id, name FROM {$wpdb->prefix}tt_teams
                      WHERE club_id = %d
                        AND " . \TT\Infrastructure\Archive\ArchiveRepository::filterClause( 'active' ) . "
                        AND id IN ($placeholders)
                      ORDER BY name ASC",
                    CurrentClub::id(), ...$allowed_team_ids
                ) );
            }
        } else {
            /** @var object[] $teams */
            $teams = $wpdb->get_results( $wpdb->prepare(
                "SELECT id, name FROM {$wpdb->prefix}tt_teams
                  WHERE club_id = %d AND " . \TT\Infrastructure\Archive\ArchiveRepository::filterClause( 'active' ) . "
                  ORDER BY name ASC",
                CurrentClub::id()
            ) );
        }

        $team_options = [ '0' => __( 'All teams', 'talenttrack' ) ];
        foreach ( (array) $teams as $t ) {
            $team_options[ (string) (int) $t->id ] = (string) $t->name;
        }

        $dash_url      = RecordLink::dashboardUrl();
        $period_labels = ReportFilters::periodLabels();
        $type_options  = ReportFilters::activityTypeOptions();

        // Base args each period pill preserves (team / type / cap / back-target).
        $pill_base = [ 'tt_view' => 'attendance-leaderboard' ];
        if ( $team_id > 0 )                $pill_base['team_id']           = $team_id;
        if ( $type_key !== '' )            $pill_base['activity_type_key'] = $type_key;
        if ( $n > 0 )                      $pill_base['n']                 = $n;
        if ( ! empty( $_GET['tt_back'] ) ) $pill_base['tt_back']           = sanitize_text_field( wp_unslash( (string) $_GET['tt_back'] ) );

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

        // Hidden fields the auto-submitting Team / Type selects must carry
        // so the link-based period + back-target survive a change.
        $hidden = [ 'tt_view' => 'attendance-leaderboard' ];
        if ( $period !== '' )              $hidden['period']  = $period;
        if ( ! empty( $_GET['tt_back'] ) ) $hidden['tt_back'] = sanitize_text_field( wp_unslash( (string) $_GET['tt_back'] ) );

        $active_count = 0;
        $chips = [];
        if ( $team_id > 0 && isset( $team_options[ (string) $team_id ] ) ) { $active_count++; $chips[] = $team_options[ (string) $team_id ]; }
        if ( $period !== '' ) { $active_count++; $chips[] = (string) ( $period_labels[ $period ] ?? '' ); }
        // #3293 — a custom window is a filter and was counted nowhere.
        $range_chip = ReportFilters::customRangeChip( $period, $from, $to );
        if ( $range_chip !== null ) { $active_count++; $chips[] = $range_chip; }
        if ( $type_key !== '' && isset( $type_options[ $type_key ] ) ) { $active_count++; $chips[] = $type_options[ $type_key ]; }
        if ( $n > 0 ) { $active_count++; $chips[] = sprintf( /* translators: %d row cap */ __( 'Top %d', 'talenttrack' ), $n ); }

        $reset_args = [ 'tt_view' => 'attendance-leaderboard' ];
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
                'key'         => 'attendance_leaderboard',
                'base_url'    => $dash_url,
                'base_params' => [ 'tt_view' => 'attendance-leaderboard' ],
            ],
            'groups'       => [
                [
                    'type'     => 'select',
                    'key'      => 'team',
                    'label'    => __( 'Team', 'talenttrack' ),
                    'name'     => 'team_id',
                    'selected' => $team_id > 0 ? (string) $team_id : '0',
                    'options'  => $team_options,
                ],
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
                [
                    // Leaderboard-specific row cap. Rendered as a text group
                    // (FilterBar has no numeric group); inputmode="numeric"
                    // keeps the mobile keypad correct (CLAUDE.md §2). A blank
                    // value means "all ranked players".
                    'type'        => 'text',
                    'key'         => 'howmany',
                    'label'       => __( 'How many', 'talenttrack' ),
                    'name'        => 'n',
                    'value'       => $n > 0 ? (string) $n : '',
                    'placeholder' => __( 'All', 'talenttrack' ),
                    'inputmode'   => 'numeric',
                ],
            ],
        ] );
    }

    /** @return array{from:string,to:string} */
    private static function defaultWindow(): array {
        return ReportFilters::seasonDefaultWindow();
    }

    /**
     * Inline present-% bar — value + a proportional track, red below 70%.
     * Returns escaped HTML. Shares the .tt-att-bar vocabulary with the
     * team + player attendance reports (#1688 / #1695).
     */
    private static function attendanceBar( ?float $pct ): string {
        if ( $pct === null ) {
            return '<span class="tt-att-bar"><span class="v">—</span></span>';
        }
        $low = $pct < 70;
        $w   = max( 0, min( 100, (int) round( $pct ) ) );
        return '<span class="tt-att-bar' . ( $low ? ' is-low' : '' ) . '">'
            . '<span class="v">' . esc_html( number_format_i18n( $pct, 1 ) . '%' ) . '</span>'
            . '<span class="track"><i style="width:' . (int) $w . '%;"></i></span>'
            . '</span>';
    }
}
