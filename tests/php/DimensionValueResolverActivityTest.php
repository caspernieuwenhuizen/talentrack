<?php
namespace TT\Tests\Php;

use WP_UnitTestCase;
use TT\Modules\Analytics\Domain\Dimension;
use TT\Modules\Analytics\Domain\DimensionValueResolver;

/**
 * #3356 — the activity dimension selected `activity_date`, which is not a
 * column on `tt_activities` (the column is `session_date`). The query failed,
 * `get_row()` returned null, and every activity dimension fell through to the
 * missing-id label. The report still rendered, it was just wrong — the worst
 * shape a reporting bug can take, and the reason this needs a test rather
 * than a code read.
 */
final class DimensionValueResolverActivityTest extends WP_UnitTestCase {

    public function test_activity_dimension_resolves_type_and_date(): void {
        global $wpdb;

        $wpdb->insert( $wpdb->prefix . 'tt_teams', [ 'club_id' => 1, 'name' => 'Dimension Team' ] );
        $team_id = (int) $wpdb->insert_id;

        $wpdb->insert( $wpdb->prefix . 'tt_activities', [
            'club_id'           => 1,
            'team_id'           => $team_id,
            'title'             => 'Dimension training',
            'session_date'      => '2026-02-17',
            'activity_type_key' => 'training',
        ] );
        $activity_id = (int) $wpdb->insert_id;

        $dim   = new Dimension( 'activity_id', 'Activity', Dimension::TYPE_FOREIGN_KEY, 'tt_activities' );
        $label = DimensionValueResolver::resolve( $dim, (string) $activity_id );

        $this->assertStringContainsString( '2026-02-17', $label );
        $this->assertNotSame( (string) $activity_id, $label );
    }
}
