<?php

namespace App\Filament\Resources\Resellers\RelationManagers;

use App\Filament\Resources\Payments\PaymentResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    public static function getTitle(
        \Illuminate\Database\Eloquent\Model $ownerRecord,
        string $pageClass
    ): string {
        return __('messages.payments');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('sale.sale_number')
                    ->label(__('messages.sale'))
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('amount')
                    ->label(__('messages.amount'))
                    ->money('MAD')
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->label(__('messages.payment_method'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('messages.' . $state)),

                TextColumn::make('status')
                    ->label(__('messages.status'))
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'paid'               => 'success',
                        'received'           => 'info',
                        'pending_validation' => 'warning',
                        'pending'            => 'warning',
                        'bounced'            => 'danger',
                        'cancelled'          => 'danger',
                        default              => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label(__('messages.date'))
                    ->date()
                    ->sortable(),

            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn ($record) => PaymentResource::getUrl('view', ['record' => $record]));
    }
}
