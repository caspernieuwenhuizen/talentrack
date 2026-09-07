/*
 * filter-refresh.js — in-place filtering for the bare-FilterBar surfaces
 * (#3336, epic #3335).
 *
 * One filter bar, two behaviours, was the problem. FrontendListTable
 * surfaces hydrate over REST: change a filter and the rows swap in place.
 * The nine bare surfaces — the two grids, five analytics reports, standard
 * reports, audit log, comparison, message log, alerts inbox — called
 * `form.requestSubmit()` on every change: a full navigation, a white flash,
 * no spinner, and nothing stopping a coach queueing a second filter into a
 * request that had already gone.
 *
 * WHY A DOCUMENT FETCH AND NOT REST
 *
 * These surfaces render aggregates in PHP — summary tiles, computed tables,
 * a spreadsheet grid — not a list of records with a JSON shape. Giving each
 * one a REST endpoint that returns its rendered region is per-surface work
 * (epic slices 2-4); giving them all a way to re-render the region they
 * already produce is not. So this refetches the surface's own URL with the
 * new query and swaps the marked region out of the response.
 *
 * That keeps one important property: the server remains the single renderer.
 * There is no second template in JS that can drift from the PHP one, which
 * is the failure mode a hand-rolled JSON renderer per report would invite.
 *
 * PROGRESSIVE ENHANCEMENT
 *
 * Opt-in per surface: the bar's form carries `data-tt-filter-refresh` and
 * the surface marks its result region `data-tt-filter-region`. Without JS,
 * or without either marker, nothing changes — the link-based groups still
 * navigate and the form still submits, which is the behaviour every one of
 * these surfaces has today.
 */
( function () {
	'use strict';

	var TT = ( window.TT = window.TT || {} );
	TT.i18n = TT.i18n || {};

	/**
	 * #3337 — guards a surface can register before its region is swapped.
	 *
	 * The two grids hold unsaved cell edits behind an explicit Save
	 * (CLAUDE.md §6 model B). A full page load let `beforeunload` catch
	 * that; an in-place swap bypasses the browser entirely and would
	 * discard twenty entered cells with no prompt and no navigation to
	 * intercept.
	 *
	 * A guard is `function (): boolean | Promise<boolean>` — false aborts
	 * the refresh and leaves both the region and the control alone. It is a
	 * registry rather than a cancellable event because the answer is a
	 * confirm dialog, and `ttConfirm` resolves asynchronously; a `preventDefault`
	 * on a synchronous event cannot wait for a person.
	 *
	 * @type {Array<function(): (boolean|Promise<boolean>)>}
	 */
	TT.filterRefreshGuards = TT.filterRefreshGuards || [];

	/** Resolve every guard; any false stops the refresh. */
	function guardsPass() {
		var answers = TT.filterRefreshGuards.map( function ( fn ) {
			try { return fn(); } catch ( e ) { return true; }
		} );
		return Promise.all( answers ).then( function ( results ) {
			return results.every( Boolean );
		} );
	}

	/** Milliseconds before the busy state is shown — see startPending(). */
	var PENDING_DELAY = 150;

	function ready( fn ) {
		if ( document.readyState !== 'loading' ) { fn(); }
		else { document.addEventListener( 'DOMContentLoaded', fn ); }
	}

	function i18n( key, fallback ) {
		return TT.i18n && TT.i18n[ key ] ? TT.i18n[ key ] : fallback;
	}

	ready( function () {
		var forms = document.querySelectorAll( '[data-tt-filterbar-form][data-tt-filter-refresh]' );
		Array.prototype.forEach.call( forms, init );
	} );

	function init( form ) {
		var region = document.querySelector( '[data-tt-filter-region]' );
		if ( ! region ) {
			// The surface asked for refresh but marked no region. Leaving the
			// native submit in place is the right failure: a full reload is
			// slower, not broken.
			return;
		}

		var bar     = form.closest( '[data-tt-filterbar]' ) || form;
		var pending = null;   // AbortController for the in-flight request
		var timer   = null;   // PENDING_DELAY handle

		var live = document.createElement( 'p' );
		live.className = 'tt-screen-reader-text';
		live.setAttribute( 'role', 'status' );
		live.setAttribute( 'aria-live', 'polite' );
		region.parentNode.insertBefore( live, region );

		/**
		 * The URL this form's current state describes.
		 *
		 * Built from the form itself rather than from the current location,
		 * so the hidden routing fields (tt_view, tt_back) come along and a
		 * cleared control drops its param instead of lingering.
		 */
		function targetUrl() {
			var data = new FormData( form );
			var params = new URLSearchParams();
			data.forEach( function ( value, key ) {
				if ( value === '' ) { return; }   // an empty control is not a filter
				params.append( key, value );
			} );
			var action = form.getAttribute( 'action' ) || window.location.pathname;
			var qs = params.toString();
			return qs ? action + ( action.indexOf( '?' ) === -1 ? '?' : '&' ) + qs : action;
		}

		function startPending() {
			// Only after a beat. A fast response that flashes a spinner reads
			// as jank; a slow one without it reads as broken.
			timer = window.setTimeout( function () {
				bar.classList.add( 'is-refreshing' );
				region.setAttribute( 'aria-busy', 'true' );
			}, PENDING_DELAY );
		}

		function endPending() {
			window.clearTimeout( timer );
			bar.classList.remove( 'is-refreshing' );
			region.removeAttribute( 'aria-busy' );
		}

		function announce( fresh ) {
			// The count the surface itself declares, so this script never has
			// to know what a "result" is on a report versus a grid.
			var n = fresh.getAttribute( 'data-tt-filter-count' );
			live.textContent = n === null
				? i18n( 'filters_applied', 'Filters applied.' )
				: i18n( 'results_count', '%s results' ).replace( '%s', n );
		}

		function refresh( push ) {
			var url = targetUrl();

			// A second change supersedes the first rather than stacking: the
			// last thing the reader asked for is the only answer that can be
			// correct, and two in flight can land out of order.
			if ( pending ) { pending.abort(); }
			pending = window.AbortController ? new AbortController() : null;

			startPending();

			window.fetch( url, {
				credentials: 'same-origin',
				headers: { 'X-Requested-With': 'XMLHttpRequest' },
				signal: pending ? pending.signal : undefined
			} )
				.then( function ( res ) {
					if ( ! res.ok ) { throw new Error( 'HTTP ' + res.status ); }
					return res.text();
				} )
				.then( function ( html ) {
					var doc   = new DOMParser().parseFromString( html, 'text/html' );
					var fresh = doc.querySelector( '[data-tt-filter-region]' );
					if ( ! fresh ) { throw new Error( 'no region in response' ); }

					region.innerHTML = fresh.innerHTML;
					if ( push ) { window.history.pushState( { ttFilter: 1 }, '', url ); }
					announce( fresh );
					remember();   // #3337 — the region now matches these values
					endPending();
					pending = null;

					// Anything the swapped-in markup needs to wire up. The
					// region's own scripts do not re-run on innerHTML, so a
					// surface with interactive output listens for this.
					region.dispatchEvent( new CustomEvent( 'tt:filter-refreshed', { bubbles: true } ) );
				} )
				.catch( function ( err ) {
					if ( err && err.name === 'AbortError' ) { return; }   // superseded
					// Fall back to the navigation this replaced. The reader
					// gets their filter applied; they just get a page load.
					endPending();
					window.location.assign( url );
				} );
		}

		// Any named control committing a value refreshes. `change` is what
		// both a select and the player picker's hidden input fire, and it is
		// what the list-table hydrator already listens for, so the two paths
		// agree on when a filter is "set".
		/**
		 * #3337 — ask the surface before swapping anything.
		 *
		 * `revert` puts the control back when a guard says no. Leaving a
		 * select showing a team whose rows were never loaded is the worst of
		 * both: the reader believes the filter applied and the data says
		 * otherwise.
		 */
		function requestRefresh( revert ) {
			guardsPass().then( function ( ok ) {
				if ( ok ) { refresh( true ); return; }
				if ( typeof revert === 'function' ) { revert(); }
			} );
		}

		// #3337 — the value each control had when the region last matched it.
		//
		// `change` fires AFTER the value has changed, so it is too late to
		// read the old one there; and a person answering a dialog takes long
		// enough that nothing transient survives. This is recorded at bind
		// time and updated on every successful refresh, so a declined guard
		// always has something true to put back.
		var committed = new WeakMap();
		function remember() {
			Array.prototype.forEach.call( form.elements, function ( el ) {
				if ( ! el.name ) { return; }
				committed.set( el, el.type === 'checkbox' || el.type === 'radio' ? el.checked : el.value );
			} );
		}
		remember();

		form.addEventListener( 'change', function ( e ) {
			var ctrl = e.target;
			if ( ! ctrl || ! ctrl.name ) { return; }
			requestRefresh( function () {
				if ( ! committed.has( ctrl ) ) { return; }
				var was = committed.get( ctrl );
				if ( ctrl.type === 'checkbox' || ctrl.type === 'radio' ) {
					ctrl.checked = was;
				} else {
					ctrl.value = was;
				}
			} );
		} );

		// The bar's Apply buttons (the sheet footer, the custom-range branch)
		// submit the form; take that over.
		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			requestRefresh( null );
		} );

		// Back/forward restores a previous filter state. Re-render from the
		// URL the browser put back rather than trusting the form, which still
		// holds the state we are navigating away from.
		window.addEventListener( 'popstate', function () {
			window.location.reload();
		} );
	}
} )();
