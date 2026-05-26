<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces single-unit offset (offset_unit + offset_value) with
 * a three-part combined offset: days, hours, minutes.
 *
 * offset_minutes (total) is kept as the computed column for scheduling queries.
 * The old offset_unit / offset_value columns remain in the table for historical data
 * but are no longer written by the application.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_funnel_steps', function (Blueprint $table) {
            $table->unsignedSmallInteger('offset_days')->default(0)->after('offset_direction');
            $table->unsignedSmallInteger('offset_hours')->default(0)->after('offset_days');
            $table->unsignedSmallInteger('offset_minutes_val')->default(0)->after('offset_hours');
        });
    }

    public function down(): void
    {
        Schema::table('sales_funnel_steps', function (Blueprint $table) {
            $table->dropColumn(['offset_days', 'offset_hours', 'offset_minutes_val']);
        });
    }
};
