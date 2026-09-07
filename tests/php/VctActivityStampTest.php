<?php
namespace TT\Tests\Php;

use WP_UnitTestCase;
use TT\Domain\Vocabularies\Lookups\ActivityTypeKey;
use TT\Modules\Activities\Repositories\ActivitiesRepository;
use TT\Modules\Vct\Repositories\VctTeamCyclesRepository;
use TT\Modules\Vct\Services\VctActivityStamper;

/**
 * #3362 (epic #3354) — the cycle week stamped onto a training.
 *
 * Two properties carry this file, and they pull in opposite directions:
 *
 *   - future trainings are re-stamped when a fixture moves, because they
 *     have not happened yet;
 *   - past trainings are NOT, because the record of what a training was
 *     planned as has to survive a later reconfiguration.
 *
 * Both are asserted. The second one is the easy one to "fix" into a bug.
 */
final class VctActivityStampTest extends WP_UnitTestCase {

    private int $teamId   = 0;
    private int $seasonId = 0;

    private const ANCHOR = '2026-08-03';

    public function set_up(): void {
        parent::set_up();
        global $wpdb;

        VctActivityStamper::init();

        $wpdb->insert( $wpdb->prefix . 'tt_teams', [ 'club_id' => 1, 'name' => 'Stamp Team' ] );
        $this->teamId = (int) $wpdb->insert_id;

        $wpdb->insert( $wpdb->prefix . 'tt_seasons', [
            'club_id'    => 1,
            'name'       => 'Stamp 26/27',
            'start_date' => self::ANCHOR,
            'end_date'   => '2026-12-20',
        ] );
        $this->seasonId = (int) $wpdb->insert_id;

        wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
    }

    private function withCycle( int $weeks = 6 ): void {
        ( new VctTeamCyclesRepository() )->upsert( $this->teamId, $this->seasonId, $weeks, self::ANCHOR );
    }

    private function activity( string $type, string $date ): int {
        $id = ( new ActivitiesRepository() )->create( [
            'team_id'           => $this->teamId,
            'title'             => $type . ' ' . $date,
            'session_date'      => $date,
            'activity_type_key' => $type,
        ] );
        return (int) $id;
    }

    /** @return array<string,mixed> */
    private function stampOf( int $activity_id ): array {
        global $wpdb;
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT vct_cycle_week, vct_cycle_state, vct_cycle_manual, vct_cycle_id
               FROM {$wpdb->prefix}tt_activities WHERE id = %d",
            $activity_id
        ), ARRAY_A );
        return is_array( $row ) ? $row : [];
    }

    public function test_a_training_is_stamped_with_its_cycle_week(): void {
        $this->withCycle();
        $id = $this->activity( ActivityTypeKey::TRAINING, '2026-08-12' );

        $stamp = $this->stampOf( $id );
        $this->assertSame( 2, (int) $stamp['vct_cycle_week'] );
        $this->assertSame( 'active', $stamp['vct_cycle_state'] );
        $this->assertNotNull( $stamp['vct_cycle_id'] );
    }

    public function test_a_training_in_a_fixture_week_is_stamped_neutral(): void {
        $this->withCycle();
        $this->activity( ActivityTypeKey::GAME, '2026-08-22' );
        $id = $this->activity( ActivityTypeKey::TRAINING, '2026-08-19' );

        $stamp = $this->stampOf( $id );
        $this->assertSame( 'neutral', $stamp['vct_cycle_state'] );
        $this->assertNull( $stamp['vct_cycle_week'] );
    }

    /** A team with no cycle is a normal state, not an error. */
    public function test_a_team_without_a_cycle_leaves_the_columns_null(): void {
        $id = $this->activity( ActivityTypeKey::TRAINING, '2026-08-12' );

        $stamp = $this->stampOf( $id );
        $this->assertNull( $stamp['vct_cycle_week'] );
        $this->assertNull( $stamp['vct_cycle_state'] );
    }

    /** Adding a fixture shifts the trainings that follow it. */
    public function test_a_new_fixture_restamps_the_trainings_after_it(): void {
        $this->withCycle();
        $later = $this->activity( ActivityTypeKey::TRAINING, '2026-08-26' );
        $this->assertSame( 4, (int) $this->stampOf( $later )['vct_cycle_week'] );

        $this->activity( ActivityTypeKey::GAME, '2026-08-11' );

        $this->assertSame(
            3,
            (int) $this->stampOf( $later )['vct_cycle_week'],
            'The pause moved the later training back a cycle week.'
        );
    }

    /**
     * The decision, not an oversight: a training BEFORE the fixture keeps
     * the week it was planned as.
     */
    public function test_a_training_before_the_fixture_keeps_its_stamp(): void {
        $this->withCycle();
        $earlier = $this->activity( ActivityTypeKey::TRAINING, '2026-08-05' );
        $this->assertSame( 1, (int) $this->stampOf( $earlier )['vct_cycle_week'] );

        $this->activity( ActivityTypeKey::GAME, '2026-08-25' );

        $this->assertSame( 1, (int) $this->stampOf( $earlier )['vct_cycle_week'] );
    }

    public function test_an_overridden_training_survives_every_restamp(): void {
        $this->withCycle();
        $id = $this->activity( ActivityTypeKey::TRAINING, '2026-08-26' );

        VctActivityStamper::setOverride( $id, $this->teamId, '2026-08-26', 'neutral', null );
        $this->assertSame( 1, (int) $this->stampOf( $id )['vct_cycle_manual'] );

        // A fixture that would otherwise shift it.
        $this->activity( ActivityTypeKey::GAME, '2026-08-11' );

        $stamp = $this->stampOf( $id );
        $this->assertSame( 'neutral', $stamp['vct_cycle_state'] );
        $this->assertSame( 1, (int) $stamp['vct_cycle_manual'] );
    }

    public function test_clearing_an_override_hands_the_training_back_to_the_cycle(): void {
        $this->withCycle();
        $id = $this->activity( ActivityTypeKey::TRAINING, '2026-08-12' );

        VctActivityStamper::setOverride( $id, $this->teamId, '2026-08-12', 'neutral', null );
        VctActivityStamper::setOverride( $id, $this->teamId, '2026-08-12', null, null );

        $stamp = $this->stampOf( $id );
        $this->assertSame( 0, (int) $stamp['vct_cycle_manual'] );
        $this->assertSame( 2, (int) $stamp['vct_cycle_week'], 'Re-stamped immediately, never left stale.' );
    }

    /**
     * The partial-update contract (CLAUDE.md §6): an edit that does not
     * mention the cycle must not discard the coach's override.
     */
    public function test_an_edit_that_omits_the_cycle_leaves_the_override_alone(): void {
        $this->withCycle();
        $id = $this->activity( ActivityTypeKey::TRAINING, '2026-08-12' );
        VctActivityStamper::setOverride( $id, $this->teamId, '2026-08-12', 'neutral', null );

        ( new ActivitiesRepository() )->update( $id, [ 'location' => 'Veld 3' ] );

        $stamp = $this->stampOf( $id );
        $this->assertSame( 'neutral', $stamp['vct_cycle_state'] );
        $this->assertSame( 1, (int) $stamp['vct_cycle_manual'] );
    }

    /** A training moved outside the cycle must not keep claiming a week. */
    public function test_moving_a_training_before_the_anchor_clears_its_stamp(): void {
        $this->withCycle();
        $id = $this->activity( ActivityTypeKey::TRAINING, '2026-08-12' );
        $this->assertSame( 2, (int) $this->stampOf( $id )['vct_cycle_week'] );

        ( new ActivitiesRepository() )->update( $id, [ 'session_date' => '2026-07-01' ] );

        $this->assertNull( $this->stampOf( $id )['vct_cycle_week'] );
    }

    public function test_a_date_outside_every_season_resolves_to_no_season(): void {
        $this->assertSame( 0, VctActivityStamper::seasonFor( '2019-01-01' ) );
        $this->assertSame( $this->seasonId, VctActivityStamper::seasonFor( '2026-09-01' ) );
    }

    /** A tournament pauses the week exactly like a game (#2686). */
    public function test_a_tournament_pauses_the_week_too(): void {
        $this->withCycle();
        $this->activity( ActivityTypeKey::TOURNAMENT, '2026-08-22' );
        $id = $this->activity( ActivityTypeKey::TRAINING, '2026-08-19' );

        $this->assertSame( 'neutral', $this->stampOf( $id )['vct_cycle_state'] );
    }
}
