<?php
/**
 * Migration 0256 — `tt_pdp_prep_questions` + `tt_pdp_prep_answers`
 * (#3305, epic #3301).
 *
 * A coach preparing a PDP conversation had one column to work with:
 * `agenda`, a bare textarea with no prompt. Nothing differed between the
 * start-of-season talk and the end-of-season one, and nothing made one
 * coach's preparation comparable with another's, or with their own from
 * three months earlier.
 *
 * These two tables are the question sets that replace it: configured per
 * conversation template, so "Start of season" asks different things than
 * "End of season", and answered per conversation.
 *
 * WHY A QUESTION IS NEVER EDITED IN PLACE
 *
 * A prep from last season has to keep reading the way it was answered. An
 * admin who rewords a question mid-season would otherwise retro-label
 * every answer already given against the old wording — a coach's sentence
 * filed under a question they were never asked.
 *
 * So an edit inserts a new row carrying `version + 1` and stamps
 * `superseded_by_question_id` on the old one. The old row stays, out of the
 * current set but reachable, and `tt_pdp_prep_answers.question_id` points
 * at the exact version it answered. `question_version` is denormalised onto
 * the answer so a row is self-describing without the join.
 *
 * `lineage_id` carries the first version's id down the chain, so a caller
 * can tell "two versions of one question" from "two questions" without
 * walking `supersedes_question_id` back to the root.
 *
 * WHY THE SHIPPED WORDING IS A `label_key` RATHER THAN THE SEEDED TEXT
 *
 * Migrations run before an install has necessarily settled its locale, so
 * a Dutch academy seeded from here would open Configuration and find
 * English. A seeded row keeps its `label_key` and the repository resolves
 * the wording through `PdpPrepQuestionDefaults` at read time; the stored
 * label is the fallback. Editing clears the key and the admin's wording
 * becomes the wording, permanently.
 *
 * `club_id` + `uuid` on both tables per CLAUDE.md §4. Idempotent: the seed
 * is guarded on the table being empty for the club, so a re-run cannot
 * duplicate a question set.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Domain\Vocabularies\Enums\PdpConversationTemplate;
use TT\Infrastructure\Database\Migration;
use TT\Infrastructure\Logging\Logger;
use TT\Modules\Pdp\Prep\PdpPrepQuestionDefaults;

return new class extends Migration {

    public function getName(): string {
        return '0256_pdp_prep_questions';
    }

    public function up(): void {
        global $wpdb;
        $p       = $wpdb->prefix;
        $charset = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta( "CREATE TABLE IF NOT EXISTS {$p}tt_pdp_prep_questions (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            uuid CHAR(36) NOT NULL,
            club_id INT UNSIGNED NOT NULL DEFAULT 1,
            template_key VARCHAR(32) NOT NULL,
            sequence SMALLINT UNSIGNED NOT NULL DEFAULT 1,
            label VARCHAR(255) NOT NULL DEFAULT '',
            label_key VARCHAR(64) DEFAULT NULL,
            help_text TEXT,
            field_type VARCHAR(32) NOT NULL DEFAULT 'textarea',
            options LONGTEXT,
            required TINYINT(1) NOT NULL DEFAULT 0,
            version SMALLINT UNSIGNED NOT NULL DEFAULT 1,
            lineage_id BIGINT UNSIGNED DEFAULT NULL,
            supersedes_question_id BIGINT UNSIGNED DEFAULT NULL,
            superseded_by_question_id BIGINT UNSIGNED DEFAULT NULL,
            archived_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_uuid (uuid),
            KEY idx_current (club_id, template_key, superseded_by_question_id, archived_at, sequence),
            KEY idx_label_key (club_id, label_key)
        ) {$charset};" );

        dbDelta( "CREATE TABLE IF NOT EXISTS {$p}tt_pdp_prep_answers (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            uuid CHAR(36) NOT NULL,
            club_id INT UNSIGNED NOT NULL DEFAULT 1,
            conversation_id BIGINT UNSIGNED NOT NULL,
            question_id BIGINT UNSIGNED NOT NULL,
            question_version SMALLINT UNSIGNED NOT NULL DEFAULT 1,
            answer_text LONGTEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_uuid (uuid),
            UNIQUE KEY uk_answer (conversation_id, question_id),
            KEY idx_conversation (club_id, conversation_id)
        ) {$charset};" );

        $this->seedDefaultSets();
    }

    /**
     * Seed the shipped question set once per club. Guarded on the club
     * having no questions at all: an academy that has already configured
     * its own sets must not have ours appended underneath them.
     */
    private function seedDefaultSets(): void {
        global $wpdb;
        $table = $wpdb->prefix . 'tt_pdp_prep_questions';

        $clubs = $wpdb->get_col( "SELECT DISTINCT club_id FROM {$wpdb->prefix}tt_pdp_files" );
        $clubs = array_values( array_unique( array_map( 'intval', (array) $clubs ) ) );
        if ( $clubs === [] ) $clubs = [ 1 ];

        $seeded = 0;
        foreach ( $clubs as $club_id ) {
            if ( $club_id <= 0 ) continue;

            $existing = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE club_id = %d",
                $club_id
            ) );
            if ( $existing > 0 ) continue;

            foreach ( PdpConversationTemplate::ALL as $template_key ) {
                $sequence = 1;
                foreach ( PdpPrepQuestionDefaults::forTemplate( $template_key ) as $q ) {
                    $wpdb->insert( $table, [
                        'uuid'         => wp_generate_uuid4(),
                        'club_id'      => $club_id,
                        'template_key' => $template_key,
                        'sequence'     => $sequence,
                        'label'        => $q['label'],
                        'label_key'    => $q['key'],
                        'help_text'    => $q['help_text'],
                        'field_type'   => $q['field_type'],
                        'required'     => (int) $q['required'],
                        'version'      => 1,
                    ] );
                    $sequence++;
                    $seeded++;
                }
            }
        }

        Logger::info( 'migration.0256.summary', [ 'questions_seeded' => $seeded ] );
    }
};
