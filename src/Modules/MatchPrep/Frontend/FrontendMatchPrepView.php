<?php
namespace TT\Modules\MatchPrep\Frontend;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Domain\Vocabularies\Lookups\ActivityTypeKey;
use TT\Infrastructure\Query\QueryHelpers;
use TT\Infrastructure\Tenancy\CurrentClub;
use TT\Modules\MatchPrep\Repositories\MatchPrepRepository;
use TT\Shared\Frontend\Components\FrontendBreadcrumbs;
use TT\Shared\Frontend\FrontendViewBase;
use TT\Shared\Util\PlayerShortName;

/**
 * FrontendMatchPrepView (#965) — head-coach match preparation surface.
 *
 * 3-column layout matching the pilot's working spreadsheet:
 *   - Left (200px): roster with minute counters per half + totals.
 *   - Middle (flex): two half-pitches side by side with a copy button
 *     between them; below them the Wedstrijddoelen panel (Algemeen
 *     full-width, then 2×2 of Aanvallen/Verdedigen and Spelhervattingen).
 *   - Right (320px): stacked panels — Doen per speler (per-player
 *     attention + ! flag + camera-icon analyst flag) and Rollen &
 *     standaardsituaties (captain + 5 set-piece takers).
 *
 * Player picker is the single interaction model for both pitch slots
 * and role rows; drag-drop from the roster is the desktop enhancement
 * for slot assignment. All edits live-save via REST.
 *
 * Desktop-only per the original #838 spec; the layout collapses to one
 * column below 1099px without crashing, but mobile polish is a
 * follow-up.
 */
class FrontendMatchPrepView extends FrontendViewBase {

    /**
     * Slot positions per formation shape. Coordinates are percentage
     * offsets from the pitch container (0 = top/left, 100 =
     * bottom/right). The 4-3-3 default mirrors the mockup; the rest
     * are sensible starting layouts. v2 can read from
     * tt_formation_templates.slots_json instead — keeping this in PHP
     * keeps the v1 shipping surface narrow.
     *
     * @return array<string, list<array{num:int,label:string,x:float,y:float}>>
     */
    public static function defaultSlotLayouts(): array {
        return [
            '4-3-3' => [
                [ 'num' =>  9, 'label' => 'ST',  'x' => 50, 'y' => 12 ],
                [ 'num' => 11, 'label' => 'LW',  'x' => 18, 'y' => 28 ],
                [ 'num' => 10, 'label' => 'AM',  'x' => 50, 'y' => 28 ],
                [ 'num' =>  7, 'label' => 'RW',  'x' => 82, 'y' => 28 ],
                [ 'num' =>  8, 'label' => 'LCM', 'x' => 36, 'y' => 45 ],
                [ 'num' =>  6, 'label' => 'RCM', 'x' => 64, 'y' => 45 ],
                [ 'num' =>  5, 'label' => 'LB',  'x' => 16, 'y' => 64 ],
                [ 'num' =>  4, 'label' => 'LCB', 'x' => 38, 'y' => 64 ],
                [ 'num' =>  3, 'label' => 'RCB', 'x' => 62, 'y' => 64 ],
                [ 'num' =>  2, 'label' => 'RB',  'x' => 84, 'y' => 64 ],
                [ 'num' =>  1, 'label' => 'GK',  'x' => 50, 'y' => 88 ],
            ],
            '4-2-3-1' => [
                [ 'num' =>  9, 'label' => 'ST',  'x' => 50, 'y' => 14 ],
                [ 'num' => 11, 'label' => 'LAM', 'x' => 20, 'y' => 30 ],
                [ 'num' => 10, 'label' => 'AM',  'x' => 50, 'y' => 30 ],
                [ 'num' =>  7, 'label' => 'RAM', 'x' => 80, 'y' => 30 ],
                [ 'num' =>  8, 'label' => 'LDM', 'x' => 36, 'y' => 50 ],
                [ 'num' =>  6, 'label' => 'RDM', 'x' => 64, 'y' => 50 ],
                [ 'num' =>  5, 'label' => 'LB',  'x' => 16, 'y' => 68 ],
                [ 'num' =>  4, 'label' => 'LCB', 'x' => 38, 'y' => 68 ],
                [ 'num' =>  3, 'label' => 'RCB', 'x' => 62, 'y' => 68 ],
                [ 'num' =>  2, 'label' => 'RB',  'x' => 84, 'y' => 68 ],
                [ 'num' =>  1, 'label' => 'GK',  'x' => 50, 'y' => 88 ],
            ],
            '4-4-2' => [
                [ 'num' =>  9, 'label' => 'ST',  'x' => 38, 'y' => 14 ],
                [ 'num' => 10, 'label' => 'ST',  'x' => 62, 'y' => 14 ],
                [ 'num' => 11, 'label' => 'LM',  'x' => 14, 'y' => 38 ],
                [ 'num' =>  8, 'label' => 'LCM', 'x' => 38, 'y' => 38 ],
                [ 'num' =>  6, 'label' => 'RCM', 'x' => 62, 'y' => 38 ],
                [ 'num' =>  7, 'label' => 'RM',  'x' => 86, 'y' => 38 ],
                [ 'num' =>  5, 'label' => 'LB',  'x' => 16, 'y' => 64 ],
                [ 'num' =>  4, 'label' => 'LCB', 'x' => 38, 'y' => 64 ],
                [ 'num' =>  3, 'label' => 'RCB', 'x' => 62, 'y' => 64 ],
                [ 'num' =>  2, 'label' => 'RB',  'x' => 84, 'y' => 64 ],
                [ 'num' =>  1, 'label' => 'GK',  'x' => 50, 'y' => 88 ],
            ],
            '3-5-2' => [
                [ 'num' =>  9, 'label' => 'ST',  'x' => 38, 'y' => 14 ],
                [ 'num' => 10, 'label' => 'ST',  'x' => 62, 'y' => 14 ],
                [ 'num' =>  7, 'label' => 'RWB', 'x' => 88, 'y' => 38 ],
                [ 'num' =>  8, 'label' => 'CM',  'x' => 36, 'y' => 40 ],
                [ 'num' =>  6, 'label' => 'CM',  'x' => 50, 'y' => 46 ],
                [ 'num' =>  4, 'label' => 'CM',  'x' => 64, 'y' => 40 ],
                [ 'num' => 11, 'label' => 'LWB', 'x' => 12, 'y' => 38 ],
                [ 'num' =>  5, 'label' => 'LCB', 'x' => 26, 'y' => 66 ],
                [ 'num' =>  3, 'label' => 'CB',  'x' => 50, 'y' => 68 ],
                [ 'num' =>  2, 'label' => 'RCB', 'x' => 74, 'y' => 66 ],
                [ 'num' =>  1, 'label' => 'GK',  'x' => 50, 'y' => 88 ],
            ],
            '3-4-3' => [
                [ 'num' =>  9, 'label' => 'ST',  'x' => 50, 'y' => 14 ],
                [ 'num' => 11, 'label' => 'LW',  'x' => 20, 'y' => 22 ],
                [ 'num' =>  7, 'label' => 'RW',  'x' => 80, 'y' => 22 ],
                [ 'num' => 10, 'label' => 'LM',  'x' => 26, 'y' => 44 ],
                [ 'num' =>  8, 'label' => 'LCM', 'x' => 42, 'y' => 46 ],
                [ 'num' =>  6, 'label' => 'RCM', 'x' => 58, 'y' => 46 ],
                [ 'num' =>  4, 'label' => 'RM',  'x' => 74, 'y' => 44 ],
                [ 'num' =>  5, 'label' => 'LCB', 'x' => 26, 'y' => 68 ],
                [ 'num' =>  3, 'label' => 'CB',  'x' => 50, 'y' => 68 ],
                [ 'num' =>  2, 'label' => 'RCB', 'x' => 74, 'y' => 68 ],
                [ 'num' =>  1, 'label' => 'GK',  'x' => 50, 'y' => 88 ],
            ],
            '4-1-4-1' => [
                [ 'num' =>  9, 'label' => 'ST',  'x' => 50, 'y' => 14 ],
                [ 'num' => 11, 'label' => 'LM',  'x' => 14, 'y' => 34 ],
                [ 'num' => 10, 'label' => 'LCM', 'x' => 38, 'y' => 34 ],
                [ 'num' =>  8, 'label' => 'RCM', 'x' => 62, 'y' => 34 ],
                [ 'num' =>  7, 'label' => 'RM',  'x' => 86, 'y' => 34 ],
                [ 'num' =>  6, 'label' => 'CDM', 'x' => 50, 'y' => 50 ],
                [ 'num' =>  5, 'label' => 'LB',  'x' => 16, 'y' => 66 ],
                [ 'num' =>  4, 'label' => 'LCB', 'x' => 38, 'y' => 66 ],
                [ 'num' =>  3, 'label' => 'RCB', 'x' => 62, 'y' => 66 ],
                [ 'num' =>  2, 'label' => 'RB',  'x' => 84, 'y' => 66 ],
                [ 'num' =>  1, 'label' => 'GK',  'x' => 50, 'y' => 88 ],
            ],
        ];
    }

    /**
     * Per-template slot layout read from a formation template's
     * `slots_json`, when that JSON carries explicit slot numbers (#2099).
     *
     * Returns a num-keyed list shaped exactly like a
     * `defaultSlotLayouts()` entry (x/y in 0..100, left→right / top→bottom),
     * or `null` when the template has no num-bearing `slots_json` — callers
     * then fall back to the shape-keyed default. This makes a template's own
     * geometry authoritative (e.g. the 3-4-3 diamond) instead of collapsing
     * every template that shares a shape string onto one flat layout.
     *
     * The templates table is install-global (no club_id column), so no
     * tenancy filter applies here.
     *
     * @return list<array{num:int,label:string,x:float,y:float}>|null
     */
    public static function templateSlotLayout( int $template_id ): ?array {
        if ( $template_id <= 0 ) return null;
        global $wpdb;
        $table = $wpdb->prefix . 'tt_formation_templates';
        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
            return null;
        }
        $json = (string) $wpdb->get_var( $wpdb->prepare(
            "SELECT slots_json FROM {$table} WHERE id = %d LIMIT 1",
            $template_id
        ) );
        return self::parseSlotsJson( $json );
    }

    /**
     * Parse a template `slots_json` string into a num-keyed slot layout.
     * Returns `null` unless every slot carries a numeric `num` — a layout
     * without slot numbers can't be aligned to the lineup, so callers fall
     * back to the shape default. `pos.x` / `pos.y` are 0..1 in the stored
     * JSON and scaled to the 0..100 percentage the pitch templates use.
     *
     * @return list<array{num:int,label:string,x:float,y:float}>|null
     */
    public static function parseSlotsJson( string $json ): ?array {
        if ( $json === '' ) return null;
        $data = json_decode( $json, true );
        if ( ! is_array( $data ) || $data === [] ) return null;

        $out = [];
        foreach ( $data as $slot ) {
            if ( ! is_array( $slot ) || ! isset( $slot['num'] ) || ! is_numeric( $slot['num'] ) ) {
                return null;
            }
            $pos = is_array( $slot['pos'] ?? null ) ? $slot['pos'] : [];
            $out[] = [
                'num'   => (int) $slot['num'],
                'label' => (string) ( $slot['label'] ?? '' ),
                'x'     => (float) ( $pos['x'] ?? 0.5 ) * 100,
                'y'     => (float) ( $pos['y'] ?? 0.5 ) * 100,
            ];
        }
        return $out;
    }

    /**
     * Role keys + Dutch labels for the Rollen & standaardsituaties pane.
     * Mirrors `MatchPrepRepository::ROLE_KEYS` order.
     *
     * @return list<array{key:string,label:string}>
     */
    public static function roleDefinitions(): array {
        return [
            [ 'key' => 'captain',  'label' => __( 'Captain', 'talenttrack' ) ],
            [ 'key' => 'corner_l', 'label' => __( 'Corner left', 'talenttrack' ) ],
            [ 'key' => 'corner_r', 'label' => __( 'Corner right', 'talenttrack' ) ],
            [ 'key' => 'fk_l',     'label' => __( 'Free kick left (cross)', 'talenttrack' ) ],
            [ 'key' => 'fk_r',     'label' => __( 'Free kick right (cross)', 'talenttrack' ) ],
            [ 'key' => 'penalty',  'label' => __( 'Penalty', 'talenttrack' ) ],
        ];
    }

    public static function render( int $user_id, bool $is_admin ): void {
        if ( ! current_user_can( 'tt_edit_activities' ) ) {
            FrontendBreadcrumbs::fromDashboard( __( 'Not authorized', 'talenttrack' ) );
            echo '<p class="tt-notice">' . esc_html__( 'Match prep is restricted to coaches and admins.', 'talenttrack' ) . '</p>';
            return;
        }

        $activity_id = isset( $_GET['activity_id'] ) ? absint( $_GET['activity_id'] ) : 0;
        if ( $activity_id <= 0 ) {
            FrontendBreadcrumbs::fromDashboard( __( 'Match prep', 'talenttrack' ) );
            echo '<p class="tt-notice">' . esc_html__( 'Open Match prep from a match activity\'s detail page.', 'talenttrack' ) . '</p>';
            return;
        }

        $activity = self::loadActivity( $activity_id );
        if ( ! $activity ) {
            FrontendBreadcrumbs::fromDashboard( __( 'Match prep', 'talenttrack' ) );
            echo '<p class="tt-notice">' . esc_html__( 'Activity not found.', 'talenttrack' ) . '</p>';
            return;
        }
        // #3151 — `tt_edit_activities` is held club-wide, so it says the
        // viewer runs matches, not whose. Narrow to the teams they coach
        // before a squad is loaded.
        if ( ! \TT\Modules\Authorization\ActivityTeamScope::coversTeam( $user_id, (int) ( $activity->team_id ?? 0 ), $is_admin ) ) {
            FrontendBreadcrumbs::fromDashboard( __( 'Match prep', 'talenttrack' ) );
            echo \TT\Modules\Authorization\ActivityTeamScope::refusalNotice(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — escaped at source
            return;
        }

        // #3105 — `match_prep` is Pro. A plan the club already wrote stays
        // on screen (and printable) with the panel above it; the REST
        // writes behind the form answer 402, so the fields render but do
        // not save. #3017's third decision.
        //
        // After the #3151 scope refusal above, deliberately: someone who
        // does not coach this team gets the permission answer rather than
        // an upgrade pitch for a plan that would not let them in either.
        // Permission first, then entitlement — the order this epic uses
        // on every surface.
        $plan_locked = ! \TT\Modules\License\LicenseGate::allows( 'match_prep' );

        // Note: 'match' is a legacy synonym for ActivityTypeKey::GAME kept
        // for back-compat — see #988 follow-up.
        if ( ( $activity->activity_type_key ?? '' ) !== ActivityTypeKey::GAME && ( $activity->activity_type_key ?? '' ) !== 'match' ) {
            FrontendBreadcrumbs::fromDashboard( __( 'Match prep', 'talenttrack' ) );
            echo '<p class="tt-notice">' . esc_html__( 'Match prep is only available for match-type activities.', 'talenttrack' ) . '</p>';
            return;
        }

        $repo = new MatchPrepRepository();
        $prep = $repo->findByActivity( $activity_id );
        if ( ! $prep ) {
            // #1660 — no prep record yet. Render an in-place empty state
            // instead of wp_safe_redirect()/exit: this view runs inside the
            // dashboard shortcode after the shell (and headers) are already
            // flushed, so the redirect was a no-op and the bare exit
            // truncated the page to a blank shell. The other early-returns
            // in this method already use this breadcrumb + notice pattern.
            $wizard_url = add_query_arg( [
                'tt_view'     => 'wizard',
                'slug'        => 'match-prep',
                'activity_id' => $activity_id,
            ], remove_query_arg( [ 'tt_view', 'activity_id' ] ) );
            FrontendBreadcrumbs::fromDashboard( __( 'Match prep', 'talenttrack' ) );
            // #3105 — nothing written yet, so there is no record to keep
            // readable: this is the creation entry point and it locks.
            if ( $plan_locked ) {
                echo \TT\Modules\License\UpgradePanel::render( 'match_prep' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — UpgradePanel returns escaped HTML
                return;
            }
            echo '<p class="tt-notice">' . esc_html__( 'No match preparation has been started for this match yet. Begin by capturing player availability.', 'talenttrack' ) . '</p>';
            echo '<p><a class="tt-btn tt-btn-primary" href="' . esc_url( $wizard_url ) . '">'
                . esc_html__( 'Start match prep', 'talenttrack' ) . '</a></p>';
            return;
        }

        // A plan that exists stays on screen, with the panel above it.
        if ( $plan_locked ) {
            FrontendBreadcrumbs::fromDashboard( __( 'Match prep', 'talenttrack' ) );
            echo \TT\Modules\License\UpgradePanel::render( 'match_prep', [ 'reads_kept' => true ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — UpgradePanel returns escaped HTML
        }

        $prep_id      = (int) $prep->id;
        $availability = $repo->listAvailability( $prep_id );
        $lineup_rows  = $repo->listLineup( $prep_id );
        $player_goals = $repo->listPlayerGoals( $prep_id );
        $roles        = $repo->listRoles( $prep_id );

        // Build helper structures.
        $players_by_id = self::loadTeamRosterById( (int) $activity->team_id );
        $roster_list   = self::sortRoster( $players_by_id );

        // Short-name map (#1023) — first name only, disambiguated to
        // "<first> <last-initial>" when two players on the team share a
        // first name. Computed against the whole team roster (not just
        // present players) so the same player renders identically on every
        // sub-surface even when availability flips during the session.
        $short_names = PlayerShortName::resolve( $roster_list );

        $availability_by_pid = [];
        $available_ids       = [];
        foreach ( $availability as $a ) {
            $pid = (int) $a->player_id;
            $availability_by_pid[ $pid ] = [
                'status' => (string) ( $a->status ?? 'Present' ),
                'reason' => (string) ( $a->reason ?? '' ),
            ];
            if ( strcasecmp( (string) $a->status, 'Present' ) === 0 ) {
                $available_ids[] = $pid;
            }
        }

        $lineup_by_half = [ 1 => [], 2 => [] ];
        foreach ( $lineup_rows as $l ) {
            $lineup_by_half[ (int) $l->half ][ (int) $l->slot_number ] = (int) $l->player_id;
        }

        $pgoals_by_pid = [];
        foreach ( $player_goals as $g ) {
            $pgoals_by_pid[ (int) $g->player_id ] = $g;
        }

        $roles_by_key = [];
        foreach ( $roles as $r ) {
            $roles_by_key[ (string) $r->role_key ] = (int) $r->player_id;
        }

        $formations  = self::listFormationTemplates();
        $formation_shape = self::resolveFormationShape( (int) ( $prep->formation_template_id ?? 0 ), $formations );

        $title = sprintf(
            /* translators: 1: activity title, 2: session date */
            __( 'Match preparation — %1$s · %2$s', 'talenttrack' ),
            (string) ( $activity->title ?? '—' ),
            (string) ( $activity->session_date ?? '' )
        );

        $back_to_activity = add_query_arg( [
            'tt_view' => 'activities',
            'id'      => $activity_id,
        ], remove_query_arg( [ 'tt_view', 'activity_id' ] ) );

        // #1479 — Dashboard → Activities → [Activity] → Match prep. The
        // previous crumb passed `href`, but the breadcrumb component reads
        // `url` (so it rendered as un-clickable plain text); `viewCrumb()`
        // builds the correct shape. The source-activity crumb is the
        // standards-compliant "back to the activity" affordance (no
        // bespoke button); the contextual pill is auto-rendered from
        // `tt_back` when match prep was entered from the activity.
        $activity_crumb_label = (string) ( $activity->title ?? '' );
        if ( $activity_crumb_label === '' ) {
            $activity_crumb_label = __( 'Activity', 'talenttrack' );
        }
        FrontendBreadcrumbs::fromDashboard( __( 'Match prep', 'talenttrack' ), [
            FrontendBreadcrumbs::viewCrumb( 'activities', __( 'Activities', 'talenttrack' ) ),
            FrontendBreadcrumbs::viewCrumb( 'activities', $activity_crumb_label, [ 'id' => $activity_id ] ),
        ] );

        parent::enqueueAssets();
        self::enqueueViewAssets( $prep_id, $activity_id );

        $half_length = (int) ( $prep->half_length_minutes ?? 35 );

        // #1695 — at-a-glance summary on real data for the 2026 KPI strip.
        $summary = self::summaryCounts( $availability_by_pid, $lineup_by_half[1], count( $roster_list ) );
        ?>
        <h1 class="tt-fview-title tt-match-prep-title"><?php echo esc_html( $title ); ?></h1>

        <section class="tt-match-prep"
                 data-activity-id="<?php echo (int) $activity_id; ?>"
                 data-prep-id="<?php echo (int) $prep_id; ?>"
                 data-formation-shape="<?php echo esc_attr( $formation_shape ); ?>"
                 data-half-length="<?php echo (int) $half_length; ?>"
                 data-cancel-url="<?php echo esc_url( $back_to_activity ); ?>">

            <!-- 2026 KPI strip — match-prep summary on real data (#1695) -->
            <div class="tt-mp-kpis" role="group" aria-label="<?php esc_attr_e( 'Match prep summary', 'talenttrack' ); ?>">
                <?php
                echo \TT\Shared\Frontend\Components\FrontendAppChrome::kpiTile( [
                    'label' => __( 'Available', 'talenttrack' ),
                    'value' => (string) $summary['available'],
                ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo \TT\Shared\Frontend\Components\FrontendAppChrome::kpiTile( [
                    'label' => __( 'Unavailable', 'talenttrack' ),
                    'value' => (string) $summary['unavailable'],
                    'flag'  => $summary['unavailable'] > 0 ? 'red' : '',
                ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo \TT\Shared\Frontend\Components\FrontendAppChrome::kpiTile( [
                    /* translators: 1st-half starters assigned out of 11. */
                    'label' => __( '1st-half starters', 'talenttrack' ),
                    'value' => sprintf( '%d/11', $summary['starters'] ),
                    'flag'  => $summary['starters'] >= 11 ? 'green' : '',
                ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo \TT\Shared\Frontend\Components\FrontendAppChrome::kpiTile( [
                    'label' => __( 'Formation', 'talenttrack' ),
                    'value' => $formation_shape,
                    // #2098 — JS hook so the formation dropdown can live-update
                    // this tile (the shape is also mirrored on the section's
                    // data-formation-shape attribute).
                    'data'  => [ 'tt-mp-formation-kpi' => '1' ],
                ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                ?>
            </div>

            <!-- Toolbar: formation / half length / availability / print / save state -->
            <div class="tt-mp-toolbar" role="toolbar" aria-label="<?php esc_attr_e( 'Match prep toolbar', 'talenttrack' ); ?>">
                <div class="tt-mp-field">
                    <label for="tt-mp-formation"><?php esc_html_e( 'Formation', 'talenttrack' ); ?></label>
                    <select id="tt-mp-formation" name="formation_template_id" data-tt-mp-formation>
                        <option value=""><?php esc_html_e( '— Select —', 'talenttrack' ); ?></option>
                        <?php foreach ( $formations as $tpl ) :
                            $tpl_id    = (int) ( $tpl->id ?? 0 );
                            $tpl_name  = (string) ( $tpl->name ?? '' );
                            $tpl_shape = (string) ( $tpl->formation_shape ?? '' );
                            // #1477 — localise the descriptive name (the shape
                            // digits stay as-is). The translated name already
                            // contains the shape for seeded formations, so only
                            // prepend the shape for a custom name that lacks it
                            // (avoids the old "4-3-3 — Neutral 4-3-3" repeat).
                            $tpl_label = \TT\Infrastructure\Query\LabelTranslator::formationName( $tpl_name );
                            if ( $tpl_shape !== '' && strpos( $tpl_label, $tpl_shape ) === false ) {
                                $tpl_label = $tpl_shape . ' — ' . $tpl_label;
                            }
                            ?>
                            <option value="<?php echo esc_attr( (string) $tpl_id ); ?>"
                                    data-shape="<?php echo esc_attr( $tpl_shape ); ?>"
                                    <?php selected( (int) ( $prep->formation_template_id ?? 0 ), $tpl_id ); ?>>
                                <?php echo esc_html( $tpl_label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="tt-mp-field">
                    <label for="tt-mp-halflen"><?php esc_html_e( 'Half length', 'talenttrack' ); ?></label>
                    <input id="tt-mp-halflen"
                           type="number"
                           min="1"
                           max="60"
                           inputmode="numeric"
                           value="<?php echo (int) $half_length; ?>"
                           data-tt-mp-halflen>
                    <span class="tt-mp-unit"><?php esc_html_e( 'min', 'talenttrack' ); ?></span>
                </div>
                <button type="button" class="tt-btn tt-btn-secondary" data-tt-mp-open-availability>
                    <?php esc_html_e( 'Availability', 'talenttrack' ); ?>
                </button>
                <?php
                // #1538 — the coach's own export honours the
                // export_match_prep_pdf sub-feature; when off, the match-prep
                // screen stays but this artefact is hidden (the print router
                // enforces the same toggle server-side).
                //
                // #2769 — the referee's team sheet below is gated separately.
                // One flag used to wrap both, so an academy that files match
                // forms digitally could only hide the referee sheet by also
                // losing the sheet the coach takes to the touchline.
                if ( \TT\Core\FeatureRegistry::isEnabled( 'export_match_prep_pdf' ) ) :
                // #2102 — Export captures the match-prep sheet with
                // html2canvas and lays it on an A4 page via tt-image-pdf.js,
                // so the export is a pixel-faithful image of what the coach
                // sees rather than a separately styled print document. The
                // browser print dialog (Ctrl/Cmd+P) and the standalone print
                // route (?tt_match_prep_print=1) stay reachable as fallbacks.
                //
                // #3272 — landscape, and `.tt-mp-sheet` rather than
                // `.tt-mp-grid`. The grid is a landscape-shaped three-column
                // spreadsheet that was being squeezed into portrait width at
                // a 0.56 scale, which is what put body text at about 5pt; and
                // capturing the grid alone left the fixture, the date and the
                // venue off the sheet entirely, because all three live above
                // it. The wrapper carries the paper header band with them.
                $export_filename = 'match-prep-' . $activity_id . '.pdf';
                ?>
                <button type="button"
                        class="tt-btn tt-btn-secondary"
                        data-tt-image-pdf
                        data-target=".tt-mp-sheet"
                        data-orientation="landscape"
                        data-filename="<?php echo esc_attr( $export_filename ); ?>">
                    <?php esc_html_e( 'Export PDF', 'talenttrack' ); ?>
                </button>
                <?php endif; // #1538 export_match_prep_pdf ?>
                <?php
                if ( \TT\Core\FeatureRegistry::isEnabled( 'export_match_day_team_sheet' ) ) :
                // #1194, #1475 — pitch-side team-sheet (Starting XI / Bench
                // / Squad partition + signature lines). Source of truth is
                // this match-prep view. The print route renders the same
                // partitioned document the DomPDF exporter emits, then the
                // image-capture action produces the A4-landscape PDF. The
                // route is cookie-authed and gated by tt_view_activities in
                // MatchPrepPrintRouter; the REST DomPDF exporter remains
                // registered as the server-side fallback.
                $team_sheet_url = add_query_arg(
                    [ 'tt_match_prep_print' => '1', 'mode' => 'team_sheet', 'activity_id' => $activity_id ],
                    home_url( '/' )
                );
                ?>
                <a class="tt-btn tt-btn-secondary"
                   href="<?php echo esc_url( $team_sheet_url ); ?>"
                   target="_blank"
                   rel="noopener">
                    <?php esc_html_e( 'Print team sheet', 'talenttrack' ); ?>
                </a>
                <?php endif; // #2769 export_match_day_team_sheet ?>
                <span class="tt-mp-spacer"></span>
                <?php
                // #3004 (epic #2881) — the shared status line. `tt-mp-save-state`
                // stays as the JS hook and as this surface's positioning class;
                // the words and the appearance are the component's now, so a
                // coach reads the same sentence here as on every other
                // autosaving surface.
                \TT\Shared\Frontend\Components\SaveState::render( 'tt-mp-save-state' );
                ?>
            </div>

            <!-- ===== PAPER SHEET (captured for the PDF) ===== -->
            <div class="tt-mp-sheet">
            <?php self::renderPaperHead( $activity, $formation_shape, $half_length, $summary ); ?>

            <!-- ===== 3-COLUMN GRID ===== -->
            <div class="tt-mp-grid">

                <!-- LEFT — roster with minute counters -->
                <section class="tt-mp-panel tt-mp-roster-panel" aria-label="<?php esc_attr_e( 'Selection · minutes', 'talenttrack' ); ?>">
                    <header class="tt-mp-panel-head"><?php esc_html_e( 'Selection · minutes', 'talenttrack' ); ?></header>
                    <table class="tt-mp-roster">
                        <thead>
                            <tr>
                                <th scope="col" class="tt-mp-col-name"></th>
                                <th scope="col"><?php esc_html_e( 'min', 'talenttrack' ); ?><br>1e</th>
                                <th scope="col"><?php esc_html_e( 'min', 'talenttrack' ); ?><br>2e</th>
                                <th scope="col"><?php esc_html_e( 'tot', 'talenttrack' ); ?></th>
                            </tr>
                        </thead>
                        <tbody data-tt-mp-roster>
                        <?php if ( empty( $available_ids ) ) : ?>
                            <tr><td colspan="4" class="tt-mp-empty">
                                <?php esc_html_e( 'No availability captured yet.', 'talenttrack' ); ?><br>
                                <a href="#" data-tt-mp-open-availability><?php esc_html_e( 'Open availability', 'talenttrack' ); ?></a>
                            </td></tr>
                        <?php else :
                            foreach ( $available_ids as $pid ) :
                                if ( ! isset( $players_by_id[ $pid ] ) ) continue;
                                $pl    = $players_by_id[ $pid ];
                                $name  = $short_names[ $pid ] ?? QueryHelpers::player_display_name( $pl );
                                $on1   = in_array( $pid, $lineup_by_half[1], true );
                                $on2   = in_array( $pid, $lineup_by_half[2], true );
                                $min1  = $on1 ? $half_length : 0;
                                $min2  = $on2 ? $half_length : 0;
                                ?>
                                <tr data-pid="<?php echo (int) $pid; ?>"
                                    class="tt-mp-roster-row <?php echo ( $on1 || $on2 ) ? 'tt-mp-assigned' : 'tt-mp-unassigned'; ?>"
                                    draggable="true">
                                    <td class="tt-mp-col-name" data-tt-mp-name><?php echo esc_html( $name ); ?></td>
                                    <td class="tt-mp-col-min <?php echo $on1 ? 'tt-mp-on' : 'tt-mp-off'; ?>" data-tt-mp-min="1"><?php echo (int) $min1; ?></td>
                                    <td class="tt-mp-col-min <?php echo $on2 ? 'tt-mp-on' : 'tt-mp-off'; ?>" data-tt-mp-min="2"><?php echo (int) $min2; ?></td>
                                    <td class="tt-mp-col-tot <?php echo ( $on1 || $on2 ) ? 'tt-mp-on' : 'tt-mp-off'; ?>" data-tt-mp-min="tot"><?php echo (int) ( $min1 + $min2 ); ?></td>
                                </tr>
                                <?php
                            endforeach;
                        endif;
                        ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td class="tt-mp-foot-blank"></td>
                                <td data-tt-mp-foot="count1">0</td>
                                <td data-tt-mp-foot="count2">0</td>
                                <td class="tt-mp-foot-blank"></td>
                            </tr>
                            <tr>
                                <td class="tt-mp-foot-blank"></td>
                                <td data-tt-mp-foot="min1">0</td>
                                <td data-tt-mp-foot="min2">0</td>
                                <td data-tt-mp-foot="mintot">0</td>
                            </tr>
                            <tr class="tt-mp-foot-half">
                                <td class="tt-mp-foot-blank"></td>
                                <td><span class="tt-mp-pill tt-mp-pill-1e">1e</span></td>
                                <td><span class="tt-mp-pill tt-mp-pill-2e">2e</span></td>
                                <td><?php esc_html_e( 'tot', 'talenttrack' ); ?></td>
                            </tr>
                            <tr>
                                <td class="tt-mp-foot-blank"></td>
                                <td data-tt-mp-foot="hl1"><?php echo (int) $half_length; ?></td>
                                <td data-tt-mp-foot="hl2"><?php echo (int) $half_length; ?></td>
                                <td data-tt-mp-foot="hltot"><?php echo (int) ( $half_length * 2 ); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </section>

                <!-- MIDDLE — pitches + tactical goals -->
                <section class="tt-mp-middle">
                    <div class="tt-mp-pitches" data-tt-mp-pitches>
                        <div class="tt-mp-pitch tt-mp-pitch-1e" data-half="1" aria-label="<?php esc_attr_e( 'First half lineup', 'talenttrack' ); ?>">
                            <div class="tt-mp-pitch-label">1e</div>
                            <svg class="tt-mp-pitch-svg" viewBox="0 0 680 750" preserveAspectRatio="none" aria-hidden="true">
                                <rect class="tt-mp-pitch-ln" x="20" y="20" width="640" height="710"/>
                                <line class="tt-mp-pitch-ln" x1="20" y1="375" x2="660" y2="375"/>
                                <circle class="tt-mp-pitch-ln" cx="340" cy="375" r="78"/>
                                <rect class="tt-mp-pitch-ln" x="138.4" y="565" width="403.2" height="165"/>
                                <rect class="tt-mp-pitch-ln" x="248.4" y="675" width="183.2" height="55"/>
                            </svg>
                            <!-- slots rendered by JS -->
                        </div>
                        <div class="tt-mp-copy-col">
                            <button type="button"
                                    class="tt-mp-copy-btn"
                                    data-tt-mp-copy-half
                                    title="<?php esc_attr_e( 'Copy 1st half lineup to 2nd half', 'talenttrack' ); ?>"
                                    aria-label="<?php esc_attr_e( 'Copy 1st half lineup to 2nd half', 'talenttrack' ); ?>">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M5 12h14M13 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </div>
                        <div class="tt-mp-pitch tt-mp-pitch-2e" data-half="2" aria-label="<?php esc_attr_e( 'Second half lineup', 'talenttrack' ); ?>">
                            <div class="tt-mp-pitch-label">2e</div>
                            <svg class="tt-mp-pitch-svg" viewBox="0 0 680 750" preserveAspectRatio="none" aria-hidden="true">
                                <rect class="tt-mp-pitch-ln" x="20" y="20" width="640" height="710"/>
                                <line class="tt-mp-pitch-ln" x1="20" y1="375" x2="660" y2="375"/>
                                <circle class="tt-mp-pitch-ln" cx="340" cy="375" r="78"/>
                                <rect class="tt-mp-pitch-ln" x="138.4" y="565" width="403.2" height="165"/>
                                <rect class="tt-mp-pitch-ln" x="248.4" y="675" width="183.2" height="55"/>
                            </svg>
                        </div>
                    </div>

                    <?php
                    // #2831 — what the academy says this match is working on,
                    // read from the activity rather than picked again here.
                    // Directly above the goals because the goals are written
                    // against it: the principle is the question, the goal
                    // boxes are this match's answer to it.
                    // The empty state always links: this whole view is behind
                    // `tt_edit_activities` (:207), so anyone reading it can
                    // edit the activity. The parameter exists for the read-only
                    // surfaces that share the component.
                    \TT\Modules\Methodology\Frontend\PrinciplePills::renderBlock(
                        $activity_id,
                        __( 'Principles', 'talenttrack' ),
                        true,
                        add_query_arg(
                            [ 'tt_view' => 'activities', 'id' => $activity_id, 'action' => 'edit' ],
                            \TT\Shared\Frontend\Components\RecordLink::dashboardUrl()
                        )
                    );
                    ?>

                    <div class="tt-mp-goals">
                        <h2 class="tt-mp-goals-title"><?php esc_html_e( 'Match goals', 'talenttrack' ); ?></h2>

                        <?php
                        $goal_groups = [
                            'goals_general'         => [ 'label' => __( 'General', 'talenttrack' ),         'cls' => 'tt-mp-gbox-full' ],
                            'goals_attack'          => [ 'label' => __( 'Attacking', 'talenttrack' ),       'cls' => '' ],
                            'goals_defend'          => [ 'label' => __( 'Defending', 'talenttrack' ),       'cls' => '' ],
                            'goals_attack_setpiece' => [ 'label' => __( 'Set pieces (attack)', 'talenttrack' ),  'cls' => '' ],
                            'goals_defend_setpiece' => [ 'label' => __( 'Set pieces (defend)', 'talenttrack' ),  'cls' => '' ],
                        ];

                        $existing_values = [];
                        foreach ( $goal_groups as $field => $_ ) {
                            $existing_values[ $field ] = (string) ( $prep->{$field} ?? '' );
                        }
                        ?>

                        <div class="tt-mp-gbox tt-mp-gbox-full">
                            <div class="tt-mp-gbox-title"><?php echo esc_html( $goal_groups['goals_general']['label'] ); ?></div>
                            <div class="tt-mp-gbox-lines">
                                <?php
                                $lines = self::splitGoalLines( $existing_values['goals_general'], 4 );
                                foreach ( $lines as $i => $val ) :
                                    ?>
                                    <input type="text"
                                           data-tt-mp-goal="goals_general"
                                           data-tt-mp-goal-line="<?php echo (int) $i; ?>"
                                           value="<?php echo esc_attr( $val ); ?>"
                                           placeholder="<?php echo esc_attr( sprintf( __( 'Goal %d…', 'talenttrack' ), $i + 1 ) ); ?>">
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="tt-mp-goals-row">
                            <?php foreach ( [ 'goals_attack', 'goals_defend' ] as $field ) :
                                $lines = self::splitGoalLines( $existing_values[ $field ], 4 );
                                ?>
                                <div class="tt-mp-gbox">
                                    <div class="tt-mp-gbox-title"><?php echo esc_html( $goal_groups[ $field ]['label'] ); ?></div>
                                    <div class="tt-mp-gbox-lines">
                                        <?php foreach ( $lines as $i => $val ) : ?>
                                            <input type="text"
                                                   data-tt-mp-goal="<?php echo esc_attr( $field ); ?>"
                                                   data-tt-mp-goal-line="<?php echo (int) $i; ?>"
                                                   value="<?php echo esc_attr( $val ); ?>"
                                                   placeholder="<?php esc_attr_e( '…', 'talenttrack' ); ?>">
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="tt-mp-goals-row">
                            <?php foreach ( [ 'goals_attack_setpiece', 'goals_defend_setpiece' ] as $field ) :
                                $lines = self::splitGoalLines( $existing_values[ $field ], 4 );
                                ?>
                                <div class="tt-mp-gbox">
                                    <div class="tt-mp-gbox-title"><?php esc_html_e( 'Set pieces', 'talenttrack' ); ?></div>
                                    <div class="tt-mp-gbox-lines">
                                        <?php foreach ( $lines as $i => $val ) : ?>
                                            <input type="text"
                                                   data-tt-mp-goal="<?php echo esc_attr( $field ); ?>"
                                                   data-tt-mp-goal-line="<?php echo (int) $i; ?>"
                                                   value="<?php echo esc_attr( $val ); ?>"
                                                   placeholder="<?php esc_attr_e( '…', 'talenttrack' ); ?>">
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>

                <!-- RIGHT — doen per speler -->
                <?php
                // #3297 — Player focus and Roles are direct children of the
                // grid rather than nested in a `.tt-mp-right` wrapper, so the
                // two layouts can place them independently: on screen they
                // stack in the right rail as before, and on paper Roles moves
                // under Selectie · minuten. That is what frees a full-height
                // column for the fifteen player-goal rows.
                //
                // Placement is explicit in both scopes (see the CSS) rather
                // than relying on source order, because the two orders differ.
                ?>
                <section class="tt-mp-panel tt-mp-focus-panel">
                        <header class="tt-mp-panel-head"><?php esc_html_e( 'Player focus', 'talenttrack' ); ?></header>
                        <table class="tt-mp-dps">
                            <thead>
                                <tr>
                                    <th scope="col"></th>
                                    <th scope="col" class="tt-mp-col-text"></th>
                                    <th scope="col" class="tt-mp-col-spec" title="<?php esc_attr_e( 'Specific goal', 'talenttrack' ); ?>">!</th>
                                    <th scope="col" class="tt-mp-col-cam" title="<?php esc_attr_e( 'Video analyst appointed', 'talenttrack' ); ?>">
                                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 6h11a2 2 0 0 1 2 2v2.2l4-2.4v12.4l-4-2.4V18a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2z"/></svg>
                                    </th>
                                </tr>
                            </thead>
                            <tbody data-tt-mp-dps>
                            <?php if ( empty( $available_ids ) ) : ?>
                                <tr><td colspan="4" class="tt-mp-empty"><?php esc_html_e( 'No availability captured yet.', 'talenttrack' ); ?></td></tr>
                            <?php else :
                                foreach ( $available_ids as $pid ) :
                                    if ( ! isset( $players_by_id[ $pid ] ) ) continue;
                                    $pl   = $players_by_id[ $pid ];
                                    $name = $short_names[ $pid ] ?? QueryHelpers::player_display_name( $pl );
                                    $g    = $pgoals_by_pid[ $pid ] ?? null;
                                    $att  = (string) ( $g->attention_text ?? '' );
                                    $spec = ! empty( $g->is_specific_goal );
                                    $cam  = ! empty( $g->analyst_appointed );
                                    ?>
                                    <tr data-pid="<?php echo (int) $pid; ?>">
                                        <td class="tt-mp-col-name"><?php echo esc_html( $name ); ?></td>
                                        <td class="tt-mp-col-text">
                                            <label class="screen-reader-text" for="tt-mp-att-<?php echo (int) $pid; ?>"><?php esc_html_e( 'Attention text', 'talenttrack' ); ?></label>
                                            <input id="tt-mp-att-<?php echo (int) $pid; ?>"
                                                   type="text"
                                                   data-tt-mp-attention="<?php echo (int) $pid; ?>"
                                                   value="<?php echo esc_attr( $att ); ?>"
                                                   placeholder="<?php esc_attr_e( '…', 'talenttrack' ); ?>">
                                        </td>
                                        <td class="tt-mp-col-spec <?php echo $spec ? 'tt-mp-on' : ''; ?>"
                                            data-tt-mp-spec="<?php echo (int) $pid; ?>"
                                            role="button"
                                            tabindex="0"
                                            aria-pressed="<?php echo $spec ? 'true' : 'false'; ?>"
                                            title="<?php esc_attr_e( 'Specific goal', 'talenttrack' ); ?>">!</td>
                                        <td class="tt-mp-col-cam <?php echo $cam ? 'tt-mp-on' : ''; ?>"
                                            data-tt-mp-cam="<?php echo (int) $pid; ?>"
                                            role="button"
                                            tabindex="0"
                                            aria-pressed="<?php echo $cam ? 'true' : 'false'; ?>"
                                            title="<?php esc_attr_e( 'Video analyst appointed', 'talenttrack' ); ?>">
                                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 6h11a2 2 0 0 1 2 2v2.2l4-2.4v12.4l-4-2.4V18a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2z"/></svg>
                                        </td>
                                    </tr>
                                    <?php
                                endforeach;
                            endif;
                            ?>
                            </tbody>
                        </table>
                </section>

                <section class="tt-mp-panel tt-mp-roles-panel">
                        <header class="tt-mp-panel-head"><?php esc_html_e( 'Roles & set pieces', 'talenttrack' ); ?></header>
                        <ul class="tt-mp-sp-list" data-tt-mp-roles>
                            <?php foreach ( self::roleDefinitions() as $role ) :
                                $key   = (string) $role['key'];
                                $label = (string) $role['label'];
                                $rpid  = (int) ( $roles_by_key[ $key ] ?? 0 );
                                $rname = $rpid > 0 && isset( $players_by_id[ $rpid ] )
                                    ? ( $short_names[ $rpid ] ?? QueryHelpers::player_display_name( $players_by_id[ $rpid ] ) )
                                    : '';
                                $filled = $rpid > 0;
                                ?>
                                <li class="tt-mp-sp-row"
                                    data-tt-mp-role="<?php echo esc_attr( $key ); ?>"
                                    data-pid="<?php echo (int) $rpid; ?>"
                                    role="button"
                                    tabindex="0">
                                    <span class="tt-mp-sp-label"><?php echo esc_html( $label ); ?></span>
                                    <span class="tt-mp-sp-pick <?php echo $filled ? 'tt-mp-filled' : ''; ?>">
                                        <span class="tt-mp-sp-name"><?php echo $filled ? esc_html( $rname ) : esc_html__( '— Pick player —', 'talenttrack' ); ?></span>
                                        <?php if ( $filled ) : ?>
                                            <button type="button"
                                                    class="tt-mp-sp-clear"
                                                    data-tt-mp-clear-role="<?php echo esc_attr( $key ); ?>"
                                                    aria-label="<?php esc_attr_e( 'Clear role', 'talenttrack' ); ?>"
                                                    title="<?php esc_attr_e( 'Clear role', 'talenttrack' ); ?>">×</button>
                                        <?php endif; ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                </section>
            </div>
            </div><!-- /.tt-mp-sheet -->

            <!-- Picker (slot + role) -->
            <div class="tt-mp-picker-backdrop" data-tt-mp-picker-backdrop hidden></div>
            <div class="tt-mp-picker" data-tt-mp-picker hidden role="dialog" aria-modal="true"></div>

            <!-- Availability drawer -->
            <div class="tt-mp-drawer-backdrop" data-tt-mp-drawer-backdrop hidden></div>
            <aside class="tt-mp-drawer" data-tt-mp-drawer aria-hidden="true">
                <header class="tt-mp-drawer-head">
                    <h3><?php esc_html_e( 'Availability', 'talenttrack' ); ?></h3>
                    <button type="button" class="tt-mp-drawer-x" data-tt-mp-drawer-close aria-label="<?php esc_attr_e( 'Close', 'talenttrack' ); ?>">×</button>
                </header>
                <div class="tt-mp-drawer-body" data-tt-mp-drawer-body></div>
                <footer class="tt-mp-drawer-foot">
                    <button type="button" class="tt-btn tt-btn-secondary" data-tt-mp-mark-all-present><?php esc_html_e( 'Mark all present', 'talenttrack' ); ?></button>
                    <button type="button" class="tt-btn tt-btn-primary" data-tt-mp-drawer-done><?php esc_html_e( 'Done', 'talenttrack' ); ?></button>
                </footer>
            </aside>
        </section>
        <?php

        // #2099 — per-template slot layouts (from slots_json) for templates
        // whose geometry differs from their shape default (e.g. the 3-4-3
        // diamond). The JS prefers the bound template's layout, falling back
        // to the shape-keyed `slot_layouts` when a template has none.
        $template_layouts = [];
        foreach ( $formations as $f ) {
            $fid = (int) ( $f->id ?? 0 );
            if ( $fid <= 0 ) continue;
            $layout = self::templateSlotLayout( $fid );
            if ( $layout !== null ) {
                $template_layouts[ $fid ] = $layout;
            }
        }

        // Bootstrap data for the JS — players, formations, current state.
        $bootstrap = [
            'activity_id'    => (int) $activity_id,
            'prep_id'        => (int) $prep_id,
            'half_length'    => (int) $half_length,
            'formation_shape' => $formation_shape,
            'formation_template_id' => (int) ( $prep->formation_template_id ?? 0 ),
            'slot_layouts'   => self::defaultSlotLayouts(),
            'template_layouts' => $template_layouts,
            'roles'          => array_map( static function( array $r ): array {
                return [ 'key' => $r['key'], 'label' => $r['label'] ];
            }, self::roleDefinitions() ),
            'players'        => array_map( static function( $p ) use ( $short_names ): array {
                $pid = (int) ( $p->id ?? 0 );
                return [
                    'id'    => $pid,
                    'name'  => $short_names[ $pid ] ?? QueryHelpers::player_display_name( $p ),
                    // Full name kept on the payload so the availability
                    // drawer can show the long form if a future variant
                    // wants it; current renderers use `name`.
                    'full'  => QueryHelpers::player_display_name( $p ),
                ];
            }, $roster_list ),
            'availability'   => (object) $availability_by_pid,
            'lineup'         => [
                '1' => (object) $lineup_by_half[1],
                '2' => (object) $lineup_by_half[2],
            ],
            'roles_assigned' => (object) $roles_by_key,
            'attention'      => self::projectAttention( $pgoals_by_pid ),
            'specific'       => self::projectFlag( $pgoals_by_pid, 'is_specific_goal' ),
            'analyst'        => self::projectFlag( $pgoals_by_pid, 'analyst_appointed' ),
            'cancel_url'     => $back_to_activity,
        ];
        ?>
        <script type="application/json" id="tt-match-prep-bootstrap"><?php
            echo wp_json_encode( $bootstrap );
        ?></script>
        <?php
    }

    private static function enqueueViewAssets( int $prep_id, int $activity_id ): void {
        wp_enqueue_style(
            'tt-match-prep',
            TT_PLUGIN_URL . 'assets/css/frontend-match-prep.css',
            [],
            TT_VERSION
        );
        // #1695 — 2026 "chrome" visual layer. Loads after the base sheet
        // and after the shared app-chrome (which carries the KPI tile rules
        // and the brand tokens this layer reads).
        wp_enqueue_style(
            'tt-match-prep-2026',
            TT_PLUGIN_URL . 'assets/css/frontend-match-prep-2026.css',
            [ 'tt-frontend-app-chrome', 'tt-match-prep' ],
            TT_VERSION
        );
        // #2831 — the O/A/V principle pill, shared with the activity detail
        // card and the printed team sheet.
        \TT\Modules\Methodology\Frontend\PrinciplePills::enqueue();
        // #3004 — the shared autosave component. Declared as a dependency
        // rather than merely enqueued alongside, so `TT.Autosave` is defined
        // before this file's `create()` call runs.
        \TT\Shared\Frontend\Components\SaveState::enqueue();
        wp_enqueue_script(
            'tt-match-prep',
            TT_PLUGIN_URL . 'assets/js/frontend-match-prep.js',
            [ 'tt-autosave' ],
            TT_VERSION,
            true
        );
        wp_localize_script( 'tt-match-prep', 'TT_MATCH_PREP', [
            'rest_url'   => esc_url_raw( rest_url( 'talenttrack/v1/match-prep/' ) ),
            'rest_nonce' => wp_create_nonce( 'wp_rest' ),
            'prep_id'    => (int) $prep_id,
            'activity_id' => (int) $activity_id,
            'i18n'       => [
                'search'          => __( 'Search player…', 'talenttrack' ),
                'no_players'      => __( 'No available players found.', 'talenttrack' ),
                'clear'           => __( 'Clear', 'talenttrack' ),
                'on_pitch'        => __( 'on pitch', 'talenttrack' ),
                'slot_label'      => __( 'Slot %1$s — %2$s half', 'talenttrack' ),
                'half_1'          => __( '1st', 'talenttrack' ),
                'half_2'          => __( '2nd', 'talenttrack' ),
                'present'         => __( 'Present', 'talenttrack' ),
                'absent_excused'  => __( 'Absent (excused)', 'talenttrack' ),
                'absent_injured'  => __( 'Injured', 'talenttrack' ),
                'reason'          => __( 'Reason (optional)…', 'talenttrack' ),
                'pick_player'     => __( '— Pick player —', 'talenttrack' ),
                'pick_for_role'   => __( 'Pick player for role', 'talenttrack' ),
            ],
        ] );

        // #2102 — image-capture export of the live grid. Loaded only when
        // the PDF export sub-feature is on (the toolbar button is gated by
        // the same flag). html2canvas + jsPDF are lazy-loaded by the module
        // itself on first click, so nothing heavy weighs on initial load.
        if ( \TT\Core\FeatureRegistry::isEnabled( 'export_match_prep_pdf' ) ) {
            wp_enqueue_script(
                'tt-image-pdf',
                TT_PLUGIN_URL . 'assets/js/tt-image-pdf.js',
                [],
                TT_VERSION,
                true
            );
            wp_localize_script( 'tt-image-pdf', 'TT_IMAGE_PDF', [
                'vendor' => [
                    'html2canvas' => TT_PLUGIN_URL . 'assets/js/vendor/html2canvas.min.js',
                    'jspdf'       => TT_PLUGIN_URL . 'assets/js/vendor/jspdf.umd.min.js',
                ],
                'i18n' => [
                    'working' => __( 'Preparing PDF…', 'talenttrack' ),
                    'failed'  => __( 'Could not generate the PDF. Use Print instead.', 'talenttrack' ),
                ],
            ] );
        }
    }

    /**
     * The header band that names the match on the exported sheet.
     *
     * #3272 — the PDF captured `.tt-mp-grid` alone, and the title, the KPI
     * strip and the formation all live outside it. So the sheet a coach
     * carried to the touchline never said who was playing whom, or when. It
     * is rendered inside the captured wrapper and shown only in the capture
     * (`.tt-image-pdf-capture`), because on screen that information is
     * already above the grid and repeating it would be noise.
     *
     * Everything here is best-effort: an activity with no opponent, kick-off
     * or venue recorded simply carries fewer facts, and each line is dropped
     * rather than printed empty. The one thing always present is the team's
     * own identity, which is what makes two sheets on a table tellable apart.
     *
     * @param array<string,int> $summary from summaryCounts()
     */
    private static function renderPaperHead(
        object $activity,
        string $formation_shape,
        int $half_length,
        array $summary
    ): void {
        $team     = QueryHelpers::get_team( (int) ( $activity->team_id ?? 0 ) );
        $our_name = (string) ( $team->name ?? '' );
        $opponent = trim( (string) ( $activity->opponent ?? '' ) );
        $home     = strtolower( (string) ( $activity->home_away ?? '' ) ) === 'home';

        // Home team on the left, as a fixture is always written.
        if ( $opponent !== '' && $our_name !== '' ) {
            $left  = $home ? $our_name : $opponent;
            $right = $home ? $opponent : $our_name;
        } else {
            $left  = $our_name !== '' ? $our_name : (string) ( $activity->title ?? '' );
            $right = $opponent;
        }

        $date    = (string) ( $activity->session_date ?? '' );
        $kickoff = trim( (string) ( $activity->kickoff_time ?? $activity->start_time ?? '' ) );
        $venue   = trim( (string) ( $activity->location ?? '' ) );

        $when = [];
        if ( $date !== '' ) {
            $when[] = \TT\Shared\Dates\TTDate::date( $date );
        }
        if ( $kickoff !== '' ) {
            $when[] = substr( $kickoff, 0, 5 );
        }
        if ( $our_name !== '' && $opponent !== '' ) {
            $when[] = $home ? __( 'Home', 'talenttrack' ) : __( 'Away', 'talenttrack' );
        }
        if ( $venue !== '' ) {
            $when[] = $venue;
        }

        $stats = [
            [ __( 'Formation', 'talenttrack' ), $formation_shape !== '' ? $formation_shape : '—' ],
            [
                __( 'Half', 'talenttrack' ),
                sprintf(
                    /* translators: %d: length of one half, in minutes. */
                    __( '%d min', 'talenttrack' ),
                    $half_length
                ),
            ],
            [
                __( 'Available', 'talenttrack' ),
                sprintf( '%d/%d', (int) ( $summary['available'] ?? 0 ), (int) ( $summary['roster'] ?? 0 ) ),
            ],
        ];
        ?>
        <header class="tt-mp-paperhead" aria-hidden="true">
            <div class="tt-mp-paperhead__id">
                <p class="tt-mp-paperhead__fixture">
                    <?php echo esc_html( $left ); ?>
                    <?php if ( $right !== '' ) : ?>
                        <span class="tt-mp-paperhead__vs">—</span><?php echo esc_html( $right ); ?>
                    <?php endif; ?>
                </p>
                <?php if ( ! empty( $when ) ) : ?>
                    <p class="tt-mp-paperhead__when"><?php echo esc_html( implode( ' · ', $when ) ); ?></p>
                <?php endif; ?>
            </div>
            <div class="tt-mp-paperhead__stats">
                <?php foreach ( $stats as [ $k, $v ] ) : ?>
                    <div class="tt-mp-paperstat">
                        <span class="tt-mp-paperstat__k"><?php echo esc_html( $k ); ?></span>
                        <span class="tt-mp-paperstat__v"><?php echo esc_html( (string) $v ); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </header>
        <?php
    }

    /**
     * #3272 — the fixture columns come along now.
     *
     * The exported sheet has to say who is playing whom, when and where. All
     * four live on the activity and none of them were selected, which is why
     * the PDF could be printed, carried to a touchline and not identify its
     * own match.
     */
    private static function loadActivity( int $activity_id ): ?object {
        global $wpdb;
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT id, team_id, title, session_date, activity_type_key,
                    opponent, home_away, kickoff_time, start_time, location
               FROM {$wpdb->prefix}tt_activities
              WHERE id = %d AND club_id = %d",
            $activity_id, CurrentClub::id()
        ) );
        return $row ?: null;
    }

    /**
     * @return array<int, object> id => player
     *
     * v4.5.1 — query `tt_players.team_id` directly. The pre-fix
     * query joined through a non-existent `tt_team_players` junction
     * table; `tt_players.team_id` is the canonical FK across the
     * codebase. Same defect class as AvailabilityStep::rosterForActivity().
     */
    private static function loadTeamRosterById( int $team_id ): array {
        if ( $team_id <= 0 ) return [];
        global $wpdb;
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT pl.*
               FROM {$wpdb->prefix}tt_players pl
              WHERE pl.team_id = %d AND pl.club_id = %d AND pl.archived_at IS NULL",
            $team_id, CurrentClub::id()
        ) );
        $out = [];
        foreach ( (array) $rows as $r ) {
            $out[ (int) $r->id ] = $r;
        }
        return $out;
    }

    /**
     * @param array<int, object> $players_by_id
     * @return list<object>
     */
    private static function sortRoster( array $players_by_id ): array {
        $out = array_values( $players_by_id );
        usort( $out, static function( $a, $b ) {
            $la = strtolower( (string) ( $a->last_name ?? '' ) );
            $lb = strtolower( (string) ( $b->last_name ?? '' ) );
            if ( $la === $lb ) {
                return strcmp(
                    strtolower( (string) ( $a->first_name ?? '' ) ),
                    strtolower( (string) ( $b->first_name ?? '' ) )
                );
            }
            return strcmp( $la, $lb );
        } );
        return $out;
    }

    /** @return list<object> */
    private static function listFormationTemplates(): array {
        global $wpdb;
        $table = $wpdb->prefix . 'tt_formation_templates';
        // Defensive: not every install has migration 0032 applied yet.
        $exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );
        if ( ! $exists ) return [];
        $rows = $wpdb->get_results(
            "SELECT id, name, formation_shape
               FROM {$table}
              WHERE archived_at IS NULL
              ORDER BY is_seeded DESC, formation_shape ASC, name ASC"
        );
        return is_array( $rows ) ? $rows : [];
    }

    /**
     * Resolve the formation shape string for the JS layer.
     *
     * Picks the template's shape if a template is bound; otherwise
     * falls back to the spreadsheet default `4-2-3-1`.
     *
     * @param list<object> $formations
     */
    private static function resolveFormationShape( int $template_id, array $formations ): string {
        if ( $template_id > 0 ) {
            foreach ( $formations as $f ) {
                if ( (int) ( $f->id ?? 0 ) === $template_id ) {
                    $shape = (string) ( $f->formation_shape ?? '' );
                    if ( $shape !== '' ) return $shape;
                }
            }
        }
        return '4-2-3-1';
    }

    /**
     * Split a stored multi-line goal string into a fixed number of
     * input slots. Existing line breaks (\n) act as separators so the
     * v1 textarea-saved data renders without surprise; new entries
     * concatenate the 4 inputs on save in JS.
     *
     * @return list<string>
     */
    private static function splitGoalLines( string $value, int $count ): array {
        $value = (string) $value;
        $lines = $value === '' ? [] : preg_split( "/\r\n|\n|\r/", $value );
        if ( ! is_array( $lines ) ) $lines = [];
        $lines = array_values( array_map( 'strval', $lines ) );
        while ( count( $lines ) < $count ) $lines[] = '';
        return array_slice( $lines, 0, $count );
    }

    /**
     * Aggregate the already-loaded match-prep state into the handful of
     * numbers the KPI strip surfaces. Pure aggregation — every value is
     * derived from data the render method already fetched (no query, no
     * eligibility decision), so this stays SaaS-portable (CLAUDE.md §4)
     * and the strip is render-only polish.
     *
     * @param array<int,array{status:string,reason:string}> $availability_by_pid
     * @param array<int,int>                                $lineup_half_1 slot => player id
     * @return array{available:int,unavailable:int,starters:int,roster:int}
     */
    private static function summaryCounts( array $availability_by_pid, array $lineup_half_1, int $roster_total ): array {
        $available   = 0;
        $unavailable = 0;
        foreach ( $availability_by_pid as $row ) {
            if ( strcasecmp( (string) ( $row['status'] ?? '' ), 'Present' ) === 0 ) {
                $available++;
            } else {
                $unavailable++;
            }
        }
        $starters = 0;
        foreach ( $lineup_half_1 as $pid ) {
            if ( (int) $pid > 0 ) {
                $starters++;
            }
        }
        return [
            'available'   => $available,
            'unavailable' => $unavailable,
            'starters'    => $starters,
            'roster'      => $roster_total,
        ];
    }

    /**
     * @param array<int, object> $pgoals_by_pid
     * @return object
     */
    private static function projectAttention( array $pgoals_by_pid ): object {
        $out = [];
        foreach ( $pgoals_by_pid as $pid => $g ) {
            $out[ (string) (int) $pid ] = (string) ( $g->attention_text ?? '' );
        }
        return (object) $out;
    }

    /**
     * @param array<int, object> $pgoals_by_pid
     * @return object
     */
    private static function projectFlag( array $pgoals_by_pid, string $field ): object {
        $out = [];
        foreach ( $pgoals_by_pid as $pid => $g ) {
            if ( ! empty( $g->{$field} ) ) {
                $out[ (string) (int) $pid ] = true;
            }
        }
        return (object) $out;
    }
}
