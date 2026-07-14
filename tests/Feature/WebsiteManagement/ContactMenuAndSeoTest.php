<?php

namespace Tests\Feature\WebsiteManagement;

use App\Models\Global\Media;
use App\Models\Iam\Personnel\User;
use App\Models\WebsiteManagement\WebsitePage;
use App\Models\WebsiteManagement\WebsiteThemeSetting;
use App\Services\Website\ContactPageService;
use App\Services\Website\HomePageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Contact Us menu consolidation + SEO/OG fallback behavior.
 *
 * One admin entry (the builder, labeled "Contact Us"); the legacy settings
 * screen is retired. OG fields are optional: blank fields inherit the Meta
 * values at render time, og:image falls back to the homepage hero (home) or
 * the site's default social image (other pages) — values are never copied
 * into the OG columns.
 */
class ContactMenuAndSeoTest extends TestCase
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

    private function actingAsAdmin(): void
    {
        $this->actingAs(User::create([
            'unique_id' => 'test-admin', 'employee_code' => '01',
            'first_name' => 'Admin', 'last_name' => 'User',
            'email' => 'admin@test.local', 'status' => 'Active',
        ]));
    }

    private function homePage(): WebsitePage
    {
        return WebsitePage::where('page_key', 'home')->firstOrFail();
    }

    private function contactPage(): WebsitePage
    {
        return WebsitePage::where('page_key', 'contact')->firstOrFail();
    }

    private function makeMedia(string $name): Media
    {
        return Media::create([
            'original_file_name' => $name,
            'asset_type'  => 'Public Asset',
            'folder_name' => 'website_builder',
            'file_name'   => $name,
            'file_extension' => 'jpg',
            'file_type'   => 'image',
            'mime_type'   => 'image/jpeg',
            'file_size'   => 1000,
            'is_used'     => 'Yes',
        ]);
    }

    // ── Menu consolidation ───────────────────────────────────────────────────

    public function test_legacy_contact_us_admin_routes_are_retired(): void
    {
        $this->assertFalse(Route::has('admin.website-management.contact-us.index'));
        $this->assertFalse(Route::has('admin.website-management.contact-us.update'));

        $this->actingAsAdmin();
        $this->get('http://' . config('app.domains.admin') . '/website-management/contact-us')
            ->assertNotFound();
    }

    public function test_sidebar_shows_single_contact_us_entry_opening_the_builder(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('admin.website-management.contact-builder.index'))->assertOk();

        // Canonical label, no legacy naming, no legacy link
        $response->assertSee('Contact Us')
            ->assertDontSee('Contact Page Builder')
            ->assertDontSee('website-management/contact-us"', false);
    }

    public function test_consolidated_builder_keeps_only_approved_sections(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.website-management.contact-builder.index'))
            ->assertOk()
            ->assertSee('Show Shared Contact Strip')
            ->assertSee('Store Display Order')
            ->assertSee('SEO Settings')
            ->assertDontSee('Hero Banner')
            ->assertDontSee('Page Header');
    }

    // ── OG fallbacks: title + description ────────────────────────────────────

    public function test_blank_og_title_and_description_fall_back_to_meta_values(): void
    {
        $this->homePage()->update([
            'meta_title'       => 'Meta Title Home',
            'meta_description' => 'Meta description for home.',
            'og_title'         => null,
            'og_description'   => null,
        ]);
        HomePageService::clearCache();

        $html = $this->get($this->frontUrl('/'))->assertOk()->getContent();

        $this->assertStringContainsString('<meta property="og:title" content="Meta Title Home">', $html);
        $this->assertStringContainsString('<meta property="og:description" content="Meta description for home.">', $html);
        $this->assertStringContainsString('<meta name="twitter:title" content="Meta Title Home">', $html);
    }

    public function test_custom_og_title_and_description_override_meta_values(): void
    {
        $this->contactPage()->update([
            'meta_title'       => 'Contact Meta Title',
            'meta_description' => 'Contact meta description.',
            'og_title'         => 'Custom OG Contact',
            'og_description'   => 'Custom OG description.',
        ]);
        ContactPageService::clearCache();

        $html = $this->get($this->frontUrl('/contact-us'))->assertOk()->getContent();

        $this->assertStringContainsString('<meta property="og:title" content="Custom OG Contact">', $html);
        $this->assertStringContainsString('<meta property="og:description" content="Custom OG description.">', $html);
        $this->assertStringNotContainsString('<meta property="og:title" content="Contact Meta Title">', $html);
    }

    // ── OG image fallbacks ───────────────────────────────────────────────────

    public function test_blank_homepage_og_image_uses_the_hero_image(): void
    {
        $hero  = $this->homePage()->sections()->where('section_key', 'hero')->firstOrFail();
        $media = $this->makeMedia('hero-banner.jpg');
        $hero->update(['image' => $media->id]);
        $this->homePage()->update(['og_image' => null]);
        HomePageService::clearCache();

        $html = $this->get($this->frontUrl('/'))->assertOk()->getContent();

        $this->assertStringContainsString('<meta property="og:image" content="' . $media->url . '">', $html);
    }

    public function test_custom_homepage_og_image_overrides_the_hero(): void
    {
        $hero    = $this->homePage()->sections()->where('section_key', 'hero')->firstOrFail();
        $hero->update(['image' => $this->makeMedia('hero.jpg')->id]);
        $ogMedia = $this->makeMedia('custom-og.jpg');
        $this->homePage()->update(['og_image' => $ogMedia->id]);
        HomePageService::clearCache();

        $html = $this->get($this->frontUrl('/'))->assertOk()->getContent();

        $this->assertStringContainsString('<meta property="og:image" content="' . $ogMedia->url . '">', $html);
    }

    public function test_blank_contact_og_image_uses_the_default_social_image(): void
    {
        $default = $this->makeMedia('default-social.jpg');
        WebsiteThemeSetting::create(['key' => 'og_image_default', 'value' => $default->id, 'group' => 'media']);
        app(\App\Services\Website\ThemeService::class)->clearCache();

        $this->contactPage()->update(['og_image' => null]);
        ContactPageService::clearCache();

        $html = $this->get($this->frontUrl('/contact-us'))->assertOk()->getContent();

        $this->assertStringContainsString('<meta property="og:image" content="' . $default->url . '">', $html);
    }

    public function test_no_empty_or_broken_og_image_tag_renders(): void
    {
        foreach (['/', '/contact-us'] as $path) {
            $html = $this->get($this->frontUrl($path))->assertOk()->getContent();
            $this->assertStringNotContainsString('og:image" content=""', $html, "empty og:image on {$path}");
        }
    }

    // ── Canonical URLs ───────────────────────────────────────────────────────

    public function test_canonical_urls_use_current_routes_and_fall_back_to_page_url(): void
    {
        $home = $this->get($this->frontUrl('/'))->assertOk()->getContent();
        $contact = $this->get($this->frontUrl('/contact-us'))->assertOk()->getContent();

        $this->assertStringNotContainsString('home-v2', $home);
        $this->assertStringNotContainsString('contact-us-v2', $contact);

        // Empty canonical falls back to the current page URL (layout default)
        $this->assertMatchesRegularExpression('#<link rel="canonical" href="[^"]*/contact-us"#', $contact);
    }

    public function test_v2_canonical_urls_are_rejected_on_save(): void
    {
        $this->actingAsAdmin();

        $this->from(route('admin.website-management.contact-builder.index'))
            ->post(route('admin.website-management.contact-builder.update'), [
                'canonical_url' => 'https://rentnking.com/contact-us-v2',
            ])
            ->assertSessionHasErrors('canonical_url');
    }

    // ── Backward compatibility ───────────────────────────────────────────────

    public function test_saving_meta_values_does_not_copy_them_into_blank_og_columns(): void
    {
        $this->actingAsAdmin();

        $this->post(route('admin.website-management.home-builder.update'), [
            'meta_title'       => 'Fresh Meta Title',
            'meta_description' => 'Fresh meta description.',
            'og_title'         => '',
            'og_description'   => '',
        ])->assertRedirect();

        $page = $this->homePage()->fresh();
        $this->assertSame('Fresh Meta Title', $page->meta_title);
        $this->assertTrue(blank($page->og_title), 'OG title must stay blank so Meta edits flow through');
        $this->assertTrue(blank($page->og_description));
    }

    public function test_existing_custom_og_values_survive_a_save(): void
    {
        $this->actingAsAdmin();

        $this->post(route('admin.website-management.home-builder.update'), [
            'meta_title' => 'Meta A',
            'og_title'   => 'Custom OG Kept',
        ])->assertRedirect();

        $this->post(route('admin.website-management.home-builder.update'), [
            'meta_title' => 'Meta B',
            'og_title'   => 'Custom OG Kept',
        ])->assertRedirect();

        $page = $this->homePage()->fresh();
        $this->assertSame('Meta B', $page->meta_title);
        $this->assertSame('Custom OG Kept', $page->og_title);
    }

    // ── Regression ───────────────────────────────────────────────────────────

    public function test_strip_footer_and_feature_strip_unchanged(): void
    {
        foreach (['/', '/contact-us'] as $path) {
            $html = $this->get($this->frontUrl($path))->assertOk()->getContent();
            $this->assertSame(1, substr_count($html, 'id="contact-strip"'), "strip on {$path}");
            $this->assertSame(1, substr_count($html, 'global-feature-strip'), "feature strip on {$path}");
            $this->assertSame(1, substr_count($html, '<footer'), "footer on {$path}");
        }

        $home = $this->get($this->frontUrl('/'))->assertOk()->getContent();
        $this->assertStringContainsString('id="home-hero"', $home);
    }
}
