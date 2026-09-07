<?php
namespace TT\Shared\Frontend;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Domain\Vocabularies\Lookups\ActivityStatusKey;
use TT\Domain\Vocabularies\Lookups\ActivityTypeKey;
use TT\Infrastructure\Query\LabelTranslator;
use TT\Infrastructure\Query\LookupTranslator;
use TT\Infrastructure\Query\QueryHelpers;
use TT\Infrastructure\Security\AuthorizationService;
use TT\Shared\Frontend\Components\DateInputComponent;
use TT\Shared\Frontend\Components\FilterBar;
use TT\Shared\Frontend\Components\FormSaveButton;
use TT\Shared\Frontend\Components\GuestAddModal;
use TT\Shared\Frontend\Components\RecordLink;
use TT\Shared\Frontend\Components\TeamPickerComponent;

/**
 * FrontendActivitiesManageView — full-CRUD frontend for training activities.
 *
 * #0019 Sprint 2 session 2.3. Replaces the v3.0.0 placeholder
 * `FrontendSessionsView` (which only rendered a create form). Three
 * modes selected via query string:
 *
 *   ?tt_view=activities               — list (FrontendListTable) + Create CTA
 *   ?tt_view=activities&action=new    — create form
 *   ?tt_view=activities&id=<int>      — edit form (loads existing row + attendance)
 *
 * Saves go through the REST endpoints introduced in Sprint 1
 * (`POST/PUT /activities`, attendance handled inline). Delete is
 * wired as a row action on the list view, hitting
 * `DELETE /activities/{id}` which the controller treats as a soft-
 * archive: `archived_at` gets set rather than the row being removed.
 *
 * Bulk attendance + mobile pagination behaviour lives in
 * `assets/js/components/attendance.js` (loaded by DashboardShortcode).
 *
 * #0037 — guest section now renders on create AND edit. On create,
 * the "+ Add guest" button auto-saves the activity first, redirects
 * to the edit URL with `&open_guest=1`, and re-opens the modal so the
 * coach can pick a guest in one fluid flow.
 */
class FrontendActivitiesManageView extends FrontendViewBase {

    private static bool $activities_css_enqueued = false;

    protected static function enqueueAssets(): void {
        parent::enqueueAssets();
        if ( self::$activities_css_enqueued ) return;
        // #0056 Sprint D — pilot mobile-first stylesheet for the
        // activities surface. Owns the responsive treatment of the
        // attendance table (was a max-width block in frontend-admin.css).
        wp_enqueue_style(
            'tt-frontend-activities-manage',
            TT_PLUGIN_URL . 'assets/css/frontend-activities-manage.css',
            // 2026 alignment (#1695) — also depend on app-chrome so the
            // green/gold brand tokens load before this sheet's alias block.
            [ 'tt-frontend-mobile', 'tt-frontend-app-chrome' ],
            TT_VERSION
        );
        // #2831 — the O/A/V principle pill, shared with match prep and the
        // printed team sheet.
        \TT\Modules\Methodology\Frontend\PrinciplePills::enqueue();
        self::$activities_css_enqueued = true;
    }

    public static function render( int $user_id, bool $is_admin ): void {
        self::enqueueAssets();

        $action = isset( $_GET['action'] ) ? sanitize_key( (string) $_GET['action'] ) : '';
        $id     = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

        // v3.92.1 — breadcrumb chain. The view renders four shapes
        // depending on `$action` / `$id` (list / new / detail / edit);
        // the chain reflects whichever the user is on.
        $current = __( 'Activities', 'talenttrack' );
        $intermediate = null;
        if ( $action === 'new' ) {
            $current = __( 'New activity', 'talenttrack' );
            $intermediate = [ \TT\Shared\Frontend\Components\FrontendBreadcrumbs::viewCrumb( 'activities', __( 'Activities', 'talenttrack' ) ) ];
        } elseif ( $id > 0 && $action === 'edit' ) {
            $current = __( 'Edit activity', 'talenttrack' );
            $intermediate = [ \TT\Shared\Frontend\Components\FrontendBreadcrumbs::viewCrumb( 'activities', __( 'Activities', 'talenttrack' ) ) ];
        } elseif ( $id > 0 ) {
            $current = __( 'Activity detail', 'talenttrack' );
            $intermediate = [ \TT\Shared\Frontend\Components\FrontendBreadcrumbs::viewCrumb( 'activities', __( 'Activities', 'talenttrack' ) ) ];
        }
        \TT\Shared\Frontend\Components\FrontendBreadcrumbs::fromDashboard( $current, $intermediate );

        if ( $action === 'new' ) {
            self::renderHeader( __( 'New activity', 'talenttrack' ) );
            // #1371 — duplicate mode: `?duplicate_from=<id>` pre-fills
            // the create form from an existing activity (title, time,
            // location, type, principles — NOT attendance/evals). The
            // user confirms in the form; nothing is cloned blind.
            self::renderForm( $user_id, $is_admin, self::duplicatePrefill(), [], [] );
            return;
        }

        // v3.70.1 hotfix — `?tt_view=activities&id=N` (no action) now
        // renders a read-only detail view, matching how players / teams /
        // people detail-dispatch in DashboardShortcode. The edit form
        // requires `&action=edit` (so links from the list's row actions
        // open the form, but title clicks open the detail). This keeps
        // the link target consistent with other master-data lists and
        // unblocks academy admins / HoD who don't qualify for the
        // player-only `my-activities` gate.
        if ( $id > 0 && $action === 'edit' ) {
            $session    = self::loadSession( $id );
            $attendance = $session ? self::loadAttendance( $id ) : [];
            $guests     = $session ? self::loadGuests( $id )     : [];
            self::renderHeader( $session ? sprintf( __( 'Edit activity — %s', 'talenttrack' ), (string) $session->title ) : __( 'Activity not found', 'talenttrack' ) );
            if ( ! $session ) {
                echo '<p class="tt-notice">' . esc_html__( 'That activity no longer exists.', 'talenttrack' ) . '</p>';
                return;
            }
            self::renderForm( $user_id, $is_admin, $session, $attendance, $guests );
            return;
        }

        if ( $id > 0 ) {
            // #2183 — the read-only detail resolves archived activities too, so
            // an archived row renders (with a Restore header action) instead of
            // reading as "not found". The edit branch above deliberately keeps
            // the active-only loader: an archived activity must be restored
            // before it can be edited.
            $session = self::loadSessionForDetail( $id );
            // v3.110.53 — Edit + Archive page-header actions on the
            // activity detail page.
            // v3.110.97 — Continue rating action added. v3.110.96
            // filtered already-rated activities out of the wizard's
            // ActivityPicker (so the picker is for fresh runs only).
            // Coaches who want to add ratings to an already-rated
            // activity now use this CTA: deep-links into the
            // mark-attendance wizard with `activity_id` pre-seeded,
            // bypassing the picker and going straight through the
            // attendance step (force-rendered, pre-filled with the
            // saved roster) → confirm → rate-actors → review. The
            // rate step filters out players who already have an
            // eval row (see RateActorsStep::ratablePlayersForActivity)
            // so re-entry shows only the unrated set; no duplicate
            // eval rows.
            $detail_actions = [];
            // #1319 — matrix-aware cap check so Functional-Role-only
            // grants (no WP role) authorise the same way the REST
            // controller does.
            $current_uid    = get_current_user_id();
            $can_edit_acts  = AuthorizationService::userCanOrMatrix( $current_uid, 'tt_edit_activities' );
            // #2199 — Archive (soft-delete) + Restore are delete-class, gated
            // on the activities create/delete cap, so an assistant coach with
            // edit-only no longer sees them; a head coach (RCD) does.
            $can_delete_acts = AuthorizationService::userCanOrMatrix( $current_uid, 'tt_delete_activities' );
            if ( $session && ( $can_edit_acts || $can_delete_acts ) ) {
                $activities_list_url = add_query_arg( [ 'tt_view' => 'activities' ], \TT\Shared\Frontend\Components\RecordLink::dashboardUrl() );
                // #2183 — an archived activity is read-only until restored:
                // the mutating header actions (Edit / match prep / live match /
                // Continue rating) are suppressed, leaving only Restore.
                $is_archived = ! empty( $session->archived_at );
                // #2685 — status is read before the action list is built,
                // not halfway down it. Everything above used to be
                // status-blind, so a completed activity offered Edit, "Start
                // match" and "Continue rating" alongside Reopen. Planned is
                // the only status that may mutate the record; the rest get
                // read affordances, and Reopen is the single way back.
                $status_now   = (string) ( $session->activity_status_key ?? ActivityStatusKey::PLANNED );
                $is_planned   = ( $status_now === ActivityStatusKey::PLANNED );
                $is_completed = ( $status_now === ActivityStatusKey::COMPLETED );
                if ( ! $is_archived && $can_edit_acts ) {
                if ( $is_planned ) {
                    $edit_url = add_query_arg(
                        [ 'tt_view' => 'activities', 'id' => (int) $session->id, 'action' => 'edit' ],
                        \TT\Shared\Frontend\Components\RecordLink::dashboardUrl()
                    );
                    $detail_actions[] = [
                        'label'   => __( 'Edit', 'talenttrack' ),
                        'href'    => $edit_url,
                        'primary' => true,
                        'icon'    => \TT\Shared\Icons\IconRenderer::render( 'edit', [ 'width' => 16, 'height' => 16 ] ), // #1365 — inline SVG edit icon.
                    ];
                }
                // v3.110.214 (#838) — match preparation surface.
                // Only on match-type activities; jumps to the wizard if
                // no prep row exists, or directly to the form when it
                // does (FrontendMatchPrepView handles the redirect).
                $type_key = strtolower( (string) ( $session->activity_type_key ?? '' ) );
                if ( self::offersFixtureSurfaces( $type_key ) && $can_edit_acts ) {
                    // #1479 — carry the back-target so match prep can
                    // render the "← Back to <activity>" pill (CLAUDE.md §5).
                    $prep_url = \TT\Shared\Frontend\Components\BackLink::appendTo(
                        add_query_arg(
                            [
                                'tt_view'     => 'match-prep',
                                'activity_id' => (int) $session->id,
                            ],
                            \TT\Shared\Frontend\Components\RecordLink::dashboardUrl()
                        )
                    );
                    // #2685 — the label said "Plan match prep" whether or not
                    // a prep row existed, and kept saying it after the match
                    // was played. The destination was already right
                    // (FrontendMatchPrepView renders the filled form or its
                    // own empty state); only the copy lied. Same pattern as
                    // the training-run action below: ask the repository, then
                    // branch.
                    $prep_row = ( new \TT\Modules\MatchPrep\Repositories\MatchPrepRepository() )
                        ->findByActivity( (int) $session->id );
                    $detail_actions[] = [
                        'label' => \TT\Modules\Activities\Services\ActivityHeaderActions::matchPrepLabel(
                            $is_planned,
                            (bool) $prep_row
                        ),
                        'href'  => $prep_url,
                        // #2830 — one of the four on a planned match: it is
                        // what a coach came here to do. Once the match is
                        // over the same button reads "view match prep", which
                        // is reference material, so it folds away.
                        'overflow' => ! $is_planned,
                    ];
                    // v3.110.216 (#847) — assistant coach live-match
                    // surface. Same gate as match prep (type=match/game
                    // + tt_edit_activities); the view re-checks that a
                    // match prep exists, refusing to launch otherwise.
                    // #1520 — make the live-match CTA state- and date-aware,
                    // consistent with the execution view's own gating (#1473):
                    //   not_started + match day  -> "Start match"
                    //   not_started + off day    -> hidden (no misleading CTA)
                    //   live                      -> "Resume match" (any day)
                    //   post-live                 -> "View match"   (any day)
                    // Shared match-day rule lives on MatchExecutionState so the
                    // button and the view can't drift.
                    $exec_row   = ( new \TT\Modules\MatchExecution\Repositories\MatchExecutionRepository() )->findByActivity( (int) $session->id );
                    $exec_state = $exec_row->state ?? \TT\Domain\Vocabularies\Enums\MatchExecutionState::NOT_STARTED;
                    $is_match_day = \TT\Domain\Vocabularies\Enums\MatchExecutionState::isMatchDay( (string) ( $session->session_date ?? '' ) );

                    // #2685 — the state machine above answers "how far has
                    // the match got", which on a completed or cancelled
                    // activity is no longer the question: "Start match" fell
                    // through whenever the date matched, so a finished match
                    // invited a second kick-off. The status branch lives in
                    // the resolver with the rest of them.
                    $exec_label = \TT\Modules\Activities\Services\ActivityHeaderActions::matchExecutionLabel(
                        $is_planned,
                        (string) $exec_state,
                        $is_match_day
                    );

                    if ( $exec_label !== null ) {
                        $exec_url = add_query_arg(
                            [
                                'tt_view'     => 'match-execution',
                                'activity_id' => (int) $session->id,
                            ],
                            \TT\Shared\Frontend\Components\RecordLink::dashboardUrl()
                        );
                        $detail_actions[] = [
                            'label'    => $exec_label,
                            'href'     => $exec_url,
                            // #2830 — folded. Starting a match is a real
                            // action, but it is offered on one day of the
                            // week and the header is read on all seven.
                            'overflow' => true,
                        ];
                    }

                    // #2704 — writing the match up. Only once it has been
                    // played: an analysis before kick-off is a prediction.
                    // Tournaments are excluded by the composer's own type
                    // gate (a tournament day is several games, and one
                    // analysis row per activity cannot say which), so the
                    // action does not render there rather than rendering
                    // and dead-ending — the failure #2686 describes.
                    // #2830 — and only once the activity itself is completed.
                    // `isReviewable()` reads *played* as "dated today or
                    // earlier", which is right for a match that finished this
                    // morning but also true of one kicking off tonight: the
                    // header offered "write the match analysis" beside
                    // "complete activity" on a match nobody had played. The
                    // status is the answer the rest of this header already
                    // uses, and the date rule stays underneath it for
                    // everything else that asks.
                    if ( $is_completed
                        && \TT\Modules\MatchAnalysis\Services\MatchAnalysisComposer::isReviewable( $session )
                        && \TT\Shared\Frontend\Components\CrossViewLink::allows( 'match-analysis' ) ) {
                        $has_analysis = ( new \TT\Modules\MatchAnalysis\Repositories\MatchAnalysisRepository() )
                            ->findByActivity( (int) $session->id );

                        // An analysis that exists is edited in place; only
                        // a first draft goes through the wizard.
                        $flat_url = \TT\Shared\Frontend\Components\BackLink::appendTo(
                            add_query_arg(
                                [
                                    'tt_view'     => 'match-analysis',
                                    'activity_id' => (int) $session->id,
                                ],
                                \TT\Shared\Frontend\Components\RecordLink::dashboardUrl()
                            )
                        );

                        $detail_actions[] = [
                            'label' => $has_analysis
                                ? __( 'Open the match analysis', 'talenttrack' )
                                : __( 'Write the match analysis', 'talenttrack' ),
                            'href'  => $has_analysis
                                ? $flat_url
                                : \TT\Shared\Wizards\WizardEntryPoint::urlFor(
                                    'match-analysis',
                                    $flat_url,
                                    [ 'activity_id' => (int) $session->id ]
                                ),
                            // #2830 — the primary thing to do with a completed
                            // match. It leads the row.
                            'primary' => true,
                        ];
                    }
                }
                // #2499 (epic #2493) — running a training plan. Trainings
                // only: a match has match prep and the live-match surface,
                // and a meeting has neither. The label carries the state
                // so the button does not lie about what is behind it —
                // "Run this training" for a fresh one, "Continue this training"
                // when a plan is already attached, which is what the REST
                // contract's 200-not-201 means in the UI (#2499).
                if ( $type_key === ActivityTypeKey::TRAINING
                    && \TT\Shared\Frontend\Components\CrossViewLink::allows( 'training-run' ) ) {
                    $run = ( new \TT\Modules\Training\Repositories\TrainingPlanRunsRepository() )
                        ->findForActivity( (int) $session->id );

                    // #2685 — a training that is over is read, not run. With
                    // a plan attached the button opens it; with none there is
                    // no plan to look at, and `?activity_id=` lands on the
                    // attach form — an invitation to start work on a finished
                    // record — so the resolver returns null and the action
                    // does not render at all.
                    $run_label = \TT\Modules\Activities\Services\ActivityHeaderActions::trainingRunLabel(
                        $is_planned,
                        (bool) $run
                    );
                    if ( $run_label !== null ) {
                    $run_url = \TT\Shared\Frontend\Components\BackLink::appendTo(
                        add_query_arg(
                            $run
                                ? [ 'tt_view' => 'training-run', 'id' => (int) $run->id ]
                                : [ 'tt_view' => 'training-run', 'activity_id' => (int) $session->id ],
                            \TT\Shared\Frontend\Components\RecordLink::dashboardUrl()
                        )
                    );

                    $detail_actions[] = [
                        'label' => $run_label,
                        'href'  => $run_url,
                        // #2830 — same rule as match prep: running the
                        // training is the point while it is planned; once it
                        // is over the button reads "view this training" and
                        // is reference material.
                        'overflow' => ! $is_planned,
                    ];
                    } // end $run_label !== null
                }
                // #2245 — status transition buttons replace the status
                // dropdown on the edit form. The type-branch decision
                // (wizard vs. match-execution) lives in the domain-layer
                // resolver; the detail button and the list-card button
                // both use it. Cancel / Reopen are direct, confirmed
                // status changes via POST /activities/{id}/status.
                // $status_now / $is_planned / $is_completed are computed
                // above the action list (#2685).
                $detail_back  = \TT\Shared\Frontend\Components\RecordLink::detailUrlFor( 'activities', (int) $session->id );
                $status_rest  = 'activities/' . (int) $session->id . '/status';

                // #2401 — seed the grid deep-link's team + date from the row
                // already loaded, so the completion resolver and the grid
                // buttons below cost no extra reads.
                \TT\Modules\Activities\Services\ActivityGridLink::primeAnchor(
                    (int) $session->id,
                    (int) ( $session->team_id ?? 0 ),
                    (string) ( $session->session_date ?? '' )
                );
                $wizard_on = \TT\Modules\Activities\Services\ActivityCompletionResolver::wizardAvailable( get_current_user_id() );

                if ( $is_planned ) {
                    // §7 (#2325) — only surface "Complete activity" when the
                    // user can actually reach the completion flow; otherwise
                    // completionUrl() resolves to an empty href and the button
                    // silently dead-clicks. Cancel stays available regardless.
                    if ( \TT\Modules\Activities\Services\ActivityCompletionResolver::canComplete(
                        (int) $session->id,
                        (string) ( $session->activity_type_key ?? '' ),
                        get_current_user_id()
                    ) ) {
                        $complete_url = \TT\Modules\Activities\Services\ActivityCompletionResolver::completionUrl(
                            (int) $session->id,
                            (string) ( $session->activity_type_key ?? '' ),
                            $detail_back
                        );
                        $detail_actions[] = [
                            // #2401 — reads "Mark attendance" on the wizard-off
                            // path, where the button leads to the grid rather
                            // than a flow that completes the activity.
                            'label'   => \TT\Modules\Activities\Services\ActivityCompletionResolver::completionLabel(
                                (int) $session->id,
                                (string) ( $session->activity_type_key ?? '' ),
                                get_current_user_id()
                            ),
                            'href'    => $complete_url,
                            'primary' => true,
                        ];
                    }
                    // #2407 — with the guided flow off, nothing else writes
                    // `completed`: the grids record attendance and minutes in
                    // bulk but never touch activity status, so a wizard-off
                    // academy accumulated activities stuck at `planned`
                    // forever. This is the explicit flip, mirroring the
                    // Cancel / Reopen mechanism (POST /activities/{id}/status,
                    // which accepts `completed` only while the wizard is off).
                    // Not offered when the wizard is on — completion belongs to
                    // the wizard's final save there, and a second path could
                    // complete an activity with no attendance recorded.
                    // `tt_edit_activities` comes from the enclosing block.
                    if ( ! $wizard_on ) {
                        $detail_actions[] = [
                            'label'      => __( 'Mark completed', 'talenttrack' ),
                            'variant'    => 'secondary',
                            'data_attrs' => [
                                'tt-archive-rest-path'     => $status_rest,
                                'tt-archive-method'        => 'POST',
                                'tt-archive-body'          => wp_json_encode( [ 'status' => ActivityStatusKey::COMPLETED ] ),
                                'tt-archive-confirm'       => __( 'Mark this activity completed? Record attendance first if you have not — you can reopen it later.', 'talenttrack' ),
                                'tt-archive-confirm-label' => __( 'Mark completed', 'talenttrack' ),
                                'tt-archive-confirm-title' => __( 'Mark activity completed', 'talenttrack' ),
                                'tt-archive-redirect'      => $detail_back,
                            ],
                        ];
                    }
                    $detail_actions[] = [
                        'label'      => __( 'Cancel activity', 'talenttrack' ),
                        'variant'    => 'secondary',
                        'data_attrs' => [
                            'tt-archive-rest-path'     => $status_rest,
                            'tt-archive-method'        => 'POST',
                            'tt-archive-body'          => wp_json_encode( [ 'status' => ActivityStatusKey::CANCELLED ] ),
                            'tt-archive-confirm'       => __( 'Cancel this activity? It will be marked cancelled; you can reopen it later.', 'talenttrack' ),
                            'tt-archive-confirm-label' => __( 'Cancel activity', 'talenttrack' ),
                            'tt-archive-confirm-title' => __( 'Cancel activity', 'talenttrack' ),
                            'tt-archive-redirect'      => $detail_back,
                        ],
                    ];
                } else {
                    // Completed or cancelled → Reopen (→ planned).
                    // #2830 — folded. Undoing a status is a correction, and a
                    // correction offered as prominently as the thing it
                    // undoes invites the mistake.
                    $detail_actions[] = [
                        'label'      => __( 'Reopen', 'talenttrack' ),
                        'overflow'   => true,
                        'data_attrs' => [
                            'tt-archive-rest-path'     => $status_rest,
                            'tt-archive-method'        => 'POST',
                            'tt-archive-body'          => wp_json_encode( [ 'status' => ActivityStatusKey::PLANNED ] ),
                            'tt-archive-confirm'       => __( 'Reopen this activity? It returns to planned.', 'talenttrack' ),
                            'tt-archive-confirm-label' => __( 'Reopen', 'talenttrack' ),
                            'tt-archive-confirm-title' => __( 'Reopen activity', 'talenttrack' ),
                            'tt-archive-variant'       => 'primary',
                            'tt-archive-redirect'      => $detail_back,
                        ],
                    ];
                }

                // v3.110.97 — rating. Only on completed activities
                // (attendance + rating only make sense after the session
                // happened). Cap-gated on the evaluation wizard's
                // `tt_edit_evaluations`.
                //
                // #2685 — the label used to be a fixed "Continue rating" on
                // every completed activity, including ones where nobody had
                // been rated yet, and it stayed there once everybody had.
                // `completed` says nothing about ratings: the wizard's rate
                // step is per-player skippable, match finalize writes minutes
                // only, and the wizard-off "Mark completed" path is a bare
                // status flip. The progress service answers the three states;
                // a fully-rated activity drops the CTA and keeps the
                // "Ratings grid" button below for review.
                if ( $is_completed && current_user_can( 'tt_edit_evaluations' ) ) {
                    $rate_label = \TT\Modules\Activities\Services\ActivityHeaderActions::ratingLabel(
                        \TT\Modules\Activities\Services\ActivityRatingProgress::state( (int) $session->id )
                    );
                    if ( $rate_label !== null ) {
                        $rate_url = \TT\Shared\Wizards\WizardEntryPoint::buildUrl(
                            'new-evaluation',
                            [
                                'mode'        => 'activity',
                                'activity_id' => (int) $session->id,
                                'restart'     => 1,
                            ]
                        );
                        $detail_actions[] = [
                            'label' => $rate_label,
                            'href'  => $rate_url,
                            // #2830 — folded. On a completed match the row
                            // carries the analysis and Archive; rating is the
                            // other half of the write-up and belongs beside
                            // the grids that do the same job in bulk.
                            'overflow' => true,
                        ];
                    }
                }

                // #2386 (epic #2381) — jump into the desktop grids for this
                // team, pre-filtered to this activity's date (one column), so a
                // coach can record/correct here in the bulk surface. tt_back is
                // carried so the grid's Cancel/back returns to this activity
                // (§5/§6). Each is hidden when its grid feature is off, and
                // gated by the enclosing tt_edit_activities check (§7 — same
                // gates the grid views + endpoints enforce). Minutes grid only
                // on minutes-bearing (match) types.
                // #2401 — URL building + the gate moved into ActivityGridLink
                // so this page, the list card and the completion resolver
                // can't drift apart on either (CLAUDE.md §4).
                // #2830 — the three grids are a group of related shortcuts
                // into a bulk surface, not three decisions. They fold into
                // the overflow menu and render as icons, so they read as one
                // cluster rather than three text buttons competing with
                // Complete and Cancel.
                $grid_uid = get_current_user_id();
                if ( \TT\Modules\Activities\Services\ActivityGridLink::canUseAttendance( (int) $session->id, $grid_uid ) ) {
                    $detail_actions[] = [
                        'label'     => __( 'Attendance grid', 'talenttrack' ),
                        'href'      => \TT\Modules\Activities\Services\ActivityGridLink::attendanceUrl( (int) $session->id ),
                        'icon'      => \TT\Shared\Icons\IconRenderer::render( 'check', [ 'width' => 20, 'height' => 20 ] ),
                        'icon_only' => true,
                        'overflow'  => true,
                    ];
                }
                if ( in_array( $type_key, [ 'match', ActivityTypeKey::GAME, ActivityTypeKey::TOURNAMENT ], true )
                    && \TT\Modules\Activities\Services\ActivityGridLink::canUseMinutes( (int) $session->id, $grid_uid ) ) {
                    $detail_actions[] = [
                        'label'     => __( 'Minutes + statistics', 'talenttrack' ),
                        'href'      => \TT\Modules\Activities\Services\ActivityGridLink::minutesUrl( (int) $session->id ),
                        'icon'      => \TT\Shared\Icons\IconRenderer::render( 'clock', [ 'width' => 20, 'height' => 20 ] ),
                        'icon_only' => true,
                        'overflow'  => true,
                    ];
                }
                // #2414 — the ratings grid for THIS activity (players ×
                // categories). Gated on the same cap + its own feature flag,
                // and deliberately not on `tt_wizards_enabled`: a wizard-off
                // academy needs this route more, not less (the mistake #2401
                // had to correct for the attendance path).
                if ( \TT\Modules\Activities\Services\ActivityGridLink::canUseRatings( (int) $session->id, $grid_uid ) ) {
                    $detail_actions[] = [
                        'label'     => __( 'Ratings grid', 'talenttrack' ),
                        'href'      => \TT\Modules\Activities\Services\ActivityGridLink::ratingsUrl( (int) $session->id ),
                        'icon'      => \TT\Shared\Icons\IconRenderer::render( 'rate-card', [ 'width' => 20, 'height' => 20 ] ),
                        'icon_only' => true,
                        'overflow'  => true,
                    ];
                }
                } // end ! $is_archived && $can_edit_acts (active-only edit actions)
                // #2183 — an already-archived activity offers Restore, not a
                // second Archive. Branch on the archive stamp: active rows keep
                // the DELETE Archive action; archived rows POST to the restore
                // endpoint and land the coach back on the (now active) record.
                // #2199 — Archive + Restore are delete-class: only surfaced to
                // a coach who can create/delete activities, not edit-only.
                if ( $can_delete_acts ) {
                if ( $is_archived ) {
                    $restore_redirect = add_query_arg(
                        [ 'tt_view' => 'activities', 'id' => (int) $session->id ],
                        \TT\Shared\Frontend\Components\RecordLink::dashboardUrl()
                    );
                    $detail_actions[] = [
                        'label'      => __( 'Restore', 'talenttrack' ),
                        'primary'    => true,
                        'data_attrs' => [
                            'tt-archive-rest-path'      => 'activities/' . (int) $session->id . '/restore',
                            'tt-archive-method'         => 'POST',
                            'tt-archive-confirm'        => __( 'Restore this activity? It returns to the active list.', 'talenttrack' ),
                            'tt-archive-confirm-label'  => __( 'Restore', 'talenttrack' ),
                            'tt-archive-confirm-title'  => __( 'Restore activity', 'talenttrack' ),
                            'tt-archive-variant'        => 'primary',
                            'tt-archive-redirect'       => $restore_redirect,
                        ],
                    ];
                } else {
                    $detail_actions[] = [
                        'label'   => __( 'Archive', 'talenttrack' ),
                        'variant' => 'danger',
                        // #2830 — on a planned activity, archiving is not one
                        // of the four things a coach came here to do, and a
                        // destructive-looking button beside Complete and
                        // Cancel is three ways to end an activity in a row.
                        // On a completed or cancelled one it IS the remaining
                        // action, so it leads.
                        'overflow' => $is_planned,
                        // #2871 — icon-only in the header row. The overflow
                        // menu still renders it with its label, where there
                        // is room and an icon alone would read as noise.
                        'icon_only' => ! $is_planned,
                        'data_attrs' => [
                            'tt-archive-rest-path' => 'activities/' . (int) $session->id,
                            'tt-archive-confirm'   => __( 'Archive this activity? It will be hidden but the data is preserved.', 'talenttrack' ),
                            'tt-archive-redirect'  => $activities_list_url,
                        ],
                    ];
                }
                } // end $can_delete_acts (Archive / Restore)
            }
            // #2438 — force a Spond re-sync from the activity itself. A stale
            // import is noticed here, not on the Spond settings page, and the
            // head coach who spots it already holds change authority on their
            // own team's connection — so this gates on TeamSpondAccess, the
            // same check the endpoint runs (CLAUDE.md §7), rather than on the
            // activity caps above.
            if ( $session ) {
                self::addSpondSyncAction( $detail_actions, $session );
            }
            self::renderHeader(
                $session ? (string) $session->title : __( 'Activity not found', 'talenttrack' ),
                self::pageActionsHtml( $detail_actions )
            );
            if ( ! $session ) {
                echo '<p class="tt-notice">' . esc_html__( 'That activity no longer exists.', 'talenttrack' ) . '</p>';
                return;
            }
            // #2479 — pinned identity. The page header scrolls away; this
            // keeps which activity you are in on screen while you work down
            // attendance and the session plan. Emits nothing under `classic`.
            \TT\Shared\Frontend\Components\RecordSpine::render( [
                'name'     => (string) $session->title,
                'initials' => \TT\Shared\Frontend\Components\RecordSpine::initials( (string) $session->title ),
                'status'   => (string) ( $session->status ?? '' ),
                'meta'     => ! empty( $session->session_date )
                    ? \TT\Shared\Dates\TTDate::date( (string) $session->session_date )
                    : '',
            ] );
            self::renderDetail( $session, $is_admin );
            return;
        }

        // Default: list view.
        // v3.110.53 — header-actions slot for + New activity.
        $list_base_url = remove_query_arg( [ 'action', 'id' ] );
        $page_actions = [];
        // #1319 — matrix-aware cap checks. ActivitiesRestController
        // already routes via userCanOrMatrix; render surfaces follow.
        $current_uid = get_current_user_id();

        // #2390 — list ↔ calendar display toggle, remembered per user. A
        // pure display switch (no nav affordance, §5); the calendar reuses
        // the Team Planner's read-only week grid.
        $view_mode     = ActivitiesViewMode::resolve( $current_uid );
        $toggle_to     = $view_mode === ActivitiesViewMode::CALENDAR ? ActivitiesViewMode::LIST : ActivitiesViewMode::CALENDAR;
        $toggle_label  = $view_mode === ActivitiesViewMode::CALENDAR
            ? __( 'List view', 'talenttrack' )
            : __( 'Calendar view', 'talenttrack' );
        $page_actions[] = [
            'label' => $toggle_label,
            'href'  => add_query_arg( 'view_mode', $toggle_to, remove_query_arg( 'view_mode', $list_base_url ) ),
        ];
        if ( AuthorizationService::userCanOrMatrix( $current_uid, 'tt_view_activities' ) ) {
            // #1047 — entry point to the dedicated match-executions
            // listing surface. Sits left of the primary "+ New
            // activity" CTA so the retrospective surface is reachable
            // without diving into individual activity-detail pages.
            $page_actions[] = [
                'label' => __( 'Match executions', 'talenttrack' ),
                'href'  => add_query_arg( [ 'tt_view' => 'match-executions' ], $list_base_url ),
            ];
        }
        if ( AuthorizationService::userCanOrMatrix( $current_uid, 'tt_edit_activities' ) ) {
            // #2382 (epic #2381) — entry point to the desktop attendance-entry
            // grid, the Excel-familiar alternative to the mark-attendance
            // wizard. Sits left of the primary CTA; hidden when the feature is
            // switched off. tt_back carries the list so the grid's Cancel/back
            // returns here (§5/§6).
            if ( \TT\Core\FeatureRegistry::isEnabled( 'attendance_grid' ) ) {
                $page_actions[] = [
                    'label' => __( 'Attendance grid', 'talenttrack' ),
                    // Already gated by the enclosing tt_edit_activities check +
                    // the attendance_grid feature toggle — the same gates the
                    // target view enforces (§7). tt-xview-ok escapes the grep.
                    'href'  => \TT\Shared\Frontend\Components\BackLink::appendTo(
                        add_query_arg( [ 'tt_view' => 'attendance-grid' ], $list_base_url ) /* tt-xview-ok */
                    ),
                ];
            }
            $flat_url = add_query_arg( [ 'tt_view' => 'activities', 'action' => 'new' ], $list_base_url );
            $page_actions[] = [
                'label'   => __( 'New activity', 'talenttrack' ),
                'href'    => \TT\Shared\Wizards\WizardEntryPoint::urlFor( 'new-activity', $flat_url ),
                'primary' => true,
                'icon'    => '+',
            ];
        }
        self::renderHeader( __( 'Activities', 'talenttrack' ), self::pageActionsHtml( $page_actions ) );

        if ( $view_mode === ActivitiesViewMode::CALENDAR ) {
            // #2390 — read-only week-grid calendar of the same activities,
            // reusing the Team Planner's condensed multi-team grid. Narrows
            // to one team when the list's ?team_id filter is set, else shows
            // every team the user can see (same scope as the list).
            $cal_team = isset( $_GET['team_id'] ) ? absint( $_GET['team_id'] ) : 0;
            // #2400 — carry the list's period / From-To window and type
            // filter across, so switching to the calendar keeps the scope
            // the coach was looking at instead of silently resetting it.
            // Same resolver the list uses, so the two can't disagree.
            $cal_window = self::resolveRequestWindow( current_time( 'Y-m-d', true ) );
            $cal_type   = isset( $_GET['activity_type_key'] ) ? sanitize_key( (string) $_GET['activity_type_key'] ) : '';
            echo \TT\Modules\Planning\Frontend\FrontendTeamPlannerView::renderReadOnlyCalendar( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- renderReadOnlyCalendar returns escaped HTML.
                $user_id,
                $cal_team,
                '4weeks',
                $cal_window['from'],
                $cal_window['to'],
                $cal_type
            );
            return;
        }

        self::renderList( $user_id, $is_admin );
    }

    /**
     * v3.70.1 hotfix — read-only activity detail. Keeps the surface
     * thin: the existing edit form remains the source of truth for
     * mutation; this just gives a clickable destination from list
     * cells without forcing the user into the form.
     *
     * @param object $session activity row from `loadSession`
     */
    private static function renderDetail( object $session, bool $is_admin ): void {
        $team_id    = (int) ( $session->team_id ?? 0 );
        $team_name  = (string) ( $session->team_name ?? '' );
        $type_key   = (string) ( $session->activity_type_key ?? ActivityTypeKey::TRAINING );
        $status_key = (string) ( $session->activity_status_key ?? ActivityStatusKey::PLANNED );

        // #1618 — a "match day" is a game-typed activity (current `game`
        // key or the legacy `match` value). Match days surface the
        // match-only fields (opponent / home-away / kick-off / formation
        // / line-up); training and other types use the date/time facts.
        $is_match = in_array( strtolower( $type_key ), [ ActivityTypeKey::GAME, 'match' ], true );

        $st     = (string) ( $session->start_time ?? '' );
        $et     = (string) ( $session->end_time   ?? '' );
        $window = $st !== '' ? substr( $st, 0, 5 ) . ( $et !== '' ? '–' . substr( $et, 0, 5 ) : '' ) : '';

        echo '<div class="tt-act-detail">';

        // #2220 — Grouping panel. Hero + stat strip + section cards +
        // audit footer live inside ONE tinted container so they read as a
        // single, deliberate group (three tonal layers: page → tinted
        // panel → white cards) even when only a couple of sections apply.
        echo '<div class="tt-act-panel">';

        // ---- Hero (de-elevated inside the panel) ------------------
        self::renderDetailHero( $session, $type_key, $status_key, $is_match, $team_id, $team_name, $window );

        // #2220 — compact key-numbers strip under the hero.
        self::renderDetailStatStrip( $session, $type_key, $status_key, $is_match );

        // ---- Facts strip ------------------------------------------
        self::renderDetailFacts( $session, $type_key, $status_key, $is_match, $window );

        // ---- Cards grid -------------------------------------------
        echo '<div class="tt-act-detail__grid">';

        // #1123 — Linked principles card.
        self::renderDetailPrinciplesCard( $session );

        // #1618 — Session/match notes card.
        $notes = (string) ( $session->notes ?? '' );
        if ( $notes !== '' ) {
            echo '<div class="tt-act-card-d">';
            echo '<div class="tt-act-card-d__head"><h3 class="tt-act-card-d__title">'
                . esc_html__( 'Notes', 'talenttrack' ) . '</h3></div>';
            echo '<div class="tt-act-card-d__body tt-act-card-d__body--prewrap">'
                . esc_html( $notes ) . '</div>';
            echo '</div>';
        }

        // #1618 — Match day: line-up card (Starting XI + Bench).
        if ( $is_match ) {
            self::renderLineupCard( $session );
        }

        // #1453 — Expected attendance card (planned roster).
        self::renderPlannedAttendance( $session );

        // v3.110.95 / #1618 — Attendance breakdown card on completed
        // activities (bar + legend + headline). Planned / cancelled
        // rows have no meaningful attendance.
        if ( $status_key === ActivityStatusKey::COMPLETED ) {
            self::renderAttendanceSummary( $session );
        }

        // #1324 — Tournament card for tournament-typed activities.
        if ( $type_key === ActivityTypeKey::TOURNAMENT ) {
            self::renderDetailTournamentBlock( $session );
        }

        echo '</div>'; // .tt-act-detail__grid

        // #2595 (epic #2589) — photos and video from this activity, plus
        // the control that tags the players in them. Outside the cards
        // grid because a gallery needs the full width; a two-column card
        // would give it three tiles a row on a desktop and one on a phone.
        self::renderMediaSection( $session );

        // v3.110.138 — evaluation-skipped notice (conditional). Kept as a
        // notice rather than a card so it reads as an inline status, per
        // the issue ("evaluation_skipped stays a conditional notice").
        self::renderEvalSkippedNotice( $session );

        // #1471 — created/changed audit footer (renders nothing for
        // pre-audit rows with no recorded author). #2221 — appends the
        // team's last Spond sync line for Spond-sourced activities.
        echo '<div class="tt-act-detail__audit">';
        \TT\Shared\Frontend\Components\AuditMeta::render( [
            'created_by' => isset( $session->created_by ) ? (int) $session->created_by : 0,
            'created_at' => (string) ( $session->created_at ?? '' ),
            'updated_by' => isset( $session->updated_by ) ? (int) $session->updated_by : 0,
            'updated_at' => (string) ( $session->updated_at ?? '' ),
        ] );
        self::renderSpondSyncLine( $session );
        echo '</div>';

        echo '</div>'; // .tt-act-panel

        echo '</div>'; // .tt-act-detail
    }

    /**
     * #2438 — "Sync team from Spond" header action on a Spond-sourced
     * activity, for anyone who may manage that team's Spond connection
     * (an academy admin globally, a head coach for their own team).
     *
     * The Spond API offers no per-event re-fetch — `SpondSync::syncTeam()`
     * re-pulls the team's whole calendar — so the label and the confirm
     * text say "team" rather than implying this one activity is refreshed.
     *
     * Rides the generic REST-action plumbing the status / archive buttons
     * use (`data-tt-archive-*`, generalised in #1555): confirm modal,
     * nonce'd POST, button disabled in flight, error surfaced, redirect
     * back to this activity. No new script.
     *
     * @param array<int,array<string,mixed>> $actions
     */
    private static function addSpondSyncAction( array &$actions, object $session ): void {
        if ( ! empty( $session->archived_at ) ) return;
        if ( strtolower( (string) ( $session->activity_source_key ?? '' ) ) !== 'spond' ) return;

        $team_id = (int) ( $session->team_id ?? 0 );
        if ( $team_id <= 0 ) return;
        if ( ! \TT\Modules\Spond\TeamSpondAccess::currentUserCanManage( $team_id ) ) return;

        $action = [
            'label'      => __( 'Sync team from Spond', 'talenttrack' ),
            'variant'    => 'secondary',
            // #2830 — a refresh icon in the overflow menu. Re-pulling a
            // calendar is maintenance: needed occasionally, never the reason
            // a coach opened the page.
            'icon'       => \TT\Shared\Icons\IconRenderer::render( 'refresh', [ 'width' => 20, 'height' => 20 ] ),
            'icon_only'  => true,
            'overflow'   => true,
            'data_attrs' => [
                'tt-archive-rest-path'     => 'teams/' . $team_id . '/spond/sync',
                'tt-archive-method'        => 'POST',
                'tt-archive-confirm'       => self::spondSyncConfirm( $session ),
                'tt-archive-confirm-label' => __( 'Sync now', 'talenttrack' ),
                'tt-archive-confirm-title' => __( 'Sync from Spond', 'talenttrack' ),
                'tt-archive-variant'       => 'primary',
                'tt-archive-redirect'      => \TT\Shared\Frontend\Components\RecordLink::detailUrlFor( 'activities', (int) $session->id ),
            ],
        ];

        // Ahead of Archive, so the destructive action keeps the last slot.
        foreach ( $actions as $i => $existing ) {
            if ( ( $existing['variant'] ?? '' ) === 'danger' ) {
                array_splice( $actions, $i, 0, [ $action ] );
                return;
            }
        }
        $actions[] = $action;
    }

    /**
     * Confirm copy for the Spond sync action. Always names the team-wide
     * scope; when the team synced moments ago it says so, which is the
     * cheapest guard against hammering Spond's API without disabling a
     * control a coach may legitimately want to press twice.
     */
    private static function spondSyncConfirm( object $session ): string {
        $synced_at = (string) ( $session->team_spond_last_sync_at ?? '' );
        if ( $synced_at !== '' ) {
            // The column is site-local (`current_time( 'mysql' )`), so
            // convert before comparing against a UTC-based time().
            $ts = strtotime( get_gmt_from_date( $synced_at ) . ' UTC' );
            if ( $ts && ( time() - $ts ) < MINUTE_IN_SECONDS ) {
                return __( 'This team synced with Spond less than a minute ago. Sync again? The whole team calendar is refreshed, not just this activity.', 'talenttrack' );
            }
        }
        return __( 'Pull this team\'s calendar from Spond now? The whole team calendar is refreshed, not just this activity.', 'talenttrack' );
    }

    /**
     * #2221 — team-level "last synced from Spond" line, shown only on
     * Spond-sourced activities. The timestamp is the TEAM's
     * `spond_last_sync_at` (tt_teams, migration 0041), resolved in the
     * repository; the label makes the team scope explicit so the freshness
     * claim stays honest (it is not a per-activity sync time). Renders
     * nothing for manual / generated activities or when the team has never
     * synced.
     */
    private static function renderSpondSyncLine( object $session ): void {
        if ( strtolower( (string) ( $session->activity_source_key ?? '' ) ) !== 'spond' ) return;
        $synced_at = (string) ( $session->team_spond_last_sync_at ?? '' );
        if ( $synced_at === '' ) return;

        echo '<p class="tt-act-detail__spond-sync">'
            . esc_html( sprintf(
                /* translators: %s = date/time the team was last synced from Spond */
                __( 'Team last synced from Spond: %s', 'talenttrack' ),
                \TT\Shared\Dates\TTDate::dateTime( $synced_at )
            ) )
            . '</p>';
    }

    /**
     * #2220 — compact stat strip under the hero. Match: Present ·
     * Substitutes · Match length. Training: Present · Duration. Numbers
     * are derived in the repository (CLAUDE.md §4); the view only lays
     * out the cells and skips any that have no value.
     */
    /**
     * #2595 (epic #2589) — media from this activity.
     *
     * This is where the polymorphic link table earns its keep. A coach
     * uploads three photos on the session and tags the players in frame
     * from the roster that is already on this page — one upload, four
     * records. Without it every photo would have to be uploaded once per
     * player, and the co-depiction model from D5 would have nothing to
     * describe.
     *
     * The roster comes from the activity's team, so the picker offers the
     * players who were plausibly there rather than the whole academy.
     */
    private static function renderMediaSection( object $session ): void {
        $activity_id = (int) $session->id;
        $user        = get_current_user_id();

        if ( ! \TT\Modules\Authorization\MatrixGate::canAnyScope(
            $user,
            \TT\Modules\Media\Authorization\MediaVisibilityService::ENTITY,
            \TT\Modules\Authorization\MatrixGate::READ
        ) ) {
            return;
        }

        $can_edit = ( new \TT\Modules\Media\Authorization\MediaVisibilityService() )
            ->canAttachTo( $user, \TT\Modules\Media\MediaEntityType::ACTIVITY, $activity_id );

        echo '<section class="tt-act-detail__media">';
        echo '<h3 class="tt-act-card-d__title">' . esc_html__( 'Media', 'talenttrack' ) . '</h3>';

        \TT\Shared\Frontend\Components\MediaGallery::render( [
            'entity_type'     => \TT\Modules\Media\MediaEntityType::ACTIVITY,
            'entity_id'       => $activity_id,
            'can_edit'        => $can_edit,
            'tag_players'     => $can_edit ? self::rosterForTagging( $session ) : [],
            'empty_headline'  => __( 'No photos or video from this activity', 'talenttrack' ),
            'empty_explainer' => __( 'Add a photo here and you can tag the players in it, so it appears on their profiles too without uploading it again.', 'talenttrack' ),
        ] );

        echo '</section>';
    }

    /**
     * Players the tag control offers: this activity's team roster.
     *
     * The answer itself lives in `MediaTagRoster` — REST needs the same
     * one, because an upload returns its rendered tile (#2742) and that
     * tile carries the tag control.
     *
     * @return array<int, string> player id => display name
     */
    private static function rosterForTagging( object $session ): array {
        return \TT\Modules\Media\MediaTagRoster::forActivity( $session );
    }

    private static function renderDetailStatStrip( object $session, string $type_key, string $status_key, bool $is_match ): void {
        $activity_id = (int) ( $session->id ?? 0 );
        $team_id     = (int) ( $session->team_id ?? 0 );
        if ( $activity_id <= 0 ) return;

        $explicit_len = isset( $session->match_length_minutes ) && $session->match_length_minutes !== null
            ? (int) $session->match_length_minutes
            : 0;

        $stats = ( new \TT\Modules\Activities\Repositories\ActivitiesRepository() )
            ->statStripForActivity(
                $activity_id,
                $team_id,
                $is_match,
                (string) ( $session->start_time ?? '' ),
                (string) ( $session->end_time ?? '' ),
                $explicit_len,
                // #2523 — the present cell only shows once the activity is
                // completed; the repository owns that decision (§4).
                $status_key
            );

        $cells = [];
        if ( $stats->present !== null && $stats->roster_size !== null ) {
            $cells[] = [
                (string) $stats->present . ' / ' . (string) $stats->roster_size,
                __( 'Present', 'talenttrack' ),
            ];
        }
        if ( $is_match ) {
            if ( $stats->substitutes !== null ) {
                $cells[] = [ (string) $stats->substitutes, __( 'Substitutes', 'talenttrack' ) ];
            }
            if ( $stats->match_length_minutes !== null ) {
                // Minutes rendered in football "90'" notation (locale-neutral).
                $cells[] = [
                    esc_html( (string) $stats->match_length_minutes . "'" ),
                    __( 'Match length', 'talenttrack' ),
                    true, // value already escaped
                ];
            }
        } elseif ( $stats->duration_minutes !== null ) {
            $cells[] = [
                esc_html( (string) $stats->duration_minutes . "'" ),
                __( 'Duration', 'talenttrack' ),
                true,
            ];
        }

        if ( count( $cells ) < 2 ) return; // a strip of one number isn't a strip

        $cols_cls = ' tt-act-stats--' . count( $cells );
        echo '<div class="tt-act-stats' . esc_attr( $cols_cls ) . '">';
        foreach ( $cells as $cell ) {
            $pre_escaped = isset( $cell[2] ) && $cell[2] === true;
            echo '<div class="tt-act-stat">';
            echo '<div class="tt-act-stat__num">' . ( $pre_escaped ? $cell[0] : esc_html( $cell[0] ) ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo '<div class="tt-act-stat__label">' . esc_html( $cell[1] ) . '</div>';
            echo '</div>';
        }
        echo '</div>';
    }

    /**
     * #1618 — A/B inline hero: type-coloured icon chip + title +
     * `date · time · team · location` sub-line + type/status pills.
     * Match days render the "Home team vs Opponent" title and a
     * kick-off / home-away sub-line. Action buttons stay in the page
     * header (rendered by render()); the hero is informational only.
     */
    private static function renderDetailHero(
        object $session,
        string $type_key,
        string $status_key,
        bool $is_match,
        int $team_id,
        string $team_name,
        string $window
    ): void {
        $colors = self::activityTypeColor( $type_key );
        $icon   = self::activityTypeIcon( $type_key );

        echo '<header class="tt-act-detail__hero">';
        // #2993 — an icon-set glyph rather than an emoji. It inherits
        // currentColor, so it works on the type's tinted ground and in a
        // print, which a colour emoji does not.
        echo '<div class="tt-act-detail__icon" style="background:' . esc_attr( $colors['bg'] ) . ';">' /* tt-inline-ok */
            . \TT\Shared\Icons\IconRenderer::render( $icon, [ 'width' => 20, 'height' => 20 ] ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — trusted SVG.
            . '</div>';
        echo '<div class="tt-act-detail__hero-main">';

        // Title — match days read "Team vs Opponent" when both known.
        $title    = (string) ( $session->title ?? '' );
        $opponent = (string) ( $session->opponent ?? '' );
        if ( $is_match && $team_name !== '' && $opponent !== '' ) {
            echo '<h2 class="tt-act-detail__name">'
                . esc_html( $team_name )
                . ' <span class="tt-act-detail__vs">' . esc_html__( 'vs', 'talenttrack' ) . '</span> '
                . esc_html( $opponent )
                . '</h2>';
        } else {
            echo '<h2 class="tt-act-detail__name">' . esc_html( $title !== '' ? $title : __( 'Activity', 'talenttrack' ) ) . '</h2>';
        }

        // Sub-line: date · time/kick-off · team (link) · location.
        $sub_parts = [];
        $sub_parts[] = esc_html( \TT\Shared\Dates\TTDate::date( (string) $session->session_date ) );
        if ( $is_match ) {
            $kick = (string) ( $session->kickoff_time ?? '' );
            if ( $kick === '' && $window !== '' ) $kick = $window;
            if ( $kick !== '' ) {
                $sub_parts[] = esc_html( sprintf( /* translators: %s = kick-off time */ __( 'Kick-off %s', 'talenttrack' ), substr( $kick, 0, 5 ) ) );
            }
        } elseif ( $window !== '' ) {
            $sub_parts[] = esc_html( $window );
        }
        if ( $team_name !== '' ) {
            if ( $team_id > 0 ) {
                $sub_parts[] = \TT\Shared\Frontend\Components\RecordLink::inline(
                    $team_name,
                    \TT\Shared\Frontend\Components\RecordLink::detailUrlForWithBack( 'teams', $team_id )
                );
            } else {
                $sub_parts[] = esc_html( $team_name );
            }
        }
        $location = (string) ( $session->location ?? '' );
        if ( $location !== '' ) $sub_parts[] = esc_html( $location );
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — parts pre-escaped above.
        echo '<p class="tt-act-detail__sub">' . implode( ' · ', $sub_parts ) . '</p>';

        // Pills — type (+ game subtype / other label) and status.
        echo '<p class="tt-act-detail__pills">';
        echo \TT\Infrastructure\Query\LookupPill::render( 'activity_type', $type_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $subtype = (string) ( $session->game_subtype_key ?? '' );
        if ( $is_match && $subtype !== '' ) {
            echo ' ' . \TT\Infrastructure\Query\LookupPill::render( 'game_subtype', $subtype ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        $other_label = (string) ( $session->other_label ?? '' );
        if ( $type_key === ActivityTypeKey::OTHER && $other_label !== '' ) {
            echo ' <span class="tt-pill" style="display:inline-block;padding:2px 10px;border-radius:999px;background:#5b6e75;color:#fff;font-size:11px;font-weight:600;">'
                . esc_html( $other_label ) . '</span>';
        }
        echo ' ' . \TT\Infrastructure\Query\LookupPill::render( 'activity_status', $status_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '</p>';

        // #2633 — the inline alert chip, in the record's own header. This is
        // the surface a coach reaches from the banner or the list, so it is
        // the last place the condition should still be visible before they
        // act on it. One subject, so the component's single-subject read is
        // the right cost here; a list primes instead.
        \TT\Shared\Frontend\Components\AlertChip::render(
            'activity',
            (int) ( $session->id ?? 0 ),
            [ 'class' => 'tt-act-detail__alert' ]
        );

        echo '</div>'; // hero-main
        echo '</header>';
    }

    /**
     * #1618 — facts strip. Training/other: Date · Time · Type · Status.
     * Match day: Opponent · Home/Away · Kick-off · Formation. Each cell
     * renders only when it has a value (no empty placeholders).
     */
    private static function renderDetailFacts(
        object $session,
        string $type_key,
        string $status_key,
        bool $is_match,
        string $window
    ): void {
        $cells = [];
        if ( $is_match ) {
            $opponent = (string) ( $session->opponent ?? '' );
            if ( $opponent !== '' ) $cells[] = [ __( 'Opponent', 'talenttrack' ), $opponent ];

            $home_away = strtolower( (string) ( $session->home_away ?? '' ) );
            if ( $home_away === 'home' ) {
                $cells[] = [ __( 'Home / Away', 'talenttrack' ), __( 'Home', 'talenttrack' ) ];
            } elseif ( $home_away === 'away' ) {
                $cells[] = [ __( 'Home / Away', 'talenttrack' ), __( 'Away', 'talenttrack' ) ];
            }

            $kick = (string) ( $session->kickoff_time ?? '' );
            if ( $kick === '' && $window !== '' ) $kick = $window;
            if ( $kick !== '' ) $cells[] = [ __( 'Kick-off', 'talenttrack' ), substr( $kick, 0, 5 ) ];

            $formation = (string) ( $session->formation ?? '' );
            if ( $formation !== '' ) $cells[] = [ __( 'Formation', 'talenttrack' ), $formation ];
        } else {
            $cells[] = [ __( 'Date', 'talenttrack' ), \TT\Shared\Dates\TTDate::date( (string) $session->session_date ) ];
            if ( $window !== '' ) $cells[] = [ __( 'Time', 'talenttrack' ), $window ];
            $type_label = (string) ( \TT\Infrastructure\Query\LabelTranslator::activityType( $type_key ) ?? '' );
            if ( $type_label !== '' ) $cells[] = [ __( 'Type', 'talenttrack' ), $type_label ];
            $status_label = \TT\Infrastructure\Query\LookupTranslator::byTypeAndName( 'activity_status', $status_key );
            if ( $status_label !== '' ) $cells[] = [ __( 'Status', 'talenttrack' ), $status_label ];

            // #3362 — where this training sits in the team's cycle. Absent
            // for a team with no cycle, which is a normal state.
            $cycle = self::cycleFactFor( $session );
            if ( $cycle !== '' ) $cells[] = [ __( 'Cycle', 'talenttrack' ), $cycle ];
        }
        if ( $cells === [] ) return;

        echo '<div class="tt-act-detail__facts">';
        foreach ( $cells as $cell ) {
            echo '<div class="tt-act-detail__fact">';
            echo '<div class="tt-act-detail__fact-k">' . esc_html( $cell[0] ) . '</div>';
            echo '<div class="tt-act-detail__fact-v">' . esc_html( $cell[1] ) . '</div>';
            echo '</div>';
        }
        echo '</div>';
    }

    /**
     * #3362 (epic #3354) — the per-training cycle override.
     *
     * Renders nothing unless the training already carries a stamp, i.e.
     * unless the team has a cycle: an override control on a form with no
     * cycle behind it would offer a choice that means nothing.
     *
     * This is deliberately NOT the same control as the cycle calendar's.
     * That one changes the week for the whole team and everything after
     * it; this one changes one training and nothing else. The help text
     * says so, because a coach will otherwise reach for the wrong one.
     */
    private static function renderCycleOverrideField( ?object $session ): void {
        if ( $session === null ) return;

        $state = (string) ( $session->vct_cycle_state ?? '' );
        if ( $state === '' ) return;
        if ( (string) ( $session->activity_type_key ?? '' ) !== \TT\Domain\Vocabularies\Lookups\ActivityTypeKey::TRAINING ) return;

        $manual  = (int) ( $session->vct_cycle_manual ?? 0 ) === 1;
        $week    = (int) ( $session->vct_cycle_week ?? 0 );
        $current = ! $manual ? 'auto' : ( $state === 'neutral' ? 'neutral' : 'week' );

        echo '<div class="tt-field">';
        echo '<label class="tt-field-label" for="tt-activity-cycle">' . esc_html__( 'Cycle week', 'talenttrack' ) . '</label>';
        echo '<select id="tt-activity-cycle" class="tt-input" name="vct_cycle_choice">';
        echo '<option value="auto"' . selected( $current, 'auto', false ) . '>'
            . esc_html__( 'Follow the cycle', 'talenttrack' ) . '</option>';
        echo '<option value="neutral"' . selected( $current, 'neutral', false ) . '>'
            . esc_html_x( 'Neutral', 'cycle week that is paused because the team plays', 'talenttrack' ) . '</option>';
        echo '<option value="week"' . selected( $current, 'week', false ) . '>'
            . esc_html__( 'A specific week', 'talenttrack' ) . '</option>';
        echo '</select>';

        echo '<label class="tt-field-label" for="tt-activity-cycle-week">'
            . esc_html__( 'Which week', 'talenttrack' ) . '</label>';
        echo '<input type="number" inputmode="numeric" id="tt-activity-cycle-week" class="tt-input"'
            . ' name="vct_cycle_week" min="1" max="12" value="' . esc_attr( $week > 0 ? (string) $week : '' ) . '">';

        echo '<p class="tt-field-hint">'
            . esc_html__( 'Changes this training only. To move the whole team\'s week, use the cycle calendar under VCT configuration.', 'talenttrack' )
            . '</p>';
        echo '</div>';
    }

    /**
     * #3362 (epic #3354) — where this training sits in the team's cycle.
     *
     * A neutral week says so *and why*: "Neutral — game this week". A bare
     * "Neutral" reads as a bug, and a coach who thinks the planner is buggy
     * stops trusting it. Returns '' when the team has no cycle, which is a
     * normal state rather than a missing value.
     */
    private static function cycleFactFor( object $session ): string {
        $state = (string) ( $session->vct_cycle_state ?? '' );
        if ( $state === '' ) return '';

        $manual = (int) ( $session->vct_cycle_manual ?? 0 ) === 1;

        if ( $state === 'neutral' ) {
            $label = _x( 'Neutral', 'cycle week that is paused because the team plays', 'talenttrack' );
            if ( $manual ) {
                return $label . ' — ' . __( 'set by hand', 'talenttrack' );
            }
            return $label . ' — ' . __( 'game this week', 'talenttrack' );
        }

        $week = (int) ( $session->vct_cycle_week ?? 0 );
        if ( $week <= 0 ) return '';

        $label = sprintf(
            /* translators: %d = the week's position within the team's cycle. */
            __( 'Week %d', 'talenttrack' ),
            $week
        );
        return $manual ? $label . ' — ' . __( 'set by hand', 'talenttrack' ) : $label;
    }

    /**
     * #1123 / #1618 — Linked principles card. O/A/V colour-coded pills,
     * each linking into the methodology browser. Renders nothing when
     * the Methodology module is absent or no principles are linked.
     */
    private static function renderDetailPrinciplesCard( object $session ): void {
        // #2831 — the pivot read, the title resolution and the O/A/V bucket
        // derivation moved to the domain layer, so match prep and the printed
        // sheets show the same list rather than each deriving it again.
        $principles = \TT\Modules\Methodology\Services\ActivityPrinciples::forActivity( (int) $session->id );
        if ( $principles === [] ) return;

        $base = \TT\Shared\Frontend\Components\RecordLink::dashboardUrl();
        $methodology_url = add_query_arg( [ 'tt_view' => 'methodology', 'mtab' => 'principles' ], $base );

        echo '<div class="tt-act-card-d tt-act-card-d--span2">';
        echo '<div class="tt-act-card-d__head">';
        echo '<h3 class="tt-act-card-d__title">' . esc_html__( 'Linked principles', 'talenttrack' ) . '</h3>';
        \TT\Shared\Frontend\Components\CrossViewLink::render( 'methodology', static function () use ( $methodology_url ): void {
            echo '<a class="tt-act-card-d__link" href="' . esc_url( $methodology_url ) . '">'
                . esc_html__( 'Methodology', 'talenttrack' ) . ' →</a>';
        } );
        echo '</div>';
        echo '<div class="tt-act-card-d__body">';
        echo \TT\Modules\Methodology\Frontend\PrinciplePills::render( $principles ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — escaped in the component.
        echo '</div>';
        echo '</div>';
    }

    /**
     * Does this activity type get the per-FIXTURE surfaces — match
     * preparation and the live-match screen?
     *
     * Matches and games only. #2253 added tournaments on the assumption of a
     * single-game day; #2686 took them back out, because multi-game days are
     * the common case at the younger age groups and `tt_match_prep` holds one
     * row per activity. A whole tournament would therefore get one
     * availability list, one line-up and one set of player goals — not merely
     * incomplete, but wrong, and silently so.
     *
     * Tournaments remain minutes-bearing: they use the minutes grid and the
     * per-player entry (#2159), which model a multi-game day correctly
     * because they are per player rather than per fixture.
     */
    private static function offersFixtureSurfaces( string $type_key ): bool {
        return in_array(
            strtolower( $type_key ),
            [ 'match', ActivityTypeKey::GAME ],
            true
        );
    }

    /**
     * #1618 — Match-day line-up card: Starting XI + Bench, each player
     * decorated with jersey + position played (preferred fallback).
     * Grouping happens in ActivitiesRepository (CLAUDE.md §4). Renders
     * nothing when no line-up has been captured yet.
     */
    private static function renderLineupCard( object $session ): void {
        $lineup = ( new \TT\Modules\Activities\Repositories\ActivitiesRepository() )
            ->lineupForActivity( (int) ( $session->id ?? 0 ) );
        if ( $lineup->starting === [] && $lineup->bench === [] ) return;

        // #2763 — the line-up spans the whole card grid. In a half-width
        // card it splits again into Starting XI | Bench, leaving each player
        // row a quarter of the page, and the name is what gives way: a bench
        // row carrying "CDM, CM, CAM" truncated to "#4 M...", which answers
        // nothing on the one screen a coach opens on match day.
        echo '<div class="tt-act-card-d tt-act-card-d--span2">';
        echo '<div class="tt-act-card-d__head"><h3 class="tt-act-card-d__title">'
            . esc_html__( 'Line-up', 'talenttrack' ) . '</h3></div>';
        echo '<div class="tt-act-card-d__body">';

        // #2232 — two-column layout: Starting XI | Bench side-by-side on
        // ≥768px, stacking to a single column on mobile. Each player
        // renders as a structured row (jersey · name · position chip).
        $render_group = static function ( string $heading, array $players, int $count ): void {
            if ( $players === [] ) return;
            echo '<div class="tt-act-lineup__col">';
            echo '<div class="tt-act-lineup__group">'
                . esc_html( $heading )
                . ' <span class="tt-act-lineup__count">' . esc_html( (string) $count ) . '</span>'
                . '</div>';
            echo '<ul class="tt-act-lineup__list">';
            foreach ( $players as $pl ) {
                $jersey   = (string) ( $pl->jersey ?? '' );
                $position = (string) ( $pl->position ?? '' );
                // Name uses the club's first-name convention (#2223):
                // first name + last initial (e.g. "Daan P.").
                $name = self::lineupShortName( (string) ( $pl->name ?? '' ) );

                echo '<li class="tt-act-lineup__player">';
                echo '<span class="tt-act-lineup__jersey">'
                    . ( $jersey !== '' ? esc_html( '#' . $jersey ) : '' )
                    . '</span>';
                echo '<span class="tt-act-lineup__name">' . esc_html( $name ) . '</span>';
                if ( $position !== '' ) {
                    echo '<span class="tt-act-lineup__pos">' . esc_html( $position ) . '</span>';
                }
                echo '</li>';
            }
            echo '</ul>';
            echo '</div>';
        };

        echo '<div class="tt-act-lineup">';
        $render_group( __( 'Starting XI', 'talenttrack' ), $lineup->starting, count( $lineup->starting ) );
        $render_group( __( 'Bench', 'talenttrack' ), $lineup->bench, count( $lineup->bench ) );
        echo '</div>';

        echo '</div>';
        echo '</div>';
    }

    /**
     * #2232/#2223 — club first-name convention for the line-up card:
     * first name + last initial (e.g. "Daan Portzgen" → "Daan P.").
     * Single-token names render as-is (no stray dot); empty degrades
     * to an empty string. Mirrors the match-execution pitch label so
     * coaches read players the way they speak on the sideline.
     */
    private static function lineupShortName( string $name ): string {
        $name = trim( $name );
        if ( $name === '' ) return '';

        $parts = preg_split( '/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY );
        if ( ! is_array( $parts ) || count( $parts ) === 0 ) return $name;

        $first = (string) $parts[0];
        if ( count( $parts ) === 1 ) return $first;

        $last    = (string) $parts[ count( $parts ) - 1 ];
        $initial = function_exists( 'mb_substr' )
            ? mb_strtoupper( mb_substr( $last, 0, 1, 'UTF-8' ), 'UTF-8' )
            : strtoupper( substr( $last, 0, 1 ) );

        return $initial === '' ? $first : $first . ' ' . $initial . '.';
    }

    /**
     * v3.110.138 — evaluation-skipped notice. When the coach marked
     * attendance and chose "Skip rating", the activity carries
     * `evaluation_skipped=1` and drops out of the eval-wizard picker.
     * Surface the state + a matrix-gated "Re-open for rating" button.
     */
    private static function renderEvalSkippedNotice( object $session ): void {
        $eval_skipped = (int) ( $session->evaluation_skipped ?? 0 );
        if ( $eval_skipped !== 1 ) return;

        echo '<div class="tt-notice tt-notice-info" style="margin:12px 0 0;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">';
        echo '<span>' . esc_html__( 'Rating skipped — this activity won\'t appear in the rating picker.', 'talenttrack' ) . '</span>';
        // #1319 — matrix-aware cap so FR-only operators can re-open.
        if ( AuthorizationService::userCanOrMatrix( get_current_user_id(), 'tt_edit_activities' ) ) {
            $aid = (int) $session->id;
            echo '<button type="button" class="tt-btn tt-btn-secondary" data-tt-reopen-rating="' . esc_attr( (string) $aid ) . '" data-tt-rest-path="activities/' . (int) $aid . '/evaluation-skipped">';
            echo esc_html__( 'Re-open for rating', 'talenttrack' );
            echo '</button>';
        }
        echo '</div>';
        ?>
        <script>
        (function () {
            var btn = document.querySelector('[data-tt-reopen-rating]');
            if ( ! btn ) return;
            btn.addEventListener('click', function () {
                var path = btn.getAttribute('data-tt-rest-path');
                btn.disabled = true;
                var tt = window.TT || {};
                var url = ( tt.rest_url || '/wp-json/talenttrack/v1/' ).replace(/\/+$/, '/') + path;
                fetch(url, {
                    method: 'PATCH',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': tt.rest_nonce || '' },
                    body: JSON.stringify({ skipped: 0 })
                }).then(function (r) {
                    if ( r.ok ) window.location.reload();
                    else { btn.disabled = false; btn.textContent = 'Retry'; }
                }).catch(function () { btn.disabled = false; });
            });
        })();
        </script>
        <?php
    }

    /**
     * #1618 — icon + colour keyed to the activity type, for the hero
     * chip. Falls back to the training treatment for unknown types.
     *
     * @return array{bg:string}
     */
    private static function activityTypeColor( string $type_key ): array {
        switch ( strtolower( $type_key ) ) {
            case ActivityTypeKey::GAME:
            case 'match':              return [ 'bg' => '#b3261e' ];
            case ActivityTypeKey::TOURNAMENT: return [ 'bg' => '#8a3b00' ];
            case ActivityTypeKey::MEETING:    return [ 'bg' => '#5b6e75' ];
            case ActivityTypeKey::OTHER:      return [ 'bg' => '#475569' ];
            case ActivityTypeKey::TRAINING:
            default:                   return [ 'bg' => '#1f5da8' ];
        }
    }

    /**
     * The icon-set name for an activity type (#2993).
     *
     * These were emoji until now, which is why the match icon read as
     * out of place: an OS-rendered colour glyph among outline SVGs. All
     * five moved together rather than only the one that was complained
     * about — a single SVG beside four emoji is a worse inconsistency
     * than five emoji were.
     *
     * Returns a name for `IconRenderer`, not markup, so callers decide
     * the size and escaping.
     */
    private static function activityTypeIcon( string $type_key ): string {
        switch ( strtolower( $type_key ) ) {
            case ActivityTypeKey::GAME:
            case 'match':                     return 'football';
            case ActivityTypeKey::TOURNAMENT: return 'trophy';
            case ActivityTypeKey::MEETING:    return 'clipboard';
            case ActivityTypeKey::OTHER:      return 'pin';
            case ActivityTypeKey::TRAINING:
            default:                          return 'training-cone';
        }
    }

    /**
     * v3.110.95 — Attendance summary on the read-only activity detail
     * page. Renders a "X / Y (Z%) Present" line plus per-status counts.
     * The percentage cell links to the edit form (`action=edit`), which
     * is the per-player attendance list with status marks — what the
     * user asked for.
     *
     * Uses the same predicate as the activities list-view SQL
     * (`ActivitiesRestController::list_sessions`) so the % shown here
     * matches the % in the list. Counts are grouped server-side in one
     * query; only attendance rows whose player is still on this team's
     * current active roster are included, mirroring v3.110.95's list-
     * view fix.
     */
    /**
     * #1453 — "Expected attendance" section on the activity detail
     * page. Lists the planned roster captured at activity creation
     * (the `record_type='expected'` rows from AttendanceRosterStep),
     * so a coach knows who to expect before the session. Guests are
     * tagged. Renders nothing when no planned roster was captured
     * (the "Set later" path), keeping the surface uncluttered.
     *
     * Composition only — the repository owns the query so REST and the
     * match-prep step read the same planned roster (CLAUDE.md §4).
     */
    private static function renderPlannedAttendance( object $session ): void {
        $activity_id = (int) ( $session->id ?? 0 );
        if ( $activity_id <= 0 ) return;

        $roster = ( new \TT\Modules\Activities\Repositories\ActivitiesRepository() )
            ->plannedRosterForActivity( $activity_id );
        if ( empty( $roster ) ) return;

        // #2248 — the plan now carries a per-player status (Expected /
        // Not coming / Maybe). Summarise the non-expected ones so a coach
        // scanning the card sees "2 not coming" at a glance.
        $not_coming = 0;
        $maybe      = 0;
        foreach ( $roster as $row ) {
            $s = strtolower( trim( (string) ( $row->status ?? '' ) ) );
            if ( $s === 'absent' )  $not_coming++;
            if ( $s === 'excused' ) $maybe++;
        }

        // #2248 — matrix-aware edit gate for the "Edit plan" link.
        $can_edit = AuthorizationService::userCanOrMatrix( get_current_user_id(), 'tt_edit_activities' );
        $edit_url = '';
        if ( $can_edit ) {
            $edit_url = \TT\Shared\Frontend\Components\BackLink::appendTo(
                add_query_arg(
                    [ 'tt_view' => 'activities', 'id' => $activity_id, 'action' => 'edit' ],
                    \TT\Shared\Frontend\Components\RecordLink::dashboardUrl()
                )
            );
        }

        echo '<div class="tt-act-card-d">';
        echo '<div class="tt-act-card-d__head"><h3 class="tt-act-card-d__title">'
            . esc_html(
                sprintf(
                    /* translators: %d = number of players expected at the activity */
                    _n( 'Expected attendance · %d player', 'Expected attendance · %d players', count( $roster ), 'talenttrack' ),
                    count( $roster )
                )
            )
            . '</h3>';
        if ( $edit_url !== '' ) {
            echo '<a class="tt-act-card-d__link" href="' . esc_url( $edit_url ) . '">'
                . esc_html__( 'Edit plan', 'talenttrack' ) . ' →</a>';
        }
        echo '</div>';
        if ( $not_coming > 0 || $maybe > 0 ) {
            $bits = [];
            if ( $not_coming > 0 ) {
                /* translators: %d = number of players marked not coming */
                $bits[] = sprintf( _n( '%d not coming', '%d not coming', $not_coming, 'talenttrack' ), $not_coming );
            }
            if ( $maybe > 0 ) {
                /* translators: %d = number of players marked maybe */
                $bits[] = sprintf( _n( '%d maybe', '%d maybe', $maybe, 'talenttrack' ), $maybe );
            }
            echo '<p class="tt-act-card-d__sub">' . esc_html( implode( ' · ', $bits ) ) . '</p>';
        }
        echo '<div class="tt-act-card-d__body">';
        foreach ( $roster as $row ) {
            $name     = (string) ( $row->name ?? '' );
            $is_guest = (int) ( $row->is_guest ?? 0 ) === 1;
            $s        = strtolower( trim( (string) ( $row->status ?? '' ) ) );
            $label    = $s === 'absent'  ? __( 'Not coming', 'talenttrack' )
                      : ( $s === 'excused' ? __( 'Maybe', 'talenttrack' ) : '' );
            echo '<span class="tt-act-rp">';
            echo esc_html( $name );
            if ( $is_guest ) {
                echo ' <span class="tt-act-rp__guest">' . esc_html__( 'Guest', 'talenttrack' ) . '</span>';
            }
            if ( $label !== '' ) {
                echo ' <span class="tt-act-rp__plan">' . esc_html( $label ) . '</span>';
            }
            echo '</span>';
        }
        echo '</div>';
        echo '</div>';
    }

    private static function renderAttendanceSummary( object $session ): void {
        $activity_id = (int) ( $session->id ?? 0 );
        $team_id     = (int) ( $session->team_id ?? 0 );
        if ( $activity_id <= 0 || $team_id <= 0 ) return;

        // #1618 — breakdown counts come from the repository, not the
        // view (CLAUDE.md §4). The view composes the bar + legend.
        $bd = ( new \TT\Modules\Activities\Repositories\ActivitiesRepository() )
            ->attendanceBreakdownForActivity( $activity_id, $team_id );

        $roster_size = (int) $bd->roster_size;
        if ( $roster_size === 0 ) return;

        $by_status = (array) $bd->by_status;
        $total     = (int) $bd->total;
        $present   = (int) $bd->present;
        $pct       = (int) $bd->pct;

        // #1319 — matrix-aware cap so FR-only operators see the Edit
        // toolbar action on the detail row.
        $can_edit = AuthorizationService::userCanOrMatrix( get_current_user_id(), 'tt_edit_activities' );
        $edit_url = '';
        if ( $can_edit ) {
            $edit_url = \TT\Shared\Frontend\Components\BackLink::appendTo(
                add_query_arg(
                    [ 'tt_view' => 'activities', 'id' => $activity_id, 'action' => 'edit' ],
                    \TT\Shared\Frontend\Components\RecordLink::dashboardUrl()
                )
            );
        }

        echo '<div class="tt-act-card-d tt-act-card-d--span2">';
        echo '<div class="tt-act-card-d__head">';
        echo '<h3 class="tt-act-card-d__title">' . esc_html__( 'Attendance', 'talenttrack' ) . '</h3>';
        if ( $edit_url !== '' ) {
            echo '<a class="tt-act-card-d__link" href="' . esc_url( $edit_url ) . '">'
                . esc_html__( 'Edit', 'talenttrack' ) . ' →</a>';
        }
        echo '</div>';
        echo '<div class="tt-act-card-d__body">';

        $headline = sprintf(
            /* translators: 1: present count, 2: roster size, 3: percentage 0-100 */
            __( '%1$d / %2$d present (%3$d%%)', 'talenttrack' ),
            $present, $roster_size, $pct
        );
        echo '<p class="tt-act-att__head">' . esc_html( $headline ) . '</p>';

        // Per-status breakdown — bar + legend. Status -> bar colour map
        // mirrors the mockup (present green / absent red / late amber /
        // excused grey / injured purple). Custom statuses fall back to a
        // neutral slate. Hidden when no rows recorded yet (total = 0).
        if ( $total > 0 ) {
            $palette = self::attendanceStatusPalette();
            $seeded = array_keys( $palette );

            // Ordered list of (key, label, count, colour) for present
            // bar segments + legend entries.
            $segments = [];
            foreach ( $seeded as $sk ) {
                $cnt = (int) ( $by_status[ $sk ] ?? 0 );
                $label = \TT\Infrastructure\Query\LabelTranslator::attendanceStatus( ucfirst( $sk ) );
                $segments[] = [ 'label' => $label, 'count' => $cnt, 'color' => $palette[ $sk ] ];
            }
            foreach ( $by_status as $sk => $cnt ) {
                if ( in_array( $sk, $seeded, true ) || $sk === '' || (int) $cnt === 0 ) continue;
                $segments[] = [ 'label' => ucfirst( (string) $sk ), 'count' => (int) $cnt, 'color' => '#64748b' ];
            }

            // Bar — width proportional to count / total. Only non-zero
            // segments contribute a bar slice.
            echo '<div class="tt-act-att__bar">';
            foreach ( $segments as $seg ) {
                if ( $seg['count'] <= 0 ) continue;
                $w = (int) round( ( $seg['count'] / $total ) * 100 );
                echo '<span style="width:' . (int) $w . '%; background:' . esc_attr( $seg['color'] ) . ';"></span>';
            }
            echo '</div>';

            // Legend — every segment (including zeroes) for the seeded
            // statuses so the operator reads the full composition.
            echo '<div class="tt-act-att__legend">';
            foreach ( $segments as $seg ) {
                echo '<span class="tt-act-att__legend-item">'
                    . '<i style="background:' . esc_attr( $seg['color'] ) . ';"></i>'
                    . esc_html( $seg['label'] ) . ' ' . (int) $seg['count']
                    . '</span>';
            }
            echo '</div>';

            // If recorded rows < current roster, surface the gap.
            if ( $total < $roster_size ) {
                $unrecorded = $roster_size - $total;
                echo '<p class="tt-act-att__warn">'
                    . esc_html( sprintf(
                        /* translators: %d: number of players without an attendance row */
                        _n(
                            '%d player on the current roster has no attendance row yet.',
                            '%d players on the current roster have no attendance row yet.',
                            $unrecorded,
                            'talenttrack'
                        ),
                        $unrecorded
                    ) )
                    . '</p>';
            }

            // #2371 — read-only per-player roster. The aggregate above
            // answers "how many"; this answers "who" without leaving for
            // the completion flow. Collapsible so the card stays compact
            // for large squads; native <details> gives keyboard + no-JS
            // behaviour for free. Editing lives on the edit form (§3
            // fallback) / the completion wizard, not here — a detail view
            // must not mutate.
            self::renderAttendanceRosterReadonly( $activity_id, (string) ( $session->plan_state ?? '' ) );
        } else {
            echo '<p class="tt-act-att__warn">'
                . esc_html__( 'No attendance recorded yet.', 'talenttrack' )
                . '</p>';
        }

        echo '</div>';
        echo '</div>';
    }

    /**
     * #2371 — the status → bar/pill colour map. Single source of truth for
     * the attendance legend, the read-only roster pills, and the edit-form
     * status options. Mirrors the mockup (present green / absent red / late
     * amber / excused grey / injured purple). Custom statuses outside this
     * set fall back to a neutral slate at the call site.
     *
     * @return array<string,string>
     */
    private static function attendanceStatusPalette(): array {
        return [
            'present' => '#2e7d4f',
            'absent'  => '#d63638',
            'late'    => '#d9a006',
            'excused' => '#9aa3a8',
            'injured' => '#7b53b6',
        ];
    }

    /**
     * #2371 — read-only per-player attendance roster for the detail view,
     * rendered inside a collapsed <details>. Guests are included (marked)
     * so the coach reads the full recorded picture. Each row: player name +
     * a status pill coloured from {@see attendanceStatusPalette()}, label
     * via LabelTranslator. Read-only by design — no inputs, no edit
     * affordance (the card head's Edit link is the edit path).
     */
    private static function renderAttendanceRosterReadonly( int $activity_id, string $plan_state = '' ): void {
        if ( $activity_id <= 0 ) return;

        // #2862 — on a COMPLETED activity show the recorded roster only.
        // The expected roster answered "who did we expect", which stops
        // being an interesting question once the register has been taken,
        // and listing both is what put every squad member on screen twice.
        // On a still-planned activity the expected roster is all there is,
        // so it stays — passing null there collapses any duplicate to one
        // row per player rather than filtering a half that does not exist.
        $record_type = strtolower( $plan_state ) === 'completed' ? 'actual' : null;

        $roster = ( new \TT\Modules\Activities\Repositories\ActivitiesRepository() )
            ->listRosterAttendance( $activity_id, true, $record_type );
        if ( empty( $roster ) ) return;

        $palette = self::attendanceStatusPalette();

        echo '<details class="tt-act-att__roster">';
        echo '<summary class="tt-act-att__roster-toggle">'
            . esc_html__( 'Show roster', 'talenttrack' ) . '</summary>';
        echo '<ul class="tt-act-att__roster-list">';
        foreach ( $roster as $row ) {
            $name = trim( (string) ( $row->first_name ?? '' ) . ' ' . (string) ( $row->last_name ?? '' ) );
            if ( $name === '' ) $name = '#' . (int) ( $row->player_id ?? 0 );
            $status_raw = (string) ( $row->status ?? '' );
            $status_key = strtolower( trim( $status_raw ) );
            $color      = $palette[ $status_key ] ?? '#64748b';
            $label      = $status_raw === ''
                ? esc_html__( 'Not recorded', 'talenttrack' )
                : \TT\Infrastructure\Query\LabelTranslator::attendanceStatus( ucfirst( $status_raw ) );
            $is_guest   = (int) ( $row->is_guest ?? 0 ) === 1;

            echo '<li class="tt-act-att__roster-row">';
            echo '<span class="tt-act-att__roster-name">' . esc_html( $name );
            if ( $is_guest ) {
                echo ' <span class="tt-act-att__roster-guest">' . esc_html__( 'Guest', 'talenttrack' ) . '</span>';
            }
            echo '</span>';
            echo '<span class="tt-act-att__pill" style="background:' . esc_attr( $color ) . ';">' /* tt-inline-ok */
                . esc_html( $label ) . '</span>';
            echo '</li>';
        }
        echo '</ul>';
        echo '</details>';
    }

    /**
     * List view — date-bucketed card list (v4.7.0, #973).
     *
     * Port of `.local-mockups/activity-list/index.html` to PHP-rendered
     * output. Replaces the v4.6.x `FrontendListTable` render (Date /
     * Title / Type / Status / Source / Team / Att.% table) with a
     * Today / This week / Next week / Later-this-month / Later card
     * stack plus a pinned-top Past toggle and a "Needs attention"
     * pseudo-bucket for past-planned activities the coach forgot to
     * close.
     *
     * Backend untouched: this method reads `tt_activities` directly via
     * a query that mirrors `ActivitiesRestController::list_sessions`'s
     * WHERE / scope rules (club_id, demo scope, head-coach team scope
     * for non-global-readers, archived filter, team_id filter). The
     * REST endpoint stays the canonical contract for non-WordPress
     * consumers per CLAUDE.md §4.
     *
     * URL state:
     *   ?team_id=N            — filter to one team (carries forward
     *                            existing dashboard-widget / team-detail
     *                            link targets).
     *   ?activity_type_key=X  — filter to one lookup-backed type.
     *   ?include_past=1       — expand the past block.
     */
    private static function renderList( int $user_id, bool $is_admin ): void {
        // URL-state -> sanitized filters.
        $team_filter = isset( $_GET['team_id'] ) ? absint( (string) $_GET['team_id'] ) : 0;
        $type_filter = isset( $_GET['activity_type_key'] ) ? sanitize_key( (string) $_GET['activity_type_key'] ) : '';
        $include_past = ! empty( $_GET['include_past'] );

        // #2185 — attendance-report drill-down: an optional player_id scopes
        // the list to activities the player has a recorded attendance row
        // for, and an explicit date_from / date_to window (Y-m-d) overrides
        // the period-derived window so the count on
        // FrontendAttendancePlayerReportView traces to these exact rows.
        $player_filter = isset( $_GET['player_id'] ) ? absint( (string) $_GET['player_id'] ) : 0;
        // A player drill-down lands from an attendance report whose window is
        // usually in the past, so the (otherwise-collapsed) past activities
        // are the whole point — expand them by default for this entry.
        if ( $player_filter > 0 ) {
            $include_past = true;
        }
        $date_from_override = isset( $_GET['date_from'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $_GET['date_from'] )
            ? sanitize_text_field( wp_unslash( (string) $_GET['date_from'] ) ) : '';
        $date_to_override = isset( $_GET['date_to'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $_GET['date_to'] )
            ? sanitize_text_field( wp_unslash( (string) $_GET['date_to'] ) ) : '';
        // #1862 — cancelled activities are hidden by default; the filter
        // checkbox opts back in.
        $show_cancelled = ! empty( $_GET['show_cancelled'] );

        // #1648 — quick period filter (this/next week, this/next month,
        // this season). Empty = the full forward agenda (default).
        $period_filter = isset( $_GET['period'] ) ? sanitize_key( (string) $_GET['period'] ) : '';
        if ( ! in_array( $period_filter, [ 'this_week', 'next_week', 'this_month', 'next_month', 'this_season' ], true ) ) {
            $period_filter = '';
        }

        // #1555 — Active / Archived / All status filter. 'active' (the
        // default) keeps the timeline as-is; 'archived' replaces the
        // buckets with a flat restore/delete list; 'all' shows the
        // active timeline with the archived list appended below it.
        $archived_view = \TT\Infrastructure\Archive\ArchiveRepository::sanitizeView(
            isset( $_GET['archived'] ) ? (string) $_GET['archived'] : 'active'
        );

        // Lookup-backed type options for the Type filter.
        $type_rows = QueryHelpers::get_lookups( 'activity_type' );

        // Team options — same source as the legacy filter, so dashboard
        // / team-detail links continue to land on the same scoped view.
        $team_options = TeamPickerComponent::filterOptions( $user_id, $is_admin );

        // Today (site timezone, GMT-stored value converted via
        // wp_timezone() per `current_time('Y-m-d', true)`).
        $today_str = current_time( 'Y-m-d', true );

        // Resolve the period filter to a [from,to] date window before the
        // query so the row set is scoped server-side. #2185 — an explicit
        // drill-down date_from/date_to overrides the period window.
        $window = self::periodWindow( $period_filter, $today_str );
        if ( $date_from_override !== '' || $date_to_override !== '' ) {
            $window = [
                'from' => $date_from_override !== '' ? $date_from_override : ( $window['from'] ?? '' ),
                'to'   => $date_to_override   !== '' ? $date_to_override   : ( $window['to']   ?? '' ),
            ];
        }

        // Pull the row set for THIS list — server-side query mirroring
        // the REST WHERE/scope so other surfaces (dashboard widgets,
        // team detail) reading the same rows stay consistent. #1555 — the
        // timeline buckets only ever hold active rows; archived rows go
        // to their own flat section, so each status is queried apart.
        $bucket_rows = ( $archived_view === 'archived' ) ? [] : self::loadActivitiesForList(
            $team_filter,
            $type_filter,
            $window['from'] ?? '',
            $window['to'] ?? '',
            $show_cancelled,
            'active',
            $player_filter
        );
        $archived_rows = ( $archived_view === 'active' ) ? [] : self::loadActivitiesForList(
            $team_filter,
            $type_filter,
            $window['from'] ?? '',
            $window['to'] ?? '',
            $show_cancelled,
            'archived',
            $player_filter
        );

        // Bucket the (active) rows.
        $buckets = self::bucketize( $bucket_rows, $today_str );

        // #2633 — resolve every open alert on this page in ONE query before
        // any card is built. `AlertChip::html()` will lazily read a single
        // subject it has not seen, which is right for a detail view and
        // catastrophic for a list: fifty unprimed cards would be fifty
        // queries. Priming here is what keeps the inline surface affordable,
        // and it is why the whole page's ids are collected first rather than
        // the chip being asked for row by row.
        $alert_subject_ids = [];
        foreach ( array_merge( $bucket_rows, $archived_rows ) as $alert_row ) {
            $alert_subject_ids[] = (int) ( $alert_row->id ?? 0 );
        }
        \TT\Shared\Frontend\Components\AlertChip::prime( 'activity', $alert_subject_ids );

        $past_total      = count( $buckets['past'] );
        $archived_total  = count( $archived_rows );

        // ---- HEADER + FILTERS ---------------------------------------
        echo '<div class="tt-act-surface" data-tt-act-surface>';

        // #2026 — the bespoke Team/Type form + period/status pill rows are
        // replaced by the shared, data-driven FilterBar component (epic
        // #2017 Phase 1). The query params and filtering behaviour are
        // unchanged: Team / Type / Show-cancelled live in the bar's GET
        // form (auto-submit), Period and archive-Status are link-based
        // options. The component owns chrome only; this view supplies the
        // options + active state (CLAUDE.md §4).
        $dash_url = \TT\Shared\Frontend\Components\RecordLink::dashboardUrl();

        // --- Team select options (id => name), with an "all" placeholder.
        $team_select_options = [];
        foreach ( $team_options as $tid => $tname ) {
            $team_select_options[ (string) (int) $tid ] = (string) $tname;
        }

        // --- Type select options (lookup name => translated label).
        $type_select_options = [];
        foreach ( $type_rows as $tr ) {
            $type_select_options[ (string) ( $tr->name ?? '' ) ] = (string) LookupTranslator::name( $tr );
        }

        // --- Period quick-windows (#1648). Link-based: each sets ?period=…
        // while preserving team / type / past / cancelled / tt_back state.
        $period_labels = [
            ''            => __( 'All', 'talenttrack' ),
            'this_week'   => __( 'This week', 'talenttrack' ),
            'next_week'   => __( 'Next week', 'talenttrack' ),
            'this_month'  => __( 'This month', 'talenttrack' ),
            'next_month'  => __( 'Next month', 'talenttrack' ),
            'this_season' => __( 'This season', 'talenttrack' ),
        ];
        $pill_base = [ 'tt_view' => 'activities' ];
        if ( $team_filter > 0 )      $pill_base['team_id']           = $team_filter;
        if ( $type_filter !== '' )   $pill_base['activity_type_key'] = $type_filter;
        if ( $include_past )         $pill_base['include_past']      = '1';
        if ( $show_cancelled )       $pill_base['show_cancelled']    = '1';
        // #2185 — carry the attendance drill-down scope so period / status
        // pills don't drop the player + date window.
        if ( $player_filter > 0 )    $pill_base['player_id']         = $player_filter;
        if ( $date_from_override !== '' ) $pill_base['date_from']    = $date_from_override;
        if ( $date_to_override   !== '' ) $pill_base['date_to']      = $date_to_override;
        if ( ! empty( $_GET['tt_back'] ) ) $pill_base['tt_back']     = (string) $_GET['tt_back'];

        $period_options = [];
        foreach ( $period_labels as $key => $label ) {
            $args = $pill_base;
            if ( $key !== '' ) $args['period'] = $key;
            $period_options[] = [
                'value'  => $key,
                'label'  => $label,
                'url'    => add_query_arg( $args, $dash_url ),
                'active' => ( $period_filter === $key ),
            ];
        }

        // --- Active / Archived status (#1555). Link-based, same base.
        // #2023 — "All" dropped: trashed rows never appear in per-entity
        // lists (the recycle bin is the only surface for them).
        $status_labels = [
            'active'   => __( 'Active', 'talenttrack' ),
            'archived' => __( 'Archived', 'talenttrack' ),
        ];
        $status_base = $pill_base;
        if ( $period_filter !== '' ) $status_base['period'] = $period_filter;
        $status_options = [];
        foreach ( $status_labels as $key => $label ) {
            $args = $status_base;
            // 'active' is the default — keep its URL clean (no param).
            if ( $key === 'active' ) {
                unset( $args['archived'] );
            } else {
                $args['archived'] = $key;
            }
            $status_options[] = [
                'value'  => $key,
                'label'  => $label,
                'url'    => add_query_arg( $args, $dash_url ),
                'active' => ( $archived_view === $key ),
                'dot'    => $key,
            ];
        }

        // --- Hidden fields the GET form must carry so the auto-submitting
        // Team / Type / Show-cancelled controls preserve the link-based
        // state (period / archived status / past / back-target).
        $hidden = [ 'tt_view' => 'activities' ];
        if ( $period_filter !== '' )       $hidden['period']       = $period_filter;
        if ( $archived_view !== 'active' ) $hidden['archived']     = $archived_view;
        if ( $include_past )               $hidden['include_past'] = '1';
        // #2185 — preserve the attendance drill-down scope across an
        // auto-submitting Team / Type / Show-cancelled change.
        if ( $player_filter > 0 )          $hidden['player_id']    = (string) $player_filter;
        if ( $date_from_override !== '' )  $hidden['date_from']    = $date_from_override;
        if ( $date_to_override   !== '' )  $hidden['date_to']      = $date_to_override;
        if ( ! empty( $_GET['tt_back'] ) ) $hidden['tt_back']      = (string) $_GET['tt_back'];

        // --- Active-count + summary chips for the mobile collapsed state.
        $active_count = 0;
        $chips = [];
        if ( $team_filter > 0 && isset( $team_select_options[ (string) $team_filter ] ) ) {
            $active_count++;
            $chips[] = $team_select_options[ (string) $team_filter ];
        }
        if ( $type_filter !== '' && isset( $type_select_options[ $type_filter ] ) ) {
            $active_count++;
            $chips[] = $type_select_options[ $type_filter ];
        }
        if ( $period_filter !== '' ) {
            $active_count++;
            $chips[] = (string) ( $period_labels[ $period_filter ] ?? '' );
        }
        if ( $archived_view !== 'active' ) {
            $active_count++;
            $chips[] = (string) ( $status_labels[ $archived_view ] ?? '' );
        }
        if ( $show_cancelled ) {
            $active_count++;
            $chips[] = __( 'Cancelled shown', 'talenttrack' );
        }

        // --- "Clear" target: the bare list with no filter params.
        $reset_args = [ 'tt_view' => 'activities' ];
        if ( ! empty( $_GET['tt_back'] ) ) $reset_args['tt_back'] = (string) $_GET['tt_back'];

        // #2834 — an inbound drill-down says what it narrowed to. The player,
        // the window and the type arrive as hidden form fields: honoured by
        // the query, invisible to the reader, so the list looked like an
        // ordinary list that happened to be short and a coach could not tell
        // which activities the number they clicked was counted from.
        self::renderDrillContext( $player_filter, $date_from_override, $date_to_override, $dash_url );

        FilterBar::render( [
            'hidden'       => $hidden,
            'active_count' => $active_count,
            'chips'        => $chips,
            'reset_url'    => add_query_arg( $reset_args, $dash_url ),
            // #2449 — personal saved views for the activities list.
            'saved_views'  => [ 'key' => 'activities-list' ],
            'groups'       => [
                [
                    'type'        => 'select',
                    'key'         => 'team',
                    'label'       => __( 'Team', 'talenttrack' ),
                    'name'        => 'team_id',
                    'selected'    => $team_filter > 0 ? (string) $team_filter : '',
                    'placeholder' => __( '— all teams —', 'talenttrack' ),
                    'options'     => $team_select_options,
                ],
                [
                    'type'        => 'select',
                    'key'         => 'type',
                    'label'       => __( 'Type', 'talenttrack' ),
                    'name'        => 'activity_type_key',
                    'selected'    => $type_filter,
                    'placeholder' => __( '— all types —', 'talenttrack' ),
                    'options'     => $type_select_options,
                ],
                [
                    'type'         => 'period',
                    'key'          => 'period',
                    'label'        => __( 'Period', 'talenttrack' ),
                    'active_label' => (string) ( $period_labels[ $period_filter ] ?? $period_labels[''] ),
                    'options'      => $period_options,
                ],
                // #2622 — the archive state collapses to the icon overflow
                // menu here too. This view builds its group by hand (its list
                // is server-rendered in date buckets, not a FrontendListTable),
                // so it opts in explicitly rather than through the shared
                // builder.
                [
                    'type'          => 'menu',
                    'key'           => 'archived',
                    'label'         => __( 'Archive', 'talenttrack' ),
                    'default_value' => 'active',
                    'options'       => $status_options,
                ],
                [
                    'type'      => 'toggle',
                    'key'       => 'cancelled',
                    'label'     => __( 'Cancelled', 'talenttrack' ),
                    'name'      => 'show_cancelled',
                    'on'        => $show_cancelled,
                    'on_label'  => __( 'Show', 'talenttrack' ),
                    'value'     => '1',
                    // #2201 — explicit OFF so turning the toggle back off
                    // submits show_cancelled=0 rather than omitting the param,
                    // clearing the flag even if it lingered in the URL.
                    'off_value' => '0',
                ],
            ],
        ] );

        // ---- EMPTY STATE --------------------------------------------
        $forward_total = $buckets['attention_count']
            + count( $buckets['today'] )
            + count( $buckets['this_week'] )
            + count( $buckets['next_week'] )
            + count( $buckets['later_this_month'] )
            + count( $buckets['later'] );

        $nothing_to_show = $archived_view === 'archived'
            ? ( $archived_total === 0 )
            : ( $forward_total === 0 && $past_total === 0 && $archived_total === 0 );
        if ( $nothing_to_show ) {
            // #1362 — guided empty state with a cap-gated create CTA
            // (same wizard entry point as the header's "New activity").
            // #1555 — the archived view gets a plain "nothing archived"
            // message instead of a create CTA (creating doesn't help).
            echo '<div class="tt-act-empty">';
            if ( $archived_view === 'archived' ) {
                \TT\Shared\Frontend\Components\EmptyStateCard::render( [
                    'icon'      => 'activities',
                    'headline'  => __( 'No archived activities', 'talenttrack' ),
                    'explainer' => __( 'Activities you archive will appear here, where you can restore them or delete them permanently.', 'talenttrack' ),
                ] );
            } else {
                \TT\Shared\Frontend\Components\EmptyStateCard::render( [
                    'icon'      => 'activities',
                    'headline'  => __( 'No activities to show', 'talenttrack' ),
                    'explainer' => __( 'Try changing the team or type filter, or create a new activity to get started.', 'talenttrack' ),
                    'cta_label' => __( 'Plan your first activity', 'talenttrack' ),
                    'cta_url'   => \TT\Shared\Wizards\WizardEntryPoint::urlFor(
                        'new-activity',
                        add_query_arg( [ 'tt_view' => 'activities', 'action' => 'new' ], remove_query_arg( [ 'action', 'id' ] ) )
                    ),
                    'cta_cap'   => 'tt_edit_activities',
                ] );
            }
            echo '</div>';
            echo '</div>'; // .tt-act-surface
            return;
        }

        // ---- TIMELINE (active rows) ---------------------------------
        // #1555 — the pure 'archived' view skips the timeline entirely and
        // renders only the flat archived list below.
        if ( $archived_view !== 'archived' ) {

        // ---- PAST, STILL OPEN (always visible by default) -----------
        // #2200 — activities that happened but were never closed off
        // (plan_state not completed/cancelled) are the coach's overdue
        // follow-up. They must be seen without extra clicks, so they render
        // as their own explicit section at the very top of the list — above
        // the collapsed "Past" toggle (completed/cancelled history), never
        // hidden behind it.
        if ( $buckets['attention_count'] > 0 ) {
            echo '<ul class="tt-act-list tt-act-list--attention">';
            self::renderBucket(
                'attention',
                __( 'Past — still open', 'talenttrack' ),
                $buckets['attention'],
                sprintf(
                    /* translators: %d: count of past activities not yet closed off */
                    _n( '%d not closed off', '%d not closed off', $buckets['attention_count'], 'talenttrack' ),
                    $buckets['attention_count']
                )
            );
            echo '</ul>';
        }

        // ---- PAST TOGGLE (completed / cancelled history) ------------
        if ( $past_total > 0 ) {
            self::renderPastToggle( $past_total, $include_past );
        }

        // ---- PAST BLOCK (when expanded) -----------------------------
        if ( $include_past && $past_total > 0 ) {
            echo '<ul class="tt-act-list tt-act-list--past" aria-label="' . esc_attr__( 'Past activities', 'talenttrack' ) . '">';
            echo '<li><div class="tt-act-bucket-head"><span>' . esc_html__( 'Past', 'talenttrack' ) . '</span>';
            echo '<span class="tt-act-bucket-head__count">' . esc_html( sprintf(
                /* translators: %d: count of past activities */
                _n( '%d activity', '%d activities', $past_total, 'talenttrack' ),
                $past_total
            ) ) . '</span></div></li>';
            foreach ( $buckets['past'] as $row ) {
                echo self::renderActivityCard( $row, 'past' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped within helper.
            }
            echo '</ul>';
        }

        // ---- FORWARD BUCKETS ----------------------------------------
        // #2200 — the past-but-open ("Past — still open") section now renders
        // above, as its own explicit block; the forward list holds only
        // today-and-later buckets.
        echo '<ul class="tt-act-list">';

        if ( $buckets['today'] ) {
            // Header: "Today · Wed 28 May" — day-of-week + date.
            $today_label = self::formatTodayHeader( $today_str );
            self::renderBucket(
                'today',
                $today_label,
                $buckets['today'],
                sprintf(
                    /* translators: %d: count of activities today */
                    _n( '%d activity', '%d activities', count( $buckets['today'] ), 'talenttrack' ),
                    count( $buckets['today'] )
                )
            );
        }

        if ( $buckets['this_week'] ) {
            self::renderBucket(
                'this-week',
                __( 'This week', 'talenttrack' ),
                $buckets['this_week'],
                sprintf(
                    /* translators: %d: count of activities */
                    _n( '%d activity', '%d activities', count( $buckets['this_week'] ), 'talenttrack' ),
                    count( $buckets['this_week'] )
                )
            );
        }

        if ( $buckets['next_week'] ) {
            self::renderBucket(
                'next-week',
                __( 'Next week', 'talenttrack' ),
                $buckets['next_week'],
                sprintf(
                    /* translators: %d: count of activities */
                    _n( '%d activity', '%d activities', count( $buckets['next_week'] ), 'talenttrack' ),
                    count( $buckets['next_week'] )
                )
            );
        }

        if ( $buckets['later_this_month'] ) {
            self::renderBucket(
                'later-this-month',
                __( 'Later this month', 'talenttrack' ),
                $buckets['later_this_month'],
                sprintf(
                    /* translators: %d: count of activities */
                    _n( '%d activity', '%d activities', count( $buckets['later_this_month'] ), 'talenttrack' ),
                    count( $buckets['later_this_month'] )
                )
            );
        }

        if ( $buckets['later'] ) {
            self::renderBucket(
                'later',
                __( 'Later', 'talenttrack' ),
                $buckets['later'],
                sprintf(
                    /* translators: %d: count of activities */
                    _n( '%d activity', '%d activities', count( $buckets['later'] ), 'talenttrack' ),
                    count( $buckets['later'] )
                )
            );
        }

        echo '</ul>';

        } // end timeline guard (#1555)

        // ---- ARCHIVED LIST ------------------------------------------
        // #1555 — flat archived section with per-row restore + gated
        // permanent-delete actions. Shown for the 'archived' and 'all'
        // status views.
        if ( $archived_total > 0 ) {
            $archived_redirect = add_query_arg( $status_base + [ 'archived' => 'archived' ], $dash_url );
            self::renderArchivedList( $archived_rows, $archived_redirect );
        }

        echo '</div>'; // .tt-act-surface
    }

    /**
     * #1555 — render the archived-activities section: a header plus one
     * card per archived row, each carrying a Restore and (cap-gated)
     * Delete-permanently action wired to the REST archive lifecycle.
     *
     * @param array<int,object> $rows
     */
    private static function renderArchivedList( array $rows, string $redirect_url ): void {
        $can_hard_delete = current_user_can( 'tt_edit_settings' );
        // #2199 — restore reverses an archive, so it is delete-class (matrix
        // `activities:create_delete`), consistent with the detail-header
        // Archive/Restore gate. Matrix-aware so Functional-Role-only grants
        // authorise the same way the REST controller does.
        $can_restore     = AuthorizationService::userCanOrMatrix( get_current_user_id(), 'tt_delete_activities' );

        echo '<ul class="tt-act-list tt-act-list--archived" aria-label="' . esc_attr__( 'Archived activities', 'talenttrack' ) . '">';
        echo '<li><div class="tt-act-bucket-head"><span>' . esc_html__( 'Archived', 'talenttrack' ) . '</span>';
        echo '<span class="tt-act-bucket-head__count">' . esc_html( sprintf(
            /* translators: %d: count of archived activities */
            _n( '%d activity', '%d activities', count( $rows ), 'talenttrack' ),
            count( $rows )
        ) ) . '</span></div></li>';

        foreach ( $rows as $row ) {
            $id = (int) ( $row->id ?? 0 );
            if ( $id <= 0 ) continue;

            echo self::renderActivityCard( $row, 'archived' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped within helper.

            // Action row, rendered as a sibling of the card link so the
            // buttons aren't nested inside the card's <a>.
            echo '<li class="tt-act-archived-actions">';
            if ( $can_restore ) {
                echo '<button type="button" class="tt-btn tt-btn-secondary tt-act-archived-actions__btn"'
                    . ' data-tt-archive-rest-path="' . esc_attr( 'activities/' . $id . '/restore' ) . '"'
                    . ' data-tt-archive-method="POST"'
                    . ' data-tt-archive-variant="primary"'
                    . ' data-tt-archive-confirm-label="' . esc_attr__( 'Restore', 'talenttrack' ) . '"'
                    . ' data-tt-archive-confirm-title="' . esc_attr__( 'Restore activity', 'talenttrack' ) . '"'
                    . ' data-tt-archive-confirm="' . esc_attr__( 'Restore this activity? It will reappear in the active list.', 'talenttrack' ) . '"'
                    . ' data-tt-archive-redirect="' . esc_url( $redirect_url ) . '">'
                    . esc_html__( 'Restore', 'talenttrack' ) . '</button>';
            }
            if ( $can_hard_delete ) {
                echo '<button type="button" class="tt-btn tt-btn-danger tt-act-archived-actions__btn"'
                    . ' data-tt-archive-rest-path="' . esc_attr( 'activities/' . $id . '/permanent' ) . '"'
                    . ' data-tt-archive-method="DELETE"'
                    . ' data-tt-archive-variant="danger"'
                    . ' data-tt-archive-confirm-label="' . esc_attr__( 'Delete permanently', 'talenttrack' ) . '"'
                    . ' data-tt-archive-confirm-title="' . esc_attr__( 'Delete activity', 'talenttrack' ) . '"'
                    . ' data-tt-archive-confirm="' . esc_attr__( 'Permanently delete this activity? This cannot be undone.', 'talenttrack' ) . '"'
                    . ' data-tt-archive-redirect="' . esc_url( $redirect_url ) . '">'
                    . esc_html__( 'Delete permanently', 'talenttrack' ) . '</button>';
            }
            echo '</li>';
        }
        echo '</ul>';
    }

    /**
     * Render a non-past bucket — header + cards.
     *
     * @param array<int,object> $rows
     */
    private static function renderBucket( string $bucket_key, string $title, array $rows, string $count_label ): void {
        $attention = $bucket_key === 'attention';
        $today     = $bucket_key === 'today';

        $cls = 'tt-act-bucket-head';
        if ( $attention ) $cls .= ' tt-act-bucket-head--attention';

        echo '<li><div class="' . esc_attr( $cls ) . '" data-bucket="' . esc_attr( $bucket_key ) . '">';
        // #1365 — attention bucket carries an inline-SVG warning icon
        // (was a glyph baked into the translated title).
        $title_icon = $attention
            ? \TT\Shared\Icons\IconRenderer::render( 'warning', [ 'width' => 13, 'height' => 13, 'style' => 'vertical-align:-2px;margin-right:4px;' ] )
            : '';
        echo '<span>' . $title_icon . esc_html( $title ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — icon is trusted SVG.
        echo '<span class="tt-act-bucket-head__count">' . esc_html( $count_label ) . '</span>';
        echo '</div></li>';

        $row_mode = $attention ? 'attention' : ( $today ? 'today' : 'future' );
        foreach ( $rows as $row ) {
            echo self::renderActivityCard( $row, $row_mode ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped within helper.
        }
    }

    /**
     * Render the past-toggle button. The form is a GET that flips
     * `include_past` between absent and `1`, preserving every other
     * existing query param.
     */
    private static function renderPastToggle( int $past_count, bool $include_past ): void {
        // Build the toggle URL — same query string, toggled include_past.
        $toggle_url = $include_past
            ? remove_query_arg( 'include_past' )
            : add_query_arg( 'include_past', '1' );

        // Carry the click via <a> so it's noscript-resilient and a
        // proper 48px tap target.
        $count_text = esc_html( sprintf(
            /* translators: %d: count of past activities */
            _n( '%d past activity', '%d past activities', $past_count, 'talenttrack' ),
            $past_count
        ) );
        $label = $include_past
            ? esc_html__( 'shown · Hide', 'talenttrack' )
            : esc_html__( 'hidden · Show', 'talenttrack' );

        echo '<a class="tt-act-past-toggle" href="' . esc_url( $toggle_url ) . '" data-past-state="' . ( $include_past ? 'expanded' : 'collapsed' ) . '">';
        echo '<span class="tt-act-past-toggle__count">' . $count_text . '</span> '; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- _n esc'd above.
        echo '<span class="tt-act-past-toggle__label">' . $label . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '<span class="tt-act-past-toggle__chev" aria-hidden="true">▼</span>';
        echo '</a>';
    }

    /**
     * Render one activity card.
     *
     * @param object $row activity row from loadActivitiesForList()
     * @param string $mode 'past' | 'attention' | 'today' | 'future'
     */
    private static function renderActivityCard( object $row, string $mode ): string {
        $id = (int) ( $row->id ?? 0 );
        if ( $id <= 0 ) return '';

        $session_date = (string) ( $row->session_date ?? '' );
        $title        = (string) ( $row->title ?? '' );
        $team_name    = (string) ( $row->team_name ?? '' );
        $type_key     = (string) ( $row->activity_type_key ?? '' );
        $status_key   = (string) ( $row->activity_status_key ?? '' );
        $location     = (string) ( $row->location ?? '' );
        $start_time   = (string) ( $row->start_time ?? '' );

        // #1862 — cancelled in either taxonomy (only ever rendered when the
        // "show cancelled" filter is on). Dim the card + always show the
        // status pill so it reads as cancelled in any bucket, not just past.
        $is_cancelled = strtolower( (string) ( $row->plan_state ?? '' ) ) === ActivityStatusKey::CANCELLED
                     || strtolower( $status_key ) === ActivityStatusKey::CANCELLED;

        $detail_url = RecordLink::detailUrlForWithBack( 'activities', $id );

        // Date badge — "May / 28" stacked.
        $month_short = '';
        $day_num     = '';
        if ( preg_match( '/^(\d{4})-(\d{2})-(\d{2})/', $session_date, $m ) ) {
            $ts = strtotime( $session_date );
            if ( $ts ) {
                // Localised abbreviated month name.
                $month_short = wp_date( 'M', (int) $ts );
                $day_num     = (int) $m[3];
            }
        }
        if ( $day_num === '' ) {
            $month_short = '—';
            $day_num     = '';
        }

        $date_cls = 'tt-act-date';
        if ( $mode === 'today' )      $date_cls .= ' tt-act-date--today';
        if ( $mode === 'attention' )  $date_cls .= ' tt-act-date--overdue';

        $type_pill_key = self::typeKeyForPill( $type_key );
        $type_label    = self::lookupLabelByName( 'activity_type', $type_key );
        $status_label  = self::lookupLabelByName( 'activity_status', $status_key );

        // Build meta line: type pill | (optional status pill for past) | team + time + location.
        $meta_bits = [];
        $meta_bits[] = '<span class="tt-act-pill" data-type="' . esc_attr( $type_pill_key ) . '">' . esc_html( $type_label !== '' ? $type_label : ucfirst( $type_key ) ) . '</span>';

        if ( $is_cancelled || ( $mode === 'past' && in_array( $status_key, [ ActivityStatusKey::COMPLETED, ActivityStatusKey::CANCELLED ], true ) ) ) {
            $pill_status = $is_cancelled ? ActivityStatusKey::CANCELLED : $status_key;
            $pill_label  = $is_cancelled
                ? self::lookupLabelByName( 'activity_status', ActivityStatusKey::CANCELLED )
                : $status_label;
            $meta_bits[] = '<span class="tt-act-pill" data-status="' . esc_attr( $pill_status ) . '">' . esc_html( $pill_label !== '' ? $pill_label : ucfirst( $pill_status ) ) . '</span>';
        }

        // #2221 — Spond-source chip. Signals at a glance that this
        // activity was imported from Spond (vs. a manually-created or
        // generated one), so a coach can trust its provenance. Only the
        // 'spond' source shows a chip; manual / generated show none. The
        // source key is set on import by SpondSync (migration 0040).
        if ( strtolower( (string) ( $row->activity_source_key ?? '' ) ) === 'spond' ) {
            $meta_bits[] = '<span class="tt-act-pill tt-act-pill--spond">'
                . esc_html__( 'Spond', 'talenttrack' ) . '</span>';
        }

        // Team / time / "still planned" tail (plain text).
        $tail = [];
        if ( $team_name !== '' ) $tail[] = esc_html( $team_name );
        if ( $start_time !== '' ) {
            // Truncate seconds; show HH:MM.
            $tail[] = esc_html( substr( $start_time, 0, 5 ) );
        }
        if ( $mode === 'attention' ) {
            $tail[] = '<em class="tt-act-meta__attn">' . esc_html__( 'still planned', 'talenttrack' ) . '</em>';
        }
        if ( $location !== '' && $mode !== 'past' ) {
            $tail[] = esc_html( $location );
        }

        $card_cls = 'tt-act-card'
            . ( $is_cancelled ? ' tt-act-card--cancelled' : '' )
            . ( $mode === 'archived' ? ' tt-act-card--archived' : '' );
        $card  = '<li class="' . esc_attr( $card_cls ) . '" data-type="' . esc_attr( $type_pill_key ) . '">';
        $card .= '<a class="tt-act-card__link" href="' . esc_url( $detail_url ) . '">';
        $card .= '<div class="' . esc_attr( $date_cls ) . '">';
        $card .= '<span class="tt-act-date__m">' . esc_html( $month_short ) . '</span>';
        $card .= '<span class="tt-act-date__d">' . esc_html( (string) $day_num ) . '</span>';
        $card .= '</div>';
        $card .= '<div class="tt-act-card__body">';
        $card .= '<p class="tt-act-card__title">' . esc_html( $title !== '' ? $title : __( '(untitled activity)', 'talenttrack' ) ) . '</p>';
        $card .= '<p class="tt-act-card__meta">' . implode( ' ', $meta_bits );
        if ( $tail !== [] ) {
            $card .= ' <span class="tt-act-card__tail">' . implode( ' · ', $tail ) . '</span>';
        }
        $card .= '</p>';
        $card .= '</div>';
        $card .= '<span class="tt-act-card__chev" aria-hidden="true">›</span>';
        $card .= '</a>';

        // #2633 — the inline alert chip. Rendered OUTSIDE the card's
        // tap-to-open `<a>` on purpose: the chip is itself a link, and a
        // nested anchor is invalid markup that browsers resolve
        // unpredictably. Same reasoning as the Complete quick-action below
        // it — two destinations, two separate targets, neither swallowing
        // the other's tap.
        $card .= \TT\Shared\Frontend\Components\AlertChip::html(
            'activity',
            $id,
            [ 'class' => 'tt-act-card__alert' ]
        );

        // #2245 — "Complete activity" quick-action on planned cards, so
        // most activities complete in one click without opening the
        // detail page. Sits OUTSIDE the card's tap-to-open `<a>` so the
        // two affordances don't fight. Type-aware target via the
        // domain-layer resolver (same one the detail button uses).
        // #2401 — seed the grid anchor from this row (the list query
        // selects `s.*`) so the wizard-off branch of the resolver builds
        // its deep-link without a per-card read, and reads "Mark
        // attendance" when it points at the grid.
        \TT\Modules\Activities\Services\ActivityGridLink::primeAnchor(
            $id,
            (int) ( $row->team_id ?? 0 ),
            $session_date
        );
        $status_lower = strtolower( $status_key );
        $is_planned   = ! $is_cancelled
            && ( $status_lower === '' || $status_lower === ActivityStatusKey::PLANNED )
            && in_array( $mode, [ 'today', 'this_week', 'next_week', 'later_this_month', 'later', 'attention' ], true );
        if ( $is_planned && \TT\Modules\Activities\Services\ActivityCompletionResolver::canComplete( $id, $type_key, get_current_user_id() ) ) {
            $complete_url = \TT\Modules\Activities\Services\ActivityCompletionResolver::completionUrl(
                $id,
                $type_key,
                RecordLink::detailUrlFor( 'activities', $id )
            );
            $card .= '<a class="tt-btn tt-btn-secondary tt-act-card__complete" href="' . esc_url( $complete_url ) . '">'
                . esc_html( \TT\Modules\Activities\Services\ActivityCompletionResolver::completionLabel(
                    $id,
                    $type_key,
                    get_current_user_id()
                ) ) . '</a>';
        }

        $card .= '</li>';
        return $card;
    }

    /**
     * Normalize a stored `activity_type_key` to one of the four pill
     * colour buckets the mockup defines: training / match / friendly /
     * other. The seeded keys ('training', 'game', 'tournament',
     * 'meeting', 'other') and any operator-renamed keys all collapse
     * down to these four. 'game' → 'match' (same red), 'tournament' →
     * 'match' (also competitive). Everything else falls through to
     * 'other'.
     */
    private static function typeKeyForPill( string $type_key ): string {
        $k = strtolower( trim( $type_key ) );
        if ( $k === '' ) return 'other';
        if ( strpos( $k, 'train' ) !== false )                  return 'training';
        if ( in_array( $k, [ 'match', ActivityTypeKey::GAME, ActivityTypeKey::TOURNAMENT ], true ) ) return 'match';
        if ( strpos( $k, 'friend' ) !== false )                 return 'friendly';
        return 'other';
    }

    /**
     * Resolve the localised label for a lookup-backed key. Returns ''
     * when the lookup isn't found.
     */
    /**
     * #2834 — the "you arrived here from a report" strip.
     *
     * Renders nothing on an ordinary visit. When the URL carries a
     * `player_id` or an explicit `date_from` / `date_to` — the parameters an
     * attendance drill-down adds — it names each one as a chip with an × that
     * clears that constraint alone and leaves the others standing.
     *
     * Team and type are deliberately NOT repeated here: those are real
     * controls in the FilterBar below, and a reader can already see and
     * change them. These three have no control of their own, which is exactly
     * why they were invisible.
     */
    private static function renderDrillContext( int $player_id, string $date_from, string $date_to, string $dash_url ): void {
        if ( $player_id <= 0 && $date_from === '' && $date_to === '' ) return;

        // Everything currently on the URL, minus WordPress's own noise, so a
        // chip's × preserves the rest of the state.
        $base = [];
        foreach ( [ 'tt_view', 'team_id', 'activity_type_key', 'period', 'archived', 'include_past', 'show_cancelled', 'tt_back', 'player_id', 'date_from', 'date_to' ] as $key ) {
            if ( ! empty( $_GET[ $key ] ) ) {
                $base[ $key ] = sanitize_text_field( wp_unslash( (string) $_GET[ $key ] ) );
            }
        }
        $base['tt_view'] = 'activities';

        $chips = [];

        if ( $player_id > 0 ) {
            $player = \TT\Infrastructure\Query\QueryHelpers::get_player( $player_id );
            $name   = $player
                ? \TT\Infrastructure\Query\QueryHelpers::player_display_name( $player )
                : sprintf( /* translators: %d = player id */ __( 'Player #%d', 'talenttrack' ), $player_id );

            $chips[] = [
                'label'  => $name,
                'remove' => add_query_arg( array_diff_key( $base, [ 'player_id' => 1 ] ), $dash_url ),
            ];
        }

        if ( $date_from !== '' || $date_to !== '' ) {
            $window = $date_from !== '' && $date_to !== ''
                ? sprintf(
                    /* translators: 1: window start date, 2: window end date */
                    __( '%1$s – %2$s', 'talenttrack' ),
                    \TT\Shared\Dates\TTDate::date( $date_from ),
                    \TT\Shared\Dates\TTDate::date( $date_to )
                )
                : \TT\Shared\Dates\TTDate::date( $date_from !== '' ? $date_from : $date_to );

            $chips[] = [
                'label'  => $window,
                'remove' => add_query_arg( array_diff_key( $base, [ 'date_from' => 1, 'date_to' => 1 ] ), $dash_url ),
            ];
        }

        echo '<div class="tt-act-drill-context">';
        echo '<span class="tt-act-drill-context__lead">'
            . esc_html__( 'Showing only:', 'talenttrack' ) . '</span>';
        foreach ( $chips as $chip ) {
            echo '<span class="tt-act-drill-context__chip">'
                . '<span class="tt-act-drill-context__label">' . esc_html( (string) $chip['label'] ) . '</span>'
                . '<a class="tt-act-drill-context__clear" href="' . esc_url( (string) $chip['remove'] ) . '"'
                . ' aria-label="' . esc_attr( sprintf(
                    /* translators: %s = the filter being removed, e.g. a player name or a date range */
                    __( 'Remove filter: %s', 'talenttrack' ),
                    (string) $chip['label']
                ) ) . '">&times;</a>'
                . '</span>';
        }
        echo '</div>';
    }

    private static function lookupLabelByName( string $type, string $name ): string {
        if ( $name === '' ) return '';
        static $cache = [];
        if ( ! isset( $cache[ $type ] ) ) {
            $cache[ $type ] = [];
            foreach ( QueryHelpers::get_lookups( $type ) as $r ) {
                $cache[ $type ][ (string) $r->name ] = $r;
            }
        }
        $row = $cache[ $type ][ $name ] ?? null;
        return $row ? LookupTranslator::name( $row ) : '';
    }

    /**
     * Format the Today bucket header "Today · Wed 28 May".
     */
    private static function formatTodayHeader( string $today_str ): string {
        $ts = strtotime( $today_str );
        if ( ! $ts ) return __( 'Today', 'talenttrack' );
        return sprintf(
            /* translators: 1: localised "Today", 2: day-of-week + date (e.g. "Wed 28 May") */
            __( '%1$s · %2$s', 'talenttrack' ),
            __( 'Today', 'talenttrack' ),
            wp_date( 'D j M', (int) $ts )
        );
    }

    /**
     * Bucketize a list of activity rows by `session_date` relative to
     * `$today_str`. Past = `plan_state IN ('completed','cancelled')` AND
     * `session_date < today`. Past-planned (still planned) lands in
     * "attention" instead.
     *
     * Buckets:
     *  - past             — past + completed/cancelled (pinned-top block)
     *  - attention        — past + planned (Needs attention pseudo-bucket)
     *  - today            — session_date == today
     *  - this_week        — today < session_date <= upcoming Sunday
     *  - next_week        — next Mon → next Sun
     *  - later_this_month — beyond next_week, <= end-of-month
     *  - later            — > end-of-month
     *
     * @param array<int,object> $rows
     * @return array{
     *     past: array<int,object>,
     *     attention: array<int,object>,
     *     attention_count: int,
     *     today: array<int,object>,
     *     this_week: array<int,object>,
     *     next_week: array<int,object>,
     *     later_this_month: array<int,object>,
     *     later: array<int,object>
     * }
     */
    private static function bucketize( array $rows, string $today_str ): array {
        $tz = wp_timezone();
        try {
            $today_dt = new \DateTimeImmutable( $today_str, $tz );
        } catch ( \Exception $e ) {
            $today_dt = new \DateTimeImmutable( 'today', $tz );
        }

        // "this week" ends on the upcoming Sunday (inclusive). PHP's
        // 'sunday this week' anchors to the current week's Sunday;
        // 'next sunday' if today IS Sunday rolls forward 7 days. Use
        // 'sunday this week' which yields today's date when today is
        // Sunday (so This Week becomes empty, which is the correct
        // semantic — no remaining days in this week).
        $end_of_this_week = $today_dt->modify( 'sunday this week' );
        $next_week_start  = $end_of_this_week->modify( '+1 day' );
        $next_week_end    = $next_week_start->modify( '+6 days' );
        $end_of_month     = $today_dt->modify( 'last day of this month' );

        $today_ymd          = $today_dt->format( 'Y-m-d' );
        $end_of_this_week_y = $end_of_this_week->format( 'Y-m-d' );
        $next_week_start_y  = $next_week_start->format( 'Y-m-d' );
        $next_week_end_y    = $next_week_end->format( 'Y-m-d' );
        $end_of_month_y     = $end_of_month->format( 'Y-m-d' );

        $past             = [];
        $attention        = [];
        $today            = [];
        $this_week        = [];
        $next_week        = [];
        $later_this_month = [];
        $later            = [];

        foreach ( $rows as $row ) {
            $sd = (string) ( $row->session_date ?? '' );
            if ( $sd === '' ) continue;

            $plan_state = strtolower( (string) ( $row->plan_state ?? '' ) );
            $status     = strtolower( (string) ( $row->activity_status_key ?? '' ) );

            // The "closed" predicate accepts EITHER the plan_state
            // taxonomy (completed/cancelled) OR the older
            // activity_status_key taxonomy. Belt-and-braces because
            // pilot installs have a mix of both on legacy rows.
            $is_closed = in_array( $plan_state, [ ActivityStatusKey::COMPLETED, ActivityStatusKey::CANCELLED ], true )
                      || in_array( $status,     [ ActivityStatusKey::COMPLETED, ActivityStatusKey::CANCELLED ], true );

            if ( $sd < $today_ymd ) {
                if ( $is_closed ) {
                    $past[] = $row;
                } else {
                    $attention[] = $row;
                }
                continue;
            }
            if ( $sd === $today_ymd ) {
                $today[] = $row;
                continue;
            }
            // future
            if ( $sd <= $end_of_this_week_y ) {
                $this_week[] = $row;
                continue;
            }
            if ( $sd >= $next_week_start_y && $sd <= $next_week_end_y ) {
                $next_week[] = $row;
                continue;
            }
            if ( $sd <= $end_of_month_y ) {
                $later_this_month[] = $row;
                continue;
            }
            $later[] = $row;
        }

        // Sort each forward bucket ascending so the next-upcoming row
        // sits at the top. Past sorts descending — most-recent first.
        $asc = static function ( $a, $b ) {
            return strcmp( (string) ( $a->session_date ?? '' ), (string) ( $b->session_date ?? '' ) );
        };
        $desc = static function ( $a, $b ) {
            return strcmp( (string) ( $b->session_date ?? '' ), (string) ( $a->session_date ?? '' ) );
        };
        usort( $today,            $asc );
        usort( $this_week,        $asc );
        usort( $next_week,        $asc );
        usort( $later_this_month, $asc );
        usort( $later,            $asc );
        usort( $attention,        $asc );
        usort( $past,             $desc );

        return [
            'past'             => $past,
            'attention'        => $attention,
            'attention_count'  => count( $attention ),
            'today'            => $today,
            'this_week'        => $this_week,
            'next_week'        => $next_week,
            'later_this_month' => $later_this_month,
            'later'            => $later,
        ];
    }

    /**
     * Server-side activity query for the redesigned list view (v4.7.0,
     * #973). Mirrors `ActivitiesRestController::list_sessions`'s WHERE
     * and scope rules so the rendered list shows the same set the REST
     * endpoint would return for an equivalent filter — just without
     * pagination (bucketing is a presentation concern over the whole
     * matched set).
     *
     * The REST controller continues to be the canonical contract for
     * non-WordPress consumers (CLAUDE.md §4); this is the PHP-render
     * sibling, not a divergence.
     *
     * @return array<int,object> raw `tt_activities` rows + team_name.
     */
    /**
     * Resolve a #1648 period key to an inclusive [from,to] Y-m-d window.
     * Weeks are ISO (Mon–Sun); months are calendar; the season comes from
     * the configured current season. Returns null for "all" / unknown.
     *
     * @return array{from:string,to:string}|null
     */
    /**
     * #2400 — resolve the request's date window the way the list does:
     * the #1648 `period` key, with an explicit #2185 `date_from` /
     * `date_to` drill-down overriding it. Returns empty strings when the
     * request asks for the unbounded forward agenda ("all").
     *
     * Extracted so the list and the embedded calendar read one resolver;
     * before this, the calendar ignored the window entirely.
     *
     * @return array{from:string,to:string}
     */
    private static function resolveRequestWindow( string $today ): array {
        $period = isset( $_GET['period'] ) ? sanitize_key( (string) $_GET['period'] ) : '';
        if ( ! in_array( $period, [ 'this_week', 'next_week', 'this_month', 'next_month', 'this_season' ], true ) ) {
            $period = '';
        }
        $window = self::periodWindow( $period, $today );

        $from = isset( $_GET['date_from'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $_GET['date_from'] )
            ? (string) $_GET['date_from'] : '';
        $to   = isset( $_GET['date_to'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $_GET['date_to'] )
            ? (string) $_GET['date_to'] : '';

        return [
            'from' => $from !== '' ? $from : (string) ( $window['from'] ?? '' ),
            'to'   => $to   !== '' ? $to   : (string) ( $window['to']   ?? '' ),
        ];
    }

    private static function periodWindow( string $period, string $today ): ?array {
        if ( $period === '' ) return null;
        $base = strtotime( $today );
        if ( $base === false ) return null;

        switch ( $period ) {
            case 'this_week':
            case 'next_week':
                $dow    = (int) gmdate( 'N', $base ); // 1 = Monday
                $monday = $base - ( $dow - 1 ) * DAY_IN_SECONDS;
                if ( $period === 'next_week' ) $monday += 7 * DAY_IN_SECONDS;
                return [
                    'from' => gmdate( 'Y-m-d', $monday ),
                    'to'   => gmdate( 'Y-m-d', $monday + 6 * DAY_IN_SECONDS ),
                ];

            case 'this_month':
                return [ 'from' => gmdate( 'Y-m-01', $base ), 'to' => gmdate( 'Y-m-t', $base ) ];

            case 'next_month':
                $nm = strtotime( gmdate( 'Y-m-01', $base ) . ' +1 month' );
                if ( $nm === false ) return null;
                return [ 'from' => gmdate( 'Y-m-01', $nm ), 'to' => gmdate( 'Y-m-t', $nm ) ];

            case 'this_season':
                if ( ! class_exists( '\\TT\\Modules\\Pdp\\Repositories\\SeasonsRepository' ) ) return null;
                $season = ( new \TT\Modules\Pdp\Repositories\SeasonsRepository() )->current();
                if ( ! $season || empty( $season->start_date ) || empty( $season->end_date ) ) return null;
                return [ 'from' => (string) $season->start_date, 'to' => (string) $season->end_date ];
        }

        return null;
    }

    private static function loadActivitiesForList( int $team_filter, string $type_filter, string $date_from = '', string $date_to = '', bool $show_cancelled = false, string $archived_view = 'active', int $player_id = 0 ): array {
        // #1320 — the query (incl. demo + coach-scope authorization) lives
        // in ActivitiesRepository so the REST list and this surface share
        // one source of truth and the view holds no SQL or permission logic.
        // #2185 — an optional player_id narrows the list to activities the
        // player has a recorded attendance row for (attendance drill-down).
        return ( new \TT\Modules\Activities\Repositories\ActivitiesRepository() )
            ->listForManageSurface( $team_filter, $type_filter, get_current_user_id(), $date_from, $date_to, $show_cancelled, $archived_view, $player_id );
    }

    /**
     * Create / edit form. Uses the existing REST endpoints; PUT vs
     * POST is decided by whether `$session` is set.
     *
     * @param object|null $session
     * @param array<int,object> $attendance roster rows keyed by player_id
     * @param array<int,object> $guests     guest attendance rows (#0026)
     */
    private static function renderForm( int $user_id, bool $is_admin, ?object $session, array $attendance, array $guests ): void {
        // The stored team is passed as `must_include` so editing an activity
        // never silently unassigns it: without that, a team outside the
        // editor's scope drops out of the options and the next save writes
        // whatever the select happens to land on. That is the #2866 failure.
        $teams         = QueryHelpers::get_teams_in_scope( $user_id, $is_admin, (int) ( $session->team_id ?? 0 ) );
        $selected_team = (int) ( $session->team_id ?? ( $teams ? $teams[0]->id : 0 ) );

        // v4.20.10 (#1154) — was: roster spanned every team the coach
        // has access to, so the entire academy shipped in the hidden
        // form fields. That produced the off-roster `is_guest = 0`
        // artefacts #1148 had to write-time-filter and DELETE-backfill.
        // Now: roster is the activity's current team only, unioned with
        // any players who already have an attendance row on this
        // activity (legacy historical squad data preserved through
        // #1148's audit-trail-aware backfill — those rows must stay
        // editable in the form even when the player has since moved
        // teams).
        //
        // Team-dropdown switch edge case: when the admin changes the
        // team `<select>`, the picker still shows the OLD team's roster
        // until the activity is saved + page reloaded. This is the
        // niche edge case (#1154 issue body) — coaches typically pick
        // the team first, then mark attendance. Save-and-reload is the
        // documented recovery.
        $players_by_team = [];
        $all_players     = [];
        if ( $selected_team > 0 ) {
            $current_roster = QueryHelpers::get_players( $selected_team );
            foreach ( $current_roster as $pl ) {
                $all_players[ (int) $pl->id ] = $pl;
                $players_by_team[ $selected_team ][] = (int) $pl->id;
            }
        }
        // Union in players who already have an attendance row on this
        // activity but aren't on the current roster. Keeps historical
        // squad data editable.
        if ( $attendance ) {
            $missing_ids = array_diff( array_map( 'intval', array_keys( $attendance ) ), array_keys( $all_players ) );
            if ( $missing_ids ) {
                global $wpdb;
                $placeholders = implode( ',', array_fill( 0, count( $missing_ids ), '%d' ) );
                /** @var object[] $legacy_rows */
                $legacy_rows = $wpdb->get_results( $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}tt_players
                      WHERE id IN ($placeholders) AND archived_at IS NULL",
                    ...$missing_ids
                ) );
                if ( is_array( $legacy_rows ) ) {
                    foreach ( $legacy_rows as $pl ) {
                        $pid = (int) $pl->id;
                        $all_players[ $pid ] = $pl;
                        $players_by_team[ (int) $pl->team_id ][] = $pid;
                    }
                }
            }
        }

        $statuses      = QueryHelpers::get_lookup_names( 'attendance_status' );
        // #1861 — get_lookup_names() strips translations, so the seeded
        // English game-subtype names ('Friendly'/'League'/'Cup') rendered
        // raw on a Dutch install. Pull full rows and translate the label
        // (value stays the canonical name), matching the admin form + wizard.
        $game_subtype_rows = QueryHelpers::get_lookups( 'game_subtype' );
        // #0050 — Type lookup-driven; admins can rename or add types
        // via Configuration → Activity Types. Conditional Subtype /
        // Other-label rows stay anchored to the seeded keys.
        $activity_type_rows   = QueryHelpers::get_lookups( 'activity_type' );
        $activity_status_rows = QueryHelpers::get_lookups( 'activity_status' );

        $current_type    = (string) ( $session->activity_type_key ?? ActivityTypeKey::TRAINING );
        $current_status  = (string) ( $session->activity_status_key ?? ActivityStatusKey::PLANNED );
        $current_subtype = (string) ( $session->game_subtype_key ?? '' );
        $current_other        = (string) ( $session->other_label ?? '' );
        $current_tournament_id = (int) ( $session->tournament_id ?? 0 );

        // Edit mode → PUT /activities/{id}; create → POST /activities.
        // #1371 — a duplicate-prefill object has no id, so it stays on
        // the create path while pre-filling every field.
        $is_edit   = $session !== null && (int) ( $session->id ?? 0 ) > 0;
        $rest_path = $is_edit ? 'activities/' . (int) $session->id : 'activities';
        $rest_meth = $is_edit ? 'PUT' : 'POST';
        $form_id   = 'tt-activity-form';

        // v3.110.51 — when the form was reached via a link that captured
        // the originating page (e.g. the team planner's "+ Schedule
        // activity" or "+ Add" links pass `tt_back=<planner URL>`),
        // redirect back there on save instead of falling through to the
        // generic activities list. The user's mental model is "I came
        // from the planner, take me back when I'm done." Falls back to
        // the existing `data-redirect-after-save="list"` behaviour when
        // no back-target is in the URL.
        $back_resolved = \TT\Shared\Frontend\Components\BackLink::resolve();
        $back_url      = is_array( $back_resolved ) ? (string) $back_resolved['url'] : '';

        ?>
        <form id="<?php echo esc_attr( $form_id ); ?>" class="tt-ajax-form tt-activity-form" data-rest-path="<?php echo esc_attr( $rest_path ); ?>" data-rest-method="<?php echo esc_attr( $rest_meth ); ?>"<?php
            if ( $back_url !== '' ) :
                ?> data-redirect-after-save-url="<?php echo esc_attr( $back_url ); ?>"<?php
            else :
                ?> data-redirect-after-save="list"<?php
            endif;
        ?>>
            <div class="tt-grid tt-grid-2">
                <div class="tt-field">
                    <label class="tt-field-label tt-field-required" for="tt-activity-type"><?php esc_html_e( 'Type', 'talenttrack' ); ?></label>
                    <select id="tt-activity-type" class="tt-input" name="activity_type_key" required>
                        <?php foreach ( $activity_type_rows as $type_row ) : ?>
                            <option value="<?php echo esc_attr( (string) $type_row->name ); ?>" <?php selected( $current_type, (string) $type_row->name ); ?>><?php echo esc_html( \TT\Infrastructure\Query\LookupTranslator::name( $type_row ) ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php
                // #2245 — the status dropdown was removed. The edit form
                // now edits details only; status transitions (Complete /
                // Cancel / Reopen) live as explicit buttons on the detail
                // view. Preserve the current status on save so a details
                // edit never resets it (the REST controller reads this
                // field; leaving it out would fall to the column default).
                ?>
                <input type="hidden" name="activity_status_key" value="<?php echo esc_attr( $current_status ); ?>" />
                <div class="tt-field">
                    <label class="tt-field-label tt-field-required" for="tt-activity-title"><?php esc_html_e( 'Title', 'talenttrack' ); ?></label>
                    <input type="text" id="tt-activity-title" class="tt-input" name="title" required value="<?php echo esc_attr( (string) ( $session->title ?? '' ) ); ?>" />
                </div>
                <div class="tt-field" id="tt-activity-subtype-row" style="<?php echo $current_type === ActivityTypeKey::GAME ? '' : 'display:none;'; ?>">
                    <label class="tt-field-label" for="tt-activity-subtype"><?php esc_html_e( 'Game subtype', 'talenttrack' ); ?></label>
                    <select id="tt-activity-subtype" class="tt-input" name="game_subtype_key">
                        <option value=""><?php esc_html_e( '— Choose —', 'talenttrack' ); ?></option>
                        <?php foreach ( $game_subtype_rows as $sub_row ) :
                            $sub_name = (string) $sub_row->name; ?>
                            <option value="<?php echo esc_attr( $sub_name ); ?>" <?php selected( $current_subtype, $sub_name ); ?>><?php echo esc_html( \TT\Infrastructure\Query\LookupTranslator::name( $sub_row ) ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="tt-field" id="tt-activity-other-row" style="<?php echo $current_type === ActivityTypeKey::OTHER ? '' : 'display:none;'; ?>">
                    <label class="tt-field-label tt-field-required" for="tt-activity-other-label"><?php esc_html_e( 'Other label', 'talenttrack' ); ?></label>
                    <input type="text" id="tt-activity-other-label" class="tt-input" name="other_label" maxlength="120" value="<?php echo esc_attr( $current_other ); ?>" placeholder="<?php esc_attr_e( 'e.g. Team-building day', 'talenttrack' ); ?>" />
                </div>
                <?php
                // #1324 — tournament link picker. Shown only when
                // activity_type_key === 'tournament' (toggle handled
                // alongside subtype + other_label by the existing JS).
                self::renderFormTournamentPicker( $selected_team, $current_tournament_id, $current_type );
                ?>
                <?php
                // #0006 — when the team-planner sends the user here
                // with `?session_date=YYYY-MM-DD&plan_state=scheduled`,
                // pre-fill the date from the URL on create. Editing
                // existing activities ignores the URL — the row's own
                // date wins.
                $prefill_date = (string) ( $session->session_date ?? '' );
                if ( $prefill_date === '' ) {
                    $url_date = isset( $_GET['session_date'] ) ? sanitize_text_field( (string) $_GET['session_date'] ) : '';
                    $prefill_date = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $url_date ) ? $url_date : current_time( 'Y-m-d' );
                }
                echo DateInputComponent::render( [
                    'name'     => 'session_date',
                    'label'    => __( 'Date', 'talenttrack' ),
                    'required' => true,
                    'value'    => $prefill_date,
                ] ); ?>
                <?php
                // #0006 — pass the planner's `plan_state=scheduled`
                // through the form as a hidden field so the REST
                // controller can branch on it. Edits leave the
                // existing `plan_state` alone (the URL param is only
                // honoured on create).
                $plan_state_url = isset( $_GET['plan_state'] ) ? sanitize_key( (string) $_GET['plan_state'] ) : '';
                if ( ! $is_edit && in_array( $plan_state_url, [ 'draft', 'scheduled' ], true ) ) :
                ?>
                <input type="hidden" name="plan_state" value="<?php echo esc_attr( $plan_state_url ); ?>" />
                <?php endif; ?>
                <?php echo TeamPickerComponent::render( [
                    'name'     => 'team_id',
                    'label'    => __( 'Team', 'talenttrack' ),
                    'required' => true,
                    'teams'    => $teams,
                    'selected' => $selected_team,
                ] ); ?>
                <?php
                // #1126 — optional start + end time. Both empty by
                // default; renderer omits the time line when both are
                // blank (no placeholder).
                $current_start = (string) ( $session->start_time ?? '' );
                $current_end   = (string) ( $session->end_time   ?? '' );
                // #1729 — arrival/presence time, captured for match types
                // only (the JS toggle below shows/hides this row).
                $current_presence = (string) ( $session->time_of_presence ?? '' );
                $is_match_type    = in_array( $current_type, [ 'game', 'match', 'friendly', 'tournament' ], true );
                ?>
                <div class="tt-field">
                    <label class="tt-field-label" for="tt-activity-start-time"><?php esc_html_e( 'Start time (optional)', 'talenttrack' ); ?></label>
                    <input type="time" id="tt-activity-start-time" class="tt-input" name="start_time" value="<?php echo esc_attr( substr( $current_start, 0, 5 ) ); ?>" />
                </div>
                <div class="tt-field">
                    <label class="tt-field-label" for="tt-activity-end-time"><?php esc_html_e( 'End time (optional)', 'talenttrack' ); ?></label>
                    <input type="time" id="tt-activity-end-time" class="tt-input" name="end_time" value="<?php echo esc_attr( substr( $current_end, 0, 5 ) ); ?>" data-tt-end-default-mins="105" data-tt-end-default-from="start_time" />
                </div>
                <div class="tt-field" id="tt-activity-presence-row" style="<?php echo $is_match_type ? '' : 'display:none;'; ?>">
                    <label class="tt-field-label" for="tt-activity-presence-time"><?php esc_html_e( 'Presence time (optional)', 'talenttrack' ); ?></label>
                    <input type="time" id="tt-activity-presence-time" class="tt-input" name="time_of_presence" value="<?php echo esc_attr( substr( $current_presence, 0, 5 ) ); ?>" />
                </div>
                <div class="tt-field">
                    <label class="tt-field-label" for="tt-activity-location"><?php esc_html_e( 'Location', 'talenttrack' ); ?></label>
                    <input type="text" id="tt-activity-location" class="tt-input" name="location" value="<?php echo esc_attr( (string) ( $session->location ?? '' ) ); ?>" />
                </div>
            </div>

            <div class="tt-field">
                <label class="tt-field-label" for="tt-activity-notes"><?php esc_html_e( 'Notes', 'talenttrack' ); ?></label>
                <textarea id="tt-activity-notes" class="tt-input" name="notes" rows="2"><?php echo esc_textarea( (string) ( $session->notes ?? '' ) ); ?></textarea>
            </div>

            <?php self::renderCycleOverrideField( $session ); ?>

            <?php
            // #0077 M2 — Principles practiced multiselect. Mirrors the
            // wp-admin ActivitiesPage form (lines ~331-352) so the
            // frontend has parity. Saved via the REST controller's
            // persistPrincipleLinks helper.
            if ( class_exists( '\\TT\\Modules\\Methodology\\Repositories\\PrinciplesRepository' )
                 && class_exists( '\\TT\\Modules\\Methodology\\Repositories\\PrincipleLinksRepository' ) ) {
                $all_principles = ( new \TT\Modules\Methodology\Repositories\PrinciplesRepository() )->listFiltered();
                // #1371 — duplicate mode pre-ticks the source
                // activity's principles via `duplicate_source_id`.
                $principles_activity_id = ( $is_edit && $session && (int) $session->id > 0 )
                    ? (int) $session->id
                    : (int) ( $session->duplicate_source_id ?? 0 );
                $linked_ids = $principles_activity_id > 0
                    ? ( new \TT\Modules\Methodology\Repositories\PrincipleLinksRepository() )->principlesForActivity( $principles_activity_id )
                    : [];
                if ( ! empty( $all_principles ) ) :
                    // #1122 — replace the flat multiselect with the
                    // bucket → principles two-level picker. Same shape
                    // as PrinciplesStep (wizard); both surfaces keep
                    // visual parity so a coach moving between
                    // wizard-create + form-edit sees the same picker.
                    $function_labels = \TT\Modules\Methodology\MethodologyEnums::teamFunctions();
                    $task_labels     = \TT\Modules\Methodology\MethodologyEnums::teamTasks();
                    $grouped = [];
                    $unbucketed = [];
                    foreach ( $all_principles as $pr ) {
                        $fk = (string) ( $pr->team_function_key ?? '' );
                        $tk = (string) ( $pr->team_task_key     ?? '' );
                        if ( $fk === '' && $tk === '' ) { $unbucketed[] = $pr; continue; }
                        $grouped[ $fk ][ $tk ][] = $pr;
                    }
                    $task_order = array_keys( $task_labels );
                    ?>
                <div class="tt-field">
                    <span class="tt-field-label"><?php esc_html_e( 'Connected principles', 'talenttrack' ); ?></span>
                    <p class="tt-field-hint" style="margin-top:0;"><?php esc_html_e( 'Group by team function + team task; tick whichever apply. Leave all unticked to save without links.', 'talenttrack' ); ?></p>
                    <?php // Marker so an all-unchecked submit still clears links (checkbox arrays vanish from the payload when empty). ?>
                    <input type="hidden" name="activity_principles_present" value="1">
                    <div class="tt-act-principle-picker" style="display:flex; flex-direction:column; gap:10px; margin-top:6px;">
                        <?php foreach ( $function_labels as $fk => $fn_label ) :
                            if ( empty( $grouped[ $fk ] ) ) continue;
                            uksort( $grouped[ $fk ], static function ( $a, $b ) use ( $task_order ): int {
                                $ia = array_search( $a, $task_order, true );
                                $ib = array_search( $b, $task_order, true );
                                if ( $ia === false ) $ia = PHP_INT_MAX;
                                if ( $ib === false ) $ib = PHP_INT_MAX;
                                return $ia <=> $ib;
                            } );
                            // #1155 — collapsed by default; auto-open
                            // the bucket only if it contains a
                            // currently-linked principle so the
                            // operator can see existing selections at
                            // a glance without hunting through every
                            // bucket. Matches the wizard step.
                            $bucket_has_selection = false;
                            foreach ( $grouped[ $fk ] as $tk_check => $rows_for_check ) {
                                foreach ( $rows_for_check as $pr_check ) {
                                    if ( in_array( (int) $pr_check->id, $linked_ids, true ) ) {
                                        $bucket_has_selection = true;
                                        break 2;
                                    }
                                }
                            }
                            $open_attr = $bucket_has_selection ? ' open' : '';
                            ?>
                            <details<?php echo $open_attr; ?> style="border:1px solid var(--tt-line, #d6dadd); border-radius:8px; padding:10px 12px; background:#fff;">
                                <summary style="cursor:pointer; font-weight:700; font-size:14px; color:var(--tt-ink, #1a1d21);"><?php echo esc_html( $fn_label ); ?></summary>
                                <?php foreach ( $grouped[ $fk ] as $tk => $rows ) :
                                    $task_label = $task_labels[ $tk ] ?? ucfirst( str_replace( '_', ' ', (string) $tk ) );
                                    ?>
                                    <div style="margin:10px 0 4px;">
                                        <?php if ( (string) $tk !== '' ) : ?>
                                            <div style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.4px; color:var(--tt-muted, #5b6e75); margin-bottom:6px;"><?php echo esc_html( (string) $task_label ); ?></div>
                                        <?php endif; ?>
                                        <ul style="list-style:none; margin:0; padding:0; display:grid; grid-template-columns:1fr; gap:6px;">
                                            <?php foreach ( $rows as $pr ) :
                                                $title  = \TT\Modules\Methodology\Helpers\MultilingualField::string( $pr->title_json );
                                                $label  = trim( (string) $pr->code . ( $title !== '' ? ' · ' . $title : '' ) );
                                                $pid    = (int) $pr->id;
                                                $is_sel = in_array( $pid, $linked_ids, true );
                                                ?>
                                                <li>
                                                    <label style="display:flex; align-items:center; gap:10px; padding:8px 10px; border-radius:6px; background:<?php echo $is_sel ? '#eef4fb' : '#f9fafb'; ?>; cursor:pointer; min-height:44px;">
                                                        <input type="checkbox" name="activity_principle_ids[]" value="<?php echo $pid; ?>"<?php checked( $is_sel ); ?> style="width:20px; height:20px;">
                                                        <span style="flex:1; font-size:14px;"><?php echo esc_html( $label ); ?></span>
                                                    </label>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endforeach; ?>
                            </details>
                        <?php endforeach; ?>
                        <?php if ( $unbucketed !== [] ) : ?>
                            <details style="border:1px solid var(--tt-line, #d6dadd); border-radius:8px; padding:10px 12px; background:#fff;">
                                <summary style="cursor:pointer; font-weight:700; font-size:14px; color:var(--tt-muted, #5b6e75);"><?php esc_html_e( 'Other principles', 'talenttrack' ); ?></summary>
                                <ul style="list-style:none; margin:10px 0 4px; padding:0; display:grid; grid-template-columns:1fr; gap:6px;">
                                    <?php foreach ( $unbucketed as $pr ) :
                                        $title  = \TT\Modules\Methodology\Helpers\MultilingualField::string( $pr->title_json );
                                        $label  = trim( (string) $pr->code . ( $title !== '' ? ' · ' . $title : '' ) );
                                        $pid    = (int) $pr->id;
                                        $is_sel = in_array( $pid, $linked_ids, true );
                                        ?>
                                        <li>
                                            <label style="display:flex; align-items:center; gap:10px; padding:8px 10px; border-radius:6px; background:<?php echo $is_sel ? '#eef4fb' : '#f9fafb'; ?>; cursor:pointer; min-height:44px;">
                                                <input type="checkbox" name="activity_principle_ids[]" value="<?php echo $pid; ?>"<?php checked( $is_sel ); ?> style="width:20px; height:20px;">
                                                <span style="flex:1; font-size:14px;"><?php echo esc_html( $label ); ?></span>
                                            </label>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </details>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif;
            }
            ?>

            <script>
            (function(){
                var sel = document.getElementById('tt-activity-type');
                if ( ! sel ) return;
                var subRow         = document.getElementById('tt-activity-subtype-row');
                var otherRow       = document.getElementById('tt-activity-other-row');
                var tournamentRow  = document.getElementById('tt-activity-tournament-row'); // #1324
                var presenceRow    = document.getElementById('tt-activity-presence-row');   // #1729
                var matchTypes     = ['game','match','friendly','tournament'];
                sel.addEventListener('change', function(){
                    if ( subRow )        subRow.style.display        = ( sel.value === 'game' )       ? '' : 'none';
                    if ( otherRow )      otherRow.style.display      = ( sel.value === 'other' )      ? '' : 'none';
                    if ( tournamentRow ) tournamentRow.style.display = ( sel.value === 'tournament' ) ? '' : 'none';
                    if ( presenceRow )   presenceRow.style.display   = ( matchTypes.indexOf( sel.value ) !== -1 ) ? '' : 'none';
                });
            })();
            </script>

            <?php
            // #2245 — the inline editable *actual* attendance table (and its
            // status-toggle JS) were removed from the edit form. The
            // evaluation wizard's AttendanceStep is now the single
            // attendance path (launched via the detail view's "Complete
            // activity" / "Continue rating" buttons and the list-card
            // quick-action). The edit form edits details + planned
            // (expected) attendance only. The read-only attendance summary
            // stays on the detail view.
            //
            // #0061 — $attendance_visible mirrors "has the activity actually
            // happened?" (status = completed). It no longer gates an
            // in-form actual-attendance table (that moved to the wizard); it
            // survives only as the inverse gate for the planned section
            // below — planned attendance is editable *until* completion.
            $attendance_visible = ( $current_status === ActivityStatusKey::COMPLETED );

            // #2248 — planned (expected) attendance is editable while the
            // activity has NOT yet happened (inverse of $attendance_visible).
            // Seeded from the stored expected rows; when none exist (the
            // "set attendance later" path) the current team roster seeds the
            // plan, all defaulting to Expected. The repository owns the read
            // (§4); the view only composes rows.
            // Edit-only: the create path (POST /activities) has no planned
            // write handler, so seeding a plan there would silently drop it.
            //
            // `renderForm` has no `$id` of its own — resolve the activity id
            // from the loaded session. Create-mode rows have no id yet and
            // skip the roster lookup.
            $activity_id_for_plan = ( $is_edit && $session ) ? (int) $session->id : 0;
            $planned_visible = $is_edit && ! $attendance_visible;
            $planned_rows    = [];
            if ( $is_edit && $activity_id_for_plan > 0 ) {
                foreach ( ( new \TT\Modules\Activities\Repositories\ActivitiesRepository() )
                    ->plannedRosterForActivity( $activity_id_for_plan ) as $prow ) {
                    $planned_rows[ (int) $prow->player_id ] = $prow;
                }
            }
            // Seed from the team roster when the plan is still empty so the
            // coach can start managing it (all default to Expected).
            if ( empty( $planned_rows ) ) {
                foreach ( $all_players as $pid => $pl ) {
                    $planned_rows[ (int) $pid ] = (object) [
                        'player_id' => (int) $pid,
                        'is_guest'  => 0,
                        'name'      => QueryHelpers::player_display_name( $pl ),
                        'status'    => '',
                        'notes'     => '',
                    ];
                }
            }
            // Plan-status → stored attendance_status map (mirrors the REST
            // controller). Kept here only to pre-select the right option.
            $plan_status_options = [
                'expected'   => __( 'Expected', 'talenttrack' ),
                'not_coming' => __( 'Not coming', 'talenttrack' ),
                'maybe'      => __( 'Maybe', 'talenttrack' ),
            ];
            $stored_to_plan_key = static function ( string $stored ): string {
                $s = strtolower( trim( $stored ) );
                if ( $s === 'absent' )  return 'not_coming';
                if ( $s === 'excused' ) return 'maybe';
                return 'expected';
            };
            ?>
            <?php
            // #2245 + #2248 — planned attendance renders only while the
            // activity has not yet been completed ($planned_visible). The
            // status `<select>` + toggle JS that used to reveal/hide this
            // client-side were removed with the actual-attendance table;
            // status changes now happen via the detail-view transition
            // buttons and reload the form. Gating the whole section on
            // $planned_visible (not just $is_edit) keeps the `planned[]`
            // inputs out of the POST once the activity is completed, so a
            // stale expected write can't overwrite the recorded attendance.
            ?>
            <?php if ( $planned_visible ) : ?>
            <div class="tt-planned-attendance" data-tt-planned-section>
                <h3 class="tt-planned-attendance__title"><?php esc_html_e( 'Planned attendance', 'talenttrack' ); ?></h3>
                <p class="tt-planned-attendance__hint">
                    <?php esc_html_e( 'Set who you expect before the activity happens. This carries into the attendance defaults once it is completed.', 'talenttrack' ); ?>
                </p>
                <?php if ( empty( $planned_rows ) ) : ?>
                    <p><em><?php esc_html_e( 'No players on this team yet.', 'talenttrack' ); ?></em></p>
                <?php else : ?>
                    <table class="tt-table tt-attendance-table">
                        <thead><tr>
                            <th><?php esc_html_e( 'Player', 'talenttrack' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'talenttrack' ); ?></th>
                            <th><?php esc_html_e( 'Note', 'talenttrack' ); ?></th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ( $planned_rows as $pid => $prow ) :
                            $pid        = (int) $pid;
                            $plan_key   = $stored_to_plan_key( (string) ( $prow->status ?? '' ) );
                            $prow_notes = (string) ( $prow->notes ?? '' );
                            $prow_guest = (int) ( $prow->is_guest ?? 0 ) === 1;
                            ?>
                            <tr class="tt-attendance-row">
                                <td data-label="<?php esc_attr_e( 'Player', 'talenttrack' ); ?>">
                                    <?php echo esc_html( (string) ( $prow->name ?? ( '#' . $pid ) ) ); ?>
                                    <?php if ( $prow_guest ) : ?>
                                        <span class="tt-planned-guest"><?php esc_html_e( 'Guest', 'talenttrack' ); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="<?php esc_attr_e( 'Status', 'talenttrack' ); ?>">
                                    <select class="tt-input tt-attendance-status" name="planned[<?php echo $pid; ?>][status]">
                                        <?php foreach ( $plan_status_options as $opt_key => $opt_label ) : ?>
                                            <option value="<?php echo esc_attr( $opt_key ); ?>" <?php selected( $plan_key, $opt_key ); ?>><?php echo esc_html( $opt_label ); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td data-label="<?php esc_attr_e( 'Note', 'talenttrack' ); ?>">
                                    <input type="text" class="tt-input" name="planned[<?php echo $pid; ?>][note]" value="<?php echo esc_attr( $prow_notes ); ?>" />
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            <?php endif; // $planned_visible planned section ?>

            <?php
            // #2245 — the *actual* attendance table moved to the evaluation
            // wizard's AttendanceStep; the wizard is the primary path.
            // #2371 — but the wizard is toggleable (`tt_wizards_enabled`) and
            // completion routes through `WizardEntryPoint::buildUrl` with an
            // EMPTY flat fallback, so with wizards OFF there was no way to
            // correct recorded attendance at all — violating CLAUDE.md §3
            // ("the flat-form path remains as the power-user fallback").
            // Restore an editable actual-attendance table on the flat edit
            // form for a *completed* activity. It posts
            // `attendance[{pid}][status|notes]` — the SAME payload the REST
            // `write_attendance` path already consumes (record_type='actual');
            // no new write logic, no separate handler. Scoped to NON-match
            // types: match minutes are owned by the match-execution / finalize
            // flow, and `update_session` wipes-then-rewrites roster rows, so a
            // status-only table would drop recorded minutes.
            $is_match_type    = \TT\Modules\Activities\Services\ActivityCompletionResolver::isMatchType( (string) ( $session->activity_type_key ?? '' ) );
            $show_edit_roster = $is_edit && $attendance_visible && ! $is_match_type && current_user_can( 'tt_edit_activities' );

            if ( $session !== null && $is_edit && ( current_user_can( 'tt_edit_evaluations' ) || $show_edit_roster ) ) :
                \TT\Modules\Activities\Services\ActivityGridLink::primeAnchor(
                    (int) $session->id,
                    (int) ( $session->team_id ?? 0 ),
                    (string) ( $session->session_date ?? '' )
                );
                $completion_url = \TT\Modules\Activities\Services\ActivityCompletionResolver::completionUrl(
                    (int) $session->id,
                    (string) ( $session->activity_type_key ?? '' ),
                    add_query_arg( [ 'tt_view' => 'activities', 'id' => (int) $session->id ], \TT\Shared\Frontend\Components\RecordLink::dashboardUrl() )
                );
                // #2401 — is there a guided flow at all? With the wizard off
                // `$completion_url` points at the attendance grid instead, so
                // every "…in the guided flow" label below has to branch.
                $guided_on = \TT\Modules\Activities\Services\ActivityCompletionResolver::wizardAvailable( get_current_user_id() );
                ?>
                <h3 class="tt-act-form-attendance-head"><?php esc_html_e( 'Attendance', 'talenttrack' ); ?></h3>

                <?php if ( $show_edit_roster ) :
                    $actual_rows = ( new \TT\Modules\Activities\Repositories\ActivitiesRepository() )
                        ->actualRosterForActivity( (int) $session->id );
                    // Status options + selected value use the shared palette
                    // keys so the flat form, the legend and the read-only
                    // roster pills all agree on the vocabulary.
                    $status_keys = array_keys( self::attendanceStatusPalette() );
                    ?>
                    <p class="tt-act-form-attendance-note">
                        <?php esc_html_e( 'This activity is completed. Correct who attended below, then Update activity to save.', 'talenttrack' ); ?>
                    </p>
                    <?php if ( empty( $actual_rows ) ) : ?>
                        <p><em><?php esc_html_e( 'No players on this team yet.', 'talenttrack' ); ?></em></p>
                    <?php else : ?>
                        <table class="tt-table tt-attendance-table">
                            <thead><tr>
                                <th><?php esc_html_e( 'Player', 'talenttrack' ); ?></th>
                                <th><?php esc_html_e( 'Status', 'talenttrack' ); ?></th>
                                <th><?php esc_html_e( 'Note', 'talenttrack' ); ?></th>
                            </tr></thead>
                            <tbody>
                            <?php foreach ( $actual_rows as $arow ) :
                                $apid     = (int) $arow->player_id;
                                $a_status = strtolower( trim( (string) $arow->status ) );
                                if ( ! in_array( $a_status, $status_keys, true ) ) $a_status = 'present';
                                ?>
                                <tr class="tt-attendance-row">
                                    <td data-label="<?php esc_attr_e( 'Player', 'talenttrack' ); ?>"><?php echo esc_html( (string) $arow->name ); ?></td>
                                    <td data-label="<?php esc_attr_e( 'Status', 'talenttrack' ); ?>">
                                        <select class="tt-input tt-attendance-status" name="attendance[<?php echo $apid; ?>][status]">
                                            <?php foreach ( $status_keys as $sk ) : ?>
                                                <option value="<?php echo esc_attr( $sk ); ?>" <?php selected( $a_status, $sk ); ?>><?php echo esc_html( \TT\Infrastructure\Query\LabelTranslator::attendanceStatus( ucfirst( $sk ) ) ); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td data-label="<?php esc_attr_e( 'Note', 'talenttrack' ); ?>">
                                        <input type="text" class="tt-input" name="attendance[<?php echo $apid; ?>][notes]" value="<?php echo esc_attr( (string) $arow->notes ); ?>" />
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                    <?php if ( current_user_can( 'tt_edit_evaluations' ) && $completion_url !== '' ) : ?>
                        <p class="tt-act-form-attendance-note">
                            <a href="<?php echo esc_url( $completion_url ); ?>"><?php
                                // #2401 — names the real destination: the grid
                                // when the guided flow is switched off.
                                echo $guided_on
                                    ? esc_html__( 'Continue rating in the guided flow →', 'talenttrack' )
                                    : esc_html__( 'Open the attendance grid →', 'talenttrack' );
                            ?></a>
                        </p>
                    <?php endif; ?>

                <?php elseif ( current_user_can( 'tt_edit_evaluations' ) ) :
                    // Not-completed, or a match type (owned by the completion
                    // flow). Wording branches on whether the activity already
                    // happened so a completed match reads "review", never
                    // "will be captured".
                    // #2401 — and on `$guided_on`: with the wizard off,
                    // pointing the coach at "the guided completion flow"
                    // describes a surface they cannot reach. There,
                    // attendance lives in the desktop grid.
                    ?>
                    <p class="tt-act-form-attendance-note">
                        <?php if ( $guided_on ) {
                            echo $attendance_visible
                                ? esc_html__( 'Attendance and ratings were captured in the guided completion flow. Review or update them there.', 'talenttrack' )
                                : esc_html__( 'Attendance and ratings are captured in the guided completion flow, not on this form.', 'talenttrack' );
                        } else {
                            echo $attendance_visible
                                ? esc_html__( 'Attendance is recorded in the attendance grid. Open it to review or correct it.', 'talenttrack' )
                                : esc_html__( 'Attendance is recorded in the attendance grid, not on this form.', 'talenttrack' );
                        } ?>
                    </p>
                    <?php if ( $completion_url !== '' ) : ?>
                    <p>
                        <a class="tt-btn tt-btn-secondary" href="<?php echo esc_url( $completion_url ); ?>">
                            <?php if ( $guided_on ) {
                                echo $attendance_visible
                                    ? esc_html__( 'Continue rating', 'talenttrack' )
                                    : esc_html__( 'Complete activity', 'talenttrack' );
                            } else {
                                esc_html_e( 'Open attendance grid', 'talenttrack' );
                            } ?>
                        </a>
                    </p>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>

            <?php
            // #0037 — guest section renders in both modes. On create it
            // shows the table + button just like edit; the "+ Add guest"
            // click auto-saves the activity first (see guest-add.js),
            // redirects to the edit URL with `&open_guest=1`, and the
            // modal re-opens so the coach picks a guest in one motion.
            self::renderGuestSection( $is_edit && $session !== null ? (int) $session->id : 0, $guests );
            ?>

            <?php
            // #1638 — surface the created/changed audit footer on the edit
            // form too (already shown on the detail view via #1471). Renders
            // nothing for pre-audit rows with no recorded author.
            if ( $is_edit ) {
                echo '<div class="tt-act-detail__audit" style="margin:16px 0;">';
                \TT\Shared\Frontend\Components\AuditMeta::render( [
                    'created_by' => isset( $session->created_by ) ? (int) $session->created_by : 0,
                    'created_at' => (string) ( $session->created_at ?? '' ),
                    'updated_by' => isset( $session->updated_by ) ? (int) $session->updated_by : 0,
                    'updated_at' => (string) ( $session->updated_at ?? '' ),
                ] );
                echo '</div>';
            }
            ?>

            <?php
            // v3.110.58 — CLAUDE.md § 6.
            $dash_url   = \TT\Shared\Frontend\Components\RecordLink::dashboardUrl();
            $list_url   = add_query_arg( [ 'tt_view' => 'activities' ], $dash_url );
            $detail_url = ( $is_edit && $session !== null ) ? add_query_arg( [ 'tt_view' => 'activities', 'id' => (int) $session->id ], $dash_url ) : $list_url;
            $back       = \TT\Shared\Frontend\Components\BackLink::resolve();
            $cancel_url = $back !== null ? $back['url'] : ( $is_edit ? $detail_url : $list_url );
            echo FormSaveButton::render( [
                'label'      => $is_edit ? __( 'Update activity', 'talenttrack' ) : __( 'Save activity', 'talenttrack' ),
                'cancel_url' => $cancel_url,
            ] );
            ?>
            <div class="tt-form-msg"></div>
        </form>
        <?php

        // Guest add modal — outside the form so its inputs don't get
        // serialized into the activity PUT payload. The JS handler
        // POSTs to /activities/{id}/guests on submit; on create it
        // first auto-saves the activity then opens the modal.
        echo GuestAddModal::render( [
            'user_id'         => $user_id,
            'is_admin'        => $is_admin,
            'exclude_team_id' => $selected_team,
        ] );
        self::enqueueGuestAddAssets();
        self::enqueueEndTimeDefaultAssets();
    }

    // #1863 — prefill a match's end time to kick-off + 105 min on the
    // flat activity form. Match-only + prefill-once is enforced in the JS.
    private static function enqueueEndTimeDefaultAssets(): void {
        wp_enqueue_script(
            'tt-activity-end-time-default',
            plugins_url( 'assets/js/components/activity-end-time-default.js', TT_PLUGIN_FILE ),
            [],
            TT_VERSION,
            true
        );
    }

    /**
     * Guest attendance section — linked + anonymous rows under the
     * roster, plus the "+ Add guest" button.
     *
     * @param array<int, object> $guests
     */
    private static function renderGuestSection( int $activity_id, array $guests ): void {
        ?>
        <h3 style="margin:24px 0 12px;"><?php esc_html_e( 'Guests', 'talenttrack' ); ?></h3>
        <p class="tt-help-text" style="margin:-6px 0 12px; font-size:12px; color:#5b6470;">
            <?php esc_html_e( 'Players from outside the squad. Guests do not count toward team stats.', 'talenttrack' ); ?>
        </p>
        <div class="tt-attendance" data-tt-guest-session-id="<?php echo (int) $activity_id; ?>">
            <table class="tt-table tt-attendance-table tt-guest-table" data-tt-guest-table>
                <?php // #943 — column widths: 35ch Player / 10ch Status / rest Notes (mobile stacks via data-label) ?>
                <colgroup>
                    <col style="width: 35ch;">
                    <col style="width: 10ch;">
                    <col style="width: auto;">
                </colgroup>
                <thead><tr>
                    <th><?php esc_html_e( 'Player', 'talenttrack' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'talenttrack' ); ?></th>
                    <th><?php esc_html_e( 'Notes', 'talenttrack' ); ?></th>
                </tr></thead>
                <tbody>
                <?php if ( empty( $guests ) ) : ?>
                    <tr class="tt-attendance-row tt-attendance-row--empty" data-tt-guest-empty>
                        <td colspan="3" style="text-align:center; color:#5b6470; font-style:italic; padding:18px;">
                            <?php esc_html_e( 'No guests added yet.', 'talenttrack' ); ?>
                        </td>
                    </tr>
                <?php else : foreach ( $guests as $g ) :
                    $is_linked = ! empty( $g->guest_player_id );
                    if ( $is_linked ) {
                        $label = trim( (string) ( $g->_player_name ?? '' ) );
                        if ( $label === '' ) $label = __( 'Guest', 'talenttrack' );
                        $sub = (string) ( $g->_home_team ?? '' );
                    } else {
                        $label = (string) ( $g->guest_name ?? __( 'Guest', 'talenttrack' ) );
                        $sub   = __( '(unaffiliated)', 'talenttrack' );
                    }
                    ?>
                    <tr class="tt-attendance-row tt-attendance-row--guest" data-tt-attendance-id="<?php echo (int) $g->id; ?>" data-is-guest="1">
                        <td data-label="<?php esc_attr_e( 'Player', 'talenttrack' ); ?>">
                            <em><?php echo esc_html( $label ); ?></em>
                            <span class="tt-guest-badge"><?php esc_html_e( 'Guest', 'talenttrack' ); ?></span>
                            <?php if ( $sub !== '' ) : ?>
                                <div class="tt-guest-subline"><?php echo esc_html( $sub ); ?></div>
                            <?php endif; ?>
                        </td>
                        <td data-label="<?php esc_attr_e( 'Status', 'talenttrack' ); ?>">
                            <?php echo esc_html( LabelTranslator::attendanceStatus( (string) ( $g->status ?? 'Present' ) ) ); ?>
                        </td>
                        <td data-label="<?php esc_attr_e( 'Notes', 'talenttrack' ); ?>">
                            <?php // #943 — Evaluate shortcut removed for linked guests; they surface in
                                //  the existing "Continue rating" wizard flow via RateActorsStep alongside
                                //  roster players (per the shaping delta on #943).
                                //  "Add as player" promote shortcut removed for anon guests; promotion
                                //  is a broader process (trial → assess → assign team → permissions)
                                //  that doesn't belong on a single activity row. ?>
                            <input type="text" class="tt-input tt-guest-notes-input"
                                   data-tt-guest-notes-id="<?php echo (int) $g->id; ?>"
                                   data-initial="<?php echo esc_attr( (string) ( $g->guest_notes ?? '' ) ); ?>"
                                   value="<?php echo esc_attr( (string) ( $g->guest_notes ?? '' ) ); ?>"
                                   placeholder="<?php esc_attr_e( 'Notes…', 'talenttrack' ); ?>" />
                            <div class="tt-guest-row-actions" style="margin-top:6px; font-size:12px;">
                                <button type="button" class="tt-btn-link" data-tt-guest-remove="<?php echo (int) $g->id; ?>" style="color:#b32d2e;">
                                    <?php esc_html_e( 'Remove', 'talenttrack' ); ?>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
            <p style="margin-top:8px;">
                <button type="button" class="tt-btn tt-btn-secondary" data-tt-guest-modal-open>
                    + <?php esc_html_e( 'Add guest', 'talenttrack' ); ?>
                </button>
            </p>
        </div>
        <?php
    }

    private static function enqueueGuestAddAssets(): void {
        wp_enqueue_script(
            'tt-guest-add',
            plugins_url( 'assets/js/components/guest-add.js', TT_PLUGIN_FILE ),
            [],
            TT_VERSION,
            true
        );
        wp_localize_script( 'tt-guest-add', 'TT_GuestAdd', [
            'restNs'  => rest_url( 'talenttrack/v1' ),
            'nonce'   => wp_create_nonce( 'wp_rest' ),
            'strings' => [
                'guestBadge'      => __( 'Guest',          'talenttrack' ),
                'unaffiliated'    => __( '(unaffiliated)', 'talenttrack' ),
                'player'          => __( 'Player',         'talenttrack' ),
                'status'          => __( 'Status',         'talenttrack' ),
                'notes'           => __( 'Notes',          'talenttrack' ),
                'remove'          => __( 'Remove',         'talenttrack' ),
                // #943 — confirmRemove now drives the app dialog modal's
                // body; companion title + confirm strings keep the
                // standard ok/cancel triple.
                'confirmRemove'        => __( 'Remove this guest?',            'talenttrack' ),
                'confirmRemoveTitle'   => __( 'Remove guest',                  'talenttrack' ),
                'confirmRemoveConfirm' => __( 'Remove',                        'talenttrack' ),
                'confirmRemoveCancel'  => __( 'Cancel',                        'talenttrack' ),
                'linkedRequired'  => __( 'Pick a player.',              'talenttrack' ),
                'nameRequired'    => __( 'Name is required.',           'talenttrack' ),
                'saveFailed'      => __( 'Could not add guest.',        'talenttrack' ),
                'saveFirst'       => __( 'Saving activity first…', 'talenttrack' ),
                'networkError'    => __( 'Network error.',              'talenttrack' ),
                'notesPlaceholder'=> __( 'Notes…',                      'talenttrack' ),
                'linkedFallback'  => __( 'Guest',                       'talenttrack' ),
                'anonFallback'    => __( 'Guest',                       'talenttrack' ),
            ],
        ] );
    }

    private static function loadSession( int $id ): ?object {
        // v4.20.32 (#1190) — routed through ActivitiesRepository so the
        // on-screen view + ActivityBriefPdfExporter share one source of
        // truth. Pre-fix the two surfaces inlined $wpdb queries with
        // subtly different filter sets — same data-fork class as #1059.
        return ( new \TT\Modules\Activities\Repositories\ActivitiesRepository() )->findById( $id );
    }

    /**
     * #2183 — read-only detail loader that also resolves archived
     * activities. The active loadSession() filters `archived_at IS
     * NULL`, so an archived row would fall through to "not found" and
     * never surface its Restore action. This variant returns the row in
     * either state; the header branches on `archived_at`.
     */
    private static function loadSessionForDetail( int $id ): ?object {
        return ( new \TT\Modules\Activities\Repositories\ActivitiesRepository() )->findByIdIncludingArchived( $id );
    }

    /**
     * #1371 — build the create-form prefill object for
     * `?action=new&duplicate_from=<id>`. Copies title / time /
     * location / type (+ subtype / other-label) and the team; the
     * status resets to Planned, the tournament link is NOT copied,
     * notes are NOT copied, and attendance/eval rows never travel
     * (the form starts empty on create anyway). `duplicate_source_id`
     * rides along so the principles picker can pre-tick the source's
     * links.
     *
     * Target date: `?session_date=YYYY-MM-DD` when the caller picked a
     * cell (the planner's copy-last-weekday chip), else source + 7
     * days (the card's Duplicate action).
     *
     * Returns null when no (valid) source is referenced — the form
     * then renders the plain empty create state.
     */
    private static function duplicatePrefill(): ?object {
        $source_id = isset( $_GET['duplicate_from'] ) ? absint( $_GET['duplicate_from'] ) : 0;
        if ( $source_id <= 0 ) return null;
        $src = self::loadSession( $source_id );
        if ( $src === null ) return null;

        $url_date = isset( $_GET['session_date'] ) ? sanitize_text_field( (string) $_GET['session_date'] ) : '';
        $target   = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $url_date )
            ? $url_date
            : gmdate( 'Y-m-d', strtotime( (string) $src->session_date . ' +7 days' ) );

        $pre = new \stdClass();
        $pre->id                  = 0;
        $pre->duplicate_source_id = (int) $src->id;
        $pre->title               = (string) ( $src->title ?? '' );
        $pre->team_id             = (int) ( $src->team_id ?? 0 );
        $pre->session_date        = $target;
        $pre->start_time          = (string) ( $src->start_time ?? '' );
        $pre->end_time            = (string) ( $src->end_time ?? '' );
        $pre->location            = (string) ( $src->location ?? '' );
        $pre->activity_type_key   = (string) ( $src->activity_type_key ?? ActivityTypeKey::TRAINING );
        $pre->game_subtype_key    = (string) ( $src->game_subtype_key ?? '' );
        $pre->other_label         = (string) ( $src->other_label ?? '' );
        $pre->activity_status_key = ActivityStatusKey::PLANNED;
        return $pre;
    }

    /**
     * @return array<int, object> roster attendance rows keyed by player_id (excludes guests).
     */
    private static function loadAttendance( int $activity_id ): array {
        // v4.20.32 (#1190) — routed through ActivitiesRepository.
        return ( new \TT\Modules\Activities\Repositories\ActivitiesRepository() )->attendanceMapByPlayer( $activity_id );
    }

    /**
     * #0026 — Guest attendance rows for a session, decorated with the
     * linked player's display name + home team for render.
     *
     * @return array<int, object>
     */
    private static function loadGuests( int $activity_id ): array {
        global $wpdb; $p = $wpdb->prefix;
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT a.*, pl.first_name, pl.last_name, t.name AS home_team_name
             FROM {$p}tt_attendance a
             LEFT JOIN {$p}tt_players pl ON pl.id = a.guest_player_id
             LEFT JOIN {$p}tt_teams   t  ON t.id  = pl.team_id
             WHERE a.activity_id = %d AND a.is_guest = 1
             ORDER BY a.id ASC",
            $activity_id
        ) );
        $out = [];
        foreach ( $rows ?: [] as $r ) {
            if ( ! empty( $r->guest_player_id ) ) {
                $r->_player_name = trim( (string) $r->first_name . ' ' . (string) $r->last_name );
                $r->_home_team   = (string) ( $r->home_team_name ?? '' );
            }
            $out[] = $r;
        }
        return $out;
    }

    /**
     * #1324 — render the tournament info block on the detail view of
     * a tournament-typed activity. The tournament name + dates +
     * match count display unconditionally (informational); the
     * "Open tournament planner" deep-link is gated on
     * `tt_view_tournaments`. When no tournament is linked, the block
     * still renders so the operator sees the slot exists, with a hint
     * to link one in the edit form.
     */
    private static function renderDetailTournamentBlock( object $session ): void {
        $tournament = isset( $session->tournament ) && is_object( $session->tournament ) ? $session->tournament : null;
        echo '<div class="tt-act-card-d">';
        echo '<div class="tt-act-card-d__head"><h3 class="tt-act-card-d__title">'
            . esc_html__( 'Tournament', 'talenttrack' ) . '</h3></div>';
        echo '<div class="tt-act-card-d__body">';
        if ( ! $tournament ) {
            echo '<p class="tt-act-card-d__muted">'
                . esc_html__( 'Not linked yet. Use the edit form to pick an existing tournament.', 'talenttrack' )
                . '</p>';
            echo '</div></div>';
            return;
        }

        $name       = (string) ( $tournament->name ?? '' );
        $start_date = (string) ( $tournament->start_date ?? '' );
        $end_date   = (string) ( $tournament->end_date ?? '' );
        $match_n    = (int) ( $tournament->match_count ?? 0 );

        $date_label = $start_date;
        if ( $end_date !== '' && $end_date !== $start_date ) {
            $date_label .= ' – ' . $end_date;
        }
        $match_label = sprintf(
            /* translators: %d: tournament match count */
            _n( '%d match', '%d matches', $match_n, 'talenttrack' ),
            $match_n
        );

        echo '<p style="margin:0 0 4px; font-weight:600;">' . esc_html( $name ) . '</p>';
        echo '<p class="tt-act-card-d__muted" style="margin:0;">'
            . esc_html( $date_label . ' · ' . $match_label )
            . '</p>';

        if ( AuthorizationService::userCanOrMatrix( get_current_user_id(), 'tt_view_tournaments' ) ) {
            $planner_url = add_query_arg(
                [ 'tt_view' => 'tournaments', 'id' => (int) $tournament->id ],
                \TT\Shared\Frontend\Components\RecordLink::dashboardUrl()
            );
            echo '<p style="margin:6px 0 0;"><a href="' . esc_url( $planner_url ) . '">'
                . esc_html__( 'Open tournament planner →', 'talenttrack' )
                . '</a></p>';
        }
        echo '</div></div>';
    }

    /**
     * #1324 — tournament link picker on the activity edit form.
     * Renders a hidden block by default; shown when
     * activity_type_key === 'tournament' via the existing JS toggle.
     * Coach can link an existing tournament without holding
     * `tt_view_tournaments` (the picker exposes id + name + dates
     * only via the narrow `TournamentsRepository::listForTeamPicker`
     * shape). The "Create new tournament" CTA is admin-only, gated
     * on `tt_edit_tournaments`.
     */
    private static function renderFormTournamentPicker( int $team_id, int $selected_tournament_id, string $current_type ): void {
        $tournaments = [];
        if ( $team_id > 0 ) {
            $tournaments = ( new \TT\Modules\Tournaments\Repositories\TournamentsRepository() )
                ->listForTeamPicker( $team_id );
        }
        $can_create_tournament = AuthorizationService::userCanOrMatrix( get_current_user_id(), 'tt_edit_tournaments' );
        ?>
        <div class="tt-field" id="tt-activity-tournament-row" style="<?php echo $current_type === ActivityTypeKey::TOURNAMENT ? '' : 'display:none;'; ?>">
            <label class="tt-field-label" for="tt-activity-tournament">
                <?php esc_html_e( 'Tournament', 'talenttrack' ); ?>
            </label>
            <select id="tt-activity-tournament" class="tt-input" name="tournament_id">
                <option value="0"<?php selected( $selected_tournament_id, 0 ); ?>>
                    <?php esc_html_e( '— Not linked —', 'talenttrack' ); ?>
                </option>
                <?php foreach ( $tournaments as $t ) :
                    $tid   = (int) $t->id;
                    $label = (string) $t->name;
                    $dates = (string) $t->start_date;
                    if ( ! empty( $t->end_date ) && $t->end_date !== $t->start_date ) {
                        $dates .= ' – ' . (string) $t->end_date;
                    }
                ?>
                    <option value="<?php echo esc_attr( (string) $tid ); ?>"<?php selected( $selected_tournament_id, $tid ); ?>>
                        <?php echo esc_html( $label . ' (' . $dates . ')' ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ( $can_create_tournament ) :
                $wizard_url = add_query_arg(
                    [
                        'tt_view' => 'wizard',
                        'slug'    => 'new-tournament',
                        'team_id' => $team_id,
                    ],
                    \TT\Shared\Frontend\Components\RecordLink::dashboardUrl()
                );
                ?>
                <p class="tt-field-hint" style="margin:4px 0 0;">
                    <a href="<?php echo esc_url( $wizard_url ); ?>">
                        <?php esc_html_e( 'Create new tournament →', 'talenttrack' ); ?>
                    </a>
                </p>
            <?php else : ?>
                <p class="tt-field-hint" style="margin:4px 0 0; color:#5b6e75;">
                    <?php esc_html_e( 'No tournament yet? Ask an admin to create one in the Tournaments planner.', 'talenttrack' ); ?>
                </p>
            <?php endif; ?>
        </div>
        <?php
    }
}
