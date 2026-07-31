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
        Schema::table('order_products', function (Blueprint $table) {
            $table->boolean('is_product_clean')->default(false)->after('damage_status');
            $table->decimal('rental_prepaid_cleaning', 10, 2)->default(0)->after('is_product_clean');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->dropColumn(['is_product_clean', 'rental_prepaid_cleaning']);
        });
    }
};
