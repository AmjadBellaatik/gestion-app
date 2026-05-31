<?php

namespace App\Filament\Resources\Reimbursements;

use App\Filament\Resources\Reimbursements\Pages\CreateReimbursement;
use App\Filament\Resources\Reimbursements\Pages\EditReimbursement;
use App\Filament\Resources\Reimbursements\Pages\ListReimbursements;
use App\Filament\Resources\Reimbursements\Pages\ViewReimbursement;
use App\Filament\Resources\Reimbursements\Schemas\ReimbursementForm;
use App\Filament\Resources\Reimbursements\Schemas\ReimbursementInfolist;
use App\Filament\Resources\Reimbursements\Tables\ReimbursementsTable;
use App\Models\Reimbursement;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ReimbursementResource extends Resource
{
    protected static ?string $model =
        Reimbursement::class;

    protected static string | \BackedEnum | null $navigationIcon =
        'heroicon-o-banknotes';

    protected static ?int $navigationSort =
        4;

    protected static ?string $recordTitleAttribute =
        'reference_number';

    public static function getNavigationLabel(): string
    {
        return __('messages.reimbursements');
    }

    public static function getNavigationGroup(): string | \UnitEnum | null
    {
        return __('messages.workshop');
    }

    public static function getModelLabel(): string
    {
        return __('messages.reimbursement');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.reimbursements');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can(
            'manage_reimbursements'
        ) ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(
            'manage_reimbursements'
        ) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can(
            'manage_reimbursements'
        ) ?? false;
    }

    public static function form(
        Schema $schema
    ): Schema {

        return ReimbursementForm::configure(
            $schema
        );
    }

    public static function infolist(
        Schema $schema
    ): Schema {

        return ReimbursementInfolist::configure(
            $schema
        );
    }

    public static function table(
        Table $table
    ): Table {

        return ReimbursementsTable::configure(
            $table
        );
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

            'index' =>
                ListReimbursements::route('/'),

            'create' =>
                CreateReimbursement::route('/create'),

            'view' =>
                ViewReimbursement::route('/{record}'),

            'edit' =>
                EditReimbursement::route('/{record}/edit'),

        ];
    }
}
