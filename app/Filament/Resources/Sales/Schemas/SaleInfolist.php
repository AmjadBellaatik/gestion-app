<?php

namespace App\Filament\Resources\Sales\Schemas;

use App\Models\Sale;
use Filament\Infolists\Components\TextEntry;
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

                        TextEntry::make('sale_date')
                            ->label(__('messages.sale_date'))
                            ->date(),

                        TextEntry::make('created_at')
                            ->label(__('messages.created_at'))
                            ->dateTime(),

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

                                    return '<a href="' . $viewUrl . '" target="_blank" style="display:inline-flex; padding:6px 10px; margin:0 8px 8px 0; border-radius:6px; background:#1f2937; color:#fff; text-decoration:none; font-weight:600;">' . $label . ' - ' . $number . '</a>';
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

                Section::make(__('messages.payments'))
                    ->schema([
                        TextEntry::make('payments_list')
                            ->hiddenLabel()
                            ->state(function (Sale $record) {
                                $payments = $record->payments()->latest()->get();

                                if ($payments->isEmpty()) {
                                    return new HtmlString('<span style="color:#94a3b8;">—</span>');
                                }

                                $statusColor = fn ($s) => match ($s) {
                                    'paid'               => '#16a34a',
                                    'pending_validation' => '#d97706',
                                    'pending'            => '#d97706',
                                    'rejected','cancelled','canceled' => '#dc2626',
                                    'bounced'            => '#7c3aed',
                                    default              => '#64748b',
                                };

                                $methodLabel = fn ($m) => match ($m) {
                                    'cash'          => __('messages.cash'),
                                    'card'          => __('messages.card'),
                                    'cheque'        => __('messages.cheque'),
                                    'bank_transfer' => __('messages.bank_transfer'),
                                    default         => $m,
                                };

                                return new HtmlString(
                                    '<table style="width:100%;border-collapse:collapse;font-size:13px;">'
                                    . '<thead><tr style="background:#f1f5f9;">'
                                    . '<th style="padding:7px 10px;text-align:left;font-weight:600;">' . __('messages.amount') . '</th>'
                                    . '<th style="padding:7px 10px;text-align:left;font-weight:600;">' . __('messages.payment_method') . '</th>'
                                    . '<th style="padding:7px 10px;text-align:left;font-weight:600;">' . __('messages.status') . '</th>'
                                    . '<th style="padding:7px 10px;text-align:left;font-weight:600;">' . __('messages.reference') . '</th>'
                                    . '<th style="padding:7px 10px;text-align:left;font-weight:600;">' . __('messages.date') . '</th>'
                                    . '</tr></thead><tbody>'
                                    . $payments->map(function ($p) use ($statusColor, $methodLabel) {
                                        $color = $statusColor($p->status);
                                        return '<tr style="border-top:1px solid #e5e7eb;">'
                                            . '<td style="padding:7px 10px;font-weight:700;">' . number_format((float) $p->amount, 2, ',', ' ') . ' MAD</td>'
                                            . '<td style="padding:7px 10px;">' . e($methodLabel($p->payment_method)) . '</td>'
                                            . '<td style="padding:7px 10px;"><span style="background:' . $color . '1a;color:' . $color . ';padding:2px 8px;border-radius:999px;font-size:11px;font-weight:600;">' . e($p->status) . '</span></td>'
                                            . '<td style="padding:7px 10px;color:#64748b;">' . e($p->reference ?? '—') . '</td>'
                                            . '<td style="padding:7px 10px;color:#64748b;">' . $p->created_at?->format('d/m/Y H:i') . '</td>'
                                            . '</tr>';
                                    })->implode('')
                                    . '</tbody></table>'
                                );
                            })
                            ->html(),
                    ]),

                Section::make(__('messages.transactions'))
                    ->schema([
                        TextEntry::make('transactions_list')
                            ->hiddenLabel()
                            ->state(function (Sale $record) {
                                $transactions = $record->payments()
                                    ->with('transaction')
                                    ->get()
                                    ->pluck('transaction')
                                    ->filter();

                                if ($transactions->isEmpty()) {
                                    return new HtmlString('<span style="color:#94a3b8;">—</span>');
                                }

                                return new HtmlString(
                                    '<table style="width:100%;border-collapse:collapse;font-size:13px;">'
                                    . '<thead><tr style="background:#f1f5f9;">'
                                    . '<th style="padding:7px 10px;text-align:left;font-weight:600;">' . __('messages.amount') . '</th>'
                                    . '<th style="padding:7px 10px;text-align:left;font-weight:600;">' . __('messages.type') . '</th>'
                                    . '<th style="padding:7px 10px;text-align:left;font-weight:600;">' . __('messages.status') . '</th>'
                                    . '<th style="padding:7px 10px;text-align:left;font-weight:600;">' . __('messages.description') . '</th>'
                                    . '<th style="padding:7px 10px;text-align:left;font-weight:600;">' . __('messages.date') . '</th>'
                                    . '</tr></thead><tbody>'
                                    . $transactions->map(function ($t) {
                                        $color = match ($t->status ?? '') {
                                            'validated' => '#16a34a',
                                            'pending'   => '#d97706',
                                            default     => '#64748b',
                                        };
                                        return '<tr style="border-top:1px solid #e5e7eb;">'
                                            . '<td style="padding:7px 10px;font-weight:700;">' . number_format((float) $t->amount, 2, ',', ' ') . ' MAD</td>'
                                            . '<td style="padding:7px 10px;">' . e($t->type ?? '—') . '</td>'
                                            . '<td style="padding:7px 10px;"><span style="background:' . $color . '1a;color:' . $color . ';padding:2px 8px;border-radius:999px;font-size:11px;font-weight:600;">' . e($t->status ?? '—') . '</span></td>'
                                            . '<td style="padding:7px 10px;color:#64748b;">' . e($t->description ?? '—') . '</td>'
                                            . '<td style="padding:7px 10px;color:#64748b;">' . ($t->transaction_date ? \Carbon\Carbon::parse($t->transaction_date)->format('d/m/Y') : '—') . '</td>'
                                            . '</tr>';
                                    })->implode('')
                                    . '</tbody></table>'
                                );
                            })
                            ->html(),
                    ]),
            ]);
    }
}
