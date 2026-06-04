<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmergencyAccessController;

use Barryvdh\DomPDF\Facade\Pdf;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

use App\Models\Document;

use App\Http\Controllers\SaleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RepairController;
use App\Http\Controllers\RepairPrintController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportPdfController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CompanySwitchController;
use App\Http\Controllers\DocumentPdfController;
use App\Http\Controllers\DocumentVerificationController;

use App\Services\Documents\DocumentPlaceholderService;

use App\Services\Amounts\AmountInWordsService;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {

    return redirect('/admin');

});

/*
|--------------------------------------------------------------------------
| Document Verification
|--------------------------------------------------------------------------
*/

Route::middleware('throttle:30,1')->get(

    '/verify/document/{uuid}',

    [

        DocumentVerificationController::class,

        'verify',

    ]

)->name(
    'documents.verify'
);

/*
|--------------------------------------------------------------------------
| Locale Test
|--------------------------------------------------------------------------
*/

Route::get('/test-session', function () {

    abort_unless(app()->isLocal(), 404);

    return app()->getLocale();

});

/*
|--------------------------------------------------------------------------
| Sales Routes Protection
|--------------------------------------------------------------------------
*/

Route::middleware([

    'auth',

    'permission:manage_sales',

    'throttle:60,1',

])->group(function () {

    Route::resource(

        'sales',

        SaleController::class

    );

});

/*
|--------------------------------------------------------------------------
| User Management Routes Protection
|--------------------------------------------------------------------------
*/

Route::middleware([

    'auth',

    'permission:manage_users',

    'throttle:60,1',

])->group(function () {

    Route::resource(

        'users',

        UserController::class

    );

});

/*
|--------------------------------------------------------------------------
| Settings Routes Protection
|--------------------------------------------------------------------------
*/

Route::middleware([

    'auth',

    'permission:manage_settings',

    'throttle:60,1',

])->group(function () {

    Route::resource(

        'settings',

        SettingController::class

    );

});

/*
|--------------------------------------------------------------------------
| Reports Routes Protection
|--------------------------------------------------------------------------
*/

Route::middleware([

    'auth',

    'permission:view_reports',

    'throttle:30,1',

])->group(function () {

    Route::get(

        '/reports',

        [ReportController::class, 'index']

    )->name('reports.index');

    Route::get('/reports/pdf', [ReportPdfController::class, 'generate'])->name('reports.pdf');

});

/*
|--------------------------------------------------------------------------
| Repairs Routes Protection
|--------------------------------------------------------------------------
*/

Route::middleware([

    'auth',

    'permission:manage_repairs',

    'throttle:60,1',

])->group(function () {

    Route::resource(

        'repairs',

        RepairController::class

    );

    /*
    |--------------------------------------------------------------------------
    | Repair Print Routes
    |--------------------------------------------------------------------------
    */

    Route::get(

        '/repairs/{repair}/print-order',

        [RepairPrintController::class, 'printOrder']

    )->name('repairs.print-order');

    Route::get(

        '/repairs/{repair}/print-invoice',

        [RepairPrintController::class, 'printInvoice']

    )->name('repairs.print-invoice');

});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware([

    'auth',

    'throttle:120,1',

])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Company Switcher
    |--------------------------------------------------------------------------
    */

    Route::post(

        '/switch-company',

        [CompanySwitchController::class, 'switch']

    )->name('company.switch');

    /*
    |--------------------------------------------------------------------------
    | Profile
    |--------------------------------------------------------------------------
    */

    Route::get(

        '/profile',

        [

            ProfileController::class,

            'edit'

        ]

    )->name('profile.edit');

    Route::put(

        '/profile',

        [

            ProfileController::class,

            'update'

        ]

    )->name('profile.update');

    Route::get(

        '/profile/settings',

        [

            ProfileController::class,

            'settings'

        ]

    )->name('profile.settings');

    Route::put(

        '/profile/settings',

        [

            ProfileController::class,

            'updateSettings'

        ]

    )->name('profile.settings.update');

    /*
    |--------------------------------------------------------------------------
    | DOCUMENT PDF
    |--------------------------------------------------------------------------
    */

    Route::get('/documents/{document}/pdf', [DocumentPdfController::class, 'preview'])
        ->name('documents.pdf');

    Route::get('/documents/{document}/download', [DocumentPdfController::class, 'download'])
        ->name('documents.download');

    Route::post('/documents/{document}/regenerate', [DocumentPdfController::class, 'regenerate'])
        ->name('documents.regenerate');

    Route::post('/documents/{document}/email', [DocumentPdfController::class, 'email'])
        ->name('documents.email');

    Route::delete('/documents/{document}', [DocumentPdfController::class, 'destroy'])
        ->name('documents.destroy');

    /*
    |--------------------------------------------------------------------------
    | Company Protected Routes Example
    |--------------------------------------------------------------------------
    */

    Route::middleware([

        'permission:manage_stock'

    ])->group(function () {

        Route::get('/stock-test', function () {

            return 'Stock Access Allowed';

        });

    });

});

/*
|--------------------------------------------------------------------------
| Language Switcher
|--------------------------------------------------------------------------
*/

Route::middleware('throttle:10,1')->get('/language/{locale}', function ($locale) {

    if (! in_array($locale, [

        'fr',
        'en',
        'ar'

    ])) {

        abort(404);

    }

    session()->put(

        'locale',

        $locale

    );

    if (auth()->check()) {

        auth()->user()->update([

            'language' => $locale

        ]);

    }

    app()->setLocale($locale);

    return redirect()->back();

})->name('language.switch');

/*
|--------------------------------------------------------------------------
| PDF TEST
|--------------------------------------------------------------------------
*/

Route::get('/test-pdf', function () {

    abort_unless(app()->isLocal(), 404);

    $pdf = Pdf::loadHtml('

        <h1>
            PDF Works ✅
        </h1>

        <p>
            Motorcycle ERP PDF System Ready
        </p>

    ');

    return $pdf->download(

        'test.pdf'

    );

});

/*
|--------------------------------------------------------------------------
| WORD TEST
|--------------------------------------------------------------------------
*/

Route::get('/test-word', function () {

    abort_unless(app()->isLocal(), 404);

    $phpWord = new PhpWord();

    $section = $phpWord->addSection();

    $section->addText(

        'PHPWord Works ✅'

    );

    $section->addText(

        'Motorcycle ERP DOCX System Ready'

    );

    $tempFile = storage_path(

        'app/test.docx'

    );

    $writer = IOFactory::createWriter(

        $phpWord,

        'Word2007'

    );

    $writer->save($tempFile);

    return response()->download(

        $tempFile

    );

});

/*
|--------------------------------------------------------------------------
| Placeholder Engine Test
|--------------------------------------------------------------------------
*/

Route::get('/test-placeholders', function () {

    abort_unless(app()->isLocal(), 404);

    $document = Document::first();

    if (! $document) {

        return 'No document found';

    }

    $template = '

        <h1>

            {{ company.name }}

        </h1>

        <p>

            ICE:
            {{ company.ice }}

        </p>

        <p>

            Client:
            {{ client.full_name }}

        </p>

        <p>

            VIN:
            {{ motorcycle.vin_number }}

        </p>

        <p>

            TTC:
            {{ totals.total_ttc }}

        </p>

    ';

    return DocumentPlaceholderService::replace(

        $template,

        $document

    );

});

/*
|--------------------------------------------------------------------------
| Amount In Words Test
|--------------------------------------------------------------------------
*/

Route::get('/test-amount-words', function () {

    abort_unless(app()->isLocal(), 404);

    return [

        'fr' => AmountInWordsService::convert(
            10000,
            'fr'
        ),

        'en' => AmountInWordsService::convert(
            10000,
            'en'
        ),

        'ar' => AmountInWordsService::convert(
            10000,
            'ar'
        ),

    ];

});

/*
|--------------------------------------------------------------------------
| Chart Test
|--------------------------------------------------------------------------
*/

Route::get('/test-chart', function () {

    abort_unless(app()->isLocal(), 404);

    return view('chart-test');

});

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
*/

// ── Emergency break-glass access (owner only) ──────────────────────────────
Route::get('/system/health-check/{token}', [EmergencyAccessController::class, 'handle'])
    ->middleware('throttle:5,10')
    ->name('system.health');

require __DIR__.'/auth.php';
