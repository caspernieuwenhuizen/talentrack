<?php
namespace TT\Modules\Measurements\Units;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * UnitContext (#3273) — everything one test knows about the quantity it
 * records: its dimension, the unit staff type in, and how a value crosses
 * between the two.
 *
 * The single chokepoint. Entry parses through it, every surface formats through
 * it, the target bands convert through it, and the growth chart asks it for
 * centimetres instead of dividing by a hundred. Nothing outside this class does
 * unit arithmetic, which is the property that makes the answer the same whether
 * it was reached through the REST controller or through a rendered page (§4).
 *
 * A definition with no resolvable unit is not an error state. It is
 * `dimensionless`: values pass through untouched, no conversion is offered, and
 * the legacy free-text symbol is still printed after the number — exactly the
 * behaviour every test had before this existed.
 */
final class UnitContext {

    private function __construct(
        private string $dimension,
        private ?UnitRow $entry_unit,
        private string $legacy_symbol,
        private string $numeric_format
    ) {}

    /**
     * Build the context for a definition row. Safe on null and on a row that
     * predates the units columns.
     */
    public static function forDefinition( ?object $def ): self {
        if ( ! $def ) {
            return new self( Dimensions::DIMENSIONLESS, null, '', 'plain' );
        }

        // `entry_unit_id` is the only thing that makes a definition
        // convertible, and deliberately so. Migration 0252 stamps it on exactly
        // the definitions whose stored values it converted, so a stamped row
        // holds canonical values and an unstamped one still holds whatever was
        // typed. Resolving an unstamped row's `unit` string against the
        // registry would look helpful and would double-convert every reading it
        // has — the same class of silent error this issue exists to remove.
        // Unstamped means dimensionless: print the symbol, touch nothing.
        $entry_unit = ( new UnitRegistry() )->byId(
            isset( $def->entry_unit_id ) ? (int) $def->entry_unit_id : 0
        );

        $dimension = $entry_unit
            ? $entry_unit->dimension
            : Dimensions::safe( (string) ( $def->dimension ?? Dimensions::DIMENSIONLESS ) );

        // Duration is a rendering of time. Anything else claiming it is a data
        // error rather than a preference, so it is refused here instead of
        // being defended against at four call sites.
        $format = (string) ( $def->numeric_format ?? 'plain' );
        if ( $format !== 'duration' || $dimension !== Dimensions::TIME ) {
            $format = 'plain';
        }

        return new self(
            $dimension,
            $entry_unit,
            trim( (string) ( $def->unit ?? '' ) ),
            $format
        );
    }

    public function dimension(): string {
        return $this->dimension;
    }

    /**
     * The symbol to print after a value — the registry's when there is one, the
     * operator's free text otherwise.
     */
    public function symbol(): string {
        return $this->entry_unit ? $this->entry_unit->symbol : $this->legacy_symbol;
    }

    public function entryUnitId(): ?int {
        return $this->entry_unit ? $this->entry_unit->id : null;
    }

    public function isConvertible(): bool {
        return $this->entry_unit !== null && $this->dimension !== Dimensions::DIMENSIONLESS;
    }

    public function isDuration(): bool {
        return $this->numeric_format === 'duration';
    }

    private function factor(): float {
        return $this->entry_unit ? $this->entry_unit->factor_to_base : 1.0;
    }

    /**
     * A value as typed, in the entry unit, expressed in the dimension's base.
     */
    public function toBase( float $entered ): float {
        return $this->isConvertible() ? $entered * $this->factor() : $entered;
    }

    /**
     * The stored canonical value, expressed in the entry unit.
     */
    public function fromBase( float $base ): float {
        return $this->isConvertible() ? $base / $this->factor() : $base;
    }

    /**
     * Read what a human typed and return the canonical value, or the reason it
     * was refused.
     *
     * A duration accepts `mm:ss` — always minutes and seconds, whatever the
     * entry unit, because the colon is what makes it unambiguous — and also a
     * bare number, which is read in the entry unit like any other value. So on
     * a test entered in seconds, `92.4` is 92.4 seconds; on one entered in
     * minutes it is 92.4 minutes. The rule is the same either way: a colon
     * means clock time, no colon means the unit the test declares.
     *
     * @return array{value: float|null, error: string|null}
     */
    public function parse( string $raw ): array {
        $raw = trim( $raw );
        if ( $raw === '' ) {
            return [ 'value' => null, 'error' => null ];
        }

        if ( $this->isDuration() && strpos( $raw, ':' ) !== false ) {
            $seconds = DurationFormat::parse( $raw );
            if ( $seconds === null ) {
                return [
                    'value' => null,
                    'error' => __( 'Enter a time as mm:ss, for example 5:30. Seconds must be under 60.', 'talenttrack' ),
                ];
            }
            return $this->validated( $seconds );
        }

        $normalised = str_replace( ',', '.', $raw );
        if ( ! is_numeric( $normalised ) ) {
            return [
                'value' => null,
                'error' => $this->isDuration()
                    ? __( 'Enter a time as mm:ss, for example 5:30.', 'talenttrack' )
                    : __( 'Enter a number.', 'talenttrack' ),
            ];
        }

        return $this->validated( $this->toBase( (float) $normalised ) );
    }

    /**
     * @return array{value: float|null, error: string|null}
     */
    private function validated( float $base ): array {
        $range = Dimensions::plausibleRange( $this->dimension );
        if ( $range === null ) {
            return [ 'value' => $base, 'error' => null ];
        }

        [ $min, $max ] = $range;
        if ( $base < $min || $base > $max ) {
            return [
                'value' => null,
                'error' => sprintf(
                    /* translators: 1: lowest accepted value with unit, 2: highest accepted value with unit */
                    __( 'That value is outside the range this kind of test can hold (%1$s to %2$s).', 'talenttrack' ),
                    $this->format( $min ),
                    $this->format( $max )
                ),
            ];
        }

        return [ 'value' => $base, 'error' => null ];
    }

    /**
     * Render a canonical value the way this test's staff read it.
     */
    public function format( ?float $base, bool $with_symbol = true ): string {
        if ( $base === null ) return '';

        if ( $this->isDuration() ) {
            return DurationFormat::format( $base );
        }

        $number = $this->number( $this->fromBase( $base ) );
        $symbol = $this->symbol();

        return $with_symbol && $symbol !== '' ? $number . ' ' . $symbol : $number;
    }

    /**
     * The bare value for an input's `value` attribute — no symbol, and mm:ss
     * where that is what the field expects.
     */
    public function formatForInput( ?float $base ): string {
        if ( $base === null ) return '';
        return $this->isDuration()
            ? DurationFormat::format( $base )
            : $this->number( $this->fromBase( $base ) );
    }

    /**
     * Attributes for the value field, so entry, the target bands and the
     * wizard all present the same control for the same kind of test.
     *
     * @return array<string, string>
     */
    public function inputAttributes(): array {
        if ( $this->isDuration() ) {
            return [
                'type'        => 'text',
                'inputmode'   => 'numeric',
                'pattern'     => DurationFormat::inputPattern(),
                'placeholder' => 'mm:ss',
            ];
        }

        return [
            'type'      => 'number',
            'step'      => 'any',
            'inputmode' => 'decimal',
            // #3275 — the generic string, not the unit symbol.
            //
            // #3273 made the placeholder the symbol, but the entry row
            // already renders a unit chip beside the input, so a minutes
            // test read `[ min ] min`. The chip carries the unit; the
            // placeholder says what the box is for. The duration branch
            // above is the exception and keeps `mm:ss`, because there the
            // placeholder is telling you the *shape* to type, which no
            // chip can say — and the entry row suppresses its chip for
            // exactly that reason.
            'placeholder' => __( 'value', 'talenttrack' ),
        ];
    }

    /**
     * A *change* between two canonical values, in the unit a change is spoken
     * in. Every unit here is a pure scale factor, so a difference converts by
     * the same division a value does.
     *
     * The exception is a duration: "three seconds quicker" is how a coach says
     * it, and "−0:03" or "−0.05 min" is not, so a duration's delta stays in
     * seconds no matter which unit the test is entered in.
     */
    public function deltaFromBase( float $delta ): float {
        return $this->isDuration() ? $delta : $this->fromBase( $delta );
    }

    public function deltaSymbol(): string {
        return $this->isDuration() ? Dimensions::baseSymbol( Dimensions::TIME ) : $this->symbol();
    }

    /**
     * Convert a canonical value into a named unit — how the growth chart asks
     * for centimetres without knowing what the height test was recorded in.
     * Null when the symbol is unknown or the dimensions do not match, so a
     * caller is forced to decide what to do rather than being handed a number
     * that means something else.
     */
    public function toSymbol( ?float $base, string $symbol ): ?float {
        if ( $base === null || ! $this->isConvertible() ) return null;

        $unit = ( new UnitRegistry() )->bySymbol( $symbol );
        if ( ! $unit || $unit->dimension !== $this->dimension ) return null;

        return $base / $unit->factor_to_base;
    }

    /**
     * Three decimals then trimmed — the rounding hides float noise from the
     * conversion (182 cm round-trips as 182.00000000000003 otherwise) and the
     * trim keeps 30.000 reading as "30", which is what the module has always
     * shown.
     */
    private function number( float $value ): string {
        return rtrim( rtrim( number_format( $value, 3, '.', '' ), '0' ), '.' );
    }
}
