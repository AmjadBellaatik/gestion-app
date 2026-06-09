<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Product;
use App\Models\Warehouse;
use Filament\Actions\Action;
use Filament\Forms;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

use Filament\Schemas\Schema;

class ProductForm
{
    public static function isAdminUser(): bool
    {
        return auth()->user()?->hasAnyRole([
            'Admin',
            'Super Admin',
        ]) ?? false;
    }

    public static function configure(
        Schema $schema
    ): Schema {

        return $schema
            ->components([

                TextInput::make('name')

                    ->label(
                        __('messages.product')
                    )

                    ->required(),

                TextInput::make('sku')

                    ->label(
                        __('messages.sku')
                    )
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText(__('messages.sku_managed_by_system')),

                TextInput::make('barcode')

                    ->label(
                        __('messages.barcode')
                    )

                    ->required()
                    ->suffixAction(
                        Action::make('generateBarcode')
                            ->label('Generate')
                            ->icon('heroicon-o-qr-code')
                            ->action(function (callable $set): void {
                                $set('barcode', self::generateBarcodeValue());
                            })
                    ),

                /*
                |--------------------------------------------------------------------------
                | Product Type
                |--------------------------------------------------------------------------
                */

                Forms\Components\Select::make(
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

                    ])

                    ->required()

                    ->searchable(),

                TextInput::make('purchase_price')

                    ->label(
                        __('messages.purchase_price')
                    )

                    ->numeric()

                    ->default(0)

                    ->visible(fn (): bool => self::isAdminUser())

                    ->dehydrated(fn (): bool => self::isAdminUser()),

                TextInput::make('selling_price')

                    ->label(
                        __('messages.customer_price')
                    )

                    ->numeric()

                    ->default(0),

                TextInput::make('reseller_price')

                    ->label(
                        __('messages.reseller_price')
                    )

                    ->numeric()

                    ->default(0),

                TextInput::make('initial_stock')

                    ->label(
                        __('messages.stock')
                    )

                    ->numeric()

                    ->minValue(0)

                    ->default(0)

                    ->dehydrated(false)

                    ->visible(fn ($record): bool => $record === null),

                Select::make('initial_warehouse_id')
                    ->label(__('messages.warehouse'))
                    ->options(fn () => Warehouse::active()->orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->preload()
                    ->required()
                    ->dehydrated(false)
                    ->visible(fn ($record): bool => $record === null),

                TextInput::make('stock_alert')

                    ->label(
                        __('messages.stock_alert')
                    )

                    ->numeric()

                    ->default(0),

                Forms\Components\Toggle::make('has_warranty')
                    ->label(__('messages.warranty'))
                    ->default(false),

            ]);
    }

    private static function generateBarcodeValue(): string
    {
        do {
            $barcode = '2' . str_pad((string) random_int(0, 999999999999), 12, '0', STR_PAD_LEFT);
        } while (Product::query()->where('barcode', $barcode)->exists());

        return $barcode;
    }
}
