<?php

namespace App\Services\Warranty;

use App\Models\WarrantyContract;

class WarrantyValidationService
{
    public static function validate(
        WarrantyContract $contract
    ): array {

        return [

            'is_valid' =>
                $contract->isValid(),

            'expired' =>
                $contract->isExpired(),

            'mileage_exceeded' =>
                $contract->exceedsMileage(),

        ];
    }
}