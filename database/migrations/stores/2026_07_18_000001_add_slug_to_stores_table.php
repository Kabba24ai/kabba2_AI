<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('store_name');
        });

        // Backfill existing stores with a unique slug derived from their name —
        // new/updated stores get theirs automatically via the Sluggable trait.
        $existingSlugs = [];
        foreach (DB::table('stores')->select('id', 'store_name')->orderBy('id')->get() as $store) {
            $base = Str::slug($store->store_name) ?: 'store';
            $slug = $base;
            $suffix = 1;
            while (in_array($slug, $existingSlugs, true)) {
                $suffix++;
                $slug = "{$base}-{$suffix}";
            }
            $existingSlugs[] = $slug;

            DB::table('stores')->where('id', $store->id)->update(['slug' => $slug]);
        }

        Schema::table('stores', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
