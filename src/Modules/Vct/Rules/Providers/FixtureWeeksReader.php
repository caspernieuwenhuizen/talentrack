<?php
namespace TT\Modules\Vct\Rules\Providers;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * FixtureWeeksReader — VCT-owned interface over the Activities module,
 * for the one question the cycle resolver asks: which weeks does this
 * team play in?
 *
 * Deliberately separate from `ActivitiesReader`, which answers "when is
 * the nearest fixture to this date" for the per-training MD context.
 * The cycle needs whole weeks, in bulk, for a season at a time — one
 * query rather than fifty-two — and widening the existing interface
 * would force every implementer (including the in-memory test fakes) to
 * grow a method they have no use for.
 *
 * Same architectural rationale as `ActivitiesReader`: each cross-module
 * dependency one-way, through an interface this module owns.
 */
interface FixtureWeeksReader {

    /**
     * The Monday of every ISO week between `$from` and `$to` in which the
     * team plays. "Plays" means an activity whose type is match-like —
     * game or tournament, a tournament being a multi-game day (#2686).
     * Cancelled and archived fixtures are excluded: a called-off game
     * should not pause a team's cycle.
     *
     * @param string $from Y-m-d, inclusive.
     * @param string $to   Y-m-d, inclusive.
     * @return list<string> Y-m-d Mondays, ascending, distinct.
     */
    public function matchWeekMondays( int $team_id, string $from, string $to ): array;
}
