<?php
namespace TT\Tests\Php;

use WP_UnitTestCase;
use TT\Infrastructure\Security\RolesService;
use TT\Infrastructure\Tenancy\CurrentClub;
use TT\Modules\Pdp\EvidencePacket;
use TT\Modules\Pdp\Print\PdpPrintRouter;
use TT\Shared\Frontend\Components\EvidencePanel;

/**
 * #3304 (epic #3301) — the three evidence surfaces agree.
 *
 * This is the point of the slice, so it is the test. The Evidence tab, the
 * printed file and the verdict screen used to assemble their own evidence
 * and filter it differently — the print scoped by neither `club_id` nor the
 * activity archive flag — so the same player on the same day could show a
 * coach one set of numbers and the head of academy another.
 */
final class PdpEvidenceSurfaceParityTest extends WP_UnitTestCase {

    private const SEASON_FROM = '2026-07-01';
    private const SEASON_TO   = '2027-06-30';

    private string $p;
    private int $club;
    private int $player;
    private int $team;
    private int $file;
    private int $coach;

    public function set_up(): void {
        parent::set_up();

        global $wpdb;
        $this->p    = $wpdb->prefix;
        $this->club = (int) CurrentClub::id();

        ( new RolesService() )->installRoles();
        $this->coach = (int) self::factory()->user->create( [
            'role'         => 'administrator',
            'display_name' => 'Ada Kuipers',
        ] );
        wp_set_current_user( $this->coach );

        $this->seed();
    }

    public function test_the_print_and_the_verdict_screen_show_the_same_numbers(): void {
        $packet = EvidencePacket::forFile( $this->file );
        $this->assertNotNull( $packet );

        $verdict_html = EvidencePanel::html( $packet );
        $print_html   = PdpPrintRouter::renderHtml( $this->fileRow(), true );

        // Two live in-window evaluations; the third is outside the season
        // and the fourth belongs to another club.
        $this->assertCount( 2, $packet['evaluations'] );
        $this->assertSame( 2, $packet['attendance']['activities'] );

        foreach ( [ 'Reads the game two passes ahead.', 'Ada Kuipers' ] as $needle ) {
            $this->assertStringContainsString( $needle, $verdict_html );
            $this->assertStringContainsString( $needle, $print_html );
        }

        // The row that must NOT appear: another club's evaluation. The old
        // print scoped by nothing at all and would have printed it.
        $this->assertStringNotContainsString( 'Belongs to another club.', $print_html );
        $this->assertStringNotContainsString( 'Belongs to another club.', $verdict_html );

        // And the one outside the season window.
        $this->assertStringNotContainsString( 'Before the season opened.', $print_html );
        $this->assertStringNotContainsString( 'Before the season opened.', $verdict_html );
    }

    public function test_the_conversation_tab_reads_the_same_assembly_narrowed(): void {
        // Same builder, one window narrower: the tab is per-conversation,
        // the print and the verdict are per-file.
        $conversation = $this->seedConversation();

        $file_packet = EvidencePacket::forFile( $this->file );
        $conv_packet = EvidencePacket::forConversation( $conversation );

        $this->assertNotNull( $file_packet );
        $this->assertNotNull( $conv_packet );

        $this->assertSame( $file_packet['player_id'], $conv_packet['player_id'] );
        $this->assertSame( 'season', $file_packet['window']['scope'] );
        $this->assertSame( 'conversation', $conv_packet['window']['scope'] );
        $this->assertSame( array_keys( $file_packet ), array_keys( $conv_packet ) );
    }

    public function test_the_print_router_carries_no_evidence_sql(): void {
        $source = (string) file_get_contents(
            TT_PLUGIN_DIR . 'src/Modules/Pdp/Print/PdpPrintRouter.php'
        );

        $this->assertStringNotContainsString( 'tt_evaluations', $source );
        $this->assertStringNotContainsString( 'tt_attendance', $source );
    }

    public function test_the_evidence_page_is_omitted_when_the_toggle_is_off(): void {
        // A coach printing a one-page summary for a parent should still be
        // able to leave the evidence page off.
        $without = PdpPrintRouter::renderHtml( $this->fileRow(), false );
        $with    = PdpPrintRouter::renderHtml( $this->fileRow(), true );

        $this->assertStringNotContainsString( 'tt-evidence', $without );
        $this->assertStringContainsString( 'tt-evidence', $with );
    }

    public function test_the_print_variant_forces_the_table_layout(): void {
        // DomPDF resolves no viewport and ignores @media print, so the
        // exporter would otherwise get the 360px card stack.
        $print_html = PdpPrintRouter::renderHtml( $this->fileRow(), true );

        $this->assertStringContainsString( 'tt-evidence--print', $print_html );
        $this->assertStringContainsString( '.tt-evidence--print .tt-evidence__table', $print_html );
    }

    /* ---- fixtures ------------------------------------------------------- */

    private function fileRow(): object {
        global $wpdb;
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$this->p}tt_pdp_files WHERE id = %d",
            $this->file
        ) );
        $this->assertNotNull( $row );
        return $row;
    }

    private function seed(): void {
        global $wpdb;

        $wpdb->insert( "{$this->p}tt_teams", [ 'club_id' => $this->club, 'name' => 'U17 Parity' ] );
        $this->team = (int) $wpdb->insert_id;

        $wpdb->insert( "{$this->p}tt_players", [
            'club_id'       => $this->club,
            'team_id'       => $this->team,
            'first_name'    => 'Parity',
            'last_name'     => 'Player',
            'status'        => 'active',
            'date_of_birth' => '2010-03-04',
        ] );
        $this->player = (int) $wpdb->insert_id;

        $wpdb->insert( "{$this->p}tt_seasons", [
            'club_id'    => $this->club,
            'name'       => '2026/27',
            'start_date' => self::SEASON_FROM,
            'end_date'   => self::SEASON_TO,
        ] );
        $season = (int) $wpdb->insert_id;

        $wpdb->insert( "{$this->p}tt_pdp_files", [
            'club_id'   => $this->club,
            'player_id' => $this->player,
            'season_id' => $season,
            'status'    => 'open',
        ] );
        $this->file = (int) $wpdb->insert_id;

        $this->insertEvaluation( '2026-09-01', 'Reads the game two passes ahead.', $this->club );
        $this->insertEvaluation( '2026-11-01', 'Braver under pressure now.', $this->club );
        $this->insertEvaluation( '2026-01-15', 'Before the season opened.', $this->club );
        $this->insertEvaluation( '2026-11-02', 'Belongs to another club.', $this->club + 1 );

        $present = $this->insertActivity( '2026-09-03' );
        $absent  = $this->insertActivity( '2026-09-10' );
        $stale   = $this->insertActivity( '2026-02-10' );
        $this->insertAttendance( $present, 'Present' );
        $this->insertAttendance( $absent, 'Absent' );
        $this->insertAttendance( $stale, 'Present' );
    }

    private function seedConversation(): int {
        global $wpdb;
        $wpdb->insert( "{$this->p}tt_pdp_conversations", [
            'club_id'      => $this->club,
            'pdp_file_id'  => $this->file,
            'sequence'     => 1,
            'template_key' => 'start',
            'scheduled_at' => '2026-10-01 10:00:00',
        ] );
        return (int) $wpdb->insert_id;
    }

    private function insertEvaluation( string $date, string $notes, int $club_id ): int {
        global $wpdb;
        $wpdb->insert( "{$this->p}tt_evaluations", [
            'club_id'   => $club_id,
            'player_id' => $this->player,
            'coach_id'  => $this->coach,
            'eval_date' => $date,
            'rating'    => 7.0,
            'notes'     => $notes,
        ] );
        return (int) $wpdb->insert_id;
    }

    private function insertActivity( string $date ): int {
        global $wpdb;
        $wpdb->insert( "{$this->p}tt_activities", [
            'club_id'             => $this->club,
            'team_id'             => $this->team,
            'title'               => 'Training ' . $date,
            'session_date'        => $date,
            'activity_type_key'   => 'training',
            'activity_status_key' => 'completed',
            'plan_state'          => 'completed',
        ] );
        return (int) $wpdb->insert_id;
    }

    private function insertAttendance( int $activity_id, string $status ): void {
        global $wpdb;
        $wpdb->insert( "{$this->p}tt_attendance", [
            'club_id'     => $this->club,
            'activity_id' => $activity_id,
            'player_id'   => $this->player,
            'status'      => $status,
            'is_guest'    => 0,
            'record_type' => 'actual',
        ] );
    }
}
