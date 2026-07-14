<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Services\Website\HomePageService;

return new class extends Migration
{
    /**
     * The canonical homepage is `/` (HomeV2 took over the root route on
     * 2026-07-07). Footer links saved before the cutover still point at the
     * legacy `/home-v2` alias — rewrite them to the canonical route.
     */
    public function up(): void
    {
        DB::table('website_section_items')
            ->where('button_url', '/home-v2')
            ->update(['button_url' => '/']);

        HomePageService::clearCache();
    }

    public function down(): void
    {
        // No reverse: '/' is also where '/home-v2' renders, and we cannot
        // know which rows originally used the alias.
    }
};
