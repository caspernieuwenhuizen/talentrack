<?php
namespace TT\Modules\Vct\Rest;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Infrastructure\REST\RestResponse;
use TT\Infrastructure\Security\AuthorizationService;
use TT\Modules\Vct\Repositories\VctCycleWeekOverridesRepository;
use TT\Modules\Vct\Services\VctCycleResolver;

/**
 * VctCycleWeeksRestController — the resolved cycle, week by week (#3361,
 * epic #3354).
 *
 *   GET /vct/cycle-weeks?team_id=N&season_id=M   the resolved week list
 *   PUT /vct/cycle-weeks?team_id=N&season_id=M   replace the overrides
 *
 * The GET returns exactly what `VctCycleResolver` returns, and the PHP
 * view calls the same service — so a future SaaS front end and the
 * rendered page agree about which week a team is in, rather than each
 * deriving it.
 *
 * Reads are gated on `tt_vct_plan`: a coach needs to see the rhythm they
 * are planning to. Writes are gated on `tt_vct_admin_config` — moving a
 * week shifts every week after it for the rest of the season, which is a
 * head-of-development decision rather than a per-training one.
 */
class VctCycleWeeksRestController {

    private const NS = 'talenttrack/v1';

    public static function init(): void {
        add_action( 'rest_api_init', [ __CLASS__, 'register' ] );
    }

    public static function register(): void {
        register_rest_route( self::NS, '/vct/cycle-weeks', [
            [
                'methods'             => 'GET',
                'callback'            => [ __CLASS__, 'resolve' ],
                'permission_callback' => [ __CLASS__, 'can_read' ],
            ],
            [
                'methods'             => 'PUT',
                'callback'            => [ __CLASS__, 'replace' ],
                'permission_callback' => [ __CLASS__, 'can_write' ],
            ],
        ] );
    }

    public static function can_read(): bool {
        return AuthorizationService::userCanOrMatrix( get_current_user_id(), 'tt_vct_plan' );
    }

    public static function can_write(): bool {
        return AuthorizationService::userCanOrMatrix( get_current_user_id(), 'tt_vct_admin_config' );
    }

    public static function resolve( \WP_REST_Request $r ): \WP_REST_Response {
        $team_id   = (int) ( $r->get_param( 'team_id' ) ?? 0 );
        $season_id = (int) ( $r->get_param( 'season_id' ) ?? 0 );
        if ( $team_id <= 0 || $season_id <= 0 ) {
            return RestResponse::error( 'bad_request', __( 'team_id and season_id are required.', 'talenttrack' ), 400 );
        }

        // An empty list is the honest answer for a team with no cycle, not
        // an error: it is planned from the season's macro-blocks instead.
        return RestResponse::success( [
            'weeks' => ( new VctCycleResolver() )->resolveSeason( $team_id, $season_id ),
        ] );
    }

    /**
     * Replace the season's overrides. `weeks` is a map of Monday to state,
     * where `auto` removes the override rather than storing a third state.
     */
    public static function replace( \WP_REST_Request $r ): \WP_REST_Response {
        $team_id   = (int) ( $r->get_param( 'team_id' ) ?? 0 );
        $season_id = (int) ( $r->get_param( 'season_id' ) ?? 0 );
        if ( $team_id <= 0 || $season_id <= 0 ) {
            return RestResponse::error( 'bad_request', __( 'team_id and season_id are required.', 'talenttrack' ), 400 );
        }

        $weeks = $r->get_param( 'weeks' );
        if ( ! is_array( $weeks ) ) {
            return RestResponse::error( 'bad_request', __( 'weeks must be a map of week start date to state.', 'talenttrack' ), 400 );
        }

        $repo   = new VctCycleWeekOverridesRepository();
        $uid    = get_current_user_id();
        $failed = [];

        foreach ( $weeks as $monday => $state ) {
            $monday = (string) $monday;
            $state  = is_string( $state ) ? $state : '';

            if ( $state === 'auto' ) {
                if ( ! $repo->clear( $team_id, $monday ) ) $failed[] = $monday;
                continue;
            }

            if ( ! VctCycleWeekOverridesRepository::isValidState( $state ) ) {
                return RestResponse::error(
                    'vct_cycle_week_invalid',
                    __( 'A week is either automatic, neutral or active.', 'talenttrack' ),
                    400
                );
            }

            if ( ! $repo->set( $team_id, $season_id, $monday, $state, null, $uid ) ) $failed[] = $monday;
        }

        if ( $failed !== [] ) {
            return RestResponse::error( 'vct_cycle_week_write_failed', __( 'Some weeks could not be saved.', 'talenttrack' ), 500 );
        }

        return RestResponse::success( [
            'weeks' => ( new VctCycleResolver() )->resolveSeason( $team_id, $season_id ),
        ] );
    }
}
