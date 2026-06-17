<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_ai_driver_capabilities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->boolean('cdl_license')->default(false);
            $table->decimal('max_gvwr', 10, 2)->nullable();
            $table->decimal('max_trailer_weight', 10, 2)->nullable();
            $table->boolean('can_tow_equipment_trailer')->default(false);
            $table->boolean('can_tow_gooseneck')->default(false);
            $table->boolean('can_operate_cdl_truck')->default(false);
            $table->unsignedBigInteger('home_store_id')->nullable();
            $table->unsignedTinyInteger('skill_rating')->default(3);  // 1–5
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('home_store_id')->references('id')->on('stores')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_ai_driver_capabilities');
    }
};
