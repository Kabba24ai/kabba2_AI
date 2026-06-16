<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_page_metadata', function (Blueprint $table) {
            $table->id();
            $table->enum('page_type', ['product', 'category', 'home']);
            $table->unsignedBigInteger('page_id')->nullable();
            $table->string('url', 500)->nullable();
            $table->string('ai_title')->nullable();
            $table->text('ai_summary')->nullable();
            $table->json('ai_keywords')->nullable();
            $table->string('ai_service_type', 50)->nullable();
            $table->json('ai_use_cases')->nullable();
            $table->json('ai_area_served')->nullable();
            $table->json('ai_related_categories')->nullable();
            $table->json('ai_related_products')->nullable();
            $table->longText('schema_json')->nullable();
            $table->string('generated_from_hash', 64)->nullable();
            $table->timestamp('last_generated_at')->nullable();
            $table->timestamps();

            $table->unique(['page_type', 'page_id']);
            $table->index('page_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_page_metadata');
    }
};
