<?php

namespace App\Filament\Resources\Resellers;

use App\Filament\Resources\Resellers\Pages\CreateReseller;
use App\Filament\Resources\Resellers\Pages\EditReseller;
use App\Filament\Resources\Resellers\Pages\ListResellers;
use App\Filament\Resources\Resellers\Pages\ViewReseller;

use App\Filament\Resources\Resellers\Schemas\ResellerForm;
use App\Filament\Resources\Resellers\Schemas\ResellerInfolist;

use App\Models\Reseller;

use BackedEnum;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;

use Filament\Resources\Resource;

use Filament\Schemas\Schema;

use Filament\Support\Icons\Heroicon;

use Filament\Tables;
use Filament\Tables\Table;

class ResellerResource extends Resource
{
    protected static ?string $model =
        Reseller::class;

    protected static string|BackedEnum|null $navigationIcon =
        Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('messages.resellers');
    }

    public static function getModelLabel(): string
    {
        return __('messages.reseller');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.resellers');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('messages.commercial');
    }

    public static function form(
        Schema $schema
    ): Schema {

        return ResellerForm::configure(
            $schema
        );
    }

    public static function infolist(
        Schema $schema
    ): Schema {

        return ResellerInfolist::configure(
            $schema
        );
    }

    public static function table(
        Table $table
    ): Table {

        return $table

            ->columns([

                Tables\Columns\TextColumn::make(
                    'name'
                )

                    ->label(
                        __('messages.reseller')
                    )

                    ->searchable()

                    ->sortable(),

                Tables\Columns\TextColumn::make(
                    'phone'
                )

                    ->label(
                        __('messages.phone')
                    )

                    ->searchable(),

                Tables\Columns\TextColumn::make(
                    'email'
                )

                    ->label(
                        __('messages.email')
                    )

                    ->searchable(),

                Tables\Columns\TextColumn::make(
                    'credit_balance'
                )

                    ->label(
                        __('messages.credit_balance')
                    )

                    ->money('MAD')

                    ->sortable(),

                Tables\Columns\TextColumn::make(
                    'total_orders'
                )

                    ->label(
                        __('messages.total_orders')
                    )

                    ->sortable(),

                Tables\Columns\IconColumn::make(
                    'is_blocked'
                )

                    ->label(
                        __('messages.status')
                    )

                    ->boolean()

                    ->trueIcon(
                        'heroicon-o-x-circle'
                    )

                    ->falseIcon(
                        'heroicon-o-check-circle'
                    )

                    ->trueColor(
                        'danger'
                    )

                    ->falseColor(
                        'success'
                    ),

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

                //
            ])

            ->actions([

                Action::make('view')

                    ->label(
                        __('messages.view')
                    )

                    ->icon(
                        'heroicon-o-eye'
                    )

                    ->url(
                        fn ($record) =>

                            static::getUrl(
                                'view',
                                [
                                    'record' => $record,
                                ]
                            )
                    ),

                Action::make('edit')

                    ->label(
                        __('messages.edit')
                    )

                    ->icon(
                        'heroicon-o-pencil-square'
                    )

                    ->url(
                        fn ($record) =>

                            static::getUrl(
                                'edit',
                                [
                                    'record' => $record,
                                ]
                            )
                    ),

                DeleteAction::make(),

            ])

            ->toolbarActions([

                BulkActionGroup::make([

                    DeleteBulkAction::make(),

                ]),

            ]);
    }

    public static function getRelations(): array
    {
        return [

            //
        ];
    }

    public static function getPages(): array
    {
        return [

            'index' => ListResellers::route('/'),

            'create' => CreateReseller::route('/create'),

            'view' => ViewReseller::route('/{record}'),

            'edit' => EditReseller::route('/{record}/edit'),

        ];
    }
}