<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Service Module cleanup: drop the redundant scalar `internal_notes` column.
 * ServiceTicketNote (service_ticket_notes) is the single canonical Service note
 * mechanism; a competing free-text column on the ticket was never wired to the
 * workbench note flow. Service-only, no cross-module dependency.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_tickets', function (Blueprint $table) {
            $table->dropColumn('internal_notes');
        });
    }

    public function down(): void
    {
        Schema::table('service_tickets', function (Blueprint $table) {
            $table->text('internal_notes')->nullable()->after('repair_summary');
        });
    }
};
