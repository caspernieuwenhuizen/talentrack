<?php
namespace TT\Shared\Frontend;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Domain\Vocabularies\Lookups\PlayerStatus;
use TT\Domain\Vocabularies\Lookups\PotentialBand;
use TT\Infrastructure\Query\LookupTranslator;
use TT\Infrastructure\Query\PlayerFileCounts;
use TT\Infrastructure\Query\QueryHelpers;
use TT\Infrastructure\Stats\PlayerStatsService;
use TT\Modules\Authorization\AgeTier;
use TT\Modules\Authorization\MatrixGate;
use TT\Modules\Media\Authorization\MediaVisibilityService;
use TT\Modules\Media\MediaEntityType;
use TT\Modules\Players\Services\ProfileCardsConfig;
use TT\Shared\Frontend\Components\EmptyStateCard;
use TT\Shared\Frontend\Components\MediaGallery;
use TT\Shared\Frontend\Components\RecordLink;

/**
 * FrontendPlayerDetailView — primary working surface for one player at
 * `?tt_view=players&id=N`.
 *
 * Cap-gated on `tt_view_players`. Composition only: every datum comes
 * from existing repositories / QueryHelpers — no business logic in the
 * render (CLAUDE.md § 4 SaaS-readiness rule).
 *
 * The visible layout is the surface defined in
 * `.local-mockups/player-profile/index.html`: paper-background hero
 * with a status-coloured avatar ring, an action row, a 3-up key-facts
 * strip, an at-a-glance KPI strip, then pill-chip tabs with count
 * badges (Profile / Goals / Evaluations / Activities / PDP / Trials /
 * Notes). Mobile-first; at ≥1024px the key-facts + KPIs move into a
 * 320px left rail while the tabs + active pane occupy a right column.
 */
final class FrontendPlayerDetailView extends FrontendViewBase {

    private static bool $detail_css_enqueued = false;

    private static function enqueueDetailCss(): void {
        if ( self::$detail_css_enqueued ) return;
        wp_enqueue_style(
            'tt-frontend-player-detail',
            TT_PLUGIN_URL . 'assets/css/frontend-player-detail.css',
            [ 'tt-frontend-mobile', 'tt-frontend-app-chrome' ],
            TT_VERSION
        );
        self::$detail_css_enqueued = true;
    }

    /**
     * Hero quick-record popovers (behaviour + potential). Only enqueues
     * + localises when the current user has at least one of the
     * recording caps — no payload sent to scouts / parents who can't
     * trigger anything anyway.
     */
    private static function enqueueHeroPopovers( int $player_id, ?object $player ): void {
        $can_log_behaviour = \TT\Modules\Players\PlayerStatusModule::behaviourCaptureAvailable();
        $can_set_potential = \TT\Modules\Players\PlayerStatusModule::potentialCaptureAvailable();
        if ( ! $can_log_behaviour && ! $can_set_potential ) return;

        wp_enqueue_style(
            'tt-frontend-player-hero-popovers',
            TT_PLUGIN_URL . 'assets/css/frontend-player-hero-popovers.css',
            [ 'tt-frontend-mobile' ],
            TT_VERSION
        );
        wp_enqueue_script(
            'tt-frontend-player-hero-popovers',
            TT_PLUGIN_URL . 'assets/js/frontend-player-hero-popovers.js',
            [],
            TT_VERSION,
            true
        );

        // #1320 — routed through ActivitiesRepository::listRecentCompletedForPlayer
        // so this site and FrontendPlayerStatusCaptureView's sibling
        // converge on a single source of the "recent completed
        // activities for behaviour rating" query.
        $recent = $can_log_behaviour
            ? ( new \TT\Modules\Activities\Repositories\ActivitiesRepository() )->listRecentCompletedForPlayer( $player_id, 20 )
            : [];
        $activities = [];
        foreach ( $recent as $a ) {
            $activities[] = [
                'id'    => (int) $a->id,
                'label' => sprintf( '%s · %s', (string) $a->session_date, (string) $a->title ),
            ];
        }

        $rmin = (int) round( (float) \TT\Infrastructure\Query\QueryHelpers::get_config( 'rating_min', '5' ) );
        $rmax = (int) round( (float) \TT\Infrastructure\Query\QueryHelpers::get_config( 'rating_max', '10' ) );

        // #3226 — one source for the band labels. This map and the capture
        // screen's had drifted apart ("First-team" here, "First team"
        // there), which is the reason a third copy was not an option.
        $bands = [];
        foreach ( \TT\Modules\Players\Services\PotentialTrajectory::labels() as $key => $label ) {
            $bands[] = [ 'key' => $key, 'label' => $label ];
        }

        $history_url = add_query_arg(
            [ 'tt_view' => 'player-status-capture', 'player_id' => $player_id ],
            \TT\Shared\Frontend\Components\RecordLink::dashboardUrl()
        );

        // #2876 — this used to read `$player->potential_band`, a column
        // `tt_players` does not have. Reading a missing property on a wpdb
        // row is silent, so the ternary took its else branch every time and
        // the popover opened blank for every player, whatever was on record.
        // Potential lives in `tt_player_potential` as dated history rows;
        // the repository that owns them is the only thing that knows the
        // current band.
        //
        // Blank is not a harmless default here. A coach who cannot see what
        // the academy currently thinks records a fresh guess instead of a
        // revision, and every save appends a row — so the history filled with
        // re-statements that read as changes of mind.
        $latest_potential = ( new \TT\Modules\Players\Repositories\PlayerPotentialRepository() )
            ->latestFor( $player_id );

        $current_band = $latest_potential ? (string) ( $latest_potential->potential_band ?? '' ) : '';
        $set_at       = $latest_potential ? (string) ( $latest_potential->set_at ?? '' ) : '';
        $set_by_id    = $latest_potential ? (int) ( $latest_potential->set_by ?? 0 ) : 0;
        $set_by_name  = '';
        if ( $set_by_id > 0 ) {
            $setter = get_userdata( $set_by_id );
            if ( $setter instanceof \WP_User ) {
                $set_by_name = (string) $setter->display_name;
            }
        }

        // "First team, set by Kevin in March" is a different prompt from a
        // bare pre-selected radio — it tells the coach whether the standing
        // judgement is current enough to be worth revising.
        $current_potential_meta = '';
        if ( $current_band !== '' && $set_at !== '' ) {
            $when = mysql2date( get_option( 'date_format' ), $set_at, true );
            $current_potential_meta = $set_by_name !== ''
                /* translators: 1: localised date, 2: name of the person who set it */
                ? sprintf( __( 'Set on %1$s by %2$s', 'talenttrack' ), $when, $set_by_name )
                /* translators: %s: localised date */
                : sprintf( __( 'Set on %s', 'talenttrack' ), $when );
        }

        wp_localize_script( 'tt-frontend-player-hero-popovers', 'TTPlayerHeroPopovers', [
            'rest_url'                => esc_url_raw( rest_url( 'talenttrack/v1/' ) ),
            'rest_nonce'              => wp_create_nonce( 'wp_rest' ),
            'player_id'               => $player_id,
            'rating_min'              => $rmin,
            'rating_max'              => $rmax,
            'activities'              => $activities,
            'potential_bands'         => $bands,
            'current_potential_band'  => $current_band,
            'current_potential_meta'  => $current_potential_meta,
            'history_url'             => $history_url,
            'i18n' => [
                'close'                => __( 'Close',                       'talenttrack' ),
                'cancel'               => __( 'Cancel',                      'talenttrack' ),
                'log_behaviour_title'  => __( 'Log behaviour',               'talenttrack' ),
                'set_potential_title'  => __( 'Set potential',               'talenttrack' ),
                'rating_label'         => __( 'Rating',                      'talenttrack' ),
                'rating_placeholder'   => __( '— pick a rating —',           'talenttrack' ),
                'activity_label'       => __( 'Related activity (optional)', 'talenttrack' ),
                'activity_none'        => __( '— none —',                    'talenttrack' ),
                'notes_label'          => __( 'Notes',                       'talenttrack' ),
                'notes_placeholder'    => __( 'Optional context',            'talenttrack' ),
                'band_label'           => __( 'Potential band',              'talenttrack' ),
                'band_placeholder'     => __( '— pick a band —',             'talenttrack' ),
                // #3241 — the same sentence the capture screen leads with,
                // so the two ways in agree. The full band meanings live on
                // the capture screen, one link away from this popover.
                'band_hint'            => __( 'How high you believe this player can reach at their peak — not where they are now.', 'talenttrack' ),
                'save_behaviour'       => __( 'Save rating',                 'talenttrack' ),
                'save_potential'       => __( 'Update potential',            'talenttrack' ),
                'view_all_behaviour'   => __( 'View all behaviour ratings →','talenttrack' ),
                'view_all_potential'   => __( 'View potential history →',    'talenttrack' ),
                'success_behaviour'    => __( 'Behaviour recorded',          'talenttrack' ),
                'success_potential'    => __( 'Potential updated',           'talenttrack' ),
                'error_generic'        => __( 'Could not save. Try again.',  'talenttrack' ),
                'error_network'        => __( 'Network error. Try again.',   'talenttrack' ),
            ],
        ] );
    }

    /**
     * Tab key → human label. Per #1107, dev-data tabs (Evaluations,
     * PDP, Trials) are matrix-gated so AC users (whose seed strips
     * `evaluations`, `pdp_file`, `trial_*` per #1060 / migration 0136)
     * don't see the tabs at all. Goals + Activities stay always-on —
     * AC's seed grants both at team scope. Notes keeps its existing
     * cap-gate. Pattern matches `renderEvaluationsTab` / `renderPdpTab`
     * / `renderTrialsTab` which gate again on entry for defense in
     * depth (direct `?tab=evaluations` URL needs the same answer).
     *
     * @return array<string,string>  tab key => human label
     */
    private static function tabs( int $user_id ): array {
        $tabs = [
            'profile' => __( 'Profile', 'talenttrack' ),
            'goals'   => __( 'Goals', 'talenttrack' ),
        ];
        if ( MatrixGate::canAnyScope( $user_id, 'evaluations', MatrixGate::READ ) ) {
            $tabs['evaluations'] = __( 'Evaluations', 'talenttrack' );
        }
        // #1892 — measurements in context, beside evaluations (dev-data).
        if ( MatrixGate::canAnyScope( $user_id, 'measurements', MatrixGate::READ ) ) {
            $tabs['measurements'] = __( 'Measurements', 'talenttrack' );
        }
        $tabs['activities'] = __( 'Activities', 'talenttrack' );
        // #2725 — what this player has repeatedly been marked as after a
        // match, and in which phase of play. The individual notes are
        // already on the journey; this is the summary above them. Gated on
        // the same cap the analyses themselves read under.
        if ( current_user_can( 'tt_view_activities' ) ) {
            $tabs['match-analysis'] = __( 'Match analysis', 'talenttrack' );
        }
        // #2500 (epic #2493) — what this player has actually been taught,
        // per principle, from the trainings they attended. Gated on its
        // own `training_exposure` entity rather than on `training_plan`:
        // reading a player's training history and planning a training are
        // different rights, and this one is player data (D16).
        if ( MatrixGate::canAnyScope( $user_id, 'training_exposure', MatrixGate::READ ) ) {
            $tabs['training'] = __( 'Training', 'talenttrack' );
        }
        // #2061 (epic #2002) — per-player Strava connection + imported
        // training, alongside the team-session activities.
        $tabs['strava'] = __( 'Strava', 'talenttrack' );
        if ( MatrixGate::canAnyScope( $user_id, 'pdp_file', MatrixGate::READ ) ) {
            $tabs['pdp'] = __( 'PDP cycle', 'talenttrack' );
        }
        if ( MatrixGate::canAnyScope( $user_id, 'trial_cases', MatrixGate::READ ) ) {
            $tabs['trials'] = __( 'Trials', 'talenttrack' );
        }
        // #2609 — injuries in context. Gated on the `player_injuries`
        // entity, so an assistant coach (who holds no row for it at all)
        // never sees the tab, let alone its contents.
        if ( MatrixGate::canAnyScope( $user_id, 'player_injuries', MatrixGate::READ ) ) {
            $tabs['injuries'] = __( 'Injuries', 'talenttrack' );
        }
        // #2594 (epic #2589) — photos and video in context. Gated on the
        // `media` entity, which `canAnyScope()` also runs the feature
        // toggle against, so switching media off takes the tab with it.
        if ( MatrixGate::canAnyScope( $user_id, MediaVisibilityService::ENTITY, MatrixGate::READ ) ) {
            $tabs['media'] = __( 'Media', 'talenttrack' );
        }
        if ( current_user_can( 'tt_view_player_notes' ) ) {
            $tabs['notes'] = __( 'Notes', 'talenttrack' );
        }
        // #1988 — the "My card" showcase (FIFA card + skills radar +
        // rating KPIs) folded into the one unified profile as a tab.
        // Same audience as the page (tt_view_players); no separate cap.
        $tabs['card'] = __( 'Player card', 'talenttrack' );
        // Analytics tab removed v3.110.187 — operators reach the
        // dimension explorer via ?tt_view=explore.
        return $tabs;
    }

    public static function render( int $player_id, int $user_id, bool $is_admin, string $default_tab = 'profile' ): void {
        // #2107 — `canViewPlayer()` is the single authority: own record /
        // global / player's team / parent-of-this-player. The old coarse
        // `current_user_can('tt_view_players')` pre-check was redundant — it
        // only ever filtered *before* this finer gate — AND it wrongly blocked
        // a player (or parent) from landing on their own unified profile, so
        // it's dropped. Staff still pass via the global / team `players.view`
        // branch inside canViewPlayer(); a player passes via own-record; a
        // parent via parent-of-this-player. Anyone else is denied.
        if ( ! \TT\Infrastructure\Security\AuthorizationService::canViewPlayer( $user_id, $player_id ) ) {
            \TT\Shared\Frontend\Components\FrontendBreadcrumbs::fromDashboard( __( 'Not authorized', 'talenttrack' ) );
            echo '<p class="tt-notice">' . esc_html__( 'You do not have permission to view this player.', 'talenttrack' ) . '</p>';
            return;
        }

        // #2064 — PHV is VCT functionality (#1089 VCT-14). This Shared view
        // always loads, so it has to gate the PHV touchpoints on the VCT
        // module being enabled itself — ModuleRegistry only stops
        // VctModule::register()/boot(), not UI an always-loaded view renders.
        $vct_on = \TT\Core\ModuleRegistry::isEnabled( \TT\Modules\Vct\VctModule::class );

        // #1089 VCT-14 — handle PHV-panel POST before rendering so the
        // panel reflects the just-saved state. Cap-gated inside. Ignored
        // entirely when VCT is off (#2064).
        $phv_panel_notice = '';
        if ( $vct_on && $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['_tt_vct_phv_panel'] ) ) {
            $phv_panel_notice = self::handleVctPhvPost( $player_id, $user_id );
        }

        $player = QueryHelpers::get_player( $player_id );

        if ( ! $player ) {
            // #2022 — the active lookup ends in `archived_at IS NULL`, so an
            // archived / trashed player never reaches it. Retry through the
            // archive-aware gate before falling to not-found. A null return
            // (genuinely absent OR a trashed record the caller may not see)
            // stays a clean 404 — never a page that confirms it exists.
            $resolved = \TT\Shared\Frontend\Components\ArchivedDetailCard::resolve( 'player', $player_id );
            if ( $resolved !== null && $resolved['state'] !== 'active' ) {
                self::renderArchivedReadOnly( $resolved );
                return;
            }
            \TT\Shared\Frontend\Components\FrontendBreadcrumbs::fromDashboard(
                __( 'Player not found', 'talenttrack' ),
                [ \TT\Shared\Frontend\Components\FrontendBreadcrumbs::viewCrumb( 'players', __( 'Players', 'talenttrack' ) ) ]
            );
            self::renderHeader( __( 'Player not found', 'talenttrack' ) );
            echo '<p><em>' . esc_html__( 'That player is no longer available, or you do not have access.', 'talenttrack' ) . '</em></p>';
            return;
        }

        self::enqueueAssets();
        self::enqueueDetailCss();
        self::enqueueHeroPopovers( $player_id, $player );
        $name = QueryHelpers::player_display_name( $player );

        $players_url = add_query_arg( [ 'tt_view' => 'players' ], RecordLink::dashboardUrl() );

        // #2107 — persona-framed breadcrumb (nav §5). Staff navigate via the
        // Players list, so they keep the "Dashboard › Players › Name" chain.
        // A player on their own profile — or a parent on their child's — can't
        // reach the Players list, so the "Players" crumb would be a dead end;
        // give them the personal "Dashboard › My card" (self) / "Dashboard ›
        // Name" (parent viewing a child) crumb instead.
        $is_staff = current_user_can( 'tt_view_players' );
        $is_self  = $user_id > 0 && (int) ( $player->wp_user_id ?? 0 ) === $user_id;
        if ( $is_staff ) {
            \TT\Shared\Frontend\Components\FrontendBreadcrumbs::render( [
                [ 'label' => __( 'Dashboard', 'talenttrack' ), 'url' => RecordLink::dashboardUrl() ],
                [ 'label' => __( 'Players', 'talenttrack' ),   'url' => $players_url ],
                [ 'label' => $name ],
            ] );
        } else {
            \TT\Shared\Frontend\Components\FrontendBreadcrumbs::fromDashboard(
                $is_self ? __( 'My profile', 'talenttrack' ) : $name
            );
        }

        $team       = ! empty( $player->team_id ) ? QueryHelpers::get_team( (int) $player->team_id ) : null;
        $team_url   = $team ? add_query_arg( [ 'tt_view' => 'teams', 'id' => (int) $team->id ], RecordLink::dashboardUrl() ) : '';

        $tab_set    = self::tabs( $user_id );
        $active_tab = isset( $_GET['tab'] ) ? sanitize_key( (string) wp_unslash( $_GET['tab'] ) ) : $default_tab;
        if ( ! array_key_exists( $active_tab, $tab_set ) ) {
            $active_tab = array_key_exists( $default_tab, $tab_set ) ? $default_tab : 'profile';
        }
        $base_url = add_query_arg( [ 'tt_view' => 'players', 'id' => $player_id ], RecordLink::dashboardUrl() );

        $counts = PlayerFileCounts::for( $player_id );
        ?>
        <article class="tt-player-detail" data-tab="<?php echo esc_attr( $active_tab ); ?>">
            <?php
            // #2064 — only look up the PHV flag when VCT is on; null keeps
            // the hero pill hidden (it keys off $phv_row).
            // #2107 — PHV is a maturation / health-adjacent flag (minors).
            // Now that a player / parent lands on this same profile, keep it
            // staff-only: leave $phv_row null for non-staff so neither the
            // hero pill nor the panel surface it.
            $phv_row = ( $vct_on && self::viewerIsStaffForPlayer( $player_id ) )
                ? ( new \TT\Modules\Vct\Repositories\VctPhvFlagsRepository() )->findForPlayer( $player_id )
                : null;
            self::renderHero( $player, $name, $team, $team_url, $phv_row );
            self::renderActionRow( $player, $players_url );
            ?>

            <div class="tt-player-detail__rail">
                <?php
                // #2633 (epic #2629) — OPEN alerts about this player, at the
                // top of the rail, answering "what is missing from this
                // player's record right now?" at the moment somebody is
                // looking at that record and could act on it.
                //
                // Two things this deliberately does NOT do (epic decision
                // 12). It shows no resolved occurrence: the journey records
                // what happened to the player, and an alert records what
                // staff failed to record, which is a different thing wearing
                // similar clothing. And it writes nothing to the journey at
                // all — at 90-day retention a journey entry would vanish
                // retroactively, which is worse than never having been
                // written.
                self::renderPlayerAlerts( $player_id );
                self::renderKeyFacts( $player );
                self::renderAtAGlance( $player_id, $player, $base_url, $counts );
                ?>
            </div>

            <div class="tt-player-detail__main">
                <?php
                // #2457 — under the app shell the tab strip is wrapped in a
                // sticky container that also carries a slim identity row, so
                // scrolling a long pane never loses track of whose record this
                // is (CLAUDE.md §1). The full hero above still anchors the
                // screen on arrival; this is what survives the scroll.
                //
                // Under `classic` the wrapper is not emitted at all, so that
                // shell renders exactly as before (#2456's rollback contract).
                $sticky_on = \TT\Shared\Frontend\ShellPreference::isApp( $user_id );
                if ( $sticky_on ) :
                    ?>
                    <div class="tt-player-sticky">
                        <?php
                        // aria-hidden: the accessible name for this record is
                        // the <h1> in the hero. Repeating it here would make a
                        // screen reader announce the player twice on the same
                        // page; this copy exists to orient the eye while
                        // scrolled, nothing more.
                        ?>
                        <div class="tt-player-sticky__id" aria-hidden="true">
                            <span class="tt-player-sticky__avatar" data-status="<?php echo esc_attr( (string) ( $player->status ?? 'inactive' ) ); ?>">
                                <?php if ( (string) ( $player->photo_url ?? '' ) !== '' ) : ?>
                                    <img class="tt-player-sticky__photo" src="<?php echo esc_url( (string) $player->photo_url ); ?>" alt="" />
                                <?php else : ?>
                                    <?php echo esc_html( self::initialsFor( $name ) ); ?>
                                <?php endif; ?>
                            </span>
                            <span class="tt-player-sticky__name"><?php echo esc_html( $name ); ?></span>
                            <?php if ( $team ) : ?>
                                <span class="tt-player-sticky__team"><?php echo esc_html( (string) $team->name ); ?></span>
                            <?php endif; ?>
                        </div>
                <?php endif; ?>
                <nav class="tt-player-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Player sections', 'talenttrack' ); ?>">
                    <?php foreach ( $tab_set as $key => $label ) :
                        $url       = add_query_arg( [ 'tab' => $key ], $base_url );
                        $is_active = $key === $active_tab;
                        $count     = $counts[ $key ] ?? null;
                        $classes   = 'tt-player-tab';
                        if ( $is_active ) $classes .= ' tt-player-tab--active';
                        ?>
                        <a href="<?php echo esc_url( $url ); ?>"
                           class="<?php echo esc_attr( $classes ); ?>"
                           role="tab"
                           aria-current="<?php echo $is_active ? 'true' : 'false'; ?>"
                           aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>">
                            <?php echo esc_html( $label ); ?>
                            <?php if ( $key !== 'profile' && (int) $count > 0 ) : ?>
                                <span class="tt-player-tab__count"><?php echo (int) $count; ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
                <?php if ( $sticky_on ) : ?>
                    </div><!-- .tt-player-sticky -->
                <?php endif; ?>

                <section class="tt-player-tab-panel">
                    <?php
                    switch ( $active_tab ) {
                        case 'goals':       self::renderGoalsTab( $player_id ); break;
                        case 'evaluations': self::renderEvaluationsTab( $player_id ); break;
                        case 'measurements': self::renderMeasurementsTab( $player_id ); break;
                        case 'activities':  self::renderActivitiesTab( $player_id, $player ); break;
                        case 'training':
                            \TT\Modules\Training\Frontend\PlayerTrainingTab::render( $player_id, $user_id );
                            break;
                        case 'match-analysis':
                            \TT\Modules\MatchAnalysis\Frontend\PlayerMatchAnalysisTab::render( $player_id, $user_id );
                            break;
                        case 'strava':      \TT\Modules\Strava\Frontend\FrontendStravaView::renderPanel( $player ); break;
                        case 'pdp':         self::renderPdpTab( $player_id ); break;
                        case 'trials':      self::renderTrialsTab( $player_id, $player ); break;
                        case 'injuries':    self::renderInjuriesTab( $player_id, $user_id ); break;
                        case 'media':       self::renderMediaTab( $player_id, $user_id ); break;
                        case 'notes':       self::renderNotesTab( $player_id, $user_id ); break;
                        case 'card':        self::renderCardTab( $player ); break;
                        case 'profile':
                        default:            self::renderProfileTab( $player, $phv_row, $phv_panel_notice, $vct_on ); break;
                    }
                    ?>
                </section>
            </div>
        </article>
        <?php
    }

    /**
     * #2022 — compact read-only surface for an archived / trashed player.
     * Reached only via the not-found retry in render(): the active lookup
     * already failed, the archive-aware gate returned a non-active row, and
     * the trashed-visibility check passed inside the domain method. Shows the
     * identity anchor + a handful of key facts + the lifecycle banner; no Edit
     * affordance (restore first, then edit).
     *
     * @param array{row:object,state:string} $resolved
     */
    private static function renderArchivedReadOnly( array $resolved ): void {
        $player = $resolved['row'];
        $name   = QueryHelpers::player_display_name( $player );

        \TT\Shared\Frontend\Components\FrontendBreadcrumbs::fromDashboard(
            $name,
            [ \TT\Shared\Frontend\Components\FrontendBreadcrumbs::viewCrumb( 'players', __( 'Players', 'talenttrack' ) ) ]
        );
        \TT\Shared\Frontend\Components\ArchivedDetailCard::enqueue();

        $players_url = add_query_arg( [ 'tt_view' => 'players' ], RecordLink::dashboardUrl() );
        $self_url    = add_query_arg( [ 'tt_view' => 'players', 'id' => (int) $player->id ], RecordLink::dashboardUrl() );

        $fields = [];
        $dob = (string) ( $player->date_of_birth ?? '' );
        if ( $dob !== '' && $dob !== '0000-00-00' ) {
            $fields[] = [ __( 'Date of birth', 'talenttrack' ), esc_html( self::shortDate( $dob ) ) ];
        }
        $positions = json_decode( (string) ( $player->preferred_positions ?? '' ), true );
        if ( is_array( $positions ) && ! empty( $positions ) ) {
            // #2155 — long position descriptions on the profiles/cards group.
            $pos_long = array_map( [ \TT\Infrastructure\Query\LabelTranslator::class, 'positionLabel' ], array_map( 'strval', $positions ) );
            $fields[] = [ __( 'Position(s)', 'talenttrack' ), esc_html( implode( ' · ', $pos_long ) ) ];
        }
        if ( ! empty( $player->status ) ) {
            $fields[] = [ __( 'Status', 'talenttrack' ), esc_html( \TT\Infrastructure\Query\LabelTranslator::playerStatus( (string) $player->status ) ) ];
        }
        $team = ! empty( $player->team_id ) ? QueryHelpers::get_team( (int) $player->team_id ) : null;
        if ( $team ) {
            $fields[] = [ __( 'Team', 'talenttrack' ), esc_html( (string) $team->name ) ];
        }

        \TT\Shared\Frontend\Components\ArchivedDetailCard::render( 'player', $resolved, [
            'title'            => $name,
            'initials'         => self::initialsFor( $name ),
            'photo_url'        => (string) ( $player->photo_url ?? '' ),
            'fields'           => $fields,
            'list_url'         => $players_url,
            'restore_redirect' => $self_url,
        ] );
    }

    /**
     * Paper-backed hero. The status signal lives on a 4px coloured
     * border around the initials/photo avatar; jersey number is a
     * small badge tucked into the avatar's bottom-right corner.
     */
    private static function renderHero( object $player, string $name, ?object $team, string $team_url, ?array $phv_row = null ): void {
        $status    = (string) ( $player->status ?? 'inactive' );
        $photo     = (string) ( $player->photo_url ?? '' );
        $jersey    = ! empty( $player->jersey_number ) ? (int) $player->jersey_number : 0;
        $positions = json_decode( (string) ( $player->preferred_positions ?? '' ), true );
        // #3329 — one pill per position, in stored order.
        //
        // The hero kept `$positions[0]` and dropped the rest, so a player
        // with four positions read as a specialist in the header while the
        // Identity row, the sidebar card and the edit form all listed four.
        // For an academy that treats positional range as a development
        // signal, that is the wrong first impression on the most-trafficked
        // screen in the plugin.
        //
        // Showing all of them only became possible with #3246: every
        // position lookup now carries a per-locale abbreviation, so four
        // pills fit where four long Dutch labels never would. #2155's choice
        // of the long description was right for a single pill and is what
        // the `title` still carries.
        //
        // `get_lookup_abbrev_pairs()` omits rows with no abbreviation rather
        // than returning an empty string, so the fallback is written here:
        // an academy that has not filled them in keeps readable full labels
        // instead of losing the position, and never sees the internal key.
        $abbrev_map = QueryHelpers::get_lookup_abbrev_pairs( 'position' );
        $position_pills = [];
        if ( is_array( $positions ) ) {
            foreach ( $positions as $code ) {
                $code = (string) $code;
                if ( $code === '' ) continue;
                $full = \TT\Infrastructure\Query\LabelTranslator::positionLabel( $code );
                $position_pills[] = [
                    'short' => (string) ( $abbrev_map[ $code ] ?? $full ),
                    'full'  => $full,
                ];
            }
        }
        $journey   = self::journeyText( $player );
        // #1089 VCT-14 — orange PHV pill on the hero when active.
        $phv_active = $phv_row !== null && ! empty( $phv_row['is_active'] );
        ?>
        <header class="tt-player-detail__hero" aria-label="<?php esc_attr_e( 'Player', 'talenttrack' ); ?>">
            <div class="tt-player-hero__row">
                <div class="tt-player-hero__avatar" data-status="<?php echo esc_attr( $status ); ?>" aria-hidden="true">
                    <?php if ( $photo !== '' ) : ?>
                        <img class="tt-player-hero__photo" src="<?php echo esc_url( $photo ); ?>" alt="" />
                    <?php else : ?>
                        <?php echo esc_html( self::initialsFor( $name ) ); ?>
                    <?php endif; ?>
                    <?php if ( $jersey > 0 ) : ?>
                        <span class="tt-player-hero__jersey">#<?php echo (int) $jersey; ?></span>
                    <?php endif; ?>
                </div>
                <div class="tt-player-hero__main">
                    <h1 class="tt-player-hero__name">
                        <?php echo esc_html( $name ); ?>
                        <?php if ( $phv_active ) : ?>
                            <span class="tt-player-phv-pill" title="<?php esc_attr_e( 'Physical / Health / Vitality flag — workload adjusted', 'talenttrack' ); ?>"><?php esc_html_e( 'PHV', 'talenttrack' ); ?></span>
                        <?php endif; ?>
                    </h1>
                    <?php if ( $team ) : ?>
                        <p class="tt-player-hero__sub">
                            <a href="<?php echo esc_url( $team_url ); ?>"><?php echo esc_html( (string) $team->name ); ?></a>
                            <?php if ( ! empty( $team->age_group ) ) : ?>
                                <span> · <?php echo esc_html( \TT\Infrastructure\Query\LookupTranslator::byTypeAndName( 'age_group', (string) $team->age_group ) ); ?></span>
                            <?php endif; ?>
                        </p>
                    <?php else : ?>
                        <p class="tt-player-hero__sub"><em><?php esc_html_e( 'Unassigned', 'talenttrack' ); ?></em></p>
                    <?php endif; ?>
                    <p class="tt-player-hero__pills">
                        <?php
                        $status_label = \TT\Infrastructure\Query\LabelTranslator::playerStatus( $status );
                        $journey_text = $journey !== null && $journey['days'] !== '' ? $journey['days'] : '';
                        ?>
                        <span class="tt-player-pill" data-status="<?php echo esc_attr( $status ); ?>">
                            <?php echo esc_html( $status_label ); ?>
                            <?php if ( $journey_text !== '' ) : ?>
                                · <?php echo esc_html( $journey_text ); ?>
                            <?php endif; ?>
                        </span>
                        <?php foreach ( $position_pills as $pill ) : ?>
                            <span class="tt-player-pill tt-player-pill--pos"
                                  title="<?php echo esc_attr( $pill['full'] ); ?>"><?php
                                echo esc_html( $pill['short'] );
                            ?><?php if ( $pill['short'] !== $pill['full'] ) : ?>
                                <span class="tt-screen-reader-text"><?php echo esc_html( $pill['full'] ); ?></span>
                            <?php endif; ?></span>
                        <?php endforeach; ?>
                    </p>
                </div>
            </div>
        </header>
        <?php
    }

    /**
     * Action row — `+ Log behaviour` · `Set potential` · `Edit` · `⋯`
     * overflow with Archive + Assign-to-team. Cap-gated identically
     * to the legacy page-header actions: Log behaviour →
     * `tt_rate_player_behaviour`, Set potential →
     * `tt_set_player_potential`, Edit + overflow items →
     * `tt_edit_players`.
     */
    private static function renderActionRow( object $player, string $players_url ): void {
        $player_id         = (int) $player->id;
        $can_log_behaviour = \TT\Modules\Players\PlayerStatusModule::behaviourCaptureAvailable();
        $can_set_potential = \TT\Modules\Players\PlayerStatusModule::potentialCaptureAvailable();
        $can_edit          = current_user_can( 'tt_edit_players' );

        // Render the row even when empty so the surface keeps its
        // visual rhythm (the paper-bottom shadow under the hero
        // continues into the action band).
        $edit_url = add_query_arg(
            [ 'tt_view' => 'players', 'id' => $player_id, 'action' => 'edit' ],
            RecordLink::dashboardUrl()
        );
        $assign_url = add_query_arg(
            [ 'tt_view' => 'players', 'id' => $player_id, 'tab' => 'profile' ],
            RecordLink::dashboardUrl()
        ) . '#tt-player-assign-team';

        $has_overflow = $can_edit; // overflow holds Archive + optional Assign.
        ?>
        <div class="tt-player-detail__actions" aria-label="<?php esc_attr_e( 'Actions', 'talenttrack' ); ?>">
            <?php if ( $can_log_behaviour ) : ?>
                <button type="button" class="tt-player-action tt-player-action--primary" data-tt-popover-trigger="behaviour">
                    + <?php esc_html_e( 'Log behaviour', 'talenttrack' ); ?>
                </button>
            <?php endif; ?>
            <?php if ( $can_set_potential ) : ?>
                <button type="button" class="tt-player-action" data-tt-popover-trigger="potential">
                    <?php esc_html_e( 'Set potential', 'talenttrack' ); ?>
                </button>
            <?php endif; ?>
            <?php if ( $can_edit ) : ?>
                <?php
                // #2871 — Edit is a pencil beside the ⋯ rather than a text
                // button at the far left. It is the one action taken on the
                // record itself, and a full-width label bought it a row of
                // header height that pushed the profile below the fold on a
                // phone. The label survives as both the accessible name and
                // the hover title, so the msgid does not change.
                $edit_label = __( 'Edit', 'talenttrack' );
                ?>
                <a class="tt-player-action tt-player-action--edit"
                   href="<?php echo esc_url( $edit_url ); ?>"
                   aria-label="<?php echo esc_attr( $edit_label ); ?>"
                   title="<?php echo esc_attr( $edit_label ); ?>">
                    <?php
                    echo \TT\Shared\Icons\IconRenderer::render( 'edit', [ 'width' => 18, 'height' => 18 ] );
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — trusted SVG.
                    ?>
                </a>
            <?php endif; ?>
            <?php if ( $has_overflow ) : ?>
                <div class="tt-player-action tt-player-action--more"
                     role="button"
                     tabindex="0"
                     aria-haspopup="true"
                     aria-expanded="false"
                     aria-label="<?php esc_attr_e( 'More actions', 'talenttrack' ); ?>"
                     onclick="this.setAttribute('aria-expanded', this.getAttribute('aria-expanded') === 'true' ? 'false' : 'true');"
                     onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();this.setAttribute('aria-expanded', this.getAttribute('aria-expanded') === 'true' ? 'false' : 'true');}">
                    ⋯
                    <div class="tt-player-action__menu" role="menu">
                        <?php if ( empty( $player->team_id ) ) : ?>
                            <a class="tt-player-action" href="<?php echo esc_url( $assign_url ); ?>" role="menuitem">
                                <?php esc_html_e( 'Assign to team', 'talenttrack' ); ?>
                            </a>
                        <?php endif; ?>
                        <?php
                        // #1017 Phase 7 — chemistry-attribute entry for staff
                        // who can rate players. §7 (#2304) — gated via the
                        // `player-attributes` registry gate, which tightens
                        // this to the per-player canEvaluatePlayer check the
                        // target view enforces (was a flat tt_edit_evaluations
                        // cap here).
                        \TT\Shared\Frontend\Components\CrossViewLink::render(
                            'player-attributes',
                            static function () use ( $player_id ): void {
                                $attr_url = add_query_arg(
                                    [ 'tt_view' => 'player-attributes', 'player_id' => $player_id ],
                                    RecordLink::dashboardUrl()
                                );
                                ?>
                                <a class="tt-player-action" href="<?php echo esc_url( $attr_url ); ?>" role="menuitem">
                                    <?php esc_html_e( 'Chemistry attributes', 'talenttrack' ); ?>
                                </a>
                                <?php
                            },
                            [ 'ctx' => [ 'player_id' => $player_id ] ]
                        );
                        // #2606 (epic #2600) — "what has this family been
                        // told?", asked from the player rather than from a
                        // global log somebody then narrows (§1). Carries
                        // `tt_back` so the message log renders the pill back
                        // to this record. Gated through the registry, which
                        // resolves to the log's own read capability.
                        \TT\Shared\Frontend\Components\CrossViewLink::render(
                            'messages',
                            static function () use ( $player_id ): void {
                                $messages_url = \TT\Shared\Frontend\Components\BackLink::appendTo(
                                    add_query_arg(
                                        [ 'tt_view' => 'messages', 'player_id' => $player_id ],
                                        RecordLink::dashboardUrl()
                                    )
                                );
                                ?>
                                <a class="tt-player-action" href="<?php echo esc_url( $messages_url ); ?>" role="menuitem">
                                    <?php esc_html_e( 'Messages sent', 'talenttrack' ); ?>
                                </a>
                                <?php
                            },
                            [ 'ctx' => [ 'player_id' => $player_id ] ]
                        );
                        ?>
                        <button type="button"
                                class="tt-player-action tt-player-action--danger"
                                role="menuitem"
                                data-tt-archive-rest-path="<?php echo esc_attr( 'players/' . $player_id ); ?>"
                                data-tt-archive-confirm="<?php echo esc_attr__( 'Archive this player? They can be restored later by a site admin.', 'talenttrack' ); ?>"
                                data-tt-archive-redirect="<?php echo esc_attr( $players_url ); ?>">
                            <?php esc_html_e( 'Archive', 'talenttrack' ); ?>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Surface DOB / Foot / Joined above the tabs. Coaches stop having
     * to open Profile to check basic identity.
     */
    /**
     * #2633 — the player-record alert surface.
     *
     * Renders nothing when the player's record is clean, which is the
     * common case: an empty "Alerts (0)" card on every profile would train
     * everyone to stop seeing the section on the day it finally has
     * something in it.
     */
    private static function renderPlayerAlerts( int $player_id ): void {
        $chip = \TT\Shared\Frontend\Components\AlertChip::playerHtml( $player_id );
        if ( $chip === '' ) return;

        echo '<section class="tt-player-alerts" aria-label="' . esc_attr__( 'Open alerts', 'talenttrack' ) . '">';
        echo '<p class="tt-player-glance__title">' . esc_html__( 'Needs attention', 'talenttrack' ) . '</p>';
        echo $chip; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in AlertChip.
        echo '</section>';
    }

    private static function renderKeyFacts( object $player ): void {
        // #2573 — normalise stored zero dates to '' so they render as '—'
        // rather than as a real-looking date.
        $dob_raw    = (string) ( \TT\Infrastructure\Query\QueryHelpers::usableDate( $player->date_of_birth ?? '' ) ?? '' );
        $foot_raw   = (string) ( $player->preferred_foot ?? '' );
        $joined_raw = (string) ( \TT\Infrastructure\Query\QueryHelpers::usableDate( $player->date_joined ?? '' ) ?? '' );
        $positions  = json_decode( (string) ( $player->preferred_positions ?? '' ), true );

        $dob_value = $dob_raw !== '' ? self::shortDate( $dob_raw ) : '—';
        $dob_hint  = $dob_raw !== '' ? self::ageHint( $dob_raw )   : '';

        $foot_value = $foot_raw !== ''
            ? \TT\Infrastructure\Query\LookupTranslator::byTypeAndName( 'foot_option', $foot_raw )
            : '—';
        // #2155 — key-facts position hint shows long descriptions.
        $foot_hint  = is_array( $positions ) && ! empty( $positions )
            ? implode( ' / ', array_map( [ \TT\Infrastructure\Query\LabelTranslator::class, 'positionLabel' ], array_map( 'strval', $positions ) ) )
            : '';

        $joined_value = $joined_raw !== '' ? self::shortDate( $joined_raw ) : '—';
        $joined_hint  = $joined_raw !== '' ? self::yearsInAcademy( $joined_raw ) : '';
        ?>
        <section class="tt-player-facts" aria-label="<?php esc_attr_e( 'Key facts', 'talenttrack' ); ?>">
            <div class="tt-player-facts__cell">
                <span class="tt-player-facts__label"><?php esc_html_e( 'DOB', 'talenttrack' ); ?></span>
                <p class="tt-player-facts__value"><?php echo esc_html( $dob_value ); ?></p>
                <?php if ( $dob_hint !== '' ) : ?>
                    <p class="tt-player-facts__hint"><?php echo esc_html( $dob_hint ); ?></p>
                <?php endif; ?>
            </div>
            <div class="tt-player-facts__cell">
                <span class="tt-player-facts__label"><?php esc_html_e( 'Foot', 'talenttrack' ); ?></span>
                <p class="tt-player-facts__value"><?php echo esc_html( $foot_value ); ?></p>
                <?php if ( $foot_hint !== '' ) : ?>
                    <p class="tt-player-facts__hint"><?php echo esc_html( $foot_hint ); ?></p>
                <?php endif; ?>
            </div>
            <div class="tt-player-facts__cell">
                <span class="tt-player-facts__label"><?php esc_html_e( 'Joined', 'talenttrack' ); ?></span>
                <p class="tt-player-facts__value"><?php echo esc_html( $joined_value ); ?></p>
                <?php if ( $joined_hint !== '' ) : ?>
                    <p class="tt-player-facts__hint"><?php echo esc_html( $joined_hint ); ?></p>
                <?php endif; ?>
            </div>
        </section>
        <?php
    }

    /**
     * KPI strip — three living signals (avg rating, attendance %,
     * active goals) replacing the loose "latest activity / eval /
     * goal" chips of the previous hero block. Each card jumps to the
     * relevant tab on tap.
     */
    private static function renderAtAGlance( int $player_id, object $player, string $base_url, array $counts ): void {
        $kpis = self::atAGlance( $player_id, $counts );
        ?>
        <section class="tt-player-glance" aria-label="<?php esc_attr_e( 'At a glance', 'talenttrack' ); ?>">
            <p class="tt-player-glance__title"><?php esc_html_e( 'At a glance', 'talenttrack' ); ?></p>
            <div class="tt-player-glance__grid">
                <?php
                // #1107 — avg rating + trend are computed from the same
                // `tt_eval_ratings` rows the Evaluations tab gates on.
                // Hide the KPI tile entirely when the user lacks
                // `evaluations:read` so the value (and the direction
                // arrow) don't leak through the side door.
                if ( MatrixGate::canAnyScope( get_current_user_id(), 'evaluations', MatrixGate::READ ) ) : ?>
                <a class="tt-player-kpi" href="<?php echo esc_url( add_query_arg( [ 'tab' => 'evaluations' ], $base_url ) ); ?>">
                    <div class="tt-player-kpi__label"><?php esc_html_e( 'Avg rating', 'talenttrack' ); ?></div>
                    <div class="tt-player-kpi__num">
                        <?php echo esc_html( $kpis['avg_rating']['value'] ); ?>
                        <?php if ( $kpis['avg_rating']['scale'] !== '' ) : ?>
                            <small><?php echo esc_html( $kpis['avg_rating']['scale'] ); ?></small>
                        <?php endif; ?>
                    </div>
                    <?php if ( $kpis['avg_rating']['hint'] !== '' ) : ?>
                        <div class="tt-player-kpi__hint <?php echo esc_attr( $kpis['avg_rating']['trend_class'] ); ?>"><?php echo esc_html( $kpis['avg_rating']['hint'] ); ?></div>
                    <?php endif; ?>
                </a>
                <?php endif; ?>
                <a class="tt-player-kpi" href="<?php echo esc_url( add_query_arg( [ 'tab' => 'activities' ], $base_url ) ); ?>">
                    <div class="tt-player-kpi__label"><?php esc_html_e( 'Attendance', 'talenttrack' ); ?></div>
                    <div class="tt-player-kpi__num">
                        <?php echo esc_html( $kpis['attendance']['value'] ); ?>
                        <?php if ( $kpis['attendance']['scale'] !== '' ) : ?>
                            <small><?php echo esc_html( $kpis['attendance']['scale'] ); ?></small>
                        <?php endif; ?>
                    </div>
                    <?php if ( $kpis['attendance']['hint'] !== '' ) : ?>
                        <div class="tt-player-kpi__hint"><?php echo esc_html( $kpis['attendance']['hint'] ); ?></div>
                    <?php endif; ?>
                </a>
                <a class="tt-player-kpi" href="<?php echo esc_url( add_query_arg( [ 'tab' => 'goals' ], $base_url ) ); ?>">
                    <div class="tt-player-kpi__label"><?php esc_html_e( 'Goals', 'talenttrack' ); ?></div>
                    <div class="tt-player-kpi__num">
                        <?php echo esc_html( $kpis['goals']['value'] ); ?>
                    </div>
                    <?php if ( $kpis['goals']['hint'] !== '' ) : ?>
                        <div class="tt-player-kpi__hint <?php echo esc_attr( $kpis['goals']['trend_class'] ); ?>"><?php echo esc_html( $kpis['goals']['hint'] ); ?></div>
                    <?php endif; ?>
                </a>
                <?php // #2859 — "Goals scored", never bare "Goals": the tile
                      // beside it counts development goals, and one word cannot
                      // carry both senses on the same strip. Links to Activities,
                      // where the per-match rows show which matches they came in. ?>
                <a class="tt-player-kpi" href="<?php echo esc_url( add_query_arg( [ 'tab' => 'activities' ], $base_url ) ); ?>">
                    <div class="tt-player-kpi__label"><?php echo esc_html_x( 'Goals scored', 'goals scored in matches', 'talenttrack' ); ?></div>
                    <div class="tt-player-kpi__num">
                        <?php echo esc_html( $kpis['scored']['value'] ); ?>
                    </div>
                    <?php if ( $kpis['scored']['hint'] !== '' ) : ?>
                        <div class="tt-player-kpi__hint"><?php echo esc_html( $kpis['scored']['hint'] ); ?></div>
                    <?php endif; ?>
                </a>
                <?php
                // #2123 — Measurements standing as a journey signal. Same
                // `measurements:read` gate as the Measurements tab so the
                // tracked-test count + below-target hint never leak to a
                // viewer who can't open the underlying timeline (§1 privacy).
                if ( MatrixGate::canAnyScope( get_current_user_id(), 'measurements', MatrixGate::READ ) ) : ?>
                <a class="tt-player-kpi" href="<?php echo esc_url( add_query_arg( [ 'tab' => 'measurements' ], $base_url ) ); ?>">
                    <div class="tt-player-kpi__label"><?php esc_html_e( 'Measurements', 'talenttrack' ); ?></div>
                    <div class="tt-player-kpi__num">
                        <?php echo esc_html( $kpis['measurements']['value'] ); ?>
                    </div>
                    <?php if ( $kpis['measurements']['hint'] !== '' ) : ?>
                        <div class="tt-player-kpi__hint <?php echo esc_attr( $kpis['measurements']['trend_class'] ); ?>"><?php echo esc_html( $kpis['measurements']['hint'] ); ?></div>
                    <?php endif; ?>
                </a>
                <?php endif; ?>
            </div>
        </section>
        <?php
    }

    /**
     * Build the three KPI signals. Each entry returns a `value`, a
     * `scale` (e.g. `/10`, `%`) shown smaller after the number, an
     * optional `hint` line, and a `trend_class` (`tt-player-kpi__trend--up|--down`)
     * for the hint colour.
     *
     * #2123 adds a `measurements` entry (tracked-test count + below-target
     * hint); the view gates that tile on `measurements:read`.
     *
     * @return array<string,array{value:string,scale:string,hint:string,trend_class:string}>
     */
    private static function atAGlance( int $player_id, array $counts ): array {
        // #1358 — KPI queries live in their domain repositories; this
        // method just shapes the numbers for display.
        $evals_repo = new \TT\Infrastructure\Evaluations\EvaluationsRepository();
        $goals_repo = new \TT\Infrastructure\Goals\GoalsRepository();

        // Avg rating: mean of every rating row across the player's
        // non-archived evaluations, via
        // EvaluationsRepository::ratingSummaryForPlayer. Trend arrow
        // compares the most recent evaluation's mean against the
        // rolling mean.
        $avg_row   = $evals_repo->ratingSummaryForPlayer( $player_id );
        $avg_value = '—';
        $avg_scale = '';
        $avg_hint  = '';
        $avg_class = '';
        if ( $avg_row && (int) $avg_row->n > 0 ) {
            $avg_value = number_format_i18n( (float) $avg_row->avg_r, 1 );
            $rmax_cfg  = (float) QueryHelpers::get_config( 'rating_max', '10' );
            if ( $rmax_cfg > 0 ) {
                $avg_scale = '/' . (int) round( $rmax_cfg );
            }
            $last = $evals_repo->lastEvaluationMeanForPlayer( $player_id );
            if ( $last !== null && (int) $avg_row->n > 1 ) {
                $delta = $last - (float) $avg_row->avg_r;
                if ( abs( $delta ) >= 0.1 ) {
                    $avg_hint  = ( $delta > 0 ? '▲ ' : '▼ ' ) . number_format_i18n( abs( $delta ), 1 );
                    $avg_class = $delta > 0 ? 'tt-player-kpi__trend--up' : 'tt-player-kpi__trend--down';
                }
            }
        }

        // Attendance %: present rows / actual attendance rows on
        // completed activities in the last 30 days, via
        // ActivitiesRepository::attendanceRateForPlayer (same scope as
        // the activities tab — only completed activities count).
        $att_row   = ( new \TT\Modules\Activities\Repositories\ActivitiesRepository() )->attendanceRateForPlayer( $player_id, 30 );
        $att_value = '—';
        $att_scale = '';
        $att_hint  = '';
        if ( $att_row && (int) $att_row->total_n > 0 ) {
            $pct = (int) round( ( (int) $att_row->present_n / (int) $att_row->total_n ) * 100 );
            $att_value = (string) $pct;
            $att_scale = '%';
            $att_hint  = __( 'last 30 d', 'talenttrack' );
        } elseif ( (int) ( $counts['activities'] ?? 0 ) > 0 ) {
            $att_hint = __( 'last 30 d', 'talenttrack' );
        }

        // Goals KPI: count of active (non-archived, non-completed)
        // goals, with a "1 due soon" hint when the nearest due date
        // is within 7 days.
        $active_goals = $goals_repo->countActiveForPlayer( $player_id );
        $goals_hint  = '';
        $goals_class = '';
        if ( $active_goals > 0 ) {
            $due_soon = $goals_repo->countDueSoonForPlayer( $player_id, 7 );
            if ( $due_soon > 0 ) {
                /* translators: %d: number of goals due in the next 7 days */
                $goals_hint  = sprintf( _n( '%d due soon', '%d due soon', $due_soon, 'talenttrack' ), $due_soon );
                $goals_class = 'tt-player-kpi__trend--down';
            }
        }

        // #2123 — Measurements KPI: the player's test standing as a journey
        // signal beside the rest. `tracked` tests carry a current value;
        // `flagged` of those sit below the age-group target band (amber +
        // red). The summary maths lives in PlayerMeasurementProfile so the
        // tile and the Measurements timeline never disagree. The view gates
        // the tile on `measurements:read`; this shaper does no gating.
        $meas_summary = ( new \TT\Modules\Measurements\Services\PlayerMeasurementProfile() )->summaryForPlayer( $player_id );
        $meas_tracked = (int) ( $meas_summary['tracked'] ?? 0 );
        $meas_flagged = (int) ( $meas_summary['flagged'] ?? 0 );
        $meas_hint    = '';
        $meas_class   = '';
        if ( $meas_tracked > 0 ) {
            if ( $meas_flagged > 0 ) {
                /* translators: %d: number of tests below the age-group target band */
                $meas_hint  = sprintf( _n( '%d below target', '%d below target', $meas_flagged, 'talenttrack' ), $meas_flagged );
                $meas_class = 'tt-player-kpi__trend--down';
            } else {
                $meas_hint  = __( 'on target', 'talenttrack' );
                $meas_class = 'tt-player-kpi__trend--up';
            }
        }

        // #2859 — match output, beside the exposure and judgement signals
        // already here. Goals were invisible on a player's record until now:
        // the goal log had no reader outside match execution, so a hat-trick
        // left no trace on the player it belonged to.
        //
        // Note the label. In this product "goals" already means a development
        // objective — the tile two along counts those — so the scoring sense
        // has to say "scored" or the strip contradicts itself.
        $contrib = ( new \TT\Modules\Analytics\Reports\GoalContributionQuery() )->forPlayer( $player_id );
        $scored_goals = (int) $contrib['goals'];
        $scored_assists = (int) $contrib['assists'];
        $scored_hint = '';
        if ( $scored_assists > 0 ) {
            /* translators: %d: number of assists the player has been credited with */
            $scored_hint = sprintf( _n( '%d assist', '%d assists', $scored_assists, 'talenttrack' ), $scored_assists );
        }

        return [
            'avg_rating' => [
                'value'       => $avg_value,
                'scale'       => $avg_scale,
                'hint'        => $avg_hint,
                'trend_class' => $avg_class,
            ],
            'attendance' => [
                'value'       => $att_value,
                'scale'       => $att_scale,
                'hint'        => $att_hint,
                'trend_class' => '',
            ],
            'goals' => [
                'value'       => (string) $active_goals,
                'scale'       => '',
                'hint'        => $goals_hint,
                'trend_class' => $goals_class,
            ],
            'measurements' => [
                'value'       => (string) $meas_tracked,
                'scale'       => '',
                'hint'        => $meas_hint,
                'trend_class' => $meas_class,
            ],
            'scored' => [
                'value'       => (string) $scored_goals,
                'scale'       => '',
                'hint'        => $scored_hint,
                'trend_class' => '',
            ],
        ];
    }

    /**
     * @return array{days:string,joined:string}|null  null when no usable date.
     */
    private static function journeyText( object $player ): ?array {
        // #2573 — a stored `0000-00-00` is non-empty AND parses (to year -1),
        // so both the emptiness guard and `strtotime()` let it through and
        // the player rendered "2028 yrs in academy". Normalise first.
        $joined_raw  = (string) ( \TT\Infrastructure\Query\QueryHelpers::usableDate( $player->date_joined ?? '' ) ?? '' );
        $created_raw = (string) ( \TT\Infrastructure\Query\QueryHelpers::usableDate( $player->created_at ?? '' ) ?? '' );
        $source = $joined_raw !== '' ? $joined_raw : $created_raw;
        if ( $source === '' ) return null;

        $ts = strtotime( $source );
        if ( $ts === false ) return null;

        $now      = current_time( 'timestamp' );
        $days     = max( 0, (int) floor( ( $now - $ts ) / DAY_IN_SECONDS ) );
        $is_fresh = $joined_raw === '' && $days < 7;

        if ( $is_fresh ) {
            return [ 'days' => __( 'Joined recently', 'talenttrack' ), 'joined' => '' ];
        }

        $years = (int) floor( $days / 365 );
        if ( $years >= 1 ) {
            /* translators: %d: number of years */
            $days_text = sprintf( _n( '%d yr in academy', '%d yrs in academy', $years, 'talenttrack' ), $years );
        } else {
            /* translators: %d: number of days */
            $days_text = sprintf( _n( '%d day in academy', '%d days in academy', $days, 'talenttrack' ), $days );
        }

        /* translators: %s: ISO date the player joined */
        $joined_text = $joined_raw !== ''
            ? sprintf( __( 'Joined %s', 'talenttrack' ), gmdate( 'Y-m-d', $ts ) )
            : '';

        return [ 'days' => $days_text, 'joined' => $joined_text ];
    }

    private static function initialsFor( string $name ): string {
        $name = trim( $name );
        if ( $name === '' ) return '?';
        $parts = preg_split( '/\s+/', $name ) ?: [ $name ];
        $first = mb_substr( (string) ( $parts[0] ?? '' ), 0, 1 );
        $last  = count( $parts ) > 1 ? mb_substr( (string) end( $parts ), 0, 1 ) : '';
        return mb_strtoupper( $first . $last );
    }

    /** Compact "12 Mar '13" style date — readable in 360px facts cells. */
    private static function shortDate( string $iso ): string {
        // v4.20.67 (#1281) — '0000-00-00' is the MySQL zero-date
        // sentinel; strtotime() returns ~-62167219200 (year -1) instead
        // of false, so the formatter rendered "30 Nov '-1" before this
        // guard. Mirrors PlayerGoalIntakePrintRouter:421.
        if ( $iso === '' || $iso === '0000-00-00' ) return '—';
        $ts = strtotime( $iso );
        if ( $ts === false ) return $iso;
        return gmdate( "j M ’y", $ts );
    }

    private static function ageHint( string $dob_iso ): string {
        if ( $dob_iso === '' || $dob_iso === '0000-00-00' ) return '';
        $ts = strtotime( $dob_iso );
        if ( $ts === false ) return '';
        $now   = current_time( 'timestamp' );
        $years = max( 0, (int) floor( ( $now - $ts ) / ( 365.25 * DAY_IN_SECONDS ) ) );
        if ( $years <= 0 ) return '';
        /* translators: %d: number of years */
        return sprintf( _n( '%d yr', '%d yrs', $years, 'talenttrack' ), $years );
    }

    private static function yearsInAcademy( string $joined_iso ): string {
        if ( $joined_iso === '' || $joined_iso === '0000-00-00' ) return '';
        $ts = strtotime( $joined_iso );
        if ( $ts === false ) return '';
        $now  = current_time( 'timestamp' );
        $days = max( 0, (int) floor( ( $now - $ts ) / DAY_IN_SECONDS ) );
        if ( $days < 30 ) return __( 'new', 'talenttrack' );
        $years = (int) floor( $days / 365 );
        if ( $years >= 1 ) {
            /* translators: %d: number of years */
            return sprintf( _n( '%d yr in', '%d yrs in', $years, 'talenttrack' ), $years );
        }
        $months = max( 1, (int) round( $days / 30 ) );
        /* translators: %d: number of months */
        return sprintf( _n( '%d mo in', '%d mos in', $months, 'talenttrack' ), $months );
    }

    /**
     * Profile tab — Identity + Academy + Parents · Guardians + Discovery
     * cards. The Parents and Discovery cards surface data that already
     * exists in `tt_player_parents` (#0032) and `tt_prospects` (#0066)
     * but wasn't previously visible on this page.
     */
    /**
     * #1988 — the "Player card" tab. The card/radar/KPI showcase from the
     * player's own "My card" (`FrontendOverviewView`), folded into the one
     * unified profile so a coach, HoD or parent sees the at-a-glance
     * standing without leaving the player's page. Reuses the existing
     * showcase renderers — no new data layer. The FIFA card handles its
     * own coming-soon state before the first rated evaluation; the radar
     * renders nothing when there's no profile yet, and the KPIs render
     * nothing until the first rating.
     */
    private static function renderCardTab( object $player ): void {
        $player_id = (int) $player->id;
        $max       = (float) QueryHelpers::get_config( 'rating_max', '10' );
        $heads     = ( new PlayerStatsService() )->getHeadlineNumbers( $player_id, [], 5 );
        $radar     = QueryHelpers::player_radar_datasets( $player_id, 3 );
        $has_radar = ! empty( $radar['datasets'] );
        ?>
        <?php
        // #2107 — carry over the "Print report" affordance the standalone
        // "My card" had, so a player landing here doesn't lose it. The
        // ?tt_print=<id> URL is handled by Stats\PrintRouter.
        $print_url = add_query_arg(
            [ 'tt_print' => $player_id ],
            remove_query_arg( [ 'tt_view', 'id', 'player_id', 'tab' ] )
        );
        ?>
        <div class="tt-player-card tt-player-cardtab">
            <div class="tt-player-card__head">
                <h3 class="tt-player-card__title"><?php esc_html_e( 'Player card', 'talenttrack' ); ?></h3>
                <a class="tt-player-card__cta tt-player-cardtab__print"
                   href="<?php echo esc_url( $print_url ); ?>"
                   target="_blank" rel="noopener"
                   title="<?php esc_attr_e( 'Print', 'talenttrack' ); ?>">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                        <rect x="6" y="14" width="12" height="8"></rect>
                    </svg>
                    <?php esc_html_e( 'Print', 'talenttrack' ); ?>
                </a>
            </div>
            <div class="tt-player-card__body">
                <div class="tt-player-cardtab__showcase">
                    <?php if ( $has_radar ) : ?>
                        <div class="tt-player-cardtab__radar tt-radar-wrap">
                            <?php echo QueryHelpers::radar_chart_svg( $radar['labels'], $radar['datasets'], $max ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </div>
                    <?php endif; ?>
                    <div class="tt-player-cardtab__fifa">
                        <?php \TT\Modules\Stats\Admin\PlayerCardView::renderCard( $player_id, 'md', true ); ?>
                    </div>
                </div>
                <?php self::renderCardKpis( $heads, $max ); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Rating KPI tiles for the Player-card tab — latest, rolling Last-5
     * (with a momentum delta vs. the all-time mean), all-time, and the
     * evaluation count. Mirrors `FrontendOverviewView::renderKpiRow()`.
     * Renders nothing before the first rated evaluation — the FIFA card's
     * own coming-soon state covers that.
     */
    private static function renderCardKpis( array $heads, float $max ): void {
        $latest  = isset( $heads['latest'] )  && $heads['latest']  !== null ? (float) $heads['latest']  : null;
        $rolling = isset( $heads['rolling'] ) && $heads['rolling'] !== null ? (float) $heads['rolling'] : null;
        $alltime = isset( $heads['alltime'] ) && $heads['alltime'] !== null ? (float) $heads['alltime'] : null;
        $evals   = (int) ( $heads['eval_count'] ?? 0 );

        if ( $latest === null && $rolling === null && $alltime === null ) {
            return;
        }

        $max_str = number_format_i18n( $max, 0 );
        $dash    = '—';

        $delta = '';
        $trend = 'flat';
        if ( $rolling !== null && $alltime !== null ) {
            $diff = round( $rolling - $alltime, 1 );
            if ( $diff > 0 )      { $trend = 'up';   $delta = '+' . number_format_i18n( $diff, 1 ); }
            elseif ( $diff < 0 )  { $trend = 'down'; $delta = number_format_i18n( $diff, 1 ); }
            else                  { $trend = 'flat'; $delta = '±0'; }
        }

        $chrome = '\\TT\\Shared\\Frontend\\Components\\FrontendAppChrome';
        ?>
        <div class="tt-player-cardtab__kpis">
            <?php
            echo $chrome::kpiTile( [
                'label' => __( 'Latest', 'talenttrack' ),
                'value' => $latest !== null ? number_format_i18n( $latest, 1 ) . ' / ' . $max_str : $dash,
            ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $chrome::kpiTile( [
                'label' => __( 'Last 5', 'talenttrack' ),
                'value' => $rolling !== null ? number_format_i18n( $rolling, 1 ) . ' / ' . $max_str : $dash,
                'delta' => $delta,
                'trend' => $trend,
            ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $chrome::kpiTile( [
                'label' => __( 'All-time', 'talenttrack' ),
                'value' => $alltime !== null ? number_format_i18n( $alltime, 1 ) . ' / ' . $max_str : $dash,
            ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $chrome::kpiTile( [
                'label' => __( 'Evaluations', 'talenttrack' ),
                'value' => number_format_i18n( $evals ),
            ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            ?>
        </div>
        <?php
    }

    private static function renderProfileTab( object $player, ?array $phv_row = null, string $phv_notice_html = '', bool $vct_on = false ): void {
        $player_id  = (int) $player->id;
        $age_tier   = AgeTier::forPlayer( $player_id );
        $tier_label = AgeTier::labels()[ $age_tier ] ?? '';
        $positions  = json_decode( (string) ( $player->preferred_positions ?? '' ), true );
        $team       = ! empty( $player->team_id ) ? QueryHelpers::get_team( (int) $player->team_id ) : null;

        $foot_label = ! empty( $player->preferred_foot )
            ? LookupTranslator::byTypeAndName( 'foot_option', (string) $player->preferred_foot )
            : '';
        $status_label = ! empty( $player->status )
            ? \TT\Infrastructure\Query\LabelTranslator::playerStatus( (string) $player->status )
            : '';
        $status_history_url = add_query_arg(
            [ 'tt_view' => 'player-status-capture', 'player_id' => $player_id ],
            RecordLink::dashboardUrl()
        );

        $identity_rows = [];
        // v4.20.67 (#1281) — route through shortDate so the zero-date
        // sentinel renders "—" instead of the literal "0000-00-00".
        $dob_raw = (string) ( $player->date_of_birth ?? '' );
        if ( $dob_raw !== '' && $dob_raw !== '0000-00-00' ) {
            $identity_rows[] = [ __( 'Date of birth', 'talenttrack' ), esc_html( self::shortDate( $dob_raw ) ) ];
        }
        if ( is_array( $positions ) && ! empty( $positions ) ) {
            // #2155 — long position descriptions on the profile tab.
            $pos_long = array_map( [ \TT\Infrastructure\Query\LabelTranslator::class, 'positionLabel' ], array_map( 'strval', $positions ) );
            $identity_rows[] = [ __( 'Position(s)', 'talenttrack' ), esc_html( implode( ' · ', $pos_long ) ) ];
        }
        if ( $foot_label !== '' ) {
            $identity_rows[] = [ __( 'Preferred foot', 'talenttrack' ), esc_html( $foot_label ) ];
        }
        if ( ! empty( $player->jersey_number ) ) {
            $identity_rows[] = [ __( 'Jersey number', 'talenttrack' ), (string) (int) $player->jersey_number ];
        }
        // #3280 — height and weight. The card carried neither, while the
        // player's own My-profile screen showed both, so a coach reading a
        // player's file could not see what the player could.
        //
        // The figures come from the dated series with the profile columns
        // behind them, resolved by PlayerPhysique — which source answers is
        // a decision and does not belong in a render method.
        foreach ( self::physiqueRows( $player_id, $player ) as $row ) {
            $identity_rows[] = $row;
        }
        // #2744 — shown to staff only, and always, including when the
        // answer is no. A blank row would be read as "not asked", which is
        // the one thing a consent record must never be ambiguous about.
        if ( self::viewerIsStaffForPlayer( $player_id ) ) {
            $identity_rows[] = [
                __( 'Photo & video consent', 'talenttrack' ),
                esc_html(
                    ! empty( $player->media_consent )
                        ? __( 'On record', 'talenttrack' )
                        : __( 'Not recorded', 'talenttrack' )
                ),
            ];
        }
        if ( $status_label !== '' ) {
            // #2107 — the status-history link goes to the staff-only
            // player-status-capture view; only show it to a staff viewer so
            // a player / parent on their own profile doesn't get a dead link
            // to a page they can't open.
            $status_value = esc_html( $status_label );
            if ( self::viewerIsStaffForPlayer( $player_id ) ) {
                $status_value .= ' · <a href="' . esc_url( $status_history_url ) . '">' . esc_html__( 'history', 'talenttrack' ) . '</a>';
            }
            $identity_rows[] = [ __( 'Status', 'talenttrack' ), $status_value ];
        }
        // #3226 — the band was reachable only by opening the hero popover,
        // so a coach reading the profile could not see the academy's own
        // view of where this player is going. Staff-only, and the history
        // link goes where the trajectory now renders.
        if ( self::viewerIsStaffForPlayer( $player_id ) ) {
            $trajectory = ( new \TT\Modules\Players\Services\PotentialTrajectory() )->forPlayer( $player_id );
            if ( $trajectory ) {
                $current        = $trajectory[ count( $trajectory ) - 1 ];
                $potential_value = esc_html( (string) $current['label'] );
                if ( count( $trajectory ) > 1 ) {
                    $potential_value .= ' · <a href="' . esc_url( $status_history_url ) . '">'
                        . esc_html__( 'history', 'talenttrack' ) . '</a>';
                }
                // `_x()` because a bare "Potential" is an adjective as
                // often as a noun, and the Dutch differs — this is the
                // noun: the band the academy has recorded.
                $identity_rows[] = [
                    _x( 'Potential', 'player profile row label — the recorded potential band', 'talenttrack' ),
                    $potential_value,
                ];
            }
        }

        $team_html = '';
        if ( $team ) {
            $team_url   = add_query_arg( [ 'tt_view' => 'teams', 'id' => (int) $team->id ], RecordLink::dashboardUrl() );
            $team_html  = '<a href="' . esc_url( $team_url ) . '">' . esc_html( (string) $team->name ) . '</a>';
            if ( ! empty( $team->age_group ) ) {
                $team_html .= ' · ' . esc_html( \TT\Infrastructure\Query\LookupTranslator::byTypeAndName( 'age_group', (string) $team->age_group ) );
            }
        }

        $academy_rows = [];
        if ( $team_html !== '' ) {
            $academy_rows[] = [ __( 'Team', 'talenttrack' ), $team_html ];
        } else {
            $academy_rows[] = [
                __( 'Team', 'talenttrack' ),
                '<em>' . esc_html__( 'Unassigned', 'talenttrack' ) . '</em>',
            ];
        }
        if ( $tier_label !== '' ) {
            $academy_rows[] = [ __( 'Age tier', 'talenttrack' ), esc_html( $tier_label ) ];
        }
        // #2573 — `! empty()` passed a stored '0000-00-00' straight through
        // to the card, which printed it verbatim as the join date.
        $joined_value = \TT\Infrastructure\Query\QueryHelpers::usableDate( $player->date_joined ?? '' );
        if ( $joined_value !== null ) {
            $academy_rows[] = [ __( 'Date joined', 'talenttrack' ), esc_html( $joined_value ) ];
        }
        // #2207 — Academy / Parents / Discovery are club-configurable and
        // may be hidden academy-wide (Identity is always-on). The staff-only
        // gating on Parents / Discovery still applies inside their renderers,
        // on top of this visibility check.
        $show_academy   = ProfileCardsConfig::isVisible( ProfileCardsConfig::CARD_ACADEMY );
        $show_parents   = ProfileCardsConfig::isVisible( ProfileCardsConfig::CARD_PARENTS );
        $show_discovery = ProfileCardsConfig::isVisible( ProfileCardsConfig::CARD_DISCOVERY );
        ?>
        <div class="tt-player-profile-grid">
            <?php
            self::renderProfileCard( __( 'Identity', 'talenttrack' ), $identity_rows );
            if ( $show_academy ) {
                self::renderProfileCard( __( 'Academy', 'talenttrack' ), $academy_rows );
            }
            if ( $show_parents ) {
                self::renderParentsCard( $player_id );
            }
            if ( $show_discovery ) {
                self::renderDiscoveryCard( $player_id );
            }
            ?>
        </div>

        <?php
        if ( empty( $player->team_id ) && current_user_can( 'tt_edit_players' ) ) {
            self::renderAssignTeamForm( $player_id );
        }

        // #1089 VCT-14 — PHV panel under the Identity/Academy cards. PHV is
        // VCT functionality, so it only renders when the VCT module is on
        // (#2064).
        if ( $vct_on ) {
            self::renderPhvPanel( $player_id, $phv_row, $phv_notice_html );
        }
    }

    /**
     * #3280 — the Identity card's Height and Weight rows.
     *
     * A figure measured in a session reads "172 cm · measured 18 Aug"; one
     * typed on the player form reads "172 cm" with no date, because there
     * isn't one. A player with neither gets no row, like every other optional
     * row on this card — an em dash would say "we asked and there is no
     * answer", which is not what is being said here.
     *
     * A viewer without measurements access sees the profile column rather
     * than the reading. They can already read that number on the edit form,
     * so nothing is exposed that was not; and where the two disagree, the
     * viewer with less access sees less.
     *
     * @return array<int,array{0:string,1:string}>
     */
    private static function physiqueRows( int $player_id, object $player ): array {
        $may_read = MatrixGate::canAnyScope( get_current_user_id(), 'measurements', MatrixGate::READ );

        $physique = ( new \TT\Modules\Measurements\Services\PlayerPhysique() )
            ->forPlayer( $player_id, $player, $may_read );

        $labels = [
            'height' => __( 'Height', 'talenttrack' ),
            'weight' => __( 'Weight', 'talenttrack' ),
        ];

        $rows = [];
        foreach ( $labels as $key => $label ) {
            if ( ! isset( $physique[ $key ] ) ) continue;

            $figure = $physique[ $key ];

            // A measured 122.5 cm reads "122,5 cm", not "123 cm": the
            // Measurements tab shows the reading to one decimal, and a card
            // that quietly rounds it makes two screens disagree about the
            // same child. A whole number keeps no decimal, and a profile
            // column is a whole number by definition — the column is
            // SMALLINT.
            //
            // `cm` / `kg` are SI symbols, not words — the same two characters
            // in every locale this product ships. Only the number is
            // localised.
            $raw      = (float) $figure['value'];
            $decimals = ( abs( $raw - round( $raw ) ) < 0.05 ) ? 0 : 1;

            $html = esc_html(
                number_format_i18n( $raw, $decimals ) . ' ' . (string) $figure['unit']
            );
            if ( ! empty( $figure['measured_on'] ) ) {
                // Same ` · ` separator the Status and Potential rows use, so
                // the card reads as one card rather than three conventions.
                $html .= ' · <span class="tt-player-kv__note">' . esc_html( sprintf(
                    /* translators: %s: the date a measurement was taken, e.g. "18 Aug". */
                    __( 'measured %s', 'talenttrack' ),
                    self::shortDate( (string) $figure['measured_on'] )
                ) ) . '</span>';
            }

            $rows[] = [ $label, $html ];
        }

        return $rows;
    }

    /**
     * One profile-tab card (Identity / Academy). Each $row is a
     * `[ field-label, value-html ]` tuple; values are emitted as
     * HTML and must already be escaped at the call site.
     *
     * @param array<int,array{0:string,1:string}> $rows
     */
    private static function renderProfileCard( string $title, array $rows ): void {
        if ( empty( $rows ) ) return;
        ?>
        <div class="tt-player-card">
            <div class="tt-player-card__head">
                <h3 class="tt-player-card__title"><?php echo esc_html( $title ); ?></h3>
            </div>
            <div class="tt-player-card__body">
                <?php foreach ( $rows as $row ) :
                    $label = (string) ( $row[0] ?? '' );
                    $value = (string) ( $row[1] ?? '' );
                    ?>
                    <div class="tt-player-kv">
                        <div class="tt-player-kv__k"><?php echo esc_html( $label ); ?></div>
                        <div class="tt-player-kv__v"><?php echo $value; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — value pre-escaped at call site ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Parents · Guardians card. Surfaces linked WP users from
     * `tt_player_parents`; relation / contact info is read from the
     * WP user record (display name, email; phone falls back to user
     * meta when present).
     */
    /**
     * #2107 — is the current viewer STAFF for this player (global
     * `players.view`, or team-scoped `players.view` on the player's team)?
     * Now that a player / parent lands on this same unified profile, the
     * staff-only Profile-tab cards (Parents · Guardians, Discovery) gate on
     * this so a player never sees co-guardian contacts or who scouted them.
     * A viewer who reached this player only via own-record / parent-of-child
     * is not staff and gets `false`.
     */
    private static function viewerIsStaffForPlayer( int $player_id ): bool {
        $uid = get_current_user_id();
        if ( \TT\Infrastructure\Security\AuthorizationService::userHasPermission( $uid, 'players.view' ) ) {
            return true;
        }
        $team_id = (int) ( QueryHelpers::get_player( $player_id )->team_id ?? 0 );
        return $team_id > 0
            && \TT\Infrastructure\Security\AuthorizationService::userHasPermission( $uid, 'players.view', 'team', $team_id );
    }

    private static function renderParentsCard( int $player_id ): void {
        // #1725 — the guardians list (names, emails, phones of every
        // co-parent) is STAFF-only. A parent viewing their own child must
        // not see co-guardians; a player must not see it either.
        if ( ! self::viewerIsStaffForPlayer( $player_id ) ) return;

        // #1358 — link rows come from PlayerParentsRepository (which
        // also owns the table-exists guard for pre-migration installs).
        $rows  = [];
        $links = ( new \TT\Modules\Invitations\PlayerParentsRepository() )->linksForPlayer( $player_id );
        foreach ( $links as $link ) {
            $user = get_userdata( (int) $link->parent_user_id );
            if ( ! $user ) continue;
            $name  = $user->display_name !== '' ? (string) $user->display_name : (string) $user->user_email;
            $email = (string) $user->user_email;
            $phone = trim( (string) get_user_meta( (int) $user->ID, 'phone', true ) );
            $bits  = [];
            if ( ! empty( $link->is_primary ) ) {
                $bits[] = '<em>' . esc_html__( 'primary', 'talenttrack' ) . '</em>';
            }
            if ( $phone !== '' ) {
                $bits[] = '<a href="tel:' . esc_attr( $phone ) . '">' . esc_html( $phone ) . '</a>';
            }
            if ( $email !== '' ) {
                $bits[] = '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
            }
            $rows[] = [ $name, implode( ' · ', $bits ) ];
        }
        ?>
        <div class="tt-player-card">
            <div class="tt-player-card__head">
                <h3 class="tt-player-card__title"><?php esc_html_e( 'Parents · Guardians', 'talenttrack' ); ?></h3>
            </div>
            <div class="tt-player-card__body">
                <?php if ( empty( $rows ) ) : ?>
                    <p class="tt-player-kv__k" style="margin:0;"><?php esc_html_e( 'No linked parent or guardian yet.', 'talenttrack' ); ?></p>
                <?php else : ?>
                    <?php foreach ( $rows as $row ) : ?>
                        <div class="tt-player-kv">
                            <div class="tt-player-kv__k"><?php echo esc_html( (string) $row[0] ); ?></div>
                            <div class="tt-player-kv__v"><?php echo $row[1]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — escaped above ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Discovery card. Surfaces the prospect that was promoted to this
     * player (when one exists) — who scouted them, at what event,
     * when. Mirrors `MyRecentProspectsSource` shape.
     */
    private static function renderDiscoveryCard( int $player_id ): void {
        // #2107 — discovery provenance (which scout logged the player, at
        // what event / club / date) is staff-internal. A player landing on
        // their own unified profile, or a parent on their child's, must not
        // see it — same staff gate as the Parents · Guardians card.
        if ( ! self::viewerIsStaffForPlayer( $player_id ) ) return;

        // #1358 — the promoted-prospect row comes from
        // ProspectsRepository (which also owns the table-exists guard
        // for pre-migration installs).
        $row = ( new \TT\Modules\Prospects\Repositories\ProspectsRepository() )->findPromotedForPlayer( $player_id );
        ?>
        <div class="tt-player-card">
            <div class="tt-player-card__head">
                <h3 class="tt-player-card__title"><?php esc_html_e( 'Discovery', 'talenttrack' ); ?></h3>
            </div>
            <div class="tt-player-card__body">
                <?php if ( $row ) :
                    $scout = get_userdata( (int) $row->discovered_by_user_id );
                    $scout_name = $scout ? (string) $scout->display_name : __( 'unknown', 'talenttrack' );
                    $event_bits = [];
                    if ( ! empty( $row->discovered_at_event ) ) $event_bits[] = (string) $row->discovered_at_event;
                    if ( ! empty( $row->current_club ) )        $event_bits[] = (string) $row->current_club;
                    if ( ! empty( $row->discovered_at ) )       $event_bits[] = (string) $row->discovered_at;
                    ?>
                    <div class="tt-player-kv">
                        <div class="tt-player-kv__k"><?php esc_html_e( 'Logged by', 'talenttrack' ); ?></div>
                        <div class="tt-player-kv__v"><?php echo esc_html( $scout_name ); ?></div>
                    </div>
                    <?php if ( ! empty( $event_bits ) ) : ?>
                        <div class="tt-player-kv">
                            <div class="tt-player-kv__k"><?php esc_html_e( 'At', 'talenttrack' ); ?></div>
                            <div class="tt-player-kv__v"><?php echo esc_html( implode( ' · ', $event_bits ) ); ?></div>
                        </div>
                    <?php endif; ?>
                <?php else : ?>
                    <p class="tt-player-kv__k" style="margin:0;"><?php esc_html_e( 'No discovery record for this player.', 'talenttrack' ); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Inline "Assign to team" picker, surfaced on the player file when
     * the player holds no team_id yet. Wires through the same
     * `PUT /players/{id}` endpoint the edit form uses, so the
     * existing permission_callback + payload validator govern;
     * nothing authorization-shaped lives in the view.
     *
     * Anchor `#tt-player-assign-team` is the jump target of the
     * action row's overflow "Assign to team" item.
     */
    private static function renderAssignTeamForm( int $player_id ): void {
        $user_id  = get_current_user_id();
        $is_admin = current_user_can( 'tt_edit_settings' );
        ?>
        <section id="tt-player-assign-team" class="tt-player-assign-team-card">
            <h2><?php esc_html_e( 'Assign this player to a team', 'talenttrack' ); ?></h2>
            <p><?php esc_html_e( 'This player has no team yet — typical after a trial admission. Pick a team to place them on the roster.', 'talenttrack' ); ?></p>
            <form class="tt-ajax-form" data-rest-path="<?php echo esc_attr( 'players/' . $player_id ); ?>" data-rest-method="PUT" data-redirect-after-save="reload">
                <?php echo \TT\Shared\Frontend\Components\TeamPickerComponent::render( [
                    'name'     => 'team_id',
                    'label'    => __( 'Team', 'talenttrack' ),
                    'required' => true,
                    'user_id'  => $user_id,
                    'is_admin' => $is_admin,
                    'selected' => 0,
                ] ); ?>
                <button type="submit" class="tt-btn tt-btn-primary">
                    <?php esc_html_e( 'Assign to team', 'talenttrack' ); ?>
                </button>
            </form>
        </section>
        <?php
    }

    /**
     * Goals tab — the player's active goals, with their finished ones in a
     * collapsed section underneath (#3033).
     */
    private static function renderGoalsTab( int $player_id ): void {
        // #1358 — list shape lives in GoalsRepository (urgency order:
        // dated goals by nearest due date, undated last by recency).
        $goals_repo = new \TT\Infrastructure\Goals\GoalsRepository();
        $rows       = $goals_repo->listActiveByDueDateForPlayer( $player_id, 50 );
        // #3033 — the heading count comes from the repository rather than
        // from `count( $rows )`, so it stays equal to the tab badge and the
        // profile KPI even for a player past the 50-row list window.
        $active_total = $goals_repo->countActiveForPlayer( $player_id );
        $closed_rows  = $goals_repo->listClosedForPlayer( $player_id, 50 );
        $closed_total = $goals_repo->countClosedForPlayer( $player_id );
        $add_url = add_query_arg(
            [ 'tt_view' => 'goals', 'action' => 'new', 'player_id' => $player_id ],
            RecordLink::dashboardUrl()
        );
        if ( empty( $rows ) && empty( $closed_rows ) ) {
            EmptyStateCard::render( [
                'icon'      => 'goals',
                'headline'  => __( 'No goals yet for this player', 'talenttrack' ),
                'explainer' => __( 'Goals capture what the player is working on this season — start with one.', 'talenttrack' ),
                'cta_label' => __( 'Add first goal', 'talenttrack' ),
                'cta_url'   => $add_url,
                'cta_cap'   => 'tt_edit_goals',
            ] );
            return;
        }
        // #1097 — Goal progress preset (Explorer →).
        $goals_explore_url = \TT\Modules\Analytics\Domain\ExplorerUrl::build(
            'goal_progress',
            [ 'player_id' => (string) $player_id, 'date_after' => '-12 months' ],
            'month'
        );
        ?>
        <div class="tt-player-card">
            <div class="tt-player-card__head">
                <h3 class="tt-player-card__title">
                    <?php
                    /* translators: %d: number of active goals */
                    echo esc_html( sprintf( __( 'Active goals · %d', 'talenttrack' ), $active_total ) );
                    ?>
                </h3>
                <div class="tt-player-card__head-actions" style="display:flex;gap:6px;align-items:center;">
                    <?php if ( \TT\Modules\Analytics\AnalyticsModule::explorerEnabled() ) : // #1552 — gate behind the Explorer toggle. ?>
                    <a class="tt-player-card__cta tt-player-card__cta--secondary" style="background:transparent;border:1px solid var(--tt-line, #d6dadd);color:var(--tt-muted, #5b6e75);text-decoration:none;padding:6px 10px;border-radius:6px;font-size:12px;font-weight:600;" href="<?php echo esc_url( $goals_explore_url ); ?>">
                        <?php esc_html_e( 'Explorer →', 'talenttrack' ); ?>
                    </a>
                    <?php endif; ?>
                    <?php if ( current_user_can( 'tt_edit_goals' ) ) :
                        // #1064 — printable season-start goal-setting intake.
                        $intake_url = add_query_arg(
                            [ 'tt_goal_intake_print' => '1', 'player_id' => $player_id ],
                            home_url( '/' )
                        );
                        ?>
                        <a class="tt-player-card__cta tt-player-card__cta--secondary" style="background:transparent;border:1px solid var(--tt-line, #d6dadd);color:var(--tt-muted, #5b6e75);text-decoration:none;padding:6px 10px;border-radius:6px;font-size:12px;font-weight:600;" href="<?php echo esc_url( $intake_url ); ?>" target="_blank" rel="noopener">
                            <?php esc_html_e( 'Print doelenintake', 'talenttrack' ); ?>
                        </a>
                        <a class="tt-player-card__cta" href="<?php echo esc_url( $add_url ); ?>">+ <?php esc_html_e( 'Add goal', 'talenttrack' ); ?></a>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ( empty( $rows ) ) : ?>
                <p class="tt-player-card__empty"><?php esc_html_e( 'No active goals — every goal on this player is finished.', 'talenttrack' ); ?></p>
            <?php else : ?>
                <ul class="tt-player-list">
                    <?php foreach ( $rows as $g ) { self::renderGoalRow( $g ); } ?>
                </ul>
            <?php endif; ?>
            <?php if ( ! empty( $closed_rows ) ) : ?>
                <details class="tt-player-goals-closed">
                    <summary class="tt-player-goals-closed__toggle">
                        <?php
                        /* translators: %d: number of completed or cancelled goals */
                        echo esc_html( sprintf( __( 'Completed goals · %d', 'talenttrack' ), $closed_total ) );
                        ?>
                    </summary>
                    <ul class="tt-player-list">
                        <?php foreach ( $closed_rows as $g ) { self::renderGoalRow( $g ); } ?>
                    </ul>
                </details>
            <?php endif; ?>
        </div>
        <?php
    }

    /** One goal row on the player-file Goals tab — active and finished lists share it (#3033). */
    private static function renderGoalRow( object $g ): void {
        $url      = RecordLink::detailUrlForWithBack( 'goals', (int) $g->id );
        $date_bit = self::dueDateBadge( (string) ( $g->due_date ?? '' ) );
        ?>
        <li>
            <a class="tt-player-row" href="<?php echo esc_url( $url ); ?>">
                <div class="tt-player-row__date <?php echo esc_attr( $date_bit['class'] ); ?>">
                    <span class="tt-player-row__date-m"><?php echo esc_html( $date_bit['m'] ); ?></span>
                    <span class="tt-player-row__date-d"><?php echo $date_bit['d']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — pre-escaped ?></span>
                </div>
                <div class="tt-player-row__body">
                    <p class="tt-player-row__title"><?php echo esc_html( (string) $g->title ); ?></p>
                    <p class="tt-player-row__meta">
                        <?php if ( ! empty( $g->priority ) ) : ?>
                            <span class="tt-player-row__pill" data-priority="<?php echo esc_attr( (string) $g->priority ); ?>">
                                <?php echo esc_html( LookupTranslator::byTypeAndName( 'goal_priority', (string) $g->priority ) ); ?>
                            </span>
                        <?php endif; ?>
                        <?php if ( ! empty( $g->status ) ) : ?>
                            <span class="tt-player-row__pill" data-status="<?php echo esc_attr( (string) $g->status ); ?>">
                                <?php echo esc_html( LookupTranslator::byTypeAndName( 'goal_status', (string) $g->status ) ); ?>
                            </span>
                        <?php endif; ?>
                    </p>
                </div>
                <span class="tt-player-row__chev" aria-hidden="true">›</span>
            </a>
        </li>
        <?php
    }

    /** Evaluations tab — card-row list with right-side colour-coded rating chip. */
    private static function renderEvaluationsTab( int $player_id ): void {
        // #1107 — defense in depth. tabs() filters this out for users
        // without `evaluations:read`, but a direct `?tab=evaluations`
        // URL still routes here (sanitize_key + array_key_exists guards
        // the active tab against the post-filter set). If the user
        // shouldn't see the tab they shouldn't see the query either.
        if ( ! MatrixGate::canAnyScope( get_current_user_id(), 'evaluations', MatrixGate::READ ) ) {
            echo '<p class="tt-notice">' . esc_html__( 'You do not have permission to view evaluations for this player.', 'talenttrack' ) . '</p>';
            return;
        }
        // #1358 — list shape lives in EvaluationsRepository (scope
        // agreement with PlayerFileCounts + the inline avg_rating
        // chip documented there).
        $rows = ( new \TT\Infrastructure\Evaluations\EvaluationsRepository() )->listRecentForPlayer( $player_id, 50 );
        $add_url = add_query_arg(
            [ 'tt_view' => 'evaluations', 'action' => 'new', 'player_id' => $player_id ],
            RecordLink::dashboardUrl()
        );
        if ( empty( $rows ) ) {
            EmptyStateCard::render( [
                'icon'      => 'evaluations',
                'headline'  => __( 'No evaluations yet for this player', 'talenttrack' ),
                'explainer' => __( 'Evaluations track how the player is developing across your rating categories. Record one after a training or match.', 'talenttrack' ),
                'cta_label' => __( 'Record first evaluation', 'talenttrack' ),
                'cta_url'   => $add_url,
                'cta_cap'   => 'tt_edit_evaluations',
            ] );
            return;
        }
        $rmax = (float) QueryHelpers::get_config( 'rating_max', '10' );
        // #1096 — Evaluations received preset (Explorer →).
        $eval_explore_url = \TT\Modules\Analytics\Domain\ExplorerUrl::build(
            'evaluations_received',
            [ 'player_id' => (string) $player_id, 'date_after' => '-12 months' ],
            'month'
        );
        ?>
        <div class="tt-player-card">
            <div class="tt-player-card__head">
                <h3 class="tt-player-card__title">
                    <?php
                    /* translators: %d: number of recent evaluations */
                    echo esc_html( sprintf( __( 'Recent · %d', 'talenttrack' ), count( $rows ) ) );
                    ?>
                </h3>
                <div class="tt-player-card__head-actions" style="display:flex;gap:6px;align-items:center;">
                    <?php if ( \TT\Modules\Analytics\AnalyticsModule::explorerEnabled() ) : // #1552 — gate behind the Explorer toggle. ?>
                    <a class="tt-player-card__cta tt-player-card__cta--secondary" style="background:transparent;border:1px solid var(--tt-line, #d6dadd);color:var(--tt-muted, #5b6e75);text-decoration:none;padding:6px 10px;border-radius:6px;font-size:12px;font-weight:600;" href="<?php echo esc_url( $eval_explore_url ); ?>">
                        <?php esc_html_e( 'Explorer →', 'talenttrack' ); ?>
                    </a>
                    <?php endif; ?>
                    <?php if ( current_user_can( 'tt_edit_evaluations' ) ) : ?>
                        <a class="tt-player-card__cta" href="<?php echo esc_url( $add_url ); ?>">+ <?php esc_html_e( 'New evaluation', 'talenttrack' ); ?></a>
                    <?php endif; ?>
                </div>
            </div>
            <ul class="tt-player-list">
                <?php foreach ( $rows as $ev ) :
                    $url      = RecordLink::detailUrlForWithBack( 'evaluations', (int) $ev->id );
                    $date_bit = self::dateBadge( (string) ( $ev->eval_date ?? '' ) );
                    $rating   = $ev->avg_rating !== null ? (float) $ev->avg_rating : null;
                    $rating_class = '';
                    if ( $rating !== null && $rmax > 0 ) {
                        $pct = $rating / $rmax;
                        if ( $pct >= 0.75 )      $rating_class = 'tt-player-row__rating--high';
                        elseif ( $pct < 0.5 )    $rating_class = 'tt-player-row__rating--low';
                    }
                    ?>
                    <li>
                        <a class="tt-player-row" href="<?php echo esc_url( $url ); ?>">
                            <div class="tt-player-row__date">
                                <span class="tt-player-row__date-m"><?php echo esc_html( $date_bit['m'] ); ?></span>
                                <span class="tt-player-row__date-d"><?php echo esc_html( $date_bit['d'] ); ?></span>
                            </div>
                            <div class="tt-player-row__body">
                                <p class="tt-player-row__title"><?php esc_html_e( 'Evaluation', 'talenttrack' ); ?></p>
                                <p class="tt-player-row__meta"><?php echo esc_html( \TT\Shared\Dates\TTDate::date( (string) ( $ev->eval_date ?? '' ) ) ); ?></p>
                            </div>
                            <?php if ( $rating !== null ) : ?>
                                <div class="tt-player-row__rating <?php echo esc_attr( $rating_class ); ?>">
                                    <?php echo esc_html( number_format_i18n( $rating, 1 ) ); ?>
                                </div>
                            <?php else : ?>
                                <span class="tt-player-row__chev" aria-hidden="true">›</span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
        // v3.110.148 — inline row-level archive/delete intentionally
        // not re-added: destructive actions live on the evaluation
        // detail page.
    }

    /**
     * Measurements tab (#1892) — the player's tests, flags, and trends in
     * context. Delegates to the shared FrontendMeasurementsView body so it
     * renders identically to the standalone Metingen view (no duplicated
     * grouping / flag logic). Defense-in-depth gate mirrors evaluations:
     * tabs() hides this for users without `measurements:read`, but a
     * direct `?tab=measurements` URL still routes here.
     */
    private static function renderMeasurementsTab( int $player_id ): void {
        if ( ! MatrixGate::canAnyScope( get_current_user_id(), 'measurements', MatrixGate::READ ) ) {
            echo '<p class="tt-notice">' . esc_html__( 'You do not have permission to view measurements for this player.', 'talenttrack' ) . '</p>';
            return;
        }
        // #2895 — BMI-for-age sits at the top of the tab, above the per-test
        // rows it is derived from. It is the third surface decision 3 called
        // for, and it renders through the same BmiBlock the report does, so a
        // coach reading a player's file and a coach reading the roster report
        // cannot be shown different percentiles for the same child.
        self::renderBmiBlock( $player_id );

        \TT\Modules\Measurements\Frontend\FrontendMeasurementsView::renderBody( $player_id );
    }

    /**
     * The BMI-for-age block on the Measurements tab.
     *
     * Renders nothing at all when the report is switched off for the academy,
     * or when this player has no usable height/weight pair and no age the
     * reference covers — an empty framed box on a player's file is noise, and
     * the roster report is where "who is missing data" belongs.
     *
     * #3278 — the figure and its percentile, nothing else. The caveat, the
     * provenance line and the change-since line moved off this tab and stayed
     * on `Player · BMI-for-age`; see `BmiBlock::renderStanding()` for why the
     * split falls there.
     */
    private static function renderBmiBlock( int $player_id ): void {
        if ( ! \TT\Core\FeatureRegistry::isEnabled( 'report_player_bmi' ) ) {
            return;
        }

        $query  = new \TT\Modules\Measurements\Reports\BmiQuery();
        $series = $query->playerSeries( $player_id );
        if ( $series === [] ) {
            return;
        }

        $latest = $series[ count( $series ) - 1 ];

        // Only what the block still renders. The `previous_date` /
        // `delta_sds` pair fed the change-since line and `date` / `gap_days`
        // fed the provenance line; both lines are gone, and computing values
        // nothing renders is how a reader ends up trusting that something on
        // screen still uses them. `BmiQuery` is unchanged — the roster report
        // reads all four.
        $row = [
            'bmi'        => $latest['bmi'],
            'sds'        => $latest['sds'],
            'percentile' => $latest['percentile'],
            'covered'    => $latest['sds'] !== null,
        ];

        wp_enqueue_style(
            'tt-frontend-bmi',
            TT_PLUGIN_URL . 'assets/css/frontend-bmi.css',
            [],
            TT_VERSION
        );

        echo '<section class="tt-bmi-section">';
        echo '<h3>' . esc_html__( 'BMI-for-age', 'talenttrack' ) . '</h3>';
        \TT\Modules\Measurements\Frontend\BmiBlock::renderStanding( $row );
        echo '</section>';
    }

    /** Activities tab — recent attended + planned activities for the player. */
    private static function renderActivitiesTab( int $player_id, ?object $player = null ): void {
        // v3.110.185 (#789) — both planned and completed activities;
        // planned rows render a neutral "Planned" pill instead of the
        // wizard's default-Present pre-fill so coach intent stays
        // distinct from coach pre-fill.
        //
        // #3277 — newest first. #1316 made this tab ascending, on the
        // reading that an operator scans a season top-to-bottom; living
        // with it said otherwise. The window is capped at 25, so ascending
        // put the one row most likely to be wanted — the latest — at the
        // bottom of the list every time. The other five surfaces #1316
        // flipped stay ascending; this is the tab, not the rule.
        //
        // Planned and scheduled rows pass the filter regardless of date, so
        // under DESC a training scheduled for next week sorts above today's
        // completed match. That is intended: what is next, then what
        // happened.
        //
        // #1320 — routed through ActivitiesRepository::listForPlayer so
        // the activity-table read path lives in one place. The window is
        // unchanged either way: the inner query always takes the MOST
        // RECENT 25, and only the display order is being asked for here.
        $rows = ( new \TT\Modules\Activities\Repositories\ActivitiesRepository() )->listForPlayer(
            (int) $player_id,
            25,
            'DESC',
            [
                'record_types'        => null,
                'plan_states'         => [ 'completed', 'planned', 'scheduled' ],
                'only_past_completed' => true,
            ]
        );
        if ( empty( $rows ) ) {
            $team_id = $player !== null ? (int) ( $player->team_id ?? 0 ) : 0;
            if ( $team_id > 0 ) {
                EmptyStateCard::render( [
                    'icon'      => 'activities',
                    'headline'  => __( 'No attended activities yet', 'talenttrack' ),
                    'explainer' => __( 'Activities are recorded at the team level. Schedule one for the player\'s team and the player will appear in the roster.', 'talenttrack' ),
                    'cta_label' => __( 'Plan a team activity', 'talenttrack' ),
                    'cta_url'   => add_query_arg(
                        [ 'tt_view' => 'activities', 'action' => 'new', 'team_id' => $team_id ],
                        RecordLink::dashboardUrl()
                    ),
                    'cta_cap'   => 'tt_edit_activities',
                ] );
            } else {
                EmptyStateCard::render( [
                    'icon'      => 'activities',
                    'headline'  => __( 'No attended activities yet', 'talenttrack' ),
                    'explainer' => __( 'Activities are recorded at the team level. Assign this player to a team first; activities planned for that team will then surface here.', 'talenttrack' ),
                ] );
            }
            return;
        }
        $list_url = add_query_arg( [ 'tt_view' => 'activities' ], RecordLink::dashboardUrl() );

        // #3045 — goals + assists per match for the rows below. One query for
        // the whole tab, keyed by activity id, from the domain service that
        // already owns the counting rules (#2859): ours only, own goals and
        // reversed events excluded, cancelled and archived matches excluded.
        // Doing it here rather than in the list query keeps one definition of
        // "a goal contribution" instead of a second one in SQL that would
        // drift from this one.
        $contrib_per_match = ( new \TT\Modules\Analytics\Reports\GoalContributionQuery() )
            ->forPlayer( $player_id )['per_match'];
        ?>
        <div class="tt-player-card">
            <div class="tt-player-card__head">
                <h3 class="tt-player-card__title">
                    <?php
                    /* translators: %d: number of recent activities */
                    echo esc_html( sprintf( __( 'Recent · %d', 'talenttrack' ), count( $rows ) ) );
                    ?>
                </h3>
                <a class="tt-player-card__cta" href="<?php echo esc_url( $list_url ); ?>"><?php esc_html_e( 'View all', 'talenttrack' ); ?></a>
            </div>
            <ul class="tt-player-list">
                <?php
                $today = gmdate( 'Y-m-d', current_time( 'timestamp' ) );
                foreach ( $rows as $a ) :
                    $activity_id = (int) $a->id;
                    $url        = RecordLink::detailUrlForWithBack( 'activities', $activity_id );
                    $date_bit   = self::dateBadge( (string) ( $a->session_date ?? '' ) );
                    $is_today   = (string) ( $a->session_date ?? '' ) === $today;
                    $is_planned = in_array( (string) ( $a->plan_state ?? '' ), [ 'planned', 'scheduled' ], true );
                    $status_key = $is_planned ? 'planned' : (string) ( $a->status ?? '' );
                    $status_lbl = $is_planned
                        ? __( 'Planned', 'talenttrack' )
                        : ( ! empty( $a->status ) ? LookupTranslator::byTypeAndName( 'attendance_status', (string) $a->status ) : '' );
                    $date_cls   = $is_today ? 'tt-player-row__date--today' : '';
                    ?>
                    <li>
                        <a class="tt-player-row" href="<?php echo esc_url( $url ); ?>">
                            <div class="tt-player-row__date <?php echo esc_attr( $date_cls ); ?>">
                                <span class="tt-player-row__date-m"><?php echo esc_html( $date_bit['m'] ); ?></span>
                                <span class="tt-player-row__date-d"><?php echo esc_html( $date_bit['d'] ); ?></span>
                            </div>
                            <div class="tt-player-row__body">
                                <p class="tt-player-row__title"><?php echo esc_html( (string) ( $a->title ?? '' ) ); ?></p>
                                <p class="tt-player-row__meta">
                                    <?php echo esc_html( \TT\Shared\Dates\TTDate::date( (string) ( $a->session_date ?? '' ) ) ); ?>
                                    <?php
                                    // #3045 — what the player produced in this match, on the
                                    // line itself. Only where there is something: a "0 · 0" on
                                    // every row is noise, and on a training it means nothing.
                                    //
                                    // #2859's naming rule holds here too — in this product
                                    // "goals" already means a development objective, and this
                                    // tab sits one click from the one that lists those. The
                                    // scoring sense has to say "scored".
                                    $contrib = $contrib_per_match[ $activity_id ] ?? null;
                                    $goals   = $contrib !== null ? (int) $contrib['goals'] : 0;
                                    $assists = $contrib !== null ? (int) $contrib['assists'] : 0;
                                    if ( $goals > 0 ) : ?>
                                        <span class="tt-player-row__stat" data-stat="goals">
                                            <?php
                                            printf(
                                                /* translators: %d: number of goals the player scored in this match */
                                                esc_html( _n( '%d goal scored', '%d goals scored', $goals, 'talenttrack' ) ),
                                                (int) $goals
                                            );
                                            ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ( $assists > 0 ) : ?>
                                        <span class="tt-player-row__stat" data-stat="assists">
                                            <?php
                                            printf(
                                                /* translators: %d: number of assists the player made in this match */
                                                esc_html( _n( '%d assist', '%d assists', $assists, 'talenttrack' ) ),
                                                (int) $assists
                                            );
                                            ?>
                                        </span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <?php if ( $status_lbl !== '' ) : ?>
                                <span class="tt-player-row__pill" data-status="<?php echo esc_attr( $status_key ); ?>">
                                    <?php echo esc_html( $status_lbl ); ?>
                                </span>
                            <?php else : ?>
                                <span class="tt-player-row__chev" aria-hidden="true">›</span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }

    /** PDP tab — active cycle with 4-step progress bar + past cycles list. */
    private static function renderPdpTab( int $player_id ): void {
        // #1107 — defense in depth. See renderEvaluationsTab note.
        if ( ! MatrixGate::canAnyScope( get_current_user_id(), 'pdp_file', MatrixGate::READ ) ) {
            echo '<p class="tt-notice">' . esc_html__( 'You do not have permission to view PDP files for this player.', 'talenttrack' ) . '</p>';
            return;
        }
        // #1358 — list shape lives in PdpFilesRepository (newest-first,
        // archived included: older cycles are still history).
        $files = ( new \TT\Modules\Pdp\Repositories\PdpFilesRepository() )->listRecentForPlayer( $player_id, 10 );
        if ( empty( $files ) ) {
            EmptyStateCard::render( [
                'icon'      => 'pdp',
                'headline'  => __( 'No PDP cycle yet for this player', 'talenttrack' ),
                'explainer' => __( 'A Personal Development Plan documents what the player is focusing on, agreed with parents and the coach. Open one to start the cycle.', 'talenttrack' ),
                'cta_label' => __( 'Start PDP cycle', 'talenttrack' ),
                'cta_url'   => add_query_arg(
                    [ 'tt_view' => 'pdp', 'action' => 'new', 'player_id' => $player_id ],
                    RecordLink::dashboardUrl()
                ),
                'cta_cap'   => 'tt_edit_pdp',
            ] );
            return;
        }

        $active = $files[0];
        $past   = array_slice( $files, 1 );

        // Map PDP status → 4-step progress (kickoff, mid-cycle,
        // end-of-cycle, signoff). The lookup vocabulary has evolved
        // across migrations so we recognise the common keys and fall
        // back to "kickoff done" for anything unrecognised.
        $progress = self::pdpProgress( (string) ( $active->status ?? '' ) );
        $active_url = add_query_arg(
            [ 'tt_view' => 'pdp', 'id' => (int) $active->id ],
            RecordLink::dashboardUrl()
        );
        ?>
        <div class="tt-player-card">
            <div class="tt-player-card__head">
                <h3 class="tt-player-card__title">
                    <?php esc_html_e( 'Active cycle', 'talenttrack' ); ?>
                </h3>
                <a class="tt-player-card__cta" href="<?php echo esc_url( $active_url ); ?>"><?php esc_html_e( 'Open file', 'talenttrack' ); ?></a>
            </div>
            <div class="tt-player-card__body">
                <p style="margin:0; font-weight:600;">
                    <?php echo esc_html( LookupTranslator::byTypeAndName( 'pdp_status', (string) ( $active->status ?? '' ) ) ); ?>
                </p>
                <div class="tt-player-pdp-progress" aria-label="<?php esc_attr_e( 'PDP progress', 'talenttrack' ); ?>">
                    <?php foreach ( $progress as $cls ) : ?>
                        <div class="tt-player-pdp-step <?php echo esc_attr( $cls ); ?>"></div>
                    <?php endforeach; ?>
                </div>
                <?php if ( ! empty( $active->created_at ) ) : ?>
                    <p class="tt-player-row__meta" style="margin-top:8px;">
                        <?php
                        /* translators: %s: date the PDP cycle was created */
                        echo esc_html( sprintf( __( 'Created %s', 'talenttrack' ), \TT\Shared\Dates\TTDate::date( (string) $active->created_at ) ) );
                        ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <?php if ( ! empty( $past ) ) : ?>
            <div class="tt-player-card">
                <div class="tt-player-card__head">
                    <h3 class="tt-player-card__title">
                        <?php
                        /* translators: %d: number of past PDP cycles */
                        echo esc_html( sprintf( __( 'Past cycles · %d', 'talenttrack' ), count( $past ) ) );
                        ?>
                    </h3>
                </div>
                <ul class="tt-player-list">
                    <?php foreach ( $past as $f ) :
                        $url  = add_query_arg( [ 'tt_view' => 'pdp', 'id' => (int) $f->id ], RecordLink::dashboardUrl() );
                        $date_bit = self::dateBadge( (string) ( $f->created_at ?? '' ) );
                        ?>
                        <li>
                            <a class="tt-player-row" href="<?php echo esc_url( $url ); ?>">
                                <div class="tt-player-row__date">
                                    <span class="tt-player-row__date-m"><?php echo esc_html( $date_bit['m'] ); ?></span>
                                    <span class="tt-player-row__date-d"><?php echo esc_html( $date_bit['d'] ); ?></span>
                                </div>
                                <div class="tt-player-row__body">
                                    <p class="tt-player-row__title">
                                        <?php echo esc_html( LookupTranslator::byTypeAndName( 'pdp_status', (string) ( $f->status ?? '' ) ) ); ?>
                                    </p>
                                    <p class="tt-player-row__meta"><?php echo esc_html( \TT\Shared\Dates\TTDate::date( (string) ( $f->created_at ?? '' ) ) ); ?></p>
                                </div>
                                <span class="tt-player-row__chev" aria-hidden="true">›</span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <?php
    }

    /**
     * Translate a PDP `status` key into a 4-step progress bitmask.
     * Returns the CSS class for each of the 4 steps (kickoff,
     * mid-cycle, end-of-cycle, signoff).
     *
     * @return array{0:string,1:string,2:string,3:string}
     */
    private static function pdpProgress( string $status ): array {
        switch ( $status ) {
            case 'closed':
            case 'completed':
            case 'signed_off':
                return [
                    'tt-player-pdp-step--done',
                    'tt-player-pdp-step--done',
                    'tt-player-pdp-step--done',
                    'tt-player-pdp-step--done',
                ];
            case 'end_of_cycle':
            case 'end-of-cycle':
                return [
                    'tt-player-pdp-step--done',
                    'tt-player-pdp-step--done',
                    'tt-player-pdp-step--current',
                    '',
                ];
            case 'mid_cycle':
            case 'mid-cycle':
            case 'in_progress':
                return [
                    'tt-player-pdp-step--done',
                    'tt-player-pdp-step--current',
                    '',
                    '',
                ];
            case 'kickoff':
            case 'opened':
            case 'draft':
            default:
                return [
                    'tt-player-pdp-step--current',
                    '',
                    '',
                    '',
                ];
        }
    }

    /** Trials tab — every trial case the player has been part of. */
    private static function renderTrialsTab( int $player_id, $player = null ): void {
        // #1107 — defense in depth. See renderEvaluationsTab note.
        if ( ! MatrixGate::canAnyScope( get_current_user_id(), 'trial_cases', MatrixGate::READ ) ) {
            echo '<p class="tt-notice">' . esc_html__( 'You do not have permission to view trial cases for this player.', 'talenttrack' ) . '</p>';
            return;
        }
        // #1358 — list shape lives in TrialCasesRepository.
        $rows = ( new \TT\Modules\Trials\Repositories\TrialCasesRepository() )->listForPlayer( $player_id, 10 );
        if ( empty( $rows ) ) {
            $is_trial_player = $player && isset( $player->status ) && (string) $player->status === PlayerStatus::TRIAL;
            $card = [
                'icon'      => 'trials',
                'headline'  => __( 'No trial history for this player', 'talenttrack' ),
                'explainer' => $is_trial_player
                    ? __( 'A trial case tracks the prospect from first training through to the academy decision. Open one when you want to evaluate this player formally.', 'talenttrack' )
                    : __( 'This player is not currently on trial, so there is no trial history to show.', 'talenttrack' ),
            ];
            if ( $is_trial_player ) {
                $card['cta_label'] = __( 'Open trial case', 'talenttrack' );
                $card['cta_url']   = add_query_arg(
                    [ 'tt_view' => 'trials', 'action' => 'new', 'player_id' => $player_id ],
                    RecordLink::dashboardUrl()
                );
                $card['cta_cap']   = 'tt_manage_trials';
            }
            EmptyStateCard::render( $card );
            return;
        }
        ?>
        <div class="tt-player-card">
            <div class="tt-player-card__head">
                <h3 class="tt-player-card__title">
                    <?php
                    /* translators: %d: number of trial cases */
                    echo esc_html( sprintf( __( 'Trial cases · %d', 'talenttrack' ), count( $rows ) ) );
                    ?>
                </h3>
            </div>
            <ul class="tt-player-list">
                <?php foreach ( $rows as $t ) :
                    $url      = add_query_arg( [ 'tt_view' => 'trial-case', 'id' => (int) $t->id ], RecordLink::dashboardUrl() );
                    $date_bit = self::dateBadge( (string) ( $t->start_date ?? '' ) );
                    $status_key = (string) ( $t->status ?? '' );
                    $status_lbl = LookupTranslator::byTypeAndName( 'trial_case_status', $status_key );
                    $meta = trim(
                        ( ! empty( $t->start_date ) ? (string) $t->start_date : '' ) .
                        ( ! empty( $t->end_date )   ? ' → ' . (string) $t->end_date : '' )
                    );
                    ?>
                    <li>
                        <a class="tt-player-row" href="<?php echo esc_url( $url ); ?>">
                            <div class="tt-player-row__date">
                                <span class="tt-player-row__date-m"><?php echo esc_html( $date_bit['m'] ); ?></span>
                                <span class="tt-player-row__date-d"><?php echo esc_html( $date_bit['d'] ); ?></span>
                            </div>
                            <div class="tt-player-row__body">
                                <p class="tt-player-row__title"><?php echo esc_html( $status_lbl ); ?></p>
                                <p class="tt-player-row__meta"><?php echo esc_html( $meta ); ?></p>
                            </div>
                            <span class="tt-player-row__chev" aria-hidden="true">›</span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }

    /**
     * Notes tab — staff-only running log via the Threads infrastructure.
     * Tab visibility is cap-gated at the `tt_view_player_notes` level
     * before the dispatcher reaches here; per-player scope is enforced
     * by `PlayerThreadAdapter::canRead` when FrontendThreadView renders.
     */
    /**
     * #2609 — the injuries tab. Read gate is the `player_injuries` entity
     * narrowed to this player; the capture and return-to-play actions ask
     * for `change` on top, so a reader with academy-wide read but no write
     * sees the history without the buttons.
     *
     * Setting the return date is the load-bearing action here: it is what
     * emits the `injury_ended` journey event and drops the player out of
     * the squad overview. Without it every injury stays open forever.
     */
    /**
     * #2594 (epic #2589) — photos and video for this player.
     *
     * The tab exists because a rating is a number and the clip behind it
     * is the evidence. Sorting is by when the media was taken rather than
     * when it was uploaded, which is what makes the gallery read as part
     * of the player's story instead of as a file listing — see
     * `MediaRepository`.
     *
     * The gate is deliberately per-player rather than the coarse "has a
     * media grant anywhere" the tab strip uses: reaching this player's
     * page does not by itself entitle a user to their photographs. In
     * practice `MediaGallery` filters every item again through
     * `MediaVisibilityService`, so this is defence in depth rather than
     * the only check.
     */
    private static function renderMediaTab( int $player_id, int $user_id ): void {
        $can_upload = ( new MediaVisibilityService() )
            ->canAttachTo( $user_id, MediaEntityType::PLAYER, $player_id );

        MediaGallery::render( [
            'entity_type'     => MediaEntityType::PLAYER,
            'entity_id'       => $player_id,
            'can_edit'        => $can_upload,
            'empty_headline'  => __( 'No photos or video yet', 'talenttrack' ),
            'empty_explainer' => __( 'A clip of a moment in training says more about how a player is developing than a score does. Anything added here sits on their timeline under the day it was taken.', 'talenttrack' ),
        ] );
    }

    private static function renderInjuriesTab( int $player_id, int $user_id ): void {
        if ( ! \TT\Infrastructure\Security\AuthorizationService::canRecordInjury( $user_id, $player_id, MatrixGate::READ ) ) {
            EmptyStateCard::render( [
                'headline'  => __( 'Injuries are not in your scope', 'talenttrack' ),
                'explainer' => __( 'Injury records are medical data and stay with the coaching staff responsible for this player. Talk to your academy admin if you should have access.', 'talenttrack' ),
            ] );
            return;
        }

        $can_write = \TT\Infrastructure\Security\AuthorizationService::canRecordInjury( $user_id, $player_id, MatrixGate::CHANGE );
        $rows      = ( new \TT\Infrastructure\Journey\InjuryRepository() )->listForPlayer( $player_id );

        ( new \TT\Infrastructure\Audit\AuditService() )->record( 'player.injuries_viewed', 'player', $player_id, [
            'surface' => 'player_file_tab',
            'count'   => count( $rows ),
        ] );

        // Empty fallback on purpose: there is no flat-form path for an
        // injury, so if the wizard is unavailable the button hides rather
        // than pointing somewhere that cannot record one.
        $add_url = \TT\Shared\Wizards\WizardEntryPoint::urlFor( 'injury', '', [ 'player_id' => $player_id ] );

        if ( empty( $rows ) ) {
            EmptyStateCard::render( array_filter( [
                'headline'  => __( 'No injuries recorded', 'talenttrack' ),
                'explainer' => __( 'Recording an injury puts it on this player\'s journey, so a coach reading the file next season can see what they came back from.', 'talenttrack' ),
                'cta_label' => $can_write ? __( 'Record injury', 'talenttrack' ) : null,
                'cta_url'   => $can_write ? $add_url : null,
            ] ) );
            return;
        }

        $today = current_time( 'Y-m-d' );
        ?>
        <div class="tt-player-card">
            <div class="tt-player-card__head">
                <h3 class="tt-player-card__title"><?php esc_html_e( 'Injuries', 'talenttrack' ); ?></h3>
                <?php if ( $can_write && $add_url !== '' ) : ?>
                    <a class="tt-btn tt-btn-primary tt-btn-small" href="<?php echo esc_url( $add_url ); ?>">
                        <?php esc_html_e( 'Record injury', 'talenttrack' ); ?>
                    </a>
                <?php endif; ?>
            </div>
            <div class="tt-player-card__body">
                <?php foreach ( $rows as $row ) :
                    $started  = (string) ( $row->started_on ?? '' );
                    $expected = (string) ( $row->expected_return ?? '' );
                    $actual   = (string) ( $row->actual_return ?? '' );
                    $overdue  = $actual === '' && $expected !== '' && strtotime( $expected ) < strtotime( $today );
                    $body     = self::injuryLookupLabel( (int) ( $row->body_part_lookup_id ?? 0 ), 'body_part' );
                    $severity = self::injuryLookupLabel( (int) ( $row->severity_lookup_id ?? 0 ), 'injury_severity' );
                    ?>
                    <div class="tt-player-row">
                        <div class="tt-player-row__main">
                            <strong><?php echo esc_html( $body !== '—' ? $body : __( 'Injury', 'talenttrack' ) ); ?></strong>
                            <?php if ( $severity !== '—' ) : ?>
                                <span class="tt-chip"><?php echo esc_html( $severity ); ?></span>
                            <?php endif; ?>
                            <div class="tt-player-row__meta">
                                <?php
                                if ( $started !== '' ) {
                                    /* translators: %s is a date. */
                                    echo esc_html( sprintf( __( 'Since %s', 'talenttrack' ), date_i18n( get_option( 'date_format' ), strtotime( $started ) ) ) );
                                }
                                if ( $actual !== '' ) {
                                    echo ' · <span class="tt-chip tt-chip--ok">' . esc_html__( 'Recovered', 'talenttrack' ) . '</span> ';
                                    echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $actual ) ) );
                                } elseif ( $expected !== '' ) {
                                    /* translators: %s is a date. */
                                    echo ' · ' . esc_html( sprintf( __( 'Expected back %s', 'talenttrack' ), date_i18n( get_option( 'date_format' ), strtotime( $expected ) ) ) );
                                    if ( $overdue ) {
                                        echo ' <span class="tt-chip tt-chip--warn">' . esc_html__( 'Overdue', 'talenttrack' ) . '</span>';
                                    }
                                } else {
                                    echo ' · ' . esc_html__( 'Return date not set', 'talenttrack' );
                                }
                                ?>
                            </div>
                            <?php if ( ! empty( $row->notes ) ) : ?>
                                <div class="tt-player-row__meta"><?php echo esc_html( (string) $row->notes ); ?></div>
                            <?php endif; ?>
                        </div>
                        <?php if ( $can_write && $actual === '' ) : ?>
                            <div class="tt-player-row__side">
                                <button type="button"
                                        class="tt-btn tt-btn-secondary tt-btn-small"
                                        data-tt-injury-recover="<?php echo (int) $row->id; ?>">
                                    <?php esc_html_e( 'Record return', 'talenttrack' ); ?>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        if ( $can_write ) {
            wp_enqueue_script(
                'tt-player-injuries',
                TT_PLUGIN_URL . 'assets/js/frontend-player-injuries.js',
                [],
                TT_VERSION,
                true
            );
            wp_localize_script( 'tt-player-injuries', 'TTInjuries', [
                'restBase' => esc_url_raw( rest_url( 'talenttrack/v1/player-injuries/' ) ),
                'nonce'    => wp_create_nonce( 'wp_rest' ),
                'i18n'     => [
                    'prompt' => __( 'Date the player returned to play (yyyy-mm-dd):', 'talenttrack' ),
                    'failed' => __( 'Could not save the return date. Try again.', 'talenttrack' ),
                    'badDate'=> __( 'Enter the date as yyyy-mm-dd.', 'talenttrack' ),
                ],
            ] );
        }
    }

    /** #2609 — resolve a lookup row to its translated label. */
    private static function injuryLookupLabel( int $lookup_id, string $type ): string {
        if ( $lookup_id <= 0 ) return '—';
        foreach ( \TT\Infrastructure\Query\QueryHelpers::get_lookups( $type ) as $row ) {
            if ( (int) $row->id === $lookup_id ) {
                return \TT\Infrastructure\Query\LookupTranslator::name( $row );
            }
        }
        return '—';
    }

    private static function renderNotesTab( int $player_id, int $user_id ): void {
        if ( ! current_user_can( 'tt_view_player_notes' ) ) {
            EmptyStateCard::render( [
                'headline'  => __( 'Notes are staff-only', 'talenttrack' ),
                'explainer' => __( 'A running log of staff observations about this player. You don\'t have access — talk to your academy admin if you should.', 'talenttrack' ),
            ] );
            return;
        }

        $adapter = \TT\Modules\Threads\ThreadTypeRegistry::get( 'player' );
        if ( ! $adapter || ! $adapter->canRead( $user_id, $player_id ) ) {
            EmptyStateCard::render( [
                'headline'  => __( 'Not in scope for this player', 'talenttrack' ),
                'explainer' => __( 'You have notes access in general, but not for this player\'s team. Talk to your academy admin if you should.', 'talenttrack' ),
            ] );
            return;
        }
        ?>
        <div class="tt-player-card">
            <div class="tt-player-card__head">
                <h3 class="tt-player-card__title"><?php esc_html_e( 'Staff notes', 'talenttrack' ); ?></h3>
            </div>
            <div class="tt-player-card__body">
                <p style="margin: 0 0 12px; font-size: 13px; color: var(--tt-ink-soft);">
                    <?php esc_html_e( 'A running log staff use to share observations about this player. Notes are visible to staff only — never to the player or their parents.', 'talenttrack' ); ?>
                </p>
                <?php \TT\Shared\Frontend\Components\FrontendThreadView::render( 'player', $player_id, $user_id ); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Format a date string into the row-badge tuple (month abbrev +
     * day-of-month). Empty input renders an em-dash placeholder so
     * the badge keeps its square footprint.
     *
     * @return array{m:string,d:string}
     */
    private static function dateBadge( string $iso ): array {
        $iso = trim( $iso );
        if ( $iso === '' ) return [ 'm' => '—', 'd' => '' ];
        $ts = strtotime( $iso );
        if ( $ts === false ) return [ 'm' => '—', 'd' => '' ];
        return [
            'm' => gmdate( 'M', $ts ),
            'd' => gmdate( 'j', $ts ),
        ];
    }

    /**
     * Format a goal due-date into the row-badge tuple, with a
     * `due-soon` class flag when the date is within the next 7 days.
     *
     * @return array{m:string,d:string,class:string}
     */
    private static function dueDateBadge( string $iso ): array {
        $iso = trim( $iso );
        if ( $iso === '' ) {
            return [ 'm' => __( 'Due', 'talenttrack' ), 'd' => '—', 'class' => '' ];
        }
        $ts = strtotime( $iso );
        if ( $ts === false ) {
            return [ 'm' => __( 'Due', 'talenttrack' ), 'd' => '—', 'class' => '' ];
        }
        $now      = current_time( 'timestamp' );
        $diff     = $ts - $now;
        $due_soon = $diff >= 0 && $diff <= 7 * DAY_IN_SECONDS;
        $cls      = $due_soon ? 'tt-player-row__date--due-soon' : '';
        return [
            'm'     => __( 'Due', 'talenttrack' ),
            'd'     => esc_html( gmdate( 'j', $ts ) ) . '<small>' . esc_html( gmdate( 'M', $ts ) ) . '</small>',
            'class' => $cls,
        ];
    }

    /**
     * #1089 VCT-14 — PHV (Physical / Health / Vitality) panel.
     *
     * Lives on the Profile tab. Coaches + HoD with `tt_edit_players`
     * see the form; everyone with `tt_view_players` sees a read-only
     * summary. The pill on the hero is emitted by `renderHero()` when
     * the row is active.
     *
     * Mockup design-of-record at `.local-mockups/vct-phv-flag/`.
     */
    private static function renderPhvPanel( int $player_id, ?array $phv_row, string $notice_html ): void {
        $can_edit  = current_user_can( 'tt_edit_players' );
        $is_active = $phv_row !== null && ! empty( $phv_row['is_active'] );
        $reason    = $phv_row !== null ? (string) $phv_row['reason_key']        : '';
        $ceiling   = $phv_row !== null ?         $phv_row['intensity_ceiling']  : null;
        $notes     = $phv_row !== null ? (string) $phv_row['notes']             : '';

        // Hide the panel entirely from non-staff viewers (parents) when
        // no flag is active — medical-adjacent metadata stays off-screen
        // unless there's something to surface.
        if ( ! $can_edit && ! $is_active ) {
            return;
        }

        // The mockup's reason picker uses a fixed Dutch enum; expose the
        // same labels with stable internal keys so they survive locale
        // changes + future i18n via `tt_lookups`.
        $reasons = [
            ''               => __( '— Pick a reason —', 'talenttrack' ),
            'injury_knee'    => __( 'Injury — knee', 'talenttrack' ),
            'injury_ankle'   => __( 'Injury — ankle', 'talenttrack' ),
            'asthma'         => __( 'Asthma', 'talenttrack' ),
            'cardiac'        => __( 'Cardiac condition', 'talenttrack' ),
            'other_medical'  => __( 'Other medical reason', 'talenttrack' ),
            'temp_fatigue'   => __( 'Temporary fatigue', 'talenttrack' ),
        ];
        $ceilings = [
            1 => __( '1 — recovery only', 'talenttrack' ),
            2 => __( '2 — low', 'talenttrack' ),
            3 => __( '3 — medium', 'talenttrack' ),
            4 => __( '4 — high', 'talenttrack' ),
        ];

        // v4.20.36 (#1196) — honour `tt_back` (CLAUDE.md §6 point 5).
        $back       = \TT\Shared\Frontend\Components\BackLink::resolve();
        $cancel_url = $back !== null
            ? (string) $back['url']
            : add_query_arg(
                [ 'tt_view' => 'players', 'id' => $player_id, 'tab' => 'profile' ],
                \TT\Shared\Frontend\Components\RecordLink::dashboardUrl()
            );
        ?>
        <section class="tt-player-card tt-player-phv-panel<?php echo $is_active ? ' is-active' : ''; ?>">
            <div class="tt-player-card__head">
                <h3><?php esc_html_e( 'PHV — Physical / Health / Vitality', 'talenttrack' ); ?></h3>
            </div>
            <?php echo $notice_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — controlled markup from handleVctPhvPost(). ?>
            <?php if ( $can_edit ) : ?>
                <form class="tt-player-phv-panel__form" method="POST" action="">
                    <?php wp_nonce_field( 'tt_vct_phv_panel_' . $player_id, '_tt_vct_phv_panel_nonce' ); ?>
                    <input type="hidden" name="_tt_vct_phv_panel" value="1">

                    <label class="tt-player-phv-toggle">
                        <input type="checkbox" name="is_active" value="1"<?php checked( $is_active ); ?>>
                        <span class="tt-player-phv-toggle__label">
                            <?php esc_html_e( 'Player has a PHV flag', 'talenttrack' ); ?>
                            <span class="tt-player-phv-toggle__sub">
                                <?php esc_html_e( 'Automatically excluded from VCT blocks above the configured intensity ceiling.', 'talenttrack' ); ?>
                            </span>
                        </span>
                    </label>

                    <div class="tt-field">
                        <label class="tt-field-label" for="tt-phv-reason-<?php echo (int) $player_id; ?>">
                            <?php esc_html_e( 'Reason (short, for the coach)', 'talenttrack' ); ?>
                        </label>
                        <select id="tt-phv-reason-<?php echo (int) $player_id; ?>" class="tt-input" name="reason_key">
                            <?php foreach ( $reasons as $key => $label ) : ?>
                                <option value="<?php echo esc_attr( $key ); ?>"<?php selected( $reason, $key ); ?>><?php echo esc_html( $label ); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="tt-field-hint">
                            <?php esc_html_e( 'Visible to staff on the coach view; not visible to other parents.', 'talenttrack' ); ?>
                        </p>
                    </div>

                    <div class="tt-field">
                        <label class="tt-field-label" for="tt-phv-ceiling-<?php echo (int) $player_id; ?>">
                            <?php esc_html_e( 'Intensity ceiling (max intensity band)', 'talenttrack' ); ?>
                        </label>
                        <select id="tt-phv-ceiling-<?php echo (int) $player_id; ?>" class="tt-input" name="intensity_ceiling">
                            <option value=""><?php esc_html_e( '— None set —', 'talenttrack' ); ?></option>
                            <?php foreach ( $ceilings as $value => $label ) : ?>
                                <option value="<?php echo (int) $value; ?>"<?php selected( (int) $ceiling, (int) $value ); ?>><?php echo esc_html( $label ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="tt-field">
                        <label class="tt-field-label" for="tt-phv-notes-<?php echo (int) $player_id; ?>">
                            <?php esc_html_e( 'Notes (optional)', 'talenttrack' ); ?>
                        </label>
                        <textarea id="tt-phv-notes-<?php echo (int) $player_id; ?>" class="tt-input" name="notes" rows="3" placeholder="<?php esc_attr_e( 'e.g. Cleared for level 4 from 1 July — physio checked.', 'talenttrack' ); ?>"><?php echo esc_textarea( $notes ); ?></textarea>
                    </div>

                    <?php
                    echo \TT\Shared\Frontend\Components\FormSaveButton::render( [
                        'label'      => __( 'Save', 'talenttrack' ),
                        'cancel_url' => $cancel_url,
                    ] );
                    ?>
                </form>
            <?php else : ?>
                <dl class="tt-player-phv-summary">
                    <dt><?php esc_html_e( 'Status', 'talenttrack' ); ?></dt>
                    <dd><?php echo $is_active ? esc_html__( 'Active', 'talenttrack' ) : esc_html__( 'Cleared', 'talenttrack' ); ?></dd>
                    <?php if ( $reason !== '' && isset( $reasons[ $reason ] ) ) : ?>
                        <dt><?php esc_html_e( 'Reason', 'talenttrack' ); ?></dt>
                        <dd><?php echo esc_html( $reasons[ $reason ] ); ?></dd>
                    <?php endif; ?>
                    <?php if ( $ceiling !== null && isset( $ceilings[ (int) $ceiling ] ) ) : ?>
                        <dt><?php esc_html_e( 'Intensity ceiling', 'talenttrack' ); ?></dt>
                        <dd><?php echo esc_html( $ceilings[ (int) $ceiling ] ); ?></dd>
                    <?php endif; ?>
                    <?php if ( $notes !== '' ) : ?>
                        <dt><?php esc_html_e( 'Notes', 'talenttrack' ); ?></dt>
                        <dd><?php echo esc_html( $notes ); ?></dd>
                    <?php endif; ?>
                </dl>
            <?php endif; ?>
            <p class="tt-player-phv-panel__surfaces">
                <strong><?php esc_html_e( 'Where the PHV flag appears:', 'talenttrack' ); ?></strong>
                <?php esc_html_e( 'Hero pill next to the name · VCT session wizard workload check · coach-view sideline banner · match prep per-player attention.', 'talenttrack' ); ?>
            </p>
        </section>
        <style>
        .tt-player-phv-pill { background: #c75c1f; color: #fff; font-size: 11px; font-weight: 800; padding: 2px 8px; border-radius: 10px; text-transform: uppercase; letter-spacing: 0.4px; margin-left: 8px; vertical-align: middle; }
        .tt-player-phv-panel { margin-top: 16px; border-color: #c75c1f33; }
        .tt-player-phv-panel.is-active { border-color: #c75c1f; }
        .tt-player-phv-panel .tt-player-card__head h3 { color: #c75c1f; text-transform: uppercase; letter-spacing: 0.4px; font-size: 14px; font-weight: 800; }
        .tt-player-phv-toggle { display: flex; align-items: center; gap: 12px; padding: 12px 0; cursor: pointer; }
        .tt-player-phv-toggle input { width: 22px; height: 22px; accent-color: #c75c1f; cursor: pointer; }
        .tt-player-phv-toggle__label { font-weight: 600; }
        .tt-player-phv-toggle__sub { display: block; font-size: 12px; color: var(--tt-muted, #5b6e75); font-weight: 400; margin-top: 2px; }
        .tt-player-phv-panel .tt-field { margin-top: 12px; padding-top: 12px; border-top: 1px dashed var(--tt-line, #d6dadd); }
        .tt-player-phv-panel__surfaces { background: var(--tt-mute, #f0f3f2); padding: 10px 12px; border-radius: 6px; font-size: 12px; color: var(--tt-muted, #5b6e75); margin-top: 16px; }
        .tt-player-phv-panel__surfaces strong { display: block; color: var(--tt-ink, #1a1d21); margin-bottom: 2px; }
        .tt-player-phv-summary { display: grid; grid-template-columns: 1fr 2fr; gap: 6px 12px; margin: 8px 0; }
        .tt-player-phv-summary dt { font-weight: 600; color: var(--tt-muted, #5b6e75); }
        .tt-player-phv-summary dd { margin: 0; }
        </style>
        <?php
    }

    /**
     * #1089 VCT-14 — POST handler for the PHV panel form. Returns
     * a notice HTML fragment (empty when the request isn't ours).
     */
    private static function handleVctPhvPost( int $player_id, int $user_id ): string {
        if ( ! current_user_can( 'tt_edit_players' ) ) {
            return '<div class="tt-notice tt-notice--error">' . esc_html__( 'You do not have permission to edit PHV flags.', 'talenttrack' ) . '</div>';
        }
        $nonce = isset( $_POST['_tt_vct_phv_panel_nonce'] ) ? (string) $_POST['_tt_vct_phv_panel_nonce'] : '';
        if ( ! wp_verify_nonce( $nonce, 'tt_vct_phv_panel_' . $player_id ) ) {
            return '<div class="tt-notice tt-notice--error">' . esc_html__( 'Save failed: session expired. Reload and try again.', 'talenttrack' ) . '</div>';
        }
        $is_active = ! empty( $_POST['is_active'] );
        $reason    = isset( $_POST['reason_key'] ) ? sanitize_key( (string) $_POST['reason_key'] ) : '';
        // Restrict reason to the known enum to avoid arbitrary strings
        // landing in the column.
        $valid_reasons = [ 'injury_knee', 'injury_ankle', 'asthma', 'cardiac', 'other_medical', 'temp_fatigue' ];
        if ( $reason !== '' && ! in_array( $reason, $valid_reasons, true ) ) {
            $reason = '';
        }
        $ceiling_raw = isset( $_POST['intensity_ceiling'] ) ? trim( (string) $_POST['intensity_ceiling'] ) : '';
        $ceiling     = $ceiling_raw === '' ? null : max( 1, min( 10, (int) $ceiling_raw ) );
        $notes       = isset( $_POST['notes'] ) ? sanitize_textarea_field( (string) wp_unslash( $_POST['notes'] ) ) : '';

        $ok = ( new \TT\Modules\Vct\Repositories\VctPhvFlagsRepository() )->setFlag(
            $player_id,
            $is_active,
            $user_id,
            $notes,
            $reason,
            $ceiling
        );
        return $ok
            ? '<div class="tt-notice tt-notice--success">' . esc_html__( 'PHV flag saved.', 'talenttrack' ) . '</div>'
            : '<div class="tt-notice tt-notice--error">' . esc_html__( 'Save failed: database error.', 'talenttrack' ) . '</div>';
    }
}
