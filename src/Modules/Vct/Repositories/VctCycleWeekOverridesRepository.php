<?php
namespace TT\Modules\Vct\Repositories;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Infrastructure\Tenancy\CurrentClub;

/**
 * VctCycleWeekOverridesRepository — manual cycle-week overrides (#3357,
 * epic #3354).
 *
 * Deliberately sparse. Most weeks have no row: the resolver computes the
 * season's week list from the cycle anchor, its length and the team's
 * fixture dates, and reads this table only for the weeks a human has
 * overruled. Setting a week back to `auto` deletes its row rather than
 * storing an `auto` state — "no row means no exception" is what keeps the
 * resolver's reading of this table honest.
 *
 * Two override directions, both load-bearing:
 *
 *   - `neutral` pauses a week that has no fixture in it.
 *   - `active` runs a week normally *despite* a fixture. Without it the
 *     automation could only be overridden in the direction that rarely
 *     matters; a coach playing a friendly they do not want the cycle to
 *     pause for is the case that actually comes up.
 */
class VctCycleWeekOverridesRepository {

    public const STATE_NEUTRAL = 'neutral';
    public const STATE_ACTIVE  = 'active';

    private function table(): string {
        global $wpdb;
        return $wpdb->prefix . 'tt_vct_cycle_weeks';
    }

    /**
     * Every override for one (team, season), keyed by the week's Monday so
     * the resolver can look each week up as it walks.
     *
     * @return array<string, array{state:string, note:string|null, set_by:int|null, set_at:string}>
     */
    public function listForSeason( int $team_id, int $season_id ): array {
        if ( $team_id <= 0 || $season_id <= 0 ) return [];

        global $wpdb;
        $table = $this->table();

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT week_starts_on, state, note, set_by, set_at
               FROM {$table}
              WHERE club_id = %d AND team_id = %d AND season_id = %d
           ORDER BY week_starts_on ASC",
            CurrentClub::id(), $team_id, $season_id
        ), ARRAY_A );
        if ( ! is_array( $rows ) ) return [];

        $out = [];
        foreach ( $rows as $row ) {
            if ( ! is_array( $row ) ) continue;
            $set_by = isset( $row['set_by'] ) ? (int) $row['set_by'] : 0;
            $note   = isset( $row['note'] ) ? (string) $row['note'] : '';
            $out[ (string) ( $row['week_starts_on'] ?? '' ) ] = [
                'state'  => (string) ( $row['state'] ?? '' ),
                'note'   => $note !== '' ? $note : null,
                'set_by' => $set_by > 0 ? $set_by : null,
                'set_at' => (string) ( $row['set_at'] ?? '' ),
            ];
        }
        return $out;
    }

    /**
     * Force one week neutral or active. `$week_starts_on` is normalised to
     * its Monday, so a caller passing any day of the week lands on the same
     * row rather than creating a second one for the same week.
     */
    public function set( int $team_id, int $season_id, string $week_starts_on, string $state, ?string $note = null, ?int $set_by = null ): bool {
        if ( $team_id <= 0 || $season_id <= 0 ) return false;
        if ( ! self::isValidState( $state ) ) return false;

        $week = VctTeamCyclesRepository::normaliseToMonday( $week_starts_on );
        if ( $week === null ) return false;

        global $wpdb;
        $table = $this->table();

        $existing = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$table}
              WHERE club_id = %d AND team_id = %d AND week_starts_on = %s
              LIMIT 1",
            CurrentClub::id(), $team_id, $week
        ) );

        if ( $existing > 0 ) {
            $ok = $wpdb->update(
                $table,
                [
                    'season_id' => $season_id,
                    'state'     => $state,
                    'note'      => $note,
                    'set_by'    => $set_by,
                    'set_at'    => current_time( 'mysql' ),
                ],
                [ 'id' => $existing, 'club_id' => CurrentClub::id() ]
            );
            return $ok !== false;
        }

        $ok = $wpdb->insert( $table, [
            'club_id'        => CurrentClub::id(),
            'team_id'        => $team_id,
            'season_id'      => $season_id,
            'week_starts_on' => $week,
            'state'          => $state,
            'note'           => $note,
            'set_by'         => $set_by,
            'set_at'         => current_time( 'mysql' ),
        ] );
        return $ok !== false;
    }

    /** Return one week to automatic resolution by removing its row. */
    public function clear( int $team_id, string $week_starts_on ): bool {
        if ( $team_id <= 0 ) return false;

        $week = VctTeamCyclesRepository::normaliseToMonday( $week_starts_on );
        if ( $week === null ) return false;

        global $wpdb;
        $ok = $wpdb->delete( $this->table(), [
            'club_id'        => CurrentClub::id(),
            'team_id'        => $team_id,
            'week_starts_on' => $week,
        ] );
        return $ok !== false;
    }

    /** Drop every override for a (team, season) — used when a cycle is removed. */
    public function clearSeason( int $team_id, int $season_id ): bool {
        if ( $team_id <= 0 || $season_id <= 0 ) return false;

        global $wpdb;
        $ok = $wpdb->delete( $this->table(), [
            'club_id'   => CurrentClub::id(),
            'team_id'   => $team_id,
            'season_id' => $season_id,
        ] );
        return $ok !== false;
    }

    public static function isValidState( string $state ): bool {
        return $state === self::STATE_NEUTRAL || $state === self::STATE_ACTIVE;
    }
}
