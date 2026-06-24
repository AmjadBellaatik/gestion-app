<?php

namespace App\Filament\Resources\AuditHistory;

use App\Filament\Resources\AuditHistory\Pages;
use App\Filament\Resources\AuditHistory\Schemas\AuditHistoryInfolist;
use App\Models\ActivityLog;
use App\Models\User;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Hidden;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AuditHistoryResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?int $navigationSort = 99;

    public static function getNavigationLabel(): string
    {
        return __('messages.audit_history');
    }

    public static function getModelLabel(): string
    {
        return __('messages.audit_entry');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.audit_history');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('messages.settings');
    }

    public static function infolist(Schema $schema): Schema
    {
        return AuditHistoryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('messages.date_time'))
                    ->dateTime('d M Y · H:i')
                    ->description(fn (ActivityLog $r): string => $r->created_at?->diffForHumans() ?? '')
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label(__('messages.user'))
                    ->placeholder(__('messages.system'))
                    ->icon('heroicon-m-user')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('action')
                    ->label(__('messages.action'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('messages.audit_action_' . $state, [], null) ?: $state)
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default   => 'gray',
                    }),

                TextColumn::make('module')
                    ->label(__('messages.module'))
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('model_id')
                    ->label(__('messages.record'))
                    ->formatStateUsing(fn ($state, ActivityLog $r): string => class_basename($r->model_type ?? '') . ' #' . $state)
                    ->placeholder('—'),

                TextColumn::make('description')
                    ->label(__('messages.description'))
                    ->limit(40)
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('ip_address')
                    ->label(__('messages.ip_address'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label(__('messages.action'))
                    ->options([
                        'created' => __('messages.audit_action_created'),
                        'updated' => __('messages.audit_action_updated'),
                        'deleted' => __('messages.audit_action_deleted'),
                    ]),

                SelectFilter::make('module')
                    ->label(__('messages.module'))
                    ->options(fn (): array => ActivityLog::query()
                        ->distinct()
                        ->orderBy('module')
                        ->pluck('module', 'module')
                        ->all()),

                SelectFilter::make('user_id')
                    ->label(__('messages.user'))
                    ->options(fn (): array => User::query()
                        ->whereIn('id', ActivityLog::query()->distinct()->pluck('user_id')->filter())
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable(),

                // Used by the "View full history" link on record pages.
                Filter::make('record')
                    ->form([
                        Hidden::make('model_type'),
                        Hidden::make('model_id'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['model_type'] ?? null, fn ($q, $v) => $q->where('model_type', $v))
                        ->when($data['model_id'] ?? null, fn ($q, $v) => $q->where('model_id', $v))),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditHistory::route('/'),
            'view'  => Pages\ViewAuditHistory::route('/{record}'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Read-only + admin-only access control
    |--------------------------------------------------------------------------
    */

    public static function isAdmin(): bool
    {
        return auth()->user()?->hasAnyRole(['Super Admin', 'Admin']) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::isAdmin();
    }

    public static function canViewAny(): bool
    {
        return static::isAdmin();
    }

    public static function canView($record): bool
    {
        return static::isAdmin();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
