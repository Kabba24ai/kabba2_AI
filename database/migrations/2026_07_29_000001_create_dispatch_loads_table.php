<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dispatch "combined load" grouping.
 *
 * A load groups several order_product LEGS that a dispatcher wants delivered (or
 * picked up) together on one driver/truck — both line items within one order and
 * items across different orders bound for similar addresses. It is a planning /
 * visual grouping only: each member order_product keeps its own priority,
 * checklist, equipment and completes individually. A load is single-leg (all
 * delivery legs OR all return legs) and belongs to at most one driver.
 *
 * Membership is expressed by the leg-specific FK on order_products
 * (delivery_load_id for a delivery-load, pickup_load_id for a return-load),
 * mirroring the existing per-leg delivery / pickup column pattern.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_loads', function (Blueprint $table) {
            $table->id();
            $table->uuid('unique_id')->unique();
            $table->string('leg'); // 'delivery' | 'return'
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('label')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['driver_id', 'leg']);
        });

        Schema::table('order_products', function (Blueprint $table) {
            $table->foreignId('delivery_load_id')->nullable()->after('delivery_priority_locked')
                ->constrained('dispatch_loads')->nullOnDelete();
            $table->foreignId('pickup_load_id')->nullable()->after('pickup_priority_locked')
                ->constrained('dispatch_loads')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('delivery_load_id');
            $table->dropConstrainedForeignId('pickup_load_id');
        });

        Schema::dropIfExists('dispatch_loads');
    }
};
