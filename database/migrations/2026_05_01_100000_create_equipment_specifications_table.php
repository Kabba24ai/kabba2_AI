<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_specifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('equipment_id');
            $table->foreign('equipment_id')->references('id')->on('equipment')->cascadeOnDelete();
            $table->string('spec_key', 100);
            $table->string('spec_label', 150);
            $table->string('value', 500)->nullable();
            $table->string('unit', 50)->nullable();
            $table->text('source_url')->nullable();
            $table->decimal('confidence_score', 5, 4)->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_manual_override')->default(false);
            $table->json('ai_lookup_payload')->nullable();
            $table->timestamps();

            $table->unique(['equipment_id', 'spec_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_specifications');
    }
};
