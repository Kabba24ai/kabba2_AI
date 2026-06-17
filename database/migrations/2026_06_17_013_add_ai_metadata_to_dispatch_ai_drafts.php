<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatch_ai_drafts', function (Blueprint $table) {
            $table->json('ai_metadata')->nullable()->after('confidence_score');
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_ai_drafts', function (Blueprint $table) {
            $table->dropColumn('ai_metadata');
        });
    }
};
