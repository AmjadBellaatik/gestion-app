<?php

namespace App\Filament\Resources\Documents\Schemas;

use App\Models\Client;
use App\Models\DocumentType;
use App\Models\Sale;
use App\Models\MotorcycleUnit;
use App\Models\Product;
use App\Models\RepairItem;
use App\Models\RepairTicket;
use App\Models\Supplier;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class DocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('messages.document_information'))
                ->schema([
                    Hidden::make('language')
                        ->default('fr')
                        ->dehydrated(),

                    Grid::make(2)->schema([
                        Select::make('document_type_id')
                            ->label(__('messages.document_type'))
                            ->options(fn () => DocumentType::query()
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required(),

                        DatePicker::make('document_date')
                            ->label(__('messages.document_date'))
                            ->default(now())
                            ->required(),
                    ]),

                    Grid::make(2)->schema([
                        Select::make('sale_id')
                            ->label(__('messages.sale'))
                            ->options(fn () => Sale::query()
                                ->with('client')
                                ->orderByDesc('id')
                                ->get()
                                ->mapWithKeys(fn (Sale $sale) => [
                                    $sale->id => ($sale->sale_number ?: ('SALE-' . $sale->id)) . ' - ' . ($sale->client?->display_name ?: '-'),
                                ])
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->visible(fn ($get) => self::supportsSaleLinking($get))
                            ->required(fn ($get) => self::requiresSaleLinking($get))
                            ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                                if (! $state) {
                                    return;
                                }

                                $sale = Sale::query()
                                    ->with(['client', 'documents.items', 'items.product', 'items.motorcycleUnit'])
                                    ->find($state);

                                if (! $sale) {
                                    return;
                                }

                                $set('client_id', $sale->client_id);

                                $items = self::isSaleReturnDocument($get)
                                    ? self::resolveReturnableSaleItems($sale)
                                    : (self::isWarrantyContract($get)
                                    ? self::resolveWarrantyDocumentSaleItems($sale)
                                    : self::resolveCommercialSaleItems($sale));

                                if ($items !== []) {
                                    $set('items', $items);
                                }

                                $warrantyItem = self::resolveSaleWarrantyItem($sale);

                                if ($warrantyItem) {
                                    $set('metadata.warranty_duration_value', $warrantyItem->warranty_duration_value);
                                    $set('metadata.warranty_duration_unit', $warrantyItem->warranty_duration_unit);
                                    $set('metadata.warranty_kilometers', $warrantyItem->warranty_kilometers);
                                }
                            }),


                        Select::make('repair_ticket_id')
                            ->label(__('messages.repair_ticket'))
                            ->options(fn () => RepairTicket::query()
                                ->orderByDesc('id')
                                ->get()
                                ->mapWithKeys(fn (RepairTicket $ticket) => [
                                    $ticket->id => ($ticket->ticket_number ?? ('#' . $ticket->id)) . ' - ' . ($ticket->client?->display_name ?? '-'),
                                ])
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->visible(fn ($get) => self::isRepairInvoiceDocument($get))
                            ->required(fn ($get) => self::isRepairInvoiceDocument($get))
                            ->afterStateUpdated(function ($state, callable $set): void {
                                if (! $state) {
                                    return;
                                }

                                $ticket = RepairTicket::query()
                                    ->with(['client', 'items.product'])
                                    ->find($state);

                                if (! $ticket) {
                                    return;
                                }

                                $set('client_id', $ticket->client_id);

                                $items = $ticket->items
                                    ->map(function (RepairItem $repairItem): array {
                                        $itemType = in_array($repairItem->item_type, ['part', 'accessory', 'consumable'], true)
                                            ? ($repairItem->product_id ? 'product' : 'service')
                                            : 'service';

                                        return [
                                            'item_type'       => $itemType,
                                            'product_id'      => $repairItem->product_id,
                                            'description'     => $repairItem->item_description,
                                            'quantity'        => (float) $repairItem->quantity,
                                            'unit_price'      => (float) $repairItem->unit_price,
                                            'discount_amount' => (float) $repairItem->discount_amount,
                                        ];
                                    })
                                    ->values()
                                    ->all();

                                if ((float) $ticket->labor_cost > 0) {
                                    $items[] = [
                                        'item_type'       => 'service',
                                        'product_id'      => null,
                                        'description'     => __('messages.labor_cost'),
                                        'quantity'        => 1,
                                        'unit_price'      => (float) $ticket->labor_cost,
                                        'discount_amount' => 0,
                                    ];
                                }

                                if ($items !== []) {
                                    $set('items', $items);
                                }
                            }),

                        Select::make('client_id')
                            ->label(__('messages.client'))
                            ->options(fn () => Client::query()
                                ->active()
                                ->get()
                                ->pluck('display_name', 'id')
                                ->filter()
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->visible(fn ($get) => ! self::isQuotationDocument($get) && ! self::isSupplierOrderDocument($get))
                            ->disabled(fn ($get) => self::isImportedFromRecord($get))
                            ->dehydrated()
                            ->required(fn ($get) => (self::isConformityDocument($get) || self::isWarrantyContract($get) || self::isInvoiceDocument($get)) && ! self::selectedSaleHasReseller($get) && ! self::isImportedFromRecord($get)),
                    ]),

                    Grid::make(2)->schema([
                        Select::make('metadata.manual_client_type')
                            ->label(__('messages.client_type'))
                            ->options([
                                'person' => __('messages.person'),
                                'company' => __('messages.company'),
                                'administration' => __('messages.administration'),
                            ])
                            ->default('person')
                            ->live()
                            ->visible(fn ($get) => self::isQuotationDocument($get))
                            ->required(fn ($get) => self::isQuotationDocument($get))
                            ->dehydrated(),

                        TextInput::make('metadata.manual_client_first_name')
                            ->label(__('messages.first_name'))
                            ->visible(fn ($get) => self::isQuotationDocument($get) && ($get('metadata.manual_client_type') ?? 'person') === 'person')
                            ->required(fn ($get) => self::isQuotationDocument($get) && ($get('metadata.manual_client_type') ?? 'person') === 'person')
                            ->dehydrated(),

                        TextInput::make('metadata.manual_client_last_name')
                            ->label(__('messages.last_name'))
                            ->visible(fn ($get) => self::isQuotationDocument($get) && ($get('metadata.manual_client_type') ?? 'person') === 'person')
                            ->required(fn ($get) => self::isQuotationDocument($get) && ($get('metadata.manual_client_type') ?? 'person') === 'person')
                            ->dehydrated(),

                        TextInput::make('metadata.manual_client_company_name')
                            ->label(__('messages.company_name'))
                            ->visible(fn ($get) => self::isQuotationDocument($get) && $get('metadata.manual_client_type') === 'company')
                            ->required(fn ($get) => self::isQuotationDocument($get) && $get('metadata.manual_client_type') === 'company')
                            ->dehydrated(),

                        TextInput::make('metadata.manual_client_administration_name')
                            ->label(__('messages.administration_name'))
                            ->visible(fn ($get) => self::isQuotationDocument($get) && $get('metadata.manual_client_type') === 'administration')
                            ->required(fn ($get) => self::isQuotationDocument($get) && $get('metadata.manual_client_type') === 'administration')
                            ->dehydrated(),

                        TextInput::make('metadata.manual_client_phone')
                            ->label(__('messages.phone'))
                            ->visible(fn ($get) => self::isQuotationDocument($get))
                            ->dehydrated(),

                        TextInput::make('metadata.manual_client_email')
                            ->label(__('messages.email'))
                            ->visible(fn ($get) => self::isQuotationDocument($get))
                            ->dehydrated(),

                        TextInput::make('metadata.purchase_order_number')
                            ->label(__('messages.purchase_order'))
                            ->visible(fn ($get) => self::isInvoiceDocument($get))
                            ->dehydrated(),

                        TextInput::make('metadata.supplier_quote_number')
                            ->label(__('messages.provider_quote_reference'))
                            ->visible(fn ($get) => self::isSupplierOrderDocument($get))
                            ->dehydrated(),

                        TextInput::make('metadata.manual_supplier_name')
                            ->label(__('messages.provider_company_name'))
                            ->visible(fn ($get) => self::isSupplierOrderDocument($get))
                            ->required(fn ($get) => self::isSupplierOrderDocument($get))
                            ->dehydrated(),

                        TextInput::make('metadata.manual_supplier_phone')
                            ->label(__('messages.phone'))
                            ->visible(fn ($get) => self::isSupplierOrderDocument($get))
                            ->dehydrated(),

                        TextInput::make('metadata.manual_supplier_email')
                            ->label(__('messages.email'))
                            ->visible(fn ($get) => self::isSupplierOrderDocument($get))
                            ->dehydrated(),

                        TextInput::make('metadata.provider_ice')
                            ->label(__('messages.ice'))
                            ->visible(fn ($get) => self::isSupplierOrderDocument($get))
                            ->dehydrated(),

                        TextInput::make('metadata.provider_rc')
                            ->label(__('messages.rc'))
                            ->visible(fn ($get) => self::isSupplierOrderDocument($get))
                            ->dehydrated(),

                        Textarea::make('metadata.manual_client_address')
                            ->label(__('messages.address'))
                            ->visible(fn ($get) => self::isQuotationDocument($get))
                            ->dehydrated()
                            ->columnSpanFull(),

                        Textarea::make('metadata.manual_supplier_address')
                            ->label(__('messages.address'))
                            ->visible(fn ($get) => self::isSupplierOrderDocument($get))
                            ->dehydrated()
                            ->columnSpanFull(),
                    ]),

                    Grid::make(2)->schema([
                        TextInput::make('metadata.warranty_duration_value')
                            ->label(__('messages.warranty_duration'))
                            ->numeric()
                            ->minValue(1)
                            ->visible(fn ($get) => self::isWarrantyContract($get))
                            ->required(fn ($get) => self::isWarrantyContract($get))
                            ->dehydrated(),

                        Select::make('metadata.warranty_duration_unit')
                            ->label(__('messages.warranty_duration_unit'))
                            ->options([
                                'weeks' => __('messages.weeks'),
                                'months' => __('messages.months'),
                                'years' => __('messages.years'),
                            ])
                            ->default('years')
                            ->visible(fn ($get) => self::isWarrantyContract($get))
                            ->required(fn ($get) => self::isWarrantyContract($get))
                            ->dehydrated(),

                        TextInput::make('metadata.warranty_kilometers')
                            ->label(__('messages.warranty_distance'))
                            ->numeric()
                            ->minValue(1)
                            ->suffix('KM')
                            ->visible(fn ($get) => self::isWarrantyContract($get))
                            ->required(fn ($get) => self::isWarrantyContract($get))
                            ->dehydrated(),
                    ])->columns(3),
                ]),

            Repeater::make('items')
                ->relationship()
                ->label(__('messages.document_articles'))
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, callable $set, callable $get) => self::updateFinancialTotals($set, $get))
                ->addable(fn ($get) => ! self::isImportedFromRecord($get))
                ->reorderable(fn ($get) => ! self::isImportedFromRecord($get))
                ->deletable(fn ($get) => ! self::isImportedFromRecord($get))
                ->schema([
                    Grid::make(4)->schema([
                        Hidden::make('item_type')
                            ->default('motorcycle')
                            ->dehydrateStateUsing(fn ($state, callable $get) => self::isSupplierOrderDocument($get) ? 'service' : Str::lower((string) ($state ?: 'motorcycle')))
                            ->dehydrated(fn ($get) => self::isConformityDocument($get) || self::isSupplierOrderDocument($get)),

                        Select::make('item_type')
                            ->label(__('messages.item_type'))
                            ->options(fn ($get) => self::isWarrantyContract($get)
                                ? [
                                    'motorcycle' => __('messages.motorcycle'),
                                    'trotinette' => __('messages.trotinette'),
                                    'velo_electrique' => __('messages.velo_electrique'),
                                    'velo_normal' => __('messages.velo_normal'),
                                ]
                                : [
                                    'product' => __('messages.product'),
                                    'motorcycle' => __('messages.motorcycle'),
                                    'service' => __('messages.service'),
                                ])
                            ->default(fn ($get) => self::isWarrantyContract($get) ? 'motorcycle' : 'product')
                            ->afterStateHydrated(function ($state, callable $set): void {
                                if (blank($state)) {
                                    return;
                                }

                                $set('item_type', Str::lower((string) $state));
                            })
                            ->dehydrateStateUsing(fn ($state) => Str::lower((string) $state))
                            ->visible(fn ($get) => ! self::isConformityDocument($get) && ! self::isSupplierOrderDocument($get))
                            ->disabled(fn ($get) => self::isImportedFromRecord($get))
                            ->dehydrated()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set): void {
                                $set('product_id', null);
                                $set('motorcycle_unit_id', null);
                            })
                            ->required(fn ($get) => ! self::isConformityDocument($get) && ! self::isImportedFromRecord($get) && ! self::isSupplierOrderDocument($get)),

                        Select::make('product_id')
                            ->label(fn ($get) => self::isWarrantyContract($get)
                                ? __('messages.' . ($get('item_type') ?: 'trotinette'))
                                : __('messages.product'))
                            ->options(fn ($get) => Product::query()
                                ->when(
                                    self::isWarrantyContract($get),
                                    fn ($query) => $query->where('type', $get('item_type'))
                                )
                                ->orderBy('name')
                                ->get()
                                ->filter(fn (Product $product) => ! self::isQuotationDocument($get) && ! self::isSupplierOrderDocument($get) || $product->current_stock > 0 || self::isSupplierOrderDocument($get))
                                ->mapWithKeys(fn (Product $product) => [
                                    $product->id => trim($product->name . ' - ' . __('messages.' . $product->type)),
                                ])
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                                if (! $state || ! self::isQuotationDocument($get)) {
                                    return;
                                }

                                $product = Product::query()->find($state);

                                if ($product) {
                                    $set('unit_price', (float) $product->selling_price);
                                    self::updateFinancialTotals($set, $get);
                                }
                            })
                            ->disabled(fn ($get) => self::isImportedFromRecord($get))
                            ->dehydrated()
                            ->visible(fn ($get) => ! self::isConformityDocument($get) && ! self::isSupplierOrderDocument($get) && (
                                self::isWarrantyContract($get)
                                    ? in_array($get('item_type'), self::warrantyProductTypes(), true)
                                    : $get('item_type') === 'product'
                            ))
                            ->required(fn ($get) => ! self::isConformityDocument($get) && ! self::isSupplierOrderDocument($get) && ! self::isImportedFromRecord($get) && (
                                self::isWarrantyContract($get)
                                    ? in_array($get('item_type'), self::warrantyProductTypes(), true)
                                    : $get('item_type') === 'product'
                            )),

                        Select::make('motorcycle_unit_id')
                            ->label(__('messages.motorcycle'))
                            ->options(fn () => MotorcycleUnit::query()
                                ->with('motorcycleModel')
                                ->whereIn('status', ['available', 'in_stock'])
                                ->get()
                                ->mapWithKeys(fn (MotorcycleUnit $unit) => [
                                    $unit->id => trim(($unit->motorcycleModel?->modele ?? __('messages.motorcycle')) . ' - ' . $unit->chassis_number),
                                ])
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                                if (! $state || ! self::isQuotationDocument($get)) {
                                    return;
                                }

                                $unit = MotorcycleUnit::query()
                                    ->with('motorcycleModel')
                                    ->find($state);

                                if ($unit) {
                                    $set('unit_price', (float) ($unit->motorcycleModel?->price_ttc ?? 0));
                                    self::updateFinancialTotals($set, $get);
                                }
                            })
                            ->disabled(fn ($get) => self::isImportedFromRecord($get))
                            ->dehydrated()
                            ->visible(fn ($get) => ! self::isSupplierOrderDocument($get) && (self::isConformityDocument($get) || $get('item_type') === 'motorcycle'))
                            ->required(fn ($get) => ! self::isSupplierOrderDocument($get) && ! self::isImportedFromRecord($get) && (self::isConformityDocument($get) || $get('item_type') === 'motorcycle')),

                        TextInput::make('quantity')
                            ->label(__('messages.quantity'))
                            ->numeric()
                            ->default(1)
                            ->visible(fn ($get) => ! self::isConformityDocument($get))
                            ->disabled(fn ($get) => self::isImportedFromRecord($get))
                            ->dehydrated()
                            ->required(fn ($get) => ! self::isImportedFromRecord($get))
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set, callable $get) => self::updateFinancialTotals($set, $get)),
                    ]),

                    Grid::make(4)->schema([
                        TextInput::make('description')
                            ->label(__('messages.description'))
                            ->disabled(fn ($get) => self::isImportedFromRecord($get))
                            ->dehydrated()
                            ->required(fn ($get) => self::isSupplierOrderDocument($get)),

                        TextInput::make('unit_price')
                            ->label(__('messages.unit_price_ttc'))
                            ->numeric()
                            ->default(0)
                            ->visible(fn ($get) => ! self::isConformityDocument($get) && ! self::isWarrantyContract($get))
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set, callable $get) => self::updateFinancialTotals($set, $get))
                            ->disabled(fn ($get) => self::isImportedFromRecord($get))
                            ->dehydrated()
                            ->required(fn ($get) => ! self::isWarrantyContract($get) && ! self::isImportedFromRecord($get)),

                        TextInput::make('discount_amount')
                            ->label(__('messages.fixed_discount'))
                            ->numeric()
                            ->visible(fn ($get) => ! self::isConformityDocument($get) && ! self::isWarrantyContract($get))
                            ->default(0)
                            ->disabled(fn ($get) => self::isImportedFromRecord($get))
                            ->dehydrated()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set, callable $get) => self::updateFinancialTotals($set, $get)),

                        TextInput::make('warranty_months')
                            ->label(__('messages.warranty_duration'))
                            ->visible(fn ($get) => ! self::isConformityDocument($get) && ! self::isWarrantyContract($get) && ! self::isQuotationDocument($get) && ! self::isInvoiceDocument($get) && ! self::isSupplierOrderDocument($get))
                            ->disabled(fn ($get) => self::isImportedFromRecord($get))
                            ->dehydrated()
                            ->numeric(),
                    ]),
                ])
                ->defaultItems(1)
                ->columnSpanFull(),

            Section::make(__('messages.financial_information'))
                ->schema([
                    Grid::make(4)->schema([
                        TextInput::make('subtotal')
                            ->label(__('messages.subtotal_ht'))
                            ->numeric()
                            ->disabled(fn ($get) => ! self::isSupplierOrderDocument($get))
                            ->dehydrated(fn ($get) => self::isSupplierOrderDocument($get))
                            ->live(onBlur: true),

                        TextInput::make('tax_rate')
                            ->label(__('messages.tax_rate'))
                            ->numeric()
                            ->default(20)
                            ->disabled(fn ($get) => ! self::isSupplierOrderDocument($get))
                            ->dehydrated(fn ($get) => self::isSupplierOrderDocument($get))
                            ->suffix('%'),

                        TextInput::make('tax_amount')
                            ->label(__('messages.tax_amount'))
                            ->numeric()
                            ->disabled(fn ($get) => ! self::isSupplierOrderDocument($get))
                            ->dehydrated(fn ($get) => self::isSupplierOrderDocument($get))
                            ->live(onBlur: true),

                        TextInput::make('total_amount')
                            ->label(__('messages.total_ttc'))
                            ->numeric()
                            ->disabled(fn ($get) => ! self::isSupplierOrderDocument($get))
                            ->dehydrated(fn ($get) => self::isSupplierOrderDocument($get))
                            ->live(onBlur: true),
                    ]),
                ])
                ->visible(fn ($get) => ! self::isConformityDocument($get) && ! self::isWarrantyContract($get) && ! self::isInvoiceDocument($get)),

            Section::make(__('messages.additional_information'))
                ->schema([
                    Textarea::make('notes')
                        ->label(__('messages.notes'))
                        ->columnSpanFull(),
                ]),
        ]);
    }

    private static function isConformityDocument(callable $get): bool
    {
        $documentTypeId = $get('../../document_type_id') ?: $get('document_type_id');

        if (! $documentTypeId) {
            return false;
        }

        return DocumentType::query()
            ->whereKey($documentTypeId)
            ->where('code', DocumentType::CONFORMITY)
            ->exists();
    }

    private static function isWarrantyContract(callable $get): bool
    {
        $documentTypeId = $get('../../document_type_id') ?: $get('document_type_id');

        if (! $documentTypeId) {
            return false;
        }

        return DocumentType::query()
            ->whereKey($documentTypeId)
            ->where('code', DocumentType::WARRANTY_CONTRACT)
            ->exists();
    }

    private static function isQuotationDocument(callable $get): bool
    {
        return self::documentHasCode($get, DocumentType::QUOTATION);
    }

    private static function isInvoiceDocument(callable $get): bool
    {
        return self::documentHasCode($get, DocumentType::INVOICE);
    }

    private static function documentHasCode(callable $get, string $code): bool
    {
        $documentTypeId = $get('../../document_type_id') ?: $get('document_type_id');

        if (! $documentTypeId) {
            return false;
        }

        return DocumentType::query()
            ->whereKey($documentTypeId)
            ->where('code', $code)
            ->exists();
    }

    private static function supportsSaleLinking(callable $get): bool
    {
        $documentTypeId = $get('../../document_type_id') ?: $get('document_type_id');

        if (! $documentTypeId) {
            return false;
        }

        $type = DocumentType::query()->find($documentTypeId);

        if (! $type) {
            return false;
        }

        return in_array($type->code, [
            DocumentType::INVOICE,
            DocumentType::DELIVERY_NOTE,
            DocumentType::WARRANTY_CONTRACT,
            DocumentType::CONFORMITY,
            DocumentType::SALE_RETURN,
        ], true);
    }

    private static function isSupplierOrderDocument(callable $get): bool
    {
        return self::documentHasCode($get, DocumentType::SUPPLIER_ORDER);
    }

    private static function selectedSaleHasReseller(callable $get): bool
    {
        $saleId = $get('../../sale_id') ?: $get('sale_id');

        return filled($saleId) && Sale::query()->whereKey($saleId)->whereNotNull('reseller_id')->exists();
    }

    private static function resolveSaleMotorcycleUnitId(Sale $sale): ?int
    {
        $item = $sale->documents
            ->flatMap(fn ($document) => $document->items ?? [])
            ->first(fn ($documentItem) => ! empty($documentItem->motorcycle_unit_id));

        if ($item?->motorcycle_unit_id) {
            return (int) $item->motorcycle_unit_id;
        }

        return MotorcycleUnit::query()
            ->where('client_id', $sale->client_id)
            ->orderByDesc('sale_date')
            ->orderByDesc('id')
            ->value('id');
    }

    private static function resolveWarrantyDocumentSaleItems(Sale $sale): array
    {
        $items = $sale->items
            ->map(function ($item): ?array {
                if ($item->motorcycle_unit_id) {
                    return [
                        'item_type' => 'motorcycle',
                        'motorcycle_unit_id' => $item->motorcycle_unit_id,
                        'quantity' => 1,
                        'unit_price' => 0,
                        'discount_amount' => 0,
                    ];
                }

                if ($item->product_id && in_array($item->product?->type, self::warrantyProductTypes(), true)) {
                    return [
                        'item_type' => $item->product->type,
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity ?: 1,
                        'unit_price' => 0,
                        'discount_amount' => 0,
                    ];
                }

                return null;
            })
            ->filter()
            ->values()
            ->all();

        if ($items !== []) {
            return $items;
        }

        $motorcycleUnitId = self::resolveSaleMotorcycleUnitId($sale);

        if (! $motorcycleUnitId) {
            return [];
        }

        return [[
            'item_type' => 'motorcycle',
            'motorcycle_unit_id' => $motorcycleUnitId,
            'quantity' => 1,
            'unit_price' => 0,
            'discount_amount' => 0,
        ]];
    }

    private static function resolveCommercialSaleItems(Sale $sale): array
    {
        return $sale->items
            ->map(function ($item): ?array {
                if ($item->motorcycle_unit_id) {
                    return [
                        'item_type' => 'motorcycle',
                        'motorcycle_unit_id' => $item->motorcycle_unit_id,
                        'quantity' => 1,
                        'unit_price' => $item->unit_price ?: ($item->motorcycleUnit?->motorcycleModel?->price_ttc ?? 0),
                        'discount_amount' => $item->discount ?? 0,
                    ];
                }

                if ($item->product_id) {
                    return [
                        'item_type' => 'product',
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity ?: 1,
                        'unit_price' => $item->unit_price ?: ($item->product?->selling_price ?? 0),
                        'discount_amount' => $item->discount ?? 0,
                    ];
                }

                return null;
            })
            ->filter()
            ->values()
            ->all();
    }

    private static function isSaleReturnDocument(callable $get): bool
    {
        return self::documentHasCode($get, DocumentType::SALE_RETURN);
    }

    private static function isDeliveryNoteDocument(callable $get): bool
    {
        return self::documentHasCode($get, DocumentType::DELIVERY_NOTE);
    }

    private static function isRepairInvoiceDocument(callable $get): bool
    {
        return self::documentHasCode($get, DocumentType::REPAIR_INVOICE);
    }

    private static function requiresSaleLinking(callable $get): bool
    {
        return self::isInvoiceDocument($get)
            || self::isWarrantyContract($get)
            || self::isConformityDocument($get)
            || self::isDeliveryNoteDocument($get)
            || self::isSaleReturnDocument($get);
    }

    private static function isImportedFromRecord(callable $get): bool
    {
        return self::requiresSaleLinking($get) || self::isRepairInvoiceDocument($get);
    }

    private static function resolveReturnableSaleItems(Sale $sale): array
    {
        return $sale->items
            ->map(function ($item): ?array {
                $remaining = max((float) $item->quantity - (float) ($item->returned_quantity ?? 0), 0);

                if ($remaining <= 0) {
                    return null;
                }

                if ($item->motorcycle_unit_id) {
                    return [
                        'item_type' => 'motorcycle',
                        'motorcycle_unit_id' => $item->motorcycle_unit_id,
                        'quantity' => 1,
                        'unit_price' => 0,
                        'discount_amount' => 0,
                    ];
                }

                if ($item->product_id) {
                    return [
                        'item_type' => 'product',
                        'product_id' => $item->product_id,
                        'quantity' => $remaining,
                        'unit_price' => 0,
                        'discount_amount' => 0,
                    ];
                }

                return null;
            })
            ->filter()
            ->values()
            ->all();
    }

    private static function resolveSaleWarrantyItem(Sale $sale): ?\App\Models\SaleItem
    {
        return $sale->items
            ->first(function ($item): bool {
                if ($item->motorcycle_unit_id) {
                    return true;
                }

                return in_array($item->product?->type, self::warrantyProductTypes(), true);
            });
    }

    private static function warrantyProductTypes(): array
    {
        return [
            'trotinette',
            'velo_electrique',
            'velo_normal',
        ];
    }

    private static function updateFinancialTotals(callable $set, callable $get): void
    {
        $items = $get('items') ?: $get('../../items') ?: [];

        $total = collect($items)
            ->sum(function (array $item): float {
                $quantity = (float) ($item['quantity'] ?? 1);
                $unitPrice = (float) ($item['unit_price'] ?? 0);
                $discount = (float) ($item['discount_amount'] ?? 0);

                return max(($quantity * $unitPrice) - $discount, 0);
            });

        $tax = round($total * (20 / 120), 2);
        $subtotal = round($total - $tax, 2);

        if ($get('items') !== null) {
            $set('subtotal', $subtotal);
            $set('tax_amount', $tax);
            $set('total_amount', round($total, 2));

            return;
        }

        $set('../../subtotal', $subtotal);
        $set('../../tax_amount', $tax);
        $set('../../total_amount', round($total, 2));
    }
}
