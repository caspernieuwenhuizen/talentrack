<?php
namespace TT\Shared\Frontend;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Infrastructure\Query\LookupTranslator;
use TT\Infrastructure\Query\QueryHelpers;
use TT\Shared\Frontend\Components\FrontendListTable;
use TT\Shared\Frontend\Components\TeamPickerComponent;

/**
 * FrontendEvaluationsView — coach-tier evaluations surface.
 *
 * List mode (default): recent evaluations across the coach's teams,
 * with filters and a "New evaluation" CTA.
 *
 * Create mode (?action=new): shows the evaluation form via
 * CoachForms::renderEvalForm. After save the form's existing redirect
 * sends the user back to the list.
 *
 * Edit mode (?action=edit&id=N): shows the same form in PUT mode,
 * prefilled from the existing eval row. Added in v3.110.55 alongside
 * the page-header Edit + Archive affordances on the detail view.
 */
class FrontendEvaluationsView extends FrontendViewBase {

    public static function render( int $user_id, bool $is_admin ): void {
        self::enqueueAssets();
        // 2026 restyle (#1695 long-tail) — green/gold card vocabulary for
        // the evaluation detail body. Depends on the app-chrome sheet for
        // the brand tokens; cheap no-op on the list / form paths.
        wp_enqueue_style(
            'tt-frontend-evaluations',
            TT_PLUGIN_URL . 'assets/css/frontend-evaluations.css',
            [ 'tt-frontend-app-chrome' ],
            TT_VERSION
        );

        $action = isset( $_GET['action'] ) ? sanitize_key( (string) $_GET['action'] ) : '';
        $id     = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
        $teams  = QueryHelpers::get_teams_in_scope( $user_id, $is_admin );

        // v3.110.64 — every code path on this routable view now emits
        // a `Dashboard / Evaluations / …` breadcrumb chain, per the
        // two-affordance contract in `docs/back-navigation.md`. The
        // list / new / edit / not-found paths previously called
        // `renderHeader()` without a breadcrumb, so the user had no
        // top-level link back to the dashboard. Detail path already
        // sets its own crumb in `renderDetail()`; nothing changes
        // there.
        $eval_crumb = \TT\Shared\Frontend\Components\FrontendBreadcrumbs::viewCrumb(
            'evaluations',
            __( 'Evaluations', 'talenttrack' )
        );

        if ( $action === 'new' ) {
            \TT\Shared\Frontend\Components\FrontendBreadcrumbs::fromDashboard(
                __( 'New evaluation', 'talenttrack' ),
                [ $eval_crumb ]
            );
            self::renderHeader( __( 'New evaluation', 'talenttrack' ) );
            // v3.110.3 — when launched from a player profile's empty
            // Evaluations tab, `?player_id=N` is in the URL; pre-fill
            // the form so the picker step is skipped.
            $preset_player_id = isset( $_GET['player_id'] ) ? absint( $_GET['player_id'] ) : 0;
            CoachForms::renderEvalForm( $teams, $is_admin, $preset_player_id );
            return;
        }

        // v3.110.55 — edit mode. Same form as create, switched to PUT
        // and prefilled. Cap-gated; falls through to the read-only
        // detail when the user can't edit.
        if ( $action === 'edit' && $id > 0 && current_user_can( 'tt_edit_evaluations' ) ) {
            $existing = self::loadEvaluation( $id );
            if ( ! $existing ) {
                \TT\Shared\Frontend\Components\FrontendBreadcrumbs::fromDashboard(
                    __( 'Evaluation not found', 'talenttrack' ),
                    [ $eval_crumb ]
                );
                self::renderHeader( __( 'Evaluation not found', 'talenttrack' ) );
                echo '<p><em>' . esc_html__( 'That evaluation no longer exists, or you do not have access.', 'talenttrack' ) . '</em></p>';
                return;
            }
            \TT\Shared\Frontend\Components\FrontendBreadcrumbs::fromDashboard(
                __( 'Edit evaluation', 'talenttrack' ),
                [ $eval_crumb ]
            );
            self::renderHeader( __( 'Edit evaluation', 'talenttrack' ) );
            CoachForms::renderEvalForm( $teams, $is_admin, 0, $existing );
            return;
        }

        // v3.110.4 — `?tt_view=evaluations&id=N` renders a read-only
        // detail page. Previously the URL was unhandled and the user
        // bounced back to the list — meaning every list-row link went
        // to the player / team / coach instead of the eval itself.
        if ( $id > 0 ) {
            self::renderDetail( $id, $user_id, $is_admin );
            return;
        }

        \TT\Shared\Frontend\Components\FrontendBreadcrumbs::fromDashboard(
            __( 'Evaluations', 'talenttrack' )
        );

        // v3.110.106 — "New evaluation" CTA moves into the page-header
        // actions slot for parity with the goals page (which is what
        // this list view's filter block was just retrofitted to match).
        $base_url      = remove_query_arg( [ 'action', 'id', 'f_team_id', 'f_player_id', 'f_date_from', 'f_date_to' ] );
        $flat_url      = add_query_arg( [ 'tt_view' => 'evaluations', 'action' => 'new' ], $base_url );
        $new_url       = \TT\Shared\Wizards\WizardEntryPoint::urlFor( 'new-evaluation', $flat_url );
        $eval_list_url = add_query_arg( [ 'tt_view' => 'evaluations' ], $base_url );
        $new_url       = add_query_arg( [ 'tt_back' => rawurlencode( $eval_list_url ) ], $new_url );

        $page_actions = [];
        if ( current_user_can( 'tt_edit_evaluations' ) ) {
            $page_actions[] = [
                'label'   => __( 'New evaluation', 'talenttrack' ),
                'href'    => $new_url,
                'primary' => true,
                'icon'    => '+',
            ];
        }
        self::renderHeader( __( 'Evaluations', 'talenttrack' ), self::pageActionsHtml( $page_actions ) );

        self::renderList( $user_id, $is_admin );
    }

    /**
     * v3.110.106 — declarative list using FrontendListTable. Filters
     * mirror the goals page (team / player / type / date range +
     * search), sortable columns + pagination handled by the shared
     * component, REST shape served by EvaluationsRestController.
     */
    private static function renderList( int $user_id, bool $is_admin ): void {
        // #3332 — the hand-built player map is gone; the picker resolves and
        // scopes its own candidates from `user_id` / `is_admin`, which is the
        // same rule this loop applied.

        // Evaluation-type options from the eval_type lookup vocabulary,
        // resolved through LookupTranslator so the label honours locale.
        $type_options = [];
        foreach ( QueryHelpers::get_eval_types() as $lk ) {
            $type_options[ (int) $lk->id ] = (string) LookupTranslator::name( $lk );
        }

        // #1380 — coach (evaluator) filter, available to academy-wide
        // viewers (the evaluation-coverage report links here per-coach).
        // Coaches with team-only scope don't see other coaches' lens, so
        // the filter only renders for `tt_view_analytics` holders.
        $coach_filter = [];
        if ( current_user_can( 'tt_view_analytics' ) ) {
            $coach_options = [];
            foreach ( ( new \TT\Modules\Analytics\EvalCoverageService() )->evaluators() as $ev ) {
                if ( $ev['coach_id'] > 0 && $ev['coach_name'] !== '' ) {
                    $coach_options[ $ev['coach_id'] ] = $ev['coach_name'];
                }
            }
            if ( $coach_options !== [] ) {
                $coach_filter['coach_id'] = [
                    'type'    => 'select',
                    'label'   => __( 'Coach', 'talenttrack' ),
                    'options' => $coach_options,
                ];
            }
        }

        echo FrontendListTable::render( [
            'rest_path' => 'evaluations',
            // #2449 — personal saved views for this list.
            'saved_views' => [ 'key' => 'evaluations-list' ],
            'columns'   => [
                'eval_date'  => [ 'label' => __( 'Date',    'talenttrack' ), 'sortable' => true, 'render' => 'html', 'value_key' => 'date_link_html' ],
                'player_name'=> [ 'label' => __( 'Player',  'talenttrack' ), 'sortable' => true, 'render' => 'html', 'value_key' => 'player_link_html' ],
                'team_name'  => [ 'label' => __( 'Team',    'talenttrack' ), 'sortable' => true, 'render' => 'html', 'value_key' => 'team_link_html' ],
                'coach_name' => [ 'label' => __( 'Coach',   'talenttrack' ), 'sortable' => true, 'render' => 'html', 'value_key' => 'coach_link_html' ],
                'avg_rating' => [ 'label' => __( 'Average', 'talenttrack' ), 'sortable' => true, 'render' => 'html', 'value_key' => 'avg_link_html' ],
                'notes'      => [ 'label' => __( 'Notes',   'talenttrack' ),                       'render' => 'text', 'value_key' => 'notes_excerpt' ],
            ],
            'filters' => array_merge( [
                'team_id' => [
                    'type'    => 'select',
                    'label'   => __( 'Team', 'talenttrack' ),
                    'options' => TeamPickerComponent::filterOptions( $user_id, $is_admin ),
                ],
                // #3332 — a typeahead, not every player in the academy.
                'player_id' => [
                    'type'     => 'player',
                    'label'    => __( 'Player', 'talenttrack' ),
                    'user_id'  => $user_id,
                    'is_admin' => $is_admin,
                ],
                'eval_type_id' => [
                    'type'    => 'select',
                    'label'   => __( 'Type', 'talenttrack' ),
                    'options' => $type_options,
                ],
            ], $coach_filter, [
                'date' => [
                    'type'       => 'date_range',
                    'param_from' => 'date_from',
                    'param_to'   => 'date_to',
                    'label_from' => __( 'From', 'talenttrack' ),
                    'label_to'   => __( 'To',   'talenttrack' ),
                ],
                // #1470 — Active / Archived status filter. #2023 — "All"
                // dropped: trashed rows live only in the recycle bin.
                'archived' => [
                    'type'    => 'select',
                    'render'  => 'status',
                    'label'   => __( 'Archive', 'talenttrack' ),
                    'options' => [
                        'active'   => __( 'Active',   'talenttrack' ),
                        'archived' => __( 'Archived', 'talenttrack' ),
                    ],
                ],
            ] ),
            // #1470 — Restore + gated permanent-delete on archived rows.
            'row_actions'  => \TT\Shared\Frontend\Components\ArchiveRowActions::build( 'evaluations', 'tt_edit_evaluations', 'evaluation' ),
            'search'       => [ 'placeholder' => __( 'Search player, notes…', 'talenttrack' ) ],
            'default_sort' => [ 'orderby' => 'eval_date', 'order' => 'desc' ],
            'empty_state'  => __( 'No evaluations match your filters.', 'talenttrack' ),
            // #1362 — guided fresh-install empty state (no active query only).
            'empty_state_card' => [
                'icon'      => 'evaluations',
                'headline'  => __( 'No evaluations yet', 'talenttrack' ),
                'explainer' => __( 'Evaluations capture how each player performed in a training or match. Record the first one after your next activity.', 'talenttrack' ),
                'cta_label' => __( 'Record first evaluation', 'talenttrack' ),
                'cta_url'   => \TT\Shared\Wizards\WizardEntryPoint::urlFor(
                    'new-evaluation',
                    add_query_arg( [ 'tt_view' => 'evaluations', 'action' => 'new' ], remove_query_arg( [ 'action', 'id' ] ) )
                ),
                'cta_cap'   => 'tt_edit_evaluations',
            ],
            // v3.110.170 — row-link standard. EvaluationsRestController
            // emits `detail_url` (same URL the inline date/avg cells use)
            // so dead-space clicks on the row open the evaluation detail.
            'row_url_key'  => 'detail_url',
        ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — render() returns escaped HTML.
    }

    /**
     * v3.110.4 — read-only detail page for a single evaluation.
     * Reachable via `?tt_view=evaluations&id=N`. Shows the eval
     * header (date / player / team / coach / activity context) plus
     * every rating grouped by main category with sub-ratings indented
     * underneath, then notes.
     */
    /**
     * #2022 — compact read-only surface for an archived / trashed evaluation.
     * Reached only via the not-found retry in renderDetail(). The resolved row
     * is the raw `tt_evaluations` row (SELECT *); the player name is looked up
     * separately for the identity anchor.
     *
     * @param array{row:object,state:string} $resolved
     */
    private static function renderArchivedReadOnly( array $resolved ): void {
        $eval = $resolved['row'];

        $player_name = '';
        $player_id   = (int) ( $eval->player_id ?? 0 );
        if ( $player_id > 0 ) {
            $player = \TT\Infrastructure\Query\QueryHelpers::get_player( $player_id );
            if ( $player ) {
                $player_name = \TT\Infrastructure\Query\QueryHelpers::player_display_name( $player );
            }
        }
        if ( $player_name === '' ) {
            $player_name = $player_id > 0 ? '#' . $player_id : __( 'Unknown player', 'talenttrack' );
        }

        /* translators: %s = player display name */
        $title = sprintf( __( 'Evaluation of %s', 'talenttrack' ), $player_name );

        \TT\Shared\Frontend\Components\FrontendBreadcrumbs::fromDashboard(
            $title,
            [ \TT\Shared\Frontend\Components\FrontendBreadcrumbs::viewCrumb( 'evaluations', __( 'Evaluations', 'talenttrack' ) ) ]
        );
        \TT\Shared\Frontend\Components\ArchivedDetailCard::enqueue();

        $list_url = add_query_arg( [ 'tt_view' => 'evaluations' ], \TT\Shared\Frontend\Components\RecordLink::dashboardUrl() );
        $self_url = add_query_arg( [ 'tt_view' => 'evaluations', 'id' => (int) $eval->id ], \TT\Shared\Frontend\Components\RecordLink::dashboardUrl() );

        $fields = [];
        $fields[] = [ __( 'Player', 'talenttrack' ), esc_html( $player_name ) ];
        $eval_date = (string) ( $eval->eval_date ?? '' );
        if ( $eval_date !== '' && $eval_date !== '0000-00-00' ) {
            $fields[] = [ __( 'Date', 'talenttrack' ), esc_html( $eval_date ) ];
        }
        $opponent = (string) ( $eval->opponent ?? '' );
        if ( $opponent !== '' ) {
            $fields[] = [ __( 'Opponent', 'talenttrack' ), esc_html( $opponent ) ];
        }

        \TT\Shared\Frontend\Components\ArchivedDetailCard::render( 'evaluation', $resolved, [
            'title'            => $title,
            'fields'           => $fields,
            'list_url'         => $list_url,
            'restore_redirect' => $self_url,
        ] );
    }

    private static function renderDetail( int $eval_id, int $user_id, bool $is_admin ): void {
        global $wpdb;
        $p = $wpdb->prefix;

        // v3.110.104 — also fetch `eval_type_id` + a left join on
        // `tt_lookups` for the localised label. Pre-fix the detail
        // page didn't render the evaluation type at all, even though
        // it's stored on every row written via the wizard (v3.110.67
        // wired the eval_type_id persistence) and the edit form
        // expects to pre-fill from it (v3.110.105 will).
        $eval = $wpdb->get_row( $wpdb->prepare(
            "SELECT e.id, e.eval_date, e.notes, e.player_id, e.coach_id, e.opponent, e.competition, e.game_result, e.home_away, e.minutes_played,
                    e.eval_type_id,
                    pl.first_name, pl.last_name, pl.team_id,
                    t.name AS team_name,
                    u.display_name AS coach_name,
                    coach_p.id AS coach_person_id,
                    et.id AS eval_type_lookup_id, et.name AS eval_type_key, et.meta AS eval_type_meta
               FROM {$p}tt_evaluations e
               LEFT JOIN {$p}tt_players pl ON pl.id = e.player_id
               LEFT JOIN {$p}tt_teams   t  ON t.id  = pl.team_id
               LEFT JOIN {$wpdb->users} u  ON u.ID  = e.coach_id
               LEFT JOIN {$p}tt_people coach_p ON coach_p.wp_user_id = e.coach_id AND coach_p.club_id = e.club_id
               LEFT JOIN {$p}tt_lookups et ON et.id = e.eval_type_id AND et.lookup_type = 'eval_type'
              WHERE e.id = %d AND e.club_id = %d AND e.archived_at IS NULL
              LIMIT 1",
            $eval_id, \TT\Infrastructure\Tenancy\CurrentClub::id()
        ) );

        if ( ! $eval ) {
            // #2022 — the detail query ends in `archived_at IS NULL`, so an
            // archived / trashed evaluation never reaches it. Retry through the
            // archive-aware gate before falling to not-found; a null return
            // stays a clean 404 (honours the trashed-visibility gate).
            $resolved = \TT\Shared\Frontend\Components\ArchivedDetailCard::resolve( 'evaluation', $eval_id );
            if ( $resolved !== null && $resolved['state'] !== 'active' ) {
                self::renderArchivedReadOnly( $resolved );
                return;
            }
            \TT\Shared\Frontend\Components\FrontendBreadcrumbs::fromDashboard(
                __( 'Evaluation not found', 'talenttrack' ),
                [ \TT\Shared\Frontend\Components\FrontendBreadcrumbs::viewCrumb( 'evaluations', __( 'Evaluations', 'talenttrack' ) ) ]
            );
            self::renderHeader( __( 'Evaluation not found', 'talenttrack' ) );
            echo '<p><em>' . esc_html__( 'That evaluation no longer exists, or you do not have access.', 'talenttrack' ) . '</em></p>';
            return;
        }

        $player_name = trim( ( $eval->first_name ?? '' ) . ' ' . ( $eval->last_name ?? '' ) );
        if ( $player_name === '' ) $player_name = '#' . (int) $eval->player_id;

        \TT\Shared\Frontend\Components\FrontendBreadcrumbs::fromDashboard(
            sprintf( /* translators: %s = player name */ __( 'Evaluation — %s', 'talenttrack' ), $player_name ),
            [ \TT\Shared\Frontend\Components\FrontendBreadcrumbs::viewCrumb( 'evaluations', __( 'Evaluations', 'talenttrack' ) ) ]
        );

        /* translators: %s = player display name */
        $page_title = sprintf( __( 'Evaluation of %s', 'talenttrack' ), $player_name );

        // v3.110.55 — Edit + Archive in the page-header actions slot.
        // Edit becomes a FAB bottom-right on mobile; Archive is a
        // danger-styled secondary button (hidden on mobile by the slot
        // CSS). Archive wires through the generic
        // tt-frontend-archive-button.js handler — DELETE evaluations/{id}
        // soft-archives the row and redirects back to the list.
        $list_url    = add_query_arg( [ 'tt_view' => 'evaluations' ], \TT\Shared\Frontend\Components\RecordLink::dashboardUrl() );
        $actions     = [];
        if ( current_user_can( 'tt_edit_evaluations' ) ) {
            $edit_url = add_query_arg(
                [ 'tt_view' => 'evaluations', 'id' => $eval_id, 'action' => 'edit' ],
                \TT\Shared\Frontend\Components\RecordLink::dashboardUrl()
            );
            $actions[] = [
                'label'   => __( 'Edit', 'talenttrack' ),
                'href'    => $edit_url,
                'primary' => true,
                'icon'    => \TT\Shared\Icons\IconRenderer::render( 'edit', [ 'width' => 16, 'height' => 16 ] ), // #1365 — inline SVG edit icon.
            ];
            $actions[] = [
                'label'      => __( 'Archive', 'talenttrack' ),
                'variant'    => 'danger',
                // #2871 — icon-only; keeps its confirmation.
                'icon_only'  => true,
                'data_attrs' => [
                    'tt-archive-rest-path' => 'evaluations/' . $eval_id,
                    'tt-archive-confirm'   => __( 'Archive this evaluation? It will be hidden but the data is preserved.', 'talenttrack' ),
                    'tt-archive-redirect'  => $list_url,
                ],
            ];
        }
        self::renderHeader( $page_title, $actions ? self::pageActionsHtml( $actions ) : '' );

        $ratings = ( new \TT\Infrastructure\Evaluations\EvalRatingsRepository() )->getForEvaluation( $eval_id );

        // Group ratings: mains first, then subs nested under their parent.
        $by_parent = [];
        $mains     = [];
        foreach ( $ratings as $row ) {
            $parent_id = $row->parent_id !== null ? (int) $row->parent_id : 0;
            if ( $parent_id === 0 ) {
                $mains[ (int) $row->category_id ] = $row;
            } else {
                $by_parent[ $parent_id ][] = $row;
            }
        }

        $team_id    = (int) ( $eval->team_id ?? 0 );
        $team_name  = (string) ( $eval->team_name ?? '' );
        $coach_name = (string) ( $eval->coach_name ?? '' );
        $coach_pid  = (int) ( $eval->coach_person_id ?? 0 );

        ?>
        <section class="tt-record-detail tt-evd">
            <div class="tt-record-detail-meta tt-evd__meta">
                <dl class="tt-profile-dl tt-evd__dl">
                    <dt><?php esc_html_e( 'Date', 'talenttrack' ); ?></dt>
                    <dd><?php echo esc_html( \TT\Shared\Dates\TTDate::date( (string) $eval->eval_date ) ); ?></dd>
                    <?php
                    // v3.110.175 — was selecting `et.label AS eval_type_label`
                    // but `tt_lookups` has no `label` column. The SELECT errored
                    // silently and the Type row never rendered. Pass the
                    // lookup id instead; `LookupTranslator::name()` resolves
                    // via `tt_translations` when an id is given, otherwise
                    // falls back to `__($name, 'talenttrack')`.
                    $eval_type_label = '';
                    if ( ! empty( $eval->eval_type_id ) ) {
                        $lookup_row = (object) [
                            'id'    => (int)    ( $eval->eval_type_lookup_id ?? 0 ),
                            'name'  => (string) ( $eval->eval_type_key ?? '' ),
                            'meta'  => (string) ( $eval->eval_type_meta ?? '' ),
                        ];
                        $eval_type_label = (string) \TT\Infrastructure\Query\LookupTranslator::name( $lookup_row );
                    }
                    if ( $eval_type_label !== '' ) :
                    ?>
                        <dt><?php esc_html_e( 'Type', 'talenttrack' ); ?></dt>
                        <dd><?php echo esc_html( $eval_type_label ); ?></dd>
                    <?php endif; ?>
                    <dt><?php esc_html_e( 'Player', 'talenttrack' ); ?></dt>
                    <dd><?php echo \TT\Shared\Frontend\Components\RecordLink::inline(
                        $player_name,
                        \TT\Shared\Frontend\Components\RecordLink::detailUrlForWithBack( 'players', (int) $eval->player_id )
                    ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — link helper escapes label and URL ?></dd>
                    <?php if ( $team_id > 0 && $team_name !== '' ) : ?>
                        <dt><?php esc_html_e( 'Team', 'talenttrack' ); ?></dt>
                        <dd><?php echo \TT\Shared\Frontend\Components\RecordLink::inline(
                            $team_name,
                            \TT\Shared\Frontend\Components\RecordLink::detailUrlForWithBack( 'teams', $team_id )
                        ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></dd>
                    <?php endif; ?>
                    <?php if ( $coach_name !== '' ) : ?>
                        <dt><?php esc_html_e( 'Coach', 'talenttrack' ); ?></dt>
                        <dd><?php
                            if ( $coach_pid > 0 ) {
                                echo \TT\Shared\Frontend\Components\RecordLink::inline(
                                    $coach_name,
                                    \TT\Shared\Frontend\Components\RecordLink::detailUrlForWithBack( 'people', $coach_pid )
                                );
                            } else {
                                echo esc_html( $coach_name );
                            }
                        ?></dd>
                    <?php endif; ?>
                    <?php if ( ! empty( $eval->opponent ) ) : ?>
                        <dt><?php esc_html_e( 'Opponent', 'talenttrack' ); ?></dt>
                        <dd><?php echo esc_html( (string) $eval->opponent ); ?></dd>
                    <?php endif; ?>
                    <?php if ( ! empty( $eval->game_result ) ) : ?>
                        <dt><?php esc_html_e( 'Result', 'talenttrack' ); ?></dt>
                        <dd><?php echo esc_html( (string) $eval->game_result ); ?>
                            <?php if ( ! empty( $eval->home_away ) ) : ?>
                                <span class="tt-muted"> &middot; <?php echo esc_html( (string) $eval->home_away ); ?></span>
                            <?php endif; ?>
                        </dd>
                    <?php endif; ?>
                    <?php if ( ! empty( $eval->minutes_played ) ) : ?>
                        <dt><?php esc_html_e( 'Minutes Played', 'talenttrack' ); ?></dt>
                        <dd><?php echo (int) $eval->minutes_played; ?></dd>
                    <?php endif; ?>
                </dl>
            </div>

            <div class="tt-record-detail-body tt-evd__body">
                <h3 class="tt-evd__h3"><?php esc_html_e( 'Ratings', 'talenttrack' ); ?></h3>
                <?php if ( empty( $mains ) && empty( $by_parent ) ) : ?>
                    <p class="tt-muted"><?php esc_html_e( 'No ratings recorded for this evaluation.', 'talenttrack' ); ?></p>
                <?php else : ?>
                    <div class="tt-table-wrap">
                    <table class="tt-table tt-evd__ratings">
                        <thead><tr>
                            <th><?php esc_html_e( 'Category', 'talenttrack' ); ?></th>
                            <th class="tt-evd__rating-col"><?php esc_html_e( 'Rating', 'talenttrack' ); ?></th>
                        </tr></thead>
                        <tbody>
                            <?php foreach ( $mains as $cat_id => $main ) :
                                $label = (string) ( $main->category_label ?? $main->category_key ?? '—' );
                                ?>
                                <tr>
                                    <td><strong><?php echo esc_html( \TT\Infrastructure\Evaluations\EvalCategoriesRepository::displayLabel( $label, (int) $cat_id ) ); ?></strong></td>
                                    <td class="tt-evd__rating-col"><?php echo esc_html( number_format_i18n( (float) $main->rating, 1 ) ); ?></td>
                                </tr>
                                <?php if ( ! empty( $by_parent[ $cat_id ] ) ) : ?>
                                    <?php foreach ( $by_parent[ $cat_id ] as $sub ) :
                                        $sub_label = (string) ( $sub->category_label ?? $sub->category_key ?? '—' );
                                        ?>
                                        <tr class="tt-evd__sub">
                                            <td class="tt-evd__sub-label"><?php echo \TT\Shared\Icons\IconRenderer::render( 'corner-down-right', [ 'width' => 12, 'height' => 12, 'style' => 'vertical-align:-1px;margin-right:2px;' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — trusted SVG. ?><?php echo esc_html( \TT\Infrastructure\Evaluations\EvalCategoriesRepository::displayLabel( $sub_label, (int) $sub->category_id ) ); ?></td>
                                            <td class="tt-evd__rating-col tt-evd__sub-rating"><?php echo esc_html( number_format_i18n( (float) $sub->rating, 1 ) ); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <?php // Subs whose parent main wasn't directly rated. ?>
                            <?php foreach ( $by_parent as $parent_id => $subs ) :
                                if ( isset( $mains[ $parent_id ] ) ) continue;
                                foreach ( $subs as $sub ) :
                                    $sub_label = (string) ( $sub->category_label ?? $sub->category_key ?? '—' );
                                    ?>
                                    <tr>
                                        <td><?php echo esc_html( \TT\Infrastructure\Evaluations\EvalCategoriesRepository::displayLabel( $sub_label, (int) $sub->category_id ) ); ?></td>
                                        <td class="tt-evd__rating-col"><?php echo esc_html( number_format_i18n( (float) $sub->rating, 1 ) ); ?></td>
                                    </tr>
                                <?php endforeach;
                            endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                <?php endif; ?>

                <?php if ( ! empty( $eval->notes ) ) : ?>
                    <h3 class="tt-evd__h3 tt-evd__h3--notes"><?php esc_html_e( 'Notes', 'talenttrack' ); ?></h3>
                    <p class="tt-evd__notes"><?php echo esc_html( (string) $eval->notes ); ?></p>
                <?php endif; ?>
            </div>
        </section>
        <?php
    }

    /**
     * v3.110.55 — load a non-archived eval row for the edit form.
     * Tenancy-scoped, so cross-club edits return null.
     */
    private static function loadEvaluation( int $eval_id ): ?object {
        global $wpdb;
        $p   = $wpdb->prefix;
        // v3.110.105 — also fetch `activity_id` so the edit form's
        // Type pre-fill can back-fill from the activity's type when
        // the eval row itself doesn't carry an `eval_type_id` (legacy
        // mark-attendance wizard rows written before v3.110.105's
        // EvaluationInserter started persisting the type).
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT id, player_id, eval_type_id, eval_date, notes, opponent, competition, game_result, home_away, minutes_played, activity_id
               FROM {$p}tt_evaluations
              WHERE id = %d AND club_id = %d AND archived_at IS NULL
              LIMIT 1",
            $eval_id, \TT\Infrastructure\Tenancy\CurrentClub::id()
        ) );
        return $row ?: null;
    }
}
