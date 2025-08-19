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
            $table->after('rental_damage_waiver_monthly', function ($table) {
                $table->decimal('rental_truck_insurance_daily')->nullable();
                $table->decimal('rental_truck_insurance_weekend')->nullable();
                $table->decimal('rental_truck_insurance_weekly')->nullable();
                $table->decimal('rental_truck_insurance_monthly')->nullable();
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'rental_truck_insurance_daily',
                'rental_truck_insurance_weekend',
                'rental_truck_insurance_weekly',
                'rental_truck_insurance_monthly'
            ]);
        });
    }
};
