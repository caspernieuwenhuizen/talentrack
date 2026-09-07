<?php
namespace TT\Modules\Pdp\Prep;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Domain\Vocabularies\Enums\PdpConversationTemplate;
use TT\Infrastructure\CustomFields\CustomFieldsRepository;

/**
 * The question set an academy starts with, per conversation template.
 *
 * A coach preparing a PDP conversation had one free-text box labelled
 * "Agenda". These are the prompts that replace it, and they differ by
 * template on purpose: the start of a season asks what we are asking of
 * this player, the end asks what we will recommend about them.
 *
 * ## Why the shipped wording lives in PHP rather than in the seeded row
 *
 * The questions are admin-editable, so they have to be rows. But a row
 * seeded by a migration carries whatever language the migration wrote, and
 * migrations run before an install has necessarily settled its locale — a
 * Dutch academy would open Configuration and find five English questions.
 *
 * So a seeded row keeps its `label_key`, and the repository resolves the
 * wording through the `__()` calls below at read time. The moment an admin
 * edits the question, `label_key` clears and their wording is the wording;
 * nothing overwrites what somebody typed.
 *
 * `ANYTHING_ELSE` is load-bearing beyond being a question: it is where the
 * retired free-text `agenda` column migrates to, so every set has one and
 * its key does not change.
 */
final class PdpPrepQuestionDefaults {

    /** The free-text catch-all every set ends with; the `agenda` migration target. */
    public const ANYTHING_ELSE = 'anything_else';

    /**
     * The shipped set for one template, in order.
     *
     * @return list<array{key:string,label:string,help_text:string,field_type:string,required:int}>
     */
    public static function forTemplate( string $template_key ): array {
        $sets = self::sets();
        return $sets[ $template_key ] ?? $sets[ PdpConversationTemplate::MID ];
    }

    /**
     * Every shipped set, keyed by template.
     *
     * @return array<string,list<array{key:string,label:string,help_text:string,field_type:string,required:int}>>
     */
    public static function sets(): array {
        $text = CustomFieldsRepository::TYPE_TEXTAREA;

        $start = [
            self::q( 'start_where_now', $text,
                __( 'Where is this player now?', 'talenttrack' ),
                __( 'The honest starting point, in your words — not a summary of the evidence panel next to you.', 'talenttrack' ),
                1
            ),
            self::q( 'start_asking_of_them', $text,
                __( 'What are we asking of them this season?', 'talenttrack' ),
                __( 'The one or two things that would make this a good season for this player.', 'talenttrack' ),
                1
            ),
            self::q( 'start_raise_from_evidence', $text,
                __( 'What in the evidence do you want to raise?', 'talenttrack' ),
                __( 'A rating, an attendance pattern, a run of minutes — name it here so you do not improvise it in the room.', 'talenttrack' ),
                0
            ),
        ];

        $mid = [
            self::q( 'mid_what_changed', $text,
                __( 'What has changed since the last conversation?', 'talenttrack' ),
                __( 'What you have seen, not what the numbers show — the numbers are already on the Evidence tab.', 'talenttrack' ),
                1
            ),
            self::q( 'mid_goals_moved', $text,
                __( 'Which goals have moved, and which have not?', 'talenttrack' ),
                __( 'A goal that has not moved since October is worth saying out loud.', 'talenttrack' ),
                1
            ),
            self::q( 'mid_needs_next', $text,
                __( 'What does this player need next?', 'talenttrack' ),
                __( 'What you want to be able to agree on before the talk ends.', 'talenttrack' ),
                1
            ),
        ];

        $end = [
            self::q( 'end_achieved', $text,
                __( 'What did this player achieve this season?', 'talenttrack' ),
                __( 'The season as they will remember it, not as a scoreline.', 'talenttrack' ),
                1
            ),
            self::q( 'end_heading_next', $text,
                __( 'Where are they heading next season?', 'talenttrack' ),
                __( 'Age group, position, minutes — whatever the next step actually is.', 'talenttrack' ),
                1
            ),
            self::q( 'end_recommendation', $text,
                __( 'What will you recommend to the head of academy?', 'talenttrack' ),
                __( 'Your view before the verdict is recorded. Private to you and the head of academy.', 'talenttrack' ),
                1
            ),
        ];

        $anything_else = self::q( self::ANYTHING_ELSE, $text,
            __( 'Anything else to prepare?', 'talenttrack' ),
            __( 'Whatever does not fit the questions above.', 'talenttrack' ),
            0
        );

        return [
            PdpConversationTemplate::START => array_merge( $start, [ $anything_else ] ),
            PdpConversationTemplate::MID   => array_merge( $mid,   [ $anything_else ] ),
            PdpConversationTemplate::MID_A => array_merge( $mid,   [ $anything_else ] ),
            PdpConversationTemplate::MID_B => array_merge( $mid,   [ $anything_else ] ),
            PdpConversationTemplate::END   => array_merge( $end,   [ $anything_else ] ),
        ];
    }

    /**
     * The shipped wording for one `label_key`, or null when the key is not
     * one this release ships (an academy's own question, or one dropped by
     * a later release — either way the stored row wins).
     *
     * @return array{label:string,help_text:string}|null
     */
    public static function wordingFor( string $label_key ): ?array {
        foreach ( self::sets() as $set ) {
            foreach ( $set as $q ) {
                if ( $q['key'] === $label_key ) {
                    return [ 'label' => $q['label'], 'help_text' => $q['help_text'] ];
                }
            }
        }
        return null;
    }

    /**
     * @return array{key:string,label:string,help_text:string,field_type:string,required:int}
     */
    private static function q( string $key, string $type, string $label, string $help, int $required ): array {
        return [
            'key'        => $key,
            'label'      => $label,
            'help_text'  => $help,
            'field_type' => $type,
            'required'   => $required,
        ];
    }
}
