<?php
namespace TT\Modules\DemoData;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Modules\DemoData\Generators\ActivityContentGenerator;
use TT\Modules\DemoData\Generators\ActivityGenerator;
use TT\Modules\DemoData\Generators\CommsOpsGenerator;
use TT\Modules\DemoData\Generators\EvaluationGenerator;
use TT\Modules\DemoData\Generators\GoalGenerator;
use TT\Modules\DemoData\Generators\GuardianGenerator;
use TT\Modules\DemoData\Generators\InjuryGenerator;
use TT\Modules\DemoData\Generators\KnowledgeGenerator;
use TT\Modules\DemoData\Generators\MatchAnalysisGenerator;
use TT\Modules\DemoData\Generators\MatchDayGenerator;
use TT\Modules\DemoData\Generators\MeasurementGenerator;
use TT\Modules\DemoData\Generators\MediaGenerator;
use TT\Modules\DemoData\Generators\PdpGenerator;
use TT\Modules\DemoData\Generators\PeopleGenerator;
use TT\Modules\DemoData\Generators\PipelineGenerator;
use TT\Modules\DemoData\Generators\PlayerGenerator;
use TT\Modules\DemoData\Generators\PlayerProfileGenerator;
use TT\Modules\DemoData\Generators\PlayerReportGenerator;
use TT\Modules\DemoData\Generators\PlayerStatusGenerator;
use TT\Modules\DemoData\Generators\StaffDevelopmentGenerator;
use TT\Modules\DemoData\Generators\TeamDevelopmentGenerator;
use TT\Modules\DemoData\Generators\TeamGenerator;
use TT\Modules\DemoData\Generators\TestTrainingGenerator;
use TT\Modules\DemoData\Generators\TrainingObservationGenerator;
use TT\Modules\DemoData\Generators\TrainingPlanGenerator;
use TT\Modules\DemoData\Generators\TrainingRunGenerator;
use TT\Modules\DemoData\Generators\TournamentGenerator;

/**
 * DemoCoverage — the single source of truth for what demo generation covers.
 *
 * Every `tt_*` table the schema creates has exactly one entry here, in one
 * of three states:
 *
 *   - **generated** — a `written_by` producer fills it, and `entity_type` +
 *     `category` say how the cleaner reaches the rows again.
 *   - **planned** — in scope, generator not written yet; the value is the
 *     issue that will write it.
 *   - **exempt** — holds no demo-worthy records, with the reason stated.
 *
 * `tools/check-demo-coverage.php` fails when a table is in none of them, so
 * a new migration forces the decision instead of silently widening the gap.
 *
 * The cleaner derives its delete order from `depends_on` by topological
 * sort. A generator that writes rows the cleaner can't reach leaves
 * permanent orphans on the operator's install, which is why wipe reach is
 * declared here next to the generator rather than in a parallel constant.
 */
class DemoCoverage {

    /** A table whose rows a producer writes during a demo run. */
    public const STATE_GENERATED = 'generated';
    /** In scope for the coverage epic; generator not written yet. */
    public const STATE_PLANNED = 'planned';
    /** Deliberately never generated; `exempt` carries the reason. */
    public const STATE_EXEMPT = 'exempt';

    /**
     * Producer markers for rows no dedicated generator class writes.
     *
     * `hook` — written by the runtime module's own subscriber, because the
     * demo generator fires the same action the real feature fires (journey
     * events work this way, so the timeline shape can't drift from
     * production). Those rows still need tagging to be wipeable; see
     * `DemoGenerator::tagUntaggedJourneyEvents()`.
     */
    public const WRITTEN_BY_HOOK  = 'hook';
    public const WRITTEN_BY_EXCEL = 'excel';

    /**
     * table => entry. See the class docblock for the three states.
     *
     * @var array<string, array<string,mixed>>
     */
    public const MANIFEST = [

        // ===== Core domain =====

        'tt_people' => [
            'entity_type' => 'person',
            'category'    => 'people',
            'written_by'  => PeopleGenerator::class,
            'depends_on'  => [],
        ],
        'tt_teams' => [
            'entity_type' => 'team',
            'category'    => 'teams',
            'written_by'  => TeamGenerator::class,
            'depends_on'  => [],
        ],
        'tt_team_people' => [
            'entity_type' => 'team_person',
            'category'    => 'teams',
            'written_by'  => TeamGenerator::class,
            'depends_on'  => [ 'team', 'person' ],
        ],
        'tt_players' => [
            'entity_type' => 'player',
            'category'    => 'players',
            'written_by'  => PlayerGenerator::class,
            'depends_on'  => [ 'team' ],
        ],

        // ===== Activities + attendance =====

        'tt_activities' => [
            'entity_type' => 'activity',
            'category'    => 'activities',
            'written_by'  => ActivityGenerator::class,
            'depends_on'  => [ 'team' ],
        ],
        'tt_attendance' => [
            'entity_type' => 'attendance',
            'category'    => 'activities',
            'written_by'  => ActivityGenerator::class,
            'depends_on'  => [ 'activity', 'player' ],
        ],

        // ===== Evaluations =====

        'tt_evaluations' => [
            'entity_type' => 'evaluation',
            'category'    => 'evaluations',
            'written_by'  => EvaluationGenerator::class,
            'depends_on'  => [ 'player', 'team' ],
        ],
        'tt_eval_ratings' => [
            'entity_type' => 'eval_rating',
            'category'    => 'evaluations',
            'written_by'  => EvaluationGenerator::class,
            'depends_on'  => [ 'evaluation' ],
        ],

        // ===== Goals =====

        'tt_goals' => [
            'entity_type' => 'goal',
            'category'    => 'goals',
            'written_by'  => GoalGenerator::class,
            'depends_on'  => [ 'player' ],
        ],

        // ===== Journey =====

        // Written by JourneyEventSubscriber, not by a generator: the player
        // and evaluation generators fire `tt_player_created` /
        // `tt_evaluation_saved` so demo timelines are byte-identical in
        // shape to production ones. Tagged in a post-pass.
        'tt_player_events' => [
            'entity_type' => 'player_event',
            'category'    => 'journey',
            'written_by'  => self::WRITTEN_BY_HOOK,
            'depends_on'  => [ 'player' ],
        ],

        // ===== Trials =====

        // Written by PipelineGenerator procedurally, and by the Excel
        // importer when a workbook carries a trial_cases sheet.
        'tt_trial_cases' => [
            'entity_type' => 'trial_case',
            'category'    => 'trials',
            'written_by'  => PipelineGenerator::class,
            'depends_on'  => [ 'player' ],
        ],
        'tt_trial_case_staff' => [
            'entity_type' => 'trial_case_staff',
            'category'    => 'trials',
            'written_by'  => PipelineGenerator::class,
            'depends_on'  => [ 'trial_case' ],
        ],
        'tt_trial_case_staff_inputs' => [
            'entity_type' => 'trial_case_staff_input',
            'category'    => 'trials',
            'written_by'  => PipelineGenerator::class,
            'depends_on'  => [ 'trial_case' ],
        ],
        'tt_trial_extensions' => [
            'entity_type' => 'trial_extension',
            'category'    => 'trials',
            'written_by'  => PipelineGenerator::class,
            'depends_on'  => [ 'trial_case' ],
        ],

        // ===== Player spine =====

        // No surrogate id — PK is (player_id, parent_user_id). See
        // TABLE_QUIRKS for how the wipe reaches it.
        'tt_player_parents' => [
            'entity_type' => 'player_parent',
            'category'    => 'guardians',
            'written_by'  => GuardianGenerator::class,
            'depends_on'  => [ 'player' ],
        ],
        'tt_player_parent_visibility' => [
            'entity_type' => 'player_parent_visibility',
            'category'    => 'guardians',
            'written_by'  => GuardianGenerator::class,
            'depends_on'  => [ 'player' ],
        ],
        'tt_player_injuries' => [
            'entity_type' => 'player_injury',
            'category'    => 'injuries',
            'written_by'  => InjuryGenerator::class,
            'depends_on'  => [ 'player' ],
        ],
        // #3242 — two of the traffic light's four inputs. Both were empty on
        // every generated player, so the status the demo showed was not the
        // status the product produces for a real club.
        'tt_player_potential' => [
            'entity_type' => 'player_potential',
            'category'    => 'player_status',
            'written_by'  => PlayerStatusGenerator::class,
            'depends_on'  => [ 'player' ],
        ],
        'tt_player_behaviour_ratings' => [
            'entity_type' => 'player_behaviour_rating',
            'category'    => 'player_status',
            'written_by'  => PlayerStatusGenerator::class,
            'depends_on'  => [ 'player' ],
        ],
        'tt_player_team_history' => [
            'entity_type' => 'player_team_history',
            'category'    => 'player_profile',
            'written_by'  => PlayerProfileGenerator::class,
            'depends_on'  => [ 'player', 'team' ],
        ],
        'tt_player_attribute_values' => [
            'entity_type' => 'player_attribute_value',
            'category'    => 'player_profile',
            'written_by'  => PlayerProfileGenerator::class,
            'depends_on'  => [ 'player' ],
        ],
        'tt_custom_fields' => [
            'entity_type' => 'custom_field',
            'category'    => 'player_profile',
            'written_by'  => PlayerProfileGenerator::class,
            'depends_on'  => [],
        ],
        'tt_custom_values' => [
            'entity_type' => 'custom_value',
            'category'    => 'player_profile',
            'written_by'  => PlayerProfileGenerator::class,
            'depends_on'  => [ 'custom_field', 'player' ],
        ],
        'tt_goal_links' => [
            'entity_type' => 'goal_link',
            'category'    => 'player_profile',
            'written_by'  => PlayerProfileGenerator::class,
            'depends_on'  => [ 'goal', 'evaluation' ],
        ],
        'tt_player_reports' => [
            'entity_type' => 'player_report',
            'category'    => 'reports',
            'written_by'  => PlayerReportGenerator::class,
            'depends_on'  => [ 'player' ],
        ],

        // ===== Measurements =====

        'tt_measurement_definitions' => [
            'entity_type' => 'measurement_definition',
            'category'    => 'measurements',
            'written_by'  => MeasurementGenerator::class,
            'depends_on'  => [],
        ],
        'tt_measurement_targets' => [
            'entity_type' => 'measurement_target',
            'category'    => 'measurements',
            'written_by'  => MeasurementGenerator::class,
            'depends_on'  => [ 'measurement_definition' ],
        ],
        'tt_measurement_sessions' => [
            'entity_type' => 'measurement_session',
            'category'    => 'measurements',
            'written_by'  => MeasurementGenerator::class,
            'depends_on'  => [ 'measurement_definition', 'team' ],
        ],
        'tt_measurement_results' => [
            'entity_type' => 'measurement_result',
            'category'    => 'measurements',
            'written_by'  => MeasurementGenerator::class,
            'depends_on'  => [ 'measurement_session', 'measurement_definition', 'player' ],
        ],

        // ===== Seasons + PDP =====

        'tt_seasons' => [
            'entity_type' => 'season',
            'category'    => 'pdp',
            'written_by'  => PdpGenerator::class,
            'depends_on'  => [],
        ],
        'tt_pdp_files' => [
            'entity_type' => 'pdp_file',
            'category'    => 'pdp',
            'written_by'  => PdpGenerator::class,
            'depends_on'  => [ 'player', 'season' ],
        ],
        'tt_pdp_conversations' => [
            'entity_type' => 'pdp_conversation',
            'category'    => 'pdp',
            'written_by'  => PdpGenerator::class,
            'depends_on'  => [ 'pdp_file' ],
        ],
        'tt_pdp_verdicts' => [
            'entity_type' => 'pdp_verdict',
            'category'    => 'pdp',
            'written_by'  => PdpGenerator::class,
            'depends_on'  => [ 'pdp_file' ],
        ],
        'tt_pdp_calendar_links' => [
            'entity_type' => 'pdp_calendar_link',
            'category'    => 'pdp',
            'written_by'  => PdpGenerator::class,
            'depends_on'  => [ 'pdp_conversation' ],
        ],
        // #3305 (epic #3301) — a coach's preparation for one conversation.
        // The questions themselves are configuration (see the exempt list);
        // the answers are a coach's own words about a demo player, so they
        // are demo content and the cleaner has to reach them.
        'tt_pdp_prep_answers' => [
            'entity_type' => 'pdp_prep_answer',
            'category'    => 'pdp',
            'written_by'  => PdpGenerator::class,
            'depends_on'  => [ 'pdp_conversation' ],
        ],

        // ===== Activity content =====

        'tt_exercise_team_overrides' => [
            'entity_type' => 'exercise_team_override',
            'category'    => 'activity_content',
            'written_by'  => ActivityContentGenerator::class,
            'depends_on'  => [ 'team' ],
        ],
        'tt_activity_exercises' => [
            'entity_type' => 'activity_exercise',
            'category'    => 'activity_content',
            'written_by'  => ActivityContentGenerator::class,
            'depends_on'  => [ 'activity' ],
        ],
        'tt_activity_principles' => [
            'entity_type' => 'activity_principle',
            'category'    => 'activity_content',
            'written_by'  => ActivityContentGenerator::class,
            'depends_on'  => [ 'activity' ],
        ],
        'tt_holidays' => [
            'entity_type' => 'holiday',
            'category'    => 'activity_content',
            'written_by'  => ActivityContentGenerator::class,
            'depends_on'  => [],
        ],

        // ===== Match day =====

        'tt_match_prep' => [
            'entity_type' => 'match_prep',
            'category'    => 'match_day',
            'written_by'  => MatchDayGenerator::class,
            'depends_on'  => [ 'activity' ],
        ],
        'tt_match_prep_availability' => [
            'entity_type' => 'match_prep_availability',
            'category'    => 'match_day',
            'written_by'  => MatchDayGenerator::class,
            'depends_on'  => [ 'match_prep', 'player' ],
        ],
        'tt_match_prep_lineup' => [
            'entity_type' => 'match_prep_lineup',
            'category'    => 'match_day',
            'written_by'  => MatchDayGenerator::class,
            'depends_on'  => [ 'match_prep', 'player' ],
        ],
        'tt_match_prep_player_goals' => [
            'entity_type' => 'match_prep_player_goal',
            'category'    => 'match_day',
            'written_by'  => MatchDayGenerator::class,
            'depends_on'  => [ 'match_prep', 'player' ],
        ],
        'tt_match_prep_roles' => [
            'entity_type' => 'match_prep_role',
            'category'    => 'match_day',
            'written_by'  => MatchDayGenerator::class,
            'depends_on'  => [ 'match_prep', 'player' ],
        ],
        'tt_match_execution' => [
            'entity_type' => 'match_execution',
            'category'    => 'match_day',
            'written_by'  => MatchDayGenerator::class,
            'depends_on'  => [ 'activity', 'match_prep' ],
        ],
        // #2704 — the post-match review. Its own category rather than part
        // of `match_day`, because it runs after attendance and minutes
        // exist: the roster it marks up is "who actually played", which the
        // match-day generator has not finished writing when it runs.
        'tt_match_analyses' => [
            'entity_type' => 'match_analysis',
            'category'    => 'match_analyses',
            'written_by'  => MatchAnalysisGenerator::class,
            'depends_on'  => [ 'activity' ],
        ],
        'tt_match_analysis_sections' => [
            'entity_type' => 'match_analysis_section',
            'category'    => 'match_analyses',
            'written_by'  => MatchAnalysisGenerator::class,
            'depends_on'  => [ 'match_analysis' ],
        ],
        'tt_match_analysis_players' => [
            'entity_type' => 'match_analysis_player',
            'category'    => 'match_analyses',
            'written_by'  => MatchAnalysisGenerator::class,
            'depends_on'  => [ 'match_analysis', 'player' ],
        ],
        'tt_match_analysis_notes' => [
            'entity_type' => 'match_analysis_note',
            'category'    => 'match_analyses',
            'written_by'  => MatchAnalysisGenerator::class,
            'depends_on'  => [ 'match_analysis' ],
        ],
        'tt_match_execution_goal_events' => [
            'entity_type' => 'match_goal_event',
            'category'    => 'match_day',
            'written_by'  => MatchDayGenerator::class,
            'depends_on'  => [ 'match_execution' ],
        ],
        'tt_match_execution_substitutions' => [
            'entity_type' => 'match_substitution',
            'category'    => 'match_day',
            'written_by'  => MatchDayGenerator::class,
            'depends_on'  => [ 'match_execution' ],
        ],
        'tt_match_execution_tracked_events' => [
            'entity_type' => 'match_tracked_event',
            'category'    => 'match_day',
            'written_by'  => MatchDayGenerator::class,
            'depends_on'  => [ 'match_execution' ],
        ],

        // ===== Test trainings =====

        'tt_test_trainings' => [
            'entity_type' => 'test_training',
            'category'    => 'test_trainings',
            'written_by'  => TestTrainingGenerator::class,
            'depends_on'  => [],
        ],

        // ===== Team development =====

        'tt_team_formations' => [
            'entity_type' => 'team_formation',
            'category'    => 'team_development',
            'written_by'  => TeamDevelopmentGenerator::class,
            'depends_on'  => [ 'team' ],
        ],
        'tt_team_playing_styles' => [
            'entity_type' => 'team_playing_style',
            'category'    => 'team_development',
            'written_by'  => TeamDevelopmentGenerator::class,
            'depends_on'  => [ 'team' ],
        ],
        'tt_team_blueprints' => [
            'entity_type' => 'team_blueprint',
            'category'    => 'team_development',
            'written_by'  => TeamDevelopmentGenerator::class,
            'depends_on'  => [ 'team' ],
        ],
        'tt_team_blueprint_assignments' => [
            'entity_type' => 'team_blueprint_assignment',
            'category'    => 'team_development',
            'written_by'  => TeamDevelopmentGenerator::class,
            'depends_on'  => [ 'team_blueprint', 'player' ],
        ],
        'tt_team_chemistry_pairings' => [
            'entity_type' => 'team_chemistry_pairing',
            'category'    => 'team_development',
            'written_by'  => TeamDevelopmentGenerator::class,
            'depends_on'  => [ 'team', 'player' ],
        ],
        'tt_team_chemistry_snapshots' => [
            'entity_type' => 'team_chemistry_snapshot',
            'category'    => 'team_development',
            'written_by'  => TeamDevelopmentGenerator::class,
            'depends_on'  => [ 'team' ],
        ],

        // ===== Scouting pipeline =====

        'tt_scouting_plan_visits' => [
            'entity_type' => 'scouting_visit',
            'category'    => 'pipeline',
            'written_by'  => PipelineGenerator::class,
            'depends_on'  => [],
        ],
        'tt_prospects' => [
            'entity_type' => 'prospect',
            'category'    => 'pipeline',
            'written_by'  => PipelineGenerator::class,
            'depends_on'  => [ 'scouting_visit' ],
        ],

        // ===== Tournaments =====

        'tt_tournaments' => [
            'entity_type' => 'tournament',
            'category'    => 'tournaments',
            'written_by'  => TournamentGenerator::class,
            'depends_on'  => [ 'team' ],
        ],
        'tt_tournament_squad' => [
            'entity_type' => 'tournament_squad',
            'category'    => 'tournaments',
            'written_by'  => TournamentGenerator::class,
            'depends_on'  => [ 'tournament', 'player' ],
        ],
        'tt_tournament_matches' => [
            'entity_type' => 'tournament_match',
            'category'    => 'tournaments',
            'written_by'  => TournamentGenerator::class,
            'depends_on'  => [ 'tournament' ],
        ],
        'tt_tournament_assignments' => [
            'entity_type' => 'tournament_assignment',
            'category'    => 'tournaments',
            'written_by'  => TournamentGenerator::class,
            'depends_on'  => [ 'tournament_match', 'player' ],
        ],

        // ===== Staff development =====

        'tt_staff_certifications' => [
            'entity_type' => 'staff_certification',
            'category'    => 'staff_development',
            'written_by'  => StaffDevelopmentGenerator::class,
            'depends_on'  => [ 'person' ],
        ],
        'tt_staff_pdp' => [
            'entity_type' => 'staff_pdp',
            'category'    => 'staff_development',
            'written_by'  => StaffDevelopmentGenerator::class,
            'depends_on'  => [ 'person' ],
        ],
        'tt_staff_goals' => [
            'entity_type' => 'staff_goal',
            'category'    => 'staff_development',
            'written_by'  => StaffDevelopmentGenerator::class,
            'depends_on'  => [ 'person' ],
        ],
        'tt_staff_evaluations' => [
            'entity_type' => 'staff_evaluation',
            'category'    => 'staff_development',
            'written_by'  => StaffDevelopmentGenerator::class,
            'depends_on'  => [ 'person' ],
        ],
        'tt_staff_eval_ratings' => [
            'entity_type' => 'staff_eval_rating',
            'category'    => 'staff_development',
            'written_by'  => StaffDevelopmentGenerator::class,
            'depends_on'  => [ 'staff_evaluation' ],
        ],
        'tt_staff_mentorships' => [
            'entity_type' => 'staff_mentorship',
            'category'    => 'staff_development',
            'written_by'  => StaffDevelopmentGenerator::class,
            'depends_on'  => [ 'person' ],
        ],

        // ===== Threads + operator records =====

        'tt_thread_messages' => [
            'entity_type' => 'thread_message',
            'category'    => 'comms_ops',
            'written_by'  => CommsOpsGenerator::class,
            'depends_on'  => [],
        ],
        'tt_thread_reads' => [
            'entity_type' => 'thread_read',
            'category'    => 'comms_ops',
            'written_by'  => CommsOpsGenerator::class,
            'depends_on'  => [ 'thread_message' ],
        ],
        'tt_saved_filters' => [
            'entity_type' => 'saved_filter',
            'category'    => 'comms_ops',
            'written_by'  => CommsOpsGenerator::class,
            'depends_on'  => [],
        ],
        'tt_report_presets' => [
            'entity_type' => 'report_preset',
            'category'    => 'comms_ops',
            'written_by'  => CommsOpsGenerator::class,
            'depends_on'  => [],
        ],
        'tt_workflow_tasks' => [
            'entity_type' => 'workflow_task',
            'category'    => 'comms_ops',
            'written_by'  => CommsOpsGenerator::class,
            'depends_on'  => [ 'player' ],
        ],
        'tt_invitations' => [
            'entity_type' => 'invitation',
            'category'    => 'comms_ops',
            'written_by'  => CommsOpsGenerator::class,
            'depends_on'  => [ 'player' ],
        ],

        // ===== Exempt — plugin infrastructure =====

        'tt_migrations'  => [ 'exempt' => 'Migration ledger. Schema bookkeeping, not club content.' ],
        // #2638 — a real person's choice about what reaches them. A seeded
        // opt-out would silently suppress demo messages the operator expects
        // to see, and an opt-out is meaningless on an account nobody signs
        // into. Absence of a row is the default (opted in), so an empty
        // table is already the correct demo state.
        // #2632 — a real person's own settings. Same reasoning as the comms
        // opt-outs below: seeding one would silence demo alerts the operator
        // expects to see, and absence of a row already means "use the
        // shipped default", which is the correct demo state.
        'tt_alert_preferences' => [ 'exempt' => 'A real person\'s own alert settings. Absence of a row already means "use the default", so an empty table is the correct state.' ],
        'tt_comms_optouts' => [ 'exempt' => 'A real recipient\'s own choice about what reaches them. Seeding one would suppress demo messages the operator expects to see.' ],
        'tt_demo_tags'   => [ 'exempt' => 'The demo registry itself. Tagging the tags would recurse.' ],
        'tt_import_batches' => [ 'exempt' => 'The record of a REAL spreadsheet import (#2956). Deliberately outside the demo registry — anything the demo cleaner can reach, it can delete, and these rows account for a club\'s actual squad.' ],
        'tt_import_tags'    => [ 'exempt' => 'Which rows a real import created (#2956). Same reason as tt_import_batches: the demo cleaner must not be able to see, let alone wipe, a club\'s imported records.' ],
        'tt_share_views' => [ 'exempt' => 'One row per browser that opened a real share link (#3096). Seeding it would tell an operator that people had read a document nobody has opened, which is the one thing the count exists to answer honestly.' ],
        'tt_share_view_totals' => [ 'exempt' => 'What the 90-day purge folded away from tt_share_views (#3096). Same reason as the table it summarises: a fabricated total is a fabricated audience.' ],
        'tt_audit_log'   => [ 'exempt' => 'Audit trail of real operator actions. Fabricating entries would corrupt the record a real audit reads.' ],
        // #3242 — four tables this gate could not see until its migration
        // scan learned to read `$table = "{$p}tt_foo"` assignments. Each
        // gets a real decision rather than a blanket exemption.
        'tt_impersonation_log' => [ 'exempt' => 'Authentication audit: who signed in as whom, and when. Same reason as tt_audit_log — a fabricated entry corrupts the record a real investigation reads, and this one is about accounts rather than content.' ],
        'tt_comms_log'   => [ 'exempt' => 'One row per real send attempt, and what the operator-facing "did the parents actually get it?" query reads. Seeding it would answer that question with sends that never happened.' ],
        'tt_comms_inbox' => [ 'planned' => '#2600' ],
        // #3357 (epic #3354) — the per-team VCT cycle and its manual week
        // overrides. A demo academy should ship with a cycle configured, or
        // the cycle week never appears on a demo training and the feature
        // looks broken rather than unconfigured. The generator lands with
        // the configuration surface that gives a cycle somewhere to be
        // edited; seeding one before then would produce rows no screen can
        // show.
        'tt_vct_team_cycles' => [ 'planned' => '#3360' ],
        // The override table stays empty even once cycles are generated: an
        // override is a coach correcting the automation, and a demo run that
        // shipped pre-corrected weeks would misrepresent what the automation
        // does on its own.
        'tt_vct_cycle_weeks' => [ 'exempt' => 'A coach\'s manual correction of an automatically-resolved week. Absence of a row already means "resolve this week automatically", so an empty table is the correct state — and a seeded override would hide what the automation does unaided.' ],
        'tt_player_status_methodology' => [ 'exempt' => 'Per-club weighting of the traffic-light inputs. Operator configuration, like tt_module_state — a seeded row would silently change how every demo player\'s status is computed.' ],
        'tt_error_log'   => [ 'exempt' => 'Error log. Synthetic errors would send operators chasing bugs that never happened.' ],
        // #2631 — derived state, not content. Rows are written only by the
        // hourly sweep from conditions already true in other tables, so
        // seeding them would mean fabricating an alert about data that does
        // not say what the alert claims. Demo activities that meet a
        // condition produce real occurrences on the next sweep, which is the
        // honest demonstration. Wiping demo data leaves occurrences behind
        // only until that sweep resolves them.
        'tt_alert_occurrences' => [ 'exempt' => 'Derived alert state, recomputed hourly from other tables. Seeded rows would assert conditions the underlying data does not hold.' ],
        'tt_usage_events' => [ 'exempt' => 'Telemetry of real usage. Seeded rows would skew the adoption stats the academy reads.' ],
        'tt_module_state' => [ 'exempt' => 'Per-club module enable/disable state. Operator configuration.' ],
        'tt_feature_state' => [ 'exempt' => 'Per-feature under-development flags. Operator configuration.' ],
        'tt_wizard_drafts' => [ 'exempt' => 'In-flight wizard drafts, per user session. Nothing to demonstrate.' ],
        'tt_user_mfa'    => [ 'exempt' => 'MFA secrets for real accounts. Never fabricated.' ],
        'tt_push_subscriptions' => [ 'exempt' => 'Browser push endpoints tied to real devices.' ],
        'tt_custom_css_history' => [ 'exempt' => 'Operator CSS revision history.' ],
        'tt_custom_widgets' => [ 'exempt' => 'Operator-authored dashboard widgets.' ],
        'tt_dev_ideas'   => [ 'exempt' => 'Internal development tracker, not academy data.' ],
        'tt_dev_tracks'  => [ 'exempt' => 'Internal development tracker, not academy data.' ],

        // ===== Exempt — configuration + authorization =====

        'tt_config'  => [ 'exempt' => 'Per-club configuration singletons. The generate form writes the few it needs directly.' ],
        'tt_lookups' => [ 'exempt' => 'Admin-editable vocabularies, seeded by migrations. Generators consume lookup ids, never invent rows.' ],
        'tt_authorization_matrix'    => [ 'exempt' => 'Capability matrix, seeded by migrations. Demo runs must not alter who can do what.' ],
        'tt_authorization_changelog' => [ 'exempt' => 'Audit trail of capability changes.' ],
        'tt_roles'                     => [ 'exempt' => 'Role definitions, seeded by migrations.' ],
        'tt_role_permissions'          => [ 'exempt' => 'Role/permission mapping, seeded by migrations.' ],
        'tt_functional_roles'          => [ 'exempt' => 'Functional-role vocabulary, seeded by migrations.' ],
        'tt_functional_role_auth_roles' => [ 'exempt' => 'Functional-role to auth-role mapping, seeded by migrations.' ],
        'tt_user_role_scopes'          => [ 'exempt' => 'Per-user role scoping, written by the invite/assignment flows.' ],

        // ===== Exempt — i18n =====

        'tt_translations'            => [ 'exempt' => 'Translation store. Generators embed per-language content pools instead.' ],
        'tt_translations_cache'      => [ 'exempt' => 'Derived cache of tt_translations.' ],
        'tt_translations_usage'      => [ 'exempt' => 'Translation-usage telemetry.' ],
        'tt_translation_source_meta' => [ 'exempt' => 'Translation source bookkeeping.' ],

        // ===== Planned — Training module (#2493) =====
        //
        // A demo academy will want plausible training plans: the module's
        // whole point is that what was trained shows up on a player's file,
        // and a demo install with an empty training history tells that story
        // badly. Generating them before the surfaces exist would mean
        // inventing a shape the builder has not settled yet, so these wait
        // for the wave that gives them one.

        'tt_training_plans' => [
            'entity_type' => 'training_plan',
            'category'    => 'training_plans',
            'written_by'  => TrainingPlanGenerator::class,
            'depends_on'  => [ 'tt_teams', 'tt_exercises' ],
        ],
        'tt_training_plan_blocks' => [
            'entity_type' => 'training_plan_block',
            'category'    => 'training_plans',
            'written_by'  => TrainingPlanGenerator::class,
            'depends_on'  => [ 'tt_training_plans' ],
        ],
        'tt_training_plan_principles' => [
            'entity_type' => 'training_plan_principle',
            'category'    => 'training_plans',
            'written_by'  => TrainingPlanGenerator::class,
            'depends_on'  => [ 'tt_training_plan_blocks' ],
        ],
        'tt_training_plan_runs' => [
            'entity_type' => 'training_plan_run',
            'category'    => 'training_runs',
            'written_by'  => TrainingRunGenerator::class,
            'depends_on'  => [ 'tt_training_plans', 'tt_activities' ],
        ],
        'tt_training_plan_run_blocks' => [
            'entity_type' => 'training_plan_run_block',
            'category'    => 'training_runs',
            'written_by'  => TrainingRunGenerator::class,
            'depends_on'  => [ 'tt_training_plan_runs' ],
        ],
        // #2500 (D18) — observations make the module look *used* rather
        // than merely furnished: someone's words about a named player,
        // from a Tuesday in August. That is what the module is for.
        'tt_training_observations' => [
            'entity_type' => 'training_observation',
            'category'    => 'training_observations',
            'written_by'  => TrainingObservationGenerator::class,
            'depends_on'  => [ 'tt_training_plan_runs', 'tt_players' ],
        ],
        // Derived, not authored: the nightly workflow job rebuilds this
        // from runs, attendance and exercise principles. Generating it
        // would write rows that disagree with their own source the first
        // time that job runs — and a wipe does not need to reach them,
        // because the next rebuild after the source is gone produces
        // nothing. (D18 states this explicitly.)
        'tt_player_principle_exposure' => [
            'exempt' => 'Derived aggregate. PlayerExposureAggregationTaskTemplate rebuilds it nightly from runs + attendance + exercise principles; generating it would create rows that contradict their own source.',
        ],

        // Media (#2596, epic #2589). A demo academy whose media tab is
        // empty does not demo the feature, so the generator writes a squad
        // photo per team, a few player portraits and one external video
        // link. Placeholder images are drawn at runtime rather than
        // committed as binaries, and nothing here is fetched over the
        // network — the Veo link has no oEmbed endpoint, so generating
        // demo data works offline.
        'tt_media' => [
            'entity_type' => 'media',
            'category'    => 'media',
            'written_by'  => MediaGenerator::class,
            'depends_on'  => [ 'team', 'player' ],
        ],
        'tt_media_links' => [
            'entity_type' => 'media_link',
            'category'    => 'media',
            'written_by'  => MediaGenerator::class,
            'depends_on'  => [ 'media', 'team', 'player' ],
        ],

        // ===== Knowledge library =====
        //
        // #2644 — staff working through the shipped courses. The courses
        // themselves are markdown in the repository, not rows, so nothing
        // here generates a course; these are people's relationships to
        // one. The spread is deliberately mixed (finished / mid-course /
        // not started / overdue / awaiting review) so the roll-up report
        // has something real to render on a demo install.

        'tt_course_enrolments' => [
            'entity_type' => 'course_enrolment',
            'category'    => 'knowledge',
            'written_by'  => KnowledgeGenerator::class,
            'depends_on'  => [ 'person' ],
        ],
        'tt_course_progress' => [
            'entity_type' => 'course_progress',
            'category'    => 'knowledge',
            'written_by'  => KnowledgeGenerator::class,
            'depends_on'  => [ 'course_enrolment' ],
        ],
        'tt_course_quiz_attempts' => [
            'entity_type' => 'course_quiz_attempt',
            'category'    => 'knowledge',
            'written_by'  => KnowledgeGenerator::class,
            'depends_on'  => [ 'course_enrolment' ],
        ],
        'tt_course_submissions' => [
            'entity_type' => 'course_submission',
            'category'    => 'knowledge',
            'written_by'  => KnowledgeGenerator::class,
            'depends_on'  => [ 'course_enrolment' ],
        ],

        // ===== Exempt — reference data seeded by migrations =====

        'tt_player_attribute_defs' => [ 'exempt' => 'The 23 chemistry attribute definitions are seeded by migration 0178; #2463 fills values against them rather than inventing more.' ],
        'tt_pdp_prep_questions'   => [ 'exempt' => 'The prompts a coach prepares a PDP conversation against (#3305), seeded by migration 0256 and admin-editable. Configuration rather than content — every install gets the set, demo or not, and a demo wipe must not take an academy\'s own questions with it.' ],
        'tt_eval_categories'      => [ 'exempt' => 'Evaluation category tree, seeded by migrations and admin-editable. The Excel path documents it as a reference sheet.' ],
        'tt_eval_type_categories' => [ 'exempt' => 'Evaluation-type to category mapping, seeded by migrations.' ],
        'tt_category_weights'     => [ 'exempt' => 'Per-age-group category weights, seeded by migrations and admin-editable.' ],
        'tt_measurement_levels'   => [ 'exempt' => 'Measurement status levels, seeded by migration 0192.' ],
        'tt_measurement_units'    => [ 'exempt' => 'The unit registry (symbol, dimension, factor to the SI base), seeded by migration 0252. Reference data an academy extends, not demo content — the demo battery picks units out of it.' ],
        'tt_exercises'            => [ 'exempt' => 'The exercise library is seeded by migration 0090; #2465 attaches those exercises to trainings rather than building a second library.' ],
        'tt_exercise_categories'  => [ 'exempt' => 'Exercise category vocabulary, seeded by migrations.' ],
        'tt_exercise_principles'  => [ 'exempt' => 'Exercise/principle reference mapping, seeded by migrations.' ],
        // #2501 — a scene is authored content: a coach draws it on a
        // canvas for their own drill. Generating one would mean inventing
        // a tactical pattern and presenting it as an academy's own
        // coaching, which is a different thing from generating a
        // plausible attendance row. The library the demo installs is
        // itself seeded rather than generated (see tt_exercises above),
        // so there is nothing here a demo run would attach a scene to.
        'tt_exercise_scenes'      => [ 'exempt' => 'Authored diagrams. Generating one would mean inventing a tactical pattern and presenting it as the academy\'s own coaching; the demo library is seeded rather than generated, so there is nothing to attach one to.' ],
        'tt_football_actions'     => [ 'exempt' => 'Football-action vocabulary, seeded by migrations.' ],
        'tt_principles'           => [ 'exempt' => 'Methodology principles, seeded by migrations (JO13 set in 0206).' ],
        'tt_methodologies'                    => [ 'exempt' => 'Methodology sets, seeded by migrations.' ],
        'tt_methodology_assets'               => [ 'exempt' => 'Methodology reference content, seeded by migrations.' ],
        'tt_methodology_framework_primers'    => [ 'exempt' => 'Methodology reference content, seeded by migrations.' ],
        'tt_methodology_influence_factors'    => [ 'exempt' => 'Methodology reference content, seeded by migrations.' ],
        'tt_methodology_learning_goals'       => [ 'exempt' => 'Methodology reference content, seeded by migrations.' ],
        'tt_methodology_phases'               => [ 'exempt' => 'Methodology reference content, seeded by migrations.' ],
        'tt_methodology_principle_links'      => [ 'exempt' => 'Methodology reference content, seeded by migrations.' ],
        'tt_methodology_sub_principles'       => [ 'exempt' => 'Methodology reference content, seeded by migrations.' ],
        'tt_methodology_tactical_scenes'      => [ 'exempt' => 'Methodology reference content, seeded by migrations.' ],
        'tt_methodology_visions'              => [ 'exempt' => 'Methodology reference content, seeded by migrations.' ],
        'tt_formation_templates'  => [ 'exempt' => 'Eight formation templates seeded by migration 0032; #2466 assigns them to teams rather than duplicating shapes.' ],
        'tt_formations'           => [ 'exempt' => 'Shipped methodology formations (is_shipped), seeded by migrations.' ],
        'tt_formation_positions'  => [ 'exempt' => 'Shipped position profiles for the seeded formations.' ],
        'tt_set_pieces'           => [ 'exempt' => 'Shipped set-piece routines, seeded by migrations and admin-extensible.' ],
        'tt_chemistry_position_matrix' => [ 'exempt' => 'Position-affinity reference matrix, seeded by migrations.' ],
        'tt_trial_letter_templates'    => [ 'exempt' => 'Trial letter templates, seeded by migrations and admin-editable.' ],
        'tt_trial_tracks'              => [ 'exempt' => 'Trial tracks are seeded by migrations (is_seeded); #2467 opens cases against them rather than inventing tracks.' ],

        // ===== Exempt — external integrations and side effects =====

        'tt_player_strava_connections' => [ 'exempt' => 'Requires real Strava OAuth tokens; a fabricated connection cannot sync and renders as broken.' ],
        'tt_player_activities'         => [ 'exempt' => 'Strava-imported activities, downstream of an exempt OAuth connection. Demo Strava data needs its own issue.' ],
        'tt_scheduled_reports'         => [ 'exempt' => 'A demo row would dispatch real email on the next cron run.' ],
        'tt_workflow_triggers'         => [ 'exempt' => 'Workflow trigger configuration, seeded by migrations.' ],
        'tt_workflow_template_config'  => [ 'exempt' => 'Workflow template configuration, seeded by migrations.' ],
        'tt_workflow_event_log'        => [ 'exempt' => 'Log of real workflow-engine events.' ],
    ];

    /**
     * Operator-facing wipe/generate categories.
     *
     * `cascade` is the ordered (children-first) entity-type list a category
     * expands to. It encodes FK dependency *and* operator preference: wiping
     * teams also drops the activities and evaluations tied to them, but not
     * the players — an operator rebuilding a team structure keeps the squad.
     * That asymmetry is why cascades are declared rather than derived from
     * `depends_on`.
     *
     * `tier` splits the generate + wipe forms into "master data" (rows other
     * categories hang off) and "dependent entities".
     *
     * @var array<string, array{tier:string, cascade:string[]}>
     */
    public const CATEGORIES = [
        'teams' => [
            'tier'    => 'master',
            'cascade' => [
                'eval_rating', 'evaluation', 'attendance',
                'match_tracked_event', 'match_substitution', 'match_goal_event', 'match_execution',
                'match_prep_role', 'match_prep_player_goal', 'match_prep_lineup',
                'match_prep_availability', 'match_prep',
                'activity_exercise', 'activity_principle', 'activity',
                'measurement_result', 'measurement_session',
                'team_blueprint_assignment', 'team_blueprint',
                'team_chemistry_snapshot', 'team_chemistry_pairing',
                'team_playing_style', 'team_formation',
                'exercise_team_override', 'team_person', 'team',
            ],
        ],
        'people' => [
            'tier'    => 'master',
            'cascade' => [ 'team_person', 'person' ],
        ],
        'players' => [
            'tier'    => 'master',
            // goal_link references both a goal and an evaluation, so it has
            // to go before either of them.
            'cascade' => [
                'goal_link', 'eval_rating', 'evaluation', 'attendance', 'goal',
                'player_event', 'trial_case', 'player_report', 'player_attribute_value',
                'player_team_history', 'player_injury', 'player_parent_visibility',
                'custom_value', 'player_parent',
                'measurement_result', 'pdp_prep_answer', 'pdp_calendar_link', 'pdp_verdict',
                'pdp_conversation', 'pdp_file',
                'player',
            ],
        ],
        // `run_order` fixes the sequence dependent generators run in. All of
        // them draw from one seeded MT stream, so reordering them changes
        // every value downstream — the same (seed, preset) would stop
        // reproducing. These three numbers preserve the pre-#2462 order
        // (evaluations, activities, goals); a new wave appends rather than
        // inserting, unless it deliberately accepts a fingerprint change.
        'evaluations' => [
            'tier'        => 'dependent',
            'run_order'   => 10,
            'cascade'     => [ 'eval_rating', 'evaluation' ],
            'excel_sheet' => 'evaluations',
        ],
        'activities' => [
            'tier'        => 'dependent',
            'run_order'   => 20,
            'cascade'     => [
                'match_tracked_event', 'match_substitution', 'match_goal_event', 'match_execution',
                'match_prep_role', 'match_prep_player_goal', 'match_prep_lineup',
                'match_prep_availability', 'match_prep',
                'activity_exercise', 'activity_principle', 'attendance', 'activity',
            ],
            'excel_sheet' => 'sessions',
        ],
        'goals' => [
            'tier'        => 'dependent',
            'run_order'   => 30,
            'cascade'     => [ 'goal' ],
            'excel_sheet' => 'goals',
        ],
        'guardians' => [
            'tier'      => 'dependent',
            'run_order' => 40,
            'cascade'   => [ 'player_parent_visibility', 'player_parent' ],
        ],
        'injuries' => [
            'tier'      => 'dependent',
            'run_order' => 50,
            'cascade'   => [ 'player_injury' ],
        ],
        'player_profile' => [
            'tier'      => 'dependent',
            'run_order' => 60,
            'cascade'   => [ 'goal_link', 'custom_value', 'custom_field', 'player_attribute_value', 'player_team_history' ],
        ],
        'reports' => [
            'tier'      => 'dependent',
            'run_order' => 70,
            'cascade'   => [ 'player_report' ],
        ],
        'measurements' => [
            'tier'      => 'dependent',
            'run_order' => 80,
            'cascade'   => [ 'measurement_result', 'measurement_session', 'measurement_target', 'measurement_definition' ],
        ],
        'pdp' => [
            'tier'      => 'dependent',
            'run_order' => 90,
            'cascade'   => [ 'pdp_prep_answer', 'pdp_calendar_link', 'pdp_verdict', 'pdp_conversation', 'pdp_file', 'season' ],
        ],
        'activity_content' => [
            'tier'      => 'dependent',
            'run_order' => 100,
            'cascade'   => [ 'activity_exercise', 'activity_principle', 'exercise_team_override', 'holiday' ],
        ],
        'match_day' => [
            'tier'      => 'dependent',
            'run_order' => 110,
            'cascade'   => [
                'match_tracked_event', 'match_substitution', 'match_goal_event', 'match_execution',
                'match_prep_role', 'match_prep_player_goal', 'match_prep_lineup',
                'match_prep_availability', 'match_prep',
            ],
        ],
        'test_trainings' => [
            'tier'      => 'dependent',
            'run_order' => 120,
            'cascade'   => [ 'test_training' ],
        ],
        'team_development' => [
            'tier'      => 'dependent',
            'run_order' => 130,
            'cascade'   => [
                'team_blueprint_assignment', 'team_blueprint',
                'team_chemistry_snapshot', 'team_chemistry_pairing',
                'team_playing_style', 'team_formation',
            ],
        ],
        'journey' => [
            'tier'    => 'dependent',
            'cascade' => [ 'player_event' ],
        ],
        'trials' => [
            'tier'      => 'dependent',
            'run_order' => 140,
            'cascade'   => [ 'trial_extension', 'trial_case_staff_input', 'trial_case_staff', 'trial_case' ],
        ],
        'pipeline' => [
            'tier'      => 'dependent',
            'run_order' => 150,
            'cascade'   => [ 'prospect', 'scouting_visit' ],
        ],
        'tournaments' => [
            'tier'      => 'dependent',
            'run_order' => 160,
            'cascade'   => [ 'tournament_assignment', 'tournament_match', 'tournament_squad', 'tournament' ],
        ],
        'staff_development' => [
            'tier'      => 'dependent',
            'run_order' => 170,
            'cascade'   => [
                'staff_eval_rating', 'staff_evaluation', 'staff_goal',
                'staff_pdp', 'staff_certification', 'staff_mentorship',
            ],
        ],
        'comms_ops' => [
            'tier'      => 'dependent',
            'run_order' => 180,
            'cascade'   => [
                'thread_read', 'thread_message', 'saved_filter',
                'report_preset', 'workflow_task', 'invitation',
            ],
        ],
        // #2498 — appended rather than inserted, so every generator
        // before it draws the same values from the seeded stream and the
        // same (seed, preset) keeps reproducing.
        'training_plans' => [
            'tier'      => 'dependent',
            'run_order' => 190,
            'cascade'   => [ 'training_plan_principle', 'training_plan_block', 'training_plan' ],
        ],
        // #2499 — after the plans, because a run attaches to one. Runs
        // are cascaded ahead of plans in the delete order for the same
        // reason, which `depends_on` already encodes.
        'training_runs' => [
            'tier'      => 'dependent',
            'run_order' => 200,
            'cascade'   => [ 'training_plan_run_block', 'training_plan_run' ],
        ],
        // #2596 — appended rather than inserted, so the existing
        // (seed, preset) fingerprint keeps reproducing. Links cascade
        // ahead of the media itself: a media row with no links is
        // unreachable, and the repository deletes it along with its file.
        'media' => [
            'tier'      => 'dependent',
            'run_order' => 210,
            'cascade'   => [ 'media_link', 'media' ],
        ],
        // #2500 — after the runs, because an observation is about one.
        // 220 rather than 210: #2596 took that number first, and the rule
        // is to append rather than insert so every generator before this
        // one keeps drawing the same values from the seeded stream.
        'training_observations' => [
            'tier'      => 'dependent',
            'run_order' => 220,
            'cascade'   => [ 'training_observation' ],
        ],
        // #2644 — 230, because #2500 landed on 220 while this was in
        // flight. Appended rather than inserted for the same reason it
        // was: every generator ahead of this one keeps drawing the same
        // values from the seeded stream, so the (seed, preset)
        // fingerprint still reproduces. Cascade runs the children before
        // the enrolment they hang off — progress, attempts and
        // submissions all key on `enrolment_id`, so deleting the
        // enrolment first would strand them.
        // #2704 — 240, appended rather than inserted for the same reason
        // every category above it was: the (seed, preset) fingerprint only
        // reproduces while the generators ahead of a new one keep drawing
        // the same values from the seeded stream. Runs late because it
        // marks up who actually played, which needs attendance and minutes
        // already written. Cascade removes the children first — a section
        // or player item whose analysis is gone is unreachable.
        'match_analyses' => [
            'tier'      => 'dependent',
            'run_order' => 240,
            'cascade'   => [ 'match_analysis_note', 'match_analysis_player', 'match_analysis_section', 'match_analysis' ],
        ],
        // #3242 — appended at 250 rather than inserted, so every generator
        // before it draws the same values from the seeded MT stream and the
        // same (seed, preset) keeps reproducing the same academy.
        'player_status' => [
            'tier'      => 'dependent',
            'run_order' => 250,
            'cascade'   => [ 'player_potential', 'player_behaviour_rating' ],
        ],
        'knowledge' => [
            'tier'      => 'dependent',
            'run_order' => 230,
            'cascade'   => [
                'course_submission', 'course_quiz_attempt',
                'course_progress', 'course_enrolment',
            ],
        ],
    ];

    /**
     * Tables whose rows lack a surrogate `id`, or whose `id` column is named
     * something else. `delete_by` deletes rows whose column matches the
     * wiped id set of another entity type — the only way to reach a pivot
     * table keyed on its two foreign keys.
     *
     * @var array<string, array{id_column?:string, delete_by?:array{column:string, entity_type:string}, club_scoped?:bool}>
     */
    public const TABLE_QUIRKS = [
        // PRIMARY KEY (player_id, parent_user_id) — migration 0025, no id.
        // The generator tags one row per player (entity_id = player_id), so
        // the wipe deletes every guardian link for the wiped players.
        'tt_player_parents' => [
            'delete_by' => [ 'column' => 'player_id', 'entity_type' => 'player_parent' ],
        ],
        // PK is (tournament_id, player_id) — no surrogate id. Tagged once per
        // tournament, so the wipe clears that tournament's whole squad.
        'tt_tournament_squad' => [
            'delete_by' => [ 'column' => 'tournament_id', 'entity_type' => 'tournament_squad' ],
        ],
        // PK is (user_id, thread_type, thread_id) — no surrogate id. Tagged
        // per thread, so the wipe clears read state for the demo threads only
        // and leaves a real user's read state on real threads alone.
        'tt_thread_reads' => [
            'delete_by' => [ 'column' => 'thread_id', 'entity_type' => 'thread_read' ],
        ],
        // #2498 — a plan's blocks and its derived principle rows have
        // their own ids but are never tagged individually: they exist
        // only as part of a plan, and tagging six blocks per plan would
        // triple the batch registry for nothing. The wipe reaches them
        // through the plan id instead, which is also what guarantees a
        // wiped plan cannot leave orphaned blocks behind.
        'tt_training_plan_blocks' => [
            'delete_by' => [ 'column' => 'plan_id', 'entity_type' => 'training_plan_block' ],
        ],
        'tt_training_plan_principles' => [
            'delete_by' => [ 'column' => 'plan_id', 'entity_type' => 'training_plan_principle' ],
        ],
        // #2499 — same shape one level down: a run's block rows are
        // addressed by run_id, and the type is tagged with the run id so
        // the cleaner does not skip the table for having no tags.
        'tt_training_plan_run_blocks' => [
            'delete_by' => [ 'column' => 'run_id', 'entity_type' => 'training_plan_run_block' ],
        ],
    ];

    /** @return array<string,mixed>|null */
    public static function entry( string $table ): ?array {
        return self::MANIFEST[ $table ] ?? null;
    }

    public static function stateOf( string $table ): ?string {
        $entry = self::entry( $table );
        if ( $entry === null ) return null;
        if ( isset( $entry['exempt'] ) ) return self::STATE_EXEMPT;
        if ( isset( $entry['planned'] ) ) return self::STATE_PLANNED;
        return self::STATE_GENERATED;
    }

    /**
     * Tables in the generated state, keyed by table name.
     *
     * @return array<string, array<string,mixed>>
     */
    public static function generatedTables(): array {
        $out = [];
        foreach ( self::MANIFEST as $table => $entry ) {
            if ( self::stateOf( $table ) === self::STATE_GENERATED ) {
                $out[ $table ] = $entry;
            }
        }
        return $out;
    }

    /**
     * entity_type => [ table, id_column ], the cleaner's lookup.
     *
     * @return array<string, array{0:string, 1:string}>
     */
    public static function tableMap(): array {
        $out = [];
        foreach ( self::generatedTables() as $table => $entry ) {
            $type   = (string) $entry['entity_type'];
            $quirks = self::TABLE_QUIRKS[ $table ] ?? [];
            $out[ $type ] = [ $table, (string) ( $quirks['id_column'] ?? 'id' ) ];
        }
        return $out;
    }

    /**
     * The Excel sheet whose presence means the workbook already covered this
     * category, so the procedural generator should stand down. Null when no
     * sheet maps to it.
     */
    public static function excelSheetFor( string $category ): ?string {
        $sheet = self::CATEGORIES[ $category ]['excel_sheet'] ?? null;
        return $sheet !== null ? (string) $sheet : null;
    }

    /** @return array{column:string, entity_type:string}|null */
    public static function deleteBy( string $table ): ?array {
        $quirks = self::TABLE_QUIRKS[ $table ] ?? [];
        return isset( $quirks['delete_by'] ) ? $quirks['delete_by'] : null;
    }

    public static function isClubScoped( string $table ): bool {
        $quirks = self::TABLE_QUIRKS[ $table ] ?? [];
        return ! isset( $quirks['club_scoped'] ) || (bool) $quirks['club_scoped'];
    }

    /**
     * Entity types in dependency-safe delete order: a type is listed before
     * every type it depends on, so children go first. Ties break on the
     * manifest's own order for a stable result across runs.
     *
     * @return string[]
     */
    public static function deleteOrder(): array {
        $depends = [];
        foreach ( self::generatedTables() as $entry ) {
            $type = (string) $entry['entity_type'];
            $deps = array_map( 'strval', (array) ( $entry['depends_on'] ?? [] ) );
            $depends[ $type ] = array_values( array_unique( array_merge( $depends[ $type ] ?? [], $deps ) ) );
        }

        // Depth = longest dependency chain below a type. Deeper types are
        // dependents, so they get deleted first.
        $depth = [];
        $resolve = function ( string $type, array $seen ) use ( &$resolve, $depends, &$depth ): int {
            if ( isset( $depth[ $type ] ) ) return $depth[ $type ];
            if ( isset( $seen[ $type ] ) ) return 0;   // cycle guard
            $seen[ $type ] = true;
            $max = 0;
            foreach ( $depends[ $type ] ?? [] as $dep ) {
                if ( ! isset( $depends[ $dep ] ) ) continue;
                $max = max( $max, 1 + $resolve( $dep, $seen ) );
            }
            $depth[ $type ] = $max;
            return $max;
        };

        $types = array_keys( $depends );
        foreach ( $types as $type ) {
            $resolve( $type, [] );
        }

        $order = $types;
        usort( $order, static function ( string $a, string $b ) use ( $depth, $types ): int {
            $byDepth = ( $depth[ $b ] ?? 0 ) <=> ( $depth[ $a ] ?? 0 );
            if ( $byDepth !== 0 ) return $byDepth;
            return array_search( $a, $types, true ) <=> array_search( $b, $types, true );
        } );

        return $order;
    }

    /**
     * Category keys, master tier first, in manifest declaration order.
     *
     * @return string[]
     */
    public static function categoryKeys( ?string $tier = null ): array {
        $out = [];
        foreach ( self::CATEGORIES as $key => $cfg ) {
            if ( $tier !== null && (string) $cfg['tier'] !== $tier ) continue;
            $out[] = $key;
        }
        return $out;
    }

    /**
     * The entity types a category expands to, ordered children-first.
     *
     * @return string[]
     */
    public static function cascade( string $category ): array {
        $cascade = self::CATEGORIES[ $category ]['cascade'] ?? [];
        $known   = self::tableMap();
        // Drop types whose generator hasn't landed yet, so a cascade can
        // name a future entity type without breaking today's wipe.
        return array_values( array_filter(
            array_map( 'strval', $cascade ),
            static function ( string $type ) use ( $known ): bool {
                return isset( $known[ $type ] );
            }
        ) );
    }

    /**
     * Generator classes that write dependent-tier categories, ordered by
     * `run_order`. The order is part of the reproducibility contract — see
     * the note on `CATEGORIES`.
     *
     * @return array<string, class-string> category => generator class
     */
    public static function dependentGenerators(): array {
        $out = [];
        foreach ( self::generatedTables() as $entry ) {
            $category = (string) $entry['category'];
            $writer   = (string) $entry['written_by'];
            if ( ( self::CATEGORIES[ $category ]['tier'] ?? '' ) !== 'dependent' ) continue;
            if ( $writer === self::WRITTEN_BY_HOOK || $writer === self::WRITTEN_BY_EXCEL ) continue;
            if ( isset( $out[ $category ] ) ) continue;
            $out[ $category ] = $writer;
        }

        uksort( $out, static function ( string $a, string $b ): int {
            $oa = (int) ( self::CATEGORIES[ $a ]['run_order'] ?? PHP_INT_MAX );
            $ob = (int) ( self::CATEGORIES[ $b ]['run_order'] ?? PHP_INT_MAX );
            return $oa <=> $ob;
        } );

        return $out;
    }

    /** Human-readable category label. Translatable; not stored. */
    public static function categoryLabel( string $category ): string {
        $labels = [
            'teams'       => __( 'Teams', 'talenttrack' ),
            'people'      => __( 'People', 'talenttrack' ),
            'players'     => __( 'Players', 'talenttrack' ),
            'activities'  => __( 'Activities', 'talenttrack' ),
            'evaluations' => __( 'Evaluations', 'talenttrack' ),
            'goals'       => __( 'Goals', 'talenttrack' ),
            'journey'     => __( 'Journey events', 'talenttrack' ),
            'trials'      => __( 'Trial cases', 'talenttrack' ),
            'guardians'   => __( 'Guardians', 'talenttrack' ),
            'injuries'    => __( 'Injuries', 'talenttrack' ),
            'player_profile' => __( 'Player profile', 'talenttrack' ),
            'reports'     => __( 'Player reports', 'talenttrack' ),
            'measurements' => __( 'Measurements', 'talenttrack' ),
            'pdp'         => __( 'PDP cycle', 'talenttrack' ),
            'activity_content' => __( 'Training content', 'talenttrack' ),
            'match_day'   => __( 'Match day', 'talenttrack' ),
            'test_trainings' => __( 'Test trainings', 'talenttrack' ),
            'team_development' => __( 'Team development', 'talenttrack' ),
            'pipeline'    => __( 'Scouting pipeline', 'talenttrack' ),
            'tournaments' => __( 'Tournaments', 'talenttrack' ),
            'staff_development' => __( 'Staff development', 'talenttrack' ),
            'comms_ops'   => __( 'Messages and operator records', 'talenttrack' ),
            'knowledge'   => __( 'Courses', 'talenttrack' ),
            'player_status' => __( 'Behaviour and potential', 'talenttrack' ),
        ];
        return $labels[ $category ] ?? $category;
    }

    /** One-line description of what a category's cascade reaches. */
    public static function categoryHint( string $category ): string {
        $hints = [
            'teams'       => __( 'Also wipes team_person, activities, attendance, evaluations and eval_ratings on those teams.', 'talenttrack' ),
            'people'      => __( 'Also wipes team_person assignments. The matching WP users stay — use "Wipe demo users" below for those.', 'talenttrack' ),
            'players'     => __( 'Also wipes attendance, evaluations, eval_ratings, goals, journey events and trial cases tied to those players.', 'talenttrack' ),
            'activities'  => __( 'Also wipes attendance for those activities.', 'talenttrack' ),
            'evaluations' => __( 'Also wipes per-category eval_ratings.', 'talenttrack' ),
            'goals'       => __( 'Per-player development goals.', 'talenttrack' ),
            'journey'     => __( 'Timeline events written by the journey subscriber during generation.', 'talenttrack' ),
            'trials'      => __( 'Historical trial cases on existing players plus a couple of open ones, each with its staff panel, assessments and extensions.', 'talenttrack' ),
            'pipeline'    => __( 'Scouting visits across the window and the prospects found on them.', 'talenttrack' ),
            'tournaments' => __( 'A tournament per team with its squad, target minutes, fixtures and per-period assignments.', 'talenttrack' ),
            'staff_development' => __( 'Coaching badges, development plans and goals, evaluations with ratings, and mentor pairings for the club\'s staff.', 'talenttrack' ),
            'comms_ops'   => __( 'Conversations with their read state, saved filters, report presets, workflow tasks and invitations. No email is ever sent.', 'talenttrack' ),
            'guardians'   => __( 'Guardian links to the demo parent accounts, plus each player\'s parent-visibility grants.', 'talenttrack' ),
            'injuries'    => __( 'Injury records with their return-to-play dates and the journey events they raise.', 'talenttrack' ),
            'player_profile' => __( 'Age-group history, attribute values, the club\'s custom fields and their values, and goal-to-evaluation links.', 'talenttrack' ),
            'reports'     => __( 'Generated player reports. No share links or recipients are created.', 'talenttrack' ),
            'measurements' => __( 'The testing battery, its per-age-group target bands, team testing sessions and one result per player.', 'talenttrack' ),
            'pdp'         => __( 'The season, one development dossier per player, its conversation cycle, calendar links and verdicts.', 'talenttrack' ),
            'activity_content' => __( 'Exercises and methodology principles on each training, per-team exercise overrides, and the season\'s holiday windows.', 'talenttrack' ),
            'match_day'   => __( 'Match prep for every fixture — availability, lineup, roles, per-player intent — plus results, goals and substitutions for the ones already played.', 'talenttrack' ),
            'test_trainings' => __( 'Open sessions for invited players, one past and one upcoming per age group.', 'talenttrack' ),
            'team_development' => __( 'A formation and playing-style mix per team, a match-day blueprint with its assignments, coach-marked pairings, and a chemistry series across the window.', 'talenttrack' ),
            'knowledge'   => __( 'Staff enrolled on the shipped courses, with a mixed spread: some finished, some mid-course, one overdue and one assignment awaiting review. The courses themselves ship with the plugin and are never generated.', 'talenttrack' ),
            'player_status' => __( 'Two of the four traffic-light inputs: behaviour ratings spread across the window, and dated potential histories for squads old enough to be asked. Some players are deliberately left without, and a few left overdue, so the alerts have something true to say.', 'talenttrack' ),
        ];
        return $hints[ $category ] ?? '';
    }
}
