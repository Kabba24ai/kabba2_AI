<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-product "Do Not Display" flags for the four primary rental price
     * levels. Presentation only — the stored price values are untouched and
     * every calculation keeps using them. Default false: existing products
     * continue to display all four prices.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('hide_rental_daily')->default(false)->after('rental_monthly');
            $table->boolean('hide_rental_weekend')->default(false)->after('hide_rental_daily');
            $table->boolean('hide_rental_weekly')->default(false)->after('hide_rental_weekend');
            $table->boolean('hide_rental_monthly')->default(false)->after('hide_rental_weekly');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'hide_rental_daily',
                'hide_rental_weekend',
                'hide_rental_weekly',
                'hide_rental_monthly',
            ]);
        });
    }
};
