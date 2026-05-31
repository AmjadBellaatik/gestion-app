<?php

namespace App\Services\Warranty;

use App\Models\Reimbursement;

class ReimbursementService
{
    public static function approve(
        Reimbursement $reimbursement,
        float $approvedAmount
    ): void {

        $reimbursement->update([

            'approved_amount' =>
                $approvedAmount,

            'status' => 'approved',

        ]);
    }

    public static function markAsPaid(
        Reimbursement $reimbursement,
        float $paidAmount
    ): void {

        $reimbursement->update([

            'paid_amount' =>
                $paidAmount,

            'paid_date' => now(),

            'status' => 'paid',

        ]);
    }
}