<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Optional thumbnail image for industry preset cards (central media table). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('price_list_presets', function (Blueprint $table) {
            $table->unsignedBigInteger('media_id')->nullable()->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('price_list_presets', function (Blueprint $table) {
            $table->dropColumn('media_id');
        });
    }
};
