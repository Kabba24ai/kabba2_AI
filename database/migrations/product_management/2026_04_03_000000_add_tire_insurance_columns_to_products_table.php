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
            $table->after('rental_track_insurance_monthly', function (Blueprint $table) {
                $table->decimal('rental_tire_insurance_daily', 10, 2)->nullable();
                $table->decimal('rental_tire_insurance_weekend', 10, 2)->nullable();
                $table->decimal('rental_tire_insurance_weekly', 10, 2)->nullable();
                $table->decimal('rental_tire_insurance_monthly', 10, 2)->nullable();
            });

            $table->after('track_insurance_size_setting', function (Blueprint $table) {
                $table->string('tire_insurance_size_setting')->nullable();
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
                'rental_tire_insurance_daily',
                'rental_tire_insurance_weekend',
                'rental_tire_insurance_weekly',
                'rental_tire_insurance_monthly',
                'tire_insurance_size_setting',
            ]);
        });
    }
};
