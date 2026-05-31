@if(auth()->check())

@php
    $companies = auth()->user()->companies->reject(
        fn ($company) => strcasecmp(
            trim($company->name),
            'Default Company'
        ) === 0
    )->values();

    $currentCompany = $companies->firstWhere(
        'id',
        session('company_id')
    ) ?? $companies->first();

    $companyLogoUrl = fn ($company) => $company?->logo
        ? asset('storage/' . ltrim($company->logo, '/'))
        : null;
@endphp

@if($companies->count())

<div class="company-switcher ml-2">

    <x-filament::dropdown placement="bottom-start">

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

                <span class="company-switcher__name truncate max-w-[220px]">

                    @if($companyLogoUrl($currentCompany))

                        <span class="company-switcher__logo">
                        <img
                            src="{{ $companyLogoUrl($currentCompany) }}"
                            alt="{{ $currentCompany?->name }}"
                        >
                        </span>

                    @endif

                    {{ $currentCompany?->name }}
                </span>

                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="w-4 h-4"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                >
                    <path
                        fill-rule="evenodd"
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        clip-rule="evenodd"
                    />
                </svg>

            </button>

        </x-slot>

        <x-filament::dropdown.list>

            @foreach($companies as $company)

                <x-filament::dropdown.list.item
                    tag="a"
                    href="{{ url('/switch-company/' . $company->id) }}"
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

                </x-filament::dropdown.list.item>

            @endforeach

        </x-filament::dropdown.list>

    </x-filament::dropdown>

</div>

@endif

@endif
