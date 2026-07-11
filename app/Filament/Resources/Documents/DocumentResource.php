<?php

namespace App\Filament\Resources\Documents;

use App\Filament\Resources\Documents\Pages\CreateDocument;
use App\Filament\Resources\Documents\Pages\EditDocument;
use App\Filament\Resources\Documents\Pages\ListDocuments;
use App\Filament\Resources\Documents\Pages\ViewDocument;
use App\Filament\Resources\Documents\Schemas\DocumentForm;
use App\Filament\Resources\Documents\Schemas\DocumentInfolist;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Client;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'document_number';

    public static function getGloballySearchableAttributes(): array
    {
        // document_number  — typed or scanned number printed on doc
        // uuid             — the unique part of the QR code URL (/verify/document/{uuid})
        // verification_url — full URL encoded in the QR; barcode scanners output this directly
        return ['document_number', 'uuid', 'verification_url'];
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return array_filter([
            __('messages.document_type') => $record->documentType?->name,
            __('messages.client')        => $record->client?->display_name,
        ]);
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.documents');
    }

    public static function getModelLabel(): string
    {
        return __('messages.document');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.documents');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('messages.commercial');
    }

    public static function form(Schema $schema): Schema
    {
        return DocumentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DocumentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with([
                'client', 'reseller', 'sale.reseller',
            ]))
            ->columns([
                Tables\Columns\TextColumn::make('document_number')
                    ->label(__('messages.document_number'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('documentType.name')
                    ->label(__('messages.document_type'))
                    ->searchable()
                    ->sortable(),

                // Client OR reseller — a reseller can be linked directly on the
                // document or through its sale (reseller sales set reseller_id and
                // null out client_id, so client.display_name alone is empty).
                Tables\Columns\TextColumn::make('client_display')
                    ->label(__('messages.client'))
                    ->getStateUsing(fn (Document $record): ?string =>
                        $record->reseller?->name
                        ?? $record->sale?->reseller?->name
                        ?? $record->client?->display_name
                        ?? ($record->reseller_id ? $record->reseller()->withoutGlobalScopes()->value('name') : null)
                        ?? ($record->sale?->reseller_id ? $record->sale->reseller()->withoutGlobalScopes()->value('name') : null))
                    ->placeholder('-')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where(
                        fn (Builder $q) => $q
                            ->whereHas('client', fn (Builder $c) => $c->where('display_name', 'like', "%{$search}%"))
                            ->orWhereHas('reseller', fn (Builder $r) => $r->where('name', 'like', "%{$search}%"))
                    )),

                Tables\Columns\TextColumn::make('supplier.name')
                    ->label(__('messages.supplier'))
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label(__('messages.total_ttc'))
                    ->formatStateUsing(function ($state, Document $record) {
                        $priceTypes = [
                            DocumentType::INVOICE,
                            DocumentType::QUOTATION,
                            DocumentType::SUPPLIER_ORDER,
                        ];
                        if (! in_array($record->documentType?->code, $priceTypes, true)) {
                            return '-';
                        }
                        return 'MAD ' . number_format((float) $state, 2);
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('generated_at')
                    ->label(__('messages.generated_at'))
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('document_actions')
                    ->label(__('messages.actions'))
                    ->state(fn (Document $record) => new HtmlString(self::tableActionsHtml($record)))
                    ->html(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('document_type_id')
                    ->label(__('messages.document_type'))
                    ->relationship('documentType', 'name'),
                Tables\Filters\SelectFilter::make('client_id')
                    ->label(__('messages.client'))
                    ->options(fn () => Client::query()
                        ->active()
                        ->get()
                        ->pluck('display_name', 'id')
                        ->filter()
                        ->toArray()),
                Tables\Filters\Filter::make('document_date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label(__('messages.from')),
                        \Filament\Forms\Components\DatePicker::make('until')->label(__('messages.until')),
                    ])
                    ->query(fn ($query, array $data) => $query
                        ->when($data['from'] ?? null, fn ($query, $date) => $query->whereDate('document_date', '>=', $date))
                        ->when($data['until'] ?? null, fn ($query, $date) => $query->whereDate('document_date', '<=', $date))),
            ]);
    }

    public static function tableActionsHtml(Document $document): string
    {
        $viewUrl = static::getUrl('view', ['record' => $document]);
        $downloadUrl = route('documents.download', $document);
        $deleteUrl = route('documents.destroy', $document);
        $token = csrf_token();
        $confirm = e(__('messages.confirm_delete_document'));
        $confirmLinked = e(__('messages.confirm_delete_sale_linked_document'));
        $deleteConfirm = json_encode(self::isSaleLinkedGeneratedDocument($document) ? $confirmLinked : $confirm);
        $viewLabel = e(__('messages.view'));
        $downloadLabel = e(__('messages.download_pdf'));
        $deleteLabel = e(__('messages.delete'));

        return <<<HTML
            <div style="display:flex; gap:6px; align-items:center; flex-wrap:wrap;">
                <a href="{$viewUrl}" target="_blank" style="display:inline-flex; align-items:center; padding:6px 10px; border-radius:6px; background:#374151; color:#fff; font-weight:600; font-size:12px; text-decoration:none;">{$viewLabel}</a>
                <a href="{$downloadUrl}" style="display:inline-flex; align-items:center; padding:6px 10px; border-radius:6px; background:#2563eb; color:#fff; font-weight:600; font-size:12px; text-decoration:none;">{$downloadLabel}</a>
                <form method="POST" action="{$deleteUrl}" style="display:inline;" onsubmit="return confirm({$deleteConfirm})">
                    <input type="hidden" name="_token" value="{$token}">
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" style="display:inline-flex; align-items:center; padding:6px 10px; border:0; border-radius:6px; background:#dc2626; color:#fff; font-weight:600; font-size:12px; cursor:pointer;">{$deleteLabel}</button>
                </form>
            </div>
        HTML;
    }

    public static function isSaleLinkedGeneratedDocument(Document $document): bool
    {
        return filled($document->sale_id)
            && in_array($document->documentType?->code, [
                DocumentType::INVOICE,
                DocumentType::DELIVERY_NOTE,
                DocumentType::WARRANTY_CONTRACT,
                DocumentType::CONFORMITY,
            ], true);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocuments::route('/'),
            'create' => CreateDocument::route('/create'),
            'edit' => EditDocument::route('/{record}/edit'),
            'view' => ViewDocument::route('/{record}'),
        ];
    }
}
