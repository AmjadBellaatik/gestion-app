<?php

namespace App\Filament\Resources\Products\Tables;

use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Filament\Resources\Stock\Support\StockActions;
use App\Models\Product;
use App\Models\User;
use App\Notifications\GenericNotification;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ProductsTable
{
    public static function configure(
        Table $table
    ): Table {

        return $table

            ->columns([

                TextColumn::make('name')

                    ->label(
                        __('messages.name')
                    )

                    ->searchable()

                    ->sortable(),

                TextColumn::make('sku')

                    ->label(
                        __('messages.sku')
                    )

                    ->searchable()

                    ->sortable(),

                TextColumn::make('type')

                    ->label(
                        __('messages.type')
                    )

                    ->badge()

                    ->color(fn ($state) => match ($state) {

                        'part' => 'primary',

                        'accessory' => 'warning',

                        'trotinette' => 'info',

                        'velo_electrique' => 'danger',

                        'velo_normal' => 'gray',

                        'consumable' => 'secondary',

                        default => 'gray',

                    })

                    ->formatStateUsing(
                        fn ($state) => match ($state) {

                            'part' =>
                                __('messages.part'),

                            'accessory' =>
                                __('messages.accessory'),

                            'trotinette' =>
                                __('messages.trotinette'),

                            'velo_electrique' =>
                                __('messages.velo_electrique'),

                            'velo_normal' =>
                                __('messages.velo_normal'),

                            'consumable' =>
                                __('messages.consumable'),

                            default => $state,

                        }
                    ),

                TextColumn::make('purchase_price')

                    ->label(
                        __('messages.purchase_price')
                    )

                    ->money('MAD')

                    ->sortable()

                    ->visible(fn (): bool => ProductForm::isAdminUser()),

                TextColumn::make('selling_price')

                    ->label(
                        __('messages.customer_price')
                    )

                    ->money('MAD')

                    ->sortable(),

                TextColumn::make('reseller_price')

                    ->label(
                        __('messages.reseller_price')
                    )

                    ->money('MAD')

                    ->sortable(),

                TextColumn::make('current_stock')

                    ->label(
                        __('messages.stock')
                    )

                    ->numeric(),

                TextColumn::make('stock_alert')

                    ->label(
                        __('messages.stock_alert')
                    )

                    ->numeric()

                    ->sortable(),

                TextColumn::make('created_at')

                    ->label(
                        __('messages.created_at')
                    )

                    ->dateTime()

                    ->sortable()

                    ->toggleable(
                        isToggledHiddenByDefault: true
                    ),

                TextColumn::make('updated_at')

                    ->label(
                        __('messages.updated_at')
                    )

                    ->dateTime()

                    ->sortable()

                    ->toggleable(
                        isToggledHiddenByDefault: true
                    ),

            ])

            ->filters([

                TrashedFilter::make(),

                Tables\Filters\SelectFilter::make(
                    'type'
                )

                    ->label(
                        __('messages.type')
                    )

                    ->options([

                        'part' =>
                            __('messages.part'),

                        'accessory' =>
                            __('messages.accessory'),

                        'trotinette' =>
                            __('messages.trotinette'),

                        'velo_electrique' =>
                            __('messages.velo_electrique'),

                        'velo_normal' =>
                            __('messages.velo_normal'),

                        'consumable' =>
                            __('messages.consumable'),

                    ]),

            ])

            ->actions([
                ViewAction::make(),

                EditAction::make(),

                DeleteAction::make()
                    ->visible(fn () => ProductForm::isAdminUser())
                    ->requiresConfirmation(),

                Action::make('add_stock')
                    ->label(__('messages.add_stock'))
                    ->icon('heroicon-o-plus')
                    ->form(fn (Product $record): array => StockActions::productMovementForm($record->id))
                    ->action(fn (array $data) => StockActions::addProductStock($data)),

                Action::make('adjust_stock')
                    ->label(__('messages.adjust_stock'))
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->visible(fn (): bool => StockActions::canAdjust())
                    ->form(fn (Product $record): array => StockActions::productAdjustmentForm($record->id))
                    ->action(fn (array $data) => StockActions::adjustProductStock($data)),

            ])

            ->toolbarActions([

                BulkActionGroup::make([

                    DeleteBulkAction::make()
                        ->visible(fn () => ProductForm::isAdminUser()),

                    ForceDeleteBulkAction::make()
                        ->visible(fn () => ProductForm::isAdminUser()),

                    RestoreBulkAction::make()
                        ->visible(fn () => ProductForm::isAdminUser()),

                    BulkAction::make('request_bulk_deletion')
                        ->label(__('messages.request_deletion'))
                        ->icon('heroicon-o-trash')
                        ->color('warning')
                        ->visible(fn () => ! ProductForm::isAdminUser())
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $admins = User::role(['Admin', 'Super Admin'])->where('status', true)->get();
                            $notification = new GenericNotification(
                                __('messages.request_deletion'),
                                'Bulk deletion requested for ' . $records->count() . ' product(s).'
                            );
                            foreach ($admins as $admin) {
                                try { $admin->notify($notification); } catch (\Throwable) {}
                            }
                            Notification::make()
                                ->title(__('messages.deletion_requested'))
                                ->success()
                                ->send();
                        }),

                ]),

            ]);
    }
}
