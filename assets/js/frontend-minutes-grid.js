/**
 * Minutes + statistics grid (#2386 / epic #2381, extended by #3094) —
 * desktop bulk entry for what a player was exposed to and what they produced.
 *
 * Players (rows) × matches (columns), three boxes per match: minutes, goals,
 * assists. Edits are tracked and written on one explicit Save. Config comes
 * in via window.TTMinutesGrid (wp_localize_script).
 *
 * ## Two endpoints, one Save
 *
 * Minutes go to `POST /minutes/bulk`, which routes each through the
 * minutes-ownership arbiter. Goals and assists go to
 * `PUT /activities/{id}/contributions`, which reconciles counts into goal
 * events. They are separate because the write rules are unrelated — folding
 * them into one endpoint would put two of them in one handler — but the
 * coach presses Save once and both go, because they typed one screen.
 *
 * ## The column switches
 *
 * Sub-columns triple the grid's width. The Goals / Assists chips hide their
 * columns and the choice is remembered per user, server-side, so it survives
 * a reload rather than being re-set at the start of every session.
 */
( function () {
	'use strict';

	var CFG = window.TTMinutesGrid || {};
	var I18N = CFG.i18n || {};

	var grid = document.querySelector( '.tt-agrid-card' );
	if ( ! grid ) {
		return;
	}

	var dirty = new Set();
	var dirtyStats = new Set();
	var statusEl = grid.querySelector( '[data-agrid-status]' );
	var saveBtn = grid.querySelector( '[data-agrid-save]' );

	function clampMinutes( raw ) {
		var n = parseInt( String( raw ).replace( /[^0-9]/g, '' ), 10 );
		if ( isNaN( n ) ) {
			return '';
		}
		return Math.max( 0, Math.min( 200, n ) );
	}

	// Goals and assists are counts, not durations: two digits is already
	// generous for one match and keeps a held-down key from producing a
	// number nobody can read.
	function clampStat( raw ) {
		var n = parseInt( String( raw ).replace( /[^0-9]/g, '' ), 10 );
		if ( isNaN( n ) ) {
			return '';
		}
		return Math.max( 0, Math.min( 99, n ) );
	}

	function sumInputs( tr, selector ) {
		var total = 0;
		tr.querySelectorAll( selector ).forEach( function ( inp ) {
			var n = parseInt( inp.value, 10 );
			if ( ! isNaN( n ) ) {
				total += n;
			}
		} );
		return total;
	}

	function recomputeTotal( tr ) {
		if ( ! tr ) {
			return;
		}
		var cell = tr.querySelector( '.tt-agrid__rate' );
		if ( cell ) {
			cell.textContent = String( sumInputs( tr, 'input.tt-agrid-min-in' ) );
		}

		[ 'goals', 'assists' ].forEach( function ( stat ) {
			var out = tr.querySelector( 'td.tt-agrid__rate[data-stat="' + stat + '"]' );
			if ( ! out ) {
				return;
			}
			out.textContent = String( sumInputs( tr, 'input.tt-agrid-stat-in[data-stat="' + stat + '"]' ) );
		} );
	}

	function refresh() {
		var n = dirty.size + dirtyStats.size;
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

	grid.querySelectorAll( 'input.tt-agrid-min-in' ).forEach( function ( inp ) {
		inp.addEventListener( 'input', function () {
			var td = inp.closest( 'td' );
			if ( td ) {
				td.classList.add( 'is-dirty' );
				dirty.add( td );
			}
			recomputeTotal( inp.closest( 'tr' ) );
			refresh();
		} );
		// Normalise + clamp when the box loses focus.
		inp.addEventListener( 'blur', function () {
			var v = clampMinutes( inp.value );
			inp.value = v === '' ? '' : String( v );
			recomputeTotal( inp.closest( 'tr' ) );
		} );
	} );

	grid.querySelectorAll( 'input.tt-agrid-stat-in' ).forEach( function ( inp ) {
		inp.addEventListener( 'input', function () {
			var td = inp.closest( 'td' );
			if ( td ) {
				td.classList.add( 'is-dirty' );
				dirtyStats.add( td );
			}
			recomputeTotal( inp.closest( 'tr' ) );
			refresh();
		} );
		inp.addEventListener( 'blur', function () {
			var v = clampStat( inp.value );
			// Empty rather than "0": a match a player was in but did not
			// score in should read as blank, the way the paper sheet does.
			inp.value = v === '' || v === 0 ? '' : String( v );
			recomputeTotal( inp.closest( 'tr' ) );
		} );
	} );

	// ---- column switches ---------------------------------------------

	function applyStatVisibility( stats ) {
		grid.querySelectorAll( '[data-stat]' ).forEach( function ( el ) {
			var stat = el.getAttribute( 'data-stat' );
			if ( stat !== 'goals' && stat !== 'assists' ) {
				return;
			}
			// A hidden column must not be in the tab order either, or a
			// keyboard user still walks through boxes nobody can see.
			var hide = stats.indexOf( stat ) === -1;
			el.hidden = hide;
			var input = el.querySelector ? el.querySelector( 'input' ) : null;
			if ( input ) {
				input.disabled = hide;
			}
		} );
	}

	function currentStats() {
		var out = [];
		grid.querySelectorAll( '[data-agrid-stattoggle]' ).forEach( function ( box ) {
			if ( box.checked ) {
				out.push( box.value );
			}
		} );
		return out;
	}

	grid.querySelectorAll( '[data-agrid-stattoggle]' ).forEach( function ( box ) {
		box.addEventListener( 'change', function () {
			var stats = currentStats();
			applyStatVisibility( stats );

			// Fire-and-forget: a preference that failed to save is a column
			// switch the coach has to flick again next time, not something
			// worth an error dialog over their data-entry screen.
			if ( CFG.restPrefs ) {
				fetch( CFG.restPrefs, {
					method: 'PUT',
					credentials: 'same-origin',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': CFG.nonce || ''
					},
					body: JSON.stringify( { stats: stats } )
				} )['catch']( function () {} );
			}
		} );
	} );

	applyStatVisibility( Array.isArray( CFG.stats ) ? CFG.stats : [ 'goals', 'assists' ] );

	function collectChanges() {
		var changes = [];
		dirty.forEach( function ( td ) {
			var inp = td.querySelector( 'input.tt-agrid-min-in' );
			var v = inp ? clampMinutes( inp.value ) : '';
			changes.push( {
				activity_id: parseInt( td.getAttribute( 'data-activity' ), 10 ),
				player_id: parseInt( td.getAttribute( 'data-player' ), 10 ),
				minutes: v === '' ? '' : v
			} );
		} );
		return changes;
	}

	/**
	 * Statistic edits, grouped by match.
	 *
	 * The endpoint takes counts per player per match, and a player's goals
	 * and assists are reconciled together — so a row must carry both boxes'
	 * current values even when only one of them was touched, or saving a
	 * goal would read the assist as zero and reverse it.
	 */
	function collectStatPayloads() {
		var byActivity = {};

		dirtyStats.forEach( function ( td ) {
			var aid = td.querySelector( 'input' ).getAttribute( 'data-activity' );
			var pid = td.querySelector( 'input' ).getAttribute( 'data-player' );
			if ( ! aid || ! pid ) {
				return;
			}
			byActivity[ aid ] = byActivity[ aid ] || {};
			byActivity[ aid ][ pid ] = true;
		} );

		return Object.keys( byActivity ).map( function ( aid ) {
			var players = Object.keys( byActivity[ aid ] ).map( function ( pid ) {
				return {
					player_id: parseInt( pid, 10 ),
					goals: readStat( aid, pid, 'goals' ),
					assists: readStat( aid, pid, 'assists' )
				};
			} );
			return { activity_id: parseInt( aid, 10 ), players: players };
		} );
	}

	function readStat( activityId, playerId, stat ) {
		var sel = 'input.tt-agrid-stat-in[data-stat="' + stat + '"]'
			+ '[data-activity="' + activityId + '"][data-player="' + playerId + '"]';
		var inp = grid.querySelector( sel );
		if ( ! inp ) {
			return 0;
		}
		var v = clampStat( inp.value );
		return v === '' ? 0 : v;
	}

	function postJson( url, method, body ) {
		return fetch( url, {
			method: method,
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': CFG.nonce || ''
			},
			body: JSON.stringify( body )
		} ).then( function ( r ) {
			return r.json().then( function ( payload ) {
				if ( ! r.ok || ! payload || payload.success !== true ) {
					throw new Error( 'save_failed' );
				}
				return payload;
			} );
		} );
	}

	function save() {
		if ( dirty.size === 0 && dirtyStats.size === 0 ) {
			return;
		}
		if ( saveBtn ) {
			saveBtn.disabled = true;
		}
		if ( statusEl ) {
			statusEl.classList.remove( 'is-dirty', 'is-error', 'is-saved' );
			statusEl.textContent = I18N.saving || '';
		}

		var writes = [];

		if ( dirty.size > 0 && CFG.restBulk ) {
			writes.push( postJson( CFG.restBulk, 'POST', { changes: collectChanges() } ) );
		}

		if ( dirtyStats.size > 0 && CFG.restStats ) {
			collectStatPayloads().forEach( function ( payload ) {
				writes.push( postJson(
					CFG.restStats + payload.activity_id + '/contributions',
					'PUT',
					{ players: payload.players }
				) );
			} );
		}

		// All or nothing for the message: a partial failure has to read as a
		// failure, because a coach told "saved" over a half-written screen
		// will close the tab.
		Promise.all( writes )
			.then( function () {
				dirty.forEach( function ( td ) {
					td.classList.remove( 'is-dirty' );
				} );
				dirtyStats.forEach( function ( td ) {
					td.classList.remove( 'is-dirty' );
				} );
				dirty.clear();
				dirtyStats.clear();
				if ( statusEl ) {
					statusEl.classList.add( 'is-saved' );
					statusEl.textContent = I18N.saved || '';
				}
				if ( saveBtn ) {
					saveBtn.disabled = true;
				}
			} )
			['catch']( function () {
				// The edits stay in the boxes and stay dirty. A failed save
				// that cleared them would throw away the work it just failed
				// to store.
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

	window.addEventListener( 'beforeunload', function ( e ) {
		if ( dirty.size > 0 || dirtyStats.size > 0 ) {
			e.preventDefault();
			e.returnValue = I18N.confirm || '';
			return I18N.confirm || '';
		}
	} );

	// #3337 — the same promise for an in-place filter change, which is not a
	// navigation and so never reaches `beforeunload` above. Warn and let the
	// coach choose; `false` leaves the grid and the control untouched.
	window.TT = window.TT || {};
	window.TT.filterRefreshGuards = window.TT.filterRefreshGuards || [];
	window.TT.filterRefreshGuards.push( function () {
		if ( dirty.size === 0 && dirtyStats.size === 0 ) { return true; }
		var message = I18N.confirm_filter || I18N.confirm || '';
		if ( typeof window.ttConfirm === 'function' ) {
			return window.ttConfirm( { message: message, danger: true } );
		}
		return window.confirm( message );
	} );

	refresh();
}() );
