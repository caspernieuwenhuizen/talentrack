<?php
namespace TT\Shared\Frontend;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Infrastructure\Query\LabelTranslator;
use TT\Infrastructure\Query\QueryHelpers;
use TT\Shared\Frontend\Components\DateInputComponent;
use TT\Shared\Frontend\Components\FormSaveButton;
use TT\Shared\Frontend\Components\FrontendListTable;
use TT\Shared\Frontend\Components\GoalPrinciplePicker;
use TT\Shared\Frontend\Components\PlayerSearchPickerComponent;
use TT\Shared\Frontend\Components\TeamPickerComponent;

/**
 * FrontendGoalsManageView — full-CRUD frontend for player goals.
 *
 * #0019 Sprint 2 session 2.4 — closes Sprint 2. Mirrors the
 * Sessions view structure (list / create / edit dispatch via
 * query string) but for goals.
 *
 *   ?tt_view=goals             — list view + "New goal" CTA
 *   ?tt_view=goals&action=new  — create form
 *   ?tt_view=goals&id=<int>    — edit form (prefilled)
 *
 * The list view uses an inline-select column for status — Q4 in
 * the Sprint 2 plan locked us to row-inline status edits (no
 * detail panel). The select hits `PATCH /goals/{id}/status`
 * (added in Sprint 1 session 1) via the FrontendListTable's
 * inline_select render type (added alongside this view).
 *
 * Delete is a row action that hits `DELETE /goals/{id}` — the
 * controller treats it as a soft-archive (sets `archived_at`).
 */
class FrontendGoalsManageView extends FrontendViewBase {

    public static function render( int $user_id, bool $is_admin ): void {
        self::enqueueAssets();
        self::enqueueGoalsStyle();

        $action = isset( $_GET['action'] ) ? sanitize_key( (string) $_GET['action'] ) : '';
        $id     = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

        // v3.92.1 — breadcrumb chain reflects action/id state.
        $current = __( 'Goals', 'talenttrack' );
        $intermediate = null;
        if ( $action === 'new' ) {
            $current = __( 'Add goal', 'talenttrack' );
            $intermediate = [ \TT\Shared\Frontend\Components\FrontendBreadcrumbs::viewCrumb( 'goals', __( 'Goals', 'talenttrack' ) ) ];
        } elseif ( $id > 0 && $action === 'edit' ) {
            $current = __( 'Edit goal', 'talenttrack' );
            $intermediate = [ \TT\Shared\Frontend\Components\FrontendBreadcrumbs::viewCrumb( 'goals', __( 'Goals', 'talenttrack' ) ) ];
        } elseif ( $id > 0 ) {
            $current = __( 'Goal detail', 'talenttrack' );
            $intermediate = [ \TT\Shared\Frontend\Components\FrontendBreadcrumbs::viewCrumb( 'goals', __( 'Goals', 'talenttrack' ) ) ];
        }
        \TT\Shared\Frontend\Components\FrontendBreadcrumbs::fromDashboard( $current, $intermediate );

        if ( $action === 'new' ) {
            self::renderHeader( __( 'Add goal', 'talenttrack' ) );
            self::renderForm( $user_id, $is_admin, null );
            return;
        }

        // v3.70.1 hotfix — `?tt_view=goals&id=N` (no action) renders a
        // read-only detail; the edit form requires `&action=edit`.
        // Mirrors the activities pattern so title clicks land on a
        // viewable detail page instead of an edit form.
        if ( $id > 0 && $action === 'edit' ) {
            $goal = self::loadGoal( $id );
            self::renderHeader( $goal ? sprintf( __( 'Edit goal — %s', 'talenttrack' ), (string) $goal->title ) : __( 'Goal not found', 'talenttrack' ) );
            if ( ! $goal ) {
                echo '<p class="tt-notice">' . esc_html__( 'That goal no longer exists.', 'talenttrack' ) . '</p>';
                return;
            }
            self::renderForm( $user_id, $is_admin, $goal );
            return;
        }

        if ( $id > 0 ) {
            $goal = self::loadGoal( $id );
            // #2022 — loadGoal() ends in `archived_at IS NULL`, so an archived
            // / trashed goal never reaches it. Retry through the archive-aware
            // gate before falling to not-found; a null return stays a clean 404.
            if ( ! $goal ) {
                $resolved = \TT\Shared\Frontend\Components\ArchivedDetailCard::resolve( 'goal', $id );
                if ( $resolved !== null && $resolved['state'] !== 'active' ) {
                    self::renderArchivedReadOnly( $resolved );
                    return;
                }
            }
            // v3.110.53 — Edit + Archive page-header actions on the
            // goal detail page (replaces the inline Edit button that
            // used to sit below the dl + the row Delete action that
            // used to sit on the list).
            $detail_actions = [];
            if ( $goal && current_user_can( 'tt_edit_goals' ) ) {
                $goals_list_url = add_query_arg( [ 'tt_view' => 'goals' ], \TT\Shared\Frontend\Components\RecordLink::dashboardUrl() );
                $edit_url = add_query_arg(
                    [ 'tt_view' => 'goals', 'id' => (int) $goal->id, 'action' => 'edit' ],
                    \TT\Shared\Frontend\Components\RecordLink::dashboardUrl()
                );
                $detail_actions[] = [
                    'label'   => __( 'Edit', 'talenttrack' ),
                    'href'    => $edit_url,
                    'primary' => true,
                    'icon'    => \TT\Shared\Icons\IconRenderer::render( 'edit', [ 'width' => 16, 'height' => 16 ] ), // #1365 — inline SVG edit icon.
                ];
                // #1332 — Print doelenintake reaches the same printable
                // surface that the player profile + team detail already
                // expose. Derive `player_id` from the goal; defensive
                // skip if it's missing (schema permits null even though
                // the create wizard requires it).
                $goal_player_id = (int) ( $goal->player_id ?? 0 );
                if ( $goal_player_id > 0 ) {
                    $intake_url = add_query_arg(
                        [ 'tt_goal_intake_print' => '1', 'player_id' => $goal_player_id ],
                        home_url( '/' )
                    );
                    $detail_actions[] = [
                        'label'  => __( 'Print doelenintake', 'talenttrack' ),
                        'href'   => $intake_url,
                        'target' => '_blank',
                        'icon'   => '⎙',
                    ];
                }
                $detail_actions[] = [
                    'label'   => __( 'Archive', 'talenttrack' ),
                    'variant' => 'danger',
                    // #2871 — icon-only; keeps its confirmation.
                    'icon_only' => true,
                    'data_attrs' => [
                        'tt-archive-rest-path' => 'goals/' . (int) $goal->id,
                        'tt-archive-confirm'   => __( 'Archive this goal? It will be hidden but the data is preserved.', 'talenttrack' ),
                        'tt-archive-redirect'  => $goals_list_url,
                    ],
                ];
            }
            self::renderHeader(
                $goal ? (string) $goal->title : __( 'Goal not found', 'talenttrack' ),
                self::pageActionsHtml( $detail_actions )
            );
            if ( ! $goal ) {
                echo '<p class="tt-notice">' . esc_html__( 'That goal no longer exists.', 'talenttrack' ) . '</p>';
                return;
            }
            self::renderDetail( $goal, $user_id );
            return;
        }

        // v3.110.53 — header-actions slot for + New goal.
        $list_base_url = remove_query_arg( [ 'action', 'id' ] );
        $page_actions = [];
        if ( current_user_can( 'tt_edit_goals' ) ) {
            $flat_url = add_query_arg( [ 'tt_view' => 'goals', 'action' => 'new' ], $list_base_url );
            $page_actions[] = [
                'label'   => __( 'Add goal', 'talenttrack' ),
                'href'    => \TT\Shared\Wizards\WizardEntryPoint::urlFor( 'new-goal', $flat_url ),
                'primary' => true,
                'icon'    => '+',
            ];
        }
        self::renderHeader( __( 'Goals', 'talenttrack' ), self::pageActionsHtml( $page_actions ) );
        self::renderList( $user_id, $is_admin );
    }

    /**
     * v3.70.1 hotfix — read-only goal detail. Shows the core fields plus
     * the goal's #0028 conversation thread when available.
     *
     * @param object $goal goal row from `loadGoal`
     */
    private static function renderDetail( object $goal, int $user_id ): void {
        $player_id = (int) ( $goal->player_id ?? 0 );
        $player    = $player_id > 0 ? QueryHelpers::get_player( $player_id ) : null;
        $status    = (string) ( $goal->status ?? '' );
        $priority  = (string) ( $goal->priority ?? '' );
        $due_date  = (string) ( $goal->due_date ?? '' );
        $desc      = (string) ( $goal->description ?? '' );

        // #1687 — 2026 restyle: chrome card with status / priority / due
        // chips, an owners row, then the remaining fields. Same data the
        // dl carried — no new queries.
        [ $status_chip_class, $card_accent ] = self::statusChipClasses( $status );
        $priority_chip_class = self::priorityChipClass( $priority );

        echo '<div class="tt-record-detail tt-goal-detail-grid">';
        echo '<article class="tt-goal-card tt-goal-detail-card ' . esc_attr( $card_accent ) . '">';

        // Chip row — status + priority + due.
        echo '<div class="tt-goal-card__meta">';
        if ( $status !== '' ) {
            echo '<span class="tt-goal-chip ' . esc_attr( $status_chip_class ) . '">'
                . esc_html( LabelTranslator::goalStatus( strtolower( str_replace( ' ', '_', $status ) ) ) )
                . '</span>';
        }
        if ( $priority !== '' ) {
            echo '<span class="tt-goal-chip ' . esc_attr( $priority_chip_class ) . '">'
                . esc_html( LabelTranslator::goalPriority( strtolower( $priority ) ) )
                . '</span>';
        }
        if ( $due_date !== '' ) {
            echo '<span class="tt-goal-due">' . esc_html__( 'Due:', 'talenttrack' ) . ' '
                . esc_html( \TT\Shared\Dates\TTDate::date( $due_date ) ) . '</span>';
        }
        echo '</div>';

        // Player owner row — anchors the card to the player record.
        if ( $player ) {
            $player_name = QueryHelpers::player_display_name( $player );
            echo '<div class="tt-goal-card__footer">';
            echo '<span class="tt-goal-owners">';
            echo '<span class="tt-goal-owner-av" aria-hidden="true">' . esc_html( self::initials( (string) $player_name ) ) . '</span>';
            echo '</span>';
            echo '<span class="tt-goal-owners-label">'
                . \TT\Shared\Frontend\Components\RecordLink::inline( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    (string) $player_name,
                    \TT\Shared\Frontend\Components\RecordLink::detailUrlForWithBack( 'players', $player_id )
                )
                . '</span>';
            echo '</div>';
        }

        if ( $desc !== '' ) {
            echo '<div class="tt-goal-detail-field">';
            echo '<span class="tt-goal-detail-field__label">' . esc_html__( 'Description', 'talenttrack' ) . '</span>';
            echo '<div class="tt-goal-detail-field__value">' . esc_html( $desc ) . '</div>';
            echo '</div>';
        }

        // #2218 — surface progress %, connected principle + football
        // action. Name resolution lives in the repositories (CLAUDE.md § 4).
        \TT\Shared\Frontend\Components\GoalDetailFields::render( $goal );

        echo '</article>';

        // v3.110.53 — Edit + Archive moved to the page-header actions
        // slot rendered by render() before this method runs.

        if ( class_exists( '\TT\Shared\Frontend\Components\FrontendThreadView' ) ) {
            echo '<section class="tt-pde-section">';
            echo '<h3>' . esc_html__( 'Conversation', 'talenttrack' ) . '</h3>';
            \TT\Shared\Frontend\Components\FrontendThreadView::render( 'goal', (int) $goal->id, $user_id );
            echo '</section>';
        }

        echo '</div>';
    }

    /**
     * #2022 — compact read-only surface for an archived / trashed goal.
     * The breadcrumb chain is already emitted by render() before this runs,
     * so this method only renders the header + the shared card.
     *
     * @param array{row:object,state:string} $resolved
     */
    private static function renderArchivedReadOnly( array $resolved ): void {
        $goal  = $resolved['row'];
        $title = (string) ( $goal->title ?? __( 'Goal', 'talenttrack' ) );

        self::renderHeader( $title );
        \TT\Shared\Frontend\Components\ArchivedDetailCard::enqueue();

        $goals_url = add_query_arg( [ 'tt_view' => 'goals' ], \TT\Shared\Frontend\Components\RecordLink::dashboardUrl() );
        $self_url  = add_query_arg( [ 'tt_view' => 'goals', 'id' => (int) $goal->id ], \TT\Shared\Frontend\Components\RecordLink::dashboardUrl() );

        $fields = [];
        $status = (string) ( $goal->status ?? '' );
        if ( $status !== '' ) {
            $fields[] = [
                __( 'Status', 'talenttrack' ),
                esc_html( LabelTranslator::goalStatus( strtolower( str_replace( ' ', '_', $status ) ) ) ),
            ];
        }
        $priority = (string) ( $goal->priority ?? '' );
        if ( $priority !== '' ) {
            $fields[] = [
                __( 'Priority', 'talenttrack' ),
                esc_html( LabelTranslator::goalPriority( strtolower( $priority ) ) ),
            ];
        }
        $due = (string) ( $goal->due_date ?? '' );
        if ( $due !== '' && $due !== '0000-00-00' ) {
            $fields[] = [ __( 'Due', 'talenttrack' ), esc_html( \TT\Shared\Dates\TTDate::date( $due ) ) ];
        }
        $player_id = (int) ( $goal->player_id ?? 0 );
        if ( $player_id > 0 ) {
            $player = QueryHelpers::get_player( $player_id );
            if ( $player ) {
                $fields[] = [ __( 'Player', 'talenttrack' ), esc_html( QueryHelpers::player_display_name( $player ) ) ];
            }
        }

        \TT\Shared\Frontend\Components\ArchivedDetailCard::render( 'goal', $resolved, [
            'title'            => $title,
            'fields'           => $fields,
            'list_url'         => $goals_url,
            'restore_redirect' => $self_url,
        ] );
    }

    /**
     * List view — FrontendListTable with team/player/status/priority/
     * deadline filters, inline status select, Edit/Delete row actions.
     */
    private static function renderList( int $user_id, bool $is_admin ): void {
        // v3.110.101 — the inline `<p><a class="tt-btn">New goal</a></p>`
        // button above the table was a duplicate of the page-header
        // action rendered in the parent `render()`. Removed; the
        // page-header action is the single CTA per the page-actions
        // standard.

        // Build status / priority option maps from the lookup tables so
        // a club running custom statuses ("Achieved", "Cancelled", …)
        // sees their values without code changes.
        $status_options = [];
        foreach ( QueryHelpers::get_lookup_names( 'goal_status' ) as $st ) {
            $value = strtolower( str_replace( ' ', '_', (string) $st ) );
            $status_options[ $value ] = LabelTranslator::goalStatus( $value );
        }
        $priority_options = [];
        foreach ( QueryHelpers::get_lookup_names( 'goal_priority' ) as $pr ) {
            $value = strtolower( (string) $pr );
            $priority_options[ $value ] = LabelTranslator::goalPriority( $value );
        }

        // #3332 — the hand-built `$player_options` map is gone. The picker
        // resolves and scopes its own candidates from `user_id` / `is_admin`,
        // which is the same rule this loop applied (admin sees all, a coach
        // sees their teams') expressed once instead of per surface.

        // v3.110.53 — Edit / Delete moved to the goal detail page; the
        // clickable goal title is the only active-row affordance.
        // #1470 — Restore + gated permanent-delete on archived rows.
        $row_actions = \TT\Shared\Frontend\Components\ArchiveRowActions::build( 'goals', 'tt_edit_goals', 'goal' );

        echo FrontendListTable::render( [
            'rest_path' => 'goals',
            // #2449 — personal saved views for this list.
            'saved_views' => [ 'key' => 'goals-list' ],
            'columns' => [
                // #0063 — player + goal title clickable; status as a
                // colour pill (display-only) instead of an inline-select.
                // Inline edit lives on the goal form; the table reads.
                'player_name' => [ 'label' => __( 'Player',   'talenttrack' ), 'sortable' => true, 'render' => 'html', 'value_key' => 'player_link_html' ],
                'title'       => [ 'label' => __( 'Goal',     'talenttrack' ), 'sortable' => true, 'render' => 'html', 'value_key' => 'title_link_html' ],
                'priority'    => [ 'label' => __( 'Priority', 'talenttrack' ), 'sortable' => true ],
                'status'      => [
                    'label'       => __( 'Status', 'talenttrack' ),
                    'sortable'    => true,
                    'render'      => 'html',
                    'value_key'   => 'status_pill_html',
                    // Drop the inline_select / options / patch_path — status
                    // is now display-only on the table per the user's
                    // "should be display only. use colored pills" ask.
                    'options'     => $status_options,
                    'patch_path'  => 'goals/{id}/status',
                    'patch_field' => 'status',
                ],
                'due_date'    => [ 'label' => __( 'Due',      'talenttrack' ), 'sortable' => true, 'render' => 'date' ],
            ],
            'filters' => [
                'team_id' => [
                    'type'    => 'select',
                    'label'   => __( 'Team', 'talenttrack' ),
                    'options' => TeamPickerComponent::filterOptions( $user_id, $is_admin ),
                ],
                // #3332 — a typeahead, not a dropdown of every player the
                // reader can see. An admin's list was the whole academy, in
                // stored order, with no way to type toward a name.
                'player_id' => [
                    'type'     => 'player',
                    'label'    => __( 'Player', 'talenttrack' ),
                    'user_id'  => $user_id,
                    'is_admin' => $is_admin,
                ],
                // #2202 — three semantic status buckets (Active / Achieved /
                // Missed) rendered as pills, no "All", defaulting to Active so
                // the list opens on the goals a coach is actively working on.
                // The REST controller maps these keys onto the canonical
                // completed / cancelled codes (GoalsRestController::statusWhereClause).
                'status' => [
                    'type'    => 'select',
                    'render'  => 'status',
                    'label'   => __( 'Status', 'talenttrack' ),
                    'no_all'  => true,
                    'default' => 'active',
                    'options' => [
                        'active'   => __( 'Active',   'talenttrack' ),
                        'achieved' => __( 'Achieved', 'talenttrack' ),
                        'missed'   => __( 'Missed',   'talenttrack' ),
                    ],
                ],
                'priority' => [
                    'type'    => 'select',
                    'label'   => __( 'Priority', 'talenttrack' ),
                    'options' => $priority_options,
                ],
                'due' => [
                    'type'       => 'date_range',
                    'param_from' => 'due_from',
                    'param_to'   => 'due_to',
                    'label_from' => __( 'Due from', 'talenttrack' ),
                    'label_to'   => __( 'Due to',   'talenttrack' ),
                ],
                // #1470 — Active / Archived. Labelled "Archive" to avoid
                // clashing with the goal-status filter above. #2023 — "All"
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
            ],
            'row_actions'  => $row_actions,
            'search'       => [ 'placeholder' => __( 'Search title, description, player…', 'talenttrack' ) ],
            'default_sort' => [ 'orderby' => 'due_date', 'order' => 'asc' ],
            'empty_state'  => __( 'No goals match your filters.', 'talenttrack' ),
            // #1362 — guided fresh-install empty state (no active query only).
            'empty_state_card' => [
                'icon'      => 'goals',
                'headline'  => __( 'No goals yet', 'talenttrack' ),
                'explainer' => __( 'Goals capture what each player is working on this season. Set the first one to give training a direction.', 'talenttrack' ),
                'cta_label' => __( 'Add first goal', 'talenttrack' ),
                'cta_url'   => \TT\Shared\Wizards\WizardEntryPoint::urlFor(
                    'new-goal',
                    add_query_arg( [ 'tt_view' => 'goals', 'action' => 'new' ], remove_query_arg( [ 'action', 'id' ] ) )
                ),
                'cta_cap'   => 'tt_edit_goals',
            ],
            // v3.110.170 — row-link standard.
            'row_url_key'  => 'detail_url',
        ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — render() returns escaped HTML.
    }

    /**
     * Create / edit form. PUT vs POST decided by `$goal`.
     */
    private static function renderForm( int $user_id, bool $is_admin, ?object $goal ): void {
        $statuses   = QueryHelpers::get_lookup_names( 'goal_status' );
        $priorities = QueryHelpers::get_lookup_names( 'goal_priority' );

        $is_edit   = $goal !== null;
        $rest_path = $is_edit ? 'goals/' . (int) $goal->id : 'goals';
        $rest_meth = $is_edit ? 'PUT' : 'POST';
        $form_id   = 'tt-goal-form';

        // v3.110.3 — when the form was launched from a player profile
        // ("Add first goal" CTA on the empty Goals tab), `?player_id=N`
        // is in the URL. Pre-fill the picker AND hide it: the player
        // is already chosen, the picker would be a redundant step.
        $preset_player_id = 0;
        if ( ! $is_edit && isset( $_GET['player_id'] ) ) {
            $preset_player_id = absint( $_GET['player_id'] );
        }
        $selected_player = $is_edit ? (int) ( $goal->player_id ?? 0 ) : $preset_player_id;
        $hide_player_picker = ! $is_edit && $preset_player_id > 0;

        // #3008 (epic #2881) — a goal being edited autosaves; a goal being
        // written for the first time does not. Autosave writes to a record,
        // and in create mode there is no record yet: pointing it at `POST
        // /goals` would leave an empty goal on a player's file behind every
        // coach who opened the form and changed their mind.
        $autosaves = $is_edit;
        if ( $autosaves ) {
            \TT\Shared\Frontend\Components\FormAutosave::enqueue();
        }
        ?>
        <form id="<?php echo esc_attr( $form_id ); ?>"
              class="<?php echo $autosaves ? 'tt-autosave-form' : 'tt-ajax-form'; ?>"
              <?php if ( $autosaves ) {
                  echo \TT\Shared\Frontend\Components\FormAutosave::formAttrs( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — the component escapes each attribute
                      $rest_path,
                      $rest_meth,
                      'goal:' . $rest_path
                  );
              } else {
                  printf(
                      'data-rest-path="%s" data-rest-method="%s" data-redirect-after-save="list"',
                      esc_attr( $rest_path ),
                      esc_attr( $rest_meth )
                  );
              } ?>>
            <div class="tt-grid tt-grid-2">
                <?php if ( $hide_player_picker ) : ?>
                    <input type="hidden" name="player_id" value="<?php echo esc_attr( (string) $preset_player_id ); ?>" />
                <?php else : ?>
                    <?php
                    // v3.110.x — searchable player picker with embedded
                    // team filter. Same shape as v3.110.11's
                    // new-evaluation form: an "All teams" dropdown sits
                    // above the search input; selecting a team filters
                    // the player list, "All teams" (the default) shows
                    // every player in the user's context. Replaces the
                    // long flat select that PlayerPickerComponent rendered.
                    echo PlayerSearchPickerComponent::render( [
                        'name'             => 'player_id',
                        'label'            => __( 'Player', 'talenttrack' ),
                        'required'         => true,
                        'user_id'          => $user_id,
                        'is_admin'         => $is_admin,
                        'selected'         => $selected_player,
                        'show_team_filter' => true,
                    ] );
                    ?>
                <?php endif; ?>
                <?php echo DateInputComponent::render( [
                    'name'     => 'due_date',
                    'label'    => __( 'Due date', 'talenttrack' ),
                    'value'    => (string) ( $goal->due_date ?? '' ),
                    // No default so the field stays empty for "no deadline" goals.
                ] ); ?>
                <div class="tt-field">
                    <label class="tt-field-label tt-field-required" for="tt-goal-title"><?php esc_html_e( 'Title', 'talenttrack' ); ?></label>
                    <input type="text" id="tt-goal-title" class="tt-input" name="title" required value="<?php echo esc_attr( (string) ( $goal->title ?? '' ) ); ?>" />
                </div>
                <div class="tt-field">
                    <label class="tt-field-label" for="tt-goal-priority"><?php esc_html_e( 'Priority', 'talenttrack' ); ?></label>
                    <select id="tt-goal-priority" class="tt-input" name="priority">
                        <?php foreach ( $priorities as $pr ) :
                            $value = strtolower( (string) $pr );
                            ?>
                            <option value="<?php echo esc_attr( $value ); ?>" <?php selected( strtolower( (string) ( $goal->priority ?? 'medium' ) ), $value ); ?>>
                                <?php echo esc_html( LabelTranslator::goalPriority( $value ) ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <?php if ( $is_edit ) : // status only editable on the edit form (create defaults to 'pending' via REST) ?>
                <div class="tt-field">
                    <label class="tt-field-label" for="tt-goal-status"><?php esc_html_e( 'Status', 'talenttrack' ); ?></label>
                    <select id="tt-goal-status" class="tt-input" name="status">
                        <?php foreach ( $statuses as $st ) :
                            $value = strtolower( str_replace( ' ', '_', (string) $st ) );
                            ?>
                            <option value="<?php echo esc_attr( $value ); ?>" <?php selected( (string) ( $goal->status ?? '' ), $value ); ?>>
                                <?php echo esc_html( LabelTranslator::goalStatus( $value ) ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="tt-field">
                <label class="tt-field-label" for="tt-goal-description"><?php esc_html_e( 'Description', 'talenttrack' ); ?></label>
                <textarea id="tt-goal-description" class="tt-input" name="description" rows="3"><?php echo esc_textarea( (string) ( $goal->description ?? '' ) ); ?></textarea>
            </div>

            <?php // #1717 — per-goal progress %. Drives the POP card bar. ?>
            <div class="tt-field">
                <label class="tt-field-label" for="tt-goal-progress"><?php esc_html_e( 'Progress (%)', 'talenttrack' ); ?></label>
                <input id="tt-goal-progress" class="tt-input" type="number" name="progress_pct" min="0" max="100" inputmode="numeric" value="<?php echo esc_attr( ( $goal && ( $goal->progress_pct ?? null ) !== null ) ? (string) (int) $goal->progress_pct : '' ); ?>" placeholder="<?php esc_attr_e( 'e.g. 60', 'talenttrack' ); ?>" />
            </div>

            <?php
            // #1717 — evidence: link the player's evaluations to this goal.
            // They render as scored "Beoordeling <date> · <score>" chips on
            // the POP card. Edit-only (the player must already be chosen).
            if ( $is_edit && (int) ( $goal->player_id ?? 0 ) > 0 ) :
                $player_evals = ( new \TT\Infrastructure\Evaluations\EvaluationsRepository() )->listRecentForPlayer( (int) $goal->player_id, 50 );
                $linked_ev    = ( new \TT\Modules\Pdp\Repositories\GoalEvidenceRepository() )->evalIdsForGoal( (int) $goal->id );
                if ( $player_evals ) : ?>
                    <div class="tt-field">
                        <label class="tt-field-label"><?php esc_html_e( 'Evidence (evaluations)', 'talenttrack' ); ?></label>
                        <p class="tt-field-hint"><?php esc_html_e( 'Tick the evaluations that evidence this goal — they show as scored chips on the POP card.', 'talenttrack' ); ?></p>
                        <div class="tt-goal-evidence-list">
                            <?php foreach ( $player_evals as $ev ) :
                                $score = ( $ev->avg_rating !== null ) ? number_format_i18n( (float) $ev->avg_rating, 1 ) : '—';
                                $label = date_i18n( 'j M Y', (int) strtotime( (string) $ev->eval_date ) ) . ' · ' . $score;
                                ?>
                                <label class="tt-goal-evidence-opt">
                                    <input type="checkbox" name="evidence[]" value="<?php echo esc_attr( (string) (int) $ev->id ); ?>" <?php checked( in_array( (int) $ev->id, $linked_ev, true ) ); ?> />
                                    <span><?php echo esc_html( $label ); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif;
            endif; ?>

            <?php
            // #2566 — the principle picker. Was a single "— None — " select
            // writing `tt_goals.linked_principle_id`, which was set on zero
            // of 109 goals on a real install. It is now a prominent
            // multi-select over the active methodology, writing the
            // canonical `tt_goal_links` shape the Training generator reads.
            echo GoalPrinciplePicker::render( [ 'goal_id' => $is_edit ? (int) $goal->id : 0 ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — component escapes its own output.
            ?>

            <?php
            if ( class_exists( '\\TT\\Modules\\Methodology\\Repositories\\FootballActionsRepository' ) ) :
                $actions       = ( new \TT\Modules\Methodology\Repositories\FootballActionsRepository() )->listAll();
                $linked_act_id = (int) ( $goal->linked_action_id ?? 0 );
                $action_cats   = \TT\Modules\Methodology\Repositories\FootballActionsRepository::categories();
                if ( ! empty( $actions ) ) : ?>
                <div class="tt-field">
                    <label class="tt-field-label" for="tt-goal-action"><?php esc_html_e( 'Linked football action', 'talenttrack' ); ?></label>
                    <select id="tt-goal-action" class="tt-input" name="linked_action_id">
                        <option value=""><?php esc_html_e( '— None —', 'talenttrack' ); ?></option>
                        <?php
                        $by_cat = [];
                        foreach ( $actions as $a ) $by_cat[ (string) $a->category_key ][] = $a;
                        foreach ( $action_cats as $cat_key => $cat_label ) :
                            if ( empty( $by_cat[ $cat_key ] ) ) continue; ?>
                            <optgroup label="<?php echo esc_attr( $cat_label ); ?>">
                                <?php foreach ( $by_cat[ $cat_key ] as $a ) :
                                    $name = \TT\Modules\Methodology\Helpers\MultilingualField::string( $a->name_json );
                                    ?>
                                    <option value="<?php echo (int) $a->id; ?>" <?php selected( $linked_act_id, (int) $a->id ); ?>>
                                        <?php echo esc_html( $name ?: $a->slug ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                    <p class="tt-field-hint"><?php esc_html_e( 'Optional. Anchor this goal to a single football action.', 'talenttrack' ); ?></p>
                </div>
            <?php endif; endif; ?>

            <?php
            // v3.110.58 — CLAUDE.md § 6.
            $dash_url   = \TT\Shared\Frontend\Components\RecordLink::dashboardUrl();
            $list_url   = add_query_arg( [ 'tt_view' => 'goals' ], $dash_url );
            $detail_url = $is_edit ? add_query_arg( [ 'tt_view' => 'goals', 'id' => (int) $goal->id ], $dash_url ) : $list_url;
            $back       = \TT\Shared\Frontend\Components\BackLink::resolve();
            $cancel_url = $back !== null ? $back['url'] : ( $is_edit ? $detail_url : $list_url );

            if ( $autosaves ) {
                // #3008 — the save state replaces Save + Cancel. §6's
                // Cancel governs a form where Save is the commit; there is
                // nothing uncommitted here to walk away from, and undo and
                // revert reach further than abandoning did.
                echo '<div class="tt-form-actions">';
                \TT\Shared\Frontend\Components\SaveState::render();
                echo '</div>';
            } else {
                echo FormSaveButton::render( [
                    'label'      => __( 'Add goal', 'talenttrack' ),
                    'cancel_url' => $cancel_url,
                ] );
            }
            ?>
            <div class="tt-form-msg"></div>
        </form>
        <?php
        // #0028 — chat-style conversation thread for the goal. Only on
        // edit (existing goal) and only when the viewer can read the thread.
        if ( $is_edit && class_exists( '\\TT\\Shared\\Frontend\\Components\\FrontendThreadView' ) ) {
            echo '<section class="tt-goal-conversation" style="margin-top:1.5rem;">';
            echo '<header style="display:flex; align-items:baseline; gap:8px; margin: 0 0 0.5rem;">';
            echo '<h2 style="font-size:1.0625rem; margin:0;">' . esc_html__( 'Conversation', 'talenttrack' ) . '</h2>';
            // #0063 — help button explaining what the goal-conversation
            // is for. Points at the existing conversational-goals doc
            // shipped with #0028. Opens in a new tab so the coach
            // doesn't lose the form they're filling in.
            $help_url = add_query_arg(
                [ 'tt_view' => 'docs', 'topic' => 'conversational-goals' ],
                \TT\Shared\Frontend\Components\RecordLink::dashboardUrl()
            );
            echo '<a class="tt-link" href="' . esc_url( $help_url ) . '" target="_blank" rel="noopener" style="font-size:12px; color:#5b6e75;">'
               . esc_html__( 'How does this work?', 'talenttrack' ) . '</a>';
            echo '</header>';
            \TT\Shared\Frontend\Components\FrontendThreadView::render( 'goal', (int) $goal->id, $user_id );
            echo '</section>';
        }
    }

    /**
     * #1687 — enqueue the 2026 goals restyle stylesheet. Idempotent; the
     * style depends on the global app-chrome sheet so the brand tokens +
     * card chrome are already present. Business logic stays out of this
     * helper — it only registers an asset.
     */
    private static function enqueueGoalsStyle(): void {
        wp_enqueue_style(
            'tt-goals',
            TT_PLUGIN_URL . 'assets/css/frontend-goals.css',
            [ 'tt-frontend-app-chrome' ],
            TT_VERSION
        );
    }

    /**
     * #1687 — two-letter initials for an owner avatar. Pure formatting.
     */
    private static function initials( string $name ): string {
        $name = trim( $name );
        if ( $name === '' ) {
            return '?';
        }
        $parts = preg_split( '/\s+/', $name ) ?: [];
        $first = mb_substr( (string) ( $parts[0] ?? '' ), 0, 1 );
        $last  = count( $parts ) > 1 ? mb_substr( (string) end( $parts ), 0, 1 ) : '';
        $out   = mb_strtoupper( $first . $last );
        return $out !== '' ? $out : '?';
    }

    /**
     * #1687 — map a raw priority value to its chip modifier class.
     */
    private static function priorityChipClass( string $priority ): string {
        $p = strtolower( trim( $priority ) );
        if ( $p === 'high' || $p === 'hoog' ) {
            return 'tt-goal-chip--priority tt-goal-chip--priority-high';
        }
        if ( $p === 'low' || $p === 'laag' ) {
            return 'tt-goal-chip--priority tt-goal-chip--priority-low';
        }
        return 'tt-goal-chip--priority';
    }

    /**
     * #1687 — map a raw status value to its chip modifier + card accent.
     * Returns [ chip_class, card_accent_class ].
     *
     * @return array{0:string,1:string}
     */
    private static function statusChipClasses( string $status ): array {
        $s = strtolower( str_replace( ' ', '_', trim( $status ) ) );
        if ( $s === 'completed' || $s === 'achieved' || $s === 'signed_off' ) {
            return [ 'tt-goal-chip--status tt-goal-chip--status-done', 'tt-goal-card--done' ];
        }
        if ( $s === 'cancelled' || $s === 'missed' || $s === 'canceled' ) {
            return [ 'tt-goal-chip--status tt-goal-chip--status-missed', 'tt-goal-card--missed' ];
        }
        return [ 'tt-goal-chip--status', 'tt-goal-card--active' ];
    }

    private static function loadGoal( int $id ): ?object {
        // v4.20.69 (#1221, audit-7 / #1181) — no `g.club_id = %d` clause
        // here. The audit flagged this helper alongside loadPlayer /
        // loadTeam / loadSession as missing the tenancy filter, but
        // v4.20.30 (#1188) settled the direction: tenancy boundary is
        // enforced at the request layer (CurrentClub resolution), not by
        // sprinkling per-helper club_id WHEREs. Sibling helpers in the
        // QueryHelpers canonical layer (`get_player`) and the repository
        // layer (`PlayersRepository::find`, `ActivitiesRepository::findById`)
        // are already aligned; this view stays consistent with them.
        global $wpdb; $p = $wpdb->prefix;
        $scope = QueryHelpers::apply_demo_scope( 'g', 'goal' );
        /** @var object|null $row */
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT g.* FROM {$p}tt_goals g WHERE g.id = %d AND g.archived_at IS NULL {$scope}",
            $id
        ) );
        return $row ?: null;
    }
}
