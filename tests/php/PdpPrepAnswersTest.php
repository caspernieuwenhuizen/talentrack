<?php
namespace TT\Tests\Php;

use WP_UnitTestCase;
use WP_REST_Request;
use TT\Domain\Vocabularies\Enums\PdpConversationTemplate;
use TT\Infrastructure\Security\RolesService;
use TT\Infrastructure\Tenancy\CurrentClub;
use TT\Modules\Pdp\Prep\PdpPrepAccess;
use TT\Modules\Pdp\Prep\PdpPrepAnswersRepository;
use TT\Modules\Pdp\Prep\PdpPrepQuestionsRepository;
use TT\Modules\Pdp\Rest\PdpPrepRestController;

/**
 * #3305 (epic #3301) — the two contracts the prep answers have to keep.
 *
 * **Partial writes.** Slice 5 autosaves against this repository, so a save
 * carries the one answer the coach just changed. A write that rebuilt the
 * set would blank the rest every time the debounce elapsed, and it would
 * look to the coach like their write-up vanishing when they edited a
 * different box. That is the `AutosaveWriteContractTest` pattern applied
 * to a row set rather than a row.
 *
 * **Privacy.** Prep is where a coach writes candidly about a minor. It
 * reaches the coach and the head of academy and nobody else, ever.
 */
final class PdpPrepAnswersTest extends WP_UnitTestCase {

    private string $p;
    private int $club;
    private int $player;
    private int $conversation;
    private PdpPrepAnswersRepository $answers;
    private PdpPrepQuestionsRepository $questions;

    public function set_up(): void {
        parent::set_up();

        global $wpdb;
        $this->p    = $wpdb->prefix;
        $this->club = (int) CurrentClub::id();

        ( new RolesService() )->installRoles();
        wp_set_current_user( (int) self::factory()->user->create( [ 'role' => 'administrator' ] ) );

        $this->answers   = new PdpPrepAnswersRepository();
        $this->questions = new PdpPrepQuestionsRepository();
        $this->seedConversation();
    }

    public function test_saving_one_answer_leaves_the_others_alone(): void {
        $set = $this->questions->listForTemplate( PdpConversationTemplate::MID );
        $a   = (int) $set[0]['id'];
        $b   = (int) $set[1]['id'];

        $this->answers->saveMany( $this->conversation, [
            $a => 'He has grown into the role.',
            $b => 'Two goals have stalled since October.',
        ] );

        // The autosave fires again carrying only the field that changed.
        $this->answers->saveMany( $this->conversation, [ $a => 'He has grown into the role, quickly.' ] );

        $saved = $this->answers->forConversation( $this->conversation );
        $this->assertSame( 'He has grown into the role, quickly.', $saved[ $a ]['answer_text'] );
        $this->assertSame(
            'Two goals have stalled since October.',
            $saved[ $b ]['answer_text'],
            'an omitted answer must be left exactly as it was'
        );
    }

    public function test_an_answer_pins_the_question_version_it_was_given_against(): void {
        $set = $this->questions->listForTemplate( PdpConversationTemplate::MID );
        $id  = (int) $set[0]['id'];

        $this->answers->saveMany( $this->conversation, [ $id => 'Answered against version one.' ] );

        $saved = $this->answers->forConversation( $this->conversation );
        $this->assertSame( 1, $saved[ $id ]['question_version'] );
    }

    public function test_saving_the_same_answer_twice_does_not_duplicate_the_row(): void {
        $set = $this->questions->listForTemplate( PdpConversationTemplate::MID );
        $id  = (int) $set[0]['id'];

        $this->answers->saveMany( $this->conversation, [ $id => 'First.' ] );
        $this->answers->saveMany( $this->conversation, [ $id => 'Second.' ] );

        global $wpdb;
        $rows = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->p}tt_pdp_prep_answers
              WHERE conversation_id = %d AND question_id = %d",
            $this->conversation, $id
        ) );

        $this->assertSame( 1, $rows );
        $this->assertSame( 'Second.', $this->answers->forConversation( $this->conversation )[ $id ]['answer_text'] );
    }

    public function test_an_answer_to_a_question_that_does_not_exist_is_ignored(): void {
        $this->assertSame( 0, $this->answers->saveMany( $this->conversation, [ 999999 => 'Nowhere.' ] ) );
    }

    public function test_a_player_can_neither_read_nor_write_their_own_prep(): void {
        $set = $this->questions->listForTemplate( PdpConversationTemplate::MID );
        $id  = (int) $set[0]['id'];
        $this->answers->saveMany( $this->conversation, [ $id => 'A candid note about a child.' ] );

        $player_user = (int) self::factory()->user->create( [ 'role' => 'tt_player' ] );
        wp_set_current_user( $player_user );

        $this->assertFalse( PdpPrepAccess::canAccess( $player_user, $this->conversation ) );
        $this->assertSame( [], $this->answers->forConversation( $this->conversation ) );
        $this->assertSame( 0, $this->answers->saveMany( $this->conversation, [ $id => 'Not mine to write.' ] ) );

        $request = new WP_REST_Request( 'GET', '/talenttrack/v1/pdp-conversations/' . $this->conversation . '/prep' );
        $request->set_param( 'id', $this->conversation );
        $this->assertFalse( PdpPrepRestController::can_read_prep( $request ) );
    }

    public function test_a_parent_cannot_reach_the_prep_either(): void {
        $parent_user = (int) self::factory()->user->create( [ 'role' => 'tt_parent' ] );
        wp_set_current_user( $parent_user );

        $this->assertFalse( PdpPrepAccess::canAccess( $parent_user, $this->conversation ) );
        $this->assertSame( [], $this->answers->forConversation( $this->conversation ) );
    }

    private function seedConversation(): void {
        global $wpdb;

        $wpdb->insert( "{$this->p}tt_players", [
            'club_id'    => $this->club,
            'first_name' => 'Prep',
            'last_name'  => 'Player',
            'status'     => 'active',
        ] );
        $this->player = (int) $wpdb->insert_id;

        $wpdb->insert( "{$this->p}tt_pdp_files", [
            'club_id'   => $this->club,
            'player_id' => $this->player,
            'status'    => 'open',
        ] );
        $file = (int) $wpdb->insert_id;

        $wpdb->insert( "{$this->p}tt_pdp_conversations", [
            'club_id'      => $this->club,
            'pdp_file_id'  => $file,
            'sequence'     => 1,
            'template_key' => PdpConversationTemplate::MID,
            'scheduled_at' => '2026-10-01 10:00:00',
        ] );
        $this->conversation = (int) $wpdb->insert_id;
    }
}
