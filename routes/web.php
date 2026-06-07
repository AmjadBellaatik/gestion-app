<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmergencyAccessController;

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
| CSP Violation Report Collector
|--------------------------------------------------------------------------
| Browsers POST violation reports here (no session/CSRF token). CSRF-exempt
| and tightly throttled. Used during Report-Only observation.
*/

Route::post('/csp-report', [\App\Http\Controllers\CspReportController::class, 'store'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class])
    ->middleware('throttle:60,1')
    ->name('csp.report');

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
| Sales Routes Protection
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| PHASE 5 — Legacy Blade CRUD SOFT-DISABLED (superseded by Filament)
|--------------------------------------------------------------------------
| Sales → SaleResource, Users → UserResource, Settings → CompanySetting/Mail
| settings. Commented (not deleted) for a reversible soak period. Uncomment to
| roll back instantly; delete the controllers/views only after soak validation.
*/

// Route::middleware(['auth', 'permission:manage_sales', 'throttle:60,1'])->group(function () {
//     Route::resource('sales', SaleController::class);
// });

// Route::middleware(['auth', 'permission:manage_users', 'throttle:60,1'])->group(function () {
//     Route::resource('users', UserController::class);
// });

// Route::middleware(['auth', 'permission:manage_settings', 'throttle:60,1'])->group(function () {
//     Route::resource('settings', SettingController::class);
// });

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

    // PHASE 5 — legacy Blade reports page disabled (superseded by Filament Reports hub).
    // Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    // KEEP: used by Filament report pages (HasReportPeriod::pdfExportAction).
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

    // PHASE 5 — legacy Blade repairs CRUD disabled (superseded by RepairTicketResource).
    // Route::resource('repairs', RepairController::class);

    /*
    |--------------------------------------------------------------------------
    | Repair Print Routes — KEEP (used by Filament EditRepairTicket)
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
    | PHASE 5 — Legacy Profile SOFT-DISABLED (superseded by Filament Profile page)
    |--------------------------------------------------------------------------
    | Commented for soak; uncomment to roll back.
    */
    // Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    // Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // Route::get('/profile/settings', [ProfileController::class, 'settings'])->name('profile.settings');
    // Route::put('/profile/settings', [ProfileController::class, 'updateSettings'])->name('profile.settings.update');

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
    | ACCOUNTING — Client Account Statement (HTML / PDF / CSV)
    |--------------------------------------------------------------------------
    */

    Route::get('/clients/{client}/statement', [\App\Http\Controllers\ClientStatementController::class, 'show'])
        ->name('clients.statement');

    Route::get('/clients/{client}/statement/pdf', [\App\Http\Controllers\ClientStatementController::class, 'pdf'])
        ->name('clients.statement.pdf');

    Route::get('/clients/{client}/statement/csv', [\App\Http\Controllers\ClientStatementController::class, 'csv'])
        ->name('clients.statement.csv');

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
| Auth Routes
|--------------------------------------------------------------------------
*/

// ── Emergency break-glass access (owner only) ──────────────────────────────
Route::get('/system/health-check/{token}', [EmergencyAccessController::class, 'handle'])
    ->middleware('throttle:5,10')
    ->name('system.health');

/*
|--------------------------------------------------------------------------
| PHASE 5 — Legacy Breeze auth stack SOFT-DISABLED
|--------------------------------------------------------------------------
| Authentication is consolidated on Filament (/admin/login). Guest redirects
| were repointed to Filament in Phase 0 (bootstrap/app.php redirectGuestsTo),
| so disabling these routes is safe. Reversible: uncomment to restore Breeze.
| Delete routes/auth.php + Auth controllers/views only after soak validation.
*/
// require __DIR__.'/auth.php';
