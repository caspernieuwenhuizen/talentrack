<?php
namespace TT\Tests\Php;

use WP_UnitTestCase;
use TT\Domain\Vocabularies\Lookups\ActivityTypeKey;
use TT\Modules\Activities\Repositories\ActivitiesRepository;
use TT\Modules\Vct\Rules\MdContextRule;
use TT\Modules\Vct\Rules\Providers\NativeActivitiesReader;
use TT\Modules\Vct\Rules\SessionPlanContext;
use TT\Modules\Vct\Repositories\VctTeamSchedulesRepository;

/**
 * #3355 — four queries filtered `tt_activities.activity_type`, a column
 * that does not exist, with a `LIKE '%match%'` pattern this vocabulary has
 * never contained. Every one returned nothing and every caller took its
 * "found nothing" branch, so VCT resolved MD context `NONE` for every
 * training and the player "my team" fixture surfaces were permanently empty.
 *
 * The existing VCT rule tests inject an in-memory `ActivitiesReader`, which
 * is why none of them could catch this. These go through the production
 * reader against real rows — that is the point of the file.
 */
final class ActivityTypeColumnTest extends WP_UnitTestCase {

    private int $teamId = 0;

    public function set_up(): void {
        parent::set_up();
        global $wpdb;

        $wpdb->insert( $wpdb->prefix . 'tt_teams', [ 'club_id' => 1, 'name' => 'Cycle Column Team' ] );
        $this->teamId = (int) $wpdb->insert_id;
    }

    private function activity( string $type, string $date, array $extra = [] ): int {
        global $wpdb;
        $wpdb->insert( $wpdb->prefix . 'tt_activities', array_merge( [
            'club_id'           => 1,
            'team_id'           => $this->teamId,
            'title'             => $type . ' ' . $date,
            'session_date'      => $date,
            'activity_type_key' => $type,
        ], $extra ) );
        return (int) $wpdb->insert_id;
    }

    /** The production reader must actually find a game. */
    public function test_native_reader_finds_a_game(): void {
        $this->activity( ActivityTypeKey::GAME, '2026-03-07' );

        $reader = new NativeActivitiesReader();
        $this->assertSame(
            '2026-03-07',
            $reader->nextMatchDate( $this->teamId, '2026-03-01', '2026-03-31' )
        );
        $this->assertSame(
            '2026-03-07',
            $reader->previousMatchDate( $this->teamId, '2026-03-01', '2026-03-31' )
        );
    }

    /** A tournament is a multi-game day (#2686) and anchors MD context too. */
    public function test_native_reader_counts_a_tournament(): void {
        $this->activity( ActivityTypeKey::TOURNAMENT, '2026-04-11' );

        $reader = new NativeActivitiesReader();
        $this->assertSame(
            '2026-04-11',
            $reader->nextMatchDate( $this->teamId, '2026-04-01', '2026-04-30' )
        );
    }

    /** A training is not a fixture and must never anchor a match day. */
    public function test_native_reader_ignores_a_training(): void {
        $this->activity( ActivityTypeKey::TRAINING, '2026-05-06' );

        $reader = new NativeActivitiesReader();
        $this->assertNull( $reader->nextMatchDate( $this->teamId, '2026-05-01', '2026-05-31' ) );
    }

    /** A called-off game must not pull the week into an MD-1 shape. */
    public function test_native_reader_ignores_a_cancelled_game(): void {
        $this->activity( ActivityTypeKey::GAME, '2026-06-06', [ 'activity_status_key' => 'cancelled' ] );

        $reader = new NativeActivitiesReader();
        $this->assertNull( $reader->nextMatchDate( $this->teamId, '2026-06-01', '2026-06-30' ) );
    }

    /**
     * The bug in one assertion: MdContextRule returned NONE for every
     * training regardless of the fixture list.
     */
    public function test_md_context_resolves_against_real_rows(): void {
        $this->activity( ActivityTypeKey::GAME, '2026-03-07' );

        $ctx                     = new SessionPlanContext();
        $ctx->team_id            = $this->teamId;
        $ctx->session_date       = '2026-03-06';
        $ctx->md_logic_enabled   = true;

        $rule = new MdContextRule( new NativeActivitiesReader(), new VctTeamSchedulesRepository() );
        $out  = $rule->apply( $ctx );

        $this->assertSame( 'MD-1', $out->md_context );
    }

    /** The player "my team" fixture peek was permanently empty. */
    public function test_upcoming_matches_for_team_returns_games(): void {
        $future = gmdate( 'Y-m-d', time() + 7 * 86400 );
        $this->activity( ActivityTypeKey::GAME, $future );

        $rows = ( new ActivitiesRepository() )->upcomingMatchesForTeam( $this->teamId );

        $this->assertCount( 1, $rows );
        $this->assertSame( $future, (string) $rows[0]->session_date );
    }

    /**
     * No query may reference the bare column again. A grep-level guard is
     * the honest test here: the defect was not a wrong answer from one
     * function, it was the same typo copied into four files.
     */
    public function test_no_source_file_queries_the_bare_column(): void {
        $root  = dirname( __DIR__, 2 ) . '/src';
        $hits  = [];
        $files = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $root ) );

        foreach ( $files as $file ) {
            if ( $file->getExtension() !== 'php' ) continue;
            $body = (string) file_get_contents( $file->getPathname() );
            if ( preg_match( '/activity_type\s+LIKE|activity_type_key\s*=\s*.match.|[\'"]activity_type[\'"]\s*=>\s*[\'"](?:training|game)[\'"]/', $body ) ) {
                $hits[] = $file->getPathname();
            }
        }

        $this->assertSame( [], $hits, 'Queries must use activity_type_key and ActivityTypeKey::MATCH_LIKE_SQL.' );
    }
}
