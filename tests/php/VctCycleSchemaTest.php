<?php
namespace TT\Tests\Php;

use WP_UnitTestCase;
use TT\Modules\Vct\Repositories\VctCycleWeekOverridesRepository;
use TT\Modules\Vct\Repositories\VctTeamCyclesRepository;

/**
 * #3357 (epic #3354) — the per-team cycle schema and its two repositories.
 *
 * The load-bearing assertion in this file is the last one: with no cycle row,
 * nothing about VCT changes. Slice 1 adds tables and columns and must alter no
 * behaviour, so an install that never configures a cycle plans exactly as it
 * did before.
 */
final class VctCycleSchemaTest extends WP_UnitTestCase {

    private int $teamId = 0;
    private const SEASON = 4242;

    public function set_up(): void {
        parent::set_up();
        global $wpdb;

        $wpdb->insert( $wpdb->prefix . 'tt_teams', [ 'club_id' => 1, 'name' => 'Cycle Schema Team' ] );
        $this->teamId = (int) $wpdb->insert_id;
    }

    public function test_tables_exist(): void {
        global $wpdb;
        foreach ( [ 'tt_vct_team_cycles', 'tt_vct_cycle_weeks' ] as $name ) {
            $table = $wpdb->prefix . $name;
            $this->assertSame(
                $table,
                $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ),
                "{$name} was not created."
            );
        }
    }

    public function test_activities_carry_the_stamp_columns(): void {
        global $wpdb;
        $table = $wpdb->prefix . 'tt_activities';
        foreach ( [ 'vct_cycle_id', 'vct_cycle_week', 'vct_cycle_state', 'vct_cycle_manual' ] as $column ) {
            $this->assertSame(
                $column,
                $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", $column ) ),
                "tt_activities.{$column} is missing."
            );
        }
    }

    public function test_three_week_reference_profile_is_seeded_once(): void {
        global $wpdb;
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT phase_profile_json FROM {$wpdb->prefix}tt_vct_macro_blocks
              WHERE club_id = 1 AND team_id = 0 AND season_id = 0 AND sequence = %d",
            4
        ) );

        $this->assertCount( 1, $rows, 'The 3-week reference must be seeded exactly once.' );

        $profile = json_decode( (string) $rows[0]->phase_profile_json, true );
        $this->assertIsArray( $profile );
        $this->assertCount( 3, $profile );
        $this->assertSame( 'deload', $profile[2]['phase'] );
    }

    public function test_upsert_round_trips_and_rejects_a_length_the_product_does_not_offer(): void {
        $repo = new VctTeamCyclesRepository();

        $this->assertTrue( $repo->upsert( $this->teamId, self::SEASON, 4, '2026-08-03' ) );

        $cycle = $repo->findForTeamSeason( $this->teamId, self::SEASON );
        $this->assertNotNull( $cycle );
        $this->assertSame( 4, $cycle['cycle_weeks'] );
        $this->assertSame( '2026-08-03', $cycle['anchor_date'] );

        // 5 is a real profile length in the seeded set but not a cycle the
        // product offers, so it must be refused rather than coerced.
        $this->assertFalse( $repo->upsert( $this->teamId, self::SEASON, 5, '2026-08-03' ) );
        $this->assertSame( 4, $repo->findForTeamSeason( $this->teamId, self::SEASON )['cycle_weeks'] );
    }

    public function test_anchor_is_snapped_back_to_its_monday(): void {
        $repo = new VctTeamCyclesRepository();

        // 2026-08-06 is a Thursday.
        $this->assertTrue( $repo->upsert( $this->teamId, self::SEASON, 6, '2026-08-06' ) );
        $this->assertSame( '2026-08-03', $repo->findForTeamSeason( $this->teamId, self::SEASON )['anchor_date'] );
    }

    public function test_upsert_updates_in_place_rather_than_adding_a_second_row(): void {
        $repo = new VctTeamCyclesRepository();
        $repo->upsert( $this->teamId, self::SEASON, 6, '2026-08-03' );
        $repo->upsert( $this->teamId, self::SEASON, 3, '2026-08-10' );

        global $wpdb;
        $count = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}tt_vct_team_cycles WHERE team_id = %d AND season_id = %d",
            $this->teamId, self::SEASON
        ) );

        $this->assertSame( 1, $count );
        $this->assertSame( 3, $repo->findForTeamSeason( $this->teamId, self::SEASON )['cycle_weeks'] );
    }

    public function test_delete_returns_the_team_to_macro_block_planning(): void {
        $repo = new VctTeamCyclesRepository();
        $repo->upsert( $this->teamId, self::SEASON, 6, '2026-08-03' );

        $this->assertTrue( $repo->delete( $this->teamId, self::SEASON ) );
        $this->assertNull( $repo->findForTeamSeason( $this->teamId, self::SEASON ) );
    }

    public function test_overrides_are_keyed_by_the_week_monday_in_both_directions(): void {
        $repo = new VctCycleWeekOverridesRepository();

        // A Saturday and the Monday of the same week must be one row.
        $this->assertTrue( $repo->set( $this->teamId, self::SEASON, '2026-09-12', VctCycleWeekOverridesRepository::STATE_NEUTRAL ) );
        $this->assertTrue( $repo->set( $this->teamId, self::SEASON, '2026-09-07', VctCycleWeekOverridesRepository::STATE_ACTIVE ) );

        $overrides = $repo->listForSeason( $this->teamId, self::SEASON );
        $this->assertCount( 1, $overrides );
        $this->assertArrayHasKey( '2026-09-07', $overrides );
        $this->assertSame( 'active', $overrides['2026-09-07']['state'] );
    }

    public function test_clearing_an_override_removes_the_row_rather_than_storing_auto(): void {
        $repo = new VctCycleWeekOverridesRepository();
        $repo->set( $this->teamId, self::SEASON, '2026-09-07', VctCycleWeekOverridesRepository::STATE_NEUTRAL );

        $this->assertTrue( $repo->clear( $this->teamId, '2026-09-09' ) );
        $this->assertSame( [], $repo->listForSeason( $this->teamId, self::SEASON ) );
    }

    public function test_an_unknown_override_state_is_refused(): void {
        $repo = new VctCycleWeekOverridesRepository();
        $this->assertFalse( $repo->set( $this->teamId, self::SEASON, '2026-09-07', 'auto' ) );
        $this->assertSame( [], $repo->listForSeason( $this->teamId, self::SEASON ) );
    }

    /**
     * Slice 1 adds structure and changes no behaviour. A team with no cycle
     * row must look exactly as it did before this migration ran.
     */
    public function test_a_team_without_a_cycle_reads_as_having_none(): void {
        $this->assertNull( ( new VctTeamCyclesRepository() )->findForTeamSeason( $this->teamId, self::SEASON ) );
        $this->assertSame( [], ( new VctTeamCyclesRepository() )->listForSeason( self::SEASON ) );
        $this->assertSame( [], ( new VctCycleWeekOverridesRepository() )->listForSeason( $this->teamId, self::SEASON ) );
    }
}
