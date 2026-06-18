<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_ai_custom_spec_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('custom_spec_id');
            $table->foreign('custom_spec_id')->references('id')->on('equipment_ai_custom_specs')->cascadeOnDelete();
            $table->unsignedBigInteger('equipment_ai_profile_id');
            $table->foreign('equipment_ai_profile_id')->references('id')->on('equipment_ai_profiles')->cascadeOnDelete();

            $table->string('spec_value', 500)->nullable();
            $table->decimal('confidence_score', 5, 4)->nullable();
            $table->enum('value_source', ['ai', 'manual', 'unknown'])->default('unknown');
            $table->text('source_reference')->nullable()->comment('Manufacturer doc or URL AI found the value from');
            $table->text('ai_reason')->nullable()->comment('AI explanation of why this value was chosen');
            $table->boolean('confirmed_by_user')->default(false);
            $table->string('last_updated_by', 150)->nullable();
            $table->timestamps();

            $table->unique(['custom_spec_id', 'equipment_ai_profile_id'], 'uq_custom_spec_value_profile');
            $table->index('custom_spec_id', 'idx_custom_spec_value_spec');
            $table->index('equipment_ai_profile_id', 'idx_custom_spec_value_profile');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_ai_custom_spec_values');
    }
};
