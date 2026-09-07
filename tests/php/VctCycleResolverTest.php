<?php
namespace TT\Tests\Php;

use WP_UnitTestCase;
use TT\Modules\Vct\Repositories\VctCycleWeekOverridesRepository;
use TT\Modules\Vct\Repositories\VctTeamCyclesRepository;
use TT\Modules\Vct\Rules\Providers\FixtureWeeksReader;
use TT\Modules\Vct\Services\VctCycleResolver;

/**
 * In-memory fixture list, so the walk can be tested without seeding
 * fifty-two activities per case.
 */
final class FakeFixtureWeeksReader implements FixtureWeeksReader {

    /** @var list<string> */
    public array $mondays;

    /** @param list<string> $mondays */
    public function __construct( array $mondays = [] ) {
        $this->mondays = $mondays;
    }

    public function matchWeekMondays( int $team_id, string $from, string $to ): array {
        return array_values( array_filter(
            $this->mondays,
            static fn( string $m ): bool => $m >= $from && $m <= $to
        ) );
    }
}

/**
 * #3358 (epic #3354) — the cycle week walker.
 *
 * The first test is the one that matters: a NEUTRAL week does not consume a
 * cycle week. Everything else in the feature reads off that.
 */
final class VctCycleResolverTest extends WP_UnitTestCase {

    private int $teamId   = 0;
    private int $seasonId = 0;

    /** Season runs 2026-08-03 (a Monday) to 2026-11-01 — 13 weeks. */
    private const ANCHOR = '2026-08-03';

    public function set_up(): void {
        parent::set_up();
        global $wpdb;

        $wpdb->insert( $wpdb->prefix . 'tt_teams', [ 'club_id' => 1, 'name' => 'Cycle Resolver Team' ] );
        $this->teamId = (int) $wpdb->insert_id;

        $wpdb->insert( $wpdb->prefix . 'tt_seasons', [
            'club_id'    => 1,
            'name'       => 'Resolver 26/27',
            'start_date' => self::ANCHOR,
            'end_date'   => '2026-11-01',
        ] );
        $this->seasonId = (int) $wpdb->insert_id;
    }

    /** @param list<string> $fixtureMondays */
    private function resolver( array $fixtureMondays = [] ): VctCycleResolver {
        return new VctCycleResolver(
            new VctTeamCyclesRepository(),
            new VctCycleWeekOverridesRepository(),
            new \TT\Modules\Vct\Repositories\VctMacroBlocksRepository(),
            new FakeFixtureWeeksReader( $fixtureMondays ),
            new \TT\Modules\Pdp\Repositories\SeasonsRepository()
        );
    }

    private function configureCycle( int $weeks, string $anchor = self::ANCHOR ): void {
        ( new VctTeamCyclesRepository() )->upsert( $this->teamId, $this->seasonId, $weeks, $anchor );
    }

    /** @return list<int|string> cycle_week, or 'N' for a neutral week. */
    private function shape( VctCycleResolver $resolver, int $take = 10 ): array {
        $out = [];
        foreach ( $resolver->resolveSeason( $this->teamId, $this->seasonId ) as $week ) {
            $out[] = $week['state'] === VctCycleResolver::STATE_NEUTRAL ? 'N' : $week['cycle_week'];
            if ( count( $out ) >= $take ) break;
        }
        return $out;
    }

    public function test_a_six_week_cycle_wraps(): void {
        $this->configureCycle( 6 );
        $this->assertSame( [ 1, 2, 3, 4, 5, 6, 1, 2 ], $this->shape( $this->resolver(), 8 ) );
    }

    /**
     * THE test. A fixture in cycle week 3's calendar week pauses the cycle;
     * the week after the pause is still week 3.
     */
    public function test_a_fixture_week_pauses_without_consuming_the_cycle_week(): void {
        $this->configureCycle( 6 );
        // 2026-08-17 is the third Monday from the anchor.
        $resolver = $this->resolver( [ '2026-08-17' ] );

        $this->assertSame( [ 1, 2, 'N', 3, 4, 5 ], $this->shape( $resolver, 6 ) );
    }

    public function test_two_consecutive_fixture_weeks_pause_twice(): void {
        $this->configureCycle( 6 );
        $resolver = $this->resolver( [ '2026-08-17', '2026-08-24' ] );

        $this->assertSame( [ 1, 2, 'N', 'N', 3, 4 ], $this->shape( $resolver, 6 ) );
    }

    public function test_a_three_week_cycle_wraps_at_three(): void {
        $this->configureCycle( 3 );
        $this->assertSame( [ 1, 2, 3, 1, 2, 3, 1 ], $this->shape( $this->resolver(), 7 ) );
    }

    public function test_a_four_week_cycle_wraps_at_four(): void {
        $this->configureCycle( 4 );
        $this->assertSame( [ 1, 2, 3, 4, 1, 2 ], $this->shape( $this->resolver(), 6 ) );
    }

    public function test_a_neutral_override_pauses_a_fixture_free_week(): void {
        $this->configureCycle( 6 );
        ( new VctCycleWeekOverridesRepository() )->set(
            $this->teamId, $this->seasonId, '2026-08-17', VctCycleWeekOverridesRepository::STATE_NEUTRAL
        );

        $this->assertSame( [ 1, 2, 'N', 3, 4 ], $this->shape( $this->resolver(), 5 ) );
    }

    /**
     * The override direction that actually comes up: a friendly the coach
     * does not want the cycle to pause for.
     */
    public function test_an_active_override_runs_a_fixture_week_normally(): void {
        $this->configureCycle( 6 );
        ( new VctCycleWeekOverridesRepository() )->set(
            $this->teamId, $this->seasonId, '2026-08-17', VctCycleWeekOverridesRepository::STATE_ACTIVE
        );
        $resolver = $this->resolver( [ '2026-08-17' ] );

        $this->assertSame( [ 1, 2, 3, 4, 5 ], $this->shape( $resolver, 5 ) );
    }

    public function test_the_week_says_why_it_is_neutral(): void {
        $this->configureCycle( 6 );
        $resolver = $this->resolver( [ '2026-08-17' ] );

        $week = $resolver->resolveWeek( $this->teamId, $this->seasonId, '2026-08-19' );

        $this->assertNotNull( $week );
        $this->assertSame( VctCycleResolver::STATE_NEUTRAL, $week['state'] );
        $this->assertSame( VctCycleResolver::SOURCE_AUTO, $week['source'] );
        $this->assertTrue( $week['fixture_week'] );
        $this->assertSame( 1.0, $week['multiplier'] );
    }

    /**
     * A team playing every week never advances. Correct under the pause
     * rule, and asserted so nobody later "fixes" it into a drift.
     */
    public function test_a_team_playing_every_week_never_advances(): void {
        $this->configureCycle( 6 );
        $every = [];
        for ( $i = 0; $i < 8; $i++ ) {
            $every[] = gmdate( 'Y-m-d', strtotime( self::ANCHOR ) + $i * 7 * 86400 );
        }

        $this->assertSame( [ 'N', 'N', 'N', 'N', 'N' ], $this->shape( $this->resolver( $every ), 5 ) );
    }

    public function test_a_date_before_the_anchor_has_no_week(): void {
        $this->configureCycle( 6, '2026-09-07' );
        $this->assertNull( $this->resolver()->resolveWeek( $this->teamId, $this->seasonId, '2026-08-10' ) );
    }

    public function test_a_mid_week_date_resolves_to_its_week(): void {
        $this->configureCycle( 6 );
        // 2026-08-13 is the Thursday of the second week.
        $week = $this->resolver()->resolveWeek( $this->teamId, $this->seasonId, '2026-08-13' );

        $this->assertNotNull( $week );
        $this->assertSame( '2026-08-10', $week['week_starts_on'] );
        $this->assertSame( 2, $week['cycle_week'] );
    }

    public function test_no_cycle_configured_resolves_to_nothing(): void {
        $this->assertSame( [], $this->resolver()->resolveSeason( $this->teamId, $this->seasonId ) );
        $this->assertNull( $this->resolver()->resolveWeek( $this->teamId, $this->seasonId, '2026-08-10' ) );
    }

    public function test_the_walk_stops_at_the_end_of_the_season(): void {
        $this->configureCycle( 6 );
        $weeks = $this->resolver()->resolveSeason( $this->teamId, $this->seasonId );

        $this->assertNotEmpty( $weeks );
        $last = end( $weeks );
        $this->assertLessThanOrEqual( '2026-11-01', $last['week_starts_on'] );
    }

    /** The season is walked once however many weeks are asked for. */
    public function test_the_season_is_resolved_once_per_request(): void {
        $this->configureCycle( 6 );
        $reader   = new FakeFixtureWeeksReader( [] );
        $resolver = new VctCycleResolver(
            new VctTeamCyclesRepository(),
            new VctCycleWeekOverridesRepository(),
            new \TT\Modules\Vct\Repositories\VctMacroBlocksRepository(),
            $reader,
            new \TT\Modules\Pdp\Repositories\SeasonsRepository()
        );

        $first  = $resolver->resolveSeason( $this->teamId, $this->seasonId );
        $reader->mondays = [ '2026-08-17' ];
        $second = $resolver->resolveSeason( $this->teamId, $this->seasonId );

        $this->assertSame( $first, $second, 'The memo must serve the second call.' );

        $resolver->flush();
        $this->assertNotSame( $first, $resolver->resolveSeason( $this->teamId, $this->seasonId ) );
    }

    /** An active week carries the phase and multiplier from the profile. */
    public function test_an_active_week_carries_its_phase_profile(): void {
        $this->configureCycle( 3 );
        $week = $this->resolver()->resolveWeek( $this->teamId, $this->seasonId, self::ANCHOR );

        $this->assertNotNull( $week );
        $this->assertSame( 1, $week['cycle_week'] );
        $this->assertSame( 'introductie', $week['phase'] );
        $this->assertEqualsWithDelta( 0.85, $week['multiplier'], 0.001 );
    }
}
