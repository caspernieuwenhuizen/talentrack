<?php
namespace TT\Infrastructure\Archive;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * CascadeRegistry (#1783) — declarative hard-delete plans for entities
 * that have an archive lifecycle but no bespoke cascade class.
 *
 * `player` and `person` keep their hand-written cascade services
 * (PlayerDeletionCascade / PersonDeletionCascade); they are NOT in this
 * registry. The entities here are driven generically by
 * GenericCascadeDeleter against the declaration below.
 *
 * Plan shape per entity:
 *   'table'        => the entity's own table (without prefix).
 *   'ref_columns'  => column names on OTHER tables that reference this
 *                     entity's id. The dependency scan finds every tt_*
 *                     table carrying one of these columns. Any referencing
 *                     table NOT listed under cascade/set_null BLOCKS the
 *                     delete (fail-closed).
 *   'cascade'      => [[bare_table, column], ...] owned children deleted
 *                     when the parent is deleted.
 *   'cascade_poly' => [[bare_table, type_col, id_col, type_value], ...]
 *                     polymorphic owned children (e.g. goal_links rows
 *                     whose link_type='evaluation'); not discoverable by
 *                     the ref_columns scan, so declared explicitly.
 *   'cascade_poly_via' => [[bare_table, type_col, id_col, type_value,
 *                     owner_table, owner_pk, owner_fk,
 *                     parent_table, parent_pk, parent_ref], ...]
 *                     polymorphic children whose id points at an owned
 *                     GRANDchild rather than at this entity — a journey
 *                     event keyed on an observation's id, say. Reached by
 *                     joining event → owner → parent → this entity, and
 *                     removed before the owner is, since the join needs it
 *                     (#2723). `cascade_poly` cannot express this: it can
 *                     only match the entity's own id.
 *   'threads'      => thread_type value whose tt_thread_messages /
 *                     tt_thread_reads rows are owned by this entity, or null.
 *   'set_null'     => [[bare_table, column], ...] references cleared (not
 *                     deleted) — facts that outlive the record. The column
 *                     MUST be nullable.
 *   'set_zero'     => [[bare_table, column], ...] references reset to 0
 *                     (not NULL, not deleted) — orphan columns that are
 *                     `NOT NULL DEFAULT 0` and so can't take NULL. The
 *                     referencing row outlives the record, re-homed to the
 *                     0 sentinel (unassigned / club-level). Use this instead
 *                     of set_null for non-nullable FK-style columns whose
 *                     row must be preserved (e.g. tt_players.team_id).
 *   'children'     => [[child, child_fk, parent, parent_pk, parent_ref], ...]
 *                     parent-keyed grandchildren that hang off a cascaded
 *                     child (no direct ref column of their own); deleted
 *                     ahead of that child.
 *   'block_only'   => true to declare NO cascades — the entity BLOCKS on
 *                     any dependent (fail-closed). Used for templates /
 *                     vocabularies that must never cascade-delete the
 *                     records using them (e.g. trial_track, measurement
 *                     definition). NOT used for team/activity since #2027
 *                     completed their full orphan-preserving plans.
 */
final class CascadeRegistry {

    private const PLANS = [

        // Evaluation — owns its category ratings + any evidence links
        // pointing at it. A normal evaluation deletes cleanly; anything
        // else referencing it blocks.
        'evaluation' => [
            'table'        => 'tt_evaluations',
            'ref_columns'  => [ 'evaluation_id' ],
            'cascade'      => [ [ 'tt_eval_ratings', 'evaluation_id' ] ],
            'cascade_poly' => [ [ 'tt_goal_links', 'link_type', 'link_id', 'evaluation' ] ],
            'threads'      => null,
            'set_null'     => [],
            'block_only'   => false,
        ],

        // Goal — owns its links and its conversation thread. A workflow
        // task that spawned the goal is a fact: keep it, clear the link.
        'goal' => [
            'table'        => 'tt_goals',
            'ref_columns'  => [ 'goal_id', 'spawned_goal_id' ],
            'cascade'      => [ [ 'tt_goal_links', 'goal_id' ] ],
            'cascade_poly' => [],
            'threads'      => 'goal',
            'set_null'     => [ [ 'tt_workflow_tasks', 'spawned_goal_id' ] ],
            'block_only'   => false,
        ],

        // Tournament (#1784) — owns its matches, squad and per-match
        // assignments. Assignments hang off matches (no tournament_id of
        // their own), so they're a parent-keyed child removed first. A
        // linked activity outlives the tournament with its link cleared.
        'tournament' => [
            'table'        => 'tt_tournaments',
            'ref_columns'  => [ 'tournament_id' ],
            'children'     => [ [ 'tt_tournament_assignments', 'match_id', 'tt_tournament_matches', 'id', 'tournament_id' ] ],
            'cascade'      => [ [ 'tt_tournament_matches', 'tournament_id' ], [ 'tt_tournament_squad', 'tournament_id' ] ],
            'cascade_poly' => [],
            'threads'      => null,
            'set_null'     => [ [ 'tt_activities', 'tournament_id' ] ],
            'block_only'   => false,
        ],

        // Trial case (#1784) — owns its staff assignments, staff inputs
        // and extension audit trail. A workflow task or prospect that
        // points at the case is a fact: keep it, clear the link.
        'trial_case' => [
            'table'        => 'tt_trial_cases',
            'ref_columns'  => [ 'case_id', 'trial_case_id', 'promoted_to_trial_case_id' ],
            'cascade'      => [
                [ 'tt_trial_case_staff', 'case_id' ],
                [ 'tt_trial_case_staff_inputs', 'case_id' ],
                [ 'tt_trial_extensions', 'case_id' ],
            ],
            'cascade_poly' => [],
            'threads'      => null,
            'set_null'     => [
                [ 'tt_workflow_tasks', 'trial_case_id' ],
                [ 'tt_prospects', 'promoted_to_trial_case_id' ],
            ],
            'block_only'   => false,
        ],

        // Holiday (#1784) — standalone metadata; nothing references it,
        // so a permanent delete just removes the row.
        'holiday' => [
            'table'        => 'tt_holidays',
            'ref_columns'  => [],
            'cascade'      => [],
            'cascade_poly' => [],
            'threads'      => null,
            'set_null'     => [],
            'block_only'   => false,
        ],

        // Test training (#1784) — a workflow task that invited a player to
        // the session is a fact: keep it, clear the link.
        'test_training' => [
            'table'        => 'tt_test_trainings',
            'ref_columns'  => [ 'test_training_id' ],
            'cascade'      => [],
            'cascade_poly' => [],
            'threads'      => null,
            'set_null'     => [ [ 'tt_workflow_tasks', 'test_training_id' ] ],
            'block_only'   => false,
        ],

        // Trial track (#1784) — a template referenced by trial cases. A
        // track must NOT cascade-delete the cases that use it, so it blocks
        // while any trial case still references it (fail-closed).
        'trial_track' => [
            'table'        => 'tt_trial_tracks',
            'ref_columns'  => [ 'track_id' ],
            'cascade'      => [],
            'cascade_poly' => [],
            'threads'      => null,
            'set_null'     => [],
            'block_only'   => true,
        ],

        // VCT exercise (#1784) — owns its coaching points; a session block
        // that referenced it keeps existing with the exercise link cleared.
        // `exercise_id` is ambiguous (it also keys tt_exercises' children),
        // so the ref_columns are TABLE-QUALIFIED to the VCT tables only.
        'vct_exercise' => [
            'table'        => 'tt_vct_exercises',
            'ref_columns'  => [ [ 'tt_vct_coaching_points', 'exercise_id' ], [ 'tt_vct_session_blocks', 'exercise_id' ] ],
            'cascade'      => [ [ 'tt_vct_coaching_points', 'exercise_id' ] ],
            'cascade_poly' => [],
            'threads'      => null,
            'set_null'     => [ [ 'tt_vct_session_blocks', 'exercise_id' ] ],
            'block_only'   => false,
        ],

        // Custom widget (#1784) — standalone dashboard config; nothing
        // references it, so a permanent delete just removes the row.
        'custom_widget' => [
            'table'        => 'tt_custom_widgets',
            'ref_columns'  => [],
            'cascade'      => [],
            'cascade_poly' => [],
            'threads'      => null,
            'set_null'     => [],
            'block_only'   => false,
        ],

        // Injury (#1784) — a minor's medical record. Its journey-timeline
        // events are owned (polymorphic source_entity_type='injury') and
        // removed with it so a right-to-erasure delete actually erases.
        'injury' => [
            'table'        => 'tt_player_injuries',
            'ref_columns'  => [],
            'cascade'      => [],
            'cascade_poly' => [ [ 'tt_player_events', 'source_entity_type', 'source_entity_id', 'injury' ] ],
            'threads'      => null,
            'set_null'     => [],
            'block_only'   => false,
        ],

        // Scheduled report (#1808) — standalone recurring-export config;
        // nothing references it, so a permanent delete just removes the row.
        // (#1784 migration 0172 added archived_at/archived_by + backfilled
        // from the legacy status='archived' enum.)
        'scheduled_report' => [
            'table'        => 'tt_scheduled_reports',
            'ref_columns'  => [],
            'cascade'      => [],
            'cascade_poly' => [],
            'threads'      => null,
            'set_null'     => [],
            'block_only'   => false,
        ],

        // Team (#2027) — player-centric orphan-preserving purge. Deleting a
        // team must never destroy player development data: players, their
        // team-history rows, the team's activities (+ their attendance /
        // evaluations), tournaments and team-scoped measurement sessions all
        // SURVIVE as unassigned (team_id → 0). Pure team-owned config
        // (formations, playing styles, chemistry, blueprints, staff
        // assignments, per-team exercise overrides, the whole VCT
        // periodization stack) is cascaded. Nullable transient links (an
        // open invitation's target team, a workflow task's team, a staged
        // dev idea's team) are cleared.
        //
        // `target_team_id` is a REAL nullable column on tt_invitations. The
        // legacy `player_team_id` / `activity_team_id` aliases from migration
        // 0145 are query-only (not physical columns) and so are absent here.
        // The exercise↔team link lives on tt_exercise_team_overrides (a
        // per-team opt-in/out table that IS cascaded) — tt_exercises itself
        // has no team_id column.
        'team' => [
            'table'        => 'tt_teams',
            'ref_columns'  => [ 'team_id', 'target_team_id' ],
            'cascade'      => [
                [ 'tt_team_formations', 'team_id' ],
                [ 'tt_team_playing_styles', 'team_id' ],
                [ 'tt_team_chemistry_pairings', 'team_id' ],
                [ 'tt_team_chemistry_snapshots', 'team_id' ],
                [ 'tt_team_blueprints', 'team_id' ],
                [ 'tt_team_people', 'team_id' ],
                [ 'tt_exercise_team_overrides', 'team_id' ],
                [ 'tt_vct_team_schedules', 'team_id' ],
                [ 'tt_vct_macro_blocks', 'team_id' ],
                [ 'tt_vct_sessions', 'team_id' ],
                [ 'tt_vct_microcycles', 'team_id' ],
                [ 'tt_vct_team_cycles', 'team_id' ],
                [ 'tt_vct_cycle_weeks', 'team_id' ],
            ],
            // #2571 — the team's scope rows. Polymorphic: `scope_id` is a
            // team id only when `scope_type = 'team'`, so it can't go in
            // `cascade` (that would delete player-scoped rows with a
            // colliding id). Left behind, these outlive the team and keep
            // satisfying `MatrixGate::userHasAnyScope()`, which counts rows
            // without joining `tt_teams`.
            'cascade_poly' => [ [ 'tt_user_role_scopes', 'scope_type', 'scope_id', 'team' ] ],
            'threads'      => null,
            'set_null'     => [
                [ 'tt_invitations', 'target_team_id' ],
                [ 'tt_workflow_tasks', 'team_id' ],
                [ 'tt_dev_ideas', 'team_id' ],
                // A training plan is reusable content, not execution data —
                // that is the whole premise of it being a standalone record
                // (#2493 D3). Deleting a team at season rollover must not
                // destroy the plans its coach wrote, so the link clears and
                // `team_id IS NULL` reads as club-wide. The run's team_id is
                // a denormalised convenience; the authoritative team lives on
                // the activity, which is set_zero'd just above.
                [ 'tt_training_plans', 'team_id' ],
                [ 'tt_training_plan_runs', 'team_id' ],
            ],
            'set_zero'     => [
                [ 'tt_players', 'team_id' ],
                [ 'tt_player_team_history', 'team_id' ],
                [ 'tt_activities', 'team_id' ],
                [ 'tt_tournaments', 'team_id' ],
                [ 'tt_measurement_sessions', 'team_id' ],
            ],
            'block_only'   => false,
        ],

        // Activity (#2027) — execution data that only exists inside the
        // activity (attendance, planned exercises, principles, the match
        // prep + match execution trees) is cascaded; activity-sourced
        // journey events are cascaded polymorphically. Records that outlive
        // the activity keep their row with the link cleared: evaluations
        // (the assessment is a fact about the player), tournament-match
        // bindings, VCT session bindings, and behaviour ratings.
        //
        // The match_prep and match_execution children hang off their parent
        // by `match_prep_id` / `execution_id` (no activity_id of their own),
        // so they're parent-keyed `children` removed ahead of the parent.
        'activity' => [
            'table'        => 'tt_activities',
            'ref_columns'  => [ 'activity_id', 'related_activity_id' ],
            'children'     => [
                [ 'tt_match_execution_goal_events', 'execution_id', 'tt_match_execution', 'id', 'activity_id' ],
                [ 'tt_match_execution_substitutions', 'execution_id', 'tt_match_execution', 'id', 'activity_id' ],
                [ 'tt_match_prep_availability', 'match_prep_id', 'tt_match_prep', 'id', 'activity_id' ],
                [ 'tt_match_prep_lineup', 'match_prep_id', 'tt_match_prep', 'id', 'activity_id' ],
                [ 'tt_match_prep_player_goals', 'match_prep_id', 'tt_match_prep', 'id', 'activity_id' ],
                [ 'tt_match_prep_roles', 'match_prep_id', 'tt_match_prep', 'id', 'activity_id' ],
                [ 'tt_training_plan_run_blocks', 'run_id', 'tt_training_plan_runs', 'id', 'activity_id' ],
                // #2723 — an observation is about a player at one training.
                // It hangs off the run, so deleting the activity left it
                // behind: unreachable, and still rendering on the player's
                // timeline via its journey event.
                [ 'tt_training_observations', 'run_id', 'tt_training_plan_runs', 'id', 'activity_id' ],
                // #2704 — the analysis's sections and player items hang
                // off the analysis by `analysis_id`, so they are removed
                // ahead of it exactly like the match-prep children above.
                [ 'tt_match_analysis_sections', 'analysis_id', 'tt_match_analyses', 'id', 'activity_id' ],
                [ 'tt_match_analysis_players', 'analysis_id', 'tt_match_analyses', 'id', 'activity_id' ],
                // #3091 — the notes hang off the analysis by `analysis_id`
                // whichever scope they are in, so one entry covers both the
                // section bullets and the player notes.
                [ 'tt_match_analysis_notes', 'analysis_id', 'tt_match_analyses', 'id', 'activity_id' ],
            ],
            'cascade'      => [
                [ 'tt_attendance', 'activity_id' ],
                [ 'tt_activity_exercises', 'activity_id' ],
                [ 'tt_activity_principles', 'activity_id' ],
                [ 'tt_match_prep', 'activity_id' ],
                [ 'tt_match_execution', 'activity_id' ],
                // #3094 — goal events hang off the activity directly now,
                // and a manually recorded goal has no execution at all. The
                // children entry above only reaches the ones a live match
                // sheet wrote; without this, deleting a match would leave
                // every typed-in goal behind as an orphan.
                [ 'tt_match_execution_goal_events', 'activity_id' ],
                [ 'tt_match_analyses', 'activity_id' ],
                // A run means "this plan was executed at this activity" and
                // is one-per-activity, so it cannot outlive it. Attendance
                // cascades here too, which is what the training-exposure
                // figures are computed from — leaving the run behind would
                // keep a session on the books with nobody recorded at it.
                // The plan itself is untouched; only this execution goes.
                [ 'tt_training_plan_runs', 'activity_id' ],
            ],
            'cascade_poly' => [ [ 'tt_player_events', 'source_entity_type', 'source_entity_id', 'activity' ] ],
            // #2723 — journey events whose `source_entity_id` is an
            // observation's id, not the activity's. `cascade_poly` can only
            // match on the activity's own id, so both of these outlived the
            // rows they describe: a coach's words about a named child, still
            // on that child's timeline, pointing at a match or training that
            // no longer exists.
            //
            // Shape: [ table, type_col, id_col, type_val,
            //          owner_table, owner_pk, owner_fk,
            //          parent_table, parent_pk, parent_ref ]
            'cascade_poly_via' => [
                [
                    'tt_player_events', 'source_entity_type', 'source_entity_id', 'match_analysis_player',
                    'tt_match_analysis_players', 'id', 'analysis_id',
                    'tt_match_analyses', 'id', 'activity_id',
                ],
                [
                    'tt_player_events', 'source_entity_type', 'source_entity_id', 'training_observation',
                    'tt_training_observations', 'id', 'run_id',
                    'tt_training_plan_runs', 'id', 'activity_id',
                ],
            ],
            'threads'      => null,
            'set_null'     => [
                [ 'tt_evaluations', 'activity_id' ],
                [ 'tt_tournament_matches', 'activity_id' ],
                [ 'tt_vct_sessions', 'activity_id' ],
                [ 'tt_player_behaviour_ratings', 'related_activity_id' ],
            ],
            'block_only'   => false,
        ],

        // Measurement definition (#1856) — the schema a test hangs off.
        // Blocks while any session, result or target still references it;
        // the operator archives/deletes those first. Fail-closed.
        'measurement_definition' => [
            'table'        => 'tt_measurement_definitions',
            'ref_columns'  => [ 'definition_id' ],
            // Levels are owned wholly by their status definition — they
            // cascade with it (nothing else references a level).
            'cascade'      => [ [ 'tt_measurement_levels', 'definition_id' ] ],
            'cascade_poly' => [],
            'threads'      => null,
            'set_null'     => [],
            'block_only'   => true,
        ],

        // Measurement level (#2138) — a coloured status level; a leaf that
        // owns nothing and deletes cleanly.
        'measurement_level' => [
            'table'        => 'tt_measurement_levels',
            'ref_columns'  => [ 'level_id' ],
            'cascade'      => [],
            'cascade_poly' => [],
            'threads'      => null,
            'set_null'     => [],
            'block_only'   => false,
        ],

        // Measurement session (#1856) — owns the results recorded against
        // it; deleting the session removes those values.
        'measurement_session' => [
            'table'        => 'tt_measurement_sessions',
            'ref_columns'  => [ 'measurement_session_id' ],
            'cascade'      => [ [ 'tt_measurement_results', 'measurement_session_id' ] ],
            'cascade_poly' => [],
            'threads'      => null,
            'set_null'     => [],
            'block_only'   => false,
        ],

        // Measurement target (#1856) — a per-age band; nothing references
        // it, deletes cleanly.
        'measurement_target' => [
            'table'        => 'tt_measurement_targets',
            'ref_columns'  => [ 'target_id' ],
            'cascade'      => [],
            'cascade_poly' => [],
            'threads'      => null,
            'set_null'     => [],
            'block_only'   => false,
        ],

        // Measurement result (#1856) — a leaf value, owns nothing.
        'measurement_result' => [
            'table'        => 'tt_measurement_results',
            'ref_columns'  => [ 'result_id' ],
            'cascade'      => [],
            'cascade_poly' => [],
            'threads'      => null,
            'set_null'     => [],
            'block_only'   => false,
        ],

        // Player attribute definition (#1912) — a chemistry attribute in
        // the catalogue. Deleting one removes every player's recorded value
        // for it.
        'player_attribute_def' => [
            'table'        => 'tt_player_attribute_defs',
            'ref_columns'  => [ 'attribute_def_id' ],
            'cascade'      => [ [ 'tt_player_attribute_values', 'attribute_def_id' ] ],
            'cascade_poly' => [],
            'threads'      => null,
            'set_null'     => [],
            'block_only'   => false,
        ],
    ];

    /** Whether this entity is driven by the generic deleter. */
    public static function has( string $entity ): bool {
        return isset( self::PLANS[ $entity ] );
    }

    /** @return array<string,mixed>|null */
    public static function plan( string $entity ): ?array {
        return self::PLANS[ $entity ] ?? null;
    }
}
