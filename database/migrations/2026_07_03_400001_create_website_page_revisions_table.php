<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_page_revisions', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();
            $table->foreignId('website_page_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('revision_number');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->text('change_summary')->nullable();
            $table->json('snapshot');
            $table->boolean('is_published_snapshot')->default(false);
            // Self-referential soft link — no FK to avoid complexity
            $table->unsignedBigInteger('restored_from_revision_id')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['website_page_id', 'revision_number']);
            $table->index(['website_page_id', 'created_at']);
        });

        // Now that revisions table exists, add FK from website_pages
        Schema::table('website_pages', function (Blueprint $table) {
            $table->foreign('published_revision_id')
                  ->references('id')
                  ->on('website_page_revisions')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('website_pages', function (Blueprint $table) {
            $table->dropForeign(['published_revision_id']);
        });
        Schema::dropIfExists('website_page_revisions');
    }
};
