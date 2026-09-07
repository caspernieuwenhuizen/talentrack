<?php
namespace TT\Modules\Vct\Rest;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Infrastructure\REST\RestResponse;
use TT\Infrastructure\Security\AuthorizationService;
use TT\Modules\Vct\Repositories\VctCycleWeekOverridesRepository;
use TT\Modules\Vct\Repositories\VctTeamCyclesRepository;
use TT\Modules\Vct\Validation\VctTeamCycleValidator;

/**
 * VctTeamCyclesRestController — per-team repeating cycles (#3360, epic
 * #3354).
 *
 *   GET    /vct/team-cycles?season_id=N            every team's cycle
 *   PUT    /vct/team-cycles?season_id=N&team_id=M  set one
 *   DELETE /vct/team-cycles?season_id=N&team_id=M  remove one
 *
 * Caps: `tt_vct_admin_config`, the same gate the VCT configuration tile
 * uses — a cycle decides how hard a team is worked week by week, so it
 * belongs with the head of development rather than with general
 * administration.
 *
 * Validation lives in the shared `VctTeamCycleValidator`, so this
 * endpoint and the configuration form refuse the same things.
 */
class VctTeamCyclesRestController {

    private const NS = 'talenttrack/v1';

    public static function init(): void {
        add_action( 'rest_api_init', [ __CLASS__, 'register' ] );
    }

    public static function register(): void {
        register_rest_route( self::NS, '/vct/team-cycles', [
            [
                'methods'             => 'GET',
                'callback'            => [ __CLASS__, 'listForSeason' ],
                'permission_callback' => [ __CLASS__, 'can_admin' ],
            ],
            [
                'methods'             => 'PUT',
                'callback'            => [ __CLASS__, 'save' ],
                'permission_callback' => [ __CLASS__, 'can_admin' ],
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [ __CLASS__, 'remove' ],
                'permission_callback' => [ __CLASS__, 'can_admin' ],
            ],
        ] );
    }

    public static function can_admin(): bool {
        return AuthorizationService::userCanOrMatrix( get_current_user_id(), 'tt_vct_admin_config' );
    }

    public static function listForSeason( \WP_REST_Request $r ): \WP_REST_Response {
        $season_id = (int) ( $r->get_param( 'season_id' ) ?? 0 );
        if ( $season_id <= 0 ) {
            return RestResponse::error( 'bad_request', __( 'season_id is required.', 'talenttrack' ), 400 );
        }

        return RestResponse::success( [
            'cycles' => array_values( ( new VctTeamCyclesRepository() )->listForSeason( $season_id ) ),
        ] );
    }

    public static function save( \WP_REST_Request $r ): \WP_REST_Response {
        $season_id = (int) ( $r->get_param( 'season_id' ) ?? 0 );
        $team_id   = (int) ( $r->get_param( 'team_id' ) ?? 0 );
        if ( $season_id <= 0 || $team_id <= 0 ) {
            return RestResponse::error( 'bad_request', __( 'season_id and team_id are required.', 'talenttrack' ), 400 );
        }

        $template_raw = $r->get_param( 'template_id' );
        $template_id  = $template_raw !== null ? (int) $template_raw : 0;

        $input = [
            'cycle_weeks' => (int) ( $r->get_param( 'cycle_weeks' ) ?? 0 ),
            'anchor_date' => (string) ( $r->get_param( 'anchor_date' ) ?? '' ),
            'template_id' => $template_id,
        ];

        $error = VctTeamCycleValidator::validate( $input );
        if ( $error !== null ) {
            return RestResponse::error( 'vct_cycle_invalid', $error, 400 );
        }

        $ok = ( new VctTeamCyclesRepository() )->upsert(
            $team_id,
            $season_id,
            $input['cycle_weeks'],
            $input['anchor_date'],
            $template_id > 0 ? $template_id : null
        );
        if ( ! $ok ) {
            return RestResponse::error( 'vct_cycle_write_failed', __( 'The cycle could not be saved.', 'talenttrack' ), 500 );
        }

        return RestResponse::success( [
            'cycle' => ( new VctTeamCyclesRepository() )->findForTeamSeason( $team_id, $season_id ),
        ] );
    }

    /**
     * Remove the cycle and, with it, its week overrides. An override only
     * ever means "this week of the cycle is an exception", so leaving them
     * behind would silently re-apply to a cycle set up months later.
     */
    public static function remove( \WP_REST_Request $r ): \WP_REST_Response {
        $season_id = (int) ( $r->get_param( 'season_id' ) ?? 0 );
        $team_id   = (int) ( $r->get_param( 'team_id' ) ?? 0 );
        if ( $season_id <= 0 || $team_id <= 0 ) {
            return RestResponse::error( 'bad_request', __( 'season_id and team_id are required.', 'talenttrack' ), 400 );
        }

        ( new VctCycleWeekOverridesRepository() )->clearSeason( $team_id, $season_id );
        $ok = ( new VctTeamCyclesRepository() )->delete( $team_id, $season_id );

        if ( ! $ok ) {
            return RestResponse::error( 'vct_cycle_write_failed', __( 'The cycle could not be removed.', 'talenttrack' ), 500 );
        }
        return RestResponse::success( [ 'removed' => true ] );
    }
}
