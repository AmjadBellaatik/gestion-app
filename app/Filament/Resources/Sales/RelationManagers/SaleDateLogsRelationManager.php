<?php

namespace App\Filament\Resources\Sales\RelationManagers;

use App\Filament\Resources\Sales\SaleResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Read-only audit trail of sale_date changes for a sale.
 * Visible to Admin / Super Admin only. Records are created automatically by
 * the Sale model observer and can never be edited or deleted from here.
 */
class SaleDateLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'saleDateLogs';

    protected static ?string $title = null;

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return SaleResource::isAdminUser();
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('messages.sale_date_history');
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('changed_at', 'desc')
            ->columns([
                TextColumn::make('changed_at')
                    ->label(__('messages.changed_at'))
                    ->dateTime('d/m/Y H:i'),

                TextColumn::make('user_name')
                    ->label(__('messages.changed_by'))
                    ->placeholder('-'),

                TextColumn::make('old_date')
                    ->label(__('messages.old_date'))
                    ->date('d/m/Y')
                    ->placeholder('-'),

                TextColumn::make('new_date')
                    ->label(__('messages.new_date'))
                    ->date('d/m/Y'),
            ])
            ->paginated([10, 25])
            // Fully read-only — no create/edit/delete actions.
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
