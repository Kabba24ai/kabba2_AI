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
        Schema::table('api_logs', function (Blueprint $table) {
            // Optional order context — not every logged call is order-scoped
            $table->unsignedBigInteger('order_id')->nullable()->after('name');
            $table->unsignedBigInteger('order_product_id')->nullable()->after('order_id');

            $table->index('order_id');
            $table->index('order_product_id');

            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
            $table->foreign('order_product_id')->references('id')->on('order_products')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('api_logs', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropForeign(['order_product_id']);
            $table->dropColumn(['order_id', 'order_product_id']);
        });
    }
};
