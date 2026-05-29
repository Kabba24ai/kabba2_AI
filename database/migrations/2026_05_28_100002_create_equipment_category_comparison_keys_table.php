<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Defines which specification keys are meaningful for substitution
     * comparisons within a given category.  Each category has its own set
     * of keys — a scissor lift category cares about different specs than a
     * skid steer category.
     */
    public function up(): void
    {
        Schema::create('equipment_category_comparison_keys', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('category_id');
            $table->foreign('category_id')
                ->references('id')
                ->on('product_categories')
                ->cascadeOnDelete();

            // Matches spec_key in equipment_ai_specifications
            $table->string('spec_key', 100);

            // Admin-defined human label for this category context
            $table->string('display_label', 150);

            // How important is this spec when comparing machines?
            $table->enum('importance_level', [
                'critical',
                'high',
                'medium',
                'low',
            ])->default('medium');

            // How should values be compared?
            $table->enum('comparison_type', [
                'higher_is_better',
                'lower_is_better',
                'must_match',
                'range_acceptable',
                'informational_only',
            ])->default('informational_only');

            // Display order within category
            $table->unsignedSmallInteger('sort_order')->default(0);

            // Must a substitute machine have this spec?
            $table->boolean('is_required')->default(false);

            $table->timestamps();

            $table->unique(['category_id', 'spec_key'], 'uq_comparison_key_category_spec');
            $table->index(['category_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_category_comparison_keys');
    }
};
