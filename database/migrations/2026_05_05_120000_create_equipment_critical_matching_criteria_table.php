<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_critical_matching_criteria', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_category_id');
            $table->foreign('product_category_id')
                ->references('id')
                ->on('product_categories')
                ->cascadeOnDelete();

            $table->string('criteria_key', 120);
            $table->string('name', 150);
            $table->string('unit', 30)->nullable();
            $table->unsignedTinyInteger('default_weight')->default(50);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['product_category_id', 'criteria_key'], 'eccmc_category_key_uq');
            $table->index(['product_category_id', 'is_active'], 'eccmc_category_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_critical_matching_criteria');
    }
};
