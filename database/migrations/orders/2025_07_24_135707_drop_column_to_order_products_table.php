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
        Schema::table('order_products', function (Blueprint $table) {
            $table->dropColumn([
                'schedule_start_date',
                'schedule_end_date',
                'is_delivery',
                'is_pickup_return'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->date('schedule_start_date')->nullable()->after('total');
            $table->date('schedule_end_date')->nullable()->after('schedule_start_date');
            $table->boolean('is_delivery')->default(false)->after('distance_range');
            $table->boolean('is_pickup_return')->default(false)->after('delivery_by');
        });
    }
};
