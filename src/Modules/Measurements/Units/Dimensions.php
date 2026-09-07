<?php
namespace TT\Modules\Measurements\Units;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Dimensions (#3273) — the physical (or countable) kinds a measurement can be,
 * and the SI base each one is stored in.
 *
 * A dimension is what makes two readings comparable. Two numbers are the same
 * quantity only if they share a dimension and have been converted to its base;
 * before this existed the module compared them on the strength of a caption.
 *
 * `dimensionless` is deliberate rather than a gap. A test with an operator's
 * own free-text unit ("shuttle", "cones cleared") has no factor to anything, so
 * it converts to nothing, offers no duration format and is never mixed with
 * another test's values. Saying so is more honest than inventing a dimension.
 */
final class Dimensions {

    public const TIME          = 'time';
    public const LENGTH        = 'length';
    public const MASS          = 'mass';
    public const COUNT         = 'count';
    public const RATE          = 'rate';
    public const RATIO         = 'ratio';
    public const LEVEL         = 'level';
    public const DIMENSIONLESS = 'dimensionless';

    /**
     * Base unit symbol per dimension — what `value_numeric` holds.
     *
     * @var array<string, string>
     */
    private const BASE = [
        self::TIME   => 's',
        self::LENGTH => 'm',
        self::MASS   => 'kg',
        self::COUNT  => 'reps',
        self::RATE   => 'bpm',
        self::RATIO  => '%',
        self::LEVEL  => 'level',
    ];

    /**
     * Plausible magnitude per dimension, in base units, as [min, max].
     *
     * These are a guard against nonsense, not a coaching judgement: a 500-metre
     * player or a sprint of four hours is a typo, a 3.2m javelin throw is not
     * this class's business. Bounds are deliberately generous — the protection
     * against the unit confusion that motivated #3273 is the recorded unit
     * itself, not this check, because 1.82m and 182cm are both plausible.
     *
     * @var array<string, array{float, float}>
     */
    private const PLAUSIBLE = [
        // #3275 — four hours, not a full day. A single test reading is never
        // hours long: a match, a Cooper test and any circuit all fit well
        // inside this. At 86400 the ceiling caught nothing that mattered —
        // `1130` typed on a minutes test (meaning 11:30) stored 67 800 s and
        // passed, which is the 18-hour reading this bound exists to stop.
        self::TIME   => [ 0.0, 14400.0 ],
        self::LENGTH => [ 0.0, 1000.0 ],
        self::MASS   => [ 0.0, 2000.0 ],
        self::COUNT  => [ 0.0, 1000000.0 ],
        self::RATE   => [ 0.0, 400.0 ],
        self::RATIO  => [ 0.0, 1000.0 ],
        self::LEVEL  => [ 0.0, 1000.0 ],
    ];

    /**
     * @return list<string>
     */
    public static function all(): array {
        return [
            self::TIME, self::LENGTH, self::MASS, self::COUNT,
            self::RATE, self::RATIO, self::LEVEL, self::DIMENSIONLESS,
        ];
    }

    public static function isKnown( string $dimension ): bool {
        return in_array( $dimension, self::all(), true );
    }

    public static function safe( string $dimension ): string {
        return self::isKnown( $dimension ) ? $dimension : self::DIMENSIONLESS;
    }

    public static function baseSymbol( string $dimension ): string {
        return self::BASE[ $dimension ] ?? '';
    }

    /**
     * The translated name of a dimension, for the test form's picker.
     *
     * `_x()` rather than `__()` on every one of these: they are single words
     * that already exist elsewhere in the product in a different sense. Bare
     * `Length` is translated "Duur" (a span of time) for the planner, and
     * `Rate` is "Percentage" — either would be actively wrong beside a unit
     * symbol. The context string is what keeps the physical reading separate.
     */
    public static function label( string $dimension ): string {
        switch ( $dimension ) {
            case self::TIME:   return _x( 'Time', 'measurement dimension', 'talenttrack' );
            case self::LENGTH: return _x( 'Length', 'measurement dimension', 'talenttrack' );
            case self::MASS:   return _x( 'Mass', 'measurement dimension', 'talenttrack' );
            case self::COUNT:  return _x( 'Count', 'measurement dimension', 'talenttrack' );
            case self::RATE:   return _x( 'Rate', 'measurement dimension', 'talenttrack' );
            case self::RATIO:  return _x( 'Percentage', 'measurement dimension', 'talenttrack' );
            case self::LEVEL:  return _x( 'Level', 'measurement dimension', 'talenttrack' );
            default:           return _x( 'No dimension', 'measurement dimension', 'talenttrack' );
        }
    }

    /**
     * @return array{float, float}|null
     */
    public static function plausibleRange( string $dimension ): ?array {
        return self::PLAUSIBLE[ $dimension ] ?? null;
    }
}
