<div class="lang-switcher-wrap" style="margin-inline-start:.25rem;">

    <x-filament::dropdown placement="bottom-end">

        <x-slot name="trigger">

            <button
                type="button"
                class="fi-topbar-item flex items-center gap-1 rounded-lg px-2 py-2 text-sm font-medium"
            >
                <span>{{ strtoupper(app()->getLocale()) }}</span>
                <span class="lang-switcher-chevron text-xs" aria-hidden="true">▼</span>
            </button>

        </x-slot>

        <x-filament::dropdown.list>

            <x-filament::dropdown.list.item tag="a" href="{{ url('/language/fr') }}">
                🇫🇷 Français
            </x-filament::dropdown.list.item>

            <x-filament::dropdown.list.item tag="a" href="{{ url('/language/en') }}">
                🇬🇧 English
            </x-filament::dropdown.list.item>

            <x-filament::dropdown.list.item tag="a" href="{{ url('/language/ar') }}">
                🇲🇦 العربية
            </x-filament::dropdown.list.item>

        </x-filament::dropdown.list>

    </x-filament::dropdown>

</div>
