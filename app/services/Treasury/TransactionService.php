<?php

namespace App\Services\Treasury;

use App\Models\Transaction;

class TransactionService
{
    public static function create(

        array $data

    ): Transaction {

        return Transaction::create([

            'company_id' =>
                session('company_id'),

            'fund_id' =>
                $data['fund_id'] ?? null,

            'transactionable_type' =>
                $data['transactionable_type'],

            'transactionable_id' =>
                $data['transactionable_id'],

            'type' =>
                $data['type'],

            'reference' =>
                $data['reference'] ?? null,

            'amount' =>
                $data['amount'],

            'direction' =>
                $data['direction'],

            'transaction_date' =>
                $data['transaction_date']
                    ?? now(),

            'payment_method' =>
                $data['payment_method']
                    ?? null,

            'notes' =>
                $data['notes']
                    ?? null,

        ]);
    }
}