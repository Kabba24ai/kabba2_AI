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
        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('rental_truck_insurance_daily', 'rental_track_insurance_daily');
            $table->renameColumn('rental_truck_insurance_weekend', 'rental_track_insurance_weekend');
            $table->renameColumn('rental_truck_insurance_weekly', 'rental_track_insurance_weekly');
            $table->renameColumn('rental_truck_insurance_monthly', 'rental_track_insurance_monthly');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('rental_track_insurance_daily', 'rental_truck_insurance_daily');
            $table->renameColumn('rental_track_insurance_weekend', 'rental_truck_insurance_weekend');
            $table->renameColumn('rental_track_insurance_weekly', 'rental_truck_insurance_weekly');
            $table->renameColumn('rental_track_insurance_monthly', 'rental_truck_insurance_monthly');
        });
    }
};
