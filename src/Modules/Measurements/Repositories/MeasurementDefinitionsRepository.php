<?php
namespace TT\Modules\Measurements\Repositories;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Infrastructure\Query\LookupTranslator;
use TT\Infrastructure\Tenancy\CurrentClub;
use TT\Modules\Measurements\Units\Dimensions;
use TT\Modules\Measurements\Units\UnitRegistry;

/**
 * MeasurementDefinitionsRepository (#1856).
 *
 * A measurement *definition* is a test an academy runs (e.g. "Sprint 30m",
 * "Height"). Definitions are the schema the rest of the module hangs off:
 * sessions are scheduled against one, results record one value for one.
 *
 * Stateless; mirrors EvaluationsRepository — global $wpdb, club-scoped via
 * CurrentClub::id(), excludes soft-deleted rows (archived_at IS NULL).
 */
class MeasurementDefinitionsRepository {

    /**
     * All active definitions, with the localised category label attached.
     *
     * @return array<int, object>
     */
    public function listActive(): array {
        global $wpdb;
        $p = $wpdb->prefix;

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT d.*, lt.name AS category_name, lt.lookup_type AS category_lookup_type
               FROM {$p}tt_measurement_definitions d
               LEFT JOIN {$p}tt_lookups lt ON d.category_id = lt.id
              WHERE d.club_id = %d AND d.archived_at IS NULL AND d.is_active = 1
              ORDER BY lt.sort_order ASC, d.sort_order ASC, d.name ASC",
            CurrentClub::id()
        ) );
        if ( ! is_array( $rows ) ) return [];

        foreach ( $rows as $row ) {
            $row->category_label = $this->localiseCategory( $row );
        }
        return $rows;
    }

    /**
     * Active definitions the operator has chosen to surface on the player
     * profile (`show_on_profile = 1`), #2204. Same shape + ordering as
     * listActive() — the profile read model uses this so a test toggled off
     * for the profile stops rendering there while staying in reports/exports.
     *
     * @return array<int, object>
     */
    public function listActiveForProfile(): array {
        global $wpdb;
        $p = $wpdb->prefix;

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT d.*, lt.name AS category_name, lt.lookup_type AS category_lookup_type
               FROM {$p}tt_measurement_definitions d
               LEFT JOIN {$p}tt_lookups lt ON d.category_id = lt.id
              WHERE d.club_id = %d AND d.archived_at IS NULL AND d.is_active = 1
                AND d.show_on_profile = 1
              ORDER BY lt.sort_order ASC, d.sort_order ASC, d.name ASC",
            CurrentClub::id()
        ) );
        if ( ! is_array( $rows ) ) return [];

        foreach ( $rows as $row ) {
            $row->category_label = $this->localiseCategory( $row );
        }
        return $rows;
    }

    /**
     * All definitions, optionally including the inactive (is_active = 0) ones.
     * Archived (soft-deleted) rows are always excluded. Club-scoped.
     *
     * @return array<int, object>
     */
    public function listAll( bool $include_inactive = false ): array {
        global $wpdb;
        $p = $wpdb->prefix;

        $active_clause = $include_inactive ? '' : ' AND d.is_active = 1';
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT d.*, lt.name AS category_name, lt.lookup_type AS category_lookup_type
               FROM {$p}tt_measurement_definitions d
               LEFT JOIN {$p}tt_lookups lt ON d.category_id = lt.id
              WHERE d.club_id = %d AND d.archived_at IS NULL{$active_clause}
              ORDER BY lt.sort_order ASC, d.sort_order ASC, d.name ASC",
            CurrentClub::id()
        ) );
        if ( ! is_array( $rows ) ) return [];

        foreach ( $rows as $row ) {
            $row->category_label = $this->localiseCategory( $row );
        }
        return $rows;
    }

    /**
     * One definition by id (club-scoped, not archived).
     */
    public function find( int $id ): ?object {
        if ( $id <= 0 ) return null;
        global $wpdb;
        $p = $wpdb->prefix;

        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT d.*, lt.name AS category_name, lt.lookup_type AS category_lookup_type
               FROM {$p}tt_measurement_definitions d
               LEFT JOIN {$p}tt_lookups lt ON d.category_id = lt.id
              WHERE d.id = %d AND d.club_id = %d AND d.archived_at IS NULL",
            $id, CurrentClub::id()
        ) );
        if ( ! $row ) return null;
        $row->category_label = $this->localiseCategory( $row );
        return $row;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create( array $data ): int {
        global $wpdb;
        $p = $wpdb->prefix;

        $wpdb->insert( "{$p}tt_measurement_definitions", [
            'club_id'     => CurrentClub::id(),
            'uuid'        => wp_generate_uuid4(),
            'category_id' => (int) ( $data['category_id'] ?? 0 ),
            'name'        => (string) ( $data['name'] ?? '' ),
            'value_type'  => $this->safeValueType( $data['value_type'] ?? 'numeric' ),
            'unit'        => isset( $data['unit'] ) && $data['unit'] !== '' ? (string) $data['unit'] : null,
            'dimension'      => Dimensions::safe( (string) ( $data['dimension'] ?? Dimensions::DIMENSIONLESS ) ),
            'entry_unit_id'  => ! empty( $data['entry_unit_id'] ) ? (int) $data['entry_unit_id'] : null,
            // #3275 — a test measured in minutes defaults to mm:ss.
            //
            // Choosing minutes as the entry unit IS the statement "this is a
            // mm:ss quantity". Defaulting to `plain` there reproduced exactly
            // the confusion #3273 set out to remove — 5.30 meaning 5:18 — and
            // left the coach to discover a checkbox they had no reason to look
            // for. Seconds and milliseconds stay plain: a 10m sprint is
            // 2.05 s, not 0:02.05.
            //
            // Only when the caller passes nothing. An explicit value always
            // wins, in both directions, so this never overrides a choice.
            'numeric_format' => $this->safeNumericFormat(
                array_key_exists( 'numeric_format', $data )
                    ? $data['numeric_format']
                    : self::defaultNumericFormatFor( $data )
            ),
            'scale_min'   => isset( $data['scale_min'] ) ? (float) $data['scale_min'] : null,
            'scale_max'   => isset( $data['scale_max'] ) ? (float) $data['scale_max'] : null,
            'frequency'   => (string) ( $data['frequency'] ?? 'adhoc' ),
            'direction'   => $this->safeDirection( $data['direction'] ?? 'higher' ),
            'is_active'   => isset( $data['is_active'] ) ? (int) (bool) $data['is_active'] : 1,
            'show_on_profile' => isset( $data['show_on_profile'] ) ? (int) (bool) $data['show_on_profile'] : 1,
            'sort_order'  => (int) ( $data['sort_order'] ?? 0 ),
            'created_by'  => get_current_user_id() ?: null,
            'created_at'  => current_time( 'mysql', true ),
        ] );
        return (int) $wpdb->insert_id;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update( int $id, array $data ): bool {
        if ( $id <= 0 ) return false;
        global $wpdb;
        $p = $wpdb->prefix;

        $fields = [ 'updated_at' => current_time( 'mysql', true ) ];
        if ( array_key_exists( 'category_id', $data ) ) $fields['category_id'] = (int) $data['category_id'];
        if ( array_key_exists( 'name', $data ) )        $fields['name']        = (string) $data['name'];
        if ( array_key_exists( 'value_type', $data ) )  $fields['value_type']  = $this->safeValueType( $data['value_type'] );
        if ( array_key_exists( 'unit', $data ) )        $fields['unit']        = $data['unit'] !== '' ? (string) $data['unit'] : null;
        if ( array_key_exists( 'dimension', $data ) )   $fields['dimension']   = Dimensions::safe( (string) $data['dimension'] );
        if ( array_key_exists( 'entry_unit_id', $data ) ) $fields['entry_unit_id'] = ! empty( $data['entry_unit_id'] ) ? (int) $data['entry_unit_id'] : null;
        if ( array_key_exists( 'numeric_format', $data ) ) $fields['numeric_format'] = $this->safeNumericFormat( $data['numeric_format'] );
        if ( array_key_exists( 'scale_min', $data ) )   $fields['scale_min']   = $data['scale_min'] !== null ? (float) $data['scale_min'] : null;
        if ( array_key_exists( 'scale_max', $data ) )   $fields['scale_max']   = $data['scale_max'] !== null ? (float) $data['scale_max'] : null;
        if ( array_key_exists( 'frequency', $data ) )   $fields['frequency']   = (string) $data['frequency'];
        if ( array_key_exists( 'direction', $data ) )   $fields['direction']   = $this->safeDirection( $data['direction'] );
        if ( array_key_exists( 'is_active', $data ) )   $fields['is_active']   = (int) (bool) $data['is_active'];
        if ( array_key_exists( 'show_on_profile', $data ) ) $fields['show_on_profile'] = (int) (bool) $data['show_on_profile'];
        if ( array_key_exists( 'sort_order', $data ) )  $fields['sort_order']  = (int) $data['sort_order'];

        // #3273 — giving a previously unclassified test a real unit is the one
        // edit that has to reach its existing rows. Migration 0252 leaves a
        // definition it could not classify `dimensionless` and its values
        // exactly as typed; the moment an operator says "this test measures
        // centimetres", those raw numbers become centimetres and have to be
        // converted to the dimension's base like any other reading, or every
        // surface would read them as metres. Read before the update, so the
        // transition can be detected.
        $was_unclassified = array_key_exists( 'entry_unit_id', $fields )
            && $fields['entry_unit_id'] !== null
            && (int) ( $wpdb->get_var( $wpdb->prepare(
                "SELECT entry_unit_id FROM {$p}tt_measurement_definitions WHERE id = %d AND club_id = %d",
                $id, CurrentClub::id()
            ) ) ?: 0 ) === 0;

        $ok = false !== $wpdb->update(
            "{$p}tt_measurement_definitions",
            $fields,
            [ 'id' => $id, 'club_id' => CurrentClub::id() ]
        );

        if ( $ok && $was_unclassified ) {
            $this->convertUnclassifiedRows( $id, (int) $fields['entry_unit_id'] );
        }

        return $ok;
    }

    /**
     * Convert a newly classified test's existing results and target bands into
     * the dimension's base, and stamp each result with the unit it is now
     * understood to have been entered in.
     *
     * Guarded on `entered_unit_id IS NULL` for the same reason the migration is:
     * a row already stamped has already been converted, and converting twice is
     * how a height becomes a hundred metres.
     */
    private function convertUnclassifiedRows( int $definition_id, int $unit_id ): void {
        $unit = ( new UnitRegistry() )->byId( $unit_id );
        if ( ! $unit ) return;

        global $wpdb;
        $p = $wpdb->prefix;

        $wpdb->query( $wpdb->prepare(
            "UPDATE {$p}tt_measurement_results
                SET entered_value   = value_numeric,
                    entered_unit_id = %d,
                    value_numeric   = value_numeric * %f
              WHERE definition_id = %d
                AND value_numeric IS NOT NULL
                AND entered_unit_id IS NULL",
            $unit_id, $unit->factor_to_base, $definition_id
        ) );

        if ( abs( $unit->factor_to_base - 1.0 ) > 0.0000001 ) {
            $wpdb->query( $wpdb->prepare(
                "UPDATE {$p}tt_measurement_targets
                    SET green_min = green_min * %f,
                        green_max = green_max * %f,
                        amber_min = amber_min * %f,
                        amber_max = amber_max * %f
                  WHERE definition_id = %d",
                $unit->factor_to_base, $unit->factor_to_base,
                $unit->factor_to_base, $unit->factor_to_base,
                $definition_id
            ) );
        }
    }

    private function localiseCategory( object $row ): string {
        if ( empty( $row->category_id ) ) return '';
        $stub = (object) [
            'id'          => (int) $row->category_id,
            'name'        => (string) ( $row->category_name ?? '' ),
            'lookup_type' => (string) ( $row->category_lookup_type ?? 'measurement_category' ),
        ];
        return LookupTranslator::name( $stub );
    }

    /**
     * Attach the operator-defined levels (status tests only) to a definition
     * row as `->levels`. A no-op for the other value types, so callers can
     * hydrate unconditionally. Lives here — not in a view — so the REST
     * controller and the rendered HTML get the same hydrated shape (§4).
     */
    public function withLevels( ?object $def ): ?object {
        if ( ! $def ) return null;
        $def->levels = (string) ( $def->value_type ?? '' ) === 'status'
            ? ( new MeasurementLevelsRepository() )->listForDefinition( (int) $def->id )
            : [];
        return $def;
    }

    private function safeValueType( string $value ): string {
        return in_array( $value, [ 'numeric', 'scale', 'passfail', 'status' ], true ) ? $value : 'numeric';
    }

    private function safeDirection( string $value ): string {
        return in_array( $value, [ 'higher', 'lower', 'neutral' ], true ) ? $value : 'higher';
    }

    /**
     * #3273 — `duration` is a rendering of a time value, not a value type of
     * its own. Whether it is *allowed* on this definition (time dimension only)
     * is UnitContext's call; this only keeps the column to its two members.
     */
    private function safeNumericFormat( string $value ): string {
        return $value === 'duration' ? 'duration' : 'plain';
    }

    /**
     * The format a test should get when the caller names none (#3275).
     *
     * Minutes-and-longer units are read and written as mm:ss; seconds and
     * milliseconds are decimals. Keyed on the unit SYMBOL rather than on the
     * dimension, because `time` covers both — every unit in the dimension
     * would otherwise get the same answer, which is the bug.
     *
     * @param array<string, mixed> $data
     */
    public static function defaultNumericFormatFor( array $data ): string {
        if ( (string) ( $data['dimension'] ?? '' ) !== Dimensions::TIME ) return 'plain';

        $unit = strtolower( trim( (string) ( $data['unit'] ?? '' ) ) );
        return in_array( $unit, self::MMSS_UNITS, true ) ? 'duration' : 'plain';
    }

    /**
     * Time units whose readings a person says as mm:ss.
     *
     * A list, not a threshold: adding an hours unit later means adding it
     * here, which is a decision someone makes rather than a rule that
     * silently reclassifies existing tests.
     *
     * @var list<string>
     */
    private const MMSS_UNITS = [ 'min', 'h', 'hr', 'u' ];
}
