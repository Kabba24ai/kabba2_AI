<?php

namespace Tests\Feature\WebsiteManagement;

use App\Models\Iam\Personnel\User;
use App\Models\WebsiteManagement\WebsitePage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Hero scope: the hero image (and its overlay/readability machinery)
 * belongs to the HOMEPAGE ONLY. Interior pages get a simple title header,
 * and their builders expose no image/overlay controls.
 */
class HeroScopeTest extends TestCase
{
    use RefreshDatabase;

    /** Markup only the homepage image-hero produces. */
    private const HERO_MARKERS = ['id="home-hero"', 'Equipment rental background'];

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

    private function actingAsAdmin(): void
    {
        $this->actingAs(User::create([
            'unique_id' => 'test-admin', 'employee_code' => '01',
            'first_name' => 'Admin', 'last_name' => 'User',
            'email' => 'admin@test.local', 'status' => 'Active',
        ]));
    }

    // ── Public rendering ─────────────────────────────────────────────────────

    public function test_homepage_renders_the_hero(): void
    {
        $html = $this->get($this->frontUrl('/'))->assertOk()->getContent();

        $this->assertStringContainsString('id="home-hero"', $html);
    }

    public function test_faq_does_not_render_hero_markup(): void
    {
        $html = $this->get($this->frontUrl('/faqs'))->assertOk()->getContent();

        foreach (self::HERO_MARKERS as $marker) {
            $this->assertStringNotContainsString($marker, $html);
        }
        $this->assertStringContainsString('Frequently Asked Questions', $html);
    }

    public function test_contact_us_does_not_render_hero_markup(): void
    {
        $html = $this->get($this->frontUrl('/contact-us'))->assertOk()->getContent();

        foreach (self::HERO_MARKERS as $marker) {
            $this->assertStringNotContainsString($marker, $html);
        }
        $this->assertStringNotContainsString('Contact Us background', $html); // old hero <img alt>

        // No header band of any kind — the page opens with the shared contact strip
        $this->assertStringNotContainsString('contact-page-header', $html);
    }

    public function test_checkout_does_not_render_hero_markup(): void
    {
        $response = $this->get($this->frontUrl('/checkout'));

        // Checkout may redirect (empty cart) or render — either way, no hero.
        $this->assertContains($response->getStatusCode(), [200, 302]);
        foreach (self::HERO_MARKERS as $marker) {
            $this->assertStringNotContainsString($marker, (string) $response->getContent());
        }
    }

    // ── Administrative UI ────────────────────────────────────────────────────

    public function test_contact_builder_exposes_no_hero_image_or_overlay_controls(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.website-management.contact-builder.index'))
            ->assertOk()
            ->assertDontSee('Page Header')
            ->assertDontSee('Hero Banner')
            ->assertDontSee('Choose from Library')
            ->assertDontSee('Overlay Settings')
            ->assertDontSee('Text Readability')
            ->assertDontSee('Background Image')
            ->assertDontSee('name="image"', false)
            ->assertDontSee('name="media_id"', false);
    }

    public function test_home_builder_keeps_the_full_hero_editor(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.website-management.home-builder.index'))
            ->assertOk()
            ->assertSee('Hero Section')
            ->assertSee('Choose from Library')
            ->assertSee('Overlay Settings')
            ->assertSee('Text Readability');
    }

    // ── Persistence ─────────────────────────────────────────────────────────

    public function test_homepage_hero_settings_remain_unaffected(): void
    {
        $home = WebsitePage::where('page_key', 'home')->firstOrFail()
            ->sections()->where('section_key', 'hero')->firstOrFail();

        // The home hero still accepts and renders its full configuration
        $this->assertNotNull($home);
        $html = $this->get($this->frontUrl('/'))->assertOk()->getContent();
        $this->assertStringContainsString('id="home-hero"', $html);
    }
}
