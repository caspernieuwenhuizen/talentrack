<?php
namespace TT\Tests\Php;

use WP_REST_Request;
use WP_UnitTestCase;
use TT\Modules\Vct\Repositories\VctCycleWeekOverridesRepository;
use TT\Modules\Vct\Repositories\VctTeamCyclesRepository;
use TT\Modules\Vct\Rest\VctCycleWeeksRestController;

/**
 * #3361 (epic #3354) — the resolved cycle week list and its overrides.
 *
 * The assertion that carries the file is the shift one: forcing a fixture
 * week active moves every week after it. That is the behaviour a coach
 * finds surprising, so it is the one worth pinning down.
 */
final class VctCycleWeeksRestTest extends WP_UnitTestCase {

    private int $teamId   = 0;
    private int $seasonId = 0;

    private const ROUTE  = '/talenttrack/v1/vct/cycle-weeks';
    private const ANCHOR = '2026-08-03';

    public function set_up(): void {
        parent::set_up();
        global $wpdb;

        ( new \TT\Infrastructure\Security\RolesService() )->installRoles();
        \TT\Modules\Authorization\Matrix\MatrixRepository::clearCache();

        $wpdb->insert( $wpdb->prefix . 'tt_teams', [ 'club_id' => 1, 'name' => 'Cycle Weeks Team' ] );
        $this->teamId = (int) $wpdb->insert_id;

        $wpdb->insert( $wpdb->prefix . 'tt_seasons', [
            'club_id'    => 1,
            'name'       => 'Cycle Weeks 26/27',
            'start_date' => self::ANCHOR,
            'end_date'   => '2026-10-25',
        ] );
        $this->seasonId = (int) $wpdb->insert_id;

        wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
        ( new VctTeamCyclesRepository() )->upsert( $this->teamId, $this->seasonId, 6, self::ANCHOR );
    }

    /** @return list<array<string,mixed>> */
    private function weeks(): array {
        $r = new WP_REST_Request( 'GET', self::ROUTE );
        $r->set_param( 'team_id', $this->teamId );
        $r->set_param( 'season_id', $this->seasonId );
        return VctCycleWeeksRestController::resolve( $r )->get_data()['data']['weeks'];
    }

    private function put( array $weeks ): \WP_REST_Response {
        $r = new WP_REST_Request( 'PUT', self::ROUTE );
        $r->set_param( 'team_id', $this->teamId );
        $r->set_param( 'season_id', $this->seasonId );
        $r->set_param( 'weeks', $weeks );
        return VctCycleWeeksRestController::replace( $r );
    }

    public function test_get_returns_the_resolved_weeks(): void {
        $weeks = $this->weeks();

        $this->assertNotEmpty( $weeks );
        $this->assertSame( self::ANCHOR, $weeks[0]['week_starts_on'] );
        $this->assertSame( 1, $weeks[0]['cycle_week'] );
    }

    public function test_forcing_a_week_neutral_shifts_every_week_after_it(): void {
        $before = $this->weeks();
        $this->assertSame( 3, $before[2]['cycle_week'] );
        $this->assertSame( 4, $before[3]['cycle_week'] );

        $res = $this->put( [ '2026-08-17' => 'neutral' ] );
        $this->assertSame( 200, $res->get_status() );

        $after = $this->weeks();
        $this->assertNull( $after[2]['cycle_week'], 'The forced week is neutral.' );
        $this->assertSame( 3, $after[3]['cycle_week'], 'Week 3 was not spent on the pause.' );
    }

    public function test_auto_removes_the_override_rather_than_storing_a_third_state(): void {
        $this->put( [ '2026-08-17' => 'neutral' ] );
        $this->put( [ '2026-08-17' => 'auto' ] );

        $this->assertSame( [], ( new VctCycleWeekOverridesRepository() )->listForSeason( $this->teamId, $this->seasonId ) );
        $this->assertSame( 3, $this->weeks()[2]['cycle_week'] );
    }

    public function test_an_unknown_state_is_refused(): void {
        $res = $this->put( [ '2026-08-17' => 'paused' ] );
        $this->assertSame( 400, $res->get_status() );
    }

    public function test_the_week_carries_the_reason_it_is_neutral(): void {
        $this->put( [ '2026-08-17' => 'neutral' ] );
        $week = $this->weeks()[2];

        $this->assertSame( 'override', $week['source'] );
        $this->assertFalse( $week['fixture_week'] );
    }

    /** A team with no cycle is planned from macro-blocks — not an error. */
    public function test_a_team_without_a_cycle_returns_an_empty_list_not_an_error(): void {
        ( new VctTeamCyclesRepository() )->delete( $this->teamId, $this->seasonId );

        $r = new WP_REST_Request( 'GET', self::ROUTE );
        $r->set_param( 'team_id', $this->teamId );
        $r->set_param( 'season_id', $this->seasonId );
        $res = VctCycleWeeksRestController::resolve( $r );

        $this->assertSame( 200, $res->get_status() );
        $this->assertSame( [], $res->get_data()['data']['weeks'] );
    }

    public function test_missing_parameters_are_a_400(): void {
        $this->assertSame( 400, VctCycleWeeksRestController::resolve( new WP_REST_Request( 'GET', self::ROUTE ) )->get_status() );
    }

    /** A coach may read the rhythm they plan to. */
    public function test_a_coach_may_read(): void {
        wp_set_current_user( self::factory()->user->create( [ 'role' => 'tt_coach' ] ) );
        $this->assertTrue( VctCycleWeeksRestController::can_read() );
    }

    /** Moving a week shifts the rest of the season — that is not a coach's call. */
    public function test_a_coach_may_not_write(): void {
        wp_set_current_user( self::factory()->user->create( [ 'role' => 'tt_coach' ] ) );
        $this->assertFalse( VctCycleWeeksRestController::can_write() );
    }

    public function test_a_logged_out_visitor_may_do_neither(): void {
        wp_set_current_user( 0 );
        $this->assertFalse( VctCycleWeeksRestController::can_read() );
        $this->assertFalse( VctCycleWeeksRestController::can_write() );
    }
}
