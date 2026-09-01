<?php

namespace Tests\Feature\Company;

use App\Filament\Resources\CompanySettings\Pages\CreateCompany;
use App\Filament\Resources\CompanySettings\Pages\EditCompanySetting;
use App\Models\Company;
use App\Models\User;
use App\Services\Branding\LogoColorExtractor;
use App\Services\Installer\BootstrapSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The Visual Identity section: uploading a logo detects brand colours and
 * fills the existing colour fields immediately (before Save), the values
 * stay manually editable, a normal form change does not re-run detection,
 * and editing one company never touches another.
 */
class CompanyBrandingFormTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();

        app(BootstrapSeeder::class)->seedPlatform();
        Storage::fake('public');

        $this->superAdmin = User::create(['name' => 'Sa', 'email' => 'sa@t.test', 'password' => bcrypt('x'), 'status' => true]);
        $this->superAdmin->assignRole('Super Admin');

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->superAdmin);

        $this->tmpDir = sys_get_temp_dir() . '/branding-form-' . uniqid();
        @mkdir($this->tmpDir);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tmpDir . '/*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($this->tmpDir);

        parent::tearDown();
    }

    private function blueGoldLogo(): UploadedFile
    {
        $w = 240;
        $h = 240;
        $img = imagecreatetruecolor($w, $h);
        imagefilledrectangle($img, 0, 0, $w, $h, imagecolorallocate($img, 255, 255, 255));
        imagefilledrectangle($img, 0, 0, 80, $h, imagecolorallocate($img, 30, 58, 138));   // dark blue
        imagefilledrectangle($img, 80, 0, 120, $h, imagecolorallocate($img, 212, 175, 55)); // gold
        $path = $this->tmpDir . '/logo.png';
        imagepng($img, $path);

        // Illuminate\Http\Testing\File — Livewire converts this to a
        // TemporaryUploadedFile when it lands in the form state.
        return UploadedFile::fake()->createWithContent('logo.png', file_get_contents($path));
    }

    public function test_uploading_a_logo_fills_the_colour_fields_before_save(): void
    {
        $file = $this->blueGoldLogo();
        $expected = (new LogoColorExtractor)->extract($file->getRealPath());
        $this->assertNotNull($expected);

        Livewire::test(CreateCompany::class)
            ->fillForm([
                'name' => 'Palette Co',
                'default_language' => 'fr',
                'logo' => $file,
            ])
            ->assertFormSet([
                'primary_color' => $expected['primary'],
                'secondary_color' => $expected['secondary'],
                'accent_color' => $expected['accent'],
            ])
            ->assertNotified();
    }

    public function test_a_manually_changed_colour_is_preserved_across_further_edits(): void
    {
        $component = Livewire::test(CreateCompany::class)
            ->fillForm([
                'name' => 'Manual Co',
                'default_language' => 'fr',
                'logo' => $this->blueGoldLogo(),
            ])
            // user overrides one detected colour
            ->fillForm(['secondary_color' => '#0a0a0a'])
            ->assertFormSet(['secondary_color' => '#0a0a0a']);

        // a normal, unrelated field change must not re-run detection
        $component
            ->fillForm(['name' => 'Manual Co Renamed'])
            ->assertFormSet(['secondary_color' => '#0a0a0a']);
    }

    public function test_a_malformed_logo_fails_gracefully_and_keeps_defaults(): void
    {
        $broken = UploadedFile::fake()->createWithContent(
            'broken.png',
            "\x89PNG\r\n\x1a\n" . str_repeat('garbage', 40)
        );

        Livewire::test(CreateCompany::class)
            ->fillForm([
                'name' => 'Broken Co',
                'default_language' => 'fr',
                'logo' => $broken,
            ])
            ->assertFormSet([
                'primary_color' => '#f59e0b',
                'secondary_color' => '#111827',
                'accent_color' => '#2563eb',
            ])
            ->assertNotified();
    }

    public function test_editing_one_company_identity_never_mutates_another(): void
    {
        $a = Company::create(['name' => 'Iso A', 'is_active' => true, 'primary_color' => '#aaaaaa', 'secondary_color' => '#bbbbbb', 'accent_color' => '#cccccc']);
        $b = Company::create(['name' => 'Iso B', 'is_active' => true, 'primary_color' => '#123456', 'secondary_color' => '#654321', 'accent_color' => '#abcdef']);
        $this->superAdmin->companies()->attach([$a->id, $b->id]);
        session(['company_id' => $a->id]);

        $bBefore = $b->only(['primary_color', 'secondary_color', 'accent_color', 'logo']);

        Livewire::test(EditCompanySetting::class, ['record' => $a->getRouteKey()])
            ->fillForm([
                'primary_color' => '#ff0000',
                'secondary_color' => '#00ff00',
                'accent_color' => '#0000ff',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('#ff0000', $a->fresh()->primary_color);
        $this->assertSame($bBefore, $b->fresh()->only(array_keys($bBefore)));
    }
}
