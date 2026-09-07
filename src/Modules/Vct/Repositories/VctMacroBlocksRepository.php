<?php
namespace TT\Modules\Vct\Repositories;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Infrastructure\Tenancy\CurrentClub;

/**
 * VctMacroBlocksRepository — periodization calendar. Spec § Integration
 * with team planning: a season's macro-blocks define the per-week intensity
 * multiplier the ProgressionRule applies.
 *
 * `findCurrent()` prefers per-team rows (`team_id = $team_id`) over the
 * club-wide season-default (`team_id = 0`), and ignores reference templates
 * stored at `season_id = 0`.
 */
class VctMacroBlocksRepository {

    private \wpdb $wpdb;
    private string $table;

    public function __construct() {
        global $wpdb;
        $this->wpdb  = $wpdb;
        $this->table = $wpdb->prefix . 'tt_vct_macro_blocks';
    }

    /**
     * Return the macro-block that covers $date for $team_id within $season_id.
     * Prefers per-team override; falls back to club-wide season default.
     * Reference templates (season_id = 0) are excluded.
     *
     * @return array{id:int, sequence:int, label:string, start_date:string, end_date:string, phase_profile:list<array<string,mixed>>}|null
     */
    public function findCurrent( int $team_id, int $season_id, string $date ): ?array {
        if ( $season_id <= 0 ) return null;
        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) return null;

        // Per-team first.
        $row = $this->wpdb->get_row( $this->wpdb->prepare(
            "SELECT id, sequence, label, start_date, end_date, phase_profile_json
               FROM {$this->table}
              WHERE club_id = %d AND season_id = %d AND team_id = %d
                AND archived_at IS NULL
                AND start_date <= %s AND end_date >= %s
              ORDER BY sequence ASC
              LIMIT 1",
            CurrentClub::id(), $season_id, $team_id, $date, $date
        ) );
        if ( ! $row && $team_id > 0 ) {
            // Fall back to club-wide season default.
            $row = $this->wpdb->get_row( $this->wpdb->prepare(
                "SELECT id, sequence, label, start_date, end_date, phase_profile_json
                   FROM {$this->table}
                  WHERE club_id = %d AND season_id = %d AND team_id = 0
                    AND archived_at IS NULL
                    AND start_date <= %s AND end_date >= %s
                  ORDER BY sequence ASC
                  LIMIT 1",
                CurrentClub::id(), $season_id, $date, $date
            ) );
        }
        if ( ! $row ) return null;
        return $this->hydrate( $row );
    }

    /** @return list<array<string,mixed>> */
    public function listForSeason( int $team_id, int $season_id ): array {
        if ( $season_id <= 0 ) return [];
        $rows = $this->wpdb->get_results( $this->wpdb->prepare(
            "SELECT id, sequence, label, start_date, end_date, phase_profile_json
               FROM {$this->table}
              WHERE club_id = %d AND season_id = %d AND (team_id = %d OR team_id = 0)
                AND archived_at IS NULL
              ORDER BY team_id DESC, sequence ASC",
            CurrentClub::id(), $season_id, $team_id
        ) );
        if ( ! is_array( $rows ) ) return [];
        return array_map( [ $this, 'hydrate' ], $rows );
    }

    /**
     * Replace the full block set for (team_id, season_id). Used by
     * `PUT /vct/macro-blocks` after server-side overlap/gap/season-
     * boundary validation. Pattern mirrors `PdpBlocksRepository::
     * replaceForSeason()`.
     *
     * @param list<array<string,mixed>> $blocks
     */
    public function replaceForSeason( int $team_id, int $season_id, array $blocks ): bool {
        if ( $season_id <= 0 ) return false;
        $club_id = CurrentClub::id();

        // Wipe existing (preserves reference templates at season_id=0).
        $this->wpdb->delete( $this->table, [
            'club_id'   => $club_id,
            'team_id'   => $team_id,
            'season_id' => $season_id,
        ] );

        foreach ( $blocks as $b ) {
            $ok = $this->wpdb->insert( $this->table, [
                'club_id'            => $club_id,
                'uuid'               => wp_generate_uuid4(),
                'season_id'          => $season_id,
                'team_id'            => $team_id,
                'sequence'           => (int)    ( $b['sequence']   ?? 0 ),
                'label'              => (string) ( $b['label']      ?? '' ),
                'start_date'         => (string) ( $b['start_date'] ?? '' ),
                'end_date'           => (string) ( $b['end_date']   ?? '' ),
                'phase_profile_json' => isset( $b['phase_profile'] )
                    ? wp_json_encode( (array) $b['phase_profile'] )
                    : ( $b['phase_profile_json'] ?? null ),
            ] );
            if ( $ok === false ) return false;
        }
        return true;
    }

    /** @return list<array<string,mixed>> The seeded reference templates. */
    public function listReferenceTemplates(): array {
        $rows = $this->wpdb->get_results( $this->wpdb->prepare(
            "SELECT id, sequence, label, start_date, end_date, phase_profile_json
               FROM {$this->table}
              WHERE club_id = %d AND season_id = 0 AND team_id = 0
                AND archived_at IS NULL
              ORDER BY sequence ASC",
            CurrentClub::id()
        ) );
        if ( ! is_array( $rows ) ) return [];
        return array_map( [ $this, 'hydrate' ], $rows );
    }

    /** @param object $row */
    private function hydrate( $row ): array {
        $profile = json_decode( (string) $row->phase_profile_json, true );
        return [
            'id'            => (int)    $row->id,
            'sequence'      => (int)    $row->sequence,
            'label'         => (string) $row->label,
            'start_date'    => (string) $row->start_date,
            'end_date'      => (string) $row->end_date,
            'phase_profile' => is_array( $profile ) ? self::normalisePhaseProfile( $profile ) : [],
        ];
    }

    /**
     * Normalise the decoded weekly phase profile so every week entry
     * carries the full shape { week, phase, multiplier, tactical_theme }.
     *
     * `tactical_theme` (#2322) is the per-week speelwijze theme layered on
     * top of the conditioning phase. It is OPTIONAL — legacy blocks stored
     * before #2322 have no `tactical_theme` key; those default to null here
     * so the read tab and any consumer can treat "no theme" uniformly. The
     * key is validated against the canonical `vct_tactical_theme` vocabulary
     * on write (REST layer); read is trusting.
     *
     * @param array<int,mixed> $profile
     * @return list<array<string,mixed>>
     */
    private static function normalisePhaseProfile( array $profile ): array {
        $out = [];
        foreach ( $profile as $week ) {
            if ( ! is_array( $week ) ) continue;
            $theme = isset( $week['tactical_theme'] ) && $week['tactical_theme'] !== ''
                ? (string) $week['tactical_theme']
                : null;
            $out[] = [
                'week'           => isset( $week['week'] ) ? (int) $week['week'] : 0,
                'phase'          => isset( $week['phase'] ) ? (string) $week['phase'] : '',
                'multiplier'     => isset( $week['multiplier'] ) ? (float) $week['multiplier'] : 1.0,
                'tactical_theme' => $theme,
            ];
        }
        return $out;
    }
}
