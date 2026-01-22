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
        Schema::create('sales_funnel_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_funnel_id');
            $table->unsignedBigInteger('product_id');
            $table->foreign('sales_funnel_id')->references('id')->on('sales_funnels')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_funnel_products');
    }
};
