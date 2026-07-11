{{-- Branded heading shown inside the login card, above the form. --}}
@php
    $logoUrl = $company?->logo
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($company->logo)
        : null;
@endphp
<div style="text-align: center; margin-bottom: 1.6rem;">
    @if ($logoUrl)
        <img src="{{ $logoUrl }}" alt="{{ $company?->name }}"
             style="max-height: 64px; max-width: 190px; margin: 0 auto 0.9rem; display: block;">
    @endif
    <h1 class="fi-login-title" style="font-size: 1.35rem; font-weight: 800; letter-spacing: -0.01em; margin: 0;">
        {{ $company?->name ?: __('messages.login_welcome_title') }}
    </h1>
    <p class="fi-login-subtitle" style="font-size: 0.86rem; margin: 0.3rem 0 0;">
        {{ __('messages.login_welcome_subtitle') }}
    </p>
</div>
<style>
    .fi-login-title    { color: #0f172a; }
    .fi-login-subtitle { color: #6b7280; }
    .dark .fi-login-title    { color: #f9fafb; }
    .dark .fi-login-subtitle { color: #9ca3af; }
</style>
