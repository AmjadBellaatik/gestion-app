<?php

namespace App\Filament\Resources\Documents\Schemas;

use App\Filament\Resources\RepairTickets\RepairTicketResource;
use App\Filament\Resources\Sales\SaleResource;
use App\Models\Document;
use App\Models\DocumentType;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class DocumentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('messages.document_actions'))
                ->schema([
                    TextEntry::make('document_action_buttons')
                        ->hiddenLabel()
                        ->state(fn (Document $record) => new HtmlString(self::actionButtonsHtml($record)))
                        ->html()
                        ->columnSpanFull(),
                ]),

            Section::make(__('messages.document_information'))
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('document_number')
                            ->label(__('messages.document_number')),
                        TextEntry::make('documentType.name')
                            ->label(__('messages.document_type')),
                    ]),
                    Grid::make(3)->schema([
                        TextEntry::make('client_display')
                            ->label(__('messages.client'))
                            ->getStateUsing(fn (Document $record): ?string => filled($record->reseller_id)
                                ? ($record->reseller?->name ?? $record->reseller()->withoutGlobalScopes()->value('name'))
                                : $record->client?->display_name)
                            ->placeholder('-'),
                        TextEntry::make('document_date')
                            ->label(__('messages.document_date'))
                            ->date(),
                    ]),
                    Grid::make(3)->schema([
                        TextEntry::make('sale.sale_number')
                            ->label(__('messages.sale'))
                            ->placeholder('-')
                            ->color('primary')
                            ->url(fn (Document $record) => $record->sale_id
                                ? SaleResource::getUrl('view', ['record' => $record->sale_id])
                                : null),
                    ])->hidden(fn (Document $record) => blank($record->sale_id)),
                    Grid::make(3)->schema([
                        TextEntry::make('repairTicket.ticket_number')
                            ->label(__('messages.repair_ticket'))
                            ->placeholder('-')
                            ->color('primary')
                            ->url(fn (Document $record) => $record->repair_ticket_id
                                ? RepairTicketResource::getUrl('view', ['record' => $record->repair_ticket_id])
                                : null),
                    ])->hidden(fn (Document $record) => blank($record->repair_ticket_id)),
                ]),

            Section::make(__('messages.pdf_information'))
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('pdf_path')
                            ->label(__('messages.pdf_path'))
                            ->placeholder('-'),
                        TextEntry::make('generated_at')
                            ->label(__('messages.generated_at'))
                            ->dateTime()
                            ->placeholder('-'),
                    ]),
                    Grid::make(2)->schema([
                        TextEntry::make('verification_url')
                            ->label(__('messages.verification_url'))
                            ->copyable()
                            ->url(fn (?string $state) => $state, true),
                        TextEntry::make('uuid')
                            ->label(__('messages.uuid'))
                            ->copyable(),
                    ]),
                ]),

            Section::make(__('messages.financial_information'))
                ->schema([
                    Grid::make(4)->schema([
                        TextEntry::make('subtotal')
                            ->label(__('messages.subtotal_ht'))
                            ->money('MAD'),
                        TextEntry::make('tax_rate')
                            ->label(__('messages.tax_rate'))
                            ->suffix('%'),
                        TextEntry::make('tax_amount')
                            ->label(__('messages.tax_amount'))
                            ->money('MAD'),
                        TextEntry::make('total_amount')
                            ->label(__('messages.total_ttc'))
                            ->money('MAD'),
                    ]),
                ])
                ->hidden(fn (Document $record) => $record->documentType?->code === DocumentType::CONFORMITY),

            Section::make(__('messages.additional_information'))
                ->schema([
                    TextEntry::make('notes')
                        ->label(__('messages.notes'))
                        ->placeholder('-')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function actionButtonsHtml(Document $document): string
    {
        $previewUrl = route('documents.pdf', $document);
        $downloadUrl = route('documents.download', $document);
        $editUrl = route('filament.admin.resources.documents.edit', $document);
        $verificationUrl = $document->verification_url;
        $deleteUrl = route('documents.destroy', $document);
        $token = csrf_token();
        $confirm = e(__('messages.confirm_delete_document'));
        $confirmLinked = e(__('messages.confirm_delete_sale_linked_document'));
        $deleteConfirm = json_encode(self::isSaleLinkedGeneratedDocument($document) ? $confirmLinked : $confirm);
        $canEdit = auth()->user()?->hasRole('Super Admin') || auth()->user()?->hasRole('Admin');
        $previewLabel = e(__('messages.preview_pdf'));
        $downloadLabel = e(__('messages.download_pdf'));
        $editLabel = e(__('messages.edit'));
        $verifyLabel = e(__('messages.verify_document'));
        $regenerateLabel = e(__('messages.regenerate'));
        $deleteLabel = e(__('messages.delete'));
        $regenerateUrl = route('documents.regenerate', $document);
        $regenerateConfirm = json_encode(e(__('messages.regenerate_document_confirm')));
        $editButton = $canEdit
            ? '<a href="' . $editUrl . '" style="display:inline-flex; align-items:center; min-height:36px; padding:8px 14px; border-radius:7px; background:#0ea5e9; color:#fff; font-weight:700; text-decoration:none;">' . $editLabel . '</a>'
            : '';

        return <<<HTML
            <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                <a href="{$previewUrl}" target="_blank" style="display:inline-flex; align-items:center; min-height:36px; padding:8px 14px; border-radius:7px; background:#374151; color:#fff; font-weight:700; text-decoration:none;">{$previewLabel}</a>
                <a href="{$downloadUrl}" style="display:inline-flex; align-items:center; min-height:36px; padding:8px 14px; border-radius:7px; background:#2563eb; color:#fff; font-weight:700; text-decoration:none;">{$downloadLabel}</a>
                {$editButton}
                <a href="{$verificationUrl}" target="_blank" style="display:inline-flex; align-items:center; min-height:36px; padding:8px 14px; border-radius:7px; background:#059669; color:#fff; font-weight:700; text-decoration:none;">{$verifyLabel}</a>
                <form method="POST" action="{$regenerateUrl}" style="display:inline;" onsubmit="return confirm({$regenerateConfirm})">
                    <input type="hidden" name="_token" value="{$token}">
                    <button type="submit" style="display:inline-flex; align-items:center; min-height:36px; padding:8px 14px; border:0; border-radius:7px; background:#d97706; color:#fff; font-weight:700; cursor:pointer;">{$regenerateLabel}</button>
                </form>
                <form method="POST" action="{$deleteUrl}" style="display:inline;" onsubmit="return confirm({$deleteConfirm})">
                    <input type="hidden" name="_token" value="{$token}">
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" style="display:inline-flex; align-items:center; min-height:36px; padding:8px 14px; border:0; border-radius:7px; background:#dc2626; color:#fff; font-weight:700; cursor:pointer;">{$deleteLabel}</button>
                </form>
            </div>
        HTML;
    }

    private static function isSaleLinkedGeneratedDocument(Document $document): bool
    {
        return filled($document->sale_id)
            && in_array($document->documentType?->code, [
                DocumentType::INVOICE,
                DocumentType::DELIVERY_NOTE,
                DocumentType::WARRANTY_CONTRACT,
                DocumentType::CONFORMITY,
            ], true);
    }
}
