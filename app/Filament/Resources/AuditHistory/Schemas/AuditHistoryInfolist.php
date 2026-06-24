<?php

namespace App\Filament\Resources\AuditHistory\Schemas;

use App\Models\ActivityLog;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class AuditHistoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([

                Section::make(__('messages.action_details'))
                    ->columns(3)
                    ->schema([

                        TextEntry::make('action')
                            ->label(__('messages.action'))
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => __('messages.audit_action_' . $state, [], null) ?: $state)
                            ->color(fn (string $state): string => match ($state) {
                                'created' => 'success',
                                'updated' => 'warning',
                                'deleted' => 'danger',
                                default   => 'gray',
                            }),

                        TextEntry::make('module')
                            ->label(__('messages.module'))
                            ->badge()
                            ->color('gray'),

                        TextEntry::make('model_id')
                            ->label(__('messages.record'))
                            ->formatStateUsing(fn ($state, ActivityLog $r): string => class_basename($r->model_type ?? '') . ' #' . $state)
                            ->placeholder('—'),

                        TextEntry::make('user.name')
                            ->label(__('messages.user'))
                            ->icon('heroicon-m-user')
                            ->weight(FontWeight::Bold)
                            ->placeholder(__('messages.system')),

                        TextEntry::make('created_at')
                            ->label(__('messages.date_time'))
                            ->dateTime('d M Y · H:i:s'),

                        TextEntry::make('ip_address')
                            ->label(__('messages.ip_address'))
                            ->icon('heroicon-m-globe-alt')
                            ->placeholder('—'),

                        TextEntry::make('description')
                            ->label(__('messages.description'))
                            ->placeholder('—')
                            ->columnSpanFull(),

                        TextEntry::make('user_agent')
                            ->label(__('messages.user_agent'))
                            ->placeholder('—')
                            ->color('gray')
                            ->columnSpanFull(),
                    ]),

                Section::make(__('messages.changes'))
                    ->schema([
                        ViewEntry::make('changes')
                            ->view('filament.infolists.audit-diff')
                            ->hiddenLabel(),
                    ]),
            ]);
    }
}
