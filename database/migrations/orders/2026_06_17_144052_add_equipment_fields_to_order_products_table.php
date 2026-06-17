<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->string('equipment_fuel')->nullable()->after('dispatch_checklist');
            $table->string('equipment_key_location')->nullable()->after('equipment_fuel');
            $table->string('equipment_driver_status')->nullable()->after('equipment_key_location');
        });
    }

    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->dropColumn(['equipment_fuel', 'equipment_key_location', 'equipment_driver_status']);
        });
    }
};
