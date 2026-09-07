/*
 * frontend-match-prep.js — #965 head-coach match preparation surface.
 *
 * Drives the 3-column layout in FrontendMatchPrepView:
 *   - Left: roster + auto-updating minute counters per half.
 *   - Middle: two half-pitches with click-to-pick / drag-drop slot
 *     assignment, a 1e → 2e copy button, and the tactical-goals grid.
 *   - Right: per-player attention text + ! flag + camera flag, and the
 *     captain + 5 set-piece-taker pane.
 *
 * Server state ships as JSON in <script id="tt-match-prep-bootstrap">.
 * The page mounts state, renders, and live-saves each edit via REST:
 *
 *   PUT    /talenttrack/v1/match-prep/<activity_id>          (form state)
 *   PUT    /talenttrack/v1/match-prep/<prep_id>/role          (role assign)
 *   DELETE /talenttrack/v1/match-prep/<prep_id>/role/<role>   (role clear)
 *
 * Saves are debounced per field so a coach typing into the attention
 * field doesn't fire one POST per keystroke; slot picks / role picks /
 * flag toggles save immediately because they're discrete events.
 */
(function () {
    'use strict';

    /**
     * #3297 — rows the "Player focus" block reserves on the printed sheet.
     *
     * A squad list grows and shrinks as availability changes; the writing
     * room on paper does not. Fifteen covers a full match-day squad with
     * lines to spare, and the block always looks the same in the coach's
     * hand. Mirrors FOCUS_LINES in the design mockup.
     */
    var FOCUS_LINES = 15;

    var root = document.querySelector('.tt-match-prep');
    if (!root) return;

    var cfg = window.TT_MATCH_PREP || {};
    var bootstrapEl = document.getElementById('tt-match-prep-bootstrap');
    if (!bootstrapEl) return;

    var bootstrap;
    try {
        bootstrap = JSON.parse(bootstrapEl.textContent || '{}');
    } catch (e) {
        return;
    }

    // ---------------------------------------------------------------------
    // State
    // ---------------------------------------------------------------------

    var state = {
        activityId:     parseInt(bootstrap.activity_id, 10) || 0,
        prepId:         parseInt(bootstrap.prep_id, 10) || 0,
        halfLength:     parseInt(bootstrap.half_length, 10) || 35,
        formationShape: String(bootstrap.formation_shape || '4-2-3-1'),
        formationTemplateId: parseInt(bootstrap.formation_template_id, 10) || 0,
        slotLayouts:    bootstrap.slot_layouts || {},
        templateLayouts: bootstrap.template_layouts || {},
        roleDefs:       bootstrap.roles || [],
        players:        Array.isArray(bootstrap.players) ? bootstrap.players : [],
        availability:   {},
        lineup:         { '1': new Map(), '2': new Map() },
        attention:      {},
        specific:       {},
        analyst:        {},
        rolesAssigned:  {},
        // `dirty` and `savingCount` moved into TT.Autosave (#3004). Ask
        // `saver.isPending()` rather than reading a flag from here.
        cancelUrl:      String(bootstrap.cancel_url || '')
    };

    // Populate maps + objects from bootstrap.
    (function () {
        var availObj = bootstrap.availability || {};
        Object.keys(availObj).forEach(function (pid) {
            state.availability[String(pid)] = availObj[pid];
        });
        ['1', '2'].forEach(function (half) {
            var src = (bootstrap.lineup || {})[half] || {};
            Object.keys(src).forEach(function (slot) {
                state.lineup[half].set(parseInt(slot, 10), parseInt(src[slot], 10));
            });
        });
        var attObj = bootstrap.attention || {};
        Object.keys(attObj).forEach(function (pid) { state.attention[String(pid)] = String(attObj[pid] || ''); });
        var specObj = bootstrap.specific || {};
        Object.keys(specObj).forEach(function (pid) { state.specific[String(pid)] = !!specObj[pid]; });
        var anaObj = bootstrap.analyst || {};
        Object.keys(anaObj).forEach(function (pid) { state.analyst[String(pid)] = !!anaObj[pid]; });
        var rolesObj = bootstrap.roles_assigned || {};
        Object.keys(rolesObj).forEach(function (key) { state.rolesAssigned[key] = parseInt(rolesObj[key], 10) || 0; });
    })();

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    function $(sel, ctx) { return (ctx || root).querySelector(sel); }
    function $$(sel, ctx) { return Array.prototype.slice.call((ctx || root).querySelectorAll(sel)); }

    function playerById(id) {
        id = parseInt(id, 10);
        for (var i = 0; i < state.players.length; i++) {
            if (state.players[i].id === id) return state.players[i];
        }
        return null;
    }

    function availablePlayers() {
        return state.players.filter(function (p) {
            var a = state.availability[String(p.id)];
            return a && String(a.status).toLowerCase() === 'present';
        });
    }

    function isOnPitch(half, pid) {
        var map = state.lineup[String(half)];
        if (!map) return false;
        var found = false;
        map.forEach(function (v) { if (v === pid) found = true; });
        return found;
    }

    function placedCount(pid) {
        var n = 0;
        if (isOnPitch(1, pid)) n++;
        if (isOnPitch(2, pid)) n++;
        return n;
    }

    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function i18n(key, fallback) {
        var t = cfg.i18n || {};
        return t[key] != null ? t[key] : (fallback || key);
    }

    function format(template, vars) {
        return template.replace(/%(\d)\$s/g, function (m, n) {
            var idx = parseInt(n, 10) - 1;
            return vars[idx] != null ? vars[idx] : m;
        });
    }

    // ---------------------------------------------------------------------
    // REST plumbing
    // ---------------------------------------------------------------------

    var baseUrl = String(cfg.rest_url || '/wp-json/talenttrack/v1/match-prep/');

    // #3004 (epic #2881) — the debounce, the save state and the error path
    // are TT.Autosave's now. This file used to own all three, and three other
    // surfaces owned their own copies; the loop that lived here was the
    // reference the component was lifted from.
    //
    // One behaviour changes with the move, deliberately: saves no longer
    // overlap. The old `savingCount` let two writes to the same record be in
    // the air at once, and whichever answered second won regardless of which
    // was composed second — so a fast typist could watch a character vanish.
    // The component keeps one request in flight and fires exactly one more
    // afterwards, carrying the state as it is by then.
    //
    // #3005 adds `apply`: the component keeps the payload each save replaced
    // and hands it back here when a coach undoes. Everything the full payload
    // covers — lineup, half length, formation, goals, per-player focus — is
    // therefore undoable in one step. Role picks are not: they write on their
    // own endpoint through `send()`, which retires the offer rather than
    // letting Undo revert something older than the coach's last action.
    //
    // #3006 adds `storageKey`: the payload as this page load found it is
    // snapshotted into `localStorage`, so "revert to how it was when I
    // opened this" survives a reload and dies with the sitting. Role picks
    // retire the offer for the same reason they retire undo — they write
    // state the full payload cannot restore.
    var saver = TT.Autosave.create({
        stateEl:  $('[data-tt-save-state]'),
        undoEl:   $('[data-tt-save-undo]'),
        revertEl: $('[data-tt-save-revert]'),
        storageKey: 'match-prep:' + state.activityId,
        nonce:   cfg.rest_nonce || '',
        delay:   250,
        i18n:    (window.TT_Autosave && window.TT_Autosave.i18n) || {},
        serialise: function () {
            if (state.activityId <= 0) return null;
            return { method: 'PUT', url: baseUrl + state.activityId, body: buildFullPayload() };
        },
        apply: function (payload) {
            mountFullPayload(payload);
            renderAll();
        }
    });

    // Named once because two functions walk them now — the payload builder
    // and the undo that puts a payload back (#3005).
    var GOAL_FIELDS = ['goals_general','goals_attack','goals_defend','goals_attack_setpiece','goals_defend_setpiece'];

    function buildFullPayload() {
        var lineupOut = { '1': {}, '2': {} };
        ['1', '2'].forEach(function (half) {
            state.lineup[half].forEach(function (pid, slot) {
                lineupOut[half][String(slot)] = pid;
            });
        });
        var attentionRows = {};
        var allPids = new Set();
        Object.keys(state.attention).forEach(function (pid) { allPids.add(pid); });
        Object.keys(state.specific).forEach(function (pid) { allPids.add(pid); });
        Object.keys(state.analyst).forEach(function (pid) { allPids.add(pid); });
        allPids.forEach(function (pid) {
            attentionRows[pid] = {
                attention_text:    state.attention[pid] || '',
                is_specific_goal:  !!state.specific[pid],
                analyst_appointed: !!state.analyst[pid]
            };
        });
        var goals = {};
        GOAL_FIELDS.forEach(function (field) {
            var inputs = $$('[data-tt-mp-goal="' + field + '"]');
            goals[field] = inputs.map(function (i) { return String(i.value || ''); }).join('\n').replace(/\n+$/, '');
        });
        var payload = {
            formation_template_id: parseInt($('[data-tt-mp-formation]').value, 10) || null,
            half_length_minutes:   state.halfLength,
            lineup:                lineupOut,
            player_goals:          attentionRows
        };
        Object.keys(goals).forEach(function (k) { payload[k] = goals[k]; });
        return payload;
    }

    /**
     * The inverse of buildFullPayload(): a previously committed payload goes
     * back into `state` and into the toolbar controls that hold their value
     * in the DOM rather than in state.
     *
     * renderAll() repaints everything drawn from state — roster, pitches,
     * player focus — so only the fields it does not own are written here:
     * the half-length box, the formation select and the tactical-goal
     * inputs. Undo (#3005) is the only caller.
     */
    function mountFullPayload(payload) {
        if (!payload) return;

        var halflen = parseInt(payload.half_length_minutes, 10);
        if (!isNaN(halflen) && halflen > 0) {
            state.halfLength = halflen;
            var hl = $('[data-tt-mp-halflen]');
            if (hl) hl.value = state.halfLength;
        }

        // Same three side-effects the select's own change handler has: the
        // shape drives the pitch layout, the root attribute and the KPI tile.
        var sel = $('[data-tt-mp-formation]');
        if (sel) {
            var tplId = parseInt(payload.formation_template_id, 10) || 0;
            sel.value = tplId ? String(tplId) : '';
            state.formationTemplateId = tplId;
            var opt = sel.selectedOptions && sel.selectedOptions[0];
            var shape = opt ? (opt.getAttribute('data-shape') || '') : '';
            if (shape) {
                state.formationShape = shape;
                root.setAttribute('data-formation-shape', shape);
                var kpiVal = document.querySelector('[data-tt-mp-formation-kpi] .tt-kpi__val');
                if (kpiVal) kpiVal.textContent = shape;
            }
        }

        var lineup = payload.lineup || {};
        ['1', '2'].forEach(function (half) {
            var map = state.lineup[half];
            map.clear();
            var src = lineup[half] || {};
            Object.keys(src).forEach(function (slot) {
                map.set(parseInt(slot, 10), parseInt(src[slot], 10));
            });
        });

        // Replaced wholesale rather than merged: a player dropped from the
        // payload had their row cleared, and merging would leave it standing.
        state.attention = {};
        state.specific  = {};
        state.analyst   = {};
        var rows = payload.player_goals || {};
        Object.keys(rows).forEach(function (pid) {
            var row = rows[pid] || {};
            state.attention[String(pid)] = String(row.attention_text || '');
            state.specific[String(pid)]  = !!row.is_specific_goal;
            state.analyst[String(pid)]   = !!row.analyst_appointed;
        });

        // Goal boxes live in the DOM, one line per input, which is how the
        // payload joined them.
        GOAL_FIELDS.forEach(function (field) {
            var inputs = $$('[data-tt-mp-goal="' + field + '"]');
            var lines  = String(payload[field] == null ? '' : payload[field]).split('\n');
            inputs.forEach(function (inp, i) { inp.value = lines[i] != null ? lines[i] : ''; });
        });
    }

    // Debounced full save — for inputs typed into rapidly (attention text,
    // goal text, half length). Discrete events (slot picks, flag toggles)
    // call saveAll() directly so the user sees the dot turn green.
    function scheduleSave(delay) { saver.change(delay); }

    function saveAll() {
        if (state.activityId <= 0) return Promise.resolve();
        return saver.saveNow();
    }

    // Roles write to their own endpoint rather than being a slice of the
    // full payload, so they go through `send()` — same status line, same
    // ordering guarantee, no second save loop in this file.
    function saveRole(roleKey, playerId) {
        if (state.prepId <= 0 || !roleKey) return Promise.resolve();
        return saver.send({
            method: 'PUT',
            url: baseUrl + state.prepId + '/role',
            body: { role_key: roleKey, player_id: playerId }
        });
    }

    function clearRoleRemote(roleKey) {
        if (state.prepId <= 0 || !roleKey) return Promise.resolve();
        return saver.send({
            method: 'DELETE',
            url: baseUrl + state.prepId + '/role/' + encodeURIComponent(roleKey)
        });
    }

    function markDirty() { saver.change(); }

    // ---------------------------------------------------------------------
    // Rendering
    // ---------------------------------------------------------------------

    // renderSaveState() is gone — TT.Autosave renders the status line, and
    // this file's only job is to hand it the element (see `saver` above).

    function renderAll() {
        renderRoster();
        renderPitches();
        renderDps();
        renderRoles();
        renderFoot();
        saver.render();
    }

    function renderRoster() {
        var tbody = $('[data-tt-mp-roster]');
        if (!tbody) return;
        var avail = availablePlayers();
        // Sort by name (last-name component sort isn't available client-side
        // — we already get the formatted display name; sort lexicographically).
        avail.sort(function (a, b) { return a.name.localeCompare(b.name); });

        if (!avail.length) {
            tbody.innerHTML = '<tr><td colspan="4" class="tt-mp-empty">'
                + escapeHtml(i18n('no_players', 'No availability captured yet.'))
                + '<br><a href="#" data-tt-mp-open-availability>'
                + escapeHtml(i18n('present', 'Open availability'))
                + '</a></td></tr>';
            return;
        }

        tbody.innerHTML = avail.map(function (p) {
            var on1 = isOnPitch(1, p.id);
            var on2 = isOnPitch(2, p.id);
            var min1 = on1 ? state.halfLength : 0;
            var min2 = on2 ? state.halfLength : 0;
            var tot = min1 + min2;
            var cls = (on1 || on2) ? 'tt-mp-assigned' : 'tt-mp-unassigned';
            return '<tr data-pid="' + p.id + '" class="tt-mp-roster-row ' + cls + '" draggable="true">'
                + '<td class="tt-mp-col-name">' + escapeHtml(p.name) + '</td>'
                + '<td class="tt-mp-col-min ' + (on1 ? 'tt-mp-on' : 'tt-mp-off') + '">' + min1 + '</td>'
                + '<td class="tt-mp-col-min ' + (on2 ? 'tt-mp-on' : 'tt-mp-off') + '">' + min2 + '</td>'
                + '<td class="tt-mp-col-tot ' + (tot ? 'tt-mp-on' : 'tt-mp-off') + '">' + tot + '</td>'
                + '</tr>';
        }).join('');

        $$('tr.tt-mp-roster-row', tbody).forEach(function (tr) {
            tr.addEventListener('dragstart', function (e) {
                tr.classList.add('tt-mp-dragging');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', String(tr.getAttribute('data-pid')));
            });
            tr.addEventListener('dragend', function () { tr.classList.remove('tt-mp-dragging'); });
        });
    }

    function currentSlotLayout() {
        // #2099 — a bound template's own geometry (from slots_json) wins, so
        // a 3-4-3 diamond draws as a diamond instead of the flat shape default.
        var tpl = state.templateLayouts || {};
        if (state.formationTemplateId && tpl[state.formationTemplateId]) {
            return tpl[state.formationTemplateId];
        }
        var layouts = state.slotLayouts || {};
        if (layouts[state.formationShape]) return layouts[state.formationShape];
        if (layouts['4-2-3-1']) return layouts['4-2-3-1'];
        var keys = Object.keys(layouts);
        return keys.length ? layouts[keys[0]] : [];
    }

    function renderPitches() {
        var layout = currentSlotLayout();
        [1, 2].forEach(function (half) {
            var pitch = $('.tt-mp-pitch[data-half="' + half + '"]');
            if (!pitch) return;
            // Remove old slots.
            $$('.tt-mp-slot', pitch).forEach(function (el) { el.parentNode.removeChild(el); });
            var map = state.lineup[String(half)];
            layout.forEach(function (pos) {
                var pid = map.get(pos.num) || null;
                var p = pid ? playerById(pid) : null;
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'tt-mp-slot' + (p ? '' : ' tt-mp-slot-empty');
                btn.setAttribute('data-half', String(half));
                btn.setAttribute('data-slot', String(pos.num));
                btn.style.left = pos.x + '%';
                btn.style.top = pos.y + '%';
                btn.setAttribute('aria-label', format(i18n('slot_label', 'Slot %1$s — %2$s half'), [pos.num, half === 1 ? i18n('half_1', '1st') : i18n('half_2', '2nd')]));
                btn.innerHTML =
                    '<span class="tt-mp-slot-num">' + pos.num + '</span>' +
                    '<span class="tt-mp-slot-name">' + (p ? escapeHtml(p.name) : '–') + '</span>';
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    openPicker(btn, { kind: 'slot', half: half, slot: pos.num });
                });
                btn.addEventListener('dragover', function (e) {
                    e.preventDefault();
                    btn.classList.add('tt-mp-drag-over');
                });
                btn.addEventListener('dragleave', function () {
                    btn.classList.remove('tt-mp-drag-over');
                });
                btn.addEventListener('drop', function (e) {
                    e.preventDefault();
                    btn.classList.remove('tt-mp-drag-over');
                    var dragPid = parseInt(e.dataTransfer.getData('text/plain'), 10);
                    if (!dragPid) return;
                    assignSlot(half, pos.num, dragPid);
                });
                pitch.appendChild(btn);
            });
        });
    }

    function renderDps() {
        var tbody = $('[data-tt-mp-dps]');
        if (!tbody) return;
        var avail = availablePlayers();
        avail.sort(function (a, b) { return a.name.localeCompare(b.name); });

        if (!avail.length) {
            tbody.innerHTML = '<tr><td colspan="4" class="tt-mp-empty">' + escapeHtml(i18n('no_players', 'No availability captured yet.')) + '</td></tr>';
            return;
        }
        tbody.innerHTML = avail.map(function (p) {
            var pidStr = String(p.id);
            var text = state.attention[pidStr] || '';
            var spec = !!state.specific[pidStr];
            var cam  = !!state.analyst[pidStr];
            return '<tr data-pid="' + p.id + '">'
                + '<td class="tt-mp-col-name">' + escapeHtml(p.name) + '</td>'
                + '<td class="tt-mp-col-text">'
                +   '<input type="text" data-tt-mp-attention="' + p.id + '" value="' + escapeHtml(text) + '" placeholder="…">'
                + '</td>'
                + '<td class="tt-mp-col-spec ' + (spec ? 'tt-mp-on' : '') + '" data-tt-mp-spec="' + p.id + '" role="button" tabindex="0" aria-pressed="' + (spec ? 'true' : 'false') + '">!</td>'
                + '<td class="tt-mp-col-cam '  + (cam  ? 'tt-mp-on' : '') + '" data-tt-mp-cam="'  + p.id + '" role="button" tabindex="0" aria-pressed="' + (cam ? 'true' : 'false') + '">'
                +   '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 6h11a2 2 0 0 1 2 2v2.2l4-2.4v12.4l-4-2.4V18a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2z"/></svg>'
                + '</td>'
                + '</tr>';
        }).join('');

        // #3297 — pad to FOCUS_LINES ruled rows, for the PDF only.
        //
        // On screen this block is one row per available player. On paper the
        // count is fixed: it is the block the coach writes in during the
        // warm-up, so the room is reserved rather than earned. The filler
        // rows carry no inputs and no player id — they are writing lines,
        // and `.tt-image-pdf-capture` is the only scope that reveals them.
        //
        // It lives here rather than in PHP because this function replaces
        // the tbody wholesale on every availability change; server-rendered
        // filler would be wiped the first time a coach ticked a player.
        if (avail.length < FOCUS_LINES) {
            var filler = '';
            for (var i = avail.length; i < FOCUS_LINES; i++) {
                filler += '<tr class="tt-mp-dps-rule" aria-hidden="true">'
                    + '<td class="tt-mp-col-name"></td>'
                    + '<td class="tt-mp-col-text"></td>'
                    + '<td class="tt-mp-col-spec"></td>'
                    + '<td class="tt-mp-col-cam"></td>'
                    + '</tr>';
            }
            tbody.insertAdjacentHTML('beforeend', filler);
        }

        // Wire events for the just-rendered rows.
        $$('input[data-tt-mp-attention]', tbody).forEach(function (inp) {
            inp.addEventListener('input', function (e) {
                state.attention[String(e.target.getAttribute('data-tt-mp-attention'))] = e.target.value;
                markDirty();
                scheduleSave(500);
            });
        });
        $$('td[data-tt-mp-spec]', tbody).forEach(function (td) {
            td.addEventListener('click', function () { togglePidFlag(td, 'spec'); });
            td.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); togglePidFlag(td, 'spec'); }
            });
        });
        $$('td[data-tt-mp-cam]', tbody).forEach(function (td) {
            td.addEventListener('click', function () { togglePidFlag(td, 'cam'); });
            td.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); togglePidFlag(td, 'cam'); }
            });
        });
    }

    function togglePidFlag(td, which) {
        var attr = which === 'spec' ? 'data-tt-mp-spec' : 'data-tt-mp-cam';
        var bucket = which === 'spec' ? state.specific : state.analyst;
        var pid = String(td.getAttribute(attr));
        bucket[pid] = !bucket[pid];
        td.classList.toggle('tt-mp-on', !!bucket[pid]);
        td.setAttribute('aria-pressed', bucket[pid] ? 'true' : 'false');
        markDirty();
        saveAll();
    }

    function renderRoles() {
        var ul = $('[data-tt-mp-roles]');
        if (!ul) return;
        ul.innerHTML = state.roleDefs.map(function (r) {
            var pid = state.rolesAssigned[r.key];
            var p = pid ? playerById(pid) : null;
            var filled = !!p;
            var inner = filled
                ? '<span class="tt-mp-sp-name">' + escapeHtml(p.name) + '</span>'
                  + '<button type="button" class="tt-mp-sp-clear" data-tt-mp-clear-role="' + escapeHtml(r.key) + '" aria-label="' + escapeHtml(i18n('clear', 'Clear')) + '" title="' + escapeHtml(i18n('clear', 'Clear')) + '">×</button>'
                : '<span class="tt-mp-sp-name">' + escapeHtml(i18n('pick_player', '— Pick player —')) + '</span>';
            return '<li class="tt-mp-sp-row" data-tt-mp-role="' + escapeHtml(r.key) + '" data-pid="' + (pid || 0) + '" role="button" tabindex="0">'
                + '<span class="tt-mp-sp-label">' + escapeHtml(r.label) + '</span>'
                + '<span class="tt-mp-sp-pick ' + (filled ? 'tt-mp-filled' : '') + '">' + inner + '</span>'
                + '</li>';
        }).join('');

        $$('li[data-tt-mp-role]', ul).forEach(function (li) {
            li.addEventListener('click', function (e) {
                var clearBtn = e.target.closest && e.target.closest('[data-tt-mp-clear-role]');
                if (clearBtn) {
                    e.stopPropagation();
                    var key = clearBtn.getAttribute('data-tt-mp-clear-role');
                    delete state.rolesAssigned[key];
                    renderRoles();
                    clearRoleRemote(key);
                    return;
                }
                e.stopPropagation();
                openPicker(li, { kind: 'role', role: li.getAttribute('data-tt-mp-role') });
            });
            li.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    openPicker(li, { kind: 'role', role: li.getAttribute('data-tt-mp-role') });
                }
            });
        });
    }

    function renderFoot() {
        var c1 = state.lineup['1'].size;
        var c2 = state.lineup['2'].size;
        setFoot('count1', c1 + ' / 11');
        setFoot('count2', c2 + ' / 11');
        var m1 = c1 * state.halfLength;
        var m2 = c2 * state.halfLength;
        setFoot('min1', m1);
        setFoot('min2', m2);
        setFoot('mintot', m1 + m2);
        setFoot('hl1', state.halfLength);
        setFoot('hl2', state.halfLength);
        setFoot('hltot', state.halfLength * 2);
    }
    function setFoot(name, val) {
        var cells = $$('[data-tt-mp-foot="' + name + '"]');
        cells.forEach(function (c) { c.textContent = String(val); });
    }

    // ---------------------------------------------------------------------
    // Slot assignment
    // ---------------------------------------------------------------------

    function assignSlot(half, slot, pid) {
        var map = state.lineup[String(half)];
        if (!map) return;
        // displace previous occupant of this slot
        map.delete(slot);
        if (pid) {
            // one player per half max
            map.forEach(function (otherPid, s) {
                if (otherPid === pid) map.delete(s);
            });
            map.set(slot, pid);
        }
        markDirty();
        renderRoster();
        renderPitches();
        renderFoot();
        saveAll();
    }

    // ---------------------------------------------------------------------
    // Picker (slot + role)
    // ---------------------------------------------------------------------

    var pickerCtx = null;

    function openPicker(anchorEl, ctx) {
        pickerCtx = ctx;
        var picker = $('[data-tt-mp-picker]');
        var backdrop = $('[data-tt-mp-picker-backdrop]');

        var title;
        var currentPid;
        if (ctx.kind === 'slot') {
            title = format(i18n('slot_label', 'Slot %1$s — %2$s half'), [ctx.slot, ctx.half === 1 ? i18n('half_1', '1st') : i18n('half_2', '2nd')]);
            currentPid = state.lineup[String(ctx.half)].get(ctx.slot) || null;
        } else {
            var def = (state.roleDefs || []).find(function (d) { return d.key === ctx.role; });
            title = def ? def.label : ctx.role;
            currentPid = state.rolesAssigned[ctx.role] || null;
        }

        picker.innerHTML =
            '<div class="tt-mp-picker-head">' + escapeHtml(title) + '</div>' +
            '<input type="text" class="tt-mp-picker-search" placeholder="' + escapeHtml(i18n('search', 'Search player…')) + '">' +
            '<div class="tt-mp-picker-list"></div>' +
            (currentPid ? '<div class="tt-mp-picker-foot" data-tt-mp-picker-clear>' + escapeHtml(i18n('clear', 'Clear')) + '</div>' : '');

        renderPickerList('');
        var searchInp = picker.querySelector('.tt-mp-picker-search');
        searchInp.addEventListener('input', function (e) { renderPickerList(e.target.value); });
        var clearFoot = picker.querySelector('[data-tt-mp-picker-clear]');
        if (clearFoot) clearFoot.addEventListener('click', function () { assignFromPicker(null); closePicker(); });

        picker.hidden = false;
        backdrop.hidden = false;

        // Position near the anchor with viewport clamping.
        var r = anchorEl.getBoundingClientRect();
        var pr = picker.getBoundingClientRect();
        var left = r.right + window.scrollX + 8;
        var top = r.top + window.scrollY;
        if (left + pr.width > window.innerWidth - 8) left = r.left + window.scrollX - pr.width - 8;
        if (left < 8) left = 8;
        if (top + pr.height > window.innerHeight + window.scrollY - 8) top = window.innerHeight + window.scrollY - pr.height - 8;
        if (top < window.scrollY + 8) top = window.scrollY + 8;
        picker.style.left = left + 'px';
        picker.style.top = top + 'px';

        setTimeout(function () { searchInp.focus(); }, 30);
    }

    function renderPickerList(q) {
        var picker = $('[data-tt-mp-picker]');
        var list = picker.querySelector('.tt-mp-picker-list');
        q = (q || '').trim().toLowerCase();
        var matches = availablePlayers().filter(function (p) {
            return !q || p.name.toLowerCase().indexOf(q) >= 0;
        });
        if (!matches.length) {
            list.innerHTML = '<div style="padding:0.5rem; color:var(--tt-mp-ink-soft); font-style:italic;">'
                + escapeHtml(i18n('no_players', 'No available players found.'))
                + '</div>';
            return;
        }
        list.innerHTML = matches.map(function (p) {
            var placed = placedCount(p.id);
            var initial = (p.name || '?').charAt(0).toUpperCase();
            var meta = pickerCtx && pickerCtx.kind === 'slot'
                ? (placed ? '<span class="tt-mp-picker-meta">×' + placed + ' ' + escapeHtml(i18n('on_pitch', 'on pitch')) + '</span>' : '')
                : '';
            return '<div class="tt-mp-picker-row ' + (placed ? 'tt-mp-placed' : '') + '" data-pid="' + p.id + '" role="button" tabindex="0">'
                + '<span class="tt-mp-av">' + escapeHtml(initial) + '</span>'
                + '<span>' + escapeHtml(p.name) + '</span>'
                + meta
                + '</div>';
        }).join('');
        $$('.tt-mp-picker-row', list).forEach(function (row) {
            row.addEventListener('click', function () {
                var pid = parseInt(row.getAttribute('data-pid'), 10);
                assignFromPicker(pid);
                closePicker();
            });
            row.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    var pid = parseInt(row.getAttribute('data-pid'), 10);
                    assignFromPicker(pid);
                    closePicker();
                }
            });
        });
    }

    function assignFromPicker(pid) {
        if (!pickerCtx) return;
        if (pickerCtx.kind === 'slot') {
            assignSlot(pickerCtx.half, pickerCtx.slot, pid);
        } else if (pickerCtx.kind === 'role') {
            if (pid === null || pid === 0) {
                delete state.rolesAssigned[pickerCtx.role];
                clearRoleRemote(pickerCtx.role);
            } else {
                state.rolesAssigned[pickerCtx.role] = pid;
                saveRole(pickerCtx.role, pid);
            }
            renderRoles();
        }
    }

    function closePicker() {
        var picker = $('[data-tt-mp-picker]');
        var backdrop = $('[data-tt-mp-picker-backdrop]');
        if (picker) picker.hidden = true;
        if (backdrop) backdrop.hidden = true;
        pickerCtx = null;
    }

    document.addEventListener('click', function (e) {
        if (!pickerCtx) return;
        var picker = $('[data-tt-mp-picker]');
        if (picker && picker.contains(e.target)) return;
        if (e.target.closest && e.target.closest('.tt-mp-slot')) return;
        if (e.target.closest && e.target.closest('[data-tt-mp-role]')) return;
        closePicker();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            if (pickerCtx) { closePicker(); return; }
            if (!$('[data-tt-mp-drawer]').classList.contains('tt-mp-open') === false) {
                // Drawer is open
                closeDrawer();
            }
        }
    });
    var pickerBackdrop = $('[data-tt-mp-picker-backdrop]');
    if (pickerBackdrop) pickerBackdrop.addEventListener('click', closePicker);

    // ---------------------------------------------------------------------
    // Print (landscape A4) — #998. In-place window.print(); the print
    // CSS in frontend-match-prep.css drops the dashboard chrome and
    // forces the 3-column grid onto the paper. Replaces the v4.5.0 PDF
    // anchor that routed to ?tt_view=exports (two clicks + a context
    // switch); browsers' own "Save as PDF" inside the print dialog
    // covers the file-output case for free.
    // ---------------------------------------------------------------------

    // #1031 — Print became a target="_blank" anchor pointing at the
    // dedicated print route (?tt_match_prep_print=1&activity_id=N) so
    // the WP admin bar + theme chrome stay off paper. No JS hookup
    // needed; the previous window.print() handler was the symptom of
    // the bug it tried to work around.

    // ---------------------------------------------------------------------
    // Copy 1e → 2e
    // ---------------------------------------------------------------------

    var copyBtn = $('[data-tt-mp-copy-half]');
    if (copyBtn) {
        copyBtn.addEventListener('click', function () {
            state.lineup['2'].clear();
            state.lineup['1'].forEach(function (pid, slot) { state.lineup['2'].set(slot, pid); });
            markDirty();
            renderAll();
            saveAll();
        });
    }

    // ---------------------------------------------------------------------
    // Toolbar inputs
    // ---------------------------------------------------------------------

    var halflenInp = $('[data-tt-mp-halflen]');
    if (halflenInp) {
        halflenInp.addEventListener('input', function (e) {
            var v = parseInt(e.target.value, 10);
            if (!isNaN(v) && v > 0) {
                state.halfLength = Math.min(60, v);
                renderRoster();
                renderFoot();
                markDirty();
                scheduleSave(500);
            }
        });
    }

    var formationSel = $('[data-tt-mp-formation]');
    if (formationSel) {
        formationSel.addEventListener('change', function (e) {
            var opt = e.target.selectedOptions && e.target.selectedOptions[0];
            var shape = opt ? (opt.getAttribute('data-shape') || '') : '';
            // #2099 — track the picked template so a template-specific layout
            // (e.g. the 3-4-3 diamond) is used instead of the shape default.
            state.formationTemplateId = parseInt(e.target.value, 10) || 0;
            if (shape) {
                state.formationShape = shape;
                root.setAttribute('data-formation-shape', shape);
                // #2098 — keep the Formation KPI tile in sync with the picked
                // shape; without this it kept showing the server-rendered value.
                var kpiVal = document.querySelector('[data-tt-mp-formation-kpi] .tt-kpi__val');
                if (kpiVal) kpiVal.textContent = shape;
                renderPitches();
            }
            markDirty();
            saveAll();
        });
    }

    // Tactical-goals inputs — debounced full save.
    $$('[data-tt-mp-goal]').forEach(function (inp) {
        inp.addEventListener('input', function () {
            markDirty();
            scheduleSave(500);
        });
    });

    // ---------------------------------------------------------------------
    // Availability drawer
    // ---------------------------------------------------------------------

    function openDrawer() {
        renderDrawer();
        var d = $('[data-tt-mp-drawer]');
        var b = $('[data-tt-mp-drawer-backdrop]');
        d.classList.add('tt-mp-open');
        d.setAttribute('aria-hidden', 'false');
        b.hidden = false;
    }
    function closeDrawer() {
        var d = $('[data-tt-mp-drawer]');
        var b = $('[data-tt-mp-drawer-backdrop]');
        d.classList.remove('tt-mp-open');
        d.setAttribute('aria-hidden', 'true');
        b.hidden = true;
        // Persist whatever the drawer set.
        scheduleSavingAvailability();
    }

    function scheduleSavingAvailability() {
        // Full save now (availability rides in the same PUT).
        var payload = buildFullPayload();
        payload.availability = {};
        Object.keys(state.availability).forEach(function (pid) {
            var entry = state.availability[pid] || {};
            // Server normalises sub into reason for the legacy availability
            // row; the substatus is held in state.availability[pid].sub so
            // the drawer can re-render after a reload doesn't matter here —
            // pilot uses one drawer session per page load.
            payload.availability[pid] = {
                status: entry.status === 'Absent' ? 'Absent' : 'Present',
                reason: String(entry.reason || (entry.sub || ''))
            };
        });
        // The availability drawer writes the full record with an extra
        // `availability` block, so it is a send() rather than the debounced
        // serialise — but it shows the same status line and queues behind
        // any save already in the air.
        saver.send({ method: 'PUT', url: baseUrl + state.activityId, body: payload });
    }

    function renderDrawer() {
        var body = $('[data-tt-mp-drawer-body]');
        if (!body) return;
        body.innerHTML = state.players.map(function (p) {
            var pidStr = String(p.id);
            var a = state.availability[pidStr] || { status: '', sub: '', reason: '' };
            var isPresent = a.status === 'Present';
            var isAbsent = a.status === 'Absent';
            var sub = a.sub || '';
            return '<div class="tt-mp-drawer-row" data-pid="' + p.id + '">'
                + '<div class="tt-mp-drawer-name">' + escapeHtml(p.name) + '</div>'
                + '<div class="tt-mp-chips">'
                +   '<button type="button" class="tt-mp-chip ' + (isPresent ? 'tt-mp-on' : '') + '" data-set="Present">' + escapeHtml(i18n('present', 'Present')) + '</button>'
                +   '<button type="button" class="tt-mp-chip tt-mp-chip-excused ' + (isAbsent && sub === 'Excused' ? 'tt-mp-on' : '') + '" data-set="Absent" data-sub="Excused">' + escapeHtml(i18n('absent_excused', 'Absent (excused)')) + '</button>'
                +   '<button type="button" class="tt-mp-chip tt-mp-chip-injured ' + (isAbsent && sub === 'Injured' ? 'tt-mp-on' : '') + '" data-set="Absent" data-sub="Injured">' + escapeHtml(i18n('absent_injured', 'Injured')) + '</button>'
                + '</div>'
                + (isAbsent ? '<div class="tt-mp-drawer-reason"><input type="text" data-tt-mp-reason="' + p.id + '" value="' + escapeHtml(a.reason || '') + '" placeholder="' + escapeHtml(i18n('reason', 'Reason (optional)…')) + '"></div>' : '')
                + '</div>';
        }).join('');
        $$('.tt-mp-chip', body).forEach(function (c) {
            c.addEventListener('click', function () {
                var row = c.closest('.tt-mp-drawer-row');
                var pid = String(row.getAttribute('data-pid'));
                var set = c.getAttribute('data-set');
                var sub = c.getAttribute('data-sub') || '';
                var cur = state.availability[pid] || {};
                if (cur.status === set && (cur.sub || '') === sub) {
                    // Toggle off — return to unset
                    delete state.availability[pid];
                } else {
                    state.availability[pid] = { status: set, sub: sub, reason: cur.reason || '' };
                }
                // Absent → pull out of lineup + roles
                if (set === 'Absent') {
                    [1, 2].forEach(function (half) {
                        state.lineup[String(half)].forEach(function (p2, s) {
                            if (p2 === parseInt(pid, 10)) state.lineup[String(half)].delete(s);
                        });
                    });
                    Object.keys(state.rolesAssigned).forEach(function (k) {
                        if (state.rolesAssigned[k] === parseInt(pid, 10)) delete state.rolesAssigned[k];
                    });
                }
                markDirty();
                renderDrawer();
                renderAll();
            });
        });
        $$('input[data-tt-mp-reason]', body).forEach(function (inp) {
            inp.addEventListener('input', function (e) {
                var pid = String(e.target.getAttribute('data-tt-mp-reason'));
                var cur = state.availability[pid] || {};
                state.availability[pid] = Object.assign({}, cur, { reason: e.target.value });
                markDirty();
            });
        });
    }

    // Wire drawer triggers.
    function bindDrawerTriggers() {
        $$('[data-tt-mp-open-availability]').forEach(function (b) {
            b.addEventListener('click', function (e) {
                e.preventDefault();
                openDrawer();
            });
        });
    }
    bindDrawerTriggers();
    document.addEventListener('click', function (e) {
        // Catch dynamically-inserted "Open availability" link in the
        // empty-roster cell.
        var t = e.target;
        if (t && t.matches && t.matches('[data-tt-mp-open-availability]')) {
            e.preventDefault();
            openDrawer();
        }
    });

    var drawerClose = $('[data-tt-mp-drawer-close]');
    if (drawerClose) drawerClose.addEventListener('click', closeDrawer);
    var drawerDone = $('[data-tt-mp-drawer-done]');
    if (drawerDone) drawerDone.addEventListener('click', closeDrawer);
    var drawerBackdrop = $('[data-tt-mp-drawer-backdrop]');
    if (drawerBackdrop) drawerBackdrop.addEventListener('click', closeDrawer);
    var markAll = $('[data-tt-mp-mark-all-present]');
    if (markAll) {
        markAll.addEventListener('click', function () {
            state.players.forEach(function (p) {
                state.availability[String(p.id)] = { status: 'Present', sub: '', reason: '' };
            });
            markDirty();
            renderDrawer();
            renderAll();
        });
    }

    // ---------------------------------------------------------------------
    // #1475 — print / team-sheet export. Both the "Print / export PDF"
    // and "Print team sheet" toolbar links open the dedicated standalone
    // print route in a new tab (cookie-authed, cap-gated server-side).
    // The image-capture → A4-landscape-PDF action lives on that page
    // (tt-image-pdf.js), so no JS hookup is needed here. Replaces the
    // #1476 fetch-blob team-sheet download.
    // ---------------------------------------------------------------------

    // ---------------------------------------------------------------------
    // Initial render
    // ---------------------------------------------------------------------

    renderAll();

    // #3005 — the record as it arrived, so the very first edit of a session
    // has something to be undone back to. Seeded after the initial render
    // because the goal inputs are read out of the DOM.
    saver.seed(buildFullPayload());
})();
