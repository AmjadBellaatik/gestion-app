<?php

namespace App\Services\Documents;

use App\Models\Document;
use App\Models\DocumentSequence;
use App\Models\DocumentType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DocumentNumberService
{
    public static function generate(Document $document): string
    {
        $type = $document->documentType ?: DocumentType::find($document->document_type_id);
        $year = now()->year;
        $prefix = $type?->prefix ?: 'DOC';

        return DB::transaction(function () use ($document, $type, $year, $prefix) {
            $sequence = DocumentSequence::query()
                ->where('company_id', $document->company_id)
                ->where('document_type_id', $document->document_type_id)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                // Recover from a missing/reset sequence by finding the highest number already issued.
                $existingMax = Document::withoutGlobalScopes()
                    ->where('company_id', $document->company_id)
                    ->where('document_type_id', $document->document_type_id)
                    ->whereNotNull('document_number')
                    ->get(['document_number'])
                    ->map(fn ($d) => (int) substr((string) $d->document_number, strrpos((string) $d->document_number, '-') + 1))
                    ->max() ?? 0;

                $sequence = DocumentSequence::create([
                    'company_id' => $document->company_id,
                    'brand_id' => null,
                    'document_type_id' => $type?->id ?? $document->document_type_id,
                    'year' => $year,
                    'prefix' => $prefix,
                    'current_number' => $existingMax,
                    'padding' => 4,
                    'yearly_reset' => true,
                ]);

                if ($existingMax > 0) {
                    Log::warning("DocumentSequence recreated for company={$document->company_id} type={$document->document_type_id} year={$year}; initialized to {$existingMax} from existing documents.");
                }
            }

            // Increment and verify uniqueness, skipping any already-taken numbers.
            do {
                $sequence->increment('current_number');
                $sequence->refresh();

                $number = $sequence->prefix
                    . '-'
                    . $year
                    . '-'
                    . str_pad((string) $sequence->current_number, $sequence->padding, '0', STR_PAD_LEFT);

            } while (
                Document::withoutGlobalScopes()
                    ->where('document_number', $number)
                    ->exists()
            );

            return $number;
        });
    }
}
