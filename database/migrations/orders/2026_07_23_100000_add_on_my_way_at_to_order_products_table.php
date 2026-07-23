<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Queue Line enhancement (2026-07-23): the actual departure trigger.
 *
 * "Ready to Go" is the driver's PREP action (fuel, keys, attachments) — the
 * truck has not left the yard. "On My Way" is when the driver loads the map
 * and drives off. Queue Line completion now fires on that departure, so we
 * stamp a dedicated timestamp mirroring the existing ready_to_go_at/arrived_at
 * pattern for both the delivery and pickup legs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->timestamp('delivery_on_my_way_at')->nullable()->after('delivery_ready_to_go_at');
            $table->timestamp('pickup_on_my_way_at')->nullable()->after('pickup_ready_to_go_at');
        });
    }

    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_on_my_way_at',
                'pickup_on_my_way_at',
            ]);
        });
    }
};
