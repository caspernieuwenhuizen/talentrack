/*
 * frontend-pdp-prep-questions.js (#3305, epic #3301)
 *
 * The PDP preparation question sets, one per conversation template.
 * Hydrates the server-rendered scaffolding from the JSON payload and
 * talks to /pdp-prep-questions for every change — add, edit, reorder,
 * remove. No build step, no framework; vanilla per CLAUDE.md § 2.
 *
 * Each row commits on its own Save. This is a settings sub-form, so
 * there is no page-level Save and nothing to cancel out of (CLAUDE.md
 * § 6 exemption (a)); the per-row editor's Cancel simply closes it.
 */
(function () {
    'use strict';

    var root = document.querySelector('[data-tt-prep-questions]');
    if (!root || typeof TT_PDP_PREP === 'undefined') return;

    var i18n = TT_PDP_PREP.i18n || {};
    var msgEl = root.querySelector('[data-tt-prep-msg]');
    var state = {};
    var types = [];

    var payloadEl = document.querySelector('[data-tt-prep-payload]');
    if (payloadEl) {
        try {
            var parsed = JSON.parse(payloadEl.textContent || '{}');
            state = parsed.templates || {};
            types = parsed.types || [];
        } catch (e) {
            state = {};
        }
    }

    var TYPE_LABELS = {
        textarea: i18n.type_textarea,
        text: i18n.type_text,
        select: i18n.type_select,
        multi_select: i18n.type_multi,
        checkbox: i18n.type_checkbox,
        number: i18n.type_number,
        date: i18n.type_date
    };

    function say(text, isError) {
        if (!msgEl) return;
        msgEl.textContent = text || '';
        msgEl.classList.toggle('is-error', !!isError);
    }

    function api(path, method, body) {
        return fetch(TT_PDP_PREP.rest_root + path, {
            method: method,
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': TT_PDP_PREP.nonce
            },
            body: body ? JSON.stringify(body) : undefined
        }).then(function (res) {
            return res.json().then(function (json) {
                if (!res.ok) throw new Error((json && json.message) || i18n.save_failed);
                return json.data || json;
            });
        });
    }

    function el(tag, cls, text) {
        var node = document.createElement(tag);
        if (cls) node.className = cls;
        if (text !== undefined && text !== null) node.textContent = text;
        return node;
    }

    function field(labelText, control) {
        var wrap = el('div', 'tt-field');
        var label = el('label', 'tt-field-label', labelText);
        var id = 'tt-prep-' + Math.random().toString(36).slice(2, 9);
        label.setAttribute('for', id);
        control.id = id;
        control.classList.add('tt-input');
        wrap.appendChild(label);
        wrap.appendChild(control);
        return wrap;
    }

    function typeSelect(current) {
        var select = document.createElement('select');
        types.forEach(function (type) {
            var option = document.createElement('option');
            option.value = type;
            option.textContent = TYPE_LABELS[type] || type;
            if (type === current) option.selected = true;
            select.appendChild(option);
        });
        return select;
    }

    function buildEditor(templateKey, question, onDone) {
        var form = el('form', 'tt-pdp-prep-config__editor');
        form.setAttribute('novalidate', 'novalidate');

        var labelInput = document.createElement('input');
        labelInput.type = 'text';
        labelInput.value = question ? question.label : '';
        labelInput.required = true;

        var helpInput = document.createElement('textarea');
        helpInput.rows = 2;
        helpInput.value = question ? (question.help_text || '') : '';

        var typeInput = typeSelect(question ? question.field_type : 'textarea');

        var optionsInput = document.createElement('textarea');
        optionsInput.rows = 3;
        optionsInput.value = question && question.options ? question.options.join('\n') : '';

        var optionsField = field(i18n.options, optionsInput);

        function syncOptionsVisibility() {
            var needsOptions = typeInput.value === 'select' || typeInput.value === 'multi_select';
            optionsField.hidden = !needsOptions;
        }
        typeInput.addEventListener('change', syncOptionsVisibility);

        var requiredWrap = el('label', 'tt-checkbox');
        var requiredInput = document.createElement('input');
        requiredInput.type = 'checkbox';
        requiredInput.checked = !!(question && question.required);
        requiredWrap.appendChild(requiredInput);
        requiredWrap.appendChild(el('span', null, i18n.required));

        form.appendChild(field(i18n.label, labelInput));
        form.appendChild(field(i18n.help_text, helpInput));
        form.appendChild(field(i18n.field_type, typeInput));
        form.appendChild(optionsField);
        form.appendChild(requiredWrap);
        syncOptionsVisibility();

        var actions = el('div', 'tt-form-actions');
        var cancel = el('button', 'tt-btn tt-btn-secondary', i18n.cancel);
        cancel.type = 'button';
        var save = el('button', 'tt-btn tt-btn-primary', i18n.save);
        save.type = 'submit';
        actions.appendChild(cancel);
        actions.appendChild(save);
        form.appendChild(actions);

        cancel.addEventListener('click', function () {
            onDone(null);
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            if (!labelInput.value.trim()) {
                labelInput.focus();
                return;
            }
            say(i18n.saving);
            save.disabled = true;

            var body = {
                label: labelInput.value.trim(),
                help_text: helpInput.value,
                field_type: typeInput.value,
                required: requiredInput.checked ? 1 : 0,
                options: optionsInput.value
                    .split('\n')
                    .map(function (line) { return line.trim(); })
                    .filter(function (line) { return line !== ''; })
            };

            var request = question
                ? api('/pdp-prep-questions/' + question.id, 'PATCH', body)
                : api('/pdp-prep-questions', 'POST', Object.assign({ template_key: templateKey }, body));

            request.then(function (data) {
                say(data && data.versioned ? i18n.versioned : i18n.saved);
                onDone(data && data.question ? data.question : null, question ? question.id : 0);
            }).catch(function (err) {
                say(err.message || i18n.save_failed, true);
                save.disabled = false;
            });
        });

        return form;
    }

    function buildRow(templateKey, question, index, total) {
        var li = el('li', 'tt-pdp-prep-config__item');

        var head = el('div', 'tt-pdp-prep-config__item-head');
        head.appendChild(el('span', 'tt-pdp-prep-config__item-label', question.label));
        var meta = TYPE_LABELS[question.field_type] || question.field_type;
        if (question.required) meta += ' · ' + i18n.required;
        head.appendChild(el('span', 'tt-pdp-prep-config__item-meta', meta));
        if (question.help_text) {
            head.appendChild(el('span', 'tt-pdp-prep-config__item-help', question.help_text));
        }
        li.appendChild(head);

        var actions = el('div', 'tt-pdp-prep-config__item-actions');

        var up = el('button', 'tt-btn tt-btn-secondary', '↑');
        up.type = 'button';
        up.setAttribute('aria-label', i18n.move_up);
        up.disabled = index === 0;

        var down = el('button', 'tt-btn tt-btn-secondary', '↓');
        down.type = 'button';
        down.setAttribute('aria-label', i18n.move_down);
        down.disabled = index === total - 1;

        var edit = el('button', 'tt-btn tt-btn-secondary', i18n.edit);
        edit.type = 'button';

        var remove = el('button', 'tt-btn tt-btn-secondary', i18n.remove);
        remove.type = 'button';

        actions.appendChild(up);
        actions.appendChild(down);
        actions.appendChild(edit);
        actions.appendChild(remove);
        li.appendChild(actions);

        up.addEventListener('click', function () { move(templateKey, index, -1); });
        down.addEventListener('click', function () { move(templateKey, index, 1); });

        edit.addEventListener('click', function () {
            var editor = buildEditor(templateKey, question, function (updated, previousId) {
                if (updated) replaceQuestion(templateKey, previousId, updated);
                render(templateKey);
            });
            li.innerHTML = '';
            li.appendChild(editor);
            editor.querySelector('input, textarea, select').focus();
        });

        remove.addEventListener('click', function () {
            if (!window.confirm(i18n.confirm_remove)) return;
            say(i18n.saving);
            api('/pdp-prep-questions/' + question.id, 'DELETE').then(function () {
                state[templateKey] = state[templateKey].filter(function (q) { return q.id !== question.id; });
                say(i18n.saved);
                render(templateKey);
            }).catch(function (err) {
                say(err.message || i18n.save_failed, true);
            });
        });

        return li;
    }

    function replaceQuestion(templateKey, previousId, updated) {
        var list = state[templateKey] || [];
        var at = -1;
        list.forEach(function (q, i) { if (q.id === previousId) at = i; });
        if (at === -1) list.push(updated);
        else list[at] = updated;
        state[templateKey] = list;
    }

    function move(templateKey, index, delta) {
        var list = state[templateKey] || [];
        var target = index + delta;
        if (target < 0 || target >= list.length) return;

        var moved = list.splice(index, 1)[0];
        list.splice(target, 0, moved);
        state[templateKey] = list;
        render(templateKey);

        say(i18n.saving);
        api('/pdp-prep-questions/order', 'PUT', {
            template_key: templateKey,
            ids: list.map(function (q) { return q.id; })
        }).then(function () {
            say(i18n.saved);
        }).catch(function (err) {
            say(err.message || i18n.save_failed, true);
        });
    }

    function render(templateKey) {
        var section = root.querySelector('[data-tt-prep-template="' + templateKey + '"]');
        if (!section) return;
        var list = section.querySelector('[data-tt-prep-list]');
        var questions = state[templateKey] || [];

        list.innerHTML = '';
        if (!questions.length) {
            var empty = el('li', 'tt-pdp-prep-config__empty', i18n.empty);
            list.appendChild(empty);
            return;
        }
        questions.forEach(function (question, index) {
            list.appendChild(buildRow(templateKey, question, index, questions.length));
        });
    }

    root.querySelectorAll('[data-tt-prep-template]').forEach(function (section) {
        var templateKey = section.getAttribute('data-tt-prep-template');
        render(templateKey);

        section.querySelector('[data-tt-prep-add]').addEventListener('click', function () {
            var holder = el('div', 'tt-pdp-prep-config__new');
            var editor = buildEditor(templateKey, null, function (created) {
                holder.remove();
                if (created) {
                    state[templateKey] = (state[templateKey] || []).concat([created]);
                    render(templateKey);
                }
            });
            holder.appendChild(editor);
            section.querySelector('.tt-pdp-prep-config__actions').before(holder);
            editor.querySelector('input, textarea, select').focus();
        });
    });
})();
