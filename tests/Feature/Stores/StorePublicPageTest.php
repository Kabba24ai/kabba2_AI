<?php

namespace Tests\Feature\Stores;

use App\Models\Global\Media;
use App\Models\Iam\Personnel\User;
use App\Models\Locations\State;
use App\Models\Stores\HoursOfOperation;
use App\Models\Stores\Store;
use App\Models\Stores\StorePage;
use App\Models\WebsiteManagement\WebsitePage;
use App\Models\WebsiteManagement\WebsitePageSection;
use App\Models\WebsiteManagement\WebsiteSectionItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorePublicPageTest extends TestCase
{
    use RefreshDatabase;

    private State $state;

    protected function setUp(): void
    {
        parent::setUp();
        $this->state = State::create(['name' => 'Tennessee', 'slug' => 'tennessee', 'abbreviation' => 'TN']);
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function makeStore(array $overrides = []): Store
    {
        static $n = 0;
        $n++;

        return Store::create(array_merge([
            'store_name' => "Test Store {$n}",
            'phone'      => '(615) 555-010' . $n,
            'email'      => "store{$n}@example.com",
            'address'    => "{$n}00 Main St",
            'country'    => 'USA',
            'state_id'   => $this->state->id,
            'city'       => 'Nashville',
            'zip_code'   => '37201',
            'latitude'   => '36.16',
            'longitude'  => '-86.78',
            'details'    => 'Operational details line',
            'is_primary' => 'No',
            'status'     => 'Active',
        ], $overrides));
    }

    private function makeHours(Store $store): void
    {
        foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'] as $day) {
            HoursOfOperation::create([
                'store_id'   => $store->id,
                'day_name'   => $day,
                'is_closed'  => false,
                'start_time' => '07:00:00',
                'end_time'   => '17:00:00',
            ]);
        }
        foreach (['Saturday', 'Sunday'] as $day) {
            HoursOfOperation::create([
                'store_id'  => $store->id,
                'day_name'  => $day,
                'is_closed' => true,
            ]);
        }
    }

    private function makeMedia(string $file = 'store-photo.jpg'): Media
    {
        return Media::create([
            'asset_type'         => 'Public Asset',
            'folder_name'        => 'media-library',
            'file_name'          => $file,
            'original_file_name' => $file,
            'file_extension'     => 'jpg',
            'file_type'          => 'image',
            'mime_type'          => 'image/jpeg',
            'file_size'          => 1234,
            'alt_text'           => 'Store front',
        ]);
    }

    private function admin(): User
    {
        return User::create([
            'unique_id'     => 'store-page-admin',
            'employee_code' => '97',
            'first_name'    => 'Store',
            'last_name'     => 'Admin',
            'email'         => 'store-page-admin@test.local',
            'password'      => bcrypt('secret-password'),
            'status'        => 'Active',
        ]);
    }

    private function homePage(): WebsitePage
    {
        return WebsitePage::firstOrCreate(
            ['page_key' => 'home'],
            ['title' => 'Home', 'slug' => 'home', 'status' => 'Active']
        );
    }

    /** Seed the homepage-owned global Feature Strip with one item. */
    private function seedFeatureStrip(): void
    {
        $section = WebsitePageSection::create([
            'website_page_id' => $this->homePage()->id,
            'section_key'     => 'feature_strip',
            'section_type'    => 'feature_strip',
            'section_name'    => 'Feature Strip',
            'display_order'   => 2,
            'status'          => 'Active',
        ]);

        WebsiteSectionItem::create([
            'website_page_section_id' => $section->id,
            'item_key'                => 'feature',
            'title'                   => 'Well Maintained Equipment',
            'subtitle'                => 'Reliable. Clean. Job Ready.',
            'icon'                    => 'fa-shield',
            'display_order'           => 1,
            'status'                  => 'Active',
        ]);
    }

    /** Seed the homepage-owned shared Contact Strip with a store_1 card. */
    private function seedContactStrip(Store $cardStore): void
    {
        $home = $this->homePage();

        $section = WebsitePageSection::create([
            'website_page_id' => $home->id,
            'section_key'     => 'contact_strip',
            'section_type'    => 'contact_strip',
            'section_name'    => 'Contact Strip',
            'title'           => 'Call Our Rental Specialists',
            'display_order'   => 1,
            'status'          => 'Active',
        ]);

        WebsiteSectionItem::create([
            'website_page_section_id' => $section->id,
            'item_key'                => 'store_1',
            'display_order'           => 1,
            'status'                  => 'Active',
            'content'                 => ['store_id' => $cardStore->id, 'display_phone' => true, 'display_address' => true],
        ]);
    }

    /** Complete valid payload for the admin store update form. */
    private function updatePayload(Store $store, array $overrides = []): array
    {
        $payload = [
            'store_name' => $store->store_name,
            'status'     => $store->status,
            'is_primary' => $store->is_primary,
            'phone'      => $store->phone,
            'email'      => $store->email,
            'state_id'   => $store->state_id,
            'city'       => $store->city,
            'address'    => $store->address,
            'zip_code'   => $store->zip_code,
            'latitude'   => $store->latitude,
            'longitude'  => $store->longitude,
            'details'    => $store->details,
        ];

        foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day) {
            $payload["{$day}_closed"] = 1;
        }

        return array_merge($payload, $overrides);
    }

    private function showUrl(Store $store): string
    {
        return route('front.stores.show', $store->slug);
    }

    // ── Store routing ────────────────────────────────────────────────────

    public function test_active_store_page_resolves(): void
    {
        $store = $this->makeStore();

        $this->get($this->showUrl($store))
            ->assertOk()
            ->assertSee($store->store_name);
    }

    public function test_correct_store_record_is_loaded_not_the_primary(): void
    {
        $primary = $this->makeStore(['is_primary' => 'Yes', 'store_name' => 'Primary HQ', 'phone' => '(615) 555-1111']);
        $other   = $this->makeStore(['store_name' => 'Second Location', 'phone' => '(615) 555-2222']);

        $response = $this->get($this->showUrl($other));

        $response->assertOk()
            ->assertSee('Second Location')
            ->assertSee('(615) 555-2222')
            ->assertDontSee('(615) 555-1111');
    }

    public function test_unknown_identifier_returns_404(): void
    {
        $this->get(route('front.stores.show', 'STO-NOPE-NOPE'))->assertNotFound();
    }

    public function test_inactive_store_returns_404(): void
    {
        $store = $this->makeStore(['status' => 'Inactive']);

        $this->get($this->showUrl($store))->assertNotFound();
    }

    public function test_unpublished_public_page_returns_404_while_store_stays_operational(): void
    {
        $store = $this->makeStore();
        StorePage::create(['store_id' => $store->id, 'status' => 'Inactive']);

        $this->get($this->showUrl($store))->assertNotFound();

        // Operational record untouched
        $this->assertSame('Active', $store->fresh()->status);
    }

    public function test_contact_strip_view_store_link_points_to_the_card_store(): void
    {
        $cardStore = $this->makeStore(['store_name' => 'Card Store']);
        $pageStore = $this->makeStore(['store_name' => 'Page Store']);
        $this->seedContactStrip($cardStore);

        $this->get($this->showUrl($pageStore))
            ->assertOk()
            ->assertSee($this->showUrl($cardStore), false);
    }

    // ── Shared components ────────────────────────────────────────────────

    public function test_page_uses_canonical_shared_layout_components(): void
    {
        $store = $this->makeStore();
        $this->seedFeatureStrip();

        $html = $this->get($this->showUrl($store))->assertOk()->getContent();

        // Canonical navbar, feature strip, and footer — exactly once each
        $this->assertSame(1, substr_count($html, 'id="global-feature-strip"'), 'feature strip must render once');
        $this->assertSame(1, substr_count($html, '<footer'), 'global footer must render once');
        $this->assertSame(1, substr_count($html, '<nav class="text-dark'), 'global navbar must render once');

        // Legacy photographic hero is gone
        $this->assertStringNotContainsString('front/images/banner.jpg', $html);
    }

    public function test_contact_strip_renders_by_default_and_when_enabled(): void
    {
        $store = $this->makeStore();

        // No store_pages row at all — default on
        $this->get($this->showUrl($store))->assertSee('id="contact-strip"', false);

        StorePage::create(['store_id' => $store->id, 'show_contact_strip' => true]);
        $this->get($this->showUrl($store))->assertSee('id="contact-strip"', false);
    }

    public function test_contact_strip_absent_when_disabled(): void
    {
        $store = $this->makeStore();
        StorePage::create(['store_id' => $store->id, 'show_contact_strip' => false]);

        $this->get($this->showUrl($store))->assertDontSee('id="contact-strip"', false);
    }

    // ── Store content ────────────────────────────────────────────────────

    public function test_operational_data_comes_from_the_store_record(): void
    {
        $store = $this->makeStore();
        $this->makeHours($store);

        $this->get($this->showUrl($store))
            ->assertOk()
            ->assertSee($store->store_name)
            ->assertSee($store->address)
            ->assertSee($store->phone)
            ->assertSee('tel:6155550', false) // valid tel: link, digits only
            ->assertSee('7:00 AM')
            ->assertSee('5:00 PM')
            ->assertSee('Closed');
    }

    public function test_custom_public_page_content_renders(): void
    {
        $store = $this->makeStore();
        StorePage::create([
            'store_id'     => $store->id,
            'page_heading' => 'Custom Heading Rentals',
            'intro_text'   => 'Welcome to our flagship rental yard.',
            'description'  => 'A longer description about this location and its equipment.',
        ]);

        $this->get($this->showUrl($store))
            ->assertOk()
            ->assertSee('Custom Heading Rentals')
            ->assertSee('Welcome to our flagship rental yard.')
            ->assertSee('A longer description about this location and its equipment.')
            ->assertSee('About Custom Heading Rentals');
    }

    public function test_store_image_renders_when_configured(): void
    {
        $store = $this->makeStore();
        $media = $this->makeMedia();
        StorePage::create(['store_id' => $store->id, 'image_media_id' => $media->id]);

        $this->get($this->showUrl($store))
            ->assertOk()
            ->assertSee($media->url, false)
            ->assertSee('object-cover', false);
    }

    public function test_missing_image_leaves_no_placeholder(): void
    {
        $store = $this->makeStore();

        $html = $this->get($this->showUrl($store))->assertOk()->getContent();

        $this->assertStringNotContainsString('aspect-[3/2]', $html);
    }

    // ── Admin ────────────────────────────────────────────────────────────

    public function test_guest_cannot_open_store_editor(): void
    {
        $store = $this->makeStore();

        $this->get(route('admin.stores.edit', $store->unique_id))
            ->assertRedirect(); // to login
    }

    /**
     * Public page settings are edited from the Contact Us → Stores tab
     * (Website Management → Contact Builder), not the Store Management form —
     * see App\Http\Controllers\Admin\WebsiteManagement\ContactPageBuilder\StorePage\UpdateController.
     */
    private function storePageUpdateUrl(Store $store): string
    {
        return route('admin.website-management.contact-builder.store-page.update', $store->unique_id);
    }

    public function test_admin_can_save_public_page_settings(): void
    {
        $admin = $this->admin();
        $store = $this->makeStore();
        $media = $this->makeMedia();

        $this->actingAs($admin)
            ->post($this->storePageUpdateUrl($store), [
                'page_status'            => 'Active',
                'show_contact_strip'     => 1,
                'page_heading'           => 'Admin Heading',
                'intro_text'             => 'Admin intro',
                'page_description'       => 'Admin description',
                'page_image_media_id'    => $media->id,
                'seo_title'              => 'Custom SEO Title',
                'page_meta_description'  => 'Custom meta description',
                'og_title'               => 'Custom OG Title',
                'og_description'         => 'Custom OG description',
                'canonical_url'          => 'https://rentnking.com/stores/custom',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('store_pages', [
            'store_id'         => $store->id,
            'status'           => 'Active',
            'page_heading'     => 'Admin Heading',
            'intro_text'       => 'Admin intro',
            'description'      => 'Admin description',
            'image_media_id'   => $media->id,
            'seo_title'        => 'Custom SEO Title',
            'meta_description' => 'Custom meta description',
            'og_title'         => 'Custom OG Title',
            'og_description'   => 'Custom OG description',
            'canonical_url'    => 'https://rentnking.com/stores/custom',
        ]);
    }

    public function test_saving_public_page_does_not_change_operational_fields(): void
    {
        $admin = $this->admin();
        $store = $this->makeStore();
        $before = $store->only(['store_name', 'phone', 'email', 'address', 'city', 'zip_code', 'status', 'is_primary']);

        $this->actingAs($admin)
            ->post($this->storePageUpdateUrl($store), [
                'page_heading' => 'Only Public Changed',
            ])
            ->assertRedirect();

        $this->assertSame($before, $store->fresh()->only(array_keys($before)));
        $this->assertSame('Only Public Changed', $store->fresh()->page->page_heading);
    }

    public function test_admin_can_unpublish_the_public_page(): void
    {
        $admin = $this->admin();
        $store = $this->makeStore();

        $this->actingAs($admin)
            ->post($this->storePageUpdateUrl($store), [
                'page_status' => 'Inactive',
            ])
            ->assertRedirect();

        $this->assertSame('Inactive', $store->fresh()->page->status);
        $this->get($this->showUrl($store))->assertNotFound();
    }

    public function test_editing_store_operational_data_does_not_reset_public_page(): void
    {
        $admin = $this->admin();
        $store = $this->makeStore();
        StorePage::create(['store_id' => $store->id, 'page_heading' => 'Set From Contact Builder']);

        $this->actingAs($admin)
            ->put(route('admin.stores.edit', $store->unique_id), $this->updatePayload($store))
            ->assertRedirect();

        $this->assertSame('Set From Contact Builder', $store->fresh()->page->page_heading);
    }

    public function test_store_edit_screen_no_longer_shows_public_page_section(): void
    {
        $admin = $this->admin();
        $store = $this->makeStore();

        $this->actingAs($admin)
            ->get(route('admin.stores.edit', $store->unique_id))
            ->assertOk()
            ->assertDontSee('Public Website Page')
            ->assertDontSee('View Public Store Page');
    }

    public function test_contact_builder_stores_tab_shows_store_page_editor_and_view_link(): void
    {
        $admin = $this->admin();
        $store = $this->makeStore();

        $contactPage = WebsitePage::create(['page_key' => 'contact', 'title' => 'Contact Us', 'slug' => 'contact-us', 'status' => 'Active']);
        WebsitePageSection::create([
            'website_page_id' => $contactPage->id,
            'section_key'     => 'locations',
            'section_type'    => 'locations',
            'section_name'    => 'Stores',
            'display_order'   => 1,
            'status'          => 'Active',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.website-management.contact-builder.index', ['tab' => 'locations']))
            ->assertOk()
            ->assertSee($store->store_name)
            ->assertSee($this->showUrl($store), false)
            ->assertSee($this->storePageUpdateUrl($store), false);
    }

    // ── SEO ──────────────────────────────────────────────────────────────

    public function test_custom_seo_values_override_defaults(): void
    {
        $store = $this->makeStore();
        $og    = $this->makeMedia('og-card.jpg');
        StorePage::create([
            'store_id'          => $store->id,
            'seo_title'         => 'Custom SEO Title',
            'meta_description'  => 'Custom meta description.',
            'og_title'          => 'Custom OG Title',
            'og_description'    => 'Custom OG description.',
            'og_image_media_id' => $og->id,
            'canonical_url'     => 'https://rentnking.com/stores/custom-canonical',
        ]);

        $this->get($this->showUrl($store))
            ->assertOk()
            ->assertSee('<title>Custom SEO Title</title>', false)
            ->assertSee('name="description" content="Custom meta description."', false)
            ->assertSee('property="og:title" content="Custom OG Title"', false)
            ->assertSee('property="og:description" content="Custom OG description."', false)
            ->assertSee('og:image" content="' . $og->url, false)
            ->assertSee('rel="canonical" href="https://rentnking.com/stores/custom-canonical"', false);
    }

    public function test_blank_seo_fields_use_the_fallback_chain(): void
    {
        $store = $this->makeStore();
        // No store_pages row at all

        $this->get($this->showUrl($store))
            ->assertOk()
            // title: store name + site name
            ->assertSee('<title>' . $store->store_name . ' - ' . config('app.name') . '</title>', false)
            // description: generated location summary
            ->assertSee('equipment rentals at ' . $store->address, false)
            // og:title inherits the resolved title — never blank
            ->assertSee('property="og:title" content="' . $store->store_name . ' - ' . config('app.name') . '"', false)
            // canonical: the store page's own URL
            ->assertSee('rel="canonical" href="' . $this->showUrl($store) . '"', false);
    }

    public function test_meta_description_falls_back_to_intro_text(): void
    {
        $store = $this->makeStore();
        StorePage::create(['store_id' => $store->id, 'intro_text' => 'Intro used as description.']);

        $this->get($this->showUrl($store))
            ->assertOk()
            ->assertSee('name="description" content="Intro used as description."', false);
    }

    public function test_og_image_falls_back_to_store_image(): void
    {
        $store = $this->makeStore();
        $image = $this->makeMedia('location.jpg');
        StorePage::create(['store_id' => $store->id, 'image_media_id' => $image->id]);

        $this->get($this->showUrl($store))
            ->assertOk()
            ->assertSee('og:image" content="' . $image->url, false);
    }
}
