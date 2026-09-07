<?php
namespace TT\Modules\Vct\Services;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Modules\Pdp\Repositories\SeasonsRepository;
use TT\Modules\Vct\Repositories\VctCycleWeekOverridesRepository;
use TT\Modules\Vct\Repositories\VctMacroBlocksRepository;
use TT\Modules\Vct\Repositories\VctTeamCyclesRepository;
use TT\Modules\Vct\Rules\Providers\FixtureWeeksReader;
use TT\Modules\Vct\Rules\Providers\NativeFixtureWeeksReader;

/**
 * VctCycleResolver — where a team is in its cycle, week by week (#3358,
 * epic #3354).
 *
 * A cycle is the repeating 3/4/6-week rhythm a team trains to. A week in
 * which the team plays is NEUTRAL: the cycle pauses, and — this is the
 * whole of the feature — the week is **not consumed**. Week 3 meets a
 * fixture, the week reports neutral, and the week after it is still week 3.
 * The cycle stretches around the fixture list rather than drifting out of
 * step with it.
 *
 * ## Why this is computed rather than stored
 *
 * Because a pause shifts every week after it, cycle position cannot come
 * from modular arithmetic on the date. The reflex is to materialise a row
 * per team per week and invalidate it whenever a fixture moves — but a
 * fixture moving in October changes every week from October to May, so that
 * invalidation is the rest of the season, every time, and any path that
 * forgets to fire it leaves a coach looking at a plan that silently
 * disagrees with the calendar.
 *
 * So: nothing is stored except the exceptions. `tt_vct_cycle_weeks` holds
 * manual overrides only, most weeks have no row, and the walk below is at
 * most ~52 iterations over one query's worth of fixture dates. Computing it
 * is cheaper than keeping it correct.
 *
 * ## Memoisation
 *
 * `resolveWeek()` is the hot path — the activity stamper calls it once per
 * training. It resolves the season once and reuses it, so a request that
 * stamps thirty trainings does one walk, not thirty.
 *
 * @phpstan-type CycleWeek array{
 *     week_starts_on: string,
 *     state: string,
 *     cycle_week: int|null,
 *     phase: string|null,
 *     multiplier: float,
 *     tactical_theme: string|null,
 *     source: string,
 *     fixture_week: bool,
 *     note: string|null
 * }
 * @phpstan-type PhaseWeek array{phase: string|null, multiplier: float, tactical_theme: string|null}
 */
class VctCycleResolver {

    public const STATE_ACTIVE  = 'active';
    public const STATE_NEUTRAL = 'neutral';

    public const SOURCE_AUTO     = 'auto';
    public const SOURCE_OVERRIDE = 'override';

    private VctTeamCyclesRepository $cycles;
    private VctCycleWeekOverridesRepository $overrides;
    private VctMacroBlocksRepository $macro_blocks;
    private FixtureWeeksReader $fixtures;
    private SeasonsRepository $seasons;

    /** @var array<string, list<CycleWeek>> Per-request memo, keyed team:season. */
    private array $memo = [];

    public function __construct(
        ?VctTeamCyclesRepository $cycles = null,
        ?VctCycleWeekOverridesRepository $overrides = null,
        ?VctMacroBlocksRepository $macro_blocks = null,
        ?FixtureWeeksReader $fixtures = null,
        ?SeasonsRepository $seasons = null
    ) {
        $this->cycles       = $cycles       ?? new VctTeamCyclesRepository();
        $this->overrides    = $overrides    ?? new VctCycleWeekOverridesRepository();
        $this->macro_blocks = $macro_blocks ?? new VctMacroBlocksRepository();
        $this->fixtures     = $fixtures     ?? new NativeFixtureWeeksReader();
        $this->seasons      = $seasons      ?? new SeasonsRepository();
    }

    /**
     * The season's weeks for one team, ascending from the cycle anchor.
     * Empty when the team has no cycle configured — every caller treats
     * that as "no cycle", not as an error, and keeps the macro-block
     * behaviour it had before cycles existed.
     *
     * @return list<CycleWeek>
     */
    public function resolveSeason( int $team_id, int $season_id ): array {
        $key = $team_id . ':' . $season_id;
        if ( isset( $this->memo[ $key ] ) ) return $this->memo[ $key ];

        $this->memo[ $key ] = $this->walk( $team_id, $season_id );
        return $this->memo[ $key ];
    }

    /**
     * The week containing `$date`, or null when the team has no cycle, the
     * date is outside the season, or the date falls before the anchor.
     *
     * A date before the anchor deliberately resolves to null rather than
     * week 1: the cycle has not started, and a pre-season training should
     * carry no cycle week rather than an invented one.
     *
     * @return CycleWeek|null
     */
    public function resolveWeek( int $team_id, int $season_id, string $date ): ?array {
        $monday = VctTeamCyclesRepository::normaliseToMonday( $date );
        if ( $monday === null ) return null;

        foreach ( $this->resolveSeason( $team_id, $season_id ) as $week ) {
            if ( $week['week_starts_on'] === $monday ) return $week;
        }
        return null;
    }

    /** Drop the memo. Only needed by long-running processes and tests. */
    public function flush(): void {
        $this->memo = [];
    }

    /**
     * @return list<CycleWeek>
     */
    private function walk( int $team_id, int $season_id ): array {
        $cycle = $this->cycles->findForTeamSeason( $team_id, $season_id );
        if ( $cycle === null ) return [];

        $season = $this->seasons->find( $season_id );
        if ( $season === null ) return [];

        $season_end = (string) ( $season->end_date ?? '' );
        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $season_end ) ) return [];

        $start_ts = strtotime( $cycle['anchor_date'] );
        $end_ts   = strtotime( $season_end );
        if ( $start_ts === false || $end_ts === false || $end_ts < $start_ts ) return [];

        $profile   = $this->profileFor( $cycle );
        $length    = max( 1, (int) $cycle['cycle_weeks'] );
        $overrides = $this->overrides->listForSeason( $team_id, $season_id );

        $fixture_weeks = array_flip( $this->fixtures->matchWeekMondays(
            $team_id,
            $cycle['anchor_date'],
            $season_end
        ) );

        $weeks   = [];
        $counter = 1;

        for ( $ts = $start_ts; $ts <= $end_ts; $ts += 7 * 86400 ) {
            $monday      = gmdate( 'Y-m-d', $ts );
            $has_fixture = isset( $fixture_weeks[ $monday ] );
            $override    = $overrides[ $monday ] ?? null;

            if ( $override !== null ) {
                $state  = $override['state'];
                $source = self::SOURCE_OVERRIDE;
                $note   = $override['note'];
            } else {
                $state  = $has_fixture ? self::STATE_NEUTRAL : self::STATE_ACTIVE;
                $source = self::SOURCE_AUTO;
                $note   = null;
            }

            if ( $state === self::STATE_NEUTRAL ) {
                // The counter does NOT advance. This one line is decision 2
                // of epic #3354 and the reason the whole class exists.
                $weeks[] = [
                    'week_starts_on' => $monday,
                    'state'          => self::STATE_NEUTRAL,
                    'cycle_week'     => null,
                    'phase'          => null,
                    'multiplier'     => 1.0,
                    'tactical_theme' => null,
                    'source'         => $source,
                    'fixture_week'   => $has_fixture,
                    'note'           => $note,
                ];
                continue;
            }

            $entry = $this->profileWeek( $profile, $counter );

            $weeks[] = [
                'week_starts_on' => $monday,
                'state'          => self::STATE_ACTIVE,
                'cycle_week'     => $counter,
                'phase'          => $entry['phase'],
                'multiplier'     => $entry['multiplier'],
                'tactical_theme' => $entry['tactical_theme'],
                'source'         => $source,
                'fixture_week'   => $has_fixture,
                'note'           => $note,
            ];

            $counter = $counter >= $length ? 1 : $counter + 1;
        }

        return $weeks;
    }

    /**
     * The per-week shape the cycle repeats. Prefers the template the cycle
     * names; falls back to whichever seeded reference has exactly as many
     * weeks as the cycle is long, so a cycle configured before templates
     * were pickable still has a shape.
     *
     * @param array{cycle_weeks:int, template_id:?int} $cycle
     * @return list<array<string,mixed>>
     */
    private function profileFor( array $cycle ): array {
        $references = $this->macro_blocks->listReferenceTemplates();

        if ( $cycle['template_id'] !== null ) {
            foreach ( $references as $ref ) {
                if ( (int) $ref['id'] === (int) $cycle['template_id'] ) {
                    return is_array( $ref['phase_profile'] ) ? $ref['phase_profile'] : [];
                }
            }
        }

        foreach ( $references as $ref ) {
            $weeks = is_array( $ref['phase_profile'] ) ? $ref['phase_profile'] : [];
            if ( count( $weeks ) === (int) $cycle['cycle_weeks'] ) return $weeks;
        }

        return [];
    }

    /**
     * The profile entry for a cycle week. A cycle whose template is missing
     * or short resolves to a neutral multiplier rather than an error — the
     * training is still plannable, it just carries no progression.
     *
     * @param list<array<string,mixed>> $profile
     * @return PhaseWeek
     */
    private function profileWeek( array $profile, int $week ): array {
        foreach ( $profile as $entry ) {
            if ( (int) ( $entry['week'] ?? 0 ) !== $week ) continue;

            $phase = isset( $entry['phase'] ) && $entry['phase'] !== '' ? (string) $entry['phase'] : null;
            $theme = isset( $entry['tactical_theme'] ) && $entry['tactical_theme'] !== ''
                ? (string) $entry['tactical_theme']
                : null;

            return [
                'phase'          => $phase,
                'multiplier'     => isset( $entry['multiplier'] ) ? (float) $entry['multiplier'] : 1.0,
                'tactical_theme' => $theme,
            ];
        }

        return [ 'phase' => null, 'multiplier' => 1.0, 'tactical_theme' => null ];
    }
}
