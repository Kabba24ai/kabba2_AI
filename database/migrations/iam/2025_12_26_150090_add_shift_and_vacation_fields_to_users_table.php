<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
                // Shift timing
            $table->time('shift_start_time')->nullable()->after('status');
            $table->time('shift_end_time')->nullable()->after('shift_start_time');

            // Vacation eligibility
            $table->boolean('vacation_eligible')
                  ->default(false)
                  ->after('shift_end_time');

            // Vacation hours (FK)
            $table->foreignId('vacation_allotment_hour_id')
                  ->nullable()
                  ->constrained('vacation_hours')
                  ->nullOnDelete()
                  ->after('vacation_eligible');

            // Vacation start day (FK)
            $table->foreignId('vacation_start_day_id')
                  ->nullable()
                  ->constrained('vacation_days')
                  ->nullOnDelete()
                  ->after('vacation_allotment_hour_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
             $table->dropForeign(['vacation_allotment_hour_id']);
            $table->dropForeign(['vacation_start_day_id']);

            $table->dropColumn([
                'shift_start_time',
                'shift_end_time',
                'vacation_eligible',
                'vacation_allotment_hour_id',
                'vacation_start_day_id',
            ]);
        });
    }
};
