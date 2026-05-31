@php
    $primary = $company?->primary_color ?: '#f59e0b';
    $secondary = $company?->secondary_color ?: '#111827';
    $accent = $company?->accent_color ?: '#2563eb';
@endphp

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
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
</style>
