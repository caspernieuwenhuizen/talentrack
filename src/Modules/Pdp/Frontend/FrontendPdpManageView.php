<?php
namespace TT\Modules\Pdp\Frontend;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Domain\Vocabularies\Lookups\PdpStatus;
use TT\Domain\Vocabularies\Lookups\PdpVerdictDecision;
use TT\Infrastructure\Query\QueryHelpers;
use TT\Modules\Pdp\Repositories\PdpConversationsRepository;
use TT\Modules\Pdp\Repositories\PdpFilesRepository;
use TT\Modules\Pdp\Repositories\PdpVerdictsRepository;
use TT\Modules\Pdp\Repositories\SeasonsRepository;
use TT\Shared\Frontend\Components\FrontendListTable;
use TT\Shared\Frontend\Components\TeamPickerComponent;
use TT\Shared\Frontend\FrontendViewBase;

/**
 * FrontendPdpManageView — coach-facing PDP cycle UI.
 *
 *   ?tt_view=pdp                          — list of players' files in current season
 *   ?tt_view=pdp&id=<file_id>             — file detail (conversations + goals + verdict)
 *   ?tt_view=pdp&id=<file_id>&conv=<cid>  — single conversation form
 *   ?tt_view=pdp&id=<file_id>&action=verdict — verdict form
 *   ?tt_view=pdp&action=new&player_id=<n> — create file (auto-templates conversations)
 *
 * Mutations route through the REST endpoints (#0044 sprint 1) — this
 * view stays read-side + form-render only. Posts use the existing
 * `tt-ajax-form` JS that other manage views use.
 */
class FrontendPdpManageView extends FrontendViewBase {

    /**
     * #1293 — enqueue the shared frontend assets and the PDP-specific
     * inline Archive / Restore button handler. The handler is scoped
     * to the manage view because no other surface emits the
     * `[data-tt-pdp-archive]` / `[data-tt-pdp-restore]` selectors.
     */
    protected static function enqueueAssets(): void {
        parent::enqueueAssets();

        // #1686 — POP / PDP body restyle to the 2026 chrome look. Depends
        // on the global app-chrome stylesheet for the shared tokens +
        // KPI tile; loaded here (not in FrontendViewBase) because only
        // the PDP surfaces use it.
        wp_enqueue_style(
            'tt-frontend-pop',
            TT_PLUGIN_URL . 'assets/css/frontend-pop.css',
            [ 'tt-frontend-app-chrome' ],
            TT_VERSION
        );

        // #3303 — the conversation surface's own chrome (panes, lock banner,
        // self-reflection card). Was an inline attribute on each element.
        wp_enqueue_style(
            'tt-frontend-pdp-conversation',
            TT_PLUGIN_URL . 'assets/css/frontend-pdp-conversation.css',
            [ 'tt-frontend-app-chrome' ],
            TT_VERSION
        );

        wp_enqueue_script(
            'tt-pdp-archive-button',
            TT_PLUGIN_URL . 'assets/js/pdp-archive-button.js',
            [ 'tt-public' ],
            TT_VERSION,
            true
        );
        wp_localize_script(
            'tt-pdp-archive-button',
            'TT_PdpArchiveI18n',
            [
                'confirm_archive' => __( 'Archive this PDP?', 'talenttrack' ),
                'confirm_restore' => __( 'Restore this PDP?', 'talenttrack' ),
                'archived_toast'  => __( 'PDP archived', 'talenttrack' ),
                'restored_toast'  => __( 'PDP restored', 'talenttrack' ),
                'error_generic'   => __( 'Action failed.', 'talenttrack' ),
            ]
        );
    }

    public static function render( int $user_id, bool $is_admin ): void {
        self::enqueueAssets();

        $action  = isset( $_GET['action'] ) ? sanitize_key( (string) $_GET['action'] ) : '';
        $file_id = isset( $_GET['id'] )     ? absint( $_GET['id'] ) : 0;
        $conv_id = isset( $_GET['conv'] )   ? absint( $_GET['conv'] ) : 0;

        // v3.92.1 — breadcrumb chain. Action / id state determines depth.
        $pdp_label = __( 'PDP', 'talenttrack' );
        if ( $action === 'new' ) {
            \TT\Shared\Frontend\Components\FrontendBreadcrumbs::fromDashboard(
                __( 'New PDP file', 'talenttrack' ),
                [ \TT\Shared\Frontend\Components\FrontendBreadcrumbs::viewCrumb( 'pdp', $pdp_label ) ]
            );
        } elseif ( $action === 'permanent-delete' && $file_id > 0 ) {
            // #1294 — destructive subview gets its own breadcrumb label
            // so the chain reads "PDP → PDP file detail → Permanently
            // delete PDP". The intermediate crumb is the back affordance
            // to the file detail per CLAUDE.md § 5.
            \TT\Shared\Frontend\Components\FrontendBreadcrumbs::fromDashboard(
                __( 'Permanently delete PDP', 'talenttrack' ),
                [
                    \TT\Shared\Frontend\Components\FrontendBreadcrumbs::viewCrumb( 'pdp', $pdp_label ),
                    [
                        'label' => __( 'PDP file detail', 'talenttrack' ),
                        'url'   => add_query_arg(
                            [ 'tt_view' => 'pdp', 'id' => $file_id ],
                            \TT\Shared\Frontend\Components\RecordLink::dashboardUrl()
                        ),
                    ],
                ]
            );
        } elseif ( $file_id > 0 && $conv_id > 0 ) {
            // #2038 — a conversation sits one level below the PDP file.
            // Without its own crumb it reused the file-detail chain, whose
            // only clickable intermediate is the PDP *list* — so "back"
            // dropped the user out to every player's PDPs instead of the
            // file they were in. Add the file-detail crumb as the
            // intermediate (back-to-file) affordance per CLAUDE.md §5:
            // "PDP → PDP file detail → Conversation".
            \TT\Shared\Frontend\Components\FrontendBreadcrumbs::fromDashboard(
                __( 'Conversation', 'talenttrack' ),
                [
                    \TT\Shared\Frontend\Components\FrontendBreadcrumbs::viewCrumb( 'pdp', $pdp_label ),
                    [
                        'label' => __( 'PDP file detail', 'talenttrack' ),
                        'url'   => add_query_arg(
                            [ 'tt_view' => 'pdp', 'id' => $file_id ],
                            \TT\Shared\Frontend\Components\RecordLink::dashboardUrl()
                        ),
                    ],
                ]
            );
        } elseif ( $file_id > 0 ) {
            \TT\Shared\Frontend\Components\FrontendBreadcrumbs::fromDashboard(
                __( 'PDP file detail', 'talenttrack' ),
                [ \TT\Shared\Frontend\Components\FrontendBreadcrumbs::viewCrumb( 'pdp', $pdp_label ) ]
            );
        } else {
            \TT\Shared\Frontend\Components\FrontendBreadcrumbs::fromDashboard( $pdp_label );
        }

        // #1294 — flash notices surfaced by the admin-post handler land
        // on the manage list (`deleted`) or the confirm form
        // (`name_mismatch`, `csv_failed`, `cascade_failed`). The form
        // renderer reads its own notice; here we surface the success /
        // not-found notice on the list view.
        if ( $action === '' && $file_id === 0 ) {
            self::renderPermanentDeleteFlashNotices();
        }

        if ( $action === 'new' ) {
            self::renderCreateForm( $user_id, $is_admin );
            return;
        }

        if ( $file_id > 0 ) {
            // #1294 — the permanent-delete confirm surface needs to
            // resolve archived files too (the common path is "archive
            // first, then permanently delete later"). Other actions
            // keep the historical default (hide archived) so the
            // existing detail / conversation / verdict flows still
            // honour the soft-archive contract.
            $include_archived = ( $action === 'permanent-delete' );
            $file = ( new PdpFilesRepository() )->find( $file_id, $include_archived );
            if ( ! $file ) {
                self::renderHeader( __( 'PDP file not found', 'talenttrack' ) );
                echo '<p class="tt-notice">' . esc_html__( 'That PDP file no longer exists.', 'talenttrack' ) . '</p>';
                return;
            }
            if ( ! self::canSeeFile( $file, $user_id, $is_admin ) ) {
                self::renderHeader( __( 'Access denied', 'talenttrack' ) );
                echo '<p class="tt-notice">' . esc_html__( 'You do not have access to this PDP file.', 'talenttrack' ) . '</p>';
                return;
            }

            // #1294 — destructive confirm surface. Cap-gated on
            // `tt_delete_pdp` (admin-only by seed). Falls through to
            // `canSeeFile` above for the view-only ladder; the cap
            // re-check here is what prevents a coach who can SEE the
            // file from triggering the surface.
            if ( $action === 'permanent-delete' ) {
                if ( ! current_user_can( 'tt_delete_pdp' ) ) {
                    self::renderHeader( __( 'Access denied', 'talenttrack' ) );
                    echo '<p class="tt-notice">' . esc_html__( 'You do not have permission to permanently delete PDP files.', 'talenttrack' ) . '</p>';
                    return;
                }
                self::renderPermanentDeleteForm( $file );
                return;
            }

            if ( $conv_id > 0 ) {
                self::renderConversationForm( $file, $conv_id, $user_id, $is_admin );
                return;
            }
            if ( $action === 'verdict' ) {
                self::renderVerdictForm( $file, $user_id, $is_admin );
                return;
            }
            self::renderFileDetail( $file, $user_id, $is_admin );
            return;
        }

        self::renderList( $user_id, $is_admin );
    }

    /**
     * #1294 — flash notices set by `PdpHardDeleteAdminPost` after a
     * successful or failed hard-delete redirect. Rendered above the
     * list table so the operator sees confirmation immediately.
     */
    private static function renderPermanentDeleteFlashNotices(): void {
        $code = isset( $_GET['tt_notice'] ) ? sanitize_key( (string) $_GET['tt_notice'] ) : '';
        if ( $code === '' ) return;
        $csv  = isset( $_GET['csv'] ) ? (string) wp_unslash( $_GET['csv'] ) : '';
        switch ( $code ) {
            case 'deleted':
                echo '<div class="tt-notice tt-notice-success" style="padding:12px 14px; border-radius:6px; background:#ecfdf5; border:1px solid #a7f3d0; margin-bottom:16px;">';
                echo '<p style="margin:0;">' . esc_html__( 'PDP file permanently deleted.', 'talenttrack' ) . '</p>';
                if ( $csv !== '' ) {
                    echo '<p style="margin:6px 0 0; font-size:13px; color:#065f46;">' . esc_html( sprintf(
                        /* translators: %s = absolute CSV file path */
                        __( 'Pre-delete CSV saved to %s', 'talenttrack' ),
                        $csv
                    ) ) . '</p>';
                }
                echo '</div>';
                break;
            case 'not_found':
                echo '<div class="tt-notice" style="padding:12px 14px; border-radius:6px; background:#fef2f2; border:1px solid #fecaca; margin-bottom:16px;">';
                echo '<p style="margin:0;">' . esc_html__( 'That PDP file no longer exists.', 'talenttrack' ) . '</p>';
                echo '</div>';
                break;
        }
    }

    /**
     * #1294 — destructive double-confirm surface. Renders the cascade
     * summary, then a typed-name confirm form. The submit button stays
     * disabled until the operator types the player's display name
     * verbatim.
     *
     * Cancel target per CLAUDE.md § 6 — back to the PDP file detail
     * (or whatever `tt_back` captured on the way in).
     */
    private static function renderPermanentDeleteForm( object $file ): void {
        $file_id = (int) $file->id;
        $player  = \TT\Infrastructure\Query\QueryHelpers::get_player( (int) $file->player_id );
        $player_name = $player ? \TT\Infrastructure\Query\QueryHelpers::player_display_name( $player ) : '';
        $season  = ( new SeasonsRepository() )->find( (int) $file->season_id );
        $season_name = $season ? (string) $season->name : '';

        $summary = \TT\Modules\Pdp\PdpHardDeleteAdminPost::cascadeSummary( $file_id );

        $title = $player_name !== ''
            ? sprintf(
                /* translators: %s = player display name */
                __( 'Permanently delete PDP — %s', 'talenttrack' ),
                $player_name
            )
            : __( 'Permanently delete PDP', 'talenttrack' );
        self::renderHeader( $title );

        // Flash notice from a failed previous attempt (typed-name
        // mismatch / CSV failure / cascade failure).
        $notice = isset( $_GET['tt_notice'] ) ? sanitize_key( (string) $_GET['tt_notice'] ) : '';
        if ( $notice === 'name_mismatch' ) {
            echo '<div class="tt-notice" style="padding:12px 14px; border-radius:6px; background:#fef2f2; border:1px solid #fecaca; margin-bottom:16px;">';
            echo '<p style="margin:0;">' . esc_html__( 'The name you typed did not match the player. Try again.', 'talenttrack' ) . '</p>';
            echo '</div>';
        } elseif ( $notice === 'csv_failed' ) {
            echo '<div class="tt-notice" style="padding:12px 14px; border-radius:6px; background:#fef2f2; border:1px solid #fecaca; margin-bottom:16px;">';
            echo '<p style="margin:0;">' . esc_html__( 'Pre-delete CSV export failed. Nothing was deleted.', 'talenttrack' ) . '</p>';
            echo '</div>';
        } elseif ( $notice === 'cascade_failed' ) {
            echo '<div class="tt-notice" style="padding:12px 14px; border-radius:6px; background:#fef2f2; border:1px solid #fecaca; margin-bottom:16px;">';
            echo '<p style="margin:0;">' . esc_html__( 'The cascade delete failed. The transaction was rolled back; nothing changed.', 'talenttrack' ) . '</p>';
            echo '</div>';
        }

        // Warning banner.
        echo '<div class="tt-notice" style="padding:14px 16px; border-radius:8px; background:#fff7ed; border:1px solid #fdba74; margin-bottom:20px;">';
        echo '<p style="margin:0; font-weight:600; color:#9a3412;">' . esc_html__( 'This action is irreversible.', 'talenttrack' ) . '</p>';
        echo '<p style="margin:8px 0 0; color:#7c2d12;">' . esc_html__( 'Every conversation, verdict, calendar link, and goal-link row tied to this PDP file will be permanently removed from the database. A pre-delete CSV snapshot will be written to wp-content/uploads/tt-pdp-deletes/ before the cascade runs.', 'talenttrack' ) . '</p>';
        echo '</div>';

        // Cascade summary card.
        echo '<div class="tt-card" style="background:#fff; border:1px solid #e5e7ea; border-radius:8px; padding:16px; margin-bottom:20px;">';
        echo '<h2 style="font-size:16px; margin:0 0 12px;">' . esc_html__( 'Cascade summary', 'talenttrack' ) . '</h2>';
        echo '<ul style="margin:0; padding-left:20px; line-height:1.7;">';
        if ( $player_name !== '' ) {
            echo '<li>' . esc_html( sprintf( /* translators: %s = player name */ __( 'Player: %s', 'talenttrack' ), $player_name ) ) . '</li>';
        }
        if ( $season_name !== '' ) {
            echo '<li>' . esc_html( sprintf( /* translators: %s = season name */ __( 'Season: %s', 'talenttrack' ), $season_name ) ) . '</li>';
        }
        echo '<li>' . esc_html( sprintf(
            /* translators: %d = count */
            _n( '%d conversation will be removed', '%d conversations will be removed', $summary['conversations'], 'talenttrack' ),
            $summary['conversations']
        ) ) . '</li>';
        echo '<li>' . esc_html( sprintf(
            /* translators: %d = count */
            _n( '%d verdict will be removed', '%d verdicts will be removed', $summary['verdict'], 'talenttrack' ),
            $summary['verdict']
        ) ) . '</li>';
        echo '<li>' . esc_html( sprintf(
            /* translators: %d = count */
            _n( '%d calendar link will be removed', '%d calendar links will be removed', $summary['calendar_links'], 'talenttrack' ),
            $summary['calendar_links']
        ) ) . '</li>';
        echo '<li>' . esc_html( sprintf(
            /* translators: %d = count */
            _n( '%d PDP block will be removed', '%d PDP blocks will be removed', $summary['blocks'], 'talenttrack' ),
            $summary['blocks']
        ) ) . '</li>';
        echo '<li>' . esc_html( sprintf(
            /* translators: %d = count */
            _n( '%d goal link will be removed', '%d goal links will be removed', $summary['goal_links'], 'talenttrack' ),
            $summary['goal_links']
        ) ) . '</li>';
        echo '<li style="margin-top:8px; border-top:1px solid #e5e7ea; padding-top:8px;"><strong>' . esc_html( sprintf(
            /* translators: %d = total cascade row count */
            __( 'Total related rows: %d', 'talenttrack' ),
            $summary['total']
        ) ) . '</strong></li>';
        echo '</ul>';
        echo '</div>';

        // Typed-name confirm form. Cancel target follows the
        // CLAUDE.md § 6 rule: prefer tt_back, else fall back to the
        // file detail page.
        $cancel_url = '';
        $resolved   = \TT\Shared\Frontend\Components\BackLink::resolve();
        if ( $resolved !== null ) {
            $cancel_url = $resolved['url'];
        } else {
            $cancel_url = add_query_arg(
                [ 'tt_view' => 'pdp', 'id' => $file_id ],
                \TT\Shared\Frontend\Components\RecordLink::dashboardUrl()
            );
        }

        $form_action = esc_url( admin_url( 'admin-post.php' ) );
        $nonce_field = wp_nonce_field(
            \TT\Modules\Pdp\PdpHardDeleteAdminPost::ACTION . '_' . $file_id,
            \TT\Modules\Pdp\PdpHardDeleteAdminPost::NONCE,
            true,
            false
        );
        $expected_attr = esc_attr( $player_name );
        ?>
        <form method="post" action="<?php echo $form_action; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — admin_url() escaped above. ?>" class="tt-pdp-perm-delete-form">
            <input type="hidden" name="action" value="<?php echo esc_attr( \TT\Modules\Pdp\PdpHardDeleteAdminPost::ACTION ); ?>" />
            <input type="hidden" name="pdp_file_id" value="<?php echo (int) $file_id; ?>" />
            <?php echo $nonce_field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — wp_nonce_field returns escaped HTML. ?>

            <div class="tt-field" style="margin-bottom:16px;">
                <label class="tt-field-label tt-field-required" for="tt-pdp-confirm-name">
                    <?php echo esc_html( sprintf(
                        /* translators: %s = expected player name */
                        __( 'Type the player\'s name to confirm: %s', 'talenttrack' ),
                        $player_name
                    ) ); ?>
                </label>
                <input
                    type="text"
                    id="tt-pdp-confirm-name"
                    name="confirm_name"
                    class="tt-input"
                    autocomplete="off"
                    autocapitalize="words"
                    inputmode="text"
                    spellcheck="false"
                    required
                    data-expected="<?php echo $expected_attr; ?>"
                    style="font-size:16px; min-height:48px;"
                />
                <p style="margin:6px 0 0; color:#5b6e75; font-size:13px;">
                    <?php esc_html_e( 'The delete button stays disabled until the typed name matches exactly (case-insensitive).', 'talenttrack' ); ?>
                </p>
            </div>

            <div class="tt-form-actions" style="display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
                <a href="<?php echo esc_url( $cancel_url ); ?>" class="tt-btn tt-btn-secondary" style="min-height:48px; padding:8px 16px; touch-action:manipulation;">
                    <?php esc_html_e( 'Cancel', 'talenttrack' ); ?>
                </a>
                <button
                    type="submit"
                    class="tt-btn tt-btn-danger"
                    id="tt-pdp-perm-delete-submit"
                    disabled
                    style="min-height:48px; padding:8px 16px; touch-action:manipulation; background:#b91c1c; color:#fff; border:1px solid #991b1b;"
                >
                    <?php esc_html_e( 'Permanently delete PDP', 'talenttrack' ); ?>
                </button>
            </div>
        </form>

        <script>
            (function () {
                var form   = document.querySelector('form.tt-pdp-perm-delete-form');
                if (!form) return;
                var input  = form.querySelector('#tt-pdp-confirm-name');
                var submit = form.querySelector('#tt-pdp-perm-delete-submit');
                if (!input || !submit) return;
                var expected = (input.dataset.expected || '').trim().replace(/\s+/g, ' ').toLowerCase();
                function normalise(v) {
                    return String(v || '').trim().replace(/\s+/g, ' ').toLowerCase();
                }
                function apply() {
                    submit.disabled = (expected === '' || normalise(input.value) !== expected);
                }
                input.addEventListener('input', apply);
                form.addEventListener('submit', function (ev) {
                    if (submit.disabled) {
                        ev.preventDefault();
                        return;
                    }
                    // Final native confirm so an accidental Enter on a
                    // matched typed name still gets one last "are you
                    // sure?" gate.
                    var msg = '<?php echo esc_js( __( 'This will permanently delete the PDP file and all related rows. Continue?', 'talenttrack' ) ); ?>';
                    if (!window.confirm(msg)) { ev.preventDefault(); }
                });
                apply();
            })();
        </script>
        <?php
    }

    /**
     * #1617 — player-centric PDP coverage list for the current season.
     * One row per coach-scoped player with a created / not-started
     * indicator + one-click create for the missing ones. Coverage data
     * comes from the REST endpoint (`pdp-files/coverage`) via
     * FrontendListTable; the summary line is computed server-side from
     * the repository for the initial scope. Business logic lives in
     * PdpFilesRepository::coverageForSeason() per CLAUDE.md §4.
     */
    private static function renderCoverageList( int $user_id, bool $is_admin, object $current ): void {
        $new_url = add_query_arg(
            [ 'tt_view' => 'pdp', 'action' => 'new' ],
            remove_query_arg( [ 'action', 'id', 'conv', 'player_id' ] )
        );
        $page_actions = [];
        if ( current_user_can( 'tt_edit_pdp' ) ) {
            $page_actions[] = [
                'label'   => __( 'Open new PDP file', 'talenttrack' ),
                'href'    => $new_url,
                'primary' => true,
                'icon'    => '+',
            ];
        }
        self::renderHeader( __( 'Player Development Plans', 'talenttrack' ), self::pageActionsHtml( $page_actions ) );

        // #2040 — team-selection gate. Users who span multiple teams (or hold
        // global PDP scope, so TeamPickerComponent hands them every team) pick
        // a team before the roster loads, instead of facing an unscoped
        // all-players list. A single-team coach (one option) skips the gate.
        $team_options  = TeamPickerComponent::filterOptions( $user_id, $is_admin );
        $selected_team = isset( $_GET['filter']['team_id'] ) ? absint( $_GET['filter']['team_id'] ) : 0;
        if ( count( $team_options ) > 1 && $selected_team <= 0 ) {
            self::renderTeamGate( $team_options );
            return;
        }

        // #2040 — Active / Archived record-state view, for operators who can
        // act on archived files. The state control is the FilterBar status
        // pills rendered by FrontendListTable below (#2083) via the coverage
        // list's `filter[archived]` group; here we only read the resulting
        // param so the summary + "only missing" block (active view only)
        // gates correctly. Non-operators are pinned to the active view.
        $can_see_archived = current_user_can( 'tt_unarchive_pdp' ) || current_user_can( 'tt_delete_pdp' );
        $archived_param   = '';
        if ( isset( $_GET['filter'] ) && is_array( $_GET['filter'] ) && isset( $_GET['filter']['archived'] ) ) {
            $archived_param = sanitize_key( wp_unslash( (string) $_GET['filter']['archived'] ) );
        }
        $archived_view = ( $can_see_archived && $archived_param === 'archived' ) ? 'archived' : 'active';

        $only_missing = false;
        if ( $archived_view === 'active' ) {
            // Coverage summary line ("M of N players have a PDP this season"),
            // scoped the same way the REST endpoint scopes.
            $summary_filters = [ 'team_id' => $selected_team ];
            if ( ! $is_admin ) {
                $summary_filters['player_ids'] = self::coachScopedPlayerIds( $user_id );
            }
            $summary = ( new PdpFilesRepository() )->coverageSummaryForSeason( (int) $current->id, $summary_filters );
            echo '<p class="tt-pdp-coverage-summary">' . esc_html( sprintf(
                /* translators: 1: covered player count, 2: total player count, 3: season name */
                __( '%1$d of %2$d players have a PDP for the current season (%3$s).', 'talenttrack' ),
                (int) $summary['covered'],
                (int) $summary['total'],
                (string) $current->name
            ) ) . '</p>';

            // "Only missing" quick filter (active view only).
            $only_missing = ! empty( $_GET['only_missing'] );
            $toggle_url   = $only_missing ? remove_query_arg( 'only_missing' ) : add_query_arg( 'only_missing', '1' );
            $toggle_label = $only_missing ? __( 'Show all players', 'talenttrack' ) : __( 'Only players without a PDP', 'talenttrack' );
            echo '<p class="tt-pdp-coverage-toggle"><a class="tt-btn tt-btn-secondary" href="' . esc_url( $toggle_url ) . '">' . esc_html( $toggle_label ) . '</a></p>';
        }

        $columns = [
            'player_name' => [ 'label' => __( 'Player', 'talenttrack' ), 'sortable' => false, 'render' => 'html', 'value_key' => 'player_link_html' ],
            'team_name'   => [ 'label' => __( 'Team',   'talenttrack' ), 'sortable' => false, 'render' => 'html', 'value_key' => 'team_link_html' ],
            'coverage'    => [ 'label' => __( 'PDP this season', 'talenttrack' ), 'render' => 'html', 'value_key' => 'coverage_html' ],
            'actions'     => [ 'label' => __( 'Actions', 'talenttrack' ), 'render' => 'html', 'value_key' => 'actions_html' ],
        ];

        $filters = [
            'team_id' => [
                'type'    => 'select',
                'label'   => __( 'Team', 'talenttrack' ),
                'options' => $team_options,
            ],
        ];
        // #2083 — Active / Archived record-state pills, surfaced only to
        // operators who can act on archived files. Replaces the bespoke
        // `.tt-pdp-statepill` links (#2040) with the shared FilterBar status
        // pills; the `filter[archived]` param + coverage endpoint are
        // unchanged (the endpoint already reads `filter[archived]`).
        if ( $can_see_archived ) {
            $filters['archived'] = [
                'type'    => 'select',
                'render'  => 'status',
                'label'   => __( 'Archive', 'talenttrack' ),
                'options' => [
                    'active'   => __( 'Active',   'talenttrack' ),
                    'archived' => __( 'Archived', 'talenttrack' ),
                ],
            ];
        }

        $list_config = [
            'rest_path' => 'pdp-files/coverage',
            'columns'   => $columns,
            'filters'   => $filters,
            'search'      => [ 'placeholder' => __( 'Search player…', 'talenttrack' ) ],
            'empty_state' => $archived_view === 'archived'
                ? __( 'No archived PDP files.', 'talenttrack' )
                : __( 'No players match your filters.', 'talenttrack' ),
            'row_url_key' => 'detail_url',
        ];
        $static = [];
        if ( $only_missing ) {
            $static['only_missing'] = '1';
        }
        if ( $static ) {
            $list_config['static_filters'] = $static;
        }

        echo FrontendListTable::render( $list_config ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — render() returns escaped HTML.
    }

    /**
     * #2040 — team-selection gate. Renders a single team picker + prompt for
     * multi-team / global-scope users so they choose a team before the roster
     * loads. The select submits `filter[team_id]`, which both this gate and
     * FrontendListTable read, so the list lands scoped on submit.
     *
     * @param array<int,string> $team_options id => name
     */
    private static function renderTeamGate( array $team_options ): void {
        $action = remove_query_arg( [ 'action', 'id', 'conv', 'player_id', 'only_missing', 'archived' ] );
        echo '<div class="tt-pdp-team-gate">';
        echo '<p class="tt-pdp-team-gate__hint">' . esc_html__( 'Select a team to see its players.', 'talenttrack' ) . '</p>';
        echo '<form method="get" action="' . esc_url( $action ) . '" class="tt-pdp-team-gate__form">';
        echo '<input type="hidden" name="tt_view" value="pdp" />';
        $back = isset( $_GET['tt_back'] ) ? sanitize_text_field( (string) wp_unslash( $_GET['tt_back'] ) ) : '';
        if ( $back !== '' ) {
            echo '<input type="hidden" name="tt_back" value="' . esc_attr( $back ) . '" />';
        }
        echo '<label class="tt-field-label" for="tt-pdp-gate-team">' . esc_html__( 'Team', 'talenttrack' ) . '</label>';
        echo '<select id="tt-pdp-gate-team" name="filter[team_id]" class="tt-input">';
        echo '<option value="">' . esc_html__( 'Choose a team…', 'talenttrack' ) . '</option>';
        foreach ( $team_options as $tid => $tname ) {
            echo '<option value="' . esc_attr( (string) (int) $tid ) . '">' . esc_html( (string) $tname ) . '</option>';
        }
        echo '</select>';
        echo '<button type="submit" class="tt-btn tt-btn-primary">' . esc_html__( 'Show players', 'talenttrack' ) . '</button>';
        echo '</form>';
        echo '</div>';
    }

    /**
     * #1617 — flat list of player ids on the coach's own teams, used to
     * scope the coverage summary the same way the REST endpoint scopes.
     *
     * @return int[]
     */
    private static function coachScopedPlayerIds( int $user_id ): array {
        $ids = [];
        foreach ( QueryHelpers::get_teams_for_coach( $user_id ) as $t ) {
            foreach ( QueryHelpers::get_players( (int) $t->id ) as $pl ) {
                $ids[ (int) $pl->id ] = (int) $pl->id;
            }
        }
        return array_values( $ids );
    }

    private static function renderList( int $user_id, bool $is_admin ): void {
        $current = ( new SeasonsRepository() )->current();
        if ( ! $current ) {
            self::renderHeader( __( 'Player Development Plans', 'talenttrack' ) );
            echo '<p class="tt-notice">' . esc_html__( 'No current season is set. Configure a season under Configuration → Seasons before creating PDP files.', 'talenttrack' ) . '</p>';
            return;
        }

        // #2040 — single player-centric list. The old Coverage / Files tab
        // split is gone; archived files are reached via the Active / Archived
        // state pills inside the coverage list itself, not a separate tab.
        self::renderCoverageList( $user_id, $is_admin, $current );
    }

    private static function renderCreateForm( int $user_id, bool $is_admin ): void {
        self::renderHeader( __( 'Open new PDP file', 'talenttrack' ) );

        $current = ( new SeasonsRepository() )->current();
        if ( ! $current ) {
            echo '<p class="tt-notice">' . esc_html__( 'No current season is set. Configure one before opening a PDP file.', 'talenttrack' ) . '</p>';
            return;
        }

        // Resolve the player roster, scoped to the coach's teams
        // (admins see every player). Used to populate the player
        // dropdown that filters client-side on team change.
        $players = self::eligiblePlayerObjects( $user_id, $is_admin );
        if ( empty( $players ) ) {
            echo '<p class="tt-notice">' . esc_html__( 'No players available. Coaches can only open PDP files for players on their own teams.', 'talenttrack' ) . '</p>';
            return;
        }

        // Pre-fill team filter when the user landed here from a team
        // page (?team_id=N). Pre-fill player when entered from a
        // player profile (?player_id=N).
        $preset_team   = isset( $_GET['team_id'] )   ? absint( $_GET['team_id'] )   : 0;
        $preset_player = isset( $_GET['player_id'] ) ? absint( $_GET['player_id'] ) : 0;

        // v3.110.110 — pilot ask: replace the search-as-you-type
        // picker with a classic two-dropdown cascade. Team dropdown
        // narrows the player dropdown to its roster; an "All teams"
        // selection shows every player the coach has access to. The
        // search picker (PlayerSearchPickerComponent) is still the
        // right choice for surfaces with hundreds of options (admin
        // scout pages, comparison view); on the PDP create form the
        // coach's eligible-player roster is typically small (a single
        // team's worth) and the dropdown is faster than typing.
        //
        // If `?player_id=N` was passed (entered from a player file),
        // the team dropdown is preselected to that player's team and
        // the player is preselected; the operator can still change
        // either. Coach-scope enforcement happens server-side in the
        // REST controller — the dropdown is convenience, not a
        // security boundary.
        $teams_for_filter = [];
        foreach ( $players as $pl ) {
            $tid = (int) ( $pl->team_id ?? 0 );
            if ( $tid <= 0 || isset( $teams_for_filter[ $tid ] ) ) continue;
            $t = QueryHelpers::get_team( $tid );
            if ( $t ) $teams_for_filter[ $tid ] = (string) $t->name;
        }
        asort( $teams_for_filter, SORT_NATURAL | SORT_FLAG_CASE );

        // Resolve the preselected team — explicit `?team_id=N`, else
        // derived from `?player_id=N`'s team membership.
        $selected_team = $preset_team;
        if ( $selected_team === 0 && $preset_player > 0 && isset( $players[ $preset_player ] ) ) {
            $selected_team = (int) ( $players[ $preset_player ]->team_id ?? 0 );
        }
        ?>
        <form class="tt-ajax-form" data-rest-path="pdp-files" data-rest-method="POST" data-redirect-after-save="list">
            <div class="tt-grid tt-grid-2">
                <div class="tt-field">
                    <label class="tt-field-label" for="tt-pdp-team-filter"><?php esc_html_e( 'Team', 'talenttrack' ); ?></label>
                    <select id="tt-pdp-team-filter" class="tt-input" data-tt-pdp-team-filter>
                        <option value="0"><?php esc_html_e( 'All teams', 'talenttrack' ); ?></option>
                        <?php foreach ( $teams_for_filter as $tid => $tname ) : ?>
                            <option value="<?php echo (int) $tid; ?>" <?php selected( $selected_team, (int) $tid ); ?>><?php echo esc_html( $tname ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="tt-field">
                    <label class="tt-field-label tt-field-required" for="tt-pdp-player-picker"><?php esc_html_e( 'Player', 'talenttrack' ); ?></label>
                    <select id="tt-pdp-player-picker" name="player_id" class="tt-input" required data-tt-pdp-player-picker>
                        <option value=""><?php esc_html_e( '— Select a player —', 'talenttrack' ); ?></option>
                        <?php foreach ( $players as $pl ) :
                            $pid     = (int) $pl->id;
                            $pteam   = (int) ( $pl->team_id ?? 0 );
                            $pname   = QueryHelpers::player_display_name( $pl );
                            ?>
                            <option value="<?php echo $pid; ?>"
                                    data-team-id="<?php echo $pteam; ?>"
                                    <?php selected( $preset_player, $pid ); ?>>
                                <?php echo esc_html( $pname ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <script>
                (function () {
                    // v3.110.110 — team-dropdown filters player-dropdown
                    // options. Player options carry data-team-id so the
                    // filter is fully client-side; "All teams" (value 0)
                    // unhides everything. The select keeps its current
                    // value when valid; otherwise resets to placeholder
                    // so the form's `required` re-applies.
                    var team = document.getElementById('tt-pdp-team-filter');
                    var player = document.getElementById('tt-pdp-player-picker');
                    if (!team || !player) return;
                    function apply() {
                        var tid = parseInt(team.value, 10) || 0;
                        var currentValid = false;
                        Array.prototype.forEach.call(player.options, function (opt) {
                            if (!opt.value) { opt.hidden = false; return; }
                            var show = tid === 0 || parseInt(opt.dataset.teamId, 10) === tid;
                            opt.hidden = !show;
                            if (show && opt.value === player.value) currentValid = true;
                        });
                        if (!currentValid) player.value = '';
                    }
                    team.addEventListener('change', apply);
                    apply();
                })();
            </script>
            <div class="tt-field">
                <label class="tt-field-label" for="tt-pdp-cycle"><?php esc_html_e( 'Conversations this season', 'talenttrack' ); ?></label>
                <select id="tt-pdp-cycle" name="cycle_size" class="tt-input">
                    <option value=""><?php esc_html_e( 'Use club / team default', 'talenttrack' ); ?></option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                </select>
            </div>
            <div class="tt-field">
                <label class="tt-field-label" for="tt-pdp-notes"><?php esc_html_e( 'Notes', 'talenttrack' ); ?></label>
                <textarea id="tt-pdp-notes" name="notes" class="tt-input" rows="3"></textarea>
            </div>
            <div class="tt-form-actions" style="margin-top:16px;">
                <button type="submit" class="tt-btn tt-btn-primary"><?php esc_html_e( 'Open file', 'talenttrack' ); ?></button>
                <a href="<?php echo esc_url( remove_query_arg( [ 'action' ] ) ); ?>" class="tt-btn tt-btn-secondary"><?php esc_html_e( 'Cancel', 'talenttrack' ); ?></a>
            </div>
            <div class="tt-form-msg"></div>
        </form>
        <?php
    }

    /**
     * Same coach-scope rules as `eligiblePlayers()` but returns the raw
     * player objects (so PlayerSearchPickerComponent can derive labels
     * + team metadata for its team-filter dropdown).
     *
     * @return array<int, object>
     */
    private static function eligiblePlayerObjects( int $user_id, bool $is_admin ): array {
        $out = [];
        if ( $is_admin ) {
            foreach ( QueryHelpers::get_players() as $pl ) {
                $out[ (int) $pl->id ] = $pl;
            }
            return $out;
        }
        foreach ( QueryHelpers::get_teams_for_coach( $user_id ) as $t ) {
            foreach ( QueryHelpers::get_players( (int) $t->id ) as $pl ) {
                $out[ (int) $pl->id ] = $pl;
            }
        }
        return $out;
    }

    private static function renderFileDetail( object $file, int $user_id, bool $is_admin ): void {
        $player = QueryHelpers::get_player( (int) $file->player_id );
        $title  = $player
            ? sprintf( /* translators: %s = player name */ __( 'PDP file — %s', 'talenttrack' ), QueryHelpers::player_display_name( $player ) )
            : __( 'PDP file', 'talenttrack' );
        self::renderHeader( $title );

        $base_url = remove_query_arg( [ 'action', 'id', 'conv', 'player_id' ] );
        $print_url = add_query_arg(
            [ 'tt_pdp_print' => 1, 'file_id' => (int) $file->id ],
            \TT\Shared\Frontend\Components\RecordLink::dashboardUrl()
        );

        $convs   = ( new PdpConversationsRepository() )->listForFile( (int) $file->id );
        $verdict = ( new PdpVerdictsRepository() )->findForFile( (int) $file->id );
        $can_verdict = current_user_can( 'tt_edit_pdp_verdict' );

        echo '<div style="display:flex; gap:12px; margin-bottom:16px; flex-wrap:wrap;">';
        echo '<a class="tt-btn tt-btn-secondary" target="_blank" rel="noopener" href="' . esc_url( $print_url ) . '">'
            . esc_html__( 'Print', 'talenttrack' ) . '</a>';
        // #2043 — the "Record verdict" CTA moved out of the top action bar to
        // sit by the conversation list + cycle progress (rendered after the
        // conversations table below), where its "all conversations closed"
        // precondition is visible. It stays disabled, with the reason, until
        // every conversation is signed off.
        // #3300 — Archive, not "Permanently delete PDP".
        //
        // This page carried the only hard-delete entry point in the plugin
        // sitting on a live record: an irreversible action in the loudest
        // element on a screen whose actual primary action is "Record verdict",
        // shouting through hardcoded inline hex that overrode the danger
        // token. Every other entity archives first and only offers a
        // permanent delete from the recycle bin, once the record is already
        // out of the way.
        //
        // PDP now does the same. `DELETE /pdp-files/{id}` has been the
        // soft-archive since #1274 PR1, so the standard archive button drives
        // it with no new endpoint; the file then appears in the bin, where
        // restore and permanent-delete live behind `tt_manage_recycle_bin`.
        //
        // `pdp-archive-button.js` is already enqueued by this view and already
        // speaks `data-tt-pdp-archive` against `DELETE /pdp-files/{id}`; the
        // affordance simply had no button on this page. Reusing that contract
        // rather than the generic `data-tt-archive-rest-path` one keeps the
        // PDP-specific confirm and toast strings that ship with it.
        $is_archived = ! empty( $file->archived_at );
        if ( ! $is_archived && current_user_can( 'tt_edit_pdp' ) ) {
            echo '<button type="button" class="tt-btn tt-btn-secondary"'
                . ' data-tt-pdp-archive="' . (int) $file->id . '">'
                . esc_html__( 'Archive', 'talenttrack' ) . '</button>';
        }
        echo '</div>';

        // #0077 M9 — derived per-conversation status (no schema change
        // needed; the dates already carry the truth):
        //   coach_signoff_at  set → 'signed_off'
        //   conducted_at      set → 'held'
        //   otherwise              → 'scheduled'
        // Plus a "X / N done" indicator on the cycle-size row so the
        // operator sees progress at a glance instead of counting rows.
        $cycle_size = (int) ( $file->cycle_size ?? 0 );
        $signed_count = 0;
        foreach ( $convs as $c ) {
            if ( ! empty( $c->coach_signoff_at ) ) $signed_count++;
        }

        // #1686 — summary panel restyled to the 2026 chrome card.
        echo '<div class="tt-pop-summary">';
        echo '<p class="tt-pop-summary__row">';
        // v3.110.110 — converge onto LookupPill so the status uses the
        // same rounded-pill chrome as the list view + every other
        // status in the app (pilot ask: "the status pill does not have
        // rounded edges like all other pills").
        echo '<span class="tt-pop-summary__label">' . esc_html__( 'Status', 'talenttrack' ) . '</span> '
            . \TT\Infrastructure\Query\LookupPill::render( 'pdp_status', (string) $file->status, self::statusLabel( (string) $file->status ) );
        // #3299 — the inline help button is gone. The shell renders a global
        // help icon whose topic comes from `TT_DocsDrawer.view_to_topic`, and
        // `docs/pdp-cycle.md` declares `views: [pdp, my-pdp]` — so
        // `?tt_view=pdp` already resolves to `pdp-cycle` and the two icons
        // opened the same drawer on the same topic.
        //
        // #0077 F1's comment described a real bug (passing the ROUTE opened a
        // topic that did not exist and fell through to "Getting started"), but
        // what fixed it was adding `views:` to the doc's front matter, not
        // this button. Nothing regresses by removing it.
        echo '</p>';
        echo '<p class="tt-pop-summary__row"><span class="tt-pop-summary__label">' . esc_html__( 'Cycle size', 'talenttrack' ) . '</span> '
            . (int) $cycle_size;
        if ( $cycle_size > 0 ) {
            echo ' <span style="color:var(--tt-muted,#5b6e75); font-size:0.82rem;">'
                . sprintf(
                    /* translators: 1: signed-off count, 2: cycle size */
                    esc_html__( '(%1$d of %2$d signed off)', 'talenttrack' ),
                    (int) $signed_count,
                    (int) $cycle_size
                )
                . '</span>';
        }
        echo '</p>';
        if ( ! empty( $file->notes ) ) {
            echo '<p class="tt-pop-summary__row"><span class="tt-pop-summary__label">' . esc_html__( 'Notes', 'talenttrack' ) . '</span> '
                . esc_html( (string) $file->notes ) . '</p>';
        }
        echo '</div>';

        // Conversations list — single Status column (was three: scheduled,
        // conducted, signed_off) + parent/player ack pills so coaches see
        // who still needs to acknowledge.
        echo '<h2 style="font-size:16px; margin:18px 0 8px;">' . esc_html__( 'Conversations', 'talenttrack' ) . '</h2>';
        echo '<table class="tt-list-table-table">';
        echo '<thead><tr>';
        echo '<th>#</th>';
        echo '<th>' . esc_html__( 'Template', 'talenttrack' ) . '</th>';
        echo '<th>' . esc_html__( 'Scheduled', 'talenttrack' ) . '</th>';
        echo '<th>' . esc_html__( 'Status', 'talenttrack' ) . '</th>';
        // #1852 — visibility only (never a gate): has the player added a
        // self-reflection ahead of the talk?
        echo '<th>' . esc_html__( 'Self-review', 'talenttrack' ) . '</th>';
        echo '<th>' . esc_html__( 'Acks', 'talenttrack' ) . '</th>';
        echo '</tr></thead><tbody>';
        // v3.110.191 — pass today + full list into derivedConvStatus()
        // so it can compute 'to_be_planned' / 'future'.
        $today = gmdate( 'Y-m-d' );
        foreach ( $convs as $c ) {
            $url = add_query_arg( [ 'tt_view' => 'pdp', 'id' => (int) $file->id, 'conv' => (int) $c->id ], $base_url );
            $derived = self::derivedConvStatus( $c, $convs, $today );
            $badge_class_map = [
                'to_be_planned' => 'tt-status-to-be-planned',
                'scheduled'     => 'tt-status-scheduled',
                'held'          => 'tt-status-in-progress',
                'signed_off'    => 'tt-status-completed',
                'future'        => 'tt-status-future',
            ];
            $badge_class = $badge_class_map[ $derived ] ?? 'tt-status-future';
            $badge_label_map = [
                'to_be_planned' => __( 'To be planned', 'talenttrack' ),
                'scheduled'     => __( 'Scheduled', 'talenttrack' ),
                'held'          => __( 'Held', 'talenttrack' ),
                'signed_off'    => __( 'Signed off', 'talenttrack' ),
                /* translators: state label for a PDP meeting that's further out in the cycle and not yet actionable */
                'future'        => __( '—', 'talenttrack' ),
            ];
            $badge_label = $badge_label_map[ $derived ] ?? '—';
            // #1667 — whole-row click (pointer) via the shared
            // tt-table-tools handler; the Template cell keeps a real
            // <a> as the keyboard / assistive-tech path.
            // #3299 — `data-label` on every cell. `tt-list-table-table`'s
            // mobile reflow hides <thead> below 640px and substitutes
            // `td::before { content: attr(data-label) }` for the headers. With
            // none set, the rows became stacked cards of right-aligned values
            // against an empty pseudo-element: "1 / Begin seizoen / 2026-09-30
            // / GEPLAND / Nog niet" with nothing naming the columns. The
            // labels are the same translated strings the <th>s use.
            echo '<tr class="is-row-link" data-row-href="' . esc_url( $url ) . '">';
            echo '<td data-label="#">' . (int) $c->sequence . '</td>';
            // #3299 — `tt-record-link`, like every other record link in the
            // app. The bare anchor fell through to the active theme's link
            // styling — blue and underlined next to the status badges in the
            // same row. The class's rule resets colour and decoration with
            // `!important` precisely so a hostile theme cannot re-underline it
            // (#1792).
            echo '<td data-label="' . esc_attr__( 'Template', 'talenttrack' ) . '">'
                . '<a class="tt-record-link" href="' . esc_url( $url ) . '">'
                . esc_html( self::templateLabel( (string) $c->template_key ) ) . '</a></td>';
            echo '<td data-label="' . esc_attr__( 'Scheduled', 'talenttrack' ) . '">'
                . esc_html( self::shortDate( $c->scheduled_at ) ) . '</td>';
            echo '<td data-label="' . esc_attr__( 'Status', 'talenttrack' ) . '">'
                . '<span class="tt-status-badge ' . esc_attr( $badge_class ) . '">' . esc_html( $badge_label ) . '</span></td>';
            // #1852 — self-review visibility. Done once the player has a
            // reflection; "Not yet" while the talk is still upcoming; a
            // muted dash once the talk is conducted with none (no nagging
            // about a past talk). Never a gate on conducting / sign-off.
            $has_reflection = trim( wp_strip_all_tags( (string) ( $c->player_reflection ?? '' ) ) ) !== '';
            if ( $has_reflection ) {
                $sr_class = 'tt-pdp-selfreview--done';
                $sr_label = __( 'Done', 'talenttrack' );
            } elseif ( empty( $c->conducted_at ) ) {
                $sr_class = 'tt-pdp-selfreview--pending';
                $sr_label = __( 'Not yet', 'talenttrack' );
            } else {
                $sr_class = 'tt-pdp-selfreview--none';
                $sr_label = __( '—', 'talenttrack' );
            }
            echo '<td data-label="' . esc_attr__( 'Self-review', 'talenttrack' ) . '">'
                . '<span class="tt-pdp-selfreview ' . esc_attr( $sr_class ) . '">' . esc_html( $sr_label ) . '</span></td>';
            // v3.110.197 (#809) — the "awaiting" state used to render as
            // a middle-dot character (`·`) which the pilot read as a
            // period — the conversation didn't visibly say "not done
            // yet". Replace with the hourglass icon so the state reads
            // at a glance: role glyph + hourglass = pending, role glyph
            // + checkmark = signed.
            echo '<td data-label="' . esc_attr__( 'Acks', 'talenttrack' ) . '">';
            $parent_ok = ! empty( $c->parent_ack_at );
            $player_ok = ! empty( $c->player_ack_at );
            $hourglass = \TT\Shared\Icons\IconRenderer::render( 'hourglass', [
                'width'  => '12',
                'height' => '12',
                'class'  => 'tt-icon tt-pdp-ack-hourglass',
                'style'  => 'vertical-align:-2px; color:#c9962a;',
            ] );
            // #1365 — role glyphs + checkmark are inline SVGs now
            // (were person / football emoji + a check text glyph).
            $role_attrs = [ 'width' => 12, 'height' => 12, 'style' => 'vertical-align:-2px;margin-right:1px;' ];
            $check_ok   = \TT\Shared\Icons\IconRenderer::render( 'check', [
                'width'  => '12',
                'height' => '12',
                'style'  => 'vertical-align:-2px; color:#2c8a2c;',
            ] );
            echo '<span title="' . esc_attr( $parent_ok
                    ? __( 'Parent has acknowledged', 'talenttrack' )
                    : __( 'Parent acknowledgement pending', 'talenttrack' ) ) . '" style="margin-right:6px;">'
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — IconRenderer output is trusted SVG.
                . \TT\Shared\Icons\IconRenderer::render( 'profile', $role_attrs )
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — IconRenderer output is trusted SVG.
                . ( $parent_ok ? $check_ok : $hourglass )
                . '</span>';
            echo '<span title="' . esc_attr( $player_ok
                    ? __( 'Player has acknowledged', 'talenttrack' )
                    : __( 'Player acknowledgement pending', 'talenttrack' ) ) . '">'
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — IconRenderer output is trusted SVG.
                . \TT\Shared\Icons\IconRenderer::render( 'players', $role_attrs )
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — IconRenderer output is trusted SVG.
                . ( $player_ok ? $check_ok : $hourglass )
                . '</span>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';

        // #2043 — verdict CTA, by the conversation list + cycle progress. The
        // end-of-season verdict can only be recorded once every conversation
        // is signed off; until then it renders disabled with the reason
        // (X/N closed) so the operator sees why it isn't available.
        if ( $can_verdict && $verdict === null ) {
            $all_closed = ( $cycle_size > 0 && $signed_count >= $cycle_size );
            echo '<div class="tt-pdp-verdict-cta">';
            if ( $all_closed ) {
                $vurl = add_query_arg( [ 'tt_view' => 'pdp', 'id' => (int) $file->id, 'action' => 'verdict' ], $base_url );
                echo '<a class="tt-btn tt-btn-primary" href="' . esc_url( $vurl ) . '">'
                    . esc_html__( 'Record verdict', 'talenttrack' ) . '</a>';
            } else {
                echo '<button type="button" class="tt-btn tt-btn-primary" disabled aria-disabled="true">'
                    . esc_html( sprintf(
                        /* translators: 1: signed-off conversation count, 2: cycle size */
                        __( 'Record verdict (%1$d/%2$d conversations closed)', 'talenttrack' ),
                        (int) $signed_count,
                        (int) $cycle_size
                    ) ) . '</button>';
                echo '<p class="tt-pdp-verdict-cta__hint">'
                    . esc_html__( 'The verdict opens once every conversation in the cycle is signed off.', 'talenttrack' )
                    . '</p>';
            }
            echo '</div>';
        }

        // Goals linked to this player (current season)
        self::renderGoalsBlock( (int) $file->player_id );

        // Verdict block
        if ( $verdict !== null ) {
            echo '<h2 style="font-size:16px; margin:24px 0 8px;">' . esc_html__( 'End-of-season verdict', 'talenttrack' ) . '</h2>';
            echo '<div class="tt-card" style="background:#fff; border:1px solid #e5e7ea; border-radius:8px; padding:16px;">';
            echo '<p style="margin:0 0 6px;"><strong>' . esc_html__( 'Decision:', 'talenttrack' ) . '</strong> '
                . esc_html( self::decisionLabel( (string) $verdict->decision ) ) . '</p>';
            if ( ! empty( $verdict->summary ) ) {
                echo '<div style="margin:8px 0; padding:8px; background:#fafbfc; border-radius:4px;">'
                    . wp_kses_post( (string) $verdict->summary ) . '</div>';
            }
            if ( ! empty( $verdict->signed_off_at ) ) {
                echo '<p style="margin:0; color:#5b6e75;"><em>'
                    . esc_html( sprintf(
                        /* translators: %s = signed-off timestamp */
                        __( 'Signed off on %s', 'talenttrack' ),
                        (string) $verdict->signed_off_at
                    ) ) . '</em></p>';
            }
            echo '</div>';
        }
    }

    private static function renderConversationForm( object $file, int $conv_id, int $user_id, bool $is_admin ): void {
        $convs_repo = new PdpConversationsRepository();
        $conv = $convs_repo->find( $conv_id );
        if ( ! $conv || (int) $conv->pdp_file_id !== (int) $file->id ) {
            self::renderHeader( __( 'Conversation not found', 'talenttrack' ) );
            echo '<p class="tt-notice">' . esc_html__( 'That conversation no longer exists.', 'talenttrack' ) . '</p>';
            return;
        }

        $title = sprintf(
            /* translators: %1$d sequence, %2$s template */
            __( 'Conversation %1$d (%2$s)', 'talenttrack' ),
            (int) $conv->sequence,
            self::templateLabel( (string) $conv->template_key )
        );
        self::renderHeader( $title );

        $base_url = remove_query_arg( [ 'action', 'conv', 'player_id' ] );

        $is_signed = ! empty( $conv->coach_signoff_at );
        // v3.110.197 (#809) — any signature locks the form. Coach
        // signoff, parent ack, OR player ack each make the conversation
        // read-only end-to-end: form inputs disabled, Save hidden,
        // signoff checkbox hidden, top banner explaining why.
        $is_locked  = PdpConversationsRepository::isLocked( $conv );
        // #2041 — only the "active" conversation (earliest by sequence not
        // yet signed off) is fully editable. Later conversations are
        // content-locked: every field is read-only EXCEPT the planned date,
        // so the coach can schedule the cycle ahead without filling in a
        // talk out of order. A signature still trumps this with a full lock.
        $is_active      = ( (int) $conv->id === $convs_repo->activeConversationId( (int) $file->id ) );
        $content_locked = ! $is_locked && ! $is_active;
        // scheduled_at honours only the signature lock (date stays editable
        // on a content-locked conversation); every other field honours both.
        $date_attr    = $is_locked ? ' readonly disabled' : '';
        $content_attr = ( $is_locked || $content_locked ) ? ' readonly disabled' : '';
        $lock_attr    = $content_attr;
        $rest_path = 'pdp-conversations/' . (int) $conv->id;
        // v3.92.5 — after save / sign, land back on the parent PDP file
        // so the user sees their work in context. Public.js honours
        // `data-redirect-after-save-url` and waits for the success
        // toast before navigating.
        $back_to_file_url = add_query_arg( [ 'tt_view' => 'pdp', 'id' => (int) $file->id ], $base_url );

        // #0063 introduced the Conversation | Evidence tab strip, hand-rolled:
        // an inline style attribute on every button, a page-level style
        // block, and an inline script binding a global document listener —
        // all three forbidden by CLAUDE.md § 2, and a fourth tab strip
        // diverging from the shared one. #3303 replaces it with
        // `RecordSpine`, which is where
        // record-scoped tabs come from (§ 5c). These tabs stay within one
        // conversation, so they are content rather than navigation and do not
        // count against the two-affordance budget.
        //
        // The spine is passed no identity: the view's own header already
        // names the conversation, and a second name strip above it would say
        // the same thing twice. `tabs_always` keeps the strip under the
        // `classic` shell, where it is the only route to the Evidence pane.
        \TT\Shared\Frontend\Components\RecordSpine::render( [
            'tabs_always' => true,
            'tabs'        => [
                [
                    'label'  => __( 'Conversation', 'talenttrack' ),
                    'panel'  => 'tt-pdp-pane-conversation',
                    'active' => true,
                ],
                [
                    'label' => __( 'Evidence', 'talenttrack' ),
                    'panel' => 'tt-pdp-pane-evidence',
                ],
            ],
        ] );

        // Main form column (Conversation pane).
        echo '<div class="tt-pdp-conv-pane" id="tt-pdp-pane-conversation" role="tabpanel"'
            . ' aria-labelledby="' . esc_attr( \TT\Shared\Frontend\Components\RecordSpine::tabId( 'tt-pdp-pane-conversation' ) ) . '">';

        // v3.110.197 (#809) — locked banner. Names the signatory chain so
        // the coach knows why edits are blocked.
        if ( $is_locked ) {
            $lock_reasons = [];
            if ( ! empty( $conv->coach_signoff_at ) ) {
                $lock_reasons[] = sprintf(
                    /* translators: %s = sign-off timestamp */
                    __( 'coach signed off on %s', 'talenttrack' ),
                    esc_html( \TT\Shared\Dates\TTDate::dateTime( (string) $conv->coach_signoff_at ) )
                );
            }
            if ( ! empty( $conv->parent_ack_at ) ) {
                $lock_reasons[] = sprintf(
                    /* translators: %s = ack timestamp */
                    __( 'parent acknowledged on %s', 'talenttrack' ),
                    esc_html( (string) $conv->parent_ack_at )
                );
            }
            if ( ! empty( $conv->player_ack_at ) ) {
                $lock_reasons[] = sprintf(
                    /* translators: %s = ack timestamp */
                    __( 'player acknowledged on %s', 'talenttrack' ),
                    esc_html( (string) $conv->player_ack_at )
                );
            }
            echo '<div class="tt-notice tt-pdp-conv-locked" role="status">';
            echo '<strong>' . esc_html__( 'Read only.', 'talenttrack' ) . '</strong> ';
            echo esc_html__( 'This conversation is locked because it carries a signature — ', 'talenttrack' );
            // Reasons array contains escaped HTML (timestamps); join with comma.
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo implode( ', ', $lock_reasons );
            echo '. ' . esc_html__( 'Open a new conversation if a follow-up edit is needed.', 'talenttrack' );
            echo '</div>';
        }
        // #2041 — content-locked (a later conversation in the cycle): only
        // the planned date is editable until the active conversation ahead
        // of it is signed off. Distinct from the signature lock above.
        if ( $content_locked ) {
            echo '<div class="tt-notice tt-pdp-conv-future" role="status">';
            echo '<strong>' . esc_html__( 'Planned date only.', 'talenttrack' ) . '</strong> ';
            echo esc_html__( 'Until the previous conversation is signed off, only the planned date can be changed here.', 'talenttrack' );
            echo '</div>';
        }
        ?>
        <?php
        // #3008 (epic #2881) — the conversation composes itself as the
        // coach writes, up to the point a signature locks it.
        //
        // The signature lock is the boundary, not the content lock. A
        // content-locked conversation (a later talk in the cycle) still has
        // one editable field, the planned date, and its other inputs are
        // rendered `disabled` — so they are absent from the serialisation
        // and the autosave carries only the date, which the endpoint
        // accepts. A signature-locked conversation has nothing writable at
        // all, so it gets no save state and no autosave.
        $autosaves = ! $is_locked;
        if ( $autosaves ) {
            \TT\Shared\Frontend\Components\FormAutosave::enqueue();
        }
        ?>
        <form class="<?php echo $autosaves ? 'tt-autosave-form' : 'tt-ajax-form'; ?>"
              <?php if ( $autosaves ) {
                  echo \TT\Shared\Frontend\Components\FormAutosave::formAttrs( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — the component escapes each attribute
                      $rest_path,
                      'PATCH',
                      'pdp-conversation:' . $rest_path
                  );
              } else {
                  printf(
                      'data-rest-path="%s" data-rest-method="PATCH" data-redirect-after-save-url="%s"',
                      esc_attr( $rest_path ),
                      esc_attr( $back_to_file_url )
                  );
              } ?>>
            <div class="tt-field">
                <label class="tt-field-label" for="tt-conv-scheduled"><?php esc_html_e( 'Scheduled at', 'talenttrack' ); ?></label>
                <input type="datetime-local" id="tt-conv-scheduled" name="scheduled_at" class="tt-input"
                    value="<?php echo esc_attr( self::toDatetimeLocal( $conv->scheduled_at ) ); ?>"<?php echo $date_attr; ?> />
            </div>
            <div class="tt-field">
                <label class="tt-field-label" for="tt-conv-conducted"><?php esc_html_e( 'Conducted at', 'talenttrack' ); ?></label>
                <input type="datetime-local" id="tt-conv-conducted" name="conducted_at" class="tt-input"
                    value="<?php echo esc_attr( self::toDatetimeLocal( $conv->conducted_at ) ); ?>"<?php echo $lock_attr; ?> />
                <small class="tt-help-text"><?php esc_html_e( 'Fill in once the conversation has happened.', 'talenttrack' ); ?></small>
            </div>
            <div class="tt-field">
                <label class="tt-field-label" for="tt-conv-agenda"><?php esc_html_e( 'Agenda (pre-meeting)', 'talenttrack' ); ?></label>
                <textarea id="tt-conv-agenda" name="agenda" class="tt-input" rows="3"<?php echo $lock_attr; ?>><?php echo esc_textarea( (string) ( $conv->agenda ?? '' ) ); ?></textarea>
            </div>
            <div class="tt-field">
                <label class="tt-field-label" for="tt-conv-notes"><?php esc_html_e( 'Notes (post-meeting)', 'talenttrack' ); ?></label>
                <textarea id="tt-conv-notes" name="notes" class="tt-input" rows="5"<?php echo $lock_attr; ?>><?php echo esc_textarea( (string) ( $conv->notes ?? '' ) ); ?></textarea>
            </div>
            <div class="tt-field">
                <label class="tt-field-label" for="tt-conv-actions"><?php esc_html_e( 'Agreed actions', 'talenttrack' ); ?></label>
                <textarea id="tt-conv-actions" name="agreed_actions" class="tt-input" rows="3"<?php echo $lock_attr; ?>><?php echo esc_textarea( (string) ( $conv->agreed_actions ?? '' ) ); ?></textarea>
            </div>

            <?php
            // #1853 — link the goals discussed in this talk (the "combine").
            // Tick the player's active goals covered here; saved to
            // tt_goal_links so My PDP can show them in context.
            $player_goals    = ( new \TT\Infrastructure\Goals\GoalsRepository() )->topActiveForPlayer( (int) $file->player_id, 20 );
            $linked_goal_ids = ( new \TT\Modules\Pdp\Repositories\GoalLinksRepository() )->goalsForConversation( (int) $conv->id );
            if ( ! empty( $player_goals ) ) :
                ?>
                <div class="tt-field tt-pdp-goal-links">
                    <span class="tt-field-label"><?php esc_html_e( 'Goals discussed in this talk', 'talenttrack' ); ?></span>
                    <input type="hidden" name="linked_goal_ids_present" value="1" />
                    <ul class="tt-pdp-goal-links__list">
                        <?php foreach ( $player_goals as $g ) :
                            $gid = (int) ( $g->id ?? 0 );
                            if ( $gid <= 0 ) continue;
                            $checked = in_array( $gid, $linked_goal_ids, true ) ? ' checked' : '';
                            ?>
                            <li>
                                <label class="tt-checkbox">
                                    <input type="checkbox" name="linked_goal_ids[]" value="<?php echo (int) $gid; ?>"<?php echo $checked . $lock_attr; ?> />
                                    <span><?php echo esc_html( (string) ( $g->title ?? '' ) ); ?></span>
                                </label>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ( ! empty( $conv->player_reflection ) ) : ?>
                <div class="tt-pdp-conv-reflection">
                    <p class="tt-pdp-conv-reflection__title"><?php esc_html_e( 'Player self-reflection', 'talenttrack' ); ?></p>
                    <div><?php echo wp_kses_post( (string) $conv->player_reflection ); ?></div>
                </div>
            <?php endif; ?>

            <?php if ( $is_signed ) : ?>
                <p class="tt-pdp-signed-off"><strong><?php esc_html_e( 'Signed off', 'talenttrack' ); ?></strong> — <?php echo esc_html( \TT\Shared\Dates\TTDate::dateTime( (string) $conv->coach_signoff_at ) ); ?></p>
            <?php endif; ?>

            <?php if ( $autosaves ) : ?>
                <div class="tt-form-actions">
                    <?php \TT\Shared\Frontend\Components\SaveState::render(); ?>
                </div>
            <?php endif; ?>
            <div class="tt-form-msg"></div>
        </form>
        <?php
        // #3008 — sign-off leaves the conversation form and becomes its own
        // confirmed action.
        //
        // It was a checkbox inside the form. On a form the coach had to
        // press Save on, that was fine: ticking it and pressing Save is two
        // deliberate acts. On an autosaving form it becomes one accidental
        // one — the tick debounces, the conversation locks end-to-end, and
        // the only way back is to open a new conversation. A control that
        // makes a player's record permanently read-only must not fire
        // because 600 milliseconds elapsed.
        //
        // So it is a button, outside the autosaving form, behind the
        // confirm `public.js` already honours, landing back on the PDP file
        // the way Save used to.
        if ( ! $is_signed && ! $is_locked && ! $content_locked ) :
            ?>
            <form class="tt-ajax-form tt-pdp-signoff"
                  data-rest-path="<?php echo esc_attr( $rest_path ); ?>"
                  data-rest-method="PATCH"
                  data-confirm-msg="<?php esc_attr_e( 'Sign off this conversation? It becomes read-only for everyone — open a new conversation if a follow-up edit is needed.', 'talenttrack' ); ?>"
                  data-redirect-after-save-url="<?php echo esc_attr( $back_to_file_url ); ?>">
                <input type="hidden" name="coach_signoff_at" value="<?php echo esc_attr( current_time( 'mysql', true ) ); ?>" />
                <button type="submit" class="tt-btn tt-btn-primary"><?php esc_html_e( 'Sign off', 'talenttrack' ); ?></button>
                <p class="tt-help-text"><?php esc_html_e( 'Everything above is already saved. Signing off closes the conversation for editing.', 'talenttrack' ); ?></p>
                <div class="tt-form-msg"></div>
            </form>
            <?php
        endif;
        // #2042 — proxy acknowledgement. A coach who ran the talk in person
        // can record the player's / parent's acknowledgement on their behalf,
        // each behind a confirm dialog. Plain proxy: it writes the same
        // *_ack_at column as a self-ack (and trips the end-to-end lock, by
        // design). Offered once the coach has signed off — the same point at
        // which the player / parent would normally get their own Acknowledge
        // button — and only for a coach who can edit the file.
        $can_proxy_ack = $is_admin || current_user_can( 'tt_edit_pdp' );
        if ( $is_signed && $can_proxy_ack ) {
            $now_utc = current_time( 'mysql', true );
            echo '<div class="tt-pdp-proxy-ack">';
            echo '<h3 class="tt-pdp-proxy-ack__title">' . esc_html__( 'Acknowledgements', 'talenttrack' ) . '</h3>';

            if ( ! empty( $conv->player_ack_at ) ) {
                echo '<p class="tt-pdp-proxy-ack__done">' . esc_html( sprintf(
                    /* translators: %s = acknowledgement timestamp */
                    __( 'Player acknowledged on %s', 'talenttrack' ),
                    \TT\Shared\Dates\TTDate::dateTime( (string) $conv->player_ack_at )
                ) ) . '</p>';
            } else {
                self::renderProxyAckForm( $rest_path, 'player_ack_at', $now_utc,
                    __( 'Record player acknowledgement', 'talenttrack' ),
                    __( 'Confirm only if the player acknowledged this conversation with you in person. This records it on their behalf.', 'talenttrack' )
                );
            }

            if ( ! empty( $conv->parent_ack_at ) ) {
                echo '<p class="tt-pdp-proxy-ack__done">' . esc_html( sprintf(
                    /* translators: %s = acknowledgement timestamp */
                    __( 'Parent acknowledged on %s', 'talenttrack' ),
                    \TT\Shared\Dates\TTDate::dateTime( (string) $conv->parent_ack_at )
                ) ) . '</p>';
            } else {
                self::renderProxyAckForm( $rest_path, 'parent_ack_at', $now_utc,
                    __( 'Record parent acknowledgement', 'talenttrack' ),
                    __( 'Confirm only if the parent acknowledged this conversation with you in person. This records it on their behalf.', 'talenttrack' )
                );
            }
            echo '</div>';
        }
        echo '</div>'; // /conversation pane

        // Evidence pane — the shared panel over the one packet (#3303).
        // Hidden by default; the tab strip above reveals it.
        echo '<div class="tt-pdp-conv-pane" id="tt-pdp-pane-evidence" role="tabpanel"'
            . ' aria-labelledby="' . esc_attr( \TT\Shared\Frontend\Components\RecordSpine::tabId( 'tt-pdp-pane-evidence' ) ) . '" hidden>';
        \TT\Shared\Frontend\Components\EvidencePanel::enqueue();
        \TT\Shared\Frontend\Components\EvidencePanel::render(
            \TT\Modules\Pdp\EvidencePacket::forConversation( $conv_id )
        );
        echo '</div>';
    }

    /**
     * #2042 — one proxy-acknowledgement mini-form. PATCHes the conversation
     * with the given ack column set to now, behind a JS confirm dialog
     * (`data-confirm-msg`), then reloads so the recorded state shows. Kept
     * separate from the main conversation form so it stays available once a
     * signature has locked that form.
     */
    private static function renderProxyAckForm( string $rest_path, string $field, string $now_utc, string $label, string $confirm ): void {
        ?>
        <form class="tt-ajax-form tt-pdp-proxy-ack__form" data-rest-path="<?php echo esc_attr( $rest_path ); ?>" data-rest-method="PATCH" data-redirect-after-save="reload" data-confirm-msg="<?php echo esc_attr( $confirm ); ?>">
            <input type="hidden" name="<?php echo esc_attr( $field ); ?>" value="<?php echo esc_attr( $now_utc ); ?>" />
            <button type="submit" class="tt-btn tt-btn-secondary"><?php echo esc_html( $label ); ?></button>
            <div class="tt-form-msg"></div>
        </form>
        <?php
    }

    private static function renderVerdictForm( object $file, int $user_id, bool $is_admin ): void {
        if ( ! current_user_can( 'tt_edit_pdp_verdict' ) ) {
            self::renderHeader( __( 'Verdict', 'talenttrack' ) );
            echo '<p class="tt-notice">' . esc_html__( 'Only head of academy or head coach roles can record a verdict.', 'talenttrack' ) . '</p>';
            return;
        }
        $existing = ( new PdpVerdictsRepository() )->findForFile( (int) $file->id );
        $title    = $existing ? __( 'Edit end-of-season verdict', 'talenttrack' ) : __( 'Record end-of-season verdict', 'talenttrack' );
        self::renderHeader( $title );

        $base_url  = remove_query_arg( [ 'action', 'conv', 'player_id' ] );
        $rest_path = 'pdp-files/' . (int) $file->id . '/verdict';
        $decisions = [
            PdpVerdictDecision::PROMOTE  => __( 'Promote to next age group', 'talenttrack' ),
            PdpVerdictDecision::RETAIN   => __( 'Retain in current group', 'talenttrack' ),
            PdpVerdictDecision::RELEASE  => __( 'Release from academy', 'talenttrack' ),
            PdpVerdictDecision::TRANSFER => __( 'Transfer to another team / club', 'talenttrack' ),
        ];
        ?>
        <form class="tt-ajax-form" data-rest-path="<?php echo esc_attr( $rest_path ); ?>" data-rest-method="PUT">
            <div class="tt-field">
                <label class="tt-field-label tt-field-required" for="tt-verdict-decision"><?php esc_html_e( 'Decision', 'talenttrack' ); ?></label>
                <select id="tt-verdict-decision" name="decision" class="tt-input" required>
                    <option value=""><?php esc_html_e( '— Select decision —', 'talenttrack' ); ?></option>
                    <?php foreach ( $decisions as $key => $label ) : ?>
                        <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $existing ? (string) $existing->decision : '', $key ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="tt-field">
                <label class="tt-field-label" for="tt-verdict-summary"><?php esc_html_e( 'Summary / rationale', 'talenttrack' ); ?></label>
                <textarea id="tt-verdict-summary" name="summary" class="tt-input" rows="6"><?php echo esc_textarea( (string) ( $existing->summary ?? '' ) ); ?></textarea>
            </div>
            <div class="tt-field">
                <label class="tt-checkbox">
                    <input type="checkbox" name="signed_off" value="1" <?php checked( $existing && ! empty( $existing->signed_off_at ) ); ?> />
                    <?php esc_html_e( 'Sign off this verdict now', 'talenttrack' ); ?>
                </label>
            </div>
            <div class="tt-form-actions" style="margin-top:16px;">
                <button type="submit" class="tt-btn tt-btn-primary"><?php esc_html_e( 'Save verdict', 'talenttrack' ); ?></button>
            </div>
            <div class="tt-form-msg"></div>
        </form>
        <?php
    }

    /**
     * Linked goals block on the PDP detail. Shows each goal with the
     * polymorphic links it carries (principles / football actions /
     * positions / values), plus the conversation footprint — the most
     * recent agreed-action note from any conversation that mentions
     * the goal title (case-insensitive substring match) anchors the
     * "what was said about it last" question without requiring the
     * coach to explicitly tag conversations to goals.
     *
     * Filter UI: a "Show" dropdown (active / all / completed) on top.
     * State is in the URL (?goals=active|all|completed) so a coach can
     * deep-link to a "completed goals" view from a parent message.
     */
    private static function renderGoalsBlock( int $player_id ): void {
        global $wpdb; $p = $wpdb->prefix;

        $filter = isset( $_GET['goals'] ) ? sanitize_key( (string) $_GET['goals'] ) : 'active';
        if ( ! in_array( $filter, [ 'active', 'all', 'completed' ], true ) ) $filter = 'active';

        $where_status = match ( $filter ) {
            'completed' => "AND g.status = 'completed'",
            'all'       => '',
            default     => "AND g.status NOT IN ('completed','archived')",
        };

        $goals = $wpdb->get_results( $wpdb->prepare(
            "SELECT g.id, g.title, g.description, g.status, g.priority, g.due_date, g.created_at, g.progress_pct
               FROM {$p}tt_goals g
              WHERE g.player_id = %d AND g.archived_at IS NULL
                {$where_status}
              ORDER BY g.due_date ASC, g.created_at DESC",
            $player_id
        ) );

        echo '<h2 style="font-size:16px; margin:24px 0 8px;">' . esc_html__( 'Linked goals', 'talenttrack' ) . '</h2>';

        // Filter dropdown — submit on change to keep the URL the source
        // of truth. Mobile-friendly: native select.
        $base = remove_query_arg( [ 'goals' ] );
        echo '<form method="get" action="' . esc_url( $base ) . '" style="margin-bottom:8px;">';
        // Preserve current view + id query args.
        foreach ( [ 'tt_view', 'id' ] as $k ) {
            if ( isset( $_GET[ $k ] ) ) {
                printf( '<input type="hidden" name="%s" value="%s" />', esc_attr( $k ), esc_attr( (string) $_GET[ $k ] ) );
            }
        }
        echo '<label style="font-size:12px; color:#5b6e75;">' . esc_html__( 'Show:', 'talenttrack' ) . ' ';
        echo '<select name="goals" class="tt-input" onchange="this.form.submit();" style="display:inline-block; width:auto;">';
        printf( '<option value="active" %s>%s</option>',     selected( $filter, 'active', false ),    esc_html__( 'Active', 'talenttrack' ) );
        printf( '<option value="completed" %s>%s</option>',  selected( $filter, 'completed', false ), esc_html__( 'Completed', 'talenttrack' ) );
        printf( '<option value="all" %s>%s</option>',        selected( $filter, 'all', false ),       esc_html__( 'All', 'talenttrack' ) );
        echo '</select></label>';
        echo '</form>';

        if ( empty( $goals ) ) {
            echo '<p><em>' . esc_html__( 'No goals match this filter.', 'talenttrack' ) . '</em></p>';
            return;
        }

        // Pre-fetch conversation snippets per file so we look up once,
        // not per-goal. Most-recent-first, capped at 20 conversations.
        $convs = $wpdb->get_results( $wpdb->prepare(
            "SELECT c.notes, c.agreed_actions, COALESCE(c.conducted_at, c.scheduled_at) AS when_at
               FROM {$p}tt_pdp_conversations c
               JOIN {$p}tt_pdp_files f ON f.id = c.pdp_file_id
              WHERE f.player_id = %d
                AND ( c.notes IS NOT NULL OR c.agreed_actions IS NOT NULL )
              ORDER BY when_at DESC LIMIT 20",
            $player_id
        ) );

        // Goal links — fetch all in one query keyed by goal_id.
        $goal_ids = array_map( static fn( $g ) => (int) $g->id, $goals );
        $links_by_goal = self::loadGoalLinks( $goal_ids );

        // #1686 — learning-goal cards in the 2026 chrome look: a
        // status-derived left accent (done / doing / planned), a number
        // (or ✓) badge, the goal_status chip, link chips, and the
        // "last mentioned" conversation snippet. Number badges count
        // up over the listed goals so the column reads as an ordered
        // development arc, mirroring the mockup.
        // #1754 — per-goal conversation message counts (one query) for the
        // collapsed-card badge, plus the viewer id for the inline threads.
        $goal_ids    = array_map( static fn( $g ) => (int) $g->id, $goals );
        $conv_counts = [];
        if ( $goal_ids ) {
            $cph  = implode( ',', array_fill( 0, count( $goal_ids ), '%d' ) );
            $crows = $wpdb->get_results( $wpdb->prepare(
                "SELECT thread_id, COUNT(*) AS n FROM {$p}tt_thread_messages
                  WHERE thread_type = 'goal' AND deleted_at IS NULL AND thread_id IN ({$cph})
                  GROUP BY thread_id",
                ...$goal_ids
            ) );
            foreach ( (array) $crows as $cr ) { $conv_counts[ (int) $cr->thread_id ] = (int) $cr->n; }
        }
        $viewer_id = get_current_user_id();

        // #1717 — scored evidence (linked evaluations) per goal, one query.
        $evidence_by_goal = ( new \TT\Modules\Pdp\Repositories\GoalEvidenceRepository() )->listForGoals( $goal_ids );

        echo '<div class="tt-pop-goals">';
        $goal_index = 0;
        foreach ( $goals as $g ) {
            $goal_index++;
            $title    = (string) $g->title;
            $status   = (string) $g->status;
            $priority = (string) ( $g->priority ?? '' );
            $due      = self::shortDate( $g->due_date );
            $links    = $links_by_goal[ (int) $g->id ] ?? [];

            $latest_mention = self::findLatestMention( (string) $title, $convs );

            // Map the goal status onto the chrome accent + badge variant.
            $is_done = ( $status === 'completed' );
            if ( $is_done ) {
                $card_mod  = 'tt-pop-goal--done';
                $num_glyph = '✓';
            } elseif ( in_array( $status, [ 'planned', 'not_started' ], true ) ) {
                $card_mod  = 'tt-pop-goal--planned';
                $num_glyph = (string) $goal_index;
            } else {
                $card_mod  = 'tt-pop-goal--doing';
                $num_glyph = (string) $goal_index;
            }

            // #1754 — collapsible goal card with a conversation per goal.
            // Native <details> (no JS, keyboard-accessible). In-progress
            // goals open by default so their active conversations show.
            $open_attr = ( ! $is_done && ! in_array( $status, [ 'planned', 'not_started' ], true ) ) ? ' open' : '';
            $msg_n     = $conv_counts[ (int) $g->id ] ?? 0;

            echo '<details class="tt-pop-goal ' . esc_attr( $card_mod ) . '"' . $open_attr . '>';
            echo '<summary class="tt-pop-goal__head">';
            echo '<span class="tt-pop-goal__num" aria-hidden="true">' . esc_html( $num_glyph ) . '</span>';
            echo '<span class="tt-pop-goal__title">' . esc_html( $title ) . '</span>';
            // #0063 — same goal_status pill as everywhere else.
            echo \TT\Infrastructure\Query\LookupPill::render( 'goal_status', $status );
            echo '<span class="tt-pop-goal__convn" title="' . esc_attr__( 'Messages in this goal’s conversation', 'talenttrack' ) . '">'
                . '<span aria-hidden="true">&#128172;</span> ' . (int) $msg_n . '</span>';
            if ( ! empty( $g->due_date ) ) {
                echo '<span class="tt-pop-goal__when">' . esc_html( $due ) . '</span>';
            }
            echo '<svg class="tt-pop-goal__chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>';
            echo '</summary>';

            echo '<div class="tt-pop-goal__body">';

            // Left column — detail: description, methodology links, mention.
            echo '<div class="tt-pop-goal__detail">';
            if ( ! empty( $g->description ) ) {
                echo '<p class="tt-pop-goal__desc">' . esc_html( (string) $g->description ) . '</p>';
            }
            if ( $priority !== '' ) {
                echo '<div class="tt-pop-goal__chips"><span class="tt-status-badge tt-status-future">' . esc_html( $priority ) . '</span></div>';
            }
            // #1717 — per-goal progress bar (renders only when set).
            if ( ( $g->progress_pct ?? null ) !== null ) {
                $pct = max( 0, min( 100, (int) $g->progress_pct ) );
                echo '<p class="tt-pop-lbl">' . esc_html__( 'Progress', 'talenttrack' ) . '</p>';
                echo '<div class="tt-pop-barwrap">';
                echo '<div class="tt-pop-bar"><i style="width:' . $pct . '%"></i></div>'; /* tt-inline-ok — computed progress width */
                echo '<span class="tt-pop-pct">' . $pct . '%</span>';
                echo '</div>';
            }
            // #1717 — Bewijslast: linked evaluations with their score + date.
            $evidence = $evidence_by_goal[ (int) $g->id ] ?? [];
            if ( $evidence ) {
                echo '<p class="tt-pop-lbl">' . esc_html__( 'Evidence', 'talenttrack' ) . '</p>';
                echo '<div class="tt-pop-evid">';
                foreach ( $evidence as $ev ) {
                    $score = ( $ev->avg_rating !== null ) ? number_format_i18n( (float) $ev->avg_rating, 1 ) : '—';
                    echo '<span class="tt-pop-ev">'
                        . esc_html( sprintf(
                            /* translators: 1: date, 2: score */
                            __( 'Assessment %1$s · %2$s', 'talenttrack' ),
                            date_i18n( 'j M', (int) strtotime( (string) $ev->eval_date ) ),
                            $score
                        ) )
                        . '</span>';
                }
                echo '</div>';
            }
            if ( ! empty( $links ) ) {
                echo '<p class="tt-pop-lbl">' . esc_html__( 'Linked to', 'talenttrack' ) . '</p>';
                echo '<div class="tt-pop-evid">';
                foreach ( $links as $l ) {
                    echo '<span class="tt-pop-ev">'
                        . esc_html( self::linkLabel( (string) $l['type'] ) ) . ': ' . esc_html( (string) $l['name'] )
                        . '</span>';
                }
                echo '</div>';
            }
            if ( $latest_mention !== null ) {
                echo '<div class="tt-pop-mention">';
                echo '<span class="tt-pop-mention__when">' . esc_html( sprintf(
                    /* translators: %s = date */
                    __( 'Last mentioned in conversation on %s', 'talenttrack' ),
                    self::shortDate( $latest_mention['when_at'] )
                ) ) . '</span>';
                echo esc_html( $latest_mention['snippet'] );
                echo '</div>';
            }
            echo '<p class="tt-pop-goal__open"><a href="'
                . esc_url( \TT\Shared\Frontend\Components\RecordLink::detailUrlForWithBack( 'goals', (int) $g->id ) )
                . '">' . esc_html__( 'Open goal', 'talenttrack' ) . ' &rsaquo;</a></p>';
            echo '</div>';

            // Right column — this goal's own conversation thread (#1754).
            echo '<div class="tt-pop-goal__convo">';
            echo '<div class="tt-pop-goal__convo-h"><b>' . esc_html__( 'Conversation', 'talenttrack' )
                . '</b><span>' . esc_html__( 'player & coach', 'talenttrack' ) . '</span></div>';
            \TT\Shared\Frontend\Components\FrontendThreadView::render( 'goal', (int) $g->id, $viewer_id );
            echo '</div>';

            echo '</div>'; // .tt-pop-goal__body
            echo '</details>';
        }
        echo '</div>';
    }

    /**
     * @param list<int> $goal_ids
     * @return array<int, list<array{type:string, name:string}>>
     */
    private static function loadGoalLinks( array $goal_ids ): array {
        if ( empty( $goal_ids ) ) return [];
        global $wpdb; $p = $wpdb->prefix;
        $placeholders = implode( ',', array_fill( 0, count( $goal_ids ), '%d' ) );
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT goal_id, link_type, link_id FROM {$p}tt_goal_links
              WHERE goal_id IN ($placeholders)",
            ...$goal_ids
        ) );
        // Resolve a name per (link_type, link_id). Cheap loop — typical
        // PDP file has <20 links across all goals.
        $names = [];
        foreach ( (array) $rows as $r ) {
            $key = (string) $r->link_type . ':' . (int) $r->link_id;
            if ( ! isset( $names[ $key ] ) ) {
                $names[ $key ] = self::resolveLinkName( (string) $r->link_type, (int) $r->link_id );
            }
        }
        $out = [];
        foreach ( (array) $rows as $r ) {
            $out[ (int) $r->goal_id ][] = [
                'type' => (string) $r->link_type,
                'name' => $names[ (string) $r->link_type . ':' . (int) $r->link_id ] ?? '',
            ];
        }
        return $out;
    }

    private static function resolveLinkName( string $type, int $id ): string {
        global $wpdb; $p = $wpdb->prefix;
        switch ( $type ) {
            case 'principle':
                return (string) $wpdb->get_var( $wpdb->prepare(
                    "SELECT name FROM {$p}tt_principles WHERE id = %d", $id
                ) );
            case 'football_action':
                return (string) $wpdb->get_var( $wpdb->prepare(
                    "SELECT name FROM {$p}tt_football_actions WHERE id = %d", $id
                ) );
            case 'position':
                return (string) $wpdb->get_var( $wpdb->prepare(
                    "SELECT name FROM {$p}tt_lookups WHERE id = %d", $id
                ) );
            case 'value':
                return (string) $wpdb->get_var( $wpdb->prepare(
                    "SELECT name FROM {$p}tt_lookups WHERE id = %d", $id
                ) );
        }
        return '';
    }

    private static function linkLabel( string $type ): string {
        switch ( $type ) {
            case 'principle':       return __( 'Principle', 'talenttrack' );
            case 'football_action': return __( 'Football action', 'talenttrack' );
            case 'position':        return __( 'Position', 'talenttrack' );
            case 'value':           return __( 'Value', 'talenttrack' );
        }
        return $type;
    }

    /**
     * Walk the player's conversation history (most recent first) and
     * return a snippet of the first conversation where the goal title
     * appears in either the notes or agreed_actions field. Returns
     * null when nothing matches.
     *
     * @param array<int, object> $convs
     * @return array{when_at:string, snippet:string}|null
     */
    private static function findLatestMention( string $title, array $convs ): ?array {
        $title = trim( $title );
        if ( $title === '' || empty( $convs ) ) return null;
        $needle = mb_strtolower( $title );
        foreach ( $convs as $c ) {
            $haystack = mb_strtolower( (string) ( $c->notes ?? '' ) . ' ' . (string) ( $c->agreed_actions ?? '' ) );
            $pos = mb_strpos( $haystack, $needle );
            if ( $pos === false ) continue;
            $source = (string) ( $c->agreed_actions ?? $c->notes ?? '' );
            $start = max( 0, $pos - 40 );
            $snippet = mb_substr( $source, $start, 160 );
            if ( $start > 0 ) $snippet = '…' . $snippet;
            if ( mb_strlen( $source ) > $start + 160 ) $snippet .= '…';
            return [
                'when_at' => (string) $c->when_at,
                'snippet' => $snippet,
            ];
        }
        return null;
    }

    /**
     * Players the user is allowed to open files for. Admin → all active.
     * Coach → players on their teams.
     *
     * @return array<int, string> player_id => display name
     */
    private static function eligiblePlayers( int $user_id, bool $is_admin ): array {
        $out = [];
        if ( $is_admin ) {
            foreach ( QueryHelpers::get_players() as $pl ) {
                $out[ (int) $pl->id ] = QueryHelpers::player_display_name( $pl );
            }
        } else {
            foreach ( QueryHelpers::get_teams_for_coach( $user_id ) as $t ) {
                foreach ( QueryHelpers::get_players( (int) $t->id ) as $pl ) {
                    $out[ (int) $pl->id ] = QueryHelpers::player_display_name( $pl );
                }
            }
        }
        asort( $out );
        return $out;
    }

    private static function canSeeFile( object $file, int $user_id, bool $is_admin ): bool {
        // #1923 / #1758 - one shared decision for frontend + REST. The
        // previous is_admin || coach_owns_player gate skipped the
        // global-reader branch, so a Head of Development who does not
        // personally coach the player was denied the files tab while
        // every REST surface let them through. PdpAccess::canSeeFile() is
        // the matrix-aware ladder both sides now share; $is_admin
        // (tt_edit_settings) is already covered by its
        // hasGlobalPdpAccess() branch.
        unset( $is_admin );
        return \TT\Modules\Pdp\PdpAccess::canSeeFile( $user_id, (int) $file->player_id );
    }

    private static function statusLabel( string $status ): string {
        // _x() — the noun "Open" (status), distinct from the verb "Open"
        // (action) that already lives in the .po as "Openen".
        switch ( $status ) {
            case PdpStatus::OPEN:      return _x( 'Open', 'PDP file status', 'talenttrack' );
            case PdpStatus::COMPLETED: return __( 'Completed', 'talenttrack' );
            case PdpStatus::ARCHIVED:  return __( 'Archived', 'talenttrack' );
        }
        return $status;
    }

    private static function templateLabel( string $key ): string {
        switch ( $key ) {
            case 'start': return __( 'Start of season', 'talenttrack' );
            case 'mid':   return __( 'Mid season', 'talenttrack' );
            case 'mid_a': return __( 'Mid-season A', 'talenttrack' );
            case 'mid_b': return __( 'Mid-season B', 'talenttrack' );
            case 'end':   return __( 'End of season', 'talenttrack' );
        }
        return $key;
    }

    private static function decisionLabel( string $decision ): string {
        // v3.110.208 (#843) — delegate to PdpVerdictsRepository::label()
        // which routes through LookupTranslator for the per-locale
        // override stored in tt_translations.
        return \TT\Modules\Pdp\Repositories\PdpVerdictsRepository::label( $decision );
    }

    private static function shortDate( $value ): string {
        if ( empty( $value ) ) return '—';
        return substr( (string) $value, 0, 10 );
    }

    /**
     * v3.110.191 — derive a per-conversation status WITH CONTEXT (the
     * file's full conversation list + today's date). Previously
     * (#0077 M9) status was a row-local function returning 'scheduled'
     * for every un-conducted row — flat, no signal about which meeting
     * the coach should be acting on now.
     *
     * New states (in evaluation order):
     *   - 'signed_off'    — coach_signoff_at set
     *   - 'held'          — conducted_at set
     *   - 'scheduled'     — scheduled_at set (on the calendar)
     *   - 'to_be_planned' — exactly one row at a time: the next
     *                       un-actioned conversation (lowest sequence
     *                       among rows missing scheduled_at) AND
     *                       either the previous row is conducted OR
     *                       this row's planning_window_start has
     *                       passed (today >= window start). Block 1
     *                       is always open (no previous row).
     *   - 'future'        — any other un-planned conversation further
     *                       out in the cycle. Rendered muted so the
     *                       coach focuses on the actionable row.
     *
     * @param list<object> $all_convs file's conversations, ordered by
     *                                sequence ASC (the SQL ORDER BY does this).
     * @param string $today           'YYYY-MM-DD' for the "block start
     *                                has passed" comparison.
     */
    private static function derivedConvStatus( object $conv, array $all_convs, string $today ): string {
        if ( ! empty( $conv->coach_signoff_at ) ) return 'signed_off';
        if ( ! empty( $conv->conducted_at ) )     return 'held';
        if ( ! empty( $conv->scheduled_at ) )     return 'scheduled';

        $current_seq = (int) $conv->sequence;
        $next_to_plan_seq = null;
        foreach ( $all_convs as $c ) {
            if ( ! empty( $c->scheduled_at ) ) continue;
            if ( ! empty( $c->conducted_at ) ) continue;
            $seq = (int) $c->sequence;
            if ( $next_to_plan_seq === null || $seq < $next_to_plan_seq ) {
                $next_to_plan_seq = $seq;
            }
        }
        if ( $next_to_plan_seq === null || $current_seq !== $next_to_plan_seq ) {
            return 'future';
        }

        $prev_done = false;
        foreach ( $all_convs as $c ) {
            if ( (int) $c->sequence === $current_seq - 1 && ! empty( $c->conducted_at ) ) {
                $prev_done = true;
                break;
            }
        }
        $window_open = ! empty( $conv->planning_window_start )
                       && (string) $conv->planning_window_start <= $today;
        if ( $current_seq === 1 || $prev_done || $window_open ) {
            return 'to_be_planned';
        }
        return 'future';
    }

    private static function toDatetimeLocal( $value ): string {
        if ( empty( $value ) ) return '';
        $v = (string) $value;
        if ( strlen( $v ) >= 16 ) return substr( str_replace( ' ', 'T', $v ), 0, 16 );
        return '';
    }
}
