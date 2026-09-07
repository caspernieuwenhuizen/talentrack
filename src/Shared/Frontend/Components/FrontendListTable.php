<?php
namespace TT\Shared\Frontend\Components;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * FrontendListTable — reusable list/search/filter/sort/paginate
 * component, backed by REST.
 *
 * #0019 Sprint 2 keystone. Sessions + Goals are the first consumers
 * (Sprint 2 sessions 2.3 + 2.4); Players + Teams (Sprint 3), People
 * (Sprint 4), and admin-tier (Sprint 5) reuse the same component.
 * The whole epic's frontend velocity downstream depends on the API
 * here being clean.
 *
 * Architecture:
 *   - Server renders the shell (filter form, table head, an initial
 *     row payload as JSON in a `<script type="application/json">`
 *     tag, pagination scaffolding) for the requested filter / page.
 *     No-JS users get the initial page and a working filter form
 *     that posts back as a full page reload.
 *   - `assets/js/components/frontend-list-table.js` hydrates: takes
 *     over filter changes, sort, pagination + per-page selector, and
 *     keeps the URL querystring in sync via `history.replaceState`.
 *
 * Usage:
 *
 *   FrontendListTable::render([
 *     'rest_path' => 'activities',
 *     'columns'   => [
 *       'session_date' => [ 'label' => __('Date',  'talenttrack'), 'sortable' => true ],
 *       'team_name'    => [ 'label' => __('Team',  'talenttrack') ],
 *       'title'        => [ 'label' => __('Title', 'talenttrack'), 'sortable' => true ],
 *       'attendance'   => [ 'label' => __('Att.%', 'talenttrack'), 'sortable' => true, 'render' => 'percent', 'value_key' => 'attendance_pct' ],
 *     ],
 *     'filters' => [
 *       'team_id' => [ 'type' => 'select', 'label' => __('Team', 'talenttrack'), 'options' => $team_options ],
 *       'date'    => [ 'type' => 'date_range', 'label_from' => __('From', 'talenttrack'), 'label_to' => __('To', 'talenttrack'), 'param_from' => 'date_from', 'param_to' => 'date_to' ],
 *     ],
 *     'row_actions' => [
 *       'edit'   => [ 'label' => __('Edit',   'talenttrack'), 'href' => '?tt_view=activities&edit={id}' ],
 *       'delete' => [ 'label' => __('Delete', 'talenttrack'), 'rest_method' => 'DELETE', 'rest_path' => 'activities/{id}', 'confirm' => __('Delete this activity?', 'talenttrack') ],
 *     ],
 *     'empty_state' => __('No activities match your filters.', 'talenttrack'),
 *     'search'      => [ 'placeholder' => __('Search sessions…', 'talenttrack') ],
 *     'default_sort' => [ 'orderby' => 'session_date', 'order' => 'desc' ],
 *   ]);
 *
 * Column render keys (built-in, more can be added later):
 *   'text'    — escape and print (default).
 *   'date'    — `Y-m-d` rendered as locale date.
 *   'percent' — append `%` if non-null, otherwise em-dash.
 *
 * Filter `type` values supported:
 *   'select'     — single-select dropdown. Requires `options` (value=>label).
 *   'date_range' — two date inputs; param_from / param_to override the
 *                  default `<key>_from` / `<key>_to` URL keys.
 *   'text'       — free-text input bound to `filter[<key>]`.
 *
 * Each instance gets a unique DOM id so multiple list tables can
 * coexist on the same view.
 */
class FrontendListTable {

    /**
     * @param array<string, mixed> $config
     */
    public static function render( array $config ): string {
        $id            = 'tt-list-' . substr( md5( (string) ( $config['rest_path'] ?? wp_rand() ) . wp_rand() ), 0, 8 );
        $rest_path     = (string) ( $config['rest_path'] ?? '' );
        $columns       = is_array( $config['columns']     ?? null ) ? $config['columns']     : [];
        $filters       = is_array( $config['filters']     ?? null ) ? $config['filters']     : [];
        $row_actions   = is_array( $config['row_actions'] ?? null ) ? $config['row_actions'] : [];
        $empty_state   = (string) ( $config['empty_state'] ?? __( 'No results.', 'talenttrack' ) );
        // #1362 — optional EmptyStateCard config (icon / headline /
        // explainer / cta_label / cta_url / cta_cap). Pre-rendered
        // server-side so the capability check on the CTA happens in
        // PHP; the JS hydrator swaps it in for the bare `empty_state`
        // string — but ONLY when no search/filter is active. "Nothing
        // matches your filters" keeps the plain text; "you have
        // nothing yet" (the fresh-install case) gets the guided card.
        $empty_card      = is_array( $config['empty_state_card'] ?? null ) ? $config['empty_state_card'] : [];
        $empty_card_html = '';
        if ( $empty_card ) {
            ob_start();
            EmptyStateCard::render( $empty_card );
            $empty_card_html = trim( (string) ob_get_clean() );
        }
        $search_cfg    = is_array( $config['search']      ?? null ) ? $config['search']      : [];
        $default_sort  = is_array( $config['default_sort']?? null ) ? $config['default_sort']: [];
        $per_page_opts = is_array( $config['per_page_options'] ?? null ) ? $config['per_page_options'] : [ 10, 25, 50, 100 ];
        // v3.92.7 — server-locked filter values that aren't surfaced as
        // user-editable controls but ARE sent on every REST request.
        // Used by surfaces like `?tt_view=my-activities` that need a
        // permanent server-side scope (player_id) without exposing it
        // as a UI control. Merged into the request `filter[…]` payload
        // by the JS hydrator at fetch time.
        $static_filters = is_array( $config['static_filters'] ?? null ) ? $config['static_filters'] : [];
        // v3.110.169 (#758) — row-link standard. When set, the JS
        // hydrator stamps each <tr> with `data-row-href` + class
        // `is-row-link` + role/tabindex from the row's value at this
        // key (a REST controller field, typically `'detail_url'`).
        // The delegated click handler navigates the row UNLESS the
        // click target is an interactive descendant (link, button,
        // input, select, etc.), preserving per-column links.
        $row_url_key = isset( $config['row_url_key'] ) ? sanitize_key( (string) $config['row_url_key'] ) : '';

        // #1614 — optional card-grid layout. When `layout` is 'cards',
        // the component renders a responsive `.tt-card-grid` shell
        // instead of the `<table>`; the JS hydrator emits each row's
        // `card_value_key` HTML fragment (a server-rendered whole-card
        // <a>) into the grid, while the same filter / search / sort /
        // pager chrome above + below keeps working. `columns` is still
        // required (it drives no-JS sort links + the JS sort config) but
        // is not rendered as a table head in card mode.
        $layout = ( ( $config['layout'] ?? 'table' ) === 'cards' ) ? 'cards' : 'table';
        $card_value_key = isset( $config['card_value_key'] ) ? sanitize_key( (string) $config['card_value_key'] ) : 'card_html';
        // In card mode, sorting is offered as a single dropdown above the
        // grid rather than clickable column headers. Each entry:
        // [ 'label' => string, 'orderby' => string, 'order' => 'asc'|'desc' ].
        $sort_options = is_array( $config['sort_options'] ?? null ) ? $config['sort_options'] : [];
        // #2449 — opt-in personal saved views: [ 'key' => '<registered key>' ].
        // Omitted → no strip, no assets, exactly as before.
        $saved_views  = is_array( $config['saved_views'] ?? null ) ? $config['saved_views'] : [];

        if ( $rest_path === '' || ! $columns ) return '';

        $state = self::stateFromQuery( $filters, $default_sort );

        // #2202 — a filter may declare a `default` value that seeds the
        // initial state (e.g. goals default to the Active status bucket).
        // A seeded default is NOT a user-chosen query, so it must not flip
        // the "guided empty state vs. no-match message" decision — otherwise
        // a fresh, goal-less install would never show the onboarding card.
        // Build the default map + the user-chosen filter subset (state minus
        // any value that merely equals its default) for the no-query test,
        // shared with the JS hydrator via `default_filters`.
        $default_filters = [];
        foreach ( $filters as $fkey => $fcfg ) {
            if ( isset( $fcfg['default'] ) ) {
                $default_filters[ (string) $fkey ] = (string) $fcfg['default'];
            }
        }
        $state_filter = is_array( $state['filter'] ?? null ) ? $state['filter'] : [];
        $user_filter  = [];
        foreach ( $state_filter as $fk => $fv ) {
            if ( isset( $default_filters[ (string) $fk ] ) && (string) $fv === $default_filters[ (string) $fk ] ) {
                continue; // seeded default, not a user query
            }
            $user_filter[ (string) $fk ] = $fv;
        }

        // Declarative config that the JS hydrator will consume — keeps
        // PHP and JS in sync without the JS having to inspect markup.
        $js_config = [
            'rest_path'        => $rest_path,
            'columns'          => self::columnsForJs( $columns ),
            'filters'          => self::filtersForJs( $filters ),
            // #2202 — seeded filter defaults, so the hydrator can tell a
            // default apart from a user-chosen filter for its empty-state
            // (guided card vs. no-match) decision.
            'default_filters'  => $default_filters,
            'static_filters'   => self::sanitizeStaticFilters( $static_filters ),
            'row_actions'      => self::rowActionsForJs( $row_actions ),
            'row_url_key'      => $row_url_key,
            // #1614 — card-grid layout config (no-op for table consumers).
            'layout'           => $layout,
            'card_value_key'   => $card_value_key,
            'empty_html'       => $empty_card_html,
            'per_page_options' => array_values( $per_page_opts ),
            'default_sort'     => [
                'orderby' => (string) ( $default_sort['orderby'] ?? '' ),
                'order'   => (string) ( $default_sort['order']   ?? 'asc' ),
            ],
            'i18n'             => [
                'empty'   => $empty_state,
                'loading' => __( 'Loading…', 'talenttrack' ),
                'error'   => __( 'Could not load — retry?', 'talenttrack' ),
                'retry'   => __( 'Retry', 'talenttrack' ),
                'page_of' => __( 'Page %1$d of %2$d', 'talenttrack' ),
                'showing' => __( 'Showing %1$d–%2$d of %3$d', 'talenttrack' ),
                'per_page'=> __( 'Per page', 'talenttrack' ),
                'no_op'   => __( '—', 'talenttrack' ),
            ],
        ];

        $config_json = wp_json_encode( $js_config );
        $state_json  = wp_json_encode( $state );

        ob_start();
        ?>
        <div class="tt-list-table" id="<?php echo esc_attr( $id ); ?>" data-tt-list-table="1">
            <?php
            // #2082 — the bespoke `.tt-list-table-filters` form is replaced
            // by the shared, mobile-first FilterBar chrome (inline row at
            // >=1024px, collapsing to a "Filters" button + bottom sheet
            // below). The list table still owns rows / sort / pagination /
            // per-page; only the filter chrome moves. The contract is
            // preserved exactly: `filter[<key>]` param names, the `search`
            // box, static filters, sort, pagination and JS hydration are
            // unchanged — the bar's own <form> carries the `data-tt-list-form`
            // hook the hydrator binds to, so live-filtering and the no-JS
            // full-submit fallback both keep working.
            $filterbar_args = self::buildFilterBarArgs( $filters, $search_cfg, $state, $layout, $sort_options, $saved_views );
            // No groups (no filters, no search, no card-sort) → nothing to
            // filter, so skip the bar entirely rather than render an empty
            // "Filters" button + sheet. Sort / pager / per-page bind on
            // their own elements, so the list still hydrates.
            if ( ! empty( $filterbar_args['groups'] ) || $filterbar_args['extra_controls'] !== '' ) {
                echo FilterBar::html( $filterbar_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- FilterBar escapes internally.
            }
            ?>

            <div class="tt-list-table-status" data-tt-list-status="1" aria-live="polite"></div>

            <?php if ( $layout === 'cards' ) : ?>
                <div class="tt-card-grid" data-tt-list-body="1" data-tt-list-cardgrid="1">
                    <div class="tt-list-table-empty" data-tt-list-empty="1"><?php
                        $no_query = $state['search'] === '' && empty( $user_filter );
                        if ( $empty_card_html !== '' && $no_query ) {
                            echo $empty_card_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — EmptyStateCard escapes internally.
                        } else {
                            echo esc_html( $empty_state );
                        }
                    ?></div>
                </div>
            <?php else : ?>
            <div class="tt-list-table-wrap">
                <table class="tt-list-table-table" data-tt-list-table-el="1">
                    <thead><tr><?php foreach ( $columns as $key => $col ) : self::renderColumnHeader( (string) $key, $col, $state ); endforeach; ?>
                        <?php if ( $row_actions ) : ?><th class="tt-list-table-actions-col"><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'talenttrack' ); ?></span></th><?php endif; ?>
                    </tr></thead>
                    <tbody data-tt-list-body="1">
                        <tr class="tt-list-table-empty" data-tt-list-empty="1"><td colspan="<?php echo (int) ( count( $columns ) + ( $row_actions ? 1 : 0 ) ); ?>"><?php
                            // #1362 — no-JS shell mirrors the hydrator's choice:
                            // guided card only when no query is active. #2202 —
                            // a seeded filter default doesn't count as a query.
                            $no_query = $state['search'] === '' && empty( $user_filter );
                            if ( $empty_card_html !== '' && $no_query ) {
                                echo $empty_card_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — EmptyStateCard escapes internally.
                            } else {
                                echo esc_html( $empty_state );
                            }
                        ?></td></tr>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <div class="tt-list-table-footer" data-tt-list-footer="1">
                <span class="tt-list-table-summary" data-tt-list-summary="1"></span>
                <label class="tt-list-table-perpage">
                    <span><?php esc_html_e( 'Per page', 'talenttrack' ); ?></span>
                    <select data-tt-list-perpage="1">
                        <?php foreach ( $per_page_opts as $opt ) : ?>
                            <option value="<?php echo (int) $opt; ?>" <?php selected( (int) $state['per_page'], (int) $opt ); ?>><?php echo (int) $opt; ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <span class="tt-list-table-pager" data-tt-list-pager="1"></span>
            </div>

            <script type="application/json" data-tt-list-config="1"><?php echo $config_json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></script>
            <script type="application/json" data-tt-list-state="1"><?php echo $state_json;  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></script>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    /**
     * Build the initial state from `$_GET` so a bookmarked URL renders
     * the right slice on first paint.
     *
     * @param array<string,mixed> $filters
     * @param array<string,mixed> $default_sort
     * @return array<string,mixed>
     */
    private static function stateFromQuery( array $filters, array $default_sort ): array {
        $get_filter = isset( $_GET['filter'] ) && is_array( $_GET['filter'] ) ? $_GET['filter'] : [];
        $clean_filter = [];
        foreach ( $filters as $key => $cfg ) {
            $type = (string) ( $cfg['type'] ?? 'text' );
            if ( $type === 'date_range' ) {
                $from = (string) ( $cfg['param_from'] ?? ( $key . '_from' ) );
                $to   = (string) ( $cfg['param_to']   ?? ( $key . '_to' ) );
                if ( ! empty( $get_filter[ $from ] ) ) $clean_filter[ $from ] = sanitize_text_field( wp_unslash( (string) $get_filter[ $from ] ) );
                if ( ! empty( $get_filter[ $to ] ) )   $clean_filter[ $to ]   = sanitize_text_field( wp_unslash( (string) $get_filter[ $to ] ) );
            } elseif ( ! empty( $get_filter[ $key ] ) ) {
                $clean_filter[ $key ] = sanitize_text_field( wp_unslash( (string) $get_filter[ $key ] ) );
            } elseif ( isset( $cfg['default'] ) && ! isset( $get_filter[ $key ] ) ) {
                // #2202 — a filter can declare a `default` value applied when
                // the URL carries no explicit selection for it. Seeds the
                // initial state so the JS hydrator's first fetch and the
                // rendered pill both reflect the default (e.g. goals default
                // to the Active status bucket). An explicit empty value in the
                // URL (`filter[<key>]=`) still means "no selection"; only a
                // wholly absent key falls back to the default.
                $clean_filter[ $key ] = sanitize_text_field( (string) $cfg['default'] );
            }
        }
        return [
            'search'   => isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['search'] ) ) : '',
            'filter'   => $clean_filter,
            'orderby'  => isset( $_GET['orderby'] ) ? sanitize_key( (string) $_GET['orderby'] ) : (string) ( $default_sort['orderby'] ?? '' ),
            'order'    => isset( $_GET['order'] ) && in_array( strtolower( (string) $_GET['order'] ), [ 'asc', 'desc' ], true )
                ? strtolower( (string) $_GET['order'] )
                : (string) ( $default_sort['order'] ?? 'asc' ),
            'page'     => max( 1, isset( $_GET['page'] ) ? absint( $_GET['page'] ) : 1 ),
            'per_page' => isset( $_GET['per_page'] ) ? absint( $_GET['per_page'] ) : 25,
        ];
    }

    /**
     * Pass-through query args that should survive a no-JS form submit.
     * Right now: anything that isn't owned by the list table itself.
     *
     * @param array<string,mixed> $filters
     * @return array<string,string>
     */
    private static function passthroughQueryArgs( array $filters ): array {
        $owned = [ 'search', 'filter', 'orderby', 'order', 'page', 'per_page' ];
        $out   = [];
        foreach ( $_GET as $k => $v ) {
            if ( in_array( (string) $k, $owned, true ) ) continue;
            if ( is_array( $v ) ) continue;
            $out[ (string) $k ] = sanitize_text_field( wp_unslash( (string) $v ) );
        }
        return $out;
    }

    /**
     * #2082 — translate the list table's `filters` config + `search` box
     * into the shared FilterBar `groups` payload, and assemble the rest
     * of the FilterBar args (hidden passthrough fields, active-count +
     * chips for the mobile collapsed state, reset URL, the card-mode sort
     * dropdown as an extra control, and the `data-tt-list-form` hook the
     * hydrator binds to).
     *
     * The filter contract is preserved exactly:
     *   - `select`     → FilterBar `select` group, `name="filter[<key>]"`.
     *                    Opt a select into status pills with
     *                    `'render' => 'status'` on the filter config.
     *   - `text`       → FilterBar `text` group, `name="filter[<key>]"`.
     *   - `date_range` → FilterBar `date_range` group, two date fields
     *                    keyed on `param_from` / `param_to`.
     *   - the list `search` box → a FilterBar `text`/`search` group named
     *                    `search` (not `filter[…]`), exactly as before.
     *
     * @param array<string,mixed>  $filters
     * @param array<string,mixed>  $search_cfg
     * @param array<string,mixed>  $state
     * @param string               $layout
     * @param array<int,mixed>     $sort_options
     * @return array<string,mixed>
     */
    private static function buildFilterBarArgs( array $filters, array $search_cfg, array $state, string $layout, array $sort_options, array $saved_views = [] ): array {
        $current = is_array( $state['filter'] ?? null ) ? $state['filter'] : [];
        $groups  = [];

        // --- Search box first (mirrors the old layout order). ----------
        if ( ! empty( $search_cfg ) ) {
            $placeholder = (string) ( $search_cfg['placeholder'] ?? __( 'Search…', 'talenttrack' ) );
            $groups[] = [
                'type'        => 'text',
                'key'         => 'search',
                'label'       => __( 'Search', 'talenttrack' ),
                'name'        => 'search',
                'value'       => (string) ( $state['search'] ?? '' ),
                'placeholder' => $placeholder,
                'input_type'  => 'search',
                'inputmode'   => 'search',
            ];
        }

        // --- Each declared filter. -------------------------------------
        foreach ( $filters as $key => $filter ) {
            $key   = (string) $key;
            $type  = (string) ( $filter['type']  ?? 'text' );
            $label = (string) ( $filter['label'] ?? $key );

            // #3332 — a player filter is a type-to-filter picker, not a
            // dropdown of every player in the academy. The list surfaces name
            // their filters `filter[<key>]`, and the picker's hidden input
            // dispatches a bubbling `change`, which is the event this table's
            // hydrator already live-filters on.
            if ( $type === 'player' ) {
                $groups[] = [
                    'type'     => 'player',
                    'key'      => $key,
                    'label'    => $label,
                    'name'     => 'filter[' . $key . ']',
                    'selected' => (int) ( $current[ $key ] ?? 0 ),
                    'team_id'  => (int) ( $filter['team_id'] ?? 0 ),
                    'user_id'  => (int) ( $filter['user_id'] ?? get_current_user_id() ),
                    'is_admin' => ! empty( $filter['is_admin'] ),
                ];
                continue;
            }

            if ( $type === 'select' ) {
                $opts   = is_array( $filter['options'] ?? null ) ? $filter['options'] : [];
                $render = (string) ( $filter['render'] ?? 'select' );
                $sel    = (string) ( $current[ $key ] ?? '' );

                // #2082 — `render => 'status'` opts a select into FilterBar
                // status pills (link-based, full-reload). Default stays a
                // plain select so existing views are unchanged.
                if ( $render === 'status' ) {
                    // #2622 — the archive-state filter collapses to an icon
                    // overflow menu. It is identifiable by key alone because
                    // #2625 normalised every list on `archived`; `status` is a
                    // domain filter (goals' Achieved/Missed, a player's trial
                    // state) and keeps its one-tap pills.
                    if ( $key === self::ARCHIVE_FILTER_KEY ) {
                        $groups[] = self::archiveMenuGroup( $key, $opts, $sel );
                        continue;
                    }
                    // #2202 — a status filter can drop the leading "All" pill
                    // (`no_all => true`) when the view wants a mandatory
                    // selection with a seeded default (goals default to Active).
                    $groups[] = self::statusGroup( $key, $label, $opts, $sel, ! empty( $filter['no_all'] ) );
                    continue;
                }

                $groups[] = [
                    'type'        => 'select',
                    'key'         => $key,
                    'label'       => $label,
                    'name'        => 'filter[' . $key . ']',
                    'selected'    => $sel,
                    'placeholder' => __( 'All', 'talenttrack' ),
                    'options'     => $opts,
                    // The list-table JS handles the change + live-filters,
                    // so opt out of filter-bar.js's own auto-submit (avoids
                    // a double fetch); the no-JS Apply button still submits.
                    'auto_submit' => false,
                ];
            } elseif ( $type === 'date_range' ) {
                $from = (string) ( $filter['param_from'] ?? ( $key . '_from' ) );
                $to   = (string) ( $filter['param_to']   ?? ( $key . '_to' ) );
                $groups[] = [
                    'type'       => 'date_range',
                    'key'        => $key,
                    'label'      => $label,
                    'label_from' => (string) ( $filter['label_from'] ?? __( 'From', 'talenttrack' ) ),
                    'label_to'   => (string) ( $filter['label_to']   ?? __( 'To', 'talenttrack' ) ),
                    'from'       => [ 'name' => 'filter[' . $from . ']', 'value' => (string) ( $current[ $from ] ?? '' ) ],
                    'to'         => [ 'name' => 'filter[' . $to . ']',   'value' => (string) ( $current[ $to ] ?? '' ) ],
                ];
            } else {
                // text (default)
                $groups[] = [
                    'type'  => 'text',
                    'key'   => $key,
                    'label' => $label,
                    'name'  => 'filter[' . $key . ']',
                    'value' => (string) ( $current[ $key ] ?? '' ),
                ];
            }
        }

        // --- Card-mode sort dropdown — injected verbatim into the bar's
        // form (inline row + sheet body) so the hydrator can bind to it.
        $extra = '';
        if ( $layout === 'cards' && $sort_options ) {
            $extra = self::sortSelectHtml( $sort_options, $state );
        }

        // --- Hidden passthrough fields (tt_view, tt_back, …) so the no-JS
        // full submit doesn't drop tile-router / back-nav state.
        $hidden = self::passthroughQueryArgs( $filters );

        // --- Active-count + summary chips for the mobile collapsed bar.
        [ $active_count, $chips ] = self::activeSummary( $filters, $search_cfg, $state );

        $args = [
            'hidden'         => $hidden,
            'active_count'   => $active_count,
            'chips'          => $chips,
            'reset_url'      => self::resetUrl(),
            'form_attrs'     => [ 'data-tt-list-form' => '1' ],
            'extra_controls' => $extra,
            'groups'         => $groups,
        ];

        // #2449 — opt this list into personal saved views. `extra_keys` adds
        // the three params the bar does not own but that are unmistakably
        // part of "the view I set up": the search term and the sort. Without
        // them a saved view would restore the filters but silently reset the
        // sort order, which reads as the view not having worked.
        if ( $saved_views !== [] && ( $saved_views['key'] ?? '' ) !== '' ) {
            $saved_views['extra_keys'] = array_values( array_unique( array_merge(
                [ 'search', 'orderby', 'order' ],
                is_array( $saved_views['extra_keys'] ?? null ) ? $saved_views['extra_keys'] : []
            ) ) );
            $args['saved_views'] = $saved_views;
        }

        return $args;
    }

    /**
     * Build a FilterBar `status` group from a select's options. The pills
     * are link-based (full reload) and set `filter[<key>]=<value>`, with
     * the empty value ("All") clearing the param.
     *
     * @param array<int|string,mixed> $opts value => label
     * @param bool                    $no_all drop the leading "All" pill (#2202)
     * @return array<string,mixed>
     */
    /**
     * The canonical archive-state filter key (#2625). Every list endpoint
     * spells it this way, which is what lets the shared builder recognise the
     * archive filter without a per-view opt-in flag.
     */
    public const ARCHIVE_FILTER_KEY = 'archived';

    /** The archive state a list shows when nothing is asked for. */
    private const ARCHIVE_DEFAULT = 'active';

    /**
     * Build the icon-only overflow menu for the archive-state filter (#2622).
     *
     * Two behaviours differ from `statusGroup()`, both deliberate:
     *
     *   - **no "All" option.** The one the builder used to inject cleared the
     *     param, and every controller reads an absent param as `active`
     *     (ArchiveRepository::sanitizeView()) — so "All" and "Active" returned
     *     identical rows while "All" was the pill highlighted on arrival. The
     *     chrome was claiming to show everything while showing active-only.
     *   - **Active is the default and its URL is param-free**, so the address
     *     bar stays clean on the state 99% of visits are in.
     *
     * @param array<int|string,mixed> $opts value => label
     * @return array<string,mixed>
     */
    private static function archiveMenuGroup( string $key, array $opts, string $selected ): array {
        $base = self::currentQueryArgs();
        unset( $base['filter'][ $key ], $base['page'] );

        if ( $selected === '' ) $selected = self::ARCHIVE_DEFAULT;

        $options = [];
        foreach ( $opts as $value => $text ) {
            $value = (string) $value;
            $args  = $base;
            // The default state carries no param — a clean URL for the common
            // case, and the target the chip's clear action points at.
            if ( $value !== self::ARCHIVE_DEFAULT ) {
                $args['filter'][ $key ] = $value;
            }
            $options[] = [
                'value'  => $value,
                'label'  => (string) $text,
                'url'    => esc_url_raw( add_query_arg( $args ) ),
                'active' => ( $selected === $value ),
            ];
        }

        return [
            'type'          => 'menu',
            'key'           => $key,
            // Not rendered as a visible label — it is the trigger's accessible
            // name, the menu heading and the chip prefix.
            'label'         => __( 'Archive', 'talenttrack' ),
            'default_value' => self::ARCHIVE_DEFAULT,
            'options'       => $options,
            'param'         => 'filter[' . $key . ']',
        ];
    }

    private static function statusGroup( string $key, string $label, array $opts, string $selected, bool $no_all = false ): array {
        $base = self::currentQueryArgs();
        unset( $base['filter'][ $key ], $base['page'] );

        $options = [];
        // Leading "All" (clears the filter) — suppressed when the caller wants
        // a mandatory selection (#2202: the goals status filter defaults to
        // Active and drops "All").
        if ( ! $no_all ) {
            $all_args = $base;
            $options[] = [
                'value'  => '',
                'label'  => __( 'All', 'talenttrack' ),
                'url'    => esc_url_raw( add_query_arg( $all_args ) ),
                'active' => ( $selected === '' ),
                'dot'    => 'all',
            ];
        }
        foreach ( $opts as $value => $text ) {
            $value = (string) $value;
            $args  = $base;
            $args['filter'][ $key ] = $value;
            $options[] = [
                'value'  => $value,
                'label'  => (string) $text,
                'url'    => esc_url_raw( add_query_arg( $args ) ),
                'active' => ( $selected === $value ),
                'dot'    => $value,
            ];
        }

        return [
            'type'    => 'status',
            'key'     => $key,
            'label'   => $label,
            'options' => $options,
            // #2449 — status pills are link-based, so the param name exists
            // only inside each option's URL. Declare it so
            // FilterBar::paramNames() can pick it up for saved views; `key`
            // alone would give the bare filter key, not the `filter[…]` form
            // the pills actually set.
            'param'   => 'filter[' . $key . ']',
        ];
    }

    /**
     * Card-mode sort dropdown, rendered as a labelled select carrying the
     * `data-tt-list-sort-select` hook the hydrator binds to. Returned as a
     * pre-escaped string for FilterBar's `extra_controls` slot.
     *
     * @param array<int,mixed>    $sort_options
     * @param array<string,mixed> $state
     */
    private static function sortSelectHtml( array $sort_options, array $state ): string {
        $cur_sort = (string) ( $state['orderby'] ?? '' ) . ':' . (string) ( $state['order'] ?? 'asc' );
        $out  = '<div class="tt-filterbar__group tt-filterbar__group--sort tt-list-table-sort">';
        $out .= '<span class="tt-filter__glabel">' . esc_html__( 'Sort by', 'talenttrack' ) . '</span>';
        $out .= '<div class="tt-filsel">';
        $out .= '<select class="tt-filsel__select" data-tt-list-sort-select="1" name="tt_sort">';
        foreach ( $sort_options as $opt ) {
            if ( ! is_array( $opt ) ) continue;
            $o_orderby = (string) ( $opt['orderby'] ?? '' );
            $o_order   = strtolower( (string) ( $opt['order'] ?? 'asc' ) ) === 'desc' ? 'desc' : 'asc';
            $o_val     = $o_orderby . ':' . $o_order;
            $out .= '<option value="' . esc_attr( $o_val ) . '"' . ( $cur_sort === $o_val ? ' selected' : '' ) . '>'
                . esc_html( (string) ( $opt['label'] ?? $o_orderby ) ) . '</option>';
        }
        $out .= '</select></div></div>';
        return $out;
    }

    /**
     * Count active filters + build summary chips for the mobile collapsed
     * bar. A select/text filter contributes its current value; a
     * date_range contributes a from/to chip; the search box contributes
     * the search term.
     *
     * @param array<string,mixed> $filters
     * @param array<string,mixed> $search_cfg
     * @param array<string,mixed> $state
     * @return array{0:int,1:array<int,string>}
     */
    private static function activeSummary( array $filters, array $search_cfg, array $state ): array {
        $current = is_array( $state['filter'] ?? null ) ? $state['filter'] : [];
        $count   = 0;
        $chips   = [];

        $search = (string) ( $state['search'] ?? '' );
        if ( ! empty( $search_cfg ) && $search !== '' ) {
            $count++;
            $chips[] = $search;
        }

        foreach ( $filters as $key => $filter ) {
            $key  = (string) $key;
            $type = (string) ( $filter['type'] ?? 'text' );
            if ( $type === 'date_range' ) {
                $from = (string) ( $filter['param_from'] ?? ( $key . '_from' ) );
                $to   = (string) ( $filter['param_to']   ?? ( $key . '_to' ) );
                $fv   = (string) ( $current[ $from ] ?? '' );
                $tv   = (string) ( $current[ $to ] ?? '' );
                if ( $fv !== '' || $tv !== '' ) {
                    $count++;
                    $chips[] = trim( $fv . ' – ' . $tv, ' –' );
                }
                continue;
            }
            $val = (string) ( $current[ $key ] ?? '' );
            if ( $val === '' ) continue;
            $count++;
            // Prefer the human label for selects; fall back to the value.
            if ( $type === 'select' && is_array( $filter['options'] ?? null ) && isset( $filter['options'][ $val ] ) ) {
                $chips[] = (string) $filter['options'][ $val ];
            } else {
                $chips[] = $val;
            }
        }

        return [ $count, $chips ];
    }

    /**
     * The current request's query args, normalised so `filter` is always
     * an array. Used to build link-based (status) URLs that preserve the
     * rest of the list state.
     *
     * @return array<string,mixed>
     */
    private static function currentQueryArgs(): array {
        $args = [];
        foreach ( $_GET as $k => $v ) {
            if ( is_array( $v ) ) {
                if ( (string) $k === 'filter' ) {
                    $clean = [];
                    foreach ( $v as $fk => $fv ) {
                        if ( is_scalar( $fv ) ) $clean[ sanitize_key( (string) $fk ) ] = sanitize_text_field( wp_unslash( (string) $fv ) );
                    }
                    $args['filter'] = $clean;
                }
                continue;
            }
            $args[ sanitize_key( (string) $k ) ] = sanitize_text_field( wp_unslash( (string) $v ) );
        }
        if ( ! isset( $args['filter'] ) ) $args['filter'] = [];
        return $args;
    }

    /**
     * The "Clear" target: the current page stripped of every list-owned
     * query key (search / filter / orderby / order / page / per_page),
     * preserving passthrough state (tt_view, tt_back, …).
     */
    private static function resetUrl(): string {
        $owned = [ 'search', 'filter', 'orderby', 'order', 'page', 'per_page' ];
        return esc_url_raw( remove_query_arg( $owned ) );
    }

    /**
     * @param array<string,mixed> $col
     * @param array<string,mixed> $state
     */
    private static function renderColumnHeader( string $key, array $col, array $state ): void {
        $label = (string) ( $col['label'] ?? $key );
        $sortable = ! empty( $col['sortable'] );
        if ( ! $sortable ) {
            printf( '<th>%s</th>', esc_html( $label ) );
            return;
        }
        $is_active = ( $state['orderby'] === $key );
        $next_order = $is_active && $state['order'] === 'asc' ? 'desc' : 'asc';
        $href = esc_url( add_query_arg( [
            'orderby' => $key,
            'order'   => $next_order,
        ] ) );
        $arrow = $is_active ? ( $state['order'] === 'asc' ? '↑' : '↓' ) : '';
        printf(
            '<th class="tt-list-table-sortable %s" data-tt-list-sort="%s"><a href="%s">%s <span aria-hidden="true">%s</span></a></th>',
            $is_active ? 'is-active' : '',
            esc_attr( $key ),
            $href,
            esc_html( $label ),
            esc_html( $arrow )
        );
    }

    /**
     * @param array<string,array<string,mixed>> $columns
     * @return array<string,array<string,mixed>>
     */
    private static function columnsForJs( array $columns ): array {
        $out = [];
        foreach ( $columns as $key => $col ) {
            $entry = [
                'label'     => (string) ( $col['label'] ?? $key ),
                'sortable'  => ! empty( $col['sortable'] ),
                'render'    => (string) ( $col['render']    ?? 'text' ),
                'value_key' => (string) ( $col['value_key'] ?? $key ),
            ];
            // #0019 Sprint 2 session 2.4 — inline_select renders an
            // editable dropdown in the cell. The hydrator binds change
            // events to PATCH the configured endpoint with the chosen
            // value. Used by the goals list for inline status edits;
            // generic enough that Sprint 3+ players/positions etc. can
            // reuse it.
            if ( ( $col['render'] ?? '' ) === 'inline_select' ) {
                $entry['options']     = is_array( $col['options'] ?? null ) ? $col['options'] : [];
                $entry['patch_path']  = (string) ( $col['patch_path']  ?? '' );
                $entry['patch_field'] = (string) ( $col['patch_field'] ?? $key );
            }
            $out[ (string) $key ] = $entry;
        }
        return $out;
    }

    /**
     * @param array<string,array<string,mixed>> $filters
     * @return array<string,array<string,mixed>>
     */
    private static function filtersForJs( array $filters ): array {
        $out = [];
        foreach ( $filters as $key => $f ) {
            $type = (string) ( $f['type'] ?? 'text' );
            $entry = [ 'type' => $type ];
            if ( $type === 'date_range' ) {
                $entry['param_from'] = (string) ( $f['param_from'] ?? ( $key . '_from' ) );
                $entry['param_to']   = (string) ( $f['param_to']   ?? ( $key . '_to' ) );
            }
            $out[ (string) $key ] = $entry;
        }
        return $out;
    }

    /**
     * @param array<string,array<string,mixed>> $actions
     * @return array<string,array<string,mixed>>
     */
    /**
     * Sanitise static-filter values for JSON. Keys are sanitised as
     * filter param keys; values are stringified (the JS hydrator uses
     * URLSearchParams which stringifies anyway).
     *
     * @param array<string,mixed> $filters
     * @return array<string,string>
     */
    private static function sanitizeStaticFilters( array $filters ): array {
        $out = [];
        foreach ( $filters as $key => $value ) {
            $clean_key = sanitize_key( (string) $key );
            if ( $clean_key === '' ) continue;
            if ( is_scalar( $value ) ) {
                $out[ $clean_key ] = (string) $value;
            }
        }
        return $out;
    }

    private static function rowActionsForJs( array $actions ): array {
        $out = [];
        foreach ( $actions as $key => $a ) {
            // v3.91.2 — optional `cap` gate. When the action declares a
            // capability and the current user doesn't have it (via
            // `current_user_can`, which routes through MatrixGate when
            // the matrix bridge is active), drop the action entirely so
            // the row-action menu only shows what the user can actually
            // do. Without this, a coach with read-only access to teams
            // saw an Edit button that landed on the detail page (the
            // routing fix in v3.91.2 surfaces the form correctly, but
            // the form itself would still 403 / silently no-op for them).
            $cap = isset( $a['cap'] ) ? (string) $a['cap'] : '';
            if ( $cap !== '' && ! current_user_can( $cap ) ) continue;

            $entry = [
                'label' => (string) ( $a['label'] ?? $key ),
            ];
            if ( ! empty( $a['href'] ) ) $entry['href'] = (string) $a['href'];
            if ( ! empty( $a['rest_path'] ) ) {
                $entry['rest_path']   = (string) $a['rest_path'];
                $entry['rest_method'] = (string) ( $a['rest_method'] ?? 'POST' );
            }
            if ( ! empty( $a['confirm'] ) ) $entry['confirm'] = (string) $a['confirm'];
            if ( ! empty( $a['variant'] ) ) $entry['variant'] = (string) $a['variant'];
            // #1470 — per-row visibility gate; JS hides the action on rows
            // whose `show_if` field is falsy (e.g. only-on-archived rows).
            if ( ! empty( $a['show_if'] ) ) $entry['show_if'] = (string) $a['show_if'];
            $out[ (string) $key ] = $entry;
        }
        return $out;
    }
}
