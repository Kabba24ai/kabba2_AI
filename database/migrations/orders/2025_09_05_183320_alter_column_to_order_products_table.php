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
        Schema::table('order_products', function (Blueprint $table) {
            $table->after('pickup_notes', function (Blueprint $table) {
                $table->string('start_hours')->nullable();
                $table->string('end_hours')->nullable();
                $table->string('total_charge')->nullable();
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->dropColumn(['start_hours', 'end_hours', 'total_charge']);
        });
    }
};
