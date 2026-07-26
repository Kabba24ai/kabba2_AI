<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Field Service consolidation: link every field mission to a canonical
 * service_tickets row so it appears on the Operations Board like all other
 * service work, while the specialized dispatch/route/on-site workflow stays in
 * the field_service_tickets record. Nullable — the two are created together at
 * intake, but the mission remains valid on its own if the companion is ever
 * detached.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('field_service_tickets', function (Blueprint $table) {
            $table->foreignId('service_ticket_id')
                ->nullable()
                ->after('mission_status')
                ->constrained('service_tickets')
                ->nullOnDelete();
            $table->index('service_ticket_id');
        });
    }

    public function down(): void
    {
        Schema::table('field_service_tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_ticket_id');
        });
    }
};
