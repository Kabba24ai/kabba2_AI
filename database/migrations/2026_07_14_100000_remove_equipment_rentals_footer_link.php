<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Services\Website\HomePageService;

return new class extends Migration
{
    /**
     * There is no Equipment Rentals landing page (`/product-categories` has
     * no index route — only `/product-categories/{slug}`), and the primary
     * navigation already exposes every rental category via its dropdown.
     * Remove the dead footer link outright; no replacement, no redirect.
     */
    public function up(): void
    {
        DB::table('website_section_items')
            ->whereIn('item_key', ['quick_link', 'other_link'])
            ->where('button_url', '/product-categories')
            ->delete();

        HomePageService::clearCache();
    }

    public function down(): void
    {
        // No reverse: the link pointed at a route that does not exist.
    }
};
