<?php
namespace TT\Modules\Vct\Services;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Domain\Vocabularies\Lookups\ActivityTypeKey;
use TT\Infrastructure\Tenancy\CurrentClub;
use TT\Modules\Pdp\Repositories\SeasonsRepository;

/**
 * VctActivityStamper — writes the cycle week onto a training activity
 * (#3362, epic #3354).
 *
 * The stamp is a **snapshot**, and that is deliberate. A training records
 * the cycle week it was planned as; adding a fixture in October shifts
 * every week after it, and past trainings keep what they said. That is
 * what makes a player's training history readable months later, rather
 * than a list that quietly rewrites itself whenever a cycle is
 * reconfigured.
 *
 * Future trainings are a different matter — they have not happened, so
 * they are re-stamped when the thing they were derived from moves.
 *
 * ## One service, one hook
 *
 * Activities are written from at least four places (the wizard, the
 * manage form, the Spond import, VCT publish). Rather than four copies
 * of resolve-and-write, everything hangs off `tt_activity_saved`, the
 * action `ActivitiesRepository` already fires on every create and update.
 *
 * The stamp itself is written with a direct `$wpdb->update()` rather than
 * back through the repository: it is a derived column, not an edit, and
 * routing it through `update()` would re-fire `tt_activity_saved` and
 * recurse.
 */
class VctActivityStamper {

    public static function init(): void {
        add_action( 'tt_activity_saved', [ __CLASS__, 'onActivitySaved' ], 10, 2 );
    }

    /**
     * @param array<string,mixed> $data Columns as written.
     */
    public static function onActivitySaved( int $activity_id, array $data ): void {
        if ( $activity_id <= 0 ) return;

        $row = self::readActivity( $activity_id );
        if ( $row === null ) return;

        $type = (string) $row['activity_type_key'];

        if ( $type === ActivityTypeKey::TRAINING ) {
            self::stamp( $activity_id, (int) $row['team_id'], (string) $row['session_date'], (int) $row['vct_cycle_manual'] === 1 );
            return;
        }

        // A fixture moving shifts every week after it, so the trainings
        // that follow it need their stamp recomputed. Archiving and
        // cancelling arrive here too — both are writes through
        // ActivitiesRepository::update(), so the row is still readable.
        if ( ActivityTypeKey::isMatchLike( $type ) ) {
            self::restampFrom( (int) $row['team_id'], (string) $row['session_date'] );
        }
    }

    /**
     * Write the cycle week onto one training. A training whose stamp a
     * human set is left alone — that is what `vct_cycle_manual` is for.
     */
    public static function stamp( int $activity_id, int $team_id, string $session_date, bool $is_manual ): void {
        if ( $is_manual ) return;
        if ( $activity_id <= 0 || $team_id <= 0 ) return;

        $season_id = self::seasonFor( $session_date );
        $week      = $season_id > 0
            ? ( new VctCycleResolver() )->resolveWeek( $team_id, $season_id, $session_date )
            : null;

        // No cycle, or a date before it starts. Clearing rather than
        // leaving a stale stamp: a training moved out of the cycle should
        // not keep claiming a week it is no longer in.
        $fields = $week === null
            ? [ 'vct_cycle_id' => null, 'vct_cycle_week' => null, 'vct_cycle_state' => null ]
            : [
                'vct_cycle_id'    => self::cycleIdFor( $team_id, $season_id ),
                'vct_cycle_week'  => $week['cycle_week'],
                'vct_cycle_state' => $week['state'],
            ];

        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'tt_activities',
            $fields,
            [ 'id' => $activity_id, 'club_id' => CurrentClub::id() ]
        );
    }

    /**
     * Re-stamp a team's trainings from `$from_date`'s week forward.
     *
     * Forward only. Past trainings keep the week they were planned as —
     * see the class docblock; this is the decision, not an oversight.
     * Manually-stamped rows are skipped in SQL rather than in PHP so a
     * coach's override survives a bulk pass without being read back.
     */
    public static function restampFrom( int $team_id, string $from_date ): void {
        if ( $team_id <= 0 ) return;
        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $from_date ) ) return;

        $monday = \TT\Modules\Vct\Repositories\VctTeamCyclesRepository::normaliseToMonday( $from_date );
        if ( $monday === null ) return;

        global $wpdb;
        $activities = $wpdb->prefix . 'tt_activities';

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, session_date FROM {$activities}
              WHERE club_id = %d
                AND team_id = %d
                AND activity_type_key = %s
                AND archived_at IS NULL
                AND vct_cycle_manual = 0
                AND session_date >= %s
              ORDER BY session_date ASC
              LIMIT 500",
            CurrentClub::id(), $team_id, ActivityTypeKey::TRAINING, $monday
        ), ARRAY_A );
        if ( ! is_array( $rows ) ) return;

        foreach ( $rows as $row ) {
            if ( ! is_array( $row ) ) continue;
            self::stamp( (int) ( $row['id'] ?? 0 ), $team_id, (string) ( $row['session_date'] ?? '' ), false );
        }
    }

    /**
     * Set or clear a human's override on one training. Setting one marks
     * the row manual, which exempts it from every re-stamp pass from then
     * on; clearing it hands the training back to the cycle and re-stamps
     * it immediately, so the field never shows a stale value.
     */
    public static function setOverride( int $activity_id, int $team_id, string $session_date, ?string $state, ?int $cycle_week ): bool {
        if ( $activity_id <= 0 ) return false;

        global $wpdb;
        $activities = $wpdb->prefix . 'tt_activities';

        if ( $state === null ) {
            $ok = $wpdb->update(
                $activities,
                [ 'vct_cycle_manual' => 0 ],
                [ 'id' => $activity_id, 'club_id' => CurrentClub::id() ]
            );
            if ( $ok === false ) return false;
            self::stamp( $activity_id, $team_id, $session_date, false );
            return true;
        }

        if ( $state !== VctCycleResolver::STATE_ACTIVE && $state !== VctCycleResolver::STATE_NEUTRAL ) {
            return false;
        }

        $ok = $wpdb->update(
            $activities,
            [
                'vct_cycle_state'  => $state,
                'vct_cycle_week'   => $state === VctCycleResolver::STATE_NEUTRAL ? null : $cycle_week,
                'vct_cycle_manual' => 1,
            ],
            [ 'id' => $activity_id, 'club_id' => CurrentClub::id() ]
        );
        return $ok !== false;
    }

    /**
     * The season whose range contains `$date`. Returns 0 when none does —
     * a training outside every configured season carries no cycle rather
     * than being forced into the current one.
     */
    public static function seasonFor( string $date ): int {
        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) return 0;

        /** @var list<object{id:int|string, start_date:string, end_date:string}> $seasons */
        $seasons = ( new SeasonsRepository() )->all();
        foreach ( $seasons as $season ) {
            $start = (string) $season->start_date;
            $end   = (string) $season->end_date;
            if ( $date >= $start && $date <= $end ) return (int) $season->id;
        }
        return 0;
    }

    private static function cycleIdFor( int $team_id, int $season_id ): ?int {
        $cycle = ( new \TT\Modules\Vct\Repositories\VctTeamCyclesRepository() )->findForTeamSeason( $team_id, $season_id );
        return $cycle !== null ? (int) $cycle['id'] : null;
    }

    /** @return array<string,mixed>|null */
    private static function readActivity( int $activity_id ): ?array {
        global $wpdb;
        $activities = $wpdb->prefix . 'tt_activities';

        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT id, team_id, session_date, activity_type_key, vct_cycle_manual
               FROM {$activities}
              WHERE id = %d AND club_id = %d
              LIMIT 1",
            $activity_id, CurrentClub::id()
        ), ARRAY_A );

        return is_array( $row ) ? $row : null;
    }
}
