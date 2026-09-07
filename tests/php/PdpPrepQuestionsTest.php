<?php
namespace TT\Tests\Php;

use WP_UnitTestCase;
use TT\Domain\Vocabularies\Enums\PdpConversationTemplate;
use TT\Infrastructure\Security\RolesService;
use TT\Infrastructure\Tenancy\CurrentClub;
use TT\Modules\Pdp\Prep\PdpPrepQuestionDefaults;
use TT\Modules\Pdp\Prep\PdpPrepQuestionsRepository;

/**
 * #3305 (epic #3301) — the configurable prep question sets.
 *
 * The load-bearing rule here is versioning: a coach's prep from last
 * season has to keep reading the way it was answered, so a question that
 * already has answers is never rewritten in place.
 */
final class PdpPrepQuestionsTest extends WP_UnitTestCase {

    private string $p;
    private int $club;
    private PdpPrepQuestionsRepository $repo;

    public function set_up(): void {
        parent::set_up();

        global $wpdb;
        $this->p    = $wpdb->prefix;
        $this->club = (int) CurrentClub::id();
        $this->repo = new PdpPrepQuestionsRepository();

        ( new RolesService() )->installRoles();
        wp_set_current_user( (int) self::factory()->user->create( [ 'role' => 'administrator' ] ) );
    }

    public function test_every_template_ships_with_a_question_set(): void {
        foreach ( PdpConversationTemplate::ALL as $template ) {
            $set = $this->repo->listForTemplate( $template );
            $this->assertNotEmpty( $set, "template {$template} must ship with questions" );

            $keys = array_map( static fn( array $q ) => $q['label_key'], $set );
            $this->assertContains(
                PdpPrepQuestionDefaults::ANYTHING_ELSE,
                $keys,
                'every set ends with the free-text catch-all the agenda migrates into'
            );
        }
    }

    public function test_a_shipped_question_reads_its_wording_from_the_defaults(): void {
        // The seeded row's own `label` is only a fallback; the wording a
        // coach sees comes through __() so a Dutch install is Dutch.
        $set      = $this->repo->listForTemplate( PdpConversationTemplate::START );
        $shipped  = PdpPrepQuestionDefaults::wordingFor( (string) $set[0]['label_key'] );

        $this->assertNotNull( $shipped );
        $this->assertSame( $shipped['label'], $set[0]['label'] );
    }

    public function test_editing_an_unanswered_question_does_not_grow_a_version(): void {
        $set = $this->repo->listForTemplate( PdpConversationTemplate::MID );
        $id  = (int) $set[0]['id'];

        $result = $this->repo->update( $id, [ 'label' => 'What has changed?' ] );

        $this->assertSame( $id, $result, 'no answers yet, so nothing to protect' );
        $after = $this->repo->find( $id );
        $this->assertSame( 'What has changed?', $after['label'] );
        $this->assertSame( 1, $after['version'] );
        $this->assertNull( $after['label_key'], 'an admin\'s wording replaces the shipped wording' );
    }

    public function test_editing_an_answered_question_versions_it_and_keeps_the_old_wording(): void {
        $set     = $this->repo->listForTemplate( PdpConversationTemplate::MID );
        $id      = (int) $set[0]['id'];
        $original = (string) $set[0]['label'];

        $conversation = $this->seedConversation( PdpConversationTemplate::MID );
        $this->seedAnswer( $conversation, $id, 'He has grown into the role.' );

        $new_id = $this->repo->update( $id, [ 'label' => 'What moved since we last spoke?' ] );

        $this->assertNotSame( $id, $new_id, 'an answered question is never rewritten in place' );

        $old = $this->repo->find( $id );
        $this->assertSame( $original, $old['label'], 'the answered prep keeps the question it was asked' );

        $new = $this->repo->find( $new_id );
        $this->assertSame( 'What moved since we last spoke?', $new['label'] );
        $this->assertSame( 2, $new['version'] );
        $this->assertSame( $id, $new['lineage_id'], 'both versions are the same question' );

        $current = $this->repo->listForTemplate( PdpConversationTemplate::MID );
        $ids     = array_map( static fn( array $q ) => (int) $q['id'], $current );
        $this->assertContains( $new_id, $ids );
        $this->assertNotContains( $id, $ids, 'the superseded row leaves the current set' );
    }

    public function test_a_started_prep_renders_the_versions_it_was_answered_against(): void {
        $set          = $this->repo->listForTemplate( PdpConversationTemplate::MID );
        $id           = (int) $set[0]['id'];
        $conversation = $this->seedConversation( PdpConversationTemplate::MID );
        $this->seedAnswer( $conversation, $id, 'Answered against version one.' );

        $new_id = $this->repo->update( $id, [ 'label' => 'Reworded mid-season.' ] );

        $rendered = $this->repo->listForConversation( $conversation, PdpConversationTemplate::MID );
        $ids      = array_map( static fn( array $q ) => (int) $q['id'], $rendered );

        $this->assertContains( $id, $ids, 'the pinned version renders' );
        $this->assertNotContains( $new_id, $ids, 'and its replacement does not appear alongside it' );
        $this->assertCount( count( $set ), $rendered, 'the set does not grow because a question was reworded' );
    }

    public function test_a_fresh_conversation_gets_todays_set(): void {
        $conversation = $this->seedConversation( PdpConversationTemplate::END );

        $rendered = $this->repo->listForConversation( $conversation, PdpConversationTemplate::END );
        $current  = $this->repo->listForTemplate( PdpConversationTemplate::END );

        $this->assertSame(
            array_map( static fn( array $q ) => (int) $q['id'], $current ),
            array_map( static fn( array $q ) => (int) $q['id'], $rendered )
        );
    }

    public function test_add_reorder_and_archive(): void {
        $template = PdpConversationTemplate::START;
        $before   = $this->repo->listForTemplate( $template );

        $new_id = $this->repo->create( $template, [
            'label'      => 'Anything the family has raised?',
            'field_type' => 'textarea',
            'required'   => 1,
        ] );
        $this->assertGreaterThan( 0, $new_id );

        $after = $this->repo->listForTemplate( $template );
        $this->assertCount( count( $before ) + 1, $after );
        $this->assertSame( $new_id, (int) $after[ count( $after ) - 1 ]['id'], 'a new question lands at the end' );

        $this->repo->reorder( $template, [ $new_id ] );
        $reordered = $this->repo->listForTemplate( $template );
        $this->assertSame( $new_id, (int) $reordered[0]['id'], 'the named id leads; the rest keep their order behind it' );

        $this->repo->archive( $new_id );
        $ids = array_map( static fn( array $q ) => (int) $q['id'], $this->repo->listForTemplate( $template ) );
        $this->assertNotContains( $new_id, $ids );
    }

    public function test_an_unknown_field_type_falls_back_to_long_text(): void {
        $id = $this->repo->create( PdpConversationTemplate::END, [
            'label'      => 'How confident are you in this recommendation?',
            'field_type' => 'signature_pad',
        ] );

        $this->assertSame( 'textarea', $this->repo->find( $id )['field_type'] );
    }

    public function test_a_question_needs_a_label(): void {
        $this->assertSame( 0, $this->repo->create( PdpConversationTemplate::MID, [ 'label' => '   ' ] ) );
    }

    /* ---- fixtures ------------------------------------------------------- */

    private function seedConversation( string $template_key ): int {
        global $wpdb;

        $wpdb->insert( "{$this->p}tt_players", [
            'club_id'    => $this->club,
            'first_name' => 'Prep',
            'last_name'  => 'Player',
            'status'     => 'active',
        ] );
        $player = (int) $wpdb->insert_id;

        $wpdb->insert( "{$this->p}tt_pdp_files", [
            'club_id'   => $this->club,
            'player_id' => $player,
            'status'    => 'open',
        ] );
        $file = (int) $wpdb->insert_id;

        $wpdb->insert( "{$this->p}tt_pdp_conversations", [
            'club_id'      => $this->club,
            'pdp_file_id'  => $file,
            'sequence'     => 1,
            'template_key' => $template_key,
            'scheduled_at' => '2026-10-01 10:00:00',
        ] );
        return (int) $wpdb->insert_id;
    }

    private function seedAnswer( int $conversation_id, int $question_id, string $text ): void {
        global $wpdb;
        $wpdb->insert( "{$this->p}tt_pdp_prep_answers", [
            'uuid'             => wp_generate_uuid4(),
            'club_id'          => $this->club,
            'conversation_id'  => $conversation_id,
            'question_id'      => $question_id,
            'question_version' => 1,
            'answer_text'      => $text,
        ] );
    }
}
