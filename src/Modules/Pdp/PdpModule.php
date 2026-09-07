<?php
namespace TT\Modules\Pdp;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Core\Container;
use TT\Core\ModuleInterface;
use TT\Modules\Pdp\Admin\SeasonsPage;
use TT\Modules\Pdp\Carryover\SeasonCarryover;
use TT\Modules\Pdp\Print\PdpPrintRouter;
use TT\Modules\Pdp\Rest\PdpBlocksRestController;
use TT\Modules\Pdp\Rest\PdpConversationsRestController;
use TT\Modules\Pdp\Rest\PdpFilesRestController;
use TT\Modules\Pdp\Rest\PdpPrepRestController;
use TT\Modules\Pdp\Rest\PdpVerdictsRestController;
use TT\Modules\Pdp\Rest\SeasonsRestController;
use TT\Modules\Pdp\Workflow\PdpConversationDueTemplate;
use TT\Modules\Pdp\Workflow\PdpSelfReviewSweep;
use TT\Modules\Pdp\Workflow\PdpSelfReviewTemplate;
use TT\Modules\Pdp\Workflow\PdpVerdictDueTemplate;
use TT\Modules\Workflow\Dispatchers\CronDispatcher;
use TT\Modules\Workflow\WorkflowModule;

/**
 * PdpModule (#0044) — Player Development Plan cycle.
 *
 * Sprint 1 ships:
 *   - Schema (migration 0031): tt_seasons, tt_pdp_files,
 *     tt_pdp_conversations, tt_pdp_verdicts, tt_goal_links,
 *     tt_pdp_calendar_links + tt_teams.pdp_cycle_size column.
 *   - Repositories under Repositories/.
 *   - REST controllers under Rest/.
 *   - PdpCalendarWriter interface + NativeCalendarWriter default.
 *   - Workflow template registration scaffold (no live cadence yet).
 *   - Capabilities: tt_view_pdp, tt_edit_pdp, tt_edit_pdp_verdict.
 *   - Auth matrix entries (`pdp_file`, `pdp_verdict`) seeded from
 *     /config/authorization_seed.php.
 *
 * Sprint 2 lands the UX surfaces, live workflow cadence, carryover,
 * print template, dashboard tile.
 */
class PdpModule implements ModuleInterface {

    public function getName(): string { return 'pdp'; }

    public function register( Container $container ): void {}

    public function boot( Container $container ): void {
        add_action( 'init', [ self::class, 'ensureCapabilities' ] );

        SeasonsRestController::init();
        PdpBlocksRestController::init();
        PdpFilesRestController::init();
        PdpConversationsRestController::init();
        PdpVerdictsRestController::init();
        PdpPrepRestController::init();

        // Sprint 2 — wp-admin Seasons page + carryover hook + print route.
        if ( is_admin() ) SeasonsPage::init();
        SeasonCarryover::init();
        PdpPrintRouter::init();

        // #1294 — wp-admin double-confirm hard-delete handler. Hooks
        // `admin_post_tt_pdp_permanent_delete`; the matching surface
        // lives in `FrontendPdpManageView::renderPermanentDeleteForm`.
        PdpHardDeleteAdminPost::init();

        // Register workflow templates. Same priority as WorkflowModule's
        // registerShippedTemplates so dispatchers (priority 20) see them.
        add_action( 'init', [ self::class, 'registerWorkflowTemplates' ], 5 );

        // #1852 — self-review nudge sweep. Runs on the workflow engine's
        // own cron tick (reusing its scheduler, not an ad-hoc wp_cron):
        // creates one self-review task per conversation whose planning
        // window has opened. Idempotent, so firing each hourly tick is safe.
        add_action( CronDispatcher::TICK_HOOK, [ PdpSelfReviewSweep::class, 'run' ] );
    }

    /**
     * Idempotent capability assignment.
     *
     *   tt_view_pdp         — coaches + admins + observers; players + parents
     *                         see their own scope via the matrix.
     *   tt_edit_pdp         — coaches + admins (own teams via matrix scope).
     *   tt_edit_pdp_verdict — head of academy + head coach + admin.
     */
    public static function ensureCapabilities(): void {
        $view   = 'tt_view_pdp';
        $edit   = 'tt_edit_pdp';
        $edit_v = 'tt_edit_pdp_verdict';

        $view_roles    = [ 'administrator', 'tt_head_dev', 'tt_club_admin', 'tt_coach', 'tt_readonly_observer', 'tt_player', 'tt_parent' ];
        $edit_roles    = [ 'administrator', 'tt_head_dev', 'tt_club_admin', 'tt_coach' ];
        $verdict_roles = [ 'administrator', 'tt_head_dev', 'tt_club_admin', 'tt_coach' ];

        foreach ( $view_roles as $r ) {
            $role = get_role( $r );
            if ( $role && ! $role->has_cap( $view ) ) $role->add_cap( $view );
        }
        foreach ( $edit_roles as $r ) {
            $role = get_role( $r );
            if ( $role && ! $role->has_cap( $edit ) ) $role->add_cap( $edit );
        }
        foreach ( $verdict_roles as $r ) {
            $role = get_role( $r );
            if ( $role && ! $role->has_cap( $edit_v ) ) $role->add_cap( $edit_v );
        }
    }

    /**
     * Register PDP workflow templates with the shared TemplateRegistry.
     * Templates are stubs in Sprint 1; Sprint 2 wires the cadence + form.
     */
    public static function registerWorkflowTemplates(): void {
        if ( ! class_exists( WorkflowModule::class ) ) return;
        $registry = WorkflowModule::registry();
        $registry->register( new PdpConversationDueTemplate() );
        $registry->register( new PdpVerdictDueTemplate() );
        $registry->register( new PdpSelfReviewTemplate() );
    }
}
