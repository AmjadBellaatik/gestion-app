<?php

namespace App\Services\Documents;

use App\Models\Document;
use App\Models\DocumentSequence;
use App\Models\DocumentType;
use Illuminate\Support\Facades\DB;

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
                $sequence = DocumentSequence::create([
                    'company_id' => $document->company_id,
                    'brand_id' => null,
                    'document_type_id' => $type?->id ?? $document->document_type_id,
                    'year' => $year,
                    'prefix' => $prefix,
                    'current_number' => 0,
                    'padding' => 4,
                    'yearly_reset' => true,
                ]);
            }

            $sequence->increment('current_number');
            $sequence->refresh();

            return $sequence->prefix
                . '-'
                . $year
                . '-'
                . str_pad((string) $sequence->current_number, $sequence->padding, '0', STR_PAD_LEFT);
        });
    }
}
