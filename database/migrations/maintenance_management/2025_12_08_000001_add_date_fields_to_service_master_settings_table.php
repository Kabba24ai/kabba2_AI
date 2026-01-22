<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_master_settings', function (Blueprint $table) {
            $table->integer('pending_before_dates')->default(20)->after('pending_after_hours');
            $table->integer('pending_after_dates')->default(15)->after('pending_before_dates');
        });
    }

    public function down(): void
    {
        Schema::table('service_master_settings', function (Blueprint $table) {
            $table->dropColumn(['pending_before_dates', 'pending_after_dates']);
        });
    }
};
