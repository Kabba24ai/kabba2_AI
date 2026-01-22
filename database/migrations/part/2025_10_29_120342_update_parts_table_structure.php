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
        Schema::table('parts', function (Blueprint $table) {

            //  Drop old columns
            if (Schema::hasColumn('parts', 'category')) {
                $table->dropColumn('category');
            }
            if (Schema::hasColumn('parts', 'equipment_name')) {
                $table->dropColumn('equipment_name');
            }
            if (Schema::hasColumn('parts', 'equipment_id')) {
                $table->dropColumn('equipment_id');
            }

            //  Rename existing columns to match the new naming convention
            if (Schema::hasColumn('parts', 'part_number')) {
                $table->renameColumn('part_number', 'primary_part_number');
            }
            if (Schema::hasColumn('parts', 'unit_cost')) {
                $table->renameColumn('unit_cost', 'primary_part_cost');
            }
            if (Schema::hasColumn('parts', 'supplier')) {
                $table->renameColumn('supplier', 'primary_part_supplier_id');
            }

            //  Rename alt columns for clarity
            if (Schema::hasColumn('parts', 'part_number_alt_1')) {
                $table->renameColumn('part_number_alt_1', 'alt_1_part_number');
            }
            if (Schema::hasColumn('parts', 'cost_alt_1')) {
                $table->renameColumn('cost_alt_1', 'alt_1_part_cost');
            }
            if (Schema::hasColumn('parts', 'supplier_alt_1')) {
                $table->renameColumn('supplier_alt_1', 'alt_1_part_supplier_id');
            }

            if (Schema::hasColumn('parts', 'part_number_alt_2')) {
                $table->renameColumn('part_number_alt_2', 'alt_2_part_number');
            }
            if (Schema::hasColumn('parts', 'cost_alt_2')) {
                $table->renameColumn('cost_alt_2', 'alt_2_part_cost');
            }
            if (Schema::hasColumn('parts', 'supplier_alt_2')) {
                $table->renameColumn('supplier_alt_2', 'alt_2_part_supplier_id');
            }

            //  Add new columns if missing
            if (!Schema::hasColumn('parts', 'general_supply_item')) {
                $table->boolean('general_supply_item')->default(false)->after('dni');
            }

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            // Rollback renames
            if (Schema::hasColumn('parts', 'primary_part_number')) {
                $table->renameColumn('primary_part_number', 'part_number');
            }
            if (Schema::hasColumn('parts', 'primary_part_cost')) {
                $table->renameColumn('primary_part_cost', 'unit_cost');
            }
            if (Schema::hasColumn('parts', 'primary_part_supplier_id')) {
                $table->renameColumn('primary_part_supplier_id', 'supplier');
            }

            if (Schema::hasColumn('parts', 'alt_1_part_number')) {
                $table->renameColumn('alt_1_part_number', 'part_number_alt_1');
            }
            if (Schema::hasColumn('parts', 'alt_1_part_cost')) {
                $table->renameColumn('alt_1_part_cost', 'cost_alt_1');
            }
            if (Schema::hasColumn('parts', 'alt_1_part_supplier_id')) {
                $table->renameColumn('alt_1_part_supplier_id', 'supplier_alt_1');
            }

            if (Schema::hasColumn('parts', 'alt_2_part_number')) {
                $table->renameColumn('alt_2_part_number', 'part_number_alt_2');
            }
            if (Schema::hasColumn('parts', 'alt_2_part_cost')) {
                $table->renameColumn('alt_2_part_cost', 'cost_alt_2');
            }
            if (Schema::hasColumn('parts', 'alt_2_part_supplier_id')) {
                $table->renameColumn('alt_2_part_supplier_id', 'supplier_alt_2');
            }

            if (Schema::hasColumn('parts', 'general_supply_item')) {
                $table->dropColumn('general_supply_item');
            }
        });
    }
};
