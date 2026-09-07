<?php
namespace TT\Tests\Php;

use WP_UnitTestCase;
use TT\Domain\Vocabularies\Enums\PdpConversationTemplate;
use TT\Infrastructure\Security\RolesService;
use TT\Infrastructure\Tenancy\CurrentClub;
use TT\Modules\Pdp\Frontend\FrontendMyPdpView;
use TT\Modules\Pdp\Prep\PdpPrepAnswersRepository;
use TT\Modules\Pdp\Prep\PdpPrepQuestionsRepository;
use TT\Modules\Pdp\Print\PdpPrintRouter;
use TT\Modules\Pdp\Repositories\PdpConversationsRepository;

/**
 * #3306 (epic #3301) — the prep boundary, per surface.
 *
 * Prep is where a coach writes candidly about a minor before sitting down
 * with them. The repository gate (#3305) is the load-bearing one; these
 * tests pin that no surface reaches round it — the player's own PDP view,
 * the print, or the file payload a parent's token can read.
 *
 * The `agenda` column is the reason this needs testing per surface rather
 * than once: it used to be shown to the player, and migration 0257 moves
 * its content into prep, which is private. A surface still rendering the
 * column would be a regression in exactly the direction that matters.
 */
final class PdpPrepPrivacyTest extends WP_UnitTestCase {

    private const SECRET = 'Family situation is difficult; go gently on the minutes question.';

    private string $p;
    private int $club;
    private int $player;
    private int $file;
    private int $conversation;
    private int $coach;

    public function set_up(): void {
        parent::set_up();

        global $wpdb;
        $this->p    = $wpdb->prefix;
        $this->club = (int) CurrentClub::id();

        ( new RolesService() )->installRoles();
        $this->coach = (int) self::factory()->user->create( [ 'role' => 'administrator' ] );
        wp_set_current_user( $this->coach );

        $this->seed();
    }

    public function test_the_players_own_view_shows_no_preparation(): void {
        $player_user = (int) self::factory()->user->create( [ 'role' => 'tt_player' ] );
        $this->linkPlayerAccount( $player_user );
        wp_set_current_user( $player_user );

        ob_start();
        FrontendMyPdpView::render( $this->playerRow() );
        $html = (string) ob_get_clean();

        $this->assertStringNotContainsString( self::SECRET, $html );
        // The old `agenda` bubble is gone with it.
        $this->assertStringNotContainsString( 'tt-pdp-pane-prep', $html );
    }

    public function test_the_printed_file_carries_no_preparation(): void {
        $file = $this->fileRow();

        $this->assertStringNotContainsString( self::SECRET, PdpPrintRouter::renderHtml( $file, false ) );
        $this->assertStringNotContainsString( self::SECRET, PdpPrintRouter::renderHtml( $file, true ) );
    }

    public function test_the_conversation_payload_no_longer_carries_agenda(): void {
        // A player's and a parent's token both reach this endpoint, and the
        // column now holds what a coach wrote in private.
        $request = new \WP_REST_Request( 'GET', '/talenttrack/v1/pdp-files/' . $this->file );
        $request->set_param( 'id', $this->file );

        $response = \TT\Modules\Pdp\Rest\PdpFilesRestController::get_one( $request );
        $data     = $response->get_data();

        $conversations = $data['data']['conversations'] ?? $data['conversations'] ?? [];
        $this->assertNotEmpty( $conversations );
        foreach ( $conversations as $conversation ) {
            $this->assertArrayNotHasKey( 'agenda', $conversation );
        }
    }

    public function test_agenda_is_no_longer_writable_through_the_repository(): void {
        // Belt and braces on the retirement: even a caller that still sends
        // the field cannot put anything back into the column.
        global $wpdb;

        ( new PdpConversationsRepository() )->update( $this->conversation, [
            'agenda' => 'Written the old way.',
            'notes'  => 'Written the new way.',
        ] );

        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT agenda, notes FROM {$this->p}tt_pdp_conversations WHERE id = %d",
            $this->conversation
        ) );

        $this->assertNull( $row->agenda );
        $this->assertSame( 'Written the new way.', $row->notes );
    }

    /* ---- fixtures ------------------------------------------------------- */

    private function seed(): void {
        global $wpdb;

        $wpdb->insert( "{$this->p}tt_teams", [ 'club_id' => $this->club, 'name' => 'U17 Privacy' ] );
        $team = (int) $wpdb->insert_id;

        $wpdb->insert( "{$this->p}tt_players", [
            'club_id'    => $this->club,
            'team_id'    => $team,
            'first_name' => 'Privacy',
            'last_name'  => 'Player',
            'status'     => 'active',
        ] );
        $this->player = (int) $wpdb->insert_id;

        $wpdb->insert( "{$this->p}tt_seasons", [
            'club_id'    => $this->club,
            'name'       => '2026/27',
            'start_date' => '2026-07-01',
            'end_date'   => '2027-06-30',
            'is_current' => 1,
        ] );
        $season = (int) $wpdb->insert_id;

        $wpdb->insert( "{$this->p}tt_pdp_files", [
            'club_id'   => $this->club,
            'player_id' => $this->player,
            'season_id' => $season,
            'status'    => 'open',
        ] );
        $this->file = (int) $wpdb->insert_id;

        $wpdb->insert( "{$this->p}tt_pdp_conversations", [
            'club_id'      => $this->club,
            'pdp_file_id'  => $this->file,
            'sequence'     => 1,
            'template_key' => PdpConversationTemplate::START,
            'scheduled_at' => '2026-10-01 10:00:00',
        ] );
        $this->conversation = (int) $wpdb->insert_id;

        $questions = ( new PdpPrepQuestionsRepository() )->listForTemplate( PdpConversationTemplate::START );
        $this->assertNotEmpty( $questions, 'migration 0256 seeds the shipped set' );

        ( new PdpPrepAnswersRepository() )->saveMany(
            $this->conversation,
            [ (int) $questions[0]['id'] => self::SECRET ]
        );
    }

    private function linkPlayerAccount( int $user_id ): void {
        global $wpdb;
        $wpdb->update(
            "{$this->p}tt_players",
            [ 'wp_user_id' => $user_id ],
            [ 'id' => $this->player ]
        );
    }

    private function playerRow(): object {
        global $wpdb;
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$this->p}tt_players WHERE id = %d",
            $this->player
        ) );
        $this->assertNotNull( $row );
        return $row;
    }

    private function fileRow(): object {
        global $wpdb;
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$this->p}tt_pdp_files WHERE id = %d",
            $this->file
        ) );
        $this->assertNotNull( $row );
        return $row;
    }
}
