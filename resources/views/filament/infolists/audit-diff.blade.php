@php
    /** @var \App\Models\ActivityLog $record */
    $old = $record->old_values ?? [];
    $new = $record->new_values ?? [];
    $keys = collect(array_keys($old + $new))->reject(fn ($k) => $k === 'id')->values();

    $render = function ($value): string {
        if (is_null($value) || $value === '') {
            return '—';
        }
        if (is_bool($value)) {
            return $value ? '✓' : '✗';
        }
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return (string) $value;
    };
@endphp

<div class="text-sm">
    @if ($keys->isEmpty())
        <p class="text-gray-500 dark:text-gray-400">{{ __('messages.no_field_changes') }}</p>
    @else
        <div class="overflow-hidden rounded-lg ring-1 ring-gray-950/5 dark:ring-white/10">
            <table class="w-full divide-y divide-gray-200 dark:divide-white/10">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        <th class="px-4 py-2">{{ __('messages.field') }}</th>
                        <th class="px-4 py-2">{{ __('messages.old_value') }}</th>
                        <th class="px-4 py-2">{{ __('messages.new_value') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @foreach ($keys as $key)
                        @php
                            $oldVal = $render($old[$key] ?? null);
                            $newVal = $render($new[$key] ?? null);
                            $changed = ($old[$key] ?? null) != ($new[$key] ?? null);
                        @endphp
                        <tr class="{{ $changed ? 'bg-warning-50/40 dark:bg-warning-400/5' : '' }}">
                            <td class="px-4 py-2 font-medium text-gray-700 dark:text-gray-300">
                                {{ \Illuminate\Support\Str::headline($key) }}
                            </td>
                            <td class="px-4 py-2 text-gray-500 line-through decoration-danger-400/50 dark:text-gray-400">
                                {{ $oldVal }}
                            </td>
                            <td class="px-4 py-2 font-medium text-gray-950 dark:text-white">
                                {{ $newVal }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
