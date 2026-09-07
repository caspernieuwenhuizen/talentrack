<?php
namespace TT\Modules\Vct\Rules;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * SessionPlanContext — the working object passed through every RulePass.
 *
 * Mutable across the pipeline (each pass returns the same instance with
 * its own mutations applied), but the pipeline is single-threaded and
 * the engine never exposes a context to outside callers — only the
 * final `Session` payload is returned.
 *
 * Fields fall into three buckets:
 *
 *   1. Input — set by the VctTrainingComposer before the pipeline runs:
 *      team_id, season_id, age_group, session_date, tactical_theme,
 *      roster_player_ids, requested_duration_minutes (optional).
 *
 *   2. Resolved constraints — set by passes 1–5: intensity ceiling,
 *      md_context, session_minutes_max, slots[], progression multiplier.
 *
 *   3. Result — set by passes 6+: blocks[], total_load, warnings[].
 */
class SessionPlanContext {

    // 1. Input
    public int $team_id = 0;
    public int $season_id = 0;
    public string $age_group = 'U10';
    public string $session_date = '';
    public ?string $tactical_theme = null;
    public ?string $start_time = null;

    /** @var list<int> Roster the session is being planned for (drives PHV lookup). */
    public array $roster_player_ids = [];

    public int $generated_by = 0;

    public ?int $requested_duration_minutes = null;

    // 2. Resolved constraints
    public int $intensity_band_max = 10;
    public int $session_minutes_max = 90;
    public int $weekly_load_envelope = 1000;
    public bool $md_logic_enabled = false;
    public int $min_recovery_hours_between_high = 48;
    public int $growth_spurt_load_reduction_pct = 0;
    public float $match_load_multiplier_per_minute = 7.0;

    public string $md_context = 'NONE';

    /** @var list<array<string,mixed>> Composed slot list — set by SessionCompositionRule. */
    public array $slots = [];

    public float $progression_multiplier = 1.0;

    /**
     * Where this training sits in the team's cycle (#3359, epic #3354).
     * Both stay null when the team has no cycle configured — that is a
     * normal state, not an error, and the activity stamper writes nothing
     * rather than inventing a week.
     */
    public ?int $cycle_week = null;

    /** `active` | `neutral`, or null when there is no cycle. */
    public ?string $cycle_state = null;

    // 3. Result
    /** @var list<array<string,mixed>> Filled blocks — set by ExerciseSelectionPass. */
    public array $blocks = [];

    public int $total_load = 0;

    /** @var list<array{code:string, severity:string, details:array<string,mixed>}> */
    public array $warnings = [];

    /**
     * Append a structured warning. Severity is `info`, `caution`, or
     * `block`. `block` warnings indicate the pipeline produced a
     * session that violates a hard rule; the engine surfaces those
     * via the validate()/compose() result envelope.
     *
     * @param array<string,mixed> $details
     */
    public function addWarning( string $code, string $severity, array $details = [] ): void {
        $this->warnings[] = [
            'code'     => $code,
            'severity' => in_array( $severity, [ 'info', 'caution', 'block' ], true ) ? $severity : 'info',
            'details'  => $details,
        ];
    }
}
