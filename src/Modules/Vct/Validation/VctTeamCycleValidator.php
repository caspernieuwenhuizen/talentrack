<?php
namespace TT\Modules\Vct\Validation;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Modules\Vct\Repositories\VctMacroBlocksRepository;
use TT\Modules\Vct\Repositories\VctTeamCyclesRepository;

/**
 * VctTeamCycleValidator — the single source of truth for what a valid
 * team cycle is (#3360, epic #3354).
 *
 * Used by both the REST endpoint and the configuration form, following
 * `VctMacroBlockValidator`'s precedent, so the WordPress render and a
 * future SaaS front end refuse exactly the same things for exactly the
 * same reasons.
 *
 * Returns a translated message on failure and null on success — the
 * caller decides whether that becomes a 400 or a form notice.
 */
class VctTeamCycleValidator {

    /**
     * @param array{cycle_weeks?:mixed, anchor_date?:mixed, template_id?:mixed} $input
     */
    public static function validate( array $input ): ?string {
        $weeks = isset( $input['cycle_weeks'] ) ? (int) $input['cycle_weeks'] : 0;
        if ( ! VctTeamCyclesRepository::isAllowedLength( $weeks ) ) {
            return sprintf(
                /* translators: %s = the cycle lengths the product offers, e.g. "3, 4, 6". */
                __( 'A cycle is %s weeks long. Pick one of those.', 'talenttrack' ),
                implode( ', ', VctTeamCyclesRepository::ALLOWED_WEEKS )
            );
        }

        $anchor = isset( $input['anchor_date'] ) ? (string) $input['anchor_date'] : '';
        if ( VctTeamCyclesRepository::normaliseToMonday( $anchor ) === null ) {
            return __( 'The start date is not a date. Use the date picker, or type it as YYYY-MM-DD.', 'talenttrack' );
        }

        $template_id = isset( $input['template_id'] ) ? (int) $input['template_id'] : 0;
        if ( $template_id > 0 ) {
            $template = self::findTemplate( $template_id );
            if ( $template === null ) {
                return __( 'That weekly shape no longer exists. Pick another one.', 'talenttrack' );
            }

            $profile_weeks = is_array( $template['phase_profile'] ) ? count( $template['phase_profile'] ) : 0;
            if ( $profile_weeks !== $weeks ) {
                // Silently accepting this is what produces a cycle whose
                // later weeks are flat for no reason the coach can see.
                return sprintf(
                    /* translators: 1: weeks in the chosen shape, 2: the cycle length chosen. */
                    __( 'That weekly shape covers %1$d weeks, but the cycle is %2$d. Pick a shape with one row per week of the cycle.', 'talenttrack' ),
                    $profile_weeks,
                    $weeks
                );
            }
        }

        return null;
    }

    /**
     * Reference templates whose profile is exactly `$weeks` long — the set
     * offered for a cycle of that length.
     *
     * @return list<array<string,mixed>>
     */
    public static function templatesForLength( int $weeks ): array {
        $out = [];
        foreach ( ( new VctMacroBlocksRepository() )->listReferenceTemplates() as $template ) {
            $profile = is_array( $template['phase_profile'] ) ? $template['phase_profile'] : [];
            if ( count( $profile ) === $weeks ) $out[] = $template;
        }
        return $out;
    }

    /** @return array<string,mixed>|null */
    private static function findTemplate( int $id ): ?array {
        foreach ( ( new VctMacroBlocksRepository() )->listReferenceTemplates() as $template ) {
            if ( (int) $template['id'] === $id ) return $template;
        }
        return null;
    }
}
