<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_page_sections', function (Blueprint $table) {
            $table->string('section_type', 100)->nullable()->after('section_key');
        });

        // Backfill: derive section_type from section_key for existing rows
        DB::statement("UPDATE website_page_sections SET section_type = section_key WHERE section_type IS NULL");
    }

    public function down(): void
    {
        Schema::table('website_page_sections', function (Blueprint $table) {
            $table->dropColumn('section_type');
        });
    }
};
