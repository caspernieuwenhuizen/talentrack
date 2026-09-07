<?php
namespace TT\Modules\DemoData\Generators;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Domain\Vocabularies\Lookups\PdpVerdictDecision;
use TT\Infrastructure\Tenancy\CurrentClub;
use TT\Modules\DemoData\DemoBatchRegistry;
use TT\Modules\Pdp\Repositories\PdpConversationsRepository;
use TT\Modules\Pdp\Repositories\PdpFilesRepository;
use TT\Modules\Pdp\Repositories\PdpVerdictsRepository;
use TT\Modules\Pdp\Repositories\SeasonsRepository;

/**
 * PdpGenerator — seasons, PDP dossiers, their conversation cycle, the
 * verdicts that close them, and calendar links.
 *
 * Goes through the PDP repositories so the conversation cycle is spaced by
 * the same block/planning-window logic the real flow uses, and so a signed-off
 * verdict raises its journey event.
 *
 * Both conversation states are represented on purpose: earlier conversations
 * conducted and signed off, the upcoming one scheduled and unsigned. A demo
 * where every conversation is closed can't show the surface an operator
 * spends most of their time on.
 */
class PdpGenerator implements DependentGeneratorInterface {

    /** @var array<string, array{agenda:string, notes:string, actions:string, reflection:string, summary:string}> */
    private const COPY_BY_LANGUAGE = [
        'en_US' => [
            'agenda'     => 'Review the block, agree two focus points, check in on wellbeing.',
            'notes'      => 'Good engagement in training. Wants more game time in midfield.',
            'actions'    => 'Extra weak-foot work twice a week; review at the next conversation.',
            'reflection' => 'I want to be braver on the ball when we are under pressure.',
            'summary'    => 'Steady progress across the season; stays in the current age group.',
        ],
        'nl_NL' => [
            'agenda'     => 'Blok terugkijken, twee aandachtspunten afspreken, welzijn bespreken.',
            'notes'      => 'Goede inzet op de training. Wil meer speeltijd op het middenveld.',
            'actions'    => 'Twee keer per week extra werken met de zwakke voet; volgende keer evalueren.',
            'reflection' => 'Ik wil durven voetballen als we onder druk staan.',
            'summary'    => 'Gestage ontwikkeling dit seizoen; blijft in de huidige leeftijdsgroep.',
        ],
    ];

    /** Verdict mix — most players stay, a few move up, releases are rare. */
    private const DECISION_WEIGHTS = [
        [ 20, PdpVerdictDecision::PROMOTE ],
        [ 88, PdpVerdictDecision::RETAIN ],
        [ 96, PdpVerdictDecision::RELEASE ],
        [ 100, PdpVerdictDecision::TRANSFER ],
    ];

    private DemoBatchRegistry $registry;

    /** @var object[] */
    private array $players;

    /** @var object[] */
    private array $teams;

    /** @var array<string,int> */
    private array $users;

    private int $weeks;

    private string $language;

    public static function category(): string {
        return 'pdp';
    }

    public static function fromContext( GeneratorContext $ctx ): self {
        return new self( $ctx->registry, $ctx->players, $ctx->teams, $ctx->users, $ctx->weeks(), $ctx->contentLanguage );
    }

    /**
     * @param object[] $players
     * @param object[] $teams
     * @param array<string,int> $users
     */
    public function __construct(
        DemoBatchRegistry $registry,
        array $players,
        array $teams,
        array $users,
        int $weeks,
        string $language = ''
    ) {
        $this->registry = $registry;
        $this->players  = $players;
        $this->teams    = $teams;
        $this->users    = $users;
        $this->weeks    = max( 1, $weeks );
        $this->language = $language !== '' ? $language : ( function_exists( 'get_locale' ) ? (string) get_locale() : 'en_US' );
    }

    public function generate(): int {
        $season_id = $this->ensureCurrentSeason();
        if ( $season_id <= 0 ) return 0;

        $season = ( new SeasonsRepository() )->find( $season_id );
        if ( ! $season ) return 0;

        $copy  = self::COPY_BY_LANGUAGE[ self::resolveLanguage( $this->language ) ];
        $files = new PdpFilesRepository();
        $convs = new PdpConversationsRepository();
        $hoa   = (int) ( $this->users['hjo'] ?? $this->users['admin'] ?? 0 );

        $coach_by_team = [];
        foreach ( $this->teams as $t ) {
            $coach_by_team[ (int) $t->id ] = (int) ( $t->head_coach_user_id ?? 0 );
        }

        $total = 1; // the season itself
        foreach ( $this->players as $p ) {
            $player_id = (int) ( $p->id ?? 0 );
            if ( $player_id <= 0 ) continue;

            $coach_id   = (int) ( $coach_by_team[ (int) ( $p->team_id ?? 0 ) ] ?? 0 );
            $cycle_size = [ 2, 3, 3, 4 ][ mt_rand( 0, 3 ) ];

            $file_id = $files->create( [
                'player_id'      => $player_id,
                'season_id'      => $season_id,
                'owner_coach_id' => $coach_id > 0 ? $coach_id : null,
                'cycle_size'     => $cycle_size,
                'notes'          => $copy['agenda'],
            ] );
            if ( $file_id <= 0 ) continue;

            $this->registry->tag( 'pdp_file', $file_id, [ 'player_id' => $player_id, 'cycle_size' => $cycle_size ] );
            $total++;

            $convs->createCycle(
                $file_id,
                $cycle_size,
                (string) $season->start_date,
                (string) $season->end_date,
                $season_id
            );

            $total += $this->fillCycle( $file_id, $coach_id, $copy );
        }

        $total += $this->closeSomeFiles( $files, $hoa, $copy );
        return $total;
    }

    /**
     * Walk a file's conversations: everything whose scheduled date has passed
     * is conducted and signed off, the next one stays open. Calendar links go
     * on the scheduled ones — that's where a coach needs the reminder.
     *
     * @param array{agenda:string, notes:string, actions:string, reflection:string, summary:string} $copy
     */
    private function fillCycle( int $file_id, int $coach_id, array $copy ): int {
        global $wpdb;

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, scheduled_at, template_key FROM {$wpdb->prefix}tt_pdp_conversations
              WHERE pdp_file_id = %d AND club_id = %d ORDER BY sequence",
            $file_id, CurrentClub::id()
        ) );

        $total = 0;
        foreach ( (array) $rows as $row ) {
            $conversation_id = (int) $row->id;
            $scheduled_ts    = strtotime( (string) $row->scheduled_at ) ?: time();
            $this->registry->tag( 'pdp_conversation', $conversation_id, [ 'pdp_file_id' => $file_id ] );
            $total++;

            // #3305 — the coach's preparation. Seeded for every conversation
            // in the cycle, conducted or not: a demo where only past talks
            // are prepared hides the surface a coach actually opens.
            $total += $this->seedPrep( $conversation_id, (string) ( $row->template_key ?? '' ), $copy );

            if ( $scheduled_ts < time() ) {
                $conducted = gmdate( 'Y-m-d H:i:s', $scheduled_ts );
                $wpdb->update(
                    "{$wpdb->prefix}tt_pdp_conversations",
                    [
                        'conducted_at'      => $conducted,
                        'agenda'            => $copy['agenda'],
                        'notes'             => $copy['notes'],
                        'agreed_actions'    => $copy['actions'],
                        'player_reflection' => $copy['reflection'],
                        'coach_signoff_at'  => $conducted,
                        'parent_ack_at'     => mt_rand( 1, 100 ) <= 70 ? $conducted : null,
                        'player_ack_at'     => mt_rand( 1, 100 ) <= 60 ? $conducted : null,
                    ],
                    [ 'id' => $conversation_id, 'club_id' => CurrentClub::id() ]
                );
                continue;
            }

            $wpdb->insert( "{$wpdb->prefix}tt_pdp_calendar_links", [
                'club_id'           => CurrentClub::id(),
                'conversation_id'   => $conversation_id,
                'provider'          => 'native',
                'provider_event_id' => 'demo-' . $conversation_id,
                'provider_payload'  => null,
            ] );
            $link_id = (int) $wpdb->insert_id;
            if ( $link_id ) {
                $this->registry->tag( 'pdp_calendar_link', $link_id );
                $total++;
            }
        }
        return $total;
    }

    /**
     * #3305 (epic #3301) — a coach's answers to this conversation's prep
     * questions.
     *
     * Written straight through `$wpdb` rather than through
     * `PdpPrepAnswersRepository`: that repository gates every write on the
     * current user being the coach who owns the file, and a generation run
     * from WP-CLI has no user at all. The gate is right; going round it here
     * is the same choice the calendar-link insert above makes.
     *
     * @param array{agenda:string, notes:string, actions:string, reflection:string, summary:string} $copy
     * @return int Rows written.
     */
    private function seedPrep( int $conversation_id, string $template_key, array $copy ): int {
        global $wpdb;

        $questions = ( new \TT\Modules\Pdp\Prep\PdpPrepQuestionsRepository() )
            ->listForTemplate( $template_key );
        if ( $questions === [] ) return 0;

        $written = 0;
        foreach ( $questions as $index => $question ) {
            // The last question is the free-text catch-all; leaving it empty
            // is what a real prep looks like more often than not.
            if ( $index === count( $questions ) - 1 ) continue;

            $text = $index === 0 ? $copy['notes'] : $copy['actions'];

            $ok = $wpdb->insert( "{$wpdb->prefix}tt_pdp_prep_answers", [
                'uuid'             => wp_generate_uuid4(),
                'club_id'          => CurrentClub::id(),
                'conversation_id'  => $conversation_id,
                'question_id'      => (int) $question['id'],
                'question_version' => (int) $question['version'],
                'answer_text'      => $text,
            ] );
            if ( ! $ok ) continue;

            $this->registry->tag( 'pdp_prep_answer', (int) $wpdb->insert_id, [
                'conversation_id' => $conversation_id,
            ] );
            $written++;
        }
        return $written;
    }

    /**
     * Close a minority of dossiers with a verdict, so both an open cycle and
     * a completed one are on screen. Signed-off verdicts raise their journey
     * event through the repository.
     *
     * @param array{agenda:string, notes:string, actions:string, reflection:string, summary:string} $copy
     */
    private function closeSomeFiles( PdpFilesRepository $files, int $hoa, array $copy ): int {
        $verdicts = new PdpVerdictsRepository();
        $file_ids = $this->registry->entityIds( 'pdp_file' );

        $total = 0;
        foreach ( $file_ids as $file_id ) {
            if ( mt_rand( 1, 100 ) > 30 ) continue;

            $file = $files->find( (int) $file_id );
            if ( ! $file ) continue;

            $signed_off = gmdate( 'Y-m-d H:i:s', strtotime( '-' . mt_rand( 3, 30 ) . ' days' ) ?: time() );
            $ok = $verdicts->upsertForFile( (int) $file_id, [
                'decision'           => $this->pickDecision(),
                'summary'            => $copy['summary'],
                'coach_id'           => (int) ( $file->owner_coach_id ?? 0 ),
                'head_of_academy_id' => $hoa,
                'signed_off_at'      => $signed_off,
            ] );
            if ( ! $ok ) continue;

            $verdict = $verdicts->findForFile( (int) $file_id );
            if ( $verdict ) {
                $this->registry->tag( 'pdp_verdict', (int) $verdict->id, [ 'pdp_file_id' => (int) $file_id ] );
                $total++;
            }
            $files->setStatus( (int) $file_id, 'completed' );
        }
        return $total;
    }

    /**
     * Reuse the club's current season when it has one; otherwise create a
     * season spanning the generated window so dossiers and conversations
     * fall inside it.
     */
    private function ensureCurrentSeason(): int {
        $seasons = new SeasonsRepository();

        $current = $seasons->current();
        if ( $current ) {
            return (int) $current->id;
        }

        $start_ts = strtotime( '-' . $this->weeks . ' weeks' );
        if ( $start_ts === false ) $start_ts = time();
        // Round out to a plausible season rather than exactly the window, so
        // the upcoming conversation still has room ahead of it.
        $start = gmdate( 'Y-m-d', $start_ts );
        $end   = gmdate( 'Y-m-d', strtotime( '+' . max( 8, (int) round( $this->weeks / 2 ) ) . ' weeks' ) ?: time() );

        $id = $seasons->create( [
            'name'       => gmdate( 'Y', $start_ts ) . '/' . gmdate( 'y', strtotime( $end ) ?: time() ),
            'start_date' => $start,
            'end_date'   => $end,
        ] );
        if ( $id > 0 ) {
            $seasons->setCurrent( $id );
            $this->registry->tag( 'season', $id );
        }
        return $id;
    }

    private function pickDecision(): string {
        $roll = mt_rand( 1, 100 );
        foreach ( self::DECISION_WEIGHTS as [ $cut, $decision ] ) {
            if ( $roll <= $cut ) return $decision;
        }
        return PdpVerdictDecision::RETAIN;
    }

    private static function resolveLanguage( string $locale ): string {
        if ( isset( self::COPY_BY_LANGUAGE[ $locale ] ) ) return $locale;
        $prefix = substr( $locale, 0, 2 );
        foreach ( array_keys( self::COPY_BY_LANGUAGE ) as $key ) {
            if ( strpos( $key, $prefix ) === 0 ) return $key;
        }
        return 'en_US';
    }
}
