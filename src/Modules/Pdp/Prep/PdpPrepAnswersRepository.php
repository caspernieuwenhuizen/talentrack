<?php
namespace TT\Modules\Pdp\Prep;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Infrastructure\Tenancy\CurrentClub;

/**
 * A coach's answers to the prep questions for one conversation.
 *
 * **Every write is partial.** The prep form autosaves (CLAUDE.md §6 model
 * A), which means a save carries the one field the coach just touched and
 * nothing else. A repository that rebuilt the row set from the request
 * would blank the other four answers every time the debounce elapsed —
 * a coach's write-up disappearing because they edited a different box.
 * `saveMany()` therefore only ever touches the question ids present in the
 * payload; `tests/php/PdpPrepAnswersTest.php` pins that an omitted answer
 * is left alone.
 *
 * Access is checked here rather than at the call site: prep is coach +
 * head of academy only, and a surface added later must not be able to
 * forget to ask (see {@see PdpPrepAccess}).
 */
final class PdpPrepAnswersRepository {

    /** The answers table, resolved per call. */
    private function table(): string {
        global $wpdb;
        return $wpdb->prefix . 'tt_pdp_prep_answers';
    }

    /**
     * Every answer for a conversation, keyed by question id.
     *
     * Returns an empty array for a reader who may not see this prep — a
     * player or a parent, on any surface. Not an error: the caller asked
     * what the reader may see, and the answer is nothing.
     *
     * @return array<int,array<string,mixed>>
     */
    public function forConversation( int $conversation_id, int $user_id = 0 ): array {
        if ( $conversation_id <= 0 ) return [];

        $user_id = $user_id > 0 ? $user_id : get_current_user_id();
        if ( ! PdpPrepAccess::canAccess( $user_id, $conversation_id ) ) return [];

        global $wpdb;
        $p = $wpdb->prefix;

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$p}tt_pdp_prep_answers
              WHERE conversation_id = %d AND club_id = %d",
            $conversation_id, CurrentClub::id()
        ) );

        $out = [];
        foreach ( (array) $rows as $row ) {
            $out[ (int) $row->question_id ] = [
                'question_id'      => (int) $row->question_id,
                'question_version' => (int) ( $row->question_version ?? 1 ),
                'answer_text'      => (string) ( $row->answer_text ?? '' ),
                'updated_at'       => (string) ( $row->updated_at ?? '' ),
            ];
        }
        return $out;
    }

    /**
     * Write the answers named in `$answers` and leave every other answer
     * on this conversation untouched.
     *
     * @param array<int|string,mixed> $answers question_id => answer text
     * @return int Number of answers written.
     */
    public function saveMany( int $conversation_id, array $answers, int $user_id = 0 ): int {
        if ( $conversation_id <= 0 || $answers === [] ) return 0;

        $user_id = $user_id > 0 ? $user_id : get_current_user_id();
        if ( ! PdpPrepAccess::canAccess( $user_id, $conversation_id ) ) return 0;

        $questions = new PdpPrepQuestionsRepository();
        $written   = 0;

        foreach ( $answers as $question_id => $value ) {
            $question_id = (int) $question_id;
            if ( $question_id <= 0 ) continue;

            $question = $questions->find( $question_id );
            if ( ! $question ) continue;

            $text = is_array( $value )
                ? implode( ', ', array_map( 'strval', $value ) )
                : (string) $value;

            $written += $this->saveOne( $conversation_id, $question_id, (int) $question['version'], $text ) ? 1 : 0;
        }

        return $written;
    }

    /**
     * Upsert one answer. The unique key on (conversation_id, question_id)
     * is what makes this safe under a debounce firing twice.
     */
    private function saveOne( int $conversation_id, int $question_id, int $version, string $text ): bool {
        global $wpdb;
        $p = $wpdb->prefix;

        $now      = current_time( 'mysql', true );
        $existing = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$p}tt_pdp_prep_answers
              WHERE conversation_id = %d AND question_id = %d AND club_id = %d",
            $conversation_id, $question_id, CurrentClub::id()
        ) );

        if ( $existing > 0 ) {
            $ok = $wpdb->update(
                $this->table(),
                [ 'answer_text' => $text, 'updated_at' => $now ],
                [ 'id' => $existing, 'club_id' => CurrentClub::id() ]
            );
            return $ok !== false;
        }

        return (bool) $wpdb->insert( $this->table(), [
            'uuid'             => wp_generate_uuid4(),
            'club_id'          => CurrentClub::id(),
            'conversation_id'  => $conversation_id,
            'question_id'      => $question_id,
            'question_version' => $version,
            'answer_text'      => $text,
            'created_at'       => $now,
            'updated_at'       => $now,
        ] );
    }

    /**
     * Has this conversation been prepared at all? Drives which pane a
     * coach lands on, and nothing about the content — so it deliberately
     * does not go through the access gate.
     */
    public function countForConversation( int $conversation_id ): int {
        if ( $conversation_id <= 0 ) return 0;

        global $wpdb;
        $p = $wpdb->prefix;

        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$p}tt_pdp_prep_answers
              WHERE conversation_id = %d AND club_id = %d
                AND answer_text IS NOT NULL AND answer_text <> ''",
            $conversation_id, CurrentClub::id()
        ) );
    }
}
