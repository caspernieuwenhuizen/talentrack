<?php
namespace TT\Shared\Frontend\Components;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Infrastructure\Filters\FilterParam;

/**
 * FilterBar — reusable, data-driven filter bar (#2026, epic #2017 Phase 1).
 *
 * One component for every list surface's filter row. The calling view
 * supplies an ordered list of filter groups (options + active state);
 * FilterBar renders the chrome. It holds NO view-specific logic — it
 * never decides what the options are, what is active, or how a filter
 * maps to a query (CLAUDE.md §4: the component composes, the view
 * decides). That keeps it portable across views and SaaS-front-ends.
 *
 * Two layouts, one markup tree (responsive treatment in
 * `assets/css/frontend-filter-bar.css`):
 *   - <1024px: collapses to a "Filters" button (+ active-count badge)
 *     and scrollable summary chips; tapping opens a bottom sheet holding
 *     the same groups.
 *   - >=1024px: a single inline row, each group under a small-caps
 *     label with dividers between groups.
 *
 * The bar is a wrapper around the view's own GET <form>. The view passes
 * the hidden fields it needs to preserve (tt_view, tt_back, etc.) and
 * the per-group controls; FilterBar renders the form, the inline row,
 * the mobile trigger + chips, and the bottom sheet (a second copy of the
 * groups). Both copies submit the same form, so behaviour is identical
 * whichever layout is active.
 *
 * Group types:
 *   - `select`  — labelled <select> (auto-submits on change).
 *   - `text`    — a free-text / search box bound to a form field
 *                 (#2082). No auto-submit on keystroke; the noscript
 *                 Apply button (or a JS hydrator) commits the value.
 *   - `date_range` — a paired from/to date range (#2082). Two date
 *                 inputs, each its own form field.
 *   - `period`  — a pill-dropdown trigger (`tt-perdrop`) that, in the
 *                 inline bar, reveals a popover of period links; in the
 *                 sheet, expands to a one-tap segmented track. Options are
 *                 links (no JS dependency for navigation).
 *   - `status`  — one-tap status pills (`tt-statpill`), link-based.
 *   - `menu`    — an icon-only `⋯` overflow menu (#2622) holding the same
 *                 link-based options as `status`, for a filter that is set
 *                 rarely and would otherwise cost a pill row on every list.
 *                 Renders no visible group label; the label is the trigger's
 *                 accessible name. Announces itself with a chip + accent when
 *                 the selection is off-default.
 *   - `toggle`  — a boolean switch (`tt-switch`) backed by a checkbox
 *                 that auto-submits.
 *
 * Each group is an array:
 *   [
 *     'type'  => 'select'|'text'|'date_range'|'period'|'status'|'menu'|'toggle',
 *     'label' => 'Team',            // small-caps group label
 *     'key'   => 'team',            // stable id for the group (CSS hook)
 *     // type-specific keys, see below
 *   ]
 *
 * select: 'name' (form field), 'options' => [value => label], 'selected',
 *         'placeholder' (optional first option label).
 * text:   'name' (form field), 'value', 'placeholder' (optional),
 *         'input_type' ('text'|'search', default 'text'),
 *         'inputmode' (optional, e.g. 'search'),
 *         'autocomplete' (optional).
 * date_range: 'from' => ['name','value'], 'to' => ['name','value'],
 *         'label_from', 'label_to' (per-input labels).
 * period/status: 'options' => [ ['value','label','url','active', 'dot'?] ],
 *         'active_label' (text shown on the period pill trigger).
 * menu:   'options' as above, plus 'default_value' — the option that needs no
 *         announcing. Any other active option raises the chip + accent.
 * toggle: 'name', 'on' (bool), 'on_label' (the "Tonen" text),
 *         'value' (submitted value when checked, default '1'),
 *         'off_value' (optional — when set, a hidden companion input
 *         submits this value while the box is unchecked, so turning the
 *         toggle OFF sends an explicit off-state instead of omitting the
 *         param; #2201).
 *
 * The wrapping <form> can carry extra attributes (e.g.
 * `data-tt-list-form="1"` so FrontendListTable's hydrator binds to it)
 * via the top-level `form_attrs` arg, and extra raw controls (already
 * escaped) can be injected into both the inline row and the sheet body
 * via `extra_controls` — used by FrontendListTable for its card-mode
 * sort dropdown.
 */
final class FilterBar {

	/** Ensure the shared stylesheet is enqueued exactly once. */
	private static bool $css_enqueued = false;
	/** Ensure the shared sheet script is enqueued exactly once. */
	private static bool $js_enqueued = false;

	/**
	 * Enqueue the FilterBar stylesheet + sheet script. Safe to call
	 * repeatedly (guards prevent double-enqueue). Strings the JS needs
	 * are localized on `TT.i18n` via the `TT_FILTER_BAR` payload.
	 */
	public static function enqueueAssets(): void {
		if ( ! self::$css_enqueued ) {
			wp_enqueue_style(
				'tt-frontend-filter-bar',
				TT_PLUGIN_URL . 'assets/css/frontend-filter-bar.css',
				[ 'tt-frontend-app-chrome' ],
				TT_VERSION
			);
			self::$css_enqueued = true;
		}
		if ( ! self::$js_enqueued ) {
			wp_enqueue_script(
				'tt-filter-bar',
				TT_PLUGIN_URL . 'assets/js/components/filter-bar.js',
				[],
				TT_VERSION,
				true
			);
			wp_localize_script( 'tt-filter-bar', 'TT_FILTER_BAR', [
				'i18n' => [
					'open'  => __( 'Open filters', 'talenttrack' ),
					'close' => __( 'Close', 'talenttrack' ),
				],
			] );
			self::$js_enqueued = true;
		}
	}

	/**
	 * The query parameters this set of groups emits (#2448).
	 *
	 * Saved views need to know which params make up "the current filters" for
	 * a surface. The bar already declares that: every form-backed group names
	 * its input, so the list can be derived instead of maintained by hand — a
	 * hardcoded whitelist would have to know all ~26 surfaces' vocabularies,
	 * and silently saves nothing when it doesn't.
	 *
	 * `period` and `status` are the exception: they are link-based, so the
	 * param name lives inside each option's `url` and appears nowhere in the
	 * group config. Those declare `param` explicitly; `key` is the fallback,
	 * which is already correct wherever the group's key IS the param name.
	 *
	 * @param array<int,array<string,mixed>> $groups
	 * @return array<int,string> unique, in group order
	 */
	public static function paramNames( array $groups ): array {
		$names = [];

		$add = static function ( $name ) use ( &$names ): void {
			$name = is_string( $name ) ? trim( $name ) : '';
			if ( $name !== '' && ! in_array( $name, $names, true ) ) {
				$names[] = $name;
			}
		};

		foreach ( $groups as $group ) {
			if ( ! is_array( $group ) ) continue;
			$type = (string) ( $group['type'] ?? '' );

			switch ( $type ) {
				case 'select':
				case 'text':
				case 'toggle':
				case 'player':
					$add( $group['name'] ?? '' );
					break;

				case 'date_range':
					$from = isset( $group['from'] ) && is_array( $group['from'] ) ? $group['from'] : [];
					$to   = isset( $group['to'] )   && is_array( $group['to'] )   ? $group['to']   : [];
					$add( $from['name'] ?? '' );
					$add( $to['name'] ?? '' );
					break;

				case 'period':
				case 'status':
				case 'menu':
					$add( $group['param'] ?? ( $group['key'] ?? $type ) );
					// #3331 — a period group can own the custom From/To now.
					// They are real form fields, so a saved view has to
					// capture them or a view saved on a custom window would
					// restore the preset and silently show a different one.
					if ( $type === 'period' && isset( $group['custom'] ) && is_array( $group['custom'] ) ) {
						$cf = isset( $group['custom']['from'] ) && is_array( $group['custom']['from'] ) ? $group['custom']['from'] : [];
						$ct = isset( $group['custom']['to'] )   && is_array( $group['custom']['to'] )   ? $group['custom']['to']   : [];
						$add( $cf['name'] ?? '' );
						$add( $ct['name'] ?? '' );
					}
					break;
			}
		}

		return $names;
	}

	/**
	 * Build the saved-views strip for a bar that opted in (#2448).
	 *
	 * `saved_views` accepts:
	 *   - `key`        (required) the surface's registered SavedViewsRegistry key.
	 *   - `base_url`   (optional) the surface URL without filter params;
	 *                  defaults to the bar's own `form_action`.
	 *   - `base_params` (optional) params every apply link keeps; defaults to
	 *                  the bar's `hidden` fields, which is already exactly the
	 *                  routing state (`tt_view`, `tt_back`, …) a link must carry.
	 *   - `extra_keys` (optional) params the bar does not own but the surface
	 *                  wants captured (FrontendListTable's search / sort).
	 *
	 * The capability is NOT taken from the caller — it is resolved from the
	 * registry, so this gate and the REST gate cannot drift.
	 *
	 * #3292 — the active filters, each with the URL that removes just it.
	 *
	 * Derived from the groups the bar was given, which already carry every
	 * option and its active state — the same source `paramNames()` reads. A
	 * caller-supplied `chips` list is bare labels and cannot be made
	 * removable; this can.
	 *
	 * `date_range` is deliberately NOT derived. Every report seeds a default
	 * From/To, so "has a value" does not mean "the reader filtered": a
	 * derived chip would be permanent and unremovable-in-effect. Whether a
	 * window is the default is knowledge the caller has, and
	 * `ReportFilters::customRangeChip()` (#3293) is where it lives.
	 *
	 * @param array<int,array<string,mixed>> $groups
	 * @return list<array{label:string, clear_url:string}>
	 */
	private static function activeChips( array $groups ): array {
		$chips = [];

		foreach ( $groups as $group ) {
			if ( ! is_array( $group ) ) continue;

			$type  = (string) ( $group['type'] ?? '' );
			$glabel = trim( (string) ( $group['label'] ?? '' ) );

			switch ( $type ) {
				case 'select':
					// `selected`, not `value` — the key renderSelect() reads and
					// the one every caller passes. Reading `value` here made this
					// branch unreachable: a select group has no `value`, so every
					// select was silently skipped and the derivation covered only
					// text and the link-based groups.
					$value = (string) ( $group['selected'] ?? '' );
					if ( $value === '' ) break; // the placeholder is "no filter"
					$options = isset( $group['options'] ) && is_array( $group['options'] ) ? $group['options'] : [];
					$olabel  = (string) ( $options[ $value ] ?? $value );
					$chips[] = [
						'label'     => self::chipLabel( $glabel, $olabel ),
						'clear_url' => FilterParam::removeFromUrl( (string) ( $group['name'] ?? '' ) ),
					];
					break;

				case 'text':
					$value = trim( (string) ( $group['value'] ?? '' ) );
					if ( $value === '' ) break;
					$chips[] = [
						'label'     => self::chipLabel( $glabel, $value ),
						'clear_url' => FilterParam::removeFromUrl( (string) ( $group['name'] ?? '' ) ),
					];
					break;

				case 'toggle':
					// `on`, not `checked` — same mismatch as `select` above.
					// A toggle group carries `on`; `checked` is not a key the
					// component defines anywhere.
					if ( empty( $group['on'] ) ) break;
					$chips[] = [
						// A toggle's label already reads as a statement
						// ("Show cancelled"), so it needs no "Label: value".
						'label'     => $glabel !== '' ? $glabel : (string) ( $group['name'] ?? '' ),
						'clear_url' => FilterParam::removeFromUrl( (string) ( $group['name'] ?? '' ) ),
					];
					break;

				case 'period':
				case 'status':
				case 'menu':
					// Link-based: the active option is marked, and the option
					// that means "no filter" carries the URL back to it. That
					// matters — several of these groups have no param-free
					// URL of their own to fall back on.
					$options = isset( $group['options'] ) && is_array( $group['options'] ) ? $group['options'] : [];
					$default = (string) ( $group['default_value'] ?? '' );

					$active = null;
					foreach ( $options as $opt ) {
						if ( is_array( $opt ) && ! empty( $opt['active'] ) ) { $active = $opt; break; }
					}
					if ( $active === null ) break;
					if ( (string) ( $active['value'] ?? '' ) === $default ) break; // already the default

					$clear = '';
					foreach ( $options as $opt ) {
						if ( is_array( $opt ) && (string) ( $opt['value'] ?? '' ) === $default ) {
							$clear = (string) ( $opt['url'] ?? '' );
							break;
						}
					}
					$chips[] = [
						'label'     => self::chipLabel( $glabel, (string) ( $active['label'] ?? '' ) ),
						'clear_url' => $clear,
					];
					break;
			}
		}

		return $chips;
	}

	/** "Team: Ajax U17", or just the value when the group carries no label. */
	private static function chipLabel( string $group_label, string $value ): string {
		$value = trim( $value );
		if ( $group_label === '' ) return $value;
		if ( $value === '' ) return $group_label;

		return sprintf(
			/* translators: 1: filter name, e.g. "Team". 2: its value, e.g. "Ajax U17". */
			_x( '%1$s: %2$s', 'active filter chip', 'talenttrack' ),
			$group_label,
			$value
		);
	}

	/**
	 * @param array<string,mixed>            $args
	 * @param array<int,array<string,mixed>> $groups
	 * @param array<string,string>           $hidden
	 */
	private static function savedViewsHtml( array $args, array $groups, array $hidden ): string {
		$cfg = isset( $args['saved_views'] ) && is_array( $args['saved_views'] ) ? $args['saved_views'] : [];
		$key = (string) ( $cfg['key'] ?? '' );
		if ( $key === '' ) return '';

		$keys = self::paramNames( $groups );
		if ( isset( $cfg['extra_keys'] ) && is_array( $cfg['extra_keys'] ) ) {
			foreach ( $cfg['extra_keys'] as $extra_key ) {
				$extra_key = is_string( $extra_key ) ? trim( $extra_key ) : '';
				if ( $extra_key !== '' && ! in_array( $extra_key, $keys, true ) ) {
					$keys[] = $extra_key;
				}
			}
		}

		$base_url = (string) ( $cfg['base_url'] ?? ( $args['form_action'] ?? '' ) );
		if ( $base_url === '' ) {
			$base_url = remove_query_arg( array_merge( $keys, [ 'paged', 'page' ] ) );
		}

		$base_params = isset( $cfg['base_params'] ) && is_array( $cfg['base_params'] )
			? $cfg['base_params']
			: $hidden;

		return SavedViews::html( $key, $keys, $base_url, array_map( 'strval', $base_params ) );
	}

	/**
	 * Render the filter bar and echo it.
	 *
	 * @param array{
	 *   form_action?:string,
	 *   hidden?:array<string,string>,
	 *   groups:array<int,array<string,mixed>>,
	 *   active_count?:int,
	 *   chips?:array<int,string>,
	 *   title?:string,
	 *   filters_label?:string,
	 *   reset_url?:string,
	 *   noscript_label?:string
	 * } $args
	 */
	public static function render( array $args ): void {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- html() escapes internally.
		echo self::html( $args );
	}

	/**
	 * Build the filter-bar HTML.
	 *
	 * @param array<string,mixed> $args see render().
	 */
	public static function html( array $args ): string {
		self::enqueueAssets();

		$groups   = isset( $args['groups'] ) && is_array( $args['groups'] ) ? $args['groups'] : [];
		$hidden   = isset( $args['hidden'] ) && is_array( $args['hidden'] ) ? $args['hidden'] : [];
		$action   = (string) ( $args['form_action'] ?? '' );
		$active   = (int) ( $args['active_count'] ?? 0 );
		$chips    = isset( $args['chips'] ) && is_array( $args['chips'] ) ? $args['chips'] : [];

		// #3292 — chips the component derives itself, each carrying the URL
		// that removes just that filter.
		//
		// A caller-supplied `chips` is a flat list of pre-rendered labels: no
		// param name, no value, no way back. So the component could not make
		// them removable without a shape change, and they were marked
		// `aria-hidden` — which left the only readback of WHICH filters are
		// applied unavailable to assistive tech in any form. A screen reader
		// heard "Filters, 3" and could not learn what the three were.
		//
		// The bar already holds every group's options and active state, so it
		// can answer both questions. Callers that still pass `chips` keep
		// today's rendering (minus the `aria-hidden`, which was wrong
		// regardless); callers that pass none — the alerts inbox and the
		// comparison view — gain removable chips for free.
		$derived_chips = $chips === [] ? self::activeChips( $groups ) : [];
		if ( $derived_chips !== [] && ! isset( $args['active_count'] ) ) {
			// One source of truth: the badge cannot disagree with the chips.
			$active = count( $derived_chips );
		}
		$title    = (string) ( $args['title'] ?? __( 'Filters', 'talenttrack' ) );
		$ftrigger = (string) ( $args['filters_label'] ?? __( 'Filters', 'talenttrack' ) );
		$reset    = (string) ( $args['reset_url'] ?? '' );
		// #2450 — the reset URL is the surface's param-free URL, which is
		// exactly the condition that re-applies a default saved view. Without
		// this marker, "Clear" would bounce the user straight back into their
		// default and they could never reach an unfiltered list.
		if ( $reset !== '' && isset( $args['saved_views']['key'] ) ) {
			$reset = add_query_arg(
				\TT\Infrastructure\Filters\SavedViewsDefaults::OFF_PARAM,
				\TT\Infrastructure\Filters\SavedViewsDefaults::OFF_VALUE,
				$reset
			);
		}
		$ns_label = (string) ( $args['noscript_label'] ?? __( 'Apply', 'talenttrack' ) );
		// #2082 — extra form attributes (e.g. data-tt-list-form so the
		// FrontendListTable hydrator binds to the bar's own form) and an
		// extra raw control block (card-mode sort dropdown) echoed into
		// both the inline row and the sheet body. Caller pre-escapes the
		// raw HTML.
		$form_attrs = isset( $args['form_attrs'] ) && is_array( $args['form_attrs'] ) ? $args['form_attrs'] : [];
		$extra      = (string) ( $args['extra_controls'] ?? '' );

		$form_attr_html = '';
		foreach ( $form_attrs as $name => $value ) {
			$form_attr_html .= ' ' . esc_attr( (string) $name ) . '="' . esc_attr( (string) $value ) . '"';
		}

		// #2448 — optional personal saved views. Opt-in: a surface passes
		// `saved_views` with its registered key. Absent, no markup is emitted
		// and neither asset is enqueued.
		//
		// #3296 — this used to be a separate band rendered ABOVE the bar. It
		// is now built here and placed inside the utility cluster below, so
		// the bar is one row of chrome instead of two. On a 360px screen a
		// list opened with a saved-views band, then a filter bar, then rows.
		$saved_views_html = self::savedViewsHtml( $args, $groups, $hidden );

		$out = '<div class="tt-filterbar" data-tt-filterbar>';
		$out .= '<form method="get" class="tt-filterbar__form" data-tt-filterbar-form'
			. ( $action !== '' ? ' action="' . esc_url( $action ) . '"' : '' )
			. $form_attr_html . '>';

		foreach ( $hidden as $name => $value ) {
			$out .= '<input type="hidden" name="' . esc_attr( (string) $name )
				. '" value="' . esc_attr( (string) $value ) . '" />';
		}

		// ---- Inline single-line row (desktop >=1024px) --------------
		//
		// #3319 — the row holds the FILTERS. The trailing utility groups
		// (`status` pills, the `⋯` menu) render in their own block further
		// down, after the chips + Clear cluster.
		//
		// They used to sit at the end of this row, right-aligned by an
		// auto-margin. #3289 then added `.tt-filterbar__utils` as a sibling
		// rendered AFTER the row — and the form is a flex row, so DOM order
		// won: the bar ended "Clear, chips, ⋯" and the ⋯ was no longer the
		// rightmost thing on it. Two auto-margins in two different flex
		// containers; neither rule wrong alone, the composition wrong.
		//
		// The cluster cannot simply move inside this row, because the row is
		// `display:none` below 1024px and the chips must render on a phone.
		// So the utility groups come out instead, and one DOM order serves
		// both layouts.
		[ $filter_groups, $utility_groups ] = self::partitionUtilityGroups( $groups );

		$out .= '<div class="tt-filterbar__row">';
		$last = count( $filter_groups ) - 1;
		foreach ( $filter_groups as $i => $group ) {
			$out .= self::renderGroup( $group, false );
			if ( $i < $last ) {
				$out .= '<div class="tt-filterbar__div" aria-hidden="true"></div>';
			}
		}
		if ( $extra !== '' ) {
			$out .= $extra; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- caller pre-escapes.
		}
		$out .= '</div>'; // .tt-filterbar__row

		// ---- Mobile collapsed trigger (<1024px) ---------------------
		//
		// The trigger is genuinely mobile-only: desktop shows the controls
		// inline, so there is nothing for it to open. The chips and Clear
		// are NOT mobile-only, and used to live in here — see below.
		$out .= '<div class="tt-filterbar__mobile">';
		$out .= '<div class="tt-filtertrigger">';
		$out .= '<button type="button" class="tt-btn tt-filterbtn" data-tt-filter-open'
			. ' aria-haspopup="dialog" aria-expanded="false">';
		$out .= '<span class="tt-filterbtn__icon" aria-hidden="true">&#9776;</span>';
		$out .= '<span>' . esc_html( $ftrigger ) . '</span>';
		if ( $active > 0 ) {
			$out .= '<span class="tt-filterbtn__badge">' . esc_html( (string) $active ) . '</span>';
		}
		$out .= '</button>';
		$out .= '</div>'; // .tt-filtertrigger
		$out .= '</div>'; // .tt-filterbar__mobile

		// ---- Utility cluster: what is applied, and how to undo it ----
		//
		// #3289 — rendered once, outside both the mobile block and the sheet,
		// and visible in both layouts.
		//
		// The chips lived inside `.tt-filterbar__mobile` and Clear inside the
		// sheet footer, and both of those are `display: none` above 1024px.
		// So a desktop user had no way to clear a filter and no readback of
		// what was applied: every surface passed `reset_url`, every surface
		// rendered it, and no desktop user had ever seen it. On a list with
		// eight controls wrapping to two rows, answering "why am I seeing
		// four rows?" meant reading every control in turn — and the
		// link-based groups often have no "none" option to walk back to.
		//
		// It sits at the end of the inline row, after the auto-margin that
		// #3291 fixed, so it reads as chrome rather than as one more filter.
		// The derived chips count towards this gate too. Testing only the
		// caller's arrays made a derived-chips surface depend on the caller
		// also passing a reset URL — the caller-drift the derivation exists
		// to end.
		//
		// #3296 — and so do the saved views: a surface with views but no
		// filters set and no reset URL still needs the cluster to hold its
		// chips.
		if ( $derived_chips !== [] || $chips !== [] || $reset !== '' || $saved_views_html !== '' ) {
			$out .= '<div class="tt-filterbar__utils">';
			// #3292 — a real list, and NOT aria-hidden. The chips are the only
			// place the bar says which filters are applied; hiding them from
			// assistive tech left "Filters, 3" as the whole announcement.
			if ( $derived_chips !== [] ) {
				$out .= '<ul class="tt-chips">';
				foreach ( $derived_chips as $chip ) {
					// `activeChips()` returns array{label:string,clear_url:string},
					// so both keys are guaranteed — a `??` here is dead at level 8.
					$label = $chip['label'];
					if ( $label === '' ) continue;
					$out .= '<li class="tt-chip"><span class="tt-chip__label">' . esc_html( $label ) . '</span>';
					$clear = $chip['clear_url'];
					if ( $clear !== '' ) {
						// A link, not a button: no JS dependency, consistent
						// with every other link-based group in this bar, and
						// the ⋯ menu's own chip already ships this pattern.
						$out .= '<a class="tt-chip__clear" href="' . esc_url( $clear ) . '"'
							. ' aria-label="' . esc_attr( sprintf(
								/* translators: %s: the active filter, e.g. "Team: Ajax U17". */
								__( 'Remove filter %s', 'talenttrack' ),
								$label
							) ) . '">&#10005;</a>';
					}
					$out .= '</li>';
				}
				$out .= '</ul>';
			} elseif ( $chips !== [] ) {
				// Caller-supplied labels. Not removable — they arrive as bare
				// strings with no param to drop — but no longer hidden from
				// assistive tech.
				$out .= '<ul class="tt-chips">';
				foreach ( $chips as $chip ) {
					$out .= '<li class="tt-chip"><span class="tt-chip__label">'
						. esc_html( (string) $chip ) . '</span></li>';
				}
				$out .= '</ul>';
			}
			if ( $reset !== '' ) {
				$out .= '<a class="tt-btn tt-btn-secondary tt-filterbar__clear" href="' . esc_url( $reset ) . '">'
					. esc_html__( 'Clear', 'talenttrack' ) . '</a>';
			}
			// #3296 — the views zone: chips then the bookmark, immediately
			// after Clear so "what is applied" and "what I saved" read as two
			// adjacent groups rather than one mixed row.
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SavedViews::html() escapes internally.
			$out .= $saved_views_html;
			$out .= '</div>'; // .tt-filterbar__utils
		}

		// ---- Trailing utility groups (desktop >=1024px) -------------
		//
		// #3319 — the status pills and the `⋯` menu, after the cluster, so
		// the bar reads: filters · [gap] · chips · Clear · pills · ⋯ and the
		// menu is the last thing on it. Inline-only, like the row: the mobile
		// sheet renders every group from `$groups` in caller order.
		if ( $utility_groups !== [] ) {
			$out .= '<div class="tt-filterbar__trailing">';
			foreach ( $utility_groups as $group ) {
				$out .= self::renderGroup( $group, false );
			}
			$out .= '</div>'; // .tt-filterbar__trailing
		}

		// ---- Bottom sheet (holds the same groups, sheet variant) ----
		//
		// #3294 — a real <dialog>, opened with showModal().
		//
		// It was a <div role="dialog" aria-modal="true">, which asserts modality
		// without implementing any of it: Tab walked straight out into the page
		// behind the scrim, a screen reader could traverse into content the
		// sheet was covering, and the body scrolled under it. The docblock
		// claimed a "focus trap-lite"; there was no trap of any kind.
		//
		// showModal() supplies natively the four things that were missing —
		// the focus trap, top-layer stacking, ::backdrop, and inertness of
		// everything behind — which is why the scrim element, its click
		// handler and the document-level Escape listener are gone from
		// filter-bar.js rather than reimplemented here. It also matches
		// ttConfirm and the saved-views manage dialog, which have both used
		// <dialog> since they were written.
		//
		// The dialog stays INSIDE the bar's form: showModal() moves it to the
		// top layer for rendering, not in the DOM, so its Apply button keeps
		// its form owner and #3288 keeps working.
		$out .= '<dialog class="tt-filter-sheet"'
			. ' aria-label="' . esc_attr( $title ) . '" data-tt-filter-sheet>';
		$out .= '<div class="tt-filter-sheet__grip" aria-hidden="true"></div>';
		$out .= '<div class="tt-filter-sheet__head">';
		$out .= '<h3 class="tt-filter-sheet__title">' . esc_html( $title ) . '</h3>';
		$out .= '<button type="button" class="tt-filter-sheet__close" data-tt-filter-close'
			. ' aria-label="' . esc_attr__( 'Close', 'talenttrack' ) . '">&#10005;</button>';
		$out .= '</div>';
		$out .= '<div class="tt-filter-sheet__body">';
		// #3334 — the same partition the inline row uses, so the two layouts
		// agree on where a control lives.
		//
		// The sheet rendered the caller's order untouched while #3319 moved
		// the status pills and the ⋯ to the end of the desktop row, so on the
		// activities list the archive menu was last on a laptop and first on
		// a phone. Same coach, same list, the two devices they use in one
		// afternoon.
		//
		// The sheet is a single column, so "last" means bottom — which is
		// also where a control set once in a blue moon belongs on a phone.
		[ $sheet_filters, $sheet_utility ] = self::partitionUtilityGroups( $groups );
		foreach ( array_merge( $sheet_filters, $sheet_utility ) as $group ) {
			$out .= self::renderGroup( $group, true );
		}
		if ( $extra !== '' ) {
			$out .= $extra; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- caller pre-escapes.
		}
		$out .= '</div>'; // .tt-filter-sheet__body
		$out .= '<div class="tt-filter-sheet__foot">';
		if ( $reset !== '' ) {
			$out .= '<a class="tt-btn tt-btn-secondary tt-filter-sheet__reset" href="' . esc_url( $reset ) . '">'
				. esc_html__( 'Clear', 'talenttrack' ) . '</a>';
		}
		// #3288 — a real submit, not a close button.
		//
		// It was `type="button"` carrying only `data-tt-filter-close`, so a
		// date range or free-text filter typed into the sheet was discarded
		// the moment the sheet shut: the user set From/To, tapped Apply, the
		// sheet closed and the list was unchanged, with nothing to say why.
		// Selects and toggles auto-submit on `change`, which is what hid it —
		// set a team as well and the page reloads, and the dates look like
		// they worked because a different control submitted the form.
		//
		// The inline row's own Apply is suppressed inside the sheet
		// (`! $in_sheet` in the date-range branch) to avoid two buttons side
		// by side; this is the one that was meant to take over. It already
		// sits inside the bar's form, so submitting is all it needs.
		$out .= '<button type="submit" class="tt-btn tt-btn-primary tt-filter-sheet__apply"'
			. ' data-tt-filter-close>' . esc_html__( 'Apply', 'talenttrack' ) . '</button>';
		$out .= '</div>'; // .tt-filter-sheet__foot
		$out .= '</dialog>'; // .tt-filter-sheet

		// noscript fallback — a real submit button for JS-off browsers.
		$out .= '<noscript><button type="submit" class="tt-btn tt-btn-secondary">'
			. esc_html( $ns_label ) . '</button></noscript>';

		$out .= '</form>';
		$out .= '</div>'; // .tt-filterbar

		return $out;
	}

	/**
	 * Split the groups for the INLINE layout into the filters and the
	 * trailing utility groups (#2203, #3319).
	 *
	 * Replaces `orderStatusLast()`, which returned one flat list because the
	 * utility groups still rendered inside the filter row. They now render in
	 * their own block after the chips + Clear cluster, so the two have to be
	 * told apart rather than merely ordered.
	 *
	 * `menu` sorts after `status` regardless of the order the caller passed
	 * them — the `⋯` is the last thing on the bar, and a caller that happens
	 * to declare it before its status group should not change that. The old
	 * version put both in one bucket in caller order, which is why a bar
	 * declaring the menu first rendered `⋯` before the pills.
	 *
	 * Everything else keeps its relative order. The mobile sheet does not use
	 * this at all — it renders every group in the caller's order.
	 *
	 * @param array<int,array<string,mixed>> $groups
	 * @return array{0:array<int,array<string,mixed>>,1:array<int,array<string,mixed>>} filters, utility
	 */
	private static function partitionUtilityGroups( array $groups ): array {
		$filters = [];
		$status  = [];
		$menu    = [];
		foreach ( $groups as $group ) {
			switch ( (string) ( $group['type'] ?? '' ) ) {
				case 'status': $status[]  = $group; break;
				case 'menu':   $menu[]    = $group; break;
				default:       $filters[] = $group; break;
			}
		}
		return [ $filters, array_merge( $status, $menu ) ];
	}

	/**
	 * Render one filter group (label + control). The control markup is
	 * shared between the inline bar and the sheet; `$in_sheet` only
	 * changes the period control (pill-dropdown inline, segmented track
	 * in the sheet).
	 *
	 * @param array<string,mixed> $group
	 */
	private static function renderGroup( array $group, bool $in_sheet ): string {
		$type  = (string) ( $group['type'] ?? '' );
		$label = (string) ( $group['label'] ?? '' );
		$key   = sanitize_key( (string) ( $group['key'] ?? $type ) );

		// #3291 — a TYPE modifier alongside the key one.
		//
		// The key modifier is built from `key`, which callers set to whatever
		// names the filter: `archived`, `state`, or a list's own filter key.
		// So a rule written against `--status` — which #2203's right-align
		// was — matched nothing anywhere in the app, and the `⋯` overflow menu
		// has always sat flush against its left-hand neighbour reading as one
		// more filter, with empty row to its right.
		//
		// Styling a group by what it IS needs the type; styling one
		// particular group still needs the key. Both are emitted.
		$out  = '<div class="tt-filterbar__group tt-filterbar__group--' . esc_attr( $key )
			. ' tt-filterbar__group--t-' . esc_attr( sanitize_key( $type ) ) . '">';
		// #2622 — a `menu` group renders no visible small-caps label. Its
		// trigger is icon-only and carries the label as its accessible name;
		// printing the label above it would give back exactly the vertical
		// space collapsing the control set out to reclaim.
		if ( $label !== '' && $type !== 'menu' ) {
			$out .= '<span class="tt-filter__glabel">' . esc_html( $label ) . '</span>';
		}

		switch ( $type ) {
			case 'menu':
				$out .= self::renderMenu( $group );
				break;
			case 'select':
				$out .= self::renderSelect( $group );
				break;
			case 'text':
				$out .= self::renderText( $group );
				break;
			case 'date_range':
				$out .= self::renderDateRange( $group, $in_sheet );
				break;
			case 'period':
				$out .= self::renderPeriod( $group, $in_sheet );
				break;
			case 'status':
				$out .= self::renderStatus( $group );
				break;
			case 'toggle':
				$out .= self::renderToggle( $group );
				break;
			case 'player':
				$out .= self::renderPlayer( $group );
				break;
		}

		$out .= '</div>';
		return $out;
	}

	/**
	 * `player` — a type-to-filter player picker (#3332).
	 *
	 * Three surfaces filtered by player with a `<select>` listing every
	 * player the reader could see — the whole academy for an admin. A
	 * dropdown of several hundred names in stored order, with no way to type
	 * toward one, and on a phone a native picker the length of the squad
	 * list.
	 *
	 * Renders the existing `PlayerSearchPickerComponent` in its `search`
	 * style, which writes the chosen id into a hidden input and dispatches a
	 * bubbling `change` on it (#1157). That is what makes it work as a
	 * filter without new JS: `FrontendListTable`'s hydrator and
	 * `filter-bar.js`'s auto-submit both listen for `change` on a named
	 * control, so a pick applies exactly as a select's would.
	 *
	 * The picker still resolves its candidate list server-side and embeds
	 * it, so this does not remove the per-player query the surfaces already
	 * paid — it makes the control usable. A REST-backed search is a separate
	 * change to the component, and every one of its ~6 existing callers
	 * would inherit it.
	 *
	 * @param array<string,mixed> $group
	 */
	private static function renderPlayer( array $group ): string {
		return PlayerSearchPickerComponent::render( [
			'name'     => (string) ( $group['name'] ?? 'player_id' ),
			// The group label is already printed above the control by
			// renderGroup(), so the picker prints none of its own.
			'label'    => '',
			'selected' => (int) ( $group['selected'] ?? 0 ),
			'team_id'  => (int) ( $group['team_id'] ?? 0 ),
			'user_id'  => (int) ( $group['user_id'] ?? get_current_user_id() ),
			'is_admin' => ! empty( $group['is_admin'] ),
		] + ( isset( $group['placeholder'] )
			// No default of our own: the picker's own "Type a name to
			// search…" is already in the catalogue and already translated.
			// A near-duplicate string here would buy nothing and cost a
			// translation on every locale.
			? [ 'placeholder' => (string) $group['placeholder'] ]
			: [] ) );
	}

	/**
	 * `select` — chevron box. Auto-submits the wrapping form on change.
	 *
	 * @param array<string,mixed> $group
	 */
	private static function renderSelect( array $group ): string {
		$name        = (string) ( $group['name'] ?? '' );
		$selected    = (string) ( $group['selected'] ?? '' );
		$placeholder = isset( $group['placeholder'] ) ? (string) $group['placeholder'] : null;
		$options     = isset( $group['options'] ) && is_array( $group['options'] ) ? $group['options'] : [];
		// #2082 — `auto_submit` (default true) controls the
		// data-tt-filter-submit hook that filter-bar.js auto-submits on.
		// FrontendListTable opts OUT (its own JS hydrator handles the
		// change and live-filters without a reload), so the two scripts
		// don't both fire on one change.
		$auto_submit = ! isset( $group['auto_submit'] ) || ! empty( $group['auto_submit'] );

		$out  = '<div class="tt-filsel">';
		$out .= '<select class="tt-filsel__select" name="' . esc_attr( $name ) . '"'
			. ( $auto_submit ? ' data-tt-filter-submit' : '' ) . '>';
		if ( $placeholder !== null ) {
			$out .= '<option value=""' . ( $selected === '' ? ' selected' : '' ) . '>'
				. esc_html( $placeholder ) . '</option>';
		}
		foreach ( $options as $value => $opt_label ) {
			$v = (string) $value;
			$out .= '<option value="' . esc_attr( $v ) . '"'
				. ( $v === $selected ? ' selected' : '' ) . '>'
				. esc_html( (string) $opt_label ) . '</option>';
		}
		$out .= '</select>';
		$out .= '</div>';
		return $out;
	}

	/**
	 * `text` — a free-text / search box. Unlike `select`/`toggle` it does
	 * NOT auto-submit on change (a keystroke shouldn't reload the page);
	 * the noscript Apply button commits the value, and a JS hydrator
	 * (FrontendListTable) live-filters as the user types. Honours
	 * `input_type` ('text'|'search'), `inputmode`, and `autocomplete`.
	 *
	 * @param array<string,mixed> $group
	 */
	private static function renderText( array $group ): string {
		$name        = (string) ( $group['name'] ?? '' );
		$value       = (string) ( $group['value'] ?? '' );
		$placeholder = (string) ( $group['placeholder'] ?? '' );
		$itype       = (string) ( $group['input_type'] ?? 'text' );
		$itype       = in_array( $itype, [ 'text', 'search' ], true ) ? $itype : 'text';
		$inputmode   = isset( $group['inputmode'] ) ? (string) $group['inputmode'] : '';
		$autocomp    = isset( $group['autocomplete'] ) ? (string) $group['autocomplete'] : '';

		$out  = '<div class="tt-filtext">';
		$out .= '<input type="' . esc_attr( $itype ) . '" class="tt-filtext__input"'
			. ' name="' . esc_attr( $name ) . '"'
			. ' value="' . esc_attr( $value ) . '"';
		if ( $placeholder !== '' ) {
			$out .= ' placeholder="' . esc_attr( $placeholder ) . '"';
		}
		if ( $inputmode !== '' ) {
			$out .= ' inputmode="' . esc_attr( $inputmode ) . '"';
		}
		if ( $autocomp !== '' ) {
			$out .= ' autocomplete="' . esc_attr( $autocomp ) . '"';
		}
		$out .= ' />';
		$out .= '</div>';
		return $out;
	}

	/**
	 * `date_range` — a paired from/to date range. Each bound to its own
	 * form field (`from.name` / `to.name`) so the surrounding view keeps
	 * full control of the param names (e.g. `filter[date_from]`). No
	 * auto-submit on change (a keystroke / date pick shouldn't reload).
	 *
	 * On the INLINE bar the range gets an explicit **Apply** submit
	 * button (#2184) so a desktop user has a clear, keyboard-reachable
	 * way to commit a changed from/to — the inline bar otherwise has no
	 * visible commit action. It's a plain `type="submit"`: for a bare
	 * FilterBar it navigates (GET reload); for a FrontendListTable
	 * adopter the hydrator intercepts the form's submit and live-filters
	 * instead, so there's no double submit. The sheet variant keeps its
	 * single footer Apply (`$in_sheet` suppresses the per-group button)
	 * — no duplicate.
	 *
	 * @param array<string,mixed> $group
	 */
	private static function renderDateRange( array $group, bool $in_sheet = false ): string {
		$from = isset( $group['from'] ) && is_array( $group['from'] ) ? $group['from'] : [];
		$to   = isset( $group['to'] )   && is_array( $group['to'] )   ? $group['to']   : [];
		$label_from = (string) ( $group['label_from'] ?? __( 'From', 'talenttrack' ) );
		$label_to   = (string) ( $group['label_to']   ?? __( 'To', 'talenttrack' ) );

		$field = static function ( array $cfg, string $sublabel ): string {
			$name  = (string) ( $cfg['name'] ?? '' );
			$value = (string) ( $cfg['value'] ?? '' );
			$o  = '<label class="tt-fildate">';
			if ( $sublabel !== '' ) {
				$o .= '<span class="tt-fildate__label">' . esc_html( $sublabel ) . '</span>';
			}
			$o .= '<input type="date" class="tt-fildate__input"'
				. ' name="' . esc_attr( $name ) . '"'
				. ' value="' . esc_attr( $value ) . '" />';
			$o .= '</label>';
			return $o;
		};

		$out  = '<div class="tt-fildaterange">';
		$out .= $field( $from, $label_from );
		$out .= $field( $to, $label_to );
		if ( ! $in_sheet ) {
			$out .= '<button type="submit" class="tt-btn tt-btn-primary tt-fildate__apply">'
				. esc_html__( 'Apply', 'talenttrack' ) . '</button>';
		}
		$out .= '</div>';
		return $out;
	}

	/**
	 * `period` — compact pill-dropdown in the inline bar; a one-tap
	 * segmented track in the sheet. Options are link-based (each carries
	 * a `url`) so navigation works without JS; the inline popover is a
	 * progressive enhancement toggled by the sheet/popover script.
	 *
	 * @param array<string,mixed> $group
	 */
	private static function renderPeriod( array $group, bool $in_sheet ): string {
		$options      = isset( $group['options'] ) && is_array( $group['options'] ) ? $group['options'] : [];
		$active_label = (string) ( $group['active_label'] ?? '' );

		// #3331 — the optional custom-range branch.
		//
		// Nine surfaces rendered a `period` pill-dropdown AND a standalone
		// `date_range` for the same question, which is why #3293 had to teach
		// the pill not to lie about which of the two the query used. One
		// control cannot contradict itself.
		//
		// The dates live inside this group now. The presets stay link-based
		// so they work with JS off, and the custom branch is a <details> that
		// reveals the two inputs — also JS-free. `custom` carries
		// `['from' => [name, value], 'to' => [...]]`, the same shape the
		// `date_range` group took, so a surface migrates by moving that array
		// rather than rewriting it.
		$custom     = isset( $group['custom'] ) && is_array( $group['custom'] ) ? $group['custom'] : [];
		$custom_on  = ! empty( $group['custom_active'] );
		$from       = isset( $custom['from'] ) && is_array( $custom['from'] ) ? $custom['from'] : [];
		$to         = isset( $custom['to'] )   && is_array( $custom['to'] )   ? $custom['to']   : [];
		$custom_lbl = (string) ( $custom['label'] ?? __( 'Custom range…', 'talenttrack' ) );

		if ( $in_sheet ) {
			$out = '<div class="tt-segtrack" role="group">';
			foreach ( $options as $opt ) {
				$url    = (string) ( $opt['url'] ?? '' );
				$lbl    = (string) ( $opt['label'] ?? '' );
				$is_on  = ! empty( $opt['active'] );
				$out   .= '<a class="tt-seg' . ( $is_on ? ' tt-seg--on' : '' ) . '" href="' . esc_url( $url ) . '"'
					. ( $is_on ? ' aria-current="true"' : '' ) . '>' . esc_html( $lbl ) . '</a>';
			}
			$out .= '</div>';
			if ( $custom !== [] ) {
				$out .= self::renderPeriodCustom( $from, $to, $custom_lbl, $custom_on, true );
			}
			return $out;
		}

		// Inline: a native <details> pill-dropdown — keyboard-operable and
		// fully functional with JS off. The script (data-tt-perdrop) only
		// adds outside-click-to-close as an enhancement.
		$out  = '<details class="tt-perdrop-wrap" data-tt-perdrop>';
		$out .= '<summary class="tt-perdrop' . ( $custom_on ? ' tt-perdrop--custom' : '' ) . '">'
			. '<span class="tt-perdrop__label">' . esc_html( $active_label ) . '</span>'
			. '<span class="tt-perdrop__chev" aria-hidden="true"></span></summary>';
		$out .= '<div class="tt-perdrop__menu" role="menu">';
		foreach ( $options as $opt ) {
			$url   = (string) ( $opt['url'] ?? '' );
			$lbl   = (string) ( $opt['label'] ?? '' );
			$is_on = ! empty( $opt['active'] );
			$out  .= '<a class="tt-perdrop__opt' . ( $is_on ? ' tt-perdrop__opt--on' : '' ) . '"'
				. ' role="menuitem" href="' . esc_url( $url ) . '"'
				. ( $is_on ? ' aria-current="true"' : '' ) . '>'
				. esc_html( $lbl ) . '</a>';
		}
		if ( $custom !== [] ) {
			$out .= self::renderPeriodCustom( $from, $to, $custom_lbl, $custom_on, false );
		}
		$out .= '</div>';
		$out .= '</details>';
		return $out;
	}

	/**
	 * The "Custom range…" branch of a `period` group (#3331).
	 *
	 * A `<details>` holding the two date inputs, open when a custom window is
	 * what the query ran on. Inside the inline pill-dropdown it is one more
	 * menu item that expands; in the sheet it sits under the segmented track.
	 *
	 * The Apply button is here rather than on the group, because this is the
	 * only part of a `period` control that does not navigate on click: the
	 * presets are links and commit themselves, a typed date does not.
	 * `$in_sheet` suppresses it — the sheet has one footer Apply and a second
	 * would be a duplicate, which is the trap #3288 fixed.
	 *
	 * @param array<string,mixed> $from
	 * @param array<string,mixed> $to
	 */
	private static function renderPeriodCustom( array $from, array $to, string $label, bool $open, bool $in_sheet ): string {
		$field = static function ( array $cfg, string $sublabel ): string {
			$o  = '<label class="tt-fildate">';
			if ( $sublabel !== '' ) {
				$o .= '<span class="tt-fildate__label">' . esc_html( $sublabel ) . '</span>';
			}
			$o .= '<input type="date" class="tt-fildate__input"'
				. ' name="' . esc_attr( (string) ( $cfg['name'] ?? '' ) ) . '"'
				. ' value="' . esc_attr( (string) ( $cfg['value'] ?? '' ) ) . '" />';
			$o .= '</label>';
			return $o;
		};

		$out  = '<details class="tt-percustom"' . ( $open ? ' open' : '' ) . '>';
		$out .= '<summary class="tt-percustom__toggle">' . esc_html( $label ) . '</summary>';
		$out .= '<div class="tt-percustom__body">';
		$out .= $field( $from, __( 'From', 'talenttrack' ) );
		$out .= $field( $to, __( 'To', 'talenttrack' ) );
		if ( ! $in_sheet ) {
			$out .= '<button type="submit" class="tt-btn tt-btn-primary tt-fildate__apply">'
				. esc_html__( 'Apply', 'talenttrack' ) . '</button>';
		}
		$out .= '</div>';
		$out .= '</details>';
		return $out;
	}

	/**
	 * `menu` — an icon-only overflow menu (#2622).
	 *
	 * For a filter that is set once in a blue moon and otherwise costs a whole
	 * 48px pill row on every list in the app. Same link-based options as
	 * `status`, collapsed behind a `⋯` trigger, built on the `tt-perdrop`
	 * <details> pattern so it stays keyboard-operable and functional with JS
	 * off (filter-bar.js only adds outside-click-to-close).
	 *
	 * The closed state still has to say when the list is NOT in its default
	 * state — an icon alone would make an archived list indistinguishable from
	 * a short active one. Two cues, both only when off-default: the trigger
	 * takes the accent treatment, and a clearable chip renders beside it.
	 *
	 * @param array<string,mixed> $group
	 */
	private static function renderMenu( array $group ): string {
		$options = isset( $group['options'] ) && is_array( $group['options'] ) ? $group['options'] : [];
		$label   = (string) ( $group['label'] ?? __( 'More filters', 'talenttrack' ) );

		// The active option, and whether it is the default one. `default_value`
		// names the option that needs no announcing.
		$default = (string) ( $group['default_value'] ?? '' );
		$active  = null;
		foreach ( $options as $opt ) {
			if ( ! empty( $opt['active'] ) ) { $active = $opt; break; }
		}
		$is_default = $active === null || (string) ( $active['value'] ?? '' ) === $default;

		// The URL that clears back to the default — the chip's ✕ target.
		$clear_url = '';
		foreach ( $options as $opt ) {
			if ( (string) ( $opt['value'] ?? '' ) === $default ) {
				$clear_url = (string) ( $opt['url'] ?? '' );
				break;
			}
		}

		$out = '<div class="tt-ovmenu-wrap">';

		// Off-default cue #1 — a chip naming the state, with a clear action.
		if ( ! $is_default && $active !== null ) {
			$out .= '<span class="tt-ovmenu__chip">'
				. esc_html( $label . ': ' . (string) ( $active['label'] ?? '' ) );
			if ( $clear_url !== '' ) {
				$out .= '<a class="tt-ovmenu__clear" href="' . esc_url( $clear_url ) . '"'
					. ' aria-label="' . esc_attr(
						/* translators: %s: the filter's name, e.g. "Archive". */
						sprintf( __( 'Clear the %s filter', 'talenttrack' ), $label )
					) . '">&#10005;</a>';
			}
			$out .= '</span>';
		}

		$out .= '<details class="tt-ovmenu" data-tt-perdrop>';
		// Off-default cue #2 — the trigger itself carries the accent.
		$out .= '<summary class="tt-ovmenu__btn' . ( $is_default ? '' : ' tt-ovmenu__btn--on' ) . '"'
			. ' aria-label="' . esc_attr( $label ) . '" title="' . esc_attr( $label ) . '">'
			. '<span aria-hidden="true">&#8943;</span></summary>';
		$out .= '<div class="tt-ovmenu__menu" role="menu">';
		$out .= '<p class="tt-ovmenu__head">' . esc_html( $label ) . '</p>';
		foreach ( $options as $opt ) {
			$url   = (string) ( $opt['url'] ?? '' );
			$lbl   = (string) ( $opt['label'] ?? '' );
			$is_on = ! empty( $opt['active'] );
			$out  .= '<a class="tt-ovmenu__opt' . ( $is_on ? ' tt-ovmenu__opt--on' : '' ) . '"'
				. ' role="menuitem" href="' . esc_url( $url ) . '"'
				. ( $is_on ? ' aria-current="true"' : '' ) . '>'
				. esc_html( $lbl ) . '</a>';
		}
		$out .= '</div>';
		$out .= '</details>';
		$out .= '</div>';

		return $out;
	}

	/**
	 * `status` — one-tap pills with a status dot, link-based.
	 *
	 * @param array<string,mixed> $group
	 */
	private static function renderStatus( array $group ): string {
		$options = isset( $group['options'] ) && is_array( $group['options'] ) ? $group['options'] : [];

		$out = '<div class="tt-statset" role="group">';
		foreach ( $options as $opt ) {
			$url   = (string) ( $opt['url'] ?? '' );
			$lbl   = (string) ( $opt['label'] ?? '' );
			$dot   = sanitize_key( (string) ( $opt['dot'] ?? ( $opt['value'] ?? '' ) ) );
			$is_on = ! empty( $opt['active'] );
			$out  .= '<a class="tt-statpill' . ( $is_on ? ' tt-statpill--on' : '' ) . '"'
				. ' href="' . esc_url( $url ) . '" data-k="' . esc_attr( $dot ) . '"'
				. ( $is_on ? ' aria-current="true"' : '' ) . '>'
				. '<span class="tt-statpill__dot" aria-hidden="true"></span>'
				. esc_html( $lbl ) . '</a>';
		}
		$out .= '</div>';
		return $out;
	}

	/**
	 * `toggle` — a boolean switch backed by a checkbox that auto-submits.
	 * The checkbox is the accessible control (label + :focus-visible);
	 * the track/knob is decorative. JS reflects the checked state onto
	 * the visual switch.
	 *
	 * @param array<string,mixed> $group
	 */
	private static function renderToggle( array $group ): string {
		$name      = (string) ( $group['name'] ?? '' );
		$on        = ! empty( $group['on'] );
		$on_label  = (string) ( $group['on_label'] ?? '' );
		$value     = (string) ( $group['value'] ?? '1' );
		// #2201 — optional explicit OFF value. A bare checkbox that is
		// unchecked simply omits its param on submit, which is fine when the
		// URL state is rebuilt from scratch — but when a stale flag can
		// linger in carried params, the toggle would appear stuck ON. When
		// `off_value` is set, a hidden companion input carries it; the
		// checkbox is rendered AFTER the hidden field, so on submit the box's
		// own value wins while checked (last duplicate wins in PHP $_GET) and
		// the hidden off-value is what reaches the server while unchecked.
		$off_value = isset( $group['off_value'] ) ? (string) $group['off_value'] : null;

		$out  = '<label class="tt-switch' . ( $on ? ' tt-switch--on' : '' ) . '" data-tt-switch>';
		if ( $off_value !== null && $name !== '' ) {
			$out .= '<input type="hidden" name="' . esc_attr( $name )
				. '" value="' . esc_attr( $off_value ) . '" data-tt-switch-off />';
		}
		$out .= '<input type="checkbox" class="tt-switch__input" name="' . esc_attr( $name )
			. '" value="' . esc_attr( $value ) . '"' . ( $on ? ' checked' : '' )
			. ' data-tt-filter-submit />';
		$out .= '<span class="tt-switch__track" aria-hidden="true"></span>';
		if ( $on_label !== '' ) {
			$out .= '<span class="tt-switch__label">' . esc_html( $on_label ) . '</span>';
		}
		$out .= '</label>';
		return $out;
	}
}
