<?php

namespace App\Filament\Resources\RepairTickets\Schemas;

use App\Models\Client;
use App\Models\MotorcycleUnit;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Technician;
use App\Models\User;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class RepairTicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            /*
            |------------------------------------------------------------------
            | Section 1 — Source of Repair (required selection)
            |------------------------------------------------------------------
            */

            Section::make(__('messages.repair_source'))
                ->icon('heroicon-o-wrench-screwdriver')
                ->schema([
                    Select::make('_repair_source')
                        ->label(__('messages.repair_source'))
                        ->options([
                            'sale'    => __('messages.linked_to_sale'),
                            'stock'   => __('messages.stock_vehicle'),
                            'foreign' => __('messages.foreign_vehicle'),
                        ])
                        ->required()
                        ->live()
                        ->dehydrated(false)
                        ->afterStateUpdated(function (string $state, callable $set) {
                            $set('is_foreign_vehicle', $state === 'foreign');

                            if ($state === 'sale') {
                                $set('motorcycle_unit_id', null);
                            } elseif ($state === 'stock') {
                                $set('sale_id', null);
                                $set('client_id', null);
                                $set('foreign_brand', null);
                                $set('foreign_model', null);
                                $set('foreign_chassis', null);
                                $set('foreign_year', null);
                                $set('foreign_color', null);
                                $set('mileage', null);
                            } else {
                                $set('sale_id', null);
                                $set('motorcycle_unit_id', null);
                                $set('foreign_brand', null);
                                $set('foreign_model', null);
                                $set('foreign_chassis', null);
                                $set('foreign_year', null);
                                $set('foreign_color', null);
                                $set('mileage', null);
                                $set('client_id', null);
                            }
                        }),
                ]),

            /*
            |------------------------------------------------------------------
            | Section 2 — Vehicle Information
            |------------------------------------------------------------------
            */

            Section::make(__('messages.vehicle_information'))
                ->icon('heroicon-o-truck')
                ->schema([

                    // ── Mode: Linked to a sale ──────────────────────────────
                    Select::make('sale_id')
                        ->label(__('messages.sale'))
                        ->searchable()
                        ->live()
                        ->required(fn (Get $get): bool => $get('_repair_source') === 'sale')
                        ->visible(fn (Get $get): bool => $get('_repair_source') === 'sale')
                        ->helperText(__('messages.type_to_search_sale'))
                        ->columnSpanFull()
                        ->getSearchResultsUsing(function (string $search) {
                            return Sale::query()
                                ->with(['client', 'items.motorcycleUnit.motorcycleModel'])
                                ->whereNotIn('status', ['cancelled', 'returned'])
                                ->where(function ($query) use ($search) {
                                    $query->where('sale_number', 'like', "%{$search}%")
                                        ->orWhereHas('client', fn ($q) => $q
                                            ->where('first_name', 'like', "%{$search}%")
                                            ->orWhere('last_name', 'like', "%{$search}%")
                                            ->orWhere('company_name', 'like', "%{$search}%")
                                            ->orWhere('administration_name', 'like', "%{$search}%"));
                                })
                                ->orderByDesc('id')
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(function (Sale $sale) {
                                    $unit  = $sale->items
                                        ->whereNotNull('motorcycle_unit_id')
                                        ->first()
                                        ?->motorcycleUnit;
                                    $brand = $unit?->motorcycleModel?->marque ?? null;
                                    $model = $unit?->motorcycleModel?->modele ?? null;

                                    $label = ($sale->sale_number ?: 'SALE-' . $sale->id)
                                        . ' — ' . ($sale->client?->display_name ?? '-');

                                    if ($brand || $model) {
                                        $label .= ' — ' . implode(' ', array_filter([$brand, $model]));
                                    }

                                    return [$sale->id => $label];
                                })
                                ->toArray();
                        })
                        ->getOptionLabelUsing(function ($value) {
                            if (! $value) {
                                return null;
                            }
                            $sale = Sale::with(['client', 'items.motorcycleUnit.motorcycleModel'])->find($value);
                            if (! $sale) {
                                return null;
                            }
                            $unit  = $sale->items
                                ->whereNotNull('motorcycle_unit_id')
                                ->first()
                                ?->motorcycleUnit;
                            $brand = $unit?->motorcycleModel?->marque ?? null;
                            $model = $unit?->motorcycleModel?->modele ?? null;

                            $label = ($sale->sale_number ?: 'SALE-' . $sale->id)
                                . ' — ' . ($sale->client?->display_name ?? '-');

                            if ($brand || $model) {
                                $label .= ' — ' . implode(' ', array_filter([$brand, $model]));
                            }

                            return $label;
                        })
                        ->afterStateUpdated(function ($state, callable $set) {
                            if (! $state) {
                                $set('client_id', null);
                                $set('motorcycle_unit_id', null);
                                $set('foreign_brand', null);
                                $set('foreign_model', null);
                                $set('foreign_chassis', null);
                                $set('foreign_year', null);
                                $set('foreign_color', null);
                                $set('mileage', null);
                                return;
                            }

                            $sale = Sale::with(['client', 'items.motorcycleUnit.motorcycleModel'])->find($state);
                            if (! $sale) {
                                return;
                            }

                            $set('client_id', $sale->client_id);

                            $saleItem = $sale->items
                                ->whereNotNull('motorcycle_unit_id')
                                ->first();

                            $unit = $saleItem?->motorcycleUnit;

                            if ($unit) {
                                $set('motorcycle_unit_id', $unit->id);

                                $motoModel = $unit->motorcycleModel;
                                $set('foreign_brand',   $motoModel?->marque      ?? null);
                                $set('foreign_model',   $motoModel?->modele      ?? null);
                                $set('foreign_chassis', $unit->chassis_number    ?? null);
                                $set('foreign_color',   $unit->color             ?? null);
                                $set('mileage',         $unit->mileage           ?? null);
                            } else {
                                $set('motorcycle_unit_id', null);
                                $set('foreign_brand', null);
                                $set('foreign_model', null);
                                $set('foreign_chassis', null);
                                $set('foreign_color', null);
                                $set('mileage', null);
                            }
                        }),

                    // ── Mode: In-stock inventory vehicle ───────────────────
                    Select::make('motorcycle_unit_id')
                        ->label(__('messages.motorcycle_unit'))
                        ->options(fn () => MotorcycleUnit::query()
                            ->whereIn('status', ['available', 'in_stock', 'in_repair'])
                            ->with('motorcycleModel')
                            ->get()
                            ->mapWithKeys(fn (MotorcycleUnit $u) => [
                                $u->id => implode(' — ', array_filter([
                                    $u->chassis_number,
                                    $u->motorcycleModel?->modele,
                                ])),
                            ])
                            ->toArray())
                        ->searchable()
                        ->preload()
                        ->live()
                        ->required(fn (Get $get): bool => $get('_repair_source') === 'stock')
                        ->visible(fn (Get $get): bool => $get('_repair_source') === 'stock')
                        ->dehydrated(true)
                        ->columnSpanFull()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if (! $state) {
                                return;
                            }
                            $unit = MotorcycleUnit::query()->with('client')->find($state);
                            if ($unit?->client_id) {
                                $set('client_id', $unit->client_id);
                            }
                        }),

                    // ── Mode: Foreign / non-inventory vehicle ──────────────
                    // Also shown in 'sale' mode so auto-populated values are visible.
                    Grid::make(3)
                        ->schema([
                            TextInput::make('foreign_brand')
                                ->label(__('messages.brand'))
                                ->maxLength(100)
                                ->disabled(fn (Get $get): bool => $get('_repair_source') === 'sale')
                                ->dehydrated(true),

                            TextInput::make('foreign_model')
                                ->label(__('messages.model'))
                                ->maxLength(100)
                                ->disabled(fn (Get $get): bool => $get('_repair_source') === 'sale')
                                ->dehydrated(true),

                            TextInput::make('foreign_chassis')
                                ->label(__('messages.chassis_number'))
                                ->maxLength(50)
                                ->disabled(fn (Get $get): bool => $get('_repair_source') === 'sale')
                                ->dehydrated(true),

                            TextInput::make('foreign_year')
                                ->label(__('messages.year'))
                                ->numeric()
                                ->minValue(1900)
                                ->maxValue((int) date('Y') + 2)
                                ->dehydrated(true),

                            TextInput::make('foreign_color')
                                ->label(__('messages.color'))
                                ->maxLength(50)
                                ->disabled(fn (Get $get): bool => $get('_repair_source') === 'sale')
                                ->dehydrated(true),

                            TextInput::make('mileage')
                                ->label(__('messages.mileage_at_reception'))
                                ->numeric()
                                ->default(0)
                                ->suffix('km')
                                ->dehydrated(true),
                        ])
                        ->visible(fn (Get $get): bool => in_array($get('_repair_source'), ['foreign', 'sale'], true)),

                    // Controlled by _repair_source — stored on the model
                    Hidden::make('is_foreign_vehicle')->default(false),

                    // Mileage for stock mode (foreign/sale modes show it inside the Grid above)
                    TextInput::make('mileage')
                        ->label(__('messages.mileage_at_reception'))
                        ->numeric()
                        ->default(0)
                        ->suffix('km')
                        ->dehydrated(true)
                        ->visible(fn (Get $get): bool => $get('_repair_source') === 'stock'),

                ]),

            /*
            |------------------------------------------------------------------
            | Section 3 — Customer and Assignment (both required)
            |------------------------------------------------------------------
            */

            Section::make(__('messages.customer_and_assignment'))
                ->icon('heroicon-o-users')
                ->schema([
                    Grid::make(2)->schema([

                        Select::make('client_id')
                            ->label(__('messages.client'))
                            ->options(fn () => Client::query()
                                ->where('is_active', true)
                                ->where('is_blocked', false)
                                ->get()
                                ->pluck('display_name', 'id')
                                ->filter(fn ($v) => filled($v))
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required()
                            ->disabled(fn (Get $get): bool => $get('_repair_source') === 'sale')
                            ->dehydrated(true)
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
                            ->createOptionUsing(fn (array $data) => Client::create(
                                array_merge(['is_active' => true, 'is_blocked' => false], $data)
                            )->id),

                        Select::make('technician_id')
                            ->label(__('messages.lead_technician'))
                            ->options(fn () => Technician::query()
                                ->where('is_active', true)
                                ->pluck('name', 'id')
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                TextInput::make('name')->label(__('messages.name'))->required(),
                                TextInput::make('phone')->label(__('messages.phone'))->tel(),
                                TextInput::make('speciality')->label(__('messages.speciality')),
                            ])
                            ->createOptionUsing(fn (array $data) => Technician::create(
                                array_merge(['is_active' => true], $data)
                            )->id),

                    ]),

                    // Additional team members — admin only
                    Repeater::make('assignedTechnicians')
                        ->relationship('assignedTechnicians')
                        ->label(__('messages.assigned_technicians'))
                        ->visible(fn () => self::isAdmin())
                        ->schema([
                            Grid::make(2)->schema([
                                Select::make('technician_id')
                                    ->label(__('messages.technician'))
                                    ->options(fn () => Technician::query()
                                        ->where('is_active', true)
                                        ->pluck('name', 'id')
                                        ->toArray())
                                    ->searchable()
                                    ->required()
                                    ->createOptionForm([
                                        TextInput::make('name')->label(__('messages.name'))->required(),
                                        TextInput::make('phone')->label(__('messages.phone'))->tel(),
                                        TextInput::make('speciality')->label(__('messages.speciality')),
                                    ])
                                    ->createOptionUsing(fn (array $data) => Technician::create(
                                        array_merge(['is_active' => true], $data)
                                    )->id),
                                Select::make('permission')
                                    ->label(__('messages.permission'))
                                    ->options([
                                        'view'   => __('messages.view_only'),
                                        'modify' => __('messages.can_modify'),
                                    ])
                                    ->default('modify')
                                    ->required(),
                            ]),
                        ])
                        ->addActionLabel(__('messages.add_technician'))
                        ->defaultItems(0)
                        ->reorderable(false),
                ]),

            /*
            |------------------------------------------------------------------
            | Section 4 — Repair Details
            |------------------------------------------------------------------
            */

            Section::make(__('messages.repair_details'))
                ->icon('heroicon-o-clipboard-document-list')
                ->schema([
                    Grid::make(3)->schema([

                        TextInput::make('ticket_number')
                            ->label(__('messages.ticket_number'))
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder(__('messages.auto_generated')),

                        Select::make('repair_type')
                            ->label(__('messages.repair_type'))
                            ->options([
                                'warranty' => __('messages.warranty'),
                                'paid'     => __('messages.paid'),
                                'internal' => __('messages.internal'),
                            ])
                            ->default('paid')
                            ->required(),

                        Select::make('priority')
                            ->label(__('messages.priority'))
                            ->options([
                                'low'    => __('messages.low'),
                                'normal' => __('messages.normal'),
                                'high'   => __('messages.high'),
                                'urgent' => __('messages.urgent'),
                            ])
                            ->default('normal')
                            ->required(),

                    ]),

                    Textarea::make('problem_description')
                        ->label(__('messages.problem_description'))
                        ->rows(3)
                        ->columnSpanFull(),

                    Textarea::make('diagnostic')
                        ->label(__('messages.diagnostic_notes'))
                        ->rows(3)
                        ->columnSpanFull(),

                    Textarea::make('before_state')
                        ->label(__('messages.vehicle_state_before'))
                        ->rows(2)
                        ->columnSpanFull(),

                    Grid::make(2)->schema([
                        Toggle::make('is_warranty')
                            ->label(__('messages.is_warranty'))
                            ->live(),
                        TextInput::make('warranty_status')
                            ->label(__('messages.warranty_status'))
                            ->visible(fn ($get) => (bool) $get('is_warranty')),
                    ]),
                ]),

            /*
            |------------------------------------------------------------------
            | Section 5 — Parts and Consumables
            |------------------------------------------------------------------
            */

            Section::make(__('messages.parts_used'))
                ->icon('heroicon-o-cube')
                ->schema([
                    Repeater::make('parts')
                        ->relationship('parts')
                        ->label('')
                        ->schema(self::itemSchema('part', 'parts'))
                        ->addActionLabel(__('messages.add_part'))
                        ->defaultItems(0)
                        ->reorderable(),
                ]),

            Section::make(__('messages.consumables_used'))
                ->icon('heroicon-o-beaker')
                ->schema([
                    Repeater::make('consumables')
                        ->relationship('consumables')
                        ->label('')
                        ->schema(self::itemSchema('consumable', 'consumables'))
                        ->addActionLabel(__('messages.add_consumable'))
                        ->defaultItems(0)
                        ->reorderable(),
                ]),

            /*
            |------------------------------------------------------------------
            | Section 6 — Financial Information
            |------------------------------------------------------------------
            */

            Section::make(__('messages.financials'))
                ->icon('heroicon-o-banknotes')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('labor_cost')
                            ->label(__('messages.labor_cost'))
                            ->numeric()
                            ->default(0)
                            ->prefix('DH')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Get $get, callable $set) {
                                $partsCost      = self::computePartsCost($get('parts') ?? [], $get('consumables') ?? []);
                                $globalDiscount = max(0, (float) ($get('discount_amount') ?? 0));
                                $set('parts_cost', $partsCost);
                                $set('total_cost', round(max(0, $partsCost + max(0, (float) $state) - $globalDiscount), 2));
                            }),

                        TextInput::make('parts_cost')
                            ->label(__('messages.parts_cost'))
                            ->numeric()
                            ->prefix('DH')
                            ->disabled()
                            ->dehydrated(),
                    ]),

                    Grid::make(2)->schema([
                        TextInput::make('discount_amount')
                            ->label(__('messages.discount_amount'))
                            ->numeric()
                            ->default(0)
                            ->prefix('DH')
                            ->live()
                            ->helperText(__('messages.discount_requires_validation'))
                            ->afterStateUpdated(function ($state, Get $get, callable $set) {
                                $partsCost = self::computePartsCost($get('parts') ?? [], $get('consumables') ?? []);
                                $laborCost = max(0, (float) ($get('labor_cost') ?? 0));
                                $set('parts_cost', $partsCost);
                                $set('total_cost', round(max(0, $partsCost + $laborCost - max(0, (float) $state)), 2));
                            }),

                        TextInput::make('total_cost')
                            ->label(__('messages.total_cost'))
                            ->numeric()
                            ->prefix('DH')
                            ->disabled()
                            ->dehydrated(),
                    ]),

                    Textarea::make('discount_note')
                        ->label(__('messages.discount_note'))
                        ->rows(2)
                        ->columnSpanFull(),
                ]),

            /*
            |------------------------------------------------------------------
            | Section 7 — Status and Notes
            |------------------------------------------------------------------
            */

            Section::make(__('messages.status_and_notes'))
                ->icon('heroicon-o-check-badge')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('status')
                            ->label(__('messages.status'))
                            ->options(self::statusOptions())
                            ->default('open')
                            ->required()
                            ->disabled(fn () => ! self::isAdmin())
                            ->dehydrated(fn () => self::isAdmin()),

                        Select::make('payment_status')
                            ->label(__('messages.payment_status'))
                            ->options([
                                'unpaid'  => __('messages.unpaid'),
                                'partial' => __('messages.partial'),
                                'paid'    => __('messages.paid'),
                            ])
                            ->default('unpaid')
                            ->disabled()
                            ->dehydrated(false),
                    ]),

                    Textarea::make('technician_notes')
                        ->label(__('messages.technician_notes'))
                        ->rows(3)
                        ->columnSpanFull(),

                    Textarea::make('after_state')
                        ->label(__('messages.vehicle_state_after'))
                        ->rows(2)
                        ->columnSpanFull(),
                ]),

            /*
            |------------------------------------------------------------------
            | Section 8 — Intervention Steps
            |------------------------------------------------------------------
            */

            Section::make(__('messages.intervention_steps'))
                ->icon('heroicon-o-list-bullet')
                ->schema([
                    Repeater::make('steps')
                        ->relationship('steps')
                        ->label('')
                        ->schema([
                            Grid::make(3)->schema([
                                TextInput::make('title')
                                    ->label(__('messages.step_title'))
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(2),
                                Select::make('status')
                                    ->label(__('messages.status'))
                                    ->options([
                                        'pending'     => __('messages.pending'),
                                        'in_progress' => __('messages.in_progress'),
                                        'done'        => __('messages.done'),
                                    ])
                                    ->default('pending')
                                    ->columnSpan(1),
                            ]),
                            Textarea::make('description')
                                ->label(__('messages.description'))
                                ->rows(2)
                                ->columnSpanFull(),
                            Grid::make(2)->schema([
                                Select::make('performed_by')
                                    ->label(__('messages.performed_by'))
                                    ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id')->toArray())
                                    ->searchable(),
                                DateTimePicker::make('performed_at')
                                    ->label(__('messages.performed_at')),
                            ]),
                        ])
                        ->addActionLabel(__('messages.add_step'))
                        ->defaultItems(0)
                        ->reorderable()
                        ->collapsible(),
                ]),

        ]);
    }

    /*
    |------------------------------------------------------------------
    | Shared repeater schema for parts and consumables
    |------------------------------------------------------------------
    */

    private static function itemSchema(string $type, string $repeaterKey): array
    {
        $types = $type === 'part' ? ['part', 'accessory'] : ['consumable'];
        $label = $type === 'part' ? __('messages.part') : __('messages.consumable');

        return [
            Hidden::make('item_type')->default($type),

            // Row 1 — Product selection (full width for readability)
            Select::make('product_id')
                ->label($label)
                ->options(function (Get $get, $state) use ($types, $repeaterKey) {
                    $selectedIds = collect($get('../../' . $repeaterKey))
                        ->pluck('product_id')
                        ->filter()
                        ->reject(fn ($id) => $id == $state)
                        ->values()
                        ->toArray();

                    return Product::query()
                        ->whereIn('type', $types)
                        ->whereNotIn('id', $selectedIds)
                        ->orderBy('name')
                        ->get()
                        ->filter(fn ($p) => $p->current_stock > 0)
                        ->mapWithKeys(fn ($p) => [
                            $p->id => $p->name . ' (' . __('messages.stock') . ': ' . (int) $p->current_stock . ')',
                        ])
                        ->toArray();
                })
                ->searchable()
                ->live()
                ->columnSpanFull()
                ->afterStateUpdated(function ($state, Get $get, callable $set) {
                    if (! $state) {
                        $set('unit_price', 0);
                        $set('total', 0);
                        return;
                    }

                    $product = Product::find($state);
                    if (! $product) {
                        return;
                    }

                    $clientId   = $get('../../client_id');
                    $isReseller = $clientId && \App\Models\Client::withoutGlobalScopes()
                        ->where('id', $clientId)
                        ->whereNotNull('reseller_id')
                        ->exists();
                    $price = ($isReseller && (float) $product->reseller_price > 0)
                        ? (float) $product->reseller_price
                        : (float) $product->selling_price;

                    $set('unit_price', $price);

                    $qty      = max(0.01, (float) ($get('quantity') ?? 1));
                    $discount = (float) ($get('discount_amount') ?? 0);
                    $set('total', round(max(0, $qty * $price - $discount), 2));

                    self::recalculateParentTotals($get, $set);
                }),

            // Row 2 — Qty | Unit Price
            Grid::make(2)->schema([

                TextInput::make('quantity')
                    ->label(__('messages.quantity'))
                    ->numeric()
                    ->default(1)
                    ->minValue(0.01)
                    ->maxValue(fn (Get $get): float => filled($get('product_id'))
                        ? max(0.01, (float) (Product::find($get('product_id'))?->current_stock ?? 9999))
                        : 9999)
                    ->live()
                    ->afterStateUpdated(function ($state, Get $get, callable $set) {
                        $productId = $get('product_id');
                        $maxStock  = $productId
                            ? max(0.01, (float) (Product::find($productId)?->current_stock ?? 9999))
                            : 9999;
                        $qty      = min(max(0, (float) $state), $maxStock);
                        $price    = (float) ($get('unit_price') ?? 0);
                        $discount = (float) ($get('discount_amount') ?? 0);
                        $set('total', round(max(0, $qty * $price - $discount), 2));
                        self::recalculateParentTotals($get, $set);
                    }),

                TextInput::make('unit_price')
                    ->label(__('messages.unit_price_col'))
                    ->numeric()
                    ->default(0)
                    ->prefix('DH')
                    ->live()
                    ->afterStateUpdated(function ($state, Get $get, callable $set) {
                        $qty      = max(0, (float) ($get('quantity') ?? 1));
                        $price    = max(0, (float) $state);
                        $discount = (float) ($get('discount_amount') ?? 0);
                        $set('total', round(max(0, $qty * $price - $discount), 2));
                        self::recalculateParentTotals($get, $set);
                    }),

            ]),

            // Row 3 — Discount | Subtotal
            Grid::make(2)->schema([

                TextInput::make('discount_amount')
                    ->label(__('messages.discount_col'))
                    ->numeric()
                    ->default(0)
                    ->prefix('DH')
                    ->live()
                    ->afterStateUpdated(function ($state, Get $get, callable $set) {
                        $qty      = max(0, (float) ($get('quantity') ?? 1));
                        $price    = (float) ($get('unit_price') ?? 0);
                        $discount = max(0, (float) $state);
                        $set('total', round(max(0, $qty * $price - $discount), 2));
                        self::recalculateParentTotals($get, $set);
                    }),

                TextInput::make('total')
                    ->label(__('messages.subtotal'))
                    ->numeric()
                    ->prefix('DH')
                    ->disabled()
                    ->dehydrated(),

            ]),
        ];
    }

    private static function computePartsCost(array $parts, array $consumables): float
    {
        $sum = fn (array $items): float => collect($items)->sum(function ($item): float {
            $qty      = max(0, (float) ($item['quantity'] ?? 0));
            $price    = max(0, (float) ($item['unit_price'] ?? 0));
            $discount = max(0, (float) ($item['discount_amount'] ?? 0));
            return max(0, $qty * $price - $discount);
        });

        return round($sum($parts) + $sum($consumables), 2);
    }

    private static function recalculateParentTotals(Get $get, callable $set): void
    {
        $partsCost      = self::computePartsCost(
            $get('../../parts') ?? [],
            $get('../../consumables') ?? []
        );
        $laborCost      = max(0, (float) ($get('../../labor_cost') ?? 0));
        $globalDiscount = max(0, (float) ($get('../../discount_amount') ?? 0));

        $set('../../parts_cost', $partsCost);
        $set('../../total_cost', round(max(0, $partsCost + $laborCost - $globalDiscount), 2));
    }

    private static function statusOptions(): array
    {
        return [
            'open'             => __('messages.open'),
            'diagnostic'       => __('messages.diagnostic'),
            'waiting_approval' => __('messages.waiting_approval'),
            'approved'         => __('messages.approved'),
            'waiting_parts'    => __('messages.waiting_parts'),
            'in_progress'      => __('messages.in_progress'),
            'completed'        => __('messages.completed'),
            'delivered'        => __('messages.delivered'),
            'closed'           => __('messages.closed'),
            'cancelled'        => __('messages.cancelled'),
        ];
    }

    private static function isAdmin(): bool
    {
        return auth()->user()?->hasAnyRole(['Admin', 'Super Admin']) ?? false;
    }
}
