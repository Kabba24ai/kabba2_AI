<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->dropColumn([
                'equipment_fuel',
                'equipment_key_location',
                'equipment_driver_status',
            ]);

            $table->string('delivery_equipment_fuel')->nullable()->after('dispatch_checklist');
            $table->string('delivery_equipment_key_location')->nullable()->after('delivery_equipment_fuel');
            $table->string('delivery_equipment_driver_status')->nullable()->after('delivery_equipment_key_location');

            $table->string('pickup_equipment_fuel')->nullable()->after('delivery_equipment_driver_status');
            $table->string('pickup_equipment_key_location')->nullable()->after('pickup_equipment_fuel');
            $table->string('pickup_equipment_driver_status')->nullable()->after('pickup_equipment_key_location');

            $table->timestamp('delivery_ready_to_go_at')->nullable()->after('pickup_equipment_driver_status');
            $table->timestamp('delivery_arrived_at')->nullable()->after('delivery_ready_to_go_at');
            $table->boolean('delivery_is_delivered')->default(false)->after('delivery_arrived_at');
            $table->boolean('delivery_is_arrived')->default(false)->after('delivery_is_delivered');

            $table->timestamp('pickup_ready_to_go_at')->nullable()->after('delivery_is_arrived');
            $table->timestamp('pickup_arrived_at')->nullable()->after('pickup_ready_to_go_at');
            $table->boolean('pickup_is_delivered')->default(false)->after('pickup_arrived_at');
            $table->boolean('pickup_is_arrived')->default(false)->after('pickup_is_delivered');
        });
    }

    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_equipment_fuel',
                'delivery_equipment_key_location',
                'delivery_equipment_driver_status',
                'pickup_equipment_fuel',
                'pickup_equipment_key_location',
                'pickup_equipment_driver_status',
                'delivery_ready_to_go_at',
                'delivery_arrived_at',
                'delivery_is_delivered',
                'delivery_is_arrived',
                'pickup_ready_to_go_at',
                'pickup_arrived_at',
                'pickup_is_delivered',
                'pickup_is_arrived',
            ]);

            $table->string('equipment_fuel')->nullable()->after('dispatch_checklist');
            $table->string('equipment_key_location')->nullable()->after('equipment_fuel');
            $table->string('equipment_driver_status')->nullable()->after('equipment_key_location');
        });
    }
};
