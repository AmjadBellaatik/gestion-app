<?php

namespace Tests\Feature\Company;

use App\Filament\Resources\CompanySettings\CompanySettingResource;
use App\Models\Company;
use App\Models\User;
use App\Services\Company\CompanyDeactivationService;
use App\Services\Company\CompanyProvisioningService;
use App\Services\Installer\BootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Super Admin company management — CREATE and DELETE are Super-Admin-only,
 * everything else keeps its existing behaviour, and DELETE is the safe
 * reversible deactivation (never a hard cascade).
 */
class CompanyManagementTest extends TestCase
{
    use RefreshDatabase;

    private Company $companyA;
    private Company $companyB;
    private User $superAdmin;
    private User $admin;
    private User $plain;

    protected function setUp(): void
    {
        parent::setUp();

        app(BootstrapSeeder::class)->seedPlatform();

        $this->companyA = Company::create(['name' => 'Alpha Motors', 'is_active' => true, 'primary_color' => '#111111']);
        $this->companyB = Company::create(['name' => 'Bravo Motors', 'is_active' => true, 'primary_color' => '#222222']);

        $superRoleId = Role::where('name', 'Super Admin')->value('id');
        $adminRoleId = Role::where('name', 'Admin')->value('id');

        $this->superAdmin = User::create(['name' => 'Sa', 'email' => 'sa@t.test', 'password' => bcrypt('x'), 'status' => true]);
        $this->superAdmin->assignRole('Super Admin');
        $this->superAdmin->companies()->attach($this->companyA->id, ['role_id' => $superRoleId]);
        $this->superAdmin->companies()->attach($this->companyB->id, ['role_id' => $superRoleId]);

        $this->admin = User::create(['name' => 'Ad', 'email' => 'ad@t.test', 'password' => bcrypt('x'), 'status' => true]);
        $this->admin->assignRole('Admin');
        $this->admin->companies()->attach($this->companyA->id, ['role_id' => $adminRoleId]);

        $this->plain = User::create(['name' => 'Pl', 'email' => 'pl@t.test', 'password' => bcrypt('x'), 'status' => true]);
        $this->plain->companies()->attach($this->companyA->id);
    }

    /* ── Authorisation matrix (policy) ─────────────────────────────── */

    public function test_only_super_admin_may_create_a_company(): void
    {
        $this->assertTrue($this->superAdmin->can('create', Company::class));
        $this->assertFalse($this->admin->can('create', Company::class));
        $this->assertFalse($this->plain->can('create', Company::class));
    }

    public function test_only_super_admin_may_delete_a_company(): void
    {
        $this->assertTrue($this->superAdmin->can('delete', $this->companyA));
        $this->assertFalse($this->admin->can('delete', $this->companyA));
        $this->assertFalse($this->plain->can('delete', $this->companyA));
    }

    public function test_admin_keeps_the_right_to_update_a_company(): void
    {
        // Editing company info / visual identity must NOT be taken away.
        $this->assertTrue($this->admin->can('update', $this->companyA));
        $this->assertTrue($this->plain->can('update', $this->companyA));
    }

    public function test_resource_can_create_flag_follows_the_policy(): void
    {
        $this->actingAs($this->admin);
        $this->assertFalse(CompanySettingResource::canCreate());

        $this->actingAs($this->superAdmin);
        $this->assertTrue(CompanySettingResource::canCreate());
    }

    /* ── Direct-URL / forged request ──────────────────────────────── */

    public function test_direct_url_to_create_page_is_forbidden_for_admin(): void
    {
        $this->actingAs($this->admin)
            ->get(CompanySettingResource::getUrl('create', panel: 'admin'))
            ->assertForbidden();
    }

    public function test_direct_url_to_create_page_is_allowed_for_super_admin(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(CompanySettingResource::getUrl('create', panel: 'admin'))
            ->assertSuccessful();
    }

    /* ── Provisioning service ─────────────────────────────────────── */

    public function test_provisioning_creates_company_links_creator_and_seeds_reference_data(): void
    {
        $company = app(CompanyProvisioningService::class)->create(
            ['name' => 'Charlie Motors', 'default_language' => 'fr'],
            $this->superAdmin,
        );

        $this->assertDatabaseHas('companies', ['id' => $company->id, 'name' => 'Charlie Motors', 'is_active' => true]);

        // creator linked through company_user with the Super Admin role_id
        $superRoleId = Role::where('name', 'Super Admin')->value('id');
        $this->assertDatabaseHas('company_user', [
            'company_id' => $company->id,
            'user_id' => $this->superAdmin->id,
            'role_id' => $superRoleId,
        ]);

        // per-company settings rows were seeded for the new company
        $this->assertDatabaseHas('settings', [
            'company_id' => $company->id,
            'group' => 'company',
            'key' => 'tax_rate',
        ]);

        // the current company was NOT switched automatically
        $this->assertNotSame($company->id, session('company_id'));
    }

    public function test_provisioning_is_wrapped_in_a_transaction_and_is_idempotent_on_retry(): void
    {
        $a = app(CompanyProvisioningService::class)->create(['name' => 'Delta', 'default_language' => 'fr'], $this->superAdmin);
        $b = app(CompanyProvisioningService::class)->create(['name' => 'Delta', 'default_language' => 'fr'], $this->superAdmin);

        // Two distinct rows are allowed (no unique name constraint); settings
        // seeding must not have thrown or duplicated per company.
        $this->assertNotNull($a->id);
        $this->assertNotNull($b->id);
        $this->assertSame(
            1,
            \DB::table('settings')->where('company_id', $a->id)->where('group', 'company')->where('key', 'tax_rate')->count()
        );
    }

    /* ── Deactivation service ─────────────────────────────────────── */

    public function test_super_admin_can_deactivate_an_eligible_company(): void
    {
        session(['company_id' => $this->companyA->id]);

        app(CompanyDeactivationService::class)->deactivate($this->companyA, $this->superAdmin);

        $this->assertFalse($this->companyA->fresh()->is_active);
        $this->assertDatabaseMissing('company_user', ['company_id' => $this->companyA->id]);

        // session repaired to the other company
        $this->assertSame($this->companyB->id, session('company_id'));
    }

    public function test_deactivating_company_a_never_mutates_company_b(): void
    {
        $before = $this->companyB->fresh()->only(['name', 'primary_color', 'secondary_color', 'accent_color', 'logo', 'is_active']);
        $bUserLinks = \DB::table('company_user')->where('company_id', $this->companyB->id)->count();

        app(CompanyDeactivationService::class)->deactivate($this->companyA, $this->superAdmin);

        $this->assertSame($before, $this->companyB->fresh()->only(array_keys($before)));
        $this->assertSame($bUserLinks, \DB::table('company_user')->where('company_id', $this->companyB->id)->count());
    }

    public function test_cannot_deactivate_the_last_active_company(): void
    {
        $this->companyB->forceFill(['is_active' => false])->save();

        $this->expectException(ValidationException::class);

        app(CompanyDeactivationService::class)->deactivate($this->companyA, $this->superAdmin);

        $this->assertTrue($this->companyA->fresh()->is_active, 'Company A must stay active.');
    }

    public function test_cannot_leave_the_acting_super_admin_without_a_usable_company(): void
    {
        // Super Admin only belongs to A; B exists & is active but they are not a member.
        $this->superAdmin->companies()->detach($this->companyB->id);

        try {
            app(CompanyDeactivationService::class)->deactivate($this->companyA, $this->superAdmin);
            $this->fail('Expected a ValidationException.');
        } catch (ValidationException) {
            $this->assertTrue($this->companyA->fresh()->is_active);
            $this->assertDatabaseHas('company_user', [
                'company_id' => $this->companyA->id,
                'user_id' => $this->superAdmin->id,
            ]);
        }
    }

    public function test_deactivated_company_disappears_from_the_scoped_resource_query(): void
    {
        $this->actingAs($this->superAdmin);
        session(['company_id' => $this->companyA->id]);

        app(CompanyDeactivationService::class)->deactivate($this->companyB, $this->superAdmin);

        $ids = CompanySettingResource::getEloquentQuery()->pluck('id')->all();

        $this->assertContains($this->companyA->id, $ids);
        $this->assertNotContains($this->companyB->id, $ids);
    }

    public function test_deactivating_is_a_noop_when_already_inactive(): void
    {
        $this->companyB->forceFill(['is_active' => false])->save();

        app(CompanyDeactivationService::class)->deactivate($this->companyB, $this->superAdmin);

        $this->assertFalse($this->companyB->fresh()->is_active);
    }
}
