<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Models
use App\Helpers\ModelHelper;
use App\Models\Orders\OrderProduct;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Add unique_id column (nullable, no unique yet)
        Schema::table('order_products', function (Blueprint $table) {

            $table->string('unique_id')->nullable()->after('id');
            $table->after('distance_range', function (Blueprint $table) {
                $table->boolean('is_delivery')->default(false);
                $table->enum('delivery_status', ['Pending', 'Completed', 'Reschedule'])->default('Pending');
                $table->enum('delivery_type', ['Store', 'Truck'])->nullable();
                $table->unsignedBigInteger('delivery_store_id')->nullable();
                $table->date('delivery_date')->nullable();
                $table->time('delivery_time')->nullable();
                $table->unsignedBigInteger('delivery_by')->nullable();
                $table->boolean('is_pickup_return')->default(false);
                $table->enum('pickup_status', ['Pending', 'Completed', 'Reschedule'])->default('Pending');
                $table->enum('pickup_type', ['Store', 'Truck'])->nullable();
                $table->unsignedBigInteger('pickup_store_id')->nullable();
                $table->date('pickup_date')->nullable();
                $table->time('pickup_time')->nullable();
                $table->unsignedBigInteger('pickup_by')->nullable();


                $table->foreign('delivery_store_id')->references('id')->on('stores')->nullOnDelete()->cascadeOnUpdate();
                $table->foreign('pickup_store_id')->references('id')->on('stores')->nullOnDelete()->cascadeOnUpdate();
                $table->foreign('delivery_by')->references('id')->on('users')->nullOnDelete()->cascadeOnUpdate();
                $table->foreign('pickup_by')->references('id')->on('users')->nullOnDelete()->cascadeOnUpdate();
            });
        });

        // Step 2: Fill unique_id for all existing rows, using your ModelHelper method!
        OrderProduct::whereNull('unique_id')->orWhere('unique_id', '')->get()->each(function($orderProduct) {
            $orderProduct->unique_id = ModelHelper::generateUniqueID($orderProduct, 'ORD-SCH');
            $orderProduct->save();
        });

        // Step 3: Make unique_id NOT NULL and unique
        Schema::table('order_products', function (Blueprint $table) {
            $table->string('unique_id')->unique()->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            // // Drop foreign keys first
            $table->dropForeign(['delivery_store_id']);
            $table->dropForeign(['pickup_store_id']);
            $table->dropForeign(['delivery_by']);
            $table->dropForeign(['pickup_by']);
            $table->dropUnique(['unique_id']);

            // Now drop columns
            $table->dropColumn([
                'unique_id',
                'is_delivery',
                'delivery_status',
                'delivery_type',
                'delivery_store_id',
                'delivery_date',
                'delivery_time',
                'delivery_by',
                'is_pickup_return',
                'pickup_status',
                'pickup_type',
                'pickup_store_id',
                'pickup_date',
                'pickup_time',
                'pickup_by'
            ]);
        });
    }
};
