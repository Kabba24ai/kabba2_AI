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
          Schema::table('sales_funnels', function (Blueprint $table) {

            // Remove old timing fields
            if (Schema::hasColumn('sales_funnels', 'timing_unit')) {
                $table->dropColumn('timing_unit');
            }

            if (Schema::hasColumn('sales_funnels', 'timing_value')) {
                $table->dropColumn('timing_value');
            }

            // Add new timing fields
            $table->string('date_value')->nullable()->after('trigger_event_timing');
            $table->string('hour_value')->nullable()->after('date_value');
            $table->string('minute_value')->nullable()->after('hour_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_funnels', function (Blueprint $table) {

            // Re-add old columns
            $table->enum('timing_unit', ['Days', 'Weeks', 'Months'])->nullable();
            $table->integer('timing_value')->nullable();

            // Remove new columns
            $table->dropColumn([
                'date_value',
                'hour_value',
                'minute_value',
            ]);
        });
    }
};
