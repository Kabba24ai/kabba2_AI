<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->after('sale_price_monthly', function (Blueprint $table) {
                $table->decimal('related_product_price_daily', 10, 2)->nullable();
                $table->decimal('related_product_price_weekend', 10, 2)->nullable();
                $table->decimal('related_product_price_weekly', 10, 2)->nullable();
                $table->decimal('related_product_price_monthly', 10, 2)->nullable();
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'related_product_price_daily',
                'related_product_price_weekend',
                'related_product_price_weekly',
                'related_product_price_monthly',
            ]);
        });
    }
};