<?php
namespace TT\Modules\Vct\Rules\Providers;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Domain\Vocabularies\Lookups\ActivityTypeKey;
use TT\Infrastructure\Tenancy\CurrentClub;

/**
 * NativeActivitiesReader — production implementation of ActivitiesReader.
 *
 * Reads from the Activities module's `tt_activities` table directly
 * (narrow query) rather than reaching into ActivitiesRepository's public
 * surface. Keeps VCT decoupled from Activities' internal class layout —
 * if Activities renames or relocates its repository, only this adapter
 * changes.
 *
 * "Match" detection uses `ActivityTypeKey::MATCH_LIKE` — game and
 * tournament, the latter because a tournament is a multi-game day
 * (#2686) and anchors a match-day context exactly like a game does.
 * Cancelled fixtures are excluded: a called-off game should not pull
 * the week's trainings into an MD-1 shape.
 */
class NativeActivitiesReader implements ActivitiesReader {

    public function nextMatchDate( int $team_id, string $window_start, string $window_end ): ?string {
        return $this->matchDate( $team_id, $window_start, $window_end, 'ASC' );
    }

    public function previousMatchDate( int $team_id, string $window_start, string $window_end ): ?string {
        return $this->matchDate( $team_id, $window_start, $window_end, 'DESC' );
    }

    private function matchDate( int $team_id, string $window_start, string $window_end, string $direction ): ?string {
        if ( $team_id <= 0 ) return null;
        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $window_start ) ) return null;
        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $window_end ) ) return null;
        $direction = $direction === 'DESC' ? 'DESC' : 'ASC';

        global $wpdb;
        $activities = $wpdb->prefix . 'tt_activities';
        $match_like = ActivityTypeKey::MATCH_LIKE_SQL;
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $date = $wpdb->get_var( $wpdb->prepare(
            "SELECT session_date FROM {$activities}
              WHERE club_id = %d
                AND team_id = %d
                AND activity_type_key IN ({$match_like})
                AND archived_at IS NULL
                AND ( activity_status_key IS NULL OR activity_status_key <> 'cancelled' )
                AND session_date BETWEEN %s AND %s
              ORDER BY session_date {$direction}
              LIMIT 1",
            CurrentClub::id(), $team_id, $window_start, $window_end
        ) );
        return $date !== null ? (string) $date : null;
    }
}
