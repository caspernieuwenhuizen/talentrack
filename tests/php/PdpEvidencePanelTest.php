<?php
namespace TT\Tests\Php;

use WP_UnitTestCase;
use TT\Shared\Frontend\Components\EvidencePanel;

/**
 * #3303 (epic #3301) — the shared evidence rendering.
 *
 * The component composes a packet and nothing else, so these are render
 * tests over a synthetic packet rather than integration tests: what
 * matters is that every group the packet carries reaches the page, that a
 * group with nothing in it *says so* rather than vanishing, and that the
 * markup a phone reflows on — `data-label` on every cell — is present.
 */
final class PdpEvidencePanelTest extends WP_UnitTestCase {

    public function test_every_group_reaches_the_page(): void {
        $html = EvidencePanel::html( self::packet() );

        $this->assertStringContainsString( 'Evaluations', $html );
        $this->assertStringContainsString( 'Attendance and minutes', $html );
        $this->assertStringContainsString( 'Goals', $html );
        $this->assertStringContainsString( 'Player self-reflection', $html );
        $this->assertStringContainsString( 'Notes, injuries and journey', $html );
        $this->assertStringContainsString( 'Potential and behaviour', $html );
    }

    public function test_an_evaluation_shows_what_it_said_not_just_that_it_happened(): void {
        // The defect this slice exists to fix: the old sidebar listed
        // evaluation dates with no rating, assessor or notes.
        $html = EvidencePanel::html( self::packet() );

        $this->assertStringContainsString( 'Ada Kuipers', $html );
        $this->assertStringContainsString( 'Reads the game two passes ahead.', $html );
        $this->assertStringContainsString( 'Passing', $html );
    }

    public function test_an_empty_group_says_so_rather_than_disappearing(): void {
        // A coach needs to see that there is no evidence, not be shown a
        // shorter page and left to assume they scrolled past it.
        $packet = self::packet();
        $packet['evaluations'] = [];
        $packet['goals']       = [];

        $html = EvidencePanel::html( $packet );

        $this->assertStringContainsString( 'Evaluations', $html );
        $this->assertStringContainsString( 'No evaluations in this window.', $html );
        $this->assertStringContainsString( 'Goals', $html );
        $this->assertStringContainsString( 'No goals open or closed in this window.', $html );
    }

    public function test_a_goal_that_has_not_moved_is_called_out(): void {
        $packet = self::packet();
        $packet['goals'][0]['changed_in_window'] = false;

        $html = EvidencePanel::html( $packet );

        $this->assertStringContainsString( 'No movement in this window', $html );
    }

    public function test_tables_carry_a_data_label_on_every_cell(): void {
        // The mobile reflow turns each row into a labelled card and reads
        // the column heading from data-label; a cell without one loses its
        // label at 360px.
        $html = EvidencePanel::html( self::packet() );

        preg_match_all( '/<td\b(?![^>]*data-label=)[^>]*>/', $html, $unlabelled );
        $this->assertSame( [], $unlabelled[0], 'every <td> needs a data-label' );
    }

    public function test_the_season_packet_shows_no_self_reflection_section(): void {
        // There is no conversation, so there is nothing a player wrote for
        // it — an empty "the player has not written one yet" would be a
        // false statement on the verdict screen.
        $packet                    = self::packet();
        $packet['window']['scope'] = 'season';

        $html = EvidencePanel::html( $packet );

        $this->assertStringNotContainsString( 'Player self-reflection', $html );
    }

    public function test_the_print_variant_drops_the_links(): void {
        $linked   = EvidencePanel::html( self::packet() );
        $unlinked = EvidencePanel::html( self::packet(), [ 'linked' => false ] );

        $this->assertStringContainsString( 'tt-record-link', $linked );
        $this->assertStringNotContainsString( 'tt-record-link', $unlinked );
        // The content is still all there — only the links went.
        $this->assertStringContainsString( 'Ada Kuipers', $unlinked );
    }

    public function test_a_missing_packet_renders_an_explanation_not_a_fatal(): void {
        $html = EvidencePanel::html( null );

        $this->assertStringContainsString( 'No evidence could be assembled', $html );
    }

    /** @return array<string,mixed> */
    private static function packet(): array {
        return [
            'file_id'         => 12,
            'player_id'       => 340,
            'conversation_id' => 7,
            'season'          => [ 'id' => 3, 'name' => '2026/27', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30' ],
            'window'          => [ 'from' => '2026-10-01', 'to' => '2027-06-30', 'scope' => 'conversation' ],
            'status'          => [],
            'behaviour'       => [ (object) [ 'rating' => 7.5, 'rated_at' => '2026-11-02 09:00:00' ] ],
            'potential'       => [ (object) [ 'potential_band' => 'first_team', 'set_at' => '2026-11-02 09:00:00' ] ],
            'evaluations'     => [
                [
                    'id'            => 88,
                    'eval_date'     => '2026-11-01',
                    'rating'        => 7.2,
                    'notes'         => 'Reads the game two passes ahead.',
                    'assessor_id'   => 9,
                    'assessor_name' => 'Ada Kuipers',
                    'eval_type_id'  => 2,
                    'categories'    => [
                        [ 'category_id' => 4, 'label' => 'Passing', 'is_main' => true, 'rating' => 7.5 ],
                    ],
                ],
            ],
            'attendance'      => [ 'activities' => 24, 'present' => 21, 'absent' => 2, 'excused' => 1, 'rate' => 88 ],
            'minutes'         => [
                'apps'      => 14,
                'minutes'   => 812,
                'breakdown' => [
                    [ 'activity_id' => 55, 'session_date' => '2026-11-08', 'title' => 'Ajax U17 away', 'type_key' => 'league', 'minutes' => 60, 'record_type' => 'actual' ],
                ],
            ],
            'goals'           => [
                [
                    'id'                => 5,
                    'title'             => 'Win more headers',
                    'status'            => 'in_progress',
                    'priority'          => 'medium',
                    'due_date'          => '2027-03-01',
                    'created_at'        => '2026-10-05 09:00:00',
                    'updated_at'        => '2026-11-09 09:00:00',
                    'created_in_window' => true,
                    'changed_in_window' => true,
                    'is_closed'         => false,
                ],
            ],
            'injuries'        => [
                [ 'id' => 2, 'started_on' => '2026-10-05', 'expected_return' => '', 'actual_return' => '2026-11-20', 'notes' => 'Ankle.', 'is_open' => false ],
            ],
            'notes'           => [
                [ 'id' => 31, 'created_at' => '2026-11-15 09:00:00', 'author_id' => 9, 'author_name' => 'Ada Kuipers', 'visibility' => 'public', 'body' => 'Quiet since the move up.' ],
            ],
            'self_reflection' => 'I want more minutes on the left.',
            'recent_journey'  => [ (object) [ 'id' => 1, 'event_type' => 'age_group_change', 'event_date' => '2026-10-20 09:00:00', 'summary' => 'Moved to U17.' ] ],
        ];
    }
}
