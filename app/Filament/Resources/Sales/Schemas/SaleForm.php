<?php

namespace App\Filament\Resources\Sales\Schemas;

use App\Models\Client;
use App\Models\DocumentType;
use App\Models\MotorcycleUnit;
use App\Models\Product;
use App\Models\Reseller;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
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

                                        // Person fields
                                        TextInput::make('first_name')
                                            ->label(__('messages.first_name'))
                                            ->visible(fn ($get) => ($get('client_type') ?? 'person') === 'person')
                                            ->required(fn ($get) => ($get('client_type') ?? 'person') === 'person'),
                                        TextInput::make('last_name')
                                            ->label(__('messages.last_name'))
                                            ->visible(fn ($get) => ($get('client_type') ?? 'person') === 'person')
                                            ->required(fn ($get) => ($get('client_type') ?? 'person') === 'person'),
                                        TextInput::make('cin')
                                            ->label(__('messages.national_id'))
                                            ->visible(fn ($get) => ($get('client_type') ?? 'person') === 'person'),

                                        // Company fields
                                        TextInput::make('company_name')
                                            ->label(__('messages.company_name'))
                                            ->visible(fn ($get) => $get('client_type') === 'company')
                                            ->required(fn ($get) => $get('client_type') === 'company'),
                                        TextInput::make('ice')
                                            ->label(__('messages.ice'))
                                            ->visible(fn ($get) => $get('client_type') === 'company')
                                            ->required(fn ($get) => $get('client_type') === 'company'),
                                        TextInput::make('rc')
                                            ->label(__('messages.rc'))
                                            ->visible(fn ($get) => $get('client_type') === 'company'),
                                        TextInput::make('if')
                                            ->label(__('messages.if'))
                                            ->visible(fn ($get) => $get('client_type') === 'company'),
                                        TextInput::make('representative_name')
                                            ->label(__('messages.representative_name'))
                                            ->visible(fn ($get) => $get('client_type') === 'company'),

                                        // Administration fields
                                        TextInput::make('administration_name')
                                            ->label(__('messages.administration_name'))
                                            ->visible(fn ($get) => $get('client_type') === 'administration')
                                            ->required(fn ($get) => $get('client_type') === 'administration'),
                                        TextInput::make('department')
                                            ->label(__('messages.department'))
                                            ->visible(fn ($get) => $get('client_type') === 'administration'),
                                        TextInput::make('responsible_person')
                                            ->label(__('messages.responsible_person'))
                                            ->visible(fn ($get) => $get('client_type') === 'administration'),

                                        // Common fields
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

                    ])
                    ->columnSpanFull(),

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
                                            ->afterStateUpdated(function ($state, callable $get, callable $set): void {
                                                $set('product_id', null);
                                                $set('motorcycle_unit_id', null);
                                                $set('quantity', 1);
                                                $set('unit_price', 0);
                                                self::syncPaidAmount($get, $set, '../../paid_amount', '../../saleItems', '../../discount', '../../paid_amount');
                                            })
                                            ->required()
                                            ->columnSpan(1),

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
                                                    self::syncPaidAmount($get, $set, '../../paid_amount', '../../saleItems', '../../discount', '../../paid_amount');

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
                                                self::syncPaidAmount($get, $set, '../../paid_amount', '../../saleItems', '../../discount', '../../paid_amount');
                                            })
                                            ->columnSpan(2),

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
                                                $set('quantity', 1);
                                                self::syncPaidAmount($get, $set, '../../paid_amount', '../../saleItems', '../../discount', '../../paid_amount');
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->columnSpan(2),

                                        TextInput::make(
                                            'quantity'
                                        )

                                            ->numeric()

                                            ->default(1)
                                            ->minValue(1)
                                            ->live()
                                            ->afterStateUpdated(fn ($state, callable $get, callable $set) => self::syncPaidAmount($get, $set, '../../paid_amount', '../../saleItems', '../../discount', '../../paid_amount'))
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
                                            ->required()
                                            ->columnSpan(1),

                                    ]),

                                Grid::make(4)

                                    ->schema([

                                        TextInput::make('unit_price')
                                            ->label(__('messages.unit_price'))
                                            ->numeric()
                                            ->default(0)
                                            ->readOnly()
                                            ->suffix('MAD')
                                            ->columnSpan(1),

                                        TextInput::make('warranty_duration_value')
                                            ->label(__('messages.warranty_duration'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->visible(fn ($get) => self::requiresWarranty($get))
                                            ->required(fn ($get) => self::requiresWarranty($get))
                                            ->columnSpan(1),

                                        Select::make('warranty_duration_unit')
                                            ->label(__('messages.warranty_duration_unit'))
                                            ->options([
                                                'weeks' => __('messages.weeks'),
                                                'months' => __('messages.months'),
                                                'years' => __('messages.years'),
                                            ])
                                            ->default('years')
                                            ->visible(fn ($get) => self::requiresWarranty($get))
                                            ->required(fn ($get) => self::requiresWarranty($get))
                                            ->columnSpan(1),

                                        TextInput::make('warranty_kilometers')
                                            ->label(__('messages.warranty_distance'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->suffix('KM')
                                            ->visible(fn ($get) => self::requiresWarranty($get))
                                            ->required(fn ($get) => self::requiresWarranty($get))
                                            ->columnSpan(1),

                                    ]),

                            ])

                            ->defaultItems(1)
                            ->live()
                            ->columns(1)
                            ->columnSpanFull(),

                    ])
                    ->columnSpanFull(),

                Section::make('Live total')
                    ->icon('heroicon-o-calculator')
                    ->schema([

                        Grid::make(4)
                            ->schema([

                                Placeholder::make('items_total_preview')
                                    ->label('Total before reduction (TTC)')
                                    ->content(fn ($get): string => self::formatMoney(self::calculateSaleTotals($get)['gross']))
                                    ->columnSpan(1),

                                Placeholder::make('discount_preview')
                                    ->label(__('messages.discount_amount'))
                                    ->content(fn ($get): string => self::formatMoney(self::calculateSaleTotals($get)['discount']))
                                    ->columnSpan(1),

                                Placeholder::make('tax_preview')
                                    ->label('TVA included')
                                    ->content(fn ($get): string => self::formatMoney(self::calculateSaleTotals($get)['tax']))
                                    ->helperText('Product prices already include TVA.')
                                    ->columnSpan(1),

                                Placeholder::make('final_total_preview')
                                    ->label('Final price (TTC)')
                                    ->content(fn ($get): string => self::formatMoney(self::calculateSaleTotals($get)['net']))
                                    ->columnSpan(1),

                                Placeholder::make('remaining_preview')
                                    ->label(__('messages.remaining_amount'))
                                    ->content(fn ($get): string => self::formatMoney(self::calculateSaleTotals($get)['remaining']))
                                    ->columnSpan(1),

                            ]),

                    ])
                    ->columnSpanFull(),

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
                                    ->live()
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
                                    ->afterStateUpdated(fn ($state, callable $get, callable $set) => self::syncPaidAmount($get, $set))
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
                            ->live()
                            ->options(function (\Filament\Schemas\Components\Utilities\Get $get): array {
                                $options = [
                                    DocumentType::INVOICE          => 'Facture',
                                    DocumentType::DELIVERY_NOTE    => 'Bon de livraison',
                                ];

                                if (self::hasMotorcycleItem($get)) {
                                    $options[DocumentType::CONFORMITY] = 'Certificat de conformite';
                                }

                                $hasWarrantyItem = collect($get('saleItems') ?? [])
                                    ->some(function (array $item): bool {
                                        if (($item['item_type'] ?? null) === 'motorcycle') {
                                            return true;
                                        }
                                        $productId = $item['product_id'] ?? null;
                                        return $productId
                                            && Product::whereKey($productId)
                                                ->where('has_warranty', true)
                                                ->exists();
                                    });

                                if ($hasWarrantyItem) {
                                    $options[DocumentType::WARRANTY_CONTRACT] = 'Contrat de garantie';
                                }

                                return $options;
                            })
                            ->columns(2)
                            ->default([DocumentType::INVOICE]),
                    ]),

            ]);

    }

    private static function requiresWarranty(callable $get): bool
    {
        if ($get('item_type') === 'motorcycle') {
            return true;
        }

        if (in_array($get('item_type'), self::warrantyProductTypes(), true)) {
            return true;
        }

        $productId = $get('product_id');

        return filled($productId)
            && Product::query()->whereKey($productId)->where('has_warranty', true)->exists();
    }

    private static function calculateSaleTotals(
        callable $get,
        string $saleItemsPath = 'saleItems',
        string $discountPath = 'discount',
        string $paidAmountPath = 'paid_amount'
    ): array
    {
        $gross = collect($get($saleItemsPath) ?? [])
            ->sum(function (array $item): float {
                $quantity = ($item['item_type'] ?? null) === 'motorcycle'
                    ? 1.0
                    : max(0.0, (float) ($item['quantity'] ?? 0));

                return $quantity * max(0.0, (float) ($item['unit_price'] ?? 0));
            });

        $discount = max(0.0, min((float) ($get($discountPath) ?? 0), $gross));
        $net = max(0.0, $gross - $discount);
        $tax = round($net * (20 / 120), 2);
        $paid = max(0.0, (float) ($get($paidAmountPath) ?? 0));

        return [
            'gross' => round($gross, 2),
            'discount' => round($discount, 2),
            'net' => round($net, 2),
            'tax' => $tax,
            'remaining' => round(max(0.0, $net - $paid), 2),
        ];
    }

    private static function syncPaidAmount(
        callable $get,
        callable $set,
        string $paidAmountPath = 'paid_amount',
        string $saleItemsPath = 'saleItems',
        string $discountPath = 'discount',
        string $currentPaidAmountPath = 'paid_amount'
    ): void
    {
        $totals = self::calculateSaleTotals($get, $saleItemsPath, $discountPath, $currentPaidAmountPath);
        $set($paidAmountPath, $totals['net']);
    }

    private static function formatMoney(float $amount): string
    {
        return number_format($amount, 2) . ' MAD';
    }

    private static function hasMotorcycleItem(callable $get): bool
    {
        return collect($get('saleItems') ?? [])
            ->some(fn (array $item): bool => ($item['item_type'] ?? null) === 'motorcycle');
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
