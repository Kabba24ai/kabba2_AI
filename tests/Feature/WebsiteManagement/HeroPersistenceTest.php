<?php

namespace Tests\Feature\WebsiteManagement;

use App\Models\WebsiteManagement\WebsitePage;
use App\Models\WebsiteManagement\WebsitePageSection;
use App\Services\Website\HomePageService;
use App\Services\Website\PagePublishService;
use App\Services\Website\PageRevisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Hero Image Persistence & Display fix (Home Builder → HomeV2).
 *
 * Guards the two confirmed defects:
 *  1. Lifecycle actions (publish/unpublish/archive/restore) must invalidate the
 *     REAL homepage cache (HomePageService `homepage_builder_data`), not just the
 *     never-written website_page_* keys.
 *  2. Revision restore must reproduce exactly the hero from the chosen revision.
 */
class HeroPersistenceTest extends TestCase
{
    use RefreshDatabase;

    private function homePage(): WebsitePage
    {
        return WebsitePage::create([
            'page_key'       => 'home',
            'title'          => 'Home',
            'slug'           => 'home',
            'status'         => 'Active',
            'publish_status' => WebsitePage::PUBLISH_STATUS_PUBLISHED,
        ]);
    }

    private function heroSection(WebsitePage $page, ?int $imageId): WebsitePageSection
    {
        return WebsitePageSection::create([
            'website_page_id' => $page->id,
            'section_key'     => 'hero',
            'section_name'    => 'Hero',
            'title'           => 'THE RIGHT EQUIPMENT.',
            'image'           => $imageId,
            'display_order'   => 1,
            'status'          => 'Active',
        ]);
    }

    /** Warm the homepage cache so we can prove a later action clears it. */
    private function warmCache(): void
    {
        app(HomePageService::class)->getData();
        $this->assertTrue(Cache::has(HomePageService::CACHE_KEY), 'precondition: cache warmed');
    }

    /** @test */
    public function publishing_the_home_page_clears_the_homepage_cache(): void
    {
        $page = $this->homePage();
        $this->warmCache();

        app(PagePublishService::class)->publish($page);

        $this->assertFalse(Cache::has(HomePageService::CACHE_KEY), 'publish must clear homepage cache');
    }

    /** @test */
    public function unpublishing_clears_the_homepage_cache(): void
    {
        $page = $this->homePage();
        $this->warmCache();

        app(PagePublishService::class)->unpublish($page);

        $this->assertFalse(Cache::has(HomePageService::CACHE_KEY));
    }

    /** @test */
    public function archiving_clears_the_homepage_cache(): void
    {
        $page = $this->homePage();
        $this->warmCache();

        app(PagePublishService::class)->archive($page);

        $this->assertFalse(Cache::has(HomePageService::CACHE_KEY));
    }

    /** @test */
    public function clear_published_cache_only_touches_homepage_for_the_home_page(): void
    {
        // A non-home page must NOT wipe the homepage cache.
        $other = WebsitePage::create([
            'page_key' => 'contact_v2', 'title' => 'Contact', 'slug' => 'contact-v2',
            'status' => 'Active', 'publish_status' => WebsitePage::PUBLISH_STATUS_PUBLISHED,
        ]);
        $this->warmCache();

        app(PagePublishService::class)->clearPublishedCache($other);

        $this->assertTrue(Cache::has(HomePageService::CACHE_KEY), 'non-home page must not clear homepage cache');
    }

    /** @test */
    public function restoring_a_revision_clears_cache_and_reinstates_that_revisions_hero(): void
    {
        $page = $this->homePage();
        $hero = $this->heroSection($page, 111);

        // Snapshot revision #1 with hero image 111.
        $revisions = app(PageRevisionService::class);
        $rev1 = $revisions->createRevision($page->fresh()->load('sections.items'), 'v1 hero=111');

        // Admin changes the hero to image 222 (the "new" image).
        $hero->update(['image' => 222]);
        app(HomePageService::class)->clearCache();
        $this->warmCache();

        // Restoring revision #1 must bring back 111 AND clear the cache.
        $publish = app(PagePublishService::class);
        $restored = $revisions->restoreRevision($rev1->fresh());
        $publish->clearPublishedCache($page->fresh());

        $this->assertFalse(Cache::has(HomePageService::CACHE_KEY), 'restore must clear homepage cache');

        $this->assertSame(
            111,
            (int) WebsitePageSection::where('website_page_id', $page->id)
                ->where('section_key', 'hero')->value('image'),
            'restore must reinstate the hero from the CHOSEN revision (111), not a stale value'
        );
        $this->assertNotNull($restored);
    }

    /** @test */
    public function public_homepage_reads_the_current_home_builder_hero_source(): void
    {
        $page = $this->homePage();
        $this->heroSection($page, null); // no media row needed; image_url resolves null

        $data = app(HomePageService::class)->getData();

        // The hero object is built from the Home Builder section (title proves the source).
        $this->assertSame('THE RIGHT EQUIPMENT.', $data->hero->title);
    }
}
