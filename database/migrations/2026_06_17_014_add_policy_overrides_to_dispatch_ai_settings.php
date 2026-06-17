<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatch_ai_settings', function (Blueprint $table) {
            $table->json('policy_overrides')->nullable()->after('route_respect_delivery_windows');
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_ai_settings', function (Blueprint $table) {
            $table->dropColumn('policy_overrides');
        });
    }
};
