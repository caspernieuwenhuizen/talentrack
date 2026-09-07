<?php
namespace TT\Shared\Frontend;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Infrastructure\Query\QueryHelpers;
use TT\Infrastructure\Security\AuthorizationService;
use TT\Modules\Trials\Repositories\TrialCaseStaffRepository;
use TT\Modules\Trials\Repositories\TrialCasesRepository;
use TT\Modules\Trials\Repositories\TrialTracksRepository;
use TT\Modules\Trials\Services\TrialCaseOpener;
use TT\Shared\Frontend\Components\PlayerSearchPickerComponent;
use TT\Shared\Frontend\Components\StaffPickerComponent;

/**
 * FrontendTrialsManageView — list + create surface for trial cases.
 *
 *   ?tt_view=trials               — list of cases with filters
 *   ?tt_view=trials&action=new    — create-case form (writes via REST,
 *                                   then redirects to ?tt_view=trial-case&id=N)
 *
 * The detail / edit surface lives in `FrontendTrialCaseView` because it
 * carries six tabs and needs its own dispatch.
 */
class FrontendTrialsManageView extends FrontendViewBase {

    public static function render( int $user_id, bool $is_admin ): void {
        $trials_label = __( 'Trials', 'talenttrack' );

        // #2005 — entry is gated on READ, matching the dashboard tile
        // (which lets a user in when the matrix grants `trial_cases:read`
        // at any scope). The previous gate demanded `tt_manage_trials`
        // (→ `trial_cases:create_delete`), so a head coach — who holds
        // `trial_cases [read, change]` at team scope but NOT
        // `create_delete` — passed the tile yet hit a 403 on the view.
        // Create / delete actions below stay gated on `tt_manage_trials`.
        if ( ! self::canRead( $user_id, $is_admin ) ) {
            \TT\Shared\Frontend\Components\FrontendBreadcrumbs::fromDashboard( __( 'Not authorized', 'talenttrack' ) );
            self::renderHeader( $trials_label );
            echo '<p class="tt-notice">' . esc_html__( 'You do not have permission to view trial cases.', 'talenttrack' ) . '</p>';
            return;
        }

        // v3.85.5 — hard cut: Trials is a Pro-tier feature per
        // FeatureMap. Free + Standard installs that previously had
        // access (because the gate was missing) now see an upgrade
        // nudge in place of the trials surface. Existing data stays
        // in the database — no loss — just inaccessible until upgrade.
        if ( class_exists( '\\TT\\Modules\\License\\LicenseGate' )
             && ! \TT\Modules\License\LicenseGate::allows( 'trial_module' )
        ) {
            \TT\Shared\Frontend\Components\FrontendBreadcrumbs::fromDashboard( $trials_label );
            self::renderHeader( $trials_label );
            echo \TT\Modules\License\Admin\UpgradeNudge::inline( __( 'Trial cases', 'talenttrack' ), 'pro' );
            return;
        }

        $can_manage = self::canManage( $user_id, $is_admin );

        self::enqueueAssets();
        self::handlePost( $user_id, $can_manage );

        $action = isset( $_GET['action'] ) ? sanitize_key( (string) $_GET['action'] ) : '';
        if ( $action === 'new' ) {
            // #2005 — creating a case requires `tt_manage_trials`
            // (`trial_cases:create_delete`). A read-only head coach who
            // reaches `&action=new` via a stale link gets a denial, not
            // the create form.
            if ( ! $can_manage ) {
                \TT\Shared\Frontend\Components\FrontendBreadcrumbs::fromDashboard(
                    __( 'Not authorized', 'talenttrack' ),
                    [ \TT\Shared\Frontend\Components\FrontendBreadcrumbs::viewCrumb( 'trials', $trials_label ) ]
                );
                self::renderHeader( __( 'Add case', 'talenttrack' ) );
                echo '<p class="tt-notice">' . esc_html__( 'You do not have permission to create trial cases.', 'talenttrack' ) . '</p>';
                return;
            }
            \TT\Shared\Frontend\Components\FrontendBreadcrumbs::fromDashboard(
                __( 'Add case', 'talenttrack' ),
                [ \TT\Shared\Frontend\Components\FrontendBreadcrumbs::viewCrumb( 'trials', $trials_label ) ]
            );
            self::renderHeader( __( 'Add case', 'talenttrack' ) );
            self::renderCreateForm();
            return;
        }

        \TT\Shared\Frontend\Components\FrontendBreadcrumbs::fromDashboard( $trials_label );
        self::renderHeader( __( 'Trial cases', 'talenttrack' ) );
        self::renderList( $user_id, $is_admin, $can_manage );
    }

    /**
     * Entry gate — true when the user may READ trial cases. Mirrors the
     * dashboard tile (`trial_cases:read` at any scope) so the tile and
     * the view agree on who gets in. Managers and admins read implicitly.
     */
    private static function canRead( int $user_id, bool $is_admin ): bool {
        if ( $is_admin || self::canManage( $user_id, $is_admin ) ) {
            return true;
        }
        if ( class_exists( '\\TT\\Modules\\Authorization\\MatrixGate' ) ) {
            return \TT\Modules\Authorization\MatrixGate::canAnyScope( $user_id, 'trial_cases', 'read' );
        }
        return false;
    }

    /**
     * Write gate — create / delete of trial cases. Maps to
     * `trial_cases:create_delete` via `tt_manage_trials`. Resolved
     * through the matrix bridge so a matrix-granted manager is not
     * denied when the `user_has_cap` filter is dormant.
     */
    private static function canManage( int $user_id, bool $is_admin ): bool {
        return $is_admin || AuthorizationService::userCanOrMatrix( $user_id, 'tt_manage_trials' );
    }

    private static function handlePost( int $user_id, bool $can_manage ): void {
        if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) return;
        // #2005 — only managers may create cases; a read-only head coach
        // who forges a POST is rejected here even if they reached the
        // surface.
        if ( ! $can_manage ) return;
        if ( ! isset( $_POST['tt_trials_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( (string) $_POST['tt_trials_nonce'] ) ), 'tt_trials_create' ) ) return;

        $player_id = isset( $_POST['player_id'] ) ? absint( $_POST['player_id'] ) : 0;
        $track_id  = isset( $_POST['track_id'] )  ? absint( $_POST['track_id'] )  : 0;
        $start     = isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['start_date'] ) ) : '';
        $end       = isset( $_POST['end_date'] )   ? sanitize_text_field( wp_unslash( (string) $_POST['end_date'] ) )   : '';
        $notes     = isset( $_POST['notes'] )      ? sanitize_textarea_field( wp_unslash( (string) $_POST['notes'] ) )  : '';

        // Inline-create path: when no existing player is picked but the
        // inline first/last/DOB fields are filled, create the trial-status
        // player first and use that id for the case.
        if ( $player_id <= 0 ) {
            $new_first = isset( $_POST['new_player_first_name'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['new_player_first_name'] ) ) : '';
            $new_last  = isset( $_POST['new_player_last_name'] )  ? sanitize_text_field( wp_unslash( (string) $_POST['new_player_last_name'] ) )  : '';
            $new_dob   = isset( $_POST['new_player_dob'] )        ? sanitize_text_field( wp_unslash( (string) $_POST['new_player_dob'] ) )        : '';
            if ( $new_first !== '' && $new_last !== '' && $new_dob !== '' ) {
                $created   = ( new TrialCaseOpener() )->createTrialPlayer( $new_first, $new_last, $new_dob );
                $player_id = $created['id'];
                if ( $player_id <= 0 ) {
                    echo '<div class="tt-notice tt-notice-error">' . esc_html( $created['error'] ) . '</div>';
                    return;
                }
            }
        }

        // Initial staff assignments (parallel arrays).
        $staff_ids   = isset( $_POST['staff_user_id'] )    ? (array) $_POST['staff_user_id']    : [];
        $staff_roles = isset( $_POST['staff_role_label'] ) ? (array) $_POST['staff_role_label'] : [];
        $staff       = [];
        foreach ( $staff_ids as $i => $u ) {
            $u = absint( $u );
            if ( $u <= 0 ) continue;
            $staff[] = [
                'user_id'    => $u,
                'role_label' => isset( $staff_roles[ $i ] )
                    ? sanitize_text_field( wp_unslash( (string) $staff_roles[ $i ] ) )
                    : '',
            ];
        }

        // #3221 — the case row, the player's status, the staff rows and
        // the `tt_trial_started` event now happen in one place, shared
        // with the wizard. This module has already been bitten twice by a
        // second write path (#3115, #3130); a third was not worth it.
        $case_id = ( new TrialCaseOpener() )->open( [
            'player_id'  => $player_id,
            'track_id'   => $track_id,
            'start_date' => $start,
            'end_date'   => $end,
            'notes'      => $notes,
            'created_by' => $user_id,
            'staff'      => $staff,
        ] );

        if ( $case_id instanceof \WP_Error ) {
            echo '<div class="tt-notice tt-notice-error">' . esc_html( $case_id->get_error_message() ) . '</div>';
            return;
        }

        $detail_url = \TT\Shared\Frontend\Components\RecordLink::detailUrlFor( 'trial-case', (int) $case_id );
        wp_safe_redirect( $detail_url ?: \TT\Shared\Frontend\Components\RecordLink::dashboardUrl() );
        exit;
    }


    private static function renderList( int $user_id, bool $is_admin, bool $can_manage ): void {
        $cases_repo = new TrialCasesRepository();
        $tracks_repo = new TrialTracksRepository();

        $filters = [
            'status'   => isset( $_GET['status'] )   ? sanitize_key( (string) $_GET['status'] )   : '',
            'track_id' => isset( $_GET['track_id'] ) ? absint( $_GET['track_id'] )                : 0,
            'decision' => isset( $_GET['decision'] ) ? sanitize_key( (string) $_GET['decision'] ) : '',
            'include_archived' => ! empty( $_GET['include_archived'] ),
        ];

        // #2005 — read-only coaches see only cases for players on their
        // own teams. The matrix grants head_coach `trial_cases [rc]` at
        // TEAM scope, so the list must honour that boundary (matching the
        // team-scoping in FrontendEvaluationsView).
        // Managers (`tt_manage_trials` → academy-wide create_delete) and
        // admins keep the unscoped, full view they had before. An empty
        // allow-list yields zero rows — a coach with no team sees nothing.
        if ( ! $is_admin && ! $can_manage ) {
            $team_ids = array_map(
                static fn( $t ) => (int) $t->id,
                QueryHelpers::get_teams_for_coach( $user_id )
            );
            $player_ids = array_map(
                static fn( $p ) => (int) $p->id,
                QueryHelpers::get_players_for_teams( $team_ids )
            );
            $filters['player_ids'] = $player_ids;
        }

        $rows   = $cases_repo->search( $filters );
        $tracks = $tracks_repo->listAll( true );

        $base_url = remove_query_arg( [ 'action', 'id', 'status', 'track_id', 'decision', 'include_archived' ] );
        $new_url  = add_query_arg( [ 'tt_view' => 'trials', 'action' => 'new' ], $base_url );
        // #3221 — §3 entry-point gating: the CTA reaches the wizard when
        // wizards are on, and this same flat-form URL when they are not.
        // `WizardEntryPoint::urlFor()` returns the fallback untouched if the
        // wizard is unavailable, so an install with wizards off is unchanged.
        $new_url  = \TT\Shared\Wizards\WizardEntryPoint::urlFor( 'trial-case', $new_url );

        // #2005 — the "New trial case" CTA is create-gated. A read-only
        // head coach sees the list but not the create button.
        if ( $can_manage ) {
            echo '<div class="tt-toolbar tt-trials-toolbar"><a class="tt-btn tt-btn-primary" href="' . esc_url( $new_url ) . '">' . esc_html__( 'Add case', 'talenttrack' ) . '</a></div>';
        }

        // #2174 — filters render via the shared FilterBar component:
        // three selects (Status / Track / Decision) + an include-archived
        // toggle. The bar renders an inline single-line row at >=1024px
        // and a "Filters" button + bottom sheet below that; behaviour is
        // identical to the old hand-rolled form (same GET params, same
        // results). FilterBar self-enqueues its stylesheet.
        $status_options = [];
        foreach ( [ 'open', 'extended', 'decided', 'archived' ] as $s ) {
            $status_options[ $s ] = TrialCasesRepository::statusLabel( $s );
        }
        $track_options = [];
        foreach ( $tracks as $t ) {
            $track_options[ (string) (int) $t->id ] = \TT\Infrastructure\Query\LabelTranslator::trialTrackName( (string) $t->name );
        }
        $decision_options = [];
        foreach ( [ 'admit', 'deny_final', 'deny_encouragement' ] as $d ) {
            $decision_options[ $d ] = TrialCasesRepository::decisionLabel( $d );
        }

        // Hidden fields the GET form must carry to preserve routing +
        // back-target across a filter submit.
        $hidden = [ 'tt_view' => 'trials' ];
        if ( ! empty( $_GET['tt_back'] ) ) $hidden['tt_back'] = sanitize_text_field( wp_unslash( (string) $_GET['tt_back'] ) );

        // Active-count + summary chips for the mobile collapsed state.
        $active_count = 0;
        $chips = [];
        if ( $filters['status'] !== '' && isset( $status_options[ $filters['status'] ] ) ) {
            $active_count++;
            $chips[] = $status_options[ $filters['status'] ];
        }
        if ( $filters['track_id'] > 0 && isset( $track_options[ (string) $filters['track_id'] ] ) ) {
            $active_count++;
            $chips[] = $track_options[ (string) $filters['track_id'] ];
        }
        if ( $filters['decision'] !== '' && isset( $decision_options[ $filters['decision'] ] ) ) {
            $active_count++;
            $chips[] = $decision_options[ $filters['decision'] ];
        }
        if ( $filters['include_archived'] ) {
            $active_count++;
            $chips[] = __( 'Archived included', 'talenttrack' );
        }

        // "Clear" target: the bare list with no filter params.
        $reset_args = [ 'tt_view' => 'trials' ];
        if ( ! empty( $hidden['tt_back'] ) ) $reset_args['tt_back'] = $hidden['tt_back'];

        \TT\Shared\Frontend\Components\FilterBar::render( [
            'hidden'       => $hidden,
            'active_count' => $active_count,
            'chips'        => $chips,
            'reset_url'    => add_query_arg( $reset_args, remove_query_arg( [ 'action', 'id', 'status', 'track_id', 'decision', 'include_archived' ] ) ),
            'groups'       => [
                [
                    'type'        => 'select',
                    'key'         => 'status',
                    'label'       => __( 'Status', 'talenttrack' ),
                    'name'        => 'status',
                    'selected'    => $filters['status'],
                    'placeholder' => __( 'All', 'talenttrack' ),
                    'options'     => $status_options,
                ],
                [
                    'type'        => 'select',
                    'key'         => 'track',
                    'label'       => __( 'Track', 'talenttrack' ),
                    'name'        => 'track_id',
                    'selected'    => $filters['track_id'] > 0 ? (string) $filters['track_id'] : '',
                    'placeholder' => __( 'All', 'talenttrack' ),
                    'options'     => $track_options,
                ],
                [
                    'type'        => 'select',
                    'key'         => 'decision',
                    'label'       => __( 'Decision', 'talenttrack' ),
                    'name'        => 'decision',
                    'selected'    => $filters['decision'],
                    'placeholder' => __( 'Any', 'talenttrack' ),
                    'options'     => $decision_options,
                ],
                // #3334 — archive state through the `⋯` menu, as on the nine
                // list surfaces and the activities view. It was the only
                // surface asking with a toggle, which cost a 48px row on
                // every visit for a filter set once in a blue moon.
                //
                // `default_value` names the state the list opens in, so the
                // default does not chip on arrival — the #3320 lesson. The
                // `include_archived` param is unchanged, so existing links,
                // bookmarks and saved views keep working.
                [
                    'type'          => 'menu',
                    'key'           => 'include-archived',
                    'label'         => __( 'Archive', 'talenttrack' ),
                    'param'         => 'include_archived',
                    'default_value' => '0',
                    'options'       => [
                        [
                            'value'  => '0',
                            'label'  => __( 'Active', 'talenttrack' ),
                            'url'    => remove_query_arg( 'include_archived' ),
                            'active' => ! $filters['include_archived'],
                        ],
                        [
                            'value'  => '1',
                            'label'  => __( 'Include archived', 'talenttrack' ),
                            'url'    => add_query_arg( 'include_archived', '1' ),
                            'active' => (bool) $filters['include_archived'],
                        ],
                    ],
                ],
            ],
        ] );

        if ( ! $rows ) {
            echo '<p class="tt-notice">' . esc_html__( 'No trial cases match those filters yet.', 'talenttrack' ) . '</p>';
            return;
        }

        $tracks_by_id = [];
        foreach ( $tracks as $t ) { $tracks_by_id[ (int) $t->id ] = $t; }
        $staff_repo = new TrialCaseStaffRepository();

        // Pre-compose the per-row display values once; both the mobile
        // card list and the desktop table render from the same array so
        // the data shown is identical (CSS shows exactly one at a time).
        $view_rows = [];
        foreach ( $rows as $r ) {
            $player = QueryHelpers::get_player( (int) $r->player_id );
            $name   = $player ? QueryHelpers::player_display_name( $player ) : '#' . (int) $r->player_id;
            $track  = $tracks_by_id[ (int) $r->track_id ] ?? null;
            $detail = \TT\Shared\Frontend\Components\BackLink::appendTo(
                add_query_arg( [ 'tt_view' => 'trial-case', 'id' => (int) $r->id ], $base_url )
            );
            $view_rows[] = [
                'name'        => $name,
                'detail'      => $detail,
                'track'       => $track ? \TT\Infrastructure\Query\LabelTranslator::trialTrackName( (string) $track->name ) : '—',
                'window'      => (string) $r->start_date . ' → ' . (string) $r->end_date,
                'status'      => (string) $r->status,
                'status_label'=> TrialCasesRepository::statusLabel( (string) $r->status ),
                'decision'    => $r->decision ? TrialCasesRepository::decisionLabel( (string) $r->decision ) : '',
                'staff_count' => count( $staff_repo->listForCase( (int) $r->id ) ),
            ];
        }

        // ---- Mobile: card list ----
        echo '<ul class="tt-trials-cards">';
        foreach ( $view_rows as $vr ) {
            echo '<li class="tt-trials-card">';
            echo '<div class="tt-trials-card__player"><a href="' . esc_url( $vr['detail'] ) . '">' . esc_html( $vr['name'] ) . '</a></div>';
            echo '<a class="tt-btn tt-btn-sm tt-trials-card__open" href="' . esc_url( $vr['detail'] ) . '">' . esc_html__( 'Open', 'talenttrack' ) . '</a>';
            echo '<div class="tt-trials-card__meta">';
            echo '<span><b>' . esc_html__( 'Track', 'talenttrack' ) . ':</b> ' . esc_html( $vr['track'] ) . '</span>';
            echo '<span><b>' . esc_html__( 'Window', 'talenttrack' ) . ':</b> ' . esc_html( $vr['window'] ) . '</span>';
            echo '</div>';
            echo '<div class="tt-trials-card__chips">';
            echo '<span class="tt-trial-chip tt-trial-chip--status-' . esc_attr( $vr['status'] ) . '">' . esc_html( $vr['status_label'] ) . '</span>';
            if ( $vr['decision'] !== '' ) {
                echo '<span class="tt-trial-chip tt-trial-chip--decision">' . esc_html( $vr['decision'] ) . '</span>';
            }
            /* translators: %d: number of assigned staff. */
            echo '<span class="tt-trial-chip tt-trial-chip__count">' . esc_html( sprintf( _n( '%d staff', '%d staff', (int) $vr['staff_count'], 'talenttrack' ), (int) $vr['staff_count'] ) ) . '</span>';
            echo '</div>';
            echo '</li>';
        }
        echo '</ul>';

        // ---- Desktop: table ----
        echo '<div class="tt-table-wrap tt-trials-table-wrap">';
        echo '<table class="tt-table"><thead><tr>';
        echo '<th>' . esc_html__( 'Player', 'talenttrack' ) . '</th>';
        echo '<th>' . esc_html__( 'Track', 'talenttrack' ) . '</th>';
        echo '<th>' . esc_html__( 'Window', 'talenttrack' ) . '</th>';
        echo '<th>' . esc_html__( 'Status', 'talenttrack' ) . '</th>';
        echo '<th>' . esc_html__( 'Decision', 'talenttrack' ) . '</th>';
        echo '<th>' . esc_html__( 'Staff', 'talenttrack' ) . '</th>';
        echo '<th>' . esc_html__( 'Actions', 'talenttrack' ) . '</th>';
        echo '</tr></thead><tbody>';
        foreach ( $view_rows as $vr ) {
            echo '<tr>';
            echo '<td><a href="' . esc_url( $vr['detail'] ) . '">' . esc_html( $vr['name'] ) . '</a></td>';
            echo '<td>' . esc_html( $vr['track'] ) . '</td>';
            echo '<td>' . esc_html( $vr['window'] ) . '</td>';
            echo '<td><span class="tt-trial-chip tt-trial-chip--status-' . esc_attr( $vr['status'] ) . '">' . esc_html( $vr['status_label'] ) . '</span></td>';
            echo '<td>' . ( $vr['decision'] !== '' ? '<span class="tt-trial-chip tt-trial-chip--decision">' . esc_html( $vr['decision'] ) . '</span>' : '—' ) . '</td>';
            echo '<td>' . esc_html( (string) $vr['staff_count'] ) . '</td>';
            echo '<td><a class="tt-btn tt-btn-sm" href="' . esc_url( $vr['detail'] ) . '">' . esc_html__( 'Open', 'talenttrack' ) . '</a></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '</div>';
    }

    private static function renderCreateForm(): void {
        $tracks_repo = new TrialTracksRepository();
        $tracks      = $tracks_repo->listAll( false );

        $today = gmdate( 'Y-m-d' );
        $default_days  = $tracks ? (int) $tracks[0]->default_duration_days : 28;
        $default_end   = gmdate( 'Y-m-d', time() + $default_days * 86400 );

        echo '<form method="post" class="tt-form tt-trial-create-form">';
        wp_nonce_field( 'tt_trials_create', 'tt_trials_nonce' );

        echo '<fieldset class="tt-trial-create-player"><legend>' . esc_html__( 'Player', 'talenttrack' ) . '</legend>';
        echo PlayerSearchPickerComponent::render( [
            'name'     => 'player_id',
            'label'    => __( 'Existing player', 'talenttrack' ),
            'required' => false,
            'user_id'  => get_current_user_id(),
            'is_admin' => current_user_can( 'tt_edit_settings' ),
        ] );
        echo '<details class="tt-trial-inline-create" style="margin-top:8px;"><summary>' . esc_html__( 'Or create a new player here', 'talenttrack' ) . '</summary>';
        echo '<p class="tt-field-hint">' . esc_html__( 'Fill in at least first name, last name and date of birth. The new player record will be created with status "trial" before the case is opened.', 'talenttrack' ) . '</p>';
        echo '<div class="tt-field"><label class="tt-field-label" for="tt-trial-new-first">' . esc_html__( 'First name', 'talenttrack' ) . '</label>';
        echo '<input type="text" id="tt-trial-new-first" name="new_player_first_name" class="tt-input" autocomplete="given-name"></div>';
        echo '<div class="tt-field"><label class="tt-field-label" for="tt-trial-new-last">' . esc_html__( 'Last name', 'talenttrack' ) . '</label>';
        echo '<input type="text" id="tt-trial-new-last" name="new_player_last_name" class="tt-input" autocomplete="family-name"></div>';
        echo '<div class="tt-field"><label class="tt-field-label" for="tt-trial-new-dob">' . esc_html__( 'Date of birth', 'talenttrack' ) . '</label>';
        echo '<input type="date" id="tt-trial-new-dob" name="new_player_dob" class="tt-input"></div>';
        echo '</details>';
        echo '</fieldset>';

        echo '<label>' . esc_html__( 'Track', 'talenttrack' );
        echo ' <select name="track_id" required>';
        foreach ( $tracks as $t ) {
            echo '<option value="' . esc_attr( (string) $t->id ) . '" data-days="' . esc_attr( (string) $t->default_duration_days ) . '">' . esc_html( (string) $t->name ) . '</option>';
        }
        echo '</select></label>';

        echo '<label>' . esc_html__( 'Start date', 'talenttrack' ) . ' <input type="date" name="start_date" value="' . esc_attr( $today ) . '" required></label>';
        echo '<label>' . esc_html__( 'End date', 'talenttrack' ) . ' <input type="date" name="end_date" value="' . esc_attr( $default_end ) . '" required></label>';

        echo '<fieldset class="tt-trial-staff-rows"><legend>' . esc_html__( 'Initial staff (optional)', 'talenttrack' ) . '</legend>';
        for ( $i = 0; $i < 3; $i++ ) {
            echo '<div class="tt-trial-staff-row">';
            echo StaffPickerComponent::render( [
                'name'        => 'staff_user_id[]',
                'label'       => sprintf( __( 'Staff slot %d', 'talenttrack' ), $i + 1 ),
                'required'    => false,
                'placeholder' => __( 'Type a name to search…', 'talenttrack' ),
            ] );
            echo ' <input type="text" name="staff_role_label[]" class="tt-input" placeholder="' . esc_attr__( 'Role label (optional)', 'talenttrack' ) . '">';
            echo '</div>';
        }
        echo '</fieldset>';

        echo '<label>' . esc_html__( 'Notes', 'talenttrack' ) . ' <textarea name="notes" rows="3"></textarea></label>';

        // Save + Cancel (CLAUDE.md §6). Create-mode cancel target is the
        // trials list; an inbound tt_back hint overrides it.
        $back = \TT\Shared\Frontend\Components\BackLink::resolve();
        $cancel_url = $back['url'] ?? add_query_arg(
            [ 'tt_view' => 'trials' ],
            \TT\Shared\Frontend\Components\RecordLink::dashboardUrl()
        );
        echo \TT\Shared\Frontend\Components\FormSaveButton::render( [
            'label'      => __( 'Add case', 'talenttrack' ),
            'cancel_url' => $cancel_url,
        ] );
        echo '</form>';
    }
}
