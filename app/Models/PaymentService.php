<?php

namespace App\Services\Payments;

use App\Models\Payment;

use App\Models\TreasuryTransaction;

use App\Services\Accounting\AccountingService;

use Illuminate\Support\Facades\DB;

class PaymentService
{
    public static function validate(
        Payment $payment
    ): void {

        DB::transaction(function () use (
            $payment
        ) {

            /*
            |--------------------------------------------------------------------------
            | Prevent Double Validation
            |--------------------------------------------------------------------------
            */

            if (

                $payment->status ===
                'paid'

            ) {

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Update Payment
            |--------------------------------------------------------------------------
            */

            $payment->update([

                'status' => 'paid',

            ]);

            /*
            |--------------------------------------------------------------------------
            | Treasury Entry
            |--------------------------------------------------------------------------
            */

            TreasuryTransaction::create([

                'company_id' =>

                    $payment->company_id,

                'payment_id' =>

                    $payment->id,

                'type' => 'entry',

                'amount' =>

                    $payment->amount,

                'description' =>

                    'Customer payment',

            ]);

            /*
            |--------------------------------------------------------------------------
            | Accounting Entry
            |--------------------------------------------------------------------------
            */

            AccountingService::createEntry([

                'company_id' =>

                    $payment->company_id,

                'reference' =>

                    $payment->reference
                    ?? ('PAY-' . $payment->id),

                'description' =>

                    'Customer payment',

                'lines' => [

                    /*
                    |--------------------------------------------------------------------------
                    | Treasury / Cash
                    |--------------------------------------------------------------------------
                    */

                    [

                        'account_code' =>

                            '531100',

                        'debit' =>

                            $payment->amount,

                        'credit' => 0,

                    ],

                    /*
                    |--------------------------------------------------------------------------
                    | Client Receivable
                    |--------------------------------------------------------------------------
                    */

                    [

                        'account_code' =>

                            '411100',

                        'debit' => 0,

                        'credit' =>

                            $payment->amount,

                    ],

                ],

            ]);
        });
    }
}