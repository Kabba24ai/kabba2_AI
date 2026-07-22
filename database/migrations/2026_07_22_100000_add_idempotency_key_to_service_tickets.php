<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ST-1 canonical ticket intake: a nullable, unique idempotency key so the
 * one programmatic creation path (ServiceTicketIntakeService) can dedupe a
 * double-submit or a repeated call from a future module (Customer Damage)
 * — a resubmitted key returns the existing ticket instead of minting a
 * second. Nullable so legacy/keyless creates are unaffected; unique still
 * enforced for real values (same pattern as customer_credits.idempotency_key).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_tickets', function (Blueprint $table) {
            $table->string('idempotency_key')->nullable()->unique()->after('ticket_number');
        });
    }

    public function down(): void
    {
        Schema::table('service_tickets', function (Blueprint $table) {
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn('idempotency_key');
        });
    }
};
