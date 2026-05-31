<?php

namespace App\Filament\Resources\RepairTickets\Tables;

use App\Models\RepairTicket;
use App\Models\User;
use App\Notifications\GenericNotification;

use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;

use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;

use Filament\Tables\Table;

class RepairTicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table

            ->defaultSort('created_at', 'desc')

            ->columns([

                TextColumn::make('ticket_number')
                    ->label(__('messages.ticket_number'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('client.display_name')
                    ->label(__('messages.client'))
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('vehicle_display')
                    ->label(__('messages.vehicle'))
                    ->getStateUsing(fn (RepairTicket $record) => $record->vehicle_display)
                    ->searchable(false)
                    ->placeholder('-'),

                IconColumn::make('is_foreign_vehicle')
                    ->label(__('messages.foreign_vehicle'))
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('technician.name')
                    ->label(__('messages.technician'))
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('repair_type')
                    ->label(__('messages.repair_type'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('messages.' . ($state ?? 'paid')))
                    ->color(fn ($state) => match ($state) {
                        'warranty'      => 'warning',
                        'paid'          => 'success',
                        'internal'      => 'info',
                        'reimbursement' => 'danger',
                        default         => 'gray',
                    }),

                TextColumn::make('status')
                    ->label(__('messages.status'))
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'open'        => 'gray',
                        'diagnostic'  => 'warning',
                        'assigned'    => 'info',
                        'in_progress' => 'primary',
                        'completed'   => 'success',
                        'delivered'   => 'success',
                        'cancelled'   => 'danger',
                        default       => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => __('messages.' . ($state ?? 'open'))),

                TextColumn::make('priority')
                    ->label(__('messages.priority'))
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'urgent' => 'danger',
                        'high'   => 'warning',
                        'normal' => 'info',
                        'low'    => 'gray',
                        default  => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => __('messages.' . ($state ?? 'normal')))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('mileage')
                    ->label(__('messages.mileage'))
                    ->numeric()
                    ->suffix(' km')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('labor_cost')
                    ->label(__('messages.labor_cost'))
                    ->money('MAD')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('parts_cost')
                    ->label(__('messages.parts_cost'))
                    ->money('MAD')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('discount_amount')
                    ->label(__('messages.discount_amount'))
                    ->money('MAD')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('total_cost')
                    ->label(__('messages.total_cost'))
                    ->money('MAD')
                    ->sortable()
                    ->weight('bold'),

                IconColumn::make('discount_validated')
                    ->label(__('messages.discount_validated'))
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_warranty')
                    ->label(__('messages.is_warranty'))
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('payment_status')
                    ->label(__('messages.payment_status'))
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'paid'    => 'success',
                        'partial' => 'warning',
                        default   => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => __('messages.' . ($state ?? 'unpaid'))),

                TextColumn::make('opened_at')
                    ->label(__('messages.opened_at'))
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('completed_at')
                    ->label(__('messages.completed_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('messages.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])

            ->filters([

                SelectFilter::make('status')
                    ->label(__('messages.status'))
                    ->options([
                        'open'        => __('messages.open'),
                        'diagnostic'  => __('messages.diagnostic'),
                        'assigned'    => __('messages.assigned'),
                        'in_progress' => __('messages.in_progress'),
                        'completed'   => __('messages.completed'),
                        'delivered'   => __('messages.delivered'),
                        'cancelled'   => __('messages.cancelled'),
                    ]),

                SelectFilter::make('repair_type')
                    ->label(__('messages.repair_type'))
                    ->options([
                        'warranty'      => __('messages.warranty'),
                        'paid'          => __('messages.paid'),
                        'internal'      => __('messages.internal'),
                        'reimbursement' => __('messages.reimbursement'),
                    ]),

                SelectFilter::make('payment_status')
                    ->label(__('messages.payment_status'))
                    ->options([
                        'unpaid'  => __('messages.unpaid'),
                        'partial' => __('messages.partial'),
                        'paid'    => __('messages.paid'),
                    ]),

                SelectFilter::make('priority')
                    ->label(__('messages.priority'))
                    ->options([
                        'low'    => __('messages.low'),
                        'normal' => __('messages.normal'),
                        'high'   => __('messages.high'),
                        'urgent' => __('messages.urgent'),
                    ]),

                TrashedFilter::make(),

            ])

            ->actions([

                ViewAction::make(),

                EditAction::make(),

                DeleteAction::make()
                    ->visible(fn () => self::isAdminUser())
                    ->requiresConfirmation(),

                \Filament\Actions\Action::make('request_deletion')
                    ->label(__('messages.request_deletion'))
                    ->icon('heroicon-o-trash')
                    ->color('warning')
                    ->visible(fn () => ! self::isAdminUser())
                    ->requiresConfirmation()
                    ->action(function (RepairTicket $record) {
                        $admins = User::role(['Admin', 'Super Admin'])->where('status', true)->get();
                        $notification = new GenericNotification(
                            __('messages.request_deletion'),
                            'Ticket #' . ($record->ticket_number ?? $record->id) . ' — ' . __('messages.request_deletion')
                        );
                        foreach ($admins as $admin) {
                            try { $admin->notify($notification); } catch (\Throwable) {}
                        }
                        Notification::make()->title(__('messages.deletion_requested'))->success()->send();
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
                                'Bulk deletion requested for ' . $records->count() . ' repair ticket(s).'
                            );
                            foreach ($admins as $admin) {
                                try { $admin->notify($notification); } catch (\Throwable) {}
                            }
                            Notification::make()->title(__('messages.deletion_requested'))->success()->send();
                        }),

                ]),

            ]);
    }

    public static function isAdminUser(): bool
    {
        return auth()->user()?->hasAnyRole(['Admin', 'Super Admin']) ?? false;
    }
}
