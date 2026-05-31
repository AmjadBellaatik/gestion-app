<?php

namespace App\Filament\Resources\Clients\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RepairTicketsRelationManager extends RelationManager
{
    protected static string $relationship = 'repairTickets';

    public static function getTitle(
        \Illuminate\Database\Eloquent\Model $ownerRecord,
        string $pageClass
    ): string {
        return __('messages.repairs');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('ticket_number')
                    ->label(__('messages.ticket_number'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('motorcycleUnit.full_name')
                    ->label(__('messages.motorcycle'))
                    ->placeholder('-'),

                TextColumn::make('status')
                    ->label(__('messages.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending'    => __('messages.pending'),
                        'in_progress'=> __('messages.in_progress'),
                        'completed'  => __('messages.completed'),
                        'cancelled'  => __('messages.cancelled'),
                        default      => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'pending'     => 'warning',
                        'in_progress' => 'info',
                        'completed'   => 'success',
                        'cancelled'   => 'danger',
                        default       => 'gray',
                    }),

                TextColumn::make('total')
                    ->label(__('messages.total'))
                    ->money('MAD')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('messages.created_at'))
                    ->date()
                    ->sortable(),

            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(
                fn ($record) => route('filament.admin.resources.repair-tickets.view', $record)
            );
    }
}
