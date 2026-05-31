<?php

namespace App\Filament\Resources\Reimbursements\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

use Filament\Tables\Columns\TextColumn;

use Filament\Tables\Filters\SelectFilter;

use Filament\Tables\Table;

class ReimbursementsTable
{
    public static function configure(
        Table $table
    ): Table {

        return $table

            ->columns([

                TextColumn::make(
                    'reference_number'
                )

                    ->label(
                        __('messages.reference_number')
                    )

                    ->searchable()

                    ->sortable(),

                TextColumn::make(
                    'supplier.name'
                )

                    ->label(
                        __('messages.supplier')
                    )

                    ->searchable()

                    ->sortable(),

                TextColumn::make(
                    'warrantyClaim.reference'
                )

                    ->label(
                        __('messages.warranty_claim')
                    )

                    ->searchable()

                    ->sortable(),

                TextColumn::make(
                    'request_date'
                )

                    ->label(
                        __('messages.request_date')
                    )

                    ->date()

                    ->sortable(),

                TextColumn::make(
                    'expected_payment_date'
                )

                    ->label(
                        __('messages.expected_payment_date')
                    )

                    ->date()

                    ->sortable(),

                TextColumn::make(
                    'paid_date'
                )

                    ->label(
                        __('messages.paid_date')
                    )

                    ->date()

                    ->sortable(),

                TextColumn::make(
                    'requested_amount'
                )

                    ->label(
                        __('messages.requested_amount')
                    )

                    ->money('MAD')

                    ->sortable(),

                TextColumn::make(
                    'approved_amount'
                )

                    ->label(
                        __('messages.approved_amount')
                    )

                    ->money('MAD')

                    ->sortable(),

                TextColumn::make(
                    'paid_amount'
                )

                    ->label(
                        __('messages.paid_amount')
                    )

                    ->money('MAD')

                    ->sortable(),

                TextColumn::make(
                    'status'
                )

                    ->label(
                        __('messages.status')
                    )

                    ->badge()

                    ->formatStateUsing(
                        fn ($state) => match ($state) {

                            'pending' =>
                                __('messages.pending'),

                            'approved' =>
                                __('messages.approved'),

                            'paid' =>
                                __('messages.paid'),

                            'rejected' =>
                                __('messages.rejected'),

                            default => $state,

                        }
                    )

                    ->color(fn ($state) => match ($state) {

                        'pending' => 'warning',

                        'approved' => 'info',

                        'paid' => 'success',

                        'rejected' => 'danger',

                        default => 'gray',

                    }),

                TextColumn::make(
                    'created_at'
                )

                    ->label(
                        __('messages.created_at')
                    )

                    ->dateTime()

                    ->sortable()

                    ->toggleable(
                        isToggledHiddenByDefault: true
                    ),

                TextColumn::make(
                    'updated_at'
                )

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

                SelectFilter::make(
                    'status'
                )

                    ->label(
                        __('messages.status')
                    )

                    ->options([

                        'pending' =>
                            __('messages.pending'),

                        'approved' =>
                            __('messages.approved'),

                        'paid' =>
                            __('messages.paid'),

                        'rejected' =>
                            __('messages.rejected'),

                    ]),

            ])

            ->actions([

            ])

            ->toolbarActions([

                BulkActionGroup::make([

                    DeleteBulkAction::make(),

                ]),

            ]);
    }
}