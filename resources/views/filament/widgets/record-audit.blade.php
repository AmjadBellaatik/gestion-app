@php
    $fmt = fn ($dt) => $dt ? \Illuminate\Support\Carbon::parse($dt) : null;
    $created = $fmt($createdAt);
    $updated = $fmt($updatedAt);

    $cell = function (string $label, string $icon, ?string $by, $when, ?string $action = null) {
        return compact('label', 'icon', 'by', 'when', 'action');
    };

    $cells = [
        $cell(__('messages.created'), 'heroicon-o-plus-circle', $createdBy, $created),
        $cell(__('messages.last_updated'), 'heroicon-o-pencil-square', $updatedBy, $updated, $lastAction),
    ];

    $actionColors = [
        'created' => 'text-success-600 dark:text-success-400 bg-success-50 dark:bg-success-400/10',
        'updated' => 'text-warning-600 dark:text-warning-400 bg-warning-50 dark:bg-warning-400/10',
        'deleted' => 'text-danger-600 dark:text-danger-400 bg-danger-50 dark:bg-danger-400/10',
    ];
@endphp

<x-filament-widgets::widget>
    <x-filament::section compact>
        <x-slot name="heading">
            <span class="flex items-center gap-2">
                <x-heroicon-m-clock class="h-5 w-5 text-gray-400" />
                {{ __('messages.record_information') }}
            </span>
        </x-slot>

        @if ($canViewAudit && $hasHistory && $historyUrl)
            <x-slot name="afterHeader">
                <a href="{{ $historyUrl }}"
                   class="inline-flex items-center gap-1 text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400">
                    {{ __('messages.view_full_history') }}
                    <x-heroicon-m-arrow-top-right-on-square class="h-4 w-4" />
                </a>
            </x-slot>
        @endif

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            @foreach ($cells as $c)
                <div class="flex items-start gap-3 rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <x-dynamic-component :component="$c['icon']" class="h-5 w-5 text-gray-500 dark:text-gray-400" />
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                {{ $c['label'] }}
                            </p>
                            @if ($c['action'])
                                <span class="rounded-md px-1.5 py-0.5 text-[10px] font-semibold uppercase {{ $actionColors[$c['action']] ?? 'bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-300' }}">
                                    {{ __('messages.audit_action_' . $c['action']) }}
                                </span>
                            @endif
                        </div>

                        @if ($c['when'])
                            <p class="mt-1 truncate text-sm font-semibold text-gray-950 dark:text-white">
                                {{ $c['by'] ?? __('messages.system') }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400" title="{{ $c['when']->format('Y-m-d H:i:s') }}">
                                {{ $c['when']->translatedFormat('d M Y · H:i') }}
                                <span class="text-gray-400">({{ $c['when']->diffForHumans() }})</span>
                            </p>
                        @else
                            <p class="mt-1 text-sm text-gray-400">—</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
