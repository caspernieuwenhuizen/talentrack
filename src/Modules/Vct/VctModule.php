<?php
namespace TT\Modules\Vct;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Core\Container;
use TT\Core\ModuleInterface;
use TT\Modules\Vct\Repositories\VctSessionsRepository;
use TT\Modules\Vct\Rest\VctAgeProfilesRestController;
use TT\Modules\Vct\Rest\VctExercisesRestController;
use TT\Modules\Vct\Rest\VctMacroBlocksRestController;
use TT\Modules\Vct\Rest\VctCycleWeeksRestController;
use TT\Modules\Vct\Rest\VctTeamCyclesRestController;
use TT\Modules\Vct\Rest\VctPhvFlagsRestController;
use TT\Modules\Vct\Rest\VctTeamSchedulesRestController;
use TT\Modules\Vct\Rest\VctTrainingsRestController;
use TT\Modules\Vct\Rest\VctWorkloadRestController;
use TT\Modules\Vct\Wizard\NewVctSessionWizard;
use TT\Modules\Vct\Workflow\VctWorkloadAggregationTaskTemplate;
use TT\Modules\Workflow\WorkflowModule;
use TT\Shared\Wizards\WizardRegistry;

/**
 * VctModule — Voetbal Conditionele Training (#0095, epic #905).
 *
 * Phase 1 ships:
 *   - Schema (migration 0122): 11 tables (10 tt_vct_* + tt_player_phv_flags)
 *   - Capabilities (#907, v4.3.1): 3 matrix-only caps + bridges + matrix seed
 *   - Lookup vocabularies (#908, v4.3.2): 5 lookup_types with 5-locale translations
 *   - Reference seeds (#909, v4.3.3): age profiles, session templates, phase profiles
 *   - **This ship (#910, v4.3.5)**: Rules Engine + repositories + supporting services
 *
 * What this ship adds (deterministic core, no AI, age-safe by design):
 *   - 11 repositories under src/Modules/Vct/Repositories/ (one per table; every
 *     read filters by club_id = CurrentClub::id())
 *   - 8 rule passes + RulesEngine orchestrator with two entry points:
 *       compose()  — full pipeline including ExerciseSelectionPass
 *                    (POST /vct/sessions/generate uses this)
 *       validate() — passes 1-5 + 7-8 only; skips selection (PATCH uses this
 *                    so a coach's manual swap is validated but not overwritten)
 *   - VctTrainingComposer service: context build → compose → persist
 *   - WorkloadCalculator (pure function: session → total load)
 *   - MdContextResolver (date + team schedule + match calendar → MD label)
 *   - Provider interfaces: ActivitiesReader, RecentPicksProvider, VctPhvFlagsProvider
 *     (this-module-owned so VCT doesn't reach into Activities internals)
 *
 * Phase 2 (separate ships) wires REST (VCT-6 / #911), the nightly workload
 * aggregation task (VCT-7 / #912), the coach mobile UI + wizard, and the
 * optional AI presentation layer.
 *
 * No surfaces here register REST routes or workflow triggers yet — the
 * module's responsibility in this ship is to expose the deterministic
 * engine + repositories that the next two ships consume.
 */
class VctModule implements ModuleInterface {

    public function getName(): string { return 'vct'; }

    public function register( Container $container ): void {
        // Repositories + services are lazily instantiated by callers
        // for now (REST controllers in VCT-6 will instantiate the
        // VctTrainingComposer they need). No DI bindings required at
        // boot time; keeps the module dormant until something else
        // wakes it.
    }

    public function boot( Container $container ): void {
        // #911 — REST controller registration. Each controller hooks
        // `rest_api_init` itself; calling init() here just registers
        // the hook. Wizard registration + the new-VCT-session wizard
        // ship in a later UI-focused issue (VCT-9).
        VctTrainingsRestController::init();
        VctExercisesRestController::init();
        VctTeamSchedulesRestController::init();
        VctMacroBlocksRestController::init();
        VctTeamCyclesRestController::init();
        VctCycleWeeksRestController::init();
        VctAgeProfilesRestController::init();
        VctWorkloadRestController::init();
        VctPhvFlagsRestController::init();

        // #911 — When an Activity is deleted, null out the bound
        // session's activity_id and revert it to draft. Per spec
        // § Integration with Activities — the session is preserved;
        // the coach can re-publish or archive it.
        add_action( 'tt_activity_deleted', [ self::class, 'onActivityDeleted' ], 10, 1 );

        // #912 — Register the nightly workload-aggregation task with
        // the Workflow module's template registry. The matching cron
        // trigger row lands via migration 0127. CronDispatcher's
        // hourly tick walks enabled cron triggers and fires
        // `dispatch()` on the registered template when the cron
        // expression resolves to "fire now or earlier". Idempotent;
        // re-registration on every request is safe.
        add_action( 'init', [ self::class, 'registerWorkflowTemplates' ], 5 );

        // #944 (VCT-9) — Register the new-VCT-training wizard with
        // the wizards framework. Reachable via
        // `?tt_view=wizard&slug=new-vct-session`.
        if ( class_exists( WizardRegistry::class ) ) {
            WizardRegistry::register( new NewVctSessionWizard() );
        }
    }

    /**
     * #912 — Register the workload-aggregation task template with the
     * Workflow registry. Same priority pattern as PdpModule (priority
     * 5 on init) so dispatchers at priority 20 see the template
     * already registered.
     */
    public static function registerWorkflowTemplates(): void {
        if ( ! class_exists( WorkflowModule::class ) ) return;
        WorkflowModule::registry()->register( new VctWorkloadAggregationTaskTemplate() );
    }

    /**
     * Hook handler for `tt_activity_deleted`. The Activities module
     * fires this action with the deleted activity_id; VCT looks up any
     * session bound to that activity and reverts it.
     */
    public static function onActivityDeleted( int $activity_id ): void {
        if ( $activity_id <= 0 ) return;
        global $wpdb;
        $sessions = $wpdb->prefix . 'tt_vct_sessions';
        $bound = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$sessions} WHERE activity_id = %d LIMIT 1",
            $activity_id
        ) );
        if ( $bound <= 0 ) return;
        ( new VctSessionsRepository() )->updateStatus( $bound, 'draft', 0 );
    }
}
