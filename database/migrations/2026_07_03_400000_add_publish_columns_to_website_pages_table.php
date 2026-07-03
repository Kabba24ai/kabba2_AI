<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_pages', function (Blueprint $table) {
            $table->string('publish_status', 20)->default('published')->after('status');
            $table->timestamp('published_at')->nullable()->after('publish_status');
            // Soft reference — FK added after revisions table exists
            $table->unsignedBigInteger('published_revision_id')->nullable()->after('published_at');
            $table->json('auto_save_data')->nullable()->after('published_revision_id');
            $table->timestamp('auto_saved_at')->nullable()->after('auto_save_data');
        });

        // Backfill: Active → published, Inactive → draft
        DB::statement("UPDATE website_pages SET publish_status = 'published', published_at = updated_at WHERE status = 'Active'");
        DB::statement("UPDATE website_pages SET publish_status = 'draft' WHERE status = 'Inactive'");
    }

    public function down(): void
    {
        Schema::table('website_pages', function (Blueprint $table) {
            $table->dropColumn(['publish_status', 'published_at', 'published_revision_id', 'auto_save_data', 'auto_saved_at']);
        });
    }
};
