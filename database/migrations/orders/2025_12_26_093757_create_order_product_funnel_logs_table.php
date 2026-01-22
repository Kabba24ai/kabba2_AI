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
        Schema::create('order_product_funnel_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_product_id');
            $table->unsignedBigInteger('sales_funnel_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->text('message')->nullable();
            $table->enum('status', ['Sent', 'Failed']);
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();

            $table->foreign('order_product_id')->references('id')->on('order_products')->onDelete('cascade');
            $table->foreign('sales_funnel_id')->references('id')->on('sales_funnels')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_product_funnel_logs');
    }
};
