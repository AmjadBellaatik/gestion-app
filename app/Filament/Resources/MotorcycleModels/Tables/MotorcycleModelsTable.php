<?php

namespace App\Filament\Resources\MotorcycleModels\Tables;

use App\Models\Brand;
use App\Models\MotorcycleModel;
use App\Models\Scopes\CompanyScope;
use Filament\Tables\Table;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;

class MotorcycleModelsTable
{
    public static function configure(
        Table $table
    ): Table {

        return $table

            ->columns([

                TextColumn::make('marque')

                    ->label(
                        __('messages.brand')
                    )

                    ->getStateUsing(fn ($record) => $record->marque ?: $record->brand?->name)

                    ->searchable()

                    ->sortable()

                    ->placeholder('—'),

                TextColumn::make('modele')

                    ->label(
                        __('messages.model')
                    )

                    ->searchable()

                    ->sortable(),

                TextColumn::make('type')

                    ->label(
                        __('messages.type')
                    )

                    ->searchable()

                    ->sortable()

                    ->placeholder('—'),

                TextColumn::make('variante')

                    ->label(
                        __('messages.variante')
                    )

                    ->searchable()

                    ->sortable()

                    ->placeholder('—'),

                TextColumn::make('categorie')

                    ->label(
                        __('messages.category')
                    )

                    ->badge()

                    ->sortable(),

                TextColumn::make('carburant')

                    ->label(
                        __('messages.fuel')
                    )

                    ->badge()

                    ->sortable(),

                TextColumn::make('date_homologation')

                    ->label(
                        __('messages.homologation_date')
                    )

                    ->date()

                    ->sortable(),

                TextColumn::make('price_ttc')
                    ->label(__('messages.price_ttc'))
                    ->money('MAD')
                    ->sortable(),

                TextColumn::make('reseller_price')
                    ->label(__('messages.reseller_price'))
                    ->money('MAD')
                    ->sortable(),

                TextColumn::make('stock_alert')
                    ->label(__('messages.stock_alert'))
                    ->numeric()
                    ->sortable(),

                TextColumn::make('created_at')

                    ->label(
                        __('messages.created_at')
                    )

                    ->dateTime()

                    ->sortable(),

            ])

            ->filters([

                SelectFilter::make('brand_id')
                    ->label(__('messages.brand'))
                    ->options(
                        fn () => Brand::withoutGlobalScope(CompanyScope::class)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray()
                    )
                    ->searchable()
                    ->preload(),

                SelectFilter::make('categorie')
                    ->label(__('messages.category'))
                    ->options(
                        fn () => MotorcycleModel::query()
                            ->distinct()
                            ->whereNotNull('categorie')
                            ->orderBy('categorie')
                            ->pluck('categorie', 'categorie')
                            ->toArray()
                    ),

                SelectFilter::make('carburant')
                    ->label(__('messages.fuel'))
                    ->options(
                        fn () => MotorcycleModel::query()
                            ->distinct()
                            ->whereNotNull('carburant')
                            ->orderBy('carburant')
                            ->pluck('carburant', 'carburant')
                            ->toArray()
                    ),

                SelectFilter::make('type')
                    ->label(__('messages.type'))
                    ->options(
                        fn () => MotorcycleModel::query()
                            ->distinct()
                            ->whereNotNull('type')
                            ->orderBy('type')
                            ->pluck('type', 'type')
                            ->toArray()
                    )
                    ->searchable(),

                SelectFilter::make('genre')
                    ->label(__('messages.genre'))
                    ->options(
                        fn () => MotorcycleModel::query()
                            ->distinct()
                            ->whereNotNull('genre')
                            ->orderBy('genre')
                            ->pluck('genre', 'genre')
                            ->toArray()
                    ),

                Filter::make('date_homologation')
                    ->label(__('messages.homologation_date'))
                    ->form([
                        DatePicker::make('from')
                            ->label(__('messages.from')),
                        DatePicker::make('until')
                            ->label(__('messages.until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'],  fn ($q) => $q->whereDate('date_homologation', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('date_homologation', '<=', $data['until']));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = __('messages.from') . ': ' . $data['from'];
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = __('messages.until') . ': ' . $data['until'];
                        }
                        return $indicators;
                    }),

                Filter::make('price_range')
                    ->label(__('messages.price_ttc'))
                    ->form([
                        \Filament\Forms\Components\TextInput::make('price_from')
                            ->label(__('messages.from'))
                            ->numeric()
                            ->prefix('MAD'),
                        \Filament\Forms\Components\TextInput::make('price_to')
                            ->label(__('messages.until'))
                            ->numeric()
                            ->prefix('MAD'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['price_from'], fn ($q) => $q->where('price_ttc', '>=', $data['price_from']))
                            ->when($data['price_to'],   fn ($q) => $q->where('price_ttc', '<=', $data['price_to']));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['price_from'] ?? null) {
                            $indicators[] = __('messages.from') . ': MAD ' . $data['price_from'];
                        }
                        if ($data['price_to'] ?? null) {
                            $indicators[] = __('messages.until') . ': MAD ' . $data['price_to'];
                        }
                        return $indicators;
                    }),

                Filter::make('low_stock')
                    ->label(__('messages.low_stock'))
                    ->query(fn (Builder $query) => $query->where('stock_alert', '>', 0))
                    ->toggle(),

            ])

            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
