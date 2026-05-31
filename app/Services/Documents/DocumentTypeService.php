<?php

namespace App\Services\Documents;

use App\Models\DocumentType;

class DocumentTypeService
{
    public static function getByCode(
        string $code
    ): ?DocumentType {

        return DocumentType::query()

            ->where(
                'code',
                $code
            )

            ->first();
    }
}