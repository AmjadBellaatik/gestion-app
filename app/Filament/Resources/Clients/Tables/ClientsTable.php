<?php

namespace App\Filament\Resources\Clients\Tables;

use App\Models\User;
use App\Notifications\GenericNotification;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;

use Filament\Notifications\Notification;

use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;

use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ClientsTable
{
    public static function configure(
        Table $table
    ): Table {

        return $table

            ->columns([

                TextColumn::make('client_name')

                    ->label(
                        __('messages.client')
                    )

                    ->getStateUsing(function ($record) {

                        if ($record->client_type === 'company') {
                            return $record->company_name;
                        }

                        if ($record->client_type === 'administration') {
                            return $record->administration_name;
                        }

                        return trim(
                            ($record->first_name ?? '') . ' ' .
                            ($record->last_name ?? '')
                        );
                    })

                    ->searchable()

                    ->sortable(),

                BadgeColumn::make('client_type')

                    ->label(
                        __('messages.client_type')
                    )

                    ->formatStateUsing(
                        fn ($state) => match ($state) {
                            'person'         => __('messages.person'),
                            'company'        => __('messages.company'),
                            'administration' => __('messages.administration'),
                            default          => $state,
                        }
                    ),

                TextColumn::make('phone')
                    ->label(__('messages.phone'))
                    ->searchable(),

                TextColumn::make('email')
                    ->label(__('messages.email'))
                    ->searchable(),

                TextColumn::make('balance')
                    ->label(__('messages.balance'))
                    ->money('MAD')
                    ->sortable(),

                IconColumn::make('is_blocked')
                    ->label(__('messages.blocked'))
                    ->boolean()
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->trueIcon('heroicon-o-x-circle')
                    ->falseIcon('heroicon-o-check-circle'),

                TextColumn::make('created_at')
                    ->label(__('messages.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])

            ->filters([

                SelectFilter::make('client_type')
                    ->label(__('messages.client_type'))
                    ->options([
                        'person'         => __('messages.person'),
                        'company'        => __('messages.company'),
                        'administration' => __('messages.administration'),
                    ]),

                TernaryFilter::make('is_blocked')
                    ->label(__('messages.blocked'))
                    ->trueLabel(__('messages.blocked'))
                    ->falseLabel(__('messages.active')),

                TernaryFilter::make('is_active')
                    ->label(__('messages.is_active')),

                TrashedFilter::make(),

            ])

            ->actions([

                ViewAction::make(),

                EditAction::make(),

                DeleteAction::make()
                    ->visible(fn () => self::isAdminUser())
                    ->requiresConfirmation(),

                Action::make('request_deletion')
                    ->label(__('messages.request_deletion'))
                    ->icon('heroicon-o-trash')
                    ->color('warning')
                    ->visible(fn () => ! self::isAdminUser())
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $admins = User::role(['Admin', 'Super Admin'])->where('status', true)->get();
                        $notification = new GenericNotification(
                            __('messages.request_deletion'),
                            'Client #' . $record->id . ' (' . ($record->display_name ?? $record->id) . ') — ' . __('messages.request_deletion')
                        );
                        foreach ($admins as $admin) {
                            try { $admin->notify($notification); } catch (\Throwable) {}
                        }
                        Notification::make()
                            ->title(__('messages.deletion_requested'))
                            ->success()
                            ->send();
                    }),

            ])

            ->toolbarActions([

                BulkActionGroup::make([

                    DeleteBulkAction::make()
                        ->visible(fn () => self::isAdminUser()),

                    ForceDeleteBulkAction::make()
                        ->visible(fn () => self::isAdminUser()),

                    RestoreBulkAction::make()
                        ->visible(fn () => self::isAdminUser()),

                    BulkAction::make('request_bulk_deletion')
                        ->label(__('messages.request_deletion'))
                        ->icon('heroicon-o-trash')
                        ->color('warning')
                        ->visible(fn () => ! self::isAdminUser())
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $admins = User::role(['Admin', 'Super Admin'])->where('status', true)->get();
                            $notification = new GenericNotification(
                                __('messages.request_deletion'),
                                'Bulk deletion requested for ' . $records->count() . ' client(s).'
                            );
                            foreach ($admins as $admin) {
                                try { $admin->notify($notification); } catch (\Throwable) {}
                            }
                            Notification::make()
                                ->title(__('messages.deletion_requested'))
                                ->success()
                                ->send();
                        }),

                ]),

            ])

            ->defaultSort('created_at', 'desc');
    }

    public static function isAdminUser(): bool
    {
        return auth()->user()?->hasAnyRole(['Admin', 'Super Admin']) ?? false;
    }
}
