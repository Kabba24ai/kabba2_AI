<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_critical_matching_criteria', function (Blueprint $table) {
            if (!Schema::hasColumn('equipment_critical_matching_criteria', 'upgrade_is_below_value')) {
                $table->boolean('upgrade_is_below_value')->default(false)->after('caution_if_change_value');
            }

            if (!Schema::hasColumn('equipment_critical_matching_criteria', 'caution_if_below_value')) {
                $table->boolean('caution_if_below_value')->default(false)->after('upgrade_is_below_value');
            }
        });
    }

    public function down(): void
    {
        Schema::table('equipment_critical_matching_criteria', function (Blueprint $table) {
            foreach (['upgrade_is_below_value', 'caution_if_below_value'] as $column) {
                if (Schema::hasColumn('equipment_critical_matching_criteria', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
