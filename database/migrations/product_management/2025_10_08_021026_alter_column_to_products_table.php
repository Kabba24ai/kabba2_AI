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
            $table->after('has_high_demand_alert', function (Blueprint $table) {
                $table->string('truck_fee_size_setting')->nullable();
                $table->string('track_insurance_size_setting')->nullable();
                $table->string('prepaid_cleaning_rate_setting')->nullable();
                $table->string('prepaid_fuel_rate_setting')->nullable();
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
                'truck_fee_size_setting',
                'track_insurance_size_setting',
                'prepaid_cleaning_rate_setting',
                'prepaid_fuel_rate_setting',
            ]);
        });
    }
};
