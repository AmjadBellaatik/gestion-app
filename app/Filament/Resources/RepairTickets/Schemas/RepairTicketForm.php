<?php

namespace App\Filament\Resources\RepairTickets\Schemas;

use App\Models\Client;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Technician;
use App\Models\MotorcycleUnit;
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
use Filament\Schemas\Schema;

class RepairTicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            /*
            |--------------------------------------------------------------------------
            | Ticket Header
            |--------------------------------------------------------------------------
            */

            Section::make(__('messages.ticket_information'))
                ->schema([
                    Grid::make(3)->schema([

                        TextInput::make('ticket_number')
                            ->label(__('messages.ticket_number'))
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder(__('messages.auto_generated'))
                            ->maxLength(50),

                        Select::make('repair_type')
                            ->label(__('messages.repair_type'))
                            ->options([
                                'warranty'      => __('messages.warranty'),
                                'paid'          => __('messages.paid'),
                                'reimbursement' => __('messages.reimbursement'),
                                'internal'      => __('messages.internal'),
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
                ]),

            /*
            |--------------------------------------------------------------------------
            | Vehicle & Client — mode selector
            |--------------------------------------------------------------------------
            */

            Section::make(__('messages.vehicle_and_client'))
                ->schema([

                    Grid::make(2)->schema([

                        Toggle::make('_linked_to_sale')
                            ->label(__('messages.linked_to_sale'))
                            ->live()
                            ->dehydrated(false)
                            ->default(false),

                        Toggle::make('is_foreign_vehicle')
                            ->label(__('messages.foreign_vehicle'))
                            ->live()
                            ->default(false)
                            ->hidden(fn ($get) => (bool) $get('_linked_to_sale')),

                    ]),

                    /*
                    | Mode 1: Linked to a sale
                    */

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
                        ->visible(fn ($get) => (bool) $get('_linked_to_sale'))
                        ->afterStateUpdated(function ($state, callable $set) {
                            if (! $state) {
                                return;
                            }
                            $sale = Sale::query()
                                ->with(['client', 'items.motorcycleUnit'])
                                ->find($state);
                            if (! $sale) {
                                return;
                            }
                            $set('client_id', $sale->client_id);
                            $unitId = $sale->items
                                ->firstWhere(fn ($i) => ! is_null($i->motorcycle_unit_id))
                                ?->motorcycle_unit_id;
                            if ($unitId) {
                                $set('motorcycle_unit_id', $unitId);
                            }
                        }),

                    /*
                    | Mode 2: Inventory vehicle (not foreign, not from sale)
                    */

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
                        ->visible(fn ($get) => ! (bool) $get('_linked_to_sale') && ! (bool) $get('is_foreign_vehicle'))
                        ->afterStateUpdated(function ($state, callable $set) {
                            if (! $state) {
                                return;
                            }
                            $unit = MotorcycleUnit::query()->with('client')->find($state);
                            if ($unit?->client_id) {
                                $set('client_id', $unit->client_id);
                            }
                        }),

                    /*
                    | Mode 3: Foreign / non-inventory vehicle
                    */

                    Grid::make(3)->schema([

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

                        TextInput::make('foreign_mileage')
                            ->label(__('messages.mileage'))
                            ->numeric()
                            ->default(0),

                    ])->visible(fn ($get) => ! (bool) $get('_linked_to_sale') && (bool) $get('is_foreign_vehicle')),

                    /*
                    | Client (common to all modes)
                    */

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

                    Grid::make(2)->schema([

                        TextInput::make('mileage')
                            ->label(__('messages.mileage_at_reception'))
                            ->numeric()
                            ->default(0),

                        Select::make('technician_id')
                            ->label(__('messages.lead_technician'))
                            ->options(fn () => Technician::query()
                                ->where('is_active', true)
                                ->pluck('name', 'id')
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')->label(__('messages.name'))->required(),
                                TextInput::make('phone')->label(__('messages.phone'))->tel(),
                                TextInput::make('speciality')->label(__('messages.speciality')),
                            ])
                            ->createOptionUsing(fn (array $data) => Technician::create(array_merge(['is_active' => true], $data))->id),

                    ]),

                ]),

            /*
            |--------------------------------------------------------------------------
            | Diagnostic
            |--------------------------------------------------------------------------
            */

            Section::make(__('messages.diagnostic'))
                ->schema([

                    Textarea::make('problem_description')
                        ->label(__('messages.problem_description'))
                        ->rows(3)
                        ->columnSpanFull(),

                    Textarea::make('diagnostic')
                        ->label(__('messages.diagnostic_notes'))
                        ->rows(4)
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
            |--------------------------------------------------------------------------
            | Parts (part / accessory type products)
            |--------------------------------------------------------------------------
            */

            Section::make(__('messages.parts_used'))
                ->schema([
                    Repeater::make('parts')
                        ->relationship('parts')
                        ->label('')
                        ->schema([

                            Hidden::make('item_type')->default('part'),

                            Grid::make(4)->schema([

                                Select::make('product_id')
                                    ->label(__('messages.part'))
                                    ->options(fn () => Product::query()
                                        ->whereIn('type', ['part', 'accessory'])
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

                        ])
                        ->addActionLabel(__('messages.add_part'))
                        ->defaultItems(0)
                        ->reorderable()
                        ->collapsible(),
                ]),

            /*
            |--------------------------------------------------------------------------
            | Consumables
            |--------------------------------------------------------------------------
            */

            Section::make(__('messages.consumables_used'))
                ->schema([
                    Repeater::make('consumables')
                        ->relationship('consumables')
                        ->label('')
                        ->schema([

                            Hidden::make('item_type')->default('consumable'),

                            Grid::make(4)->schema([

                                Select::make('product_id')
                                    ->label(__('messages.consumable'))
                                    ->options(fn () => Product::query()
                                        ->where('type', 'consumable')
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

                        ])
                        ->addActionLabel(__('messages.add_consumable'))
                        ->defaultItems(0)
                        ->reorderable()
                        ->collapsible(),
                ]),

            /*
            |--------------------------------------------------------------------------
            | Intervention Steps
            |--------------------------------------------------------------------------
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
                                    ->options(fn () => User::query()
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->toArray())
                                    ->searchable(),

                                DateTimePicker::make('performed_at')
                                    ->label(__('messages.performed_at')),

                            ]),

                        ])
                        ->addActionLabel(__('messages.add_step'))
                        ->defaultItems(0)
                        ->reorderable('sort_order')
                        ->collapsible(),
                ]),

            /*
            |--------------------------------------------------------------------------
            | Assigned Technicians (admin / super admin only)
            |--------------------------------------------------------------------------
            */

            Section::make(__('messages.assigned_technicians'))
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
                                    ->createOptionUsing(fn (array $data) => Technician::create(array_merge(['is_active' => true], $data))->id),

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
                ])
                ->visible(fn () => auth()->user()?->hasAnyRole(['Super Admin', 'Admin']) ?? false),

            /*
            |--------------------------------------------------------------------------
            | Financials & Discount
            |--------------------------------------------------------------------------
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
            |--------------------------------------------------------------------------
            | Status & Notes
            |--------------------------------------------------------------------------
            */

            Section::make(__('messages.status_and_notes'))
                ->schema([
                    Grid::make(2)->schema([

                        Select::make('status')
                            ->label(__('messages.status'))
                            ->options([
                                'open'        => __('messages.open'),
                                'diagnostic'  => __('messages.diagnostic'),
                                'assigned'    => __('messages.assigned'),
                                'in_progress' => __('messages.in_progress'),
                                'completed'   => __('messages.completed'),
                                'delivered'   => __('messages.delivered'),
                                'cancelled'   => __('messages.cancelled'),
                            ])
                            ->default('open')
                            ->required(),

                        Select::make('payment_status')
                            ->label(__('messages.payment_status'))
                            ->options([
                                'unpaid'  => __('messages.unpaid'),
                                'partial' => __('messages.partial'),
                                'paid'    => __('messages.paid'),
                            ])
                            ->default('unpaid')
                            ->required(),

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

        ]);
    }
}
