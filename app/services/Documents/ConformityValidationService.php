<?php

namespace App\Services\Documents;

use App\Models\ConformityCertificate;

class ConformityValidationService
{
    public static function validate(
        ConformityCertificate $certificate,
        int $userId
    ): void {

        $hash = hash(

            'sha256',

            json_encode([

                $certificate->certificate_number,

                $certificate->vin_number,

                $certificate->engine_number,

                $certificate->homologation_reference,

                now(),

            ])

        );

        $certificate->update([

            'is_validated' => true,

            'validated_at' => now(),

            'validated_by' => $userId,

            'hash' => $hash,

        ]);
    }
}