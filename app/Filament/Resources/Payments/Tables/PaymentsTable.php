<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Models\User;
use App\Notifications\GenericNotification;
use App\Services\Payments\PaymentService;

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

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class PaymentsTable
{
    public static function configure(
        Table $table
    ): Table {

        return $table

            ->defaultSort('created_at', 'desc')

            ->columns([

                TextColumn::make('client.display_name')
                    ->label(__('messages.client'))
                    ->searchable(false)
                    ->placeholder('-'),

                TextColumn::make('sale.sale_number')
                    ->label(__('messages.sale'))
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('amount')
                    ->label(__('messages.amount'))
                    ->money('MAD')
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->label(__('messages.payment_method'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'cash'          => __('messages.cash'),
                        'card'          => __('messages.card'),
                        'bank_transfer' => __('messages.bank_transfer'),
                        'cheque'        => __('messages.cheque'),
                        default         => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'cash'          => 'success',
                        'card'          => 'info',
                        'bank_transfer' => 'warning',
                        'cheque'        => 'gray',
                        default         => 'gray',
                    }),

                TextColumn::make('status')
                    ->label(__('messages.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending'  => __('messages.pending'),
                        'paid'     => __('messages.paid'),
                        'received' => __('messages.cheque_received'),
                        'bounced'  => __('messages.cheque_bounced'),
                        'sent'     => __('messages.transfer_sent'),
                        'cancelled'=> __('messages.cancelled'),
                        default    => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'pending', 'received', 'sent' => 'warning',
                        'paid'     => 'success',
                        'bounced', 'cancelled' => 'danger',
                        default    => 'gray',
                    }),

                TextColumn::make('reference')
                    ->label(__('messages.reference'))
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('created_at')
                    ->label(__('messages.created_at'))
                    ->dateTime()
                    ->sortable(),

            ])

            ->filters([

                SelectFilter::make('status')
                    ->label(__('messages.status'))
                    ->options([
                        'pending'  => __('messages.pending'),
                        'paid'     => __('messages.paid'),
                        'received' => __('messages.cheque_received'),
                        'bounced'  => __('messages.cheque_bounced'),
                        'sent'     => __('messages.transfer_sent'),
                        'cancelled'=> __('messages.cancelled'),
                    ]),

                SelectFilter::make('payment_method')
                    ->label(__('messages.payment_method'))
                    ->options([
                        'cash'          => __('messages.cash'),
                        'card'          => __('messages.card'),
                        'bank_transfer' => __('messages.bank_transfer'),
                        'cheque'        => __('messages.cheque'),
                    ]),

                TrashedFilter::make(),

            ])

            ->actions([

                ViewAction::make(),

                EditAction::make(),

                Action::make('validate_payment')
                    ->label(__('messages.validate'))
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn ($record) => $record->status !== 'paid')
                    ->requiresConfirmation()
                    ->action(fn ($record) => PaymentService::validate($record)),

                Action::make('mark_bounced')
                    ->label(__('messages.cheque_bounced'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->payment_method === 'cheque' && $record->status !== 'bounced')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->update(['status' => 'bounced'])),

                DeleteAction::make()
                    ->visible(fn () => self::isAdminUser())
                    ->requiresConfirmation(),

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
                                'Bulk deletion requested for ' . $records->count() . ' payment(s).'
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

            ]);
    }

    public static function isAdminUser(): bool
    {
        return auth()->user()?->hasAnyRole(['Admin', 'Super Admin']) ?? false;
    }
}
