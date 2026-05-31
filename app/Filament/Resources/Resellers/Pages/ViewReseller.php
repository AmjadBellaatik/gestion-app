<?php

namespace App\Filament\Resources\Resellers\Pages;

use App\Filament\Resources\Resellers\ResellerResource;

use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewReseller extends ViewRecord
{
    protected static string $resource =
        ResellerResource::class;

    protected function getHeaderActions(): array
    {
        return [

            Action::make('edit')

                ->label(
                    __('messages.edit')
                )

                ->icon(
                    'heroicon-o-pencil-square'
                )

                ->url(
                    fn () =>

                        ResellerResource::getUrl(
                            'edit',
                            [
                                'record' => $this->record,
                            ]
                        )
                ),

            Action::make('delete')

                ->label(
                    __('messages.delete')
                )

                ->icon(
                    'heroicon-o-trash'
                )

                ->color('danger')

                ->requiresConfirmation()

                ->action(function () {

                    $this->record->delete();

                    $this->redirect(
                        ResellerResource::getUrl()
                    );
                }),

        ];
    }
}