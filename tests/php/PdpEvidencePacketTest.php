<?php
namespace TT\Tests\Php;

use WP_UnitTestCase;
use TT\Infrastructure\Security\RolesService;
use TT\Infrastructure\Tenancy\CurrentClub;
use TT\Modules\Pdp\EvidencePacket;

/**
 * #3302 (epic #3301) — the evidence packet is the single assembly three
 * surfaces read. These tests pin the two things that makes true: it
 * carries every group the epic agreed, and it excludes what the three
 * hand-rolled assemblies each excluded differently — rows outside the
 * window, rows belonging to another club, and archived rows.
 */
final class PdpEvidencePacketTest extends WP_UnitTestCase {

    private const SEASON_FROM = '2026-07-01';
    private const SEASON_TO   = '2027-06-30';

    private string $p;
    private int $club;
    private int $player;
    private int $team;
    private int $file;
    private int $conv_one;
    private int $conv_two;
    private int $coach;

    public function set_up(): void {
        parent::set_up();

        global $wpdb;
        $this->p    = $wpdb->prefix;
        $this->club = (int) CurrentClub::id();

        // Roles are installed on activation, which the test bootstrap does
        // not fire; the notes group is capability-gated, so install them.
        ( new RolesService() )->installRoles();

        $this->coach = (int) self::factory()->user->create( [
            'role'         => 'administrator',
            'display_name' => 'Ada Kuipers',
        ] );
        wp_set_current_user( $this->coach );

        $this->team   = $this->insertTeam();
        $this->player = $this->insertPlayer( $this->team );
        $season       = $this->insertSeason();
        $this->file   = $this->insertFile( $this->player, $season );

        $this->conv_one = $this->insertConversation( 1, '2026-10-01 10:00:00', 'start' );
        $this->conv_two = $this->insertConversation( 2, '2027-02-01 10:00:00', 'mid' );
    }

    public function test_the_season_packet_carries_every_group(): void {
        $this->seedFullPlayerHistory();

        $packet = EvidencePacket::forFile( $this->file );

        $this->assertNotNull( $packet );
        $this->assertSame( $this->player, $packet['player_id'] );
        $this->assertSame( 'season', $packet['window']['scope'] );
        $this->assertSame( self::SEASON_FROM, $packet['window']['from'] );

        $this->assertCount( 1, $packet['evaluations'], 'one live in-window evaluation' );
        $this->assertCount( 1, $packet['goals'] );
        $this->assertCount( 1, $packet['injuries'] );
        $this->assertCount( 1, $packet['notes'] );
        $this->assertCount( 1, $packet['recent_journey'] );

        $this->assertSame( 2, $packet['attendance']['activities'] );
        $this->assertSame( 1, $packet['attendance']['present'] );
        $this->assertSame( 1, $packet['attendance']['absent'] );
        $this->assertSame( 50.0, (float) $packet['attendance']['rate'] );

        $this->assertArrayHasKey( 'minutes', $packet );
        $this->assertArrayHasKey( 'breakdown', $packet['minutes'] );
    }

    public function test_an_evaluation_carries_its_assessor_and_what_they_wrote(): void {
        // The Evidence tab used to show a coach that an evaluation existed
        // without showing what it said. This is the group that fixes.
        $this->insertEvaluation( '2026-09-01', 'Reads the game two passes ahead.' );

        $packet = EvidencePacket::forFile( $this->file );

        $this->assertNotNull( $packet );
        $eval = $packet['evaluations'][0];
        $this->assertSame( 'Reads the game two passes ahead.', $eval['notes'] );
        $this->assertSame( 'Ada Kuipers', $eval['assessor_name'] );
        $this->assertArrayHasKey( 'categories', $eval );
    }

    public function test_rows_outside_the_window_in_another_club_or_archived_are_excluded(): void {
        $in_window = $this->insertEvaluation( '2026-09-01', 'Counts.' );

        $this->insertEvaluation( '2026-01-15', 'Before the season opened.' );
        $archived = $this->insertEvaluation( '2026-11-01', 'Archived.' );
        $this->setEvaluationArchived( $archived );
        $other_club = $this->insertEvaluation( '2026-11-02', 'Another club.' );
        $this->setEvaluationClub( $other_club, $this->club + 1 );

        $packet = EvidencePacket::forFile( $this->file );

        $this->assertNotNull( $packet );
        $ids = array_map( static fn( array $e ): int => $e['id'], $packet['evaluations'] );
        $this->assertSame( [ $in_window ], $ids );
    }

    public function test_a_conversation_packet_starts_at_the_previous_conversation(): void {
        $before = $this->insertEvaluation( '2026-09-01', 'Before the first talk.' );
        $after  = $this->insertEvaluation( '2026-11-15', 'Since the first talk.' );

        $packet = EvidencePacket::forConversation( $this->conv_two );

        $this->assertNotNull( $packet );
        $this->assertSame( 'conversation', $packet['window']['scope'] );
        $this->assertSame( '2026-10-01', $packet['window']['from'] );

        $ids = array_map( static fn( array $e ): int => $e['id'], $packet['evaluations'] );
        $this->assertSame( [ $after ], $ids, 'the earlier evaluation belongs to the previous talk' );
        $this->assertNotContains( $before, $ids );
    }

    public function test_the_first_conversation_of_a_cycle_falls_back_to_the_season(): void {
        // A coach preparing the first talk of the season wants the season,
        // not an empty page.
        $this->insertEvaluation( '2026-09-01', 'Early season.' );

        $packet = EvidencePacket::forConversation( $this->conv_one );

        $this->assertNotNull( $packet );
        $this->assertSame( self::SEASON_FROM, $packet['window']['from'] );
        $this->assertCount( 1, $packet['evaluations'] );
    }

    public function test_the_conversation_packet_carries_the_players_self_reflection(): void {
        global $wpdb;
        $wpdb->update(
            "{$this->p}tt_pdp_conversations",
            [ 'player_reflection' => 'I want more minutes on the left.' ],
            [ 'id' => $this->conv_two ]
        );

        $packet = EvidencePacket::forConversation( $this->conv_two );

        $this->assertNotNull( $packet );
        $this->assertSame( 'I want more minutes on the left.', $packet['self_reflection'] );
    }

    public function test_the_file_packet_carries_no_self_reflection(): void {
        $packet = EvidencePacket::forFile( $this->file );

        $this->assertNotNull( $packet );
        $this->assertSame( '', $packet['self_reflection'] );
        $this->assertSame( 0, $packet['conversation_id'] );
    }

    public function test_a_reader_without_notes_access_sees_no_notes(): void {
        // Staff notes are written about a minor by people who assume only
        // staff read them. A packet is assembled for a reader.
        $this->insertNote( 'Struggling with the move to a back three.' );

        $outsider = (int) self::factory()->user->create( [ 'role' => 'subscriber' ] );
        wp_set_current_user( $outsider );

        $packet = EvidencePacket::forFile( $this->file );

        $this->assertNotNull( $packet );
        $this->assertSame( [], $packet['notes'] );
    }

    public function test_an_unknown_file_or_conversation_yields_nothing(): void {
        $this->assertNull( EvidencePacket::forFile( 999999 ) );
        $this->assertNull( EvidencePacket::forConversation( 999999 ) );
        $this->assertNull( EvidencePacket::forConversation( 0 ) );
    }

    /* ---- fixtures ------------------------------------------------------- */

    private function seedFullPlayerHistory(): void {
        $this->insertEvaluation( '2026-09-01', 'Live evaluation.' );
        $this->insertEvaluation( '2026-01-15', 'Outside the season.' );

        $present = $this->insertActivity( '2026-09-03' );
        $absent  = $this->insertActivity( '2026-09-10' );
        $stale   = $this->insertActivity( '2026-02-10' );
        $this->insertAttendance( $present, 'Present' );
        $this->insertAttendance( $absent, 'Absent' );
        $this->insertAttendance( $stale, 'Present' );

        $this->insertGoal( 'Win more headers', 'in_progress', '2026-09-05 09:00:00' );
        $this->insertInjury( '2026-10-05', '2026-11-20' );
        $this->insertNote( 'Quiet in the dressing room since the move up.' );
        $this->insertJourneyEvent( '2026-09-20 09:00:00', 'Moved to U17.' );
    }

    private function insertTeam(): int {
        global $wpdb;
        $wpdb->insert( "{$this->p}tt_teams", [
            'club_id' => $this->club,
            'name'    => 'U17 Evidence',
        ] );
        return (int) $wpdb->insert_id;
    }

    private function insertPlayer( int $team_id ): int {
        global $wpdb;
        $wpdb->insert( "{$this->p}tt_players", [
            'club_id'       => $this->club,
            'team_id'       => $team_id,
            'first_name'    => 'Evidence',
            'last_name'     => 'Player',
            'status'        => 'active',
            'date_of_birth' => '2010-03-04',
        ] );
        return (int) $wpdb->insert_id;
    }

    private function insertSeason(): int {
        global $wpdb;
        $wpdb->insert( "{$this->p}tt_seasons", [
            'club_id'    => $this->club,
            'name'       => '2026/27',
            'start_date' => self::SEASON_FROM,
            'end_date'   => self::SEASON_TO,
        ] );
        return (int) $wpdb->insert_id;
    }

    private function insertFile( int $player_id, int $season_id ): int {
        global $wpdb;
        $wpdb->insert( "{$this->p}tt_pdp_files", [
            'club_id'   => $this->club,
            'player_id' => $player_id,
            'season_id' => $season_id,
            'status'    => 'open',
        ] );
        return (int) $wpdb->insert_id;
    }

    private function insertConversation( int $sequence, string $scheduled, string $template ): int {
        global $wpdb;
        $wpdb->insert( "{$this->p}tt_pdp_conversations", [
            'club_id'      => $this->club,
            'pdp_file_id'  => $this->file,
            'sequence'     => $sequence,
            'template_key' => $template,
            'scheduled_at' => $scheduled,
        ] );
        return (int) $wpdb->insert_id;
    }

    private function insertEvaluation( string $date, string $notes ): int {
        global $wpdb;
        $wpdb->insert( "{$this->p}tt_evaluations", [
            'club_id'   => $this->club,
            'player_id' => $this->player,
            'coach_id'  => $this->coach,
            'eval_date' => $date,
            'rating'    => 7.0,
            'notes'     => $notes,
        ] );
        return (int) $wpdb->insert_id;
    }

    private function setEvaluationArchived( int $eval_id ): void {
        global $wpdb;
        $wpdb->update( "{$this->p}tt_evaluations", [ 'archived_at' => '2026-11-05 09:00:00' ], [ 'id' => $eval_id ] );
    }

    private function setEvaluationClub( int $eval_id, int $club_id ): void {
        global $wpdb;
        $wpdb->update( "{$this->p}tt_evaluations", [ 'club_id' => $club_id ], [ 'id' => $eval_id ] );
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

    private function insertGoal( string $title, string $status, string $created ): int {
        global $wpdb;
        $wpdb->insert( "{$this->p}tt_goals", [
            'club_id'    => $this->club,
            'player_id'  => $this->player,
            'title'      => $title,
            'status'     => $status,
            'created_by' => $this->coach,
            'created_at' => $created,
            'updated_at' => $created,
        ] );
        return (int) $wpdb->insert_id;
    }

    private function insertInjury( string $started, ?string $returned ): int {
        global $wpdb;
        $wpdb->insert( "{$this->p}tt_player_injuries", [
            'club_id'       => $this->club,
            'player_id'     => $this->player,
            'started_on'    => $started,
            'actual_return' => $returned,
            'notes'         => 'Ankle.',
        ] );
        return (int) $wpdb->insert_id;
    }

    private function insertNote( string $body ): int {
        global $wpdb;
        $wpdb->insert( "{$this->p}tt_thread_messages", [
            'club_id'        => $this->club,
            'uuid'           => wp_generate_uuid4(),
            'thread_type'    => 'player',
            'thread_id'      => $this->player,
            'author_user_id' => $this->coach,
            'body'           => $body,
            'visibility'     => 'public',
            'created_at'     => '2026-09-15 09:00:00',
        ] );
        return (int) $wpdb->insert_id;
    }

    private function insertJourneyEvent( string $when, string $summary ): int {
        global $wpdb;
        $wpdb->insert( "{$this->p}tt_player_events", [
            'club_id'            => $this->club,
            'uuid'               => wp_generate_uuid4(),
            'player_id'          => $this->player,
            'event_type'         => 'age_group_change',
            'event_date'         => $when,
            'summary'            => $summary,
            'source_module'      => 'tests',
            'source_entity_type' => 'test_event',
            'source_entity_id'   => 1,
        ] );
        return (int) $wpdb->insert_id;
    }
}
