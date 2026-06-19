@php
    $primary = $company?->primary_color ?: '#f59e0b';
    $secondary = $company?->secondary_color ?: '#111827';
    $accent = $company?->accent_color ?: '#2563eb';
@endphp

{{-- Force Latin (0-9) digits everywhere — must run before any other script --}}
<script>
(function () {
    /* When the page locale is Arabic, browsers & Intl APIs default to
       Arabic-Indic numerals (٠١٢٣٤٥٦٧٨٩). This patch forces Latin digits
       globally by appending the Unicode extension -u-nu-latn to any 'ar*' locale. */
    var _Orig = Intl.NumberFormat;
    function patchLocale(l) {
        if (typeof l !== 'string' || !/^ar/i.test(l)) return l;
        if (l.indexOf('nu-latn') !== -1) return l;
        return l.indexOf('-u-') !== -1 ? l + '-nu-latn' : l + '-u-nu-latn';
    }
    function PatchedNF(locales, options) {
        if (typeof locales === 'string') locales = patchLocale(locales);
        else if (Array.isArray(locales)) locales = locales.map(patchLocale);
        return new _Orig(locales, options);
    }
    PatchedNF.prototype           = _Orig.prototype;
    PatchedNF.supportedLocalesOf  = _Orig.supportedLocalesOf.bind(_Orig);
    try { Intl.NumberFormat = PatchedNF; } catch (e) {}

    /* Also patch Intl.DateTimeFormat so date parts stay in Latin digits */
    var _OrigDTF = Intl.DateTimeFormat;
    function PatchedDTF(locales, options) {
        if (typeof locales === 'string') locales = patchLocale(locales);
        else if (Array.isArray(locales)) locales = locales.map(patchLocale);
        return new _OrigDTF(locales, options);
    }
    PatchedDTF.prototype          = _OrigDTF.prototype;
    PatchedDTF.supportedLocalesOf = _OrigDTF.supportedLocalesOf.bind(_OrigDTF);
    try { Intl.DateTimeFormat = PatchedDTF; } catch (e) {}
})();
</script>

<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<meta name="robots" content="noindex, nofollow, noarchive, nosnippet, noimageindex, nositelinkssearchbox">
<meta name="googlebot" content="noindex, nofollow, noarchive, nosnippet, noimageindex">
<meta name="bingbot" content="noindex, nofollow, noarchive, nosnippet, noimageindex">

<style>
    :root {
        --company-primary: {{ $primary }};
        --company-secondary: {{ $secondary }};
        --company-accent: {{ $accent }};
    }

    .fi-topbar,
    .fi-sidebar-header {
        border-color: color-mix(in srgb, var(--company-primary) 35%, transparent);
    }

    .fi-logo,
    .fi-sidebar-header .fi-logo {
        color: var(--company-secondary);
    }

    .fi-sidebar-nav-groups .fi-sidebar-item.fi-active > a,
    .fi-sidebar-nav-groups .fi-sidebar-item > a:hover {
        background-color: color-mix(in srgb, var(--company-primary) 14%, transparent);
    }

    .fi-sidebar-nav-groups .fi-sidebar-item.fi-active .fi-sidebar-item-label,
    .fi-sidebar-nav-groups .fi-sidebar-item.fi-active svg,
    .fi-breadcrumbs-item a,
    .fi-link {
        color: var(--company-primary);
    }

    .fi-btn.fi-color-primary,
    .fi-ta-header-toolbar .fi-btn.fi-color-primary {
        background-color: var(--company-primary);
        border-color: var(--company-primary);
    }

    .fi-tabs-item.fi-active,
    .fi-input-wrp:focus-within,
    .fi-fo-field-wrp:focus-within {
        border-color: var(--company-primary);
    }

    .fi-badge.fi-color-primary {
        background-color: color-mix(in srgb, var(--company-primary) 16%, transparent);
        color: var(--company-secondary);
    }

    .company-switcher .fi-topbar-item,
    .company-switcher .fi-dropdown-list-item {
        align-items: center;
    }

    .company-switcher__logo {
        display: inline-flex;
        width: 1.75rem;
        height: 1.75rem;
        flex: 0 0 1.75rem;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border-radius: 0.375rem;
        background: color-mix(in srgb, var(--company-primary) 10%, transparent);
        vertical-align: middle;
    }

    .company-switcher__logo--list {
        margin-inline-end: 0.625rem;
    }

    .company-switcher__logo img {
        display: block;
        width: 100% !important;
        height: 100% !important;
        max-width: 100% !important;
        max-height: 100% !important;
        object-fit: contain;
    }

    .company-switcher__name {
        min-width: 0;
        max-width: 9rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* ────────────────────────────────────────────────────────────────────────
       GLOBAL RESPONSIVE — applied on every page via HEAD_END render hook
    ──────────────────────────────────────────────────────────────────────── */

    /* Base: prevent any horizontal page overflow */
    html, body { overflow-x: hidden; }

    /* ── TOPBAR ─────────────────────────────────────────────────────────── */
    .fi-topbar nav {
        gap: 0.375rem;
    }

    /* Tighten topbar buttons on smaller screens */
    @media (max-width: 639px) {
        .fi-topbar nav {
            padding-inline: 0.5rem;
            gap: 0.125rem;
        }

        /* Compact topbar trigger: show only the logo (hide the name + chevron)
           to save space. The name is scoped to the trigger (.fi-topbar-item)
           so it STAYS visible inside the switcher dropdown list — otherwise the
           companies appear as unlabeled logos and can't be told apart. */
        .company-switcher .fi-topbar-item .company-switcher__name,
        .company-switcher .text-xs { display: none !important; }
        .company-switcher .fi-topbar-item { padding: 0.375rem 0.5rem; }

        .lang-switcher-wrap {
            display: inline-flex !important;
            flex-shrink: 0;
        }

        /* Shrink notification bell & avatar */
        .fi-topbar-item { padding: 0.375rem 0.5rem; }

        /* Search bar shorter */
        .fi-global-search-field { max-width: 6rem; }
    }

    @media (max-width: 479px) {
        .lang-switcher-wrap .fi-topbar-item {
            min-width: 2.5rem;
            padding-inline: 0.375rem;
            justify-content: center;
        }

        /* Even tighter search */
        .fi-global-search-field { max-width: 4.75rem; }
    }

    /* ── PAGE HEADER ────────────────────────────────────────────────────── */
    .fi-header {
        flex-wrap: wrap;
        gap: 0.75rem;
    }

    @media (max-width: 767px) {
        .fi-header-heading { font-size: 1.25rem; }
        .fi-header-actions { flex-wrap: wrap; gap: 0.5rem; }
        .fi-header-actions .fi-btn { flex-shrink: 0; }
    }

    /* ── SIDEBAR — truly fixed, never scrolls with the page ────────────── */
    .fi-sidebar,
    .fi-body-has-sidebar-fully-collapsible-on-desktop .fi-sidebar,
    .fi-body-has-sidebar-collapsible-on-desktop .fi-sidebar {
        position: fixed !important;
        overflow: hidden !important;
    }

    @media (min-width: 1024px) {
        .fi-sidebar .fi-sidebar-nav {
            overflow-y: auto;
            overflow-x: hidden;
            flex: 1 1 0%;
            min-height: 0;
        }

        /* Main content offset managed by JS — support both LTR and RTL */
        .fi-main-ctn {
            transition: margin-left 0.25s ease, margin-right 0.25s ease;
        }
    }

    @media (max-width: 1023px) {
        .fi-sidebar { max-width: 17rem; }
    }

    /* ── Hide default topbar sidebar toggle buttons on DESKTOP only ──────────
       On desktop (≥1024px) the floating #sidebar-toggle-tab replaces them.
       On mobile/tablet (≤1023px) the floating tab is hidden, so Filament's
       native hamburger (.fi-topbar-open-sidebar-btn) must stay visible to open
       the sidebar — otherwise the navigation becomes unreachable on mobile. */
    @media (min-width: 1024px) {
        .fi-topbar-collapse-sidebar-btn-ctn,
        .fi-topbar-open-sidebar-btn,
        .fi-topbar-close-sidebar-btn {
            display: none !important;
        }
    }

    /* ── Floating sidebar toggle tab ────────────────────────────────────── */
    #sidebar-toggle-tab {
        position: fixed;
        top: 50%;
        transform: translateY(-50%);
        z-index: 50;
        width: 1.25rem;
        height: 3.5rem;
        background: #fff;
        border: 1px solid rgba(0, 0, 0, 0.1);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.1);
        padding: 0;
        outline: none;
        /* LTR defaults */
        border-left: none;
        border-radius: 0 0.5rem 0.5rem 0;
        transition: left 0.25s ease, background 0.15s ease;
    }
    /* RTL: tab appears on the right side of the sidebar */
    [dir="rtl"] #sidebar-toggle-tab {
        border-left: 1px solid rgba(0, 0, 0, 0.1);
        border-right: none;
        border-radius: 0.5rem 0 0 0.5rem;
        transition: right 0.25s ease, background 0.15s ease;
        left: auto;
    }
    .dark #sidebar-toggle-tab {
        background: rgb(30, 41, 59);
        border-color: rgba(255, 255, 255, 0.1);
        box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.35);
    }
    #sidebar-toggle-tab:hover {
        background: color-mix(in srgb, var(--company-primary) 10%, white);
    }
    .dark #sidebar-toggle-tab:hover {
        background: color-mix(in srgb, var(--company-primary) 10%, rgb(30, 41, 59));
    }
    #sidebar-toggle-tab svg {
        width: 0.75rem;
        height: 0.75rem;
        color: var(--company-primary);
        flex-shrink: 0;
    }
    @media (max-width: 1023px) {
        #sidebar-toggle-tab { display: none !important; }
    }


    /* ── TABLES ─────────────────────────────────────────────────────────── */
    /* Horizontal scroll on mobile instead of broken overflow */
    .fi-ta-ctn,
    .fi-ta-content {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    @media (max-width: 767px) {
        /* Tighten table cell padding */
        .fi-ta-cell { padding: 0.5rem 0.625rem; }
        .fi-ta-header-cell { padding: 0.5rem 0.625rem; }

        /* Shrink table action buttons */
        .fi-ta-row-actions .fi-btn { padding: 0.25rem 0.5rem; font-size: 0.75rem; }

        /* Hide less important columns on mobile (columns can opt in with .fi-ta-col-mobile-hide) */
        .fi-ta-col-mobile-hide { display: none !important; }

        /* Toolbar wraps on mobile */
        .fi-ta-header-toolbar { flex-wrap: wrap; gap: 0.5rem; }
    }

    /* ── FORMS ──────────────────────────────────────────────────────────── */
    @media (max-width: 767px) {
        /* Force 1-column layout in all form grids on small screens */
        .fi-fo-component-ctn .grid { grid-template-columns: 1fr !important; }

        /* Reduce section padding */
        .fi-section-content { padding: 1rem; }
        .fi-section-header { padding: 0.875rem 1rem; }

        /* Prevent long inputs from overflowing */
        .fi-input, .fi-select-input, .fi-textarea { max-width: 100% !important; }
    }

    /* ── MODALS ─────────────────────────────────────────────────────────── */
    @media (max-width: 639px) {
        .fi-modal-window {
            width: calc(100vw - 1.5rem) !important;
            max-width: calc(100vw - 1.5rem) !important;
            margin: 0.75rem;
            border-radius: 0.875rem;
        }

        /* Modal form grids → 1 col */
        .fi-modal-content .grid { grid-template-columns: 1fr !important; }
    }

    /* ── STATS / KPI WIDGETS ────────────────────────────────────────────── */
    @media (max-width: 639px) {
        .fi-wi-stats-overview-stat { padding: 1rem; }
        .fi-wi-stats-overview-stat-value { font-size: 1.25rem; }
    }

    /* ── TABS ───────────────────────────────────────────────────────────── */
    @media (max-width: 639px) {
        .fi-tabs { overflow-x: auto; }
        .fi-tabs-list { white-space: nowrap; }
    }

    /* ── BREADCRUMBS ────────────────────────────────────────────────────── */
    @media (max-width: 479px) {
        .fi-breadcrumbs { display: none; }
    }

    /* ── INFOLISTS ──────────────────────────────────────────────────────── */
    @media (max-width: 767px) {
        .fi-in-component-ctn .grid { grid-template-columns: 1fr !important; }
    }

    /* ── FOOTER ─────────────────────────────────────────────────────────── */
    .fi-footer { padding: 0.5rem 1rem; }

    /* ── SIDEBAR RESIZE HANDLE ──────────────────────────────────────────── */
    #sidebar-resize-handle {
        position: fixed;
        top: 0;
        width: 5px;
        height: 100vh;
        cursor: col-resize;
        /* z-index 35: above sidebar (30) but below Filament modals (~50)
           so modals naturally intercept clicks without any JS hacks */
        z-index: 35;
        background: transparent;
        transition: background 0.15s;
    }
    #sidebar-resize-handle:hover,
    #sidebar-resize-handle.dragging {
        background: color-mix(in srgb, var(--company-primary) 45%, transparent);
    }
    @media (max-width: 1023px) {
        #sidebar-resize-handle { display: none; }
    }
</style>

<script>
(function () {
    var STORAGE_KEY = 'fi-sidebar-width';
    var MIN = 192;
    var MAX = 420;
    var DEFAULT = 256;

    /* ── helpers ──────────────────────────────────────────────────────── */

    function getSidebar()   { return document.querySelector('.fi-sidebar'); }
    function getMainCtn()   { return document.querySelector('.fi-main-ctn'); }
    function getHandle()    { return document.getElementById('sidebar-resize-handle'); }
    function getToggleTab() { return document.getElementById('sidebar-toggle-tab'); }

    function isDesktop() { return window.innerWidth >= 1024; }
    function isRTL()     { return document.documentElement.dir === 'rtl'; }

    /* Returns true when any modal/dialog is actually VISIBLE (not just in DOM) */
    function isModalOpen() {
        var els = document.querySelectorAll('[role="dialog"]');
        for (var i = 0; i < els.length; i++) {
            if (window.getComputedStyle(els[i]).display !== 'none') return true;
        }
        return false;
    }

    /* ── Sync main content margin to match sidebar width ─────────────── */
    function syncMargin() {
        var main = getMainCtn();
        if (!main) return;
        if (!isDesktop()) {
            main.style.marginLeft  = '';
            main.style.marginRight = '';
            return;
        }
        var sidebar = getSidebar();
        var w = sidebar ? sidebar.offsetWidth + 'px' : '0px';
        if (isRTL()) {
            main.style.marginRight = w;
            main.style.marginLeft  = '';
        } else {
            main.style.marginLeft  = w;
            main.style.marginRight = '';
        }
    }

    /* ── Apply an explicit width to the sidebar + reposition handle ───── */
    function applyWidth(w) {
        w = Math.max(MIN, Math.min(MAX, w));
        var sidebar = getSidebar();
        if (sidebar) {
            sidebar.style.width    = w + 'px';
            sidebar.style.minWidth = w + 'px';
            sidebar.style.maxWidth = w + 'px';
        }
        var handle = getHandle();
        if (handle) {
            if (isRTL()) {
                handle.style.right = w + 'px';
                handle.style.left  = '';
            } else {
                handle.style.left  = w + 'px';
                handle.style.right = '';
            }
        }
        syncMargin();
        return w;
    }

    /* ── Release width control so Filament shrinks to icon-only size ───── */
    function clearWidth() {
        var sidebar = getSidebar();
        if (!sidebar) return;
        sidebar.style.width    = '';
        sidebar.style.minWidth = '';
        sidebar.style.maxWidth = '';
    }

    /* ── Toggle tab chevron icon ──────────────────────────────────────── */
    function updateToggleIcon(sidebarIsOpen) {
        var tab = getToggleTab();
        if (!tab) return;
        /* In LTR: open → left chevron (collapse), closed → right chevron (expand)
           In RTL: directions are mirrored                                        */
        var showLeft = isRTL() ? sidebarIsOpen : !sidebarIsOpen;
        tab.innerHTML = showLeft
            ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>'
            : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>';
    }

    /* ── Position the floating toggle tab at the sidebar edge ────────── */
    function positionToggleTab() {
        var tab     = getToggleTab();
        var sidebar = getSidebar();
        if (!tab) return;
        if (!isDesktop()) { tab.style.display = 'none'; return; }
        tab.style.display = 'flex';

        var sidebarW = sidebar ? sidebar.offsetWidth : 0;
        var isOpen   = sidebar ? sidebar.classList.contains('fi-sidebar-open') : false;

        if (isRTL()) {
            /* Sidebar sits on the right; tab hangs off its left edge */
            tab.style.right = sidebarW + 'px';
            tab.style.left  = '';
        } else {
            /* Sidebar sits on the left; tab hangs off its right edge */
            tab.style.left  = sidebarW + 'px';
            tab.style.right = '';
        }

        updateToggleIcon(isOpen);

        /* Show/hide the resize handle together with the open sidebar */
        var handle = getHandle();
        if (handle) handle.style.display = isOpen ? '' : 'none';
    }

    /* ── Attach toggle tab click handler ─────────────────────────────── */
    function attachToggleTab() {
        var tab = getToggleTab();
        if (!tab) {
            tab = document.createElement('button');
            tab.id   = 'sidebar-toggle-tab';
            tab.type = 'button';
            document.body.appendChild(tab);
        }

        tab.onclick = function () {
            if (isModalOpen()) return;
            if (window.Alpine && window.Alpine.store) {
                var store = window.Alpine.store('sidebar');
                if (store) {
                    if (store.isOpen) store.close(); else store.open();
                    setTimeout(function () { positionToggleTab(); syncMargin(); }, 300);
                    return;
                }
            }
            var btn = document.querySelector(
                '.fi-topbar-close-sidebar-btn, .fi-topbar-open-sidebar-btn'
            );
            if (btn) btn.click();
        };

        positionToggleTab();
    }

    /* ── Resize handle ────────────────────────────────────────────────── */
    function attachResizeHandle() {
        var handle = getHandle();
        if (!handle) {
            handle = document.createElement('div');
            handle.id = 'sidebar-resize-handle';
            document.body.appendChild(handle);
        }

        var saved   = parseInt(localStorage.getItem(STORAGE_KEY), 10) || DEFAULT;
        var clamped = Math.max(MIN, Math.min(MAX, saved));

        /* Pre-position the handle for when the sidebar opens */
        if (isRTL()) {
            handle.style.right = clamped + 'px';
            handle.style.left  = '';
        } else {
            handle.style.left  = clamped + 'px';
            handle.style.right = '';
        }

        /* Only force the saved width if sidebar is already open (avoids overriding
           Filament's icon-only collapsed width on first render)                  */
        var sidebar = getSidebar();
        if (sidebar && sidebar.classList.contains('fi-sidebar-open')) {
            applyWidth(saved);
        }

        var startX, startW, dragging = false;

        handle.onmousedown = function (e) {
            if (isModalOpen()) return;
            e.preventDefault();
            dragging = true;
            startX = e.clientX;
            var s = getSidebar();
            startW = s ? s.offsetWidth : saved;
            handle.classList.add('dragging');
            document.body.style.userSelect = 'none';
            document.body.style.cursor = 'col-resize';
        };

        document.addEventListener('mousemove', function (e) {
            if (!dragging) return;
            /* In RTL, dragging left (negative delta) should grow the sidebar */
            var delta = isRTL() ? (startX - e.clientX) : (e.clientX - startX);
            var newW  = applyWidth(startW + delta);
            localStorage.setItem(STORAGE_KEY, newW);
            positionToggleTab();
        });

        document.addEventListener('mouseup', function () {
            if (!dragging) return;
            dragging = false;
            handle.classList.remove('dragging');
            document.body.style.userSelect = '';
            document.body.style.cursor     = '';
        });
    }

    /* ── Observe sidebar size + class changes ────────────────────────── */
    var sidebarObserver      = null;
    var sidebarClassObserver = null;

    function observeSidebar() {
        var sidebar = getSidebar();
        if (!sidebar) return;

        if (sidebarObserver)      { sidebarObserver.disconnect();      sidebarObserver = null; }
        if (sidebarClassObserver) { sidebarClassObserver.disconnect(); sidebarClassObserver = null; }

        sidebarObserver = new ResizeObserver(function () {
            syncMargin();
            positionToggleTab();
        });
        sidebarObserver.observe(sidebar);

        sidebarClassObserver = new MutationObserver(function () {
            requestAnimationFrame(function () {
                var s      = getSidebar();
                var isOpen = s && s.classList.contains('fi-sidebar-open');
                if (isOpen) {
                    var saved = parseInt(localStorage.getItem(STORAGE_KEY), 10) || DEFAULT;
                    applyWidth(saved);
                } else {
                    clearWidth(); /* Let Filament control the icon-only width */
                }
                syncMargin();
                positionToggleTab();
            });
        });
        sidebarClassObserver.observe(sidebar, { attributes: true, attributeFilter: ['class'] });
    }

    /* ── Init ─────────────────────────────────────────────────────────── */
    function init() {
        attachResizeHandle();
        attachToggleTab();
        observeSidebar();
        syncMargin();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    document.addEventListener('livewire:navigated', function () {
        var s = getSidebar();
        if (s && s.classList.contains('fi-sidebar-open')) {
            var saved = parseInt(localStorage.getItem(STORAGE_KEY), 10) || DEFAULT;
            applyWidth(saved);
        } else {
            clearWidth();
        }
        positionToggleTab();
        observeSidebar();
    });
})();
</script>

@auth
<script>
/* ── Auto-logout: 30 min inactivity + 8 h max session ─────────────── */
(function () {
    var IDLE_LIMIT    = 30 * 60 * 1000;   /* 30 min in ms */
    var SESSION_LIMIT = 8  * 60 * 60 * 1000; /* 8 h in ms */
    var STORAGE_IDLE  = 'erp_last_activity';
    var STORAGE_LOGIN = 'erp_session_start';
    var LOGOUT_URL    = '{{ route("filament.admin.auth.logout") }}';
    var CHECK_EVERY   = 60 * 1000; /* check every 60 s */

    /* Initialise login timestamp only once per browser session */
    if (!sessionStorage.getItem(STORAGE_LOGIN)) {
        sessionStorage.setItem(STORAGE_LOGIN, Date.now().toString());
    }

    function touch() {
        localStorage.setItem(STORAGE_IDLE, Date.now().toString());
    }

    /* Bump last-activity on any real user interaction */
    ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click'].forEach(function (evt) {
        document.addEventListener(evt, touch, { passive: true, capture: true });
    });

    touch(); /* record activity on page load */

    function check() {
        var now       = Date.now();
        var lastAct   = parseInt(localStorage.getItem(STORAGE_IDLE) || '0', 10);
        var loginTime = parseInt(sessionStorage.getItem(STORAGE_LOGIN) || '0', 10);

        var idle        = lastAct   ? now - lastAct   : 0;
        var sessionAge  = loginTime ? now - loginTime : 0;

        if (idle >= IDLE_LIMIT || sessionAge >= SESSION_LIMIT) {
            /* POST to the Filament logout endpoint to properly clear the session */
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = LOGOUT_URL;

            var csrf = document.createElement('input');
            csrf.type  = 'hidden';
            csrf.name  = '_token';
            csrf.value = document.querySelector('meta[name="csrf-token"]')?.content || '';
            form.appendChild(csrf);

            document.body.appendChild(form);
            sessionStorage.removeItem(STORAGE_LOGIN);
            localStorage.removeItem(STORAGE_IDLE);
            form.submit();
        }
    }

    /* Sync last-activity across tabs via storage events */
    window.addEventListener('storage', function (e) {
        if (e.key === STORAGE_IDLE) check();
    });

    /* Re-hook after Livewire navigations */
    document.addEventListener('livewire:navigated', function () { touch(); });

    setInterval(check, CHECK_EVERY);
})();
</script>
@endauth
