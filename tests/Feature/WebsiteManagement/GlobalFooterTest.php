<?php

namespace Tests\Feature\WebsiteManagement;

use App\Models\Iam\Personnel\User;
use App\Models\WebsiteManagement\WebsitePage;
use App\Models\WebsiteManagement\WebsitePageSection;
use App\Services\Website\HomePageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * The feature strip and footer are GLOBAL site components: stored once
 * (on the `home` website page, section_keys `feature_strip` / `footer`),
 * edited once (Website Management → Footer), and rendered by the shared
 * front layout on every public page.
 */
class GlobalFooterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\Configurations\SettingSeeder::class);
        $this->seed(\Database\Seeders\WebsiteManagement\HomePageBuilderSeeder::class);
    }

    private function frontUrl(string $path = '/'): string
    {
        return 'http://' . config('app.domains.front') . $path;
    }

    private function actingAsAdmin(): void
    {
        $this->actingAs(User::create([
            'unique_id' => 'test-admin', 'employee_code' => '01',
            'first_name' => 'Admin', 'last_name' => 'User',
            'email' => 'admin@test.local', 'status' => 'Active',
        ]));
    }

    private function footerSection(): WebsitePageSection
    {
        return WebsitePage::where('page_key', 'home')->firstOrFail()
            ->sections()->where('section_key', 'footer')->firstOrFail();
    }

    // ── Public rendering ─────────────────────────────────────────────────────

    public function test_public_pages_render_exactly_one_global_footer_and_feature_strip(): void
    {
        $paths = ['/', '/faqs', '/privacy-policy', '/terms-and-conditions', '/login', '/register'];

        foreach ($paths as $path) {
            $response = $this->get($this->frontUrl($path));
            $response->assertOk();

            $html = $response->getContent();

            $this->assertSame(1, substr_count($html, '<footer'), "Expected exactly one <footer> on {$path}");
            $this->assertSame(1, substr_count($html, 'global-feature-strip'), "Expected exactly one feature strip on {$path}");

            // Baseline content from the single global source
            $this->assertStringContainsString('Quick Links', $html, "Footer quick links missing on {$path}");
            $this->assertStringContainsString('Well Maintained Equipment', $html, "Feature strip item missing on {$path}");
        }
    }

    public function test_footer_quick_links_use_canonical_home_route_not_legacy_alias(): void
    {
        // The 2026_07_13 migration rewrites any stored '/home-v2' link to '/'
        $this->assertDatabaseMissing('website_section_items', ['button_url' => '/home-v2']);
    }

    public function test_inactive_feature_strip_hides_globally(): void
    {
        WebsitePage::where('page_key', 'home')->firstOrFail()
            ->sections()->where('section_key', 'feature_strip')
            ->update(['status' => 'Inactive']);
        HomePageService::clearCache();

        foreach (['/', '/faqs'] as $path) {
            $html = $this->get($this->frontUrl($path))->assertOk()->getContent();
            $this->assertSame(0, substr_count($html, 'global-feature-strip'), "Inactive strip must not render on {$path}");
            $this->assertSame(1, substr_count($html, '<footer'), "Footer must still render on {$path}");
        }
    }

    // ── Administrative UI ────────────────────────────────────────────────────

    public function test_footer_screen_hosts_the_global_footer_and_feature_strip_editors(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('admin.website-management.footer.index'));
        $response->assertOk()
            ->assertSee('Footer Content')
            ->assertSee('Feature Strip')
            // Editor markup from the shared builder partials
            ->assertSee('sortable-footer-quick', false)
            ->assertSee('sortable-feature-strip', false);
    }

    public function test_home_page_builder_no_longer_exposes_footer_or_feature_strip_editors(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('admin.website-management.home-builder.index'));
        $response->assertOk()
            ->assertDontSee('sortable-footer-quick', false)
            ->assertDontSee('sortable-feature-strip', false)
            // Pointer to the global editor
            ->assertSee('Website Management → Footer');
    }

    // ── Persistence + cache ─────────────────────────────────────────────────

    public function test_saving_the_global_footer_persists_and_clears_the_public_cache(): void
    {
        $this->actingAsAdmin();
        $section = $this->footerSection();

        Cache::put(HomePageService::CACHE_KEY, 'sentinel', 600);

        $response = $this->post(
            route('admin.website-management.home-builder.section.update', $section->unique_id),
            [
                'status'  => 'Active',
                'content' => [
                    'copyright_text'  => '© 2026 Test Co. All rights reserved.',
                    'powered_by_text' => 'Kabba.ai',
                ],
            ]
        );

        // Footer edits return to the global editor, not the Home Page Builder
        $response->assertRedirect(route('admin.website-management.footer.index'));

        $this->assertFalse(Cache::has(HomePageService::CACHE_KEY), 'Saving the footer must clear the public cache');

        $section->refresh();
        $this->assertSame('© 2026 Test Co. All rights reserved.', $section->content['copyright_text']);

        // And the public site renders the new value immediately
        $this->get($this->frontUrl('/'))
            ->assertOk()
            ->assertSee('© 2026 Test Co. All rights reserved.');
    }

    public function test_non_global_section_saves_still_return_to_the_home_builder(): void
    {
        $this->actingAsAdmin();

        $hero = WebsitePage::where('page_key', 'home')->firstOrFail()
            ->sections()->where('section_key', 'hero')->firstOrFail();

        $this->post(
            route('admin.website-management.home-builder.section.update', $hero->unique_id),
            ['title' => 'Updated Hero Title', 'status' => 'Active']
        )->assertRedirect(route('admin.website-management.home-builder.index', ['tab' => 'hero']));
    }
}
