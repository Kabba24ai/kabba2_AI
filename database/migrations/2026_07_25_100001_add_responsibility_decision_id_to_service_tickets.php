<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Stage 1 of retiring the hard-coded responsibility_decision enum column.
 *
 * Adds the canonical FK service_tickets.responsibility_decision_id →
 * service_responsibility_decisions and backfills it from the existing string
 * column by matching on the master `key` (which equals the legacy enum value).
 * "pending" tickets map to NULL — Pending is the absence of a decision, not a
 * master record.
 *
 * The legacy `responsibility_decision` string column is RETAINED as a
 * historical key snapshot and transitional safety net; it is dual-written by
 * ServiceTicket::decideResponsibility() going forward and will be retired in a
 * later migration only after all consumers are verified in production.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('service_tickets', function (Blueprint $table) {
            $table->foreignId('responsibility_decision_id')
                ->nullable()
                ->after('responsibility_decision')
                ->constrained('service_responsibility_decisions')
                ->nullOnDelete();
            $table->index('responsibility_decision_id');
        });

        // Backfill from the legacy key. `key` is a reserved word — the query
        // builder quotes identifiers, so the join is safe.
        DB::table('service_tickets as t')
            ->join('service_responsibility_decisions as r', 'r.key', '=', 't.responsibility_decision')
            ->whereNotNull('t.responsibility_decision')
            ->where('t.responsibility_decision', '<>', 'pending')
            ->update(['t.responsibility_decision_id' => DB::raw('r.id')]);
    }

    public function down(): void
    {
        Schema::table('service_tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('responsibility_decision_id');
        });
    }
};
