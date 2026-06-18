<?php
/**
 * PDF Visual Verification Script
 *
 * Bootstraps Laravel, creates minimal test data, generates PDFs for every
 * document template, saves them to storage/app/test-pdfs/, then prints the
 * file paths for Chrome headless screenshot capture.
 *
 * Usage:  php scripts/generate-test-pdfs.php
 */

define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Client;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\DocumentTemplate;
use App\Models\DocumentType;
use App\Models\Supplier;
use App\Services\Documents\DocumentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

// ── Disable CompanyScope for direct DB access ──────────────────────────────
// The script runs without a session company; we set it up manually.

echo "\n=== PDF VISUAL VERIFICATION ===\n\n";

// ── 1. Company ─────────────────────────────────────────────────────────────
$company = Company::withoutGlobalScopes()->first()
    ?? Company::create([
        'name'             => 'MOTO MAROC SARL',
        'legal_name'       => 'MOTO MAROC SARL AU',
        'phone'            => '0522 00 11 22',
        'email'            => 'contact@motomarcoc.ma',
        'address'          => '123 Boulevard Hassan II',
        'city'             => 'Casablanca',
        'country'          => 'Maroc',
        'ice'              => '001234567000012',
        'rc'               => '12345 FBL',
        'if'               => '12345678',
        'patente'          => '98765432',
        'cnss'             => '4567890',
        'tax_rate'         => 20,
        'default_language' => 'fr',
        'footer'           => 'MOTO MAROC SARL — Votre partenaire moto de confiance. Toute réclamation doit être formulée dans les 8 jours.',
        'invoice_footer'   => 'Merci de votre confiance. Paiement à 30 jours.',
        'bank_name'        => 'Attijariwafa Bank',
        'rib'              => 'RIB 007 780 0012345678901234 35',
        'legal_address'    => '123 Boulevard Hassan II, Casablanca',
    ]);

echo "Company: {$company->name} (ID: {$company->id})\n";

// ── 2. Client ──────────────────────────────────────────────────────────────
$client = Client::withoutGlobalScopes()
    ->where('company_id', $company->id)->first();
if (! $client) {
    $client = new Client([
        'company_id'  => $company->id,
        'client_type' => 'person',
        'first_name'  => 'Mohamed',
        'last_name'   => 'Benali',
        'phone'       => '0661 23 45 67',
        'email'       => 'mbenali@example.ma',
        'address'     => '45 Rue des Orangers, Quartier Palmier, Casablanca',
        'cin'         => 'BE123456',
    ]);
    $client->saveQuietly();
}

echo "Client: {$client->first_name} {$client->last_name} (ID: {$client->id})\n";

// ── 3. Supplier ────────────────────────────────────────────────────────────
$supplier = Supplier::withoutGlobalScopes()
    ->where('company_id', $company->id)->first();
if (! $supplier) {
    $supplier = new Supplier([
        'company_id' => $company->id,
        'name'       => 'HONDA MAROC DISTRIBUTION',
        'phone'      => '0522 99 88 77',
        'email'      => 'commandes@hondamaroc.ma',
        'address'    => 'Zone Industrielle Mohammedia',
        'city'       => 'Mohammedia',
        'ice'        => '000987654000001',
        'rc'         => 'RC 98765',
    ]);
    $supplier->saveQuietly();
}

echo "Supplier: {$supplier->name} (ID: {$supplier->id})\n";

// ── 4. Document types & templates ─────────────────────────────────────────
$typesDef = [
    DocumentType::INVOICE => [
        'name'       => 'Facture Commerciale',
        'prefix'     => 'FAC',
        'blade_view' => 'documents.pdf.commercial-invoice',
        'category'   => 'commercial',
    ],
    DocumentType::QUOTATION => [
        'name'       => 'Devis',
        'prefix'     => 'DEV',
        'blade_view' => 'documents.pdf.commercial-quotation',
        'category'   => 'commercial',
    ],
    DocumentType::DELIVERY_NOTE => [
        'name'       => 'Bon de Livraison',
        'prefix'     => 'BL',
        'blade_view' => 'documents.pdf.delivery-note',
        'category'   => 'commercial',
    ],
    DocumentType::SUPPLIER_ORDER => [
        'name'       => 'Bon de Commande',
        'prefix'     => 'BC',
        'blade_view' => 'documents.pdf.supplier-order',
        'category'   => 'purchase',
    ],
    DocumentType::WARRANTY_CONTRACT => [
        'name'       => 'Contrat de Garantie',
        'prefix'     => 'GAR',
        'blade_view' => 'documents.pdf.warranty-contract',
        'category'   => 'legal',
    ],
    DocumentType::OWNERSHIP => [
        'name'       => 'Certificat de Propriété',
        'prefix'     => 'PRSK',
        'blade_view' => 'documents.pdf.ownership-prsk',
        'category'   => 'legal',
    ],
];

$types = [];
foreach ($typesDef as $code => $def) {
    $type = DocumentType::withoutGlobalScopes()
        ->where('company_id', $company->id)
        ->where('code', $code)
        ->first();
    if (! $type) {
        $type = new DocumentType(array_merge($def, [
            'company_id'          => $company->id,
            'code'                => $code,
            'header_enabled'      => true,
            'footer_enabled'      => true,
            'affects_stock'       => false,
            'affects_accounting'  => false,
            'default_language'    => 'fr',
            'language'            => 'fr',
            'is_active'           => true,
            'automatic_variables' => [],
        ]));
        $type->saveQuietly();
    }

    $tmpl = DocumentTemplate::withoutGlobalScopes()
        ->where('company_id', $company->id)
        ->where('document_type_id', $type->id)
        ->first();
    if (! $tmpl) {
        $tmpl = new DocumentTemplate([
            'company_id'         => $company->id,
            'document_type_id'   => $type->id,
            'name'               => $def['name'] . ' — FR',
            'category'           => $def['category'],
            'blade_view'         => $def['blade_view'],
            'language'           => 'fr',
            'is_default'         => true,
            'version'            => 1,
            'orientation'        => 'portrait',
            'paper_size'         => 'A4',
            'rtl'                => false,
            'footer_enabled'     => true,
            'header_enabled'     => true,
            'signature_enabled'  => true,
            'stamp_enabled'      => true,
            'template_type'      => $def['category'],
            'variables'          => [],
        ]);
        $tmpl->saveQuietly();
    }

    $types[$code] = ['type' => $type, 'template' => $tmpl];
    echo "DocumentType: {$code} (ID: {$type->id})\n";
}

// ── 5. Document items shared ───────────────────────────────────────────────
// Standard motorcycle sale items — realistic data
$motoItems = [
    ['description' => 'Honda CB 125F — 2024 — Blanc Perlé', 'quantity' => 1, 'unit_price' => 18000.00, 'discount_amount' => 0, 'total' => 18000.00],
    ['description' => 'Casque intégral HJC C10 Taille L',   'quantity' => 1, 'unit_price' => 850.00,   'discount_amount' => 0, 'total' => 850.00],
    ['description' => 'Antivol U Kryptonite Serie 2',        'quantity' => 1, 'unit_price' => 320.00,   'discount_amount' => 0, 'total' => 320.00],
    ['description' => 'Carte Grise + Immatriculation',       'quantity' => 1, 'unit_price' => 250.00,   'discount_amount' => 0, 'total' => 250.00],
];
$motoTotal    = 19420.00;
$motoTax      = round($motoTotal * (20 / 120), 2);
$motoSubtotal = round($motoTotal - $motoTax, 2);

// Supplier order items
$supplyItems = [
    ['description' => 'Honda CB 125F (x5 unités)',     'quantity' => 5, 'unit_price' => 14500.00, 'discount_amount' => 0, 'total' => 72500.00],
    ['description' => 'Honda Wave 110 (x3 unités)',    'quantity' => 3, 'unit_price' => 11200.00, 'discount_amount' => 0, 'total' => 33600.00],
    ['description' => 'Pièces détachées (lot mixte)',  'quantity' => 1, 'unit_price' => 8760.00,  'discount_amount' => 0, 'total' => 8760.00],
];
$supplyTotal    = 114860.00;
$supplyTax      = round($supplyTotal * (20 / 120), 2);
$supplySubtotal = round($supplyTotal - $supplyTax, 2);

// ── helper: build QR URL ───────────────────────────────────────────────────
function makeQrSvg(string $url): string
{
    return base64_encode(
        QrCode::format('svg')->size(120)->margin(1)->generate($url)
    );
}

// ── helper: save PDF ───────────────────────────────────────────────────────
function renderAndSave(Document $document, Company $company, string $outputDir): string
{
    $document->loadMissing([
        'documentType', 'documentTemplate', 'client', 'supplier', 'items',
    ]);

    $view = $document->documentTemplate?->blade_view
        ?? $document->documentType->defaultBladeView();

    $qrSvg = makeQrSvg($document->verification_url ?? 'https://moto-maroc.ma/verify/' . $document->uuid);

    $placeholders = [];

    $pdf = Pdf::loadView($view, [
        'document'      => $document,
        'company'       => $company,
        'template'      => $document->documentTemplate,
        'client'        => $document->client,
        'supplier'      => $document->supplier,
        'motorcycleUnit'=> null,
        'qrSvg'         => $qrSvg,
        'placeholders'  => $placeholders,
        'repairTicket'  => null,
        'city'          => $company->city,
    ])->setPaper('A4', 'portrait');

    $filename = $outputDir . '/' . ($document->document_number ?? 'doc-' . $document->id) . '.pdf';
    file_put_contents($filename, $pdf->output());
    return $filename;
}

// ── 6. Create documents & generate PDFs ───────────────────────────────────
$outDir = base_path('storage/app/test-pdfs');
if (! is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}

$generated = [];

// ── COMMERCIAL INVOICE ─────────────────────────────────────────────────────
$typeObj = $types[DocumentType::INVOICE]['type'];
$tmpl    = $types[DocumentType::INVOICE]['template'];
$doc = new Document([
    'company_id'          => $company->id,
    'document_type_id'    => $typeObj->id,
    'document_template_id'=> $tmpl->id,
    'client_id'           => $client->id,
    'document_number'     => 'FAC-2026-0001',
    'document_date'       => now()->toDateString(),
    'language'            => 'fr',
    'status'              => 'generated',
    'subtotal'            => $motoSubtotal,
    'tax_rate'            => 20,
    'tax_amount'          => $motoTax,
    'tax'                 => $motoTax,
    'discount_amount'     => 0,
    'total_amount'        => $motoTotal,
    'total'               => $motoTotal,
    'invoice_source'      => 'sale',
    'uuid'                => (string) \Illuminate\Support\Str::uuid(),
    'verification_url'    => 'https://moto-maroc.ma/verify/inv-0001',
    'notes'               => 'Livraison immédiate. Garantie constructeur 2 ans.',
]);
$doc->saveQuietly();
foreach ($motoItems as $item) {
    $di = new DocumentItem(array_merge($item, ['document_id' => $doc->id, 'item_type' => 'product']));
    $di->saveQuietly();
}
$doc->load('items');
$path = renderAndSave($doc, $company, $outDir);
$generated[] = ['type' => 'Commercial Invoice', 'path' => $path];
echo "✓ Commercial Invoice: {$path}\n";

// ── QUOTATION ──────────────────────────────────────────────────────────────
$typeObj = $types[DocumentType::QUOTATION]['type'];
$tmpl    = $types[DocumentType::QUOTATION]['template'];
$doc2 = new Document([
    'company_id'          => $company->id,
    'document_type_id'    => $typeObj->id,
    'document_template_id'=> $tmpl->id,
    'client_id'           => $client->id,
    'document_number'     => 'DEV-2026-0001',
    'document_date'       => now()->toDateString(),
    'language'            => 'fr',
    'status'              => 'generated',
    'subtotal'            => $motoSubtotal,
    'tax_rate'            => 20,
    'tax_amount'          => $motoTax,
    'tax'                 => $motoTax,
    'discount_amount'     => 0,
    'total_amount'        => $motoTotal,
    'total'               => $motoTotal,
    'invoice_source'      => 'sale',
    'uuid'                => (string) \Illuminate\Support\Str::uuid(),
    'verification_url'    => 'https://moto-maroc.ma/verify/dev-0001',
    'notes'               => 'Devis valable 30 jours à compter de la date d\'émission.',
]);
$doc2->saveQuietly();
foreach ($motoItems as $item) {
    $di = new DocumentItem(array_merge($item, ['document_id' => $doc2->id, 'item_type' => 'product']));
    $di->saveQuietly();
}
$doc2->load('items');
$path = renderAndSave($doc2, $company, $outDir);
$generated[] = ['type' => 'Quotation', 'path' => $path];
echo "✓ Quotation: {$path}\n";

// ── DELIVERY NOTE ──────────────────────────────────────────────────────────
$typeObj = $types[DocumentType::DELIVERY_NOTE]['type'];
$tmpl    = $types[DocumentType::DELIVERY_NOTE]['template'];
$deliveryItems = [
    ['description' => 'Honda CB 125F — 2024 — Blanc Perlé — Chassis: MLHCA6300N4000001', 'quantity' => 1, 'unit_price' => 18000.00, 'discount_amount' => 0, 'total' => 18000.00],
    ['description' => 'Casque + antivol (accessoires)',                                    'quantity' => 1, 'unit_price' => 1170.00,  'discount_amount' => 0, 'total' => 1170.00],
];
$doc3 = new Document([
    'company_id'          => $company->id,
    'document_type_id'    => $typeObj->id,
    'document_template_id'=> $tmpl->id,
    'client_id'           => $client->id,
    'document_number'     => 'BL-2026-0001',
    'document_date'       => now()->toDateString(),
    'language'            => 'fr',
    'status'              => 'generated',
    'subtotal'            => 15975.00,
    'tax_rate'            => 20,
    'tax_amount'          => 3195.00,
    'tax'                 => 3195.00,
    'discount_amount'     => 0,
    'total_amount'        => 19170.00,
    'total'               => 19170.00,
    'uuid'                => (string) \Illuminate\Support\Str::uuid(),
    'verification_url'    => 'https://moto-maroc.ma/verify/bl-0001',
]);
$doc3->saveQuietly();
foreach ($deliveryItems as $item) {
    $di = new DocumentItem(array_merge($item, ['document_id' => $doc3->id, 'item_type' => 'product']));
    $di->saveQuietly();
}
$doc3->load('items');
$path = renderAndSave($doc3, $company, $outDir);
$generated[] = ['type' => 'Delivery Note', 'path' => $path];
echo "✓ Delivery Note: {$path}\n";

// ── SUPPLIER ORDER ─────────────────────────────────────────────────────────
$typeObj = $types[DocumentType::SUPPLIER_ORDER]['type'];
$tmpl    = $types[DocumentType::SUPPLIER_ORDER]['template'];
$doc4 = new Document([
    'company_id'          => $company->id,
    'document_type_id'    => $typeObj->id,
    'document_template_id'=> $tmpl->id,
    'supplier_id'         => $supplier->id,
    'document_number'     => 'BC-2026-0001',
    'document_date'       => now()->toDateString(),
    'language'            => 'fr',
    'status'              => 'generated',
    'subtotal'            => $supplySubtotal,
    'tax_rate'            => 20,
    'tax_amount'          => $supplyTax,
    'tax'                 => $supplyTax,
    'discount_amount'     => 0,
    'total_amount'        => $supplyTotal,
    'total'               => $supplyTotal,
    'uuid'                => (string) \Illuminate\Support\Str::uuid(),
    'verification_url'    => 'https://moto-maroc.ma/verify/bc-0001',
    'notes'               => 'Délai de livraison : 15 jours ouvrés. Incoterms : DDP Casablanca.',
]);
$doc4->saveQuietly();
foreach ($supplyItems as $item) {
    $di = new DocumentItem(array_merge($item, ['document_id' => $doc4->id, 'item_type' => 'product']));
    $di->saveQuietly();
}
$doc4->load('items');
$path = renderAndSave($doc4, $company, $outDir);
$generated[] = ['type' => 'Supplier Order', 'path' => $path];
echo "✓ Supplier Order: {$path}\n";

// ── WARRANTY CONTRACT ──────────────────────────────────────────────────────
$typeObj = $types[DocumentType::WARRANTY_CONTRACT]['type'];
$tmpl    = $types[DocumentType::WARRANTY_CONTRACT]['template'];
$doc5 = new Document([
    'company_id'          => $company->id,
    'document_type_id'    => $typeObj->id,
    'document_template_id'=> $tmpl->id,
    'client_id'           => $client->id,
    'document_number'     => 'GAR-2026-0001',
    'document_date'       => now()->toDateString(),
    'language'            => 'fr',
    'status'              => 'generated',
    'subtotal'            => 18000.00,
    'tax_rate'            => 20,
    'tax_amount'          => 3000.00,
    'tax'                 => 3000.00,
    'discount_amount'     => 0,
    'total_amount'        => 18000.00,
    'total'               => 18000.00,
    'uuid'                => (string) \Illuminate\Support\Str::uuid(),
    'verification_url'    => 'https://moto-maroc.ma/verify/gar-0001',
    'metadata'            => [
        'chassis_number'    => 'MLHCA6300N4000001',
        'engine_number'     => 'CA6300N4000001',
        'model_name'        => 'Honda CB 125F',
        'color'             => 'Blanc Perlé',
        'purchase_date'     => now()->toDateString(),
        'warranty_end_date' => now()->addYears(2)->toDateString(),
        'warranty_terms'    => implode("\n\n", [
            "La présente garantie couvre les défauts de fabrication pour une durée de 24 mois à compter de la date de vente, dans les conditions normales d'utilisation définies dans le manuel du propriétaire.",
            "Sont exclus de la garantie : les consommables (huile, pneus, plaquettes de frein, filtres), les pièces d'usure normale, les dommages résultant d'accidents, de mauvais entretien, de modifications non autorisées ou d'une utilisation non conforme.",
            "Pour bénéficier de la garantie, le propriétaire doit présenter ce document ainsi que la facture d'achat originale. Les révisions périodiques doivent être effectuées dans notre réseau agréé.",
            "La garantie ne couvre pas les frais de transport, d'immobilisation ni les pertes indirectes. En cas de litige, seul le tribunal de commerce de Casablanca est compétent.",
        ]),
    ],
    'notes' => 'Honda CB 125F — Garantie fabricant 2 ans. Entretien obligatoire tous les 3000 km.',
]);
$doc5->saveQuietly();
// Warranty contract has one item line for the motorcycle
$di = new DocumentItem([
    'document_id' => $doc5->id,
    'item_type'   => 'product',
    'description' => 'Honda CB 125F 2024 — Chassis MLHCA6300N4000001 — Blanc Perlé',
    'quantity'    => 1,
    'unit_price'  => 18000.00,
    'total'       => 18000.00,
    'discount_amount' => 0,
]);
$di->saveQuietly();
$doc5->load(['items', 'client', 'company', 'documentType', 'documentTemplate']);
$path = renderAndSave($doc5, $company, $outDir);
$generated[] = ['type' => 'Warranty Contract', 'path' => $path];
echo "✓ Warranty Contract: {$path}\n";

// ── OWNERSHIP CERTIFICATE ──────────────────────────────────────────────────
$typeObj = $types[DocumentType::OWNERSHIP]['type'];
$tmpl    = $types[DocumentType::OWNERSHIP]['template'];
$doc6 = new Document([
    'company_id'          => $company->id,
    'document_type_id'    => $typeObj->id,
    'document_template_id'=> $tmpl->id,
    'client_id'           => $client->id,
    'document_number'     => 'PRSK-2026-0001',
    'document_date'       => now()->toDateString(),
    'language'            => 'fr',
    'status'              => 'generated',
    'total_amount'        => 18000.00,
    'total'               => 18000.00,
    'uuid'                => (string) \Illuminate\Support\Str::uuid(),
    'verification_url'    => 'https://moto-maroc.ma/verify/prsk-0001',
    'metadata'            => [
        'chassis_number' => 'MLHCA6300N4000001',
        'engine_number'  => 'CA6300N4000001',
        'model_name'     => 'Honda CB 125F',
        'color'          => 'Blanc Perlé',
    ],
]);
$doc6->saveQuietly();
$di = new DocumentItem([
    'document_id' => $doc6->id,
    'item_type'   => 'product',
    'description' => 'Honda CB 125F 2024',
    'quantity'    => 1,
    'unit_price'  => 18000.00,
    'total'       => 18000.00,
    'discount_amount' => 0,
]);
$di->saveQuietly();
$doc6->load(['items', 'client', 'company', 'documentType', 'documentTemplate']);
$path = renderAndSave($doc6, $company, $outDir);
$generated[] = ['type' => 'Ownership Certificate', 'path' => $path];
echo "✓ Ownership Certificate: {$path}\n";

// ── REPAIR INVOICE (invoice_source = repair) ───────────────────────────────
$typeObj = $types[DocumentType::INVOICE]['type'];
$tmpl    = $types[DocumentType::INVOICE]['template'];
$repairItems = [
    ['description' => 'Vidange huile moteur (SAE 10W40)',      'quantity' => 1, 'unit_price' => 120.00,  'discount_amount' => 0, 'total' => 120.00],
    ['description' => 'Filtre à huile Honda OEM',              'quantity' => 1, 'unit_price' => 85.00,   'discount_amount' => 0, 'total' => 85.00],
    ['description' => 'Filtre à air Honda CB 125F',            'quantity' => 1, 'unit_price' => 95.00,   'discount_amount' => 0, 'total' => 95.00],
    ['description' => 'Bougies d\'allumage NGK CR6HSA (x2)',   'quantity' => 2, 'unit_price' => 45.00,   'discount_amount' => 0, 'total' => 90.00],
    ['description' => 'Plaquettes de frein avant',             'quantity' => 1, 'unit_price' => 160.00,  'discount_amount' => 0, 'total' => 160.00],
    ['description' => 'Main-d\'œuvre révision complète 10 000 km', 'quantity' => 1, 'unit_price' => 350.00, 'discount_amount' => 0, 'total' => 350.00],
];
$repairTotal    = 900.00;
$repairTax      = round($repairTotal * (20 / 120), 2);
$repairSubtotal = round($repairTotal - $repairTax, 2);

$doc7 = new Document([
    'company_id'          => $company->id,
    'document_type_id'    => $typeObj->id,
    'document_template_id'=> $tmpl->id,
    'client_id'           => $client->id,
    'document_number'     => 'FAC-REP-2026-0001',
    'document_date'       => now()->toDateString(),
    'language'            => 'fr',
    'status'              => 'generated',
    'subtotal'            => $repairSubtotal,
    'tax_rate'            => 20,
    'tax_amount'          => $repairTax,
    'tax'                 => $repairTax,
    'discount_amount'     => 0,
    'total_amount'        => $repairTotal,
    'total'               => $repairTotal,
    'invoice_source'      => 'repair',
    'uuid'                => (string) \Illuminate\Support\Str::uuid(),
    'verification_url'    => 'https://moto-maroc.ma/verify/rep-0001',
    'notes'               => 'Révision complète 10 000 km — Honda CB 125F — Chassis : MLHCA6300N4000001.',
]);
$doc7->saveQuietly();
foreach ($repairItems as $item) {
    $di = new DocumentItem(array_merge($item, ['document_id' => $doc7->id, 'item_type' => 'service']));
    $di->saveQuietly();
}
$doc7->load('items');
$path = renderAndSave($doc7, $company, $outDir);
$generated[] = ['type' => 'Repair Invoice', 'path' => $path];
echo "✓ Repair Invoice: {$path}\n";

// ── Multi-page: Commercial Invoice with 100 lines ──────────────────────────
$typeObj = $types[DocumentType::INVOICE]['type'];
$tmpl    = $types[DocumentType::INVOICE]['template'];
$bigItems = [];
$bigTotal = 0;
for ($i = 1; $i <= 40; $i++) {
    $price = round(100 + ($i * 37.5), 2);
    $qty   = ($i % 3 === 0) ? 2 : 1;
    $total = round($price * $qty, 2);
    $bigItems[] = [
        'description'    => sprintf('Pièce réf. HON-CB125-%04d', $i) . ' — ' . ['Filtre', 'Joint', 'Roulement', 'Vis', 'Câble'][$i % 5] . " N°{$i}",
        'quantity'       => $qty,
        'unit_price'     => $price,
        'discount_amount'=> 0,
        'total'          => $total,
    ];
    $bigTotal += $total;
}
$bigTax      = round($bigTotal * (20 / 120), 2);
$bigSubtotal = round($bigTotal - $bigTax, 2);

$docBig = new Document([
    'company_id'          => $company->id,
    'document_type_id'    => $typeObj->id,
    'document_template_id'=> $tmpl->id,
    'client_id'           => $client->id,
    'document_number'     => 'FAC-2026-MULTI',
    'document_date'       => now()->toDateString(),
    'language'            => 'fr',
    'status'              => 'generated',
    'subtotal'            => $bigSubtotal,
    'tax_rate'            => 20,
    'tax_amount'          => $bigTax,
    'tax'                 => $bigTax,
    'discount_amount'     => 0,
    'total_amount'        => $bigTotal,
    'total'               => $bigTotal,
    'invoice_source'      => 'sale',
    'uuid'                => (string) \Illuminate\Support\Str::uuid(),
    'verification_url'    => 'https://moto-maroc.ma/verify/multi',
    'notes'               => 'Commande groupée pièces détachées. Livraison en 5 jours ouvrés.',
]);
$docBig->saveQuietly();
foreach ($bigItems as $item) {
    $di = new DocumentItem(array_merge($item, ['document_id' => $docBig->id, 'item_type' => 'product']));
    $di->saveQuietly();
}
$docBig->load('items');
$path = renderAndSave($docBig, $company, $outDir);
$generated[] = ['type' => 'Multi-page Invoice (40 lines)', 'path' => $path];
echo "✓ Multi-page Invoice (40 lines): {$path}\n";

// ── Summary ────────────────────────────────────────────────────────────────
echo "\n=== GENERATED PDF FILES ===\n";
foreach ($generated as $g) {
    $size = file_exists($g['path']) ? round(filesize($g['path']) / 1024) . ' KB' : 'ERROR';
    echo sprintf("  %-40s %s (%s)\n", $g['type'], basename($g['path']), $size);
}

echo "\nAll PDFs saved to: {$outDir}\n";
echo "\nNow run the screenshot script to capture PNG images.\n";
