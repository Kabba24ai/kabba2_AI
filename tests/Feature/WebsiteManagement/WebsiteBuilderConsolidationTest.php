<?php

namespace Tests\Feature\WebsiteManagement;

use App\Models\WebsiteManagement\WebsitePage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Final Website Builder consolidation: one canonical page per public page,
 * no transitional V2 artifacts, and no dead footer destinations.
 */
class WebsiteBuilderConsolidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\Configurations\SettingSeeder::class);
        $this->seed(\Database\Seeders\WebsiteManagement\HomePageBuilderSeeder::class);
        $this->seed(\Database\Seeders\WebsiteManagement\ContactPageBuilderSeeder::class);
    }

    private function frontUrl(string $path = '/'): string
    {
        return 'http://' . config('app.domains.front') . $path;
    }

    // ── Canonical routes ─────────────────────────────────────────────────────

    public function test_contact_us_canonical_route_serves_the_builder_page(): void
    {
        $this->get($this->frontUrl('/contact-us'))
            ->assertOk()
            ->assertViewIs('front.website_pages.contact_us.index');
    }

    public function test_retired_v2_routes_no_longer_exist(): void
    {
        $this->get($this->frontUrl('/home-v2'))->assertNotFound();
        $this->get($this->frontUrl('/contact-us-v2'))->assertNotFound();
    }

    public function test_homepage_serves_the_builder_page_at_root(): void
    {
        $this->get($this->frontUrl('/'))
            ->assertOk()
            ->assertViewIs('front.home.index');
    }

    // ── Page definitions ─────────────────────────────────────────────────────

    public function test_no_v2_page_keys_or_slugs_remain(): void
    {
        $this->assertDatabaseMissing('website_pages', ['page_key' => 'contact_v2']);
        $this->assertDatabaseMissing('website_pages', ['page_key' => 'home_v2']);
        $this->assertDatabaseMissing('website_pages', ['slug' => 'contact-us-v2']);

        $this->assertNotNull(WebsitePage::where('page_key', 'home')->first());
        $this->assertNotNull(WebsitePage::where('page_key', 'contact')->where('slug', 'contact-us')->first());
    }

    // ── Footer destinations ──────────────────────────────────────────────────

    public function test_footer_contains_no_equipment_rentals_link_or_dead_destination(): void
    {
        $this->assertDatabaseMissing('website_section_items', ['button_url' => '/product-categories']);
        $this->assertDatabaseMissing('website_section_items', ['button_url' => '/home-v2']);
        $this->assertDatabaseMissing('website_section_items', ['button_url' => '/contact-us-v2']);

        $html = $this->get($this->frontUrl('/'))->assertOk()->getContent();
        $footer = substr($html, strpos($html, '<footer'));
        $this->assertStringNotContainsString('Equipment Rentals', $footer);
        $this->assertStringNotContainsString('/product-categories', $footer);
    }

    public function test_contact_page_renders_the_global_footer_and_feature_strip(): void
    {
        $html = $this->get($this->frontUrl('/contact-us'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '<footer'));
        $this->assertSame(1, substr_count($html, 'global-feature-strip'));
    }
}
