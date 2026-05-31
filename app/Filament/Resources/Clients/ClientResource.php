<?php

namespace App\Filament\Resources\Clients;

use App\Filament\Resources\Clients\Pages\CreateClient;
use App\Filament\Resources\Clients\Pages\EditClient;
use App\Filament\Resources\Clients\Pages\ListClients;
use App\Filament\Resources\Clients\Pages\ViewClient;

use App\Filament\Resources\Clients\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\Clients\RelationManagers\PaymentsRelationManager;
use App\Filament\Resources\Clients\RelationManagers\RepairTicketsRelationManager;
use App\Filament\Resources\Clients\RelationManagers\SalesRelationManager;
use App\Filament\Resources\Clients\RelationManagers\WarrantiesRelationManager;

use App\Filament\Resources\Clients\Schemas\ClientForm;
use App\Filament\Resources\Clients\Schemas\ClientInfolist;

use App\Models\Client;

use BackedEnum;

use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;

use Filament\Resources\Resource;

use Filament\Schemas\Schema;

use Filament\Support\Icons\Heroicon;

use Filament\Tables;
use Filament\Tables\Table;

class ClientResource extends Resource
{
    protected static ?string $model =
        Client::class;

    protected static string|BackedEnum|null $navigationIcon =
        Heroicon::OutlinedUserGroup;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'display_name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['first_name', 'last_name', 'company_name', 'phone', 'email'];
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return array_filter([
            __('messages.phone') => $record->phone,
            __('messages.email') => $record->email,
        ]);
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.clients');
    }

    public static function getModelLabel(): string
    {
        return __('messages.client');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.clients');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('messages.commercial');
    }

    public static function form(
        Schema $schema
    ): Schema {

        return ClientForm::configure(
            $schema
        );
    }

    public static function infolist(
        Schema $schema
    ): Schema {

        return ClientInfolist::configure(
            $schema
        );
    }

    public static function table(
        Table $table
    ): Table {

        return $table

            ->columns([

                Tables\Columns\TextColumn::make(
                    'display_name'
                )

                    ->label(
                        __('messages.client')
                    )

                    ->searchable()

                    ->sortable(),

                Tables\Columns\TextColumn::make(
                    'client_type'
                )

                    ->label(
                        __('messages.client_type')
                    )

                    ->badge()

                    ->formatStateUsing(
                        fn (
                            string $state
                        ): string => match ($state) {

                            'person' =>

                                __('messages.person'),

                            'company' =>

                                __('messages.company'),

                            'administration' =>

                                __('messages.administration'),

                            default => $state,
                        }
                    ),

                Tables\Columns\TextColumn::make(
                    'phone'
                )

                    ->label(
                        __('messages.phone')
                    ),

                Tables\Columns\TextColumn::make(
                    'email'
                )

                    ->label(
                        __('messages.email')
                    ),

                Tables\Columns\TextColumn::make(
                    'balance'
                )

                    ->label(
                        __('messages.balance')
                    )

                    ->money('MAD'),

                Tables\Columns\IconColumn::make(
                    'is_blocked'
                )

                    ->label(
                        __('messages.blocked')
                    )

                    ->boolean()

                    ->trueColor('danger')

                    ->falseColor('success')

                    ->trueIcon('heroicon-o-x-circle')

                    ->falseIcon('heroicon-o-check-circle'),

                Tables\Columns\TextColumn::make(
                    'created_at'
                )

                    ->label(
                        __('messages.created_at')
                    )

                    ->dateTime()

                    ->sortable(),

            ])

            ->filters([

                Tables\Filters\SelectFilter::make(
                    'client_type'
                )

                    ->label(
                        __('messages.client_type')
                    )

                    ->options([

                        'person' =>

                            __('messages.person'),

                        'company' =>

                            __('messages.company'),

                        'administration' =>

                            __('messages.administration'),

                    ]),

            ])

            ->recordUrl(fn ($record) => self::getUrl('view', ['record' => $record]))
            ->actions([

                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),

            ])

            ->toolbarActions([

                DeleteBulkAction::make(),

            ]);
    }

    public static function getRelations(): array
    {
        return [
            SalesRelationManager::class,
            PaymentsRelationManager::class,
            RepairTicketsRelationManager::class,
            WarrantiesRelationManager::class,
            DocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [

            'index' => ListClients::route('/'),

            'create' => CreateClient::route('/create'),

            'view' => ViewClient::route('/{record}'),

            'edit' => EditClient::route('/{record}/edit'),

        ];
    }
}
