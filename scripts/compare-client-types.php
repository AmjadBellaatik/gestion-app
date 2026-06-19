<?php
/**
 * Client-type HTML comparison harness.
 *
 * Reproduces the production scenario: the SAME document template rendered for a
 * `company` client (ER FACTORY) vs an `administration` client (COMMUNE DE BNI HILAL).
 *
 * Two experiments:
 *   A) IDENTICAL data, only client_type differs  → isolates template branching.
 *   B) REALISTIC data (company has ICE/RC, administration does not) → mimics BL-0001 vs BL-0007.
 *
 * For each case it dumps:
 *   - the final rendered HTML  (storage/app/test-pdfs/html/*.html)
 *   - a PDF                    (storage/app/test-pdfs/*.pdf)
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Client;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\DocumentTemplate;
use App\Models\DocumentType;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

$company = Company::withoutGlobalScopes()->first();
app()->setLocale('fr');

$outDir  = base_path('storage/app/test-pdfs');
$htmlDir = $outDir . '/html';
@mkdir($htmlDir, 0755, true);

// ── Clients ────────────────────────────────────────────────────────────────
// Experiment B (realistic): company WITH ice/rc, administration WITHOUT.
function ensureClient(Company $company, array $attrs): Client
{
    $c = Client::withoutGlobalScopes()
        ->where('company_id', $company->id)
        ->where('client_type', $attrs['client_type'])
        ->where(function ($q) use ($attrs) {
            $q->where('company_name', $attrs['company_name'] ?? null)
              ->orWhere('administration_name', $attrs['administration_name'] ?? null);
        })->first();
    if (! $c) {
        $c = new Client(array_merge(['company_id' => $company->id], $attrs));
        $c->saveQuietly();
    }
    return $c;
}

$companyClient = ensureClient($company, [
    'client_type'  => 'company',
    'company_name' => 'ER FACTORY',
    'ice'          => '003731648000083',
    'rc'           => '77719',
    'phone'        => '0661172447',
    'address'      => 'ANGLE AVENUE MLY SLIMANE ET RUE AMR BEN EL ASS, N 8, RESIDENCE AL HAMDOU LILIAH, BUREAU N 3 ET MAGASIN N 1, KENITRA, Maroc',
]);

$adminClient = ensureClient($company, [
    'client_type'        => 'administration',
    'administration_name'=> 'COMMUNE DE BNI HILAL',
    'phone'              => '0662733708',
    'address'            => '84 Lot. Tounsia , Arbaa Aounate Sidi Bennour - Maroc',
    // No ICE, no RC — exactly like the real administration record.
]);

// Experiment A (controlled): identical data, only client_type differs.
$companyClientCtl = ensureClient($company, [
    'client_type'  => 'company',
    'company_name' => 'CTRL COMPANY',
    'ice'          => '111111111000011',
    'rc'           => '55555',
    'phone'        => '0600000000',
    'address'      => 'Même adresse de contrôle, Casablanca, Maroc',
]);
$adminClientCtl = ensureClient($company, [
    'client_type'        => 'administration',
    'administration_name'=> 'CTRL ADMINISTRATION',
    'ice'                => '111111111000011',
    'rc'                 => '55555',
    'phone'              => '0600000000',
    'address'            => 'Même adresse de contrôle, Casablanca, Maroc',
]);

// ── Helpers ────────────────────────────────────────────────────────────────
function qr(string $url): string
{
    return base64_encode(QrCode::format('svg')->size(120)->margin(1)->generate($url));
}

function buildDoc(Company $company, Client $client, DocumentType $type, DocumentTemplate $tmpl, string $number, array $meta = []): Document
{
    $doc = new Document([
        'company_id'          => $company->id,
        'document_type_id'    => $type->id,
        'document_template_id'=> $tmpl->id,
        'client_id'           => $client->id,
        'document_number'     => $number,
        'document_date'       => now()->toDateString(),
        'language'            => 'fr',
        'status'              => 'generated',
        'subtotal'            => 10000.00,
        'tax_rate'            => 20,
        'tax_amount'          => 2000.00,
        'tax'                 => 2000.00,
        'discount_amount'     => 0,
        'total_amount'        => 12000.00,
        'total'               => 12000.00,
        'uuid'                => (string) \Illuminate\Support\Str::uuid(),
        'verification_url'    => 'https://verify/' . $number,
        'metadata'            => $meta ?: null,
    ]);
    $doc->saveQuietly();
    $di = new DocumentItem([
        'document_id' => $doc->id,
        'item_type'   => 'product',
        'description' => 'R50',
        'quantity'    => 1,
        'unit_price'  => 12000.00,
        'total'       => 12000.00,
        'discount_amount' => 0,
    ]);
    $di->saveQuietly();
    $doc->load(['items', 'company', 'documentType', 'documentTemplate']);
    // CompanyScope filters Client by session('company_id') which is null in CLI;
    // bind the scope-free client explicitly so templates receive real data.
    $doc->setRelation('client', $client);
    return $doc;
}

function renderHtmlAndPdf(Document $doc, Company $company, string $view, string $label, string $htmlDir, string $outDir): array
{
    $data = [
        'document'      => $doc,
        'company'       => $company,
        'template'      => $doc->documentTemplate,
        'client'        => $doc->client,
        'supplier'      => null,
        'motorcycleUnit'=> null,
        'qrSvg'         => qr($doc->verification_url),
        'placeholders'  => [],
        'repairTicket'  => null,
        'city'          => $company->city,
    ];

    // 1) Raw HTML
    $html = view($view, $data)->render();
    $htmlPath = $htmlDir . '/' . $label . '.html';
    file_put_contents($htmlPath, $html);

    // 2) PDF
    $pdf = Pdf::loadView($view, $data)->setPaper('A4', 'portrait');
    $pdfPath = $outDir . '/' . $label . '.pdf';
    file_put_contents($pdfPath, $pdf->output());

    return ['html' => $htmlPath, 'pdf' => $pdfPath, 'html_len' => strlen($html), 'lines' => substr_count($html, "\n")];
}

// ── Resolve types/templates ────────────────────────────────────────────────
function typeAndTemplate(Company $company, string $code, string $view): array
{
    $type = DocumentType::withoutGlobalScopes()->where('company_id', $company->id)->where('code', $code)->first();
    $tmpl = DocumentTemplate::withoutGlobalScopes()->where('company_id', $company->id)->where('document_type_id', $type->id)->first();
    return [$type, $tmpl];
}

[$blType, $blTmpl]   = typeAndTemplate($company, DocumentType::DELIVERY_NOTE, 'documents.pdf.delivery-note');
[$garType, $garTmpl] = typeAndTemplate($company, DocumentType::WARRANTY_CONTRACT, 'documents.pdf.warranty-contract');

$warrantyMeta = [
    'warranty_duration_value' => 1,
    'warranty_duration_unit'  => 'years',
    'warranty_kilometers'     => 5000,
];

$results = [];

// ── Experiment B: realistic ────────────────────────────────────────────────
$results['BL_company']  = renderHtmlAndPdf(buildDoc($company, $companyClient, $blType, $blTmpl, 'CMP-BL-COMPANY'),  $company, 'documents.pdf.delivery-note',     'BL-company',  $htmlDir, $outDir);
$results['BL_admin']    = renderHtmlAndPdf(buildDoc($company, $adminClient,   $blType, $blTmpl, 'CMP-BL-ADMIN'),    $company, 'documents.pdf.delivery-note',     'BL-admin',    $htmlDir, $outDir);
$results['GAR_company'] = renderHtmlAndPdf(buildDoc($company, $companyClient, $garType, $garTmpl, 'CMP-GAR-COMPANY', $warrantyMeta), $company, 'documents.pdf.warranty-contract', 'GAR-company', $htmlDir, $outDir);
$results['GAR_admin']   = renderHtmlAndPdf(buildDoc($company, $adminClient,   $garType, $garTmpl, 'CMP-GAR-ADMIN',   $warrantyMeta), $company, 'documents.pdf.warranty-contract', 'GAR-admin',   $htmlDir, $outDir);

// ── Experiment A: controlled (identical data) ──────────────────────────────
$results['BL_company_ctl']  = renderHtmlAndPdf(buildDoc($company, $companyClientCtl, $blType, $blTmpl, 'CTL-BL-COMPANY'), $company, 'documents.pdf.delivery-note', 'BL-company-ctl', $htmlDir, $outDir);
$results['BL_admin_ctl']    = renderHtmlAndPdf(buildDoc($company, $adminClientCtl,   $blType, $blTmpl, 'CTL-BL-ADMIN'),   $company, 'documents.pdf.delivery-note', 'BL-admin-ctl',   $htmlDir, $outDir);

echo "=== RENDER RESULTS ===\n";
foreach ($results as $k => $r) {
    printf("  %-18s html=%6d bytes, %3d lines  -> %s\n", $k, $r['html_len'], $r['lines'], basename($r['pdf']));
}
echo "\nHTML dumped to: {$htmlDir}\n";
echo "PDFs dumped to: {$outDir}\n";
