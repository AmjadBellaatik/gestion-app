<?php

namespace App\Filament\Resources\CompanySettings\Actions;

use App\Models\Company;
use App\Services\Company\CompanyDeactivationService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

/**
 * "Delete Company" for Super Admin — implemented as a safe, reversible
 * deactivation (see CompanyDeactivationService for why a hard delete is
 * never performed).
 *
 * Guards, in order:
 *   - visible only when the current user can 'delete' this Company;
 *   - the action re-authorises server-side before doing anything;
 *   - a strong confirmation modal that requires typing the exact company name;
 *   - the service enforces "last active company" / "user keeps a company".
 */
class DeactivateCompanyAction
{
    public static function make(): Action
    {
        return Action::make('deactivateCompany')
            ->label(__('messages.delete_company'))
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->visible(fn (Company $record): bool => (bool) auth()->user()?->can('delete', $record))
            ->requiresConfirmation()
            ->modalHeading(fn (Company $record): string => __('messages.delete_company_heading', ['name' => $record->name]))
            ->modalDescription(__('messages.delete_company_warning'))
            ->modalSubmitActionLabel(__('messages.delete_company'))
            ->schema([
                TextInput::make('confirmation')
                    ->label(fn (Company $record): string => __('messages.type_company_name_to_confirm', ['name' => $record->name]))
                    ->required()
                    ->autocomplete(false)
                    ->dehydrated(true),
            ])
            ->action(function (Company $record, array $data, Action $action): void {
                $user = auth()->user();

                abort_unless((bool) $user?->can('delete', $record), 403);

                if (($data['confirmation'] ?? null) !== $record->name) {
                    Notification::make()
                        ->title(__('messages.company_name_confirmation_mismatch'))
                        ->danger()
                        ->send();

                    $action->halt();
                }

                try {
                    app(CompanyDeactivationService::class)->deactivate($record, $user);
                } catch (ValidationException $e) {
                    Notification::make()
                        ->title(collect($e->errors())->flatten()->first() ?? __('messages.logo_analysis_failed'))
                        ->danger()
                        ->send();

                    $action->halt();
                }

                Notification::make()
                    ->title(__('messages.company_deleted', ['name' => $record->name]))
                    ->success()
                    ->send();

                $action->success();
            });
    }
}
