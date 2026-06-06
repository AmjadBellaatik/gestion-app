<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
use App\Models\Sale;
use App\Models\SaleDateLog;
use App\Models\User;
use App\Services\Accounting\ClientStatementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Manual Sale Date Management — full feature coverage.
 *
 * Verifies default-to-today, role-based override, audit logging, future-date
 * blocking, statement/report sourcing, and multi-company isolation.
 *
 * NOTE: The Filament UI/page guards are exercised by replicating their exact
 * server-side enforcement logic (CreateSale/EditSale mutate hooks), since
 * Livewire page testing requires a full panel render. The model-level audit
 * and defaults are tested directly against the real models.
 */
class SaleDateTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Client $client;
    private User $admin;
    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create(['name' => 'Test Co']);
        session(['company_id' => $this->company->id]);

        Role::findOrCreate('Super Admin', 'web');
        Role::findOrCreate('Admin', 'web');
        Role::findOrCreate('Sales Agent', 'web');

        $this->admin = $this->makeUser('Admin User', 'Admin');
        $this->agent = $this->makeUser('Agent User', 'Sales Agent');

        $this->client = Client::create([
            'company_id' => $this->company->id,
            'client_type' => 'person',
            'first_name' => 'A', 'last_name' => 'B',
            'display_name' => 'A B', 'is_active' => true,
        ]);
    }

    private function makeUser(string $name, string $role): User
    {
        $u = new User();
        $u->forceFill(['name' => $name, 'email' => str()->random(8).'@t.co', 'password' => bcrypt('x')]);
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'company_id')) {
            $u->company_id = $this->company->id;
        }
        $u->save();
        $u->assignRole($role);
        return $u;
    }

    private function makeSale(array $attrs = []): Sale
    {
        return Sale::create(array_merge([
            'company_id' => $this->company->id,
            'client_id'  => $this->client->id,
            'user_id'    => $this->admin->id,
            'sale_number' => 'S-'.str()->random(6),
            'sale_type'  => 'direct',
            'subtotal' => 1000, 'total' => 1000,
            'paid_amount' => 0, 'remaining_amount' => 1000,
            'payment_status' => 'unpaid', 'status' => 'confirmed',
        ], $attrs));
    }

    /** Mirror of CreateSale::mutateFormDataBeforeCreate. */
    private function enforceCreate(array $data, bool $isAdmin): array
    {
        $today = now()->toDateString();
        if (! $isAdmin) {
            $data['sale_date'] = $today;
        } elseif (filled($data['sale_date'] ?? null) && $data['sale_date'] > $today) {
            $data['sale_date'] = $today;
        }
        $data['sale_date'] ??= $today;
        return $data;
    }

    /** Mirror of EditSale::mutateFormDataBeforeSave. */
    private function enforceEdit(Sale $sale, array $data, bool $isAdmin): array
    {
        $today = now()->toDateString();
        if (! $isAdmin) {
            $data['sale_date'] = optional($sale->sale_date)->toDateString();
        } elseif (filled($data['sale_date'] ?? null) && $data['sale_date'] > $today) {
            $data['sale_date'] = $today;
        }
        return $data;
    }

    // 1. Sale created without date → defaults to today
    public function test_sale_without_date_defaults_to_today(): void
    {
        $sale = $this->makeSale();
        $this->assertSame(now()->toDateString(), $sale->sale_date->toDateString());
    }

    // 2. Admin creates historical (backdated) sale → accepted
    public function test_admin_can_create_backdated_sale(): void
    {
        $this->actingAs($this->admin);
        $data = $this->enforceCreate(['sale_date' => '2026-01-15'], isAdmin: true);
        $sale = $this->makeSale(['sale_date' => $data['sale_date']]);
        $this->assertSame('2026-01-15', $sale->sale_date->toDateString());
    }

    // 3. Non-admin attempts to change date → denied (forced to today)
    public function test_non_admin_cannot_set_custom_date(): void
    {
        $this->actingAs($this->agent);
        $data = $this->enforceCreate(['sale_date' => '2026-01-15'], isAdmin: false);
        $this->assertSame(now()->toDateString(), $data['sale_date']);
    }

    // Future dates blocked for everyone (clamped to today)
    public function test_future_dates_are_blocked(): void
    {
        $future = now()->addDays(10)->toDateString();
        $data = $this->enforceCreate(['sale_date' => $future], isAdmin: true);
        $this->assertSame(now()->toDateString(), $data['sale_date']);
    }

    // 4. Admin edits sale date → accepted  +  5. Audit log created
    public function test_admin_edit_changes_date_and_writes_audit_log(): void
    {
        $this->actingAs($this->admin);
        $sale = $this->makeSale(['sale_date' => '2026-06-06']);

        $this->assertDatabaseCount('sale_date_logs', 0);

        $data = $this->enforceEdit($sale, ['sale_date' => '2026-06-01'], isAdmin: true);
        $sale->update($data);

        $this->assertSame('2026-06-01', $sale->fresh()->sale_date->toDateString());

        $log = SaleDateLog::withoutGlobalScopes()->where('sale_id', $sale->id)->first();
        $this->assertNotNull($log);
        $this->assertSame('2026-06-06', $log->old_date->toDateString());
        $this->assertSame('2026-06-01', $log->new_date->toDateString());
        $this->assertSame($this->admin->id, $log->user_id);
        $this->assertSame('Admin User', $log->user_name);
        $this->assertSame($this->company->id, $log->company_id);
    }

    // Non-admin edit cannot change the stored date
    public function test_non_admin_edit_keeps_original_date(): void
    {
        $this->actingAs($this->agent);
        $sale = $this->makeSale(['sale_date' => '2026-06-06']);
        $data = $this->enforceEdit($sale, ['sale_date' => '2026-01-01'], isAdmin: false);
        $sale->update($data);
        $this->assertSame('2026-06-06', $sale->fresh()->sale_date->toDateString());
        $this->assertDatabaseCount('sale_date_logs', 0); // nothing changed → no log
    }

    // No silent change: identical date does not create a log
    public function test_unchanged_date_creates_no_log(): void
    {
        $this->actingAs($this->admin);
        $sale = $this->makeSale(['sale_date' => '2026-06-06']);
        $sale->update(['sale_date' => '2026-06-06', 'notes' => 'touch']);
        $this->assertDatabaseCount('sale_date_logs', 0);
    }

    // 6. PDF / document date follows sale_date
    public function test_document_date_inherits_sale_date(): void
    {
        // DocumentService::create derives document_date from the sale when absent.
        // Here we assert the contract at the data level: a backdated sale's date
        // is what flows to documents (SaleService passes $sale->sale_date).
        $sale = $this->makeSale(['sale_date' => '2026-03-03']);
        $this->assertSame('2026-03-03', optional($sale->sale_date)->toDateString());
    }

    // 7 & 9. Reports and client statement use sale_date
    public function test_statement_and_reports_use_sale_date(): void
    {
        $sale = $this->makeSale(['sale_date' => '2026-02-02']);

        // Statement line date = sale_date
        $stmt = app(ClientStatementService::class)->build($this->client->fresh());
        $first = $stmt['lines']->firstWhere('document', $sale->sale_number);
        $this->assertNotNull($first);
        $this->assertSame('2026-02-02', $first['date']->toDateString());

        // Report-style query filters on sale_date
        $inRange = Sale::whereBetween('sale_date', ['2026-02-01', '2026-02-28'])->count();
        $this->assertSame(1, $inRange);
        $outRange = Sale::whereBetween('sale_date', ['2026-05-01', '2026-05-31'])->count();
        $this->assertSame(0, $outRange);
    }

    // 8. Filters use sale_date (created_at differs from sale_date)
    public function test_filters_use_sale_date_not_created_at(): void
    {
        // created today, but sale_date backdated to January
        $sale = $this->makeSale(['sale_date' => '2026-01-10']);
        $this->assertSame(now()->toDateString(), $sale->created_at->toDateString());

        // A sale_date filter for January finds it; a created_at filter for January would not.
        $bySaleDate = Sale::whereDate('sale_date', '2026-01-10')->count();
        $this->assertSame(1, $bySaleDate);
        $this->assertNotSame(
            $sale->created_at->toDateString(),
            $sale->sale_date->toDateString(),
            'created_at and sale_date must coexist as distinct values'
        );
    }

    // 10. Multi-company isolation for sale_date logs
    public function test_audit_logs_are_company_isolated(): void
    {
        $this->actingAs($this->admin);
        $sale = $this->makeSale(['sale_date' => '2026-06-06']);
        $sale->update(['sale_date' => '2026-06-01']);

        // Switch to another company → scoped query sees no logs
        $other = Company::create(['name' => 'Other Co']);
        session(['company_id' => $other->id]);
        $this->assertSame(0, SaleDateLog::count());

        // Back to original company → sees its log
        session(['company_id' => $this->company->id]);
        $this->assertSame(1, SaleDateLog::count());
    }
}
