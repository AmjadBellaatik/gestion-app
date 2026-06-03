@php
    $primary = $company?->primary_color ?: '#f59e0b';
    $secondary = $company?->secondary_color ?: '#111827';
    $accent = $company?->accent_color ?: '#2563eb';
@endphp

<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

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

        /* Hide company name, show only logo */
        .company-switcher__name,
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

    /* ── SIDEBAR ────────────────────────────────────────────────────────── */
    @media (min-width: 1024px) {
        .fi-main-sidebar {
            position: sticky !important;
            top: 0 !important;
            height: 100dvh;
            max-height: 100dvh;
            overflow-y: auto;
        }

        .fi-main-sidebar .fi-sidebar-nav {
            overflow-y: auto;
        }
    }

    @media (max-width: 1023px) {
        /* Ensure sidebar overlay doesn't cause horizontal scroll */
        .fi-sidebar { max-width: 17rem; }
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
        z-index: 9999;
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

    function applyWidth(w) {
        w = Math.max(MIN, Math.min(MAX, w));
        var sidebar = document.querySelector('.fi-sidebar');
        if (sidebar) {
            sidebar.style.width = w + 'px';
            sidebar.style.minWidth = w + 'px';
            sidebar.style.maxWidth = w + 'px';
        }
        var handle = document.getElementById('sidebar-resize-handle');
        if (handle) handle.style.left = w + 'px';
        return w;
    }

    function attachHandle() {
        var saved = parseInt(localStorage.getItem(STORAGE_KEY), 10) || DEFAULT;
        applyWidth(saved);

        var handle = document.getElementById('sidebar-resize-handle');
        if (!handle) {
            handle = document.createElement('div');
            handle.id = 'sidebar-resize-handle';
            document.body.appendChild(handle);
        }
        handle.style.left = Math.max(MIN, Math.min(MAX, saved)) + 'px';

        var startX, startW, dragging = false;

        handle.onmousedown = function (e) {
            e.preventDefault();
            dragging = true;
            startX = e.clientX;
            var sidebar = document.querySelector('.fi-sidebar');
            startW = sidebar ? sidebar.offsetWidth : saved;
            handle.classList.add('dragging');
            document.body.style.userSelect = 'none';
            document.body.style.cursor = 'col-resize';
        };

        document.addEventListener('mousemove', function (e) {
            if (!dragging) return;
            var newW = applyWidth(startW + (e.clientX - startX));
            localStorage.setItem(STORAGE_KEY, newW);
        });

        document.addEventListener('mouseup', function () {
            if (!dragging) return;
            dragging = false;
            handle.classList.remove('dragging');
            document.body.style.userSelect = '';
            document.body.style.cursor = '';
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', attachHandle);
    } else {
        attachHandle();
    }

    document.addEventListener('livewire:navigated', function () {
        var saved = parseInt(localStorage.getItem(STORAGE_KEY), 10) || DEFAULT;
        applyWidth(saved);
    });
})();
</script>
