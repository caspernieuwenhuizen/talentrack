<?php
namespace TT\Modules\Pdp\Prep;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Infrastructure\Tenancy\CurrentClub;
use TT\Modules\Pdp\PdpAccess;

/**
 * Who may read or write a coach's preparation for a PDP conversation.
 *
 * The answer is narrower than the file's own: **the coach who can edit
 * this file, and the head of academy. Never the player, never a parent,
 * on any surface, at any point.**
 *
 * That is not a UI preference. Prep is where a coach writes candidly about
 * a minor before sitting down with them — a doubt, a worry about a family
 * situation, a recommendation they have not made yet. What gets shared is
 * the notes and the agreed actions from the talk itself (CLAUDE.md §1 —
 * privacy and dignity).
 *
 * The gate lives here, beside the repository that uses it, rather than in
 * a view: every read path goes through the repository, so a surface added
 * later cannot forget to ask.
 */
final class PdpPrepAccess {

    /**
     * Can this user see the prep for this conversation?
     *
     * Read and write are the same answer on purpose. There is no reader of
     * a coach's preparation who is not also entitled to change it — a
     * "read-only prep" role would be a person looking over the coach's
     * shoulder at notes written on the assumption nobody was.
     */
    public static function canAccess( int $user_id, int $conversation_id ): bool {
        if ( $user_id <= 0 || $conversation_id <= 0 ) return false;

        $player_id = self::playerFor( $conversation_id );
        if ( $player_id <= 0 ) return false;

        return PdpAccess::canEditFile( $user_id, $player_id );
    }

    /**
     * Can this user configure the question sets themselves? A settings
     * decision for the academy, not a per-file one.
     */
    public static function canConfigure( int $user_id ): bool {
        if ( $user_id <= 0 ) return false;
        return user_can( $user_id, 'tt_edit_settings' )
            || PdpAccess::isGlobalVerdictAuthority( $user_id );
    }

    /** The player a conversation belongs to, or 0. Club-scoped. */
    public static function playerFor( int $conversation_id ): int {
        if ( $conversation_id <= 0 ) return 0;

        global $wpdb;
        $p = $wpdb->prefix;

        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT f.player_id
               FROM {$p}tt_pdp_conversations c
               JOIN {$p}tt_pdp_files f ON f.id = c.pdp_file_id AND f.club_id = c.club_id
              WHERE c.id = %d AND c.club_id = %d",
            $conversation_id, CurrentClub::id()
        ) );
    }
}
