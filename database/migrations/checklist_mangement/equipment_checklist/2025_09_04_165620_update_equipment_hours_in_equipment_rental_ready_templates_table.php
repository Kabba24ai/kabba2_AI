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
        Schema::table('equipment_rental_ready_templates', function (Blueprint $table) {
            // Change from integer → decimal(8,1)
            $table->decimal('equipment_hours', 8, 1)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipment_rental_ready_templates', function (Blueprint $table) {
            // Roll back to integer (default 0 as before)
            $table->integer('equipment_hours')->default(0)->change();
        });
    }
};
