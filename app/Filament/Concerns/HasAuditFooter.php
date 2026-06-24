<?php

namespace App\Filament\Concerns;

use App\Filament\Widgets\RecordAuditWidget;

/**
 * Adds the "Record Information" audit footer (created/last-updated by + date)
 * to a Filament resource View page. Just `use HasAuditFooter;` on the page.
 */
trait HasAuditFooter
{
    protected function getFooterWidgets(): array
    {
        return [
            RecordAuditWidget::class,
        ];
    }

    public function getWidgetData(): array
    {
        return [
            'record' => $this->getRecord(),
        ];
    }
}
