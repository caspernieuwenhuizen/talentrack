<?php
namespace TT\Modules\Analytics\Frontend;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Infrastructure\Query\QueryHelpers;
use TT\Infrastructure\Tenancy\CurrentClub;
use TT\Modules\Analytics\Domain\AttendanceFlagService;
use TT\Modules\Analytics\Reports\AttendanceRankingQuery;
use TT\Modules\Analytics\Reports\ReportFilters;
use TT\Shared\Frontend\Components\BackLink;
use TT\Shared\Frontend\Components\FilterBar;
use TT\Shared\Frontend\Components\FrontendAppChrome;
use TT\Shared\Frontend\Components\FrontendBreadcrumbs;
use TT\Shared\Frontend\Components\RecordLink;
use TT\Shared\Frontend\FrontendViewBase;

/**
 * FrontendAttendancePlayerReportView (v3.110.109) — standard report:
 * player attendance statistics over a date range, optionally scoped
 * to a single team.
 *
 * Reached via the "Standard reports" section on the central Analytics
 * surface. One row per player; columns: activities in window, present
 * %, absent %, late %, excused %, injured %. Click-through to the
 * player profile.
 *
 * Status lookups use `LOWER(att.status)` for case insensitivity
 * (legacy mixed-case rows aggregate into the same bucket as the
 * v3.110.78-onward lowercase rows). Cap-gated on `tt_view_analytics`.
 * Scope: club-wide, narrowed by an optional `team_id` filter.
 */
final class FrontendAttendancePlayerReportView extends FrontendViewBase {

    /**
     * #1695 — pull in the 2026 green/gold report stylesheet (KPI strip,
     * card table, inline attendance bars). Depends on the app-chrome
     * handle the base view registers.
     */
    protected static function enqueueAssets(): void {
        parent::enqueueAssets();
        wp_enqueue_style(
            'tt-attendance-player-report',
            TT_PLUGIN_URL . 'assets/css/frontend-attendance-player-report.css',
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
        // Player attendance report has been switched off for this academy.
        if ( ! \TT\Core\FeatureRegistry::isEnabled( 'report_attendance_report_player' ) ) {
            FrontendBreadcrumbs::fromDashboard(
                __( 'Player attendance', 'talenttrack' ),
                [ FrontendBreadcrumbs::viewCrumb( 'reports', __( 'Reports', 'talenttrack' ) ) ]
            );
            echo '<p class="tt-notice">' . esc_html__( 'This report has been switched off for your academy.', 'talenttrack' ) . '</p>';
            return;
        }

        FrontendBreadcrumbs::fromDashboard(
            __( 'Player attendance', 'talenttrack' ),
            [ FrontendBreadcrumbs::viewCrumb( 'reports', __( 'Reports', 'talenttrack' ) ) ]
        );
        self::renderHeader( __( 'Player attendance statistics', 'talenttrack' ) );

        $defaults = self::defaultWindow();
        $team_id  = isset( $_GET['team_id'] ) ? absint( $_GET['team_id'] ) : 0;
        // #2136 — retrospective period pills + activity-type filter, shared
        // with the team report via ReportFilters. A manual From/To overrides
        // the active period; the type filter flows into the shared query.
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

        // #2351 — parity with the team report: surface the silent season
        // default as an active "This season" pill rather than a blank "Custom
        // range" when the seeded window equals the current season window.
        $effective_period = ReportFilters::effectivePeriod( $period, $has_manual_from, $has_manual_to, $from, $to );

        // v4.20.4 (#1147) — analytics scope honours the user's team
        // assignment. Global-read-on-`activities` holders keep the
        // club-wide view; everyone else (notably AC, scoped to
        // `team` per the #1060 trim) only ever sees teams they coach.
        // Routed through `QueryHelpers::get_teams_for_coach()` — the
        // same resolver the players list + teamplanner use, so analytics
        // can't drift from the rest of the app. #1942 — academy-wide =
        // global-scope read on `activities`; the settings-admin flag
        // stays as the WP-admin fallback.
        $is_scope_admin = $is_admin
            || \TT\Modules\Authorization\AllTeamsScope::canSeeAllTeamsActivities( $user_id );
        $allowed_team_ids = $is_scope_admin
            ? null
            : array_values( array_map( 'intval', array_column( QueryHelpers::get_teams_for_coach( $user_id ), 'id' ) ) );

        if ( ! $is_scope_admin && $allowed_team_ids === [] ) {
            echo '<p class="tt-notice">' . esc_html__( "You don't coach any teams yet, so there is no attendance to show. Ask an administrator to assign you to a team.", 'talenttrack' ) . '</p>';
            return;
        }

        // If user picked a team they're not allowed to see, fall through
        // to empty — no row leak via URL tampering.
        if ( $allowed_team_ids !== null && $team_id > 0 && ! in_array( $team_id, $allowed_team_ids, true ) ) {
            self::renderFilterForm( $from, $to, $team_id, $allowed_team_ids, $effective_period, $type_key );
            echo '<p class="tt-notice">' . esc_html__( 'No attendance recorded in the selected window.', 'talenttrack' )
                . ( $allowed_team_ids !== null ? ' ' . esc_html__( 'This report is limited to the teams you coach.', 'talenttrack' ) : '' )
                . '</p>';
            return;
        }

        self::renderFilterForm( $from, $to, $team_id, $allowed_team_ids, $effective_period, $type_key );

        // #1488 — ranking, the missed count, and the at-risk flag all
        // come from the shared AttendanceRankingQuery so the report, the
        // leaderboard, the REST surface, and the Comms cron can never
        // drift. Rows arrive worst-attendance-first; the table stays
        // client-side sortable on any column on top of that default.
        $rows = ( new AttendanceRankingQuery() )->rows( $from, $to, $team_id, $allowed_team_ids, $type_key );

        // #3338 — everything the filters govern, empty state included, so a
        // filter that finds nothing replaces the tables rather than leaving
        // the previous window's on screen.
        printf( '<div data-tt-filter-region data-tt-filter-count="%d">', count( $rows ) );

        if ( $rows === [] ) {
            echo '<p class="tt-notice">' . esc_html__( 'No attendance recorded in the selected window.', 'talenttrack' )
                . ( $allowed_team_ids !== null ? ' ' . esc_html__( 'This report is limited to the teams you coach.', 'talenttrack' ) : '' )
                . '</p>';
            echo '</div>';
            return;
        }

        $threshold = AttendanceFlagService::threshold();
        $at_risk   = array_values( array_filter( $rows, static fn( array $r ): bool => ! empty( $r['flagged'] ) ) );

        // #1695 — KPI summary strip computed from the already-fetched rows
        // (presentation-level aggregation only; the query stays the source).
        $player_count = count( $rows );
        $sum_present = 0; $sum_total = 0;
        foreach ( $rows as $r ) {
            $sum_present += (int) $r['present'];
            $sum_total   += (int) $r['total'];
        }
        $avg = $sum_total > 0 ? number_format_i18n( $sum_present / $sum_total * 100, 1 ) . '%' : '—';
        $at_risk_count = count( $at_risk );

        echo '<div class="tt-report-kpis">';
        // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped — kpiTile() escapes internally.
        echo FrontendAppChrome::kpiTile( [ 'label' => __( 'Players', 'talenttrack' ),         'value' => (string) $player_count ] );
        echo FrontendAppChrome::kpiTile( [ 'label' => __( 'Avg. attendance', 'talenttrack' ), 'value' => $avg ] );
        echo FrontendAppChrome::kpiTile( [ 'label' => __( 'At-risk players', 'talenttrack' ), 'value' => (string) $at_risk_count, 'flag' => $at_risk_count > 0 ? 'red' : 'green' ] );
        // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '</div>';

        if ( $at_risk !== [] ) {
            usort( $at_risk, static fn( $a, $b ) => (int) $b['missed'] <=> (int) $a['missed'] );
            echo '<div class="tt-atrisk-card">';
            echo '<h3 class="tt-atrisk-card__title">' . esc_html( sprintf(
                /* translators: %d is the missed-activities threshold */
                __( 'At-risk players (%d or more missed)', 'talenttrack' ),
                $threshold
            ) ) . '</h3>';
            echo '<ul class="tt-atrisk-list">';
            foreach ( $at_risk as $r ) {
                $nm = trim( ( (string) $r['first_name'] ) . ' ' . ( (string) $r['last_name'] ) );
                if ( $nm === '' ) $nm = '#' . (int) $r['player_id'];
                // #2351 — mirror the inline-badge drill-down: the summary-card
                // row links to this player's missed-activities list too.
                $card_args = [
                    'tt_view'   => 'activities',
                    'player_id' => (int) $r['player_id'],
                    'date_from' => $from,
                    'date_to'   => $to,
                ];
                if ( $team_id > 0 ) $card_args['team_id'] = $team_id;
                // #2834 — carry the type the report is filtered to. Without
                // it a count of three trainings landed on a list that also
                // held matches and meetings, and the number the coach
                // clicked could not be reconciled with what they arrived at.
                if ( $type_key !== '' ) $card_args['activity_type_key'] = $type_key;
                $card_url = BackLink::appendTo( add_query_arg( $card_args, RecordLink::dashboardUrl() ) );
                echo '<li><a class="tt-record-link tt-att-drill" href="' . esc_url( $card_url ) . '">' . esc_html( $nm ) . '</a> <span class="missed">'
                    . esc_html( sprintf( /* translators: %d missed activities */ __( '%d missed', 'talenttrack' ), (int) $r['missed'] ) )
                    . '</span></li>';
            }
            echo '</ul></div>';
        }

        echo '<div class="tt-report-card"><div class="tt-table-wrap"><table class="tt-table tt-table-sortable" style="width:100%;">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__( 'Player',    'talenttrack' ) . '</th>';
        echo '<th>' . esc_html__( 'Team',      'talenttrack' ) . '</th>';
        echo '<th class="tt-att-num">' . esc_html__( 'Activities', 'talenttrack' ) . '</th>';
        // #2834 — Present keeps its percentage because it is the figure an
        // academy reports on and compares across a squad; the other four drop
        // theirs and show the count instead. A percentage of a three-activity
        // denominator is noise — "66,7% excused" is two missed sessions, and
        // "2" says that without arithmetic.
        echo '<th class="tt-att-num">' . esc_html__( 'Present %', 'talenttrack' ) . '</th>';
        echo '<th class="tt-att-num">' . esc_html__( 'Late',    'talenttrack' ) . '</th>';
        echo '<th class="tt-att-num">' . esc_html__( 'Absent',  'talenttrack' ) . '</th>';
        echo '<th class="tt-att-num">' . esc_html__( 'Excused', 'talenttrack' ) . '</th>';
        echo '<th class="tt-att-num">' . esc_html__( 'Injured', 'talenttrack' ) . '</th>';
        echo '</tr></thead><tbody>';

        // #2185 — drill-down: the "Activities" count links to the activities
        // list filtered to this player + the report's team + date window, so
        // a coach can trace the number to the actual dated sessions (each
        // activity's detail shows the recorded attendance status). BackLink
        // carries a tt_back hint back to this report. The list scopes to
        // activities the player has a recorded attendance row for, matching
        // the count.
        foreach ( $rows as $r ) {
            $player_name = trim( ( (string) $r['first_name'] ) . ' ' . ( (string) $r['last_name'] ) );
            if ( $player_name === '' ) $player_name = '#' . (int) $r['player_id'];
            $player_url = BackLink::appendTo( add_query_arg(
                [ 'tt_view' => 'players', 'id' => (int) $r['player_id'] ],
                RecordLink::dashboardUrl()
            ) );
            $team_name = (string) $r['team_name'];

            $drill_args = [
                'tt_view'   => 'activities',
                'player_id' => (int) $r['player_id'],
                'date_from' => $from,
                'date_to'   => $to,
            ];
            if ( $team_id > 0 ) $drill_args['team_id'] = $team_id;
            // #2834 — and the activity type, so the list the coach lands on
            // is the one the number was counted from.
            if ( $type_key !== '' ) $drill_args['activity_type_key'] = $type_key;
            $activities_url = BackLink::appendTo(
                add_query_arg( $drill_args, RecordLink::dashboardUrl() )
            );
            // #1488 — inline at-risk badge for flagged players (#1695: chip styling).
            // #2351 — the badge drills to the same missed-activities list the
            // Activities count links to (the #2185 player_id + window scope),
            // so a coach can trace the flag to the actual dated sessions where
            // the player was absent/excused/injured. BackLink carries the
            // tt_back hint back to this report, matching the count drill-down.
            $badge = '';
            if ( ! empty( $r['flagged'] ) ) {
                $missed_label = sprintf( /* translators: %d missed activities */ __( '%d missed', 'talenttrack' ), (int) $r['missed'] );
                $badge = ' <a class="tt-flag-badge tt-att-drill" href="' . esc_url( $activities_url ) . '" title="'
                    . esc_attr( $missed_label ) . '" aria-label="'
                    . esc_attr( sprintf(
                        /* translators: 1: missed count, 2: player name */
                        __( 'View the %1$d missed activities for %2$s', 'talenttrack' ),
                        (int) $r['missed'],
                        $player_name
                    ) ) . '">⚠ ' . (int) $r['missed'] . '</a>';
            }
            $present_pct = (int) $r['total'] > 0 ? ( (int) $r['present'] / (int) $r['total'] ) * 100 : null;
            echo '<tr' . ( ! empty( $r['flagged'] ) ? ' class="is-flagged"' : '' ) . '>';
            echo '<td><a class="tt-record-link" href="' . esc_url( $player_url ) . '">' . esc_html( $player_name ) . '</a>' . $badge . '</td>';
            echo '<td>' . ( $team_name !== '' ? esc_html( $team_name ) : '<span class="tt-muted">&mdash;</span>' ) . '</td>';
            $activities_count = (int) $r['activities'];
            echo '<td class="tt-att-num">';
            if ( $activities_count > 0 ) {
                echo '<a class="tt-att-drill" href="' . esc_url( $activities_url ) . '" aria-label="'
                    . esc_attr( sprintf(
                        /* translators: 1: activity count, 2: player name */
                        __( 'View the %1$d activities counted for %2$s', 'talenttrack' ),
                        $activities_count,
                        $player_name
                    ) ) . '">' . $activities_count . '</a>';
            } else {
                echo $activities_count;
            }
            echo '</td>';
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — attendanceBar() escapes internally.
            echo '<td>' . self::attendanceBar( $present_pct, (int) $r['present'], (int) $r['total'] ) . '</td>';
            // #2834 — the count, not the percentage. `data-sort` carries the
            // number so the client-side sorter orders 2 before 10 instead of
            // lexically.
            foreach ( [ 'late', 'absent', 'excused', 'injured' ] as $state ) {
                $n = (int) $r[ $state ];
                echo '<td class="tt-att-num" data-sort="' . esc_attr( (string) $n ) . '">'
                    . esc_html( number_format_i18n( $n ) ) . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table></div></div>';
        echo '</div>'; // #3338 — /.tt-filter-region
    }

    /**
     * Inline attendance bar for the Present % cell — value + a track that
     * fills proportionally, red below 70%. Returns escaped HTML.
     * Mirrors the team report's bar (#1688) for a consistent vocabulary.
     *
     * #2834 — the percentage now carries the fraction it came from. "33,3%"
     * over three activities is one session attended; showing `(1/3)` beside
     * it means a coach never has to work the denominator out, and it is the
     * first thing anyone asks of a small sample.
     */
    private static function attendanceBar( ?float $pct, int $present = -1, int $total = -1 ): string {
        if ( $pct === null ) {
            return '<span class="tt-att-bar"><span class="v">—</span></span>';
        }
        $low = $pct < 70;
        $w   = max( 0, min( 100, (int) round( $pct ) ) );

        $fraction = '';
        if ( $present >= 0 && $total > 0 ) {
            $fraction = '<span class="tt-att-bar__of">' . esc_html( sprintf(
                /* translators: 1: activities attended, 2: activities counted */
                __( '(%1$s/%2$s)', 'talenttrack' ),
                number_format_i18n( $present ),
                number_format_i18n( $total )
            ) ) . '</span>';
        }

        return '<span class="tt-att-bar' . ( $low ? ' is-low' : '' ) . '">'
            . '<span class="v">' . esc_html( number_format_i18n( $pct, 1 ) . '%' ) . '</span>'
            . $fraction
            . '<span class="track"><i style="width:' . (int) $w . '%;"></i></span>'
            . '</span>';
    }

    /** @return array{from:string,to:string} */
    private static function defaultWindow(): array {
        return ReportFilters::seasonDefaultWindow();
    }


    /**
     * #2136 — Team select + retrospective period pills + activity-type
     * filter + From/To range, through the shared FilterBar for visual +
     * a11y parity with the activities list. Pills + period are link-based;
     * Team and Type auto-submit; the date range is the manual override.
     *
     * @param list<int>|null $allowed_team_ids
     */
    private static function renderFilterForm( string $from, string $to, int $team_id, ?array $allowed_team_ids, string $period = '', string $type_key = '' ): void {
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

        // Base args each period pill preserves (team / type / back-target).
        $pill_base = [ 'tt_view' => 'attendance-report-player' ];
        if ( $team_id > 0 )                $pill_base['team_id']           = $team_id;
        if ( $type_key !== '' )            $pill_base['activity_type_key'] = $type_key;
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
        $hidden = [ 'tt_view' => 'attendance-report-player' ];
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

        $reset_args = [ 'tt_view' => 'attendance-report-player' ];
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
                'key'         => 'attendance_player',
                'base_url'    => $dash_url,
                'base_params' => [ 'tt_view' => 'attendance-report-player' ],
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
            ],
        ] );
    }
}
