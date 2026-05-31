<?php

namespace App\Filament\Resources\RepairTickets;

use App\Filament\Resources\RepairTickets\Pages\CreateRepairTicket;
use App\Filament\Resources\RepairTickets\Pages\EditRepairTicket;
use App\Filament\Resources\RepairTickets\Pages\ListRepairTickets;
use App\Filament\Resources\RepairTickets\Pages\ViewRepairTicket;
use App\Filament\Resources\RepairTickets\Schemas\RepairTicketForm;
use App\Filament\Resources\RepairTickets\Schemas\RepairTicketInfolist;
use App\Filament\Resources\RepairTickets\Tables\RepairTicketsTable;

use App\Models\RepairTicket;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class RepairTicketResource extends Resource
{
    protected static ?string $model = RepairTicket::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'ticket_number';

    public static function getGloballySearchableAttributes(): array
    {
        return ['ticket_number', 'problem_description'];
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return array_filter([
            __('messages.status') => $record->status,
            __('messages.client') => $record->client?->display_name,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    */

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('manage_repairs') ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('manage_repairs') ?? false;
    }

    /*
    |--------------------------------------------------------------------------
    | Labels
    |--------------------------------------------------------------------------
    */

    public static function getNavigationLabel(): string
    {
        return __('messages.repairs');
    }

    public static function getModelLabel(): string
    {
        return __('messages.repair');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.repairs');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('messages.workshop');
    }

    /*
    |--------------------------------------------------------------------------
    | Form / Table / Infolist
    |--------------------------------------------------------------------------
    */

    public static function form(Schema $schema): Schema
    {
        return RepairTicketForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RepairTicketsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RepairTicketInfolist::configure($schema);
    }

    /*
    |--------------------------------------------------------------------------
    | Pages
    |--------------------------------------------------------------------------
    */

    public static function getPages(): array
    {
        return [
            'index'  => ListRepairTickets::route('/'),
            'create' => CreateRepairTicket::route('/create'),
            'view'   => ViewRepairTicket::route('/{record}'),
            'edit'   => EditRepairTicket::route('/{record}/edit'),
        ];
    }
}
