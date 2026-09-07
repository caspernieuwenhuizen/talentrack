<?php
namespace TT\Modules\Vct\Rules;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Modules\Vct\Repositories\VctMacroBlocksRepository;
use TT\Modules\Vct\Services\VctCycleResolver;

/**
 * ProgressionRule — Pass 5.
 *
 * Decides how hard this week is meant to be, and resolves it in three
 * steps, most specific first:
 *
 *   1. **The team's cycle** (#3359, epic #3354), when one is configured.
 *      The resolver already knows which cycle week the training falls in
 *      and whether the week is neutral because the team plays, so this
 *      pass only reads the answer and stamps it.
 *   2. **The season's macro-blocks**, when there is no cycle. Unchanged
 *      from before cycles existed — an install that never configures one
 *      plans exactly as it did.
 *   3. **Neither** — multiplier 1.0 and an `info` warning, so a coach
 *      sees "no progression context" rather than a silently flat plan.
 *
 * A neutral week gives multiplier 1.0 and says why, which matters more
 * than it sounds: a flat week with no explanation reads as a bug, and the
 * first thing a coach does about a bug is stop trusting the planner.
 */
class ProgressionRule implements RulePass {

    private VctMacroBlocksRepository $macro_blocks;
    private ?VctCycleResolver $cycles;

    public function __construct( VctMacroBlocksRepository $macro_blocks, ?VctCycleResolver $cycles = null ) {
        $this->macro_blocks = $macro_blocks;
        $this->cycles       = $cycles;
    }

    public function apply( SessionPlanContext $ctx ): SessionPlanContext {
        if ( $ctx->season_id <= 0 ) {
            $ctx->progression_multiplier = 1.0;
            $ctx->addWarning( 'no_macro_block_configured', 'info', [
                'reason' => 'season_id_missing_or_unset',
            ] );
            return $ctx;
        }

        if ( $this->applyCycle( $ctx ) ) return $ctx;

        return $this->applyMacroBlock( $ctx );
    }

    /**
     * Step 1. Returns false when the team has no cycle, so the caller falls
     * through to the macro-block path.
     */
    private function applyCycle( SessionPlanContext $ctx ): bool {
        // Kept, not rebuilt per call: the resolver memoises the season walk,
        // and a composer run planning a month of trainings should do one
        // walk rather than one per training.
        $this->cycles ??= new VctCycleResolver();

        $week = $this->cycles->resolveWeek( $ctx->team_id, $ctx->season_id, $ctx->session_date );
        if ( $week === null ) return false;

        $ctx->cycle_week  = $week['cycle_week'];
        $ctx->cycle_state = $week['state'];

        if ( $week['state'] === VctCycleResolver::STATE_NEUTRAL ) {
            $ctx->progression_multiplier = 1.0;
            $ctx->addWarning( 'cycle_week_neutral', 'info', [
                'reason'         => $week['source'],
                'fixture_week'   => $week['fixture_week'],
                'week_starts_on' => $week['week_starts_on'],
            ] );
            return true;
        }

        $ctx->progression_multiplier = $week['multiplier'];

        // A cycle week can carry a speelwijze theme (#2322). It only fills
        // a gap — a theme the coach asked for explicitly always wins.
        if ( $ctx->tactical_theme === null && $week['tactical_theme'] !== null ) {
            $ctx->tactical_theme = $week['tactical_theme'];
        }

        return true;
    }

    /** Step 2 and 3. The behaviour that predates cycles, unchanged. */
    private function applyMacroBlock( SessionPlanContext $ctx ): SessionPlanContext {
        $block = $this->macro_blocks->findCurrent( $ctx->team_id, $ctx->season_id, $ctx->session_date );
        if ( $block === null ) {
            $ctx->progression_multiplier = 1.0;
            $ctx->addWarning( 'no_macro_block_configured', 'info', [
                'team_id'      => $ctx->team_id,
                'season_id'    => $ctx->season_id,
                'session_date' => $ctx->session_date,
            ] );
            return $ctx;
        }

        $week_within = $this->weekWithinBlock( $block['start_date'], $ctx->session_date );
        $profile     = $block['phase_profile'];

        // A block longer than its profile used to fall through to 1.0 in
        // silence, so a twelve-week block on a six-week profile planned six
        // real weeks and six flat ones with nothing said. Same multiplier,
        // but now the coach is told the profile ran out.
        if ( $profile !== [] && $this->profileEntryFor( $profile, $week_within ) === null ) {
            $ctx->addWarning( 'phase_profile_shorter_than_block', 'info', [
                'week_within_block' => $week_within,
                'profile_weeks'     => count( $profile ),
            ] );
        }

        $ctx->progression_multiplier = $this->multiplierForWeek( $profile, $week_within );
        return $ctx;
    }

    /**
     * 1-indexed week-within-block. Week 1 is the seven days starting at
     * `start_date`. Returns at minimum 1 even for session_date <
     * start_date (defensive — shouldn't happen because the repo filters by
     * date range).
     */
    private function weekWithinBlock( string $start_date, string $session_date ): int {
        $start = strtotime( $start_date );
        $sess  = strtotime( $session_date );
        if ( $start === false || $sess === false || $sess < $start ) return 1;
        $days = (int) floor( ( $sess - $start ) / 86400 );
        return max( 1, (int) floor( $days / 7 ) + 1 );
    }

    /**
     * Find the multiplier for the given week from the phase_profile array.
     * Each entry is `{week, phase, multiplier}`. Falls back to 1.0 if the
     * requested week is beyond the profile.
     *
     * @param list<array<string,mixed>> $profile
     */
    private function multiplierForWeek( array $profile, int $week ): float {
        $entry = $this->profileEntryFor( $profile, $week );
        if ( $entry === null ) return 1.0;
        return isset( $entry['multiplier'] ) ? (float) $entry['multiplier'] : 1.0;
    }

    /**
     * @param list<array<string,mixed>> $profile
     * @return array<string,mixed>|null
     */
    private function profileEntryFor( array $profile, int $week ): ?array {
        foreach ( $profile as $entry ) {
            if ( (int) ( $entry['week'] ?? 0 ) === $week ) return $entry;
        }
        return null;
    }
}
