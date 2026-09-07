<?php
namespace TT\Modules\Analytics\Domain;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Infrastructure\Query\LabelTranslator;
use TT\Infrastructure\Query\QueryHelpers;
use TT\Infrastructure\Tenancy\CurrentClub;

/**
 * DimensionValueResolver — turn a raw dimension VALUE (player_id "70",
 * activity_type "training", team_id "5") into a human label
 * ("Lucas van der Berg", "Training", "U17") for the explorer + CSV.
 *
 * Pre-#0093 the explorer's group-by table and the CSV both rendered
 * `(string) $row->{$dim_key}` directly, so operators saw raw IDs and
 * enum slugs where a name belonged. This class is the one place that
 * decision lives — both consumers go through `resolve()`.
 *
 * Strategy: foreign_key dimensions resolve through the existing
 * QueryHelpers / WP user lookups; lookup dimensions route through
 * LabelTranslator (already i18n-aware); enum dimensions get a small
 * built-in label map for the common ones (status, decision) and fall
 * back to a humanised slug for unknowns; date_range values pass
 * through (the SQL bucket expression already produces a friendly
 * "2026-04" / "2025/26").
 *
 * Caching is per-request only — a static `$cache` keyed by
 * "type:id" so a group-by table with 30 rows on `player_id` does 30
 * unique lookups (not 30+ cache misses on the same id).
 */
final class DimensionValueResolver {

    /** @var array<string,string> */
    private static array $cache = [];

    /**
     * Resolve `$value` for `$dim` to a display string. Falls back to the
     * raw value when nothing can be resolved — never blanks out, never
     * throws. An em-dash is returned for null/empty so the table cell
     * is non-empty.
     */
    public static function resolve( Dimension $dim, $value ): string {
        if ( $value === null || $value === '' ) return '—';
        $raw = (string) $value;

        $cache_key = $dim->key . ':' . $raw;
        if ( isset( self::$cache[ $cache_key ] ) ) {
            return self::$cache[ $cache_key ];
        }

        $resolved = self::resolveByType( $dim, $raw );
        self::$cache[ $cache_key ] = $resolved;
        return $resolved;
    }

    private static function resolveByType( Dimension $dim, string $raw ): string {
        switch ( $dim->type ) {
            case Dimension::TYPE_FOREIGN_KEY:
                return self::resolveForeignKey( $dim, $raw );
            case Dimension::TYPE_LOOKUP:
                return self::resolveLookup( $dim, $raw );
            case Dimension::TYPE_ENUM:
                return self::resolveEnum( $dim, $raw );
            case Dimension::TYPE_DATE_RANGE:
            default:
                return $raw;
        }
    }

    private static function resolveForeignKey( Dimension $dim, string $raw ): string {
        $id = (int) $raw;
        if ( $id <= 0 ) return $raw;

        $table = (string) ( $dim->foreignTable ?? '' );
        global $wpdb;
        switch ( $table ) {
            case 'tt_players':
                $player = QueryHelpers::get_player( $id );
                if ( ! $player ) return self::missingIdLabel( $raw );
                return QueryHelpers::player_display_name( $player );

            case 'tt_teams':
                $team = QueryHelpers::get_team( $id );
                if ( ! $team ) return self::missingIdLabel( $raw );
                return (string) ( $team->name ?? $raw );

            case 'tt_activities':
                // Activities don't have a `name`; surface "{type} on {date}"
                // so a coach can identify the activity at a glance.
                //
                // The date column is `session_date` and is aliased here.
                // Selecting `activity_date` (#3356) made the query fail
                // outright, so every activity dimension fell through to
                // the missing-id label and the report rendered wrong
                // rather than erroring.
                $row = $wpdb->get_row( $wpdb->prepare(
                    "SELECT activity_type_key, session_date AS activity_date FROM {$wpdb->prefix}tt_activities
                     WHERE id = %d AND club_id = %d LIMIT 1",
                    $id, CurrentClub::id()
                ) );
                if ( ! $row ) return self::missingIdLabel( $raw );
                $type = LabelTranslator::activityType( (string) ( $row->activity_type_key ?? '' ) );
                $date = (string) ( $row->activity_date ?? '' );
                if ( $type === '' && $date === '' ) return $raw;
                if ( $type === '' ) return $date;
                if ( $date === '' ) return $type;
                /* translators: 1: activity type, 2: activity date */
                return sprintf( __( '%1$s — %2$s', 'talenttrack' ), $type, $date );

            case 'wp_users':
                $user = function_exists( 'get_user_by' ) ? get_user_by( 'id', $id ) : null;
                if ( ! $user ) return self::missingIdLabel( $raw );
                $name = trim( (string) ( $user->display_name ?? '' ) );
                if ( $name === '' ) $name = (string) ( $user->user_login ?? '' );
                return $name !== '' ? $name : self::missingIdLabel( $raw );
        }

        // Unknown foreign table — leave the id visible so the developer
        // can spot a missing resolver case rather than silently masking.
        return self::missingIdLabel( $raw );
    }

    private static function resolveLookup( Dimension $dim, string $raw ): string {
        $type = (string) ( $dim->lookupType ?? '' );
        if ( $type === '' ) return $raw;

        // LabelTranslator carries hand-rolled i18n maps for the
        // vocabularies the analytics surface groups on today
        // (`activity_type`). Lookups outside the typed methods (free-form
        // vocabularies admins extend at runtime) pass through as the
        // stored name — which is already a human-readable string in
        // tt_lookups, just not translated. That's preferable to a raw
        // id, which lookup values are not.
        if ( $type === 'activity_type' ) {
            $label = LabelTranslator::activityType( $raw );
            if ( is_string( $label ) && $label !== '' ) return $label;
        }
        return $raw;
    }

    private static function resolveEnum( Dimension $dim, string $raw ): string {
        // Small built-in label map for the enum values the explorer
        // surfaces today. Keys are "{dim_key}:{value}" so we never
        // collide between dimensions that share a value name (e.g.
        // attendance.status='present' vs goal.status='present-tense').
        static $labels = null;
        if ( $labels === null ) {
            $labels = [
                'status:present'           => __( 'Present', 'talenttrack' ),
                'status:absent'            => __( 'Absent', 'talenttrack' ),
                'status:excused'           => __( 'Excused', 'talenttrack' ),
                'status:late'              => __( 'Late', 'talenttrack' ),
                'status:open'              => __( 'Open', 'talenttrack' ),
                'status:in_progress'       => __( 'In progress', 'talenttrack' ),
                'status:completed'         => __( 'Completed', 'talenttrack' ),
                'status:cancelled'         => __( 'Cancelled', 'talenttrack' ),
                'plan_state:planned'       => __( 'Planned', 'talenttrack' ),
                'plan_state:completed'     => __( 'Completed', 'talenttrack' ),
                'plan_state:cancelled'     => __( 'Cancelled', 'talenttrack' ),
                'priority:high'            => __( 'High', 'talenttrack' ),
                'priority:medium'          => __( 'Medium', 'talenttrack' ),
                'priority:low'             => __( 'Low', 'talenttrack' ),
                'decision:admit'           => __( 'Admit', 'talenttrack' ),
                'decision:decline'         => __( 'Decline', 'talenttrack' ),
                'decision:no_offer_made'   => __( 'No offer made', 'talenttrack' ),
                'event_type:promoted'      => __( 'Promoted', 'talenttrack' ),
                'event_type:age_group_change' => __( 'Age group change', 'talenttrack' ),
            ];
        }
        $key = $dim->key . ':' . $raw;
        if ( isset( $labels[ $key ] ) ) return $labels[ $key ];

        // Unknown enum value — humanise the slug ("no_offer_made" →
        // "No offer made") so the table is readable even for values
        // we haven't curated yet.
        return ucfirst( str_replace( '_', ' ', $raw ) );
    }

    /**
     * Bracketed "#70" label for foreign-key ids that don't resolve —
     * makes the cell readable without losing the lookup key, which is
     * useful for debugging "why is this player invisible" cases.
     */
    private static function missingIdLabel( string $raw ): string {
        /* translators: %s record id that couldn't be resolved to a name */
        return sprintf( __( '#%s (missing)', 'talenttrack' ), $raw );
    }
}
