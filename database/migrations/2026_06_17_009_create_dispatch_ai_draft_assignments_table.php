<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_ai_draft_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('draft_id');
            $table->unsignedBigInteger('order_product_id');
            $table->enum('slot', ['delivery', 'return']);
            $table->unsignedBigInteger('recommended_driver_id')->nullable();
            $table->unsignedBigInteger('recommended_truck_id')->nullable();
            $table->unsignedBigInteger('recommended_trailer_id')->nullable();
            $table->unsignedSmallInteger('recommended_priority')->nullable();
            $table->boolean('is_early_delivery')->default(false);
            $table->date('suggested_delivery_date')->nullable();
            $table->text('ai_reasoning')->nullable();
            $table->boolean('was_applied')->default(false);  // true once admin accepts this recommendation
            $table->timestamps();

            $table->foreign('draft_id')->references('id')->on('dispatch_ai_drafts')->onDelete('cascade');
            $table->foreign('order_product_id')->references('id')->on('order_products')->onDelete('cascade');
            $table->foreign('recommended_driver_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('recommended_truck_id')->references('id')->on('dispatch_ai_trucks')->onDelete('set null');
            $table->foreign('recommended_trailer_id')->references('id')->on('dispatch_ai_trailers')->onDelete('set null');

            $table->unique(['draft_id', 'order_product_id', 'slot'], 'dai_draft_op_slot_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_ai_draft_assignments');
    }
};
