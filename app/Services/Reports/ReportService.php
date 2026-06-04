<?php

namespace App\Services\Reports;

use App\Models\User;
use App\Models\Company;
use App\Models\Document;
use App\Models\RepairTicket;
use App\Models\StockMovement;
use App\Models\Reimbursement;
use App\Models\Transaction;
use App\Models\Reseller;

class ReportService
{
    public static function sales(array $filters = [])
    {
        return Document::query()

            ->when(
                $filters['date_from'] ?? null,
                fn ($q, $date) => $q->whereDate(
                    'created_at',
                    '>=',
                    $date
                )
            )

            ->when(
                $filters['date_to'] ?? null,
                fn ($q, $date) => $q->whereDate(
                    'created_at',
                    '<=',
                    $date
                )
            )

            ->when(
                $filters['company_id'] ?? null,
                fn ($q, $company) => $q->where(
                    'company_id',
                    $company
                )
            )

            ->latest()

            ->get();
    }

    public static function repairs(array $filters = [])
    {
        return RepairTicket::query()

            ->when(
                $filters['date_from'] ?? null,
                fn ($q, $date) => $q->whereDate(
                    'created_at',
                    '>=',
                    $date
                )
            )

            ->when(
                $filters['date_to'] ?? null,
                fn ($q, $date) => $q->whereDate(
                    'created_at',
                    '<=',
                    $date
                )
            )

            ->latest()

            ->get();
    }

    public static function stock(array $filters = [])
    {
        return StockMovement::query()

            ->when(
                $filters['company_id'] ?? null,
                fn ($q, $company) => $q->where(
                    'company_id',
                    $company
                )
            )

            ->latest()

            ->get();
    }

    public static function resellerCredits()
    {
        return Reseller::query()

            ->where('credit', '>', 0)

            ->get();
    }

    public static function reimbursements()
    {
        return Reimbursement::query()

            ->latest()

            ->get();
    }

    public static function profits(array $filters = [])
    {
        // Transaction has CompanyScope but we also apply an explicit company_id filter
        // from the validated request filters as defense-in-depth.
        $income = Transaction::query()
            ->where('direction', 'in')
            ->when($filters['company_id'] ?? null, fn ($q, $c) => $q->where('company_id', $c))
            ->sum('amount');

        $expenses = Transaction::query()
            ->where('direction', 'out')
            ->when($filters['company_id'] ?? null, fn ($q, $c) => $q->where('company_id', $c))
            ->sum('amount');

        return [
            'income'   => $income,
            'expenses' => $expenses,
            'profit'   => $income - $expenses,
        ];
    }

    public static function companyReport(
        int $companyId
    )
    {
        return [

            'sales' => Document::query()

                ->where(
                    'company_id',
                    $companyId
                )

                ->count(),

            'repairs' => RepairTicket::query()

                ->where(
                    'company_id',
                    $companyId
                )

                ->count(),

            'transactions' => Transaction::query()

                ->where(
                    'company_id',
                    $companyId
                )

                ->sum('amount'),

        ];
    }
}