/**
 * Attendance grid (#2382 / epic #2381) — desktop bulk attendance entry.
 *
 * Players (rows) × activities (columns). Each cell is a native <select>
 * whose closed text is hidden; the compact abbreviation is painted over it
 * (full words stay in the open list). Edits are tracked per cell and written
 * in one batch to POST /talenttrack/v1/attendance/bulk on Save.
 *
 * No framework: config comes in via window.TTAttendanceGrid (wp_localize_script).
 */
( function () {
	'use strict';

	var CFG = window.TTAttendanceGrid || {};
	var STATUSES = Array.isArray( CFG.statuses ) ? CFG.statuses : [];
	var I18N = CFG.i18n || {};

	var SHORT = {};
	var MOD = {};
	var MOD_CLASSES = [];
	STATUSES.forEach( function ( s ) {
		SHORT[ s.value ] = s.short;
		MOD[ s.value ] = s.mod;
		MOD_CLASSES.push( 'tt-agrid-cell--' + s.mod );
	} );

	var grid = document.querySelector( '.tt-agrid-card' );
	if ( ! grid ) {
		return;
	}

	var dirty = new Set();
	var statusEl = grid.querySelector( '[data-agrid-status]' );
	var saveBtn = grid.querySelector( '[data-agrid-save]' );

	function paintCell( td, value ) {
		MOD_CLASSES.forEach( function ( c ) {
			td.classList.remove( c );
		} );
		td.classList.add( 'tt-agrid-cell--' + ( MOD[ value ] || 'empty' ) );
		var abbr = td.querySelector( '.tt-agrid-abbr' );
		if ( abbr ) {
			abbr.textContent = value !== '' && SHORT[ value ] !== undefined ? SHORT[ value ] : ( SHORT[ '' ] || '' );
		}
	}

	// Live-recompute a row's Present % from its current dropdowns, so the
	// figure tracks the edits in the grid instead of the page-load snapshot.
	// Present + Late count as attended, over the cells that carry a status.
	function recomputeRate( tr ) {
		if ( ! tr ) {
			return;
		}
		var cell = tr.querySelector( '.tt-agrid__rate' );
		if ( ! cell ) {
			return;
		}
		var recorded = 0;
		var attended = 0;
		tr.querySelectorAll( 'select.tt-agrid-sel' ).forEach( function ( s ) {
			if ( s.value !== '' ) {
				recorded++;
				if ( s.value === 'present' || s.value === 'late' ) {
					attended++;
				}
			}
		} );
		cell.textContent = recorded > 0 ? Math.round( attended / recorded * 100 ) + '%' : '—';
	}

	function refresh() {
		var n = dirty.size;
		if ( statusEl ) {
			statusEl.classList.remove( 'is-dirty', 'is-error', 'is-saved' );
			if ( n > 0 ) {
				statusEl.classList.add( 'is-dirty' );
				statusEl.textContent = ( I18N.unsaved || '%d unsaved change(s)' ).replace( '%d', String( n ) );
			} else {
				statusEl.textContent = I18N.noChanges || '';
			}
		}
		if ( saveBtn ) {
			saveBtn.disabled = n === 0;
		}
	}

	function markDirty( td ) {
		td.classList.add( 'is-dirty' );
		dirty.add( td );
		refresh();
	}

	// cell edits
	grid.querySelectorAll( 'select.tt-agrid-sel' ).forEach( function ( sel ) {
		sel.addEventListener( 'change', function () {
			var td = sel.closest( 'td' );
			if ( ! td ) {
				return;
			}
			paintCell( td, sel.value );
			markDirty( td );
			recomputeRate( sel.closest( 'tr' ) );
		} );
	} );

	// "all present" fills a whole activity column
	grid.querySelectorAll( '.tt-agrid__fill' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			var aid = btn.getAttribute( 'data-activity' );
			grid.querySelectorAll( 'td[data-activity="' + aid + '"]' ).forEach( function ( td ) {
				var sel = td.querySelector( 'select.tt-agrid-sel' );
				if ( sel && sel.value !== 'present' ) {
					sel.value = 'present';
					paintCell( td, 'present' );
					markDirty( td );
				}
			} );
			grid.querySelectorAll( 'tbody tr' ).forEach( recomputeRate );
		} );
	} );

	function collectChanges() {
		var changes = [];
		dirty.forEach( function ( td ) {
			var sel = td.querySelector( 'select.tt-agrid-sel' );
			changes.push( {
				activity_id: parseInt( td.getAttribute( 'data-activity' ), 10 ),
				player_id: parseInt( td.getAttribute( 'data-player' ), 10 ),
				status: sel ? sel.value : ''
			} );
		} );
		return changes;
	}

	/**
	 * #2521 — which of the edited columns are past-dated activities that
	 * are still planned. Saving those marks them completed, so the coach
	 * is told first. Returns their column labels, newest column last.
	 */
	function activitiesToComplete() {
		var ids = {};
		dirty.forEach( function ( td ) {
			ids[ td.getAttribute( 'data-activity' ) ] = true;
		} );
		var labels = [];
		grid.querySelectorAll( 'th[data-completes="1"]' ).forEach( function ( th ) {
			if ( ids[ th.getAttribute( 'data-activity' ) ] ) {
				labels.push( th.getAttribute( 'data-label' ) || '' );
			}
		} );
		return labels;
	}

	function escapeHtml( s ) {
		return String( s ).replace( /[&<>"']/g, function ( c ) {
			return ( { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' } )[ c ];
		} );
	}

	/**
	 * Confirmation before a save that changes an activity's status. Uses
	 * the same `<dialog>`-based app modal as the archive/reopen buttons
	 * (never window.confirm — pilot feedback: a browser notification does
	 * not read as part of the application). Falls back to window.confirm
	 * only where <dialog> is unsupported.
	 */
	function confirmCompletion( labels, onResult ) {
		var plain = ( I18N.completeIntro || '' ) + '\n\n' + labels.join( '\n' );
		if ( typeof HTMLDialogElement === 'undefined' ) {
			onResult( window.confirm( plain ) );
			return;
		}
		var dialog = document.getElementById( 'tt-agrid-complete-dialog' );
		if ( ! dialog ) {
			dialog = document.createElement( 'dialog' );
			dialog.id = 'tt-agrid-complete-dialog';
			dialog.className = 'tt-modal tt-modal--agrid-complete';
			dialog.innerHTML =
				'<form method="dialog" class="tt-modal-form">' +
					'<h2 class="tt-modal-title">' + escapeHtml( I18N.completeTitle || '' ) + '</h2>' +
					'<p class="tt-modal-message">' + escapeHtml( I18N.completeIntro || '' ) + '</p>' +
					'<ul class="tt-modal-list" data-agrid-complete-list></ul>' +
					'<p class="tt-modal-message">' + escapeHtml( I18N.completeOutro || '' ) + '</p>' +
					'<div class="tt-modal-actions">' +
						'<button type="submit" value="cancel" class="tt-btn tt-btn-secondary">' + escapeHtml( I18N.completeCancel || '' ) + '</button>' +
						'<button type="submit" value="confirm" class="tt-btn tt-btn-primary">' + escapeHtml( I18N.completeConfirm || '' ) + '</button>' +
					'</div>' +
				'</form>';
			document.body.appendChild( dialog );
		}
		var list = dialog.querySelector( '[data-agrid-complete-list]' );
		list.innerHTML = '';
		labels.forEach( function ( label ) {
			var li = document.createElement( 'li' );
			li.textContent = label;
			list.appendChild( li );
		} );

		var closeHandler = function () {
			dialog.removeEventListener( 'close', closeHandler );
			onResult( dialog.returnValue === 'confirm' );
		};
		dialog.addEventListener( 'close', closeHandler );
		dialog.returnValue = '';
		dialog.showModal();
	}

	function save() {
		if ( dirty.size === 0 || ! CFG.restBulk ) {
			return;
		}
		// #2521 — a save that changes an activity's status is never silent.
		var completing = activitiesToComplete();
		if ( completing.length && ! save.confirmed ) {
			confirmCompletion( completing, function ( ok ) {
				if ( ! ok ) return;
				save.confirmed = true;
				save();
				save.confirmed = false;
			} );
			return;
		}
		if ( saveBtn ) {
			saveBtn.disabled = true;
		}
		if ( statusEl ) {
			statusEl.classList.remove( 'is-dirty', 'is-error', 'is-saved' );
			statusEl.textContent = I18N.saving || '';
		}
		fetch( CFG.restBulk, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': CFG.nonce || ''
			},
			body: JSON.stringify( { changes: collectChanges() } )
		} )
			.then( function ( r ) {
				return r.json().then( function ( body ) {
					return { ok: r.ok, body: body };
				} );
			} )
			.then( function ( res ) {
				if ( ! res.ok || ! res.body || res.body.success !== true ) {
					throw new Error( 'save_failed' );
				}
				dirty.forEach( function ( td ) {
					td.classList.remove( 'is-dirty' );
				} );
				dirty.clear();
				if ( statusEl ) {
					statusEl.classList.add( 'is-saved' );
					// #2521 — recording a register completes a past-dated
					// activity that was still planned. Say so: the coach
					// is entitled to know their entry changed a status.
					var done = res.body.data && res.body.data.completed
						? parseInt( res.body.data.completed, 10 )
						: 0;
					statusEl.textContent = done > 0 && I18N.completed
						? I18N.completed.replace( '%d', String( done ) )
						: ( I18N.saved || '' );
				}
				if ( saveBtn ) {
					saveBtn.disabled = true;
				}
			} )
			.catch( function () {
				if ( statusEl ) {
					statusEl.classList.add( 'is-error' );
					statusEl.textContent = I18N.error || '';
				}
				if ( saveBtn ) {
					saveBtn.disabled = false;
				}
			} );
	}

	if ( saveBtn ) {
		saveBtn.addEventListener( 'click', save );
	}

	// guard against losing edits
	window.addEventListener( 'beforeunload', function ( e ) {
		if ( dirty.size > 0 ) {
			e.preventDefault();
			e.returnValue = I18N.confirm || '';
			return I18N.confirm || '';
		}
	} );

	// #3337 — the same promise, for a filter change that no longer navigates.
	//
	// `beforeunload` above is the browser's guard against losing entered
	// cells, and it only fires on a real navigation. Filtering in place
	// (#3336) is not one, so without this a coach who had entered twenty
	// cells and then re-scoped the grid would lose them with no prompt.
	//
	// Warn and let them choose, per the decision on #3337: saving first
	// would turn a filter change into a write, which breaks the explicit-Save
	// promise this surface makes (CLAUDE.md §6 model B); refusing while dirty
	// would block a legitimate read. `false` leaves the grid and the control
	// exactly as they were.
	window.TT = window.TT || {};
	window.TT.filterRefreshGuards = window.TT.filterRefreshGuards || [];
	window.TT.filterRefreshGuards.push( function () {
		if ( dirty.size === 0 ) { return true; }
		var message = I18N.confirm_filter || I18N.confirm || '';
		if ( typeof window.ttConfirm === 'function' ) {
			return window.ttConfirm( { message: message, danger: true } );
		}
		return window.confirm( message );
	} );

	grid.querySelectorAll( 'tbody tr' ).forEach( recomputeRate );
	refresh();
}() );
