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
        Schema::create('order_media', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique(); // Unique identifier for the media
            $table->string('type'); // e.g., 'license', 'delivery', 'pickup'
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('order_product_id')->nullable(); // Nullable if not applicable
            $table->unsignedBigInteger('media_id'); // ID of the media in the media table
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('order_product_id')->references('id')->on('order_products')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('media_id')->references('id')->on('media')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null')->onUpdate('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_media');
    }
};
