<?php
/**
 * ActivityTypeKey — typed constants for the five values stored in
 * `tt_activities.activity_type_key`. Backs the `activity_type` lookup
 * (operator-editable via the lookups admin) but the seeded values below
 * are the canonical set used by every consumer (analytics dimensions,
 * workflow templates that filter `activity_type_key = 'game'`,
 * match-prep gating, etc.).
 *
 * Use the constants in PHP comparisons:
 *
 *     if ( $activity->activity_type_key === ActivityTypeKey::GAME ) { ... }
 *
 * In SQL, use `MATCH_LIKE_SQL` rather than writing the values out again.
 * Four separate queries had drifted onto a `'match'` literal that this
 * vocabulary has never contained, and so matched nothing.
 *
 * REST endpoints accept BOTH the literal AND the constant for one release
 * per #988's backward-compat allowlist; see docs/rest-api.md for the
 * deprecation timeline.
 */

namespace TT\Domain\Vocabularies\Lookups;

if ( ! defined( 'ABSPATH' ) ) exit;

final class ActivityTypeKey {

    public const TRAINING   = 'training';
    public const GAME       = 'game';
    public const OTHER      = 'other';
    public const TOURNAMENT = 'tournament';
    public const MEETING    = 'meeting';

    /** @var list<string> */
    public const ALL = [
        self::TRAINING,
        self::GAME,
        self::OTHER,
        self::TOURNAMENT,
        self::MEETING,
    ];

    /**
     * Never written; tolerated on read. Nothing in this vocabulary has
     * ever been `match`, but several queries were written against it and
     * `MatchAnalysisGenerator` already reads `IN ('game','match')`, so a
     * hand-edited or imported row may hold it. Reading it costs nothing;
     * missing such a row would be a silently empty fixture list.
     */
    public const LEGACY_GAME = 'match';

    /**
     * The types that mean "the team plays a fixture". A tournament is a
     * multi-game day (#2686), so anything resolving a match day or a
     * match week counts it exactly like a game.
     *
     * @var list<string>
     */
    public const MATCH_LIKE = [ self::GAME, self::TOURNAMENT, self::LEGACY_GAME ];

    /**
     * `MATCH_LIKE` as an SQL value list, for interpolating into an
     * `IN (...)` clause. A constant expression over class constants, so
     * it stays a literal-string for the prepared-statement analyser and
     * can never carry request input.
     */
    public const MATCH_LIKE_SQL = "'" . self::GAME . "','" . self::TOURNAMENT . "','" . self::LEGACY_GAME . "'";

    public static function isValid( string $value ): bool {
        return in_array( $value, self::ALL, true );
    }

    public static function isMatchLike( string $value ): bool {
        return in_array( $value, self::MATCH_LIKE, true );
    }
}
