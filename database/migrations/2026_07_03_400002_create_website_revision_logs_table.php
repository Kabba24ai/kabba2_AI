<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_revision_logs', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();
            $table->foreignId('website_page_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('website_page_revision_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action', 60);   // published, unpublished, archived, draft_saved, auto_saved, restored, revision_deleted
            $table->text('description')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['website_page_id', 'created_at']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_revision_logs');
    }
};
