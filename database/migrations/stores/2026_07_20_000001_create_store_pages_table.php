<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Public website presentation settings for a store — one row per store,
     * created lazily the first time the store's Public Website Page section
     * is saved. Stores without a row render with all defaults (page visible,
     * shared contact strip on, no custom content), so no backfill is needed.
     * Operational data (name, address, phone, hours, map) stays on `stores`.
     */
    public function up(): void
    {
        Schema::create('store_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->unique()->constrained('stores')->cascadeOnDelete();

            // Publish control for the public detail page
            $table->string('status', 20)->default('Active'); // Active, Inactive

            // Customer-facing presentation
            $table->string('page_heading', 240)->nullable();  // fallback: store_name
            $table->string('intro_text', 500)->nullable();
            $table->text('description')->nullable();
            $table->foreignId('image_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->boolean('show_contact_strip')->default(true);

            // SEO / Open Graph overrides — blanks fall back at render time
            $table->string('seo_title', 240)->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->string('og_title', 240)->nullable();
            $table->string('og_description', 500)->nullable();
            $table->foreignId('og_image_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('canonical_url', 500)->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_pages');
    }
};
