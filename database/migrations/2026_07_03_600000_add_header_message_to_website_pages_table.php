<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_pages', function (Blueprint $table) {
            $table->string('header_message', 100)->nullable()->after('canonical_url');
            $table->string('header_highlight', 100)->nullable()->after('header_message');
            $table->string('header_badge', 60)->nullable()->after('header_highlight');
            $table->string('header_callout', 160)->nullable()->after('header_badge');
        });
    }

    public function down(): void
    {
        Schema::table('website_pages', function (Blueprint $table) {
            $table->dropColumn(['header_message', 'header_highlight', 'header_badge', 'header_callout']);
        });
    }
};
