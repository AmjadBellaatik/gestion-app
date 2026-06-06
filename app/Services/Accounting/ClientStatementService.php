<?php

namespace App\Services\Accounting;

use App\Models\Client;
use Illuminate\Support\Collection;

/**
 * Builds a client account statement (ledger) with running balance.
 *
 * Debits  = sales (amount billed).
 * Credits = validated payments (amount received).
 * Running balance = cumulative (debit − credit) = what the client owes over time.
 *
 * The headline outstanding figure is taken from the SAME source of truth used
 * everywhere else: Client::outstanding_balance (SUM of remaining_amount on
 * unpaid/partial sales). All queries inherit CompanyScope via relationships.
 */
class ClientStatementService
{
    /**
     * @return array{client: Client, lines: Collection, totals: array}
     */
    public function build(Client $client): array
    {
        // Debits — sales billed
        $sales = $client->sales()
            ->get(['id', 'sale_number', 'total', 'sale_date', 'created_at'])
            ->map(fn ($s) => [
                'date'     => $s->sale_date ?: $s->created_at,
                'document' => $s->sale_number,
                'type'     => 'sale',
                'debit'    => (float) $s->total,
                'credit'   => 0.0,
            ]);

        // Credits — validated payments
        $payments = $client->payments()
            ->where('status', 'paid')
            ->get(['id', 'reference', 'amount', 'payment_method', 'created_at'])
            ->map(fn ($p) => [
                'date'     => $p->created_at,
                'document' => $p->reference ?: strtoupper($p->payment_method ?? 'PAYMENT'),
                'type'     => 'payment',
                'debit'    => 0.0,
                'credit'   => (float) $p->amount,
            ]);

        // Merge, sort chronologically, compute running balance
        $running = 0.0;
        $lines = $sales->concat($payments)
            ->sortBy(fn ($l) => $l['date']?->getTimestamp() ?? 0)
            ->values()
            ->map(function ($line) use (&$running) {
                $running += $line['debit'] - $line['credit'];
                $line['balance'] = round($running, 2);
                return $line;
            });

        $totals = [
            'total_debit'       => round($lines->sum('debit'), 2),
            'total_credit'      => round($lines->sum('credit'), 2),
            'ledger_balance'    => round($running, 2),
            // Source of truth (matches detail / list / balances pages exactly):
            'outstanding'       => $client->outstanding_balance,
            'credit_balance'    => $client->credit_balance,
        ];

        return ['client' => $client, 'lines' => $lines, 'totals' => $totals];
    }
}
