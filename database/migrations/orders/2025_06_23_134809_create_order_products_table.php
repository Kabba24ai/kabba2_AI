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
        Schema::create('order_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id');

            $table->string('product_name');
            $table->decimal('price', 10, 2);
            $table->integer('quantity');
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('total', 10, 2);

            $table->date('schedule_start_date')->nullable(); // added field
            $table->date('schedule_end_date')->nullable();   // added field
            $table->json('product_data')->nullable(); // <<-- JSON column for extra details
            $table->enum('service_method', ['In Store Pickup', 'Delivery'])->nullable();
            $table->unsignedBigInteger('store_id')->nullable(); // store reference
            $table->enum('service_option', ['Delivery + Pickup', 'Delivery + Return', 'Pickup + Return'])->nullable();
            $table->enum('distance_type', ['Standard', 'Extended', 'Custom'])->nullable();
            $table->string('distance_range')->nullable(); // 15, 30 etc.

            $table->timestamps();

             // Foreign keys
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('store_id')->references('id')->on('stores')->nullOnDelete(); // nullable FK
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_products');
    }
};
