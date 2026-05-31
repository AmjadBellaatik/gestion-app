<?php

namespace App\Filament\Resources\Brands;

use App\Filament\Resources\Brands\Pages\CreateBrand;
use App\Filament\Resources\Brands\Pages\EditBrand;
use App\Filament\Resources\Brands\Pages\ListBrands;
use App\Filament\Resources\Brands\Pages\ViewBrand;
use App\Models\Brand;
use App\Models\Company;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('messages.brands');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('messages.settings');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('messages.brand'))
                ->schema([
                    Select::make('company_id')
                        ->label(__('messages.company'))
                        ->options(fn () => Company::query()->orderBy('name')->pluck('name', 'id')->toArray())
                        ->searchable()
                        ->preload()
                        ->required(),
                    TextInput::make('name')->label(__('messages.name'))->required()->maxLength(255),
                    TextInput::make('accreditation_reference')->label(__('messages.accreditation_reference'))->maxLength(255),
                    FileUpload::make('logo')->label(__('messages.logo'))->image()->directory('brands/logos'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label(__('messages.name'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('company.name')->label(__('messages.company'))->sortable(),
                Tables\Columns\TextColumn::make('motorcycle_models_count')
                    ->label(__('messages.motorcycle_models'))
                    ->counts('motorcycleModels'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBrands::route('/'),
            'create' => CreateBrand::route('/create'),
            'view' => ViewBrand::route('/{record}'),
            'edit' => EditBrand::route('/{record}/edit'),
        ];
    }
}
