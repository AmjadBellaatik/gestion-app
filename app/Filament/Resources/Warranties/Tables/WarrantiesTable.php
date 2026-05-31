<?php

namespace App\Filament\Resources\Warranties\Tables;

use App\Models\Warranty;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WarrantiesTable
{
    public static function configure(Table $table): Table
    {
        return $table

            ->columns([

                // Chassis number for motorcycle units, product name + SKU for products
                TextColumn::make('item_label')
                    ->label(__('messages.item'))
                    ->state(fn (Warranty $record) => $record->item_label)
                    ->searchable(query: function ($query, string $search) {
                        $query->whereHas('motorcycleUnit', fn ($q) => $q->where('chassis_number', 'like', "%{$search}%"))
                            ->orWhereHas('product', fn ($q) => $q->where('name', 'like', "%{$search}%")
                                ->orWhere('sku', 'like', "%{$search}%"));
                    }),

                TextColumn::make('client.display_name')
                    ->label(__('messages.client'))
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('sale.sale_number')
                    ->label(__('messages.sale'))
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('start_date')
                    ->label(__('messages.start_date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('end_date')
                    ->label(__('messages.end_date'))
                    ->date()
                    ->sortable(),

                // Status derived from end_date via Warranty::getStatusAttribute
                TextColumn::make('status')
                    ->label(__('messages.status'))
                    ->badge()
                    ->state(fn (Warranty $record) => $record->status)
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'active'  => __('messages.active'),
                        'expired' => __('messages.expired'),
                        default   => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'active'  => 'success',
                        'expired' => 'danger',
                        default   => 'gray',
                    }),

                TextColumn::make('warranty_kilometers')
                    ->label(__('messages.warranty_distance'))
                    ->suffix(' km')
                    ->placeholder('—')
                    ->sortable(),

            ])

            ->filters([

                SelectFilter::make('status_filter')
                    ->label(__('messages.status'))
                    ->options([
                        'active'  => __('messages.active'),
                        'expired' => __('messages.expired'),
                    ])
                    ->query(function ($query, array $data) {
                        if (blank($data['value'])) {
                            return;
                        }

                        if ($data['value'] === 'expired') {
                            $query->whereDate('end_date', '<', now());
                        } else {
                            $query->whereDate('end_date', '>=', now());
                        }
                    }),

            ])

            ->actions([])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
