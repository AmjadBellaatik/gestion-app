<?php

namespace App\Filament\Resources\Clients\RelationManagers;

use App\Filament\Resources\Warranties\WarrantyResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WarrantiesRelationManager extends RelationManager
{
    protected static string $relationship = 'warranties';

    public static function getTitle(
        \Illuminate\Database\Eloquent\Model $ownerRecord,
        string $pageClass
    ): string {
        return __('messages.warranties');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('sale.sale_number')
                    ->label(__('messages.sale'))
                    ->placeholder('-'),

                TextColumn::make('item_label')
                    ->label(__('messages.item'))
                    ->placeholder('-'),

                TextColumn::make('status')
                    ->label(__('messages.status'))
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'active'  => 'success',
                        'expired' => 'danger',
                        'claimed' => 'warning',
                        default   => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => __('messages.' . $state)),

                TextColumn::make('start_date')
                    ->label(__('messages.start_date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('end_date')
                    ->label(__('messages.end_date'))
                    ->date()
                    ->sortable(),

            ])
            ->defaultSort('start_date', 'desc')
            ->recordUrl(fn ($record) => WarrantyResource::getUrl('view', ['record' => $record]));
    }
}
