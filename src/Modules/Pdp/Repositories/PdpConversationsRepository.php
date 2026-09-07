<?php
namespace TT\Modules\Pdp\Repositories;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Infrastructure\Tenancy\CurrentClub;

/**
 * PdpConversationsRepository — N conversations per PDP file.
 *
 * createCycle() seeds the full set of N rows when a file is opened.
 * Subsequent calls update individual rows by id. All reads + writes
 * are club-scoped per #0052 PR-A.
 */
class PdpConversationsRepository {

    private \wpdb $wpdb;
    private string $table;

    public function __construct() {
        global $wpdb;
        $this->wpdb  = $wpdb;
        $this->table = $wpdb->prefix . 'tt_pdp_conversations';
    }

    public function find( int $id ): ?object {
        if ( $id <= 0 ) return null;
        $row = $this->wpdb->get_row( $this->wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE id = %d AND club_id = %d",
            $id, CurrentClub::id()
        ) );
        return $row ?: null;
    }

    /**
     * v3.110.197 (#809) — single predicate for "this conversation is
     * read-only because someone has signed it." Once ANY of the three
     * signature timestamps (`coach_signoff_at`, `parent_ack_at`,
     * `player_ack_at`) is set, the conversation freezes — coach can
     * no longer edit notes / agenda / dates / actions, and the second
     * signatory can still ack their own column but cannot change the
     * content that was put in front of them. Source of truth for the
     * frontend form (renders fields disabled), the REST PATCH guard
     * (rejects non-signature mutations), and any other consumer that
     * needs the read-only signal.
     *
     * Accepts either a row object (with the three columns populated)
     * or null (returns false — there's nothing to lock).
     */
    public static function isLocked( ?object $row ): bool {
        if ( $row === null ) return false;
        if ( ! empty( $row->coach_signoff_at ) ) return true;
        if ( ! empty( $row->parent_ack_at ) )    return true;
        if ( ! empty( $row->player_ack_at ) )    return true;
        return false;
    }

    /**
     * #1852 — every conversation whose planning window has opened on or
     * before $today and that hasn't been conducted yet, with the file's
     * player_id joined. Drives the self-review nudge sweep. Club-scoped.
     *
     * @return object[] rows: id, scheduled_at, planning_window_start, player_id
     */
    public function listEnteringPlanningWindow( string $today ): array {
        $files = $this->wpdb->prefix . 'tt_pdp_files';
        $rows = $this->wpdb->get_results( $this->wpdb->prepare(
            "SELECT c.id, c.scheduled_at, c.planning_window_start, f.player_id
               FROM {$this->table} c
               JOIN {$files} f ON f.id = c.pdp_file_id
              WHERE c.club_id = %d
                AND c.conducted_at IS NULL
                AND c.scheduled_at IS NOT NULL
                AND c.planning_window_start IS NOT NULL
                AND c.planning_window_start <= %s
              ORDER BY c.scheduled_at ASC",
            CurrentClub::id(), $today
        ) );
        return is_array( $rows ) ? $rows : [];
    }

    /**
     * #2041 — the "active" conversation in a cycle is the earliest by
     * sequence that hasn't been signed off yet. Only it is fully
     * editable; later conversations are content-locked (the coach can
     * still adjust their planned date to schedule ahead). Returns that
     * conversation's id, or 0 when every conversation is signed off or
     * the file has none. listForFile() is already ordered by sequence,
     * so the first un-signed row is the active one.
     */
    public function activeConversationId( int $file_id ): int {
        foreach ( $this->listForFile( $file_id ) as $c ) {
            if ( empty( $c->coach_signoff_at ) ) {
                return (int) $c->id;
            }
        }
        return 0;
    }

    /** @return object[] */
    public function listForFile( int $file_id ): array {
        if ( $file_id <= 0 ) return [];
        $rows = $this->wpdb->get_results( $this->wpdb->prepare(
            "SELECT * FROM {$this->table}
              WHERE pdp_file_id = %d AND club_id = %d
              ORDER BY sequence ASC",
            $file_id, CurrentClub::id()
        ) );
        return is_array( $rows ) ? $rows : [];
    }

    /**
     * Seed the full conversation cycle for a freshly-created file.
     * Distributes scheduled_at evenly between the season's start +
     * end. cycle_size is one of 2 / 3 / 4. Returns the count inserted
     * (0 if any guard fails).
     */
    public function createCycle( int $file_id, int $cycle_size, string $season_start, string $season_end, int $season_id = 0 ): int {
        if ( $file_id <= 0 ) return 0;
        if ( ! in_array( $cycle_size, [ 2, 3, 4 ], true ) ) return 0;

        // v3.110.191 — read academy-configured blocks if present. The
        // block count must match the file's cycle_size for the
        // configured dates to apply; otherwise the legacy even-divide
        // fallback runs.
        if ( $season_id > 0 ) {
            $configured = ( new PdpBlocksRepository() )->listForSeason( $season_id );
            if ( count( $configured ) === $cycle_size ) {
                return $this->createCycleFromBlocks( $file_id, $cycle_size, $configured );
            }
        }

        $start_ts = strtotime( $season_start . ' 00:00:00' );
        $end_ts   = strtotime( $season_end   . ' 23:59:59' );
        if ( ! $start_ts || ! $end_ts || $end_ts <= $start_ts ) return 0;

        // Distribute evenly. The Nth conversation lands at the
        // mid-point of the Nth slice — first conversation early-
        // season, last conversation late-season, never on the very
        // first or last day.
        $span = $end_ts - $start_ts;
        $step = (int) floor( $span / ( $cycle_size + 1 ) );

        $template_keys = $this->templateKeysFor( $cycle_size );

        // #0054 — planning window length is admin-configurable (default 21 days).
        $window_days = (int) \TT\Infrastructure\Query\QueryHelpers::get_config( 'pdp_planning_window_days', '21' );
        if ( $window_days < 7 ) $window_days = 21;
        $half = (int) floor( $window_days / 2 );

        $inserted = 0;
        for ( $i = 1; $i <= $cycle_size; $i++ ) {
            $when_ts = $start_ts + $step * $i;
            $when    = gmdate( 'Y-m-d H:i:s', $when_ts );

            $win_start = max( $start_ts, $when_ts - $half * 86400 );
            $win_end   = min( $end_ts,   $when_ts + $half * 86400 );

            $ok = $this->wpdb->insert( $this->table, [
                'club_id'               => CurrentClub::id(),
                'pdp_file_id'           => $file_id,
                'sequence'              => $i,
                'template_key'          => $template_keys[ $i - 1 ] ?? 'mid',
                'scheduled_at'          => $when,
                'planning_window_start' => gmdate( 'Y-m-d', $win_start ),
                'planning_window_end'   => gmdate( 'Y-m-d', $win_end ),
            ] );
            if ( $ok ) $inserted++;
        }
        return $inserted;
    }

    /**
     * @param array<string,mixed> $patch
     */
    public function update( int $id, array $patch ): bool {
        if ( $id <= 0 || empty( $patch ) ) return false;

        // #3306 (epic #3301) — `agenda` is gone from the writable set. The
        // coach's preparation is the question sets in
        // `tt_pdp_prep_answers`; the column keeps its content for one
        // release so migration 0257's move is reversible, and nothing
        // writes to it after this release.
        $allowed = [
            'scheduled_at', 'conducted_at', 'notes',
            'agreed_actions', 'player_reflection',
            'coach_signoff_at', 'parent_ack_at', 'player_ack_at',
            'planning_window_start', 'planning_window_end',
        ];
        $clean = [];
        foreach ( $patch as $k => $v ) {
            if ( in_array( $k, $allowed, true ) ) $clean[ $k ] = $v;
        }
        if ( empty( $clean ) ) return false;

        $ok = $this->wpdb->update( $this->table, $clean, [ 'id' => $id, 'club_id' => CurrentClub::id() ] );
        if ( $ok === false ) return false;

        /**
         * #2731 — a conversation in a PDP cycle was updated. Stamping
         * `conducted_at` is the interesting case, and the only one the
         * alerts engine reacts to, but the event is not narrowed to it:
         * a hook that fires for some updates and not others is the kind
         * anyone integrating later gets wrong.
         *
         * @param int $conversation_id
         * @param int $pdp_file_id
         */
        do_action(
            'tt_pdp_conversation_saved',
            $id,
            (int) ( $this->find( $id )->pdp_file_id ?? 0 )
        );

        return true;
    }

    /**
     * v3.110.191 — create the cycle from academy-configured blocks.
     * `scheduled_at` is the block's midpoint (so the visible date
     * lands roughly in the middle of the window); planning windows
     * are copied verbatim from the block's start/end dates.
     *
     * @param list<array{sequence:int,start_date:string,end_date:string}> $blocks
     */
    private function createCycleFromBlocks( int $file_id, int $cycle_size, array $blocks ): int {
        $template_keys = $this->templateKeysFor( $cycle_size );
        usort( $blocks, static fn( $a, $b ): int => $a['sequence'] <=> $b['sequence'] );

        $inserted = 0;
        foreach ( $blocks as $i => $b ) {
            $win_start = (string) $b['start_date'];
            $win_end   = (string) $b['end_date'];
            $mid_ts = (int) ( ( strtotime( $win_start . ' 00:00:00' ) + strtotime( $win_end . ' 23:59:59' ) ) / 2 );
            $when   = gmdate( 'Y-m-d H:i:s', $mid_ts );

            $seq = (int) $b['sequence'];
            $ok = $this->wpdb->insert( $this->table, [
                'club_id'               => CurrentClub::id(),
                'pdp_file_id'           => $file_id,
                'sequence'              => $seq,
                'template_key'          => $template_keys[ $i ] ?? 'mid',
                'scheduled_at'          => $when,
                'planning_window_start' => $win_start,
                'planning_window_end'   => $win_end,
            ] );
            if ( $ok ) $inserted++;
        }
        return $inserted;
    }

    /** @return list<string> */
    private function templateKeysFor( int $cycle_size ): array {
        return match ( $cycle_size ) {
            2       => [ 'start', 'end' ],
            3       => [ 'start', 'mid', 'end' ],
            4       => [ 'start', 'mid_a', 'mid_b', 'end' ],
            default => [],
        };
    }
}
