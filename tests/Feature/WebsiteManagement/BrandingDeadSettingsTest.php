<?php

namespace Tests\Feature\WebsiteManagement;

use App\Models\Configurations\Setting;
use App\Models\Iam\Personnel\User;
use App\Models\WebsiteManagement\WebsitePage;
use App\Services\AIVisibility\AIPageMetadataGenerator;
use App\Services\AIVisibility\SchemaBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Branding dead-settings retirement.
 *
 * The Branding page keeps only company identity (logo, favicon, site name,
 * phone, email, rights/powered-by). Retired: the "Search Engine Optimize"
 * box (home_seo_*) — Home Page Builder → SEO is the canonical homepage SEO
 * editor — and the "Home Page Settings" card (top_text/top_phone/
 * bottom_title/bottom_text). Structured data + AI metadata now read the
 * canonical sources (website_pages meta, site_phone) with the legacy
 * settings preserved in the DB as read-only fallbacks.
 */
class BrandingDeadSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\Configurations\SettingSeeder::class);
        $this->seed(\Database\Seeders\WebsiteManagement\HomePageBuilderSeeder::class);
    }

    private function actingAsAdmin(): void
    {
        $this->actingAs(User::create([
            'unique_id' => 'test-admin', 'employee_code' => '01',
            'first_name' => 'Admin', 'last_name' => 'User',
            'email' => 'admin@test.local', 'status' => 'Active',
        ]));
    }

    private function setBranding(string $name, ?string $value): void
    {
        Setting::updateOrCreate(
            ['setting_name' => $name, 'setting_type' => 'Website Management Branding'],
            ['setting_value' => $value],
        );
    }

    private function branding(string $name): ?string
    {
        return Setting::where('setting_name', $name)
            ->where('setting_type', 'Website Management Branding')
            ->value('setting_value');
    }

    // ── Branding page: retired sections gone, identity intact ───────────────

    public function test_branding_page_no_longer_renders_the_retired_sections(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.website-management.branding.index'))
            ->assertOk()
            ->assertDontSee('Search Engine Optimize')
            ->assertDontSee('Edit SEO meta')
            ->assertDontSee('Home Page Settings')
            ->assertDontSee('Top Text')
            ->assertDontSee('Bottom Text Title');
    }

    public function test_branding_page_keeps_company_identity_controls(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.website-management.branding.index'))
            ->assertOk()
            ->assertSee('Branding Settings')
            ->assertSee('Profile Section')
            ->assertSee('Upload Site Logo')
            ->assertSee('Upload Favicon')
            ->assertSee('site_name', false)
            ->assertSee('site_phone', false)
            ->assertSee('site_email', false);
    }

    public function test_branding_save_cannot_update_retired_fields(): void
    {
        $this->actingAsAdmin();

        foreach ([
            'home_seo_title' => 'Legacy SEO Title',
            'top_text'       => 'Legacy Top Text',
            'top_phone'      => '(111) 111-1111',
            'bottom_text'    => 'Legacy Bottom Text',
        ] as $key => $original) {
            $this->setBranding($key, $original);
        }

        $this->post(route('admin.website-management.branding.update'), [
            'site_name'      => 'Rent n King',
            'home_seo_title' => 'Injected SEO',
            'top_text'       => 'Injected Top',
            'top_phone'      => '(999) 999-9999',
            'bottom_text'    => 'Injected Bottom',
        ])->assertRedirect();

        $this->assertSame('Legacy SEO Title', $this->branding('home_seo_title'));
        $this->assertSame('Legacy Top Text', $this->branding('top_text'));
        $this->assertSame('(111) 111-1111', $this->branding('top_phone'));
        $this->assertSame('Legacy Bottom Text', $this->branding('bottom_text'));
    }

    public function test_identity_fields_still_save(): void
    {
        $this->actingAsAdmin();

        $this->post(route('admin.website-management.branding.update'), [
            'site_name'           => 'Rent n King Updated',
            'site_phone'          => '(615) 555-0001',
            'site_email'          => 'hello@rentnking.com',
            'all_rights_reserved' => 'Rent n King LLC',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('Rent n King Updated', $this->branding('site_name'));
        $this->assertSame('(615) 555-0001', $this->branding('site_phone'));
        $this->assertSame('hello@rentnking.com', $this->branding('site_email'));
        $this->assertSame('Rent n King LLC', $this->branding('all_rights_reserved'));
    }

    // ── AI metadata: canonical homepage SEO source ───────────────────────────

    public function test_ai_home_metadata_uses_the_home_builder_seo_values(): void
    {
        WebsitePage::where('page_key', 'home')->firstOrFail()->update([
            'meta_title'       => 'Canonical Home Title',
            'meta_description' => 'Canonical home description.',
        ]);
        $this->setBranding('home_seo_title', 'Legacy Title Should Lose');

        $record = app(AIPageMetadataGenerator::class)->generateForHome();

        $this->assertNotNull($record);
        $this->assertSame('Canonical Home Title', $record->ai_title);
        $this->assertSame('Canonical home description.', $record->ai_summary);
    }

    public function test_ai_home_metadata_falls_back_to_legacy_settings_when_meta_blank(): void
    {
        WebsitePage::where('page_key', 'home')->firstOrFail()->update([
            'meta_title'       => null,
            'meta_description' => null,
        ]);
        $this->setBranding('home_seo_title', 'Legacy Fallback Title');
        $this->setBranding('home_seo_description', 'Legacy fallback description.');

        $record = app(AIPageMetadataGenerator::class)->generateForHome();

        $this->assertNotNull($record);
        $this->assertSame('Legacy Fallback Title', $record->ai_title);
        $this->assertSame('Legacy fallback description.', $record->ai_summary);
    }

    // ── Structured data: canonical phone source ──────────────────────────────

    public function test_organization_schema_prefers_the_canonical_site_phone(): void
    {
        $this->setBranding('site_phone', '(615) 815-6734');
        $this->setBranding('top_phone', '(999) 000-0000');

        $schema = app(SchemaBuilder::class)->buildOrganizationSchema();

        $this->assertSame('(615) 815-6734', $schema['telephone']);
    }

    public function test_organization_schema_falls_back_to_legacy_top_phone(): void
    {
        $this->setBranding('site_phone', null);
        $this->setBranding('top_phone', '(777) 777-7777');

        $schema = app(SchemaBuilder::class)->buildOrganizationSchema();

        $this->assertSame('(777) 777-7777', $schema['telephone']);
    }

    // ── Regression: public homepage metadata unchanged ───────────────────────

    public function test_public_homepage_metadata_still_comes_from_the_builder(): void
    {
        WebsitePage::where('page_key', 'home')->firstOrFail()->update([
            'meta_title'       => 'Public Home Title',
            'meta_description' => 'Public home description.',
        ]);
        \App\Services\Website\HomePageService::clearCache();

        $html = $this->get('http://' . config('app.domains.front') . '/')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('<title>Public Home Title</title>', $html);
        $this->assertStringContainsString('name="description" content="Public home description."', $html);
    }
}
