<?php

namespace App\Services\Warranty;

use App\Models\WarrantyClaim;

class WarrantyClaimService
{
    public static function approve(
        WarrantyClaim $claim,
        float $approvedAmount
    ): void {

        $claim->update([

            'approved' => true,

            'status' => 'approved',

            'approved_amount' =>
                $approvedAmount,

            'approved_at' => now(),

        ]);
    }

    public static function reimburse(
        WarrantyClaim $claim,
        float $amount
    ): void {

        $claim->update([

            'status' => 'reimbursed',

            'reimbursed_amount' =>
                $amount,

            'reimbursed_at' => now(),

        ]);
    }
}