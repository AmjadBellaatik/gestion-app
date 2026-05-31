@php

    $companies = auth()->user()
        ? auth()->user()->companies->reject(
            fn ($company) => strcasecmp(
                trim($company->name),
                'Default Company'
            ) === 0
        )->values()
        : collect();

    $currentCompany = session('company_id');

    $activeCompany = $companies
        ->where('id', $currentCompany)
        ->first()
        ?? $companies->first();

    $companyLogoUrl = fn ($company) => $company?->logo
        ? asset('storage/' . ltrim($company->logo, '/'))
        : null;

@endphp

@if(auth()->check() && $companies->count())

    <div class="company-switcher me-3">

        <x-filament::dropdown
            placement="bottom-start"
        >

            <x-slot name="trigger">

                <button
                    type="button"
                    class="
                        fi-topbar-item
                        flex items-center gap-2
                        rounded-lg px-3 py-2
                        text-sm font-medium
                    "
                >

                    <span class="company-switcher__logo">
                        @if($companyLogoUrl($activeCompany))

                            <img
                                src="{{ $companyLogoUrl($activeCompany) }}"
                                alt="{{ $activeCompany?->name }}"
                            >

                        @endif
                    </span>

                    <span class="company-switcher__name">

                        {{ $activeCompany?->name }}

                    </span>

                    <span class="text-xs">
                        ▼
                    </span>

                </button>

            </x-slot>

            <x-filament::dropdown.list>

                @foreach($companies as $company)

                    <a
                        href="#"
                        onclick="
                            event.preventDefault();
                            document.getElementById(
                                'company-switch-{{ $company->id }}'
                            ).submit();
                        "
                        class="
                            fi-dropdown-list-item
                            flex
                            w-full
                            px-4
                            py-2
                            text-sm
                        "
                    >

                        <span class="company-switcher__logo company-switcher__logo--list">
                            @if($companyLogoUrl($company))

                                <img
                                    src="{{ $companyLogoUrl($company) }}"
                                    alt="{{ $company->name }}"
                                >

                            @endif
                        </span>

                        <span class="company-switcher__name">
                            {{ $company->name }}
                        </span>

                    </a>

                    <form
                        id="company-switch-{{ $company->id }}"
                        method="POST"
                        action="{{ route('company.switch') }}"
                        class="hidden"
                    >

                        @csrf

                        <input
                            type="hidden"
                            name="company_id"
                            value="{{ $company->id }}"
                        >

                    </form>

                @endforeach

            </x-filament::dropdown.list>

        </x-filament::dropdown>

    </div>

@endif
