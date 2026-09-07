<?php
namespace TT\Modules\Analytics\Frontend;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Infrastructure\Query\QueryHelpers;
use TT\Infrastructure\Tenancy\CurrentClub;
use TT\Modules\Analytics\Reports\GoalContributionQuery;
use TT\Modules\Analytics\Reports\MinutesQuery;
use TT\Modules\Analytics\Reports\ReportFilters;
use TT\Shared\Frontend\Components\BackLink;
use TT\Shared\Frontend\Components\FilterBar;
use TT\Shared\Frontend\Components\FrontendAppChrome;
use TT\Shared\Frontend\Components\FrontendBreadcrumbs;
use TT\Shared\Frontend\Components\MinutesBreakdown;
use TT\Shared\Frontend\Components\RecordLink;
use TT\Shared\Frontend\FrontendViewBase;

/**
 * FrontendMinutesTeamReportView (#1034) — standard report: minutes
 * played per player on a team over a date window, split by match type
 * (game_subtype_key — League / Cup / Friendly).
 *
 * URL: `?tt_view=minutes-report-team&team_id=N&from=YYYY-MM-DD&to=YYYY-MM-DD&type=all|<key>`
 *
 * Reached via the "Standard reports" section on the central Analytics
 * surface (`?tt_view=analytics`). Sortable table — one row per player
 * who actually played in the window. Columns: Player | Total | Matches
 * | Starts | Subs in | Subs off | Avg | League | Cup | Friendly | %
 * available.
 *
 * Cap-gated on `tt_view_analytics` (same as the parent Analytics view).
 *
 * v1 scope: team report only. A player-detail variant (`tt_view=
 * minutes-report-player`) is a #1034 follow-up; the existing PDP-style
 * spotlight from the mockup builds on the same `MinutesQuery` service.
 */
final class FrontendMinutesTeamReportView extends FrontendViewBase {

    /**
     * Enqueue the 2026 surface stylesheet on top of the shared frontend
     * assets. Depends on the app-chrome sheet so it inherits the brand +
     * neutral tokens and the shared .tt-kpi tile styling. Mirrors the
     * gold-standard FrontendStandardReportsView enqueue.
     */
    protected static function enqueueAssets(): void {
        parent::enqueueAssets();
        wp_enqueue_style(
            'tt-frontend-minutes-report',
            TT_PLUGIN_URL . 'assets/css/frontend-minutes-report.css',
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
        // Minutes-played-per-team report has been switched off for this academy.
        if ( ! \TT\Core\FeatureRegistry::isEnabled( 'report_minutes_report_team' ) ) {
            FrontendBreadcrumbs::fromDashboard(
                __( 'Minutes played (team)', 'talenttrack' ),
                [ FrontendBreadcrumbs::viewCrumb( 'reports', __( 'Reports', 'talenttrack' ) ) ]
            );
            echo '<p class="tt-notice">' . esc_html__( 'This report has been switched off for your academy.', 'talenttrack' ) . '</p>';
            return;
        }

        FrontendBreadcrumbs::fromDashboard(
            __( 'Minutes played (team)', 'talenttrack' ),
            [ FrontendBreadcrumbs::viewCrumb( 'reports', __( 'Reports', 'talenttrack' ) ) ]
        );
        self::renderHeader( __( 'Minutes played per player', 'talenttrack' ) );

        // v4.20.34 (#1193) — same scope pattern as the two attendance
        // views (v4.20.4 / #1147). Global-read-on-`activities` holders
        // keep the club-wide list; everyone else only sees teams they
        // coach. Both the dropdown AND the downstream `MinutesQuery`
        // call guard against URL tampering. #1942 — the academy-wide lens
        // is global-scope read on `activities`; the settings-admin flag
        // stays as the WP-admin fallback.
        $is_scope_admin = $is_admin
            || \TT\Modules\Authorization\AllTeamsScope::canSeeAllTeamsActivities( $user_id );
        $allowed_team_ids = $is_scope_admin
            ? null
            : array_values( array_map( 'intval', array_column( QueryHelpers::get_teams_for_coach( $user_id ), 'id' ) ) );

        if ( ! $is_scope_admin && $allowed_team_ids === [] ) {
            echo '<p class="tt-notice">' . esc_html__( "You don't coach any teams yet, so there is no minutes data to show. Ask an administrator to assign you to a team.", 'talenttrack' ) . '</p>';
            return;
        }

        $team_id = isset( $_GET['team_id'] ) ? absint( $_GET['team_id'] ) : 0;
        $teams = self::listTeams( $allowed_team_ids );
        if ( empty( $teams ) ) {
            echo '<p class="tt-notice">' . esc_html__( 'No teams yet — add one to enable this report.', 'talenttrack' ) . '</p>';
            return;
        }
        // URL tampering: requested team_id not in the scoped list →
        // empty state instead of forging through to MinutesQuery.
        if ( $allowed_team_ids !== null && $team_id > 0 && ! in_array( $team_id, $allowed_team_ids, true ) ) {
            echo '<p class="tt-notice">' . esc_html__( 'No data for the selected team in your scope.', 'talenttrack' ) . '</p>';
            return;
        }
        if ( $team_id <= 0 || ! self::teamExists( $team_id, $teams ) ) {
            $team_id = (int) $teams[0]->id;
        }

        $defaults = self::defaultWindow();
        // #2349 — a retrospective period pill resolves the window unless the
        // user typed an explicit From/To (manual override wins), matching the
        // attendance reports' shared behaviour.
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

        $type_filter = isset( $_GET['type'] ) ? sanitize_key( (string) wp_unslash( $_GET['type'] ) ) : 'all';
        // #2349 — the shared FilterBar's placeholder option submits an empty
        // `type=`; normalize it back to the report's 'all' sentinel.
        if ( $type_filter === '' ) $type_filter = 'all';

        self::renderFilterForm( $teams, $team_id, $from, $to, $type_filter, $period );

        $rows = ( new MinutesQuery() )->forTeam( $team_id, $from, $to );

        // #3338 — everything the filters govern, empty state included.
        printf( '<div data-tt-filter-region data-tt-filter-count="%d">', count( $rows ) );

        if ( $rows === [] ) {
            echo '<p class="tt-notice">' . esc_html__( 'No minutes recorded for this team in the selected window.', 'talenttrack' ) . '</p>';
            echo '</div>';
            return;
        }

        // Collect every match-type key that actually appears (so we
        // only show columns the operator has data for, plus the
        // canonical League / Cup / Friendly even when empty).
        $type_labels = [
            'League'   => __( 'League',   'talenttrack' ),
            'Cup'      => __( 'Cup',      'talenttrack' ),
            'Friendly' => __( 'Friendly', 'talenttrack' ),
        ];
        $type_keys = array_keys( $type_labels );
        foreach ( $rows as $r ) {
            foreach ( array_keys( $r['by_type'] ) as $k ) {
                if ( ! isset( $type_labels[ $k ] ) ) {
                    $type_labels[ $k ] = $k;
                    $type_keys[]       = $k;
                }
            }
        }
        $type_keys = array_values( array_unique( $type_keys ) );

        $type_match = ( $type_filter !== 'all' && isset( $type_labels[ $type_filter ] ) );

        // Pre-compute the visible rows + headline totals for the KPI
        // strip. Pure aggregation over the already-fetched rows (no query,
        // no eligibility decision) so this stays SaaS-portable per
        // CLAUDE.md §4 and the strip is render-only polish.
        $visible_rows  = [];
        $sum_minutes   = 0;
        $sum_starts    = 0;
        foreach ( $rows as $r ) {
            // When the operator filters to one match type, the visible
            // Total + Avg only count that bucket. Other columns stay
            // informational so a coach can compare league-only vs
            // overall in one glance.
            $effective_total = $type_match
                ? (int) ( $r['by_type'][ $type_filter ] ?? 0 )
                : (int) $r['total_minutes'];
            if ( $type_match && $effective_total <= 0 ) continue;

            $matches = max( 1, (int) $r['matches'] );
            $avg = $effective_total > 0 ? (int) round( $effective_total / $matches ) : 0;
            $pct_avail = $r['available_minutes'] > 0
                ? min( 100, (int) round( ( $effective_total / $r['available_minutes'] ) * 100 ) )
                : 0;

            $sum_minutes += $effective_total;
            $sum_starts  += (int) $r['starts'];

            $r['__effective_total'] = $effective_total;
            $r['__avg']             = $avg;
            $r['__pct_avail']       = $pct_avail;
            $visible_rows[] = $r;
        }

        // KPI strip — squad-wide headline totals for the window. #2349 —
        // uses the shared `.tt-report-kpis` grid (frontend-app-chrome.css)
        // like every other report surface, retiring the bespoke
        // `tt-rep-kpi-row` markup + its one-off CSS.
        echo '<div class="tt-report-kpis" role="group" aria-label="' . esc_attr__( 'Minutes summary', 'talenttrack' ) . '">';
        // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped — kpiTile escapes its own fields.
        echo FrontendAppChrome::kpiTile( [
            'label' => __( 'Players', 'talenttrack' ),
            'value' => (string) count( $visible_rows ),
        ] );
        echo FrontendAppChrome::kpiTile( [
            'label' => __( 'Total minutes', 'talenttrack' ),
            'value' => number_format_i18n( $sum_minutes ),
        ] );
        echo FrontendAppChrome::kpiTile( [
            'label' => __( 'Total starts', 'talenttrack' ),
            'value' => number_format_i18n( $sum_starts ),
        ] );
        // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '</div>';

        echo '<div class="tt-report-card"><div class="tt-table-wrap"><table class="tt-table tt-table-sortable">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__( 'Player', 'talenttrack' ) . '</th>';
        echo '<th class="num">' . esc_html__( 'Total', 'talenttrack' ) . '</th>';
        echo '<th class="num">' . esc_html__( 'Matches', 'talenttrack' ) . '</th>';
        echo '<th class="num">' . esc_html__( 'Starts', 'talenttrack' ) . '</th>';
        echo '<th class="num">' . esc_html__( 'Subs in', 'talenttrack' ) . '</th>';
        echo '<th class="num">' . esc_html__( 'Subs off', 'talenttrack' ) . '</th>';
        echo '<th class="num">' . esc_html__( 'Avg / match', 'talenttrack' ) . '</th>';
        // #2859 — output beside exposure. Reading "played 340 minutes" and
        // "scored 6" off two different screens is what made the comparison
        // nobody was making; they belong in one row.
        echo '<th class="num">' . esc_html_x( 'Goals scored', 'goals scored in matches', 'talenttrack' ) . '</th>';
        echo '<th class="num">' . esc_html_x( 'Assists', 'who created a goal', 'talenttrack' ) . '</th>';
        foreach ( $type_keys as $key ) {
            echo '<th class="num">' . esc_html( $type_labels[ $key ] ) . '</th>';
        }
        echo '<th class="num">' . esc_html__( '% available', 'talenttrack' ) . '</th>';
        echo '</tr></thead><tbody>';

        // #2160 — total column count for the drill-down row's colspan.
        // #2859 — +2 for the goals / assists columns.
        $col_count = 10 + count( $type_keys );
        $minutes_query = new MinutesQuery();
        // One query for the whole team, keyed by player. A player with no
        // contribution is absent from it and renders 0 — an empty cell would
        // read as "not measured" rather than "none".
        $contributions = ( new GoalContributionQuery() )->forTeam( $team_id, [ 'from' => $from, 'to' => $to ] );
        foreach ( $visible_rows as $r ) {
            $name = trim( (string) $r['first_name'] . ' ' . (string) $r['last_name'] );
            if ( $name === '' ) $name = '#' . (int) $r['player_id'];
            $player_url = BackLink::appendTo( add_query_arg(
                [ 'tt_view' => 'players', 'id' => (int) $r['player_id'] ],
                RecordLink::dashboardUrl()
            ) );

            $pid = (int) $r['player_id'];
            echo '<tr>';
            // #2160 — the player's Total cell expands to a per-match trace.
            // The breakdown reuses the same MinutesQuery + window so its
            // rows sum exactly to this Total. <details> = keyboard-operable,
            // no-JS, reconciles at 360px.
            echo '<td><a class="tt-record-link" href="' . esc_url( $player_url ) . '">' . esc_html( $name ) . '</a></td>';
            echo '<td class="num"><a class="tt-record-link" href="#tt-min-bd-' . $pid . '" data-tt-minutes-toggle="' . $pid . '" aria-controls="tt-min-bd-' . $pid . '">' . esc_html( number_format_i18n( $r['__effective_total'] ) ) . '</a></td>';
            echo '<td class="num">' . (int) $r['matches'] . '</td>';
            echo '<td class="num">' . (int) $r['starts'] . '</td>';
            echo '<td class="num">' . (int) $r['subs_in'] . '</td>';
            echo '<td class="num">' . (int) $r['subs_off'] . '</td>';
            echo '<td class="num">' . (int) $r['__avg'] . '</td>';
            echo '<td class="num">' . (int) ( $contributions[ $pid ]['goals'] ?? 0 ) . '</td>';
            echo '<td class="num">' . (int) ( $contributions[ $pid ]['assists'] ?? 0 ) . '</td>';
            foreach ( $type_keys as $key ) {
                $v = (int) ( $r['by_type'][ $key ] ?? 0 );
                echo '<td class="num">' . esc_html( number_format_i18n( $v ) ) . '</td>';
            }
            echo '<td class="num">' . (int) $r['__pct_avail'] . '%</td>';
            echo '</tr>';

            $breakdown = $minutes_query->matchBreakdownForPlayer( $team_id, $pid, $from, $to );
            echo '<tr class="tt-min-breakdown-row" id="tt-min-bd-' . $pid . '">';
            echo '<td colspan="' . (int) $col_count . '">';
            // #2348 — shared breakdown component; identical rows to the
            // former per-file renderer, still reconciling to the Total.
            MinutesBreakdown::render( $breakdown, $pid );
            echo '</td></tr>';
        }
        echo '</tbody></table></div></div>';
        echo '</div>'; // #3338 — /.tt-filter-region
        self::enqueueDrilldownAssets();
    }

    /**
     * #2160 — enqueue the tiny toggle script that expands a player's
     * minutes breakdown row when their Total is clicked. The breakdown
     * rows are present in the DOM (no-JS reconciliation is possible by
     * following the in-page anchor); the script just collapses them by
     * default and toggles one at a time.
     */
    private static function enqueueDrilldownAssets(): void {
        wp_enqueue_script(
            'tt-minutes-drilldown',
            TT_PLUGIN_URL . 'assets/js/components/minutes-drilldown.js',
            [],
            TT_VERSION,
            true
        );
    }

    /**
     * v4.20.34 (#1193) — accepts an `$allowed_team_ids` scope filter.
     * `null` means unrestricted (admin / view_all_teams); a list narrows
     * the SQL `IN (...)` clause.
     *
     * @param list<int>|null $allowed_team_ids
     * @return list<object>  id, name
     */
    private static function listTeams( ?array $allowed_team_ids = null ): array {
        global $wpdb;
        if ( $allowed_team_ids !== null ) {
            if ( $allowed_team_ids === [] ) return [];
            $placeholders = implode( ',', array_fill( 0, count( $allowed_team_ids ), '%d' ) );
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

    /**
     * Default window: current season start through today (90-day fallback).
     * @return array{from:string,to:string}
     */
    private static function defaultWindow(): array {
        return ReportFilters::seasonDefaultWindow();
    }

    /**
     * #2349 — render the filter row through the shared FilterBar for visual
     * + a11y parity with the attendance reports (period quick-pills, mobile
     * bottom-sheet, 48px targets). The team + match-type selects auto-submit;
     * the retrospective period pills are link-based (set `?period=`); the
     * From/To range is the manual override.
     *
     * @param list<object> $teams
     */
    private static function renderFilterForm( array $teams, int $team_id, string $from, string $to, string $type_filter, string $period ): void {
        $dash_url = RecordLink::dashboardUrl();
        $period_labels = ReportFilters::periodLabels();

        // Match-type buckets (subtype, distinct from ReportFilters' activity
        // type — this report splits League / Cup / Friendly).
        $type_options = [
            'League'   => __( 'League',   'talenttrack' ),
            'Cup'      => __( 'Cup',      'talenttrack' ),
            'Friendly' => __( 'Friendly', 'talenttrack' ),
        ];

        // Team options keyed by id for the shared select group.
        $team_options = [];
        foreach ( $teams as $t ) {
            $team_options[ (string) (int) $t->id ] = (string) $t->name;
        }

        // Base args every period pill preserves (team + type + back-target).
        $pill_base = [ 'tt_view' => 'minutes-report-team', 'team_id' => $team_id ];
        if ( $type_filter !== 'all' )      $pill_base['type']    = $type_filter;
        if ( ! empty( $_GET['tt_back'] ) ) $pill_base['tt_back'] = sanitize_text_field( wp_unslash( (string) $_GET['tt_back'] ) );

        $period_options = [];
        foreach ( $period_labels as $key => $label ) {
            $args = $pill_base;
            if ( $key !== '' ) $args['period'] = $key;
            // Picking a pill drops any manual From/To so the window follows.
            $period_options[] = [
                'value'  => $key,
                'label'  => $label,
                'url'    => add_query_arg( $args, $dash_url ),
                'active' => ( $period === $key ),
            ];
        }

        // Hidden fields the auto-submitting selects carry so the link-based
        // period + back-target survive a team/type change.
        $hidden = [ 'tt_view' => 'minutes-report-team' ];
        if ( $period !== '' )              $hidden['period']  = $period;
        if ( ! empty( $_GET['tt_back'] ) ) $hidden['tt_back'] = sanitize_text_field( wp_unslash( (string) $_GET['tt_back'] ) );

        $active_count = 0;
        $chips = [];
        if ( $period !== '' )         { $active_count++; $chips[] = (string) ( $period_labels[ $period ] ?? '' ); }
        // #3293 — a custom window is a filter and was counted nowhere.
        $range_chip = ReportFilters::customRangeChip( $period, $from, $to );
        if ( $range_chip !== null ) { $active_count++; $chips[] = $range_chip; }
        if ( $type_filter !== 'all' && isset( $type_options[ $type_filter ] ) ) { $active_count++; $chips[] = $type_options[ $type_filter ]; }

        $reset_args = [ 'tt_view' => 'minutes-report-team', 'team_id' => $team_id ];
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
                'key'         => 'minutes_team',
                'base_url'    => $dash_url,
                'base_params' => [ 'tt_view' => 'minutes-report-team' ],
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
                    'options'     => $type_options,
                ],
            ],
        ] );
    }
}
