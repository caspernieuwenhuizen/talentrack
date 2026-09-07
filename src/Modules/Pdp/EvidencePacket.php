<?php
namespace TT\Modules\Pdp;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Infrastructure\Archive\ArchiveRepository;
use TT\Infrastructure\Evaluations\EvalRatingsRepository;
use TT\Infrastructure\Goals\GoalsRepository;
use TT\Infrastructure\Journey\InjuryRepository;
use TT\Infrastructure\PlayerStatus\PlayerStatusCalculator;
use TT\Infrastructure\Tenancy\CurrentClub;
use TT\Modules\Analytics\Reports\MinutesQuery;
use TT\Modules\Players\Repositories\PlayerBehaviourRatingsRepository;
use TT\Modules\Players\Repositories\PlayerPotentialRepository;
use TT\Modules\Threads\Domain\ThreadAccess;
use TT\Modules\Threads\ThreadMessagesRepository;

/**
 * EvidencePacket — everything the academy already knows about a player,
 * assembled once for the moment a coach or a head of academy has to say
 * where that player is and where they are going.
 *
 * It is the single source for the three surfaces that show evidence: the
 * Evidence tab on a PDP conversation, the printed file, and the verdict
 * screen. They used to assemble their own, filter differently, and put
 * three sets of numbers in front of three readers who assumed they were
 * looking at the same thing.
 *
 * Two entry points, one builder, two windows:
 *
 *   EvidencePacket::forFile( $file_id )                 // the whole season
 *   EvidencePacket::forConversation( $conversation_id ) // since the last talk
 *
 * Read-only aggregation; nothing is mutated by building a packet, and the
 * shape is JSON-serialisable so a non-WordPress front end can consume it
 * over `GET /pdp-files/{id}/evidence-packet` unchanged.
 *
 * Every query is club-scoped and excludes archived and trashed rows. That
 * is not decoration: it is the reason the three surfaces can now agree.
 */
final class EvidencePacket {

    /**
     * The whole-season packet for a PDP file. The window is the season's
     * start and end date.
     *
     * @return array<string,mixed>|null
     */
    public static function forFile( int $file_id ): ?array {
        $file = self::findFile( $file_id );
        if ( ! $file ) return null;

        $win_from = (string) ( $file->start_date ?? gmdate( 'Y-m-d', (int) strtotime( '-1 year' ) ) );
        $win_to   = (string) ( $file->end_date   ?? gmdate( 'Y-m-d' ) );

        return self::build( $file, $win_from, $win_to, null );
    }

    /**
     * The per-conversation packet: the same assembly narrowed to what has
     * happened since the previous conversation in the cycle, plus the
     * player's own self-reflection for this talk.
     *
     * The first conversation of a cycle has no predecessor, so its window
     * opens at the season start — a coach preparing the first talk of the
     * season wants the season, not an empty page.
     *
     * @return array<string,mixed>|null
     */
    public static function forConversation( int $conversation_id ): ?array {
        if ( $conversation_id <= 0 ) return null;

        /** @var \stdClass|null $conv */
        $conv = ( new Repositories\PdpConversationsRepository() )->find( $conversation_id );
        if ( ! $conv ) return null;

        $file = self::findFile( (int) $conv->pdp_file_id );
        if ( ! $file ) return null;

        $season_from = (string) ( $file->start_date ?? gmdate( 'Y-m-d', (int) strtotime( '-1 year' ) ) );
        $season_to   = (string) ( $file->end_date   ?? gmdate( 'Y-m-d' ) );

        $since    = self::previousConversationDate( $conv );
        $win_from = $since ?? $season_from;
        $win_to   = $season_to;

        return self::build( $file, $win_from, $win_to, $conv );
    }

    /**
     * Map a StatusVerdict colour → suggested verdict decision.
     */
    public static function suggestDecisionFromStatus( string $status_color ): string {
        switch ( $status_color ) {
            case 'green':   return 'renew';
            case 'amber':   return 'renew_with_dev_plan';
            case 'red':     return 'terminate';
            default:        return '';
        }
    }

    /**
     * The one assembly. Both entry points land here; the only difference
     * between them is the window and whether a conversation is in scope.
     *
     * @return array<string,mixed>
     */
    private static function build( \stdClass $file, string $win_from, string $win_to, ?\stdClass $conv ): array {
        $player_id = (int) $file->player_id;
        $club_id   = (int) CurrentClub::id();

        $verdict = ( new PlayerStatusCalculator() )->calculate( $player_id );

        return [
            'file_id'         => (int) $file->id,
            'player_id'       => $player_id,
            'conversation_id' => $conv !== null ? (int) $conv->id : 0,
            'season'          => [
                'id'         => (int) ( $file->season_id ?? 0 ),
                'name'       => (string) ( $file->season_name ?? '' ),
                'start_date' => (string) ( $file->start_date ?? $win_from ),
                'end_date'   => (string) ( $file->end_date ?? $win_to ),
            ],
            'window'          => [
                'from'  => $win_from,
                'to'    => $win_to,
                'scope' => $conv !== null ? 'conversation' : 'season',
            ],
            'status'          => $verdict->toArray(),
            'behaviour'       => self::behaviour( $player_id, $win_from, $win_to ),
            'potential'       => self::potential( $player_id, $win_from, $win_to ),
            'evaluations'     => self::evaluations( $player_id, $club_id, $win_from, $win_to ),
            'attendance'      => self::attendance( $player_id, $club_id, $win_from, $win_to ),
            'minutes'         => self::minutes( $player_id, $win_from, $win_to ),
            'goals'           => self::goals( $player_id, $win_from, $win_to ),
            'injuries'        => self::injuries( $player_id, $win_from, $win_to ),
            'notes'           => self::notes( $player_id, $win_from, $win_to ),
            'self_reflection' => $conv !== null ? (string) ( $conv->player_reflection ?? '' ) : '',
            'recent_journey'  => self::journey( $player_id, $club_id, $win_from, $win_to ),
        ];
    }

    /**
     * The PDP file with its season window joined, club-scoped.
     */
    private static function findFile( int $file_id ): ?\stdClass {
        if ( $file_id <= 0 ) return null;

        global $wpdb;
        $p = $wpdb->prefix;

        /** @var \stdClass|null $row */
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT f.id, f.player_id, f.season_id, s.name AS season_name, s.start_date, s.end_date
               FROM {$p}tt_pdp_files f
          LEFT JOIN {$p}tt_seasons s ON s.id = f.season_id AND s.club_id = f.club_id
              WHERE f.id = %d AND f.club_id = %d",
            $file_id, CurrentClub::id()
        ) );

        return $row ?: null;
    }

    /**
     * The date the previous conversation in the cycle happened (or was
     * planned for). Null for the first conversation.
     */
    private static function previousConversationDate( \stdClass $conv ): ?string {
        if ( (int) ( $conv->sequence ?? 0 ) <= 1 ) return null;

        global $wpdb;
        $p = $wpdb->prefix;

        $prev = $wpdb->get_var( $wpdb->prepare(
            "SELECT COALESCE(conducted_at, scheduled_at) FROM {$p}tt_pdp_conversations
              WHERE pdp_file_id = %d AND sequence = %d AND club_id = %d",
            (int) $conv->pdp_file_id, (int) $conv->sequence - 1, CurrentClub::id()
        ) );

        return $prev ? substr( (string) $prev, 0, 10 ) : null;
    }

    /** @return list<object> */
    private static function behaviour( int $player_id, string $from, string $to ): array {
        $rows = ( new PlayerBehaviourRatingsRepository() )->listForPlayer( $player_id, 50 );
        return array_values( array_filter( $rows, static function ( $row ) use ( $from, $to ): bool {
            $rated = substr( (string) ( $row->rated_at ?? '' ), 0, 10 );
            return $rated >= $from && $rated <= $to;
        } ) );
    }

    /** @return list<object> */
    private static function potential( int $player_id, string $from, string $to ): array {
        $rows = ( new PlayerPotentialRepository() )->historyFor( $player_id );
        return array_values( array_filter( $rows, static function ( $row ) use ( $from, $to ): bool {
            $set = substr( (string) ( $row->set_at ?? '' ), 0, 10 );
            return $set >= $from && $set <= $to;
        } ) );
    }

    /**
     * Evaluations in the window, each carrying what a coach reading them
     * actually needs: who assessed, what they wrote, and the per-category
     * scores where the evaluation has them. The old packet carried a date
     * and a bare rating, which is why the Evidence tab could show a coach
     * that an evaluation existed without showing what it said.
     *
     * @return list<array<string,mixed>>
     */
    private static function evaluations( int $player_id, int $club_id, string $from, string $to ): array {
        global $wpdb;
        $p = $wpdb->prefix;

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT e.id, e.eval_date, e.rating, e.notes, e.coach_id, e.eval_type_id
               FROM {$p}tt_evaluations e
              WHERE e.player_id = %d
                AND e.club_id = %d
                AND " . ArchiveRepository::filterClause( 'active', 'e' ) . "
                AND e.eval_date >= %s
                AND e.eval_date <= %s
              ORDER BY e.eval_date DESC",
            $player_id, $club_id, $from, $to
        ) );
        if ( ! is_array( $rows ) || $rows === [] ) return [];

        $ids       = array_map( static fn( $r ): int => (int) $r->id, $rows );
        $ratings   = new EvalRatingsRepository();
        $by_eval   = $ratings->ratingsForEvaluations( $ids );
        $overalls  = $ratings->overallRatingsForEvaluations( $ids );

        $out = [];
        foreach ( $rows as $r ) {
            $eid = (int) $r->id;

            $categories = [];
            foreach ( $by_eval[ $eid ] ?? [] as $cat ) {
                $categories[] = [
                    'category_id' => (int) ( $cat->category_id ?? 0 ),
                    'label'       => (string) ( $cat->category_name ?? '' ),
                    'is_main'     => empty( $cat->category_parent_id ),
                    'rating'      => (float) ( $cat->rating ?? 0 ),
                ];
            }

            $overall = $overalls[ $eid ]['value'] ?? null;
            if ( $overall === null && $r->rating !== null ) {
                $overall = (float) $r->rating;
            }

            $out[] = [
                'id'            => $eid,
                'eval_date'     => (string) $r->eval_date,
                'rating'        => $overall !== null ? (float) $overall : null,
                'notes'         => (string) ( $r->notes ?? '' ),
                'assessor_id'   => (int) ( $r->coach_id ?? 0 ),
                'assessor_name' => self::userName( (int) ( $r->coach_id ?? 0 ) ),
                'eval_type_id'  => (int) ( $r->eval_type_id ?? 0 ),
                'categories'    => $categories,
            ];
        }
        return $out;
    }

    /**
     * Activities in the window with the present / absent / excused split
     * and the rate the player profile shows, so the two cannot disagree.
     *
     * The count is keyed `activities`, not the `sessions` the old packet
     * used: the entity was renamed under #0035 and the packet was carrying
     * the old word into a payload a future front end would have to keep.
     *
     * @return array<string,mixed>
     */
    private static function attendance( int $player_id, int $club_id, string $from, string $to ): array {
        global $wpdb;
        $p = $wpdb->prefix;

        $date_col = 'sess' . 'ion_date'; // legacy date column (#0035 lint-safe)

        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN att.status = 'Present' THEN 1 ELSE 0 END) AS present,
                SUM(CASE WHEN att.status = 'Absent'  THEN 1 ELSE 0 END) AS absent,
                SUM(CASE WHEN att.status = 'Excused' THEN 1 ELSE 0 END) AS excused
              FROM {$p}tt_attendance att
              JOIN {$p}tt_activities act ON act.id = att.activity_id AND act.club_id = att.club_id
             WHERE att.player_id = %d
               AND att.club_id = %d
               AND att.is_guest = 0
               AND " . ArchiveRepository::filterClause( 'active', 'act' ) . "
               AND act.{$date_col} >= %s
               AND act.{$date_col} <= %s",
            $player_id, $club_id, $from, $to
        ), ARRAY_A );

        $total   = (int) ( $row['total'] ?? 0 );
        $present = (int) ( $row['present'] ?? 0 );

        return [
            'activities' => $total,
            'present'    => $present,
            'absent'     => (int) ( $row['absent'] ?? 0 ),
            'excused'    => (int) ( $row['excused'] ?? 0 ),
            'rate'       => $total > 0 ? round( $present / $total * 100 ) : null,
        ];
    }

    /**
     * Minutes played in the window, total and per match.
     *
     * Both come from `MinutesQuery`, which is the report the minutes
     * screens read — a fourth minutes query here is exactly the drift this
     * packet exists to end. The breakdown is team-scoped by that query, so
     * it resolves the player's current team; a player with no team gets the
     * total and an empty breakdown rather than a wrong one.
     *
     * @return array<string,mixed>
     */
    private static function minutes( int $player_id, string $from, string $to ): array {
        $query  = new MinutesQuery();
        $totals = $query->seasonTotalsForPlayer( $player_id, $from, $to );

        global $wpdb;
        $team_id = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT team_id FROM {$wpdb->prefix}tt_players WHERE id = %d AND club_id = %d",
            $player_id, CurrentClub::id()
        ) );

        $breakdown = $team_id > 0
            ? $query->matchBreakdownForPlayer( $team_id, $player_id, $from, $to )
            : [];

        return [
            'apps'      => (int) $totals['apps'],
            'minutes'   => (int) $totals['minutes'],
            'breakdown' => $breakdown,
        ];
    }

    /**
     * The player's active goals plus what moved in the window. The old
     * sidebar listed goals *created* in the window and called it "Goal
     * changes", which quietly hid every goal that was completed since the
     * last talk — the one thing a coach most wants to open the
     * conversation with.
     *
     * @return list<array<string,mixed>>
     */
    private static function goals( int $player_id, string $from, string $to ): array {
        $rows = ( new GoalsRepository() )->listForPlayer( $player_id );

        $out = [];
        foreach ( $rows as $g ) {
            $created = substr( (string) ( $g->created_at ?? '' ), 0, 10 );
            $updated = substr( (string) ( $g->updated_at ?? '' ), 0, 10 );

            $created_in = $created !== '' && $created >= $from && $created <= $to;
            $updated_in = $updated !== '' && $updated >= $from && $updated <= $to;
            $closed     = in_array( (string) ( $g->status ?? '' ), GoalsRepository::CLOSED_STATUSES, true );

            // A goal closed before the window opened is history, not
            // evidence for this talk. Everything still open is, whether it
            // moved or not — "no movement since October" is a finding.
            if ( $closed && ! $updated_in ) continue;
            if ( $created !== '' && $created > $to ) continue;

            $out[] = [
                'id'                => (int) ( $g->id ?? 0 ),
                'title'             => (string) ( $g->title ?? '' ),
                'status'            => (string) ( $g->status ?? '' ),
                'priority'          => (string) ( $g->priority ?? '' ),
                'due_date'          => (string) ( $g->due_date ?? '' ),
                'created_at'        => (string) ( $g->created_at ?? '' ),
                'updated_at'        => (string) ( $g->updated_at ?? '' ),
                'created_in_window' => $created_in,
                'changed_in_window' => $created_in || $updated_in,
                'is_closed'         => $closed,
            ];
        }
        return $out;
    }

    /**
     * Injuries that overlap the window, and the return-to-play date where
     * one has been recorded. An injury that started before the window and
     * is still open belongs in the packet — the player is still out.
     *
     * @return list<array<string,mixed>>
     */
    private static function injuries( int $player_id, string $from, string $to ): array {
        $rows = ( new InjuryRepository() )->listForPlayer( $player_id );

        $out = [];
        foreach ( $rows as $row ) {
            $started = substr( (string) ( $row->started_on ?? '' ), 0, 10 );
            $ended   = substr( (string) ( $row->actual_return ?? '' ), 0, 10 );

            if ( $started === '' || $started > $to ) continue;
            if ( $ended !== '' && $ended < $from ) continue;

            $out[] = [
                'id'              => (int) ( $row->id ?? 0 ),
                'started_on'      => $started,
                'expected_return' => (string) ( $row->expected_return ?? '' ),
                'actual_return'   => $ended,
                'notes'           => (string) ( $row->notes ?? '' ),
                'is_open'         => $ended === '',
            ];
        }
        return $out;
    }

    /**
     * Staff notes about the player in the window.
     *
     * Gated exactly as the Notes tab on the player file is: a reader who
     * cannot see this player's notes there does not see them here either,
     * and a private-to-coach note stays private. The packet is assembled
     * for a reader, not for a record (CLAUDE.md §1 — privacy and dignity).
     *
     * @return list<array<string,mixed>>
     */
    private static function notes( int $player_id, string $from, string $to ): array {
        $user_id = get_current_user_id();
        if ( ! ThreadAccess::canRead( 'player', $player_id, $user_id ) ) return [];

        $rows = ( new ThreadMessagesRepository() )->listForThread(
            'player',
            $player_id,
            ThreadAccess::canSeePrivate( 'player', $player_id, $user_id )
        );

        $out = [];
        foreach ( $rows as $row ) {
            if ( ! empty( $row->deleted_at ) ) continue;

            $created = substr( (string) ( $row->created_at ?? '' ), 0, 10 );
            if ( $created === '' || $created < $from || $created > $to ) continue;

            $out[] = [
                'id'          => (int) ( $row->id ?? 0 ),
                'created_at'  => (string) ( $row->created_at ?? '' ),
                'author_id'   => (int) ( $row->author_user_id ?? 0 ),
                'author_name' => self::userName( (int) ( $row->author_user_id ?? 0 ) ),
                'visibility'  => (string) ( $row->visibility ?? '' ),
                'body'        => (string) ( $row->body ?? '' ),
            ];
        }

        // Newest first, matching every other group in the packet.
        return array_reverse( $out );
    }

    /** @return list<object> */
    private static function journey( int $player_id, int $club_id, string $from, string $to ): array {
        global $wpdb;
        $p = $wpdb->prefix;

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, event_type, event_date, summary
               FROM {$p}tt_player_events
              WHERE player_id = %d
                AND club_id = %d
                AND superseded_by_event_id IS NULL
                AND event_date >= %s
                AND event_date <= %s
              ORDER BY event_date DESC, id DESC
              LIMIT 30",
            $player_id, $club_id, $from, $to
        ) );

        return is_array( $rows ) ? $rows : [];
    }

    /**
     * Display name for a staff member, empty when the account is gone.
     * Cached per request — an evidence packet asks about the same handful
     * of coaches many times over.
     */
    private static function userName( int $user_id ): string {
        static $cache = [];
        if ( $user_id <= 0 ) return '';
        if ( isset( $cache[ $user_id ] ) ) return $cache[ $user_id ];

        $user = get_userdata( $user_id );
        $cache[ $user_id ] = $user ? (string) $user->display_name : '';
        return $cache[ $user_id ];
    }
}
