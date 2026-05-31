<?php

namespace App\Filament\Resources\CompanySettings;

use App\Filament\Resources\CompanySettings\Pages\EditCompanySetting;
use App\Filament\Resources\CompanySettings\Pages\ListCompanySettings;
use App\Filament\Resources\CompanySettings\Schemas\CompanySettingForm;
use App\Filament\Resources\CompanySettings\Tables\CompanySettingsTable;
use App\Models\Company;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CompanySettingResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static string|\BackedEnum|null $navigationIcon =
        'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('messages.company_settings');
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('messages.settings');
    }

    public static function getModelLabel(): string
    {
        return __('messages.company');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.companies');
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        if (! $user) {

            return parent::getEloquentQuery()
                ->whereRaw('1 = 0');

        }

        $companyIds = $user
            ->companies()
            ->where(
                'companies.name',
                '!=',
                'Default Company'
            )
            ->pluck('companies.id')
            ->all();

        return parent::getEloquentQuery()
            ->whereKey($companyIds)
            ->where(
                'name',
                '!=',
                'Default Company'
            );
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(
        $record
    ): bool {
        return false;
    }

    public static function form(
        Schema $schema
    ): Schema {
        return CompanySettingForm::configure(
            $schema
        );
    }

    public static function table(
        Table $table
    ): Table {
        return CompanySettingsTable::configure(
            $table
        );
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompanySettings::route('/'),

            'edit' => EditCompanySetting::route('/{record}/edit'),
        ];
    }
}
