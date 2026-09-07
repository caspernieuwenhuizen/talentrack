<?php
namespace TT\Modules\Vct\Repositories;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Infrastructure\Tenancy\CurrentClub;

/**
 * VctTeamCyclesRepository — the per-team repeating cycle (#3357, epic #3354).
 *
 * A macro-block is a dated season phase; a cycle is the 3/4/6-week rhythm
 * that repeats inside it. One row per (team, season). No row means the team
 * has no cycle, and the macro-block behaviour applies unchanged — so every
 * caller must treat null as a normal answer rather than an error.
 *
 * `anchor_date` is normalised to a Monday on write. Weeks are ISO weeks
 * throughout the cycle feature, and letting a mid-week anchor through would
 * put every later week boundary half a week out from the fixture list the
 * resolver compares it against.
 */
class VctTeamCyclesRepository {

    /** The cycle lengths the product offers. Validated here, not by a DB ENUM. */
    public const ALLOWED_WEEKS = [ 3, 4, 6 ];

    public const DEFAULT_WEEKS = 6;

    private \wpdb $wpdb;
    private string $table;

    public function __construct() {
        global $wpdb;
        $this->wpdb  = $wpdb;
        $this->table = $wpdb->prefix . 'tt_vct_team_cycles';
    }

    /**
     * @return array{id:int, uuid:string, team_id:int, season_id:int, cycle_weeks:int, anchor_date:string, template_id:?int}|null
     */
    public function findForTeamSeason( int $team_id, int $season_id ): ?array {
        if ( $team_id <= 0 || $season_id <= 0 ) return null;

        $row = $this->wpdb->get_row( $this->wpdb->prepare(
            "SELECT id, uuid, team_id, season_id, cycle_weeks, anchor_date, template_id
               FROM {$this->table}
              WHERE club_id = %d AND team_id = %d AND season_id = %d
                AND archived_at IS NULL
              LIMIT 1",
            CurrentClub::id(), $team_id, $season_id
        ) );
        if ( ! $row ) return null;
        return self::hydrate( $row );
    }

    /**
     * Every team's cycle for one season, keyed by team_id — one query for
     * the configuration screen rather than one per team.
     *
     * @return array<int, array{id:int, uuid:string, team_id:int, season_id:int, cycle_weeks:int, anchor_date:string, template_id:?int}>
     */
    public function listForSeason( int $season_id ): array {
        if ( $season_id <= 0 ) return [];

        $rows = $this->wpdb->get_results( $this->wpdb->prepare(
            "SELECT id, uuid, team_id, season_id, cycle_weeks, anchor_date, template_id
               FROM {$this->table}
              WHERE club_id = %d AND season_id = %d
                AND archived_at IS NULL
           ORDER BY team_id ASC",
            CurrentClub::id(), $season_id
        ) );
        if ( ! is_array( $rows ) ) return [];

        $out = [];
        foreach ( $rows as $row ) {
            $cycle = self::hydrate( $row );
            $out[ $cycle['team_id'] ] = $cycle;
        }
        return $out;
    }

    /**
     * Insert or update the team's cycle. Returns false when the length is
     * not one the product offers or the anchor is not a real date — the
     * caller surfaces that; a silently coerced cycle length would plan a
     * season nobody chose.
     */
    public function upsert( int $team_id, int $season_id, int $cycle_weeks, string $anchor_date, ?int $template_id = null ): bool {
        if ( $team_id <= 0 || $season_id <= 0 ) return false;
        if ( ! self::isAllowedLength( $cycle_weeks ) ) return false;

        $anchor = self::normaliseToMonday( $anchor_date );
        if ( $anchor === null ) return false;

        $existing = $this->findForTeamSeason( $team_id, $season_id );
        if ( $existing !== null ) {
            $ok = $this->wpdb->update(
                $this->table,
                [
                    'cycle_weeks' => $cycle_weeks,
                    'anchor_date' => $anchor,
                    'template_id' => $template_id,
                ],
                [ 'id' => $existing['id'], 'club_id' => CurrentClub::id() ]
            );
            return $ok !== false;
        }

        $ok = $this->wpdb->insert( $this->table, [
            'club_id'     => CurrentClub::id(),
            'uuid'        => wp_generate_uuid4(),
            'team_id'     => $team_id,
            'season_id'   => $season_id,
            'cycle_weeks' => $cycle_weeks,
            'anchor_date' => $anchor,
            'template_id' => $template_id,
        ] );
        return $ok !== false;
    }

    /**
     * Remove the team's cycle, returning them to macro-block planning.
     * A hard delete: the row is two numbers and a date, there is no
     * history in it worth keeping, and leaving an archived row behind
     * would collide with the (club, team, season) UNIQUE index the next
     * time somebody set one up.
     */
    public function delete( int $team_id, int $season_id ): bool {
        if ( $team_id <= 0 || $season_id <= 0 ) return false;

        $ok = $this->wpdb->delete( $this->table, [
            'club_id'   => CurrentClub::id(),
            'team_id'   => $team_id,
            'season_id' => $season_id,
        ] );
        return $ok !== false;
    }

    public static function isAllowedLength( int $weeks ): bool {
        return in_array( $weeks, self::ALLOWED_WEEKS, true );
    }

    /**
     * Snap a date back to the Monday of its ISO week. Returns null when
     * the input is not a `Y-m-d` date.
     */
    public static function normaliseToMonday( string $date ): ?string {
        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) return null;
        $ts = strtotime( $date );
        if ( $ts === false ) return null;

        // 'N' is 1 for Monday through 7 for Sunday.
        $weekday = (int) gmdate( 'N', $ts );
        return gmdate( 'Y-m-d', $ts - ( $weekday - 1 ) * 86400 );
    }

    /**
     * @param object $row
     * @return array{id:int, uuid:string, team_id:int, season_id:int, cycle_weeks:int, anchor_date:string, template_id:?int}
     */
    private static function hydrate( object $row ): array {
        $template_id = isset( $row->template_id ) ? (int) $row->template_id : 0;
        return [
            'id'          => (int) $row->id,
            'uuid'        => (string) $row->uuid,
            'team_id'     => (int) $row->team_id,
            'season_id'   => (int) $row->season_id,
            'cycle_weeks' => (int) $row->cycle_weeks,
            'anchor_date' => (string) $row->anchor_date,
            'template_id' => $template_id > 0 ? $template_id : null,
        ];
    }
}
