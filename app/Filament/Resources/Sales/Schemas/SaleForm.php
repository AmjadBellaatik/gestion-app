<?php

namespace App\Filament\Resources\Sales\Schemas;

use App\Models\Client;
use App\Models\DocumentType;
use App\Models\MotorcycleUnit;
use App\Models\Product;
use App\Models\Reseller;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SaleForm
{
    public static function configure(
        Schema $schema
    ): Schema {

        return $schema

            ->components([

                /*
                |--------------------------------------------------------------------------
                | CLIENTS
                |--------------------------------------------------------------------------
                */

                Section::make(
                    __('messages.client_information')
                )

                    ->schema([

                        Grid::make(2)

                            ->schema([

                                Select::make(
                                    'client_id'
                                )

                                    ->label(
                                        __('messages.client')
                                    )

                                    ->options(fn () => Client::query()
                                        ->where('is_active', true)
                                        ->where('is_blocked', false)
                                        ->get()
                                        ->pluck('display_name', 'id')
                                        ->filter(fn ($v) => filled($v))
                                        ->toArray())

                                    ->searchable()

                                    ->preload()
                                    ->required(fn ($get) => blank($get('reseller_id')))
                                    ->hidden(fn ($get) => filled($get('reseller_id')))
                                    ->live()
                                    ->createOptionForm([
                                        Select::make('client_type')
                                            ->label(__('messages.client_type'))
                                            ->options([
                                                'person'         => __('messages.person'),
                                                'company'        => __('messages.company'),
                                                'administration' => __('messages.administration'),
                                            ])
                                            ->default('person')
                                            ->live()
                                            ->required(),
                                        TextInput::make('first_name')
                                            ->label(__('messages.first_name'))
                                            ->visible(fn ($get) => ($get('client_type') ?? 'person') === 'person')
                                            ->required(fn ($get) => ($get('client_type') ?? 'person') === 'person'),
                                        TextInput::make('last_name')
                                            ->label(__('messages.last_name'))
                                            ->visible(fn ($get) => ($get('client_type') ?? 'person') === 'person'),
                                        TextInput::make('company_name')
                                            ->label(__('messages.company_name'))
                                            ->visible(fn ($get) => $get('client_type') === 'company')
                                            ->required(fn ($get) => $get('client_type') === 'company'),
                                        TextInput::make('administration_name')
                                            ->label(__('messages.administration_name'))
                                            ->visible(fn ($get) => $get('client_type') === 'administration')
                                            ->required(fn ($get) => $get('client_type') === 'administration'),
                                        TextInput::make('phone')->label(__('messages.phone'))->tel(),
                                        TextInput::make('email')->label(__('messages.email'))->email(),
                                    ])
                                    ->createOptionUsing(fn (array $data) => Client::create(array_merge(['is_active' => true, 'is_blocked' => false], $data))->id),

                                Select::make(
                                    'reseller_id'
                                )

                                    ->label(
                                        __('messages.reseller')
                                    )

                                    ->options(
                                        fn () => Reseller::query()
                                            ->where('is_active', true)
                                            ->where('is_blocked', false)
                                            ->pluck('name', 'id')
                                            ->toArray()
                                    )

                                    ->searchable()

                                    ->preload()
                                    ->required(fn ($get) => blank($get('client_id')))
                                    ->hidden(fn ($get) => filled($get('client_id')))
                                    ->live()
                                    ->createOptionForm([
                                        TextInput::make('name')->label(__('messages.name'))->required(),
                                        TextInput::make('phone')->label(__('messages.phone'))->tel(),
                                        TextInput::make('email')->label(__('messages.email'))->email(),
                                        TextInput::make('address')->label(__('messages.address')),
                                    ])
                                    ->createOptionUsing(fn (array $data) => Reseller::create($data)->id),

                            ]),

                    ]),

                /*
                |--------------------------------------------------------------------------
                | PRODUCTS
                |--------------------------------------------------------------------------
                */

                Section::make(
                    __('messages.products')
                )

                    ->schema([

                        Repeater::make(
                            'saleItems'
                        )

                            ->schema([

                                Grid::make(4)

                                    ->schema([
                                        Select::make('item_type')
                                            ->label(__('messages.item_type'))
                                            ->options([
                                                'product' => __('messages.product'),
                                                'motorcycle' => __('messages.motorcycle'),
                                                'trotinette' => __('messages.trotinette'),
                                                'velo_electrique' => __('messages.velo_electrique'),
                                                'velo_normal' => __('messages.velo_normal'),
                                            ])
                                            ->default('product')
                                            ->live()
                                            ->afterStateUpdated(function ($state, callable $set): void {
                                                $set('product_id', null);
                                                $set('motorcycle_unit_id', null);
                                            })
                                            ->required(),

                                        Select::make(
                                            'product_id'
                                        )

                                            ->label(
                                                __('messages.product')
                                            )

                                            ->options(fn ($get) =>

                                                Product::query()
                                                    ->when(
                                                        in_array($get('item_type'), self::warrantyProductTypes(), true),
                                                        fn ($query) => $query->where('type', $get('item_type')),
                                                        fn ($query) => $query->whereNotIn('type', self::warrantyProductTypes())
                                                            ->where('type', '!=', 'service')
                                                    )
                                                    ->orderBy('name')
                                                    ->get()
                                                    ->filter(fn (Product $p) => $p->current_stock > 0)
                                                    ->pluck('name', 'id')

                                            )

                                            ->searchable()
                                            ->visible(fn ($get) => ($get('item_type') ?? 'product') !== 'motorcycle')
                                            ->required(fn ($get) => ($get('item_type') ?? 'product') !== 'motorcycle')

                                            ->searchable()

                                            ->preload()

                                            ->live()

                                            ->afterStateUpdated(function ($state, callable $get, callable $set): void {
                                                if (! $state) {
                                                    $set('unit_price', 0);

                                                    return;
                                                }

                                                $product = Product::query()->find($state);

                                                if (! $product) {
                                                    return;
                                                }

                                                $set(
                                                    'unit_price',
                                                    self::resolveProductPrice(
                                                        $product,
                                                        filled($get('../../reseller_id'))
                                                    )
                                                );
                                            }),

                                        Select::make('motorcycle_unit_id')
                                            ->label(__('messages.motorcycle_unit'))
                                            ->options(fn () => MotorcycleUnit::query()
                                                ->with('motorcycleModel')
                                                ->whereIn('status', ['available', 'in_stock'])
                                                ->orderByDesc('id')
                                                ->get()
                                                ->mapWithKeys(fn (MotorcycleUnit $unit) => [
                                                    $unit->id => trim(($unit->motorcycleModel?->modele ?? __('messages.motorcycle')) . ' - ' . $unit->chassis_number),
                                                ])
                                                ->toArray())
                                            ->visible(fn ($get) => $get('item_type') === 'motorcycle')
                                            ->required(fn ($get) => $get('item_type') === 'motorcycle')
                                            ->live()
                                            ->afterStateUpdated(function ($state, callable $get, callable $set): void {
                                                if (! $state) {
                                                    return;
                                                }

                                                $unit = MotorcycleUnit::query()
                                                    ->with('motorcycleModel')
                                                    ->find($state);

                                                if (! $unit) {
                                                    return;
                                                }

                                                $set(
                                                    'unit_price',
                                                    self::resolveMotorcycleModelPrice(
                                                        $unit->motorcycleModel,
                                                        filled($get('../../reseller_id'))
                                                    )
                                                );
                                            })
                                            ->searchable()
                                            ->preload(),

                                        TextInput::make(
                                            'quantity'
                                        )

                                            ->numeric()

                                            ->default(1)
                                            ->minValue(1)
                                            ->disabled(fn ($get) => $get('item_type') === 'motorcycle')
                                            ->helperText(function ($get) {
                                                $productId = $get('product_id');
                                                if (! $productId || $get('item_type') === 'motorcycle') {
                                                    return null;
                                                }
                                                $product = Product::query()->find($productId);
                                                if (! $product) {
                                                    return null;
                                                }
                                                $stock = (float) $product->current_stock;
                                                return __('messages.available_stock') . ': ' . number_format($stock, 2);
                                            })
                                            ->rules([
                                                fn ($get) => function ($attribute, $value, $fail) use ($get) {
                                                    if ($get('item_type') === 'motorcycle') {
                                                        return;
                                                    }
                                                    $productId = $get('product_id');
                                                    if (! $productId) {
                                                        return;
                                                    }
                                                    $product = Product::query()->find($productId);
                                                    if (! $product) {
                                                        return;
                                                    }
                                                    $stock = (float) $product->current_stock;
                                                    if ((float) $value > $stock) {
                                                        $fail(__('messages.quantity_exceeds_stock', ['available' => number_format($stock, 2)]));
                                                    }
                                                },
                                            ])
                                            ->required(),

                                    ]),

                                Grid::make(4)

                                    ->schema([

                                        TextInput::make('unit_price')
                                            ->label(__('messages.unit_price'))
                                            ->numeric()
                                            ->default(0)
                                            ->readOnly()
                                            ->suffix('MAD'),

                                        TextInput::make('warranty_duration_value')
                                            ->label(__('messages.warranty_duration'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->visible(fn ($get) => self::requiresWarranty($get))
                                            ->required(fn ($get) => self::requiresWarranty($get)),

                                        Select::make('warranty_duration_unit')
                                            ->label(__('messages.warranty_duration_unit'))
                                            ->options([
                                                'weeks' => __('messages.weeks'),
                                                'months' => __('messages.months'),
                                                'years' => __('messages.years'),
                                            ])
                                            ->default('years')
                                            ->visible(fn ($get) => self::requiresWarranty($get))
                                            ->required(fn ($get) => self::requiresWarranty($get)),

                                        TextInput::make('warranty_kilometers')
                                            ->label(__('messages.warranty_distance'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->suffix('KM')
                                            ->visible(fn ($get) => self::requiresWarranty($get))
                                            ->required(fn ($get) => self::requiresWarranty($get)),

                                    ]),

                            ])

                            ->defaultItems(1),

                    ]),

                /*
                |--------------------------------------------------------------------------
                | PAYMENT
                |--------------------------------------------------------------------------
                */

                Section::make(
                    __('messages.payment')
                )

                    ->schema([

                        Grid::make(3)

                            ->schema([

                                TextInput::make('paid_amount')
                                    ->label(__('messages.paid_amount'))
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->suffix('MAD'),

                                Select::make('payment_method')
                                    ->label(__('messages.payment_method'))
                                    ->options([
                                        'cash'          => __('messages.cash'),
                                        'card'          => __('messages.card'),
                                        'cheque'        => __('messages.cheque'),
                                        'bank_transfer' => __('messages.bank_transfer'),
                                    ])
                                    ->default('cash')
                                    ->live()
                                    ->required()
                                    ->afterStateUpdated(fn ($state, callable $set) => $set('reference', null)),

                                TextInput::make('reference')
                                    ->label(__('messages.reference'))
                                    ->visible(fn ($get) => ! \in_array($get('payment_method'), ['cash', 'cheque'], true))
                                    ->required(fn ($get) => $get('payment_method') === 'bank_transfer')
                                    ->maxLength(100),

                            ]),

                    ]),

                /*
                |--------------------------------------------------------------------------
                | CHEQUE DETAILS (shown only when payment_method = cheque)
                |--------------------------------------------------------------------------
                */

                Section::make(__('messages.cheque_information'))
                    ->visible(fn ($get) => $get('payment_method') === 'cheque')
                    ->schema([

                        Grid::make(3)
                            ->schema([

                                TextInput::make('cheque_number')
                                    ->label(__('messages.cheque_number'))
                                    ->required()
                                    ->maxLength(100),

                                Select::make('bank_name')
                                    ->label(__('messages.bank_name'))
                                    ->options([
                                        'Attijariwafa Bank'              => 'Attijariwafa Bank',
                                        'Banque Centrale Populaire (BCP)' => 'Banque Centrale Populaire (BCP)',
                                        'Bank of Africa (BOA)'           => 'Bank of Africa (BOA)',
                                        'CIH Bank'                       => 'CIH Bank',
                                        'Al Barid Bank'                  => 'Al Barid Bank',
                                        'Crédit Agricole du Maroc'       => 'Crédit Agricole du Maroc',
                                        'Crédit du Maroc'                => 'Crédit du Maroc',
                                        'BMCI'                           => 'BMCI',
                                        'CFG Bank'                       => 'CFG Bank',
                                        'Saham Bank'                     => 'Saham Bank',
                                        'Umnia Bank'                     => 'Umnia Bank',
                                        'Bank Assafa'                    => 'Bank Assafa',
                                        'Bank Al Yousr'                  => 'Bank Al Yousr',
                                        'Al Akhdar Bank'                 => 'Al Akhdar Bank',
                                        'Bank Al-Tamweel wal-Inma'       => 'Bank Al-Tamweel wal-Inma',
                                    ])
                                    ->searchable()
                                    ->required(),

                                \Filament\Forms\Components\DatePicker::make('cheque_due_date')
                                    ->label(__('messages.due_date'))
                                    ->required()
                                    ->minDate(today()),

                            ]),

                    ]),

                /*
                |--------------------------------------------------------------------------
                | BANK TRANSFER DETAILS (shown only when payment_method = bank_transfer)
                |--------------------------------------------------------------------------
                */

                Section::make(__('messages.bank_transfer_information'))
                    ->visible(fn ($get) => $get('payment_method') === 'bank_transfer')
                    ->schema([

                        Grid::make(3)
                            ->schema([

                                TextInput::make('bank_name')
                                    ->label(__('messages.bank_name'))
                                    ->maxLength(150),

                                TextInput::make('transfer_reference')
                                    ->label(__('messages.reference_number'))
                                    ->maxLength(100),

                                \Filament\Forms\Components\DatePicker::make('transfer_date')
                                    ->label(__('messages.transfer_date'))
                                    ->default(today()),

                            ]),

                    ]),

                /*
                |--------------------------------------------------------------------------
                | DISCOUNT — Admin / Super Admin only
                |--------------------------------------------------------------------------
                */

                Section::make(__('messages.discount'))
                    ->icon('heroicon-o-tag')
                    ->iconColor('warning')
                    ->visible(fn () => auth()->user()?->hasAnyRole(['Admin', 'Super Admin']))
                    ->schema([

                        Grid::make(2)
                            ->schema([

                                TextInput::make('discount')
                                    ->label(__('messages.discount_amount'))
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->suffix('MAD')
                                    ->live()
                                    ->helperText(__('messages.discount_reduces_total')),

                                TextInput::make('discount_note')
                                    ->label(__('messages.discount_note'))
                                    ->placeholder(__('messages.discount_note_placeholder'))
                                    ->maxLength(255)
                                    ->visible(fn ($get) => (float) ($get('discount') ?? 0) > 0),

                            ]),

                    ]),

                /*
                |--------------------------------------------------------------------------
                | NOTES
                |--------------------------------------------------------------------------
                */

                Section::make(
                    __('messages.notes')
                )

                    ->schema([

                        Textarea::make(
                            'notes'
                        )

                            ->rows(4),

                    ]),

                Section::make(__('messages.purchase_order'))
                    ->visible(fn ($get) => \App\Models\Client::query()
                        ->whereKey($get('client_id'))
                        ->whereIn('client_type', ['company', 'administration'])
                        ->exists()
                    )
                    ->schema([
                        TextInput::make('purchase_order_number')
                            ->label(__('messages.purchase_order'))
                            ->maxLength(100)
                            ->placeholder('BC-2026-XXXX'),
                    ]),

                Section::make(__('messages.documents'))
                    ->schema([
                        CheckboxList::make('auto_document_codes')
                            ->label(__('messages.documents'))
                            ->options([
                                DocumentType::INVOICE => 'Facture',
                                DocumentType::DELIVERY_NOTE => 'Bon de livraison',
                                DocumentType::WARRANTY_CONTRACT => 'Contrat de garantie',
                                DocumentType::CONFORMITY => 'Certificat de conformite',
                            ])
                            ->columns(2)
                            ->default([
                                DocumentType::INVOICE,
                            ]),
                    ]),

            ]);

    }

    private static function requiresWarranty(callable $get): bool
    {
        if ($get('item_type') === 'motorcycle') {
            return true;
        }

        $productId = $get('product_id');

        return filled($productId)
            && Product::query()->whereKey($productId)->where('has_warranty', true)->exists();
    }

    private static function warrantyProductTypes(): array
    {
        return [
            'trotinette',
            'velo_electrique',
            'velo_normal',
        ];
    }

    private static function resolveProductPrice(Product $product, bool $hasReseller): float
    {
        if ($hasReseller && (float) $product->reseller_price > 0) {
            return (float) $product->reseller_price;
        }

        return (float) $product->selling_price;
    }

    private static function resolveMotorcycleModelPrice(?\App\Models\MotorcycleModel $model, bool $hasReseller): float
    {
        if (! $model) {
            return 0.0;
        }

        if ($hasReseller && (float) $model->reseller_price > 0) {
            return (float) $model->reseller_price;
        }

        return (float) $model->price_ttc;
    }
}
