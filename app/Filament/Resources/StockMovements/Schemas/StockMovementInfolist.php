<?php

namespace App\Filament\Resources\StockMovements\Schemas;

use App\Models\Sale;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class StockMovementInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([

                /*
                |--------------------------------------------------------------
                | Movement Details — left column
                |--------------------------------------------------------------
                */
                Section::make(__('messages.movement_details'))
                    ->columnSpan(1)
                    ->schema([

                        TextEntry::make('type')
                            ->label(__('messages.type'))
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'in', 'entry', 'purchase', 'return' => 'success',
                                'out', 'exit', 'sale'               => 'danger',
                                'adjustment'                         => 'warning',
                                'transfer'                           => 'info',
                                default                              => 'gray',
                            }),

                        TextEntry::make('movement_type')
                            ->label(__('messages.movement_type'))
                            ->badge()
                            ->placeholder('-')
                            ->color('gray'),

                        TextEntry::make('product.name')
                            ->label(__('messages.product'))
                            ->weight(FontWeight::Bold)
                            ->placeholder('-'),

                        TextEntry::make('motorcycleUnit.chassis_number')
                            ->label(__('messages.motorcycle_unit'))
                            ->placeholder('-')
                            ->visible(fn ($record) => filled($record?->motorcycle_unit_id)),

                        TextEntry::make('warehouse.name')
                            ->label(__('messages.warehouse'))
                            ->placeholder('-'),

                        TextEntry::make('quantity')
                            ->label(__('messages.quantity'))
                            ->numeric()
                            ->weight(FontWeight::Bold)
                            ->color(fn ($record) => in_array($record?->type, ['in', 'entry', 'purchase', 'return']) ? 'success' : 'danger'),

                        TextEntry::make('unit_cost')
                            ->label(__('messages.unit_cost'))
                            ->money('MAD')
                            ->placeholder('-'),

                        TextEntry::make('notes')
                            ->label(__('messages.notes'))
                            ->placeholder('-')
                            ->columnSpanFull(),

                    ])
                    ->columns(2),

                /*
                |--------------------------------------------------------------
                | Context & Traceability — right column
                |--------------------------------------------------------------
                */
                Section::make(__('messages.context_traceability'))
                    ->columnSpan(1)
                    ->schema([

                        TextEntry::make('user.name')
                            ->label(__('messages.performed_by'))
                            ->icon('heroicon-o-user')
                            ->weight(FontWeight::Bold)
                            ->placeholder('-'),

                        TextEntry::make('created_at')
                            ->label(__('messages.date'))
                            ->dateTime()
                            ->icon('heroicon-o-calendar'),

                        TextEntry::make('reference')
                            ->label(__('messages.reference'))
                            ->placeholder('-')
                            ->icon('heroicon-o-hashtag')
                            ->copyable(),

                        TextEntry::make('reference_type')
                            ->label(__('messages.source_type'))
                            ->placeholder('-')
                            ->formatStateUsing(fn ($state) => match ($state) {
                                'App\\Models\\Sale'          => __('messages.sale'),
                                'App\\Models\\Purchase'      => __('messages.purchase'),
                                'App\\Models\\RepairTicket'  => __('messages.repair_ticket'),
                                'App\\Models\\StockTransfer' => __('messages.stock_transfer'),
                                null                         => '-',
                                default                      => class_basename($state),
                            })
                            ->badge()
                            ->color('gray'),

                        TextEntry::make('referenceable.sale_number')
                            ->label(__('messages.sale_number'))
                            ->weight(FontWeight::Bold)
                            ->icon('heroicon-o-document-text')
                            ->placeholder('-')
                            ->visible(fn ($record) => $record?->reference_type === 'App\\Models\\Sale'),

                        TextEntry::make('referenceable.ticket_number')
                            ->label(__('messages.repair_ticket'))
                            ->weight(FontWeight::Bold)
                            ->icon('heroicon-o-wrench-screwdriver')
                            ->placeholder('-')
                            ->visible(fn ($record) => $record?->reference_type === 'App\\Models\\RepairTicket'),

                        TextEntry::make('referenceable.reference_number')
                            ->label(__('messages.reference_number'))
                            ->weight(FontWeight::Bold)
                            ->icon('heroicon-o-document')
                            ->placeholder('-')
                            ->visible(fn ($record) => filled($record?->reference_type)
                                && !in_array($record?->reference_type, [
                                    'App\\Models\\Sale',
                                    'App\\Models\\RepairTicket',
                                ])),

                    ])
                    ->columns(2),

            ]);
    }
}
