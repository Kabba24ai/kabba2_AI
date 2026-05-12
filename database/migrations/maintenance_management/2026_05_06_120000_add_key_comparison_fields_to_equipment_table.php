<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            if (!Schema::hasColumn('equipment', 'similar_equipment_ids')) {
                $table->json('similar_equipment_ids')->nullable()->after('coi_submitted');
            }

            if (!Schema::hasColumn('equipment', 'critical_matching_criteria')) {
                $table->json('critical_matching_criteria')->nullable()->after('similar_equipment_ids');
            }

            if (!Schema::hasColumn('equipment', 'allow_upgrades')) {
                $table->boolean('allow_upgrades')->default(true)->after('critical_matching_criteria');
            }

            if (!Schema::hasColumn('equipment', 'allow_downgrades')) {
                $table->boolean('allow_downgrades')->default(false)->after('allow_upgrades');
            }

            if (!Schema::hasColumn('equipment', 'downgrade_requires_approval')) {
                $table->boolean('downgrade_requires_approval')->default(true)->after('allow_downgrades');
            }

            if (!Schema::hasColumn('equipment', 'equipment_key_comparison_notes')) {
                $table->text('equipment_key_comparison_notes')->nullable()->after('downgrade_requires_approval');
            }
        });
    }

    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $columns = [
                'similar_equipment_ids',
                'critical_matching_criteria',
                'allow_upgrades',
                'allow_downgrades',
                'downgrade_requires_approval',
                'equipment_key_comparison_notes',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('equipment', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
