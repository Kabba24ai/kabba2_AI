<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->boolean('delivery_driver_locked')->default(false)->after('delivery_priority');
            $table->boolean('delivery_priority_locked')->default(false)->after('delivery_driver_locked');
            $table->boolean('pickup_driver_locked')->default(false)->after('pickup_priority');
            $table->boolean('pickup_priority_locked')->default(false)->after('pickup_driver_locked');
        });
    }

    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_driver_locked',
                'delivery_priority_locked',
                'pickup_driver_locked',
                'pickup_priority_locked',
            ]);
        });
    }
};
