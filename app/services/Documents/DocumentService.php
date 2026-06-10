<?php

namespace App\Services\Documents;

use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\DocumentTemplate;
use App\Models\DocumentType;
use App\Models\GeneratedPdf;
use App\Models\Product;
use App\Models\MotorcycleUnit;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Services\Stock\StockService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class DocumentService
{
    public function create(array $data): Document
    {
        $document = DB::transaction(function () use ($data) {
            $type = DocumentType::findOrFail($data['document_type_id']);
            $template = $this->resolveTemplate($type, $data['language'] ?? null);
            $sale = filled($data['sale_id'] ?? null)
                ? Sale::query()->select(['id', 'client_id', 'reseller_id', 'sale_date'])->find($data['sale_id'])
                : null;

            // Sales documents inherit the sale's effective date unless one is given.
            if (blank($data['document_date'] ?? null) && $sale?->sale_date) {
                $data['document_date'] = $sale->sale_date->toDateString();
            }
            $resellerId = $data['reseller_id'] ?? $sale?->reseller_id;
            $clientId = filled($resellerId)
                ? null
                : ($data['client_id'] ?? $sale?->client_id);

            $document = Document::create([
                'company_id' => $data['company_id'] ?? session('company_id'),
                'document_type_id' => $type->id,
                'document_template_id' => $template?->id,
                'template_version' => $template?->version ?? 1,
                'client_id' => $clientId,
                'supplier_id' => $data['supplier_id'] ?? null,
                'reseller_id' => $resellerId,
                'sale_id' => $data['sale_id'] ?? null,
                'repair_ticket_id' => $data['repair_ticket_id'] ?? null,
                'document_number' => $data['document_number'] ?? null,
                'sequence_number' => $data['sequence_number'] ?? null,
                'document_year'   => $data['document_year'] ?? null,
                'document_date' => $data['document_date'] ?? now()->toDateString(),
                'language' => $data['language'] ?? 'fr',
                'status' => $data['status'] ?? 'generated',
                'discount_amount' => $data['discount_amount'] ?? 0,
                'notes' => $data['notes'] ?? null,
                'generated_by' => auth()?->id(),
                'metadata' => $data['metadata'] ?? null,
            ]);

            $this->syncItems($document, $data['items'] ?? []);

            if ($type->code === DocumentType::SUPPLIER_ORDER) {
                $document->forceFill([
                    'subtotal'     => $data['subtotal'] ?? 0,
                    'tax_rate'     => $data['tax_rate'] ?? 20,
                    'tax_amount'   => $data['tax_amount'] ?? 0,
                    'tax'          => $data['tax_amount'] ?? 0,
                    'total_amount' => $data['total_amount'] ?? 0,
                    'total'        => $data['total_amount'] ?? 0,
                ])->save();
            } else {
                $this->recalculateTotals($document);
            }

            $this->applySaleReturn($document);

            return $document->refresh();
        });

        // PDF rendering, file I/O, and checksum computation run outside the
        // transaction so they do not hold row locks during expensive operations.
        $this->storePdf($document);

        return $document->refresh();
    }

    private function applySaleReturn(Document $document): void
    {
        $document->loadMissing(['documentType', 'sale.items', 'items']);

        if ($document->documentType?->code !== DocumentType::SALE_RETURN || ! $document->sale) {
            return;
        }

        foreach ($document->items as $item) {
            $saleItem = $document->sale->items
                ->first(function (SaleItem $saleItem) use ($item): bool {
                    return ((int) $saleItem->product_id > 0 && $saleItem->product_id === $item->product_id)
                        || ((int) $saleItem->motorcycle_unit_id > 0 && $saleItem->motorcycle_unit_id === $item->motorcycle_unit_id);
                });

            if (! $saleItem) {
                continue;
            }

            $quantity = min(
                (float) $item->quantity,
                max((float) $saleItem->quantity - (float) ($saleItem->returned_quantity ?? 0), 0)
            );

            if ($quantity <= 0) {
                continue;
            }

            $saleItem->increment('returned_quantity', $quantity);

            // Copy unit price from the sale item so the PDF shows real amounts.
            $unitPrice = (float) $saleItem->unit_price;
            $discountPerUnit = (float) $saleItem->quantity > 0
                ? (float) $saleItem->discount / (float) $saleItem->quantity
                : 0;

            $item->update([
                'unit_price'      => $unitPrice,
                'quantity'        => $quantity,
                'discount_amount' => round($discountPerUnit * $quantity, 2),
            ]);

            // Resolve the warehouse from the original outbound movement for this item.
            // DocumentItem.warehouse_id is null for return docs created via the form
            // because resolveReturnableSaleItems() does not carry it over.
            $warehouseId = $item->warehouse_id;
            if (! $warehouseId && $document->sale_id) {
                $warehouseId = StockMovement::withoutGlobalScopes()
                    ->where('reference_type', Sale::class)
                    ->where('reference_id', $document->sale_id)
                    ->when($item->product_id, fn ($q) => $q->where('product_id', $item->product_id))
                    ->when($item->motorcycle_unit_id, fn ($q) => $q->where('motorcycle_unit_id', $item->motorcycle_unit_id))
                    ->whereNotNull('warehouse_id')
                    ->value('warehouse_id');
            }

            if (! $warehouseId) {
                throw new \InvalidArgumentException(
                    'Cannot create return StockMovement: no warehouse_id for '
                    . ($item->product_id ? "product #{$item->product_id}" : "unit #{$item->motorcycle_unit_id}")
                    . " on document {$document->document_number}."
                );
            }

            StockService::movement([
                'company_id'         => $document->company_id,
                'product_id'         => $item->product_id,
                'motorcycle_unit_id' => $item->motorcycle_unit_id,
                'warehouse_id'       => $warehouseId,
                'movement_type'      => 'return',
                'type'               => 'entry',
                'quantity'           => $quantity,
                'unit_cost'          => 0,
                'reference'          => $document->document_number,
                'reference_type'     => Document::class,
                'reference_id'       => $document->id,
                'notes'              => 'Return document ' . $document->document_number,
                'user_id'            => auth()->id(),
            ]);

            if ($item->motorcycle_unit_id) {
                MotorcycleUnit::query()
                    ->whereKey($item->motorcycle_unit_id)
                    ->update([
                        'client_id' => null,
                        'sale_date' => null,
                        'status' => 'in_stock',
                    ]);
            }

            if ($item->product_id) {
                $product = Product::query()->find($item->product_id);

                if ($product && Schema::hasColumn('products', 'status')) {
                    $product->update([
                        'status' => 'in_stock',
                    ]);
                }
            }
        }

        $document->sale->refresh();

        $totalQuantity = (float) $document->sale->items()->sum('quantity');
        $returnedQuantity = (float) $document->sale->items()->sum('returned_quantity');

        $fullyReturned = $returnedQuantity >= $totalQuantity;

        $document->sale->update([
            'status'         => $fullyReturned ? 'returned' : 'partially_returned',
            'payment_status' => $fullyReturned ? 'returned' : 'partially_returned',
            'returned_at'    => now(),
        ]);

        // Recompute document totals now that item prices have been populated.
        $document->load('items');
        $this->recalculateTotals($document);
    }

    public static function generate(array $data): Document
    {
        return app(self::class)->create($data);
    }

    public function syncItems(Document $document, array $items): void
    {
        $document->items()->delete();
        $document->loadMissing('documentType');

        foreach (array_values($items) as $index => $item) {
            $isConformity = $document->documentType?->code === DocumentType::CONFORMITY;
            $itemType = strtolower((string) ($item['item_type'] ?? ''));

            DocumentItem::create([
                'document_id' => $document->id,
                'item_type' => $isConformity ? 'motorcycle' : ($itemType ?: ($item['motorcycle_unit_id'] ?? null ? 'motorcycle' : 'product')),
                'product_id' => $isConformity ? null : ($item['product_id'] ?? null),
                'motorcycle_unit_id' => $item['motorcycle_unit_id'] ?? null,
                'warehouse_id' => $item['warehouse_id'] ?? null,
                'description' => $item['description'] ?? null,
                'quantity' => $item['quantity'] ?? 1,
                'unit_price' => $item['unit_price'] ?? 0,
                'discount_amount' => $item['discount_amount'] ?? 0,
                'serial_number' => $item['serial_number'] ?? null,
                'warranty_months' => $item['warranty_months'] ?? null,
                'line_notes' => $item['line_notes'] ?? null,
                'line_sort' => $index + 1,
                'unit_type' => $item['unit_type'] ?? 'unit',
                'metadata' => $item['metadata'] ?? null,
            ]);
        }
    }

    public function recalculateTotals(Document $document): void
    {
        $document->load([
            'documentType',
            'items',
            'sale',
        ]);

        if (
            $document->documentType?->code === DocumentType::INVOICE
            && $document->sale
            && $document->items->count() === 1
            && (float) $document->items->first()->total <= 0
        ) {
            $fallbackTotal = (float) $document->sale->total;

            if ($fallbackTotal <= 0) {
                $fallbackTotal = (float) $document->sale->paid_amount;
            }

            if ($fallbackTotal > 0) {
                $item = $document->items->first();
                $item->unit_price = $fallbackTotal / max((float) $item->quantity, 1);
                $item->save();

                $document->load('items');
            }
        }

        $itemsTotalIncludingTax = $document->items->sum(fn (DocumentItem $item) => (float) $item->total);
        $documentDiscountIncludingTax = (float) $document->discount_amount;
        $total = max(round($itemsTotalIncludingTax - $documentDiscountIncludingTax, 2), 0);
        $tax = round($total * (Document::TAX_RATE / (100 + Document::TAX_RATE)), 2);
        $subtotal = round($total - $tax, 2);

        $document->forceFill([
            'subtotal' => $subtotal,
            'tax_rate' => Document::TAX_RATE,
            'tax_amount' => $tax,
            'tax' => $tax,
            'total_amount' => $total,
            'total' => $total,
        ])->save();
    }

    public function storePdf(Document $document): GeneratedPdf
    {
        $previousLocale = app()->getLocale();
        app()->setLocale($document->language ?? 'fr');

        $document = Document::query()
            ->with([
                'company',
                'documentType',
                'documentTemplate',
                'client',
                'supplier',
                'reseller',
                'sale.reseller',
                'items.product',
                'items.motorcycleUnit.motorcycleModel.brand',
                'items.motorcycleUnit.motorcycleModel.homologation',
            ])
            ->findOrFail($document->id);

        $document->loadMissing([
            'company',
            'documentType',
            'documentTemplate',
            'client',
            'supplier',
            'reseller',
            'sale.reseller',
            'sale.items',
            'items.product',
            'items.motorcycleUnit.motorcycleModel.brand',
            'items.motorcycleUnit.motorcycleModel.homologation',
        ]);

        // For sale-return documents, ensure item prices are populated from the sale.
        if ($document->documentType?->code === DocumentType::SALE_RETURN && $document->sale) {
            foreach ($document->items as $item) {
                if ((float) $item->unit_price > 0) {
                    continue;
                }
                $saleItem = $document->sale->items->first(fn ($s) =>
                    ((int) $s->product_id > 0 && $s->product_id === $item->product_id)
                    || ((int) $s->motorcycle_unit_id > 0 && $s->motorcycle_unit_id === $item->motorcycle_unit_id)
                );
                if ($saleItem && (float) $saleItem->unit_price > 0) {
                    $discountPerUnit = (float) $saleItem->quantity > 0
                        ? (float) $saleItem->discount / (float) $saleItem->quantity
                        : 0;
                    $item->update([
                        'unit_price'      => (float) $saleItem->unit_price,
                        'discount_amount' => round($discountPerUnit * (float) $item->quantity, 2),
                    ]);
                }
            }
            $document->load('items');
            $this->recalculateTotals($document);
            $document->refresh();
        }

        $template = $document->documentTemplate;
        $view = $template?->blade_view ?: $document->documentType->defaultBladeView();

        if ($document->documentType?->code === DocumentType::INVOICE) {
            $view = 'documents.pdf.commercial-invoice';
        }

        if ($document->documentType?->code === DocumentType::QUOTATION) {
            $view = 'documents.pdf.commercial-quotation';
        }

        if ($document->documentType?->code === DocumentType::DELIVERY_NOTE) {
            $view = 'documents.pdf.delivery-note';
        }

        if ($document->documentType?->code === DocumentType::CONFORMITY) {
            $view = 'documents.pdf.conformity-certificate';
        }

        if ($document->documentType?->code === DocumentType::WARRANTY_CONTRACT) {
            $view = 'documents.pdf.warranty-contract';
        }

        if ($document->documentType?->code === DocumentType::SUPPLIER_ORDER) {
            $view = 'documents.pdf.supplier-order';
        }

        if ($document->documentType?->code === DocumentType::SALE_RETURN) {
            $view = 'documents.pdf.sale-return';
        }

        if ($document->documentType?->code === DocumentType::OWNERSHIP) {
            $view = 'documents.pdf.ownership-prsk';
        }

        if ($document->documentType?->code === DocumentType::INVOICE && $document->invoice_source === 'repair') {
            $view = 'documents.pdf.repair-invoice';
        }

        $company = Company::findOrFail($document->company_id);
        $qrSvg = base64_encode(
            QrCode::format('svg')->size(120)->margin(1)->generate($document->verification_url)
        );

        $pdf = Pdf::loadView($view, [
            'document' => $document,
            'company' => $company,
            'template' => $template,
            'client' => $document->client,
            'supplier' => $document->supplier,
            'motorcycleUnit' => $document->primaryMotorcycleUnit(),
            'qrSvg' => $qrSvg,
            'placeholders' => DocumentPlaceholderService::context($document),
        ])->setPaper($template?->paper_size ?: 'A4', $template?->orientation ?: 'portrait');

        app()->setLocale($previousLocale);

        $directory = 'documents/'
            . $company->id
            . '/'
            . now()->format('Y')
            . '/'
            . strtolower($document->documentType->code);

        $path = $directory . '/' . Str::slug($document->document_number) . '-' . now()->format('YmdHis') . '.pdf';
        Storage::disk('public')->put($path, $pdf->output());

        $generatedPdf = GeneratedPdf::create([
            'company_id' => $document->company_id,
            'document_id' => $document->id,
            'document_template_id' => $template?->id,
            'uuid' => (string) Str::uuid(),
            'path' => $path,
            'disk' => 'public',
            'template_version' => $template?->version ?? $document->template_version ?? 1,
            'checksum' => hash('sha256', Storage::disk('public')->get($path)),
            'generated_at' => now(),
            'generated_by' => auth()->id(),
        ]);

        $document->forceFill([
            'pdf_path' => $path,
            'generated_at' => now(),
            'template_version' => $generatedPdf->template_version,
        ])->save();

        return $generatedPdf;
    }

    public static function generatePdfFor(Document $document): GeneratedPdf
    {
        return app(self::class)->storePdf($document);
    }

    public static function generatePdf(Document $document, string $template = ''): \Barryvdh\DomPDF\PDF
    {
        $previousLocale = app()->getLocale();
        app()->setLocale($document->language ?? 'fr');

        $document = Document::query()
            ->with([
                'company',
                'documentType',
                'documentTemplate',
                'client',
                'supplier',
                'reseller',
                'sale.reseller',
                'items.product',
                'items.motorcycleUnit.motorcycleModel.brand',
                'items.motorcycleUnit.motorcycleModel.homologation',
            ])
            ->findOrFail($document->id);

        $view = $template ?: ($document->documentTemplate?->blade_view ?: $document->documentType->defaultBladeView());

        if ($document->documentType?->code === DocumentType::INVOICE) {
            $view = 'documents.pdf.commercial-invoice';
        }

        if ($document->documentType?->code === DocumentType::DELIVERY_NOTE) {
            $view = 'documents.pdf.delivery-note';
        }

        if ($document->documentType?->code === DocumentType::CONFORMITY) {
            $view = 'documents.pdf.conformity-certificate';
        }

        if ($document->documentType?->code === DocumentType::WARRANTY_CONTRACT) {
            $view = 'documents.pdf.warranty-contract';
        }

        if ($document->documentType?->code === DocumentType::SUPPLIER_ORDER) {
            $view = 'documents.pdf.supplier-order';
        }

        $qrSvg = base64_encode(QrCode::format('svg')->size(120)->margin(1)->generate($document->verification_url));

        $pdf = Pdf::loadView($view, [
            'document' => $document,
            'company' => $document->company,
            'template' => $document->documentTemplate,
            'client' => $document->client,
            'supplier' => $document->supplier,
            'motorcycleUnit' => $document->primaryMotorcycleUnit(),
            'qrSvg' => $qrSvg,
            'placeholders' => DocumentPlaceholderService::context($document),
        ]);

        app()->setLocale($previousLocale);

        return $pdf;
    }

    public function resolveTemplate(DocumentType $type, ?string $language = null): ?DocumentTemplate
    {
        return DocumentTemplate::query()
            ->where('document_type_id', $type->id)
            ->when($language, fn ($query) => $query->where('language', $language))
            ->where('is_default', true)
            ->first()
            ?: DocumentTemplate::query()
                ->where('document_type_id', $type->id)
                ->where('is_default', true)
                ->first();
    }
}
