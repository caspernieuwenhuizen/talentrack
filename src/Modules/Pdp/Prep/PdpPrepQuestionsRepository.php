<?php
namespace TT\Modules\Pdp\Prep;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Domain\Vocabularies\Enums\PdpConversationTemplate;
use TT\Infrastructure\CustomFields\CustomFieldsRepository;
use TT\Infrastructure\Tenancy\CurrentClub;

/**
 * The configurable prep questions, per conversation template.
 *
 * Two things this repository owns that a caller must not re-derive:
 *
 *   1. **Which rows are the current set** — not archived, not superseded,
 *      ordered by sequence.
 *   2. **Versioning on edit** — a question with answers against it is
 *      never rewritten in place. `update()` inserts the next version and
 *      supersedes the old row, so a prep answered in September still reads
 *      the question it was answered against in June.
 *
 * Seeded rows carry a `label_key` and take their wording from
 * {@see PdpPrepQuestionDefaults} at read time, so a Dutch academy sees
 * Dutch questions from a migration that ran before the locale settled.
 * The moment an admin edits one, the key clears and their wording stands.
 */
final class PdpPrepQuestionsRepository {

    /**
     * Field types a prep question may use. A deliberate subset of the
     * custom-fields vocabulary rather than a parallel type system — a URL
     * or a phone number is not something a coach prepares.
     *
     * @return list<string>
     */
    public static function allowedFieldTypes(): array {
        return [
            CustomFieldsRepository::TYPE_TEXTAREA,
            CustomFieldsRepository::TYPE_TEXT,
            CustomFieldsRepository::TYPE_SELECT,
            CustomFieldsRepository::TYPE_MULTI_SELECT,
            CustomFieldsRepository::TYPE_CHECKBOX,
            CustomFieldsRepository::TYPE_NUMBER,
            CustomFieldsRepository::TYPE_DATE,
        ];
    }

    /** The questions table, resolved per call. */
    private function table(): string {
        global $wpdb;
        return $wpdb->prefix . 'tt_pdp_prep_questions';
    }

    /**
     * The current set for one template, in order.
     *
     * @return list<array<string,mixed>>
     */
    public function listForTemplate( string $template_key ): array {
        if ( ! PdpConversationTemplate::isValid( $template_key ) ) return [];

        global $wpdb;
        $p = $wpdb->prefix;

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$p}tt_pdp_prep_questions
              WHERE club_id = %d
                AND template_key = %s
                AND superseded_by_question_id IS NULL
                AND archived_at IS NULL
              ORDER BY sequence ASC, id ASC",
            CurrentClub::id(), $template_key
        ) );

        return array_map( [ self::class, 'hydrate' ], (array) $rows );
    }

    /**
     * Every template's current set, keyed by template.
     *
     * @return array<string,list<array<string,mixed>>>
     */
    public function listAll(): array {
        $out = [];
        foreach ( PdpConversationTemplate::ALL as $key ) {
            $out[ $key ] = $this->listForTemplate( $key );
        }
        return $out;
    }

    /** @return array<string,mixed>|null */
    public function find( int $id ): ?array {
        if ( $id <= 0 ) return null;

        global $wpdb;
        $p = $wpdb->prefix;

        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$p}tt_pdp_prep_questions WHERE id = %d AND club_id = %d",
            $id, CurrentClub::id()
        ) );
        return $row ? self::hydrate( $row ) : null;
    }

    /**
     * The questions a given conversation should render.
     *
     * A conversation the coach has already started answering is pinned to
     * the versions those answers were given against; anything since added
     * to the template joins the end. A conversation with no answers yet
     * simply gets today's set.
     *
     * This is the "at the version pinned for this conversation" rule, in
     * one place, so no surface has to reimplement it.
     *
     * @return list<array<string,mixed>>
     */
    public function listForConversation( int $conversation_id, string $template_key ): array {
        $current = $this->listForTemplate( $template_key );
        if ( $conversation_id <= 0 ) return $current;

        global $wpdb;
        $p = $wpdb->prefix;

        $answered = $wpdb->get_results( $wpdb->prepare(
            "SELECT q.*
               FROM {$p}tt_pdp_prep_answers a
               JOIN {$p}tt_pdp_prep_questions q ON q.id = a.question_id AND q.club_id = a.club_id
              WHERE a.conversation_id = %d
                AND a.club_id = %d
                AND q.template_key = %s
              ORDER BY q.sequence ASC, q.id ASC",
            $conversation_id, CurrentClub::id(), $template_key
        ) );
        if ( empty( $answered ) ) return $current;

        $out  = [];
        $seen = [];
        foreach ( (array) $answered as $row ) {
            $q     = self::hydrate( $row );
            $out[] = $q;
            // A pinned question and its later versions are the same
            // question; the current version must not appear twice.
            $seen[ (int) $q['lineage_id'] ] = true;
        }
        foreach ( $current as $q ) {
            if ( isset( $seen[ (int) $q['lineage_id'] ] ) ) continue;
            $out[] = $q;
        }

        usort( $out, static fn( array $a, array $b ): int => [ $a['sequence'], $a['id'] ] <=> [ $b['sequence'], $b['id'] ] );
        return $out;
    }

    /**
     * Add a question to the end of a template's set.
     *
     * @param array<string,mixed> $data
     */
    public function create( string $template_key, array $data ): int {
        if ( ! PdpConversationTemplate::isValid( $template_key ) ) return 0;

        $label = trim( (string) ( $data['label'] ?? '' ) );
        if ( $label === '' ) return 0;

        global $wpdb;
        $p = $wpdb->prefix;

        $next = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COALESCE(MAX(sequence), 0) + 1 FROM {$p}tt_pdp_prep_questions
              WHERE club_id = %d AND template_key = %s
                AND superseded_by_question_id IS NULL AND archived_at IS NULL",
            CurrentClub::id(), $template_key
        ) );

        $ok = $wpdb->insert( $this->table(), [
            'uuid'         => wp_generate_uuid4(),
            'club_id'      => CurrentClub::id(),
            'template_key' => $template_key,
            'sequence'     => max( 1, $next ),
            'label'        => $label,
            'label_key'    => null,
            'help_text'    => (string) ( $data['help_text'] ?? '' ),
            'field_type'   => self::sanitiseType( (string) ( $data['field_type'] ?? '' ) ),
            'options'      => self::encodeOptions( $data['options'] ?? [] ),
            'required'     => ! empty( $data['required'] ) ? 1 : 0,
            'version'      => 1,
        ] );

        return $ok ? (int) $wpdb->insert_id : 0;
    }

    /**
     * Change a question.
     *
     * Once an answer exists against it the row is immutable: a new version
     * is inserted and the old row is superseded, keeping every prep already
     * written readable as it was written. With no answers yet there is
     * nothing to protect, so the row is updated in place and the set does
     * not grow a version for a typo fixed a minute after it was typed.
     *
     * @param array<string,mixed> $patch
     * @return int The id now current for this question, or 0 on failure.
     */
    public function update( int $id, array $patch ): int {
        $existing = $this->find( $id );
        if ( ! $existing ) return 0;

        global $wpdb;

        $fields = [];
        if ( array_key_exists( 'label', $patch ) ) {
            $label = trim( (string) $patch['label'] );
            if ( $label === '' ) return 0;
            $fields['label'] = $label;
            // An admin's wording replaces the shipped wording for good.
            $fields['label_key'] = null;
        }
        if ( array_key_exists( 'help_text', $patch ) ) {
            $fields['help_text'] = (string) $patch['help_text'];
            $fields['label_key'] = null;
        }
        if ( array_key_exists( 'field_type', $patch ) ) {
            $fields['field_type'] = self::sanitiseType( (string) $patch['field_type'] );
        }
        if ( array_key_exists( 'options', $patch ) ) {
            $fields['options'] = self::encodeOptions( $patch['options'] );
        }
        if ( array_key_exists( 'required', $patch ) ) {
            $fields['required'] = ! empty( $patch['required'] ) ? 1 : 0;
        }
        if ( $fields === [] ) return $id;

        if ( ! $this->hasAnswers( $id ) ) {
            $fields['updated_at'] = current_time( 'mysql', true );
            $ok = $wpdb->update( $this->table(), $fields, [ 'id' => $id, 'club_id' => CurrentClub::id() ] );
            return $ok === false ? 0 : $id;
        }

        $row = array_merge( [
            'label'      => $existing['label'],
            'label_key'  => $existing['label_key'],
            'help_text'  => $existing['help_text'],
            'field_type' => $existing['field_type'],
            'options'    => self::encodeOptions( $existing['options'] ),
            'required'   => (int) $existing['required'],
        ], $fields );

        $ok = $wpdb->insert( $this->table(), array_merge( $row, [
            'uuid'                   => wp_generate_uuid4(),
            'club_id'                => CurrentClub::id(),
            'template_key'           => $existing['template_key'],
            'sequence'               => (int) $existing['sequence'],
            'version'                => (int) $existing['version'] + 1,
            'lineage_id'             => (int) $existing['lineage_id'],
            'supersedes_question_id' => $id,
        ] ) );
        if ( ! $ok ) return 0;

        $new_id = (int) $wpdb->insert_id;
        $wpdb->update(
            $this->table(),
            [ 'superseded_by_question_id' => $new_id ],
            [ 'id' => $id, 'club_id' => CurrentClub::id() ]
        );
        return $new_id;
    }

    /**
     * Take a question out of the current set. Archive rather than delete:
     * the answers already given against it stay readable.
     */
    public function archive( int $id ): bool {
        if ( $id <= 0 ) return false;

        global $wpdb;
        $ok = $wpdb->update(
            $this->table(),
            [ 'archived_at' => current_time( 'mysql', true ) ],
            [ 'id' => $id, 'club_id' => CurrentClub::id() ]
        );
        return $ok !== false;
    }

    /**
     * Reorder a template's current set. Ids not in the set are ignored;
     * questions the caller left out keep their place after the ones named.
     *
     * @param list<int> $ordered_ids
     */
    public function reorder( string $template_key, array $ordered_ids ): bool {
        global $wpdb;

        $set   = $this->listForTemplate( $template_key );
        $valid = [];
        foreach ( $set as $q ) $valid[ (int) $q['id'] ] = true;

        $sequence = 1;
        foreach ( $ordered_ids as $id ) {
            $id = (int) $id;
            if ( ! isset( $valid[ $id ] ) ) continue;
            $wpdb->update(
                $this->table(),
                [ 'sequence' => $sequence ],
                [ 'id' => $id, 'club_id' => CurrentClub::id() ]
            );
            unset( $valid[ $id ] );
            $sequence++;
        }
        foreach ( array_keys( $valid ) as $id ) {
            $wpdb->update(
                $this->table(),
                [ 'sequence' => $sequence ],
                [ 'id' => (int) $id, 'club_id' => CurrentClub::id() ]
            );
            $sequence++;
        }
        return true;
    }

    /**
     * The current-version row for a shipped question key, or null. The
     * `agenda` migration in slice 5 finds its target this way.
     *
     * @return array<string,mixed>|null
     */
    public function findByLabelKey( string $template_key, string $label_key ): ?array {
        global $wpdb;
        $p = $wpdb->prefix;

        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$p}tt_pdp_prep_questions
              WHERE club_id = %d AND template_key = %s AND label_key = %s
                AND superseded_by_question_id IS NULL AND archived_at IS NULL
              ORDER BY id ASC LIMIT 1",
            CurrentClub::id(), $template_key, $label_key
        ) );
        return $row ? self::hydrate( $row ) : null;
    }

    private function hasAnswers( int $question_id ): bool {
        global $wpdb;
        $p = $wpdb->prefix;

        $count = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$p}tt_pdp_prep_answers WHERE question_id = %d AND club_id = %d",
            $question_id, CurrentClub::id()
        ) );
        return $count > 0;
    }

    /**
     * Row into the shape every consumer reads, with the shipped wording
     * resolved and `lineage_id` naming the first version of this question
     * so callers can tell two versions of one question from two questions.
     *
     * @return array<string,mixed>
     */
    private static function hydrate( object $row ): array {
        $label     = (string) ( $row->label ?? '' );
        $help      = (string) ( $row->help_text ?? '' );
        $label_key = (string) ( $row->label_key ?? '' );

        if ( $label_key !== '' ) {
            $shipped = PdpPrepQuestionDefaults::wordingFor( $label_key );
            if ( $shipped !== null ) {
                $label = $shipped['label'];
                $help  = $shipped['help_text'];
            }
        }

        $id      = (int) ( $row->id ?? 0 );
        $lineage = (int) ( $row->lineage_id ?? 0 );

        return [
            'id'           => $id,
            'lineage_id'   => $lineage > 0 ? $lineage : $id,
            'template_key' => (string) ( $row->template_key ?? '' ),
            'sequence'     => (int) ( $row->sequence ?? 0 ),
            'label'        => $label,
            'label_key'    => $label_key !== '' ? $label_key : null,
            'help_text'    => $help,
            'field_type'   => (string) ( $row->field_type ?? 'textarea' ),
            'options'      => self::decodeOptions( $row->options ?? null ),
            'required'     => (int) ( $row->required ?? 0 ) === 1,
            'version'      => (int) ( $row->version ?? 1 ),
            'is_archived'  => ! empty( $row->archived_at ),
        ];
    }

    private static function sanitiseType( string $type ): string {
        return in_array( $type, self::allowedFieldTypes(), true )
            ? $type
            : CustomFieldsRepository::TYPE_TEXTAREA;
    }

    /**
     * @param mixed $options
     * @return list<string>
     */
    private static function decodeOptions( $options ): array {
        if ( is_array( $options ) ) return array_values( array_map( 'strval', $options ) );
        if ( ! is_string( $options ) || $options === '' ) return [];
        $decoded = json_decode( $options, true );
        return is_array( $decoded ) ? array_values( array_map( 'strval', $decoded ) ) : [];
    }

    /** @param mixed $options */
    private static function encodeOptions( $options ): string {
        $clean = self::decodeOptions( $options );
        return $clean === [] ? '' : (string) wp_json_encode( $clean );
    }
}
