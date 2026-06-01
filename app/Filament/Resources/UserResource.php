<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput as FormTextInput;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;

use Filament\Resources\Resource;

use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model =
        User::class;

    protected static string | \BackedEnum | null $navigationIcon =
        'heroicon-o-users';

    protected static ?int $navigationSort =
        4;

    public static function getNavigationLabel(): string
    {
        return __('messages.users');
    }

    public static function getModelLabel(): string
    {
        return __('messages.user');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.users');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('messages.settings');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole([

            'Super Admin',
            'Admin',

        ]) ?? false;
    }

    public static function form(
        Schema $schema
    ): Schema {

        return $schema

            ->components([

                Grid::make(2)

                    ->schema([

                        TextInput::make('name')

                            ->label(
                                __('messages.name')
                            )

                            ->required()

                            ->maxLength(255),

                        TextInput::make('email')

                            ->label(
                                __('messages.email')
                            )

                            ->email()

                            ->required()

                            ->unique(
                                ignoreRecord: true
                            ),

                        TextInput::make('password')

                            ->label(
                                __('messages.password')
                            )

                            ->password()

                            ->dehydrated(
                                fn ($state) => filled($state)
                            )

                            ->dehydrateStateUsing(
                                fn ($state) => Hash::make($state)
                            )

                            ->required(
                                fn ($operation) => $operation === 'create'
                            ),

                        TextInput::make('phone')

                            ->label(
                                __('messages.phone')
                            ),

                        Textarea::make('address')

                            ->label(
                                __('messages.address')
                            )

                            ->columnSpanFull(),

                        Select::make('language')

                            ->label(
                                __('messages.language')
                            )

                            ->options([

                                'fr' => __('messages.french'),

                                'en' => __('messages.english'),

                                'ar' => __('messages.arabic'),

                            ])

                            ->default('fr')

                            ->required(),

                        Toggle::make('status')

                            ->label(
                                __('messages.status')
                            )

                            ->default(true),

                        FileUpload::make('profile_picture')

                            ->label(
                                __('messages.profile_picture')
                            )

                            ->avatar()

                            ->imageEditor()

                            ->imageAspectRatio('1:1')
                            ->automaticallyCropImagesToAspectRatio()

                            ->disk('public')

                            ->directory('profile_pictures')

                            ->image(),

                        Select::make('companies')

                            ->label(
                                __('messages.companies')
                            )

                            ->relationship(
                                'companies',
                                'name'
                            )

                            ->multiple()

                            ->preload(),

                        Select::make('roles')

                            ->label(
                                __('messages.roles')
                            )

                            ->multiple()

                            ->live()

                            ->afterStateHydrated(function ($component, ?User $record): void {
                                $component->state(
                                    $record?->roles->pluck('name')->all() ?? []
                                );
                            })

                            ->afterStateUpdated(function (?array $state, callable $set): void {
                                if (empty($state)) {
                                    $set('permissions', []);
                                    return;
                                }

                                // Collect the union of permissions for all selected roles
                                $rolePermissions = Role::whereIn('name', $state)
                                    ->with('permissions')
                                    ->get()
                                    ->flatMap(fn ($role) => $role->permissions->pluck('name'))
                                    ->unique()
                                    ->sort()
                                    ->values()
                                    ->toArray();

                                $set('permissions', $rolePermissions);
                            })

                            ->options(function () {
                                $query = Role::query();

                                if (! auth()->user()?->hasRole('Super Admin')) {
                                    $query->where('name', '!=', 'Super Admin');
                                }

                                return $query->pluck('name', 'name');
                            }),

                        Select::make('permissions')

                            ->label(
                                __('messages.permissions')
                            )

                            ->helperText(__('messages.permissions_auto_filled'))

                            ->multiple()

                            ->preload()

                            ->afterStateHydrated(function ($component, ?User $record): void {
                                $component->state(
                                    $record?->permissions->pluck('name')->all() ?? []
                                );
                            })

                            ->options(fn () =>
                                Permission::query()
                                    ->orderBy('name')
                                    ->pluck('name', 'name')
                                    ->all()
                            ),

                    ]),

            ]);
    }

    public static function table(
        Table $table
    ): Table {

        return $table

            ->modifyQueryUsing(function (Builder $query): Builder {
                if (! auth()->user()?->hasRole('Super Admin')) {
                    $query->whereDoesntHave(
                        'roles',
                        fn (Builder $q) => $q->where('name', 'Super Admin')
                    );
                }

                return $query;
            })

            ->columns([

                ImageColumn::make('profile_picture')

                    ->label(
                        __('messages.profile_picture')
                    )

                    ->disk('public')

                    ->circular()

                    ->imageSize(40)

                    ->defaultImageUrl(fn (User $record): string =>
                        'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&color=ffffff&background=4f46e5&size=80'
                    ),

                TextColumn::make('name')

                    ->label(
                        __('messages.name')
                    )

                    ->searchable()

                    ->sortable(),

                TextColumn::make('email')

                    ->label(
                        __('messages.email')
                    )

                    ->searchable(),

                TextColumn::make('companies.name')

                    ->label(
                        __('messages.companies')
                    )

                    ->badge(),

                TextColumn::make('roles.name')

                    ->label(
                        __('messages.roles')
                    )

                    ->badge(),

                IconColumn::make('status')

                    ->label(
                        __('messages.status')
                    )

                    ->boolean(),

                TextColumn::make('last_login_at')

                    ->label(
                        __('messages.last_login_at')
                    )

                    ->dateTime(),

            ])

            ->actions([

                Action::make('toggleStatus')

                    ->label(
                        fn (User $record): string => $record->status
                            ? __('messages.suspend')
                            : __('messages.activate')
                    )

                    ->icon(
                        fn (User $record): string => $record->status
                            ? 'heroicon-o-pause-circle'
                            : 'heroicon-o-check-circle'
                    )

                    ->color(
                        fn (User $record): string => $record->status
                            ? 'warning'
                            : 'success'
                    )

                    ->requiresConfirmation()

                    ->action(function (User $record): void {

                        $record->update([

                            'status' => ! $record->status,

                        ]);
                    }),

                static::resetPasswordAction(),

                DeleteAction::make(),

            ]);
    }

    public static function getPages(): array
    {
        return [

            'index' => Pages\ListUsers::route('/'),

            'create' => Pages\CreateUser::route('/create'),

            'view' => Pages\ViewUser::route('/{record}'),

            'edit' => Pages\EditUser::route('/{record}/edit'),

        ];
    }

    /**
     * Shared "Reset Password" action — used in both the table and the view page.
     */
    public static function resetPasswordAction(): Action
    {
        return Action::make('resetPassword')

            ->label(__('messages.reset_password'))

            ->icon('heroicon-o-key')

            ->color('warning')

            // Only visible to Admin / Super Admin
            ->visible(fn () => auth()->user()?->hasAnyRole(['Super Admin', 'Admin']) ?? false)

            // Admins cannot reset a Super Admin's password (only Super Admin can)
            ->disabled(fn (User $record): bool =>
                $record->hasRole('Super Admin')
                && ! auth()->user()?->hasRole('Super Admin')
            )

            ->tooltip(fn (User $record): ?string =>
                ($record->hasRole('Super Admin') && ! auth()->user()?->hasRole('Super Admin'))
                    ? __('messages.cannot_reset_super_admin')
                    : null
            )

            ->form([

                FormTextInput::make('password')

                    ->label(__('messages.new_password'))

                    ->password()

                    ->revealable()

                    ->required()

                    ->minLength(8)

                    ->rules(['confirmed']),

                FormTextInput::make('password_confirmation')

                    ->label(__('messages.confirm_password'))

                    ->password()

                    ->revealable()

                    ->required()

                    ->dehydrated(false),

            ])

            ->modalHeading(__('messages.reset_password'))

            ->modalDescription(fn (User $record): string =>
                __('messages.reset_password_for', ['name' => $record->name])
            )

            ->modalSubmitActionLabel(__('messages.reset_password'))

            ->modalIcon('heroicon-o-key')

            ->action(function (User $record, array $data): void {

                $record->update([
                    'password' => Hash::make($data['password']),
                ]);

                \Filament\Notifications\Notification::make()
                    ->title(__('messages.password_reset_success'))
                    ->success()
                    ->send();
            });
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole([

            'Super Admin',
            'Admin',

        ]) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasAnyRole([

            'Super Admin',
            'Admin',

        ]) ?? false;
    }

    public static function canEdit(
        $record
    ): bool {

        return auth()->user()?->hasAnyRole([

            'Super Admin',
            'Admin',

        ]) ?? false;
    }

    public static function canDelete(
        $record
    ): bool {

        return auth()->user()?->hasAnyRole([

            'Super Admin',
            'Admin',

        ]) ?? false;
    }
}
