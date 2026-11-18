/*!
 * Crumbz frontend. GPL-2.0-or-later.
 * Builds the banner, the settings dialog, and the draggable settings icon.
 * Writes the consent record cookie only after a visitor chooses.
 */
(function () {
    'use strict';

    var cfg = window.crumbzConfig || {};
    var T = cfg.text || {};
    // Only tags the plugin printed carry this per-page marker. Tags injected from elsewhere are left alone.
    var NONCE = cfg.nonce || '';
    function ours(node) {
        return !!NONCE && node.getAttribute('data-crumbz-nonce') === NONCE;
    }
    var CATS = ['preferences', 'statistics', 'marketing'];
    var THEMES = ['auto', 'light', 'dark'];
    var POS_COOKIE = 'crumbz_icon';
    var THEME_COOKIE = 'crumbz_theme';
    var pageTheme = null; // the theme chosen on this page only, when Preferences is refused
    var ICON_SVG = '<svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">' +
        '<path fill="currentColor" d="M12 2a10 10 0 1 0 10 10 3.5 3.5 0 0 1-4.2-4.2A3.5 3.5 0 0 1 14.5 4.9 4 4 0 0 1 12 2z"/>' +
        '<circle cx="8" cy="9" r="1.4" fill="currentColor"/>' +
        '<circle cx="12.5" cy="14.5" r="1.4" fill="currentColor"/>' +
        '<circle cx="8" cy="15.5" r="1" fill="currentColor"/></svg>';

    var root, banner, dialog, fab, gpcNote;
    var themeButtons = [];
    var catBoxes = {};
    var docBoxes = [];
    var drag = null;
    var lastDragEnd = 0;

    function t(key, fallback) {
        return (T[key] !== undefined && T[key] !== '') ? T[key] : (fallback || '');
    }

    function storageGet(key) {
        try { return window.sessionStorage.getItem(key); } catch (e) { return null; }
    }

    function storageSet(key, value) {
        try { window.sessionStorage.setItem(key, value); } catch (e) { /* storage unavailable */ }
    }

    function el(tag, attrs, kids) {
        var n = document.createElement(tag);
        if (attrs) {
            Object.keys(attrs).forEach(function (k) {
                var v = attrs[k];
                if (k === 'text') {
                    n.textContent = v;
                } else if (k === 'class') {
                    n.className = v;
                } else if (v === true) {
                    n.setAttribute(k, '');
                } else if (v !== false && v !== null && v !== undefined) {
                    n.setAttribute(k, String(v));
                }
            });
        }
        (kids || []).forEach(function (c) {
            if (c) { n.appendChild(c); }
        });
        return n;
    }

    function button(label, cls, onClick) {
        var b = el('button', { type: 'button', class: 'crumbz-btn ' + cls, text: label });
        b.addEventListener('click', onClick);
        return b;
    }

    function closeButton(onClick) {
        var b = el('button', { type: 'button', class: 'crumbz-x', 'aria-label': t('close', 'Close'), text: '×' });
        b.addEventListener('click', onClick);
        return b;
    }

    /* ---------- consent record ---------- */

    function readRecord() {
        var prefix = cfg.cookie + '=';
        var parts = document.cookie ? document.cookie.split('; ') : [];
        for (var i = 0; i < parts.length; i++) {
            if (parts[i].indexOf(prefix) === 0) {
                try {
                    var rec = JSON.parse(decodeURIComponent(parts[i].slice(prefix.length)));
                    if (rec && rec.v === 1 && rec.r === cfg.revision) {
                        return rec;
                    }
                } catch (e) { /* malformed: treat as no record */ }
                return null;
            }
        }
        return null;
    }

    function saveRecord(rec) {
        var secure = window.location.protocol === 'https:' ? '; Secure' : '';
        document.cookie = cfg.cookie + '=' + encodeURIComponent(JSON.stringify(rec)) +
            '; Max-Age=' + (cfg.lifetime * 86400) + '; Path=/; SameSite=Lax' + secure;
    }

    var record = readRecord();

    function marketingBlocked() {
        var gpc = !!cfg.gpc || navigator.globalPrivacyControl === true;
        return gpc && (cfg.jurisdictions || []).indexOf('ca') !== -1;
    }

    function allowed(cat) {
        if (cat === 'necessary') { return true; }
        if (cat === 'marketing' && marketingBlocked()) { return false; }
        return !!(record && record.cats && record.cats[cat]);
    }

    /** Re-creates blocked scripts that the visitor has now allowed. */
    function unblock() {
        var nodes = document.querySelectorAll('script[type="text/plain"][data-crumbz-category]');
        Array.prototype.forEach.call(nodes, function (old) {
            var cat = old.getAttribute('data-crumbz-category');
            if (!ours(old) || !allowed(cat)) { return; }
            old.parentNode.replaceChild(recreateScript(old), old);
        });
        unblockTemplates();
    }

    /**
     * Makes an executable copy of a blocked script. The original type (such as module) is restored,
     * and external scripts keep document order (async = false), so dependent scripts run in sequence.
     */
    function recreateScript(old) {
        var s = document.createElement('script');
        Array.prototype.forEach.call(old.attributes, function (a) {
            if (a.name !== 'type' && a.name !== 'data-crumbz-category' && a.name !== 'data-crumbz-type' && a.name !== 'data-crumbz-nonce') {
                s.setAttribute(a.name, a.value);
            }
        });
        var origType = old.getAttribute('data-crumbz-type');
        if (origType) { s.setAttribute('type', origType); }
        if (s.src) { s.async = false; }
        s.text = old.textContent;
        return s;
    }

    /** Moves allowed head snippets out of their inert <template> wrappers into the page. */
    function unblockTemplates() {
        var tpls = document.querySelectorAll('template[data-crumbz-category]');
        Array.prototype.forEach.call(tpls, function (tpl) {
            if (!ours(tpl) || !allowed(tpl.getAttribute('data-crumbz-category'))) { return; }
            var frag = tpl.content.cloneNode(true);
            Array.prototype.forEach.call(frag.querySelectorAll('script'), function (old) {
                old.parentNode.replaceChild(recreateScript(old), old);
            });
            (document.head || document.documentElement).appendChild(frag);
            tpl.parentNode.removeChild(tpl);
        });
    }

    function allDocs() {
        var o = {};
        (cfg.documents || []).forEach(function (d) { o[d.id] = d.version; });
        return o;
    }

    function catsAll(on) {
        var o = {};
        CATS.forEach(function (c) { o[c] = !!on; });
        return o;
    }

    function commit(cats, docs, hidden) {
        record = { v: 1, r: cfg.revision, cats: cats, docs: docs, hidden: !!hidden, ts: Math.floor(Date.now() / 1000) };
        saveRecord(record);
        if (!record.cats.preferences) { clearPosCookie(); clearThemeCookie(); }
        unblock();
        if (banner) { banner.hidden = true; }
        closeDialog();
        if (record.hidden) { hideFab(); } else { showFab(); }
    }

    function acceptAll() { commit(catsAll(true), allDocs(), false); }

    function rejectAll() { commit(catsAll(false), {}, false); }

    function hideIcon() {
        commit(record ? record.cats : catsAll(false), record ? record.docs : {}, true);
    }

    function saveFromDialog() {
        var blocked = marketingBlocked();
        var cats = {};
        CATS.forEach(function (c) {
            cats[c] = !!catBoxes[c].checked && !(c === 'marketing' && blocked);
        });
        var docs = {};
        docBoxes.forEach(function (b) {
            if (b.cb.checked) { docs[b.id] = b.version; }
        });
        commit(cats, docs, false);
    }

    /* ---------- theme ---------- */

    /* The theme choice is a preference: it is stored in a cookie only when Preferences is accepted.
       Without that consent it lasts for the current page only. */
    function currentTheme() {
        var v = pageTheme;
        if (!v && prefsAllowed()) {
            v = readCookieValue(THEME_COOKIE);
        }
        v = v || cfg.theme || 'auto';
        return THEMES.indexOf(v) === -1 ? 'auto' : v;
    }

    function setTheme(v) {
        pageTheme = v;
        if (prefsAllowed()) {
            var secure = window.location.protocol === 'https:' ? '; Secure' : '';
            document.cookie = THEME_COOKIE + '=' + encodeURIComponent(v) +
                '; Max-Age=' + (cfg.lifetime * 86400) + '; Path=/; SameSite=Lax' + secure;
        }
        root.setAttribute('data-theme', v);
        updateThemeButtons();
    }

    function updateThemeButtons() {
        var names = { auto: t('themeAuto', 'Auto'), light: t('themeLight', 'Light'), dark: t('themeDark', 'Dark') };
        var label = t('themeLabel', 'Theme') + ': ' + names[currentTheme()];
        themeButtons.forEach(function (b) { b.textContent = label; });
    }

    function themeButton() {
        var b = el('button', { type: 'button', class: 'crumbz-btn crumbz-btn-theme' });
        b.addEventListener('click', function () {
            var i = THEMES.indexOf(currentTheme());
            setTheme(THEMES[(i + 1) % THEMES.length]);
        });
        themeButtons.push(b);
        return b;
    }

    /* ---------- banner ---------- */

    function buildBanner() {
        var kids = [
            el('div', { class: 'crumbz-head' }, [
                el('h2', { class: 'crumbz-title', id: 'crumbz-banner-title', text: t('bannerTitle', 'Your choices about cookies') }),
                themeButton(),
                closeButton(rejectAll)
            ]),
            el('p', { class: 'crumbz-msg', text: t('bannerMessage', '') })
        ];

        if ((cfg.documents || []).length) {
            kids.push(el('ul', { class: 'crumbz-doclist', 'aria-label': t('documentsHeading', 'Documents') },
                cfg.documents.map(function (d) {
                    return el('li', null, [el('a', { href: d.url, target: '_blank', rel: 'noopener', text: d.label })]);
                })));
        }

        kids.push(el('div', { class: 'crumbz-actions' }, [
            button(t('reject', 'Reject all'), 'crumbz-btn-secondary', rejectAll),
            button(t('customize', 'Customize'), 'crumbz-btn-ghost', openDialog),
            button(t('accept', 'Accept all'), 'crumbz-btn-primary', acceptAll)
        ]));

        banner = el('section', {
            class: 'crumbz-banner crumbz-banner-' + (cfg.bannerPosition || 'bottom'),
            role: 'region',
            'aria-labelledby': 'crumbz-banner-title'
        }, [el('div', { class: 'crumbz-inner' }, kids)]);
        root.appendChild(banner);
    }

    /* ---------- settings dialog ---------- */

    function buildDialog() {
        dialog = document.createElement('dialog');
        dialog.className = 'crumbz-dialog';
        dialog.setAttribute('aria-labelledby', 'crumbz-dialog-title');
        dialog.addEventListener('cancel', function (e) {
            e.preventDefault();
            dismissDialog();
        });

        var docsGroup = null;
        if ((cfg.documents || []).length) {
            docBoxes = cfg.documents.map(function (d) {
                var cb = el('input', { type: 'checkbox', id: 'crumbz-doc-' + d.id });
                var check = el('label', { class: 'crumbz-check', 'for': 'crumbz-doc-' + d.id }, [
                    cb,
                    el('span', { text: t('docAccept', 'I have read and accept the %s').replace('%s', d.label) })
                ]);
                return {
                    id: d.id,
                    version: d.version,
                    cb: cb,
                    row: el('div', { class: 'crumbz-doc' }, [
                        check,
                        el('a', { href: d.url, target: '_blank', rel: 'noopener', class: 'crumbz-doclink', text: t('read', 'Read') + ' ↗' })
                    ])
                };
            });
            docsGroup = el('section', { class: 'crumbz-group', 'aria-labelledby': 'crumbz-docs-h' },
                [el('h3', { id: 'crumbz-docs-h', text: t('documentsHeading', 'Documents') })]
                    .concat(docBoxes.map(function (b) { return b.row; })));
        }

        var catRows = ['necessary'].concat(CATS).map(function (c) {
            var cb = el('input', { type: 'checkbox', id: 'crumbz-cat-' + c });
            if (c === 'necessary') {
                cb.checked = true;
                cb.disabled = true;
            }
            catBoxes[c] = cb;
            return el('div', { class: 'crumbz-cat' }, [
                el('label', { class: 'crumbz-check', 'for': 'crumbz-cat-' + c }, [
                    cb,
                    el('span', { class: 'crumbz-cat-name', text: t(c, c) })
                ]),
                el('p', { class: 'crumbz-cat-desc', text: t(c + 'Desc', '') })
            ]);
        });
        var catsGroup = el('section', { class: 'crumbz-group', 'aria-labelledby': 'crumbz-cats-h' },
            [el('h3', { id: 'crumbz-cats-h', text: t('categoriesHeading', 'Cookie categories') })].concat(catRows));

        gpcNote = el('p', { class: 'crumbz-note', text: t('gpcNote', '') });
        gpcNote.hidden = !marketingBlocked();

        var extra = [];
        if ((cfg.jurisdictions || []).indexOf('ca') !== -1) {
            extra.push(button(t('doNotSell', 'Do Not Sell or Share My Personal Information'), 'crumbz-btn-link', function () {
                catBoxes.marketing.checked = false;
                saveFromDialog();
            }));
        }

        var inner = el('div', { class: 'crumbz-dialog-inner' }, [
            el('div', { class: 'crumbz-head' }, [
                el('h2', { id: 'crumbz-dialog-title', class: 'crumbz-title', text: t('settingsTitle', 'Cookie settings') }),
                themeButton(),
                closeButton(dismissDialog)
            ]),
            el('p', { class: 'crumbz-msg', text: t('settingsIntro', '') }),
            docsGroup,
            catsGroup,
            gpcNote,
            el('div', { class: 'crumbz-actions' }, [
                button(t('reject', 'Reject all'), 'crumbz-btn-secondary', rejectAll),
                button(t('save', 'Save choices'), 'crumbz-btn-secondary', saveFromDialog),
                button(t('accept', 'Accept all'), 'crumbz-btn-primary', acceptAll)
            ]),
            el('div', { class: 'crumbz-actions crumbz-actions-start' },
                [button(t('hideIcon', 'Hide this icon'), 'crumbz-btn-link', hideIcon)].concat(extra))
        ]);

        dialog.appendChild(inner);
        root.appendChild(dialog);
    }

    function fillDialog() {
        var cats = (record && record.cats) ? record.cats : {};
        var docs = (record && record.docs) ? record.docs : {};
        CATS.forEach(function (c) { catBoxes[c].checked = !!cats[c]; });
        var blocked = marketingBlocked();
        catBoxes.marketing.disabled = blocked;
        if (blocked) { catBoxes.marketing.checked = false; }
        docBoxes.forEach(function (b) {
            b.cb.checked = !!record && docs[b.id] === b.version;
        });
        gpcNote.hidden = !blocked;
    }

    function openDialog() {
        fillDialog();
        if (typeof dialog.showModal === 'function') {
            if (!dialog.open) { dialog.showModal(); }
        } else {
            dialog.setAttribute('open', '');
        }
        var first = dialog.querySelector('input:not([disabled]), button');
        if (first) { first.focus(); }
    }

    function closeDialog() {
        if (typeof dialog.close === 'function' && dialog.open) {
            dialog.close();
        } else {
            dialog.removeAttribute('open');
        }
        if (fab && !fab.hidden) { fab.focus(); }
    }

    /** Closing without a saved choice is a refusal, so the banner does not come back. */
    function dismissDialog() {
        if (!record) {
            rejectAll();
        } else {
            closeDialog();
        }
    }

    /* ---------- settings icon ---------- */

    function clamp(v, min, max) {
        return Math.min(Math.max(v, min), Math.max(min, max));
    }

    function place(x, y) {
        var w = fab.offsetWidth || 48;
        var h = fab.offsetHeight || 48;
        x = clamp(x, 8, window.innerWidth - w - 8);
        y = clamp(y, 8, window.innerHeight - h - 8);
        fab.classList.add('crumbz-fab-free');
        fab.style.left = x + 'px';
        fab.style.top = y + 'px';
    }

    /* The icon position is a preference, so it is stored in a cookie only when the
       visitor has accepted the Preferences category. Otherwise it resets on each page. */
    function prefsAllowed() {
        return !!(record && record.cats && record.cats.preferences);
    }

    function clearPosCookie() {
        document.cookie = POS_COOKIE + '=; Max-Age=0; Path=/; SameSite=Lax';
    }

    function clearThemeCookie() {
        document.cookie = THEME_COOKIE + '=; Max-Age=0; Path=/; SameSite=Lax';
    }

    function readCookieValue(name) {
        var prefix = name + '=';
        var parts = document.cookie ? document.cookie.split('; ') : [];
        for (var i = 0; i < parts.length; i++) {
            if (parts[i].indexOf(prefix) === 0) {
                try { return decodeURIComponent(parts[i].slice(prefix.length)); } catch (e) { return null; }
            }
        }
        return null;
    }

    function savePos() {
        if (!prefsAllowed()) { return; }
        var r = fab.getBoundingClientRect();
        var value = JSON.stringify({ fx: +(r.left / window.innerWidth).toFixed(4), fy: +(r.top / window.innerHeight).toFixed(4) });
        var secure = window.location.protocol === 'https:' ? '; Secure' : '';
        document.cookie = POS_COOKIE + '=' + encodeURIComponent(value) +
            '; Max-Age=' + (cfg.lifetime * 86400) + '; Path=/; SameSite=Lax' + secure;
    }

    function restorePos() {
        if (!prefsAllowed()) { return; }
        var prefix = POS_COOKIE + '=';
        var parts = document.cookie ? document.cookie.split('; ') : [];
        for (var i = 0; i < parts.length; i++) {
            if (parts[i].indexOf(prefix) === 0) {
                try {
                    var p = JSON.parse(decodeURIComponent(parts[i].slice(prefix.length)));
                    place(p.fx * window.innerWidth, p.fy * window.innerHeight);
                } catch (e) { /* ignore a bad value */ }
                return;
            }
        }
    }

    function buildFab() {
        var pos = cfg.position || 'bottom-left';
        fab = el('button', {
            type: 'button',
            class: 'crumbz-fab crumbz-pos-' + pos,
            'aria-label': t('iconLabel', 'Cookie settings'),
            title: t('iconLabel', 'Cookie settings'),
            'aria-haspopup': 'dialog'
        });
        fab.innerHTML = ICON_SVG;
        fab.hidden = true;

        fab.addEventListener('click', function () {
            if (Date.now() - lastDragEnd < 300) { return; }
            openDialog();
        });

        fab.addEventListener('pointerdown', function (e) {
            if (e.button !== 0) { return; }
            var r = fab.getBoundingClientRect();
            drag = { id: e.pointerId, dx: e.clientX - r.left, dy: e.clientY - r.top, x0: e.clientX, y0: e.clientY, moved: false };
        });

        window.addEventListener('pointermove', function (e) {
            if (!drag || e.pointerId !== drag.id) { return; }
            if (!drag.moved && Math.hypot(e.clientX - drag.x0, e.clientY - drag.y0) < 6) { return; }
            drag.moved = true;
            place(e.clientX - drag.dx, e.clientY - drag.dy);
        });

        var endDrag = function (e) {
            if (!drag || e.pointerId !== drag.id) { return; }
            if (drag.moved) {
                lastDragEnd = Date.now();
                savePos();
            }
            drag = null;
        };
        window.addEventListener('pointerup', endDrag);
        window.addEventListener('pointercancel', endDrag);

        fab.addEventListener('keydown', function (e) {
            var step = 8;
            var r = fab.getBoundingClientRect();
            var x = r.left;
            var y = r.top;
            if (e.key === 'ArrowLeft') { x -= step; }
            else if (e.key === 'ArrowRight') { x += step; }
            else if (e.key === 'ArrowUp') { y -= step; }
            else if (e.key === 'ArrowDown') { y += step; }
            else { return; }
            e.preventDefault();
            place(x, y);
            savePos();
        });

        window.addEventListener('resize', function () {
            if (fab.classList.contains('crumbz-fab-free')) {
                place(parseFloat(fab.style.left) || 0, parseFloat(fab.style.top) || 0);
            }
        });

        root.appendChild(fab);
    }

    function showFab() {
        fab.hidden = false;
        restorePos();
    }

    function hideFab() {
        fab.hidden = true;
    }

    /* ---------- start ---------- */

    function init() {
        root = el('div', { id: 'crumbz-root', class: 'crumbz', 'data-theme': currentTheme() });
        document.body.appendChild(root);
        buildBanner();
        buildDialog();
        buildFab();
        updateThemeButtons();

        if (record) {
            banner.hidden = true;
            if (!record.hidden) { showFab(); }
        } else {
            banner.hidden = false;
        }
        // Scripts added to the page after this point (for example by other scripts) are checked too.
        if (window.MutationObserver) {
            new MutationObserver(function (records) {
                for (var i = 0; i < records.length; i++) {
                    if (records[i].addedNodes.length) { unblock(); return; }
                }
            }).observe(document.documentElement, { childList: true, subtree: true });
        }
        // Any dismissal of the banner that is not an Accept click is a refusal, Escape included.
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !record && !banner.hidden && !dialog.open) {
                rejectAll();
            }
        });
        unblock();
    }

    // This script is loaded in the footer, so the body already exists. Starting now (not on
    // DOMContentLoaded) lets allowed scripts and snippets run during parsing, so their own
    // DOMContentLoaded handlers and document.write calls behave normally.
    if (document.body) {
        init();
    } else {
        document.addEventListener('DOMContentLoaded', init);
    }
})();
