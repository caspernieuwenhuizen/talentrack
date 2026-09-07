<?php
namespace TT\Tests\Php;

use WP_UnitTestCase;
use TT\Modules\Pdp\Repositories\SeasonsRepository;
use TT\Modules\Vct\Repositories\VctCycleWeekOverridesRepository;
use TT\Modules\Vct\Repositories\VctMacroBlocksRepository;
use TT\Modules\Vct\Repositories\VctTeamCyclesRepository;
use TT\Modules\Vct\Rules\ProgressionRule;
use TT\Modules\Vct\Rules\SessionPlanContext;
use TT\Modules\Vct\Services\VctCycleResolver;

/**
 * #3359 (epic #3354) — the planner reads the cycle.
 *
 * Two assertions carry this file. A team WITH a cycle plans off it, pausing
 * for fixture weeks. A team WITHOUT one plans exactly as it did before
 * cycles existed — that second one is what makes this slice safe to ship.
 */
final class VctProgressionRuleCycleTest extends WP_UnitTestCase {

    private int $teamId   = 0;
    private int $seasonId = 0;

    private const ANCHOR = '2026-08-03';

    public function set_up(): void {
        parent::set_up();
        global $wpdb;

        $wpdb->insert( $wpdb->prefix . 'tt_teams', [ 'club_id' => 1, 'name' => 'Progression Cycle Team' ] );
        $this->teamId = (int) $wpdb->insert_id;

        $wpdb->insert( $wpdb->prefix . 'tt_seasons', [
            'club_id'    => 1,
            'name'       => 'Progression 26/27',
            'start_date' => self::ANCHOR,
            'end_date'   => '2026-11-01',
        ] );
        $this->seasonId = (int) $wpdb->insert_id;
    }

    /** @param list<string> $fixtureMondays */
    private function rule( array $fixtureMondays = [] ): ProgressionRule {
        return new ProgressionRule(
            new VctMacroBlocksRepository(),
            new VctCycleResolver(
                new VctTeamCyclesRepository(),
                new VctCycleWeekOverridesRepository(),
                new VctMacroBlocksRepository(),
                new FakeFixtureWeeksReader( $fixtureMondays ),
                new SeasonsRepository()
            )
        );
    }

    private function context( string $date ): SessionPlanContext {
        $ctx               = new SessionPlanContext();
        $ctx->team_id      = $this->teamId;
        $ctx->season_id    = $this->seasonId;
        $ctx->session_date = $date;
        return $ctx;
    }

    /** @return list<string> */
    private function warningCodes( SessionPlanContext $ctx ): array {
        return array_map(
            static fn( array $w ): string => (string) $w['code'],
            $ctx->warnings
        );
    }

    public function test_a_cycle_week_supplies_the_multiplier(): void {
        ( new VctTeamCyclesRepository() )->upsert( $this->teamId, $this->seasonId, 3, self::ANCHOR );

        $out = $this->rule()->apply( $this->context( self::ANCHOR ) );

        $this->assertSame( 1, $out->cycle_week );
        $this->assertSame( VctCycleResolver::STATE_ACTIVE, $out->cycle_state );
        $this->assertEqualsWithDelta( 0.85, $out->progression_multiplier, 0.001 );
    }

    public function test_a_fixture_week_plans_neutral_and_says_why(): void {
        ( new VctTeamCyclesRepository() )->upsert( $this->teamId, $this->seasonId, 6, self::ANCHOR );

        $out = $this->rule( [ '2026-08-17' ] )->apply( $this->context( '2026-08-19' ) );

        $this->assertSame( VctCycleResolver::STATE_NEUTRAL, $out->cycle_state );
        $this->assertNull( $out->cycle_week );
        $this->assertSame( 1.0, $out->progression_multiplier );
        $this->assertContains( 'cycle_week_neutral', $this->warningCodes( $out ) );
    }

    /** The pause does not spend the week: the next week is still week 3. */
    public function test_the_week_after_a_pause_resumes_the_same_cycle_week(): void {
        ( new VctTeamCyclesRepository() )->upsert( $this->teamId, $this->seasonId, 6, self::ANCHOR );
        $rule = $this->rule( [ '2026-08-17' ] );

        $this->assertSame( 3, $rule->apply( $this->context( '2026-08-24' ) )->cycle_week );
    }

    /**
     * The safety assertion for this slice: no cycle row, and nothing about
     * the existing macro-block path changes.
     */
    public function test_a_team_without_a_cycle_falls_through_to_macro_blocks(): void {
        ( new VctMacroBlocksRepository() )->replaceForSeason( $this->teamId, $this->seasonId, [
            [
                'sequence'      => 1,
                'label'         => 'Opbouw',
                'start_date'    => self::ANCHOR,
                'end_date'      => '2026-09-13',
                'phase_profile' => [
                    [ 'week' => 1, 'phase' => 'introductie', 'multiplier' => 0.85 ],
                    [ 'week' => 2, 'phase' => 'opbouw',      'multiplier' => 0.95 ],
                ],
            ],
        ] );

        $out = $this->rule()->apply( $this->context( '2026-08-10' ) );

        $this->assertNull( $out->cycle_week, 'No cycle configured, so no cycle week.' );
        $this->assertNull( $out->cycle_state );
        $this->assertEqualsWithDelta( 0.95, $out->progression_multiplier, 0.001 );
    }

    /**
     * The silent fall-through this slice gives a voice to: a block longer
     * than its profile still plans at 1.0, but now says so.
     */
    public function test_a_block_longer_than_its_profile_now_warns(): void {
        ( new VctMacroBlocksRepository() )->replaceForSeason( $this->teamId, $this->seasonId, [
            [
                'sequence'      => 1,
                'label'         => 'Lange blok',
                'start_date'    => self::ANCHOR,
                'end_date'      => '2026-10-25',
                'phase_profile' => [
                    [ 'week' => 1, 'phase' => 'introductie', 'multiplier' => 0.85 ],
                    [ 'week' => 2, 'phase' => 'opbouw',      'multiplier' => 0.95 ],
                ],
            ],
        ] );

        // Fourth week of the block — beyond the two-week profile.
        $out = $this->rule()->apply( $this->context( '2026-08-24' ) );

        $this->assertSame( 1.0, $out->progression_multiplier );
        $this->assertContains( 'phase_profile_shorter_than_block', $this->warningCodes( $out ) );
    }

    public function test_no_season_still_warns_as_before(): void {
        $ctx            = $this->context( self::ANCHOR );
        $ctx->season_id = 0;

        $out = $this->rule()->apply( $ctx );

        $this->assertSame( 1.0, $out->progression_multiplier );
        $this->assertContains( 'no_macro_block_configured', $this->warningCodes( $out ) );
    }

    /** A theme the coach chose is never overwritten by the cycle's. */
    public function test_an_explicit_theme_survives_the_cycle(): void {
        ( new VctTeamCyclesRepository() )->upsert( $this->teamId, $this->seasonId, 3, self::ANCHOR );

        $ctx                 = $this->context( self::ANCHOR );
        $ctx->tactical_theme = 'defending';

        $this->assertSame( 'defending', $this->rule()->apply( $ctx )->tactical_theme );
    }
}
