<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Individual specification key/value rows for each AI profile.
     * Populated by AI lookup jobs or manual admin entry.
     */
    public function up(): void
    {
        Schema::create('equipment_ai_specifications', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('equipment_ai_profile_id');
            $table->foreign('equipment_ai_profile_id')
                ->references('id')
                ->on('equipment_ai_profiles')
                ->cascadeOnDelete();

            // Machine-readable key (e.g. "engine_hp", "lift_capacity_lbs")
            $table->string('spec_key', 100);

            // Human-readable label (e.g. "Engine Horsepower")
            $table->string('spec_label', 150);

            // The actual value (stored as string; cast in application layer)
            $table->string('spec_value', 500)->nullable();

            // Optional unit (e.g. "hp", "lbs", "ft")
            $table->string('spec_unit', 50)->nullable();

            // 0.0000–1.0000 — AI confidence or manual = 1.0
            $table->decimal('confidence_score', 5, 4)->nullable();

            // Where this spec came from (e.g. "manual", "ai_openai", "manufacturer_pdf")
            $table->string('source', 100)->nullable()->default('manual');

            $table->timestamps();

            // Explicit short names — MySQL has a 64-char index name limit
            $table->index(['equipment_ai_profile_id', 'spec_key'], 'idx_ai_spec_profile_key');
            $table->unique(['equipment_ai_profile_id', 'spec_key'], 'uq_ai_spec_profile_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_ai_specifications');
    }
};
