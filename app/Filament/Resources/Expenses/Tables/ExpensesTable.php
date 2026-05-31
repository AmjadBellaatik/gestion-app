<?php

namespace App\Filament\Resources\Expenses\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

use Filament\Tables\Table;

use Filament\Tables\Columns\TextColumn;

class ExpensesTable
{
    public static function configure(
        Table $table
    ): Table {

        return $table

            ->columns([

                TextColumn::make(
                    'title'
                )

                    ->label(
                        __('messages.title')
                    )

                    ->searchable()

                    ->sortable(),

                TextColumn::make(
                    'category'
                )

                    ->label(
                        __('messages.category')
                    )

                    ->badge()

                    ->searchable(),

                TextColumn::make(
                    'amount'
                )

                    ->label(
                        __('messages.amount')
                    )

                    ->money('MAD')

                    ->sortable(),

                TextColumn::make(
                    'payment_method'
                )

                    ->label(
                        __('messages.payment_method')
                    )

                    ->badge(),

                TextColumn::make(
                    'expense_date'
                )

                    ->label(
                        __('messages.expense_date')
                    )

                    ->date()

                    ->sortable(),

                TextColumn::make(
                    'createdBy.name'
                )

                    ->label(
                        __('messages.created_by')
                    ),

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

                //

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