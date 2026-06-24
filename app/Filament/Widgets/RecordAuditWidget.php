<?php

namespace App\Filament\Widgets;

use App\Models\ActivityLog;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;

/**
 * Compact footer card shown on every resource View page. Displays who
 * created the record and who last modified it (with date + time), plus a
 * shortcut into the full Audit History for admins.
 */
class RecordAuditWidget extends Widget
{
    public ?Model $record = null;

    protected string $view = 'filament.widgets.record-audit';

    protected int | string | array $columnSpan = 'full';

    protected static bool $isLazy = false;

    public function getViewData(): array
    {
        $created = $this->auditEntry('created', 'asc');
        $latest  = $this->latestEntry();

        return [
            'createdAt'    => $this->record?->created_at,
            'updatedAt'    => $this->record?->updated_at,
            'createdBy'    => $this->userName($created),
            'updatedBy'    => $this->userName($latest),
            'lastAction'   => $latest?->action,
            'hasHistory'   => $latest !== null,
            'canViewAudit' => auth()->user()?->hasAnyRole(['Super Admin', 'Admin']) ?? false,
            'historyUrl'   => $this->historyUrl(),
        ];
    }

    protected function baseQuery()
    {
        if (! $this->record) {
            return null;
        }

        return ActivityLog::withoutGlobalScopes()
            ->where('model_type', $this->record::class)
            ->where('model_id', $this->record->getKey());
    }

    protected function auditEntry(string $action, string $direction): ?ActivityLog
    {
        return $this->baseQuery()
            ?->where('action', $action)
            ->orderBy('id', $direction)
            ->with('user')
            ->first();
    }

    protected function latestEntry(): ?ActivityLog
    {
        return $this->baseQuery()
            ?->latest('id')
            ->with('user')
            ->first();
    }

    protected function userName(?ActivityLog $entry): ?string
    {
        if (! $entry) {
            return null;
        }

        return $entry->user?->name ?? __('messages.system');
    }

    protected function historyUrl(): ?string
    {
        if (! $this->record) {
            return null;
        }

        $resource = \App\Filament\Resources\AuditHistory\AuditHistoryResource::class;

        if (! class_exists($resource)) {
            return null;
        }

        try {
            return $resource::getUrl('index', [
                'tableFilters' => [
                    'record' => [
                        'model_type' => $this->record::class,
                        'model_id'   => $this->record->getKey(),
                    ],
                ],
            ]);
        } catch (\Throwable) {
            return null;
        }
    }
}
