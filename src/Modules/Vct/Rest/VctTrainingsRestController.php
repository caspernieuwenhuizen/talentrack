<?php
namespace TT\Modules\Vct\Rest;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Domain\Vocabularies\Lookups\ActivityTypeKey;
use TT\Infrastructure\REST\RestResponse;
use TT\Infrastructure\Security\AuthorizationService;
use TT\Infrastructure\Tenancy\CurrentClub;
use TT\Modules\Vct\Repositories\VctSessionBlocksRepository;
use TT\Modules\Vct\Repositories\VctSessionsRepository;
use TT\Modules\Vct\Rules\AgeAdmissibilityRule;
use TT\Modules\Vct\Rules\ExerciseSelectionPass;
use TT\Modules\Vct\Rules\FinalizationPass;
use TT\Modules\Vct\Rules\MdContextRule;
use TT\Modules\Vct\Rules\ProgressionRule;
use TT\Modules\Vct\Rules\Providers\NativeActivitiesReader;
use TT\Modules\Vct\Rules\Providers\NativeRecentPicksProvider;
use TT\Modules\Vct\Rules\RecoveryRule;
use TT\Modules\Vct\Rules\RulesEngine;
use TT\Modules\Vct\Rules\SessionCompositionRule;
use TT\Modules\Vct\Rules\SessionPlanContext;
use TT\Modules\Vct\Rules\TacticalThemeRule;
use TT\Modules\Vct\Rules\WorkloadCapRule;
use TT\Modules\Vct\Repositories\VctAgeProfilesRepository;
use TT\Modules\Vct\Repositories\VctExercisesRepository;
use TT\Modules\Vct\Repositories\VctMacroBlocksRepository;
use TT\Modules\Vct\Repositories\VctPhvFlagsRepository;
use TT\Modules\Vct\Repositories\VctSessionTemplatesRepository;
use TT\Modules\Vct\Repositories\VctTeamSchedulesRepository;
use TT\Modules\Vct\Services\VctTrainingComposer;

/**
 * VctTrainingsRestController — /wp-json/talenttrack/v1/vct/sessions[...]
 *
 * Spec calls this `VctSessions…Controller`; renamed because the
 * legacy `Session…RestController` substring is banned under the
 * #0035 no-regression linter. Functionally the same — owns the five
 * VCT-training routes (URL paths still use `/vct/sessions/` since
 * they're public API contract).
 *
 * Five routes:
 *   GET    /vct/sessions?team_id=N&from=...&to=...
 *   GET    /vct/sessions/{id}
 *   POST   /vct/sessions/generate          → RulesEngine::compose()
 *   PATCH  /vct/sessions/{id}              → RulesEngine::validate()
 *   POST   /vct/sessions/{id}/publish      → bind/create Activity
 *
 * Two-layer permission_callback (spec architecture review H1):
 *   layer 1 — cap: `tt_vct_plan` (matrix-aware via userCanOrMatrix)
 *   layer 2 — scope: `canPlanForTeam( $uid, $team_id, $activity )`
 *
 * Validation envelope on rules-engine failures:
 *   400 { error: { code: 'vct_validation', reasons: [ {code, details} ] } }
 *
 * `publish` race-condition fallback: 409 conflict_existing_activity
 * + existing activity payload (the spec's architecture review H3
 * fallback that sidesteps the cross-module UNIQUE-index ask).
 */
class VctTrainingsRestController {

    private const NS = 'talenttrack/v1';

    public static function init(): void {
        add_action( 'rest_api_init', [ __CLASS__, 'register' ] );
    }

    public static function register(): void {
        register_rest_route( self::NS, '/vct/sessions', [
            [
                'methods'             => 'GET',
                'callback'            => [ __CLASS__, 'listForTeam' ],
                'permission_callback' => [ __CLASS__, 'can_read_team' ],
            ],
        ] );

        register_rest_route( self::NS, '/vct/sessions/generate', [
            [
                'methods'             => 'POST',
                'callback'            => [ __CLASS__, 'generate' ],
                'permission_callback' => [ __CLASS__, 'can_write_team' ],
            ],
        ] );

        register_rest_route( self::NS, '/vct/sessions/(?P<id>\d+)', [
            [
                'methods'             => 'GET',
                'callback'            => [ __CLASS__, 'find' ],
                'permission_callback' => [ __CLASS__, 'can_read_row' ],
            ],
            [
                'methods'             => 'PATCH',
                'callback'            => [ __CLASS__, 'patch' ],
                'permission_callback' => [ __CLASS__, 'can_write_row' ],
            ],
        ] );

        register_rest_route( self::NS, '/vct/sessions/(?P<id>\d+)/publish', [
            [
                'methods'             => 'POST',
                'callback'            => [ __CLASS__, 'publish' ],
                'permission_callback' => [ __CLASS__, 'can_write_row' ],
            ],
        ] );
    }

    // Permission callbacks ---------------------------------------------

    public static function can_read_team( \WP_REST_Request $r ): bool {
        $uid = get_current_user_id();
        if ( ! AuthorizationService::userCanOrMatrix( $uid, 'tt_vct_plan' ) ) return false;
        $team_id = absint( $r->get_param( 'team_id' ) );
        return $team_id > 0 && AuthorizationService::canPlanForTeam( $uid, $team_id, 'read' );
    }

    public static function can_write_team( \WP_REST_Request $r ): bool {
        $uid = get_current_user_id();
        if ( ! AuthorizationService::userCanOrMatrix( $uid, 'tt_vct_plan' ) ) return false;
        $team_id = (int) ( $r->get_param( 'team_id' ) ?? 0 );
        return $team_id > 0 && AuthorizationService::canPlanForTeam( $uid, $team_id, 'create_delete' );
    }

    public static function can_read_row( \WP_REST_Request $r ): bool {
        $uid = get_current_user_id();
        if ( ! AuthorizationService::userCanOrMatrix( $uid, 'tt_vct_plan' ) ) return false;
        $row = ( new VctSessionsRepository() )->find( (int) $r->get_param( 'id' ) );
        if ( $row === null ) return false;
        return AuthorizationService::canPlanForTeam( $uid, (int) $row['team_id'], 'read' );
    }

    public static function can_write_row( \WP_REST_Request $r ): bool {
        $uid = get_current_user_id();
        if ( ! AuthorizationService::userCanOrMatrix( $uid, 'tt_vct_plan' ) ) return false;
        $row = ( new VctSessionsRepository() )->find( (int) $r->get_param( 'id' ) );
        if ( $row === null ) return false;
        return AuthorizationService::canPlanForTeam( $uid, (int) $row['team_id'], 'change' );
    }

    // Handlers ---------------------------------------------------------

    public static function listForTeam( \WP_REST_Request $r ): \WP_REST_Response {
        $team_id = absint( $r->get_param( 'team_id' ) );
        $status  = $r->get_param( 'status' );
        $rows = ( new VctSessionsRepository() )->listForTeam(
            $team_id,
            is_string( $status ) && $status !== '' ? $status : null,
            (int) ( $r->get_param( 'limit' ) ?? 50 )
        );
        return RestResponse::success( [ 'sessions' => $rows ] );
    }

    public static function find( \WP_REST_Request $r ): \WP_REST_Response {
        $id = (int) $r->get_param( 'id' );
        $session = ( new VctSessionsRepository() )->find( $id );
        if ( $session === null ) return RestResponse::error( 'not_found', __( 'VCT training not found.', 'talenttrack' ), 404 );
        $session['blocks'] = ( new VctSessionBlocksRepository() )->listForSession( $id );
        return RestResponse::success( [ 'session' => $session ] );
    }

    public static function generate( \WP_REST_Request $r ): \WP_REST_Response {
        $payload = [
            'team_id'                    => (int) ( $r->get_param( 'team_id' ) ?? 0 ),
            'season_id'                  => (int) ( $r->get_param( 'season_id' ) ?? 0 ),
            'age_group'                  => (string) ( $r->get_param( 'age_group' ) ?? '' ),
            'session_date'               => (string) ( $r->get_param( 'session_date' ) ?? '' ),
            'start_time'                 => $r->get_param( 'start_time' ),
            'tactical_theme'             => $r->get_param( 'tactical_theme' ),
            'roster_player_ids'          => (array) ( $r->get_param( 'roster_player_ids' ) ?? [] ),
            'requested_duration_minutes' => $r->get_param( 'requested_duration_minutes' ),
            'generated_by'               => get_current_user_id(),
        ];

        $composer = self::makeComposer();
        $result = $composer->generate( $payload );
        if ( $result === null ) {
            // Re-run compose to surface the blocking reasons.
            $ctx_for_reasons = self::buildContextFromPayload( $payload );
            $ctx_for_reasons = self::makeEngine()->compose( $ctx_for_reasons );
            $reasons = [];
            foreach ( $ctx_for_reasons->warnings as $w ) {
                if ( ( $w['severity'] ?? '' ) === 'block' ) {
                    $reasons[] = [ 'code' => $w['code'], 'details' => $w['details'] ];
                }
            }
            return new \WP_REST_Response( [
                'error' => [
                    'code'    => 'vct_validation',
                    'reasons' => $reasons,
                ],
            ], 400 );
        }
        return RestResponse::success( $result );
    }

    public static function patch( \WP_REST_Request $r ): \WP_REST_Response {
        $id = (int) $r->get_param( 'id' );
        $sessions = new VctSessionsRepository();
        $session = $sessions->find( $id );
        if ( $session === null ) return RestResponse::error( 'not_found', __( 'VCT training not found.', 'talenttrack' ), 404 );

        $blocks_repo = new VctSessionBlocksRepository();

        // Optional block patches from the payload.
        $block_patches = (array) ( $r->get_param( 'blocks' ) ?? [] );
        foreach ( $block_patches as $bp ) {
            if ( ! is_array( $bp ) ) continue;
            $bid = (int) ( $bp['id'] ?? 0 );
            if ( $bid <= 0 ) continue;
            $blocks_repo->updateBlock( $bid, $bp );
        }

        // Re-validate against current rules.
        $ctx = self::buildContextFromSession( $session, $blocks_repo->listForSession( $id ) );
        $result = self::makeEngine()->validate( $ctx );
        if ( ! $result->passes ) {
            return new \WP_REST_Response( [
                'error' => [
                    'code'    => 'vct_validation',
                    'reasons' => $result->blockingReasons(),
                ],
            ], 400 );
        }

        $sessions->updateTotalLoad( $id, $result->total_load );
        $session = $sessions->find( $id );
        $session['blocks']   = $blocks_repo->listForSession( $id );
        $session['warnings'] = $result->warnings;
        return RestResponse::success( [ 'session' => $session ] );
    }

    public static function publish( \WP_REST_Request $r ): \WP_REST_Response {
        $id = (int) $r->get_param( 'id' );
        $sessions = new VctSessionsRepository();
        $session = $sessions->find( $id );
        if ( $session === null ) return RestResponse::error( 'not_found', __( 'VCT training not found.', 'talenttrack' ), 404 );
        if ( $session['status'] !== 'draft' ) {
            return RestResponse::error( 'already_published',
                __( 'Only draft trainings can be published.', 'talenttrack' ), 409 );
        }

        // Conflict check: existing Activity at the same slot?
        $existing = self::findActivityForSlot( $session );
        $bind_existing = (bool) ( $r->get_param( 'bind_existing' ) ?? false );
        if ( $existing !== null && ! $bind_existing ) {
            return new \WP_REST_Response( [
                'error' => [
                    'code'              => 'conflict_existing_activity',
                    'existing_activity' => $existing,
                ],
            ], 409 );
        }

        $activity_id = $existing !== null ? (int) $existing['id'] : self::createActivityForSession( $session );
        if ( $activity_id <= 0 ) {
            return RestResponse::error( 'activity_create_failed',
                __( 'Could not create the bound Activity.', 'talenttrack' ), 500 );
        }

        $sessions->updateStatus( $id, 'published', $activity_id );
        $session = $sessions->find( $id );
        $session['blocks'] = ( new VctSessionBlocksRepository() )->listForSession( $id );
        return RestResponse::success( [ 'session' => $session, 'activity_id' => $activity_id ] );
    }

    // Helpers ----------------------------------------------------------

    private static function makeEngine(): RulesEngine {
        $age_profiles = new VctAgeProfilesRepository();
        return new RulesEngine(
            new AgeAdmissibilityRule( $age_profiles ),
            new MdContextRule( new NativeActivitiesReader(), new VctTeamSchedulesRepository() ),
            new SessionCompositionRule( new VctSessionTemplatesRepository() ),
            new TacticalThemeRule(),
            new ProgressionRule( new VctMacroBlocksRepository() ),
            new ExerciseSelectionPass( new VctExercisesRepository(), new NativeRecentPicksProvider() ),
            new WorkloadCapRule( new VctPhvFlagsRepository() ),
            new RecoveryRule( new \TT\Modules\Vct\Repositories\VctWorkloadSnapshotsRepository() ),
            new FinalizationPass()
        );
    }

    private static function makeComposer(): VctTrainingComposer {
        return new VctTrainingComposer(
            self::makeEngine(),
            new VctSessionsRepository(),
            new VctSessionBlocksRepository()
        );
    }

    /** @param array<string,mixed> $payload */
    private static function buildContextFromPayload( array $payload ): SessionPlanContext {
        $ctx = new SessionPlanContext();
        $ctx->team_id        = (int)    ( $payload['team_id']        ?? 0 );
        $ctx->season_id      = (int)    ( $payload['season_id']      ?? 0 );
        $ctx->age_group      = (string) ( $payload['age_group']      ?? 'U10' );
        $ctx->session_date   = (string) ( $payload['session_date']   ?? '' );
        $ctx->tactical_theme = isset( $payload['tactical_theme'] ) && $payload['tactical_theme'] !== ''
            ? (string) $payload['tactical_theme'] : null;
        $ctx->start_time     = isset( $payload['start_time'] ) && $payload['start_time'] !== ''
            ? (string) $payload['start_time'] : null;
        $ctx->roster_player_ids = array_values( array_map( 'intval', (array) ( $payload['roster_player_ids'] ?? [] ) ) );
        $ctx->generated_by   = (int)    ( $payload['generated_by']   ?? get_current_user_id() );
        if ( isset( $payload['requested_duration_minutes'] ) ) {
            $ctx->requested_duration_minutes = (int) $payload['requested_duration_minutes'];
        }
        return $ctx;
    }

    /**
     * @param array<string,mixed> $session
     * @param list<array<string,mixed>> $blocks
     */
    private static function buildContextFromSession( array $session, array $blocks ): SessionPlanContext {
        $ctx = new SessionPlanContext();
        $ctx->team_id        = (int)    $session['team_id'];
        $ctx->age_group      = (string) $session['age_group'];
        $ctx->session_date   = (string) $session['session_date'];
        $ctx->start_time     = $session['start_time'] !== null ? (string) $session['start_time'] : null;
        $ctx->tactical_theme = $session['tactical_theme'] !== null ? (string) $session['tactical_theme'] : null;
        $ctx->blocks         = $blocks;
        return $ctx;
    }

    /**
     * Look for an existing Activity in the same (team_id, date, start_time)
     * slot. Returns the activity row when found; null when free.
     *
     * @return array<string,mixed>|null
     */
    private static function findActivityForSlot( array $session ): ?array {
        global $wpdb;
        $activities = $wpdb->prefix . 'tt_activities';
        $sql = "SELECT id, session_date, start_time, activity_type_key, location FROM {$activities}
                 WHERE club_id = %d AND team_id = %d AND session_date = %s AND archived_at IS NULL";
        $params = [ CurrentClub::id(), (int) $session['team_id'], (string) $session['session_date'] ];
        if ( ! empty( $session['start_time'] ) ) {
            $sql .= ' AND start_time = %s';
            $params[] = (string) $session['start_time'];
        } else {
            $sql .= ' AND start_time IS NULL';
        }
        $sql .= ' AND activity_type_key = %s LIMIT 1';
        $params[] = ActivityTypeKey::TRAINING;

        $row = $wpdb->get_row( $wpdb->prepare( $sql, $params ), ARRAY_A );
        return $row !== null ? (array) $row : null;
    }

    /**
     * Create a new Activity row mirroring the published VCT session.
     * Returns the new id, or 0 on failure.
     */
    private static function createActivityForSession( array $session ): int {
        global $wpdb;
        $activities = $wpdb->prefix . 'tt_activities';
        $ok = $wpdb->insert( $activities, [
            'club_id'       => CurrentClub::id(),
            'team_id'       => (int) $session['team_id'],
            'session_date'  => (string) $session['session_date'],
            'start_time'    => $session['start_time'] ?? null,
            'activity_type_key' => ActivityTypeKey::TRAINING,
            'title'         => sprintf(
                /* translators: 1: age group, 2: md context label */
                __( 'VCT training — %1$s (%2$s)', 'talenttrack' ),
                (string) $session['age_group'],
                (string) $session['md_context']
            ),
        ] );
        return $ok !== false ? (int) $wpdb->insert_id : 0;
    }
}
