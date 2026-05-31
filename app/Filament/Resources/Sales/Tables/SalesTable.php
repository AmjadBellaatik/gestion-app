<?php

namespace App\Filament\Resources\Sales\Tables;

use App\Filament\Resources\Sales\SaleResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class SalesTable
{
    public static function configure(
        Table $table
    ): Table {

        return $table

            ->columns([

                TextColumn::make('sale_number')

                    ->label(
                        __('messages.reference')
                    )

                    ->searchable()

                    ->sortable(),

                TextColumn::make('client.display_name')

                    ->label(
                        __('messages.client')
                    )

                    ->getStateUsing(fn ($record) => $record->client?->display_name ?? $record->reseller?->name ?? '-')

                    ->searchable(false)

                    ->sortable(false),

                TextColumn::make('total')

                    ->label(
                        __('messages.total_amount')
                    )

                    ->money('MAD')

                    ->sortable(),

                TextColumn::make('paid_amount')

                    ->label(
                        __('messages.paid_amount')
                    )

                    ->money('MAD')

                    ->sortable(),

                TextColumn::make('remaining_amount')

                    ->label(
                        __('messages.remaining_amount')
                    )

                    ->money('MAD')

                    ->sortable(),

                TextColumn::make('payment_status')

                    ->label(
                        __('messages.payment_status')
                    )

                    ->badge()

                    ->color(fn ($state) => match ($state) {

                        'paid'    => 'success',
                        'partial' => 'warning',
                        'unpaid'  => 'danger',

                        default => 'gray',

                    })

                    ->formatStateUsing(
                        fn ($state) => match ($state) {

                            'paid'    => __('messages.paid'),
                            'partial' => __('messages.partial'),
                            'unpaid'  => __('messages.unpaid'),

                            default => $state,

                        }
                    ),

                TextColumn::make('created_at')

                    ->label(
                        __('messages.sale_date')
                    )

                    ->date()

                    ->sortable(),

            ])

            ->filters([

                TrashedFilter::make(),

            ])

            ->actions([
                ViewAction::make(),

                EditAction::make(),

                DeleteAction::make()
                    ->visible(fn () => SaleResource::isAdminUser()),

            ])

            ->toolbarActions([

                BulkActionGroup::make([

                    DeleteBulkAction::make()
                        ->visible(fn () => SaleResource::isAdminUser()),

                    ForceDeleteBulkAction::make()
                        ->visible(fn () => SaleResource::isAdminUser()),

                    RestoreBulkAction::make(),

                ])
                    ->visible(fn () => SaleResource::isAdminUser()),

            ]);
    }
}
