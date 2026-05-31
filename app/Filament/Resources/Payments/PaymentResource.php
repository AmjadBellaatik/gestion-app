<?php

namespace App\Filament\Resources\Payments;

use App\Filament\Resources\Payments\Pages\CreatePayment;
use App\Filament\Resources\Payments\Pages\EditPayment;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Filament\Resources\Payments\Pages\ViewPayment;

use App\Filament\Resources\Payments\Schemas\PaymentForm;
use App\Filament\Resources\Payments\Schemas\PaymentInfolist;
use App\Filament\Resources\Payments\Tables\PaymentsTable;

use App\Models\Payment;

use Filament\Resources\Resource;

use Filament\Schemas\Schema;

use Filament\Tables\Table;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentResource extends Resource
{
    protected static ?string $model =
        Payment::class;

    protected static string | \BackedEnum | null $navigationIcon =
        'heroicon-o-credit-card';

    protected static ?int $navigationSort =
        1;

    protected static ?string $recordTitleAttribute =
        'reference';

    public static function getGloballySearchableAttributes(): array
    {
        return ['reference'];
    }

    public static function getGlobalSearchQuery(string $search): Builder
    {
        return parent::getGlobalSearchQuery($search)
            ->orWhereHas(
                'chequePayment',
                fn (Builder $q) => $q->where('cheque_number', 'like', "%{$search}%")
            );
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return array_filter([
            __('messages.payment_method') => $record->payment_method,
            __('messages.amount')         => $record->amount ? 'MAD ' . number_format($record->amount, 2) : null,
            __('messages.cheque_number')  => $record->chequePayment?->cheque_number,
        ]);
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.payments');
    }

    public static function getNavigationGroup(): string | \UnitEnum | null
    {
        return __('messages.accounting');
    }

    public static function getModelLabel(): string
    {
        return __('messages.payment');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.payments');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can(
            'manage_payments'
        ) ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(
            'manage_payments'
        ) ?? false;
    }

    public static function form(
        Schema $schema
    ): Schema {

        return PaymentForm::configure(
            $schema
        );
    }

    public static function infolist(
        Schema $schema
    ): Schema {

        return PaymentInfolist::configure(
            $schema
        );
    }

    public static function table(
        Table $table
    ): Table {

        return PaymentsTable::configure(
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
                ListPayments::route('/'),

            'create' =>
                CreatePayment::route('/create'),

            'view' =>
                ViewPayment::route('/{record}'),

            'edit' =>
                EditPayment::route('/{record}/edit'),

        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()

            ->withoutGlobalScopes([

                SoftDeletingScope::class,

            ]);
    }
}