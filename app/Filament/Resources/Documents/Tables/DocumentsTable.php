<?php

namespace App\Filament\Resources\Documents\Tables;

use App\Models\Document;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with([
                'client', 'reseller', 'sale.reseller',
            ]))
            ->columns([
                TextColumn::make('document_number')
                    ->label(__('messages.document_number'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('documentType.name')
                    ->label(__('messages.document_type'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('client_display')
                    ->label(__('messages.client'))
                    ->getStateUsing(fn (Document $record): ?string => $record->partyDisplayName())
                    ->placeholder('-'),
                TextColumn::make('total_amount')
                    ->label(__('messages.total_amount'))
                    ->money('MAD')
                    ->sortable(),
                TextColumn::make('preview_pdf')
                    ->label(__('messages.preview'))
                    ->state(fn () => __('messages.preview'))
                    ->url(fn (Document $record) => route('documents.pdf', $record), true),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
