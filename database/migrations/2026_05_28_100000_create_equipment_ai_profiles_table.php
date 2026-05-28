<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One reusable AI profile per unique category + make + model combination.
     * Profiles are created by scanning existing equipment records; AI spec
     * data is fetched later, one profile at a time, via queue jobs.
     */
    public function up(): void
    {
        Schema::create('equipment_ai_profiles', function (Blueprint $table) {
            $table->id();

            // Human-readable unique ID (e.g. EQAI-ABCD-EFGH)
            $table->string('unique_id', 50)->unique();

            // Links to product_categories (same FK used by equipment table)
            $table->unsignedBigInteger('category_id');
            $table->foreign('category_id')
                ->references('id')
                ->on('product_categories')
                ->cascadeOnDelete();

            // Raw make/model as read from equipment records
            $table->string('make', 255);
            $table->string('model', 255);

            // Lowercased / trimmed versions used for dedup matching
            $table->string('normalized_make', 255);
            $table->string('normalized_model', 255);

            // Workflow status
            $table->enum('ai_status', [
                'pending',
                'ready_for_ai',
                'processing',
                'completed',
                'needs_review',
                'failed',
            ])->default('pending');

            $table->timestamp('last_ai_update_at')->nullable();

            // Free-text notes (e.g. "sourced from manufacturer PDF")
            $table->text('source_notes')->nullable();

            $table->timestamps();

            // One profile per category + normalized make + normalized model
            $table->unique(['category_id', 'normalized_make', 'normalized_model'], 'uq_ai_profile_category_make_model');

            $table->index('ai_status');
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_ai_profiles');
    }
};
