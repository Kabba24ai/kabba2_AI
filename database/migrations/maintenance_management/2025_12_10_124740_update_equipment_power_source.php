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
        Schema::table('equipment', function (Blueprint $table) {
               // Update enum to include electric
            $table->enum('power_source_type', ['diesel', 'gas', 'batteries', 'electric'])->nullable()->change();
 // Add new nullable fields for electric
            $table->text('volts')->nullable()->after('power_source_type'); // store comma-separated volts (e.g., "110V,220V")
            $table->text('amps')->nullable()->after('volts'); // store comma-separated amps (e.g., "5A,10A")

        });
    }

    /**
     * Reverse the migrations.
     */
     public function down()
    {
        Schema::table('equipment', function (Blueprint $table) {
            // revert enum to original
            $table->enum('power_source_type', ['diesel', 'gas', 'batteries'])->nullable()->change();

            // drop electric fields
            $table->dropColumn(['volts', 'amps']);
        });
    }
};
