<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Services\Website\ContactPageService;
use App\Services\Website\HomePageService;

return new class extends Migration
{
    /**
     * Final V2 cleanup: the builder-managed contact page (transitional key
     * `contact_v2`, served at /contact-us-v2) becomes THE contact page at
     * /contact-us, and all retired V2 route references are canonicalized.
     */
    public function up(): void
    {
        // 1. Promote the builder contact page to the canonical identity.
        //    If a stale row already claims the canonical key, remove it first —
        //    the builder-managed page is the production content (sections and
        //    revisions cascade on delete).
        if (DB::table('website_pages')->where('page_key', 'contact_v2')->exists()) {
            DB::table('website_pages')->where('page_key', 'contact')->delete();

            DB::table('website_pages')->where('page_key', 'contact_v2')->update([
                'page_key' => 'contact',
                'slug'     => 'contact-us',
                'title'    => 'Contact Us',
            ]);
        }

        // 2. Remove any stray transitional home page definition ('home' is canonical).
        DB::table('website_pages')->where('page_key', 'home_v2')->delete();

        // 3. Canonicalize links that still point at retired V2 routes.
        DB::table('website_menu_items')->where('url', '/home-v2')->update(['url' => '/']);
        DB::table('website_menu_items')->where('url', '/contact-us-v2')->update(['url' => '/contact-us']);
        DB::table('website_section_items')->where('button_url', '/contact-us-v2')->update(['button_url' => '/contact-us']);

        HomePageService::clearCache();
        ContactPageService::clearCache();
    }

    public function down(): void
    {
        DB::table('website_pages')->where('page_key', 'contact')->update([
            'page_key' => 'contact_v2',
            'slug'     => 'contact-us-v2',
        ]);

        HomePageService::clearCache();
        ContactPageService::clearCache();
    }
};
