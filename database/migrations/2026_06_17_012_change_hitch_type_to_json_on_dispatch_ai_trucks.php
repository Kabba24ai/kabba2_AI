<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatch_ai_trucks', function (Blueprint $table) {
            $table->dropColumn('hitch_type');
        });

        Schema::table('dispatch_ai_trucks', function (Blueprint $table) {
            $table->json('hitch_types')->nullable()->after('tow_rating');
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_ai_trucks', function (Blueprint $table) {
            $table->dropColumn('hitch_types');
        });

        Schema::table('dispatch_ai_trucks', function (Blueprint $table) {
            $table->string('hitch_type')->nullable()->after('tow_rating');
        });
    }
};
