<?php
namespace TT\Modules\Vct\Rules\Providers;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Domain\Vocabularies\Lookups\ActivityTypeKey;
use TT\Infrastructure\Tenancy\CurrentClub;
use TT\Modules\Vct\Repositories\VctTeamCyclesRepository;

/**
 * NativeFixtureWeeksReader — production implementation.
 *
 * Reads `tt_activities` directly through a narrow query rather than
 * reaching into ActivitiesRepository, the same way NativeActivitiesReader
 * does: if Activities relocates its repository, only this adapter changes.
 *
 * One query per season. The Monday is computed in PHP rather than with
 * MySQL's WEEK()/YEARWEEK(), which depend on the server's
 * `default_week_format` and disagree with PHP's ISO weeks around the turn
 * of the year — exactly where a season's cycle is mid-run.
 */
class NativeFixtureWeeksReader implements FixtureWeeksReader {

    public function matchWeekMondays( int $team_id, string $from, string $to ): array {
        if ( $team_id <= 0 ) return [];
        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $from ) ) return [];
        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $to ) ) return [];

        global $wpdb;
        $activities = $wpdb->prefix . 'tt_activities';
        $match_like = ActivityTypeKey::MATCH_LIKE_SQL;

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $dates = $wpdb->get_col( $wpdb->prepare(
            "SELECT DISTINCT session_date FROM {$activities}
              WHERE club_id = %d
                AND team_id = %d
                AND activity_type_key IN ({$match_like})
                AND archived_at IS NULL
                AND ( activity_status_key IS NULL OR activity_status_key <> 'cancelled' )
                AND session_date BETWEEN %s AND %s
              ORDER BY session_date ASC",
            CurrentClub::id(), $team_id, $from, $to
        ) );
        if ( ! is_array( $dates ) ) return [];

        $mondays = [];
        foreach ( $dates as $date ) {
            $monday = VctTeamCyclesRepository::normaliseToMonday( (string) $date );
            if ( $monday !== null ) $mondays[ $monday ] = true;
        }

        $out = array_keys( $mondays );
        sort( $out );
        return $out;
    }
}
