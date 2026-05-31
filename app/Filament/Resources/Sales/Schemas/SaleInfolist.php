<?php

namespace App\Filament\Resources\Sales\Schemas;

use App\Models\Payment;
use App\Models\Sale;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class SaleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('messages.general_information'))
                    ->schema([

                        TextEntry::make('sale_number')
                            ->label(__('messages.reference')),

                        TextEntry::make('created_at')
                            ->label(__('messages.sale_date'))
                            ->date(),

                        TextEntry::make('client_display')
                            ->label(__('messages.client'))
                            ->state(fn (Sale $record) => $record->client?->display_name
                                ?? $record->reseller?->name
                                ?? '-'),

                        TextEntry::make('reseller.name')
                            ->label(__('messages.reseller'))
                            ->placeholder('-'),

                        TextEntry::make('discount')
                            ->label(__('messages.discount_amount'))
                            ->money('MAD')
                            ->color('warning')
                            ->hidden(fn (Sale $record) => (float) $record->discount <= 0),

                        TextEntry::make('discount_note')
                            ->label(__('messages.discount_note'))
                            ->placeholder('-')
                            ->hidden(fn (Sale $record) => blank($record->discount_note)),

                        TextEntry::make('total')
                            ->label(fn (Sale $record) => (float) $record->discount > 0
                                ? __('messages.net_total_after_discount')
                                : __('messages.total_amount'))
                            ->money('MAD')
                            ->weight('bold'),

                        TextEntry::make('paid_amount')
                            ->label(__('messages.paid_amount'))
                            ->money('MAD'),

                        TextEntry::make('remaining_amount')
                            ->label(__('messages.remaining_amount'))
                            ->money('MAD')
                            ->color(fn (Sale $record) => (float) $record->remaining_amount > 0 ? 'danger' : 'success'),

                        TextEntry::make('payment_status')
                            ->label(__('messages.payment_status'))
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'paid'    => 'success',
                                'partial' => 'warning',
                                'unpaid'  => 'danger',
                                default   => 'gray',
                            })
                            ->formatStateUsing(fn ($state) => match ($state) {
                                'paid'    => __('messages.paid'),
                                'partial' => __('messages.partial'),
                                'unpaid'  => __('messages.unpaid'),
                                default   => $state,
                            }),

                        TextEntry::make('notes')
                            ->label(__('messages.notes'))
                            ->columnSpanFull()
                            ->placeholder('-'),

                    ])->columns(2),

                Section::make(__('messages.documents'))
                    ->schema([
                        TextEntry::make('linked_documents')
                            ->hiddenLabel()
                            ->state(function (Sale $record) {
                                $documents = $record->documents()
                                    ->with('documentType')
                                    ->latest('id')
                                    ->get();

                                if ($documents->isEmpty()) {
                                    return '-';
                                }

                                $html = $documents->map(function ($document) {
                                    $viewUrl = route('filament.admin.resources.documents.view', $document);
                                    $label = e($document->documentType?->name ?: 'Document');
                                    $number = e($document->document_number ?: ('#' . $document->id));

                                    return '<a href="' . $viewUrl . '" style="display:inline-flex; padding:6px 10px; margin:0 8px 8px 0; border-radius:6px; background:#1f2937; color:#fff; text-decoration:none; font-weight:600;">' . $label . ' - ' . $number . '</a>';
                                })->implode('');

                                return new HtmlString($html);
                            })
                            ->html(),
                    ]),

                Section::make(__('messages.items'))
                    ->schema([
                        TextEntry::make('sale_items')
                            ->hiddenLabel()
                            ->state(function (Sale $record) {
                                $record->loadMissing(['items.product', 'items.motorcycleUnit.motorcycleModel']);

                                if ($record->items->isEmpty()) {
                                    return '-';
                                }

                                return new HtmlString($record->items->map(function ($item) {
                                    $name = $item->motorcycle_unit_id
                                        ? trim(($item->motorcycleUnit?->motorcycleModel?->marque ? $item->motorcycleUnit->motorcycleModel->marque . ' ' : '') . ($item->motorcycleUnit?->motorcycleModel?->modele ?: __('messages.motorcycle')))
                                        : ($item->product?->name ?: __('messages.product'));

                                    $ref = $item->motorcycle_unit_id
                                        ? e($item->motorcycleUnit?->chassis_number)
                                        : e($item->product?->sku);

                                    return '<div style="padding:8px 0; border-bottom:1px solid #e5e7eb;"><strong>' . e($name) . '</strong> <span style="color:#64748b;">' . $ref . '</span><br><span>' . __('messages.quantity') . ': ' . e($item->quantity) . ' | ' . __('messages.total_amount') . ': ' . number_format((float) $item->total, 2, ',', ' ') . ' MAD</span></div>';
                                })->implode(''));
                            })
                            ->html(),
                    ]),
            ]);
    }
}
