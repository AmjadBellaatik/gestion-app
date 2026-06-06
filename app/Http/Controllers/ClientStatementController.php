<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Company;
use App\Services\Accounting\ClientStatementService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientStatementController extends Controller
{
    public function __construct(private ClientStatementService $statements) {}

    /** HTML view of the account statement. */
    public function show(Client $client): Response
    {
        abort_unless(auth()->user()?->can('manage_payments'), 403);
        $this->assertSameCompany($client);

        $data = $this->statements->build($client->loadMissing('company'));

        return response()->view('accounting.client-statement', $data + [
            'company' => $client->company ?: Company::find($client->company_id),
        ]);
    }

    /** PDF export via DomPDF (same engine as documents). */
    public function pdf(Client $client): Response
    {
        abort_unless(auth()->user()?->can('manage_payments'), 403);
        $this->assertSameCompany($client);

        $data = $this->statements->build($client->loadMissing('company'));

        $pdf = Pdf::loadView('accounting.client-statement-pdf', $data + [
            'company' => $client->company ?: Company::find($client->company_id),
        ])->setPaper('A4', 'portrait');

        return $pdf->download('statement-'.\Illuminate\Support\Str::slug($client->display_name).'.pdf');
    }

    /** Excel-compatible CSV export (no external package required). */
    public function csv(Client $client): StreamedResponse
    {
        abort_unless(auth()->user()?->can('manage_payments'), 403);
        $this->assertSameCompany($client);

        $data = $this->statements->build($client);
        $filename = 'statement-'.\Illuminate\Support\Str::slug($client->display_name).'.csv';

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
            fputcsv($out, [
                __('messages.date'), __('messages.document'),
                __('messages.debit'), __('messages.credit'), __('messages.balance'),
            ]);
            foreach ($data['lines'] as $l) {
                fputcsv($out, [
                    optional($l['date'])->format('d/m/Y H:i'),
                    $l['document'],
                    number_format($l['debit'], 2, '.', ''),
                    number_format($l['credit'], 2, '.', ''),
                    number_format($l['balance'], 2, '.', ''),
                ]);
            }
            fputcsv($out, []);
            fputcsv($out, [__('messages.total_sales'), '', number_format($data['totals']['total_debit'], 2, '.', '')]);
            fputcsv($out, [__('messages.total_payments'), '', '', number_format($data['totals']['total_credit'], 2, '.', '')]);
            fputcsv($out, [__('messages.outstanding_balance'), '', '', '', number_format($data['totals']['outstanding'], 2, '.', '')]);
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Tenant isolation guard: never expose another company's client. */
    private function assertSameCompany(Client $client): void
    {
        $tenant = session('company_id');
        abort_if($tenant && (int) $client->company_id !== (int) $tenant, 404);
    }
}
