<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('equipment_key_comparisons');
        Schema::dropIfExists('equipment_critical_matching_criteria');
    }

    public function down(): void
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
            $table->string('source_type', 20)->default('manual');
            $table->boolean('upgrade_exceeds_value')->default(true);
            $table->boolean('caution_if_change_value')->default(true);
            $table->boolean('upgrade_is_below_value')->default(false);
            $table->boolean('caution_if_below_value')->default(false);
            $table->unsignedTinyInteger('default_weight')->default(50);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_key_criteria')->default(true);
            $table->timestamps();

            $table->unique(['product_category_id', 'criteria_key'], 'eccmc_category_key_uq');
            $table->index(['product_category_id', 'is_active'], 'eccmc_category_active_idx');
            $table->index(['product_category_id', 'is_active', 'is_key_criteria'], 'eccmc_category_active_key_idx');
        });

        Schema::create('equipment_key_comparisons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('equipment_id');
            $table->foreign('equipment_id')
                ->references('id')
                ->on('equipment')
                ->cascadeOnDelete();
            $table->string('spec_label');
            $table->string('spec_value');
            $table->string('spec_unit')->nullable();
            $table->boolean('is_manual_override')->default(false);
            $table->integer('sort_order')->default(0)->comment('Display order');
            $table->timestamps();

            $table->index(['equipment_id', 'sort_order']);
        });
    }
};
