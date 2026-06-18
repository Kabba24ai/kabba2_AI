<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_ai_custom_specs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->foreign('category_id')->references('id')->on('product_categories')->cascadeOnDelete();
            $table->string('spec_key', 100);
            $table->string('spec_label', 150);
            $table->text('description')->nullable()->comment('Research instruction sent to AI');
            $table->enum('value_type', ['boolean', 'numeric', 'text', 'enum'])->default('text');
            $table->json('allowed_values')->nullable()->comment('For boolean/enum: ordered list of valid values');
            $table->boolean('is_key_comparison')->default(false);
            $table->boolean('is_ignored')->default(false);
            $table->timestamps();

            $table->unique(['category_id', 'spec_key'], 'uq_custom_spec_category_key');
            $table->index('category_id', 'idx_custom_spec_category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_ai_custom_specs');
    }
};
