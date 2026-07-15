<?php

namespace Tests\Feature\WebsiteManagement;

use App\Models\Configurations\Setting;
use App\Models\Iam\Personnel\User;
use App\Models\WebsiteManagement\WebsitePage;
use App\Services\Website\HomePageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Footer ownership: Website Management → Footer is the ONLY editor of footer
 * content (copyright_text on the footer section). The Branding page no longer
 * exposes or saves all_rights_reserved / powered_by; those settings survive in
 * the DB only as a frozen compatibility fallback for the COPYRIGHT line.
 *
 * The "Powered by" attribution is PLATFORM branding: rendered from
 * config('app.powered_by_name' / 'app.powered_by_url'), never from website
 * data, and not editable through any admin screen or save endpoint.
 */
class FooterOwnershipTest extends TestCase
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

    private function footerSection()
    {
        return WebsitePage::where('page_key', 'home')->firstOrFail()
            ->sections()->where('section_key', 'footer')->firstOrFail();
    }

    private function setFooterContent(array $overrides): void
    {
        $section = $this->footerSection();
        $section->update(['content' => array_merge($section->content ?? [], $overrides)]);
        HomePageService::clearCache();
    }

    private function setBranding(string $name, ?string $value): void
    {
        Setting::updateOrCreate(
            ['setting_name' => $name, 'setting_type' => 'Website Management Branding'],
            ['setting_value' => $value],
        );
    }

    // ── Branding page: footer fields retired ────────────────────────────────

    public function test_branding_page_no_longer_renders_footer_fields(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.website-management.branding.index'))
            ->assertOk()
            ->assertDontSee('All rights reserved')
            ->assertDontSee('Powered By')
            ->assertDontSee('all_rights_reserved', false)
            ->assertDontSee('powered_by', false);
    }

    public function test_branding_save_ignores_retired_footer_values(): void
    {
        $this->actingAsAdmin();

        $this->setBranding('all_rights_reserved', 'Frozen Legacy Co');
        $this->setBranding('powered_by', 'Kabba.ai');

        $this->post(route('admin.website-management.branding.update'), [
            'site_name'           => 'Rent n King',
            'all_rights_reserved' => 'Injected Rights',
            'powered_by'          => 'Injected Power',
        ])->assertRedirect();

        $this->assertSame(
            'Frozen Legacy Co',
            Setting::where('setting_name', 'all_rights_reserved')
                ->where('setting_type', 'Website Management Branding')->value('setting_value')
        );
        $this->assertSame(
            'Kabba.ai',
            Setting::where('setting_name', 'powered_by')
                ->where('setting_type', 'Website Management Branding')->value('setting_value')
        );
    }

    // ── Copyright: Footer Builder owns it, everywhere ────────────────────────

    public function test_every_public_page_renders_builder_copyright_and_platform_attribution(): void
    {
        $this->setFooterContent(['copyright_text' => '© 2026 Footer Builder Owns This.']);

        foreach (['/', '/contact-us', '/faqs', '/privacy-policy'] as $path) {
            $html = $this->get($this->frontUrl($path))->assertOk()->getContent();

            $this->assertStringContainsString('© 2026 Footer Builder Owns This.', $html, "copyright on {$path}");
            $this->assertStringContainsString('Powered by', $html, "attribution label on {$path}");
            $this->assertStringContainsString(config('app.powered_by_name'), $html, "attribution name on {$path}");
            $this->assertSame(
                1,
                substr_count($html, 'Powered by'),
                "exactly one Powered by on {$path}"
            );
        }
    }

    public function test_branding_edits_cannot_affect_footer_output(): void
    {
        $this->setFooterContent(['copyright_text' => '© 2026 Canonical Copyright.']);

        $this->setBranding('all_rights_reserved', 'Legacy Should Not Render');
        $this->setBranding('powered_by', 'Legacy Power Should Not Render');
        HomePageService::clearCache();

        $html = $this->get($this->frontUrl('/'))->assertOk()->getContent();

        $this->assertStringContainsString('© 2026 Canonical Copyright.', $html);
        $this->assertStringNotContainsString('Legacy Should Not Render', $html);
        $this->assertStringNotContainsString('Legacy Power Should Not Render', $html);
    }

    public function test_frozen_legacy_copyright_fallback_renders_when_builder_value_is_blank(): void
    {
        $this->setFooterContent(['copyright_text' => null]);
        $this->setBranding('all_rights_reserved', 'Frozen Fallback Co');
        $this->setBranding('powered_by', 'Frozen Fallback Power');
        HomePageService::clearCache();

        $html = $this->get($this->frontUrl('/'))->assertOk()->getContent();

        // Copyright still honors the frozen legacy fallback…
        $this->assertStringContainsString('Frozen Fallback Co', $html);
        $this->assertStringContainsString('All rights reserved', $html);
        // …but the attribution never reads legacy data
        $this->assertStringNotContainsString('Frozen Fallback Power', $html);
        $this->assertStringContainsString(config('app.powered_by_name'), $html);
    }

    public function test_footer_builder_save_immediately_updates_public_pages(): void
    {
        $this->actingAsAdmin();

        $section = $this->footerSection();
        $this->post(route('admin.website-management.home-builder.section.update', $section->unique_id), [
            'status'  => 'Active',
            'content' => array_merge($section->content ?? [], [
                'copyright_text' => '© 2026 Saved Through The Builder.',
            ]),
        ])->assertRedirect(route('admin.website-management.footer.index'));

        $html = $this->get($this->frontUrl('/'))->assertOk()->getContent();

        $this->assertStringContainsString('© 2026 Saved Through The Builder.', $html);
    }

    // ── Powered-by attribution: locked platform branding ─────────────────────

    public function test_footer_editor_no_longer_renders_a_powered_by_input(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.website-management.footer.index'))
            ->assertOk()
            ->assertSee('Copyright Text')
            ->assertDontSee('Powered By Text')
            ->assertDontSee('content[powered_by_text]', false);
    }

    public function test_injected_powered_by_text_is_not_persisted_by_the_footer_save(): void
    {
        $this->actingAsAdmin();

        $section = $this->footerSection();
        $this->post(route('admin.website-management.home-builder.section.update', $section->unique_id), [
            'status'  => 'Active',
            'content' => [
                'copyright_text'  => '© 2026 Kept.',
                'powered_by_text' => 'Injected Attribution',
            ],
        ])->assertRedirect();

        $content = $this->footerSection()->fresh()->content;
        $this->assertSame('© 2026 Kept.', $content['copyright_text']);
        $this->assertArrayNotHasKey('powered_by_text', $content, 'powered_by_text must not be writable');
    }

    public function test_legacy_stored_powered_by_text_never_renders(): void
    {
        // Historical JSON values are preserved but ignored at render time
        $this->setFooterContent(['powered_by_text' => 'Historical Value Should Not Render']);

        $html = $this->get($this->frontUrl('/'))->assertOk()->getContent();

        $this->assertStringNotContainsString('Historical Value Should Not Render', $html);
        $this->assertStringContainsString(config('app.powered_by_name'), $html);
    }

    public function test_attribution_link_uses_the_configured_kabba_destination(): void
    {
        $html = $this->get($this->frontUrl('/'))->assertOk()->getContent();

        $this->assertStringContainsString(
            'href="' . config('app.powered_by_url') . '" target="_blank" rel="noopener noreferrer"',
            $html
        );
        $this->assertSame('Kabba.ai', config('app.powered_by_name'));
        $this->assertSame('https://kabba.ai', config('app.powered_by_url'));
    }
}
