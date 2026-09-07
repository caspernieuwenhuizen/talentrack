<?php
/**
 * Migration 0257 — the free-text `agenda` becomes a prep answer
 * (#3306, epic #3301).
 *
 * `tt_pdp_conversations.agenda` was the only thing a coach could fill in
 * before a talk: a bare textarea with no prompt, the same one for the
 * start-of-season conversation and the end-of-season one. Migration 0256
 * replaced it with configurable question sets; this moves what coaches
 * already wrote into them, so nothing is lost.
 *
 * WHERE IT GOES
 *
 * Every shipped question set ends with the free-text catch-all
 * `PdpPrepQuestionDefaults::ANYTHING_ELSE` — "Anything else to prepare?".
 * That is this migration's target, matched by `label_key` rather than by
 * wording, so an academy that reworded the question still gets its
 * coaches' text filed under it.
 *
 * A conversation whose template has no catch-all (an academy that archived
 * it before upgrading) keeps its `agenda` column untouched rather than
 * losing the text: the column stays for one release precisely so this case
 * is recoverable.
 *
 * THE COLUMN STAYS FOR ONE RELEASE
 *
 * Nothing writes `agenda` after this release, and no surface reads it, but
 * the column keeps its content. Same courtesy migration 0245 gave the match
 * analysis notes: if the move turns out wrong, the previous text is still
 * there to roll back to. A follow-up drops it once a release has passed.
 *
 * A NOTE ON WHO COULD SEE IT
 *
 * `agenda` was shown to the player on their own PDP view. Prep is coach and
 * head-of-academy only (epic decision 4), so text that moves here becomes
 * *less* visible, never more. That is deliberate — it is where a coach
 * writes candidly about a minor before sitting down with them — and it is
 * stated here because a migration that quietly changes who can read
 * something is the kind nobody finds later.
 *
 * Idempotent: the answer upsert is keyed on (conversation_id, question_id)
 * and skips a conversation that already has an answer against the target
 * question, so a re-run cannot overwrite what a coach has since written.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Infrastructure\Database\Migration;
use TT\Infrastructure\Logging\Logger;
use TT\Modules\Pdp\Prep\PdpPrepQuestionDefaults;

return new class extends Migration {

    public function getName(): string {
        return '0257_pdp_agenda_to_prep';
    }

    public function up(): void {
        global $wpdb;
        $p = $wpdb->prefix;

        $conversations = $p . 'tt_pdp_conversations';
        $questions     = $p . 'tt_pdp_prep_questions';
        $answers       = $p . 'tt_pdp_prep_answers';

        foreach ( [ $conversations, $questions, $answers ] as $table ) {
            if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
                return;
            }
        }

        $rows = $wpdb->get_results(
            "SELECT id, club_id, template_key, agenda
               FROM {$conversations}
              WHERE agenda IS NOT NULL AND agenda <> ''"
        );

        $moved   = 0;
        $skipped = 0;

        foreach ( (array) $rows as $row ) {
            $conversation_id = (int) $row->id;
            $club_id         = (int) $row->club_id;
            $template_key    = (string) ( $row->template_key ?? '' );
            $text            = (string) $row->agenda;

            $question = $wpdb->get_row( $wpdb->prepare(
                "SELECT id, version FROM {$questions}
                  WHERE club_id = %d AND template_key = %s AND label_key = %s
                    AND superseded_by_question_id IS NULL AND archived_at IS NULL
                  ORDER BY id ASC LIMIT 1",
                $club_id, $template_key, PdpPrepQuestionDefaults::ANYTHING_ELSE
            ) );

            if ( ! $question ) { $skipped++; continue; }

            $question_id = (int) $question->id;

            $existing = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$answers}
                  WHERE conversation_id = %d AND question_id = %d",
                $conversation_id, $question_id
            ) );
            if ( $existing > 0 ) { $skipped++; continue; }

            $now = current_time( 'mysql', true );
            $ok  = $wpdb->insert( $answers, [
                'uuid'             => wp_generate_uuid4(),
                'club_id'          => $club_id,
                'conversation_id'  => $conversation_id,
                'question_id'      => $question_id,
                'question_version' => (int) ( $question->version ?? 1 ),
                'answer_text'      => $text,
                'created_at'       => $now,
                'updated_at'       => $now,
            ] );

            if ( $ok ) $moved++;
        }

        Logger::info( 'migration.0257.summary', [
            'agenda_moved'   => $moved,
            'agenda_skipped' => $skipped,
        ] );
    }
};
