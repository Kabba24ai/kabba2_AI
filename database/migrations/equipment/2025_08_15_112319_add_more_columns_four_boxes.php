<?php
/*
--------------------------------------------------------
Created By: Jignesh Parmar
--------------------------------------------------------
Details:
Following Columnsa are added in the existing table
power_source_type Varchar 255 NOT NULL
has_def (enum T or F) NULL
diesel_tank_capacity int NULL Default 0
gas_tank_capacity int NULL Default 0
standard_battery_count int NULL Default 0
expanded_battery_count int NULL Default 0
checklist_master Varchar 255  NOT NULL
equipment_parts_list Varchar 255 NOT NULL
*/

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
        Schema::table('equipments', function (Blueprint $table) {
            $table->string('power_source_type', 255)->after('equipment_service_list');
            $table->string('has_def', 10)->nullable()->after('power_source_type');
            $table->integer('diesel_tank_capacity')->nullable()->default(0)->after('has_def');
            $table->integer('gas_tank_capacity')->nullable()->default(0)->after('diesel_tank_capacity');
            $table->integer('standard_battery_count')->nullable()->default(0)->after('gas_tank_capacity');
            $table->integer('expanded_battery_count')->nullable()->default(0)->after('standard_battery_count');
            $table->string('checklist_master', 255)->after('expanded_battery_count');
            $table->string('equipment_parts_list', 255)->after('checklist_master');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipments', function (Blueprint $table) {
            $table->dropColumn([
                'power_source_type',
                'has_def',
                'diesel_tank_capacity',
                'gas_tank_capacity',
                'standard_battery_count',
                'expanded_battery_count',
                'checklist_master',
                'equipment_parts_list'
            ]);
        });
    }
};
