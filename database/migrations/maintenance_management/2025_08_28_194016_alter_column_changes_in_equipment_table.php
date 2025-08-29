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
            $table->dropColumn([
                'rental_ready_checklist',
                'equipment_service_list',
                'equipment_parts_list',
                'tech_manager',
                'location',
                'service_interval',
                'current_hours',
                'last_service',
            ]);

            $table->dropIndex('equipments_category_status_index');
            $table->dropIndex('equipments_equipment_id_index');
            $table->dropIndex('equipments_status_index');

            $table->enum('has_def', ['Yes', 'No'])->default('No')->change();
            $table->enum('power_source_type', ['diesel', 'gas', 'batteries'])->nullable()->change();
            $table->decimal('diesel_tank_capacity', 10, 2)->nullable()->change();
            $table->decimal('def_tank_capacity', 10, 2)->nullable()->change();
            $table->decimal('gas_tank_capacity', 10, 2)->nullable()->change();
            $table->decimal('standard_battery_count', 10, 2)->nullable()->change();
            $table->decimal('expanded_battery_count', 10, 2)->nullable()->change();


            $table->renameColumn('category', 'product_category_id');
            $table->renameColumn('checklist_master', 'checklist_master_id');
            $table->renameColumn('status', 'current_status');
            $table->renameColumn('term', 'term_in_months');
            $table->renameColumn('cost', 'purchase_cost');
            $table->renameColumn('vin', 'vehicle_identification_number');
            $table->renameColumn('plate', 'license_plate');
            $table->renameColumn('rate', 'interest_rate');

            $table->string('unique_id')->change();
            $table->unsignedBigInteger('product_category_id')->nullable()->change();
            $table->unsignedBigInteger('checklist_master_id')->nullable()->change();
            $table->after('current_status', function (Blueprint $table) {
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
            });

            $table->foreign('product_category_id')->references('id')->on('product_categories')->onDelete('set null')->onUpdate('cascade');
            $table->foreign('checklist_master_id')->references('id')->on('checklist_masters')->onDelete('set null')->onUpdate('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null')->onUpdate('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropForeign(['product_category_id']);
            $table->dropForeign(['checklist_master_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);

            $table->dropColumn(['created_by', 'updated_by']);

            $table->after('power_source_type', function (Blueprint $table) {
                $table->string('rental_ready_checklist')->nullable();
                $table->string('equipment_service_list')->nullable();
                $table->string('equipment_parts_list')->nullable();
                $table->string('tech_manager')->nullable();
                $table->string('location')->nullable();
                $table->integer('service_interval')->nullable();
                $table->integer('current_hours')->nullable();
                $table->date('last_service')->nullable();
            });

            $table->string('has_def')->nullable()->change();
            $table->string('power_source_type')->nullable()->change();
            $table->integer('diesel_tank_capacity')->nullable()->change();
            $table->integer('def_tank_capacity')->nullable()->change();
            $table->integer('gas_tank_capacity')->nullable()->change();
            $table->integer('standard_battery_count')->nullable()->change();
            $table->integer('expanded_battery_count')->nullable()->change();

            $table->renameColumn('product_category_id', 'category');
            $table->renameColumn('checklist_master_id', 'checklist_master');
            $table->renameColumn('current_status', 'status');
            $table->renameColumn('term_in_months', 'term');
            $table->renameColumn('purchase_cost', 'cost');
            $table->renameColumn('vehicle_identification_number', 'vin');
            $table->renameColumn('license_plate', 'plate');
            $table->renameColumn('interest_rate', 'rate');

            $table->string('category')->nullable()->change();
            $table->string('checklist_master')->nullable()->change();
            $table->uuid('unique_id')->change();

            $table->index(['category', 'status'], 'equipments_category_status_index');
            $table->index('equipment_id', 'equipments_equipment_id_index');
            $table->index('status', 'equipments_status_index');
        });
    }
};
