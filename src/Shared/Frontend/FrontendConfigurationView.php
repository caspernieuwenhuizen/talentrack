<?php
namespace TT\Shared\Frontend;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Infrastructure\Query\QueryHelpers;
use TT\Shared\Frontend\BrandFonts;
use TT\Shared\Frontend\Components\FormSaveButton;

/**
 * FrontendConfigurationView — frontend mirror of the wp-admin
 * Configuration page.
 *
 * Layout follows the wp-admin Configuration tile grid: a landing page
 * with one sub-tile per configuration area. Branding, Theme & fonts,
 * and Rating scale render frontend forms inline (?config_sub=…); the
 * remaining areas (lookups, evaluation types, feature toggles,
 * backups, translations, audit log) link out to the existing wp-admin
 * tabs because they're heavier admin work that doesn't yet have a
 * dedicated frontend port.
 *
 * Saving the inline forms still goes through
 * `POST /wp-json/talenttrack/v1/config`.
 */
class FrontendConfigurationView extends FrontendViewBase {

    public static function render( int $user_id, bool $is_admin ): void {
        if ( ! current_user_can( 'tt_view_settings' ) ) {
            \TT\Shared\Frontend\Components\FrontendBreadcrumbs::fromDashboard( __( 'Not authorized', 'talenttrack' ) );
            echo '<p class="tt-notice">' . esc_html__( 'You do not have permission to view this section.', 'talenttrack' ) . '</p>';
            return;
        }

        self::enqueueAssets();

        $sub = isset( $_GET['config_sub'] ) ? sanitize_key( (string) $_GET['config_sub'] ) : '';

        // v3.92.1 — breadcrumb: when sub is set, render two-level chain
        // (Dashboard → Configuration → [sub]); otherwise just Dashboard
        // → Configuration.
        $config_label = __( 'Configuration', 'talenttrack' );
        if ( $sub !== '' ) {
            $sub_labels = [
                'general'     => __( 'General', 'talenttrack' ),
                'appearance'  => __( 'Appearance', 'talenttrack' ),
                // #1531 — Branding + Theme & fonts retired into Appearance;
                // labels kept so old deep links still resolve a crumb.
                'branding'    => __( 'Appearance', 'talenttrack' ),
                'theme'       => __( 'Appearance', 'talenttrack' ),
                'rating'      => __( 'Rating scale', 'talenttrack' ),
                'pdp-blocks'  => __( 'PDP cycle blocks', 'talenttrack' ),
                // #3305 — the questions a coach prepares each talk against.
                'pdp-prep-questions' => __( 'PDP preparation questions', 'talenttrack' ),
                // #1727 — central per-age-category default match minutes.
                'match-minutes' => __( 'Match minutes', 'talenttrack' ),
                // #3044 — how many a side each age group plays.
                'football-form' => __( 'Football form', 'talenttrack' ),
                // #2207 — which player-profile cards are shown club-wide.
                'profile-cards' => __( 'Profile cards', 'talenttrack' ),
                // #2540 — download the configuration as a JSON file.
                'export'        => __( 'Export configuration', 'talenttrack' ),
                // #2603 — per-template on/off for outgoing messages.
                'messages'      => __( 'Messages', 'talenttrack' ),
            ];
            $current_sub = $sub_labels[ $sub ] ?? ucfirst( str_replace( '_', ' ', $sub ) );
            \TT\Shared\Frontend\Components\FrontendBreadcrumbs::fromDashboard(
                $current_sub,
                [ \TT\Shared\Frontend\Components\FrontendBreadcrumbs::viewCrumb( 'configuration', $config_label ) ]
            );
        } else {
            \TT\Shared\Frontend\Components\FrontendBreadcrumbs::fromDashboard( $config_label );
        }

        switch ( $sub ) {
            case 'general':
                self::renderHeader( __( 'General', 'talenttrack' ) );
                self::renderSubBackLink();
                self::renderGeneralForm();
                return;
            // #1531 — Branding + Theme & fonts consolidated into one
            // Appearance surface (colours unified). The old subs route here
            // too so existing deep links / bookmarks still land somewhere.
            case 'appearance':
            case 'branding':
            case 'theme':
                self::renderHeader( __( 'Appearance', 'talenttrack' ) );
                self::renderSubBackLink();
                wp_enqueue_media();
                self::renderAppearanceForm();
                return;
            case 'rating':
                self::renderHeader( __( 'Rating scale', 'talenttrack' ) );
                self::renderSubBackLink();
                self::renderRatingForm();
                return;
            case 'pdp-blocks':
                // v3.110.191 — academy-configurable PDP cycle blocks.
                self::renderHeader( __( 'PDP cycle blocks', 'talenttrack' ) );
                self::renderSubBackLink();
                self::renderPdpBlocksForm();
                return;
            case 'pdp-prep-questions':
                // #3305 (epic #3301) — the prompts a coach prepares a PDP
                // conversation against, configured per conversation template.
                self::renderHeader( __( 'PDP preparation questions', 'talenttrack' ) );
                self::renderSubBackLink();
                self::renderPdpPrepQuestionsForm();
                return;
            case 'match-minutes':
                // #1727 — central per-age-category default match minutes.
                self::renderHeader( __( 'Match minutes', 'talenttrack' ) );
                self::renderSubBackLink();
                self::renderMatchMinutesForm();
                return;
            case 'football-form':
                // #3044 — the default form (6v6 / 8v8 / 11v11) per age group.
                self::renderHeader( __( 'Football form', 'talenttrack' ) );
                self::renderSubBackLink();
                self::renderFootballFormForm();
                return;
            case 'menus':
                self::renderHeader( __( 'wp-admin menus', 'talenttrack' ) );
                self::renderSubBackLink();
                self::renderMenusForm();
                return;
            case 'profile-cards':
                // #2207 — club-wide visibility of the player-profile cards.
                self::renderHeader( __( 'Profile cards', 'talenttrack' ) );
                self::renderSubBackLink();
                self::renderProfileCardsForm();
                return;
            case 'dashboard':
                self::renderHeader( __( 'Default dashboard', 'talenttrack' ) );
                self::renderSubBackLink();
                self::renderDashboardForm();
                return;
            case 'export':
                // #2540 — download the current configuration as JSON.
                self::renderHeader( __( 'Export configuration', 'talenttrack' ) );
                self::renderSubBackLink();
                self::renderExportForm();
                return;
            case 'messages':
                // #2603 — switch individual message templates off.
                self::renderHeader( __( 'Messages', 'talenttrack' ) );
                self::renderSubBackLink();
                self::renderMessagesForm();
                return;
            case 'lookups':
                // v3.74.3 — #5: per-category frontend editor. When
                // `category` is on the URL, render the dedicated CRUD
                // surface for that lookup type; otherwise render the
                // index that picks one. Editing no longer jumps to
                // wp-admin.
                $category_slug = isset( $_GET['category'] ) ? sanitize_key( (string) $_GET['category'] ) : '';
                if ( $category_slug !== '' ) {
                    $meta = self::lookupCategoryMeta( $category_slug );
                    if ( $meta !== null ) {
                        self::renderHeader( $meta['label'] );
                        self::renderLookupsBackLink();
                        self::renderLookupCategoryEditor( $meta );
                        return;
                    }
                }
                self::renderHeader( __( 'Lookups', 'talenttrack' ) );
                self::renderSubBackLink();
                self::renderLookupsIndex();
                return;
        }

        self::renderHeader( __( 'Configuration', 'talenttrack' ) );
        self::renderTileGrid();
    }

    /**
     * Sub-tile index that mirrors every individual `tt_lookups`
     * category visible in the wp-admin Configuration → Lookups tabs.
     * Each card opens the matching wp-admin tab for inline editing.
     *
     * Closing the parity gap from #0061: previously the frontend
     * collapsed all 10 lookup tabs to a single "Lookups" tile, which
     * obscured what's actually configurable.
     */
    private static function renderLookupsIndex(): void {
        self::tileGridStyles();
        // v3.74.3 — #5: each card now points at the dedicated frontend
        // editor (`?config_sub=lookups&category=<slug>`) instead of
        // jumping to wp-admin. The "Rating scale" card stays special-
        // cased because rating-scale lives in tt_config (min/max/step),
        // not tt_lookups.
        $base = remove_query_arg( [ 'config_sub', 'category', 'edit' ] );
        $rating_url = add_query_arg( [ 'config_sub' => 'rating' ], $base );

        $cards = [
            [ __( 'Evaluation types',   'talenttrack' ), __( 'The evaluation templates rosters can attach to a player record.', 'talenttrack' ), 'eval_types',     'evaluations' ],
            [ __( 'Activity types',     'talenttrack' ), __( 'Training, game, tournament, meeting — colour-coded type pills.',   'talenttrack' ), 'activity_types', 'activities' ],
            [ __( 'Game subtypes',      'talenttrack' ), __( 'Friendly, league, cup, tournament. Filters game-only reports.',     'talenttrack' ), 'game_subtypes',  'sessions' ],
            [ __( 'Positions',          'talenttrack' ), __( 'Football positions players can be tagged with.',                    'talenttrack' ), 'positions',       'compare' ],
            [ __( 'Preferred foot',     'talenttrack' ), __( 'Left, right, both — used on the player edit form.',                  'talenttrack' ), 'foot_options',    'players' ],
            [ __( 'Age groups',         'talenttrack' ), __( 'U7, U8, … U23 — feed the team age-group dropdown and weights.',     'talenttrack' ), 'age_groups',      'teams' ],
            [ __( 'Football forms',     'talenttrack' ), __( '6v6, 8v8, 11v11 — how many a side a team plays. Add 4v4, 7v7 or 9v9 if your federation uses them.', 'talenttrack' ), 'football_forms', 'teams' ],
            [ __( 'Goal statuses',      'talenttrack' ), __( 'Open / in progress / done / cancelled. Drives the goals KPI.',     'talenttrack' ), 'goal_statuses',   'goals' ],
            [ __( 'Goal priorities',    'talenttrack' ), __( 'Low / medium / high. Sorts the my-goals list.',                     'talenttrack' ), 'goal_priorities', 'goals' ],
            [ __( 'Attendance statuses', 'talenttrack' ), __( 'Present / absent / excused / late. Drives the attendance KPI.',  'talenttrack' ), 'att_statuses',    'inbox' ],
            // v3.110.163 — surface `player_value` as a first-class lookup
            // tile. The Goal wizard's LinkStep already exposes a "Value"
            // picker that reads this vocabulary; it just had no maintenance
            // surface beyond wp-admin. Seeded with 8 starters by #0044.
            [ __( 'Player values',      'talenttrack' ), __( 'Player virtues used as PDP goal links: Commitment, Coachability, Resilience, etc.', 'talenttrack' ), 'player_values',   'lightbulb' ],
            // v3.110.201 (#831) — eight lookup_types that were already in
            // tt_lookups and read by the renderer but had no maintenance
            // surface on the frontend. Adding them as tiles unlocks the
            // same translate/extend UX the other categories already have.
            [ __( 'Activity statuses',     'talenttrack' ), __( 'Draft / scheduled / conducted. Colour-coded pills on the activities list.',                  'talenttrack' ), 'activity_statuses',          'workflow' ],
            [ __( 'Certification types',   'talenttrack' ), __( 'Staff certifications (UEFA-A/B/C, First aid, GDPR awareness, Child safeguarding, …).',       'talenttrack' ), 'cert_types',                 'rate-card' ],
            [ __( 'Tournament formations', 'talenttrack' ), __( 'Formations selectable when configuring a tournament (4-3-3, 3-5-2, …).',                     'talenttrack' ), 'tournament_formations',      'kanban' ],
            [ __( 'Opponent levels',       'talenttrack' ), __( 'Opponent strength buckets selectable on tournament setup.',                                  'talenttrack' ), 'tournament_opponent_levels', 'podium' ],
            [ __( 'Behaviour ratings',     'talenttrack' ), __( 'Needs support … Exemplary. Labels for the player behaviour card and evaluation review.',         'talenttrack' ), 'behaviour_ratings',          'profile' ],
            [ __( 'Potential bands',       'talenttrack' ), __( 'Far below club level … Elite potential. Drives the player potential card.',                  'talenttrack' ), 'potential_bands',            'categories' ],
            [ __( 'Journey event types',   'talenttrack' ), __( 'Trial / signing / promotion / release / graduation. Tags player timeline events.',          'talenttrack' ), 'journey_event_types',        'track' ],
            [ __( 'Competition types',     'talenttrack' ), __( 'Competition categories (league, cup, friendly, tournament, …) used by match pickers.',       'talenttrack' ), 'competition_types',          'methodology' ],
            // v3.110.205 (#803/#808) — invitation status labels relabel /
            // translate via the lookup admin instead of the hardcoded
            // PHP labels they used to ship with. Stored keys
            // (pending / accepted / expired / revoked) stay code-side.
            [ __( 'Invitation statuses', 'talenttrack' ), __( 'Pending / accepted / expired / revoked — what the invitations list shows per row.', 'talenttrack' ), 'invitation_statuses', 'invitation' ],
            // v3.110.207 (#803/#841) — goal approval decisions (approve /
            // amend / reject) move from hardcoded labels in GoalApprovalForm
            // into tt_lookups. Stored keys stay code-side.
            [ __( 'Goal approval decisions', 'talenttrack' ), __( 'Approve / approve with amendment / reject — the three buttons on the goal-approval task.', 'talenttrack' ), 'goal_approval_decisions', 'approval' ],
            // v3.110.208 (#803/#843) — PDP verdict decisions (promote /
            // retain / release / transfer) move from hardcoded labels
            // into tt_lookups. show_color on because the verdict pills
            // are colour-coded on the player profile.
            [ __( 'PDP verdict decisions', 'talenttrack' ), __( 'Promote / retain / release / transfer — the four end-of-season verdict outcomes on a PDP file.', 'talenttrack' ), 'pdp_verdict_decisions', 'podium' ],
            // v3.110.209 (#803/#839) — workflow task statuses (open /
            // in_progress / completed / overdue / skipped / cancelled).
            // Most-seen vocabulary across the dashboard; pilot has asked
            // about Dutch translations specifically.
            [ __( 'Task statuses', 'talenttrack' ), __( 'Open / in progress / completed / overdue / skipped / cancelled — workflow task statuses.', 'talenttrack' ), 'task_statuses', 'kanban' ],
            // v3.110.210 (#803/#844) — eight report audience types
            // (standard / parent monthly / internal coaches / player
            // keepsake / scout + three trial letters).
            [ __( 'Report audiences', 'talenttrack' ), __( 'Eight report audience templates: standard, parent monthly, internal coaches, player keepsake, scout, and three trial letters.', 'talenttrack' ), 'audience_types', 'reports' ],
            // v3.110.211 (#803/#840) — nine internal idea statuses
            // (#0009 ideas board).
            [ __( 'Idea statuses', 'talenttrack' ), __( 'Submitted / refining / ready for approval / rejected / promoting / accepted / promotion failed / in progress / done — ideas-board status vocabulary.', 'talenttrack' ), 'idea_statuses', 'lightbulb' ],
            // v3.110.212 (#803/#842) — trial-case statuses + decisions.
            // Heavy operator surface; trial workflow varies by academy.
            [ __( 'Trial statuses', 'talenttrack' ), __( 'Open / extended / decided / archived — trial-case lifecycle states.', 'talenttrack' ), 'trial_case_statuses', 'inbox' ],
            [ __( 'Trial decisions', 'talenttrack' ), __( 'Admit / decline (final or with encouragement) / offered team position / declined / continue in trial group.', 'talenttrack' ), 'trial_case_decisions', 'approval' ],
            // v3.110.213 (#803/#845) — final batch: invitation kind,
            // idea type, scouting visit status, scheduled report
            // frequency + status. Closes the #803 audit.
            [ __( 'Invitation kinds', 'talenttrack' ), __( 'Player / parent / staff — role variants of the invitation flow.', 'talenttrack' ), 'invitation_kinds', 'invitation' ],
            [ __( 'Idea types', 'talenttrack' ), __( 'Feature / bug / epic / needs triage — ideas-board type tag.', 'talenttrack' ), 'idea_types', 'lightbulb' ],
            [ __( 'Scouting visit statuses', 'talenttrack' ), __( 'Planned / completed / cancelled — scouting visit lifecycle.', 'talenttrack' ), 'scouting_visit_statuses', 'compare' ],
            [ __( 'Scheduled report frequencies', 'talenttrack' ), __( 'Weekly (Monday) / monthly (1st) / season end — cadence for scheduled CSV exports.', 'talenttrack' ), 'scheduled_report_frequencies', 'reports' ],
            [ __( 'Scheduled report statuses', 'talenttrack' ), __( 'Active / paused / archived — scheduled report lifecycle.', 'talenttrack' ), 'scheduled_report_statuses', 'audit-log' ],
            [ __( 'Rating scale',       'talenttrack' ), __( 'Min, max and step for evaluation ratings.',                         'talenttrack' ), '__rating',        'weights' ],
        ];

        echo '<p style="margin-bottom:var(--tt-sp-4); color:var(--tt-muted);">';
        esc_html_e( 'Lookup vocabularies, grouped by domain. Values are translatable and feed every dropdown across the dashboard.', 'talenttrack' );
        echo '</p>';

        // #1535 — group the ~32 lookup cards into domain sections via the
        // shared FrontendSectionedTileGrid presenter (auto-hides any empty
        // section). Tiles keep their existing .tt-cfg-tile markup + icon, so
        // grid_inline is off — the enqueued tileGridStyles() drives layout.
        $tiles = [];
        foreach ( $cards as $row ) {
            list( $title, $desc, $slug, $icon ) = $row;
            $url = ( $slug === '__rating' )
                ? $rating_url
                : add_query_arg( [ 'config_sub' => 'lookups', 'category' => $slug ], $base );
            $tiles[] = [ 'slug' => $slug, 'title' => $title, 'desc' => $desc, 'icon' => $icon, 'url' => $url ];
        }

        $groups = [
            [ 'label' => __( 'Activities & attendance', 'talenttrack' ), 'slugs' => [ 'activity_types', 'activity_statuses', 'game_subtypes', 'competition_types', 'att_statuses' ] ],
            [ 'label' => __( 'Players & teams', 'talenttrack' ),         'slugs' => [ 'positions', 'foot_options', 'age_groups', 'football_forms', 'journey_event_types' ] ],
            [ 'label' => __( 'Evaluations & development', 'talenttrack' ),'slugs' => [ 'eval_types', '__rating', 'behaviour_ratings', 'potential_bands', 'player_values', 'pdp_verdict_decisions' ] ],
            [ 'label' => __( 'Goals', 'talenttrack' ),                   'slugs' => [ 'goal_statuses', 'goal_priorities', 'goal_approval_decisions' ] ],
            [ 'label' => __( 'Scouting & trials', 'talenttrack' ),       'slugs' => [ 'trial_case_statuses', 'trial_case_decisions', 'scouting_visit_statuses' ] ],
            [ 'label' => __( 'Tournaments & match', 'talenttrack' ),     'slugs' => [ 'tournament_formations', 'tournament_opponent_levels' ] ],
            [ 'label' => __( 'Staff & people', 'talenttrack' ),          'slugs' => [ 'cert_types', 'invitation_statuses', 'invitation_kinds' ] ],
            [ 'label' => __( 'Reports & workflow', 'talenttrack' ),      'slugs' => [ 'task_statuses', 'audience_types', 'scheduled_report_frequencies', 'scheduled_report_statuses' ] ],
            [ 'label' => __( 'Advanced / internal', 'talenttrack' ),     'slugs' => [ 'idea_statuses', 'idea_types' ] ],
        ];

        $sections = \TT\Shared\Frontend\Components\FrontendSectionedTileGrid::fromGroups(
            $tiles,
            $groups,
            __( 'Other', 'talenttrack' )
        );
        \TT\Shared\Frontend\Components\FrontendSectionedTileGrid::render(
            $sections,
            [
                'grid_inline'   => false,
                'tile_renderer' => static function ( array $tile ): void {
                    echo '<a class="tt-cfg-tile" href="' . esc_url( (string) $tile['url'] ) . '">';
                    echo '<div class="tt-cfg-tile-icon">' . \TT\Shared\Frontend\Components\TileIconChip::render( (string) $tile['icon'], '#0b3d2e' ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — TileIconChip escapes its own attrs and IconRenderer returns trusted SVG.
                    echo '<div class="tt-cfg-tile-title">' . esc_html( (string) $tile['title'] ) . '</div>';
                    echo '<div class="tt-cfg-tile-desc">' . esc_html( (string) $tile['desc'] ) . '</div>';
                    echo '</a>';
                },
            ]
        );
    }

    /**
     * v3.74.3 — registry of lookup categories editable on the frontend.
     * Each entry maps the URL slug (used in `?category=`) to the
     * underlying `tt_lookups.lookup_type` plus presentation flags.
     *
     * @return array{label:string,type:string,show_desc:bool,show_color:bool,show_abbrev:bool,slug:string}|null
     */
    private static function lookupCategoryMeta( string $slug ): ?array {
        $registry = [
            'eval_types'      => [ 'label' => __( 'Evaluation types',    'talenttrack' ), 'type' => 'eval_type',         'show_desc' => true,  'show_color' => false ],
            'activity_types'  => [ 'label' => __( 'Activity types',      'talenttrack' ), 'type' => 'activity_type',     'show_desc' => true,  'show_color' => true  ],
            'game_subtypes'   => [ 'label' => __( 'Game subtypes',       'talenttrack' ), 'type' => 'game_subtype',      'show_desc' => false, 'show_color' => false ],
            // #3246 — positions are the one vocabulary printed as a short
            // code (the chips on the player form), so they are the one
            // category with the abbreviation column on. Before this the
            // code was the internal key, which is immutable and
            // language-neutral and so could be neither.
            'positions'       => [ 'label' => __( 'Positions',           'talenttrack' ), 'type' => 'position',          'show_desc' => false, 'show_color' => false, 'show_abbrev' => true ],
            'foot_options'    => [ 'label' => __( 'Preferred foot',      'talenttrack' ), 'type' => 'foot_option',       'show_desc' => false, 'show_color' => false ],
            'age_groups'      => [ 'label' => __( 'Age groups',          'talenttrack' ), 'type' => 'age_group',         'show_desc' => false, 'show_color' => false ],
            // #3044 — how many a side a team plays. The description carries
            // the keeper and pitch-size implication, so it is editable here.
            'football_forms'  => [ 'label' => __( 'Football forms',      'talenttrack' ), 'type' => 'football_form',     'show_desc' => true,  'show_color' => false ],
            'goal_statuses'   => [ 'label' => __( 'Goal statuses',       'talenttrack' ), 'type' => 'goal_status',       'show_desc' => false, 'show_color' => true  ],
            'goal_priorities' => [ 'label' => __( 'Goal priorities',     'talenttrack' ), 'type' => 'goal_priority',     'show_desc' => false, 'show_color' => false ],
            'att_statuses'    => [ 'label' => __( 'Attendance statuses', 'talenttrack' ), 'type' => 'attendance_status', 'show_desc' => false, 'show_color' => true  ],
            // v3.110.163 — player virtues used as PDP goal-link target.
            // Description column is on so the operator can write a short
            // gloss for each value ("Commitment — turning up on time and
            // ready to train") that surfaces wherever the value is shown.
            'player_values'   => [ 'label' => __( 'Player values',       'talenttrack' ), 'type' => 'player_value',      'show_desc' => true,  'show_color' => false ],
            // v3.110.201 (#831) — eight lookup_types previously hidden from the
            // frontend grid. `show_desc=true` on the four where the description
            // column is operator-meaningful (gloss for the label); `show_color`
            // only on activity_status because its pills are colour-coded list-side.
            'activity_statuses'          => [ 'label' => __( 'Activity statuses',     'talenttrack' ), 'type' => 'activity_status',           'show_desc' => false, 'show_color' => true  ],
            'cert_types'                 => [ 'label' => __( 'Certification types',   'talenttrack' ), 'type' => 'cert_type',                 'show_desc' => true,  'show_color' => false ],
            'tournament_formations'      => [ 'label' => __( 'Tournament formations', 'talenttrack' ), 'type' => 'tournament_formation',      'show_desc' => false, 'show_color' => false ],
            'tournament_opponent_levels' => [ 'label' => __( 'Opponent levels',       'talenttrack' ), 'type' => 'tournament_opponent_level', 'show_desc' => true,  'show_color' => false ],
            'behaviour_ratings'          => [ 'label' => __( 'Behaviour ratings',     'talenttrack' ), 'type' => 'behaviour_rating_label',    'show_desc' => true,  'show_color' => false ],
            'potential_bands'            => [ 'label' => __( 'Potential bands',       'talenttrack' ), 'type' => 'potential_band',            'show_desc' => true,  'show_color' => false ],
            'journey_event_types'        => [ 'label' => __( 'Journey event types',   'talenttrack' ), 'type' => 'journey_event_type',        'show_desc' => true,  'show_color' => false ],
            'competition_types'          => [ 'label' => __( 'Competition types',     'talenttrack' ), 'type' => 'competition_type',          'show_desc' => false, 'show_color' => false ],
            // v3.110.205 (#803/#808) — invitation statuses moved from
            // hardcoded PHP labels in `InvitationStatus::label()` into
            // tt_lookups; seeded by migration 0110. Description off
            // (status names are self-explanatory); color off (the
            // invitations list doesn't render colour pills today).
            'invitation_statuses' => [ 'label' => __( 'Invitation statuses', 'talenttrack' ), 'type' => 'invitation_status', 'show_desc' => false, 'show_color' => false ],
            // v3.110.207 (#803/#841) — three approval-form decisions; the
            // `amend` value benefits from a description ("Approve with
            // amendment — back to the player to revise"), so show_desc on.
            'goal_approval_decisions' => [ 'label' => __( 'Goal approval decisions', 'talenttrack' ), 'type' => 'goal_approval_decision', 'show_desc' => true, 'show_color' => false ],
            // v3.110.208 (#803/#843) — PDP verdict decisions. show_desc
            // on so academies can gloss what each decision means in their
            // context; show_color on for the player-profile pills.
            'pdp_verdict_decisions'   => [ 'label' => __( 'PDP verdict decisions',  'talenttrack' ), 'type' => 'pdp_verdict_decision',   'show_desc' => true, 'show_color' => true  ],
            // v3.110.209 (#803/#839) — six workflow task statuses.
            // show_color so the academy can colour-code the status pills
            // on task lists; show_desc=false (status names are
            // self-explanatory).
            'task_statuses'           => [ 'label' => __( 'Task statuses',          'talenttrack' ), 'type' => 'task_status',            'show_desc' => false, 'show_color' => true ],
            // v3.110.210 (#803/#844) — eight report audience types. The
            // description column carries the operator-facing gloss for
            // each audience ("warm parent-monthly summary…"), so
            // show_desc=true. show_color=false; audiences aren't pilled
            // anywhere with a colour.
            'audience_types'          => [ 'label' => __( 'Report audiences',       'talenttrack' ), 'type' => 'audience_type',          'show_desc' => true,  'show_color' => false ],
            // v3.110.211 (#803/#840) — nine internal idea statuses. The
            // transient `promoting` / `promotion-failed` states surface
            // in the admin board; operators may want to relabel.
            // show_color=true so the kanban columns get colour-coded
            // pills; show_desc=false (status names self-explanatory).
            'idea_statuses'           => [ 'label' => __( 'Idea statuses',          'talenttrack' ), 'type' => 'idea_status',            'show_desc' => false, 'show_color' => true  ],
            // v3.110.212 (#803/#842) — trial-case statuses + decisions.
            // Statuses get show_color (the trial list renders status
            // pills); decisions get show_desc=true so academies can gloss
            // what each decision means in their context.
            'trial_case_statuses'     => [ 'label' => __( 'Trial statuses',         'talenttrack' ), 'type' => 'trial_case_status',      'show_desc' => false, 'show_color' => true  ],
            'trial_case_decisions'    => [ 'label' => __( 'Trial decisions',        'talenttrack' ), 'type' => 'trial_case_decision',    'show_desc' => true,  'show_color' => false ],
            // v3.110.213 (#803/#845) — final batch. All simple-label
            // vocabularies; show_desc=false, show_color=false except
            // scheduled_report_status (lifecycle pill colour-coded).
            'invitation_kinds'             => [ 'label' => __( 'Invitation kinds',             'talenttrack' ), 'type' => 'invitation_kind',            'show_desc' => false, 'show_color' => false ],
            'idea_types'                   => [ 'label' => __( 'Idea types',                   'talenttrack' ), 'type' => 'idea_type',                  'show_desc' => false, 'show_color' => false ],
            'scouting_visit_statuses'      => [ 'label' => __( 'Scouting visit statuses',      'talenttrack' ), 'type' => 'scouting_visit_status',      'show_desc' => false, 'show_color' => true  ],
            'scheduled_report_frequencies' => [ 'label' => __( 'Scheduled report frequencies', 'talenttrack' ), 'type' => 'scheduled_report_frequency', 'show_desc' => false, 'show_color' => false ],
            'scheduled_report_statuses'    => [ 'label' => __( 'Scheduled report statuses',    'talenttrack' ), 'type' => 'scheduled_report_status',    'show_desc' => false, 'show_color' => true  ],
        ];
        if ( ! isset( $registry[ $slug ] ) ) return null;
        $meta = $registry[ $slug ];
        // Opt-in flag — every category that doesn't name it is off.
        $meta['show_abbrev'] = ! empty( $meta['show_abbrev'] );
        $meta['slug'] = $slug;
        return $meta;
    }

    /**
     * v4.8.0 (#985) — every locale the operator maintains a translation
     * for, INCLUDING `en_US`. The `tt_lookups.name` column is now
     * presented as an immutable internal key; the canonical English
     * display value lives in `tt_translations` alongside the other
     * locales, so the grid surfaces `en_US` as a first-class row.
     *
     * Order: site locale first (Q2), then `en_US` if not the site
     * locale, then the remaining installed locales in stable order.
     * Q1's "site-locale-first" coverage-dot order on the list rail
     * mirrors this same ordering for consistency.
     *
     * @return list<string>
     */
    private static function translationTargets(): array {
        $installed = \TT\Infrastructure\Query\LookupTranslator::installedLocales();
        $site      = function_exists( 'get_locale' ) ? (string) get_locale() : 'en_US';
        if ( $site === '' ) $site = 'en_US';

        $out = [];
        // 1. Site locale first.
        if ( in_array( $site, $installed, true ) ) {
            $out[] = $site;
        }
        // 2. en_US second (always include — operator can author canonical
        //    English here even when the site locale is something else).
        if ( ! in_array( 'en_US', $out, true ) ) {
            $out[] = 'en_US';
        }
        // 3. Remaining installed locales in stable (alpha) order.
        foreach ( $installed as $loc ) {
            if ( ! in_array( $loc, $out, true ) ) {
                $out[] = $loc;
            }
        }
        return array_values( array_unique( $out ) );
    }

    private static function renderLookupsBackLink(): void {
        $base = add_query_arg( [ 'config_sub' => 'lookups' ], remove_query_arg( [ 'category', 'edit' ] ) );
        echo '<p style="margin:0 0 var(--tt-sp-3);"><a class="tt-link" href="' . esc_url( $base ) . '">&larr; ' . esc_html__( 'All lookups', 'talenttrack' ) . '</a></p>';
    }

    /**
     * Frontend CRUD editor for one lookup category — list-first layout
     * per `.local-mockups/lookup-admin/index.html` (#985).
     *
     * The view defaults to a clean list of values. `+ Add value` opens
     * an empty form. Clicking a row opens the form populated with the
     * row's data and translations. Both forms emit the canonical Save +
     * Cancel pair via `FormSaveButton::render( cancel_url: … )` so the
     * CLAUDE.md §6 contract is honoured.
     *
     * Save / delete go through the existing `LookupsRestController`
     * endpoints — authorisation + tenancy + audit logging stay
     * centralised. The on-row coverage dots reflect `tt_translations`
     * state for the 5 supported locales (Q5: "name set" only; description
     * is optional and doesn't gate the dot).
     *
     * Internal key (the `name` column) is immutable on existing rows
     * per Q4 — display value lives in `tt_translations`, the `name`
     * column is a stable database identifier. New rows can set it once;
     * existing rows render the field disabled.
     *
     * @param array{label:string,type:string,show_desc:bool,show_color:bool,show_abbrev:bool,slug:string} $meta
     */
    private static function renderLookupCategoryEditor( array $meta ): void {
        $type     = $meta['type'];
        $items    = QueryHelpers::get_lookups( $type );
        $edit_id  = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
        $editing  = null;
        if ( $edit_id > 0 ) {
            foreach ( $items as $row ) {
                if ( (int) $row->id === $edit_id ) { $editing = $row; break; }
            }
        }

        // One bulk SELECT for translations across every row in the list
        // (#0090 Phase 6 / v3.110.203 pattern). Keyed
        // `lookup_id => locale => field => value` so the JS row-click
        // handler can populate the per-locale inputs without a REST
        // round-trip. Empty when no rows exist yet.
        $tx_by_row  = self::loadTranslationsForLookupIds(
            array_map( fn( $r ) => (int) $r->id, $items )
        );
        $tx_targets = self::translationTargets();
        $site_locale = function_exists( 'get_locale' ) ? (string) get_locale() : 'en_US';

        $base   = remove_query_arg( [ 'edit' ] );
        $add_id = 'tt-lkp-' . sanitize_html_class( $meta['slug'] );

        $initial_state = $editing ? 'edit' : 'list';

        // Enqueue the dedicated stylesheet + module. Mobile-first
        // (CLAUDE.md §2); the inline `masterDetailStyles()` and inline
        // IIFE are gone — the new layout owns its own files.
        wp_enqueue_style(
            'tt-frontend-lookup-admin',
            TT_PLUGIN_URL . 'assets/css/frontend-lookup-admin.css',
            [],
            TT_VERSION
        );
        wp_enqueue_script(
            'tt-frontend-lookup-admin',
            TT_PLUGIN_URL . 'assets/js/components/lookup-admin.js',
            [],
            TT_VERSION,
            true
        );

        $js_config = [
            'rest_base'   => esc_url_raw( rest_url( 'talenttrack/v1' ) ) . '/',
            'nonce'       => wp_create_nonce( 'wp_rest' ),
            'lookup_type' => $type,
            'show_desc'   => (bool) $meta['show_desc'],
            'show_color'  => (bool) $meta['show_color'],
            'locales'     => $tx_targets,
            'site_locale' => $site_locale,
            'source_lang' => substr( $site_locale, 0, 2 ),
            'i18n'        => [
                'add'              => __( 'Add value', 'talenttrack' ),
                'save'             => __( 'Save changes', 'talenttrack' ),
                'saving'           => __( 'Saving…', 'talenttrack' ),
                'translating'      => __( 'Translating…', 'talenttrack' ),
                'translated'       => __( 'Translated. Review and edit before saving.', 'talenttrack' ),
                'err_name_required'=> __( 'Internal key is required.', 'talenttrack' ),
                'err_enter_name'   => __( 'Enter a name first.', 'talenttrack' ),
                'confirm_delete'   => __( 'Delete this row?', 'talenttrack' ),
                'error'            => __( 'Error', 'talenttrack' ),
                'network_error'    => __( 'Network error.', 'talenttrack' ),
                'title_add'        => __( 'Add new value', 'talenttrack' ),
                'title_edit'       => __( 'Edit value', 'talenttrack' ),
                'hint_add'         => __( 'Lowercase ASCII, no spaces. Used as the database identifier and cannot be changed later.', 'talenttrack' ),
                'hint_edit'        => __( 'Stable database identifier. Locked once the row is created — change it via a code migration.', 'talenttrack' ),
            ],
        ];
        ?>
        <div class="tt-lkp-admin"
             data-tt-lkp-admin
             data-state="<?php echo esc_attr( $initial_state ); ?>"
             data-tt-lkp-config="<?php echo esc_attr( (string) wp_json_encode( $js_config ) ); ?>">

            <?php // #1039 — master-detail grid: list left, panel right ?>
            <div class="tt-lkp-md">
                <?php self::renderLookupListView( $meta, $items, $tx_by_row, $tx_targets, $site_locale ); ?>

                <?php self::renderLookupFormViews( $meta, $editing, $tx_by_row, $tx_targets, $site_locale, $add_id, $base ); ?>
            </div>
        </div>
        <?php
    }

    /**
     * List view — clean roster of values + `+ Add value` button. No
     * form rendered. Drag-reorder wiring still works because each row
     * still carries `data-tt-sortable` ancestry via the `tt-sortable-table`
     * class — the existing `DragReorder` wp-admin endpoint is unchanged.
     *
     * @param array{label:string,type:string,show_desc:bool,show_color:bool,show_abbrev:bool,slug:string} $meta
     * @param array<int,object> $items
     * @param array<int, array<string, array<string, string>>> $tx_by_row
     * @param list<string> $tx_targets
     */
    private static function renderLookupListView( array $meta, array $items, array $tx_by_row, array $tx_targets, string $site_locale ): void {
        $count = count( $items );
        ?>
        <section class="tt-lkp-view tt-lkp-view-list" aria-label="<?php esc_attr_e( 'Lookup values', 'talenttrack' ); ?>">
            <div class="tt-lkp-card">
                <div class="tt-lkp-list-header">
                    <p class="tt-lkp-list-meta">
                        <?php
                        printf(
                            /* translators: %d = number of lookup values */
                            esc_html( _n( '%d value · tap a row to edit', '%d values · tap a row to edit', $count, 'talenttrack' ) ),
                            (int) $count
                        );
                        ?>
                    </p>
                </div>

                <?php if ( empty( $items ) ) : ?>
                    <p class="tt-lkp-empty">
                        <em><?php esc_html_e( 'No values yet. Tap "+ Add value" to seed the first row.', 'talenttrack' ); ?></em>
                    </p>
                <?php else : ?>
                    <ul class="tt-lkp-list tt-sortable-table" data-tt-sortable="1">
                        <?php foreach ( $items as $row ) :
                            $row_meta_arr = QueryHelpers::lookup_meta( $row );
                            $row_color    = is_string( $row_meta_arr['color'] ?? null ) ? (string) $row_meta_arr['color'] : '';
                            $is_locked    = ! empty( $row_meta_arr['is_locked'] );
                            $row_id       = (int) $row->id;
                            $row_tx       = $tx_by_row[ $row_id ] ?? [];
                            ?>
                            <li class="tt-lkp-row"
                                data-id="<?php echo (int) $row_id; ?>"
                                data-tt-lkp-row
                                role="button"
                                tabindex="0"
                                data-row-name="<?php echo esc_attr( (string) $row->name ); ?>"
                                data-row-sort="<?php echo (int) $row->sort_order; ?>"
                                data-row-desc="<?php echo esc_attr( (string) ( $row->description ?? '' ) ); ?>"
                                data-row-abbrev="<?php echo esc_attr( (string) ( $row->abbreviation ?? '' ) ); ?>"
                                data-row-color="<?php echo esc_attr( $row_color ); ?>"
                                data-row-locked="<?php echo $is_locked ? '1' : '0'; ?>"
                                data-row-tx="<?php echo esc_attr( (string) wp_json_encode( $row_tx ) ); ?>">
                                <span class="tt-lkp-row-grip tt-drag-handle"
                                      title="<?php esc_attr_e( 'Drag to reorder', 'talenttrack' ); ?>"
                                      aria-hidden="true">⋮⋮</span>
                                <?php if ( $meta['show_color'] && $row_color !== '' ) : ?>
                                    <span class="tt-lkp-row-swatch" style="background:<?php echo esc_attr( $row_color ); ?>" aria-hidden="true"></span>
                                <?php else : ?>
                                    <span class="tt-lkp-row-swatch-blank" aria-hidden="true"></span>
                                <?php endif; ?>
                                <span class="tt-lkp-row-label">
                                    <?php echo esc_html( \TT\Infrastructure\Query\LookupTranslator::name( $row ) ); ?>
                                    <span class="tt-lkp-row-key"><?php echo esc_html( (string) $row->name ); ?></span>
                                    <?php if ( $is_locked ) : ?>
                                        <span class="tt-lkp-row-lock" title="<?php esc_attr_e( 'Locked — workflow rules depend on this row.', 'talenttrack' ); ?>" aria-label="<?php esc_attr_e( 'Locked', 'talenttrack' ); ?>">🔒</span>
                                    <?php endif; ?>
                                </span>
                                <?php
                                // Coverage dots — site locale first per Q2,
                                // then en_US, then remaining locales. Q5:
                                // dot = "name set" only (description is
                                // optional and doesn't gate coverage).
                                ?>
                                <span class="tt-lkp-coverage" title="<?php esc_attr_e( 'Translation coverage', 'talenttrack' ); ?>">
                                    <?php foreach ( $tx_targets as $locale ) :
                                        $has_name = isset( $row_tx[ $locale ]['name'] ) && (string) $row_tx[ $locale ]['name'] !== '';
                                        // On a fresh install where en_US
                                        // hasn't been backfilled yet, treat
                                        // the row's `name` column as the
                                        // English seed so the dot doesn't
                                        // misread an unmigrated row as
                                        // "missing English".
                                        if ( $locale === 'en_US' && ! $has_name ) {
                                            $has_name = ( (string) $row->name ) !== '';
                                        }
                                        $dot_class = $has_name ? 'tt-lkp-dot is-set' : 'tt-lkp-dot is-missing';
                                        $label_short = substr( $locale, 0, 2 );
                                        $title_str   = $has_name
                                            ? sprintf( esc_html__( '%s — set', 'talenttrack' ), $locale )
                                            : sprintf( esc_html__( '%s — missing', 'talenttrack' ), $locale );
                                        ?>
                                        <span class="<?php echo esc_attr( $dot_class ); ?>"
                                              data-locale="<?php echo esc_attr( $locale ); ?>"
                                              title="<?php echo esc_attr( $title_str ); ?>"
                                              aria-label="<?php echo esc_attr( $title_str ); ?>"></span>
                                        <?php
                                        // Suppress unused-variable lint
                                        // for legibility helpers.
                                        unset( $label_short );
                                    endforeach; ?>
                                </span>
                                <span class="tt-lkp-row-sort tt-sort-order-cell"><?php echo (int) $row->sort_order; ?></span>
                                <?php if ( ! $is_locked ) : ?>
                                    <button type="button"
                                            class="tt-lkp-row-delete"
                                            data-tt-lkp-delete="<?php echo (int) $row_id; ?>"
                                            data-tt-lkp-name="<?php echo esc_attr( (string) $row->name ); ?>"
                                            title="<?php esc_attr_e( 'Delete', 'talenttrack' ); ?>"
                                            aria-label="<?php esc_attr_e( 'Delete', 'talenttrack' ); ?>">×</button>
                                <?php else : ?>
                                    <span class="tt-lkp-row-delete-spacer" aria-hidden="true"></span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            <?php
            // Drag-reorder script targets `data-tt-sortable` and hits
            // the existing wp-admin endpoint — unchanged from prior
            // versions, so the operator's saved sort order keeps working.
            \TT\Shared\Admin\DragReorder::renderScript( 'lookup', $meta['type'] );
            ?>
            <?php if ( ! empty( $items ) && ! empty( $tx_targets ) ) : ?>
                <p class="tt-lkp-list-footer">
                    <?php
                    printf(
                        /* translators: %s = comma-separated list of locale codes */
                        esc_html__( 'Coverage dots show translation status for %s. Filled = set; warning = missing.', 'talenttrack' ),
                        esc_html( implode( ' · ', $tx_targets ) )
                    );
                    ?>
                </p>
            <?php endif; ?>

            <?php // #1039 — "+ Add value" sits under the list, right-aligned. ?>
            <div class="tt-lkp-list-foot-actions">
                <button type="button"
                        class="tt-lkp-btn tt-lkp-btn-primary tt-lkp-btn-add"
                        data-tt-lkp-go="add">
                    <?php esc_html_e( 'Add value', 'talenttrack' ); ?>
                </button>
            </div>
            <?php
            // Suppress unused-arg lint while keeping the signature stable.
            unset( $site_locale );
            ?>
        </section>
        <?php
    }

    /**
     * Form views — one shared form scaffolds both Add and Edit. The JS
     * rewrites input values + the data-state on the root to switch
     * between them. Footer follows the CLAUDE.md §6 Save+Cancel contract
     * via `FormSaveButton::render()` with a `cancel_url` that drops
     * `?edit=` so a Cancel from edit lands on the list view of the
     * same category.
     *
     * @param array{label:string,type:string,show_desc:bool,show_color:bool,show_abbrev:bool,slug:string} $meta
     * @param object|null $editing
     * @param array<int, array<string, array<string, string>>> $tx_by_row
     * @param list<string> $tx_targets
     */
    private static function renderLookupFormViews( array $meta, ?object $editing, array $tx_by_row, array $tx_targets, string $site_locale, string $add_id, string $base ): void {
        $existing_translations = $editing ? ( $tx_by_row[ (int) $editing->id ] ?? [] ) : [];
        $existing_meta_arr     = $editing ? QueryHelpers::lookup_meta( $editing ) : [];
        $existing_color        = is_string( $existing_meta_arr['color'] ?? null )
            ? (string) $existing_meta_arr['color']
            : '#5b6e75';

        $heading_id = $add_id . '-form-heading';
        ?>
        <?php // #1039 — right-column panel. Sticky on desktop; slides over
              // the list on mobile when a row is clicked (back-pill returns). ?>
        <aside class="tt-lkp-panel" aria-label="<?php esc_attr_e( 'Edit lookup value', 'talenttrack' ); ?>">

            <button type="button"
                    class="tt-lkp-btn tt-lkp-btn-ghost tt-lkp-back-to-list"
                    data-tt-lkp-go="list">
                <?php esc_html_e( '← Back to list', 'talenttrack' ); ?>
            </button>

            <div class="tt-lkp-panel-empty">
                <div class="tt-lkp-panel-empty-icon" aria-hidden="true"><?php echo \TT\Shared\Icons\IconRenderer::render( 'edit', [ 'width' => 28, 'height' => 28 ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — trusted SVG. ?></div>
                <p>
                    <strong><?php esc_html_e( 'Tap a row to edit', 'talenttrack' ); ?></strong>
                    <br>
                    <?php esc_html_e( 'or click + Add value to seed a new one.', 'talenttrack' ); ?>
                </p>
            </div>

            <section class="tt-lkp-view tt-lkp-view-form" aria-labelledby="<?php echo esc_attr( $heading_id ); ?>">
                <?php self::renderLookupForm( $meta, $editing, $existing_translations, $tx_targets, $site_locale, $add_id, $base, $heading_id, $existing_color ); ?>
            </section>
        </aside>
        <?php
    }

    /**
     * Single shared form renderer. Internal key is disabled on existing
     * rows (Q4). Save + Cancel pair rendered via `FormSaveButton`.
     *
     * @param array{label:string,type:string,show_desc:bool,show_color:bool,show_abbrev:bool,slug:string} $meta
     * @param array<string, array<string, string>> $existing_translations
     * @param list<string> $tx_targets
     */
    private static function renderLookupForm( array $meta, ?object $editing, array $existing_translations, array $tx_targets, string $site_locale, string $add_id, string $base, string $heading_id, string $color_default ): void {
        $is_edit = $editing !== null;
        $field_classes = 'tt-lkp-tx-grid';
        if ( $meta['show_desc'] )   $field_classes .= ' has-desc';
        if ( $meta['show_abbrev'] ) $field_classes .= ' has-abbrev';
        ?>
        <form data-tt-lkp-form novalidate>
            <input type="hidden" name="id" value="<?php echo (int) ( $editing->id ?? 0 ); ?>" data-tt-lkp-id />

            <div class="tt-lkp-card">
                <h2 id="<?php echo esc_attr( $heading_id ); ?>" class="tt-lkp-card-title" data-tt-lkp-form-title>
                    <?php echo $is_edit ? esc_html__( 'Edit value', 'talenttrack' ) : esc_html__( 'Add new value', 'talenttrack' ); ?>
                </h2>
                <div class="tt-lkp-form-grid">
                    <div class="tt-lkp-field">
                        <label for="<?php echo esc_attr( $add_id ); ?>-name" class="tt-lkp-field-required">
                            <?php esc_html_e( 'Internal key', 'talenttrack' ); ?>
                        </label>
                        <input type="text"
                               id="<?php echo esc_attr( $add_id ); ?>-name"
                               name="name"
                               value="<?php echo esc_attr( (string) ( $editing->name ?? '' ) ); ?>"
                               autocomplete="off"
                               inputmode="text"
                               <?php echo $is_edit ? 'readonly disabled' : 'required'; ?> />
                        <span class="tt-lkp-hint" data-tt-lkp-name-hint>
                            <?php
                            if ( $is_edit ) {
                                esc_html_e( 'Stable database identifier. Locked once the row is created — change it via a code migration.', 'talenttrack' );
                            } else {
                                esc_html_e( 'Lowercase ASCII, no spaces. Used as the database identifier and cannot be changed later.', 'talenttrack' );
                            }
                            ?>
                        </span>
                    </div>
                    <div class="tt-lkp-field">
                        <label for="<?php echo esc_attr( $add_id ); ?>-sort">
                            <?php esc_html_e( 'Sort order', 'talenttrack' ); ?>
                        </label>
                        <input type="number"
                               id="<?php echo esc_attr( $add_id ); ?>-sort"
                               name="sort_order"
                               inputmode="numeric"
                               min="0"
                               step="1"
                               value="<?php echo esc_attr( (string) ( $editing->sort_order ?? 0 ) ); ?>" />
                    </div>
                    <?php if ( $meta['show_color'] ) : ?>
                        <div class="tt-lkp-field">
                            <label for="<?php echo esc_attr( $add_id ); ?>-color">
                                <?php esc_html_e( 'Pill colour', 'talenttrack' ); ?>
                            </label>
                            <input type="color"
                                   id="<?php echo esc_attr( $add_id ); ?>-color"
                                   name="meta[color]"
                                   value="<?php echo esc_attr( $color_default ); ?>" />
                            <span class="tt-lkp-hint">
                                <?php esc_html_e( 'Used wherever this value renders as a colour-coded pill.', 'talenttrack' ); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    <?php if ( $meta['show_abbrev'] ) : ?>
                        <div class="tt-lkp-field">
                            <label for="<?php echo esc_attr( $add_id ); ?>-abbrev">
                                <?php esc_html_e( 'Abbreviation (canonical, optional)', 'talenttrack' ); ?>
                            </label>
                            <input type="text"
                                   id="<?php echo esc_attr( $add_id ); ?>-abbrev"
                                   name="abbreviation"
                                   maxlength="16"
                                   autocomplete="off"
                                   inputmode="text"
                                   autocapitalize="characters"
                                   value="<?php echo esc_attr( (string) ( $editing->abbreviation ?? '' ) ); ?>" />
                            <span class="tt-lkp-hint">
                                <?php esc_html_e( 'Short code for surfaces with no room for the full label, such as the position chips on a player. Leave blank to show the label instead.', 'talenttrack' ); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    <?php if ( $meta['show_desc'] ) : ?>
                        <div class="tt-lkp-field tt-lkp-field-full">
                            <label for="<?php echo esc_attr( $add_id ); ?>-desc">
                                <?php esc_html_e( 'Description (canonical, optional)', 'talenttrack' ); ?>
                            </label>
                            <input type="text"
                                   id="<?php echo esc_attr( $add_id ); ?>-desc"
                                   name="description"
                                   value="<?php echo esc_attr( (string) ( $editing->description ?? '' ) ); ?>" />
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ( ! empty( $tx_targets ) ) : ?>
                <div class="tt-lkp-card">
                    <div class="tt-lkp-tx-head">
                        <h3 class="tt-lkp-card-title" style="margin:0;">
                            <?php
                            printf(
                                /* translators: %d = number of supported locales */
                                esc_html__( 'Translations · %d locales', 'talenttrack' ),
                                (int) count( $tx_targets )
                            );
                            ?>
                        </h3>
                        <p class="tt-lkp-tx-help">
                            <?php esc_html_e( 'Every supported locale has a slot — including English, so the canonical value lives next to its translations. Site locale is highlighted; missing translations show a warning dot on the list.', 'talenttrack' ); ?>
                        </p>
                        <div class="tt-lkp-tx-actions">
                            <button type="button" class="tt-lkp-btn" data-tt-lkp-translate>
                                <?php esc_html_e( 'Translate from English', 'talenttrack' ); ?>
                            </button>
                            <span class="tt-lkp-tx-msg" data-tt-lkp-tx-msg></span>
                        </div>
                    </div>
                    <div class="<?php echo esc_attr( $field_classes ); ?>">
                        <?php foreach ( $tx_targets as $locale ) :
                            $field_id_name   = $add_id . '-tx-name-' . sanitize_html_class( $locale );
                            $field_id_desc   = $add_id . '-tx-desc-' . sanitize_html_class( $locale );
                            $field_id_abbrev = $add_id . '-tx-abbrev-' . sanitize_html_class( $locale );
                            $value_name   = (string) ( $existing_translations[ $locale ]['name']         ?? '' );
                            $value_desc   = (string) ( $existing_translations[ $locale ]['description']  ?? '' );
                            $value_abbrev = (string) ( $existing_translations[ $locale ]['abbreviation'] ?? '' );
                            $is_site    = ( $locale === $site_locale );
                            $cell_class = $is_site ? 'tt-lkp-tx-locale is-site' : 'tt-lkp-tx-locale';
                            ?>
                            <span class="<?php echo esc_attr( $cell_class ); ?>" aria-label="<?php echo esc_attr( $locale ); ?>">
                                <?php echo esc_html( substr( $locale, 0, 2 ) ); ?>
                            </span>
                            <input type="text"
                                   id="<?php echo esc_attr( $field_id_name ); ?>"
                                   name="translations[<?php echo esc_attr( $locale ); ?>][name]"
                                   value="<?php echo esc_attr( $value_name ); ?>"
                                   data-tt-tx-locale="<?php echo esc_attr( $locale ); ?>"
                                   data-tt-tx-field="name"
                                   aria-label="<?php
                                   printf(
                                        /* translators: %s = locale code such as nl_NL */
                                        esc_attr__( 'Label in %s', 'talenttrack' ),
                                        esc_attr( $locale )
                                   ); ?>"
                                   placeholder="<?php esc_attr_e( 'Label', 'talenttrack' ); ?>" />
                            <?php if ( $meta['show_abbrev'] ) : ?>
                                <input type="text"
                                       id="<?php echo esc_attr( $field_id_abbrev ); ?>"
                                       class="tt-lkp-tx-cell-abbrev"
                                       name="translations[<?php echo esc_attr( $locale ); ?>][abbreviation]"
                                       value="<?php echo esc_attr( $value_abbrev ); ?>"
                                       maxlength="16"
                                       autocapitalize="characters"
                                       data-tt-tx-locale="<?php echo esc_attr( $locale ); ?>"
                                       data-tt-tx-field="abbreviation"
                                       aria-label="<?php
                                       printf(
                                            /* translators: %s = locale code such as nl_NL */
                                            esc_attr__( 'Abbreviation in %s', 'talenttrack' ),
                                            esc_attr( $locale )
                                       ); ?>"
                                       placeholder="<?php echo esc_attr_x( 'Abbr.', 'placeholder for the short-code input on a lookup value', 'talenttrack' ); ?>" />
                            <?php endif; ?>
                            <?php if ( $meta['show_desc'] ) : ?>
                                <input type="text"
                                       id="<?php echo esc_attr( $field_id_desc ); ?>"
                                       class="tt-lkp-tx-cell-desc"
                                       name="translations[<?php echo esc_attr( $locale ); ?>][description]"
                                       value="<?php echo esc_attr( $value_desc ); ?>"
                                       data-tt-tx-locale="<?php echo esc_attr( $locale ); ?>"
                                       data-tt-tx-field="description"
                                       aria-label="<?php
                                       printf(
                                            /* translators: %s = locale code such as nl_NL */
                                            esc_attr__( 'Description in %s', 'talenttrack' ),
                                            esc_attr( $locale )
                                       ); ?>"
                                       placeholder="<?php esc_attr_e( 'Description', 'talenttrack' ); ?>" />
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="tt-lkp-form-foot">
                <div class="tt-lkp-foot-left">
                    <button type="button" class="tt-lkp-btn tt-lkp-btn-ghost" data-tt-lkp-go="list">
                        <?php esc_html_e( '← Back to list', 'talenttrack' ); ?>
                    </button>
                </div>
                <div class="tt-lkp-foot-right">
                    <?php
                    $save_label_idle = $is_edit ? __( 'Save changes', 'talenttrack' ) : __( 'Add value', 'talenttrack' );
                    echo FormSaveButton::render( [
                        'label'        => $save_label_idle,
                        'label_saving' => __( 'Saving…', 'talenttrack' ),
                        'label_saved'  => __( 'Saved', 'talenttrack' ),
                        'cancel_url'   => $base,
                        'cancel_label' => __( 'Cancel', 'talenttrack' ),
                    ] );
                    ?>
                </div>
            </div>
            <div class="tt-lkp-form-msg" data-tt-lkp-msg role="alert" aria-live="polite"></div>
        </form>
        <?php
    }

    /* The v3.110.203 master-detail markup, inline IIFE, and inline
     * `masterDetailStyles()` block were removed in v4.8.0 (#985). The
     * list-first rework replaces all three with the new
     * `renderLookupListView()` + `renderLookupForm()` pair plus the
     * shared `assets/css/frontend-lookup-admin.css` stylesheet and the
     * `assets/js/components/lookup-admin.js` module. */

    /*OLD_ORPHAN_DELETE_START*/
    private static function _UNUSED_DEAD_ORPHAN(): void {
        return;
        /* phpcs:disable */
        $type=''; $items=[]; $meta=[]; $editing=null; $edit_id=0; $base=''; $add_id=''; $tx_by_row=[]; $tx_targets=[]; $existing_translations=[]; $existing_color='';
        ?>
        <div class="tt-lookup-md" data-tt-lookups-editor
             data-lookup-type="<?php echo esc_attr( $type ); ?>"
             data-show-desc="<?php echo $meta['show_desc'] ? '1' : '0'; ?>"
             data-show-color="<?php echo $meta['show_color'] ? '1' : '0'; ?>"
             data-edit-id="<?php echo (int) $edit_id; ?>">

            <aside class="tt-lookup-md-rail" aria-label="<?php esc_attr_e( 'Lookup rows', 'talenttrack' ); ?>">
                <div class="tt-lookup-md-rail-head">
                    <button type="button" class="tt-btn tt-btn-primary tt-btn-small tt-lookup-md-new"
                            data-tt-lookup-new>
                        <?php esc_html_e( 'Add value', 'talenttrack' ); ?>
                    </button>
                </div>
                <div class="tt-lookup-md-rail-body">
                    <?php if ( empty( $items ) ) : ?>
                        <p class="tt-lookup-md-empty"><em><?php esc_html_e( 'No items yet. Use "+ Add new" to seed the first row.', 'talenttrack' ); ?></em></p>
                    <?php else : ?>
                        <ul class="tt-lookup-md-list tt-sortable-table" data-tt-sortable="1">
                            <?php foreach ( $items as $row ) :
                                $row_meta_arr = QueryHelpers::lookup_meta( $row );
                                $row_color    = is_string( $row_meta_arr['color'] ?? null ) ? (string) $row_meta_arr['color'] : '';
                                $is_locked    = ! empty( $row_meta_arr['is_locked'] );
                                $row_id       = (int) $row->id;
                                $row_tx       = $tx_by_row[ $row_id ] ?? [];
                                $is_active    = ( $editing && (int) $editing->id === $row_id );
                                ?>
                                <li class="tt-lookup-md-row<?php echo $is_active ? ' is-active' : ''; ?>"
                                    data-id="<?php echo $row_id; ?>"
                                    data-tt-lookup-row
                                    role="button"
                                    tabindex="0"
                                    data-row-name="<?php echo esc_attr( (string) $row->name ); ?>"
                                    data-row-sort="<?php echo (int) $row->sort_order; ?>"
                                    data-row-desc="<?php echo esc_attr( (string) ( $row->description ?? '' ) ); ?>"
                                    data-row-color="<?php echo esc_attr( $row_color ); ?>"
                                    data-row-locked="<?php echo $is_locked ? '1' : '0'; ?>"
                                    data-row-tx="<?php echo esc_attr( (string) wp_json_encode( $row_tx ) ); ?>">
                                    <span class="tt-lookup-md-row-grip tt-drag-handle"
                                          title="<?php esc_attr_e( 'Drag to reorder', 'talenttrack' ); ?>">⋮⋮</span>
                                    <?php if ( $meta['show_color'] && $row_color !== '' ) : ?>
                                        <span class="tt-lookup-md-row-swatch" style="background:<?php echo esc_attr( $row_color ); ?>" aria-hidden="true"></span>
                                    <?php endif; ?>
                                    <span class="tt-lookup-md-row-name">
                                        <?php echo esc_html( \TT\Infrastructure\Query\LookupTranslator::name( $row ) ); ?>
                                        <?php if ( $is_locked ) : ?>
                                            <span class="tt-lookup-md-row-lock" title="<?php esc_attr_e( 'Locked — workflow rules depend on this row.', 'talenttrack' ); ?>">🔒</span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="tt-lookup-md-row-sort tt-sort-order-cell"><?php echo (int) $row->sort_order; ?></span>
                                    <?php if ( ! $is_locked ) : ?>
                                        <button type="button" class="tt-lookup-md-row-delete"
                                                data-tt-lookup-delete="<?php echo $row_id; ?>"
                                                data-tt-lookup-name="<?php echo esc_attr( (string) $row->name ); ?>"
                                                title="<?php esc_attr_e( 'Delete', 'talenttrack' ); ?>"
                                                aria-label="<?php esc_attr_e( 'Delete', 'talenttrack' ); ?>">×</button>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <?php
                // Keep drag-reorder wiring intact. Targets the same
                // `data-tt-sortable` selector the previous table used,
                // so the existing wp-admin endpoint keeps working
                // unchanged.
                \TT\Shared\Admin\DragReorder::renderScript( 'lookup', $type );
                ?>
            </aside>

            <section class="tt-lookup-md-pane" aria-label="<?php esc_attr_e( 'Lookup editor', 'talenttrack' ); ?>">
                <header class="tt-lookup-md-pane-head">
                    <h3 class="tt-lookup-md-pane-title" data-tt-lookup-pane-title>
                        <?php echo $editing ? esc_html__( 'Edit row', 'talenttrack' ) : esc_html__( 'Add new row', 'talenttrack' ); ?>
                    </h3>
                    <button type="button" class="tt-lookup-md-pane-back" data-tt-lookup-pane-back
                            aria-label="<?php esc_attr_e( 'Back to list', 'talenttrack' ); ?>">
                        ← <?php esc_html_e( 'Back to list', 'talenttrack' ); ?>
                    </button>
                </header>
                <form data-tt-lookup-form class="tt-lookup-md-pane-body">
                    <input type="hidden" name="id" value="<?php echo (int) ( $editing->id ?? 0 ); ?>" data-tt-lookup-id />
                    <div class="tt-grid tt-grid-2">
                        <div class="tt-field">
                            <label class="tt-field-label tt-field-required" for="<?php echo esc_attr( $add_id ); ?>-name"><?php esc_html_e( 'Name', 'talenttrack' ); ?></label>
                            <input type="text" id="<?php echo esc_attr( $add_id ); ?>-name" class="tt-input" name="name" required value="<?php echo esc_attr( (string) ( $editing->name ?? '' ) ); ?>" />
                        </div>
                        <div class="tt-field">
                            <label class="tt-field-label" for="<?php echo esc_attr( $add_id ); ?>-sort"><?php esc_html_e( 'Sort order', 'talenttrack' ); ?></label>
                            <input type="number" id="<?php echo esc_attr( $add_id ); ?>-sort" class="tt-input" name="sort_order" inputmode="numeric" min="0" step="1" value="<?php echo esc_attr( (string) ( $editing->sort_order ?? 0 ) ); ?>" />
                        </div>
                        <?php if ( $meta['show_desc'] ) : ?>
                            <div class="tt-field" style="grid-column:1 / -1;">
                                <label class="tt-field-label" for="<?php echo esc_attr( $add_id ); ?>-desc"><?php esc_html_e( 'Description', 'talenttrack' ); ?></label>
                                <input type="text" id="<?php echo esc_attr( $add_id ); ?>-desc" class="tt-input" name="description" value="<?php echo esc_attr( (string) ( $editing->description ?? '' ) ); ?>" />
                            </div>
                        <?php endif; ?>
                        <?php if ( $meta['show_color'] ) :
                            $existing_meta = $editing ? QueryHelpers::lookup_meta( $editing ) : [];
                            $existing_color = is_string( $existing_meta['color'] ?? null ) ? (string) $existing_meta['color'] : '#5b6e75';
                            ?>
                            <div class="tt-field">
                                <label class="tt-field-label" for="<?php echo esc_attr( $add_id ); ?>-color"><?php esc_html_e( 'Pill colour', 'talenttrack' ); ?></label>
                                <input type="color" id="<?php echo esc_attr( $add_id ); ?>-color" name="meta[color]" value="<?php echo esc_attr( $existing_color ); ?>" />
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php
                    $existing_translations = $editing ? ( $tx_by_row[ (int) $editing->id ] ?? [] ) : [];
                    if ( ! empty( $tx_targets ) ) :
                        ?>
                        <div class="tt-field tt-lookup-md-tx" style="grid-column:1 / -1;">
                            <span class="tt-field-label"><?php esc_html_e( 'Translations', 'talenttrack' ); ?></span>
                            <p class="tt-lookup-md-tx-help">
                                <?php
                                if ( $meta['show_desc'] ) {
                                    esc_html_e( 'Per-locale name and description. Leave blank to fall back to the canonical English values and the .po-shipped translation. Click "Translate" to pre-fill the name fields from the configured engine — review and edit before saving.', 'talenttrack' );
                                } else {
                                    esc_html_e( 'Per-locale display name. Leave blank to fall back to the canonical English value and the .po-shipped translation. Click "Translate" to fill these from the configured engine — review and edit before saving.', 'talenttrack' );
                                }
                                ?>
                            </p>
                            <p>
                                <button type="button" class="tt-btn tt-btn-secondary" data-tt-lookup-translate><?php esc_html_e( 'Translate to other languages', 'talenttrack' ); ?></button>
                                <span class="tt-form-msg" data-tt-translate-msg style="margin-left:8px;"></span>
                            </p>
                            <div class="tt-lookup-md-tx-list">
                                <?php foreach ( $tx_targets as $locale ) :
                                    $field_id_name = $add_id . '-tx-name-' . sanitize_html_class( $locale );
                                    $field_id_desc = $add_id . '-tx-desc-' . sanitize_html_class( $locale );
                                    $value_name = (string) ( $existing_translations[ $locale ]['name']        ?? '' );
                                    $value_desc = (string) ( $existing_translations[ $locale ]['description'] ?? '' );
                                    ?>
                                    <div class="tt-field tt-lookup-md-tx-row">
                                        <label class="tt-field-label" for="<?php echo esc_attr( $field_id_name ); ?>"><code><?php echo esc_html( $locale ); ?></code></label>
                                        <input type="text" id="<?php echo esc_attr( $field_id_name ); ?>"
                                               class="tt-input"
                                               name="translations[<?php echo esc_attr( $locale ); ?>][name]"
                                               value="<?php echo esc_attr( $value_name ); ?>"
                                               data-tt-tx-locale="<?php echo esc_attr( $locale ); ?>"
                                               data-tt-tx-field="name"
                                               placeholder="<?php esc_attr_e( 'Translated name', 'talenttrack' ); ?>" />
                                        <?php if ( $meta['show_desc'] ) : ?>
                                            <input type="text" id="<?php echo esc_attr( $field_id_desc ); ?>"
                                                   class="tt-input"
                                                   name="translations[<?php echo esc_attr( $locale ); ?>][description]"
                                                   value="<?php echo esc_attr( $value_desc ); ?>"
                                                   data-tt-tx-locale="<?php echo esc_attr( $locale ); ?>"
                                                   data-tt-tx-field="description"
                                                   placeholder="<?php esc_attr_e( 'Translated description', 'talenttrack' ); ?>" />
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="tt-lookup-md-pane-foot">
                        <?php
                        $save_label_idle = $editing ? __( 'Save changes', 'talenttrack' ) : __( 'Add row', 'talenttrack' );
                        echo FormSaveButton::render( [
                            'label'        => $save_label_idle,
                            'label_saving' => __( 'Saving…', 'talenttrack' ),
                            'label_saved'  => __( 'Saved', 'talenttrack' ),
                            'cancel_url'   => $base,
                            'cancel_label' => __( 'Cancel', 'talenttrack' ),
                        ] );
                        ?>
                    </div>
                    <div class="tt-form-msg" data-tt-lookup-msg></div>
                </form>
            </section>
        </div>

        <script>
        (function(){
            'use strict';
            var root = document.querySelector('[data-tt-lookups-editor]');
            if (!root) return;

            var nonce = (window.TT && window.TT.rest_nonce) || (window.wpApiSettings && window.wpApiSettings.nonce) || '';
            var rest  = ((window.TT && window.TT.rest_url) || '/wp-json/talenttrack/v1/').replace(/\/+$/, '/');
            var type  = root.getAttribute('data-lookup-type');

            // i18n — localized labels used by inline JS error paths.
            var T_ERROR = '<?php echo esc_js( __( 'Error', 'talenttrack' ) ); ?>';
            var T_NETWORK_ERROR = '<?php echo esc_js( __( 'Network error.', 'talenttrack' ) ); ?>';

            // Save (create / update)
            var form = root.querySelector('[data-tt-lookup-form]');
            if (form) {
                form.addEventListener('submit', function(e){
                    e.preventDefault();
                    var msg = root.querySelector('[data-tt-lookup-msg]');
                    var fd  = new FormData(form);
                    var id  = parseInt(fd.get('id') || '0', 10);
                    var body = {
                        name:       String(fd.get('name') || '').trim(),
                        sort_order: parseInt(fd.get('sort_order') || '0', 10),
                    };
                    if (fd.get('description') !== null) body.description = String(fd.get('description') || '');
                    var meta = {};
                    if (fd.get('meta[color]')) meta.color = String(fd.get('meta[color]') || '');
                    if (Object.keys(meta).length > 0) body.meta = meta;

                    // v3.110.190 (#798) — collect per-locale name +
                    // description edits and send as a top-level
                    // `translations` field; the REST controller writes
                    // through TranslationsRepository to the canonical
                    // `tt_translations` table. The old meta.translations
                    // path is gone — it was a write-only sink the
                    // renderer didn't read.
                    var translations = {};
                    var tx_inputs = form.querySelectorAll('[data-tt-tx-locale]');
                    tx_inputs.forEach(function(inp){
                        var loc   = inp.getAttribute('data-tt-tx-locale');
                        var field = inp.getAttribute('data-tt-tx-field') || 'name';
                        var val   = String(inp.value || '').trim();
                        if (!loc) return;
                        if (!translations[loc]) translations[loc] = {};
                        // Empty string sent explicitly so the controller
                        // can drop a previously-set translation on save.
                        translations[loc][field] = val;
                    });
                    if (Object.keys(translations).length > 0) body.translations = translations;

                    var url = rest + 'lookups/' + encodeURIComponent(type);
                    var method = 'POST';
                    if (id > 0) { url += '/' + id; method = 'PUT'; }

                    msg.textContent = '';
                    fetch(url, {
                        method: method,
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce, 'Accept': 'application/json' },
                        body: JSON.stringify(body)
                    }).then(function(r){ return r.json().then(function(j){ return { ok: r.ok, status: r.status, json: j }; }); })
                      .then(function(r){
                        if (r.ok) { window.location.reload(); return; }
                        var first = r.json && r.json.errors && r.json.errors[0] && r.json.errors[0].message;
                        msg.textContent = first || (T_ERROR + ' ' + r.status);
                      })
                      .catch(function(){ msg.textContent = T_NETWORK_ERROR; });
                });
            }

            // v3.74.4 — Translate button: POST to /translations/preview
            // and fill the per-locale fields. Admin can edit before save.
            var tx_btn = root.querySelector('[data-tt-lookup-translate]');
            if (tx_btn) {
                tx_btn.addEventListener('click', function(){
                    var msg = root.querySelector('[data-tt-translate-msg]');
                    var name_input = root.querySelector('input[name="name"]');
                    var text = name_input ? String(name_input.value || '').trim() : '';
                    if (text === '') { msg.textContent = '<?php echo esc_js( __( 'Enter a name first.', 'talenttrack' ) ); ?>'; return; }
                    msg.textContent = '<?php echo esc_js( __( 'Translating…', 'talenttrack' ) ); ?>';
                    tx_btn.disabled = true;
                    fetch(rest + 'translations/preview', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce, 'Accept': 'application/json' },
                        body: JSON.stringify({ text: text, source_lang: '<?php echo esc_js( substr( (string) get_locale(), 0, 2 ) ); ?>' })
                    }).then(function(r){ return r.json().then(function(j){ return { ok: r.ok, status: r.status, json: j }; }); })
                      .then(function(r){
                        tx_btn.disabled = false;
                        if (r.ok && r.json && r.json.success) {
                            var translations = (r.json.data && r.json.data.translations) || {};
                            Object.keys(translations).forEach(function(loc){
                                var inp = form.querySelector('[data-tt-tx-locale="' + loc + '"]');
                                if (inp && (!inp.value || inp.value.trim() === '')) inp.value = translations[loc];
                            });
                            msg.textContent = '<?php echo esc_js( __( 'Translated. Review and edit before saving.', 'talenttrack' ) ); ?>';
                        } else {
                            var first = r.json && r.json.errors && r.json.errors[0] && r.json.errors[0].message;
                            msg.textContent = first || (T_ERROR + ' ' + r.status);
                        }
                      })
                      .catch(function(){ tx_btn.disabled = false; msg.textContent = '<?php echo esc_js( __( 'Network error.', 'talenttrack' ) ); ?>'; });
                });
            }

            // Delete (per-row buttons) — keep `closest` so swatch / text
            // clicks inside the row don't accidentally hit Delete.
            root.addEventListener('click', function(e){
                var btn = e.target.closest && e.target.closest('[data-tt-lookup-delete]');
                if (!btn) return;
                e.stopPropagation(); // don't also fire the row-select handler
                var id = parseInt(btn.getAttribute('data-tt-lookup-delete'), 10);
                var name = btn.getAttribute('data-tt-lookup-name') || '';
                if (!id) return;
                if (!window.confirm('<?php echo esc_js( __( 'Delete this row?', 'talenttrack' ) ); ?>'.replace('%s', name))) return;
                btn.disabled = true;
                fetch(rest + 'lookups/' + encodeURIComponent(type) + '/' + id, {
                    method: 'DELETE',
                    credentials: 'same-origin',
                    headers: { 'X-WP-Nonce': nonce, 'Accept': 'application/json' }
                }).then(function(r){ if (r.ok) window.location.reload(); else { btn.disabled = false; window.alert(T_ERROR + ' ' + r.status); } });
            });

            // v3.110.203 (#830) — row-click populates the form in-place
            // (no page navigation). Rail rows carry every value they own
            // as `data-row-*` attributes, including a JSON blob of all
            // translations for that row. The form is rendered once
            // server-side; we just rewrite its input values.
            var SAVE_LABEL_ADD  = '<?php echo esc_js( __( 'Add row', 'talenttrack' ) ); ?>';
            var SAVE_LABEL_EDIT = '<?php echo esc_js( __( 'Save changes', 'talenttrack' ) ); ?>';
            var TITLE_ADD       = '<?php echo esc_js( __( 'Add new row', 'talenttrack' ) ); ?>';
            var TITLE_EDIT      = '<?php echo esc_js( __( 'Edit row', 'talenttrack' ) ); ?>';

            function populate(row) {
                if (!form) return;
                var idEl   = form.querySelector('[data-tt-lookup-id]');
                var name   = form.querySelector('input[name="name"]');
                var sort   = form.querySelector('input[name="sort_order"]');
                var desc   = form.querySelector('input[name="description"]');
                var color  = form.querySelector('input[name="meta[color]"]');
                var title  = root.querySelector('[data-tt-lookup-pane-title]');
                var save   = form.querySelector('.tt-save-btn');
                var label  = save && save.querySelector('.tt-save-btn-label');
                var msg    = root.querySelector('[data-tt-lookup-msg]');

                if (msg) msg.textContent = '';

                if (row === null) {
                    if (idEl)  idEl.value  = '0';
                    if (name)  name.value  = '';
                    if (sort)  sort.value  = '0';
                    if (desc)  desc.value  = '';
                    if (color) color.value = '#5b6e75';
                    if (title) title.textContent = TITLE_ADD;
                    if (save)  save.setAttribute('data-label-idle', SAVE_LABEL_ADD);
                    if (label) label.textContent = SAVE_LABEL_ADD;
                } else {
                    if (idEl)  idEl.value  = String(row.getAttribute('data-id') || '0');
                    if (name)  name.value  = String(row.getAttribute('data-row-name') || '');
                    if (sort)  sort.value  = String(row.getAttribute('data-row-sort') || '0');
                    if (desc)  desc.value  = String(row.getAttribute('data-row-desc') || '');
                    if (color) color.value = String(row.getAttribute('data-row-color') || '') || '#5b6e75';
                    if (title) title.textContent = TITLE_EDIT;
                    if (save)  save.setAttribute('data-label-idle', SAVE_LABEL_EDIT);
                    if (label) label.textContent = SAVE_LABEL_EDIT;
                }

                // Translations: parse the JSON blob (or {} for blank).
                var tx = {};
                if (row !== null) {
                    var raw = row.getAttribute('data-row-tx') || '';
                    if (raw !== '') { try { tx = JSON.parse(raw); } catch (e) { tx = {}; } }
                }
                form.querySelectorAll('[data-tt-tx-locale]').forEach(function(inp){
                    var loc   = inp.getAttribute('data-tt-tx-locale');
                    var field = inp.getAttribute('data-tt-tx-field') || 'name';
                    var v     = (tx && tx[loc] && tx[loc][field]) || '';
                    inp.value = String(v);
                });

                // URL: keep ?edit=N for deep-link / refresh so the
                // selected row survives a reload. Empty selection
                // clears the param.
                var id = row !== null ? parseInt(row.getAttribute('data-id'), 10) : 0;
                var url = new URL(window.location.href);
                if (id > 0) url.searchParams.set('edit', String(id));
                else        url.searchParams.delete('edit');
                window.history.replaceState({}, '', url.toString());

                // Mobile: scroll the pane into view so the form is
                // visible after a tap on the rail. No-op on desktop
                // (the pane is already onscreen).
                if (window.matchMedia('(max-width: 767.98px)').matches) {
                    root.classList.add('is-pane-open');
                    var pane = root.querySelector('.tt-lookup-md-pane');
                    if (pane && pane.scrollIntoView) pane.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }

                // Mark the rail row active.
                root.querySelectorAll('[data-tt-lookup-row]').forEach(function(el){
                    if (row !== null && el === row) el.classList.add('is-active');
                    else el.classList.remove('is-active');
                });

                if (name) name.focus();
            }

            // Rail row click.
            root.addEventListener('click', function(e){
                if (e.target.closest && e.target.closest('[data-tt-lookup-delete]')) return; // handled above
                if (e.target.closest && e.target.closest('.tt-lookup-md-row-grip'))   return; // drag-handle, ignore
                var row = e.target.closest && e.target.closest('[data-tt-lookup-row]');
                if (!row) return;
                populate(row);
            });

            // Keyboard: Enter / Space on a focused row triggers the
            // same populate() the click handler runs. Each row has
            // role="button" tabindex="0", so it's reachable via Tab.
            root.addEventListener('keydown', function(e){
                if (e.key !== 'Enter' && e.key !== ' ') return;
                var row = e.target.closest && e.target.closest('[data-tt-lookup-row]');
                if (!row) return;
                if (e.target.closest && e.target.closest('[data-tt-lookup-delete]')) return;
                e.preventDefault();
                populate(row);
            });

            // "+ Add new" button → blank form, no row selected.
            var add_btn = root.querySelector('[data-tt-lookup-new]');
            if (add_btn) add_btn.addEventListener('click', function(){ populate(null); });

            // Mobile-only "Back to list" inside the pane header.
            var back_btn = root.querySelector('[data-tt-lookup-pane-back]');
            if (back_btn) back_btn.addEventListener('click', function(){
                root.classList.remove('is-pane-open');
                var rail = root.querySelector('.tt-lookup-md-rail');
                if (rail && rail.scrollIntoView) rail.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });

            // On mobile load: if we landed with ?edit=N, the pane is
            // already populated server-side — surface it.
            if (window.matchMedia('(max-width: 767.98px)').matches) {
                var initial_edit = parseInt(root.getAttribute('data-edit-id') || '0', 10);
                if (initial_edit > 0) root.classList.add('is-pane-open');
            }
        })();
        </script>
        <?php
    }

    private static function renderSubBackLink(): void {
        $base = remove_query_arg( [ 'config_sub' ] );
        echo '<p style="margin:0 0 var(--tt-sp-3);"><a class="tt-link" href="' . esc_url( $base ) . '">&larr; ' . esc_html__( 'All configuration', 'talenttrack' ) . '</a></p>';
    }

    /**
     * v3.110.203 (#830) — bulk-load translations for every row in the
     * rail in one SELECT. Without this we'd either do N+1 `allFor()`
     * calls or fetch on row-click; both are unnecessary round-trips
     * for data already keyed by `(entity_type='lookup', entity_id)`.
     *
     * @param list<int> $ids
     * @return array<int, array<string, array<string, string>>>  id => locale => field => value
     */
    private static function loadTranslationsForLookupIds( array $ids ): array {
        $ids = array_values( array_unique( array_filter( array_map( 'intval', $ids ), fn( $n ) => $n > 0 ) ) );
        if ( empty( $ids ) ) return [];

        global $wpdb;
        $table = $wpdb->prefix . 'tt_translations';

        // Schema check — early installs may not have the table yet.
        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) return [];

        $club_id      = \TT\Infrastructure\Tenancy\CurrentClub::id();
        $entity_type  = \TT\Modules\I18n\TranslatableFieldRegistry::ENTITY_LOOKUP;
        $placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

        $params   = array_merge( [ $club_id, $entity_type ], $ids );
        $sql      = $wpdb->prepare(
            "SELECT entity_id, field, locale, value
               FROM {$table}
              WHERE club_id = %d AND entity_type = %s AND entity_id IN ($placeholders)",
            ...$params
        );
        $rows = (array) $wpdb->get_results( $sql, ARRAY_A );

        $out = [];
        foreach ( $rows as $r ) {
            $id    = (int) ( $r['entity_id'] ?? 0 );
            $field = (string) ( $r['field']   ?? '' );
            $loc   = (string) ( $r['locale']  ?? '' );
            $val   = (string) ( $r['value']   ?? '' );
            if ( $id <= 0 || $field === '' || $loc === '' ) continue;
            $out[ $id ][ $loc ][ $field ] = $val;
        }
        return $out;
    }

    /**
     * Inline CSS for the lookup master-detail editor.
     * Mobile-first: stacked rail + pane below 768px; switches to a
     * two-column grid above. Pane footer (Save / Cancel) sticks to the
     * bottom of its column on desktop, scrolls inline on mobile.
     */
    private static function masterDetailStyles(): void {
        ?>
        <style>
        .tt-lookup-md { display: grid; grid-template-columns: 1fr; gap: var(--tt-sp-3, 12px); }
        .tt-lookup-md-rail,
        .tt-lookup-md-pane { background: #fff; border: 1px solid var(--tt-line, #e5e7ea); border-radius: 8px; }
        .tt-lookup-md-rail-head { display: flex; justify-content: flex-end; padding: var(--tt-sp-2, 8px) var(--tt-sp-3, 12px); border-bottom: 1px solid var(--tt-line, #e5e7ea); }
        .tt-lookup-md-rail-body { padding: var(--tt-sp-2, 8px) 0; max-height: 60vh; overflow-y: auto; }
        .tt-lookup-md-empty { padding: var(--tt-sp-3, 12px); color: var(--tt-muted, #6b7280); margin: 0; }
        .tt-lookup-md-list { list-style: none; margin: 0; padding: 0; }
        .tt-lookup-md-row { display: flex; align-items: center; gap: var(--tt-sp-2, 8px); padding: var(--tt-sp-2, 8px) var(--tt-sp-3, 12px); border-bottom: 1px solid var(--tt-line-soft, #eef0f2); cursor: pointer; min-height: 48px; user-select: none; }
        .tt-lookup-md-row:last-child { border-bottom: 0; }
        .tt-lookup-md-row:hover { background: #fafbfc; }
        .tt-lookup-md-row.is-active { background: #eef5ff; }
        .tt-lookup-md-row-grip { cursor: grab; color: #b6bcc2; font-size: 14px; min-width: 18px; }
        .tt-lookup-md-row-swatch { display: inline-block; width: 14px; height: 14px; border-radius: 3px; flex-shrink: 0; }
        .tt-lookup-md-row-name { flex: 1; font-weight: 500; }
        .tt-lookup-md-row-lock { margin-left: 6px; color: #888; font-size: 11px; }
        .tt-lookup-md-row-sort { color: var(--tt-muted, #6b7280); font-size: 12px; min-width: 24px; text-align: right; }
        .tt-lookup-md-row-delete { background: transparent; border: 0; color: #b32d2e; font-size: 22px; line-height: 1; cursor: pointer; padding: 4px 8px; border-radius: 4px; min-width: 32px; min-height: 32px; }
        .tt-lookup-md-row-delete:hover { background: #fde2e2; }
        .tt-lookup-md-pane { display: flex; flex-direction: column; }
        .tt-lookup-md-pane-head { display: flex; align-items: center; justify-content: space-between; gap: var(--tt-sp-2, 8px); padding: var(--tt-sp-2, 8px) var(--tt-sp-3, 12px); border-bottom: 1px solid var(--tt-line, #e5e7ea); }
        .tt-lookup-md-pane-title { margin: 0; font-size: 16px; line-height: 1.3; }
        .tt-lookup-md-pane-back { display: none; background: transparent; border: 0; color: #1a4f9e; cursor: pointer; padding: 6px 10px; font-size: 14px; }
        .tt-lookup-md-pane-body { padding: var(--tt-sp-3, 12px); display: flex; flex-direction: column; gap: var(--tt-sp-3, 12px); overflow-y: auto; max-height: 70vh; flex: 1; }
        .tt-lookup-md-tx { margin-top: var(--tt-sp-3, 12px); border-top: 1px solid var(--tt-line, #e5e7ea); padding-top: var(--tt-sp-3, 12px); }
        .tt-lookup-md-tx-help { font-size: 12px; color: var(--tt-muted, #6b7280); margin: 0 0 var(--tt-sp-3, 12px); }
        .tt-lookup-md-tx-list { display: flex; flex-direction: column; gap: var(--tt-sp-2, 8px); }
        .tt-lookup-md-tx-row { padding: var(--tt-sp-2, 8px) 0; border-bottom: 1px solid var(--tt-line-soft, #eef0f2); display: flex; flex-direction: column; gap: 4px; }
        .tt-lookup-md-tx-row:last-child { border-bottom: 0; }
        .tt-lookup-md-tx-row .tt-field-label { margin-bottom: 4px; }
        .tt-lookup-md-pane-foot { position: sticky; bottom: 0; background: #fff; padding: var(--tt-sp-3, 12px) 0 0; border-top: 1px solid var(--tt-line, #e5e7ea); margin-top: var(--tt-sp-3, 12px); }
        /* Mobile: rail stays up top, pane hidden until a row is picked
         * (or until ?edit=N comes in on initial load — handled in JS). */
        @media (max-width: 767.98px) {
            .tt-lookup-md-pane { display: none; }
            .tt-lookup-md.is-pane-open .tt-lookup-md-pane { display: flex; }
            .tt-lookup-md.is-pane-open .tt-lookup-md-rail { display: none; }
            .tt-lookup-md-pane-back { display: inline-block; }
            .tt-lookup-md-rail-body { max-height: none; }
        }
        @media (min-width: 768px) {
            .tt-lookup-md { grid-template-columns: minmax(0, 2fr) minmax(0, 3fr); align-items: start; }
            .tt-lookup-md-pane-back { display: none; }
        }
        </style>
        <?php
    }

    /**
     * Inline CSS for the configuration tile grid. Shared between the
     * top-level Configuration index and the Lookups sub-index so a
     * direct deep-link to `?config_sub=lookups` still picks up the
     * styling without the top-level tile grid having run first.
     */
    private static function tileGridStyles(): void {
        // #1587 — shared tile-grid standard CSS (idempotent per request).
        echo \TT\Shared\Frontend\Components\TileGridStandard::styles(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — static trusted CSS.
        // The `.tt-cfg-tile-grid` markup carries the active preset's
        // custom properties so every config tile responds to the
        // academy-wide Tile appearance setting without a separate wrapper.
        ?>
        <style>
        .tt-cfg-tile-grid { <?php echo esc_html( \TT\Shared\Frontend\Components\TileGridStandard::cssVars() ); ?> }
        /* #1587 — the config tile grid/card now consume the shared
         * `--tt-tile-*` custom properties (TileGridStandard), so size +
         * layout match every other tile surface and respond to the
         * academy-wide Tile appearance preset. The `.tt-cfg-tile-grid`
         * markup wrapper sets the variables (see renderTileGrid /
         * tileGridStyles emitting TileGridStandard::openWrap). */
        .tt-cfg-tile-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(var(--tt-tile-min-width, 220px), 1fr)); gap: var(--tt-tile-gap, 10px); }
        .tt-cfg-tile { display: block; background: #fff; border: 1px solid var(--tt-line, #e5e7ea); border-radius: var(--tt-tile-radius, 8px); padding: var(--tt-tile-padding, 14px); text-decoration: none; color: #1a1d21; min-height: var(--tt-tile-min-height, 76px); box-shadow: var(--tt-shadow-sm, none); transition: transform var(--tt-motion-duration, 180ms) var(--tt-motion-easing, cubic-bezier(0.2, 0.8, 0.2, 1)), box-shadow var(--tt-motion-duration, 180ms) var(--tt-motion-easing, ease), border-color var(--tt-motion-duration, 180ms) var(--tt-motion-easing, ease); }
        .tt-cfg-tile:hover, .tt-cfg-tile:focus, .tt-cfg-tile:focus-visible { transform: translateY(-1px); box-shadow: var(--tt-shadow-md, 0 4px 12px rgba(0,0,0,0.08)); border-color: #d0d4d8; color: #1a1d21; }
        /* #1553 — the icon slot now hosts a TileIconChip (Phosphor duotone
         * glyph in an accent chip); the chip sizes itself, so this wrapper
         * only handles spacing. Emoji tiles still set their own font-size
         * inline. */
        .tt-cfg-tile-icon { margin-bottom: 8px; line-height: 0; }
        .tt-cfg-tile-title { font-weight: 600; font-size: 14px; line-height: 1.25; margin: 0 0 4px; color: #1a1d21; }
        .tt-cfg-tile-desc { color: #6b7280; font-size: 12px; line-height: 1.35; margin: 0; }
        /* #1087 VCT-12 — accent variant + count line for the two VCT
         * Configuration tiles (macro-blocks + age-profiles). Pattern
         * lifted from the PDP-blocks tile; accent + "NEW" pill mirror
         * the `.local-mockups/vct-config-tiles/` design-of-record. */
        .tt-cfg-tile--vct { border-color: #1d7874; position: relative; }
        .tt-cfg-tile--vct:hover, .tt-cfg-tile--vct:focus { border-color: #145955; }
        .tt-cfg-tile-badge { position: absolute; top: 8px; right: 8px; background: #1d7874; color: #fff; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: 800; letter-spacing: 0.5px; }
        .tt-cfg-tile-count { margin-top: 8px; font-size: 11px; color: #1d7874; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px; }
        </style>
        <?php
        echo \TT\Shared\Frontend\Components\TileIconChip::styles(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — static trusted CSS.
    }

    /**
     * #3046 — the Configuration landing grid, grouped by module.
     *
     * Which entries exist, which module each belongs to and whether the
     * viewer may open it are all decided in
     * {@see \TT\Shared\Configuration\ConfigurationSurfaces}; this method
     * resolves the two base URLs from the current request and paints.
     */
    private static function renderTileGrid(): void {
        $sections = \TT\Shared\Configuration\ConfigurationSurfaces::groups(
            get_current_user_id(),
            (string) remove_query_arg( [ 'config_sub' ] ),
            (string) remove_query_arg( [ 'tt_view', 'config_sub' ] )
        );

        self::tileGridStyles();
        \TT\Shared\Frontend\Components\FrontendSectionedTileGrid::render(
            $sections,
            [ 'grid_inline' => false, 'tile_renderer' => [ self::class, 'renderConfigTile' ] ]
        );
    }

    /**
     * #1532 — unified renderer for one Configuration tile, used by every
     * section. Handles an IconRenderer slug or an emoji icon, the
     * "opens in wp-admin" marker, and the VCT accent variant (badge +
     * count line).
     *
     * @param array<string,mixed> $tile
     */
    public static function renderConfigTile( array $tile ): void {
        $title    = (string) ( $tile['title'] ?? '' );
        $desc     = (string) ( $tile['desc'] ?? '' );
        $url      = (string) ( $tile['url'] ?? '' );
        $icon     = (string) ( $tile['icon'] ?? '' );
        $emoji    = ! empty( $tile['emoji'] );
        $external = ! empty( $tile['external'] );
        $is_vct   = ! empty( $tile['vct'] );

        echo '<a class="tt-cfg-tile' . ( $is_vct ? ' tt-cfg-tile--vct' : '' ) . '" href="' . esc_url( $url ) . '">';
        if ( $is_vct ) {
            echo '<span class="tt-cfg-tile-badge">' . esc_html__( 'NEW', 'talenttrack' ) . '</span>';
        }
        if ( $icon !== '' ) {
            if ( $emoji ) {
                echo '<div class="tt-cfg-tile-icon" style="font-size:22px; line-height:1;">' . esc_html( $icon ) . '</div>';
            } else {
                echo '<div class="tt-cfg-tile-icon">' . \TT\Shared\Frontend\Components\TileIconChip::render( $icon, '#0b3d2e' ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — TileIconChip escapes its own attrs and IconRenderer returns trusted SVG.
            }
        }
        echo '<div class="tt-cfg-tile-title">' . esc_html( $title );
        if ( $external ) {
            echo ' ' . \TT\Shared\Icons\IconRenderer::render( 'external-link', [ 'width' => 12, 'height' => 12, 'style' => 'vertical-align:-1px;' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — trusted SVG.
        }
        echo '</div>';
        echo '<div class="tt-cfg-tile-desc">' . esc_html( $desc ) . '</div>';
        if ( ! empty( $tile['count'] ) ) {
            echo '<div class="tt-cfg-tile-count">' . esc_html( (string) $tile['count'] ) . '</div>';
        }
        echo '</a>';
    }

    /**
     * #1531 — single "Appearance" surface consolidating the former
     * Branding and Theme & fonts tiles. Stacked sections: Identity,
     * Colours (primary/secondary + the accent palette, all in one place),
     * Typography, Theme, and an Advanced link to Custom CSS. One form +
     * one Save/Cancel; reuses the existing config keys + save path, so no
     * data migration. The accent-colour fields simply moved out of the
     * theme form into Colours.
     */
    private static function renderAppearanceForm(): void {
        $logo            = QueryHelpers::get_config( 'logo_url', '' );
        $club_short_code = QueryHelpers::get_config( 'club_short_code', '' );
        $font_display    = (string) QueryHelpers::get_config( 'font_display',  BrandFonts::SYSTEM_DEFAULT );
        $font_body       = (string) QueryHelpers::get_config( 'font_body',     BrandFonts::SYSTEM_DEFAULT );
        // #1587 — academy-wide Tile appearance preset.
        $tile_appearance = \TT\Shared\Frontend\Components\TileGridStandard::activePreset();
        // #1598 — academy-wide Tile layout (orthogonal to the size preset).
        $tile_layout     = \TT\Shared\Frontend\Components\TileGridStandard::activeLayout();
        // #1809 — academy-wide Tile colour scheme (orthogonal to size + layout).
        $tile_style      = \TT\Shared\Frontend\Components\TileGridStandard::activeStyle();
        $tile_labels     = [
            'compact'     => __( 'Compact', 'talenttrack' ),
            'comfortable' => __( 'Comfortable', 'talenttrack' ),
            'spacious'    => __( 'Spacious', 'talenttrack' ),
        ];
        $tile_layout_labels = [
            'row'     => __( 'Row (icon left of title)', 'talenttrack' ),
            'stacked' => __( 'Stacked (icon + title, description below)', 'talenttrack' ),
        ];
        $tile_style_labels = [
            'default'     => __( 'Default', 'talenttrack' ),
            'border'      => __( 'Brand border', 'talenttrack' ),
            'gold-topped' => __( 'Gold-topped', 'talenttrack' ),
            'soft-fill'   => __( 'Soft green fill', 'talenttrack' ),
            'solid'       => __( 'Solid green', 'talenttrack' ),
            'left-accent' => __( 'Left accent', 'talenttrack' ),
            'federation'  => __( 'Federation — top edge, one chip colour', 'talenttrack' ),
        ];
        $cancel_url      = remove_query_arg( [ 'config_sub' ] );
        $css_url         = add_query_arg( [ 'tt_view' => 'custom-css' ], remove_query_arg( [ 'tt_view', 'config_sub' ] ) );
        ?>
        <form id="tt-config-form" data-tt-config-form="1" data-tt-config-sub="appearance">

            <h3 class="tt-cfg-section-head" style="margin:8px 0 8px;"><?php esc_html_e( 'Identity', 'talenttrack' ); ?></h3>
            <div class="tt-panel">
                <div class="tt-grid tt-grid-2">
                    <div class="tt-field">
                        <label class="tt-field-label" for="tt-cfg-academy-name"><?php esc_html_e( 'Academy name', 'talenttrack' ); ?></label>
                        <input type="text" id="tt-cfg-academy-name" class="tt-input" name="config[academy_name]" value="<?php echo esc_attr( QueryHelpers::get_config( 'academy_name', '' ) ); ?>" />
                    </div>
                    <div class="tt-field">
                        <label class="tt-field-label" for="tt-cfg-club-short-code"><?php esc_html_e( 'Club short code', 'talenttrack' ); ?></label>
                        <input type="text" id="tt-cfg-club-short-code" class="tt-input" name="config[club_short_code]" maxlength="3" value="<?php echo esc_attr( $club_short_code ); ?>" inputmode="text" autocomplete="off" />
                        <p class="tt-field-hint" style="margin-top:6px; color:var(--tt-muted);"><?php esc_html_e( 'Three-letter club abbreviation shown on the match scoreboard (e.g. HED for vv Hedel). Leave empty to derive from the academy name.', 'talenttrack' ); ?></p>
                    </div>
                    <div class="tt-field">
                        <span class="tt-field-label"><?php esc_html_e( 'Logo', 'talenttrack' ); ?></span>
                        <input type="hidden" id="tt-cfg-logo-url" name="config[logo_url]" value="<?php echo esc_attr( $logo ); ?>" />
                        <div id="tt-cfg-logo-preview" style="margin-bottom:8px;">
                            <?php if ( $logo ) : ?>
                                <img src="<?php echo esc_url( $logo ); ?>" alt="" style="max-height:80px; border-radius:6px; border:1px solid var(--tt-line);" />
                            <?php endif; ?>
                        </div>
                        <button type="button" class="tt-btn tt-btn-secondary" id="tt-cfg-logo-pick"><?php esc_html_e( 'Choose logo…', 'talenttrack' ); ?></button>
                        <button type="button" class="tt-btn tt-btn-secondary" id="tt-cfg-logo-clear" style="margin-left:6px;"><?php esc_html_e( 'Remove', 'talenttrack' ); ?></button>
                    </div>
                </div>
            </div>

            <h3 class="tt-cfg-section-head" style="margin:18px 0 8px;"><?php esc_html_e( 'Colours', 'talenttrack' ); ?></h3>
            <div class="tt-panel">
                <?php
                // #2524 — the theme picker belongs on Appearance, at the top
                // of the panel whose settings it overrides. It shipped in
                // #2512 anchored to the Navigation layout selector, which
                // turned out to live in renderGeneralForm() — so the control
                // sat on General while the "these colours are not in use"
                // notice it causes rendered here, on Appearance.
                $theme_current = \TT\Shared\Frontend\ThemePreference::clubDefault();
                ?>
                <div class="tt-field tt-field--section-top">
                    <h4 class="tt-field-subhead"><?php esc_html_e( 'Visual theme', 'talenttrack' ); ?></h4>
                    <label class="tt-field-label" for="tt-cfg-frontend-theme"><?php esc_html_e( 'Academy default', 'talenttrack' ); ?></label>
                    <select id="tt-cfg-frontend-theme" class="tt-input" name="config[tt_frontend_theme]">
                        <?php foreach ( \TT\Shared\Frontend\ThemePreference::labels() as $theme_value => $theme_label ) : ?>
                            <option value="<?php echo esc_attr( $theme_value ); ?>" <?php selected( $theme_current, $theme_value ); ?>><?php echo esc_html( $theme_label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="tt-field-hint">
                        <?php esc_html_e( 'Changes colours, corners and heading type only — never what anyone can see or do. Federation is a navy chrome with a gold marker on the active section; Leon Hutten is the club design system, a black crest banner with a cyan stripe. A theme supplies the whole colour scheme, so the Colours settings do not apply while one is active; your logo and academy name still do. Everyone can pick their own theme in My settings; this is what they get until they do.', 'talenttrack' ); ?>
                    </p>
                </div>

                <?php
                // #2515 — a theme owns the complete visual design, so these
                // fields do nothing while one is active. Saying so up front
                // beats letting an operator pick a colour and watch the page
                // not change.
                if ( \TT\Shared\Frontend\ThemePreference::hasThemeSheet() ) :
                    $active_theme_labels = \TT\Shared\Frontend\ThemePreference::labels();
                    $active_theme        = \TT\Shared\Frontend\ThemePreference::resolve();
                    ?>
                    <div class="tt-notice tt-notice-info">
                        <?php
                        printf(
                            /* translators: %s: the name of the active visual theme. */
                            esc_html__( 'These colours are not in use. The %s theme supplies the whole colour scheme, so the fields below have no effect until the visual theme is set back to Default. Your logo and academy name are unaffected.', 'talenttrack' ),
                            esc_html( $active_theme_labels[ $active_theme ] ?? $active_theme )
                        );
                        ?>
                    </div>
                <?php endif; ?>
                <p style="margin:0 0 var(--tt-sp-3); color:var(--tt-muted);"><?php esc_html_e( 'Every brand colour in one place — the two primary brand colours plus the accent/status palette.', 'talenttrack' ); ?></p>
                <div class="tt-grid tt-grid-2">
                    <div class="tt-field">
                        <label class="tt-field-label" for="tt-cfg-primary-color"><?php esc_html_e( 'Primary color', 'talenttrack' ); ?></label>
                        <input type="color" id="tt-cfg-primary-color" name="config[primary_color]" value="<?php echo esc_attr( QueryHelpers::get_config( 'primary_color', '#0b3d2e' ) ); ?>" />
                    </div>
                    <div class="tt-field">
                        <label class="tt-field-label" for="tt-cfg-secondary-color"><?php esc_html_e( 'Secondary color', 'talenttrack' ); ?></label>
                        <input type="color" id="tt-cfg-secondary-color" name="config[secondary_color]" value="<?php echo esc_attr( QueryHelpers::get_config( 'secondary_color', '#e8b624' ) ); ?>" />
                    </div>
                    <?php
                    foreach ( [
                        'color_accent'  => [ __( 'Accent color',     'talenttrack' ), '#1e88e5' ],
                        'color_danger'  => [ __( 'Danger color',     'talenttrack' ), '#b32d2e' ],
                        'color_warning' => [ __( 'Warning color',    'talenttrack' ), '#dba617' ],
                        'color_success' => [ __( 'Success color',    'talenttrack' ), '#00a32a' ],
                        'color_info'    => [ __( 'Info color',       'talenttrack' ), '#2271b1' ],
                        'color_focus'   => [ __( 'Focus ring color', 'talenttrack' ), '#1e88e5' ],
                    ] as $key => $meta ) :
                        [ $label, $default ] = $meta;
                        ?>
                        <div class="tt-field">
                            <label class="tt-field-label" for="tt-cfg-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
                            <input type="color" id="tt-cfg-<?php echo esc_attr( $key ); ?>" name="config[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( QueryHelpers::get_config( $key, $default ) ); ?>" />
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <h3 class="tt-cfg-section-head" style="margin:18px 0 8px;"><?php esc_html_e( 'Typography', 'talenttrack' ); ?></h3>
            <div class="tt-panel">
                <div class="tt-grid tt-grid-2">
                    <div class="tt-field">
                        <label class="tt-field-label" for="tt-cfg-font-display"><?php esc_html_e( 'Display font', 'talenttrack' ); ?></label>
                        <select id="tt-cfg-font-display" class="tt-input" name="config[font_display]">
                            <?php foreach ( BrandFonts::displayOptions() as $value => $label ) : ?>
                                <option value="<?php echo esc_attr( (string) $value ); ?>" <?php selected( $font_display, (string) $value ); ?>><?php echo esc_html( (string) $label ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="tt-field">
                        <label class="tt-field-label" for="tt-cfg-font-body"><?php esc_html_e( 'Body font', 'talenttrack' ); ?></label>
                        <select id="tt-cfg-font-body" class="tt-input" name="config[font_body]">
                            <?php foreach ( BrandFonts::bodyOptions() as $value => $label ) : ?>
                                <option value="<?php echo esc_attr( (string) $value ); ?>" <?php selected( $font_body, (string) $value ); ?>><?php echo esc_html( (string) $label ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <h3 class="tt-cfg-section-head" style="margin:18px 0 8px;"><?php esc_html_e( 'Tile appearance', 'talenttrack' ); ?></h3>
            <div class="tt-panel">
                <p style="margin:0 0 var(--tt-sp-3); color:var(--tt-muted);">
                    <?php esc_html_e( 'Set the size and arrangement of the tiles shown on the dashboard, Configuration, Reports and Teams. Size and layout are independent — pick any combination.', 'talenttrack' ); ?>
                </p>
                <div class="tt-field">
                    <label class="tt-field-label" for="tt-cfg-tile-appearance"><?php esc_html_e( 'Tile size', 'talenttrack' ); ?></label>
                    <select id="tt-cfg-tile-appearance" class="tt-input" name="config[tile_appearance]">
                        <?php foreach ( \TT\Shared\Frontend\Components\TileGridStandard::presetKeys() as $preset_key ) : ?>
                            <option value="<?php echo esc_attr( $preset_key ); ?>" <?php selected( $tile_appearance, $preset_key ); ?>><?php echo esc_html( (string) ( $tile_labels[ $preset_key ] ?? $preset_key ) ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="tt-field">
                    <label class="tt-field-label" for="tt-cfg-tile-layout"><?php esc_html_e( 'Tile layout', 'talenttrack' ); ?></label>
                    <select id="tt-cfg-tile-layout" class="tt-input" name="config[tile_layout]">
                        <?php foreach ( \TT\Shared\Frontend\Components\TileGridStandard::layoutKeys() as $layout_key ) : ?>
                            <option value="<?php echo esc_attr( $layout_key ); ?>" <?php selected( $tile_layout, $layout_key ); ?>><?php echo esc_html( (string) ( $tile_layout_labels[ $layout_key ] ?? $layout_key ) ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="tt-field-hint" style="margin:var(--tt-sp-2) 0 0; color:var(--tt-muted); font-size:.85em;">
                        <?php esc_html_e( 'Stacked puts the icon and title on the first line with the description beneath, so titles can run two lines without widening the tile.', 'talenttrack' ); ?>
                    </p>
                </div>
                <div class="tt-field">
                    <label class="tt-field-label" for="tt-cfg-tile-style"><?php esc_html_e( 'Tile colour scheme', 'talenttrack' ); ?></label>
                    <select id="tt-cfg-tile-style" class="tt-input" name="config[tile_style]">
                        <?php foreach ( \TT\Shared\Frontend\Components\TileGridStandard::styleKeys() as $style_key ) : ?>
                            <option value="<?php echo esc_attr( $style_key ); ?>" <?php selected( $tile_style, $style_key ); ?>><?php echo esc_html( (string) ( $tile_style_labels[ $style_key ] ?? $style_key ) ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <h3 class="tt-cfg-section-head" style="margin:18px 0 8px;"><?php esc_html_e( 'Theme isolation', 'talenttrack' ); ?></h3>
            <div class="tt-panel">
                <p style="margin:0; color:var(--tt-muted);">
                    <?php esc_html_e( 'TalentTrack always renders as a full-canvas app, isolated from the active WordPress theme — the theme’s header, footer, sidebar, menus and CSS are stripped so only TalentTrack’s own design shows. This keeps your palette and layout consistent on every install.', 'talenttrack' ); ?>
                </p>
            </div>

            <h3 class="tt-cfg-section-head" style="margin:18px 0 8px;"><?php esc_html_e( 'Advanced', 'talenttrack' ); ?></h3>
            <div class="tt-panel">
                <p style="margin:0 0 var(--tt-sp-3); color:var(--tt-muted);"><?php esc_html_e( 'Per-club custom styling — visual + code editor, file upload, starter templates and revertable history.', 'talenttrack' ); ?></p>
                <a class="tt-btn tt-btn-secondary" href="<?php echo esc_url( $css_url ); ?>"><?php esc_html_e( 'Open custom CSS', 'talenttrack' ); ?></a>
            </div>

            <div style="margin-top:16px;">
                <?php echo FormSaveButton::render( [ 'label' => __( 'Save', 'talenttrack' ), 'cancel_url' => $cancel_url ] ); ?>
            </div>
            <div class="tt-form-msg"></div>
        </form>
        <?php
        self::renderConfigJs( true );
    }

    /**
     * #1481 — General settings: academy-wide date notation, first day of
     * the week, timezone and locale. Save-only (settings sub-form, §6a
     * wizard + Cancel exemption). Persists via POST /config like the
     * other inline sub-forms; the keys are whitelisted in
     * ConfigRestController::ALLOWED_KEYS.
     */
    private static function renderGeneralForm(): void {
        $date_preset = QueryHelpers::get_config( \TT\Shared\Dates\TTDate::FORMAT_KEY, 'system' );
        $week_start  = QueryHelpers::get_config( \TT\Shared\Dates\TTDate::WEEK_START_KEY, 'mon' );
        $tz_current  = QueryHelpers::get_config( \TT\Shared\Dates\TTDate::TIMEZONE_KEY, '' );
        if ( $tz_current === '' ) {
            $opt        = get_option( 'timezone_string' );
            $tz_current = is_string( $opt ) ? $opt : '';
        }
        $locale_current = QueryHelpers::get_config( \TT\Shared\Dates\TTDate::LOCALE_KEY, '' );
        if ( $locale_current === '' ) {
            $locale_current = (string) get_locale();
        }

        $preset_labels  = \TT\Shared\Dates\TTDate::presetLabels();
        $preset_samples = \TT\Shared\Dates\TTDate::presetSamples();
        $locales        = array_merge( [ 'en_US' ], (array) get_available_languages() );
        $locales        = array_values( array_unique( $locales ) );
        ?>
        <form id="tt-config-form" data-tt-config-form="1" data-tt-config-sub="general">
            <div class="tt-panel">
                <div class="tt-field">
                    <label class="tt-field-label" for="tt-cfg-date-format"><?php esc_html_e( 'Date notation', 'talenttrack' ); ?></label>
                    <select id="tt-cfg-date-format" class="tt-input" name="config[tt_date_format]">
                        <?php foreach ( $preset_labels as $slug => $label ) : ?>
                            <option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $date_preset, $slug ); ?>><?php echo esc_html( $label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="tt-field-hint" style="margin-top:6px;">
                        <?php esc_html_e( 'How dates are written across TalentTrack. Example with today’s date:', 'talenttrack' ); ?>
                        <strong id="tt-cfg-date-preview"><?php echo esc_html( $preset_samples[ $date_preset ] ?? '' ); ?></strong>
                    </p>
                </div>

                <div class="tt-field" style="margin-top:var(--tt-sp-3);">
                    <label class="tt-field-label" for="tt-cfg-week-start"><?php esc_html_e( 'First day of the week', 'talenttrack' ); ?></label>
                    <select id="tt-cfg-week-start" class="tt-input" name="config[tt_week_start]">
                        <option value="mon" <?php selected( $week_start, 'mon' ); ?>><?php esc_html_e( 'Monday', 'talenttrack' ); ?></option>
                        <option value="sun" <?php selected( $week_start, 'sun' ); ?>><?php esc_html_e( 'Sunday', 'talenttrack' ); ?></option>
                    </select>
                    <p class="tt-field-hint" style="margin-top:6px;">
                        <?php esc_html_e( 'The day the team planner week grid starts on.', 'talenttrack' ); ?>
                    </p>
                </div>

                <div class="tt-field" style="margin-top:var(--tt-sp-3);">
                    <label class="tt-field-label" for="tt-cfg-timezone"><?php esc_html_e( 'Timezone', 'talenttrack' ); ?></label>
                    <select id="tt-cfg-timezone" class="tt-input" name="config[tt_timezone]">
                        <?php
                        // wp_timezone_choice returns escaped <option> markup.
                        echo wp_timezone_choice( $tz_current, get_user_locale() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core helper returns escaped option list.
                        ?>
                    </select>
                </div>

                <div class="tt-field" style="margin-top:var(--tt-sp-3);">
                    <label class="tt-field-label" for="tt-cfg-locale"><?php esc_html_e( 'Locale', 'talenttrack' ); ?></label>
                    <select id="tt-cfg-locale" class="tt-input" name="config[tt_locale]">
                        <?php foreach ( $locales as $loc ) : ?>
                            <option value="<?php echo esc_attr( (string) $loc ); ?>" <?php selected( $locale_current, $loc ); ?>><?php echo esc_html( (string) $loc ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="tt-field-hint" style="margin-top:6px;">
                        <?php esc_html_e( 'Default language for date and number formatting. Only installed languages are listed.', 'talenttrack' ); ?>
                    </p>
                </div>

                <?php
                // #2456 — club-wide navigation layout. `classic` is the
                // default and stays a complete rollback; individuals can
                // pin either shell for themselves in My settings, so this
                // is the default they inherit rather than a hard setting.
                $shell_current = \TT\Shared\Frontend\ShellPreference::clubDefault();
                ?>
                <div class="tt-field tt-field--section-top">
                    <h4 class="tt-field-subhead"><?php esc_html_e( 'Navigation layout', 'talenttrack' ); ?></h4>
                    <label class="tt-field-label" for="tt-cfg-frontend-shell"><?php esc_html_e( 'Academy default', 'talenttrack' ); ?></label>
                    <select id="tt-cfg-frontend-shell" class="tt-input" name="config[tt_frontend_shell]">
                        <?php foreach ( \TT\Shared\Frontend\ShellPreference::labels() as $shell_value => $shell_label ) : ?>
                            <option value="<?php echo esc_attr( $shell_value ); ?>" <?php selected( $shell_current, $shell_value ); ?>><?php echo esc_html( $shell_label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="tt-field-hint">
                        <?php esc_html_e( 'The app shell keeps a navigation sidebar on screen on a laptop and a slide-out menu on a phone. Classic returns to the tile overview to switch sections. Everyone can pick their own layout in My settings; this is what they get until they do.', 'talenttrack' ); ?>
                    </p>
                </div>

                <?php
                // #2934 — how the live-match screen is laid out. Same shape
                // as the navigation layout above: an academy default that
                // individuals can override in My settings, with `classic`
                // the ship value so nobody is moved mid-season.
                $mexec_layout = \TT\Modules\MatchExecution\MatchExecutionLayout::clubDefault();
                ?>
                <div class="tt-field tt-field--section-top">
                    <h4 class="tt-field-subhead"><?php esc_html_e( 'Match day', 'talenttrack' ); ?></h4>
                    <label class="tt-field-label" for="tt-cfg-match-execution-layout"><?php esc_html_e( 'Live match screen', 'talenttrack' ); ?></label>
                    <select id="tt-cfg-match-execution-layout" class="tt-input" name="config[<?php echo esc_attr( \TT\Modules\MatchExecution\MatchExecutionLayout::CONFIG_KEY ); ?>]">
                        <?php foreach ( \TT\Modules\MatchExecution\MatchExecutionLayout::labels() as $layout_value => $layout_label ) : ?>
                            <option value="<?php echo esc_attr( $layout_value ); ?>" <?php selected( $mexec_layout, $layout_value ); ?>><?php echo esc_html( $layout_label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="tt-field-hint">
                        <?php esc_html_e( 'Classic puts everything on one long page. Sections keeps the score and the clock fixed at the top and puts a row of tabs within thumb reach at the bottom, so the bench is one tap away instead of three scrolls. Coaches can pick their own in My settings; this is what they get until they do.', 'talenttrack' ); ?>
                    </p>
                </div>

                <?php // #1488 — attendance at-risk flag threshold. ?>
                <div class="tt-field" style="margin-top:var(--tt-sp-3);">
                    <label class="tt-field-label" for="tt-cfg-attendance-threshold"><?php esc_html_e( 'Attendance at-risk threshold', 'talenttrack' ); ?></label>
                    <input type="number" inputmode="numeric" id="tt-cfg-attendance-threshold" class="tt-input"
                        name="config[<?php echo esc_attr( \TT\Modules\Analytics\Domain\AttendanceFlagService::CONFIG_KEY ); ?>]"
                        min="1" max="50" step="1"
                        value="<?php echo esc_attr( (string) \TT\Modules\Analytics\Domain\AttendanceFlagService::threshold() ); ?>" />
                    <p class="tt-field-hint" style="margin-top:6px;">
                        <?php esc_html_e( 'How many missed activities (absent, excused or injured) flag a player as at risk. Used by the player attendance report, the attendance leaderboard, and the daily attendance-flag notification — they all read this one number. Default 3.', 'talenttrack' ); ?>
                    </p>
                </div>

                <?php // #2666 (epic #2589) — how long a departed player's media is kept. ?>
                <div class="tt-field">
                    <label class="tt-field-label" for="tt-cfg-media-retention"><?php esc_html_e( 'Keep media after a player leaves', 'talenttrack' ); ?></label>
                    <select id="tt-cfg-media-retention" class="tt-input"
                        name="config[<?php echo esc_attr( \TT\Modules\Media\Retention\MediaRetentionService::CONFIG_KEY ); ?>]">
                        <?php
                        $tt_retention_now = \TT\Modules\Media\Retention\MediaRetentionService::years();
                        foreach ( [ 0, 1, 2, 3, 5, 7, 10 ] as $tt_years ) :
                            $tt_label = $tt_years === 0
                                ? __( 'Keep indefinitely', 'talenttrack' )
                                : sprintf(
                                    /* translators: %d is a number of years. */
                                    _n( '%d year after they leave', '%d years after they leave', $tt_years, 'talenttrack' ),
                                    $tt_years
                                );
                            ?>
                            <option value="<?php echo esc_attr( (string) $tt_years ); ?>" <?php selected( $tt_retention_now, $tt_years ); ?>><?php echo esc_html( $tt_label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="tt-field-hint">
                        <?php esc_html_e( 'Photos and video of a player who has left are listed for review once this period has passed — they are never deleted automatically. Review them under Media retention. This does not affect players who are still at the academy, however old their photos are.', 'talenttrack' ); ?>
                    </p>
                </div>

                <?php // #1994 — academy-wide opt-out for the install-on-mobile banner. ?>
                <div class="tt-field">
                    <input type="hidden" name="config[install_banner.enabled]" value="0" />
                    <label>
                        <input type="checkbox" name="config[install_banner.enabled]" value="1" <?php checked( QueryHelpers::get_config( 'install_banner.enabled', '1' ), '1' ); ?> />
                        <?php esc_html_e( 'Show the install-on-mobile prompt', 'talenttrack' ); ?>
                    </label>
                    <p class="tt-field-hint">
                        <?php esc_html_e( 'When on, players and parents see a banner inviting them to install TalentTrack on their phone. Switch it off to hide that prompt for everyone in your academy.', 'talenttrack' ); ?>
                    </p>
                </div>

                <?php
                // #2157 — academy-managed email sender identity. Applied to
                // every plugin email (account notifications + Comms) via the
                // wp_mail_from / wp_mail_from_name filters. Blank = inherit
                // the WordPress default sender.
                $email_from_name    = (string) QueryHelpers::get_config( 'comms_email_from_name', '' );
                $email_from_address = (string) QueryHelpers::get_config( 'comms_email_from_address', '' );
                ?>
                <div class="tt-field tt-field--section-top">
                    <h3 class="tt-field-subhead"><?php esc_html_e( 'Email sender', 'talenttrack' ); ?></h3>
                    <p class="tt-field-hint">
                        <?php esc_html_e( 'The name and address plugin emails are sent from — account invitations, notifications and other messages. Leave both blank to use the WordPress default sender for your site.', 'talenttrack' ); ?>
                    </p>
                </div>
                <div class="tt-field">
                    <label class="tt-field-label" for="tt-cfg-email-from-name"><?php esc_html_e( 'Sender name', 'talenttrack' ); ?></label>
                    <input type="text" id="tt-cfg-email-from-name" class="tt-input"
                        name="config[comms_email_from_name]"
                        autocomplete="organization"
                        value="<?php echo esc_attr( $email_from_name ); ?>"
                        placeholder="<?php esc_attr_e( 'e.g. Ajax Academy', 'talenttrack' ); ?>" />
                </div>
                <div class="tt-field">
                    <label class="tt-field-label" for="tt-cfg-email-from-address"><?php esc_html_e( 'Sender address', 'talenttrack' ); ?></label>
                    <input type="email" inputmode="email" id="tt-cfg-email-from-address" class="tt-input"
                        name="config[comms_email_from_address]"
                        autocomplete="email"
                        value="<?php echo esc_attr( $email_from_address ); ?>"
                        placeholder="<?php esc_attr_e( 'e.g. noreply@academy.example', 'talenttrack' ); ?>" />
                    <p class="tt-field-hint">
                        <?php esc_html_e( 'Must be a valid email address. An invalid or empty address falls back to the WordPress default — your email is never sent with a broken sender.', 'talenttrack' ); ?>
                    </p>
                </div>
            </div>
            <div class="tt-form-actions" style="margin-top:16px;">
                <?php echo FormSaveButton::render( [ 'label' => __( 'Save', 'talenttrack' ) ] ); ?>
            </div>
            <div class="tt-form-msg"></div>
        </form>
        <script>
        (function(){
            var samples = <?php echo wp_json_encode( $preset_samples ); ?>;
            var sel = document.getElementById('tt-cfg-date-format');
            var out = document.getElementById('tt-cfg-date-preview');
            if (sel && out) {
                sel.addEventListener('change', function(){
                    if (Object.prototype.hasOwnProperty.call(samples, sel.value)) out.textContent = samples[sel.value];
                });
            }
        })();
        </script>
        <?php
        self::renderConfigJs( false );
    }

    private static function renderRatingForm(): void {
        ?>
        <form id="tt-config-form" data-tt-config-form="1" data-tt-config-sub="rating">
            <div class="tt-panel">
                <div class="tt-grid tt-grid-3">
                    <div class="tt-field">
                        <label class="tt-field-label" for="tt-cfg-rating-min"><?php esc_html_e( 'Min', 'talenttrack' ); ?></label>
                        <input type="number" inputmode="decimal" id="tt-cfg-rating-min" class="tt-input" name="config[rating_min]" min="0" max="10" step="0.5" value="<?php echo esc_attr( QueryHelpers::get_config( 'rating_min', '5' ) ); ?>" />
                    </div>
                    <div class="tt-field">
                        <label class="tt-field-label" for="tt-cfg-rating-max"><?php esc_html_e( 'Max', 'talenttrack' ); ?></label>
                        <input type="number" inputmode="decimal" id="tt-cfg-rating-max" class="tt-input" name="config[rating_max]" min="1" max="20" step="0.5" value="<?php echo esc_attr( QueryHelpers::get_config( 'rating_max', '10' ) ); ?>" />
                    </div>
                    <div class="tt-field">
                        <label class="tt-field-label" for="tt-cfg-rating-step"><?php esc_html_e( 'Step', 'talenttrack' ); ?></label>
                        <input type="number" inputmode="decimal" id="tt-cfg-rating-step" class="tt-input" name="config[rating_step]" min="0.1" max="1" step="0.1" value="<?php echo esc_attr( QueryHelpers::get_config( 'rating_step', '0.5' ) ); ?>" />
                    </div>
                </div>
                <?php // #1384 — opt-in player-visible team rank. ?>
                <div class="tt-field" style="margin-top:var(--tt-sp-3);">
                    <input type="hidden" name="config[tt_player_visible_rank]" value="0" />
                    <label>
                        <input type="checkbox" name="config[tt_player_visible_rank]" value="1" <?php checked( QueryHelpers::get_config( 'tt_player_visible_rank', '0' ), '1' ); ?> />
                        <?php esc_html_e( 'Show each player their team rank', 'talenttrack' ); ?>
                    </label>
                    <p class="tt-field-hint" style="margin-top:6px;">
                        <?php esc_html_e( 'When off (default), players see a personal growth trend on My team instead of a "#N of M" rank. Turn on only if your academy wants players to see their numeric standing. Staff always see ranks; no other teammate\'s rank is ever shown to a player.', 'talenttrack' ); ?>
                    </p>
                </div>
            </div>
            <div class="tt-form-actions" style="margin-top:16px;">
                <?php echo FormSaveButton::render( [ 'label' => __( 'Save', 'talenttrack' ) ] ); ?>
            </div>
            <div class="tt-form-msg"></div>
        </form>
        <?php
        self::renderConfigJs( false );
    }

    /**
     * #1727 — central per-age-category default match minutes.
     *
     * One row per `age_group` lookup value, each with a "minutes per
     * half (N)" numeric input and a live "= 2 x N total" readout. The
     * per-row values are assembled client-side into a single JSON map
     * and saved under the `match_minutes_by_age_group` config key via
     * `POST /v1/config` (the standard config-form handler).
     *
     * Save-only — settings sub-form (CLAUDE.md §6a exemption).
     */
    private static function renderMatchMinutesForm(): void {
        wp_enqueue_style(
            'tt-frontend-match-minutes',
            TT_PLUGIN_URL . 'assets/css/frontend-match-minutes.css',
            [],
            TT_VERSION
        );

        $age_groups = QueryHelpers::get_lookup_names( 'age_group' );
        $current    = ( new \TT\Modules\MatchPrep\Services\MatchLengthResolver() )->configuredMap();
        $fallback   = \TT\Modules\MatchPrep\Services\MatchLengthResolver::FALLBACK_HALF_MINUTES;
        ?>
        <p class="tt-mm-intro">
            <?php echo esc_html( sprintf(
                /* translators: %d is the global fallback minutes per half. */
                __( 'Set the default match length for each age category, in minutes per half. The full match is twice that (2 x N). Leave a row blank to use the global fallback of %d minutes per half.', 'talenttrack' ),
                (int) $fallback
            ) ); ?>
        </p>

        <form id="tt-config-form" data-tt-config-form="1" data-tt-config-sub="match-minutes" data-tt-match-minutes-form>
            <input type="hidden" name="config[match_minutes_by_age_group]" value="" data-tt-match-minutes-json />
            <div class="tt-panel">
                <?php if ( empty( $age_groups ) ) : ?>
                    <?php
                    $lookups_url = add_query_arg(
                        [ 'config_sub' => 'lookups', 'category' => 'age_groups' ],
                        remove_query_arg( [ 'config_sub', 'category' ] )
                    );
                    ?>
                    <p class="tt-notice">
                        <?php esc_html_e( 'No age categories configured yet. Add age groups first, then come back to set their default match minutes.', 'talenttrack' ); ?>
                        <a href="<?php echo esc_url( $lookups_url ); ?>"><?php esc_html_e( 'Manage age groups', 'talenttrack' ); ?></a>
                    </p>
                <?php else : ?>
                    <div class="tt-mm-list" role="group" aria-label="<?php esc_attr_e( 'Default match minutes per age category', 'talenttrack' ); ?>">
                        <div class="tt-mm-head" aria-hidden="true">
                            <span class="tt-mm-head-group"><?php esc_html_e( 'Age category', 'talenttrack' ); ?></span>
                            <span class="tt-mm-head-half"><?php esc_html_e( 'Minutes per half (N)', 'talenttrack' ); ?></span>
                            <span class="tt-mm-head-total"><?php esc_html_e( 'Total (2 x N)', 'talenttrack' ); ?></span>
                        </div>
                        <?php foreach ( $age_groups as $i => $group ) :
                            $group = (string) $group;
                            $value = isset( $current[ $group ] ) ? (int) $current[ $group ] : 0;
                            $field_id = 'tt-mm-' . sanitize_html_class( (string) $i );
                            $total = $value > 0 ? $value * 2 : 0;
                        ?>
                            <div class="tt-mm-row" data-tt-mm-row>
                                <label class="tt-mm-group" for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $group ); ?></label>
                                <input
                                    type="number"
                                    inputmode="numeric"
                                    min="0"
                                    max="60"
                                    step="1"
                                    id="<?php echo esc_attr( $field_id ); ?>"
                                    class="tt-input tt-mm-input"
                                    data-tt-mm-half
                                    data-tt-mm-group="<?php echo esc_attr( $group ); ?>"
                                    value="<?php echo $value > 0 ? esc_attr( (string) $value ) : ''; ?>"
                                    placeholder="<?php echo esc_attr( (string) $fallback ); ?>"
                                    aria-describedby="<?php echo esc_attr( $field_id ); ?>-total"
                                />
                                <output
                                    class="tt-mm-total"
                                    id="<?php echo esc_attr( $field_id ); ?>-total"
                                    for="<?php echo esc_attr( $field_id ); ?>"
                                    data-tt-mm-total
                                ><?php echo $total > 0 ? esc_html( (string) $total ) : esc_html( '—' ); // em dash placeholder ?></output>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php
            // #2835 — the minimum share of the available minutes every player
            // should reach, used by the Minutes share report. It lives on this
            // form because it is read in the same terms as the match lengths
            // above: those set the denominator, this sets the line drawn
            // across it.
            $share_target = \TT\Modules\Analytics\Reports\MinutesShareQuery::targetPct();
            ?>
            <div class="tt-panel">
                <label class="tt-field" for="tt-minutes-share-target">
                    <span class="tt-field-label"><?php esc_html_e( 'Minimum share of playing time', 'talenttrack' ); ?></span>
                    <input
                        type="number"
                        inputmode="numeric"
                        min="0"
                        max="100"
                        step="1"
                        id="tt-minutes-share-target"
                        class="tt-input"
                        name="config[minutes_share_target_pct]"
                        value="<?php echo esc_attr( (string) $share_target ); ?>"
                        aria-describedby="tt-minutes-share-target-help"
                    />
                    <span class="tt-field-help" id="tt-minutes-share-target-help">
                        <?php echo esc_html( sprintf(
                            /* translators: %d is the default minimum share percentage. */
                            __( 'The percentage of a team\'s played minutes every player should reach. The Minutes share report flags anyone below it. Default %d%%.', 'talenttrack' ),
                            (int) \TT\Modules\Analytics\Reports\MinutesShareQuery::DEFAULT_TARGET_PCT
                        ) ); ?>
                    </span>
                </label>
            </div>

            <div class="tt-form-actions tt-mm-actions">
                <?php echo FormSaveButton::render( [ 'label' => __( 'Save', 'talenttrack' ) ] ); ?>
            </div>
            <div class="tt-form-msg"></div>
        </form>
        <?php
        self::renderMatchMinutesJs();
        self::renderConfigJs( false );
    }

    /**
     * #1727 — keep the hidden JSON field + per-row totals in sync with
     * the per-age-category inputs, so the standard config-form submit
     * handler ships a single `match_minutes_by_age_group` JSON value.
     */
    private static function renderMatchMinutesJs(): void {
        $em_dash = '—';
        ?>
        <script>
        (function(){
            var form = document.querySelector('[data-tt-match-minutes-form]');
            if (!form) return;
            var hidden = form.querySelector('[data-tt-match-minutes-json]');
            var rows   = form.querySelectorAll('[data-tt-mm-row]');

            function sync(){
                var map = {};
                rows.forEach(function(row){
                    var input = row.querySelector('[data-tt-mm-half]');
                    var out   = row.querySelector('[data-tt-mm-total]');
                    if (!input) return;
                    var group = input.getAttribute('data-tt-mm-group') || '';
                    var n = parseInt(input.value, 10);
                    if (!isNaN(n) && n > 0 && group) {
                        map[group] = n;
                        if (out) out.textContent = String(n * 2);
                    } else if (out) {
                        out.textContent = '<?php echo $em_dash; ?>';
                    }
                });
                if (hidden) hidden.value = JSON.stringify(map);
            }

            form.addEventListener('input', function(e){
                if (e.target && e.target.hasAttribute('data-tt-mm-half')) sync();
            });
            sync();
        })();
        </script>
        <?php
    }

    /**
     * #3044 — the default football form per age category.
     *
     * One select per `age_group` lookup value, offering the forms this
     * academy plays. The per-row values are assembled by
     * `assets/js/components/football-form-defaults.js` into a single JSON map
     * and saved under `football_form_by_age_group` via `POST /v1/config` —
     * the same shape the match-minutes form uses, because the config handler
     * stores one scalar per key.
     *
     * A team may still override its own form on the team record; this is only
     * what a new team is pre-filled with.
     *
     * Save-only — settings sub-form (CLAUDE.md §6a exemption).
     */
    private static function renderFootballFormForm(): void {
        wp_enqueue_style(
            'tt-frontend-football-form',
            TT_PLUGIN_URL . 'assets/css/frontend-football-form.css',
            [],
            TT_VERSION
        );
        wp_enqueue_script(
            'tt-football-form-defaults',
            TT_PLUGIN_URL . 'assets/js/components/football-form-defaults.js',
            [],
            TT_VERSION,
            true
        );

        $age_groups = QueryHelpers::get_lookup_names( 'age_group' );
        $forms      = \TT\Modules\Teams\FootballFormResolver::forms();
        $current    = \TT\Modules\Teams\FootballFormResolver::configuredMap();
        ?>
        <p class="tt-ff-intro">
            <?php esc_html_e( 'How many a side does each age category play? A new team is pre-filled with its age category\'s form, and any team can be set differently on the team itself. The form decides which formations the team blueprint offers.', 'talenttrack' ); ?>
        </p>

        <form id="tt-config-form" data-tt-config-form="1" data-tt-config-sub="football-form" data-tt-ff-form>
            <input type="hidden" name="config[football_form_by_age_group]" value="" data-tt-ff-json />
            <div class="tt-panel">
                <?php if ( empty( $forms ) ) : ?>
                    <p class="tt-notice">
                        <?php esc_html_e( 'No football forms configured yet. Add them under Lookups, then come back to set a default per age category.', 'talenttrack' ); ?>
                    </p>
                <?php elseif ( empty( $age_groups ) ) : ?>
                    <p class="tt-notice">
                        <?php esc_html_e( 'No age categories configured yet. Add age groups first, then come back to set their football form.', 'talenttrack' ); ?>
                    </p>
                <?php else : ?>
                    <div class="tt-ff-list" role="group" aria-label="<?php esc_attr_e( 'Default football form per age category', 'talenttrack' ); ?>">
                        <div class="tt-ff-head" aria-hidden="true">
                            <span><?php esc_html_e( 'Age category', 'talenttrack' ); ?></span>
                            <span><?php esc_html_e( 'Football form', 'talenttrack' ); ?></span>
                        </div>
                        <?php foreach ( $age_groups as $i => $group ) :
                            $group    = (string) $group;
                            $value    = (string) ( $current[ $group ] ?? \TT\Modules\Teams\FootballFormResolver::bandFor( $group ) );
                            $field_id = 'tt-ff-' . sanitize_html_class( (string) $i );
                        ?>
                            <div class="tt-ff-row">
                                <label class="tt-ff-group" for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $group ); ?></label>
                                <select
                                    id="<?php echo esc_attr( $field_id ); ?>"
                                    class="tt-input tt-ff-input"
                                    data-tt-ff-select
                                    data-tt-ff-group="<?php echo esc_attr( $group ); ?>"
                                >
                                    <?php foreach ( $forms as $form ) : ?>
                                        <option value="<?php echo esc_attr( (string) $form ); ?>" <?php selected( $value, (string) $form ); ?>>
                                            <?php echo esc_html( \TT\Infrastructure\Query\LookupTranslator::byTypeAndName( 'football_form', (string) $form ) ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="tt-form-actions tt-ff-actions">
                <?php echo FormSaveButton::render( [ 'label' => __( 'Save', 'talenttrack' ) ] ); ?>
            </div>
            <div class="tt-form-msg"></div>
        </form>
        <?php
        self::renderConfigJs( false );
    }

    private static function renderMenusForm(): void {
        ?>
        <form id="tt-config-form" data-tt-config-form="1" data-tt-config-sub="menus">
            <div class="tt-panel">
                <p style="margin:0 0 var(--tt-sp-3); color:var(--tt-muted);">
                    <?php esc_html_e( 'TalentTrack admin tools moved to the frontend in v3.12.0. The legacy wp-admin menu entries (Players, Teams, Configuration, …) are hidden by default. Direct URLs to those pages still work as an emergency fallback.', 'talenttrack' ); ?>
                </p>
                <div class="tt-field">
                    <input type="hidden" name="config[show_legacy_menus]" value="0" />
                    <label>
                        <input type="checkbox" name="config[show_legacy_menus]" value="1" <?php checked( QueryHelpers::get_config( 'show_legacy_menus', '0' ), '1' ); ?> />
                        <?php esc_html_e( 'Show legacy wp-admin menus', 'talenttrack' ); ?>
                    </label>
                    <p class="tt-field-hint" style="margin-top:6px;">
                        <?php esc_html_e( 'Re-expose the legacy menu entries in wp-admin for users who prefer them. Plugin still works on both surfaces; this just controls menu visibility.', 'talenttrack' ); ?>
                    </p>
                </div>
            </div>
            <div class="tt-form-actions" style="margin-top:16px;">
                <?php echo FormSaveButton::render( [ 'label' => __( 'Save', 'talenttrack' ) ] ); ?>
            </div>
            <div class="tt-form-msg"></div>
        </form>
        <?php
        self::renderConfigJs( false );
    }

    /**
     * #2540 — download the academy's current configuration as a JSON file:
     * every setting, plus which modules and features are on or off.
     *
     * Posts to `admin-post.php?action=tt_export` with the `config_json`
     * exporter key — the same pipeline the Exports page uses, so the cap
     * gate, nonce check, audit entry and streaming are shared rather than
     * re-implemented here. admin-post.php also bypasses the theme, which
     * matters: rendering chrome before the download would corrupt it.
     *
     * Compose-only. What goes in the file (and what gets redacted) is
     * decided by ConfigSnapshotService, per CLAUDE.md §4.
     */
    private static function exportModuleEnabled(): bool {
        return \TT\Core\ModuleRegistry::isEnabled( 'TT\\Modules\\Export\\ExportModule' );
    }

    private static function renderExportForm(): void {
        // Deep links survive the tile being hidden, so re-check here
        // rather than rendering a button that posts into a 400.
        if ( ! current_user_can( 'tt_edit_settings' ) ) {
            echo '<p class="tt-notice">' . esc_html__( 'You do not have permission to export the configuration.', 'talenttrack' ) . '</p>';
            return;
        }
        if ( ! self::exportModuleEnabled() ) {
            echo '<p class="tt-notice">' . esc_html__( 'The Export module is switched off for this academy. Turn it on under Modules to export the configuration.', 'talenttrack' ) . '</p>';
            return;
        }

        $modules  = \TT\Core\ModuleRegistry::allWithState();
        $features = \TT\Core\FeatureRegistry::allWithState();

        $modules_on  = count( array_filter( $modules, static fn( $m ) => ! empty( $m['enabled'] ) ) );
        $features_on = count( array_filter( $features, static fn( $f ) => ! empty( $f['enabled'] ) ) );

        $return_url = add_query_arg(
            [ 'tt_view' => 'configuration', 'config_sub' => 'export' ],
            \TT\Shared\Wizards\WizardEntryPoint::dashboardBaseUrl()
        );
        ?>
        <div class="tt-panel">
            <p class="tt-field-hint">
                <?php esc_html_e( 'Download this academy\'s configuration as a JSON file: every setting, plus which modules and features are switched on or off. Useful for checking what is actually available here before writing training material, and for setting up a second academy the same way.', 'talenttrack' ); ?>
            </p>
            <p class="tt-field-hint">
                <?php
                printf(
                    /* translators: 1: enabled module count, 2: total module count, 3: enabled feature count, 4: total feature count. */
                    esc_html__( 'Right now %1$d of %2$d modules and %3$d of %4$d features are enabled.', 'talenttrack' ),
                    (int) $modules_on,
                    count( $modules ),
                    (int) $features_on,
                    count( $features )
                );
                ?>
            </p>
            <p class="tt-field-hint">
                <?php esc_html_e( 'Integration credentials (Strava, Spond, translation engines) are replaced with "[redacted]" — the setting name is listed so you can see it is configured, but the value never leaves the server. The file contains no player data.', 'talenttrack' ); ?>
            </p>
            <form class="tt-export-form" method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'tt_export', '_tt_export_nonce' ); ?>
                <input type="hidden" name="action" value="tt_export" />
                <input type="hidden" name="tt_export_key" value="config_json" />
                <input type="hidden" name="format" value="json" />
                <input type="hidden" name="tt_export_return_url" value="<?php echo esc_attr( $return_url ); ?>" />
                <div class="tt-form-actions">
                    <button type="submit" class="tt-btn tt-btn-primary">
                        <?php esc_html_e( 'Download JSON', 'talenttrack' ); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * #2207 — academy-wide visibility of the player-profile "Profile" tab
     * cards. One checkbox per hideable card (checked = shown); the Identity
     * card is always-on and not listed. The checked/unchecked state is
     * assembled client-side into a single `profile_cards_hidden` JSON array
     * (the UNchecked cards) and saved via `POST /v1/config`, mirroring the
     * match-minutes hidden-field pattern.
     *
     * Save-only — settings sub-form (CLAUDE.md §6a exemption). Which-card
     * logic lives in ProfileCardsConfig, not here.
     */
    /**
     * #2603 / #3112 — the Messages settings screen: what this academy
     * sends, and how each message is allowed to reach people.
     *
     * Built from the SWITCHABLE registered templates rather than a
     * hardcoded list, so a template registered by a later release shows
     * up here with no change to this view. #3110 keeps account mail (the
     * invitation email) out of that set, so it is absent rather than
     * shown ticked and greyed — a disabled checkbox invites "why can't I
     * change this"; absence does not raise the question at all.
     *
     * #3112 — the copy that says what each message is, who receives it
     * and when it fires comes from `TemplateGuide`, NOT from this file:
     * the setup-wizard step (#3113) shows the same messages in the same
     * families, and two surfaces describing the same eighteen things in
     * two sets of words is how they drift apart.
     *
     * Two controls per message, deliberately separate:
     *   - the message's own switch — does this go at all;
     *   - which channels it may use — how it is allowed to travel.
     * A template with one supported channel gets no channel control,
     * only a line of text; a checkbox that cannot be unticked is noise.
     *
     * Wizard plan: exemption — settings toggle list, single-field changes
     * per row (CLAUDE.md §3 exemption (a) analogue, ruled on #2603).
     * §6(a) likewise exempts it from Cancel + Save.
     *
     * Mirrors the profile-cards form (#2207): the checkboxes are synced
     * into hidden JSON fields so the standard config-form submit handler
     * ships one value per key. Both stored sets are the NEGATIVE ones —
     * the templates switched off, and the channels ruled out — so that an
     * empty value means "nothing changed" and anything shipped in a later
     * release lands in a defined state. See `TemplateSwitch` and
     * `TemplateChannels` for why that direction.
     */
    private static function renderMessagesForm(): void {
        wp_enqueue_style(
            'tt-frontend-messages-config',
            TT_PLUGIN_URL . 'assets/css/frontend-messages-config.css',
            [],
            TT_VERSION
        );

        $templates = \TT\Modules\Comms\Template\TemplateSwitch::switchableTemplates();
        $disabled  = \TT\Modules\Comms\Template\TemplateSwitch::disabledKeys();
        $blocked   = \TT\Modules\Comms\Template\TemplateChannels::blocked();
        $families  = \TT\Modules\Comms\Template\TemplateGuide::families();
        $grouped   = \TT\Modules\Comms\Template\TemplateGuide::grouped( $templates );
        ?>
        <form id="tt-config-form" data-tt-config-form="1" data-tt-config-sub="messages" data-tt-messages-form>
            <input type="hidden" name="config[comms_templates_disabled]" value="" data-tt-messages-json />
            <input type="hidden" name="config[comms_template_channels_blocked]" value="" data-tt-messages-channels-json />
            <div class="tt-panel">
                <p class="tt-field-hint">
                    <?php esc_html_e( 'What your academy sends, and how. Switching a message off stops it reaching anyone; it is still written to the message log, so you can see it would have gone out.', 'talenttrack' ); ?>
                </p>

                <?php if ( empty( $templates ) ) : ?>
                    <p class="tt-notice"><?php esc_html_e( 'No message templates are registered.', 'talenttrack' ); ?></p>
                <?php else : ?>
                    <?php foreach ( $grouped as $family_key => $group ) :
                        $family = $families[ $family_key ] ?? null;
                        if ( $family === null ) continue;
                        $group_id = 'tt-msg-family-' . sanitize_html_class( (string) $family_key );
                    ?>
                        <section class="tt-messages-group" aria-labelledby="<?php echo esc_attr( $group_id ); ?>">
                            <h3 class="tt-messages-group-title" id="<?php echo esc_attr( $group_id ); ?>">
                                <?php echo esc_html( $family['label'] ); ?>
                                <?php if ( ! empty( $family['recommended'] ) ) : ?>
                                    <span class="tt-messages-recommended"><?php esc_html_e( 'Recommended', 'talenttrack' ); ?></span>
                                <?php endif; ?>
                            </h3>
                            <p class="tt-messages-group-blurb"><?php echo esc_html( $family['blurb'] ); ?></p>

                            <ul class="tt-messages-list">
                                <?php foreach ( $group as $key => $template ) :
                                    $key       = (string) $key;
                                    $entry     = \TT\Modules\Comms\Template\TemplateGuide::forKey( $key );
                                    $is_off    = in_array( $key, $disabled, true );
                                    $field_id  = 'tt-msg-tpl-' . sanitize_html_class( $key );
                                    $channels  = array_values( array_map( 'strval', $template->supportedChannels() ) );
                                    $off_keys  = $blocked[ $key ] ?? [];
                                ?>
                                    <li class="tt-messages-row">
                                        <div class="tt-messages-head">
                                            <label class="tt-messages-toggle" for="<?php echo esc_attr( $field_id ); ?>">
                                                <input
                                                    type="checkbox"
                                                    id="<?php echo esc_attr( $field_id ); ?>"
                                                    data-tt-message-template="<?php echo esc_attr( $key ); ?>"
                                                    value="1"
                                                    <?php checked( ! $is_off ); ?>
                                                />
                                                <span class="tt-messages-name"><?php echo esc_html( $template->label() ); ?></span>
                                            </label>
                                            <?php if ( $entry !== null && empty( $entry['triggered'] ) ) : ?>
                                                <span class="tt-messages-badge" title="<?php esc_attr_e( 'Nothing in TalentTrack sends this yet. Leaving it on changes nothing until it is wired up.', 'talenttrack' ); ?>">
                                                    <?php esc_html_e( 'Not sent automatically yet', 'talenttrack' ); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <?php if ( $entry !== null ) : ?>
                                            <p class="tt-messages-what"><?php echo esc_html( $entry['what'] ); ?></p>
                                            <dl class="tt-messages-meta">
                                                <dt><?php esc_html_e( 'Who gets it', 'talenttrack' ); ?></dt>
                                                <dd><?php echo esc_html( $entry['who'] ); ?></dd>
                                                <dt><?php esc_html_e( 'When', 'talenttrack' ); ?></dt>
                                                <dd><?php echo esc_html( $entry['when'] ); ?></dd>
                                            </dl>
                                        <?php endif; ?>

                                        <?php if ( count( $channels ) > 1 ) : ?>
                                            <fieldset class="tt-messages-channels">
                                                <legend><?php esc_html_e( 'Ways it may reach them', 'talenttrack' ); ?></legend>
                                                <p class="tt-messages-channels-hint">
                                                    <?php esc_html_e( 'One of these per person, in this order — whichever reaches them first. Untick a way you do not want used.', 'talenttrack' ); ?>
                                                </p>
                                                <div class="tt-messages-channel-set">
                                                    <?php foreach ( $channels as $channel ) :
                                                        $channel_id = $field_id . '-ch-' . sanitize_html_class( $channel );
                                                    ?>
                                                        <label class="tt-messages-channel" for="<?php echo esc_attr( $channel_id ); ?>">
                                                            <input
                                                                type="checkbox"
                                                                id="<?php echo esc_attr( $channel_id ); ?>"
                                                                data-tt-message-channel="<?php echo esc_attr( $channel ); ?>"
                                                                data-tt-message-channel-of="<?php echo esc_attr( $key ); ?>"
                                                                value="1"
                                                                <?php checked( ! in_array( $channel, $off_keys, true ) ); ?>
                                                            />
                                                            <span><?php echo esc_html( \TT\Modules\Comms\Template\TemplateGuide::channelLabel( $channel ) ); ?></span>
                                                        </label>
                                                    <?php endforeach; ?>
                                                </div>
                                            </fieldset>
                                        <?php elseif ( $channels !== [] ) : ?>
                                            <p class="tt-messages-single-channel">
                                                <?php
                                                printf(
                                                    /* translators: %s: channel name, e.g. Email */
                                                    esc_html__( 'Sent by: %s', 'talenttrack' ),
                                                    esc_html( \TT\Modules\Comms\Template\TemplateGuide::channelLabel( $channels[0] ) )
                                                );
                                                ?>
                                            </p>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </section>
                    <?php endforeach; ?>

                    <p class="tt-field-hint tt-messages-footnote">
                        <?php esc_html_e( 'To stop a message completely, switch the message off rather than unticking every way it can travel — a message with nowhere to go is recorded as a failure, not as your decision.', 'talenttrack' ); ?>
                    </p>
                <?php endif; ?>
            </div>
            <div class="tt-form-actions">
                <?php echo FormSaveButton::render( [ 'label' => __( 'Save messages', 'talenttrack' ) ] ); ?>
            </div>
            <div class="tt-form-msg"></div>
        </form>
        <?php
        self::renderMessagesJs();
        self::renderConfigJs( false );
    }

    /**
     * #2603 / #3112 — keep the two hidden JSON fields in sync with the
     * checkboxes: `comms_templates_disabled` from the UNticked messages,
     * and `comms_template_channels_blocked` from the UNticked channels of
     * each message. The standard config-form submit handler then ships
     * one value per key.
     *
     * Enqueued rather than inlined (CLAUDE.md §2). Everything the script
     * needs is already in `data-` attributes, so it needs no localised
     * config and can be deferred.
     */
    private static function renderMessagesJs(): void {
        wp_enqueue_script(
            'tt-frontend-messages-config',
            TT_PLUGIN_URL . 'assets/js/frontend-messages-config.js',
            [],
            TT_VERSION,
            true
        );
    }

    private static function renderProfileCardsForm(): void {
        wp_enqueue_style(
            'tt-frontend-profile-cards-config',
            TT_PLUGIN_URL . 'assets/css/frontend-profile-cards-config.css',
            [],
            TT_VERSION
        );

        $labels = \TT\Modules\Players\Services\ProfileCardsConfig::hideableLabels();
        $hidden = \TT\Modules\Players\Services\ProfileCardsConfig::hidden();
        ?>
        <form id="tt-config-form" data-tt-config-form="1" data-tt-config-sub="profile-cards" data-tt-profile-cards-form>
            <input type="hidden" name="config[profile_cards_hidden]" value="" data-tt-profile-cards-json />
            <div class="tt-panel">
                <p class="tt-field-hint">
                    <?php esc_html_e( 'Choose which cards the player-profile Profile tab shows for the whole academy. Uncheck a card to hide it everywhere. The Identity card (name, date of birth, position) always shows and cannot be hidden.', 'talenttrack' ); ?>
                </p>
                <div class="tt-profile-cards-list" role="group" aria-label="<?php esc_attr_e( 'Visible profile cards', 'talenttrack' ); ?>">
                    <?php foreach ( $labels as $key => $label ) :
                        $key       = (string) $key;
                        $is_hidden = in_array( $key, $hidden, true );
                        $field_id  = 'tt-profile-card-' . sanitize_html_class( $key );
                    ?>
                        <label class="tt-profile-card-row" for="<?php echo esc_attr( $field_id ); ?>">
                            <input
                                type="checkbox"
                                id="<?php echo esc_attr( $field_id ); ?>"
                                data-tt-profile-card="<?php echo esc_attr( $key ); ?>"
                                value="1"
                                <?php checked( ! $is_hidden ); ?>
                            />
                            <span><?php echo esc_html( $label ); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="tt-form-actions tt-profile-cards-actions">
                <?php echo FormSaveButton::render( [ 'label' => __( 'Save', 'talenttrack' ) ] ); ?>
            </div>
            <div class="tt-form-msg"></div>
        </form>
        <?php
        self::renderProfileCardsJs();
        self::renderConfigJs( false );
    }

    /**
     * #2207 — keep the hidden `profile_cards_hidden` JSON field in sync
     * with the per-card checkboxes (the UNchecked cards are the hidden
     * set), so the standard config-form submit handler ships a single
     * JSON value.
     */
    private static function renderProfileCardsJs(): void {
        ?>
        <script>
        (function(){
            var form = document.querySelector('[data-tt-profile-cards-form]');
            if (!form) return;
            var hidden = form.querySelector('[data-tt-profile-cards-json]');
            var boxes  = form.querySelectorAll('[data-tt-profile-card]');

            function sync(){
                var hiddenCards = [];
                boxes.forEach(function(box){
                    if (!box.checked) hiddenCards.push(box.getAttribute('data-tt-profile-card'));
                });
                hidden.value = JSON.stringify(hiddenCards);
            }

            boxes.forEach(function(box){ box.addEventListener('change', sync); });
            sync();
        })();
        </script>
        <?php
    }

    /**
     * v3.110.191 — academy-configurable PDP cycle blocks per season.
     * Form scaffolding for `frontend-pdp-blocks.js` to hydrate.
     */
    private static function renderPdpBlocksForm(): void {
        $seasons_repo = new \TT\Modules\Pdp\Repositories\SeasonsRepository();
        $blocks_repo  = new \TT\Modules\Pdp\Repositories\PdpBlocksRepository();

        $all_seasons = $seasons_repo->all();
        if ( empty( $all_seasons ) ) {
            $seasons_url = add_query_arg( [ 'tt_view' => 'seasons' ], remove_query_arg( [ 'tt_view', 'config_sub' ] ) );
            echo '<p class="tt-notice">'
                . esc_html__( 'No seasons configured yet. Add a season under Configuration → Seasons first, then come back here to set its PDP blocks.', 'talenttrack' )
                . ' <a href="' . esc_url( $seasons_url ) . '">' . esc_html__( 'Manage seasons', 'talenttrack' ) . '</a>'
                . '</p>';
            return;
        }

        $current = $seasons_repo->current();
        $current_id = $current ? (int) $current->id : (int) $all_seasons[0]->id;

        $payload = [ 'seasons' => [] ];
        foreach ( $all_seasons as $s ) {
            $payload['seasons'][] = [
                'id'         => (int) $s->id,
                'name'       => (string) $s->name,
                'start_date' => (string) $s->start_date,
                'end_date'   => (string) $s->end_date,
                'is_current' => (int) $s->is_current === 1,
                'blocks'     => $blocks_repo->listForSeason( (int) $s->id ),
            ];
        }
        ?>
        <p style="color:var(--tt-muted, #5b6e75); margin: 0 0 var(--tt-sp-3, 16px);">
            <?php esc_html_e( "Configure the dates of each PDP cycle block for a season. Coaches opening a new PDP file pick how many blocks (2, 3 or 4); the file's conversation windows use the dates set here.", 'talenttrack' ); ?>
        </p>

        <form id="tt-pdp-blocks-form" class="tt-pdp-blocks" novalidate>
            <div class="tt-panel">
                <div class="tt-grid tt-grid-2">
                    <div class="tt-field">
                        <label class="tt-field-label" for="tt-pdp-blocks-season"><?php esc_html_e( 'Season', 'talenttrack' ); ?></label>
                        <select id="tt-pdp-blocks-season" class="tt-input" data-tt-pdp-blocks-season>
                            <?php foreach ( $all_seasons as $s ) : ?>
                                <option value="<?php echo (int) $s->id; ?>" <?php selected( $current_id, (int) $s->id ); ?>>
                                    <?php echo esc_html( (string) $s->name );
                                    if ( (int) $s->is_current === 1 ) {
                                        echo ' — ' . esc_html__( 'current', 'talenttrack' );
                                    } ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="tt-field">
                        <label class="tt-field-label"><?php esc_html_e( 'Number of blocks', 'talenttrack' ); ?></label>
                        <div class="tt-pdp-blocks-size" role="radiogroup" aria-label="<?php esc_attr_e( 'Number of blocks in the cycle', 'talenttrack' ); ?>">
                            <?php foreach ( [ 2, 3, 4 ] as $n ) : ?>
                                <label class="tt-pdp-blocks-size-option">
                                    <input type="radio" name="tt-pdp-blocks-size" value="<?php echo (int) $n; ?>" data-tt-pdp-blocks-size />
                                    <span><?php echo (int) $n; ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tt-panel">
                <h3 style="margin: 0 0 var(--tt-sp-2, 12px); font-size: 0.95rem;"><?php esc_html_e( 'Block dates', 'talenttrack' ); ?></h3>
                <div class="tt-pdp-blocks-rows" data-tt-pdp-blocks-rows></div>
            </div>

            <div class="tt-panel">
                <h3 style="margin: 0 0 var(--tt-sp-2, 12px); font-size: 0.95rem;"><?php esc_html_e( 'Year timeline', 'talenttrack' ); ?></h3>
                <div class="tt-pdp-blocks-timeline" data-tt-pdp-blocks-timeline></div>
                <div class="tt-pdp-blocks-axis" data-tt-pdp-blocks-axis aria-hidden="true"></div>
            </div>

            <div class="tt-pdp-blocks-messages" data-tt-pdp-blocks-messages role="status" aria-live="polite"></div>

            <div class="tt-form-actions" style="margin-top: var(--tt-sp-3, 16px);">
                <button type="submit" class="tt-btn tt-btn-primary" data-tt-pdp-blocks-save>
                    <?php esc_html_e( 'Save', 'talenttrack' ); ?>
                </button>
                <span class="tt-form-msg" data-tt-pdp-blocks-msg></span>
            </div>
        </form>

        <script type="application/json" data-tt-pdp-blocks-payload>
            <?php echo wp_json_encode( $payload ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — JSON in script type=application/json is safe ?>
        </script>
        <?php
        wp_enqueue_script(
            'tt-pdp-blocks-config',
            TT_PLUGIN_URL . 'assets/js/frontend-pdp-blocks.js',
            [],
            TT_VERSION,
            true
        );
        wp_localize_script( 'tt-pdp-blocks-config', 'TT_PDP_BLOCKS', [
            'rest_root' => esc_url_raw( rest_url( 'talenttrack/v1' ) ),
            'nonce'     => wp_create_nonce( 'wp_rest' ),
            'i18n'      => [
                /* translators: %d = block number, 1-indexed */
                'block_label'        => __( 'Block %d', 'talenttrack' ),
                'from'               => __( 'From', 'talenttrack' ),
                'to'                 => __( 'To', 'talenttrack' ),
                'saving'             => __( 'Saving…', 'talenttrack' ),
                'saved'              => __( 'Blocks saved.', 'talenttrack' ),
                'save_failed'        => __( 'Could not save. Try again.', 'talenttrack' ),
                /* translators: 1: block number, 2: season start, 3: season end */
                'err_outside_season' => __( 'Block %1$d extends outside the season window (%2$s – %3$s).', 'talenttrack' ),
                /* translators: 1: block A number, 2: block B number */
                'err_overlap'        => __( 'Block %1$d overlaps with block %2$d.', 'talenttrack' ),
                /* translators: 1: gap start date, 2: gap end date */
                'err_gap'            => __( '%1$s to %2$s is not covered by any block.', 'talenttrack' ),
                /* translators: %d = block number */
                'err_end_before'     => __( 'Block %d ends before it starts.', 'talenttrack' ),
                'msg_no_issues'      => __( 'All dates inside the season; no overlaps; no gaps.', 'talenttrack' ),
            ],
        ] );
        wp_enqueue_style(
            'tt-pdp-blocks-config',
            TT_PLUGIN_URL . 'assets/css/frontend-pdp-blocks.css',
            [],
            TT_VERSION
        );
    }

    /**
     * #3305 (epic #3301) — the prompts a coach prepares a PDP conversation
     * against, one set per conversation template.
     *
     * Save model: this is a settings sub-form, so the §6 Save + Cancel
     * contract's exemption (a) applies — each row commits on its own and
     * "leaving without saving" is just navigating away. There is no record
     * being edited to cancel out of.
     *
     * The scaffolding is server-rendered and `frontend-pdp-prep-questions.js`
     * hydrates it from the JSON payload below, talking to
     * `/pdp-prep-questions` for every change.
     */
    private static function renderPdpPrepQuestionsForm(): void {
        $repo    = new \TT\Modules\Pdp\Prep\PdpPrepQuestionsRepository();
        $labels  = \TT\Domain\Vocabularies\Enums\PdpConversationTemplate::labelled();
        $payload = [
            'templates' => $repo->listAll(),
            'labels'    => $labels,
            'types'     => \TT\Modules\Pdp\Prep\PdpPrepQuestionsRepository::allowedFieldTypes(),
        ];
        ?>
        <p class="tt-pdp-prep-config__intro">
            <?php esc_html_e( 'What a coach is asked to think about before each PDP conversation. The start of a season asks different things than the end, so each conversation in the cycle has its own set.', 'talenttrack' ); ?>
        </p>
        <p class="tt-pdp-prep-config__intro">
            <?php esc_html_e( 'A coach\'s answers are private to them and the head of academy — never shown to the player or their parents. Rewording a question that has already been answered keeps the old wording on those answers, so a prep from last season still reads the way it was written.', 'talenttrack' ); ?>
        </p>

        <div class="tt-pdp-prep-config" data-tt-prep-questions>
            <?php foreach ( $labels as $key => $label ) : ?>
                <section class="tt-pdp-prep-config__template" data-tt-prep-template="<?php echo esc_attr( $key ); ?>">
                    <h3 class="tt-pdp-prep-config__heading"><?php echo esc_html( $label ); ?></h3>
                    <ol class="tt-pdp-prep-config__list" data-tt-prep-list></ol>
                    <div class="tt-pdp-prep-config__actions">
                        <button type="button" class="tt-btn tt-btn-secondary" data-tt-prep-add>
                            <?php esc_html_e( 'Add question', 'talenttrack' ); ?>
                        </button>
                    </div>
                </section>
            <?php endforeach; ?>
            <p class="tt-form-msg" data-tt-prep-msg role="status" aria-live="polite"></p>
        </div>

        <script type="application/json" data-tt-prep-payload>
            <?php echo wp_json_encode( $payload ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — JSON in script type=application/json is safe ?>
        </script>
        <?php
        wp_enqueue_style(
            'tt-pdp-prep-questions',
            TT_PLUGIN_URL . 'assets/css/frontend-pdp-prep-questions.css',
            [],
            TT_VERSION
        );
        wp_enqueue_script(
            'tt-pdp-prep-questions',
            TT_PLUGIN_URL . 'assets/js/frontend-pdp-prep-questions.js',
            [],
            TT_VERSION,
            true
        );
        wp_localize_script( 'tt-pdp-prep-questions', 'TT_PDP_PREP', [
            'rest_root' => esc_url_raw( rest_url( 'talenttrack/v1' ) ),
            'nonce'     => wp_create_nonce( 'wp_rest' ),
            'i18n'      => [
                'label'          => __( 'Question', 'talenttrack' ),
                'help_text'      => __( 'Help text', 'talenttrack' ),
                'field_type'     => __( 'Answer type', 'talenttrack' ),
                'options'        => __( 'Choices (one per line)', 'talenttrack' ),
                'required'       => __( 'Answer required', 'talenttrack' ),
                'save'           => __( 'Save question', 'talenttrack' ),
                'cancel'         => __( 'Cancel', 'talenttrack' ),
                'edit'           => __( 'Edit', 'talenttrack' ),
                'remove'         => __( 'Remove', 'talenttrack' ),
                'move_up'        => __( 'Move up', 'talenttrack' ),
                'move_down'      => __( 'Move down', 'talenttrack' ),
                'empty'          => __( 'No questions yet for this conversation.', 'talenttrack' ),
                'saving'         => __( 'Saving…', 'talenttrack' ),
                'saved'          => __( 'Saved.', 'talenttrack' ),
                'save_failed'    => __( 'Could not save. Try again.', 'talenttrack' ),
                'confirm_remove' => __( 'Remove this question? Answers already given to it stay readable on the preps that hold them.', 'talenttrack' ),
                'versioned'      => __( 'Saved as a new version — preps already answered keep the old wording.', 'talenttrack' ),
                'type_textarea'  => __( 'Long text', 'talenttrack' ),
                'type_text'      => __( 'Short text', 'talenttrack' ),
                'type_select'    => __( 'Choose one', 'talenttrack' ),
                'type_multi'     => __( 'Choose several', 'talenttrack' ),
                'type_checkbox'  => __( 'Yes / no', 'talenttrack' ),
                'type_number'    => __( 'Number', 'talenttrack' ),
                'type_date'      => __( 'Date', 'talenttrack' ),
            ],
        ] );
    }

    private static function renderDashboardForm(): void {
        $current = QueryHelpers::get_config( 'persona_dashboard.enabled', '1' );
        $is_persona = $current !== '0';

        // #0069 — per-persona override map. Empty string = inherit from
        // global default. '1' = force persona dashboard. '0' = force
        // classic tile grid. Used for testing one persona at a time
        // without flipping the whole site.
        $personas = [
            'academy_admin'       => __( 'Academy admin',           'talenttrack' ),
            'head_of_development' => __( 'Head of Development',     'talenttrack' ),
            'head_coach'          => __( 'Head coach',              'talenttrack' ),
            'assistant_coach'     => __( 'Assistant coach',         'talenttrack' ),
            'team_manager'        => __( 'Team manager',            'talenttrack' ),
            'scout'               => __( 'Scout',                   'talenttrack' ),
            'player'              => __( 'Player',                  'talenttrack' ),
            'parent'              => __( 'Parent',                  'talenttrack' ),
            'readonly_observer'   => __( 'Read-only observer',      'talenttrack' ),
        ];
        $per_persona = [];
        foreach ( $personas as $key => $_label ) {
            $per_persona[ $key ] = (string) QueryHelpers::get_config( 'persona_dashboard.' . $key . '.enabled', '' );
        }
        ?>
        <form id="tt-config-form" data-tt-config-form="1" data-tt-config-sub="dashboard">
            <div class="tt-panel">
                <p style="margin:0 0 var(--tt-sp-3); color:var(--tt-muted);">
                    <?php esc_html_e( 'Choose what every user sees when they open the dashboard. The persona dashboard is the configurable per-role landing built in #0060; the classic tile grid is the legacy menu of all available views.', 'talenttrack' ); ?>
                </p>

                <div class="tt-field">
                    <label style="display:block; margin-bottom:8px;">
                        <input type="radio" name="config[persona_dashboard.enabled]" value="1" <?php checked( $is_persona, true ); ?> />
                        <strong><?php esc_html_e( 'Persona dashboard (recommended)', 'talenttrack' ); ?></strong>
                    </label>
                    <p class="tt-field-hint" style="margin:0 0 var(--tt-sp-3) 24px;">
                        <?php esc_html_e( 'Each user lands on a layout tailored to their persona (player, parent, coach, head of development, club admin, scout, observer). Layouts are edited under wp-admin → Configuration → Dashboard layouts.', 'talenttrack' ); ?>
                    </p>

                    <label style="display:block; margin-bottom:8px;">
                        <input type="radio" name="config[persona_dashboard.enabled]" value="0" <?php checked( $is_persona, false ); ?> />
                        <strong><?php esc_html_e( 'Classic tile grid', 'talenttrack' ); ?></strong>
                    </label>
                    <p class="tt-field-hint" style="margin:0 0 0 24px;">
                        <?php esc_html_e( 'Falls back to the original tile grid (every user sees the same menu of tiles, filtered by capability). Use this if a customer is not yet ready to roll out the persona dashboard or hits a blocker.', 'talenttrack' ); ?>
                    </p>
                </div>
            </div>

            <div class="tt-panel" style="margin-top: 16px;">
                <h3 style="margin: 0 0 var(--tt-sp-2);">
                    <?php esc_html_e( 'Per-persona overrides', 'talenttrack' ); ?>
                </h3>
                <p style="margin:0 0 var(--tt-sp-3); color:var(--tt-muted); font-size: 13px;">
                    <?php esc_html_e( "Optional. Force a specific dashboard for a single persona — useful for testing one persona at a time on a real install without flipping the whole site. \"Inherit\" follows the global default above.", 'talenttrack' ); ?>
                </p>
                <div class="tt-table-wrap">
                <table class="tt-table" style="width:100%; max-width: 520px;">
                    <thead>
                        <tr>
                            <th style="text-align:left;"><?php esc_html_e( 'Persona', 'talenttrack' ); ?></th>
                            <th style="text-align:left; width:200px;"><?php esc_html_e( 'Dashboard', 'talenttrack' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $personas as $key => $label ) :
                            $cur = $per_persona[ $key ];
                            $name = 'config[persona_dashboard.' . $key . '.enabled]';
                            ?>
                            <tr>
                                <td><?php echo esc_html( $label ); ?></td>
                                <td>
                                    <select name="<?php echo esc_attr( $name ); ?>" class="tt-input">
                                        <option value=""  <?php selected( $cur, '' );  ?>><?php esc_html_e( 'Inherit (use global default)', 'talenttrack' ); ?></option>
                                        <option value="1" <?php selected( $cur, '1' ); ?>><?php esc_html_e( 'Persona dashboard', 'talenttrack' ); ?></option>
                                        <option value="0" <?php selected( $cur, '0' ); ?>><?php esc_html_e( 'Classic tile grid', 'talenttrack' ); ?></option>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>

            <div class="tt-form-actions" style="margin-top:16px;">
                <?php echo FormSaveButton::render( [ 'label' => __( 'Save', 'talenttrack' ) ] ); ?>
            </div>
            <div class="tt-form-msg"></div>
        </form>
        <?php
        self::renderConfigJs( false );
    }

    private static function renderConfigJs( bool $with_logo ): void {
        ?>
        <script>
        (function(){
            <?php if ( $with_logo ) : ?>
            // v3.92.5 — was guarding the entire block on `wp.media` being
            // ready at script-execution time. Inline `<script>` runs at
            // parse time, but media-views.js (which defines wp.media) loads
            // via wp_enqueue_media() with no `dom_loaded` ordering guarantee.
            // On Dutch installs the operator reported the Choose logo button
            // simply did nothing — wp.media wasn't ready yet so the click
            // listener never got registered. Moved the readiness check
            // INSIDE the click handler so the button always responds, and
            // the user gets a console hint if media-views genuinely failed
            // to load.
            var frame;
            var pickBtn  = document.getElementById('tt-cfg-logo-pick');
            var clearBtn = document.getElementById('tt-cfg-logo-clear');
            var hidden   = document.getElementById('tt-cfg-logo-url');
            var preview  = document.getElementById('tt-cfg-logo-preview');
            if (pickBtn) pickBtn.addEventListener('click', function(){
                if (typeof wp === 'undefined' || !wp.media) {
                    if (window.console) console.warn('TalentTrack: wp.media not loaded — Choose logo button can\'t open the picker.');
                    return;
                }
                if (!frame) {
                    frame = wp.media({
                        title: '<?php echo esc_js( __( 'Select logo', 'talenttrack' ) ); ?>',
                        button: { text: '<?php echo esc_js( __( 'Use', 'talenttrack' ) ); ?>' },
                        library: { type: 'image' },
                        multiple: false
                    });
                    frame.on('select', function(){
                        var att = frame.state().get('selection').first().toJSON();
                        hidden.value = att.url;
                        preview.innerHTML = '<img src="' + att.url + '" alt="" style="max-height:80px; border-radius:6px; border:1px solid var(--tt-line);" />';
                    });
                }
                frame.open();
            });
            if (clearBtn) clearBtn.addEventListener('click', function(){
                hidden.value = '';
                preview.innerHTML = '';
            });
            <?php endif; ?>

            var form = document.getElementById('tt-config-form');
            if (!form) return;
            form.addEventListener('submit', function(e){
                e.preventDefault();
                var btn = form.querySelector('.tt-save-btn');
                var i18n = (window.TT && window.TT.i18n) || {};
                var rest = window.TT || {};
                if (btn) btn.setAttribute('data-state', 'saving');

                var fd = new FormData(form);
                var config = {};
                fd.forEach(function(value, key){
                    var m = /^config\[(.+)\]$/.exec(key);
                    if (m) config[m[1]] = value;
                });
                if (form.dataset.ttConfigSub === 'menus' && (config.show_legacy_menus === undefined || config.show_legacy_menus === '')) config.show_legacy_menus = '0';

                var url = (rest.rest_url || '/wp-json/talenttrack/v1/').replace(/\/+$/, '/') + 'config';
                var headers = { 'Accept': 'application/json', 'Content-Type': 'application/json' };
                if (rest.rest_nonce) headers['X-WP-Nonce'] = rest.rest_nonce;
                fetch(url, { method: 'POST', credentials: 'same-origin', headers: headers, body: JSON.stringify({ config: config }) })
                    .then(function(res){ return res.json().then(function(json){ return { ok: res.ok, json: json }; }); })
                    .then(function(r){
                        var msg = form.querySelector('.tt-form-msg');
                        if (r.ok && r.json && r.json.success) {
                            if (btn) btn.setAttribute('data-state', 'saved');
                            if (msg) { msg.classList.add('tt-success'); msg.textContent = i18n.saved || 'Saved.'; }
                            setTimeout(function(){ if (btn) btn.setAttribute('data-state', 'idle'); }, 1500);
                        } else {
                            if (btn) btn.setAttribute('data-state', 'error');
                            var errMsg = (r.json && r.json.errors && r.json.errors[0] && r.json.errors[0].message) || i18n.error_generic || 'Error.';
                            if (msg) { msg.classList.add('tt-error'); msg.textContent = errMsg; }
                            setTimeout(function(){ if (btn) btn.setAttribute('data-state', 'idle'); }, 2500);
                        }
                    })
                    .catch(function(){
                        if (btn) btn.setAttribute('data-state', 'error');
                        var msg = form.querySelector('.tt-form-msg');
                        if (msg) { msg.classList.add('tt-error'); msg.textContent = (i18n.network_error || 'Network error.'); }
                        setTimeout(function(){ if (btn) btn.setAttribute('data-state', 'idle'); }, 2500);
                    });
            });
        })();
        </script>
        <?php
    }
}
