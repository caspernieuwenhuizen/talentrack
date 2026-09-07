<?php
namespace TT\Modules\Pdp\Rest;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Infrastructure\Logging\Logger;
use TT\Infrastructure\Query\QueryHelpers;
use TT\Infrastructure\REST\RestResponse;
use TT\Modules\Authorization\MatrixGate;
use TT\Modules\Pdp\Calendar\PdpCalendarWriters;
use TT\Modules\Pdp\Repositories\PdpConversationsRepository;
use TT\Modules\Pdp\Repositories\PdpFilesRepository;
use TT\Modules\Pdp\Repositories\SeasonsRepository;

/**
 * PdpFilesRestController — /wp-json/talenttrack/v1/pdp-files
 *
 * #0044 Sprint 1. CRUD for PDP files with coach scoping. Creating a
 * file auto-templates the conversation cycle (createCycle) and emits
 * one calendar-link row per conversation via the PdpCalendarWriter
 * (NativeWriter today; Spond writer plugs in via #0031).
 */
class PdpFilesRestController {

    private const NS = 'talenttrack/v1';

    public static function init(): void {
        add_action( 'rest_api_init', [ __CLASS__, 'register' ] );
    }

    public static function register(): void {
        register_rest_route( self::NS, '/pdp-files', [
            [
                'methods'             => 'GET',
                'callback'            => [ __CLASS__, 'list' ],
                'permission_callback' => [ __CLASS__, 'can_view' ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ __CLASS__, 'create' ],
                'permission_callback' => [ __CLASS__, 'can_edit' ],
            ],
        ] );
        // #1617 — player-centric coverage list for the PDP setup
        // landing: every coach-scoped player + whether they HAVE a PDP
        // for the season. Read-side only; reuses the view's coach/admin
        // scoping so a non-WP front end gets the same answer.
        register_rest_route( self::NS, '/pdp-files/coverage', [
            [
                'methods'             => 'GET',
                'callback'            => [ __CLASS__, 'coverage' ],
                'permission_callback' => [ __CLASS__, 'can_view' ],
            ],
        ] );
        register_rest_route( self::NS, '/pdp-files/(?P<id>\d+)', [
            [
                'methods'             => 'GET',
                'callback'            => [ __CLASS__, 'get_one' ],
                'permission_callback' => [ __CLASS__, 'can_view' ],
            ],
            [
                'methods'             => 'PATCH',
                'callback'            => [ __CLASS__, 'patch' ],
                'permission_callback' => [ __CLASS__, 'can_edit' ],
            ],
            // #1274 PR1 — DELETE = soft-archive (mirrors the
            // EvaluationsRestController::delete_eval pattern, also a
            // soft-delete). Cap-gated on tt_edit_pdp.
            [
                'methods'             => 'DELETE',
                'callback'            => [ __CLASS__, 'archive' ],
                'permission_callback' => [ __CLASS__, 'can_edit' ],
            ],
        ] );
        // #1274 PR1 — restore endpoint, gated on a new admin-only cap.
        register_rest_route( self::NS, '/pdp-files/(?P<id>\d+)/restore', [
            [
                'methods'             => 'POST',
                'callback'            => [ __CLASS__, 'restore' ],
                'permission_callback' => [ __CLASS__, 'can_unarchive' ],
            ],
        ] );
        // #1274 PR3 — permanent delete with five-table cascade.
        // Cap-gated on tt_delete_pdp (admin only by seed). The
        // double-confirm UX (typed-slug) lives on the calling
        // surface, not the endpoint itself — REST is the primitive.
        register_rest_route( self::NS, '/pdp-files/(?P<id>\d+)/permanent-delete', [
            [
                'methods'             => 'POST',
                'callback'            => [ __CLASS__, 'permanent_delete' ],
                'permission_callback' => [ __CLASS__, 'can_delete' ],
            ],
        ] );
    }

    public static function can_unarchive(): bool {
        return current_user_can( 'tt_unarchive_pdp' );
    }

    public static function can_delete(): bool {
        return current_user_can( 'tt_delete_pdp' );
    }

    public static function can_view(): bool {
        return current_user_can( 'tt_view_pdp' ) || current_user_can( 'tt_edit_pdp' );
    }

    public static function can_edit(): bool {
        return current_user_can( 'tt_edit_pdp' );
    }

    /**
     * Whitelist for the `orderby` query param. v3.110.110 — added so
     * FrontendListTable's clickable sort headers can request a column.
     */
    private const ORDERBY_WHITELIST = [
        'player_name'  => 'pl.last_name',
        'team_name'    => 't.name',
        'status'       => 'f.status',
        'cycle_size'   => 'f.cycle_size',
        'updated_at'   => 'f.updated_at',
    ];

    /**
     * GET /pdp-files — coach sees own players' files; admin sees all.
     *
     * v3.110.110 — rewritten to the FrontendListTable contract (matches
     * GoalsRestController / EvaluationsRestController). Accepts
     * `filter[team_id]`, `filter[player_id]`, `filter[status]`,
     * `search`, `orderby`, `order`, `page`, `per_page`. Returns the
     * standard `{rows, total, page, per_page}` envelope. Rows are
     * pre-formatted with HTML link cells (player / team click-through)
     * and per-file parent/player ack rollups for the FrontendPdpManageView
     * list table. Coach-scoping preserved via the `listForCoach` /
     * `listForSeason` repository methods replaced by a unified SQL
     * query here (the repo methods returned raw rows; FrontendListTable
     * needs paginated joined output).
     */
    public static function list( \WP_REST_Request $r ): \WP_REST_Response {
        global $wpdb; $p = $wpdb->prefix;

        $season_id = absint( $r['season_id'] ?? 0 );
        if ( $season_id <= 0 ) {
            $current = ( new SeasonsRepository() )->current();
            $season_id = $current ? (int) $current->id : 0;
        }
        if ( $season_id <= 0 ) {
            return RestResponse::success( [ 'rows' => [], 'total' => 0, 'page' => 1, 'per_page' => 25 ] );
        }

        $page     = max( 1, absint( $r['page'] ?? 1 ) );
        $per_page = self::clamp_per_page( $r['per_page'] ?? 25 );
        $offset   = ( $page - 1 ) * $per_page;

        $orderby_key = sanitize_key( (string) ( $r['orderby'] ?? 'updated_at' ) );
        if ( ! isset( self::ORDERBY_WHITELIST[ $orderby_key ] ) ) $orderby_key = 'updated_at';
        $orderby = self::ORDERBY_WHITELIST[ $orderby_key ];
        $order   = strtolower( (string) ( $r['order'] ?? ( $orderby_key === 'updated_at' ? 'desc' : 'asc' ) ) );
        if ( ! in_array( $order, [ 'asc', 'desc' ], true ) ) $order = 'desc';

        $where  = [ 'f.club_id = %d', 'f.season_id = %d' ];
        $params = [ \TT\Infrastructure\Tenancy\CurrentClub::id(), $season_id ];

        if ( ! self::hasGlobalPdpAccess( 'read' ) ) {
            $where[]  = 'f.owner_coach_id = %d';
            $params[] = get_current_user_id();
        }

        // #1293 — `include_archived` query param. Default hides
        // archived rows (the soft-archive primitive landed in #1274
        // but the list endpoint kept showing every row regardless).
        // Operators with the `tt_unarchive_pdp` cap can opt in via
        // `?include_archived=1` (top-level) or `filter[include_archived]=1`
        // (the form FrontendListTable forwards via its static_filters
        // map). Coaches without the cap always see only active rows
        // (no point in showing a row they can't act on).
        // #1610 — also honour the toggle for `tt_delete_pdp` holders so
        // the archived list is reachable to permanently delete from,
        // even on seeds where an operator can delete but not restore.
        $filter = is_array( $r['filter'] ?? null ) ? (array) $r['filter'] : [];
        $include_archived_raw = $r['include_archived'] ?? ( $filter['include_archived'] ?? null );
        $can_see_archived     = current_user_can( 'tt_unarchive_pdp' ) || current_user_can( 'tt_delete_pdp' );
        $include_archived     = ! empty( $include_archived_raw ) && $can_see_archived;
        unset( $filter['include_archived'] );
        if ( ! $include_archived ) {
            $where[] = 'f.archived_at IS NULL';
        }
        if ( ! empty( $filter['team_id'] ) ) {
            $where[]  = 'pl.team_id = %d';
            $params[] = absint( $filter['team_id'] );
        }
        if ( ! empty( $filter['player_id'] ) ) {
            $where[]  = 'f.player_id = %d';
            $params[] = absint( $filter['player_id'] );
        }
        if ( ! empty( $filter['status'] ) ) {
            $where[]  = 'f.status = %s';
            $params[] = sanitize_text_field( (string) $filter['status'] );
        }
        if ( ! empty( $r['search'] ) ) {
            $like     = '%' . $wpdb->esc_like( (string) $r['search'] ) . '%';
            $where[]  = '(pl.first_name LIKE %s OR pl.last_name LIKE %s)';
            $params[] = $like; $params[] = $like;
        }

        $where_sql = implode( ' AND ', $where );

        // Aggregate parent/player ack across the file's conversations.
        // The list view renders one rollup checkmark per file —
        // "received" = at least one conversation has the ack set.
        // Per-conversation acks remain visible on the file detail page.
        $list_sql = "SELECT f.id, f.player_id, f.season_id, f.owner_coach_id, f.cycle_size, f.status, f.notes, f.updated_at, f.archived_at,
                            pl.first_name, pl.last_name, pl.team_id,
                            t.name AS team_name,
                            (SELECT MAX(c.parent_ack_at IS NOT NULL)
                               FROM {$p}tt_pdp_conversations c
                              WHERE c.pdp_file_id = f.id) AS has_parent_ack,
                            (SELECT MAX(c.player_ack_at IS NOT NULL)
                               FROM {$p}tt_pdp_conversations c
                              WHERE c.pdp_file_id = f.id) AS has_player_ack
                       FROM {$p}tt_pdp_files f
                       LEFT JOIN {$p}tt_players pl ON pl.id = f.player_id
                       LEFT JOIN {$p}tt_teams   t  ON t.id  = pl.team_id
                      WHERE {$where_sql}
                      ORDER BY {$orderby} {$order}, f.id DESC
                      LIMIT %d OFFSET %d";

        $list_params = array_merge( $params, [ $per_page, $offset ] );
        $rows        = $wpdb->get_results( $wpdb->prepare( $list_sql, ...$list_params ) );

        $count_sql = "SELECT COUNT(*)
                        FROM {$p}tt_pdp_files f
                        LEFT JOIN {$p}tt_players pl ON pl.id = f.player_id
                        LEFT JOIN {$p}tt_teams   t  ON t.id  = pl.team_id
                       WHERE {$where_sql}";
        $total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) );

        return RestResponse::success( [
            'rows'      => array_map( [ __CLASS__, 'format_list_row' ], $rows ?: [] ),
            'total'     => $total,
            'page'      => $page,
            'per_page'  => $per_page,
            'season_id' => $season_id,
        ] );
    }

    /**
     * #1617 — GET /pdp-files/coverage
     *
     * Player-centric coverage for the current (or requested) season:
     * one row per coach-scoped player with a `has_pdp` flag, the file
     * link when present, conversation progress, and a "Create PDP"
     * deep-link (pre-filled with player + team) when absent.
     *
     * Accepts `season_id`, `filter[team_id]`, `search`,
     * `filter[only_missing]` (or `only_missing`). Returns the standard
     * FrontendListTable envelope plus a `summary` block for the
     * "M of N players have a PDP" line. Pagination is applied in PHP
     * after the (single, indexed) coverage query — coach rosters are
     * small, so this stays cheap and keeps the repo method a plain
     * read.
     */
    public static function coverage( \WP_REST_Request $r ): \WP_REST_Response {
        $season_id = absint( $r['season_id'] ?? 0 );
        if ( $season_id <= 0 ) {
            $current   = ( new SeasonsRepository() )->current();
            $season_id = $current ? (int) $current->id : 0;
        }
        if ( $season_id <= 0 ) {
            return RestResponse::success( [
                'rows' => [], 'total' => 0, 'page' => 1, 'per_page' => 25,
                'summary' => [ 'total' => 0, 'covered' => 0 ],
            ] );
        }

        $filter = is_array( $r['filter'] ?? null ) ? (array) $r['filter'] : [];

        $coverage_filters = [];
        // Coach scope — global readers see every player; coaches are
        // narrowed to their own rosters via player ids.
        if ( ! self::hasGlobalPdpAccess( 'read' ) ) {
            $coverage_filters['player_ids'] = self::coachScopedPlayerIds( get_current_user_id() );
        }
        if ( ! empty( $filter['team_id'] ) ) {
            $coverage_filters['team_id'] = absint( $filter['team_id'] );
        }
        if ( ! empty( $r['search'] ) ) {
            $coverage_filters['search'] = sanitize_text_field( (string) $r['search'] );
        }
        $only_missing = ! empty( $r['only_missing'] ) || ! empty( $filter['only_missing'] );
        if ( $only_missing ) {
            $coverage_filters['only_missing'] = true;
        }
        // #2040 — Active / Archived record-state view. Only operators who can
        // act on archived files (unarchive or delete) may switch to the
        // archived view; everyone else is pinned to active.
        $archived_raw = (string) ( $r['archived'] ?? ( $filter['archived'] ?? 'active' ) );
        $can_see_archived = current_user_can( 'tt_unarchive_pdp' ) || current_user_can( 'tt_delete_pdp' );
        if ( $archived_raw === 'archived' && $can_see_archived ) {
            $coverage_filters['archived_view'] = 'archived';
        }

        $files   = new PdpFilesRepository();
        $rows    = $files->coverageForSeason( $season_id, $coverage_filters );
        $summary = $files->coverageSummaryForSeason( $season_id, $coverage_filters );

        $page     = max( 1, absint( $r['page'] ?? 1 ) );
        $per_page = self::clamp_per_page( $r['per_page'] ?? 25 );
        $total    = count( $rows );
        $paged    = array_slice( $rows, ( $page - 1 ) * $per_page, $per_page );

        return RestResponse::success( [
            'rows'      => array_map( [ __CLASS__, 'format_coverage_row' ], $paged ),
            'total'     => $total,
            'page'      => $page,
            'per_page'  => $per_page,
            'season_id' => $season_id,
            'summary'   => $summary,
        ] );
    }

    /**
     * #1617 — flat list of player ids on the coach's own teams. Mirrors
     * the roster scoping the manage view uses for its filter options.
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

    /**
     * #1617 — coverage row shape for FrontendListTable. Player name
     * links to the player record (§1 anchor); the status cell is a
     * green "PDP ✓ (x/y)" pill linking to the file, or a grey
     * "Not started" pill. The action cell offers "Open" (covered) or
     * "Create PDP" pre-filled with player + team (missing). Whole-row
     * click target is the file when covered, else the create flow.
     *
     * @param object $row coverageForSeason() result
     * @return array<string,mixed>
     */
    private static function format_coverage_row( $row ): array {
        $player_id = (int) $row->player_id;
        $team_id   = (int) ( $row->team_id ?? 0 );
        $first     = (string) ( $row->first_name ?? '' );
        $last      = (string) ( $row->last_name ?? '' );
        $player_name = trim( $first . ' ' . $last );
        if ( $player_name === '' ) $player_name = '#' . $player_id;
        $team_name = (string) ( $row->team_name ?? '' );

        $player_link_html = \TT\Shared\Frontend\Components\RecordLink::inline(
            $player_name,
            \TT\Shared\Frontend\Components\RecordLink::detailUrlForWithBack( 'players', $player_id )
        );

        $team_link_html = '—';
        if ( $team_id > 0 && $team_name !== '' ) {
            $team_link_html = \TT\Shared\Frontend\Components\RecordLink::inline(
                $team_name,
                \TT\Shared\Frontend\Components\RecordLink::detailUrlForWithBack( 'teams', $team_id )
            );
        }

        $file_id     = (int) ( $row->pdp_file_id ?? 0 );
        $has_pdp     = $file_id > 0;
        $is_archived = $has_pdp && ! empty( $row->archived_at );
        $conducted   = (int) ( $row->conv_conducted ?? 0 );
        $total       = (int) ( $row->conv_total ?? 0 );

        $dash = \TT\Shared\Frontend\Components\RecordLink::dashboardUrl();

        if ( $is_archived ) {
            // #2040 — archived-view row: the player's PDP for this season is
            // soft-archived. Show an Archived pill + the shared Restore /
            // permanent-delete row actions. No whole-row navigation — the
            // actions are explicit.
            $coverage_html = '<span class="tt-status-badge tt-pdp-archived-pill">'
                . esc_html__( 'Archived', 'talenttrack' ) . '</span>';
            $action_html = self::row_actions_html( $file_id, $player_name, true );
            $row_url     = '';
        } elseif ( $has_pdp ) {
            $detail_url = \TT\Shared\Frontend\Components\BackLink::appendTo( add_query_arg(
                [ 'tt_view' => 'pdp', 'id' => $file_id ],
                $dash
            ) );
            $progress = $total > 0
                ? sprintf(
                    /* translators: 1: conducted conversations, 2: total conversations */
                    _x( '%1$d/%2$d', 'PDP conversation progress', 'talenttrack' ),
                    $conducted,
                    $total
                )
                : '';
            $pill_label = $progress !== ''
                ? sprintf(
                    /* translators: %s = progress like "1/3" */
                    __( 'PDP ✓ %s', 'talenttrack' ),
                    $progress
                )
                : __( 'PDP ✓', 'talenttrack' );
            $coverage_html = '<a href="' . esc_url( $detail_url ) . '" class="tt-status-badge tt-status-completed" style="text-decoration:none;display:inline-flex;align-items:center;min-height:28px;">'
                . esc_html( $pill_label ) . '</a>';
            // #2039 — no redundant "Open" button: the whole row already
            // navigates to $detail_url (row_url_key), and the coverage pill
            // above is an <a> to the same target for the keyboard / AT path.
            $action_html = '';
            $row_url = $detail_url;
        } else {
            $create_url = \TT\Shared\Frontend\Components\BackLink::appendTo( add_query_arg(
                array_filter( [
                    'tt_view'   => 'pdp',
                    'action'    => 'new',
                    'player_id' => $player_id,
                    'team_id'   => $team_id > 0 ? $team_id : null,
                ] ),
                $dash
            ) );
            $coverage_html = '<span class="tt-status-badge tt-status-to-be-planned" style="display:inline-flex;align-items:center;min-height:28px;">'
                . esc_html__( 'Not started', 'talenttrack' ) . '</span>';
            $action_html = current_user_can( 'tt_edit_pdp' )
                ? '<a class="tt-btn tt-btn-primary tt-btn-sm" href="' . esc_url( $create_url ) . '" style="min-height:48px;display:inline-flex;align-items:center;padding:8px 12px;touch-action:manipulation;">'
                    . esc_html__( 'Add PDP', 'talenttrack' ) . '</a>'
                : '';
            $row_url = current_user_can( 'tt_edit_pdp' ) ? $create_url : '';
        }

        return [
            'player_id'        => $player_id,
            'player_name'      => $player_name,
            'player_link_html' => $player_link_html,
            'team_id'          => $team_id,
            'team_name'        => $team_name,
            'team_link_html'   => $team_link_html,
            'has_pdp'          => $has_pdp,
            'pdp_file_id'      => $file_id ?: null,
            'conv_total'       => $total,
            'conv_conducted'   => $conducted,
            'coverage_html'    => $coverage_html,
            'actions_html'     => $action_html,
            'detail_url'       => $row_url,
        ];
    }

    private static function clamp_per_page( $value ): int {
        $n = absint( $value );
        if ( ! in_array( $n, [ 10, 25, 50, 100 ], true ) ) return 25;
        return $n;
    }

    /**
     * v3.110.110 — list-row shape for FrontendListTable. Includes
     * pre-rendered link HTML for player/team cells and grey/green
     * checkmark HTML for the parent/player ack columns (pilot ask:
     * "use a grey checkmark if not received and a green checkmark
     * when received").
     *
     * @param object $row joined query result
     * @return array<string,mixed>
     */
    private static function format_list_row( $row ): array {
        $file_id   = (int) $row->id;
        $player_id = (int) $row->player_id;
        $team_id   = (int) ( $row->team_id ?? 0 );
        $first     = (string) ( $row->first_name ?? '' );
        $last      = (string) ( $row->last_name ?? '' );
        $player_name = trim( $first . ' ' . $last );
        if ( $player_name === '' ) $player_name = '#' . $player_id;
        $team_name = (string) ( $row->team_name ?? '' );

        $detail_url = \TT\Shared\Frontend\Components\BackLink::appendTo( add_query_arg(
            [ 'tt_view' => 'pdp', 'id' => $file_id ],
            \TT\Shared\Frontend\Components\RecordLink::dashboardUrl()
        ) );

        $player_link_html = \TT\Shared\Frontend\Components\RecordLink::inline(
            $player_name,
            \TT\Shared\Frontend\Components\RecordLink::detailUrlForWithBack( 'players', $player_id )
        );

        $team_link_html = '—';
        if ( $team_id > 0 && $team_name !== '' ) {
            $team_link_html = \TT\Shared\Frontend\Components\RecordLink::inline(
                $team_name,
                \TT\Shared\Frontend\Components\RecordLink::detailUrlForWithBack( 'teams', $team_id )
            );
        }

        $status        = (string) ( $row->status ?? '' );
        $status_pill_html = \TT\Infrastructure\Query\LookupPill::render( 'pdp_status', $status );

        $parent_ack = ! empty( $row->has_parent_ack );
        $player_ack = ! empty( $row->has_player_ack );
        $parent_ack_html = self::ack_checkmark( $parent_ack, __( 'Parent confirmation', 'talenttrack' ) );
        $player_ack_html = self::ack_checkmark( $player_ack, __( 'Player confirmation', 'talenttrack' ) );

        // #1293 — inline Archive / Restore button per row. Archive
        // visible to `tt_edit_pdp` holders on active rows; Restore to
        // `tt_unarchive_pdp` holders on already-archived rows. The
        // pdp-archive-button JS handler intercepts the click, fires
        // DELETE / POST against the per-row REST endpoint, fades the
        // row out and toasts the result. Buttons carry the 48×48
        // minimum tap target via the shared `.tt-btn-secondary`
        // baseline + the explicit `min-height:48px` inline style.
        $is_archived  = ! empty( $row->archived_at );
        $actions_html = self::row_actions_html( $file_id, $player_name, $is_archived );

        return [
            'id'               => $file_id,
            'player_id'        => $player_id,
            'player_name'      => $player_name,
            'player_link_html' => $player_link_html,
            'team_id'          => $team_id,
            'team_name'        => $team_name,
            'team_link_html'   => $team_link_html,
            'status'           => $status,
            'status_pill_html' => $status_pill_html,
            'cycle_size'       => $row->cycle_size !== null ? (int) $row->cycle_size : null,
            'parent_ack'       => $parent_ack,
            'parent_ack_html'  => $parent_ack_html,
            'player_ack'       => $player_ack,
            'player_ack_html'  => $player_ack_html,
            'updated_at'       => (string) ( $row->updated_at ?? '' ),
            'archived_at'      => $row->archived_at !== null ? (string) $row->archived_at : null,
            'is_archived'      => $is_archived,
            'actions_html'     => $actions_html,
            'detail_url'       => $detail_url,
        ];
    }

    /**
     * #1293 — render the inline Archive / Restore button HTML for a
     * single row. Returns an empty fragment when the current user
     * doesn't hold the relevant cap (so the actions column stays
     * neat for read-only viewers). The button payload is consumed
     * by `assets/js/pdp-archive-button.js`.
     */
    private static function row_actions_html( int $file_id, string $player_name, bool $is_archived ): string {
        if ( $is_archived ) {
            $buttons = [];
            if ( current_user_can( 'tt_unarchive_pdp' ) ) {
                $buttons[] = sprintf(
                    '<button type="button" class="tt-btn tt-btn-secondary tt-pdp-row-action" data-tt-pdp-restore="%1$d" data-tt-pdp-player="%2$s" style="min-height:48px;min-width:48px;padding:8px 12px;touch-action:manipulation;">%3$s</button>',
                    $file_id,
                    esc_attr( $player_name ),
                    esc_html__( 'Restore', 'talenttrack' )
                );
            }
            // #1610 — permanent-delete affordance for archived rows.
            // Cap-gated on tt_delete_pdp (admin-only by seed). Links to
            // the existing typed-name confirm subview
            // (?tt_view=pdp&action=permanent-delete) rather than firing
            // an inline fetch — the irreversible five-table cascade
            // warrants the double-confirm + cascade-summary surface, and
            // reusing it keeps a single delete path. Non-holders never
            // see the link.
            if ( current_user_can( 'tt_delete_pdp' ) ) {
                $del_url = \TT\Shared\Frontend\Components\BackLink::appendTo( add_query_arg(
                    [ 'tt_view' => 'pdp', 'id' => $file_id, 'action' => 'permanent-delete' ],
                    \TT\Shared\Frontend\Components\RecordLink::dashboardUrl()
                ) );
                $buttons[] = sprintf(
                    '<a class="tt-btn tt-btn-danger tt-pdp-row-action" href="%1$s" style="min-height:48px;min-width:48px;padding:8px 12px;touch-action:manipulation;background:#b91c1c;color:#fff;border:1px solid #991b1b;">%2$s</a>',
                    esc_url( $del_url ),
                    esc_html__( 'Delete permanently', 'talenttrack' )
                );
            }
            return $buttons === [] ? '' : implode( ' ', $buttons );
        }
        if ( ! current_user_can( 'tt_edit_pdp' ) ) return '';
        return sprintf(
            '<button type="button" class="tt-btn tt-btn-secondary tt-pdp-row-action" data-tt-pdp-archive="%1$d" data-tt-pdp-player="%2$s" style="min-height:48px;min-width:48px;padding:8px 12px;touch-action:manipulation;">%3$s</button>',
            $file_id,
            esc_attr( $player_name ),
            esc_html__( 'Archive', 'talenttrack' )
        );
    }

    /**
     * v3.110.110 — checkmark glyph for the ack columns. Grey when not
     * received, green when received. Single inline-SVG so it renders
     * accessibly without a webfont and respects user dark/light themes
     * via `currentColor`.
     */
    private static function ack_checkmark( bool $received, string $aria_label ): string {
        $colour = $received ? '#16a34a' : '#94a3b8'; // green-600 / slate-400
        $title  = $received
            ? sprintf( /* translators: %s = parent / player */ __( '%s received', 'talenttrack' ), $aria_label )
            : sprintf( /* translators: %s = parent / player */ __( '%s not yet received', 'talenttrack' ), $aria_label );
        return '<span title="' . esc_attr( $title ) . '" aria-label="' . esc_attr( $title ) . '" style="display:inline-flex; align-items:center; justify-content:center; width:20px; height:20px;">'
             . '<svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true" style="color:' . esc_attr( $colour ) . ';">'
             . '<path fill="currentColor" d="M6.173 11.207 3.207 8.241l1.06-1.06 1.906 1.905 4.56-4.56 1.06 1.06z"/>'
             . '</svg>'
             . '</span>';
    }

    /**
     * POST /pdp-files — body: { player_id, season_id?, owner_coach_id?,
     * cycle_size?, notes? }. Creates the file and the conversation cycle.
     */
    public static function create( \WP_REST_Request $r ): \WP_REST_Response {
        $player_id = absint( $r['player_id'] ?? 0 );
        if ( $player_id <= 0 ) {
            return RestResponse::error( 'missing_fields',
                __( 'Player is required.', 'talenttrack' ), 400 );
        }

        $seasons = new SeasonsRepository();
        $season_id = absint( $r['season_id'] ?? 0 );
        $season    = $season_id > 0 ? $seasons->find( $season_id ) : $seasons->current();
        if ( ! $season ) {
            return RestResponse::error( 'no_season',
                __( 'No current season is set. Configure a season before creating PDP files.', 'talenttrack' ), 400 );
        }

        // Coach-scope guard: holders without global PDP edit must own
        // the player. Global holders (HoD / academy admin / WP admin)
        // bypass.
        if ( ! self::hasGlobalPdpAccess( 'change' ) ) {
            if ( ! QueryHelpers::coach_owns_player( get_current_user_id(), $player_id ) ) {
                return RestResponse::error( 'forbidden',
                    __( 'You can only create PDP files for players on your own teams.', 'talenttrack' ), 403 );
            }
        }

        $cycle_size = self::resolveCycleSize( $r, $player_id );
        $owner_id   = absint( $r['owner_coach_id'] ?? get_current_user_id() );

        $files = new PdpFilesRepository();
        $file_id = $files->create( [
            'player_id'      => $player_id,
            'season_id'      => (int) $season->id,
            'owner_coach_id' => $owner_id,
            'cycle_size'     => $cycle_size,
            'notes'          => (string) ( $r['notes'] ?? '' ),
        ] );
        if ( $file_id <= 0 ) {
            return RestResponse::error( 'duplicate_or_failed',
                __( 'A PDP file already exists for this player and season, or the database refused the write.', 'talenttrack' ),
                409 );
        }

        $convs    = new PdpConversationsRepository();
        $created  = $convs->createCycle( $file_id, $cycle_size, (string) $season->start_date, (string) $season->end_date, (int) $season->id );
        if ( $created !== $cycle_size ) {
            Logger::error( 'pdp.cycle.partial', [
                'file_id'   => $file_id,
                'expected'  => $cycle_size,
                'created'   => $created,
            ] );
        }

        // Calendar link per conversation. Sprint 1 native writer no-ops
        // beyond inserting a tt_pdp_calendar_links row; the Spond writer
        // (#0031) replaces the implementation, not the call site.
        $writer = PdpCalendarWriters::default();
        foreach ( $convs->listForFile( $file_id ) as $c ) {
            $writer->onConversationScheduled( (int) $c->id );
        }

        return RestResponse::success( [
            'id'                  => $file_id,
            'cycle_size'          => $cycle_size,
            'conversations_seeded' => $created,
        ] );
    }

    public static function get_one( \WP_REST_Request $r ): \WP_REST_Response {
        $id = absint( $r['id'] );
        if ( $id <= 0 ) {
            return RestResponse::error( 'bad_id', __( 'Invalid PDP file id.', 'talenttrack' ), 400 );
        }
        $files = new PdpFilesRepository();
        $file  = $files->find( $id );
        if ( ! $file ) {
            return RestResponse::error( 'not_found', __( 'PDP file not found.', 'talenttrack' ), 404 );
        }
        if ( ! self::canSeeFile( $file ) ) {
            return RestResponse::error( 'forbidden',
                __( 'You do not have access to this PDP file.', 'talenttrack' ), 403 );
        }

        $conversations = ( new PdpConversationsRepository() )->listForFile( $id );

        return RestResponse::success( [
            'file'          => self::format_file( $file ),
            'conversations' => array_map( [ __CLASS__, 'format_conversation' ], $conversations ),
        ] );
    }

    /** PATCH /pdp-files/{id} — owner_coach_id and/or status. */
    public static function patch( \WP_REST_Request $r ): \WP_REST_Response {
        $id = absint( $r['id'] );
        if ( $id <= 0 ) {
            return RestResponse::error( 'bad_id', __( 'Invalid PDP file id.', 'talenttrack' ), 400 );
        }
        $files = new PdpFilesRepository();
        $file  = $files->find( $id );
        if ( ! $file ) {
            return RestResponse::error( 'not_found', __( 'PDP file not found.', 'talenttrack' ), 404 );
        }
        if ( ! self::canEditFile( $file ) ) {
            return RestResponse::error( 'forbidden',
                __( 'You do not have permission to edit this PDP file.', 'talenttrack' ), 403 );
        }

        $changed = false;
        if ( array_key_exists( 'owner_coach_id', (array) $r->get_params() ) ) {
            $owner = $r['owner_coach_id'];
            $owner_id = ( $owner === null || $owner === '' ) ? null : absint( $owner );
            if ( ! $files->setOwner( $id, $owner_id ) ) {
                return RestResponse::error( 'db_error',
                    __( 'Owner update failed.', 'talenttrack' ), 500 );
            }
            $changed = true;
        }
        if ( isset( $r['status'] ) ) {
            $status = sanitize_text_field( (string) $r['status'] );
            if ( ! $files->setStatus( $id, $status ) ) {
                return RestResponse::error( 'bad_status',
                    __( 'Invalid status. Use open, completed, or archived.', 'talenttrack' ), 400 );
            }
            $changed = true;
        }
        if ( ! $changed ) {
            return RestResponse::success( [ 'id' => $id, 'unchanged' => true ] );
        }
        return RestResponse::success( [ 'id' => $id ] );
    }

    /**
     * #1274 PR1 — DELETE /pdp-files/{id} → soft archive.
     *
     * Mirrors EvaluationsRestController::delete_eval (also a soft-
     * delete). 404 when the row is gone OR already archived; 200 on
     * a successful archive write.
     */
    public static function archive( \WP_REST_Request $r ): \WP_REST_Response {
        $id = absint( $r['id'] );
        if ( $id <= 0 ) {
            return RestResponse::error( 'bad_id', __( 'Invalid PDP file id.', 'talenttrack' ), 400 );
        }
        $files = new PdpFilesRepository();
        $file  = $files->find( $id );
        if ( ! $file ) {
            return RestResponse::error( 'not_found', __( 'PDP file not found or already archived.', 'talenttrack' ), 404 );
        }
        if ( ! self::canEditFile( $file ) ) {
            return RestResponse::error( 'forbidden',
                __( 'You do not have permission to archive this PDP file.', 'talenttrack' ), 403 );
        }
        if ( ! $files->archive( $id ) ) {
            return RestResponse::error( 'archive_failed',
                __( 'Could not archive the PDP file.', 'talenttrack' ), 500 );
        }
        Logger::info( 'pdp.file.archived', [
            'pdp_file_id' => $id,
            'player_id'   => (int) ( $file->player_id ?? 0 ),
            'by_user'     => get_current_user_id(),
        ] );
        return RestResponse::success( [ 'id' => $id, 'archived' => true ] );
    }

    /**
     * #1274 PR1 — POST /pdp-files/{id}/restore → un-archive.
     *
     * Cap-gated on `tt_unarchive_pdp` (admin-only by default seed).
     */
    public static function restore( \WP_REST_Request $r ): \WP_REST_Response {
        $id = absint( $r['id'] );
        if ( $id <= 0 ) {
            return RestResponse::error( 'bad_id', __( 'Invalid PDP file id.', 'talenttrack' ), 400 );
        }
        $files = new PdpFilesRepository();
        // include_archived=true so we can find the row to restore.
        $file = $files->find( $id, true );
        if ( ! $file ) {
            return RestResponse::error( 'not_found', __( 'PDP file not found.', 'talenttrack' ), 404 );
        }
        if ( ! $files->restore( $id ) ) {
            return RestResponse::error( 'restore_failed',
                __( 'Could not restore the PDP file.', 'talenttrack' ), 500 );
        }
        Logger::info( 'pdp.file.restored', [
            'pdp_file_id' => $id,
            'player_id'   => (int) ( $file->player_id ?? 0 ),
            'by_user'     => get_current_user_id(),
        ] );
        return RestResponse::success( [ 'id' => $id, 'restored' => true ] );
    }

    /**
     * #1274 PR3 — POST /pdp-files/{id}/permanent-delete → irreversible
     * cascade delete across five tables via PdpCascadeDeleter. Caller
     * must hold `tt_delete_pdp` (admin only by seed). Returns the
     * per-table row counts so the calling UI can confirm what
     * vanished. The double-confirm UX (typed slug) lives on the
     * calling surface; this endpoint is the primitive.
     */
    public static function permanent_delete( \WP_REST_Request $r ): \WP_REST_Response {
        $id = absint( $r['id'] );
        if ( $id <= 0 ) {
            return RestResponse::error( 'bad_id', __( 'Invalid PDP file id.', 'talenttrack' ), 400 );
        }
        $files = new PdpFilesRepository();
        // include_archived=true — the operator may be purging an
        // already-archived PDP (the common data-retention case is
        // "archive first, then permanently delete later").
        $file = $files->find( $id, true );
        if ( ! $file ) {
            return RestResponse::error( 'not_found', __( 'PDP file not found.', 'talenttrack' ), 404 );
        }
        try {
            $deleted = ( new \TT\Modules\Pdp\PdpCascadeDeleter() )->deletePdpFile( $id );
        } catch ( \Throwable $e ) {
            return RestResponse::error( 'cascade_failed',
                __( 'Could not permanently delete the PDP file. The transaction was rolled back; nothing changed.', 'talenttrack' ),
                500
            );
        }
        return RestResponse::success( [
            'id'      => $id,
            'deleted' => $deleted,
        ] );
    }

    private static function resolveCycleSize( \WP_REST_Request $r, int $player_id ): int {
        $explicit = absint( $r['cycle_size'] ?? 0 );
        if ( in_array( $explicit, [ 2, 3, 4 ], true ) ) return $explicit;

        // Per-team override → club default → 3.
        global $wpdb; $p = $wpdb->prefix;
        $team_size = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT t.pdp_cycle_size
               FROM {$p}tt_players pl
               LEFT JOIN {$p}tt_teams t ON t.id = pl.team_id
              WHERE pl.id = %d",
            $player_id
        ) );
        if ( in_array( $team_size, [ 2, 3, 4 ], true ) ) return $team_size;

        $club_default = (int) QueryHelpers::get_config( 'pdp_cycle_default', '3' );
        return in_array( $club_default, [ 2, 3, 4 ], true ) ? $club_default : 3;
    }

    private static function canSeeFile( object $file ): bool {
        // #1923 - the canonical ladder now lives in PdpAccess so the
        // frontend + verdicts surfaces share one decision. Behaviour is
        // identical to the inlined #0080 Wave C3 ladder this replaces.
        return \TT\Modules\Pdp\PdpAccess::canSeeFile(
            get_current_user_id(), (int) $file->player_id
        );
    }

    private static function canEditFile( object $file ): bool {
        // #1923 - shared PdpAccess decision; identical to the inlined ladder.
        return \TT\Modules\Pdp\PdpAccess::canEditFile(
            get_current_user_id(), (int) $file->player_id
        );
    }

    /**
     * #0080 Wave C3 — replacement for the legacy "is admin?" proxy
     * (`current_user_can('tt_edit_settings')`) used to bypass the
     * coach-ownership ladder. Four sources, in order:
     *   1. Matrix grant: `pdp_file/<activity>/global` — the precise
     *      semantic ("user has unrestricted PDP access").
     *   2. WordPress site admin (`manage_options`) — portable fallback
     *      for installs whose matrix is dormant or partially seeded.
     *   3. Legacy umbrella `tt_edit_settings` — preserved for
     *      back-compat with v3.0 callers; the CapabilityAliases
     *      roll-up still grants this when the user holds every
     *      settings sub-cap.
     *   4. v3.110.112 — global-reader personas. The HoD and academy
     *      admin personas are designed to have academy-wide scope on
     *      every player-development surface (PDP files, evaluations,
     *      goals, etc.). On installs whose MatrixGate matrix is
     *      dormant (no rows seeded), step 1 returns false and the HoD
     *      drops through to coach-scoping — returning zero files
     *      because the HoD isn't a coach. This step explicitly
     *      recognises the persona instead of relying on the matrix.
     *      Pilot symptom: "POP verdicts pending" KPI link landed on
     *      an empty list for the HoD.
     *
     * @param string $activity 'read' | 'change'
     */
    private static function hasGlobalPdpAccess( string $activity ): bool {
        $uid = get_current_user_id();
        if ( $uid > 0 && class_exists( '\\TT\\Modules\\Authorization\\MatrixGate' ) ) {
            $matrix_activity = $activity === 'read' ? MatrixGate::READ : MatrixGate::CHANGE;
            if ( MatrixGate::can( $uid, 'pdp_file', $matrix_activity, MatrixGate::SCOPE_GLOBAL ) ) {
                return true;
            }
        }
        if ( current_user_can( 'manage_options' ) ) return true;
        if ( current_user_can( 'tt_edit_settings' ) ) return true;

        // Persona-based fallback. `read` is granted to all global readers;
        // `change` is restricted to HoD + academy_admin (Club Admin) —
        // mirrors the FunctionalRoles seed which gives those personas
        // PDP edit reach.
        if ( $uid > 0 && class_exists( '\\TT\\Modules\\Authorization\\PersonaResolver' ) ) {
            $personas = \TT\Modules\Authorization\PersonaResolver::personasFor( $uid );
            $global_readers = [ 'head_of_development', 'academy_admin' ];
            foreach ( $personas as $p ) {
                if ( in_array( $p, $global_readers, true ) ) return true;
            }
        }
        return false;
    }

    /** @return array<string,mixed> */
    private static function format_file( object $row ): array {
        return [
            'id'             => (int) $row->id,
            'player_id'      => (int) $row->player_id,
            'season_id'      => (int) $row->season_id,
            'owner_coach_id' => $row->owner_coach_id !== null ? (int) $row->owner_coach_id : null,
            'cycle_size'     => $row->cycle_size !== null ? (int) $row->cycle_size : null,
            'status'         => (string) $row->status,
            'notes'          => $row->notes,
            'created_at'     => $row->created_at,
            'updated_at'     => $row->updated_at,
        ];
    }

    /**
     * #3306 (epic #3301) — `agenda` no longer appears in this payload. A
     * player's and a parent's token both reach this endpoint, and the
     * column's content is now the coach's private preparation, which they
     * must not be able to read from anywhere. The preparation has its own
     * resource at `/pdp-conversations/{id}/prep`, gated to coach + head of
     * academy.
     *
     * @return array<string,mixed>
     */
    private static function format_conversation( object $row ): array {
        return [
            'id'                => (int) $row->id,
            'pdp_file_id'       => (int) $row->pdp_file_id,
            'sequence'          => (int) $row->sequence,
            'template_key'      => (string) $row->template_key,
            'scheduled_at'      => $row->scheduled_at,
            'conducted_at'      => $row->conducted_at,
            'notes'             => $row->notes,
            'agreed_actions'    => $row->agreed_actions,
            'player_reflection' => $row->player_reflection,
            'coach_signoff_at'  => $row->coach_signoff_at,
            'parent_ack_at'     => $row->parent_ack_at,
            'player_ack_at'     => $row->player_ack_at,
        ];
    }
}
