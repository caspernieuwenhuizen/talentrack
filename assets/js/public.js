/**
 * TalentTrack — frontend dashboard script.
 *
 * #0019 Sprint 1 session 2: rewritten as vanilla JS + fetch() against
 * the REST API. jQuery is no longer a dependency. Forms declare their
 * REST target via `data-rest-path` (path relative to
 * `/wp-json/talenttrack/v1/`) plus optional `data-rest-method`
 * (default POST). Inline goal status / delete handlers hit
 * `/goals/{id}/status` (PATCH) and `/goals/{id}` (DELETE).
 *
 * `data-redirect-after-save` decides what happens once a save succeeds:
 *
 *   list / list:<slug>     — back to the list view
 *   detail:<entity>:<id>   — to that record's detail page
 *   reload                 — re-render this URL
 *   stay                   — nothing moves; the form keeps what was typed
 *   <a URL via data-redirect-after-save-url>
 *
 * With none of them the form is reset, which is right for an "add another"
 * form and wrong for one that edits a record in place — use `stay` there.
 */
(function(){
    'use strict';

    var i18n = (window.TT && TT.i18n) ? TT.i18n : {
        saving: 'Saving...',
        saved: 'Saved.',
        error_generic: 'Error.',
        network_error: 'Network error.',
        confirm_delete_goal: 'Delete this goal?',
        save_evaluation: 'Save Evaluation',
        save_activity: 'Save Activity',
        add_goal: 'Add Goal',
        save: 'Save'
    };

    /**
     * Turn a submitted <form> into a plain object, expanding bracketed
     * names (`ratings[12]=4.5`, `att[7][status]=Present`) into nested
     * objects. The REST controllers read these as sub-resources, matching
     * the shape the legacy admin-ajax handlers accepted.
     */
    function formToJSON(form) {
        var out = {};
        var fd = new FormData(form);
        fd.forEach(function(value, key) {
            // Skip the legacy hidden fields — REST uses headers + path.
            if (key === 'action' || key === 'nonce' || key === '_wpnonce') return;
            var match = key.match(/^([^\[]+)((?:\[[^\]]*\])*)$/);
            if (!match) { out[key] = value; return; }
            var base = match[1];
            var rest = match[2];
            if (!rest) { out[base] = value; return; }
            var keys = [];
            rest.replace(/\[([^\]]*)\]/g, function(_m, k) { keys.push(k); return ''; });
            // name[] case — push to array on the base key.
            if (keys.length === 1 && keys[0] === '') {
                if (!Array.isArray(out[base])) out[base] = [];
                out[base].push(value);
                return;
            }
            var cursor = out[base] = out[base] || {};
            for (var i = 0; i < keys.length - 1; i++) {
                var k = keys[i];
                cursor[k] = cursor[k] || {};
                cursor = cursor[k];
            }
            cursor[keys[keys.length - 1]] = value;
        });
        return out;
    }

    /**
     * #1361 — shared loading-state helper. Call start(container) before
     * a REST fetch and stop(container) when it settles: the container
     * gets aria-busy="true" (announced by AT) plus the .tt-is-loading
     * class CSS consumers hook their busy treatment onto. skeleton(n)
     * returns n pulse-placeholder blocks (aria-hidden — purely visual;
     * the aria-busy on the container carries the semantics). Exposed on
     * window.TT so the other bundles (frontend-list-table.js,
     * persona-dashboard.js) reuse one implementation.
     */
    var Loading = {
        start: function (el) {
            if (!el) return;
            el.setAttribute('aria-busy', 'true');
            el.classList.add('tt-is-loading');
        },
        stop: function (el) {
            if (!el) return;
            el.removeAttribute('aria-busy');
            el.classList.remove('tt-is-loading');
        },
        skeleton: function (count) {
            var out = '';
            for (var i = 0; i < (count || 3); i++) {
                out += '<span class="tt-skeleton" aria-hidden="true"></span>';
            }
            return out;
        }
    };
    window.TT = window.TT || {};
    window.TT.Loading = Loading;

    // #3007 — exposed because a surface that autosaves has to build the
    // same body this file's submit handler builds, without submitting.
    // Two implementations of the bracket expansion would be two ways for
    // the same form to reach the same endpoint with different shapes.
    window.TT.formToJSON = formToJSON;

    function restRequest(path, method, body) {
        var base = (window.TT && TT.rest_url) ? TT.rest_url : '/wp-json/talenttrack/v1/';
        var url = base.replace(/\/+$/, '/') + path.replace(/^\/+/, '');
        var headers = { 'Content-Type': 'application/json', 'Accept': 'application/json' };
        if (window.TT && TT.rest_nonce) headers['X-WP-Nonce'] = TT.rest_nonce;
        return fetch(url, {
            method: method,
            credentials: 'same-origin',
            headers: headers,
            body: body ? JSON.stringify(body) : undefined
        }).then(function(res) {
            return res.json().then(function(json) { return { status: res.status, ok: res.ok, json: json }; });
        });
    }

    function firstErrorMessage(json) {
        if (json && Array.isArray(json.errors) && json.errors.length > 0 && json.errors[0].message) {
            return json.errors[0].message;
        }
        if (json && json.message) return json.message;
        return i18n.error_generic;
    }

    function showMsg(form, type, text) {
        var el = form.querySelector('.tt-form-msg');
        if (!el) return;
        el.classList.remove('tt-success', 'tt-error');
        el.classList.add(type === 'success' ? 'tt-success' : 'tt-error');
        el.textContent = text;
        el.style.display = '';
    }

    function clearMsg(form) {
        var el = form.querySelector('.tt-form-msg');
        if (!el) return;
        el.classList.remove('tt-success', 'tt-error');
        el.textContent = '';
        el.style.display = 'none';
    }

    function defaultButtonLabel(formId) {
        switch (formId) {
            case 'tt-eval-form':    return i18n.save_evaluation;
            case 'tt-activity-form': return i18n.save_activity;
            case 'tt-goal-form':    return i18n.add_goal;
            default:                return i18n.save;
        }
    }

    /**
     * Drive a FormSaveButton through its idle/saving/saved/error states.
     * Falls back to the old behaviour (simple text swap) for plain
     * `<button type="submit">` buttons without the component class.
     */
    function setSaveBtnState(btn, state) {
        if (!btn) return;
        if (!btn.classList.contains('tt-save-btn')) {
            // Legacy button — just flip text + disabled.
            if (state === 'saving') { btn.disabled = true; btn.textContent = i18n.saving; }
            else { btn.disabled = false; btn.textContent = defaultButtonLabel(btn.form ? btn.form.id : ''); }
            return;
        }
        btn.setAttribute('data-state', state);
        btn.disabled = (state === 'saving');
        var labelEl = btn.querySelector('.tt-save-btn-label');
        if (!labelEl) return;
        var key = 'data-label-' + state;
        var label = btn.getAttribute(key);
        if (label) labelEl.textContent = label;
    }

    function on(selector, event, handler) {
        document.addEventListener(event, function(e) {
            var target = e.target.closest(selector);
            if (target) handler.call(target, e);
        });
    }

    // v4.20.66 (#1271) — when redirecting back to the team planner after
    // an activity save, jump the planner to the week that contains the
    // saved date. Without this, picking a date outside the captured
    // `week_start` makes the activity disappear from the planner grid
    // post-redirect (matches FrontendTeamPlannerView::resolveWeekStart).
    function adjustPlannerWeek(redirectUrl, form) {
        try {
            var url = new URL(redirectUrl, window.location.origin);
            if (url.searchParams.get('tt_view') !== 'team-planner') return redirectUrl;
            var dateInput = form.querySelector('input[name="session_date"]');
            var d = dateInput ? String(dateInput.value || '') : '';
            if (!/^\d{4}-\d{2}-\d{2}$/.test(d)) return redirectUrl;
            var dt = new Date(d + 'T00:00:00Z');
            if (isNaN(dt.getTime())) return redirectUrl;
            // JS getUTCDay(): 0=Sun..6=Sat. Snap back to Monday.
            var dow = dt.getUTCDay();
            var daysBack = (dow + 6) % 7;
            dt.setUTCDate(dt.getUTCDate() - daysBack);
            var monday = dt.toISOString().slice(0, 10);
            url.searchParams.set('week_start', monday);
            return url.toString();
        } catch (e) {
            return redirectUrl;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {

        // #3341 — the legacy `.tt-dashboard .tt-tab[data-tab]` switcher was
        // removed here along with `CoachDashboardView` and
        // `PlayerDashboardView`, the only two surfaces that emitted the
        // markup it bound to. Both had been unreachable for some time —
        // neither appeared in the dispatcher, and `PlayerDashboardView` had
        // no reference anywhere in `src/` at all.
        //
        // Worth knowing why it is called out rather than quietly deleted:
        // it rewrote `tt_view` with `history.replaceState`, which REPLACES
        // the current history entry instead of adding one. On a surface
        // with a goals tab that is an exact mechanism for #3048 ("back from
        // a goal screen lands on the domain's front page"), so two separate
        // investigations found it, believed it, and had to do the
        // reachability work to rule it out. It is gone now, so #3048's
        // cause is elsewhere.
        //
        // Record-scoped tabs come from `RecordSpine` (CLAUDE.md §5c).

        // REST form submission
        on('.tt-ajax-form', 'submit', function(e) {
            e.preventDefault();
            var form = this;
            // #2042 — optional confirm gate. A form carrying
            // data-confirm-msg (e.g. the PDP proxy-acknowledgement) asks
            // for confirmation before it submits. No attribute = no prompt,
            // so every existing form is unaffected.
            var confirmMsg = form.getAttribute('data-confirm-msg');
            if (confirmMsg && !window.confirm(confirmMsg)) { return; }
            var path = form.getAttribute('data-rest-path');
            if (!path) {
                showMsg(form, 'error', i18n.error_generic);
                return;
            }
            var method = (form.getAttribute('data-rest-method') || 'POST').toUpperCase();
            var btn = form.querySelector('button[type="submit"]');
            clearMsg(form);
            setSaveBtnState(btn, 'saving');

            restRequest(path, method, formToJSON(form)).then(function(res) {
                if (res.ok && res.json && res.json.success) {
                    showMsg(form, 'success', i18n.saved);
                    setSaveBtnState(btn, 'saved');
                    // Drafts module listens for this to clear its stored snapshot.
                    form.dispatchEvent(new CustomEvent('tt:form-saved', { bubbles: true }));
                    var redirect = form.getAttribute('data-redirect-after-save');
                    var redirectUrl = form.getAttribute('data-redirect-after-save-url');
                    // v3.110.188 (#795) — was checking equality against
                    // '1' and 'list' only; many forms emit compound values
                    // like 'list:scouting-visits' or 'detail:scouting-visit:42'
                    // which fell through to the form.reset() else-branch, so
                    // the saved record stayed visible on the page instead of
                    // returning the user to the list/detail. Match prefix
                    // instead of equality and handle each compound shape.
                    var redirectIsList   = (redirect === '1' || redirect === 'list' || (redirect && redirect.indexOf('list:') === 0));
                    var redirectIsDetail = (redirect && redirect.indexOf('detail:') === 0);
                    // #1478 — consistent success toast on every save,
                    // including forms with no `.tt-form-msg`. Queue it
                    // across a post-save redirect so it lands on the
                    // destination; show it in place when we stay put.
                    if (window.ttFlash) {
                        var willRedirect = redirectIsList || redirectIsDetail
                            || redirect === 'reload' || !!redirectUrl;
                        if (willRedirect) { window.ttFlash.queue('success', i18n.saved); }
                        else { window.ttFlash.add('success', i18n.saved); }
                    }
                    if (redirectIsList) {
                        setTimeout(function() {
                            try {
                                var url = new URL(window.location.href);
                                url.searchParams.delete('action');
                                url.searchParams.delete('id');
                                url.searchParams.delete('edit');
                                if (redirect && redirect.indexOf('list:') === 0) {
                                    var listSlug = redirect.substring(5);
                                    if (listSlug) url.searchParams.set('tt_view', listSlug);
                                }
                                window.location.href = url.toString();
                            } catch (e) {
                                window.location.href = window.location.pathname;
                            }
                        }, 1200);
                    } else if (redirectIsDetail) {
                        // 'detail:<entity>:<id>' — go to the record's detail
                        // page; mirrors PHP-side RecordLink::detailUrlFor.
                        setTimeout(function() {
                            try {
                                var parts = redirect.split(':');
                                var entity = parts[1] || '';
                                var rid    = parseInt(parts[2] || '0', 10);
                                var url = new URL(window.location.href);
                                url.searchParams.delete('action');
                                url.searchParams.delete('edit');
                                if (entity) url.searchParams.set('tt_view', entity);
                                if (rid > 0) url.searchParams.set('id', String(rid));
                                window.location.href = url.toString();
                            } catch (e) {
                                window.location.href = window.location.pathname;
                            }
                        }, 1200);
                    } else if (redirect === 'reload') {
                        // v3.92.5 — reload the current URL in place so the
                        // page re-renders with the saved state. Used by
                        // surfaces where the form lives inside a card on
                        // the same page as the data it edits (e.g. PDP
                        // conversation save/sign cards on the file view).
                        setTimeout(function() { window.location.reload(); }, 1200);
                    } else if (redirect === 'stay') {
                        // #2749 — stay exactly where we are: no reload, no
                        // reset, scroll position untouched. For long forms
                        // whose values ARE the server state after a save,
                        // where reloading reads as having been taken off
                        // the page and reset() would silently revert every
                        // field to its pre-save value.
                        setTimeout(function() { setSaveBtnState(btn, 'idle'); }, 1500);
                    } else if (redirectUrl) {
                        // v3.92.5 — explicit URL redirect. Used by the
                        // coach-side PDP conversation form to land back on
                        // the parent file view after save / sign.
                        // v4.20.66 (#1271) — pass through adjustPlannerWeek so
                        // a team-planner redirect lands on the week containing
                        // the saved activity, not the week the user was on.
                        setTimeout(function() { window.location.href = adjustPlannerWeek(redirectUrl, form); }, 1200);
                    } else {
                        form.reset();
                        setTimeout(function() { setSaveBtnState(btn, 'idle'); }, 1500);
                    }
                } else {
                    showMsg(form, 'error', firstErrorMessage(res.json));
                    setSaveBtnState(btn, 'error');
                    setTimeout(function() { setSaveBtnState(btn, 'idle'); }, 2500);
                }
            }).catch(function() {
                showMsg(form, 'error', i18n.network_error);
                setSaveBtnState(btn, 'error');
                setTimeout(function() { setSaveBtnState(btn, 'idle'); }, 2500);
            });
        });

        // Goal status inline update — PATCH /goals/{id}/status
        on('.tt-dashboard .tt-goal-status-select', 'change', function() {
            var id = this.getAttribute('data-goal-id');
            if (!id) return;
            restRequest('goals/' + encodeURIComponent(id) + '/status', 'PATCH', { status: this.value });
        });

        // Generic record delete — DELETE <data-rest-path>
        //
        // Any inline delete button can declare:
        //   class="tt-record-delete"
        //   data-rest-path="evaluations/{id}"      (or any v1 endpoint)
        //   data-confirm-msg="Delete this evaluation?"
        //   data-deleted-msg="Evaluation deleted."
        //
        // After a successful DELETE the closest `tr` or `[data-tt-row]`
        // ancestor fades out + is removed. If no such ancestor exists,
        // the page reloads. Sweep target: evaluations, activities, any
        // future record where the REST DELETE exists but UI didn't
        // expose it.
        on('.tt-record-delete', 'click', function(e) {
            e.preventDefault();
            var btn = this;
            var path = btn.getAttribute('data-rest-path');
            if (!path) return;
            var confirmMsg = btn.getAttribute('data-confirm-msg') || (i18n.confirm_delete_record || 'Delete this record?');
            var deletedMsg = btn.getAttribute('data-deleted-msg') || (i18n.deleted_record || 'Deleted.');
            // #1329 — `data-redirect-after-delete` lets a destructive
            // action navigate away on success (e.g. blueprint editor
            // deleting itself → return to the team's blueprint list).
            // Beats the default `reload()` which would 404 the just-
            // deleted record.
            var redirectAfter = btn.getAttribute('data-redirect-after-delete');
            var doDelete = function() {
                restRequest(path, 'DELETE', null).then(function(res) {
                    if (res.ok) {
                        if (window.ttFlash && window.ttFlash.addNear) {
                            window.ttFlash.addNear(btn, 'success', deletedMsg);
                        }
                        if (redirectAfter) {
                            window.location.href = redirectAfter;
                            return;
                        }
                        var row = btn.closest('[data-tt-row]') || btn.closest('tr') || btn.closest('li');
                        if (row) {
                            row.style.transition = 'opacity 0.25s';
                            row.style.opacity = '0';
                            setTimeout(function() {
                                if (row.parentNode) row.parentNode.removeChild(row);
                            }, 260);
                        } else {
                            window.location.reload();
                        }
                    } else if (res.json && res.json.errors && res.json.errors[0]) {
                        window.alert(res.json.errors[0].message || (i18n.error_generic || 'Error.'));
                    }
                });
            };
            if (typeof window.ttConfirm === 'function') {
                window.ttConfirm({
                    title:        i18n.confirm_delete_record_title || 'Delete?',
                    message:      confirmMsg,
                    confirmLabel: i18n.delete_label || 'Delete',
                    danger:       true
                }).then(function(ok) { if (ok) doDelete(); });
            } else if (window.confirm(confirmMsg)) {
                doDelete();
            }
        });

        // Goal delete — DELETE /goals/{id}
        on('.tt-dashboard .tt-goal-delete', 'click', function(e) {
            e.preventDefault();
            var btn = this;
            var id = btn.getAttribute('data-goal-id');
            if (!id) return;
            var doDelete = function() {
                restRequest('goals/' + encodeURIComponent(id), 'DELETE', null).then(function(res) {
                    if (res.ok && res.json && res.json.success) {
                        if (window.ttFlash && window.ttFlash.addNear) {
                            window.ttFlash.addNear(btn, 'success', i18n.deleted_goal || 'Goal deleted.');
                        }
                        var row = btn.closest('tr');
                        if (row) {
                            row.style.transition = 'opacity 0.25s';
                            row.style.opacity = '0';
                            setTimeout(function() { if (row.parentNode) row.parentNode.removeChild(row); }, 260);
                        }
                    }
                });
            };
            if (typeof window.ttConfirm === 'function') {
                window.ttConfirm({
                    title:        i18n.confirm_delete_goal_title || 'Delete goal?',
                    message:      i18n.confirm_delete_goal || 'Are you sure you want to delete this goal?',
                    confirmLabel: i18n.delete_label || 'Delete',
                    danger:       true
                }).then(function(ok) { if (ok) doDelete(); });
            } else if (window.confirm(i18n.confirm_delete_goal || 'Delete this goal?')) {
                doDelete();
            }
        });
    });

    // ─── Generic dropdown dependency — `data-tt-depends-on` ──────
    //
    // Any <select> can declare `data-tt-depends-on="other_field_name"`
    // plus either:
    //   - `data-tt-options-source="rest:path/with/{value}"` for a REST
    //     fetch keyed on the new parent value, or
    //   - `data-tt-options-map='{"a":[["1","X"]],"b":[["2","Y"]]}'` for
    //     a static client-side mapping (no REST call).
    //
    // When the parent <select> changes, the dependent select rebuilds
    // its options. The previously-selected value is preserved if it
    // still exists in the new option set; otherwise the dependent
    // resets to its placeholder option (or first option).
    //
    // Sweep target: any cascading dropdown (new-goal wizard's
    // link_type → link_id, position → preferred_action, team → player
    // picker, etc.). Each form opts in via the data-attribute pair.
    document.addEventListener('change', function(e) {
        var dependents = document.querySelectorAll('select[data-tt-depends-on]');
        if (!dependents.length) return;
        var changedName = e.target && e.target.name;
        if (!changedName) return;
        dependents.forEach(function(dep) {
            if (dep.dataset.ttDependsOn !== changedName) return;
            var prevValue = dep.value;
            // Static-map mode: instant client-side rebuild.
            if (dep.dataset.ttOptionsMap) {
                try {
                    var map = JSON.parse(dep.dataset.ttOptionsMap);
                    var rows = (map && map[e.target.value]) || [];
                    rebuildSelect(dep, rows, prevValue);
                } catch (err) { /* malformed JSON — leave alone */ }
                return;
            }
            // REST mode: fetch options keyed on the new parent value.
            var src = dep.dataset.ttOptionsSource || '';
            if (src.indexOf('rest:') !== 0) return;
            var path = src.substring(5).replace('{value}', encodeURIComponent(e.target.value));
            var base = (window.TT && TT.rest_url) ? TT.rest_url : '/wp-json/talenttrack/v1/';
            dep.disabled = true;
            fetch(base + path, {
                credentials: 'same-origin',
                headers: { 'X-WP-Nonce': (window.TT && TT.rest_nonce) || '' }
            })
            .then(function(r) { return r.ok ? r.json() : []; })
            .then(function(data) {
                // Accept either [["value","label"], …] or [{value, label}, …].
                var rows = Array.isArray(data) ? data.map(function(row) {
                    if (Array.isArray(row)) return row;
                    return [row.value, row.label != null ? row.label : row.value];
                }) : [];
                rebuildSelect(dep, rows, prevValue);
            })
            .catch(function() { /* leave existing options in place */ })
            .finally(function() { dep.disabled = false; });
        });
    });

    function rebuildSelect(select, rows, prevValue) {
        // Preserve a placeholder option (first option with empty value).
        var placeholder = select.options.length > 0 && select.options[0].value === ''
            ? select.options[0].cloneNode(true)
            : null;
        select.innerHTML = '';
        if (placeholder) select.appendChild(placeholder);
        var matched = false;
        rows.forEach(function(row) {
            var opt = document.createElement('option');
            opt.value = String(row[0]);
            opt.textContent = String(row[1] != null ? row[1] : row[0]);
            if (opt.value === prevValue) { opt.selected = true; matched = true; }
            select.appendChild(opt);
        });
        if (!matched && placeholder) placeholder.selected = true;
        // Fire change so any cascade further down the chain refreshes too.
        select.dispatchEvent(new Event('change', { bubbles: true }));
    }
})();
