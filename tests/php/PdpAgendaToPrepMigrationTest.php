<?php
namespace TT\Tests\Php;

use WP_UnitTestCase;
use TT\Domain\Vocabularies\Enums\PdpConversationTemplate;
use TT\Infrastructure\Security\RolesService;
use TT\Infrastructure\Tenancy\CurrentClub;
use TT\Modules\Pdp\Prep\PdpPrepAnswersRepository;
use TT\Modules\Pdp\Prep\PdpPrepQuestionDefaults;
use TT\Modules\Pdp\Prep\PdpPrepQuestionsRepository;

/**
 * #3306 (epic #3301) — migration 0257 moves the retired `agenda` text into
 * the prep question set.
 *
 * The promise is "nothing is lost", so this pins the three ways that could
 * quietly stop being true: the text arriving verbatim, the migration
 * surviving a re-run, and a conversation whose template has no catch-all
 * keeping its column rather than dropping the text on the floor.
 */
final class PdpAgendaToPrepMigrationTest extends WP_UnitTestCase {

    private const AGENDA = 'Ask about the move to a back three, and about school.';

    private string $p;
    private int $club;
    private int $file;

    public function set_up(): void {
        parent::set_up();

        global $wpdb;
        $this->p    = $wpdb->prefix;
        $this->club = (int) CurrentClub::id();

        ( new RolesService() )->installRoles();
        wp_set_current_user( (int) self::factory()->user->create( [ 'role' => 'administrator' ] ) );

        $this->seedFile();
    }

    public function test_agenda_text_arrives_verbatim_as_a_prep_answer(): void {
        $conversation = $this->insertConversation( PdpConversationTemplate::START, self::AGENDA );

        $this->runMigration();

        $target = $this->catchAll( PdpConversationTemplate::START );
        $answers = ( new PdpPrepAnswersRepository() )->forConversation( $conversation );

        $this->assertArrayHasKey( $target, $answers );
        $this->assertSame( self::AGENDA, $answers[ $target ]['answer_text'] );
    }

    public function test_a_re_run_does_not_overwrite_what_a_coach_has_since_written(): void {
        $conversation = $this->insertConversation( PdpConversationTemplate::MID, self::AGENDA );

        $this->runMigration();

        $target = $this->catchAll( PdpConversationTemplate::MID );
        ( new PdpPrepAnswersRepository() )->saveMany( $conversation, [ $target => 'Rewritten since.' ] );

        $this->runMigration();

        $answers = ( new PdpPrepAnswersRepository() )->forConversation( $conversation );
        $this->assertSame( 'Rewritten since.', $answers[ $target ]['answer_text'] );
        $this->assertCount( 1, $answers );
    }

    public function test_a_conversation_with_no_catch_all_keeps_its_column(): void {
        // An academy that archived the free-text question before upgrading.
        // The column stays for one release precisely so this is recoverable.
        global $wpdb;

        $conversation = $this->insertConversation( PdpConversationTemplate::END, self::AGENDA );
        $repo         = new PdpPrepQuestionsRepository();
        $repo->archive( $this->catchAll( PdpConversationTemplate::END ) );

        $this->runMigration();

        $this->assertSame( [], ( new PdpPrepAnswersRepository() )->forConversation( $conversation ) );

        $kept = $wpdb->get_var( $wpdb->prepare(
            "SELECT agenda FROM {$this->p}tt_pdp_conversations WHERE id = %d",
            $conversation
        ) );
        $this->assertSame( self::AGENDA, $kept );
    }

    public function test_a_conversation_with_no_agenda_gains_no_empty_answer(): void {
        $conversation = $this->insertConversation( PdpConversationTemplate::START, '' );

        $this->runMigration();

        $this->assertSame( [], ( new PdpPrepAnswersRepository() )->forConversation( $conversation ) );
    }

    /* ---- fixtures ------------------------------------------------------- */

    private function runMigration(): void {
        $migration = require dirname( __DIR__, 2 ) . '/database/migrations/0257_pdp_agenda_to_prep.php';
        $migration->up();
    }

    private function catchAll( string $template_key ): int {
        $question = ( new PdpPrepQuestionsRepository() )
            ->findByLabelKey( $template_key, PdpPrepQuestionDefaults::ANYTHING_ELSE );
        $this->assertNotNull( $question, 'the shipped set ends with the free-text catch-all' );
        return (int) $question['id'];
    }

    private function seedFile(): void {
        global $wpdb;

        $wpdb->insert( "{$this->p}tt_players", [
            'club_id'    => $this->club,
            'first_name' => 'Agenda',
            'last_name'  => 'Player',
            'status'     => 'active',
        ] );
        $player = (int) $wpdb->insert_id;

        $wpdb->insert( "{$this->p}tt_pdp_files", [
            'club_id'   => $this->club,
            'player_id' => $player,
            'status'    => 'open',
        ] );
        $this->file = (int) $wpdb->insert_id;
    }

    private function insertConversation( string $template_key, string $agenda ): int {
        global $wpdb;

        static $sequence = 0;
        $sequence++;

        $wpdb->insert( "{$this->p}tt_pdp_conversations", [
            'club_id'      => $this->club,
            'pdp_file_id'  => $this->file,
            'sequence'     => $sequence,
            'template_key' => $template_key,
            'scheduled_at' => '2026-10-01 10:00:00',
            'agenda'       => $agenda !== '' ? $agenda : null,
        ] );
        return (int) $wpdb->insert_id;
    }
}
