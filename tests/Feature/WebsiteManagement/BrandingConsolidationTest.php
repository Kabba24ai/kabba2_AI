<?php

namespace Tests\Feature\WebsiteManagement;

use App\Models\Configurations\Setting;
use App\Models\Global\Media;
use App\Models\Iam\Personnel\User;
use App\Services\Website\HomePageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Branding consolidation: Website Management → Branding is the ONLY editor
 * for the site logo and favicon. The Home Page Builder Branding tab is
 * retired, and the front (home/contact/SEO fallback) renders the canonical
 * site_logo/site_favicon — the legacy hp_builder_* slots survive only as a
 * read fallback for unmigrated data.
 */
class BrandingConsolidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\Configurations\SettingSeeder::class);
        $this->seed(\Database\Seeders\WebsiteManagement\HomePageBuilderSeeder::class);
        $this->seed(\Database\Seeders\WebsiteManagement\ContactPageBuilderSeeder::class);
    }

    private function actingAsAdmin(): void
    {
        $this->actingAs(User::create([
            'unique_id' => 'test-admin', 'employee_code' => '01',
            'first_name' => 'Admin', 'last_name' => 'User',
            'email' => 'admin@test.local', 'status' => 'Active',
        ]));
    }

    private function frontUrl(string $path = '/'): string
    {
        return 'http://' . config('app.domains.front') . $path;
    }

    private function setting(string $name): ?string
    {
        return Setting::where('setting_name', $name)
            ->where('setting_type', 'Website Management Branding')
            ->value('setting_value');
    }

    private function makeMedia(string $name): Media
    {
        return Media::create([
            'original_file_name' => $name,
            'asset_type'  => 'Public Asset',
            'folder_name' => 'branding',
            'file_name'   => $name,
            'file_extension' => 'png',
            'file_type'   => 'image',
            'mime_type'   => 'image/png',
            'file_size'   => 1000,
            'is_used'     => 'Yes',
        ]);
    }

    private function setBrandingMedia(string $settingName, Media $media): void
    {
        Setting::updateOrCreate(
            ['setting_name' => $settingName, 'setting_type' => 'Website Management Branding'],
            ['setting_value' => $media->id],
        );
    }

    // ── Home Page Builder: Branding tab retired ─────────────────────────────

    public function test_home_builder_no_longer_shows_a_branding_tab(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.website-management.home-builder.index'))
            ->assertOk()
            ->assertDontSee('Save Branding')
            ->assertDontSee('hp_builder_logo')
            ->assertDontSee('hp_builder_favicon');
    }

    public function test_home_builder_keeps_its_approved_tabs(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.website-management.home-builder.index'))
            ->assertOk()
            ->assertSee('Hero Banner')
            ->assertSee('Contact Strip')
            ->assertSee('Featured Rentals')
            ->assertSee('SEO Settings');
    }

    public function test_branding_save_ignores_legacy_hp_builder_uploads(): void
    {
        $this->actingAsAdmin();
        Storage::fake('public_asset');

        $legacy = $this->makeMedia('legacy-logo.png');
        $this->setBrandingMedia('hp_builder_logo', $legacy);

        $this->post(route('admin.website-management.branding.update'), [
            'site_name'       => 'Rent n King',
            'hp_builder_logo' => UploadedFile::fake()->image('sneaky.png', 64, 64),
        ])->assertRedirect();

        $this->assertSame(
            (string) $legacy->id,
            $this->setting('hp_builder_logo'),
            'The retired hp_builder_logo slot must not be writable through the Branding save'
        );
    }

    // ── Branding page: canonical editor still fully functional ──────────────

    public function test_site_logo_upload_still_functions(): void
    {
        $this->actingAsAdmin();

        $before = $this->setting('site_logo');

        $this->post(route('admin.website-management.branding.update'), [
            'site_name' => 'Rent n King',
            'site_logo' => UploadedFile::fake()->image('new-logo.png', 64, 64),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $after = $this->setting('site_logo');
        $this->assertNotSame($before, $after, 'site_logo setting should point at the new media');
        $this->assertNotNull(Media::find($after), 'uploaded logo must exist in the media library');
    }

    public function test_site_favicon_upload_still_functions(): void
    {
        $this->actingAsAdmin();

        $before = $this->setting('site_favicon');

        $this->post(route('admin.website-management.branding.update'), [
            'site_name'    => 'Rent n King',
            'site_favicon' => UploadedFile::fake()->image('new-favicon.png', 64, 64),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $after = $this->setting('site_favicon');
        $this->assertNotSame($before, $after);
        $this->assertNotNull(Media::find($after));
    }

    // ── Public site renders the canonical assets ────────────────────────────

    public function test_homepage_renders_the_canonical_branding_logo(): void
    {
        $logo = $this->makeMedia('canonical-logo.png');
        $this->setBrandingMedia('site_logo', $logo);
        HomePageService::clearCache();

        $html = $this->get($this->frontUrl('/'))->assertOk()->getContent();

        $this->assertStringContainsString($logo->url, $html);
    }

    public function test_contact_page_renders_the_canonical_branding_logo(): void
    {
        $logo = $this->makeMedia('canonical-logo.png');
        $this->setBrandingMedia('site_logo', $logo);

        $html = $this->get($this->frontUrl('/contact-us'))->assertOk()->getContent();

        $this->assertStringContainsString($logo->url, $html);
    }

    public function test_legacy_hp_builder_logo_still_renders_when_canonical_is_unset(): void
    {
        Setting::where('setting_name', 'site_logo')
            ->where('setting_type', 'Website Management Branding')
            ->update(['setting_value' => null]);

        $legacy = $this->makeMedia('legacy-only-logo.png');
        $this->setBrandingMedia('hp_builder_logo', $legacy);
        HomePageService::clearCache();

        $html = $this->get($this->frontUrl('/'))->assertOk()->getContent();

        $this->assertStringContainsString($legacy->url, $html);
    }

    public function test_og_image_fallback_prefers_the_canonical_logo(): void
    {
        // Contact page with no OG image and no theme default → falls back to
        // the canonical branding logo (not the legacy hp_builder slot)
        $canonical = $this->makeMedia('canonical-logo.png');
        $legacy    = $this->makeMedia('legacy-logo.png');
        $this->setBrandingMedia('site_logo', $canonical);
        $this->setBrandingMedia('hp_builder_logo', $legacy);

        $html = $this->get($this->frontUrl('/contact-us'))->assertOk()->getContent();

        $this->assertStringContainsString(
            '<meta property="og:image" content="' . $canonical->url . '">',
            $html
        );
    }
}
