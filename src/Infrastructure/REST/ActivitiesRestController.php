<?php
namespace TT\Infrastructure\REST;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Domain\Vocabularies\Lookups\ActivityStatusKey;
use TT\Domain\Vocabularies\Lookups\ActivityTypeKey;
use TT\Domain\Vocabularies\Lookups\AttendanceStatus;
use TT\Infrastructure\Logging\Logger;
use TT\Infrastructure\Query\QueryHelpers;
use TT\Infrastructure\Security\AuthorizationService;
use TT\Infrastructure\Tenancy\CurrentClub;
use TT\Modules\Activities\Repositories\ActivitiesRepository;

/**
 * ActivitiesRestController — /wp-json/talenttrack/v1/activities
 *
 * #0019 Sprint 1 — replaces the legacy `tt_fe_save_session` admin-ajax
 * path. Attendance is a nested sub-resource handled inline on create
 * and update because the UI posts the full attendance matrix with the
 * activity form. Fail-loud: every $wpdb write return value is checked
 * and failures land in the Logger.
 *
 * #0037 — three REST routes that #0035 missed (the rename gate didn't
 * cover REST URL segments). Routes were registered as /sessions* and
 * the JS client at guest-add.js POSTs to /activities/{id}/guests, so
 * adding a guest 404'd and the modal got stuck in the "kritieke fout"
 * state. Routes are now /activities*.
 */
class ActivitiesRestController {

    const NS = 'talenttrack/v1';

    /**
     * #3107 — the three bulk-entry grids are Pro. Only the grid routes are
     * wrapped, not the controller: attendance, minutes and ratings are all
     * recordable one activity at a time on every plan, through the other
     * routes here. The grid is the desktop bulk affordance, not the only
     * path to the data, and gating this controller wholesale would take
     * the ungated path with it.
     *
     * `enforceFeatureRest()` rather than the write-verb helper: a grid
     * stores nothing of its own — the rows it writes are ordinary
     * attendance, minutes and ratings, readable everywhere else — so there
     * is no record here to keep open, and the read endpoint that feeds the
     * matrix is only useful to the grid it feeds.
     */
    private static function gateAttendanceGrid( callable $callback ): \Closure {
        return static function ( \WP_REST_Request $r ) use ( $callback ) {
            $blocked = \TT\Modules\License\LicenseGate::enforceFeatureRest( 'attendance_grid' );
            return $blocked ?? $callback( $r );
        };
    }

    private static function gateMinutesGrid( callable $callback ): \Closure {
        return static function ( \WP_REST_Request $r ) use ( $callback ) {
            $blocked = \TT\Modules\License\LicenseGate::enforceFeatureRest( 'minutes_grid' );
            return $blocked ?? $callback( $r );
        };
    }

    private static function gateRatingsGrid( callable $callback ): \Closure {
        return static function ( \WP_REST_Request $r ) use ( $callback ) {
            $blocked = \TT\Modules\License\LicenseGate::enforceFeatureRest( 'ratings_grid' );
            return $blocked ?? $callback( $r );
        };
    }

    public static function init(): void {
        add_action( 'rest_api_init', [ __CLASS__, 'register' ] );
    }

    public static function register(): void {
        register_rest_route( self::NS, '/activities', [
            [
                'methods'             => 'GET',
                'callback'            => [ __CLASS__, 'list_sessions' ],
                'permission_callback' => [ __CLASS__, 'can_view' ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ __CLASS__, 'create_session' ],
                'permission_callback' => [ __CLASS__, 'can_edit' ],
            ],
        ] );
        register_rest_route( self::NS, '/activities/(?P<id>\d+)', [
            [
                'methods'             => 'PUT',
                'callback'            => [ __CLASS__, 'update_session' ],
                'permission_callback' => [ __CLASS__, 'can_edit' ],
            ],
            [
                'methods'             => 'DELETE',
                // #2199 — DELETE soft-archives, so it is delete-class, not
                // edit-class (an assistant coach with edit-only cannot archive).
                'callback'            => [ __CLASS__, 'delete_session' ],
                'permission_callback' => [ __CLASS__, 'can_delete' ],
            ],
        ] );
        // #1555 — archive lifecycle: restore + gated permanent delete,
        // mirroring the goals/players/teams route pairs from #1470.
        register_rest_route( self::NS, '/activities/(?P<id>\d+)/restore', [
            [
                'methods'             => 'POST',
                'callback'            => [ __CLASS__, 'restore_session' ],
                // #2199 — restore reverses an archive, so it is gated
                // consistently with archive (delete-class), not edit.
                'permission_callback' => [ __CLASS__, 'can_delete' ],
            ],
        ] );
        register_rest_route( self::NS, '/activities/(?P<id>\d+)/permanent', [
            [
                'methods'             => 'DELETE',
                'callback'            => [ __CLASS__, 'delete_session_permanently' ],
                // #2024 security #6 — re-gate the permanent delete onto
                // tt_manage_recycle_bin so no purge path is weaker than the
                // bin's own purge.
                'permission_callback' => static function () { return current_user_can( 'tt_manage_recycle_bin' ); },
            ],
        ] );
        // #0026 — guest attendance endpoints. Guests live alongside
        // roster rows in `tt_attendance` but are managed independently
        // of the activity PUT cycle so the historical fact of a guest
        // visit (incl. promoted-to-real-player) survives activity edits.
        register_rest_route( self::NS, '/activities/(?P<id>\d+)/guests', [
            [
                'methods'             => 'POST',
                'callback'            => [ __CLASS__, 'add_guest' ],
                'permission_callback' => [ __CLASS__, 'can_edit' ],
            ],
        ] );
        // v3.110.138 — toggle the per-activity "evaluation skipped"
        // flag. POST body: `{ skipped: 0|1 }`. The mark-attendance
        // wizard's "Skip rating — no rating needed" branch sets this
        // to 1; the activity detail view exposes a "Re-open for
        // rating" button that flips it back to 0.
        // #2245 — direct, confirmed status transitions (Cancel / Reopen)
        // for the detail-view buttons. POST body: `{ status: 'cancelled'
        // | 'planned' }`. Completion runs through the evaluation flow,
        // which flips the status at its final save — so `completed` is
        // rejected here UNLESS that flow is switched off, in which case
        // it's the only way an activity can complete (#2407; see
        // set_status). Cap-gated via can_edit (never __return_true).
        register_rest_route( self::NS, '/activities/(?P<id>\d+)/status', [
            [
                'methods'             => 'POST',
                'callback'            => [ __CLASS__, 'set_status' ],
                'permission_callback' => [ __CLASS__, 'can_edit' ],
            ],
        ] );
        register_rest_route( self::NS, '/activities/(?P<id>\d+)/evaluation-skipped', [
            [
                'methods'             => 'PATCH',
                'callback'            => [ __CLASS__, 'patch_evaluation_skipped' ],
                'permission_callback' => [ __CLASS__, 'can_edit' ],
            ],
        ] );
        // #1453 — read the planned (expected) attendance roster for an
        // activity, so a non-WP front end gets the same "who to expect"
        // data the activity detail page and match-prep step render.
        register_rest_route( self::NS, '/activities/(?P<id>\d+)/planned-attendance', [
            [
                'methods'             => 'GET',
                'callback'            => [ __CLASS__, 'get_planned_attendance' ],
                'permission_callback' => [ __CLASS__, 'can_view' ],
            ],
        ] );
        // #2831 — the methodology principles an activity is linked to, read
        // through the same domain service the activity detail card, the match
        // prep screen and the printed team sheet compose from. Read-only by
        // design: principles are attached on the activity itself (the
        // `activity_principle_ids[]` field the PUT already accepts), so this
        // route reports what a match is working on rather than offering a
        // second place to decide it.
        register_rest_route( self::NS, '/activities/(?P<id>\d+)/principles', [
            [
                'methods'             => 'GET',
                'callback'            => [ __CLASS__, 'get_principles' ],
                'permission_callback' => [ __CLASS__, 'can_view' ],
            ],
        ] );
        register_rest_route( self::NS, '/attendance/(?P<id>\d+)', [
            [
                'methods'             => 'PATCH',
                'callback'            => [ __CLASS__, 'patch_attendance' ],
                'permission_callback' => [ __CLASS__, 'can_edit' ],
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [ __CLASS__, 'delete_attendance' ],
                'permission_callback' => [ __CLASS__, 'can_edit' ],
            ],
        ] );
        // #2382 (epic #2381) — the desktop attendance-entry grid. A read
        // endpoint for the players × activities matrix and a bulk upsert for
        // the whole period's edits. Both gate on `tt_edit_activities` AND the
        // `attendance_grid` feature — the SAME gates the view enforces, so the
        // affordance and the endpoint can't drift (§7). Team scope is enforced
        // per-activity in the handlers.
        register_rest_route( self::NS, '/activities/attendance-grid', [
            [
                'methods'             => 'GET',
                'callback'            => self::gateAttendanceGrid( [ __CLASS__, 'attendance_grid_data' ] ),
                'permission_callback' => [ __CLASS__, 'can_edit_grid' ],
                'args'                => [
                    'team_id' => [ 'sanitize_callback' => 'absint',              'required' => true ],
                    'from'    => [ 'sanitize_callback' => 'sanitize_text_field', 'required' => false ],
                    'to'      => [ 'sanitize_callback' => 'sanitize_text_field', 'required' => false ],
                    'type'    => [ 'sanitize_callback' => 'sanitize_key',        'required' => false ],
                ],
            ],
        ] );
        register_rest_route( self::NS, '/attendance/bulk', [
            [
                'methods'             => 'POST',
                'callback'            => self::gateAttendanceGrid( [ __CLASS__, 'bulk_attendance' ] ),
                'permission_callback' => [ __CLASS__, 'can_edit_grid' ],
            ],
        ] );
        // #2414 (epic #2381) — the ratings grid's bulk write: one activity,
        // many players × categories. Gated on `tt_edit_activities` AND the
        // `ratings_grid` feature; team scope enforced in the handler.
        register_rest_route( self::NS, '/activities/(?P<id>\d+)/ratings/bulk', [
            [
                'methods'             => 'POST',
                'callback'            => self::gateRatingsGrid( [ __CLASS__, 'bulk_ratings' ] ),
                'permission_callback' => [ __CLASS__, 'can_edit_ratings_grid' ],
            ],
        ] );
        // #2386 (epic #2381) — the minutes grid (players × match activities).
        // A read endpoint for the matrix and a bulk write that routes each
        // edit through the minutes-ownership arbiter (#2367): a match owned by
        // a match-execution takes an override, a paper match writes minutes
        // directly. Gated on `tt_edit_activities` AND the `minutes_grid`
        // feature. Team scope enforced per-activity in the handlers.
        register_rest_route( self::NS, '/activities/minutes-grid', [
            [
                'methods'             => 'GET',
                'callback'            => self::gateMinutesGrid( [ __CLASS__, 'minutes_grid_data' ] ),
                'permission_callback' => [ __CLASS__, 'can_edit_minutes_grid' ],
                'args'                => [
                    'team_id' => [ 'sanitize_callback' => 'absint',              'required' => true ],
                    'from'    => [ 'sanitize_callback' => 'sanitize_text_field', 'required' => false ],
                    'to'      => [ 'sanitize_callback' => 'sanitize_text_field', 'required' => false ],
                ],
            ],
        ] );
        register_rest_route( self::NS, '/minutes/bulk', [
            [
                'methods'             => 'POST',
                'callback'            => self::gateMinutesGrid( [ __CLASS__, 'bulk_minutes' ] ),
                'permission_callback' => [ __CLASS__, 'can_edit_minutes_grid' ],
            ],
        ] );
        // #3094 — goals and assists per player for one match, entered as
        // counts. Resource-oriented per CLAUDE.md §4, and separate from
        // `/minutes/bulk` on purpose: minutes go through the ownership
        // arbiter and these do not, so folding them into one endpoint would
        // mean one handler with two unrelated write rules in it.
        register_rest_route( self::NS, '/activities/(?P<activity_id>\d+)/contributions', [
            [
                'methods'             => 'PUT',
                'callback'            => [ __CLASS__, 'put_contributions' ],
                'permission_callback' => [ __CLASS__, 'can_edit_minutes_grid' ],
            ],
        ] );
    }

    public static function can_view( ?\WP_REST_Request $r = null ): bool {
        // v3.71.4 — also consult the matrix mapper so users granted
        // tt_view_activities / tt_edit_activities via Functional Roles
        // (matrix scope-rows) are not blocked by REST when the
        // `user_has_cap` bridge is dormant. Same fallback pattern as
        // TileRegistry::userMayAccess.
        $uid = get_current_user_id();
        if ( $uid <= 0 ) return false;
        if ( AuthorizationService::userCanOrMatrix( $uid, 'tt_view_activities' )
             || AuthorizationService::userCanOrMatrix( $uid, 'tt_edit_activities' ) ) {
            return true;
        }

        // v3.92.7 — players + parents on `?tt_view=my-activities` consume
        // this endpoint via `FrontendListTable` with `filter[player_id]`
        // set to the linked player. They don't hold `tt_view_activities`
        // (it's a coach-side cap), but the list query scopes via the
        // EXISTS subquery on `tt_attendance` so every returned row is
        // already a row the player attended. Allow the request when the
        // `filter[player_id]` matches the current user's linked player
        // (or the parent's child).
        $filter = $r ? (array) ( $r->get_param( 'filter' ) ?? [] ) : ( is_array( $_GET['filter'] ?? null ) ? $_GET['filter'] : [] );
        $filter_pid = isset( $filter['player_id'] ) ? absint( $filter['player_id'] ) : 0;
        if ( $filter_pid <= 0 ) return false;

        // #1712 — identity lookups moved to ActivitiesRepository; the
        // permission decision (is this caller the player or their parent?)
        // stays here.
        $repo = self::repo();
        if ( $repo->linkedPlayerIdForUser( $uid ) === $filter_pid ) return true;
        if ( $repo->userIsParentOfPlayer( $uid, $filter_pid ) ) return true;

        return false;
    }

    public static function can_edit(): bool {
        return AuthorizationService::userCanOrMatrix( get_current_user_id(), 'tt_edit_activities' );
    }

    /**
     * #2199 — archive/restore are delete-class (soft-delete), so they gate on
     * the activities create/delete capability rather than edit. Maps through
     * `tt_delete_activities → activities:create_delete` (LegacyCapMapper), so
     * an assistant coach with edit-only cannot archive, while a head coach
     * (RCD) can. Restore stays consistent with the same gate.
     */
    public static function can_delete(): bool {
        return AuthorizationService::userCanOrMatrix( get_current_user_id(), 'tt_delete_activities' );
    }

    /** #1712 — shared activities repository instance for the request. */
    private static function repo(): ActivitiesRepository {
        return new ActivitiesRepository();
    }

    /**
     * #2382 — the attendance-grid gate: the `tt_edit_activities` write cap
     * (matrix-aware) AND the `attendance_grid` feature toggle. Same gate the
     * view enforces so they never drift (§7).
     */
    public static function can_edit_grid(): bool {
        if ( ! AuthorizationService::userCanOrMatrix( get_current_user_id(), 'tt_edit_activities' ) ) return false;
        if ( class_exists( '\\TT\\Core\\FeatureRegistry' ) && ! \TT\Core\FeatureRegistry::isEnabled( 'attendance_grid' ) ) return false;
        return true;
    }

    /** #2414 — same cap, its own feature toggle. */
    public static function can_edit_ratings_grid(): bool {
        if ( ! AuthorizationService::userCanOrMatrix( get_current_user_id(), 'tt_edit_activities' ) ) return false;
        if ( class_exists( '\\TT\\Core\\FeatureRegistry' ) && ! \TT\Core\FeatureRegistry::isEnabled( 'ratings_grid' ) ) return false;
        return true;
    }

    /**
     * #2414 — POST /activities/{id}/ratings/bulk. Body:
     * `{ changes: [ { player_id, category_id, rating } ] }`.
     *
     * A blank / null rating means "not rated": the category is skipped, so
     * an untouched cell never writes a zero and never clears an existing
     * score. Writes go through `EvaluationInserter::upsertForActivity()` —
     * the same writer the evaluation wizard uses — so the grid and the
     * wizard can't produce different rows for the same input.
     *
     * A value outside the configured scale, or off its step, is refused and
     * reported in `data.rejected` as `{ player_id, category_id, reason }`
     * (#2431). Refusals are NOT folded into `skipped`, which stays reserved
     * for blank cells: a caller that cannot distinguish "nothing to write"
     * from "would not write" ends up telling the user their score saved
     * when it did not. Partial batches still commit — the valid cells are
     * written and the refused ones reported — so one bad cell can't cost a
     * coach a whole squad's worth of typing.
     */
    public static function bulk_ratings( \WP_REST_Request $r ): \WP_REST_Response {
        $activity_id = (int) $r['id'];
        $changes     = $r['changes'] ?? null;
        if ( $activity_id <= 0 || ! is_array( $changes ) ) {
            return RestResponse::error( 'bad_request', __( 'No changes supplied.', 'talenttrack' ), 400 );
        }

        $data     = \TT\Modules\Activities\Reports\RatingsGridQuery::forActivity( $activity_id );
        $activity = $data['activity'];
        if ( ! $activity ) {
            return RestResponse::notFound( 'activity_not_found', __( 'Activity not found.', 'talenttrack' ) );
        }

        // Team scope — a coach may only rate on a team they coach.
        $allowed = self::gridAllowedTeamIds();
        if ( $allowed !== null && ! in_array( (int) $activity->team_id, $allowed, true ) ) {
            return RestResponse::error( 'forbidden', __( 'You do not coach this activity\'s team.', 'talenttrack' ), 403 );
        }

        // Only players on the roster and categories this activity is rated
        // on are writable; anything else is a client bug or a probe.
        $valid_players    = [];
        foreach ( $data['players'] as $pl ) $valid_players[ (int) $pl->id ] = true;
        $valid_categories = [];
        foreach ( $data['categories'] as $c ) $valid_categories[ (int) $c['id'] ] = true;

        $scale = $data['scale'];
        $byPlayer = [];
        $skipped  = 0;
        $rejected = [];

        // "Skipped" and "rejected" are deliberately separate outcomes. A
        // blank cell is a legitimate no-op — not rated, write nothing. A
        // value outside the scale is a REFUSAL, and the caller has to be
        // able to tell the two apart: reporting a refusal as a clean save
        // is how a coach ends up believing a score was recorded when it
        // never was (#2431).
        foreach ( $changes as $c ) {
            $pid = (int) ( $c['player_id'] ?? 0 );
            $cid = (int) ( $c['category_id'] ?? 0 );
            $raw = $c['rating'] ?? null;

            if ( ! isset( $valid_players[ $pid ], $valid_categories[ $cid ] ) ) { $skipped++; continue; }
            if ( $raw === null || $raw === '' ) { $skipped++; continue; }

            // The step is measured from the scale's floor, not from zero —
            // the same base an HTML `step` attribute uses. On the default
            // 5–10 half-point scale the two are identical, but on a scale
            // whose floor isn't a multiple of the step they diverge, and
            // the grid's client-side check has to agree with this one or a
            // cell the browser accepted gets refused here.
            $num = (float) $raw;
            $val = $scale['min'] + round( ( $num - $scale['min'] ) / $scale['step'] ) * $scale['step'];

            if ( $val < $scale['min'] || $val > $scale['max'] ) {
                $rejected[] = [ 'player_id' => $pid, 'category_id' => $cid, 'reason' => 'out_of_range' ];
                continue;
            }

            // Snapping to the step is a float-noise tolerance, not licence
            // to rewrite what the user typed: 7.4999999 becomes 7.5, but a
            // deliberate 7.3 on a half-point scale is refused rather than
            // silently stored as 7.5. Silent mutation of a rating is the
            // same class of bug as silent loss of one.
            if ( abs( $num - $val ) > ( $scale['step'] / 100 ) ) {
                $rejected[] = [ 'player_id' => $pid, 'category_id' => $cid, 'reason' => 'off_step' ];
                continue;
            }

            $byPlayer[ $pid ][ $cid ] = $val;
        }

        $saved  = 0;
        $failed = 0;
        foreach ( $byPlayer as $pid => $ratings ) {
            $res = \TT\Modules\Wizards\Evaluation\EvaluationInserter::upsertForActivity( $pid, $activity_id, $ratings );
            if ( is_wp_error( $res ) ) { $failed++; continue; }
            $saved += count( $ratings );
        }

        return RestResponse::success( [
            'activity_id' => $activity_id,
            'saved'       => $saved,
            'skipped'     => $skipped,
            'failed'      => $failed,
            // The cells that were refused, so a client can leave them
            // flagged instead of promoting them to a saved baseline.
            'rejected'       => $rejected,
            'rejected_count' => count( $rejected ),
            'scale'          => $scale,
        ] );
    }

    /**
     * #2382 — the caller's team scope for the grid. Null = academy-wide
     * (sees every team). Otherwise the list of team ids the coach coaches.
     *
     * @return list<int>|null
     */
    private static function gridAllowedTeamIds(): ?array {
        $uid = get_current_user_id();
        if ( \TT\Modules\Authorization\AllTeamsScope::canSeeAllTeamsActivities( $uid ) ) {
            return null;
        }
        return array_values( array_map( 'intval', array_column( QueryHelpers::get_teams_for_coach( $uid ), 'id' ) ) );
    }

    /**
     * #2382 — GET /activities/attendance-grid — the players × activities
     * attendance matrix for a team + window, for the desktop grid (and any
     * SaaS consumer, §4). Team scope enforced on the requested team.
     */
    public static function attendance_grid_data( \WP_REST_Request $r ): \WP_REST_Response {
        $team_id = absint( $r['team_id'] );
        $allowed = self::gridAllowedTeamIds();
        if ( $allowed !== null && ! in_array( $team_id, $allowed, true ) ) {
            return RestResponse::error( 'forbidden', __( 'That team is not in your scope.', 'talenttrack' ), 403 );
        }

        $defaults = \TT\Modules\Analytics\Reports\ReportFilters::seasonDefaultWindow();
        $from = preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) ( $r['from'] ?? '' ) ) ? (string) $r['from'] : $defaults['from'];
        $to   = preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) ( $r['to'] ?? '' ) )   ? (string) $r['to']   : $defaults['to'];
        $type = (string) ( $r['type'] ?? 'all' );
        if ( ! in_array( $type, [ 'all', 'training', 'match' ], true ) ) $type = 'all';

        $matrix = ( new \TT\Modules\Activities\Reports\AttendanceGridQuery() )->matrix( $team_id, $from, $to, $type );
        return RestResponse::success( $matrix );
    }

    /**
     * #2382 — POST /attendance/bulk — apply a batch of grid edits. Body:
     * `{ changes: [ { activity_id, player_id, status } ] }`. A blank status
     * clears the cell. Each change is validated (known status, activity in
     * scope, player on the activity's roster) and upserted; the response
     * reports how many saved / were skipped / failed.
     */
    public static function bulk_attendance( \WP_REST_Request $r ): \WP_REST_Response {
        $changes = $r['changes'] ?? null;
        if ( ! is_array( $changes ) ) {
            return RestResponse::error( 'bad_request', __( 'No changes supplied.', 'talenttrack' ), 400 );
        }

        $allowed = self::gridAllowedTeamIds();
        $repo    = self::repo();
        $saved   = 0;
        $skipped = 0;
        $failed  = 0;
        /** @var array<int,bool> $touched activity ids that took a write */
        $touched = [];

        foreach ( $changes as $c ) {
            if ( ! is_array( $c ) ) { $skipped++; continue; }
            $aid    = absint( $c['activity_id'] ?? 0 );
            $pid    = absint( $c['player_id'] ?? 0 );
            // #2909 — fold to the canonical member rather than lowercasing.
            // The grid's JS posts lowercase, older clients post Title Case,
            // and both must land in the column as one vocabulary.
            $raw    = trim( (string) ( $c['status'] ?? '' ) );
            $status = $raw === '' ? '' : (string) ( AttendanceStatus::normalise( $raw ) ?? $raw );
            if ( $aid <= 0 || $pid <= 0 ) { $skipped++; continue; }
            if ( $status !== '' && ! AttendanceStatus::isValid( $status ) ) { $skipped++; continue; }

            // The grid only writes team activities; a team-less (club-wide)
            // activity has no roster to scope against, so it is out of scope.
            $team = $repo->activityTeamId( $aid );
            if ( $team <= 0 ) { $skipped++; continue; }
            if ( $allowed !== null && ! in_array( $team, $allowed, true ) ) { $skipped++; continue; }

            // Roster integrity (#1148): non-guest attendance must be on the
            // activity's roster. The grid only offers the team's own players
            // as rows, so this only rejects a tampered payload.
            if ( $repo->playerTeamId( $pid ) !== $team ) { $skipped++; continue; }

            if ( $repo->upsertActualAttendanceStatus( $aid, $pid, $status ) ) {
                $saved++;
                if ( $status !== '' ) $touched[ $aid ] = true;
            } else {
                $failed++;
            }
        }

        // #2521 — the grid can write attendance for an activity the coach
        // never marked completed, and reports count completed activities
        // only. Recording a register is that assertion, so complete the
        // past-dated, non-terminal activities this batch wrote to; without
        // it the coach's entry would silently never reach the reports.
        $completed = 0;
        foreach ( array_keys( $touched ) as $aid ) {
            if ( $repo->completeIfNotTerminal( (int) $aid ) ) $completed++;
        }

        return RestResponse::success( [
            'saved'     => $saved,
            'skipped'   => $skipped,
            'failed'    => $failed,
            'completed' => $completed,
        ] );
    }

    /**
     * #2386 — the minutes-grid gate: `tt_edit_activities` (matrix-aware) AND
     * the `minutes_grid` feature toggle. Same gate the view enforces (§7).
     */
    public static function can_edit_minutes_grid(): bool {
        if ( ! AuthorizationService::userCanOrMatrix( get_current_user_id(), 'tt_edit_activities' ) ) return false;
        if ( class_exists( '\\TT\\Core\\FeatureRegistry' ) && ! \TT\Core\FeatureRegistry::isEnabled( 'minutes_grid' ) ) return false;
        return true;
    }

    /**
     * #2386 — GET /activities/minutes-grid — the players × match-activities
     * minutes matrix for a team + window. Team scope enforced on the team.
     */
    public static function minutes_grid_data( \WP_REST_Request $r ): \WP_REST_Response {
        $team_id = absint( $r['team_id'] );
        $allowed = self::gridAllowedTeamIds();
        if ( $allowed !== null && ! in_array( $team_id, $allowed, true ) ) {
            return RestResponse::error( 'forbidden', __( 'That team is not in your scope.', 'talenttrack' ), 403 );
        }

        $defaults = \TT\Modules\Analytics\Reports\ReportFilters::seasonDefaultWindow();
        $from = preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) ( $r['from'] ?? '' ) ) ? (string) $r['from'] : $defaults['from'];
        $to   = preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) ( $r['to'] ?? '' ) )   ? (string) $r['to']   : $defaults['to'];

        $matrix = ( new \TT\Modules\Activities\Reports\MinutesGridQuery() )->matrix( $team_id, $from, $to );
        return RestResponse::success( $matrix );
    }

    /**
     * #2386 — POST /minutes/bulk — apply a batch of minutes-grid edits. Body:
     * `{ changes: [ { activity_id, player_id, minutes } ] }`. A blank minutes
     * clears the value. Each edit is routed through the minutes-ownership
     * arbiter: a match owned by a match-execution takes an override that
     * survives recompute; a paper match writes minutes_played directly. Only
     * squad players (an existing non-guest attendance row) can be written, so
     * a change with no row is skipped rather than inventing one.
     */
    public static function bulk_minutes( \WP_REST_Request $r ): \WP_REST_Response {
        $changes = $r['changes'] ?? null;
        if ( ! is_array( $changes ) ) {
            return RestResponse::error( 'bad_request', __( 'No changes supplied.', 'talenttrack' ), 400 );
        }

        $allowed = self::gridAllowedTeamIds();
        $repo    = self::repo();
        $exec    = new \TT\Modules\MatchExecution\Repositories\MatchExecutionRepository();
        $saved   = 0;
        $skipped = 0;
        $failed  = 0;

        foreach ( $changes as $c ) {
            if ( ! is_array( $c ) ) { $skipped++; continue; }
            $aid = absint( $c['activity_id'] ?? 0 );
            $pid = absint( $c['player_id'] ?? 0 );
            if ( $aid <= 0 || $pid <= 0 ) { $skipped++; continue; }

            $raw = $c['minutes'] ?? '';
            $minutes = ( $raw === '' || $raw === null ) ? null : max( 0, min( 200, absint( $raw ) ) );

            $team = $repo->activityTeamId( $aid );
            if ( $team <= 0 ) { $skipped++; continue; }
            if ( $allowed !== null && ! in_array( $team, $allowed, true ) ) { $skipped++; continue; }
            if ( $repo->playerTeamId( $pid ) !== $team ) { $skipped++; continue; }

            // The arbiter (#2367): an execution owns the minutes → write the
            // override (survives recompute); otherwise write minutes_played on
            // the paper-match attendance row. Both target an existing squad
            // row; a non-squad cell can't be edited in the grid.
            $ok = $exec->existsForActivity( $aid )
                ? $exec->setMinuteOverride( $aid, $pid, $minutes )
                : $repo->updateActualAttendanceMinutes( $aid, $pid, $minutes );

            if ( $ok ) {
                $saved++;
            } else {
                $skipped++; // no squad row to write onto — not a hard failure
            }
        }

        return RestResponse::success( [ 'saved' => $saved, 'skipped' => $skipped, 'failed' => $failed ] );
    }

    /**
     * #3094 — PUT /activities/{id}/contributions. Body:
     * `{ players: [ { player_id, goals, assists } ] }`.
     *
     * Counts in, events out. The reconciliation rules — insert manual rows,
     * reverse rather than delete, attach an assist to a goal that has not got
     * one — live in `MatchExecutionRepository::setContributions()`, not here:
     * this is the transport, and a future front end calling it must get the
     * same answers the grid does (CLAUDE.md §4).
     *
     * Scoped exactly like `/minutes/bulk`: the activity's team has to be one
     * the caller may edit, and a player has to belong to it. Recording a goal
     * for someone else's player is not a lesser mistake than recording their
     * minutes.
     *
     * The scoreline is never written. A mismatch between attributed goals and
     * `home_score` is information the grid shows in its footer, not an error
     * to correct behind the coach's back.
     */
    public static function put_contributions( \WP_REST_Request $r ): \WP_REST_Response {
        $activity_id = absint( $r['activity_id'] );
        $players     = $r['players'] ?? null;

        if ( $activity_id <= 0 || ! is_array( $players ) ) {
            return RestResponse::error( 'bad_request', __( 'No contributions supplied.', 'talenttrack' ), 400 );
        }

        $repo = self::repo();
        $team = $repo->activityTeamId( $activity_id );
        if ( $team <= 0 ) {
            return RestResponse::error( 'not_found', __( 'Unknown match.', 'talenttrack' ), 404 );
        }

        $allowed = self::gridAllowedTeamIds();
        if ( $allowed !== null && ! in_array( $team, $allowed, true ) ) {
            return RestResponse::error( 'forbidden', __( 'Not your team.', 'talenttrack' ), 403 );
        }

        $exec    = new \TT\Modules\MatchExecution\Repositories\MatchExecutionRepository();
        $saved   = 0;
        $skipped = 0;

        foreach ( $players as $row ) {
            if ( ! is_array( $row ) ) { $skipped++; continue; }

            $pid = absint( $row['player_id'] ?? 0 );
            if ( $pid <= 0 || $repo->playerTeamId( $pid ) !== $team ) { $skipped++; continue; }

            // Capped rather than refused: a coach who holds a key down should
            // get a number, not an error dialog. 99 is far beyond any real
            // youth match and still obviously a typo when it appears.
            $goals   = min( 99, absint( $row['goals'] ?? 0 ) );
            $assists = min( 99, absint( $row['assists'] ?? 0 ) );

            $exec->setContributions( $activity_id, $pid, $goals, $assists );
            $saved++;
        }

        return RestResponse::success( [ 'saved' => $saved, 'skipped' => $skipped ] );
    }

    /** Whitelist of columns the `orderby` query param accepts. */
    private const ORDERBY_WHITELIST = [
        'session_date' => 's.session_date',
        'title'        => 's.title',
        'team_name'    => 't.name',
        'attendance'   => 'attendance_count',
    ];

    /**
     * GET /sessions — paginated list with search, filters, sort.
     *
     * Query params (Sprint 2 contract):
     *   ?search=<text>            — title / location / team name LIKE
     *   ?filter[team_id]=<int|CSV>  — single id or comma-separated list (v4.20.26 / #1212)
     *   ?filter[date_from]=<YYYY-MM-DD>
     *   ?filter[date_to]=<YYYY-MM-DD>
     *   ?filter[attendance]=complete|partial|none
     *   ?orderby=session_date|title|team_name|attendance
     *   ?order=asc|desc                                   (default: desc on session_date, asc otherwise)
     *   ?page=<int>                                       (default 1)
     *   ?per_page=10|25|50|100                            (default 25)
     *   ?include_archived=1                               (default off)
     *
     * Coach-scoping: non-admin users only see sessions for teams they
     * head-coach. Admins (`tt_edit_settings`) see all.
     *
     * Attendance-completeness is computed on the fly per row (Q2 in the
     * Sprint 2 plan): roster size = active players on the team;
     * attendance_count = rows in tt_attendance for the session;
     * complete = count >= roster (and roster > 0); partial = 0 < count < roster;
     * none = count = 0.
     *
     * @return \WP_REST_Response
     */
    public static function list_sessions( \WP_REST_Request $r ) {
        $page     = max( 1, absint( $r['page'] ?? 1 ) );
        $per_page = self::clamp_per_page( $r['per_page'] ?? 25 );
        $offset   = ( $page - 1 ) * $per_page;

        $orderby_key = sanitize_key( (string) ( $r['orderby'] ?? 'session_date' ) );
        if ( ! isset( self::ORDERBY_WHITELIST[ $orderby_key ] ) ) {
            return RestResponse::error(
                'bad_orderby',
                __( 'Unknown orderby column.', 'talenttrack' ),
                400,
                [ 'allowed' => array_keys( self::ORDERBY_WHITELIST ) ]
            );
        }
        $orderby = self::ORDERBY_WHITELIST[ $orderby_key ];
        $order   = strtolower( (string) ( $r['order'] ?? ( $orderby_key === 'session_date' ? 'desc' : 'asc' ) ) );
        if ( ! in_array( $order, [ 'asc', 'desc' ], true ) ) $order = 'desc';

        // #1555 — the Active / Archived / All status filter is applied in
        // ActivitiesRepository::searchForRest() (the $wpdb query lives there
        // post-#1712); the `filter` array carrying `archived` is passed through.
        // Filters — pulled early because the coach-scope guard below has
        // to know whether the request is a player-scoped my-activities
        // call before deciding to early-return on "no head-coach teams".
        $filter = is_array( $r['filter'] ?? null ) ? $r['filter'] : [];

        // #2150 — fail-closed scoping for the player / parent
        // "my-activities" surface. A caller without the staff activities
        // capability reaches this handler only through the player-or-
        // parent branch of `can_view`, so their result MUST be scoped to
        // a player they're entitled to read. We re-derive that player id
        // server-side from the session (never trusting the query param
        // alone): for a linked player it's their own id; for a parent the
        // requested child id is honoured only after the parent link is
        // verified. If neither resolves, the request is force-scoped to a
        // sentinel that returns the empty set — never the unscoped list.
        $uid        = get_current_user_id();
        $caller_is_staff = AuthorizationService::userCanOrMatrix( $uid, 'tt_view_activities' )
            || AuthorizationService::userCanOrMatrix( $uid, 'tt_edit_activities' );

        if ( ! $caller_is_staff ) {
            $scoped_pid = self::resolvePlayerScopeForCaller( $uid, (int) ( $filter['player_id'] ?? 0 ) );
            if ( $scoped_pid <= 0 ) {
                // No linked player (or unverified child) → empty set, not
                // the unscoped list. Fail closed.
                return RestResponse::success( [
                    'rows' => [], 'total' => 0, 'page' => $page, 'per_page' => $per_page,
                ] );
            }
            // Force-scope server-side to the verified player id, replacing
            // whatever the client sent.
            $filter['player_id'] = $scoped_pid;
        }

        // v3.110.51 — when the request is a player-scoped my-activities
        // call (`filter[player_id]` matches the caller's linked player,
        // already validated by `can_view`), the player → team-membership
        // predicate inside searchForRest is the correct scoping. Skip the
        // coach-scope restriction for these requests; otherwise a
        // logged-in player calling `?tt_view=my-activities` got an empty
        // list because they have zero head-coach teams.
        $is_player_scoped = ! empty( $filter['player_id'] )
            && self::callerCanReadAsPlayerOrParent( (int) $filter['player_id'] );

        // v3.91.2 — bypass the coach-scope restriction for personas with
        // matrix `activities:r[global]` (scout, head_of_development,
        // academy_admin). v3.110.51 — also for player-scoped requests.
        // #1712 — the authorization decision (which teams may this caller
        // see?) stays here; the resolved restriction is handed to the
        // repository, where the SQL lives. null = unrestricted.
        $restrict_team_ids = null;
        if ( ! $is_player_scoped
             && ! QueryHelpers::user_has_global_entity_read( get_current_user_id(), 'activities' ) ) {
            $coach_teams = QueryHelpers::get_teams_for_coach( get_current_user_id() );
            if ( ! $coach_teams ) {
                // No accessible teams → empty list (don't expose sessions).
                return RestResponse::success( [
                    'rows' => [], 'total' => 0, 'page' => $page, 'per_page' => $per_page,
                ] );
            }
            $restrict_team_ids = array_map( static function ( $t ) { return (int) $t->id; }, $coach_teams );
        }

        $result = self::repo()->searchForRest( [
            'per_page'          => $per_page,
            'offset'            => $offset,
            'orderby'           => $orderby,
            'order'             => $order,
            'include_archived'  => ! empty( $r['include_archived'] ),
            'restrict_team_ids' => $restrict_team_ids,
            'filter'            => $filter,
            'search'            => (string) ( $r['search'] ?? '' ),
            'your_status_pid'   => isset( $filter['player_id'] ) && (int) $filter['player_id'] > 0 ? absint( $filter['player_id'] ) : 0,
        ] );

        return RestResponse::success( [
            'rows'     => array_map( [ __CLASS__, 'format_row' ], $result['rows'] ),
            'total'    => $result['total'],
            'page'     => $page,
            'per_page' => $per_page,
        ] );
    }

    /** Per-page values the client may request. Defaults to 25. */
    private static function clamp_per_page( $value ): int {
        $n = absint( $value );
        if ( ! in_array( $n, [ 10, 25, 50, 100 ], true ) ) return 25;
        return $n;
    }

    /**
     * v3.110.51 — true when the calling user has a legitimate read
     * relationship to the requested player_id: either the player IS
     * the calling user, or the calling user is a registered parent
     * of that player. Used by `list_sessions` to detect player-scoped
     * my-activities calls (player or parent) and bypass the coach-team
     * scope filter for them.
     *
     * Mirrors the logic in `can_view()` — the permission gate already
     * validates this, so we re-derive the boolean here without
     * re-running the permission checks.
     */
    private static function callerCanReadAsPlayerOrParent( int $player_id ): bool {
        if ( $player_id <= 0 ) return false;
        $uid = get_current_user_id();
        if ( $uid <= 0 ) return false;
        // #1712 — identity lookups moved to ActivitiesRepository.
        $repo = self::repo();
        if ( $repo->linkedPlayerIdForUser( $uid ) === $player_id ) return true;
        return $repo->userIsParentOfPlayer( $uid, $player_id );
    }

    /**
     * #2150 — resolve the player id a non-staff caller may be scoped to
     * for the "my-activities" list, derived from the session rather than
     * trusted from the query param.
     *
     * Returns:
     *   - the caller's own linked player id, when they have one (their
     *     own journey — the query param is ignored for self-scope);
     *   - the requested child id, only when the caller is a verified
     *     parent of that player;
     *   - 0 when nothing resolves — the caller (e.g. a WP user with no
     *     linked `tt_players` row) must see an empty list, never a leak.
     */
    private static function resolvePlayerScopeForCaller( int $uid, int $requested_pid ): int {
        if ( $uid <= 0 ) return 0;
        $repo = self::repo();

        $own = $repo->linkedPlayerIdForUser( $uid );
        if ( $own > 0 ) return $own;

        // Parent viewing a specific child: honour the requested id only
        // after the parent → child link is verified server-side.
        if ( $requested_pid > 0 && $repo->userIsParentOfPlayer( $uid, $requested_pid ) ) {
            return $requested_pid;
        }

        return 0;
    }

    /** Shape one row for the JSON response. */
    private static function format_row( $row ): array {
        $attendance_pct = null;
        $roster  = (int) ( $row->roster_size ?? 0 );
        $count   = (int) ( $row->attendance_count ?? 0 );
        $present = (int) ( $row->present_count ?? 0 );
        $status  = (string) ( $row->activity_status_key ?? 'planned' );
        // v3.87.1 — only compute attendance % for activities that
        // actually happened. Planned/cancelled rows previously rendered
        // 0% which looked like real data — operators on a pilot install
        // mistook the column for "no-one showed up" instead of "didn't
        // happen yet / won't happen".
        // #0061 — pct = present / roster, not recorded / roster.
        // v3.110.x — clamp to 100. The denominator is the active-status
        // roster; the numerator is every Present row regardless of
        // whether the player is still on the team. A player who moved
        // teams between activity creation and attendance recording
        // still has their `tt_attendance` row counted, but their
        // `tt_players.team_id` may have moved away from this activity's
        // team — present_count > roster_size in that edge case, which
        // produced > 100% values. Clamp so the % column never lies.
        if ( $roster > 0 && ! in_array( $status, [ 'planned', 'cancelled' ], true ) ) {
            $attendance_pct = (int) round( ( $present / $roster ) * 100 );
            if ( $attendance_pct > 100 ) $attendance_pct = 100;
        }

        $type_key = (string) ( $row->activity_type_key ?? ActivityTypeKey::TRAINING );

        // #0063 — pre-render the title + team as RecordLink HTML so the
        // generic FrontendListTable can display them as clickable cells
        // via render: html. Title links to activities&id=N (read-only
        // detail), team to teams&id=N. The picture is the same regardless
        // of who renders the row.
        // v3.70.1 hotfix — slug switched from `my-activities` (player-
        // self-scope) to the generic `activities` so cross-persona views
        // (HoD on a team's roster, academy admin on the full list) don't
        // hit the player-only "you must be connected to a player" gate.
        $title_url = \TT\Shared\Frontend\Components\RecordLink::detailUrlForWithBack( 'activities', (int) $row->id );
        $title_link_html = \TT\Shared\Frontend\Components\RecordLink::inline(
            (string) $row->title,
            $title_url
        );
        $team_link_html = '';
        if ( ! empty( $row->team_id ) && ! empty( $row->team_name ) ) {
            $team_link_html = \TT\Shared\Frontend\Components\RecordLink::inline(
                (string) $row->team_name,
                \TT\Shared\Frontend\Components\RecordLink::detailUrlForWithBack( 'teams', (int) $row->team_id )
            );
        }

        return [
            'id'                       => (int) $row->id,
            'title'                    => (string) $row->title,
            'title_link_html'          => $title_link_html,
            'session_date'             => (string) $row->session_date,
            // #1126 — surface optional time window on the read payload.
            'start_time'               => $row->start_time !== null ? (string) $row->start_time : null,
            'end_time'                 => $row->end_time   !== null ? (string) $row->end_time   : null,
            // #1729 — optional arrival/presence time (match activities).
            'time_of_presence'         => isset( $row->time_of_presence ) && $row->time_of_presence !== null ? (string) $row->time_of_presence : null,
            'location'                 => (string) ( $row->location ?? '' ),
            // #1989 — match result fields (present on the single-activity
            // a.* fetch; null on list shapes that don't select them).
            'opponent'                 => isset( $row->opponent ) ? (string) $row->opponent : null,
            'home_away'                => isset( $row->home_away ) ? (string) $row->home_away : null,
            'home_score'               => isset( $row->home_score ) && $row->home_score !== null ? (int) $row->home_score : null,
            'away_score'               => isset( $row->away_score ) && $row->away_score !== null ? (int) $row->away_score : null,
            'team_id'                  => (int) ( $row->team_id ?? 0 ),
            'team_name'                => (string) ( $row->team_name ?? '' ),
            'team_link_html'           => $team_link_html,
            'coach_id'                 => (int) ( $row->coach_id ?? 0 ),
            'activity_type_key'         => $type_key,
            'activity_type_pill_html'   => \TT\Infrastructure\Query\LookupPill::render( 'activity_type', $type_key ),
            // #1324 — surface the optional tournament link.
            'tournament_id'             => isset( $row->tournament_id ) ? (int) $row->tournament_id : null,
            'tournament'                => isset( $row->tournament ) && is_object( $row->tournament )
                ? [
                    'id'          => (int) $row->tournament->id,
                    'name'        => (string) $row->tournament->name,
                    'start_date'  => (string) ( $row->tournament->start_date ?? '' ),
                    'end_date'    => $row->tournament->end_date !== null ? (string) $row->tournament->end_date : null,
                    'match_count' => (int) ( $row->tournament->match_count ?? 0 ),
                ]
                : null,
            'activity_status_key'       => (string) ( $row->activity_status_key ?? 'planned' ),
            'activity_status_pill_html' => \TT\Infrastructure\Query\LookupPill::render( 'activity_status', (string) ( $row->activity_status_key ?? 'planned' ) ),
            'activity_source_key'       => (string) ( $row->activity_source_key ?? 'manual' ),
            // v3.71.0 — pill HTML so the activity list can show Source
            // alongside Type / Status without an extra render mode. The
            // `activity_source` lookup is already seeded (manual / spond /
            // generated, migration 0040) and editable via the Lookups
            // admin page.
            'activity_source_pill_html' => \TT\Infrastructure\Query\LookupPill::render( 'activity_source', (string) ( $row->activity_source_key ?? 'manual' ) ),
            // #2221 — team-level last Spond sync timestamp (from tt_teams,
            // migration 0041). Only meaningful on Spond-sourced activities;
            // null on list shapes / non-Spond rows. A future front end
            // renders the same "team last synced" freshness line the PHP
            // detail view shows, keyed off activity_source_key === 'spond'.
            'team_spond_last_sync_at'   => isset( $row->team_spond_last_sync_at ) && $row->team_spond_last_sync_at !== null
                ? (string) $row->team_spond_last_sync_at
                : null,
            'attendance_count'         => $count,
            'present_count'            => $present,
            'roster_size'              => $roster,
            'attendance_pct'           => $attendance_pct,
            // #1726 — per-match full length (minutes); null for non-match
            // types. Per-player minutes/lineup_role are persisted on
            // tt_attendance and read by the minutes report + match-execution
            // surfaces (a dedicated per-player GET can expose them when a
            // non-WP consumer needs the breakdown).
            'match_length_minutes'     => isset( $row->match_length_minutes ) && $row->match_length_minutes !== null
                ? (int) $row->match_length_minutes
                : null,
            // v3.92.7 — surfaced only when filter[player_id] was set on
            // the request (the SELECT subquery emits NULL otherwise).
            // Picked up by `?tt_view=my-activities`'s "Your status" column.
            'your_attendance_status'   => isset( $row->your_attendance_status ) && $row->your_attendance_status !== null
                ? (string) $row->your_attendance_status
                : '',
            'your_attendance_pill_html' => isset( $row->your_attendance_status ) && $row->your_attendance_status !== null
                ? \TT\Infrastructure\Query\LookupPill::render( 'attendance_status', (string) $row->your_attendance_status )
                : '',
            // v3.110.170 — row-link standard (#758). Same URL the title
            // cell links to; exposed as a top-level field so FrontendListTable's
            // `row_url_key` config can navigate the whole row.
            'detail_url'               => $title_url,
            // #2023 — archived_at + trashed_at via the shared lifecycle helper.
        ] + \TT\Infrastructure\Archive\LifecycleFields::forRow( $row );
    }

    public static function create_session( \WP_REST_Request $r ) {
        $type_error = self::validateActivityType( $r );
        if ( $type_error !== null ) return $type_error;

        $data = self::extract( $r );
        $data['coach_id'] = get_current_user_id();
        $data['club_id']  = CurrentClub::id();
        // Source defaults to 'manual' on REST creation. Spond import +
        // demo-data writes set this from their own code paths.
        $data['activity_source_key'] = 'manual';

        if ( $data['title'] === '' || $data['session_date'] === '' ) {
            return RestResponse::error( 'missing_fields', __( 'Title and date are required.', 'talenttrack' ), 400 );
        }

        // #1712 — shared write path with the wp-admin page (ActivitiesPage).
        // create() re-stamps club_id and the created_by audit column.
        $repo        = self::repo();
        $activity_id = $repo->create( $data );
        if ( $activity_id === null ) {
            $err = $repo->lastError();
            Logger::error( 'session.save.failed', [ 'db_error' => $err, 'payload' => $data ] );
            return RestResponse::error(
                'db_error',
                __( 'The activity could not be saved. The database rejected the operation.', 'talenttrack' ),
                500,
                [ 'db_error' => $err ]
            );
        }

        // #0049 / v3.76.2 — auto-tag demo-on rows. Refactored from an
        // inline check to the central DemoMode::tagIfActive helper so
        // every entity-create site shares the same idempotent path.
        \TT\Modules\DemoData\DemoMode::tagIfActive( 'activity', $activity_id );

        // #0077 M2 — frontend↔admin parity. The wp-admin ActivitiesPage
        // wrote `activity_principle_ids[]` via PrincipleLinksRepository;
        // the REST path silently dropped them. Both surfaces now share
        // the same handler so the frontend form can save its principle
        // multiselect.
        self::persistPrincipleLinks( $r, $activity_id );

        // #0025 — detect source language for free-text session fields.
        \TT\Modules\Translations\TranslationLayer::detectAndCache( 'activity', $activity_id, 'title',    (string) $data['title'] );
        \TT\Modules\Translations\TranslationLayer::detectAndCache( 'activity', $activity_id, 'notes',    (string) $data['notes'] );
        \TT\Modules\Translations\TranslationLayer::detectAndCache( 'activity', $activity_id, 'location', (string) $data['location'] );

        $att_failures = self::write_attendance( $activity_id, self::attendance_from_request( $r ) );
        if ( $att_failures ) {
            Logger::error( 'session.attendance.save.failed', [ 'activity_id' => $activity_id, 'failures' => $att_failures ] );
            return RestResponse::error(
                'partial_save',
                __( 'The activity was saved, but some attendance rows could not be stored.', 'talenttrack' ),
                500,
                [ 'activity_id' => $activity_id, 'failures' => $att_failures ]
            );
        }

        // #1636 — an activity created already in "completed" status with no
        // usable attendance is unrateable (the rate step needs present/late
        // rows). Seed the team roster as present so the coach can evaluate
        // immediately and adjust absences afterward.
        if ( ( $data['activity_status_key'] ?? '' ) === ActivityStatusKey::COMPLETED ) {
            self::seedCompletedRosterPresent( $activity_id, (int) ( $data['team_id'] ?? 0 ) );
        }

        // v3.71.6 — fire the workflow event so EventDispatcher hands
        // off to the post-game evaluation template (and any other
        // template subscribed to `tt_activity_completed`). The wp-admin
        // ActivitiesPage::handle_save fires the same event; the REST
        // path was missing it, so coaches saving games via the
        // frontend never got tasks generated. Root cause of #24.
        if ( class_exists( '\\TT\\Modules\\Workflow\\TaskContext' ) ) {
            $ctx = new \TT\Modules\Workflow\TaskContext( null, (int) $data['team_id'], $activity_id );
            do_action( 'tt_activity_completed', $ctx, (string) $data['activity_type_key'] );
        }

        return RestResponse::success( [ 'id' => $activity_id ] );
    }

    public static function update_session( \WP_REST_Request $r ) {
        $activity_id = absint( $r['id'] );
        if ( $activity_id <= 0 ) {
            return RestResponse::error( 'bad_id', __( 'Invalid activity id.', 'talenttrack' ), 400 );
        }

        $type_error = self::validateActivityType( $r );
        if ( $type_error !== null ) return $type_error;

        $data = self::extract( $r );
        // Preserve original coach on update.
        unset( $data['coach_id'] );

        // #1712 — shared write path with the wp-admin page (ActivitiesPage).
        // update() is club-scoped and stamps the updated_by audit column.
        $repo = self::repo();
        if ( ! $repo->update( $activity_id, $data ) ) {
            $err = $repo->lastError();
            Logger::error( 'session.update.failed', [ 'db_error' => $err, 'activity_id' => $activity_id ] );
            return RestResponse::error(
                'db_error',
                __( 'The activity could not be updated. The database rejected the operation.', 'talenttrack' ),
                500,
                [ 'db_error' => $err ]
            );
        }

        // #3362 — the per-training cycle override. Applied AFTER the
        // repository write, because that write fires `tt_activity_saved`
        // and the stamper re-derives the cycle from it; applying the
        // override first would have it immediately overwritten.
        //
        // Absent from the payload means "leave it alone", never "clear
        // it" — a PATCH that omits the field must not discard a coach's
        // override.
        self::applyCycleOverride( $r, $activity_id );

        // #0025 — re-detect source language on update; idempotent on
        // unchanged content via the source_hash check inside.
        \TT\Modules\Translations\TranslationLayer::detectAndCache( 'activity', $activity_id, 'title',    (string) $data['title'] );
        \TT\Modules\Translations\TranslationLayer::detectAndCache( 'activity', $activity_id, 'notes',    (string) $data['notes'] );
        \TT\Modules\Translations\TranslationLayer::detectAndCache( 'activity', $activity_id, 'location', (string) $data['location'] );

        // #2771 — a match prep writes its Starting XI through onto this
        // activity's attendance rows, and the Line-up card reads nothing
        // else. Both blocks below delete rows before rewriting them, so the
        // projection is snapshotted here, once, while it still exists.
        // Taking it inside either block would leave the other one reading an
        // already-emptied table.
        $planned_lineup = $repo->lineupProjectionFor( $activity_id, 'expected' );
        $roster_lineup  = $repo->lineupProjectionFor( $activity_id, null );

        if ( self::request_has_attendance( $r ) ) {
            // #0026 — only wipe the roster rows; guest rows are
            // managed via the dedicated guest endpoints and must
            // survive a session update.
            $repo->deleteRosterAttendance( $activity_id );
            $att_failures = self::write_attendance(
                $activity_id,
                self::withPreservedLineup( self::attendance_from_request( $r ), $roster_lineup )
            );
            if ( $att_failures ) {
                Logger::error( 'session.attendance.update.failed', [ 'activity_id' => $activity_id, 'failures' => $att_failures ] );
                return RestResponse::error(
                    'partial_save',
                    __( 'The activity was updated, but some attendance rows could not be stored.', 'talenttrack' ),
                    500,
                    [ 'activity_id' => $activity_id, 'failures' => $att_failures ]
                );
            }
            // #0006 — auto-transition plan-state from scheduled /
            // in_progress → completed once attendance is logged.
            // The planner depends on this transition to surface
            // "what happened this week" vs. "what's coming up".
            $current_state = $repo->planState( $activity_id );
            if ( in_array( $current_state, [ 'scheduled', 'in_progress' ], true ) ) {
                $repo->setPlanState( $activity_id, 'completed' );
            }
        }

        // #2248 — planned (expected) attendance edited on the activity
        // form for a not-yet-completed activity. Separate from the
        // actual-attendance block above: it only ever touches
        // `record_type='expected'` rows, so recorded attendance and the
        // reports (which count `actual`) are unaffected. Gated on the same
        // `tt_edit_activities` cap as the whole PUT.
        if ( isset( $r['planned'] ) && is_array( $r['planned'] ) ) {
            $repo->replacePlannedAttendance(
                $activity_id,
                self::planned_attendance_from_request( $r ),
                $planned_lineup
            );
        }

        // v3.71.6 — see create_session above. Frontend updates were
        // not firing the workflow event, so a coach moving a game from
        // planned → completed via the frontend never triggered the
        // post-game evaluation tasks. Mirrors wp-admin behaviour.
        if ( class_exists( '\\TT\\Modules\\Workflow\\TaskContext' ) ) {
            $ctx = new \TT\Modules\Workflow\TaskContext( null, (int) $data['team_id'], $activity_id );
            do_action( 'tt_activity_completed', $ctx, (string) $data['activity_type_key'] );
        }

        // #0077 M2 — see create_session.
        self::persistPrincipleLinks( $r, $activity_id );

        return RestResponse::success( [ 'id' => $activity_id ] );
    }

    /**
     * #0077 M2 — extract `activity_principle_ids[]` from the request and
     * write through PrincipleLinksRepository. Called from both
     * create_session and update_session so the frontend form has parity
     * with the wp-admin page.
     */
    /**
     * #3362 (epic #3354) — the per-training cycle override.
     *
     * `vct_cycle_choice` is `auto` | `neutral` | `week`. Anything else,
     * including its absence, leaves the training alone — an omitted field
     * must never discard a coach's override, which is the partial-update
     * contract every autosaving and PATCH-ing caller relies on.
     */
    private static function applyCycleOverride( \WP_REST_Request $r, int $activity_id ): void {
        if ( ! class_exists( '\\TT\\Modules\\Vct\\Services\\VctActivityStamper' ) ) return;

        $choice = $r['vct_cycle_choice'] ?? null;
        if ( ! is_string( $choice ) || $choice === '' ) return;

        $activity = self::repo()->findById( $activity_id );
        if ( $activity === null ) return;

        $team_id = (int) ( $activity->team_id ?? 0 );
        $date    = (string) ( $activity->session_date ?? '' );

        switch ( $choice ) {
            case 'auto':
                \TT\Modules\Vct\Services\VctActivityStamper::setOverride( $activity_id, $team_id, $date, null, null );
                return;
            case 'neutral':
                \TT\Modules\Vct\Services\VctActivityStamper::setOverride( $activity_id, $team_id, $date, 'neutral', null );
                return;
            case 'week':
                $week = absint( $r['vct_cycle_week'] ?? 0 );
                if ( $week <= 0 ) return;
                \TT\Modules\Vct\Services\VctActivityStamper::setOverride( $activity_id, $team_id, $date, 'active', $week );
                return;
        }
    }

    private static function persistPrincipleLinks( \WP_REST_Request $r, int $activity_id ): void {
        if ( ! class_exists( '\\TT\\Modules\\Methodology\\Repositories\\PrincipleLinksRepository' ) ) return;
        $raw = $r['activity_principle_ids'] ?? null;
        // The frontend form carries an `activity_principles_present` marker so an
        // all-unchecked submission (which omits the checkbox array entirely)
        // still clears the links. Without the marker an absent array means a
        // partial API update that didn't touch principles — leave them intact.
        $present = ! empty( $r['activity_principles_present'] );
        if ( $raw === null && ! $present ) return;
        if ( ! is_array( $raw ) ) $raw = [];
        $ids = array_values( array_unique( array_filter( array_map( 'intval', $raw ) ) ) );
        ( new \TT\Modules\Methodology\Repositories\PrincipleLinksRepository() )->setActivityPrinciples( $activity_id, $ids );
    }

    /**
     * DELETE /activities/{id} — soft-archive (#1555).
     *
     * Mirrors the goals/players/teams archive lifecycle: the row is
     * stamped archived_at + archived_by rather than hard-deleted, so it
     * disappears from the default (active) timeline but survives for
     * restore. Attendance rows are left intact — they're part of the
     * historical record and come back on restore. Permanent removal is
     * the separate, capability-gated `/permanent` route below.
     */
    public static function delete_session( \WP_REST_Request $r ) {
        $activity_id = absint( $r['id'] );
        if ( $activity_id <= 0 ) {
            return RestResponse::error( 'bad_id', __( 'Invalid activity id.', 'talenttrack' ), 400 );
        }

        $n = ( new \TT\Infrastructure\Archive\ArchiveRepository() )
            ->archive( 'activity', [ $activity_id ], (int) get_current_user_id() );
        if ( $n === 0 ) {
            return RestResponse::error( 'not_found', __( 'Activity not found.', 'talenttrack' ), 404 );
        }

        return RestResponse::success( [ 'archived' => true, 'id' => $activity_id ] );
    }

    /**
     * POST /activities/{id}/restore — clear the archive stamp (#1555).
     */
    public static function restore_session( \WP_REST_Request $r ) {
        $activity_id = absint( $r['id'] );
        if ( $activity_id <= 0 ) {
            return RestResponse::error( 'bad_id', __( 'Invalid activity id.', 'talenttrack' ), 400 );
        }

        $n = ( new \TT\Infrastructure\Archive\ArchiveRepository() )->restore( 'activity', [ $activity_id ] );
        if ( $n === 0 ) {
            return RestResponse::error( 'not_found', __( 'Activity not found.', 'talenttrack' ), 404 );
        }

        return RestResponse::success( [ 'restored' => true, 'id' => $activity_id ] );
    }

    /**
     * DELETE /activities/{id}/permanent — gated hard-delete (#1555).
     *
     * Capability-gated behind `tt_edit_settings` and routed through
     * ArchiveRepository, which fail-closes via the CascadeRegistry. The
     * activity cascade plan (#2027) cascades execution data (attendance,
     * exercises, principles, the match-prep / match-execution trees) and
     * clears the link on records that outlive the activity (evaluations,
     * behaviour ratings); an undeclared reference still surfaces as a 409
     * dependency report rather than stranding orphans.
     */
    public static function delete_session_permanently( \WP_REST_Request $r ) {
        $activity_id = absint( $r['id'] );
        if ( $activity_id <= 0 ) {
            return RestResponse::error( 'bad_id', __( 'Invalid activity id.', 'talenttrack' ), 400 );
        }

        try {
            $n = ( new \TT\Infrastructure\Archive\ArchiveRepository() )
                ->deletePermanently( 'activity', [ $activity_id ] );
        } catch ( \TT\Infrastructure\Archive\DeleteBlockedException $e ) {
            return RestResponse::error( 'delete_blocked', $e->getMessage(), 409 );
        }
        if ( $n === 0 ) {
            return RestResponse::error( 'not_found', __( 'Activity not found.', 'talenttrack' ), 404 );
        }

        return RestResponse::success( [ 'deleted' => true, 'id' => $activity_id ] );
    }

    public static function can_hard_delete(): bool {
        return current_user_can( 'tt_edit_settings' );
    }

    /**
     * @return array<string, mixed>
     */
    private static function extract( \WP_REST_Request $r ): array {
        // #0050 — Type comes from the activity_type lookup. Empty value
        // falls back to the seeded 'training'; an unknown value is the
        // caller's responsibility to reject (see validateActivityType).
        $type    = sanitize_text_field( (string) ( $r['activity_type_key'] ?? '' ) );
        if ( $type === '' ) $type = ActivityTypeKey::TRAINING;
        $subtype = sanitize_text_field( (string) ( $r['game_subtype_key'] ?? '' ) );
        $other   = sanitize_text_field( (string) ( $r['other_label'] ?? '' ) );

        $status = sanitize_text_field( (string) ( $r['activity_status_key'] ?? '' ) );
        $valid_statuses = QueryHelpers::get_lookup_names( 'activity_status' );
        if ( $status === '' || ! in_array( $status, $valid_statuses, true ) ) $status = ActivityStatusKey::PLANNED;

        // #0006 — plan-state is optional on REST. The legacy logging
        // flow leaves it null and the column default ('completed') wins,
        // preserving back-compat. Planner-driven creation passes
        // 'scheduled' so the row appears in the planner-only filter.
        $plan_state = sanitize_text_field( (string) ( $r['plan_state'] ?? '' ) );
        $allowed_plan_states = [ 'draft', 'scheduled', 'in_progress', 'completed', 'cancelled' ];

        // #1126 — optional start_time + end_time. Validate HH:MM
        // shape, treat empty as null. End must come after start when
        // both are set.
        $start_time_raw = trim( (string) ( $r['start_time'] ?? '' ) );
        $end_time_raw   = trim( (string) ( $r['end_time']   ?? '' ) );
        $start_time = null;
        $end_time   = null;
        if ( $start_time_raw !== '' && preg_match( '/^\d{2}:\d{2}(:\d{2})?$/', $start_time_raw ) ) {
            $start_time = $start_time_raw;
        }
        if ( $end_time_raw !== '' && preg_match( '/^\d{2}:\d{2}(:\d{2})?$/', $end_time_raw ) ) {
            $end_time = $end_time_raw;
        }
        if ( $start_time !== null && $end_time !== null
            && strtotime( '1970-01-01 ' . $end_time ) <= strtotime( '1970-01-01 ' . $start_time )
        ) {
            // Silently drop end_time on a malformed combo so the row
            // still persists; UI validation should have caught this.
            $end_time = null;
        }
        // #1729 — optional arrival/presence time (match types). Same
        // HH:MM shape validation as start_time; empty → null.
        $presence_raw  = trim( (string) ( $r['time_of_presence'] ?? '' ) );
        $time_of_presence = ( $presence_raw !== '' && preg_match( '/^\d{2}:\d{2}(:\d{2})?$/', $presence_raw ) )
            ? $presence_raw
            : null;
        // #1324 — tournament_id only persists when the activity is
        // type=tournament; non-tournament types null it out so a type
        // change doesn't leave a stale FK behind.
        $tournament_id = absint( $r['tournament_id'] ?? 0 );

        // #1726 — per-match full length (minutes), match types only. Used
        // to derive subs-off (starters under the full length). Clamped to a
        // sane ceiling; non-match types null it.
        $match_length_raw = absint( $r['match_length_minutes'] ?? 0 );
        $match_length     = ( $type === ActivityTypeKey::GAME && $match_length_raw > 0 )
            ? min( 300, $match_length_raw )
            : null;

        $payload = [
            'title'               => sanitize_text_field( (string) ( $r['title'] ?? '' ) ),
            'session_date'        => sanitize_text_field( (string) ( $r['session_date'] ?? '' ) ),
            'start_time'          => $start_time,
            'end_time'            => $end_time,
            'time_of_presence'    => $time_of_presence,
            // #2282 — mirror start_time into kickoff_time for match types
            // (game / tournament). The edit form only exposes start_time
            // ("Begintijd"), but the week-plan print shows "Aftrap" from
            // kickoff_time — which Spond seeds on import. Without this, a
            // coach editing the start time on a Spond-imported match left
            // kickoff_time stale, so the print disagreed with the form.
            // Keeping them in sync on every save fixes that divergence;
            // non-match types null it out.
            'kickoff_time'        => in_array( $type, [ ActivityTypeKey::GAME, ActivityTypeKey::TOURNAMENT ], true ) ? $start_time : null,
            'team_id'             => absint( $r['team_id'] ?? 0 ),
            'coach_id'            => get_current_user_id(),
            'location'            => sanitize_text_field( (string) ( $r['location'] ?? '' ) ),
            'notes'               => sanitize_textarea_field( (string) ( $r['notes'] ?? '' ) ),
            'activity_type_key'   => $type,
            'activity_status_key' => $status,
            'game_subtype_key'    => $type === ActivityTypeKey::GAME && $subtype !== '' ? $subtype : null,
            'other_label'         => $type === ActivityTypeKey::OTHER && $other !== ''   ? $other   : null,
            'tournament_id'       => $type === ActivityTypeKey::TOURNAMENT && $tournament_id > 0 ? $tournament_id : null,
            'match_length_minutes' => $match_length,
        ];
        if ( in_array( $plan_state, $allowed_plan_states, true ) ) {
            $payload['plan_state'] = $plan_state;
            if ( $plan_state === 'scheduled' ) {
                $payload['planned_at'] = current_time( 'mysql' );
                $payload['planned_by'] = get_current_user_id();
            }
        } else {
            // #1349 — the edit form posts activity_status_key but not
            // plan_state, so flipping status to Completed used to leave
            // plan_state='scheduled' behind and the session stayed
            // invisible to the attendance/eval wizards. The two columns
            // are distinct lifecycle axes (migration 0144), but a
            // terminal status implies the matching terminal plan state.
            // #3081 moved the rule into `ActivityLifecycle` so the
            // wp-admin save path applies the same one.
            $implied = \TT\Modules\Activities\Services\ActivityLifecycle::planStateForTerminalStatus( $status );
            if ( $implied !== null ) $payload['plan_state'] = $implied;
        }
        return $payload;
    }

    /**
     * #0050 — strict-mode validation: reject unknown type values with
     * 400 instead of silently falling back. Returns null when the type
     * is valid (or empty — empty falls back to 'training' inside
     * extract()), or a WP_REST_Response error to short-circuit on.
     */
    private static function validateActivityType( \WP_REST_Request $r ): ?\WP_REST_Response {
        $type = sanitize_text_field( (string) ( $r['activity_type_key'] ?? '' ) );
        if ( $type === '' ) return null;
        $valid = QueryHelpers::get_lookup_names( 'activity_type' );
        if ( in_array( $type, $valid, true ) ) return null;
        return RestResponse::error(
            'bad_activity_type',
            __( 'Unknown activity type. Pick one from the configured list.', 'talenttrack' ),
            400,
            [ 'allowed' => array_values( $valid ) ]
        );
    }

    /**
     * Accept attendance under either `attendance` (new name) or the
     * legacy `att` key that the pre-REST form used.
     *
     * @return array<int, array{status:string, notes:string}>
     */
    private static function attendance_from_request( \WP_REST_Request $r ): array {
        $raw = $r['attendance'] ?? $r['att'] ?? [];
        if ( ! is_array( $raw ) ) return [];
        $out = [];
        foreach ( $raw as $player_id => $fields ) {
            if ( ! is_array( $fields ) ) continue;
            $pid = absint( $player_id );
            if ( $pid <= 0 ) continue;
            $row = [
                'status' => sanitize_text_field( (string) ( $fields['status'] ?? 'Present' ) ),
                'notes'  => sanitize_text_field( (string) ( $fields['notes'] ?? '' ) ),
            ];
            // #1726 — match-completion direct entry: optional starter flag
            // (→ lineup_role) and per-player minutes (→ minutes_played).
            // Only present for match activities; absent keys leave the
            // columns untouched.
            if ( array_key_exists( 'starter', $fields ) ) {
                $row['lineup_role'] = ! empty( $fields['starter'] ) ? 'start' : 'bench';
            }
            if ( array_key_exists( 'minutes', $fields ) ) {
                $m = trim( (string) $fields['minutes'] );
                $row['minutes_played'] = $m === '' ? null : max( 0, (int) $m );
            }
            $out[ $pid ] = $row;
        }
        return $out;
    }

    /**
     * #2771 — carry a line-up projection back onto rows that are about to
     * be rewritten.
     *
     * An explicit `starter` from the completion form wins: the coach who
     * ticked a box on this save meant it, and a stale projection must not
     * overrule them. A row that says nothing about the line-up keeps
     * whatever the match prep put there.
     *
     * @param array<int, array<string, mixed>>                               $rows
     * @param array<int, array{lineup_role:string, position_played:string}>  $lineup
     * @return array<int, array<string, mixed>>
     */
    private static function withPreservedLineup( array $rows, array $lineup ): array {
        if ( ! $lineup ) return $rows;

        foreach ( $rows as $pid => $fields ) {
            $pid = (int) $pid;
            if ( ! isset( $lineup[ $pid ] ) ) continue;
            if ( array_key_exists( 'lineup_role', $fields ) ) continue;

            $rows[ $pid ]['lineup_role']     = $lineup[ $pid ]['lineup_role'];
            $rows[ $pid ]['position_played'] = $lineup[ $pid ]['position_played'];
        }

        return $rows;
    }

    /**
     * #2248 — the plan-status keys the activity edit form submits, mapped
     * to the stored `attendance_status`. Expected → present, Not coming →
     * absent, Maybe → excused (reused so no lookup seed / migration is
     * needed; the expected/actual `record_type` split keeps these rows out
     * of the actual-attendance KPIs).
     *
     * @return array<string, string>
     */
    private static function plannedStatusMap(): array {
        // #2909 — Title Case, matching AttendanceStatus. This writer used to
        // emit lowercase while the wizard and the grid wrote Title Case, which
        // is how the column ended up with two casings in the first place.
        return [
            'expected'   => AttendanceStatus::PRESENT,
            'not_coming' => AttendanceStatus::ABSENT,
            'maybe'      => AttendanceStatus::EXCUSED,
        ];
    }

    /**
     * #2248 — inverse of plannedStatusMap: the stored attendance_status
     * (case-insensitive; tolerates a renamed lookup by matching the seed
     * present/absent/excused keys) back to the plan-status key the form
     * uses. Anything unrecognised defaults to 'expected'.
     */
    private static function plannedStatusToKey( string $status ): string {
        // #2909 — compare canonically rather than by lowercasing both sides.
        // The map's values are AttendanceStatus members (Title Case) now, so a
        // lowercased needle would never match; folding the incoming value keeps
        // this tolerant of rows written in any casing, which is the point.
        $canonical = AttendanceStatus::normalise( $status );
        if ( $canonical === null ) return 'expected';

        foreach ( self::plannedStatusMap() as $key => $stored ) {
            if ( $canonical === $stored ) return $key;
        }
        return 'expected';
    }

    /**
     * #2248 — read the `planned[{pid}][status|note]` sub-resource off the
     * activity PUT and normalise it into rows the repository upserts as
     * `record_type='expected'`. Guests already in the plan are flagged so
     * the repository re-pins `is_guest`/`guest_player_id`.
     *
     * @return array<int, array{status:string, notes:string, is_guest:int}>
     */
    private static function planned_attendance_from_request( \WP_REST_Request $r ): array {
        $raw = $r['planned'] ?? [];
        if ( ! is_array( $raw ) ) return [];

        // Which planned players are guests? Look at the current expected
        // rows so we preserve the guest flag the form can't re-derive.
        $guest_ids = [];
        foreach ( self::repo()->plannedRosterForActivity( absint( $r['id'] ) ) as $row ) {
            if ( (int) ( $row->is_guest ?? 0 ) === 1 ) {
                $guest_ids[ (int) $row->player_id ] = true;
            }
        }

        $map = self::plannedStatusMap();
        $out = [];
        foreach ( $raw as $player_id => $fields ) {
            if ( ! is_array( $fields ) ) continue;
            $pid = absint( $player_id );
            if ( $pid <= 0 ) continue;
            $plan_key = sanitize_text_field( (string) ( $fields['status'] ?? 'expected' ) );
            $stored   = $map[ $plan_key ] ?? 'present';
            $out[ $pid ] = [
                'status'   => $stored,
                'notes'    => sanitize_text_field( (string) ( $fields['note'] ?? '' ) ),
                'is_guest' => isset( $guest_ids[ $pid ] ) ? 1 : 0,
            ];
        }
        return $out;
    }

    private static function request_has_attendance( \WP_REST_Request $r ): bool {
        return isset( $r['attendance'] ) || isset( $r['att'] );
    }

    /**
     * v4.20.5 (#1148) — Roster integrity guard on non-guest attendance.
     *
     * The activity edit form's attendance picker pre-loads every player
     * from every team the coach has access to (see
     * FrontendActivitiesManageView::renderForm — the JS helper hides
     * off-team rows visually, but the hidden form fields still submit).
     * For admin users that means the entire academy ships in the POST.
     * Historically this handler accepted any submitted player_id and
     * wrote it as is_guest = 0, producing rows where the player's
     * team_id != the activity's team_id without explicit guest
     * intention.
     *
     * Integrity rule now enforced here at the data-layer chokepoint:
     * non-guest attendance MUST be on the activity's roster. Any
     * submitted player_id whose current team_id != activity.team_id
     * is silently dropped (with a warning log so operators can see when
     * the UI is sending off-roster ids). The explicit guest path
     * (POST /sessions/{id}/guests → is_guest = 1) remains the only way
     * to record off-roster attendance.
     *
     * The activity form's multi-team pool optimisation is tracked as
     * a separate upstream fix (#1154).
     *
     * @param array<int, array{status:string, notes:string}> $rows
     * @return array<int, array{player_id:int, db_error:string}>
     */
    private static function write_attendance( int $activity_id, array $rows ): array {
        if ( ! $rows ) return [];
        $repo = self::repo();

        // Look up the activity's team once; off-roster filter keys off it.
        $activity_team_id = $repo->activityTeamId( $activity_id );

        $dropped = [];
        $failures = [];
        foreach ( $rows as $pid => $fields ) {
            $pid = (int) $pid;
            // Roster check — only when the activity has a team scope. Some
            // activities (legacy / club-wide) carry team_id = 0; those
            // accept any player as squad attendance.
            if ( $activity_team_id > 0 ) {
                $player_team_id = $repo->playerTeamId( $pid );
                if ( $player_team_id !== $activity_team_id ) {
                    $dropped[] = [ 'player_id' => $pid, 'player_team_id' => $player_team_id ];
                    continue;
                }
            }
            $insert = [
                'club_id'     => CurrentClub::id(),
                'activity_id' => $activity_id,
                'player_id'  => $pid,
                'status'     => $fields['status'],
                'notes'      => $fields['notes'],
                'is_guest'   => 0,
                // #2159 — roster attendance written here is canonical
                // recorded data. Set `record_type='actual'` explicitly
                // (matches the column default) so manual per-player
                // minutes land in the same `actual` / non-guest scope the
                // hardened minutes reports (#2158) sum.
                'record_type' => 'actual',
            ];
            // #1726 — match-completion direct entry. Present only for match
            // activities; leave the columns at their default otherwise.
            if ( array_key_exists( 'lineup_role', $fields ) ) {
                $insert['lineup_role'] = $fields['lineup_role'];
            }
            // #2771 — set alongside the role when a preserved projection
            // carried one; the completion form never submits a position.
            if ( array_key_exists( 'position_played', $fields ) ) {
                $insert['position_played'] = $fields['position_played'];
            }
            if ( array_key_exists( 'minutes_played', $fields ) ) {
                $insert['minutes_played'] = $fields['minutes_played'];
            }
            if ( $repo->insertAttendance( $insert ) === null ) {
                $failures[] = [ 'player_id' => $pid, 'db_error' => $repo->lastError() ];
            }
        }
        if ( $dropped ) {
            Logger::warning( 'session.attendance.dropped_off_roster', [
                'activity_id'      => $activity_id,
                'activity_team_id' => $activity_team_id,
                'dropped'          => $dropped,
            ] );
        }
        return $failures;
    }

    /**
     * #1636 — seed every active roster player as present for an activity
     * that was created already completed but ended up with no attendance.
     * No-op when the activity already has attendance rows (so a filled-in
     * form is never overwritten) or when the team / roster is empty.
     */
    private static function seedCompletedRosterPresent( int $activity_id, int $team_id ): void {
        if ( $activity_id <= 0 || $team_id <= 0 ) return;

        // No-op when the activity already has attendance rows.
        if ( self::repo()->countAttendance( $activity_id ) > 0 ) return;

        $players = \TT\Infrastructure\Query\QueryHelpers::get_players( $team_id );
        if ( ! $players ) return;

        $statuses = \TT\Infrastructure\Query\QueryHelpers::get_lookup_names( 'attendance_status' );
        $present  = $statuses[0] ?? 'Present';

        $rows = [];
        foreach ( $players as $pl ) {
            $rows[ (int) $pl->id ] = [ 'status' => $present, 'notes' => '' ];
        }
        self::write_attendance( $activity_id, $rows );
    }

    // Guest endpoints (#0026)

    /**
     * POST /sessions/{id}/guests — add a linked or anonymous guest to
     * a session's attendance. Body shape:
     *
     *   Linked   : { guest_player_id: <int>, status?: <str>, notes?: <str> }
     *   Anonymous: { guest_name: <str>, guest_age?: <int>,
     *                guest_position?: <str>, guest_notes?: <str>,
     *                status?: <str> }
     *
     * Application invariant: linked XOR anonymous. Both populated, or
     * neither, → 400.
     */
    public static function add_guest( \WP_REST_Request $r ) {
        $activity_id = absint( $r['id'] );
        if ( $activity_id <= 0 ) {
            return RestResponse::error( 'bad_id', __( 'Invalid activity id.', 'talenttrack' ), 400 );
        }
        $repo = self::repo();
        if ( ! $repo->activityExists( $activity_id ) ) {
            return RestResponse::error( 'not_found', __( 'Activity not found.', 'talenttrack' ), 404 );
        }

        $linked_id = absint( $r['guest_player_id'] ?? 0 );
        $name      = sanitize_text_field( (string) ( $r['guest_name'] ?? '' ) );
        $age_raw   = $r['guest_age'] ?? '';
        $age       = ( $age_raw === '' || $age_raw === null ) ? null : max( 0, min( 99, absint( $age_raw ) ) );
        $position  = sanitize_text_field( (string) ( $r['guest_position'] ?? '' ) );
        // #2909 — this used to lowercase, following a v3.110.4 convention
        // that the wizard and the grid never actually obeyed; that split is
        // how the column ended up holding two casings. AttendanceStatus is
        // the authority now, so fold to the canonical member instead.
        // Anything outside the five is an academy's own vocabulary and is
        // stored as given.
        $status    = sanitize_text_field( (string) ( $r['status'] ?? AttendanceStatus::PRESENT ) );
        if ( $status !== '' ) {
            $status = (string) ( AttendanceStatus::normalise( $status ) ?? $status );
        }
        $g_notes   = sanitize_textarea_field( (string) ( $r['guest_notes'] ?? '' ) );

        if ( $linked_id > 0 && $name !== '' ) {
            return RestResponse::error( 'invariant',
                __( 'A guest is either linked OR anonymous, not both.', 'talenttrack' ), 400 );
        }
        if ( $linked_id <= 0 && $name === '' ) {
            return RestResponse::error( 'invariant',
                __( 'Pick a player or enter a guest name.', 'talenttrack' ), 400 );
        }
        if ( $linked_id > 0 && ! $repo->playerExists( $linked_id ) ) {
            return RestResponse::error( 'bad_player', __( 'Linked player does not exist.', 'talenttrack' ), 400 );
        }

        $row = [
            'club_id'          => CurrentClub::id(),
            'activity_id'      => $activity_id,
            'player_id'       => null,
            'status'          => $status,
            'notes'           => '',
            'is_guest'        => 1,
            'guest_player_id' => $linked_id > 0 ? $linked_id : null,
            'guest_name'      => $linked_id > 0 ? null : $name,
            'guest_age'       => $linked_id > 0 ? null : $age,
            'guest_position'  => $linked_id > 0 ? null : ( $position !== '' ? $position : null ),
            'guest_notes'     => $linked_id > 0 ? null : ( $g_notes !== '' ? $g_notes : null ),
        ];
        $new_id = $repo->insertAttendance( $row );

        // v3.110.158 — defensive fallback for installs where
        // `tt_attendance.player_id` is still NOT NULL despite
        // migrations 0020 / 0101 / 0105 attempting to relax it.
        // Pilot symptom: "Column 'player_id' cannot be null". Some
        // shared-hosting installs lack ALTER privileges on the WP
        // DB user, so the schema change never lands. Guest rows
        // are identified by `is_guest = 1` regardless; downstream
        // reads filter on that flag and never JOIN on player_id
        // for guests (linked guests use `guest_player_id`, anon
        // guests use `guest_name`). Writing `player_id = 0` for
        // guests is safe: BIGINT UNSIGNED has 0 in range, real
        // players auto-increment from 1, no row ever has 0 as a
        // real `tt_players.id`. The 0 is unambiguous sentinel for
        // "no player on this guest row" on installs stuck NOT NULL.
        if ( $new_id === null ) {
            $err = $repo->lastError();
            if ( stripos( $err, "Column 'player_id' cannot be null" ) !== false
              || ( stripos( $err, 'player_id' ) !== false && stripos( $err, 'null' ) !== false ) ) {
                $row['player_id'] = 0;
                $new_id = $repo->insertAttendance( $row );
                if ( $new_id !== null ) {
                    Logger::warning( 'attendance.guest.add.player_id_zero_fallback', [
                        'activity_id'      => $activity_id,
                        'original_db_error'=> $err,
                    ] );
                }
            }
        }

        if ( $new_id === null ) {
            $err = $repo->lastError();
            Logger::error( 'attendance.guest.add.failed', [ 'db_error' => $err, 'activity_id' => $activity_id ] );
            // v3.110.143 — surface the actual db_error in the
            // user-visible message. Previously the message was
            // generic ("The guest could not be added.") and the
            // diagnostic db_error was buried in `details`, which
            // the JS doesn't surface. Pilot reported the failure
            // recurring with no diagnostic; bubbling the error lets
            // them paste it back so the root cause is identifiable.
            // The install is admin-only / pilot-stage so leaking
            // SQL-level error text to operators is acceptable.
            $msg = $err !== ''
                ? sprintf( __( 'The guest could not be added (database error: %s).', 'talenttrack' ), $err )
                : __( 'The guest could not be added.', 'talenttrack' );
            return RestResponse::error( 'db_error', $msg, 500, [ 'db_error' => $err ] );
        }
        return RestResponse::success( [ 'id' => $new_id ] + self::format_guest_row( self::find_attendance( $new_id ) ) );
    }

    /**
     * POST /activities/{id}/status — direct, confirmed status transition
     * (#2245). Body: `{ status: 'cancelled' | 'planned' }`.
     *
     * Only Cancel (→ cancelled) and Reopen (→ planned) go through here;
     * Completion is intentionally rejected because it runs through the
     * evaluation flow (which flips the status at its final save), never
     * as a bare field write.
     */
    public static function set_status( \WP_REST_Request $r ) {
        $id = absint( $r['id'] );
        if ( $id <= 0 ) return RestResponse::error( 'bad_id', __( 'Invalid activity id.', 'talenttrack' ), 400 );

        $status = sanitize_key( (string) ( $r['status'] ?? '' ) );
        $allowed = [ ActivityStatusKey::CANCELLED, ActivityStatusKey::PLANNED ];

        // #2407 — `completed` is accepted here ONLY when the guided
        // evaluation wizard is switched off for this user. With the wizard
        // on it stays rejected: completion is the wizard's final save, and
        // a second path would let an activity complete with no attendance
        // recorded. With the wizard off there is no such final save — the
        // grids write attendance in bulk and never touch status — so
        // without this an activity could never leave `planned` at all.
        $wizard_on = \TT\Modules\Activities\Services\ActivityCompletionResolver::wizardAvailable( get_current_user_id() );
        if ( ! $wizard_on ) {
            $allowed[] = ActivityStatusKey::COMPLETED;
        }

        if ( ! in_array( $status, $allowed, true ) ) {
            return RestResponse::error(
                'bad_status',
                $wizard_on
                    ? __( 'Only Cancel and Reopen are allowed here. Completing an activity runs through the evaluation flow.', 'talenttrack' )
                    : __( 'Only Complete, Cancel and Reopen are allowed here.', 'talenttrack' ),
                400
            );
        }

        if ( ! self::repo()->setStatus( $id, $status ) ) {
            return RestResponse::error( 'db_error', __( 'Could not update the activity status.', 'talenttrack' ), 500 );
        }
        do_action( 'tt_activity_status_changed', $id, $status );
        return RestResponse::success( [ 'id' => $id, 'activity_status_key' => $status ] );
    }

    /**
     * PATCH /activities/{id}/evaluation-skipped — toggle the
     * "evaluation skipped" flag introduced by v3.110.138's two-button
     * skip flow. Body: `{ skipped: 0|1 }`. Idempotent.
     */
    public static function patch_evaluation_skipped( \WP_REST_Request $r ) {
        $id = absint( $r['id'] );
        if ( $id <= 0 ) return RestResponse::error( 'bad_id', __( 'Invalid activity id.', 'talenttrack' ), 400 );
        $skipped = (int) (bool) $r['skipped'];
        if ( ! self::repo()->setEvaluationSkipped( $id, $skipped ) ) {
            return RestResponse::error( 'db_error', __( 'Could not update the activity.', 'talenttrack' ), 500 );
        }
        do_action( 'tt_activity_evaluation_skipped_changed', $id, $skipped );
        return RestResponse::success( [ 'id' => $id, 'evaluation_skipped' => $skipped ] );
    }

    /**
     * GET /activities/{id}/planned-attendance — the planned (expected)
     * roster for an activity: the players the coach ticked at creation
     * (`record_type='expected'`). Read-only; mirrors what the activity
     * detail page and the match-prep availability step consume. Empty
     * `roster` when no plan was captured.
     */
    public static function get_planned_attendance( \WP_REST_Request $r ) {
        $id = absint( $r['id'] );
        if ( $id <= 0 ) return RestResponse::error( 'bad_id', __( 'Invalid activity id.', 'talenttrack' ), 400 );

        // Club-scope the activity before exposing its roster.
        $repo = self::repo();
        if ( ! $repo->activityExists( $id ) ) return RestResponse::error( 'not_found', __( 'Activity not found.', 'talenttrack' ), 404 );

        $roster = $repo->plannedRosterForActivity( $id );
        $out = array_map( static function ( $row ) {
            $status = (string) ( $row->status ?? '' );
            return [
                'player_id'   => (int) ( $row->player_id ?? 0 ),
                'is_guest'    => (int) ( $row->is_guest ?? 0 ) === 1,
                'name'        => (string) ( $row->name ?? '' ),
                // #2248 — the raw attendance_status stored on the expected
                // row plus its plan meaning (expected / not_coming / maybe).
                'status'      => $status,
                'plan_status' => self::plannedStatusToKey( $status ),
                'notes'       => (string) ( $row->notes ?? '' ),
            ];
        }, $roster );

        return RestResponse::success( [
            'activity_id' => $id,
            'count'       => count( $out ),
            'roster'      => $out,
        ] );
    }

    /**
     * #2831 — the methodology principles this activity is linked to.
     *
     * Reads `ActivityPrinciples`, the same domain service the activity detail
     * card, the match-prep screen and the printed team sheet compose from, so
     * a non-WordPress front end draws the identical row (CLAUDE.md §4). The
     * `bucket` is the O/A/V colour class the pill uses; a consumer that wants
     * its own palette has the `code` it is derived from.
     */
    public static function get_principles( \WP_REST_Request $r ): \WP_REST_Response {
        $id = absint( $r['id'] );
        if ( $id <= 0 ) return RestResponse::error( 'bad_id', __( 'Invalid activity id.', 'talenttrack' ), 400 );

        if ( ! self::repo()->activityExists( $id ) ) {
            return RestResponse::error( 'not_found', __( 'Activity not found.', 'talenttrack' ), 404 );
        }

        $principles = \TT\Modules\Methodology\Services\ActivityPrinciples::forActivity( $id );

        return RestResponse::success( [
            'activity_id' => $id,
            'count'       => count( $principles ),
            'principles'  => $principles,
        ] );
    }

    /**
     * PATCH /attendance/{id} — partial update of an attendance row.
     * Today only edits guest fields (status, guest_notes, guest_name/
     * age/position) on guest rows; reuses the same handler so the
     * frontend doesn't need a parallel "edit roster row" pathway.
     */
    public static function patch_attendance( \WP_REST_Request $r ) {
        $id = absint( $r['id'] );
        if ( $id <= 0 ) return RestResponse::error( 'bad_id', __( 'Invalid attendance id.', 'talenttrack' ), 400 );
        $repo = self::repo();
        $row = $repo->findAttendanceRow( $id );
        if ( ! $row ) return RestResponse::error( 'not_found', __( 'Attendance row not found.', 'talenttrack' ), 404 );

        $update = [];
        if ( isset( $r['status'] ) )         $update['status']         = sanitize_text_field( (string) $r['status'] );
        if ( isset( $r['notes'] ) )          $update['notes']          = sanitize_text_field( (string) $r['notes'] );
        // Minutes-authority arbiter (match-execution rebuild): when an
        // execution row owns this activity, match-execution owns its
        // minutes. Manual minutes edits must go through the execution
        // surface's per-player override endpoint
        // (PATCH /match-execution/{activity_id}/minutes), not this raw
        // attendance path — refuse with 409 rather than silently drop, so
        // the coach is routed to the right place. A match with no execution
        // (never prepped/run on the sideline) is unaffected: manual minutes
        // entry works exactly as before.
        if ( array_key_exists( 'minutes_played', (array) $r->get_params() ) ) {
            $activity_id = (int) ( $row->activity_id ?? 0 );
            if ( $activity_id > 0
                && ( new \TT\Modules\MatchExecution\Repositories\MatchExecutionRepository() )->existsForActivity( $activity_id ) ) {
                return RestResponse::error(
                    'minutes_owned_by_execution',
                    __( 'Minutes for this match are managed on the match-execution screen. Set a per-player override there.', 'talenttrack' ),
                    409
                );
            }
            $mp = $r['minutes_played'];
            $update['minutes_played'] = ( $mp === '' || $mp === null ) ? null : max( 0, min( 200, absint( $mp ) ) );
        }
        if ( isset( $r['guest_notes'] ) )    $update['guest_notes']    = sanitize_textarea_field( (string) $r['guest_notes'] );
        if ( isset( $r['guest_name'] ) )     $update['guest_name']     = sanitize_text_field( (string) $r['guest_name'] );
        if ( isset( $r['guest_position'] ) ) $update['guest_position'] = sanitize_text_field( (string) $r['guest_position'] );
        if ( array_key_exists( 'guest_age', (array) $r->get_params() ) ) {
            $age_raw = $r['guest_age'];
            $update['guest_age'] = ( $age_raw === '' || $age_raw === null ) ? null : max( 0, min( 99, absint( $age_raw ) ) );
        }
        if ( empty( $update ) ) {
            return RestResponse::success( [ 'id' => $id, 'unchanged' => true ] );
        }
        if ( ! $repo->updateAttendanceRow( $id, $update ) ) {
            $err = $repo->lastError();
            Logger::error( 'attendance.patch.failed', [ 'db_error' => $err, 'id' => $id ] );
            return RestResponse::error( 'db_error',
                __( 'The attendance row could not be updated.', 'talenttrack' ), 500, [ 'db_error' => $err ] );
        }
        return RestResponse::success( self::format_guest_row( $repo->findAttendanceRow( $id ) ) );
    }

    /**
     * DELETE /attendance/{id} — remove an attendance row outright.
     * Used by the guest UI's "remove" affordance. Roster rows can
     * also be deleted this way (rare; the session PUT cycle is the
     * usual path).
     */
    public static function delete_attendance( \WP_REST_Request $r ) {
        $id = absint( $r['id'] );
        if ( $id <= 0 ) return RestResponse::error( 'bad_id', __( 'Invalid attendance id.', 'talenttrack' ), 400 );
        if ( ! self::repo()->deleteAttendanceRow( $id ) ) {
            return RestResponse::error( 'db_error',
                __( 'The attendance row could not be deleted.', 'talenttrack' ), 500 );
        }
        return RestResponse::success( [ 'deleted' => true, 'id' => $id ] );
    }

    private static function find_attendance( int $id ): ?object {
        // #1712 — thin delegate so existing callers keep working.
        return self::repo()->findAttendanceRow( $id );
    }

    /**
     * Shape a guest attendance row for JSON. Resolves the linked
     * player's display name when present so the frontend can append
     * the new row without an extra round-trip.
     *
     * @return array<string, mixed>
     */
    private static function format_guest_row( ?object $row ): array {
        if ( ! $row ) return [];
        $player_name = '';
        $home_team   = '';
        if ( ! empty( $row->guest_player_id ) ) {
            $hit = self::repo()->guestPlayerNameAndTeam( (int) $row->guest_player_id );
            if ( $hit ) {
                $player_name = trim( (string) $hit->first_name . ' ' . (string) $hit->last_name );
                $home_team   = (string) ( $hit->team_name ?? '' );
            }
        }
        return [
            'id'              => (int) $row->id,
            'activity_id'      => (int) $row->activity_id,
            'is_guest'        => (int) $row->is_guest,
            'guest_player_id' => $row->guest_player_id !== null ? (int) $row->guest_player_id : null,
            'guest_name'      => $row->guest_name,
            'guest_age'       => $row->guest_age !== null ? (int) $row->guest_age : null,
            'guest_position'  => $row->guest_position,
            'guest_notes'     => $row->guest_notes,
            'status'          => (string) $row->status,
            'player_name'     => $player_name,
            'home_team'       => $home_team,
        ];
    }
}
