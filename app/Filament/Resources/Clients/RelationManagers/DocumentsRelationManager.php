<?php

namespace App\Filament\Resources\Clients\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    public static function getTitle(
        \Illuminate\Database\Eloquent\Model $ownerRecord,
        string $pageClass
    ): string {
        return __('messages.documents');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('document_number')
                    ->label(__('messages.reference'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('documentType.name')
                    ->label(__('messages.type'))
                    ->placeholder('-'),

                TextColumn::make('total_amount')
                    ->label(__('messages.total'))
                    ->money('MAD')
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('messages.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'draft'     => __('messages.draft'),
                        'generated' => __('messages.generated'),
                        'validated' => __('messages.validated'),
                        'cancelled' => __('messages.cancelled'),
                        default     => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'draft'     => 'gray',
                        'generated' => 'info',
                        'validated' => 'success',
                        'cancelled' => 'danger',
                        default     => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label(__('messages.created_at'))
                    ->date()
                    ->sortable(),

            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(
                fn ($record) => route('filament.admin.resources.documents.view', $record)
            );
    }
}
