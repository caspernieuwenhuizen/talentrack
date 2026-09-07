<?php
namespace TT\Modules\Vct\Frontend;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Infrastructure\Query\LookupTranslator;
use TT\Infrastructure\Query\QueryHelpers;
use TT\Infrastructure\Security\AuthorizationService;
use TT\Modules\Exercises\ExercisesRepository;
use TT\Modules\Pdp\Repositories\SeasonsRepository;
use TT\Modules\Vct\Repositories\VctAgeProfilesRepository;
use TT\Modules\Vct\Repositories\VctCycleWeekOverridesRepository;
use TT\Modules\Vct\Repositories\VctMacroBlocksRepository;
use TT\Modules\Vct\Repositories\VctTeamCyclesRepository;
use TT\Modules\Vct\Repositories\VctTeamSchedulesRepository;
use TT\Modules\Vct\Services\AgeProfileAdminService;
use TT\Modules\Vct\Validation\VctTeamCycleValidator;
use TT\Shared\Frontend\Components\FrontendBreadcrumbs;
use TT\Shared\Frontend\FrontendViewBase;

/**
 * FrontendVctConfigView (#0095 VCT-12 / #952, overhauled in #1546).
 *
 * Single "VCT configuration" tile at ?tt_view=vct-config with three
 * sub-tabs:
 *
 *   ?tab=blocks       — macro-block calendar editor (season periodization)
 *   ?tab=age-profiles — per-age intensity ceiling + envelope tuning
 *   ?tab=schedules    — per-team weekly training-day preferences
 *
 * Season + team are dropdowns (no raw ID typing); the season select
 * auto-loads on change. Macro-blocks are edited with a structured
 * label + date-range repeater (add / remove / reorder) that saves
 * through `PUT /vct/macro-blocks`, so the WordPress render and a
 * future SaaS front end share the same validated write path
 * (CLAUDE.md §4). Per-block phase profiles have an advanced JSON
 * fallback so the common case (label + dates) stays friendly.
 *
 * All three are settings sub-forms; Save+Cancel exempt per
 * CLAUDE.md §6 (a). Cap: tt_vct_admin_config (HoD/admin only).
 *
 * The age-profile + schedule forms POST back to the same view via the
 * standard shortcode dispatch; handlers call the repos directly.
 */
class FrontendVctConfigView extends FrontendViewBase {

    public static function render( int $user_id, bool $is_admin ): void {
        if ( ! AuthorizationService::userCanOrMatrix( $user_id, 'tt_vct_admin_config' ) && ! $is_admin ) {
            FrontendBreadcrumbs::fromDashboard( __( 'Not authorized', 'talenttrack' ) );
            self::renderHeader( __( 'VCT configuration', 'talenttrack' ) );
            echo '<p class="tt-notice">' . esc_html__( 'You do not have access to the VCT configuration tile.', 'talenttrack' ) . '</p>';
            return;
        }

        if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
            self::handlePost();
        }

        if ( defined( 'TT_PLUGIN_URL' ) && defined( 'TT_VERSION' ) ) {
            wp_enqueue_style( 'tt-vct-config', TT_PLUGIN_URL . 'assets/css/frontend-vct-config.css', [], TT_VERSION );
        }

        $tab = isset( $_GET['tab'] ) ? sanitize_key( (string) $_GET['tab'] ) : 'blocks';
        if ( ! in_array( $tab, [ 'blocks', 'age-profiles', 'schedules' ], true ) ) $tab = 'blocks';

        FrontendBreadcrumbs::fromDashboard( __( 'VCT configuration', 'talenttrack' ) );
        self::renderHeader( __( 'VCT configuration', 'talenttrack' ) );

        self::renderTabBar( $tab );

        switch ( $tab ) {
            case 'age-profiles': self::renderAgeProfilesTab(); break;
            case 'schedules':    self::renderSchedulesTab();    break;
            case 'blocks':
            default:             self::renderBlocksTab();       break;
        }
    }

    private static function renderTabBar( string $current ): void {
        $tabs = [
            'blocks'       => __( 'Macro-blocks',   'talenttrack' ),
            'age-profiles' => __( 'Age profiles',   'talenttrack' ),
            'schedules'    => __( 'Team schedules', 'talenttrack' ),
        ];
        echo '<nav class="tt-vct-config-tabs" aria-label="' . esc_attr__( 'VCT configuration sections', 'talenttrack' ) . '">';
        foreach ( $tabs as $slug => $label ) {
            $active = $slug === $current;
            $href = add_query_arg( [ 'tab' => $slug ] );
            echo '<a class="tt-vct-config-tab' . ( $active ? ' is-active' : '' ) . '"'
                . ( $active ? ' aria-current="page"' : '' )
                . ' href="' . esc_url( $href ) . '">' . esc_html( $label ) . '</a>';
        }
        echo '</nav>';
    }

    /**
     * Render a season `<select>` that submits its enclosing GET form on
     * change (auto-load). Defaults to the active season. Returns the
     * resolved season id (the requested one, the active one, or the
     * newest) and the season list so the caller can reuse it.
     *
     * @param object[] $seasons
     */
    private static function renderSeasonSelect( array $seasons, int $selected, string $label ): void {
        echo '<div class="tt-field">';
        echo '<label class="tt-field-label" for="tt-vct-season">' . esc_html( $label ) . '</label>';
        echo '<select id="tt-vct-season" class="tt-input" name="season_id" data-tt-vct-autoload>';
        foreach ( $seasons as $s ) {
            $is_current = (int) $s->is_current === 1;
            $name = (string) $s->name;
            if ( $is_current ) {
                $name .= ' — ' . __( 'current', 'talenttrack' );
            }
            echo '<option value="' . esc_attr( (string) (int) $s->id ) . '" ' . selected( $selected, (int) $s->id, false ) . '>'
                . esc_html( $name ) . '</option>';
        }
        echo '</select>';
        echo '</div>';
    }

    private static function noSeasonsNotice(): void {
        $seasons_url = add_query_arg( [ 'tt_view' => 'seasons' ], remove_query_arg( [ 'tt_view', 'tab' ] ) );
        echo '<p class="tt-notice">'
            . esc_html__( 'No seasons configured yet. Add a season under Configuration → Seasons first, then come back here.', 'talenttrack' )
            . ' <a href="' . esc_url( $seasons_url ) . '">' . esc_html__( 'Manage seasons', 'talenttrack' ) . '</a>'
            . '</p>';
    }

    // ── BLOCKS ───────────────────────────────────────────────────────

    private static function renderBlocksTab(): void {
        $seasons = ( new SeasonsRepository() )->all();
        if ( empty( $seasons ) ) {
            echo '<p>' . esc_html__( 'Define the macro-block calendar for a season. The club default applies to every team; pick a team to set an override.', 'talenttrack' ) . '</p>';
            self::noSeasonsNotice();
            return;
        }

        $current = ( new SeasonsRepository() )->current();
        $default_season = $current ? (int) $current->id : (int) $seasons[0]->id;
        $season_id = isset( $_GET['season_id'] ) ? absint( $_GET['season_id'] ) : $default_season;
        if ( $season_id <= 0 ) $season_id = $default_season;
        $team_id = isset( $_GET['team_id'] ) ? absint( $_GET['team_id'] ) : 0;

        $repo       = new VctMacroBlocksRepository();
        $references = $repo->listReferenceTemplates();
        $teams      = QueryHelpers::get_teams();

        echo '<p>' . esc_html__( 'Define the macro-block calendar for a season. The club default applies to every team; pick a team to set an override just for them.', 'talenttrack' ) . '</p>';

        // Season + team pickers — auto-load on change (no Load button).
        echo '<form method="GET" action="" class="tt-vct-picker">';
        echo '<input type="hidden" name="tt_view" value="vct-config">';
        echo '<input type="hidden" name="tab"     value="blocks">';
        self::renderSeasonSelect( $seasons, $season_id, __( 'Season', 'talenttrack' ) );

        echo '<div class="tt-field">';
        echo '<label class="tt-field-label" for="tt-vct-team">' . esc_html__( 'Team', 'talenttrack' ) . '</label>';
        echo '<select id="tt-vct-team" class="tt-input" name="team_id" data-tt-vct-autoload>';
        echo '<option value="0" ' . selected( $team_id, 0, false ) . '>' . esc_html__( 'Club default (all teams)', 'talenttrack' ) . '</option>';
        foreach ( $teams as $t ) {
            $tname = (string) $t->name;
            if ( ! empty( $t->age_group ) ) {
                $tname .= ' (' . (string) $t->age_group . ')';
            }
            echo '<option value="' . esc_attr( (string) (int) $t->id ) . '" ' . selected( $team_id, (int) $t->id, false ) . '>'
                . esc_html( $tname ) . '</option>';
        }
        echo '</select>';
        echo '</div>';
        // No-JS fallback so the pickers still load without the auto-submit script.
        echo '<noscript><button type="submit" class="tt-btn tt-btn-secondary">' . esc_html__( 'Load', 'talenttrack' ) . '</button></noscript>';
        echo '</form>';

        // Reference templates (read-only) in the shared table.
        if ( $references ) {
            echo '<h3 class="tt-vct-section-title">' . esc_html__( 'Reference phase profiles', 'talenttrack' ) . '</h3>';
            echo '<table class="tt-table"><thead><tr><th>' . esc_html__( 'Template', 'talenttrack' ) . '</th><th>' . esc_html__( 'Weeks', 'talenttrack' ) . '</th></tr></thead><tbody>';
            foreach ( $references as $r ) {
                $weeks = is_array( $r['phase_profile'] ) ? count( $r['phase_profile'] ) : 0;
                echo '<tr><td>' . esc_html( (string) $r['label'] ) . '</td><td>' . esc_html( (string) $weeks ) . '</td></tr>';
            }
            echo '</tbody></table>';
        }

        // Structured editor — hydrated + saved by frontend-vct-config.js.
        $blocks = $repo->listForSeason( $team_id, $season_id );
        // When viewing a team that has no own override, listForSeason
        // returns the club-default rows too (team_id DESC). For editing a
        // team override we only want its own rows; the club default is
        // edited via the "Club default" option. Filter to the picked scope.
        $own = [];
        foreach ( $blocks as $b ) {
            $own[] = [
                'sequence'      => (int) $b['sequence'],
                'label'         => (string) $b['label'],
                'start_date'    => (string) $b['start_date'],
                'end_date'      => (string) $b['end_date'],
                'phase_profile' => is_array( $b['phase_profile'] ) ? $b['phase_profile'] : [],
            ];
        }

        $payload = [
            'season_id' => $season_id,
            'team_id'   => $team_id,
            'blocks'    => $own,
        ];

        $scope_label = $team_id === 0
            ? __( 'Club default', 'talenttrack' )
            : self::teamName( $teams, $team_id );

        echo '<h3 class="tt-vct-section-title">' . esc_html( sprintf(
            /* translators: %s = the scope being edited (a team name or "Club default") */
            __( 'Macro-blocks — %s', 'talenttrack' ),
            $scope_label
        ) ) . '</h3>';

        echo '<form id="tt-vct-blocks-form" class="tt-vct-blocks" novalidate>';
        echo '<div class="tt-vct-blocks-rows" data-tt-vct-rows></div>';
        echo '<div class="tt-vct-blocks-actions">';
        echo '<button type="button" class="tt-btn tt-btn-secondary" data-tt-vct-add>' . esc_html__( 'Add block', 'talenttrack' ) . '</button>';
        echo '</div>';
        echo '<div class="tt-vct-blocks-messages" data-tt-vct-messages role="status" aria-live="polite"></div>';
        echo '<div class="tt-form-actions">';
        echo '<button type="submit" class="tt-btn tt-btn-primary" data-tt-vct-save>' . esc_html__( 'Save', 'talenttrack' ) . '</button>';
        echo '<span class="tt-form-msg" data-tt-vct-msg></span>';
        echo '</div>';
        echo '</form>';

        echo '<script type="application/json" data-tt-vct-blocks-payload>'
            . wp_json_encode( $payload ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — JSON in a script type=application/json block is safe
            . '</script>';

        self::enqueueBlocksEditor();
    }

    /** @param object[] $teams */
    private static function teamName( array $teams, int $team_id ): string {
        foreach ( $teams as $t ) {
            if ( (int) $t->id === $team_id ) {
                $name = (string) $t->name;
                if ( ! empty( $t->age_group ) ) {
                    $name .= ' (' . (string) $t->age_group . ')';
                }
                return $name;
            }
        }
        /* translators: %d = team id for a team no longer in the list */
        return sprintf( __( 'Team #%d', 'talenttrack' ), $team_id );
    }

    private static function enqueueBlocksEditor(): void {
        if ( ! defined( 'TT_PLUGIN_URL' ) || ! defined( 'TT_VERSION' ) ) return;

        // Season/team auto-load + structured block repeater.
        wp_enqueue_script( 'tt-vct-config', TT_PLUGIN_URL . 'assets/js/frontend-vct-config.js', [], TT_VERSION, true );

        // #2322 — the canonical speelwijze (tactical-theme) vocabulary, for
        // the optional per-week theme picker inside the advanced editor.
        // Same `vct_tactical_theme` keys the exercise catalogue uses.
        $themes = [];
        foreach ( QueryHelpers::get_lookup_names( 'vct_tactical_theme' ) as $name ) {
            $themes[] = [
                'key'   => (string) $name,
                'label' => LookupTranslator::byTypeAndName( 'vct_tactical_theme', (string) $name ),
            ];
        }

        wp_localize_script( 'tt-vct-config', 'TT_VCT_CONFIG', [
            'rest_root' => esc_url_raw( rest_url( 'talenttrack/v1' ) ),
            'nonce'     => wp_create_nonce( 'wp_rest' ),
            'themes'    => $themes,
            'i18n'      => [
                /* translators: %d = block number, 1-indexed */
                'block_label'    => __( 'Block %d', 'talenttrack' ),
                'name'           => __( 'Name', 'talenttrack' ),
                'from'           => __( 'From', 'talenttrack' ),
                'to'             => __( 'To', 'talenttrack' ),
                'remove'         => __( 'Remove', 'talenttrack' ),
                'move_up'        => __( 'Move up', 'talenttrack' ),
                'move_down'      => __( 'Move down', 'talenttrack' ),
                'advanced'       => __( 'Advanced: weekly phase profile (JSON)', 'talenttrack' ),
                'phase_hint'     => __( 'Optional. Array of { week, phase, multiplier, tactical_theme } objects. Leave blank for the default profile.', 'talenttrack' ),
                'themes_title'   => __( 'Speelwijze-thema per week', 'talenttrack' ),
                'themes_hint'    => __( 'Optionally tag each week with a playing-style theme. Weeks come from the phase profile above.', 'talenttrack' ),
                'theme_label'    => __( 'Speelwijze-thema', 'talenttrack' ),
                'theme_none'     => __( '— geen —', 'talenttrack' ),
                'week_label'     => __( 'Week %d', 'talenttrack' ),
                'no_weeks'       => __( 'Add weeks to the phase profile above to tag themes.', 'talenttrack' ),
                'name_ph'        => __( 'e.g. Build-up block', 'talenttrack' ),
                'saving'         => __( 'Saving…', 'talenttrack' ),
                'saved'          => __( 'Block set saved.', 'talenttrack' ),
                'save_failed'    => __( 'Could not save. Try again.', 'talenttrack' ),
                'empty'          => __( 'No macro-blocks yet. Add the first block to start the season calendar.', 'talenttrack' ),
                'need_one'       => __( 'Add at least one block before saving.', 'talenttrack' ),
                'bad_json'       => __( 'The advanced phase profile for block %d is not valid JSON.', 'talenttrack' ),
                /* translators: %d = block number */
                'err_no_name'    => __( 'Block %d needs a name.', 'talenttrack' ),
                /* translators: %d = block number */
                'err_no_dates'   => __( 'Block %d needs a start and end date.', 'talenttrack' ),
                /* translators: %d = block number */
                'err_end_before' => __( 'Block %d ends before it starts.', 'talenttrack' ),
                /* translators: 1: block A number, 2: block B number */
                'err_overlap'    => __( 'Block %1$d overlaps with block %2$d.', 'talenttrack' ),
                'msg_ok'         => __( 'Looks good — no overlaps, all dates valid.', 'talenttrack' ),
            ],
        ] );
    }

    // ── AGE PROFILES ─────────────────────────────────────────────────

    private static function renderAgeProfilesTab(): void {
        $profiles = ( new VctAgeProfilesRepository() )->listAll();
        if ( ! $profiles ) {
            echo '<div class="tt-notice tt-notice--info tt-vct-empty">';
            echo '<p><strong>' . esc_html__( 'No age profiles are set up yet.', 'talenttrack' ) . '</strong></p>';
            echo '<p>' . esc_html__( 'Age profiles cap how long and how intensely each team can train safely, by age group. Until they exist, the VCT planner can\'t build a training. Add the first one below — they\'re part of the standard VCT setup for your club.', 'talenttrack' ) . '</p>';
            echo '</div>';
            self::renderAddAgeProfileForm( [] );
            return;
        }

        echo '<p>' . esc_html__( 'Tune the per-age workload envelope. Coaches see the resulting ceiling on the wizard\'s Duration step + the engine enforces it everywhere.', 'talenttrack' ) . '</p>';

        foreach ( $profiles as $p ) {
            echo '<details class="tt-vct-accordion">';
            echo '<summary class="tt-vct-accordion-summary">';
            echo '<span class="tt-vct-accordion-title">' . esc_html( (string) $p['age_group'] ) . '</span>';
            echo '<span class="tt-vct-accordion-meta">' . esc_html( sprintf(
                /* translators: 1: minutes max, 2: intensity ceiling */
                __( '%1$d min · band %2$d', 'talenttrack' ),
                (int) $p['session_minutes_max'], (int) $p['intensity_band_max']
            ) ) . '</span>';
            echo '</summary>';
            echo '<form method="POST" action="" class="tt-vct-form tt-vct-form-grid">';
            wp_nonce_field( 'tt_vct_cfg_age_save_' . (int) $p['id'], '_tt_vct_cfg_nonce' );
            echo '<input type="hidden" name="_tt_action" value="save_age_profile">';
            echo '<input type="hidden" name="id"         value="' . esc_attr( (string) $p['id'] ) . '">';
            self::renderNumberInput( 'session_minutes_max',             __( 'Minutes per training (max)',      'talenttrack' ), (int) $p['session_minutes_max'],             30, 180 );
            /* translators: 1: lowest intensity band, 2: highest intensity band. */
            $band_max_label = sprintf( __( 'Intensity band max (%1$d-%2$d)', 'talenttrack' ), ExercisesRepository::INTENSITY_BAND_MIN, ExercisesRepository::INTENSITY_BAND_MAX );
            // The ceiling an age profile can carry is bounded by the scale
            // itself (#2767): a profile capping at 9 could never be met by
            // content that stops at 7, and the age-safe check silently never
            // fires for it.
            self::renderNumberInput( 'intensity_band_max',              $band_max_label,                                        (int) $p['intensity_band_max'],              ExercisesRepository::INTENSITY_BAND_MIN, ExercisesRepository::INTENSITY_BAND_MAX );
            self::renderNumberInput( 'min_recovery_hours_between_high', __( 'Min recovery hours between high', 'talenttrack' ), (int) $p['min_recovery_hours_between_high'], 12, 168 );
            self::renderNumberInput( 'growth_spurt_load_reduction_pct', __( 'PHV load reduction %',            'talenttrack' ), (int) $p['growth_spurt_load_reduction_pct'], 0, 50 );
            self::renderNumberInput( 'weekly_load_envelope',            __( 'Weekly load envelope',            'talenttrack' ), (int) $p['weekly_load_envelope'],            50, 10000 );
            echo '<div class="tt-field">';
            echo '<label class="tt-field-label" for="match_load_multiplier_per_minute_' . esc_attr( (string) $p['id'] ) . '">' . esc_html__( 'Match load multiplier per minute', 'talenttrack' ) . '</label>';
            echo '<input class="tt-input" id="match_load_multiplier_per_minute_' . esc_attr( (string) $p['id'] ) . '" type="number" inputmode="decimal" step="0.1" min="0" max="20" name="match_load_multiplier_per_minute" value="' . esc_attr( (string) $p['match_load_multiplier_per_minute'] ) . '">';
            echo '</div>';
            echo '<label class="tt-vct-check tt-vct-form-full"><input type="checkbox" name="md_logic_enabled" value="1" ' . checked( $p['md_logic_enabled'], true, false ) . '> '
                . esc_html__( 'MD logic enabled (off for U10/U11 per Appendix A)', 'talenttrack' )
                . '</label>';
            echo '<div class="tt-form-actions tt-vct-form-full">';
            echo '<button type="submit" class="tt-btn tt-btn-primary">' . esc_html__( 'Save', 'talenttrack' ) . '</button>';
            echo '</div>';
            echo '</form>';

            // Removal is its own form: a destructive action inside the
            // save form would submit on Enter from any number field.
            echo '<form method="POST" action="" class="tt-vct-form tt-vct-form-danger">';
            wp_nonce_field( 'tt_vct_cfg_age_delete_' . (int) $p['id'], '_tt_vct_cfg_nonce' );
            echo '<input type="hidden" name="_tt_action" value="delete_age_profile">';
            echo '<input type="hidden" name="id"         value="' . esc_attr( (string) $p['id'] ) . '">';
            echo '<button type="submit" class="tt-btn tt-btn-secondary">' . esc_html(
                sprintf(
                    /* translators: %s is an age group, e.g. U13. */
                    __( 'Remove %s profile', 'talenttrack' ),
                    (string) $p['age_group']
                )
            ) . '</button>';
            echo '<p class="tt-help">' . esc_html__( 'Teams in this age group stop getting drafted trainings. Trainings already planned are unaffected.', 'talenttrack' ) . '</p>';
            echo '</form>';
            echo '</details>';
        }

        self::renderAddAgeProfileForm( $profiles );
    }

    /**
     * #2601 — the add path. Until this existed, five profiles shipped
     * seeded and an academy fielding U15-U19 had a generator that refused
     * to draft for any of them, with nowhere to go.
     *
     * Nothing is pre-filled with a suggested ceiling. These numbers govern
     * how hard minors are worked, and a plausible-looking default is worse
     * than an empty field — it invites acceptance without a decision. The
     * seeded profiles above are visible on the same screen as the shape to
     * follow.
     *
     * @param list<array<string,mixed>> $existing
     */
    private static function renderAddAgeProfileForm( array $existing ): void {
        $covered = array_map( static fn( $p ) => (string) $p['age_group'], $existing );
        $options = [];
        foreach ( QueryHelpers::get_lookups( 'age_group' ) as $row ) {
            $name = (string) ( $row->name ?? '' );
            if ( $name === '' || in_array( $name, $covered, true ) ) continue;
            $options[ $name ] = LookupTranslator::name( $row );
        }

        echo '<details class="tt-vct-accordion tt-vct-accordion--add">';
        echo '<summary class="tt-vct-accordion-summary">';
        echo '<span class="tt-vct-accordion-title">' . esc_html__( 'Add an age profile', 'talenttrack' ) . '</span>';
        echo '</summary>';

        if ( ! $options ) {
            echo '<p class="tt-help">' . esc_html__( 'Every age group already has a profile.', 'talenttrack' ) . '</p>';
            echo '</details>';
            return;
        }

        echo '<p class="tt-help">' . esc_html__( 'Set the limits for an age group the planner cannot draft for yet. The training shape is copied from the closest age group that already has one; the limits below are yours.', 'talenttrack' ) . '</p>';

        echo '<form method="POST" action="" class="tt-vct-form tt-vct-form-grid">';
        wp_nonce_field( 'tt_vct_cfg_age_create', '_tt_vct_cfg_nonce' );
        echo '<input type="hidden" name="_tt_action" value="create_age_profile">';

        echo '<div class="tt-field">';
        echo '<label class="tt-field-label" for="tt_new_age_group">' . esc_html__( 'Age group', 'talenttrack' ) . '</label>';
        echo '<select class="tt-input" id="tt_new_age_group" name="age_group" required>';
        foreach ( $options as $value => $label ) {
            echo '<option value="' . esc_attr( $value ) . '">' . esc_html( $label ) . '</option>';
        }
        echo '</select>';
        echo '</div>';

        self::renderNumberInput( 'session_minutes_max', __( 'Minutes per training (max)', 'talenttrack' ), null, 30, 180, 'new' );
        /* translators: 1: lowest intensity band, 2: highest intensity band. */
        $band_max_label = sprintf( __( 'Intensity band max (%1$d-%2$d)', 'talenttrack' ), ExercisesRepository::INTENSITY_BAND_MIN, ExercisesRepository::INTENSITY_BAND_MAX );
        self::renderNumberInput( 'intensity_band_max',              $band_max_label,                                        null, ExercisesRepository::INTENSITY_BAND_MIN, ExercisesRepository::INTENSITY_BAND_MAX, 'new' );
        self::renderNumberInput( 'min_recovery_hours_between_high', __( 'Min recovery hours between high', 'talenttrack' ), 48,   12, 168,   'new' );
        self::renderNumberInput( 'growth_spurt_load_reduction_pct', __( 'PHV load reduction %',            'talenttrack' ), 20,   0,  50,    'new' );
        self::renderNumberInput( 'weekly_load_envelope',            __( 'Weekly load envelope',            'talenttrack' ), null, 50, 10000, 'new' );

        echo '<div class="tt-field">';
        echo '<label class="tt-field-label" for="tt_new_match_multiplier">' . esc_html__( 'Match load multiplier per minute', 'talenttrack' ) . '</label>';
        echo '<input class="tt-input" id="tt_new_match_multiplier" type="number" inputmode="decimal" step="0.1" min="0" max="20" name="match_load_multiplier_per_minute" value="7.0">';
        echo '</div>';

        echo '<label class="tt-vct-check tt-vct-form-full"><input type="checkbox" name="md_logic_enabled" value="1"> '
            . esc_html__( 'MD logic enabled (off for the youngest groups per Appendix A)', 'talenttrack' )
            . '</label>';

        echo '<div class="tt-form-actions tt-vct-form-full">';
        echo '<button type="submit" class="tt-btn tt-btn-primary">' . esc_html__( 'Add profile', 'talenttrack' ) . '</button>';
        echo '</div>';
        echo '</form>';
        echo '</details>';
    }

    // ── SCHEDULES ────────────────────────────────────────────────────

    private static function renderSchedulesTab(): void {
        $seasons = ( new SeasonsRepository() )->all();
        echo '<p>' . esc_html__( 'Set per-team weekly VCT training days. Drives the wizard\'s date-default to the next configured weekday.', 'talenttrack' ) . '</p>';

        if ( empty( $seasons ) ) {
            self::noSeasonsNotice();
            return;
        }

        $current = ( new SeasonsRepository() )->current();
        $default_season = $current ? (int) $current->id : (int) $seasons[0]->id;
        $season_id = isset( $_GET['season_id'] ) ? absint( $_GET['season_id'] ) : $default_season;
        if ( $season_id <= 0 ) $season_id = $default_season;

        echo '<form method="GET" action="" class="tt-vct-picker">';
        echo '<input type="hidden" name="tt_view" value="vct-config">';
        echo '<input type="hidden" name="tab"     value="schedules">';
        self::renderSeasonSelect( $seasons, $season_id, __( 'Season', 'talenttrack' ) );
        echo '<noscript><button type="submit" class="tt-btn tt-btn-secondary">' . esc_html__( 'Load', 'talenttrack' ) . '</button></noscript>';
        echo '</form>';

        if ( defined( 'TT_PLUGIN_URL' ) && defined( 'TT_VERSION' ) ) {
            // Only the auto-load handler is needed here (no block editor).
            wp_enqueue_script( 'tt-vct-config', TT_PLUGIN_URL . 'assets/js/frontend-vct-config.js', [], TT_VERSION, true );
        }

        $teams_repo = new VctTeamSchedulesRepository();
        $teams = QueryHelpers::get_teams();
        if ( ! $teams ) {
            echo '<p class="tt-empty">' . esc_html__( 'No teams yet. Create your teams under Teams first, then come back here to set their training days.', 'talenttrack' ) . '</p>';
            return;
        }

        $weekday_labels = [ 1 => __( 'Mon', 'talenttrack' ), 2 => __( 'Tue', 'talenttrack' ), 4 => __( 'Wed', 'talenttrack' ), 8 => __( 'Thu', 'talenttrack' ), 16 => __( 'Fri', 'talenttrack' ), 32 => __( 'Sat', 'talenttrack' ), 64 => __( 'Sun', 'talenttrack' ) ];

        foreach ( $teams as $t ) {
            $team_id = (int) $t->id;
            $row = $teams_repo->findForTeamSeason( $team_id, $season_id );
            $bitmask = $row !== null ? (int) $row['weekdays_bitmask'] : 0;
            $start   = $row !== null ? (string) ( $row['default_start_time'] ?? '' ) : '';
            $dur     = $row !== null && $row['default_duration_minutes'] !== null ? (string) $row['default_duration_minutes'] : '';

            echo '<details class="tt-vct-accordion">';
            echo '<summary class="tt-vct-accordion-summary">';
            echo '<span class="tt-vct-accordion-title">' . esc_html( (string) $t->name )
                . ( ! empty( $t->age_group ) ? ' (' . esc_html( (string) $t->age_group ) . ')' : '' )
                . '</span>';
            if ( $bitmask > 0 ) {
                echo '<span class="tt-vct-accordion-meta">' . esc_html( self::weekdaySummary( $weekday_labels, $bitmask ) ) . '</span>';
            }
            echo '</summary>';
            echo '<form method="POST" action="" class="tt-vct-form">';
            wp_nonce_field( 'tt_vct_cfg_schedule_save_' . $team_id . '_' . $season_id, '_tt_vct_cfg_nonce' );
            echo '<input type="hidden" name="_tt_action" value="save_schedule">';
            echo '<input type="hidden" name="team_id"    value="' . esc_attr( (string) $team_id ) . '">';
            echo '<input type="hidden" name="season_id"  value="' . esc_attr( (string) $season_id ) . '">';

            echo '<fieldset class="tt-vct-weekdays">';
            echo '<legend>' . esc_html__( 'Training days', 'talenttrack' ) . '</legend>';
            echo '<div class="tt-vct-weekday-row">';
            foreach ( $weekday_labels as $bit => $label ) {
                $checked = ( $bitmask & $bit ) === $bit ? 'checked' : '';
                echo '<label class="tt-vct-weekday"><input type="checkbox" name="weekday_bits[]" value="' . esc_attr( (string) $bit ) . '" ' . $checked . '> <span>' . esc_html( $label ) . '</span></label>';
            }
            echo '</div>';
            echo '</fieldset>';

            echo '<div class="tt-vct-form-grid">';
            echo '<div class="tt-field">';
            echo '<label class="tt-field-label">' . esc_html__( 'Default start time', 'talenttrack' ) . '</label>';
            echo '<input class="tt-input" type="time" name="default_start_time" value="' . esc_attr( $start ) . '">';
            echo '</div>';
            echo '<div class="tt-field">';
            echo '<label class="tt-field-label">' . esc_html__( 'Default duration (minutes)', 'talenttrack' ) . '</label>';
            echo '<input class="tt-input" type="number" inputmode="numeric" name="default_duration_minutes" min="20" max="180" step="5" value="' . esc_attr( $dur ) . '">';
            echo '</div>';
            echo '</div>';

            echo '<div class="tt-form-actions">';
            echo '<button type="submit" class="tt-btn tt-btn-primary">' . esc_html__( 'Save', 'talenttrack' ) . '</button>';
            echo '</div>';
            echo '</form>';

            self::renderCycleForm( $team_id, $season_id );

            echo '</details>';
        }
    }

    /**
     * The team's repeating cycle (#3360, epic #3354) — length, the Monday
     * it starts on, and the weekly shape it repeats.
     *
     * Its own form inside the team's accordion, so it saves independently
     * of the training days above it. Settings sub-form, so Save-only per
     * CLAUDE.md §6 (a) — there is no record being edited to cancel out of.
     */
    private static function renderCycleForm( int $team_id, int $season_id ): void {
        $cycle  = ( new VctTeamCyclesRepository() )->findForTeamSeason( $team_id, $season_id );
        $length = $cycle !== null ? (int) $cycle['cycle_weeks'] : VctTeamCyclesRepository::DEFAULT_WEEKS;
        $anchor = $cycle !== null ? (string) $cycle['anchor_date'] : self::defaultAnchorFor( $season_id );
        $chosen = $cycle !== null ? (int) ( $cycle['template_id'] ?? 0 ) : 0;

        echo '<form method="POST" action="" class="tt-vct-form tt-vct-cycle">';
        wp_nonce_field( 'tt_vct_cfg_cycle_save_' . $team_id . '_' . $season_id, '_tt_vct_cfg_nonce' );
        echo '<input type="hidden" name="_tt_action" value="save_cycle">';
        echo '<input type="hidden" name="team_id"    value="' . esc_attr( (string) $team_id ) . '">';
        echo '<input type="hidden" name="season_id"  value="' . esc_attr( (string) $season_id ) . '">';

        echo '<fieldset class="tt-vct-cycle-length">';
        echo '<legend>' . esc_html__( 'Cycle length', 'talenttrack' ) . '</legend>';
        echo '<div class="tt-vct-cycle-row">';
        foreach ( VctTeamCyclesRepository::ALLOWED_WEEKS as $weeks ) {
            $id = 'tt-cycle-' . $team_id . '-' . $weeks;
            echo '<label class="tt-vct-cycle-option" for="' . esc_attr( $id ) . '">';
            echo '<input type="radio" id="' . esc_attr( $id ) . '" name="cycle_weeks" value="' . esc_attr( (string) $weeks ) . '"'
                . checked( $length, $weeks, false ) . '> ';
            echo '<span>' . esc_html( sprintf(
                /* translators: %d = number of weeks in the cycle. */
                _n( '%d week', '%d weeks', $weeks, 'talenttrack' ),
                $weeks
            ) ) . '</span>';
            echo '</label>';
        }
        echo '</div>';
        echo '</fieldset>';

        echo '<div class="tt-vct-form-grid">';
        echo '<div class="tt-field">';
        echo '<label class="tt-field-label" for="tt-cycle-anchor-' . esc_attr( (string) $team_id ) . '">'
            . esc_html__( 'Starts on', 'talenttrack' ) . '</label>';
        echo '<input class="tt-input" type="date" id="tt-cycle-anchor-' . esc_attr( (string) $team_id ) . '"'
            . ' name="anchor_date" value="' . esc_attr( $anchor ) . '">';
        echo '<p class="tt-field-hint">' . esc_html__( 'Week 1 begins on the Monday of this week.', 'talenttrack' ) . '</p>';
        echo '</div>';

        echo '<div class="tt-field">';
        echo '<label class="tt-field-label" for="tt-cycle-shape-' . esc_attr( (string) $team_id ) . '">'
            . esc_html__( 'Weekly shape', 'talenttrack' ) . '</label>';
        echo '<select class="tt-input" id="tt-cycle-shape-' . esc_attr( (string) $team_id ) . '" name="template_id">';
        echo '<option value="0">' . esc_html__( 'Match the cycle length', 'talenttrack' ) . '</option>';
        foreach ( VctTeamCycleValidator::templatesForLength( $length ) as $template ) {
            echo '<option value="' . esc_attr( (string) $template['id'] ) . '"'
                . selected( $chosen, (int) $template['id'], false ) . '>'
                . esc_html( (string) $template['label'] ) . '</option>';
        }
        echo '</select>';
        echo '</div>';
        echo '</div>';

        if ( $cycle !== null ) {
            echo '<p class="tt-vct-cycle-preview">' . esc_html( self::cyclePreview( $length, $chosen ) ) . '</p>';
        }

        echo '<div class="tt-form-actions">';
        echo '<button type="submit" class="tt-btn tt-btn-primary">' . esc_html__( 'Save cycle', 'talenttrack' ) . '</button>';
        echo '</div>';
        echo '</form>';

        if ( $cycle !== null ) {
            echo '<form method="POST" action="" class="tt-vct-cycle-remove">';
            wp_nonce_field( 'tt_vct_cfg_cycle_delete_' . $team_id . '_' . $season_id, '_tt_vct_cfg_nonce' );
            echo '<input type="hidden" name="_tt_action" value="delete_cycle">';
            echo '<input type="hidden" name="team_id"    value="' . esc_attr( (string) $team_id ) . '">';
            echo '<input type="hidden" name="season_id"  value="' . esc_attr( (string) $season_id ) . '">';
            echo '<p class="tt-field-hint">' . esc_html__( 'Removing the cycle sends this team back to planning from the season\'s macro-blocks. Trainings already planned keep the week they were given.', 'talenttrack' ) . '</p>';
            echo '<button type="submit" class="tt-btn tt-btn-secondary">' . esc_html__( 'Remove cycle', 'talenttrack' ) . '</button>';
            echo '</form>';
        }
    }

    /**
     * The season's start, snapped forward to a Monday — the sensible place
     * for week 1 to begin when nobody has chosen one.
     */
    private static function defaultAnchorFor( int $season_id ): string {
        $season = ( new SeasonsRepository() )->find( $season_id );
        $start  = $season !== null ? (string) ( $season->start_date ?? '' ) : '';
        $monday = VctTeamCyclesRepository::normaliseToMonday( $start );
        return $monday ?? '';
    }

    /**
     * One line naming each week of the cycle, so the choice is legible
     * without opening the cycle calendar.
     */
    private static function cyclePreview( int $length, int $template_id ): string {
        $templates = VctTeamCycleValidator::templatesForLength( $length );
        $profile   = [];

        foreach ( $templates as $template ) {
            if ( $template_id > 0 && (int) $template['id'] !== $template_id ) continue;
            $profile = is_array( $template['phase_profile'] ) ? $template['phase_profile'] : [];
            break;
        }
        if ( $profile === [] ) return '';

        $parts = [];
        foreach ( $profile as $week ) {
            $phase = isset( $week['phase'] ) ? (string) $week['phase'] : '';
            if ( $phase === '' ) continue;
            $parts[] = sprintf(
                /* translators: 1: week number within the cycle, 2: the phase name for that week. */
                __( 'week %1$d %2$s', 'talenttrack' ),
                (int) ( $week['week'] ?? 0 ),
                LookupTranslator::byTypeAndName( 'vct_phase', $phase )
            );
        }
        return implode( ' · ', $parts );
    }

    /**
     * Human-readable training-day summary for a schedule accordion's
     * meta line, e.g. "Tue · Thu".
     *
     * @param array<int,string> $labels
     */
    private static function weekdaySummary( array $labels, int $bitmask ): string {
        $out = [];
        foreach ( $labels as $bit => $label ) {
            if ( ( $bitmask & $bit ) === $bit ) $out[] = $label;
        }
        return implode( ' · ', $out );
    }

    /**
     * @param int|null $value Null renders an empty field — #2601's add
     *                        form deliberately suggests no load ceiling,
     *                        because a plausible-looking default invites
     *                        acceptance without a decision, and these
     *                        numbers govern how hard children are worked.
     * @param string   $id_suffix Keeps the `for`/`id` pair unique when the
     *                        same field name appears in more than one form
     *                        on the page.
     */
    private static function renderNumberInput( string $name, string $label, ?int $value, int $min, int $max, string $id_suffix = '' ): void {
        $id = $name . ( $id_suffix !== '' ? '_' . $id_suffix : '' );
        echo '<div class="tt-field">';
        echo '<label class="tt-field-label" for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label>';
        echo '<input class="tt-input" type="number" inputmode="numeric" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" min="' . esc_attr( (string) $min ) . '" max="' . esc_attr( (string) $max ) . '" value="' . esc_attr( $value === null ? '' : (string) $value ) . '" required>';
        echo '</div>';
    }

    // ── POST handlers ────────────────────────────────────────────────

    private static function handlePost(): void {
        $action = isset( $_POST['_tt_action'] ) ? sanitize_key( (string) $_POST['_tt_action'] ) : '';

        if ( $action === 'save_age_profile' ) {
            $id = absint( $_POST['id'] ?? 0 );
            if ( ! wp_verify_nonce( (string) ( $_POST['_tt_vct_cfg_nonce'] ?? '' ), 'tt_vct_cfg_age_save_' . $id ) ) {
                self::notice( 'error', __( 'Save failed: your form expired. Please reload.', 'talenttrack' ) );
                return;
            }
            $patch = [
                'session_minutes_max'              => (int)   ( $_POST['session_minutes_max']             ?? 0 ),
                'intensity_band_max'               => (int)   ( $_POST['intensity_band_max']              ?? 0 ),
                'md_logic_enabled'                 => ! empty( $_POST['md_logic_enabled'] ) ? 1 : 0,
                'min_recovery_hours_between_high'  => (int)   ( $_POST['min_recovery_hours_between_high'] ?? 0 ),
                'growth_spurt_load_reduction_pct'  => (int)   ( $_POST['growth_spurt_load_reduction_pct'] ?? 0 ),
                'weekly_load_envelope'             => (int)   ( $_POST['weekly_load_envelope']            ?? 0 ),
                'match_load_multiplier_per_minute' => (float) ( $_POST['match_load_multiplier_per_minute'] ?? 7.0 ),
            ];
            $ok = ( new VctAgeProfilesRepository() )->update( $id, $patch );
            self::notice(
                $ok ? 'success' : 'error',
                $ok ? __( 'Age profile updated.', 'talenttrack' ) : __( 'Save failed: database error.', 'talenttrack' )
            );
            return;
        }

        // #2601 — bring an age group the planner cannot draft for into
        // range. Both handlers delegate to `AgeProfileAdminService`, so
        // this view and the REST route make the same decisions about
        // duplicates, the copied session blueprint, and what blocks a
        // removal.
        if ( $action === 'create_age_profile' ) {
            if ( ! wp_verify_nonce( (string) ( $_POST['_tt_vct_cfg_nonce'] ?? '' ), 'tt_vct_cfg_age_create' ) ) {
                self::notice( 'error', __( 'Save failed: your form expired. Please reload.', 'talenttrack' ) );
                return;
            }
            $result = ( new AgeProfileAdminService() )->create( [
                'age_group'                        => sanitize_text_field( wp_unslash( (string) ( $_POST['age_group'] ?? '' ) ) ),
                'session_minutes_max'              => (int)   ( $_POST['session_minutes_max']              ?? 0 ),
                'intensity_band_max'               => (int)   ( $_POST['intensity_band_max']               ?? 0 ),
                'md_logic_enabled'                 => ! empty( $_POST['md_logic_enabled'] ) ? 1 : 0,
                'min_recovery_hours_between_high'  => (int)   ( $_POST['min_recovery_hours_between_high']  ?? 48 ),
                'growth_spurt_load_reduction_pct'  => (int)   ( $_POST['growth_spurt_load_reduction_pct']  ?? 20 ),
                'weekly_load_envelope'             => (int)   ( $_POST['weekly_load_envelope']             ?? 0 ),
                'match_load_multiplier_per_minute' => (float) ( $_POST['match_load_multiplier_per_minute'] ?? 7.0 ),
            ] );

            if ( $result['id'] <= 0 ) {
                self::notice( 'error', $result['error'] );
                return;
            }
            self::notice(
                'success',
                $result['templates_copied'] > 0
                    ? __( 'Age profile added. The training shape was copied from the closest age group, so the planner can draft for these teams now.', 'talenttrack' )
                    : __( 'Age profile added. No training shape existed to copy, so the planner still needs a blueprint for this age group.', 'talenttrack' )
            );
            return;
        }

        if ( $action === 'delete_age_profile' ) {
            $id = absint( $_POST['id'] ?? 0 );
            if ( ! wp_verify_nonce( (string) ( $_POST['_tt_vct_cfg_nonce'] ?? '' ), 'tt_vct_cfg_age_delete_' . $id ) ) {
                self::notice( 'error', __( 'Save failed: your form expired. Please reload.', 'talenttrack' ) );
                return;
            }
            $result = ( new AgeProfileAdminService() )->delete( $id );
            self::notice(
                $result['deleted'] ? 'success' : 'error',
                $result['deleted'] ? __( 'Age profile removed.', 'talenttrack' ) : $result['error']
            );
            return;
        }

        if ( $action === 'save_schedule' ) {
            $team_id   = absint( $_POST['team_id']   ?? 0 );
            $season_id = absint( $_POST['season_id'] ?? 0 );
            if ( ! wp_verify_nonce( (string) ( $_POST['_tt_vct_cfg_nonce'] ?? '' ), 'tt_vct_cfg_schedule_save_' . $team_id . '_' . $season_id ) ) {
                self::notice( 'error', __( 'Save failed: your form expired. Please reload.', 'talenttrack' ) );
                return;
            }
            $bits = 0;
            foreach ( (array) ( $_POST['weekday_bits'] ?? [] ) as $b ) {
                $bits |= (int) $b;
            }
            $start_time = isset( $_POST['default_start_time'] ) ? (string) $_POST['default_start_time'] : '';
            $duration   = isset( $_POST['default_duration_minutes'] ) ? (int) $_POST['default_duration_minutes'] : 0;
            $ok = ( new VctTeamSchedulesRepository() )->upsert(
                $team_id, $season_id, $bits,
                $start_time !== '' ? $start_time : null,
                $duration > 0 ? $duration : null,
                get_current_user_id()
            );
            self::notice(
                $ok ? 'success' : 'error',
                $ok ? __( 'Team schedule saved.', 'talenttrack' ) : __( 'Save failed: database error.', 'talenttrack' )
            );
        }

        if ( $action === 'save_cycle' ) {
            $team_id   = absint( $_POST['team_id']   ?? 0 );
            $season_id = absint( $_POST['season_id'] ?? 0 );
            if ( ! wp_verify_nonce( (string) ( $_POST['_tt_vct_cfg_nonce'] ?? '' ), 'tt_vct_cfg_cycle_save_' . $team_id . '_' . $season_id ) ) {
                self::notice( 'error', __( 'Save failed: your form expired. Please reload.', 'talenttrack' ) );
                return;
            }

            $template_id = absint( $_POST['template_id'] ?? 0 );
            $input = [
                'cycle_weeks' => absint( $_POST['cycle_weeks'] ?? 0 ),
                'anchor_date' => isset( $_POST['anchor_date'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['anchor_date'] ) ) : '',
                'template_id' => $template_id,
            ];

            // The shared validator, so the form and the REST endpoint
            // refuse the same things for the same reasons.
            $error = VctTeamCycleValidator::validate( $input );
            if ( $error !== null ) {
                self::notice( 'error', $error );
                return;
            }

            $ok = ( new VctTeamCyclesRepository() )->upsert(
                $team_id,
                $season_id,
                (int) $input['cycle_weeks'],
                (string) $input['anchor_date'],
                $template_id > 0 ? $template_id : null
            );
            self::notice(
                $ok ? 'success' : 'error',
                $ok ? __( 'Cycle saved.', 'talenttrack' ) : __( 'Save failed: database error.', 'talenttrack' )
            );
        }

        if ( $action === 'delete_cycle' ) {
            $team_id   = absint( $_POST['team_id']   ?? 0 );
            $season_id = absint( $_POST['season_id'] ?? 0 );
            if ( ! wp_verify_nonce( (string) ( $_POST['_tt_vct_cfg_nonce'] ?? '' ), 'tt_vct_cfg_cycle_delete_' . $team_id . '_' . $season_id ) ) {
                self::notice( 'error', __( 'Removing failed: your form expired. Please reload.', 'talenttrack' ) );
                return;
            }

            // The overrides go with it. An override only ever means "this
            // week of the cycle is an exception", so one left behind would
            // silently re-apply to a cycle set up months later.
            ( new VctCycleWeekOverridesRepository() )->clearSeason( $team_id, $season_id );
            $ok = ( new VctTeamCyclesRepository() )->delete( $team_id, $season_id );

            self::notice(
                $ok ? 'success' : 'error',
                $ok
                    ? __( 'Cycle removed. This team plans from the season\'s macro-blocks again.', 'talenttrack' )
                    : __( 'Removing failed: database error.', 'talenttrack' )
            );
        }
    }

    private static function notice( string $variant, string $msg ): void {
        echo '<div class="tt-notice tt-notice--' . esc_attr( $variant ) . ' tt-vct-notice">'
            . esc_html( $msg ) . '</div>';
    }
}
