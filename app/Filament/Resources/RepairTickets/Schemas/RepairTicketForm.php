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
use Filament\Forms\Get;

use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
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
                            } else {
                                $set('sale_id', null);
                                $set('motorcycle_unit_id', null);
                            }
                        }),
                ]),

            /*
            |------------------------------------------------------------------
            | Section 2 — Vehicle Information
            |------------------------------------------------------------------
            */

            Section::make(__('messages.vehicle_information'))
                ->schema([

                    // Mode: Linked to a sale
                    Select::make('sale_id')
                        ->label(__('messages.sale'))
                        ->options(fn () => Sale::query()
                            ->with('client')
                            ->orderByDesc('id')
                            ->get()
                            ->mapWithKeys(fn (Sale $sale) => [
                                $sale->id => ($sale->sale_number ?: 'SALE-' . $sale->id)
                                    . ' — ' . ($sale->client?->display_name ?? '-'),
                            ])
                            ->toArray())
                        ->searchable()
                        ->preload()
                        ->live()
                        ->required(fn (Get $get): bool => $get('_repair_source') === 'sale')
                        ->visible(fn ($get) => $get('_repair_source') === 'sale')
                        ->afterStateUpdated(function ($state, callable $set) {
                            if (! $state) {
                                return;
                            }
                            $sale = Sale::query()->with(['client', 'items.motorcycleUnit'])->where('id', $state)->first();
                            if (! $sale) {
                                return;
                            }
                            $set('client_id', $sale->client_id);
                            $unitId = $sale->items
                                ->whereNotNull('motorcycle_unit_id')
                                ->first()
                                ?->motorcycle_unit_id;
                            if ($unitId) {
                                $set('motorcycle_unit_id', $unitId);
                            }
                        }),

                    // Mode: In-stock inventory vehicle
                    Select::make('motorcycle_unit_id')
                        ->label(__('messages.motorcycle_unit'))
                        ->options(fn () => MotorcycleUnit::query()
                            ->whereIn('status', ['available', 'in_stock', 'in_repair'])
                            ->with('motorcycleModel')
                            ->get()
                            ->mapWithKeys(fn (MotorcycleUnit $u) => [
                                $u->id => implode(' — ', array_filter([
                                    $u->chassis_number,
                                    $u->motorcycleModel?->name,
                                ])),
                            ])
                            ->toArray())
                        ->searchable()
                        ->preload()
                        ->live()
                        ->required(fn (Get $get): bool => $get('_repair_source') === 'stock')
                        ->visible(fn ($get) => $get('_repair_source') === 'stock')
                        ->afterStateUpdated(function ($state, callable $set) {
                            if (! $state) {
                                return;
                            }
                            $unit = MotorcycleUnit::query()->with('client')->find($state);
                            if ($unit?->client_id) {
                                $set('client_id', $unit->client_id);
                            }
                        }),

                    // Mode: Foreign / non-inventory vehicle
                    Grid::make(3)
                        ->schema([
                            TextInput::make('foreign_brand')
                                ->label(__('messages.brand'))
                                ->maxLength(100),
                            TextInput::make('foreign_model')
                                ->label(__('messages.model'))
                                ->maxLength(100),
                            TextInput::make('foreign_chassis')
                                ->label(__('messages.chassis_number'))
                                ->maxLength(50),
                            TextInput::make('foreign_year')
                                ->label(__('messages.year'))
                                ->numeric()
                                ->minValue(1900)
                                ->maxValue((int) date('Y') + 2),
                            TextInput::make('foreign_color')
                                ->label(__('messages.color'))
                                ->maxLength(50),
                        ])
                        ->visible(fn ($get) => $get('_repair_source') === 'foreign'),

                    // Controlled by _repair_source — stored on the model
                    Hidden::make('is_foreign_vehicle')->default(false),

                    // Single reception mileage field — common to all vehicle types
                    TextInput::make('mileage')
                        ->label(__('messages.mileage_at_reception'))
                        ->numeric()
                        ->default(0)
                        ->suffix('km'),

                ]),

            /*
            |------------------------------------------------------------------
            | Section 3 — Customer and Assignment (both required)
            |------------------------------------------------------------------
            */

            Section::make(__('messages.customer_and_assignment'))
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
                ]),

            /*
            |------------------------------------------------------------------
            | Section 4 — Repair Details
            |------------------------------------------------------------------
            */

            Section::make(__('messages.repair_details'))
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
                ->schema([
                    Repeater::make('parts')
                        ->relationship('parts')
                        ->label('')
                        ->schema(self::itemSchema('part'))
                        ->addActionLabel(__('messages.add_part'))
                        ->defaultItems(0)
                        ->reorderable()
                        ->collapsible(),
                ]),

            Section::make(__('messages.consumables_used'))
                ->schema([
                    Repeater::make('consumables')
                        ->relationship('consumables')
                        ->label('')
                        ->schema(self::itemSchema('consumable'))
                        ->addActionLabel(__('messages.add_consumable'))
                        ->defaultItems(0)
                        ->reorderable()
                        ->collapsible(),
                ]),

            /*
            |------------------------------------------------------------------
            | Section 6 — Financial Information
            |------------------------------------------------------------------
            */

            Section::make(__('messages.financials'))
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('labor_cost')
                            ->label(__('messages.labor_cost'))
                            ->numeric()
                            ->default(0)
                            ->prefix('DH')
                            ->required(),
                        TextInput::make('parts_cost')
                            ->label(__('messages.parts_cost'))
                            ->numeric()
                            ->prefix('DH')
                            ->disabled()
                            ->dehydrated(),
                        TextInput::make('total_cost')
                            ->label(__('messages.total_cost'))
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
                            ->helperText(__('messages.discount_requires_validation')),
                        Textarea::make('discount_note')
                            ->label(__('messages.discount_note'))
                            ->rows(2),
                    ]),
                ]),

            /*
            |------------------------------------------------------------------
            | Section 7 — Status and Notes
            |------------------------------------------------------------------
            */

            Section::make(__('messages.status_and_notes'))
                ->schema([
                    Grid::make(2)->schema([
                        // Status: admins can override manually; others see it read-only
                        Select::make('status')
                            ->label(__('messages.status'))
                            ->options(self::statusOptions())
                            ->default('open')
                            ->required()
                            ->disabled(fn () => ! self::isAdmin())
                        ->dehydrated(fn () => self::isAdmin()),

                        // payment_status is derived from payments — display only
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
            | Intervention Steps
            |------------------------------------------------------------------
            */

            Section::make(__('messages.intervention_steps'))
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

            /*
            |------------------------------------------------------------------
            | Assigned Technicians — admin / super admin only
            |------------------------------------------------------------------
            */

            Section::make(__('messages.assigned_technicians'))
                ->visible(fn () => self::isAdmin())
                ->schema([
                    Repeater::make('assignedTechnicians')
                        ->relationship('assignedTechnicians')
                        ->label('')
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
                        ->collapsible(),
                ]),

        ]);
    }

    /*
    |------------------------------------------------------------------
    | Shared repeater schema for parts and consumables
    |------------------------------------------------------------------
    */

    private static function itemSchema(string $type): array
    {
        $types = $type === 'part' ? ['part', 'accessory'] : ['consumable'];
        $label = $type === 'part' ? __('messages.part') : __('messages.consumable');

        return [
            Hidden::make('item_type')->default($type),

            Grid::make(4)->schema([
                Select::make('product_id')
                    ->label($label)
                    ->options(fn () => Product::query()
                        ->whereIn('type', $types)
                        ->orderBy('name')
                        ->get()
                        ->mapWithKeys(fn ($p) => [
                            $p->id => $p->name . ' (' . __('messages.stock') . ': ' . (int) $p->current_stock . ')',
                        ])
                        ->toArray())
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if (! $state) {
                            return;
                        }
                        $product = Product::find($state);
                        if ($product) {
                            $set('unit_price', (float) $product->selling_price);
                        }
                    })
                    ->columnSpan(2),

                TextInput::make('quantity')
                    ->label(__('messages.quantity'))
                    ->numeric()
                    ->default(1)
                    ->minValue(0.01)
                    ->live()
                    ->columnSpan(1),

                TextInput::make('unit_price')
                    ->label(__('messages.unit_price'))
                    ->numeric()
                    ->prefix('DH')
                    ->live()
                    ->columnSpan(1),
            ]),

            Grid::make(2)->schema([
                TextInput::make('discount_amount')
                    ->label(__('messages.discount_amount'))
                    ->numeric()
                    ->default(0)
                    ->prefix('DH'),
                TextInput::make('total')
                    ->label(__('messages.subtotal'))
                    ->numeric()
                    ->prefix('DH')
                    ->disabled()
                    ->dehydrated(),
            ]),

            Textarea::make('item_description')
                ->label(__('messages.description'))
                ->rows(1)
                ->columnSpanFull(),
        ];
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
