<?php
namespace TT\Modules\Threads\Domain;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Modules\Authorization\MatrixGate;
use TT\Modules\Threads\ThreadTypeRegistry;

/**
 * ThreadAccess — who may read a thread, and who may read the
 * private-to-coach messages inside it.
 *
 * Lifted out of `ThreadsRestController` (#3302) once a second reader
 * appeared: the PDP evidence packet carries a player's staff notes, and a
 * packet that answered the visibility question its own way would be the
 * fifth place in the plugin where "can this person see this note" gets
 * decided. The REST controller delegates here, so both answers come from
 * one place (CLAUDE.md §4 — the gate is domain logic, not view logic).
 */
final class ThreadAccess {

    /**
     * Can this user read the thread at all?
     */
    public static function canRead( string $thread_type, int $thread_id, int $user_id ): bool {
        if ( $user_id <= 0 ) return false;
        $adapter = ThreadTypeRegistry::get( $thread_type );
        if ( ! $adapter ) return false;
        return $adapter->canRead( $user_id, $thread_id );
    }

    /**
     * Can this user see the messages marked private to the coaching staff?
     *
     * A global thread reader always can. Otherwise the user must be able to
     * read the thread AND hold the evaluating-staff capability — a parent
     * who can read a goal thread is not staff, and never sees the private
     * side of it.
     */
    public static function canSeePrivate( string $thread_type, int $thread_id, int $user_id ): bool {
        if ( self::hasGlobalAccess( $user_id, 'read' ) ) return true;
        if ( ! self::canRead( $thread_type, $thread_id, $user_id ) ) return false;
        return user_can( $user_id, 'tt_edit_evaluations' );
    }

    /**
     * Does the user hold `thread_messages/<activity>/global` in the matrix?
     * Falls back to WP `manage_options` for matrix-dormant installs, then
     * to the v3.0 umbrella capability for back-compat.
     *
     * @param string $activity 'read' | 'change'
     */
    public static function hasGlobalAccess( int $user_id, string $activity ): bool {
        if ( $user_id <= 0 ) return false;
        if ( class_exists( '\\TT\\Modules\\Authorization\\MatrixGate' ) ) {
            $matrix_activity = $activity === 'read' ? MatrixGate::READ : MatrixGate::CHANGE;
            if ( MatrixGate::can( $user_id, 'thread_messages', $matrix_activity, MatrixGate::SCOPE_GLOBAL ) ) {
                return true;
            }
        }
        if ( user_can( $user_id, 'manage_options' ) ) return true;
        return user_can( $user_id, 'tt_view_settings' );
    }
}
