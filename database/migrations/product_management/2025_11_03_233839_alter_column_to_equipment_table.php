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
            $table->after('equipment_hours', function (Blueprint $table) {
                $table->enum('is_tracked', ['Yes', 'No'])->nullable();
                $table->decimal('overage_rate', 10, 2)->nullable();
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropColumn(['hour_tracking', 'hour_rate']);
        });
    }
};
