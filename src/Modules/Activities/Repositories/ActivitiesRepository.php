<?php
namespace TT\Modules\Activities\Repositories;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Domain\Vocabularies\Lookups\ActivityStatusKey;
use TT\Domain\Vocabularies\Lookups\ActivityTypeKey;
use TT\Infrastructure\Archive\ArchiveRepository;
use TT\Infrastructure\Query\QueryHelpers;
use TT\Infrastructure\Tenancy\CurrentClub;

/**
 * ActivitiesRepository — shared read-path for `tt_activities` + the
 * roster attendance rows joined into the same view. Since the #1320
 * admin-CRUD slice it also owns the wp-admin write paths (create /
 * update / roster replace / delete), so `Admin\ActivitiesPage` no
 * longer touches `$wpdb` directly.
 *
 * v4.20.32 (#1190) — extracted from
 * `FrontendActivitiesManageView::loadSession()`/`loadAttendance()` and
 * `ActivityBriefPdfExporter::collect()`, which previously inlined
 * `$wpdb` queries with subtly different filter sets:
 *
 *   - on-screen view: demo-scope only on `tt_activities`; no `club_id`
 *     filter on the attendance JOIN.
 *   - PDF exporter: club_id-strict on `tt_activities` AND on the
 *     `tt_players` join; missing `is_guest = 0` (so guests leaked into
 *     the printed roster while the on-screen list excluded them).
 *
 * Audit 10 (#1184) flagged this as the same data-fork pattern #1059
 * established the fix for: share one repository call between the
 * on-screen view and the print/PDF surface so the two outputs can't
 * silently drift.
 *
 * `findById()` matches the on-screen `loadSession` filter shape; the
 * exporter's previous strict `club_id` filter on the activity is dropped
 * here because the WordPress install's single tenancy is already
 * enforced at the request boundary (CurrentClub resolution), and
 * sprinkling per-helper club_id WHEREs was the inconsistency #1188 just
 * resolved on `get_player()`. Demo-scope stays.
 *
 * `listRosterAttendance()` keeps the on-screen view's filters
 * (`is_guest = 0`) and the exporter's player-join shape so the printed
 * roster table no longer includes guests by default. Callers wanting
 * guests pass `includeGuests = true`.
 */
final class ActivitiesRepository {

    /**
     * Fetch an activity row + joined team name, applying the same
     * `archived_at IS NULL` + demo-scope filters the on-screen view uses.
     *
     * #1324 — when the activity links to a tournament
     * (`tournament_id` not null), the returned object also carries a
     * `tournament` sub-object: `{ id, name, start_date, end_date,
     * match_count }`. When `tournament_id` is null or the linked row
     * is archived/missing, `$row->tournament` is null.
     */
    public function findById( int $activity_id ): ?object {
        return $this->findByIdInternal( $activity_id, false );
    }

    /**
     * #2183 — archive-aware detail lookup. Identical join/shape to
     * findById() but WITHOUT the `archived_at IS NULL` filter, so the
     * detail view can render an archived activity (and offer Restore
     * on its header) instead of falling through to "not found". Active
     * rows resolve exactly as findById(); the caller branches on the
     * returned `archived_at`.
     */
    public function findByIdIncludingArchived( int $activity_id ): ?object {
        return $this->findByIdInternal( $activity_id, true );
    }

    private function findByIdInternal( int $activity_id, bool $include_archived ): ?object {
        global $wpdb;
        $p     = $wpdb->prefix;
        $scope = QueryHelpers::apply_demo_scope( 's', 'activity' );
        $archive_clause = $include_archived ? '' : 'AND s.archived_at IS NULL';
        // #2221 — surface the team-level last Spond sync timestamp so the
        // detail view can show honest data-freshness on Spond-sourced
        // activities. It lives on tt_teams (migration 0041), not per
        // activity; the label makes the team scope explicit. Resolution
        // stays in the domain layer (CLAUDE.md §4).
        /** @var object|null $row */
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT s.*, t.name AS team_name, t.spond_last_sync_at AS team_spond_last_sync_at
             FROM {$p}tt_activities s
             LEFT JOIN {$p}tt_teams t ON t.id = s.team_id AND t.club_id = s.club_id
             WHERE s.id = %d {$archive_clause} {$scope}",
            $activity_id
        ) );
        if ( ! $row ) return null;

        // #1324 — hydrate the linked tournament (if any).
        $row->tournament = null;
        $tournament_id   = isset( $row->tournament_id ) ? (int) $row->tournament_id : 0;
        if ( $tournament_id > 0 ) {
            $row->tournament = self::hydrateTournament( $tournament_id, (int) ( $row->club_id ?? 0 ) );
        }

        return $row;
    }

    /**
     * #1324 — narrow tournament shape: id, name, start_date, end_date,
     * match_count. Returns null when the tournament is missing or
     * archived (the activity stays in tournament-typed limbo with
     * `tournament_id` populated but no hydrated sub-object).
     */
    private static function hydrateTournament( int $tournament_id, int $club_id ): ?object {
        global $wpdb;
        $p = $wpdb->prefix;
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT id, name, start_date, end_date
               FROM {$p}tt_tournaments
              WHERE id = %d AND archived_at IS NULL AND club_id = %d
              LIMIT 1",
            $tournament_id, $club_id
        ) );
        if ( ! $row ) return null;
        $row->match_count = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$p}tt_tournament_matches WHERE tournament_id = %d",
            $tournament_id
        ) );
        return $row;
    }

    /**
     * Fetch roster attendance rows for an activity, joined to the
     * player's profile columns the print/PDF + on-screen views render.
     *
     * @param bool $include_guests Default false; mirrors the on-screen
     *                             attendance-table behaviour (which
     *                             lists guests in a separate panel).
     * @return list<object>
     */
    public function listRosterAttendance( int $activity_id, bool $include_guests = false, ?string $record_type = null ): array {
        global $wpdb;
        $p = $wpdb->prefix;
        $guest_filter = $include_guests ? '' : 'AND att.is_guest = 0';

        // #2862 — a player can hold both an `expected` row (written by the
        // planner) and an `actual` row (the register). With no filter at
        // all, this listed every squad member twice: a fifteen-player squad
        // rendered thirty rows under a summary reading "15 / 15 aanwezig".
        //
        // `$record_type` is explicit rather than hard-coded because the two
        // callers want different things and both are right: the attendance
        // panel wants the recorded roster on a completed activity, while the
        // wizard's pre-fill path reads the expected rows on purpose. Passing
        // null keeps the old unfiltered behaviour for anything not yet
        // migrated, but collapses duplicates to one row per player,
        // preferring the recorded one.
        $params = [ $activity_id ];
        if ( $record_type !== null && $record_type !== '' ) {
            $type_filter = 'AND att.record_type = %s';
            $params[]    = $record_type;
        } else {
            $type_filter = "AND att.id = (
                                SELECT pick.id
                                  FROM {$p}tt_attendance pick
                                 WHERE pick.activity_id = att.activity_id
                                   AND pick.player_id   = att.player_id
                                   AND pick.is_guest    = att.is_guest
                              ORDER BY ( pick.record_type = 'actual' ) DESC, pick.id DESC
                                 LIMIT 1
                            )";
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT att.id, att.player_id, att.status, att.notes AS att_notes,
                    att.is_guest, att.record_type,
                    pl.first_name, pl.last_name, pl.jersey_number, pl.preferred_positions
               FROM {$p}tt_attendance att
               JOIN {$p}tt_players pl ON pl.id = att.player_id
              WHERE att.activity_id = %d {$guest_filter} {$type_filter}
              ORDER BY pl.last_name ASC, pl.first_name ASC",
            ...$params
        ) );
        return is_array( $rows ) ? $rows : [];
    }

    /**
     * #1320 — recent-window list of activities a player attended.
     *
     * The player profile Activities tab is the caller; the sibling
     * surfaces that used to inline this JOIN-on-attendance shape with
     * subtly different filter sets read through
     * `listRecentCompletedForPlayer()` instead. Both live here so a
     * schema change to `tt_activities` / `tt_attendance` lands in one
     * place rather than in each view independently.
     *
     * Inner query selects the MOST RECENT $limit activities (DESC) — the
     * recent-window semantics the caller wants either way. The outer
     * SELECT reverses to ASC when asked, which is what makes a windowed
     * ascending list correct at all: a naive `ORDER BY … ASC LIMIT 25`
     * returns the oldest 25 of a player's career rather than the recent
     * 25 in reading order. #3277 moved the tab to DESC, so nothing asks
     * for the reversal today; it stays because that trap is the reason
     * the parameter exists.
     *
     * Filter shape:
     *   - `include_guests`     (default false) — attendance rows where
     *                          `is_guest = 1` joined via `guest_player_id`.
     *   - `record_types`       (default `['actual']`) — list of
     *                          `att.record_type` values. The player
     *                          dashboard wants `['actual']`; the
     *                          Activities tab also accepts planned
     *                          rows (it filters by `plan_state` instead).
     *   - `include_archived`   (default false) — when true, drops the
     *                          `a.archived_at IS NULL` filter.
     *   - `plan_states`        (optional) — list of `a.plan_state`
     *                          values to filter on. Empty = no filter.
     *   - `only_past_completed` (default false) — when true, completed
     *                          activities are only included if
     *                          `session_date <= CURDATE()` (the
     *                          Activities tab's "no future-dated
     *                          completed shows" rule).
     *
     * @param array{
     *     include_guests?: bool,
     *     record_types?: list<string>,
     *     include_archived?: bool,
     *     plan_states?: list<string>,
     *     only_past_completed?: bool
     * } $filters
     * @param 'ASC'|'DESC' $display_order
     * @return list<object>
     */
    public function listForPlayer( int $player_id, int $limit, string $display_order = 'DESC', array $filters = [] ): array {
        global $wpdb;
        $p = $wpdb->prefix;

        $display_order = strtoupper( $display_order ) === 'ASC' ? 'ASC' : 'DESC';
        $limit         = max( 1, min( 100, $limit ) );

        $include_guests     = ! empty( $filters['include_guests'] );
        // record_types: array_key_exists distinguishes "not set" (default
        // to ['actual']) from explicit null (skip the filter entirely).
        $record_types_key   = array_key_exists( 'record_types', $filters );
        $record_types       = $record_types_key
            ? ( is_array( $filters['record_types'] ) ? array_values( array_filter( array_map( 'strval', $filters['record_types'] ) ) ) : null )
            : [ 'actual' ];
        $include_archived   = ! empty( $filters['include_archived'] );
        $plan_states        = isset( $filters['plan_states'] ) && is_array( $filters['plan_states'] )
            ? array_values( array_filter( array_map( 'strval', $filters['plan_states'] ) ) )
            : [];
        $only_past_completed = ! empty( $filters['only_past_completed'] );

        $where  = [];
        $params = [];

        if ( $include_guests ) {
            $where[]  = '( att.player_id = %d OR att.guest_player_id = %d )';
            $params[] = $player_id;
            $params[] = $player_id;
        } else {
            $where[]  = 'att.player_id = %d';
            $where[]  = 'att.is_guest = 0';
            $params[] = $player_id;
        }

        if ( is_array( $record_types ) && ! empty( $record_types ) ) {
            $placeholders = implode( ',', array_fill( 0, count( $record_types ), '%s' ) );
            $where[]      = "att.record_type IN ({$placeholders})";
            $params       = array_merge( $params, $record_types );
        }

        if ( ! $include_archived ) {
            $where[] = 'a.archived_at IS NULL';
        }

        if ( ! empty( $plan_states ) ) {
            $placeholders = implode( ',', array_fill( 0, count( $plan_states ), '%s' ) );
            $where[]      = "a.plan_state IN ({$placeholders})";
            $params       = array_merge( $params, $plan_states );
        }

        if ( $only_past_completed ) {
            // Completed rows must be in the past; in-flight rows
            // (planned/scheduled) pass through regardless of date.
            $where[] = "( ( a.plan_state = 'completed' AND a.session_date <= CURDATE() ) OR a.plan_state IN ( 'planned', 'scheduled' ) )";
        }

        $where_sql = implode( ' AND ', $where );

        // #2862 — one row per ACTIVITY, not per attendance row.
        //
        // A player can hold two rows for the same activity: the `expected`
        // row the planner writes and the `actual` row recording what
        // happened. This list is a list of activities, so both rows joining
        // produced a duplicate entry — the profile's Activities tab showed
        // "Training 8.2" twice on the same date, four times over on one
        // pilot player.
        //
        // Filtering to `record_type = 'actual'` is NOT the fix here, even
        // though that is what the tab's badge count does: this tab
        // deliberately shows still-planned activities, which have only an
        // `expected` row, and filtering would empty that half of the list.
        //
        // So collapse to one attendance row per activity, preferring the
        // recorded one where both exist — the recorded row carries the real
        // status, and on a planned activity the expected row is all there is.
        $dedupe = "att.id = (
                       SELECT pick.id
                         FROM {$p}tt_attendance pick
                        WHERE pick.activity_id = att.activity_id
                          AND pick.player_id   = att.player_id
                          AND pick.is_guest    = att.is_guest
                     ORDER BY ( pick.record_type = 'actual' ) DESC, pick.id DESC
                        LIMIT 1
                   )";

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $sql = "SELECT * FROM (
                    SELECT a.id, a.title, a.session_date, a.activity_type_key, a.plan_state, a.team_id, a.activity_status_key,
                           att.id AS attendance_id, att.status, att.notes AS att_notes, att.is_guest, att.record_type
                      FROM {$p}tt_attendance att
                      JOIN {$p}tt_activities a ON a.id = att.activity_id
                     WHERE {$where_sql}
                       AND {$dedupe}
                  ORDER BY a.session_date DESC, a.id DESC
                     LIMIT %d
                ) recent
                ORDER BY recent.session_date {$display_order}, recent.id {$display_order}";

        $params[] = $limit;

        $rows = $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) );
        return is_array( $rows ) ? $rows : [];
    }

    /**
     * #1320 slice 2 — short list of completed activities the player
     * attended. Used by the behaviour-rating popovers + the player
     * status-capture surface as the "Related activity" dropdown source.
     *
     * Filters on `activity_status_key = 'completed'` (NOT `plan_state`
     * — those are different lifecycle axes per migration 0144) so
     * coaches don't tie a behaviour rating to an activity that hasn't
     * happened yet. Returns id / session_date / title only — the
     * dropdown doesn't need a full row.
     *
     * @return list<object>
     */
    public function listRecentCompletedForPlayer( int $player_id, int $limit ): array {
        global $wpdb;
        $p     = $wpdb->prefix;
        $limit = max( 1, min( 100, $limit ) );
        $rows  = $wpdb->get_results( $wpdb->prepare(
            "SELECT DISTINCT a.id, a.session_date, a.title
               FROM {$p}tt_activities a
               JOIN {$p}tt_attendance att ON att.activity_id = a.id
              WHERE att.player_id = %d
                AND a.activity_status_key = %s
                AND a.archived_at IS NULL
              ORDER BY a.session_date DESC
              LIMIT %d",
            $player_id, 'completed', $limit
        ) );
        return is_array( $rows ) ? $rows : [];
    }

    /**
     * #1358 — attendance summary for the player-profile "Attendance"
     * KPI: present rows vs. all actual attendance rows on completed,
     * non-archived activities in the trailing window. Matches the
     * "actual attendance" scope of the player Activities tab — only
     * completed activities count.
     *
     * v4.20.48 (#1227) — `att.record_type = 'actual'` filter so the
     * denominator doesn't inflate once #788 ship 2 lands with
     * pre-filled expected rows. Audit 7 (#1181).
     *
     * @return object|null `{present_n: int|null, total_n: int}`; null on query failure.
     */
    public function attendanceRateForPlayer( int $player_id, int $days = 30 ): ?object {
        if ( $player_id <= 0 ) return null;
        $days = max( 1, $days );

        global $wpdb;
        $p   = $wpdb->prefix;
        // #1382 — PLAYER-level attendance includes guest appearances: a
        // played-up player guesting for another team has an attendance
        // row keyed by either `player_id` (is_guest = 1) or
        // `guest_player_id` (player_id NULL). Match both and drop the
        // `is_guest = 0` filter so the profile KPI reflects everything
        // the player actually did. Team-level attendance (TeamKpisRepository,
        // AttendanceRankingQuery) keeps its guest-exclusive filter.
        //
        // #2521 — gated on the coach-set status, not `plan_state`.
        $completed = \TT\Infrastructure\Query\ActivityLifecycle::completedClause( 'a' );
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT
                SUM(CASE WHEN att.status = 'present' THEN 1 ELSE 0 END) AS present_n,
                COUNT(*) AS total_n
               FROM {$p}tt_attendance att
               JOIN {$p}tt_activities a ON a.id = att.activity_id
              WHERE ( att.player_id = %d OR att.guest_player_id = %d )
                AND att.club_id = %d
                AND att.record_type = 'actual'
                AND a.archived_at IS NULL
                AND {$completed}
                AND a.session_date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)",
            $player_id, $player_id, CurrentClub::id(), $days
        ) );
        return $row ?: null;
    }

    /**
     * Variant for the on-screen view's edit form, which keys
     * attendance rows by `player_id` for fast lookups. Excludes
     * guests by contract.
     *
     * @return array<int, object>
     */
    public function attendanceMapByPlayer( int $activity_id ): array {
        global $wpdb;
        $p = $wpdb->prefix;
        // v4.20.48 (#1227) — added `record_type = 'actual'` so the edit
        // form's per-player attendance map doesn't double up once #788
        // ship 2 lands with pre-filled expected rows. Audit 7 (#1181).
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$p}tt_attendance WHERE activity_id = %d AND is_guest = 0 AND record_type = 'actual'",
            $activity_id
        ) );
        $out = [];
        foreach ( $rows ?: [] as $r ) {
            if ( $r->player_id !== null ) $out[ (int) $r->player_id ] = $r;
        }
        return $out;
    }

    /**
     * #1726 — derived substitution counts for a completed match, from the
     * directly-entered per-player minutes + starter flags:
     *   - subs_off = starters who played less than the full match length
     *   - subs_on  = non-starters (bench) who played > 0 minutes
     * Single source of truth so the REST payload and the rendered form
     * agree. Rows without a minutes value don't count either way.
     *
     * @return array{subs_on:int, subs_off:int}
     */
    public function matchParticipationSummary( int $activity_id, int $match_length ): array {
        $subs_on  = 0;
        $subs_off = 0;
        foreach ( $this->attendanceMapByPlayer( $activity_id ) as $row ) {
            $minutes = $row->minutes_played ?? null;
            if ( $minutes === null || $minutes === '' ) continue;
            $minutes = (int) $minutes;
            $is_start = strtolower( (string) ( $row->lineup_role ?? '' ) ) === 'start';
            if ( $is_start ) {
                if ( $match_length > 0 && $minutes < $match_length ) $subs_off++;
            } elseif ( $minutes > 0 ) {
                $subs_on++;
            }
        }
        return [ 'subs_on' => $subs_on, 'subs_off' => $subs_off ];
    }

    /**
     * #1453 — the planned (expected) attendance roster for an activity:
     * the `record_type='expected'` rows seeded by the activity wizard's
     * AttendanceRosterStep (#1297) and the match-prep flow. One entry
     * per expected roster player and per expected guest, with the
     * display name resolved and ordered by name.
     *
     * Effective player id is `guest_player_id` for guests (the expected
     * row pins `player_id=0`, `is_guest=1`, `guest_player_id=<pid>`) and
     * `player_id` otherwise — see
     * `Wizards\Activity\ReviewStep::insertExpectedAttendance()`.
     *
     * Drives the "Expected attendance" section on the activity detail
     * page and the default seeding in the match-prep availability step,
     * so both surfaces (and REST) read planned attendance from one
     * place rather than re-querying `tt_attendance` in a view.
     *
     * #2248 — each row also carries its expected `status` (present /
     * absent / excused, mapping to Expected / Not coming / Maybe) and the
     * free-text `notes`, so the activity edit form can render an editable
     * plan and the detail card can summarise it.
     *
     * @return list<object> each: {player_id:int, is_guest:int, name:string, status:string, notes:string}
     */
    public function plannedRosterForActivity( int $activity_id ): array {
        if ( $activity_id <= 0 ) return [];

        global $wpdb;
        $p = $wpdb->prefix;
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT att.player_id, att.guest_player_id, att.is_guest,
                    att.status, att.notes,
                    COALESCE( gpl.first_name, pl.first_name ) AS first_name,
                    COALESCE( gpl.last_name,  pl.last_name )  AS last_name
               FROM {$p}tt_attendance att
               LEFT JOIN {$p}tt_players pl  ON pl.id  = att.player_id
               LEFT JOIN {$p}tt_players gpl ON gpl.id = att.guest_player_id
              WHERE att.activity_id = %d
                AND att.club_id = %d
                AND att.record_type = 'expected'
              ORDER BY last_name ASC, first_name ASC",
            $activity_id, CurrentClub::id()
        ) );

        $out = [];
        foreach ( $rows ?: [] as $r ) {
            $is_guest = (int) $r->is_guest === 1;
            $pid      = $is_guest ? (int) $r->guest_player_id : (int) $r->player_id;
            if ( $pid <= 0 ) continue;
            $name = trim( (string) ( $r->first_name ?? '' ) . ' ' . (string) ( $r->last_name ?? '' ) );
            if ( $name === '' ) $name = '#' . $pid;
            $out[] = (object) [
                'player_id' => $pid,
                'is_guest'  => $is_guest ? 1 : 0,
                'name'      => $name,
                'status'    => (string) ( $r->status ?? '' ),
                'notes'     => (string) ( $r->notes ?? '' ),
            ];
        }
        return $out;
    }

    /**
     * #2371 — the actual (recorded) attendance roster for the read-only
     * detail summary and the flat edit form's editable table. Starts FROM
     * the activity's current team players (so every squad member has a row
     * even with no attendance stored yet) and LEFT JOINs the recorded
     * `record_type='actual'`, non-guest row for its status + notes. Guests
     * are excluded — they're managed via the dedicated guest endpoints and
     * shown in their own section. Club-scoped (§4: the view composes, the
     * repository decides).
     *
     * @return array<int, object{player_id:int, name:string, jersey:int, status:string, notes:string}>
     */
    public function actualRosterForActivity( int $activity_id ): array {
        if ( $activity_id <= 0 ) return [];

        global $wpdb;
        $p    = $wpdb->prefix;
        $club = CurrentClub::id();

        $team_id = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT team_id FROM {$p}tt_activities WHERE id = %d AND club_id = %d",
            $activity_id, $club
        ) );
        if ( $team_id <= 0 ) return [];

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT pl.id AS player_id, pl.first_name, pl.last_name, pl.jersey_number,
                    att.status, att.notes
               FROM {$p}tt_players pl
               LEFT JOIN {$p}tt_attendance att
                      ON att.player_id  = pl.id
                     AND att.activity_id = %d
                     AND att.club_id     = %d
                     AND att.is_guest    = 0
                     AND att.record_type = 'actual'
              WHERE pl.team_id = %d
                AND pl.club_id = %d
                AND pl.archived_at IS NULL
              ORDER BY pl.last_name ASC, pl.first_name ASC",
            $activity_id, $club, $team_id, $club
        ) );

        $out = [];
        foreach ( $rows ?: [] as $r ) {
            $pid = (int) $r->player_id;
            if ( $pid <= 0 ) continue;
            $name = trim( (string) ( $r->first_name ?? '' ) . ' ' . (string) ( $r->last_name ?? '' ) );
            if ( $name === '' ) $name = '#' . $pid;
            $out[] = (object) [
                'player_id' => $pid,
                'name'      => $name,
                'jersey'    => (int) ( $r->jersey_number ?? 0 ),
                'status'    => (string) ( $r->status ?? '' ),
                'notes'     => (string) ( $r->notes ?? '' ),
            ];
        }
        return $out;
    }

    /**
     * #2248 — replace the planned (expected) attendance rows for an
     * activity with the supplied per-player status + notes. Club-scoped;
     * only `record_type='expected'` rows are touched, so recorded
     * (`actual`) attendance and guest visits are never disturbed. Guests
     * that were already in the plan are preserved by re-inserting them
     * with `is_guest=1` / `guest_player_id` pinned (mirrors the wizard's
     * ReviewStep::insertExpectedAttendance shape).
     *
     * @param array<int, array{status:string, notes:string, is_guest?:int}> $rows keyed by player id
     * @param array<int, array{lineup_role:string, position_played:string}>|null $preserve_lineup
     *        A projection snapshot taken before an earlier delete in the same
     *        request. Null means "read it here", which is right for every
     *        caller that has not already emptied the table.
     */
    public function replacePlannedAttendance( int $activity_id, array $rows, ?array $preserve_lineup = null ): void {
        if ( $activity_id <= 0 ) return;

        global $wpdb;
        $p    = $wpdb->prefix;
        $club = CurrentClub::id();

        // #2771 — the match prep writes its Starting XI through onto these
        // same rows (`lineup_role` / `position_played`), and the activity
        // detail's Line-up card reads nothing else. This rewrite is about
        // status and notes; it has no opinion about the line-up, so it
        // snapshots those two columns and carries them back rather than
        // silently discarding columns it does not own. Without this, saving
        // an activity emptied the Line-up card and the team-sheet PDF's
        // partition until somebody re-saved the prep.
        $lineup = $preserve_lineup ?? $this->lineupProjectionFor( $activity_id );

        // Wipe only the expected rows for this activity; actual + guest
        // actual rows are on other record_type / managed separately.
        $wpdb->delete( "{$p}tt_attendance", [
            'activity_id' => $activity_id,
            'club_id'     => $club,
            'record_type' => 'expected',
        ] );

        foreach ( $rows as $pid => $fields ) {
            $pid = (int) $pid;
            if ( $pid <= 0 ) continue;
            $is_guest = ! empty( $fields['is_guest'] );
            $insert = [
                'club_id'     => $club,
                'activity_id' => $activity_id,
                'player_id'   => $is_guest ? 0 : $pid,
                'is_guest'    => $is_guest ? 1 : 0,
                'status'      => (string) ( $fields['status'] ?? '' ),
                'notes'       => (string) ( $fields['notes'] ?? '' ),
                'record_type' => 'expected',
            ];
            if ( $is_guest ) {
                $insert['guest_player_id'] = $pid;
            }
            if ( ! $is_guest && isset( $lineup[ $pid ] ) ) {
                $insert['lineup_role']     = $lineup[ $pid ]['lineup_role'];
                $insert['position_played'] = $lineup[ $pid ]['position_played'];
            }
            $wpdb->insert( "{$p}tt_attendance", $insert );
        }
    }

    /**
     * #2771 — the line-up projection currently on an activity's attendance
     * rows, keyed by player id.
     *
     * Roster rows only: a guest has no `player_id` to key on, and match
     * prep never puts one in a Starting XI.
     *
     * `$record_type` must match the rows the caller is about to delete.
     * Snapshotting more widely than that duplicates a player across an
     * `expected` and an `actual` row, and `lineupForActivity()` reads both
     * — the Starting XI would list them twice.
     *
     * @param string|null $record_type 'expected', 'actual', or null for any.
     * @return array<int, array{lineup_role:string, position_played:string}>
     */
    public function lineupProjectionFor( int $activity_id, ?string $record_type = 'expected' ): array {
        if ( $activity_id <= 0 ) return [];

        global $wpdb;
        $p    = $wpdb->prefix;
        $club = CurrentClub::id();

        $sql = "SELECT player_id, lineup_role, position_played
                  FROM {$p}tt_attendance
                 WHERE activity_id = %d AND club_id = %d
                   AND is_guest = 0 AND lineup_role IS NOT NULL AND lineup_role <> ''";
        $args = [ $activity_id, $club ];
        if ( $record_type !== null ) {
            $sql   .= ' AND record_type = %s';
            $args[] = $record_type;
        }

        $rows = $wpdb->get_results( $wpdb->prepare( $sql, $args ) );
        if ( ! is_array( $rows ) ) return [];

        $out = [];
        foreach ( $rows as $row ) {
            $pid = (int) $row->player_id;
            if ( $pid <= 0 ) continue;
            $out[ $pid ] = [
                'lineup_role'     => (string) $row->lineup_role,
                'position_played' => (string) ( $row->position_played ?? '' ),
            ];
        }

        return $out;
    }

    /**
     * #1453 — the set of player ids planned (expected) for an activity,
     * keyed for O(1) membership tests. Roster players only (guests are
     * not part of the team's availability default in match prep).
     *
     * @return array<int, true>
     */
    public function plannedPlayerIdSet( int $activity_id ): array {
        $set = [];
        foreach ( $this->plannedRosterForActivity( $activity_id ) as $row ) {
            if ( (int) ( $row->is_guest ?? 0 ) === 1 ) continue;
            $set[ (int) $row->player_id ] = true;
        }
        return $set;
    }

    /**
     * #1320 admin-CRUD slice — the wp-admin activities list
     * (`Admin\ActivitiesPage::render_page`). Club-strict, archive-tab
     * aware, demo-scoped, optionally filtered to one activity type.
     *
     * `$type_key` is validated against the live `activity_type` lookup
     * here (not in the caller) so an unknown value silently means "no
     * type filter" — same lenient wp-admin semantics as before.
     *
     * `coach_name` rides along for parity with the historical inline
     * query even though the current list table doesn't render it.
     *
     * @param string $view 'active' | 'archived' | 'all' (re-sanitized here).
     * @return list<object>
     */
    public function listForAdmin( string $view = 'active', string $type_key = '', int $limit = 50 ): array {
        global $wpdb;
        $p = $wpdb->prefix;

        $view_clause = ArchiveRepository::filterClause( ArchiveRepository::sanitizeView( $view ), 'a' );
        $scope       = QueryHelpers::apply_demo_scope( 'a', 'activity' );
        $limit       = max( 1, min( 200, $limit ) );

        $type_clause = '';
        $params      = [ CurrentClub::id() ];
        if ( $type_key !== '' ) {
            $valid_types = array_map(
                static fn( $row ) => (string) $row->name,
                QueryHelpers::get_lookups( 'activity_type' )
            );
            if ( in_array( $type_key, $valid_types, true ) ) {
                $type_clause = ' AND a.activity_type_key = %s';
                $params[]    = $type_key;
            }
        }
        $params[] = $limit;

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT a.*, t.name AS team_name, u.display_name AS coach_name
               FROM {$p}tt_activities a
               LEFT JOIN {$p}tt_teams t ON a.team_id = t.id AND t.club_id = a.club_id
               LEFT JOIN {$wpdb->users} u ON a.coach_id = u.ID
              WHERE {$view_clause}
                AND a.club_id = %d
                {$scope}
                {$type_clause}
           ORDER BY a.session_date DESC
              LIMIT %d",
            ...$params
        ) );
        return is_array( $rows ) ? $rows : [];
    }

    /**
     * #1320 — the activities-manage surface list, moved out of
     * `FrontendActivitiesManageView::loadActivitiesForList()`. Club- and
     * demo-scoped, applies the same coach-scope guard as the REST
     * `list_sessions` endpoint: personas with global `activities` read
     * (scout, head_of_development, academy_admin) see every team;
     * everyone else is restricted to the teams they head-coach. Optional
     * team / type / date-window filters. No LIMIT — the surface renders
     * the full filtered set (pilot volumes are tractable).
     *
     * @return object[]
     */
    public function listForManageSurface( int $team_filter, string $type_filter, int $user_id, string $date_from = '', string $date_to = '', bool $show_cancelled = false, string $archived_view = 'active', int $player_id = 0 ): array {
        global $wpdb;
        $p = $wpdb->prefix;

        // #1555 — Active / Archived status filter, mirroring the
        // goals/players/teams archive lifecycle. 'active' (default) keeps
        // the historical behaviour of hiding archived rows.
        // #2023 — through filterClause (alias 's') so every state also hides
        // trashed (recycle-bin) rows; the query joins tt_teams (also
        // archivable), so the alias keeps trashed_at unambiguous.
        $archived_view = \TT\Infrastructure\Archive\ArchiveRepository::sanitizeView( $archived_view );
        $where  = [ 's.club_id = %d', \TT\Infrastructure\Archive\ArchiveRepository::filterClause( $archived_view, 's' ) ];
        $params = [ CurrentClub::id() ];

        // #1862 — cancelled activities are noise on the schedule, so hide
        // them by default. A cancelled row carries 'cancelled' in EITHER
        // the newer plan_state taxonomy OR the older activity_status_key
        // one (pilot installs mix both), so exclude on both. The manage
        // list opts back in with the "show cancelled" filter.
        if ( ! $show_cancelled ) {
            $where[]  = "( LOWER(COALESCE(s.plan_state, '')) <> %s AND LOWER(COALESCE(s.activity_status_key, '')) <> %s )";
            $params[] = ActivityStatusKey::CANCELLED;
            $params[] = ActivityStatusKey::CANCELLED;
        }

        if ( $date_from !== '' ) {
            $where[]  = 's.session_date >= %s';
            $params[] = $date_from;
        }
        if ( $date_to !== '' ) {
            $where[]  = 's.session_date <= %s';
            $params[] = $date_to;
        }

        $scope = QueryHelpers::apply_demo_scope( 's', 'activity' );

        // Coach-scope guard — mirrors REST list_sessions. Global-read
        // personas bypass; everyone else is restricted to their teams.
        if ( ! QueryHelpers::user_has_global_entity_read( $user_id, 'activities' ) ) {
            $coach_teams = QueryHelpers::get_teams_for_coach( $user_id );
            if ( ! $coach_teams ) {
                return [];
            }
            $team_ids     = array_map( static function ( $t ) { return (int) $t->id; }, $coach_teams );
            $placeholders = implode( ',', array_fill( 0, count( $team_ids ), '%d' ) );
            $where[]      = "s.team_id IN ($placeholders)";
            $params       = array_merge( $params, $team_ids );
        }

        if ( $team_filter > 0 ) {
            $where[]  = 's.team_id = %d';
            $params[] = $team_filter;
        }
        if ( $type_filter !== '' ) {
            $where[]  = 's.activity_type_key = %s';
            $params[] = $type_filter;
        }

        // #2185 — attendance-report drill-down: narrow to activities where
        // THIS player has a recorded (non-guest, actual) attendance row, so
        // the filtered list matches the per-player "Activities" count on
        // FrontendAttendancePlayerReportView. EXISTS keeps one row per
        // activity (no fan-out) and mirrors the report's attendance filters.
        if ( $player_id > 0 ) {
            $where[]  = "EXISTS ( SELECT 1 FROM {$p}tt_attendance att
                WHERE att.activity_id = s.id
                  AND att.player_id = %d
                  AND att.is_guest = 0
                  AND att.record_type = 'actual' )";
            $params[] = $player_id;
        }

        $where_sql = implode( ' AND ', $where ) . ' ' . $scope;

        $sql = "SELECT s.*, t.name AS team_name
                FROM {$p}tt_activities s
                LEFT JOIN {$p}tt_teams t ON t.id = s.team_id AND t.club_id = s.club_id
                WHERE {$where_sql}
                ORDER BY s.session_date ASC, s.id ASC";

        $rows = $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) );
        return is_array( $rows ) ? $rows : [];
    }

    /**
     * #1320 admin-CRUD slice — single activity for the wp-admin edit
     * form. Unlike `findById` (the frontend detail shape) this is
     * club-strict, ignores demo scope, and DOES return archived rows —
     * the admin archive tab links straight into the edit form.
     */
    public function findForAdmin( int $activity_id ): ?object {
        if ( $activity_id <= 0 ) return null;
        global $wpdb;
        $p   = $wpdb->prefix;
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$p}tt_activities WHERE id = %d AND club_id = %d",
            $activity_id, CurrentClub::id()
        ) );
        return $row ?: null;
    }

    /**
     * #1320 admin-CRUD slice — guest attendance rows for one activity,
     * joined to the linked player + their team for display. Read-only
     * panel on the wp-admin edit form (#0077 M2 parity); guest CRUD
     * stays on the frontend modal flow.
     *
     * @return list<object>
     */
    public function listGuestAttendance( int $activity_id ): array {
        global $wpdb;
        $p    = $wpdb->prefix;
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT att.*, pl.first_name, pl.last_name, t.name AS guest_team_name
               FROM {$p}tt_attendance att
               LEFT JOIN {$p}tt_players pl ON pl.id = att.guest_player_id AND pl.club_id = att.club_id
               LEFT JOIN {$p}tt_teams   t  ON t.id = pl.team_id           AND t.club_id  = pl.club_id
              WHERE att.activity_id = %d AND att.is_guest = 1 AND att.club_id = %d
              ORDER BY att.id ASC",
            $activity_id, CurrentClub::id()
        ) );
        return is_array( $rows ) ? $rows : [];
    }

    /**
     * #1320 admin-CRUD slice — insert a new activity. The caller
     * supplies the sanitized column map (including
     * `activity_source_key`); `club_id` is stamped here.
     *
     * @return int|null New activity id, or null when the insert failed
     *                  (read `lastError()` for the DB message).
     */
    public function create( array $data ): ?int {
        global $wpdb;
        $p               = $wpdb->prefix;
        $data['club_id'] = CurrentClub::id();
        // #1471 — stamp the creator (audit, distinct from coach_id).
        // Forward-only: a system / cron write (uid 0) leaves it null.
        $uid = get_current_user_id();
        if ( $uid > 0 ) {
            $data['created_by'] = $uid;
        }
        $ok = $wpdb->insert( "{$p}tt_activities", $data );
        if ( $ok === false ) return null;

        $id = (int) $wpdb->insert_id;

        /**
         * #2731 — an activity row was written.
         *
         * Distinct from `tt_activity_completed`, which is about one
         * transition; this fires for any create or edit, which is what the
         * alerts engine needs to notice that a coach was assigned or a
         * session was moved.
         *
         * @param int   $activity_id
         * @param array $data Columns as written.
         */
        do_action( 'tt_activity_saved', $id, $data );

        return $id;
    }

    /**
     * #1320 admin-CRUD slice — club-scoped update of one activity.
     *
     * @return bool False only on a DB error ("0 rows changed" is true,
     *              matching the historical `$ok !== false` check).
     */
    public function update( int $activity_id, array $data ): bool {
        global $wpdb;
        $p = $wpdb->prefix;
        // #1471 — record who last changed the activity (audit footer).
        $uid = get_current_user_id();
        if ( $uid > 0 ) {
            $data['updated_by'] = $uid;
        }
        // #3081 — the write is the only place that still knows what the
        // row said before it. Read it only when the payload could be a
        // cancellation, so the common edit keeps its single query.
        $was_cancelled = $this->wasCancelledBefore( $activity_id, $data );
        $ok = $wpdb->update(
            "{$p}tt_activities",
            $data,
            [ 'id' => $activity_id, 'club_id' => CurrentClub::id() ]
        ) !== false;

        /** This event documented on {@see self::create()}. */
        if ( $ok ) do_action( 'tt_activity_saved', $activity_id, $data );

        if ( $ok && $was_cancelled === false ) $this->announceCancellation( $activity_id );

        return $ok;
    }

    /**
     * #3081 — did this row already read `cancelled` before the write?
     *
     * Returns null when the payload is not a cancellation at all, which
     * is the signal to skip the transition check entirely; false when the
     * payload cancels a row that was not cancelled (the transition worth
     * announcing); true when it re-saves one that already was.
     *
     * @param array<string,mixed> $data Columns about to be written.
     */
    private function wasCancelledBefore( int $activity_id, array $data ): ?bool {
        if ( $activity_id <= 0 ) return null;
        if ( ! array_key_exists( 'activity_status_key', $data ) ) return null;
        if ( (string) $data['activity_status_key'] !== ActivityStatusKey::CANCELLED ) return null;
        return $this->statusKey( $activity_id ) === ActivityStatusKey::CANCELLED;
    }

    /**
     * #3081 — the activity's current `activity_status_key` (club-scoped),
     * or ''. Sibling of `planState()`; the two lifecycle columns each
     * need their own pre-write read.
     */
    public function statusKey( int $activity_id ): string {
        if ( $activity_id <= 0 ) return '';
        global $wpdb;
        $p = $wpdb->prefix;
        return (string) $wpdb->get_var( $wpdb->prepare(
            "SELECT activity_status_key FROM {$p}tt_activities WHERE id = %d AND club_id = %d",
            $activity_id, CurrentClub::id()
        ) );
    }

    /**
     * #3081 — say that an activity was cancelled.
     *
     * Fired from the repository for the reason `announceAttendanceChange()`
     * gives above: cancellation has two independent write paths —
     * `setStatus()` behind the detail view's Cancel button, and `update()`
     * behind both the REST edit form and the wp-admin activities page —
     * and an event only one of them emits is worse than none. A family told
     * about half the cancellations is the failure this exists to prevent.
     *
     * Fires on the transition, never on the state: both callers establish
     * that the row did not already read `cancelled`, so re-saving a
     * cancelled activity does not re-notify a parent who was told
     * yesterday.
     */
    private function announceCancellation( int $activity_id ): void {
        if ( $activity_id <= 0 ) return;

        /**
         * An activity moved into `cancelled` from a non-cancelled state.
         *
         * Carries the id only; a listener that needs the title, date or
         * team re-reads. The row has already been written when this
         * fires, so the read sees the cancelled state.
         *
         * @param int $activity_id
         */
        do_action( 'tt_activity_cancelled', $activity_id );
    }

    /**
     * #1320 admin-CRUD slice — wipe + rewrite the roster attendance
     * rows for an activity. Only touches `is_guest = 0` rows: guest
     * rows are managed via the frontend / REST endpoints and survive a
     * legacy admin save cycle (#0026).
     *
     * @param array<int, array{status: string, notes: string}> $entries
     *                  Keyed by player id; values already sanitized.
     * @return array<int, string> Player id => DB error message, for
     *                  each row whose insert failed (caller logs).
     */
    public function replaceRosterAttendance( int $activity_id, array $entries ): array {
        global $wpdb;
        $p = $wpdb->prefix;
        $wpdb->delete( "{$p}tt_attendance", [ 'activity_id' => $activity_id, 'is_guest' => 0, 'club_id' => CurrentClub::id() ] );
        $failed = [];
        foreach ( $entries as $player_id => $entry ) {
            $ok = $wpdb->insert( "{$p}tt_attendance", [
                'activity_id' => $activity_id,
                'player_id'   => (int) $player_id,
                'status'      => (string) ( $entry['status'] ?? 'Present' ),
                'notes'       => (string) ( $entry['notes'] ?? '' ),
                'is_guest'    => 0,
                'club_id'     => CurrentClub::id(),
            ] );
            if ( $ok === false ) $failed[ (int) $player_id ] = (string) $wpdb->last_error;
        }
        $this->announceAttendanceChange( $activity_id );
        return $failed;
    }

    /**
     * #2731 — say that an activity's attendance rows changed.
     *
     * Fired from every method here that writes `tt_attendance`, rather than
     * from the callers, because there are eight of those across REST, the
     * grids, wp-admin and the tournament path, and an event only some of
     * them emit is worse than none: the alert would clear when attendance
     * is recorded one way and linger when it is recorded another, which
     * reads as a bug rather than as staleness.
     *
     * @param int $activity_id
     */
    private function announceAttendanceChange( int $activity_id ): void {
        if ( $activity_id <= 0 ) return;

        /**
         * Attendance rows for an activity were created, changed or removed.
         *
         * Says nothing about what the rows now contain — a listener that
         * cares must re-read. That is deliberate: the write paths range
         * from one cell of a grid to a whole roster rewrite, and a payload
         * describing all of them would be a payload nobody could trust.
         *
         * @param int $activity_id
         */
        do_action( 'tt_activity_attendance_changed', $activity_id );
    }

    /**
     * #1320 admin-CRUD slice — hard-delete an activity and ALL its
     * attendance rows (roster + guests). Club-scoped.
     *
     * #1712 — returns the activity-delete result so the REST controller
     * can surface a DB failure (the wp-admin caller ignores the return).
     *
     * @return bool False only when the activity delete hit a DB error.
     */
    public function deleteWithAttendance( int $activity_id ): bool {
        global $wpdb;
        $p = $wpdb->prefix;
        $wpdb->delete( "{$p}tt_attendance", [ 'activity_id' => $activity_id, 'club_id' => CurrentClub::id() ] );
        $ok = $wpdb->delete( "{$p}tt_activities", [ 'id' => $activity_id, 'club_id' => CurrentClub::id() ] ) !== false;
        // Fired after the activity is gone on purpose: the alert definitions
        // then return nothing for it, which is what resolves the occurrences
        // a deleted activity left behind.
        $this->announceAttendanceChange( $activity_id );
        return $ok;
    }

    /**
     * #1618 — attendance status breakdown for the activity detail card.
     *
     * Aggregates the actual attendance rows (`record_type='actual'`,
     * `is_guest=0`) of players still on the team's current active
     * roster into per-status counts, plus the roster size, the present
     * count and the present percentage. The view composes the bar +
     * legend from this shape; the SQL stays out of the view (CLAUDE.md
     * §4). `LOWER(a.status)` normalises legacy capitalised rows into
     * the same bucket as current lowercase rows (same case-handling as
     * v3.110.98's list-view fix).
     *
     * @return object {
     *   roster_size:int, total:int, present:int, pct:int,
     *   by_status:array<string,int>  // lowercase status key => count
     * }  roster_size 0 means the team has no active roster.
     */
    public function attendanceBreakdownForActivity( int $activity_id, int $team_id ): object {
        global $wpdb;
        $p       = $wpdb->prefix;
        $club_id = CurrentClub::id();
        $empty   = (object) [ 'roster_size' => 0, 'total' => 0, 'present' => 0, 'pct' => 0, 'by_status' => [] ];
        if ( $activity_id <= 0 || $team_id <= 0 ) return $empty;

        $roster_size = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$p}tt_players
              WHERE team_id = %d AND club_id = %d AND status = 'active'",
            $team_id, $club_id
        ) );
        if ( $roster_size === 0 ) return $empty;

        // #2522 — recorded attendance only. The planned roster lives in the
        // same table under `record_type='expected'` and carries a real
        // status (Expected → present, Not coming → absent, Maybe → excused;
        // see ActivitiesRestController::plannedStatusMap), so without this
        // predicate an activity with both a plan and a register reported
        // every planned player a second time — "28 / 15 present".
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT LOWER(a.status) AS status, COUNT(*) AS cnt
               FROM {$p}tt_attendance a
               INNER JOIN {$p}tt_players pl ON pl.id = a.player_id AND pl.club_id = a.club_id
              WHERE a.activity_id = %d AND a.is_guest = 0 AND a.club_id = %d
                AND a.record_type = 'actual'
                AND pl.team_id = %d AND pl.status = 'active'
              GROUP BY LOWER(a.status)",
            $activity_id, $club_id, $team_id
        ) );

        $by_status = [];
        $total     = 0;
        foreach ( (array) $rows as $r ) {
            $key = strtolower( (string) ( $r->status ?? '' ) );
            if ( $key === '' ) continue;
            $cnt              = (int) ( $r->cnt ?? 0 );
            $by_status[ $key ] = $cnt;
            $total           += $cnt;
        }
        $present = (int) ( $by_status['present'] ?? 0 );
        $pct     = (int) round( ( $present / $roster_size ) * 100 );
        if ( $pct > 100 ) $pct = 100;

        return (object) [
            'roster_size' => $roster_size,
            'total'       => $total,
            'present'     => $present,
            'pct'         => $pct,
            'by_status'   => $by_status,
        ];
    }

    /**
     * #1618 — match-day line-up for the activity detail card.
     *
     * Reads the `lineup_role` / `position_played` columns the match-prep
     * flow writes onto `tt_attendance` (see
     * `MatchPrep\Rest\MatchPrepRestController::upsertAttendanceLineup`)
     * and groups them into Starting XI + Bench, each decorated with the
     * player's name, jersey number and the position played (falling back
     * to the first preferred position). Roster rows only (no guests).
     *
     * Keeping the grouping in the repository means REST and the rendered
     * card read the same line-up shape (CLAUDE.md §4).
     *
     * @return object {
     *   starting: list<object{player_id:int,name:string,jersey:string,position:string}>,
     *   bench:    list<object{...}>
     * }
     */
    public function lineupForActivity( int $activity_id ): object {
        $out = (object) [ 'starting' => [], 'bench' => [] ];
        if ( $activity_id <= 0 ) return $out;

        global $wpdb;
        $p       = $wpdb->prefix;
        $club_id = CurrentClub::id();
        $rows    = $wpdb->get_results( $wpdb->prepare(
            "SELECT a.player_id, a.lineup_role, a.position_played,
                    pl.first_name, pl.last_name, pl.jersey_number, pl.preferred_positions
               FROM {$p}tt_attendance a
               INNER JOIN {$p}tt_players pl ON pl.id = a.player_id AND pl.club_id = a.club_id
              WHERE a.activity_id = %d AND a.is_guest = 0 AND a.club_id = %d
                AND a.lineup_role IN ( 'start', 'bench' )
              ORDER BY ( pl.jersey_number IS NULL ), pl.jersey_number ASC,
                       pl.last_name ASC, pl.first_name ASC",
            $activity_id, $club_id
        ) );

        foreach ( (array) $rows as $r ) {
            $pid  = (int) ( $r->player_id ?? 0 );
            if ( $pid <= 0 ) continue;
            $name = trim( (string) ( $r->first_name ?? '' ) . ' ' . (string) ( $r->last_name ?? '' ) );
            if ( $name === '' ) $name = '#' . $pid;

            $position = trim( (string) ( $r->position_played ?? '' ) );
            if ( $position === '' ) {
                // #2196 — `preferred_positions` is stored as a JSON array
                // (e.g. `["LW"]` / `["LW","CDM"]`). Decode it so the bench
                // chip shows the clean short code(s), never the raw JSON.
                // Compact surface → keep the SHORT abbreviation (§2155).
                $position = self::primaryPreferredPositions(
                    (string) ( $r->preferred_positions ?? '' )
                );
            }

            $entry = (object) [
                'player_id' => $pid,
                'name'      => $name,
                'jersey'    => (string) ( $r->jersey_number ?? '' ),
                'position'  => $position,
            ];
            if ( (string) $r->lineup_role === 'start' ) {
                $out->starting[] = $entry;
            } else {
                $out->bench[] = $entry;
            }
        }
        return $out;
    }

    /**
     * #2220 — compact stat strip for the activity detail panel. Returns
     * the key numbers the detail header shows above the section cards,
     * derived entirely from data the detail surface already reads
     * (attendance breakdown + line-up + the resolved match length). The
     * view composes these into cells; no derivation lives in the view
     * (CLAUDE.md §4).
     *
     * Match:    present / roster · substitutes (bench count) · match length (min).
     * Training: present / roster · duration (min, from the time window).
     *
     * Each value degrades to null when unavailable so the strip can skip
     * an empty cell rather than show a placeholder.
     *
     * #2523 — the present cell is only meaningful once the coach has marked
     * the activity completed. Attendance can legitimately be entered ahead
     * of that (the entry grid writes a whole column at once), but reporting
     * "15 / 15 present" on a session still showing STATUS: Planned states
     * something that has not happened. The Attendance card below the strip
     * has always been gated this way; the strip now matches it. Pass the
     * activity's `activity_status_key`; an empty string keeps the cell (the
     * caller does not know the status).
     *
     * @return object {
     *   present:?int, roster_size:?int, substitutes:?int,
     *   match_length_minutes:?int, duration_minutes:?int
     * }
     */
    public function statStripForActivity( int $activity_id, int $team_id, bool $is_match, string $start_time, string $end_time, int $explicit_match_length = 0, string $status_key = '' ): object {
        $out = (object) [
            'present'              => null,
            'roster_size'         => null,
            'substitutes'          => null,
            'match_length_minutes' => null,
            'duration_minutes'     => null,
        ];
        if ( $activity_id <= 0 ) return $out;

        // Present / roster — reuse the same breakdown the Attendance card
        // reads, under the same "has it happened?" gate (#2523).
        $status_key   = strtolower( trim( $status_key ) );
        $has_happened = $status_key === '' || $status_key === ActivityStatusKey::COMPLETED;
        if ( $team_id > 0 && $has_happened ) {
            $bd = $this->attendanceBreakdownForActivity( $activity_id, $team_id );
            if ( (int) $bd->roster_size > 0 ) {
                $out->present     = (int) $bd->present;
                $out->roster_size = (int) $bd->roster_size;
            }
        }

        if ( $is_match ) {
            // Substitutes — bench count from the captured line-up.
            $lineup = $this->lineupForActivity( $activity_id );
            if ( $lineup->starting !== [] || $lineup->bench !== [] ) {
                $out->substitutes = count( $lineup->bench );
            }
            // Match length — stored per-match override, else resolved from
            // the age-group config (MatchPrep domain service).
            if ( class_exists( '\\TT\\Modules\\MatchPrep\\Services\\MatchLengthResolver' ) ) {
                $minutes = ( new \TT\Modules\MatchPrep\Services\MatchLengthResolver() )
                    ->matchMinutesForActivity( $activity_id, $explicit_match_length > 0 ? (int) ceil( $explicit_match_length / 2 ) : 0 );
                if ( $minutes > 0 ) $out->match_length_minutes = $minutes;
            } elseif ( $explicit_match_length > 0 ) {
                $out->match_length_minutes = $explicit_match_length;
            }
        } else {
            // Duration — minutes between start + end of the time window.
            $dur = self::minutesBetween( $start_time, $end_time );
            if ( $dur > 0 ) $out->duration_minutes = $dur;
        }

        return $out;
    }

    /**
     * #2220 — minutes between two `HH:MM[:SS]` clock times on the same
     * day. Returns 0 when either is missing / unparseable or the window
     * is non-positive.
     */
    private static function minutesBetween( string $start, string $end ): int {
        if ( ! preg_match( '/^(\d{1,2}):(\d{2})/', $start, $s ) ) return 0;
        if ( ! preg_match( '/^(\d{1,2}):(\d{2})/', $end, $e ) )   return 0;
        $start_min = (int) $s[1] * 60 + (int) $s[2];
        $end_min   = (int) $e[1] * 60 + (int) $e[2];
        $diff      = $end_min - $start_min;
        return $diff > 0 ? $diff : 0;
    }

    /**
     * #2196 — normalise a stored `preferred_positions` value into clean
     * short position code(s) for the compact line-up bench chip. The
     * column holds a JSON array (`["LW"]`, `["LW","CDM"]`) on modern rows
     * and a bare comma-separated string on legacy ones; both degrade to a
     * comma-joined list of trimmed codes. Empty / unparseable input
     * returns an empty string so the chip renders nothing rather than raw
     * JSON. Short codes are kept intentionally — this is a compact surface
     * (§2155's long-form switch was only for profiles/cards/dashboards).
     */
    private static function primaryPreferredPositions( string $raw ): string {
        $raw = trim( $raw );
        if ( $raw === '' ) return '';
        $decoded = json_decode( $raw, true );
        $codes   = is_array( $decoded ) ? $decoded : explode( ',', $raw );
        $parts   = array_values( array_filter(
            array_map( static fn ( $v ): string => trim( (string) $v ), $codes ),
            static fn ( string $v ): bool => $v !== ''
        ) );
        return implode( ', ', $parts );
    }

    /**
     * Last DB error message, for callers surfacing write failures
     * without reaching into `$wpdb` themselves.
     */
    public function lastError(): string {
        global $wpdb;
        return (string) $wpdb->last_error;
    }

    /**
     * #1614 — upcoming-activity counts per team for the teams-list cards.
     *
     * Counts non-archived, non-completed/cancelled activities whose
     * `session_date` falls in the window `[today, today + $days)` for
     * each of the supplied teams. Returns a map keyed by `team_id`;
     * teams with zero upcoming activities are present with a `0` value
     * so the caller can render every card without a second lookup.
     *
     * Club-scoped via `CurrentClub::id()` and demo-scoped to match the
     * activity universe the activities list + coach heroes surface.
     * The count query lives here (not in the view / REST controller) so
     * the teams REST row stays a thin data assembler — same data-fork
     * discipline #1190 established for the rest of this repository.
     *
     * @param list<int> $team_ids
     * @return array<int, int> team_id => upcoming count (0 when none).
     */
    public function upcomingCountsByTeam( array $team_ids, int $days = 14 ): array {
        $team_ids = array_values( array_unique( array_filter( array_map( 'intval', $team_ids ) ) ) );
        $out = [];
        foreach ( $team_ids as $tid ) {
            $out[ $tid ] = 0;
        }
        if ( empty( $team_ids ) ) {
            return $out;
        }

        global $wpdb;
        $p     = $wpdb->prefix;
        $days  = max( 1, $days );
        $scope = QueryHelpers::apply_demo_scope( 'a', 'activity' );

        $placeholders = implode( ',', array_fill( 0, count( $team_ids ), '%d' ) );
        $params       = array_merge( $team_ids, [ CurrentClub::id(), $days ] );

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT a.team_id, COUNT(*) AS n
               FROM {$p}tt_activities a
              WHERE a.team_id IN ({$placeholders})
                AND a.club_id = %d
                AND a.archived_at IS NULL
                AND a.session_date >= CURDATE()
                AND a.session_date < DATE_ADD(CURDATE(), INTERVAL %d DAY)
                AND ( a.activity_status_key IS NULL OR a.activity_status_key NOT IN ('completed','cancelled') )
                {$scope}
           GROUP BY a.team_id",
            ...$params
        ) );

        foreach ( $rows ?: [] as $row ) {
            $out[ (int) $row->team_id ] = (int) $row->n;
        }
        return $out;
    }

    /**
     * #1850 — the next few upcoming activities for a team, for the
     * player/parent development home's "Coming up" peek. Future-dated,
     * not completed/cancelled, soonest first. Club- + demo-scoped like
     * the rest of this repository so the home sees the same activity
     * universe as the activities list.
     *
     * @return array<int, object> rows with id, title, session_date, location
     */
    public function upcomingForTeam( int $team_id, int $limit = 3 ): array {
        if ( $team_id <= 0 ) return [];
        $limit = max( 1, min( 20, $limit ) );

        global $wpdb;
        $p     = $wpdb->prefix;
        $scope = QueryHelpers::apply_demo_scope( 'a', 'activity' );

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT a.id, a.title, a.session_date, a.location
               FROM {$p}tt_activities a
              WHERE a.team_id = %d
                AND a.club_id = %d
                AND a.archived_at IS NULL
                AND a.session_date >= CURDATE()
                AND ( a.activity_status_key IS NULL OR a.activity_status_key NOT IN ('completed','cancelled') )
                {$scope}
           ORDER BY a.session_date ASC, a.id ASC
              LIMIT %d",
            $team_id, CurrentClub::id(), $limit
        ) );

        return is_array( $rows ) ? $rows : [];
    }

    /**
     * #1989 — upcoming MATCHES for a team (player "My team" next-match peek).
     * Future-dated, match-type, not completed/cancelled, soonest first.
     * Carries the opponent + home/away so the surface can show a fixture
     * line. Club- + demo-scoped like the rest of this repository.
     *
     * @return array<int, object> rows with id, title, session_date, location,
     *                            opponent, home_away
     */
    public function upcomingMatchesForTeam( int $team_id, int $limit = 3 ): array {
        if ( $team_id <= 0 ) return [];
        $limit = max( 1, min( 20, $limit ) );

        global $wpdb;
        $p     = $wpdb->prefix;
        $scope = QueryHelpers::apply_demo_scope( 'a', 'activity' );
        $match_like = ActivityTypeKey::MATCH_LIKE_SQL;

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT a.id, a.title, a.session_date, a.location, a.opponent, a.home_away
               FROM {$p}tt_activities a
              WHERE a.team_id = %d
                AND a.club_id = %d
                AND a.archived_at IS NULL
                AND a.activity_type_key IN ({$match_like})
                AND a.session_date >= CURDATE()
                AND ( a.activity_status_key IS NULL OR a.activity_status_key NOT IN ('completed','cancelled') )
                {$scope}
           ORDER BY a.session_date ASC, a.id ASC
              LIMIT %d",
            $team_id, CurrentClub::id(), $limit
        ) );

        return is_array( $rows ) ? $rows : [];
    }

    /**
     * #1989 — recent finished MATCHES for a team with the result framed from
     * the team's perspective (player "My team" form line). Only matches with
     * both scores recorded; most recent first. Each row gains `team_score`,
     * `opp_score` and `outcome` ('W' | 'D' | 'L'). Club- + demo-scoped.
     *
     * @return array<int, object> rows with id, title, session_date, opponent,
     *                            team_score, opp_score, outcome
     */
    public function recentResultsForTeam( int $team_id, int $limit = 5 ): array {
        if ( $team_id <= 0 ) return [];
        $limit = max( 1, min( 20, $limit ) );

        global $wpdb;
        $p     = $wpdb->prefix;
        $scope = QueryHelpers::apply_demo_scope( 'a', 'activity' );
        $match_like = ActivityTypeKey::MATCH_LIKE_SQL;

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT a.id, a.title, a.session_date, a.opponent, a.home_away, a.home_score, a.away_score
               FROM {$p}tt_activities a
              WHERE a.team_id = %d
                AND a.club_id = %d
                AND a.archived_at IS NULL
                AND a.activity_type_key IN ({$match_like})
                AND a.session_date <= CURDATE()
                AND a.home_score IS NOT NULL
                AND a.away_score IS NOT NULL
                {$scope}
           ORDER BY a.session_date DESC, a.id DESC
              LIMIT %d",
            $team_id, CurrentClub::id(), $limit
        ) );
        if ( ! is_array( $rows ) ) return [];

        foreach ( $rows as $r ) {
            // The academy team is 'home' unless the row says 'away'.
            $is_home          = ( (string) ( $r->home_away ?? '' ) ) !== 'away';
            $r->team_score    = (int) ( $is_home ? $r->home_score : $r->away_score );
            $r->opp_score     = (int) ( $is_home ? $r->away_score : $r->home_score );
            $r->outcome       = $r->team_score > $r->opp_score ? 'W'
                              : ( $r->team_score < $r->opp_score ? 'L' : 'D' );
        }

        return $rows;
    }

    // ──────────────────────────────────────────────────────────────
    // #1712 — REST query-shape methods. The activities REST controller
    // previously inlined ~19 `$wpdb` sites against tt_activities /
    // tt_attendance; they now route through the methods below so the
    // controller and the (already-clean) views share one source of
    // truth per query shape. Authorization decisions stay in the
    // controller's permission_callback / handler; only the SQL moved.
    // ──────────────────────────────────────────────────────────────

    /**
     * #1712 — the linked player id for a WP user (active, non-archived),
     * or 0 when the user maps to no player. Used by the REST permission
     * helpers to detect player-scoped my-activities calls.
     */
    public function linkedPlayerIdForUser( int $wp_user_id ): int {
        if ( $wp_user_id <= 0 ) return 0;
        global $wpdb;
        $p = $wpdb->prefix;
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$p}tt_players WHERE wp_user_id = %d AND archived_at IS NULL LIMIT 1",
            $wp_user_id
        ) );
    }

    /**
     * #1712 — true when the WP user is a registered parent/guardian of
     * the given player. Defensive against installs that predate the
     * `tt_player_parents` table (returns false when it is absent).
     */
    public function userIsParentOfPlayer( int $wp_user_id, int $player_id ): bool {
        if ( $wp_user_id <= 0 || $player_id <= 0 ) return false;
        global $wpdb;
        $parents_table = $wpdb->prefix . 'tt_player_parents';
        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $parents_table ) ) !== $parents_table ) {
            return false;
        }
        $link = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT player_id FROM {$parents_table} WHERE parent_user_id = %d AND player_id = %d LIMIT 1",
            $wp_user_id, $player_id
        ) );
        return $link > 0;
    }

    /**
     * #1712 — the REST activities list (GET /activities), with the
     * on-the-fly attendance-completeness computed columns + HAVING
     * filter the controller's `list_sessions` inlined before the sweep.
     *
     * Coach/player scoping decisions stay in the controller (authz); the
     * resolved team restriction arrives via `restrict_team_ids`.
     *
     * `$args` keys:
     *   - per_page (int), offset (int)
     *   - orderby (string) — a column already resolved from the
     *     controller's `ORDERBY_WHITELIST`; order ('asc'|'desc')
     *   - include_archived (bool)
     *   - restrict_team_ids (?list<int>) — null = no team restriction
     *     (global-read persona or player-scoped request); a list = limit
     *     to those team ids (coach scope)
     *   - filter (array) — team_id (int|CSV), player_id, date_from,
     *     date_to, plan_state (CSV), attendance ('complete'|'partial'|'none')
     *   - search (string)
     *   - your_status_pid (int) — when > 0, adds the per-row
     *     `your_attendance_status` subquery
     *
     * @param array<string, mixed> $args
     * @return array{rows: list<object>, total: int}
     */
    public function searchForRest( array $args ): array {
        global $wpdb;
        $p = $wpdb->prefix;

        $per_page = (int) ( $args['per_page'] ?? 25 );
        $offset   = max( 0, (int) ( $args['offset'] ?? 0 ) );
        $orderby  = (string) ( $args['orderby'] ?? 's.session_date' );
        $order    = strtolower( (string) ( $args['order'] ?? 'desc' ) ) === 'asc' ? 'asc' : 'desc';
        $filter   = is_array( $args['filter'] ?? null ) ? $args['filter'] : [];

        $where  = [ '1=1', 's.club_id = %d' ];
        $params = [ CurrentClub::id() ];

        $scope = QueryHelpers::apply_demo_scope( 's', 'activity' );

        // #1555 — Active / Archived / All status filter, mirroring the
        // goals/players/teams archive lifecycle. Default (empty) resolves to
        // 'active'; the legacy include_archived=1 flag maps to 'all'.
        $archived_view = \TT\Infrastructure\Archive\ArchiveRepository::sanitizeView(
            $filter['archived'] ?? ''
        );
        if ( ! empty( $args['include_archived'] ) ) {
            $archived_view = 'all';
        }
        if ( $archived_view === 'active' ) {
            $where[] = 's.archived_at IS NULL';
        } elseif ( $archived_view === 'archived' ) {
            $where[] = 's.archived_at IS NOT NULL';
        }
        // 'all' → no archived clause.

        // Coach scope — the controller resolved the accessible team set;
        // null means unrestricted (global-read persona or player-scoped).
        $restrict = $args['restrict_team_ids'] ?? null;
        if ( is_array( $restrict ) && ! empty( $restrict ) ) {
            $team_ids     = array_values( array_map( 'intval', $restrict ) );
            $placeholders = implode( ',', array_fill( 0, count( $team_ids ), '%d' ) );
            $where[]      = "s.team_id IN ($placeholders)";
            $params       = array_merge( $params, $team_ids );
        }

        if ( ! empty( $filter['team_id'] ) ) {
            $raw_team = (string) $filter['team_id'];
            $team_ids = array_values( array_filter( array_map( 'absint', explode( ',', $raw_team ) ) ) );
            if ( count( $team_ids ) === 1 ) {
                $where[]  = 's.team_id = %d';
                $params[] = $team_ids[0];
            } elseif ( count( $team_ids ) > 1 ) {
                $placeholders = implode( ',', array_fill( 0, count( $team_ids ), '%d' ) );
                $where[]  = "s.team_id IN ($placeholders)";
                $params   = array_merge( $params, $team_ids );
            }
        }

        if ( ! empty( $filter['player_id'] ) ) {
            $pid = absint( $filter['player_id'] );
            $where[]  = "(
                s.team_id IN ( SELECT pl.team_id FROM {$p}tt_players pl WHERE pl.id = %d AND pl.club_id = s.club_id )
                OR EXISTS ( SELECT 1 FROM {$p}tt_attendance a WHERE a.activity_id = s.id AND a.club_id = s.club_id AND ( a.player_id = %d OR a.guest_player_id = %d ) )
            )";
            $params[] = $pid;
            $params[] = $pid;
            $params[] = $pid;
        }
        if ( ! empty( $filter['date_from'] ) ) {
            $where[]  = 's.session_date >= %s';
            $params[] = sanitize_text_field( (string) $filter['date_from'] );
        }
        if ( ! empty( $filter['date_to'] ) ) {
            $where[]  = 's.session_date <= %s';
            $params[] = sanitize_text_field( (string) $filter['date_to'] );
        }
        if ( ! empty( $filter['plan_state'] ) ) {
            $states  = array_filter( array_map( 'trim', explode( ',', (string) $filter['plan_state'] ) ) );
            $allowed = [ 'draft', 'scheduled', 'in_progress', 'completed', 'cancelled' ];
            $states  = array_values( array_intersect( $states, $allowed ) );
            if ( ! empty( $states ) ) {
                $placeholders = implode( ',', array_fill( 0, count( $states ), '%s' ) );
                $where[]  = "s.plan_state IN ($placeholders)";
                foreach ( $states as $st ) $params[] = $st;
            }
        }

        if ( ! empty( $args['search'] ) ) {
            $like = '%' . $wpdb->esc_like( (string) $args['search'] ) . '%';
            $where[]  = '(s.title LIKE %s OR s.location LIKE %s OR t.name LIKE %s)';
            $params[] = $like; $params[] = $like; $params[] = $like;
        }

        $where_sql = implode( ' AND ', $where ) . ' ' . $scope;

        // Attendance computed columns via correlated subqueries. The
        // numerator + denominator share a player set (current active
        // roster) so the list-view % equals the detail-page form's
        // present/total ratio — see the v3.110.95 note in git history.
        $select_cols = "s.*, t.name AS team_name,
            (SELECT COUNT(*) FROM {$p}tt_attendance a
               INNER JOIN {$p}tt_players pl_a ON pl_a.id = a.player_id AND pl_a.club_id = a.club_id
              WHERE a.activity_id = s.id AND a.is_guest = 0 AND a.club_id = s.club_id
                AND pl_a.team_id = s.team_id AND pl_a.status = 'active') AS attendance_count,
            (SELECT COUNT(*) FROM {$p}tt_attendance a
               INNER JOIN {$p}tt_players pl_b ON pl_b.id = a.player_id AND pl_b.club_id = a.club_id
              WHERE a.activity_id = s.id AND a.is_guest = 0 AND a.club_id = s.club_id
                AND pl_b.team_id = s.team_id AND pl_b.status = 'active'
                AND a.status = 'Present') AS present_count,
            (SELECT COUNT(*) FROM {$p}tt_players pl WHERE pl.team_id = s.team_id AND pl.club_id = s.club_id AND pl.status = 'active') AS roster_size";

        $your_status_pid = (int) ( $args['your_status_pid'] ?? 0 );
        if ( $your_status_pid > 0 ) {
            $select_cols .= $wpdb->prepare(
                ", (SELECT a.status FROM {$p}tt_attendance a
                       WHERE a.activity_id = s.id AND a.club_id = s.club_id
                         AND ( a.player_id = %d OR a.guest_player_id = %d )
                       LIMIT 1) AS your_attendance_status",
                $your_status_pid, $your_status_pid
            );
        }

        $having     = '';
        $att_filter = isset( $filter['attendance'] ) ? sanitize_key( (string) $filter['attendance'] ) : '';
        if ( $att_filter === 'complete' ) {
            $having = 'HAVING attendance_count >= roster_size AND roster_size > 0';
        } elseif ( $att_filter === 'partial' ) {
            $having = 'HAVING attendance_count > 0 AND attendance_count < roster_size';
        } elseif ( $att_filter === 'none' ) {
            $having = 'HAVING attendance_count = 0';
        }

        $list_sql = "SELECT {$select_cols}
                     FROM {$p}tt_activities s
                     LEFT JOIN {$p}tt_teams t ON t.id = s.team_id AND t.club_id = s.club_id
                     WHERE {$where_sql}
                     {$having}
                     ORDER BY {$orderby} {$order}
                     LIMIT %d OFFSET %d";

        $list_params = array_merge( $params, [ $per_page, $offset ] );
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rows = $wpdb->get_results( $wpdb->prepare( $list_sql, ...$list_params ) );

        if ( $having !== '' ) {
            $count_sql = "SELECT COUNT(*) FROM (
                SELECT s.id,
                    (SELECT COUNT(*) FROM {$p}tt_attendance a
                       INNER JOIN {$p}tt_players pl_a ON pl_a.id = a.player_id AND pl_a.club_id = a.club_id
                      WHERE a.activity_id = s.id AND a.is_guest = 0 AND a.club_id = s.club_id
                        AND pl_a.team_id = s.team_id AND pl_a.status = 'active') AS attendance_count,
                    (SELECT COUNT(*) FROM {$p}tt_players pl WHERE pl.team_id = s.team_id AND pl.club_id = s.club_id AND pl.status = 'active') AS roster_size
                FROM {$p}tt_activities s
                LEFT JOIN {$p}tt_teams t ON t.id = s.team_id AND t.club_id = s.club_id
                WHERE {$where_sql}
                {$having}
            ) AS sub";
        } else {
            $count_sql = "SELECT COUNT(*) FROM {$p}tt_activities s
                          LEFT JOIN {$p}tt_teams t ON t.id = s.team_id AND t.club_id = s.club_id
                          WHERE {$where_sql}";
        }
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) );

        return [ 'rows' => is_array( $rows ) ? $rows : [], 'total' => $total ];
    }

    /**
     * #1712 — the activity's team id (club-scoped), or 0. Used by the
     * REST roster-integrity guard (#1148) to drop off-roster attendance.
     */
    public function activityTeamId( int $activity_id ): int {
        if ( $activity_id <= 0 ) return 0;
        global $wpdb;
        $p = $wpdb->prefix;
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT team_id FROM {$p}tt_activities WHERE id = %d AND club_id = %d LIMIT 1",
            $activity_id, CurrentClub::id()
        ) );
    }

    /**
     * #2401 — the team + date a desktop-grid deep-link needs for one
     * activity, in a single club-scoped read. `activityTeamId()` answers
     * half of it; the grid link also needs the date so it can narrow the
     * grid's window to this activity's own column.
     *
     * @return array{team:int,date:string} `team` is 0 for a club-wide
     *         activity (no roster to grid) or a missing/out-of-club row.
     */
    public function gridAnchor( int $activity_id ): array {
        if ( $activity_id <= 0 ) return [ 'team' => 0, 'date' => '' ];
        global $wpdb;
        $p   = $wpdb->prefix;
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT team_id, session_date FROM {$p}tt_activities WHERE id = %d AND club_id = %d LIMIT 1",
            $activity_id, CurrentClub::id()
        ) );
        if ( ! $row ) return [ 'team' => 0, 'date' => '' ];
        return [
            'team' => (int) ( $row->team_id ?? 0 ),
            'date' => (string) ( $row->session_date ?? '' ),
        ];
    }

    /**
     * #1712 — the player's current team id (club-scoped), or 0.
     */
    public function playerTeamId( int $player_id ): int {
        if ( $player_id <= 0 ) return 0;
        global $wpdb;
        $p = $wpdb->prefix;
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT team_id FROM {$p}tt_players WHERE id = %d AND club_id = %d LIMIT 1",
            $player_id, CurrentClub::id()
        ) );
    }

    /**
     * #1712 — insert one attendance row (roster or guest). The caller
     * supplies the fully-shaped, sanitized column map; club scoping is
     * the caller's responsibility (it varies by write path). Returns the
     * new row id, or null on a DB error (read `lastError()` for the message).
     *
     * @param array<string, mixed> $row
     */
    public function insertAttendance( array $row ): ?int {
        global $wpdb;
        $p = $wpdb->prefix;
        $ok = $wpdb->insert( "{$p}tt_attendance", $row );
        if ( $ok === false ) return null;
        $this->announceAttendanceChange( (int) ( $row['activity_id'] ?? 0 ) );
        return (int) $wpdb->insert_id;
    }

    /**
     * #1712 — wipe the non-guest (roster) attendance rows for an
     * activity. Guest rows survive (managed via the guest endpoints).
     */
    public function deleteRosterAttendance( int $activity_id ): void {
        global $wpdb;
        $p = $wpdb->prefix;
        $wpdb->delete( "{$p}tt_attendance", [ 'activity_id' => $activity_id, 'is_guest' => 0, 'club_id' => CurrentClub::id() ] );
        $this->announceAttendanceChange( $activity_id );
    }

    /**
     * #1712 — count ALL attendance rows for an activity (roster + guest),
     * club-scoped. Used to detect "already has attendance" before seeding.
     */
    public function countAttendance( int $activity_id ): int {
        if ( $activity_id <= 0 ) return 0;
        global $wpdb;
        $p = $wpdb->prefix;
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$p}tt_attendance WHERE activity_id = %d AND club_id = %d",
            $activity_id, CurrentClub::id()
        ) );
    }

    /**
     * #1712 — the activity's current plan_state (club-scoped), or ''.
     */
    public function planState( int $activity_id ): string {
        if ( $activity_id <= 0 ) return '';
        global $wpdb;
        $p = $wpdb->prefix;
        return (string) $wpdb->get_var( $wpdb->prepare(
            "SELECT plan_state FROM {$p}tt_activities WHERE id = %d AND club_id = %d",
            $activity_id, CurrentClub::id()
        ) );
    }

    /**
     * #1712 — set an activity's plan_state (club-scoped). No audit stamp:
     * this is an internal lifecycle transition, not a user edit.
     */
    public function setPlanState( int $activity_id, string $plan_state ): bool {
        global $wpdb;
        $p = $wpdb->prefix;
        return $wpdb->update(
            "{$p}tt_activities",
            [ 'plan_state' => $plan_state ],
            [ 'id' => $activity_id, 'club_id' => CurrentClub::id() ]
        ) !== false;
    }

    /**
     * #2245 — set an activity's status + plan_state together
     * (club-scoped). Used by the detail-view transition buttons
     * (Cancel / Reopen). Cancel → cancelled; Reopen → planned. The
     * Complete transition is NOT done here — completion runs through the
     * evaluation flow and flips status at its final save.
     */
    public function setStatus( int $activity_id, string $status_key ): bool {
        global $wpdb;
        $p = $wpdb->prefix;
        // `plan_state` has its own vocabulary (scheduled/…/cancelled);
        // a `planned` status maps to the `scheduled` plan_state so the
        // planner's "what's coming up" bucket picks the row back up.
        $plan_state = $status_key === ActivityStatusKey::PLANNED ? 'scheduled' : $status_key;
        // #3081 — see `announceCancellation()`.
        $was_cancelled = $this->wasCancelledBefore( $activity_id, [ 'activity_status_key' => $status_key ] );
        $ok = $wpdb->update(
            "{$p}tt_activities",
            [
                'activity_status_key' => $status_key,
                'plan_state'          => $plan_state,
            ],
            [ 'id' => $activity_id, 'club_id' => CurrentClub::id() ]
        ) !== false;

        if ( $ok && $was_cancelled === false ) $this->announceCancellation( $activity_id );

        return $ok;
    }

    /**
     * #2521 — recording attendance asserts the session took place, so flip
     * a non-terminal activity to completed. Both lifecycle columns move
     * together, which is what keeps the reports (which read
     * `activity_status_key`) and the planner (which reads `plan_state`)
     * telling the same story.
     *
     * Never overrides an explicit completed / cancelled — the coach has
     * been clear. Never completes a future-dated session either: a coach
     * can legitimately pre-record known absences for next week, and that
     * is not an assertion the session has happened.
     *
     * The guided flow reaches the same end state from its own final save
     * (`Wizards\Evaluation\AttendanceStep::completeActivityIfNotTerminal`),
     * which has no date guard because its activity picker only ever offers
     * sessions that have already happened.
     *
     * Returns true when the activity was transitioned.
     */
    public function completeIfNotTerminal( int $activity_id ): bool {
        if ( $activity_id <= 0 ) return false;
        global $wpdb;
        $p   = $wpdb->prefix;
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT activity_status_key, session_date
               FROM {$p}tt_activities
              WHERE id = %d AND club_id = %d",
            $activity_id, CurrentClub::id()
        ) );
        if ( ! $row ) return false;

        $status = strtolower( trim( (string) ( $row->activity_status_key ?? '' ) ) );
        if ( $status === ActivityStatusKey::COMPLETED || $status === ActivityStatusKey::CANCELLED ) {
            return false;
        }
        $date = (string) ( $row->session_date ?? '' );
        if ( $date === '' || $date > current_time( 'Y-m-d' ) ) return false;

        return $wpdb->update(
            "{$p}tt_activities",
            [
                'activity_status_key' => ActivityStatusKey::COMPLETED,
                'plan_state'          => 'completed',
            ],
            [ 'id' => $activity_id, 'club_id' => CurrentClub::id() ]
        ) !== false;
    }

    /**
     * #1712 — toggle the per-activity `evaluation_skipped` flag
     * (club-scoped). Dedicated method (not the audited `update()`) to
     * preserve the pre-sweep behaviour of not stamping `updated_by`.
     */
    public function setEvaluationSkipped( int $activity_id, int $skipped ): bool {
        global $wpdb;
        $p = $wpdb->prefix;
        return $wpdb->update(
            "{$p}tt_activities",
            [ 'evaluation_skipped' => $skipped ],
            [ 'id' => $activity_id, 'club_id' => CurrentClub::id() ]
        ) !== false;
    }

    /**
     * #1712 — true when the activity exists within the current club.
     */
    public function activityExists( int $activity_id ): bool {
        if ( $activity_id <= 0 ) return false;
        global $wpdb;
        $p = $wpdb->prefix;
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$p}tt_activities WHERE id = %d AND club_id = %d",
            $activity_id, CurrentClub::id()
        ) ) > 0;
    }

    /**
     * #1712 — true when the player exists within the current club.
     */
    public function playerExists( int $player_id ): bool {
        if ( $player_id <= 0 ) return false;
        global $wpdb;
        $p = $wpdb->prefix;
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$p}tt_players WHERE id = %d AND club_id = %d",
            $player_id, CurrentClub::id()
        ) ) > 0;
    }

    /**
     * #1712 — one attendance row by id (club-scoped), or null.
     */
    public function findAttendanceRow( int $id ): ?object {
        if ( $id <= 0 ) return null;
        global $wpdb;
        $p   = $wpdb->prefix;
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$p}tt_attendance WHERE id = %d AND club_id = %d LIMIT 1",
            $id, CurrentClub::id()
        ) );
        return $row ?: null;
    }

    /**
     * #1712 — partial update of one attendance row (club-scoped). Caller
     * supplies the sanitized column map.
     *
     * @param array<string, mixed> $fields
     */
    public function updateAttendanceRow( int $id, array $fields ): bool {
        global $wpdb;
        $p  = $wpdb->prefix;
        $ok = $wpdb->update( "{$p}tt_attendance", $fields, [ 'id' => $id, 'club_id' => CurrentClub::id() ] ) !== false;
        $this->announceAttendanceChange( $this->attendanceRowActivityId( $id ) );
        return $ok;
    }

    /**
     * #2731 — the activity one attendance row belongs to, or 0.
     *
     * A primary-key lookup on the single-row write paths, which handle one
     * cell at a time and already cost a round trip. The alternative was to
     * fire the event from the REST controllers that happen to know the
     * activity id, which would leave the wp-admin and tournament callers
     * silently uncovered.
     */
    private function attendanceRowActivityId( int $id ): int {
        if ( $id <= 0 ) return 0;
        global $wpdb;
        $p = $wpdb->prefix;
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT activity_id FROM {$p}tt_attendance WHERE id = %d AND club_id = %d",
            $id, CurrentClub::id()
        ) );
    }

    /**
     * #1712 — delete one attendance row by id (club-scoped).
     */
    public function deleteAttendanceRow( int $id ): bool {
        global $wpdb;
        $p = $wpdb->prefix;
        // Read before the delete — afterwards there is no row to ask.
        $activity_id = $this->attendanceRowActivityId( $id );
        $ok          = $wpdb->delete( "{$p}tt_attendance", [ 'id' => $id, 'club_id' => CurrentClub::id() ] ) !== false;
        $this->announceAttendanceChange( $activity_id );
        return $ok;
    }

    /**
     * #2382 — upsert one player's ACTUAL attendance status for an activity
     * (the bulk attendance-grid write path, epic #2381). Finds the existing
     * non-guest `record_type='actual'` row for (activity, player) and updates
     * its status; inserts a fresh row when none exists; DELETES the row when
     * `$status` is blank (clearing a grid cell). Club-scoped. Returns true on
     * success.
     *
     * Roster integrity (#1148) and status validation are the caller's
     * responsibility — the controller guards both before calling this.
     */
    public function upsertActualAttendanceStatus( int $activity_id, int $player_id, string $status ): bool {
        if ( $activity_id <= 0 || $player_id <= 0 ) return false;
        global $wpdb;
        $p       = $wpdb->prefix;
        $club_id = (int) CurrentClub::id();

        // All existing recorded rows for this (activity, player), newest last.
        // Legacy dirty data can leave more than one `actual` row (a wizard row
        // and a match-execution row); heal it here so the grid stops being
        // ambiguous — keep the latest, drop the rest.
        $existing_ids = array_map( 'intval', (array) $wpdb->get_col( $wpdb->prepare(
            "SELECT id FROM {$p}tt_attendance
              WHERE activity_id = %d AND player_id = %d AND club_id = %d
                AND is_guest = 0 AND record_type = 'actual'
              ORDER BY id ASC",
            $activity_id, $player_id, $club_id
        ) ) );
        $keep_id = $existing_ids ? (int) end( $existing_ids ) : 0;
        $stale   = array_filter( $existing_ids, static fn( int $id ): bool => $id !== $keep_id );

        // Blank status clears the cell — remove every recorded row.
        if ( $status === '' ) {
            $ok = true;
            foreach ( $existing_ids as $id ) {
                if ( $wpdb->delete( "{$p}tt_attendance", [ 'id' => $id, 'club_id' => $club_id ] ) === false ) $ok = false;
            }
            $this->announceAttendanceChange( $activity_id );
            return $ok;
        }

        // Drop the stale duplicates, then update the one we keep (or insert).
        foreach ( $stale as $id ) {
            $wpdb->delete( "{$p}tt_attendance", [ 'id' => $id, 'club_id' => $club_id ] );
        }

        if ( $keep_id > 0 ) {
            $ok = $wpdb->update(
                "{$p}tt_attendance",
                [ 'status' => $status ],
                [ 'id' => $keep_id, 'club_id' => $club_id ]
            ) !== false;
            $this->announceAttendanceChange( $activity_id );
            return $ok;
        }

        $ok = $wpdb->insert( "{$p}tt_attendance", [
            'club_id'     => $club_id,
            'activity_id' => $activity_id,
            'player_id'   => $player_id,
            'status'      => $status,
            'is_guest'    => 0,
            'record_type' => 'actual',
        ] ) !== false;
        $this->announceAttendanceChange( $activity_id );
        return $ok;
    }

    /**
     * #2386 — set (int) or clear (null) a player's recorded minutes on the
     * existing non-guest attendance row for a match, for the minutes grid's
     * NON-execution (paper-match, #2159) write path. Mirrors the
     * `PATCH /attendance/{id}` minutes behaviour but keyed by
     * (activity, player). Targets an EXISTING row only — the grid only
     * offers minutes for squad players — so a missing row returns false
     * (the controller skips it) rather than inventing an attendance row
     * with no status. Clamped to 0..200. Club-scoped.
     */
    public function updateActualAttendanceMinutes( int $activity_id, int $player_id, ?int $minutes ): bool {
        if ( $activity_id <= 0 || $player_id <= 0 ) return false;
        global $wpdb;
        $p       = $wpdb->prefix;
        $club_id = (int) CurrentClub::id();

        $row_id = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$p}tt_attendance
              WHERE activity_id = %d AND player_id = %d AND club_id = %d
                AND is_guest = 0 AND record_type = 'actual'
              ORDER BY id DESC LIMIT 1",
            $activity_id, $player_id, $club_id
        ) );
        if ( $row_id <= 0 ) return false;

        $value = $minutes === null ? null : max( 0, min( 200, $minutes ) );
        return $wpdb->update(
            "{$p}tt_attendance",
            [ 'minutes_played' => $value ],
            [ 'id' => $row_id, 'club_id' => $club_id ]
        ) !== false;
    }

    /**
     * #1712 — a linked guest's display name + home-team name (club-scoped),
     * for shaping a guest attendance row in the REST response. Null when
     * the player is missing.
     *
     * @return object|null `{first_name, last_name, team_name}`
     */
    public function guestPlayerNameAndTeam( int $player_id ): ?object {
        if ( $player_id <= 0 ) return null;
        global $wpdb;
        $p   = $wpdb->prefix;
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT pl.first_name, pl.last_name, t.name AS team_name
             FROM {$p}tt_players pl
             LEFT JOIN {$p}tt_teams t ON t.id = pl.team_id AND t.club_id = pl.club_id
             WHERE pl.id = %d AND pl.club_id = %d LIMIT 1",
            $player_id, CurrentClub::id()
        ) );
        return $row ?: null;
    }
}
