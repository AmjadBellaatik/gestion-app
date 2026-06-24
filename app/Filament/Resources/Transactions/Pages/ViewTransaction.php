<?php

namespace App\Filament\Resources\Transactions\Pages;

use App\Filament\Concerns\HasAuditFooter;

use App\Filament\Resources\Transactions\TransactionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTransaction extends ViewRecord
{
    use HasAuditFooter;

    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [

            DeleteAction::make()

                ->visible(fn () => auth()->user()?->hasRole('Super Admin') || auth()->user()?->hasRole('Admin')),

        ];
    }
}
