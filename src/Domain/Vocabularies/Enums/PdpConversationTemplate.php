<?php
namespace TT\Domain\Vocabularies\Enums;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * PdpConversationTemplate — which talk in the cycle a conversation is.
 *
 * `PdpConversationsRepository::createCycle()` stamps one of these on every
 * conversation it seeds, and a 2 / 3 / 4-conversation cycle uses a
 * different subset. The keys have existed since the module shipped; they
 * are gathered here (#3305) because the prep question sets are configured
 * per template, so a second surface now needs the list and its labels.
 *
 * Code-only vocabulary rather than a `tt_lookups` type: the keys are
 * structural — `createCycle()` picks them by cycle size — so an academy
 * adding a sixth one would produce a template nothing ever stamps.
 */
final class PdpConversationTemplate {

    public const START = 'start';
    public const MID   = 'mid';
    public const MID_A = 'mid_a';
    public const MID_B = 'mid_b';
    public const END   = 'end';

    /** @var list<string> */
    public const ALL = [ self::START, self::MID, self::MID_A, self::MID_B, self::END ];

    public static function isValid( string $key ): bool {
        return in_array( $key, self::ALL, true );
    }

    /**
     * The label a coach sees. Falls back to the raw key so a conversation
     * carrying an unrecognised template still renders something.
     */
    public static function label( string $key ): string {
        switch ( $key ) {
            case self::START: return __( 'Start of season', 'talenttrack' );
            case self::MID:   return __( 'Mid season', 'talenttrack' );
            case self::MID_A: return __( 'Mid-season A', 'talenttrack' );
            case self::MID_B: return __( 'Mid-season B', 'talenttrack' );
            case self::END:   return __( 'End of season', 'talenttrack' );
        }
        return $key;
    }

    /**
     * Every template with its label, in cycle order.
     *
     * @return array<string,string> key => label
     */
    public static function labelled(): array {
        $out = [];
        foreach ( self::ALL as $key ) {
            $out[ $key ] = self::label( $key );
        }
        return $out;
    }
}
