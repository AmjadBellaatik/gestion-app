<?php

namespace App\Filament\Resources\Resellers\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SalesRelationManager extends RelationManager
{
    protected static string $relationship = 'sales';

    public static function getTitle(
        \Illuminate\Database\Eloquent\Model $ownerRecord,
        string $pageClass
    ): string {
        return __('messages.sales');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('sale_number')
                    ->label(__('messages.sale_number'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('total')
                    ->label(__('messages.total'))
                    ->money('MAD')
                    ->sortable(),

                TextColumn::make('paid_amount')
                    ->label(__('messages.paid_amount'))
                    ->money('MAD')
                    ->sortable(),

                TextColumn::make('remaining_amount')
                    ->label(__('messages.remaining_amount'))
                    ->money('MAD')
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success'),

                TextColumn::make('payment_status')
                    ->label(__('messages.payment_status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'paid'    => __('messages.paid'),
                        'partial' => __('messages.partial'),
                        'unpaid'  => __('messages.unpaid'),
                        default   => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'paid'    => 'success',
                        'partial' => 'warning',
                        'unpaid'  => 'danger',
                        default   => 'gray',
                    }),

                TextColumn::make('status')
                    ->label(__('messages.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'completed' => __('messages.completed'),
                        'pending'   => __('messages.pending'),
                        'cancelled' => __('messages.cancelled'),
                        default     => $state,
                    }),

                TextColumn::make('created_at')
                    ->label(__('messages.created_at'))
                    ->date()
                    ->sortable(),

            ])
            ->defaultSort('created_at', 'desc')
            ->actions([

                Action::make('view')
                    ->label(__('messages.view'))
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route('filament.admin.resources.sales.view', $record)),

            ])
            ->recordUrl(null);
    }
}
