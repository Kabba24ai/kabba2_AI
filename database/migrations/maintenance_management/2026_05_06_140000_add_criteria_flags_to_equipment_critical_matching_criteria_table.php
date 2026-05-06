<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_critical_matching_criteria', function (Blueprint $table) {
            if (!Schema::hasColumn('equipment_critical_matching_criteria', 'upgrade_exceeds_value')) {
                $table->boolean('upgrade_exceeds_value')->default(true)->after('default_weight');
            }
            if (!Schema::hasColumn('equipment_critical_matching_criteria', 'caution_if_change_value')) {
                $table->boolean('caution_if_change_value')->default(true)->after('upgrade_exceeds_value');
            }
        });
    }

    public function down(): void
    {
        Schema::table('equipment_critical_matching_criteria', function (Blueprint $table) {
            foreach (['upgrade_exceeds_value', 'caution_if_change_value'] as $col) {
                if (Schema::hasColumn('equipment_critical_matching_criteria', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
