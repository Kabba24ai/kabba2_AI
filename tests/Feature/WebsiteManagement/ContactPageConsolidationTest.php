<?php

namespace Tests\Feature\WebsiteManagement;

use App\Models\Iam\Personnel\User;
use App\Models\Stores\Store;
use App\Models\WebsiteManagement\WebsitePage;
use App\Models\WebsiteManagement\WebsitePageSection;
use App\Services\Website\ContactPageService;
use App\Services\Website\HomePageService;
use App\Services\Website\PageRevisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Contact page simplification:
 *  - no page header / hero band at all
 *  - ONE shared contact strip (content owned by Home Page Builder;
 *    the contact page owns only its show/hide state)
 *  - store display order owned by the Contact Page Builder,
 *    independent of operational Store Management
 */
class ContactPageConsolidationTest extends TestCase
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

    private function contactSection(string $key): WebsitePageSection
    {
        return WebsitePage::where('page_key', 'contact')->firstOrFail()
            ->sections()->where('section_key', $key)->firstOrFail();
    }

    private function makeStore(string $name, string $primary = 'No', string $status = 'Active'): Store
    {
        return Store::create([
            'store_name' => $name,
            'phone'      => '(615) 555-0100',
            'address'    => '123 Main St',
            'city'       => 'Nashville',
            'zip_code'   => '37201',
            'is_primary' => $primary,
            'status'     => $status,
        ]);
    }

    // ── 1. Page header is gone ───────────────────────────────────────────────

    public function test_contact_page_renders_no_header_or_hero_markup(): void
    {
        $html = $this->get($this->frontUrl('/contact-us'))->assertOk()->getContent();

        $this->assertStringNotContainsString('contact-page-header', $html);
        $this->assertStringNotContainsString('id="home-hero"', $html);
        $this->assertStringNotContainsString('Contact Us background', $html);
    }

    public function test_contact_builder_has_no_page_header_tab(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.website-management.contact-builder.index'))
            ->assertOk()
            ->assertDontSee('Page Header')
            ->assertDontSee('Hero Banner')
            ->assertSee('Show Shared Contact Strip');
    }

    // ── 2+6. One shared contact strip ────────────────────────────────────────

    public function test_homepage_and_contact_page_render_the_same_shared_strip(): void
    {
        $home    = $this->get($this->frontUrl('/'))->assertOk()->getContent();
        $contact = $this->get($this->frontUrl('/contact-us'))->assertOk()->getContent();

        // Same shared component id on both pages
        $this->assertSame(1, substr_count($home, 'id="contact-strip"'));
        $this->assertSame(1, substr_count($contact, 'id="contact-strip"'));

        // Same global content (home builder's contact_strip section title)
        $stripTitle = HomePageService::CACHE_KEY ? app(HomePageService::class)->getData()->contactStrip->title : null;
        $this->assertStringContainsString($stripTitle, $home);
        $this->assertStringContainsString($stripTitle, $contact);
    }

    public function test_contact_strip_hides_on_contact_page_when_inactive(): void
    {
        $this->contactSection('contact_strip')->update(['status' => 'Inactive']);
        ContactPageService::clearCache();

        $html = $this->get($this->frontUrl('/contact-us'))->assertOk()->getContent();
        $this->assertSame(0, substr_count($html, 'id="contact-strip"'));

        // Homepage strip is unaffected by the contact page's toggle
        $home = $this->get($this->frontUrl('/'))->assertOk()->getContent();
        $this->assertSame(1, substr_count($home, 'id="contact-strip"'));
    }

    public function test_homepage_strip_edits_appear_on_the_contact_page(): void
    {
        $this->actingAsAdmin();

        $homeStrip = WebsitePage::where('page_key', 'home')->firstOrFail()
            ->sections()->where('section_key', 'contact_strip')->firstOrFail();

        $this->post(
            route('admin.website-management.home-builder.section.update', $homeStrip->unique_id),
            ['title' => 'CALL OUR NEW GLOBAL TITLE', 'status' => 'Active']
        )->assertRedirect();

        $this->get($this->frontUrl('/contact-us'))
            ->assertOk()
            ->assertSee('CALL OUR NEW GLOBAL TITLE');
    }

    public function test_contact_builder_cannot_edit_shared_strip_content(): void
    {
        $this->actingAsAdmin();
        $section = $this->contactSection('contact_strip');
        $originalTitle = $section->title;

        $this->post(
            route('admin.website-management.contact-builder.section.update', $section->unique_id),
            ['status' => 'Inactive', 'title' => 'HIJACKED TITLE']
        )->assertRedirect();

        $section->refresh();
        $this->assertSame('Inactive', $section->status);
        $this->assertSame($originalTitle, $section->title, 'Contact builder must not edit strip content');
    }

    public function test_contact_page_revision_restore_does_not_touch_shared_strip_data(): void
    {
        $contactPage = WebsitePage::where('page_key', 'contact')->firstOrFail();
        $homeStrip   = WebsitePage::where('page_key', 'home')->firstOrFail()
            ->sections()->where('section_key', 'contact_strip')->firstOrFail();

        $revisionService = app(PageRevisionService::class);
        $revision = $revisionService->createRevision($contactPage, 'test snapshot');

        // Global strip content changes AFTER the contact revision was taken
        $homeStrip->update(['title' => 'TITLE AFTER SNAPSHOT']);
        HomePageService::clearCache();

        $revisionService->restoreRevision($revision->fresh());
        HomePageService::clearCache();
        ContactPageService::clearCache();

        $this->assertSame('TITLE AFTER SNAPSHOT', $homeStrip->fresh()->title,
            'Restoring a contact revision must not overwrite the shared strip');

        $this->get($this->frontUrl('/contact-us'))
            ->assertOk()
            ->assertSee('TITLE AFTER SNAPSHOT');
    }

    // ── 5. Store display order ───────────────────────────────────────────────

    public function test_store_order_persists_and_public_page_follows_it(): void
    {
        $this->actingAsAdmin();
        $a = $this->makeStore('Bon Aqua', 'Yes');
        $b = $this->makeStore('Waverly');
        $c = $this->makeStore('Dickson');

        // Primary is NOT forced to Position 1 — admin puts it last
        $this->postJson(route('admin.website-management.contact-builder.store-order.update'), [
            'store_ids' => [$c->id, $b->id, $a->id],
        ])->assertOk()->assertJson(['success' => true]);

        $html = $this->get($this->frontUrl('/contact-us'))->assertOk()->getContent();

        $posC = strpos($html, "data-store-card=\"{$c->id}\"");
        $posB = strpos($html, "data-store-card=\"{$b->id}\"");
        $posA = strpos($html, "data-store-card=\"{$a->id}\"");
        $this->assertTrue($posC < $posB && $posB < $posA, 'Public page must follow the saved order');
    }

    public function test_reordering_does_not_affect_operational_store_management(): void
    {
        $this->actingAsAdmin();
        $a = $this->makeStore('Bon Aqua', 'Yes');
        $b = $this->makeStore('Waverly');

        $this->postJson(route('admin.website-management.contact-builder.store-order.update'), [
            'store_ids' => [$b->id, $a->id],
        ])->assertOk();

        $this->assertSame('Yes', $a->fresh()->is_primary);
        $this->assertSame('No', $b->fresh()->is_primary);
        $this->assertSame('Active', $a->fresh()->status);
    }

    public function test_new_stores_append_and_inactive_stores_compact_without_gaps(): void
    {
        $this->actingAsAdmin();
        $a = $this->makeStore('Bon Aqua', 'Yes');
        $b = $this->makeStore('Waverly');

        $this->postJson(route('admin.website-management.contact-builder.store-order.update'), [
            'store_ids' => [$b->id, $a->id],
        ])->assertOk();

        // A new store appears at the end without any reordering
        $c = $this->makeStore('Future Store');
        ContactPageService::clearCache();

        $html = $this->get($this->frontUrl('/contact-us'))->assertOk()->getContent();
        $this->assertTrue(
            strpos($html, "data-store-card=\"{$b->id}\"") < strpos($html, "data-store-card=\"{$c->id}\""),
            'New stores must append to the end'
        );

        // Deactivating an ordered store removes it entirely — no blank position
        $b->update(['status' => 'Inactive']);
        ContactPageService::clearCache();

        $html = $this->get($this->frontUrl('/contact-us'))->assertOk()->getContent();
        $this->assertStringNotContainsString("data-store-card=\"{$b->id}\"", $html);
        $this->assertStringContainsString("data-store-card=\"{$a->id}\"", $html);
        $this->assertStringContainsString("data-store-card=\"{$c->id}\"", $html);
    }

    public function test_primary_store_defaults_first_only_when_no_order_saved(): void
    {
        $this->makeStore('Alpha');
        $primary = $this->makeStore('Primary Store', 'Yes');

        $html = $this->get($this->frontUrl('/contact-us'))->assertOk()->getContent();

        $first = strpos($html, "data-store-card=\"{$primary->id}\"");
        $other = strpos($html, 'data-store-card=');
        $this->assertSame($first, $other, 'Primary store defaults to Position 1 with no saved order');
    }

    public function test_admin_locations_tab_lists_stores_with_positions_and_primary_badge(): void
    {
        $this->actingAsAdmin();
        $this->makeStore('Bon Aqua', 'Yes');
        $this->makeStore('Waverly');

        $this->get(route('admin.website-management.contact-builder.index'))
            ->assertOk()
            ->assertSee('Store Display Order')
            ->assertSee('Drag stores to control their display order')
            ->assertSee('Bon Aqua')
            ->assertSee('Waverly')
            ->assertSee('Primary');
    }

    // ── Regression: global components untouched ──────────────────────────────

    public function test_global_feature_strip_and_footer_unchanged_on_both_pages(): void
    {
        foreach (['/', '/contact-us'] as $path) {
            $html = $this->get($this->frontUrl($path))->assertOk()->getContent();
            $this->assertSame(1, substr_count($html, 'global-feature-strip'), "feature strip on {$path}");
            $this->assertSame(1, substr_count($html, '<footer'), "footer on {$path}");
        }
    }
}
