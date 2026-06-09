<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_category_comparison_keys', function (Blueprint $table) {
            if (! Schema::hasColumn('equipment_category_comparison_keys', 'upgrade_exceeds_value')) {
                $table->boolean('upgrade_exceeds_value')->default(true)->after('is_required');
            }

            if (! Schema::hasColumn('equipment_category_comparison_keys', 'caution_if_change_value')) {
                $table->boolean('caution_if_change_value')->default(true)->after('upgrade_exceeds_value');
            }

            if (! Schema::hasColumn('equipment_category_comparison_keys', 'upgrade_is_below_value')) {
                $table->boolean('upgrade_is_below_value')->default(false)->after('caution_if_change_value');
            }

            if (! Schema::hasColumn('equipment_category_comparison_keys', 'caution_if_below_value')) {
                $table->boolean('caution_if_below_value')->default(false)->after('upgrade_is_below_value');
            }
        });
    }

    public function down(): void
    {
        Schema::table('equipment_category_comparison_keys', function (Blueprint $table) {
            foreach ([
                'upgrade_exceeds_value',
                'caution_if_change_value',
                'upgrade_is_below_value',
                'caution_if_below_value',
            ] as $column) {
                if (Schema::hasColumn('equipment_category_comparison_keys', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
