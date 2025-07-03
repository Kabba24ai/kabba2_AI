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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();
            $table->string('product_name');
            $table->string('slug')->unique();
            $table->enum('product_type',['Rental', 'Retail'])->default('Retail');
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();

            // Content
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();

            $table->unsignedBigInteger('media_id')->nullable(); // Foreign key for media
            $table->foreign('media_id')->references('id')->on('media')->nullOnDelete();
            $table->boolean('is_general_term_type')->default(true);
            $table->boolean('is_custom_term_type')->default(false);

            // Retail configuration
            $table->string('sku')->nullable();
            $table->string('barcode')->nullable();
            $table->decimal('retail_price')->nullable();
            $table->decimal('retail_sale_price')->nullable();
            $table->decimal('retail_product_cost')->nullable();

            // Rental configuration
            $table->decimal('rental_daily')->nullable();
            $table->decimal('rental_weekend')->nullable();
            $table->decimal('rental_weekly')->nullable();
            $table->decimal('rental_monthly')->nullable();
            $table->decimal('rental_damage_waiver_daily')->nullable();
            $table->decimal('rental_damage_waiver_weekend')->nullable();
            $table->decimal('rental_damage_waiver_weekly')->nullable();
            $table->decimal('rental_damage_waiver_monthly')->nullable();
            $table->decimal('rental_prepaid_cleaning')->nullable();
            $table->decimal('rental_prepaid_fuel')->nullable();
            $table->decimal('rental_fuel_gallons')->nullable();
            $table->enum('rental_fuel_type',['Diesel','Gas'])->nullable();
            $table->decimal('rental_def_gallons')->nullable();

            // Sale Prices
            $table->decimal('sale_price_daily')->nullable();
            $table->decimal('sale_price_weekend')->nullable();
            $table->decimal('sale_price_weekly')->nullable();
            $table->decimal('sale_price_monthly')->nullable();

            $table->decimal('standard_delivery_fee')->nullable();
            $table->decimal('extended_delivery_fee')->nullable();
            $table->enum('in_store_pickup',['Yes','No'])->nullable();
            $table->enum('delivery_and_pickup',['Yes','No'])->nullable();
            $table->enum('hour_tracking',['Yes','No'])->nullable();
            $table->decimal('hour_rate')->nullable();
            $table->enum('status', ['Published', 'Draft', 'Pending'])->default('Pending');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
