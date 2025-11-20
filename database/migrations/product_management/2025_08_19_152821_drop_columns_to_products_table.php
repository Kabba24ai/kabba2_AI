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
            $table->dropColumn([
                'rental_fuel_gallons',
                'rental_fuel_type',
                'rental_def_gallons'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->after('rental_prepaid_fuel', function ($table) {
                $table->decimal('rental_fuel_gallons')->nullable();
                $table->enum('rental_fuel_type',['Diesel','Gas'])->nullable();
                $table->decimal('rental_def_gallons')->nullable();
            });

        });
    }
};
