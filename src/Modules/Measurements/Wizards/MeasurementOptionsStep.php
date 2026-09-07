<?php
namespace TT\Modules\Measurements\Wizards;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Modules\Measurements\Units\Dimensions;
use TT\Modules\Measurements\Units\UnitRegistry;
use TT\Modules\Measurements\Repositories\MeasurementDefinitionsRepository;
use TT\Shared\Wizards\WizardStepInterface;

/**
 * Step 2 — unit, direction, recurrence.
 *
 * The unit is picked from the unit registry OR typed as a custom value (the
 * custom field wins when filled — no JS reveal needed). Unit only applies to
 * the numeric value type.
 *
 * #3273 — a registry unit brings its dimension, so the draft already knows
 * what kind of quantity the test records and whether mm:ss is even meaningful
 * for it. A custom string is dimensionless by construction.
 */
final class MeasurementOptionsStep implements WizardStepInterface {

    public function slug(): string  { return 'options'; }
    public function label(): string { return __( 'Unit & recurrence', 'talenttrack' ); }

    public function render( array $state ): void {
        $value_type = (string) ( $state['value_type'] ?? 'numeric' );
        $unit       = (string) ( $state['unit'] ?? '' );
        $direction  = (string) ( $state['direction'] ?? 'higher' );
        $frequency  = (string) ( $state['frequency'] ?? 'adhoc' );

        if ( $value_type === 'numeric' ) {
            // #3273 — the registry, not the lookup list: a unit picked here
            // carries the dimension the test's values are stored against.
            $units = ( new UnitRegistry() )->all();
            $unit_names = array_map( static fn( $r ) => (string) $r->symbol, $units );
            $is_listed = in_array( $unit, $unit_names, true );

            echo '<label><span>' . esc_html__( 'Unit', 'talenttrack' ) . '</span><select name="unit">';
            echo '<option value="">' . esc_html__( '— none —', 'talenttrack' ) . '</option>';
            foreach ( $units as $row ) {
                $name  = (string) $row->symbol;
                $label = sprintf(
                    /* translators: 1: unit symbol, 2: the kind of quantity it measures */
                    __( '%1$s — %2$s', 'talenttrack' ),
                    $name,
                    Dimensions::label( (string) $row->dimension )
                );
                echo '<option value="' . esc_attr( $name ) . '" ' . selected( $is_listed ? $unit : '', $name, false ) . '>' . esc_html( $label ) . '</option>';
            }
            echo '</select></label>';

            // #3275 — directly under the unit picker, above the custom-unit
            // escape hatch. It modifies the unit just chosen, and below a
            // field most operators skip past it is never seen. Same order as
            // the edit form.
            echo '<label><input type="checkbox" name="numeric_format" value="duration" '
                . checked( (string) ( $state['numeric_format'] ?? '' ), 'duration', false ) . ' /> '
                . '<span>' . esc_html__( 'Enter and show as mm:ss', 'talenttrack' ) . '</span></label>';

            echo '<label><span>' . esc_html__( 'Custom unit (overrides the list)', 'talenttrack' ) . '</span>'
                . '<input type="text" name="unit_custom" maxlength="50" value="' . esc_attr( ! $is_listed ? $unit : '' ) . '" '
                . 'placeholder="' . esc_attr__( 'e.g. watt/kg', 'talenttrack' ) . '" /></label>';

            $dirs = [
                'higher'  => __( 'Higher is better', 'talenttrack' ),
                'lower'   => __( 'Lower is better', 'talenttrack' ),
                'neutral' => __( 'Neither (just track it)', 'talenttrack' ),
            ];
            echo '<label><span>' . esc_html__( 'Direction', 'talenttrack' ) . '</span><select name="direction">';
            foreach ( $dirs as $key => $label ) {
                echo '<option value="' . esc_attr( $key ) . '" ' . selected( $direction, $key, false ) . '>' . esc_html( $label ) . '</option>';
            }
            echo '</select></label>';
        }

        $freqs = [
            'annual'    => __( 'Once a season', 'talenttrack' ),
            'biannual'  => __( 'Twice a season', 'talenttrack' ),
            'quarterly' => __( 'Four times a season', 'talenttrack' ),
            'monthly'   => __( 'Monthly', 'talenttrack' ),
            'adhoc'     => __( 'No fixed cadence', 'talenttrack' ),
        ];
        echo '<label><span>' . esc_html__( 'Recurrence', 'talenttrack' ) . '</span><select name="frequency">';
        foreach ( $freqs as $key => $label ) {
            echo '<option value="' . esc_attr( $key ) . '" ' . selected( $frequency, $key, false ) . '>' . esc_html( $label ) . '</option>';
        }
        echo '</select></label>';
    }

    public function validate( array $post, array $state ) {
        $value_type = (string) ( $state['value_type'] ?? 'numeric' );

        $unit_listed = isset( $post['unit'] ) ? sanitize_text_field( wp_unslash( (string) $post['unit'] ) ) : '';
        $unit_custom = isset( $post['unit_custom'] ) ? sanitize_text_field( wp_unslash( (string) $post['unit_custom'] ) ) : '';
        $unit = $unit_custom !== '' ? $unit_custom : $unit_listed;

        $direction = isset( $post['direction'] ) ? sanitize_text_field( wp_unslash( (string) $post['direction'] ) ) : 'higher';
        if ( ! in_array( $direction, [ 'higher', 'lower', 'neutral' ], true ) ) $direction = 'higher';

        $frequency = isset( $post['frequency'] ) ? sanitize_text_field( wp_unslash( (string) $post['frequency'] ) ) : 'adhoc';
        if ( ! in_array( $frequency, [ 'annual', 'biannual', 'quarterly', 'monthly', 'adhoc' ], true ) ) $frequency = 'adhoc';

        // #3273 — resolve the symbol now so the draft carries the dimension it
        // will be created with, and mm:ss only survives on a time unit.
        $unit_row  = $value_type === 'numeric' ? ( new UnitRegistry() )->bySymbol( $unit ) : null;
        $dimension = $unit_row ? (string) $unit_row->dimension : Dimensions::DIMENSIONLESS;
        // #3275 — an unticked box on a NEW test is not a decision.
        //
        // The operator has just said "this is measured in minutes", which is
        // the same statement as "these are mm:ss readings"; they had no
        // reason to go looking for a checkbox to confirm it. So the unit
        // decides when nothing was ticked, and ticking still forces mm:ss on
        // any time unit. This is a create path — there is no earlier choice
        // here to override.
        $format = ( ! empty( $post['numeric_format'] ) && $dimension === Dimensions::TIME )
            ? 'duration'
            : MeasurementDefinitionsRepository::defaultNumericFormatFor( [
                'dimension' => $dimension,
                'unit'      => $unit,
            ] );

        return [
            'unit'           => $value_type === 'numeric' ? $unit : '',
            'dimension'      => $dimension,
            'entry_unit_id'  => $unit_row ? (int) $unit_row->id : null,
            'numeric_format' => $format,
            'direction' => $value_type === 'numeric' ? $direction : 'neutral',
            'frequency' => $frequency,
        ];
    }

    public function nextStep( array $state ): ?string { return 'targets'; }

    public function submit( array $state ) { return null; }
}
