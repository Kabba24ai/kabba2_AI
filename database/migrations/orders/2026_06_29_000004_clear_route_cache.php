<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    public function up(): void
    {
        // Force route cache to be cleared so the pipeline's subsequent route:cache
        // regenerates from fresh files (avoids OPcache serving stale routes).
        Artisan::call('route:clear');
    }

    public function down(): void
    {
        //
    }
};
