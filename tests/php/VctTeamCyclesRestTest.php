<?php
namespace TT\Tests\Php;

use WP_REST_Request;
use WP_UnitTestCase;
use TT\Modules\Vct\Repositories\VctCycleWeekOverridesRepository;
use TT\Modules\Vct\Repositories\VctTeamCyclesRepository;
use TT\Modules\Vct\Rest\VctTeamCyclesRestController;

/**
 * #3360 (epic #3354) — the per-team cycle REST surface.
 *
 * The cycle governs how hard a team is worked week by week, so the
 * authorization assertions matter as much as the round-trip ones: a coach
 * without the VCT configuration capability must not be able to reshape a
 * squad's season.
 */
final class VctTeamCyclesRestTest extends WP_UnitTestCase {

    private int $teamId   = 0;
    private int $seasonId = 0;
    private int $adminId  = 0;

    private const ROUTE = '/talenttrack/v1/vct/team-cycles';

    public function set_up(): void {
        parent::set_up();
        global $wpdb;

        ( new \TT\Infrastructure\Security\RolesService() )->installRoles();
        \TT\Modules\Authorization\Matrix\MatrixRepository::clearCache();

        do_action( 'rest_api_init' );
        VctTeamCyclesRestController::register();

        $wpdb->insert( $wpdb->prefix . 'tt_teams', [ 'club_id' => 1, 'name' => 'Cycle REST Team' ] );
        $this->teamId = (int) $wpdb->insert_id;

        $wpdb->insert( $wpdb->prefix . 'tt_seasons', [
            'club_id'    => 1,
            'name'       => 'Cycle REST 26/27',
            'start_date' => '2026-08-03',
            'end_date'   => '2027-05-30',
        ] );
        $this->seasonId = (int) $wpdb->insert_id;

        $this->adminId = self::factory()->user->create( [ 'role' => 'administrator' ] );
        wp_set_current_user( $this->adminId );
    }

    private function put( array $params ): \WP_REST_Response {
        $r = new WP_REST_Request( 'PUT', self::ROUTE );
        foreach ( $params as $k => $v ) $r->set_param( $k, $v );
        return VctTeamCyclesRestController::save( $r );
    }

    public function test_put_then_get_round_trips(): void {
        $res = $this->put( [
            'season_id'   => $this->seasonId,
            'team_id'     => $this->teamId,
            'cycle_weeks' => 4,
            'anchor_date' => '2026-08-03',
        ] );
        $this->assertSame( 200, $res->get_status() );

        $get = new WP_REST_Request( 'GET', self::ROUTE );
        $get->set_param( 'season_id', $this->seasonId );
        $data   = VctTeamCyclesRestController::listForSeason( $get )->get_data();
        $cycles = $data['data']['cycles'];

        $this->assertCount( 1, $cycles );
        $this->assertSame( 4, (int) $cycles[0]['cycle_weeks'] );
        $this->assertSame( $this->teamId, (int) $cycles[0]['team_id'] );
    }

    public function test_a_length_the_product_does_not_offer_is_refused(): void {
        $res = $this->put( [
            'season_id'   => $this->seasonId,
            'team_id'     => $this->teamId,
            'cycle_weeks' => 5,
            'anchor_date' => '2026-08-03',
        ] );

        $this->assertSame( 400, $res->get_status() );
        $this->assertNull( ( new VctTeamCyclesRepository() )->findForTeamSeason( $this->teamId, $this->seasonId ) );
    }

    public function test_a_non_date_anchor_is_refused(): void {
        $res = $this->put( [
            'season_id'   => $this->seasonId,
            'team_id'     => $this->teamId,
            'cycle_weeks' => 6,
            'anchor_date' => 'next monday',
        ] );

        $this->assertSame( 400, $res->get_status() );
    }

    public function test_the_anchor_is_stored_as_its_monday(): void {
        // 2026-08-06 is a Thursday.
        $this->put( [
            'season_id'   => $this->seasonId,
            'team_id'     => $this->teamId,
            'cycle_weeks' => 6,
            'anchor_date' => '2026-08-06',
        ] );

        $cycle = ( new VctTeamCyclesRepository() )->findForTeamSeason( $this->teamId, $this->seasonId );
        $this->assertNotNull( $cycle );
        $this->assertSame( '2026-08-03', $cycle['anchor_date'] );
    }

    /** Removing a cycle takes its week overrides with it. */
    public function test_delete_clears_the_overrides_too(): void {
        $this->put( [
            'season_id'   => $this->seasonId,
            'team_id'     => $this->teamId,
            'cycle_weeks' => 6,
            'anchor_date' => '2026-08-03',
        ] );
        ( new VctCycleWeekOverridesRepository() )->set(
            $this->teamId, $this->seasonId, '2026-09-07', VctCycleWeekOverridesRepository::STATE_NEUTRAL
        );

        $r = new WP_REST_Request( 'DELETE', self::ROUTE );
        $r->set_param( 'season_id', $this->seasonId );
        $r->set_param( 'team_id', $this->teamId );
        $res = VctTeamCyclesRestController::remove( $r );

        $this->assertSame( 200, $res->get_status() );
        $this->assertNull( ( new VctTeamCyclesRepository() )->findForTeamSeason( $this->teamId, $this->seasonId ) );
        $this->assertSame( [], ( new VctCycleWeekOverridesRepository() )->listForSeason( $this->teamId, $this->seasonId ) );
    }

    public function test_a_missing_season_is_a_400_not_a_silent_empty(): void {
        $r = new WP_REST_Request( 'GET', self::ROUTE );
        $this->assertSame( 400, VctTeamCyclesRestController::listForSeason( $r )->get_status() );
    }

    public function test_an_admin_may_configure_a_cycle(): void {
        $this->assertTrue( VctTeamCyclesRestController::can_admin() );
    }

    /** A cycle decides how hard minors are worked; a plain coach may not set one. */
    public function test_a_coach_without_the_config_capability_may_not(): void {
        wp_set_current_user( self::factory()->user->create( [ 'role' => 'tt_coach' ] ) );
        $this->assertFalse( VctTeamCyclesRestController::can_admin() );
    }

    public function test_a_logged_out_visitor_may_not(): void {
        wp_set_current_user( 0 );
        $this->assertFalse( VctTeamCyclesRestController::can_admin() );
    }
}
