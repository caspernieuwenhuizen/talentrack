<?php
namespace TT\Shared\Frontend;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Infrastructure\Evaluations\EvalCategoriesRepository;
use TT\Infrastructure\Query\QueryHelpers;
use TT\Infrastructure\Stats\PlayerStatsService;
use TT\Shared\Frontend\Components\ComparisonSlotPicker;

/**
 * FrontendComparisonView — the "Player comparison" tile destination
 * (analytics group).
 *
 * v3.0.0 slice 5. Streamlined mobile-first version of the admin
 * PlayerComparisonPage. Slot pickers (up to 4), FIFA card row, basic
 * facts table, headline numbers table, main-category breakdown.
 *
 * #0077 M6 — radar overlay + trend overlay charts brought to parity
 * with the admin PlayerComparisonPage. Same Chart.js dataset shape;
 * frontend now ships the multi-dataset radar so coaches don't have to
 * jump to wp-admin for at-a-glance multi-axis profile compare.
 *
 * Permission gate: tt_view_reports. Observer role has this cap.
 */
class FrontendComparisonView extends FrontendViewBase {

    /** Chart palette; matches admin PlayerComparisonPage. */
    private const COLORS = [ '#2271b1', '#00a32a', '#e8b624', '#b32d2e' ];

    /**
     * B3 — enqueue the per-view 2026 stylesheet on top of the shared
     * frontend assets. Styles the picker/filter card, the side-by-side
     * compare grid, and the radar / trend chart frames; depends on the
     * app-chrome sheet for the brand tokens. Chart.js canvases and the
     * stats queries are untouched.
     */
    protected static function enqueueAssets(): void {
        parent::enqueueAssets();
        wp_enqueue_style(
            'tt-frontend-compare',
            TT_PLUGIN_URL . 'assets/css/frontend-compare.css',
            [ 'tt-frontend-app-chrome' ],
            TT_VERSION
        );
    }

    public static function render(): void {
        self::enqueueAssets();
        \TT\Modules\Stats\Admin\PlayerCardView::enqueueStyles();
        // #0077 M6 — Chart.js for radar + trend overlay parity.
        wp_enqueue_script(
            'tt-chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
            [],
            '4.4.0',
            true
        );
        // v3.91.6 — comparison slot picker (Team → Player two-step).
        wp_enqueue_script(
            'tt-comparison-slot-picker',
            TT_PLUGIN_URL . 'assets/js/components/comparison-slot-picker.js',
            [],
            TT_VERSION,
            true
        );

        \TT\Shared\Frontend\Components\FrontendBreadcrumbs::fromDashboard( __( 'Player comparison', 'talenttrack' ) );
        self::renderHeader( __( 'Player comparison', 'talenttrack' ) );

        // #0011 — feature gate. Player comparison is a Standard-tier
        // feature; on Free, surface an upgrade nudge instead of the
        // analytics body.
        if ( \TT\Core\ModuleRegistry::isEnabled( 'TT\\Modules\\License\\LicenseModule' )
             && class_exists( '\TT\Modules\License\LicenseGate' )
             && ! \TT\Modules\License\LicenseGate::can( 'player_comparison' )
        ) {
            echo \TT\Modules\License\Admin\UpgradeNudge::inline(
                __( 'Player comparison', 'talenttrack' ),
                'standard'
            );
            return;
        }

        // Pick up to 4 players from ?p1, ?p2, ?p3, ?p4
        $picked = [];
        for ( $i = 1; $i <= 4; $i++ ) {
            $pid = isset( $_GET[ "p{$i}" ] ) ? absint( $_GET[ "p{$i}" ] ) : 0;
            if ( $pid > 0 ) $picked[] = $pid;
        }
        $picked = array_values( array_unique( $picked ) );
        $picked = array_slice( $picked, 0, 4 );

        $filters = PlayerStatsService::sanitizeFilters( $_GET );

        // Resolve picked players
        $players = [];
        foreach ( $picked as $pid ) {
            $pl = QueryHelpers::get_player( $pid );
            if ( $pl ) $players[] = $pl;
        }

        // Coach-context scope (#2006). The compare selectors previously
        // loaded every active player and every team, ignoring the coach's
        // assignment — a head coach saw the whole academy roster in the
        // picker. Mirror the `reports/player-radar` REST endpoint and the
        // standard-reports view: global-scope read on `reports` (HoD,
        // academy admin, scout) or the WP settings admin sees all teams;
        // a team-scoped coach is narrowed to `get_teams_for_coach`, and the
        // player list is filtered to those teams. Demo-mode scope still
        // applies so demo runs only show demo-tagged players.
        global $wpdb;
        $p = $wpdb->prefix;
        $user_id = get_current_user_id();
        $is_scope_admin = current_user_can( 'tt_edit_settings' )
            || \TT\Modules\Authorization\AllTeamsScope::canSeeAllTeamsReports( $user_id );
        $allowed_team_ids = $is_scope_admin
            ? null
            : array_values( array_map(
                'intval',
                array_column( QueryHelpers::get_teams_for_coach( $user_id ), 'id' )
            ) );

        $player_scope = QueryHelpers::apply_demo_scope( 'pl', 'player' );
        $all_players = $wpdb->get_results(
            "SELECT pl.id, pl.first_name, pl.last_name, pl.team_id, t.name AS team_name, t.age_group
             FROM {$p}tt_players pl
             LEFT JOIN {$p}tt_teams t ON pl.team_id = t.id
             WHERE pl.status = 'active' AND pl.archived_at IS NULL {$player_scope}
             ORDER BY pl.last_name, pl.first_name ASC"
        );

        // v3.91.6 — teams keyed by id for the slot pickers' team `<select>`s.
        // Sorted alphabetically so dropdowns read naturally.
        $teams_by_id = [];
        $team_rows = $wpdb->get_results(
            "SELECT id, name FROM {$p}tt_teams
             WHERE " . \TT\Infrastructure\Archive\ArchiveRepository::filterClause( 'active' ) . "
             ORDER BY name ASC"
        );
        foreach ( $team_rows as $tr ) {
            $teams_by_id[ (int) $tr->id ] = (string) $tr->name;
        }

        // #2006 — narrow both selector data sets to the coach's teams when
        // they aren't a scope admin. A null `$allowed_team_ids` means "no
        // restriction"; an empty list means "scope-limited with no teams"
        // and correctly collapses both selectors to nothing.
        if ( $allowed_team_ids !== null ) {
            $allowed_lookup = array_flip( $allowed_team_ids );
            $all_players = array_values( array_filter(
                $all_players,
                static fn( $pl ) => isset( $allowed_lookup[ (int) $pl->team_id ] )
            ) );
            $teams_by_id = array_intersect_key( $teams_by_id, $allowed_lookup );
            // Drop any directly-URL-supplied (`?pN=`) player outside scope so
            // a coach can't hand-craft an out-of-context comparison.
            $players = array_values( array_filter(
                $players,
                static fn( $pl ) => isset( $allowed_lookup[ (int) $pl->team_id ] )
            ) );
        }

        // Slot visibility: 2 by default; grow as picks land. Empty slots
        // beyond visible_slots stay hidden until "Add another player".
        $populated     = array_values( array_filter( array_map( 'intval', $picked ) ) );
        $visible_slots = max( 2, min( 4, count( $populated ) + 1 ) );
        if ( $visible_slots > 4 ) $visible_slots = 4;

        ?>
        <p class="tt-fcompare-intro">
            <?php esc_html_e( 'Compare up to 4 players side-by-side. Cross-team is supported — pick any players from any team or age group.', 'talenttrack' ); ?>
        </p>

        <form method="get" action="" class="tt-fcompare-form">
            <?php
            // Preserve tt_view + any other non-filter args
            foreach ( $_GET as $k => $v ) {
                if ( preg_match( '/^p[1-4]$/', (string) $k ) ) continue;
                if ( preg_match( '/^team_[1-4]$/', (string) $k ) ) continue;
                if ( in_array( $k, [ 'date_from', 'date_to', 'eval_type_id' ], true ) ) continue;
                if ( is_string( $v ) ) {
                    echo '<input type="hidden" name="' . esc_attr( (string) $k ) . '" value="' . esc_attr( wp_unslash( $v ) ) . '" />';
                }
            }
            ?>
            <h3><?php esc_html_e( 'Select players', 'talenttrack' ); ?></h3>
            <div id="tt-compare-slots" class="tt-fcompare-slots">
                <?php for ( $i = 1; $i <= 4; $i++ ) :
                    $current = (int) ( $picked[ $i - 1 ] ?? 0 );
                    $hidden  = ( $i > $visible_slots && $current === 0 );
                    ?>
                    <div class="tt-compare-slot" data-tt-slot="<?php echo (int) $i; ?>" <?php echo $hidden ? 'hidden' : ''; ?>>
                        <?php echo ComparisonSlotPicker::render( [
                            'index'              => $i,
                            'selected_player_id' => $current,
                            'players'            => $all_players,
                            'teams_by_id'        => $teams_by_id,
                        ] ); ?>
                    </div>
                <?php endfor; ?>
            </div>
            <p class="tt-fcompare-addrow">
                <button type="button" class="tt-btn tt-btn-secondary" id="tt-compare-add-slot" <?php echo $visible_slots >= 4 ? 'disabled' : ''; ?>>
                    <?php esc_html_e( 'Add another player', 'talenttrack' ); ?>
                </button>
                <small id="tt-compare-slot-max" class="tt-fcompare-addnote" <?php echo $visible_slots >= 4 ? '' : 'hidden'; ?>>
                    <?php esc_html_e( 'Maximum of 4 players.', 'talenttrack' ); ?>
                </small>
            </p>
            <script>
            (function () {
                var btn  = document.getElementById( 'tt-compare-add-slot' );
                var note = document.getElementById( 'tt-compare-slot-max' );
                if ( ! btn ) return;
                btn.addEventListener( 'click', function () {
                    var slots = document.querySelectorAll( '#tt-compare-slots .tt-compare-slot' );
                    for ( var i = 0; i < slots.length; i++ ) {
                        if ( slots[ i ].hasAttribute( 'hidden' ) ) {
                            slots[ i ].removeAttribute( 'hidden' );
                            if ( i + 1 >= slots.length ) {
                                btn.disabled = true;
                                if ( note ) note.hidden = false;
                            }
                            return;
                        }
                    }
                    btn.disabled = true;
                    if ( note ) note.hidden = false;
                } );
            })();
            </script>

            <?php
            // #2176 — the date + evaluation-type filters render via the
            // shared FilterBar component (one date_range group + one
            // select). FilterBar emits its own <form>, but here it sits
            // inside the surrounding .tt-fcompare-form: the HTML parser
            // drops the nested <form> tag and re-associates its controls
            // with the outer form, so the single "Compare" submit below
            // still posts the player picks AND these filters together —
            // behaviour is unchanged. The select opts OUT of auto-submit
            // (auto_submit => false) so changing it doesn't fire an early
            // GET; the user commits via Compare, exactly as before. No
            // `hidden` fields are passed because the outer form already
            // re-emits every preserved GET param above.
            $sel_from     = (string) ( $filters['date_from'] ?? '' );
            $sel_to       = (string) ( $filters['date_to'] ?? '' );
            $sel_evaltype = (int) ( $filters['eval_type_id'] ?? 0 );

            $eval_options = [];
            foreach ( QueryHelpers::get_eval_types() as $t ) {
                $eval_options[ (string) (int) $t->id ] = (string) $t->name;
            }

            $active_count = 0;
            $chips = [];
            if ( $sel_from !== '' || $sel_to !== '' ) {
                $active_count++;
                $chips[] = trim( $sel_from . ' – ' . $sel_to, ' –' );
            }
            if ( $sel_evaltype > 0 && isset( $eval_options[ (string) $sel_evaltype ] ) ) {
                $active_count++;
                $chips[] = $eval_options[ (string) $sel_evaltype ];
            }

            \TT\Shared\Frontend\Components\FilterBar::render( [
                'active_count' => $active_count,
                'chips'        => $chips,
                // #3333 — this was the one FilterBar surface with no way back
                // to unfiltered: without a `reset_url` the bar renders no
                // Clear at all, and the two controls here are a date range and
                // a select, neither of which has a "none" option to walk back
                // to. The comparison view keeps its player selection in the
                // same query, so Clear drops only the filter params.
                'reset_url'    => \TT\Infrastructure\Filters\FilterParam::removeFromUrl(
                    'eval_type_id',
                    \TT\Infrastructure\Filters\FilterParam::removeFromUrl(
                        'date_to',
                        \TT\Infrastructure\Filters\FilterParam::removeFromUrl( 'date_from' )
                    )
                ),
                'groups'       => [
                    [
                        'type'       => 'date_range',
                        'key'        => 'date',
                        'label'      => __( 'Date', 'talenttrack' ),
                        'label_from' => __( 'Date from', 'talenttrack' ),
                        'label_to'   => __( 'Date to', 'talenttrack' ),
                        'from'       => [ 'name' => 'date_from', 'value' => $sel_from ],
                        'to'         => [ 'name' => 'date_to',   'value' => $sel_to ],
                    ],
                    [
                        'type'        => 'select',
                        'key'         => 'eval-type',
                        'label'       => __( 'Evaluation Type', 'talenttrack' ),
                        'name'        => 'eval_type_id',
                        'selected'    => $sel_evaltype > 0 ? (string) $sel_evaltype : '',
                        'placeholder' => __( 'All types', 'talenttrack' ),
                        'options'     => $eval_options,
                        'auto_submit' => false,
                    ],
                ],
            ] );
            ?>

            <p class="tt-fcompare-submit">
                <button type="submit" class="tt-btn tt-btn-primary"><?php esc_html_e( 'Compare', 'talenttrack' ); ?></button>
            </p>
        </form>

        <?php
        if ( empty( $players ) ) {
            echo '<p class="tt-fcompare-note-empty"><em>' . esc_html__( 'Pick at least one player above and click Compare.', 'talenttrack' ) . '</em></p>';
            return;
        }

        $service = new PlayerStatsService();
        $headlines = [];
        $mains = [];
        $age_groups = [];
        $trends = [];
        $radar_sets = [];
        foreach ( $players as $pl ) {
            $pid = (int) $pl->id;
            $team = $pl->team_id ? QueryHelpers::get_team( (int) $pl->team_id ) : null;
            $age_groups[ $pid ] = $team && ! empty( $team->age_group ) ? $team->age_group : '';
            $headlines[ $pid ] = $service->getHeadlineNumbers( $pid, $filters, 5 );
            $mains[ $pid ] = $service->getMainCategoryBreakdown( $pid, $filters );
            // #0077 M6 — radar + trend datasets, same calls as admin.
            $trends[ $pid ]     = $service->getTrendSeries( $pid, $filters );
            $radar_sets[ $pid ] = $service->getRadarSnapshots( $pid, $filters, 1 );
        }
        $unique_ages = array_values( array_unique( array_filter( $age_groups ) ) );
        $mixed_ages = count( $unique_ages ) > 1;
        ?>

        <?php if ( $mixed_ages ) : ?>
            <div class="tt-fcompare-mixedages">
                <?php esc_html_e( 'Mixed age groups in this comparison. Overall ratings use age-group-specific category weights, so the numbers below are not perfectly apples-to-apples — they reflect what each player\'s own coaching staff uses.', 'talenttrack' ); ?>
            </div>
        <?php endif; ?>

        <?php
        // v3.94.1 — unified CSS Grid lines every player's column up
        // vertically from the FIFA card down through Basic facts,
        // Headline numbers, and Main category averages. The column
        // count is data-driven (one per picked player), passed to the
        // stylesheet via the `--tt-fcompare-cols` custom property; all
        // static rules live in frontend-compare.css (B3).
        $n = count( $players );
        ?>

        <h3 class="tt-fcompare-h3"><?php esc_html_e( 'Side-by-side', 'talenttrack' ); ?></h3>
        <div class="tt-fcompare-grid" style="--tt-fcompare-cols:<?php echo (int) $n; ?>;">
            <!-- Header row: blank label cell + N player names -->
            <!-- v4.0.7 (#878) — was `tt-fcompare-label tt-fcompare-headerplayer`;
                 the two classes conflict on `background` + `font-weight`
                 (latter wins on specificity). Drop the label class so
                 the header anchor cell renders consistently. -->
            <div class="tt-fcompare-cell tt-fcompare-headerplayer">&nbsp;</div>
            <?php foreach ( $players as $pl ) : ?>
                <div class="tt-fcompare-cell tt-fcompare-headerplayer"><?php echo esc_html( QueryHelpers::player_display_name( $pl ) ); ?></div>
            <?php endforeach; ?>

            <!-- Card row -->
            <div class="tt-fcompare-section"><?php esc_html_e( 'Cards', 'talenttrack' ); ?></div>
            <div class="tt-fcompare-cell tt-fcompare-label">&nbsp;</div>
            <?php foreach ( $players as $pl ) : ?>
                <div class="tt-fcompare-cell tt-fcompare-card">
                    <div class="tt-fcompare-card-inner">
                        <?php \TT\Modules\Stats\Admin\PlayerCardView::renderCard( (int) $pl->id, 'sm', true ); ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Basic facts -->
            <div class="tt-fcompare-section"><?php esc_html_e( 'Basic facts', 'talenttrack' ); ?></div>
            <?php
            $rows = [
                [ __( 'Team', 'talenttrack' ),         function( $pl ) { $t = $pl->team_id ? QueryHelpers::get_team( (int) $pl->team_id ) : null; return $t ? (string) $t->name : '—'; } ],
                [ __( 'Age group', 'talenttrack' ),    function( $pl ) use ( $age_groups ) { return (string) ( $age_groups[ (int) $pl->id ] ?: '—' ); } ],
                [ __( 'Position(s)', 'talenttrack' ),  function( $pl ) { $pos = json_decode( (string) $pl->preferred_positions, true ); return is_array( $pos ) ? implode( ', ', $pos ) : '—'; } ],
                [ __( 'Foot', 'talenttrack' ),         function( $pl ) { return $pl->preferred_foot ? \TT\Infrastructure\Query\LookupTranslator::byTypeAndName( 'foot_option', (string) $pl->preferred_foot ) : '—'; } ],
                [ __( 'Jersey', 'talenttrack' ),       function( $pl ) { return $pl->jersey_number ? '#' . (int) $pl->jersey_number : '—'; } ],
                [ __( 'Height', 'talenttrack' ),       function( $pl ) { return $pl->height_cm ? ( (int) $pl->height_cm . ' cm' ) : '—'; } ],
            ];
            foreach ( $rows as [ $label, $fn ] ) :
                ?>
                <div class="tt-fcompare-cell tt-fcompare-label"><?php echo esc_html( $label ); ?></div>
                <?php foreach ( $players as $pl ) : ?>
                    <div class="tt-fcompare-cell"><?php echo esc_html( (string) $fn( $pl ) ); ?></div>
                <?php endforeach; ?>
            <?php endforeach; ?>

            <!-- Headline numbers -->
            <div class="tt-fcompare-section"><?php esc_html_e( 'Headline numbers', 'talenttrack' ); ?></div>
            <?php
            $metrics = [
                [ __( 'Most recent', 'talenttrack' ), 'recent' ],
                [ __( 'Rolling (last 5)', 'talenttrack' ), 'rolling' ],
                [ __( 'All-time', 'talenttrack' ), 'alltime' ],
                [ __( 'Evaluations', 'talenttrack' ), 'count' ],
            ];
            foreach ( $metrics as [ $label, $key ] ) :
                ?>
                <div class="tt-fcompare-cell tt-fcompare-label"><?php echo esc_html( $label ); ?></div>
                <?php foreach ( $players as $pl ) :
                    $pid = (int) $pl->id;
                    $val = $headlines[ $pid ][ $key ] ?? null;
                    $display = $val === null ? '—' : ( is_numeric( $val ) ? (string) $val : (string) $val );
                    ?>
                    <div class="tt-fcompare-cell tt-fcompare-num"><?php echo esc_html( (string) $display ); ?></div>
                <?php endforeach; ?>
            <?php endforeach; ?>

            <!-- Main category averages — rendered into the same grid -->
            <div class="tt-fcompare-section"><?php esc_html_e( 'Main category averages', 'talenttrack' ); ?></div>
            <?php self::renderMainBreakdownGrid( $players, $mains ); ?>
        </div>

        <!-- #0077 M6 — radar overlay + trend overlay (parity with admin) -->
        <h3 class="tt-fcompare-h3"><?php esc_html_e( 'Radar profile', 'talenttrack' ); ?></h3>
        <div class="tt-fcompare-chart tt-fcompare-chart--radar">
            <canvas id="tt-fcompare-radar"></canvas>
        </div>

        <h3 class="tt-fcompare-h3"><?php esc_html_e( 'Rating trend', 'talenttrack' ); ?></h3>
        <div class="tt-fcompare-chart tt-fcompare-chart--trend">
            <canvas id="tt-fcompare-trend"></canvas>
        </div>

        <?php self::renderChartScripts( $players, $trends, $radar_sets ); ?>
        <?php
    }

    /**
     * #0077 M6 — radar + trend overlay scripts, mirrors
     * PlayerComparisonPage::renderChartScripts. Single-axis trend
     * (no per-category lines) to keep the frontend chart readable on
     * mobile; radar is multi-dataset so all picked players overlay.
     *
     * @param array<int,object> $players
     * @param array<int,array<string,mixed>> $trends
     * @param array<int,array<int,array<string,mixed>>> $radar_sets
     */
    private static function renderChartScripts( array $players, array $trends, array $radar_sets ): void {
        // v4.0.7 (#878) — `getRadarSnapshots()` returns an associative
        // object `{ labels, datasets[] }`. The previous consumer
        // indexed `$radar_sets[$pid][0]` which is a numeric lookup on
        // an assoc array → always null → radar canvas stayed blank.
        // Read the assoc directly and use the LAST dataset (most
        // recent eval — `array_slice(-N)` returns oldest-to-newest).
        $radar_labels_union = [];
        foreach ( $players as $pl ) {
            $pid  = (int) $pl->id;
            $snap = $radar_sets[ $pid ] ?? null;
            if ( $snap && ! empty( $snap['labels'] ) ) {
                foreach ( (array) $snap['labels'] as $lbl ) {
                    $key = (string) $lbl;
                    if ( ! in_array( $key, $radar_labels_union, true ) ) $radar_labels_union[] = $key;
                }
            }
        }
        $radar_datasets = [];
        foreach ( $players as $i => $pl ) {
            $pid  = (int) $pl->id;
            $snap = $radar_sets[ $pid ] ?? null;
            $values = [];
            $latest = is_array( $snap ) && ! empty( $snap['datasets'] )
                ? end( $snap['datasets'] )
                : null;
            if ( $snap && ! empty( $snap['labels'] ) && is_array( $latest ) && ! empty( $latest['values'] ) ) {
                $map = array_combine( $snap['labels'], $latest['values'] );
                foreach ( $radar_labels_union as $lbl ) {
                    $values[] = isset( $map[ $lbl ] ) ? (float) $map[ $lbl ] : null;
                }
            } else {
                $values = array_fill( 0, count( $radar_labels_union ), null );
            }
            $radar_datasets[] = [
                'label'  => QueryHelpers::player_display_name( $pl ),
                'values' => $values,
                'color'  => self::COLORS[ $i % count( self::COLORS ) ],
            ];
        }
        $radar_labels_display = [];
        foreach ( $radar_labels_union as $lbl ) {
            $radar_labels_display[] = EvalCategoriesRepository::displayLabel( (string) $lbl );
        }

        $trend_labels_union = [];
        foreach ( $players as $pl ) {
            $pid = (int) $pl->id;
            $t = $trends[ $pid ] ?? [];
            if ( ! empty( $t['labels'] ) ) {
                foreach ( (array) $t['labels'] as $d ) {
                    if ( ! in_array( (string) $d, $trend_labels_union, true ) ) $trend_labels_union[] = (string) $d;
                }
            }
        }
        sort( $trend_labels_union );

        $trend_datasets = [];
        foreach ( $players as $i => $pl ) {
            $pid = (int) $pl->id;
            $t = $trends[ $pid ] ?? [];
            $points = [];
            if ( ! empty( $t['labels'] ) && ! empty( $t['series'] ) ) {
                $overall = $t['series'][0] ?? null;
                if ( $overall && ! empty( $overall['points'] ) ) {
                    $map = array_combine( $t['labels'], $overall['points'] );
                    foreach ( $trend_labels_union as $d ) {
                        $points[] = $map[ $d ] ?? null;
                    }
                }
            }
            if ( empty( $points ) ) {
                $points = array_fill( 0, count( $trend_labels_union ), null );
            }
            $trend_datasets[] = [
                'label'  => QueryHelpers::player_display_name( $pl ),
                'points' => $points,
                'color'  => self::COLORS[ $i % count( self::COLORS ) ],
            ];
        }

        $rating_max = (float) QueryHelpers::get_config( 'rating_max', '10' );
        ?>
        <script>
        (function(){
            if (typeof Chart === 'undefined') return;
            var ratingMax = <?php echo wp_json_encode( $rating_max ); ?>;

            var radarLabels = <?php echo wp_json_encode( $radar_labels_display ); ?>;
            var radarSets = <?php echo wp_json_encode( $radar_datasets ); ?>;
            var radarEl = document.getElementById('tt-fcompare-radar');
            if (radarEl && radarLabels.length > 0 && radarSets.length > 0) {
                new Chart(radarEl.getContext('2d'), {
                    type: 'radar',
                    data: {
                        labels: radarLabels,
                        datasets: radarSets.map(function (s) {
                            return {
                                label: s.label,
                                data: s.values,
                                borderColor: s.color,
                                backgroundColor: s.color + '22',
                                pointBackgroundColor: s.color,
                                spanGaps: true
                            };
                        })
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        scales: { r: { min: 0, max: ratingMax, ticks: { stepSize: 1 } } },
                        plugins: { legend: { position: 'bottom' } }
                    }
                });
            }

            var trendLabels = <?php echo wp_json_encode( $trend_labels_union ); ?>;
            var trendSets = <?php echo wp_json_encode( $trend_datasets ); ?>;
            var trendEl = document.getElementById('tt-fcompare-trend');
            if (trendEl && trendLabels.length > 0 && trendSets.length > 0) {
                new Chart(trendEl.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: trendLabels,
                        datasets: trendSets.map(function (s) {
                            return {
                                label: s.label,
                                data: s.points,
                                borderColor: s.color,
                                backgroundColor: s.color + '22',
                                pointBackgroundColor: s.color,
                                spanGaps: true,
                                pointRadius: 3
                            };
                        })
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        scales: {
                            y: { min: 0, max: ratingMax, ticks: { stepSize: 1 } },
                            x: { ticks: { maxTicksLimit: 8, autoSkip: true } }
                        },
                        plugins: { legend: { position: 'bottom' } }
                    }
                });
            }
        })();
        </script>
        <?php
    }

    /**
     * v3.94.1 — main-category breakdown rendered as grid rows so it
     * shares the same vertical column alignment as the rest of the
     * compare grid. Caller must already be inside `.tt-fcompare-grid`.
     */
    private static function renderMainBreakdownGrid( array $players, array $mains ): void {
        $all_cats = [];
        foreach ( $mains as $pid => $data ) {
            foreach ( $data as $row ) {
                $key = (string) ( $row->main_label ?? $row->label ?? '' );
                if ( $key !== '' ) $all_cats[ $key ] = $key;
            }
        }
        if ( empty( $all_cats ) ) {
            echo '<div class="tt-fcompare-cell tt-fcompare-label">&nbsp;</div>';
            $n = count( $players );
            for ( $i = 0; $i < $n; $i++ ) {
                echo '<div class="tt-fcompare-cell"><em>' . esc_html__( 'No category data yet for these filters.', 'talenttrack' ) . '</em></div>';
            }
            return;
        }
        foreach ( array_keys( $all_cats ) as $cat_label ) {
            echo '<div class="tt-fcompare-cell tt-fcompare-label">' . esc_html( EvalCategoriesRepository::displayLabel( (string) $cat_label ) ) . '</div>';
            foreach ( $players as $pl ) {
                $pid = (int) $pl->id;
                $val = '—';
                foreach ( ( $mains[ $pid ] ?? [] ) as $row ) {
                    $key = (string) ( $row->main_label ?? $row->label ?? '' );
                    if ( $key === $cat_label ) {
                        $avg = $row->avg ?? $row->average ?? null;
                        if ( $avg !== null ) $val = number_format( (float) $avg, 2 );
                        break;
                    }
                }
                echo '<div class="tt-fcompare-cell tt-fcompare-num">' . esc_html( $val ) . '</div>';
            }
        }
    }

    /**
     * Main-category breakdown table. Collect all unique main-category
     * labels across the 4 players, then render one row per category
     * with per-player averages.
     *
     * @deprecated v3.94.1 — `renderMainBreakdownGrid` replaces this for
     *             the comparison view; kept in case any other caller
     *             still uses it.
     */
    private static function renderMainBreakdown( array $players, array $mains ): void {
        // Collect unique main category labels across all players
        $all_cats = [];
        foreach ( $mains as $pid => $data ) {
            foreach ( $data as $row ) {
                $key = (string) ( $row->main_label ?? $row->label ?? '' );
                if ( $key !== '' ) $all_cats[ $key ] = $key;
            }
        }

        if ( empty( $all_cats ) ) {
            echo '<p><em>' . esc_html__( 'No category data yet for these filters.', 'talenttrack' ) . '</em></p>';
            return;
        }
        ?>
        <div class="tt-table-wrap">
            <table class="tt-table" style="width:100%; background:#fff;">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Category', 'talenttrack' ); ?></th>
                        <?php foreach ( $players as $pl ) : ?>
                            <th><?php echo esc_html( QueryHelpers::player_display_name( $pl ) ); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( array_keys( $all_cats ) as $cat_label ) : ?>
                        <tr>
                            <td style="font-weight:600;"><?php echo esc_html( EvalCategoriesRepository::displayLabel( (string) $cat_label ) ); ?></td>
                            <?php foreach ( $players as $pl ) :
                                $pid = (int) $pl->id;
                                $val = '—';
                                foreach ( ( $mains[ $pid ] ?? [] ) as $row ) {
                                    $key = (string) ( $row->main_label ?? $row->label ?? '' );
                                    if ( $key === $cat_label ) {
                                        $avg = $row->avg ?? $row->average ?? null;
                                        if ( $avg !== null ) $val = number_format( (float) $avg, 2 );
                                        break;
                                    }
                                }
                                ?>
                                <td style="font-variant-numeric:tabular-nums;"><?php echo esc_html( $val ); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
