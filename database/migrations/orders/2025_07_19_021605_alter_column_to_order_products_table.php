<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add a temporary column with new enum values
        Schema::table('order_products', function (Blueprint $table) {
            $table->enum('service_option_tmp', ['Delivery + Pickup', 'Delivery Only', 'Return Only'])->nullable()->after('service_option');
        });

        // 2. Map and migrate data to the new column
        DB::table('order_products')->update([
            'service_option_tmp' => DB::raw("
                CASE
                    WHEN service_option = 'Delivery + Pickup' THEN 'Delivery + Pickup'
                    WHEN service_option = 'Delivery + Return' THEN 'Delivery Only'
                    WHEN service_option = 'Pickup + Return' THEN 'Return Only'
                    WHEN service_option = 'Delivery Only' THEN 'Delivery Only'
                    WHEN service_option = 'Return Only' THEN 'Return Only'
                    ELSE NULL
                END
            ")
        ]);

        // 3. Drop the old column
        Schema::table('order_products', function (Blueprint $table) {
            $table->dropColumn('service_option');
        });

        // 4. Rename the new column to the original name
        Schema::table('order_products', function (Blueprint $table) {
            $table->renameColumn('service_option_tmp', 'service_option');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Add the old column back with old enum values
        Schema::table('order_products', function (Blueprint $table) {
            $table->enum('service_option_tmp', ['Delivery + Pickup', 'Delivery + Return', 'Pickup + Return'])->nullable()->after('service_option');
        });

        // 2. Map new values back to old if possible
        DB::table('order_products')->update([
            'service_option_tmp' => DB::raw("
                CASE
                    WHEN service_option = 'Delivery + Pickup' THEN 'Delivery + Pickup'
                    WHEN service_option = 'Delivery Only' THEN 'Delivery + Return'
                    WHEN service_option = 'Return Only' THEN 'Pickup + Return'
                    ELSE NULL
                END
            ")
        ]);

        // 3. Drop the new column
        Schema::table('order_products', function (Blueprint $table) {
            $table->dropColumn('service_option');
        });

        // 4. Rename the tmp column back to original name
        Schema::table('order_products', function (Blueprint $table) {
            $table->renameColumn('service_option_tmp', 'service_option');
        });
    }
};

