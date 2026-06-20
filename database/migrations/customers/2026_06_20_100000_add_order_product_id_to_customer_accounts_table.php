<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('order_product_id')
                ->nullable()
                ->after('order_id');

            $table->foreign('order_product_id')
                ->references('id')
                ->on('order_products')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customer_accounts', function (Blueprint $table) {
            $table->dropForeign(['order_product_id']);
            $table->dropColumn('order_product_id');
        });
    }
};
