<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_critical_matching_criteria', function (Blueprint $table) {
            if (!Schema::hasColumn('equipment_critical_matching_criteria', 'source_type')) {
                $table->string('source_type', 20)->default('manual')->after('unit');
            }

            if (!Schema::hasColumn('equipment_critical_matching_criteria', 'is_key_criteria')) {
                $table->boolean('is_key_criteria')->default(true)->after('is_active');
                $table->index(['product_category_id', 'is_active', 'is_key_criteria'], 'eccmc_category_active_key_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('equipment_critical_matching_criteria', function (Blueprint $table) {
            if (Schema::hasColumn('equipment_critical_matching_criteria', 'is_key_criteria')) {
                $table->dropIndex('eccmc_category_active_key_idx');
                $table->dropColumn('is_key_criteria');
            }

            if (Schema::hasColumn('equipment_critical_matching_criteria', 'source_type')) {
                $table->dropColumn('source_type');
            }
        });
    }
};
