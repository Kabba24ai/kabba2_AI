<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Field Service dispatch routing: the explicit departure origin the dispatcher
 * chose and an immutable snapshot of the route computed at creation. The origin
 * and its normalized address/coordinates are snapshotted so the saved dispatch
 * plan is preserved verbatim even if a store's address later changes.
 *
 * estimated_departure_at (Departure Time) and estimated_arrival_at (Expected
 * Arrival) already exist on the table and are reused.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('field_service_tickets', function (Blueprint $table) {
            // Explicit departure-location decision — no default. 'store' | 'other'.
            $table->string('departure_location_type')->nullable()->after('truck_id');
            $table->foreignId('departure_store_id')->nullable()->after('departure_location_type')
                ->constrained('stores')->nullOnDelete();

            // Structured "Other" origin snapshot (only when type = 'other').
            $table->string('departure_street')->nullable()->after('departure_store_id');
            $table->string('departure_line2')->nullable()->after('departure_street');
            $table->string('departure_city')->nullable()->after('departure_line2');
            $table->string('departure_state')->nullable()->after('departure_city');
            $table->string('departure_zip')->nullable()->after('departure_state');

            // Immutable route snapshot from the shared routing layer.
            $table->string('route_origin_label')->nullable()->after('departure_zip');
            $table->string('route_destination_label')->nullable()->after('route_origin_label');
            $table->decimal('route_origin_latitude', 10, 7)->nullable()->after('route_destination_label');
            $table->decimal('route_origin_longitude', 10, 7)->nullable()->after('route_origin_latitude');
            $table->decimal('route_destination_latitude', 10, 7)->nullable()->after('route_origin_longitude');
            $table->decimal('route_destination_longitude', 10, 7)->nullable()->after('route_destination_latitude');
            $table->unsignedInteger('route_distance_meters')->nullable()->after('route_destination_longitude');
            $table->unsignedInteger('route_duration_seconds')->nullable()->after('route_distance_meters');
            $table->unsignedInteger('route_traffic_duration_seconds')->nullable()->after('route_duration_seconds');
            $table->string('route_status')->nullable()->after('route_traffic_duration_seconds');
            $table->string('route_provider')->nullable()->after('route_status');
            $table->dateTime('route_calculated_at')->nullable()->after('route_provider');
        });
    }

    public function down(): void
    {
        Schema::table('field_service_tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('departure_store_id');
            $table->dropColumn([
                'departure_location_type',
                'departure_street', 'departure_line2', 'departure_city', 'departure_state', 'departure_zip',
                'route_origin_label', 'route_destination_label',
                'route_origin_latitude', 'route_origin_longitude',
                'route_destination_latitude', 'route_destination_longitude',
                'route_distance_meters', 'route_duration_seconds', 'route_traffic_duration_seconds',
                'route_status', 'route_provider', 'route_calculated_at',
            ]);
        });
    }
};
