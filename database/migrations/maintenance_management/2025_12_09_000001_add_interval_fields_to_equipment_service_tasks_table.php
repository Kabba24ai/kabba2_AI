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
        Schema::table('equipment_service_tasks', function (Blueprint $table) {
            $table->integer('interval_value')->nullable()->after('service_task_id');
            $table->enum('interval_type', ['hour', 'date'])->default('hour')->after('interval_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipment_service_tasks', function (Blueprint $table) {
            $table->dropColumn(['interval_value', 'interval_type']);
        });
    }
};
