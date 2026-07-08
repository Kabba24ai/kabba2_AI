<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 3.1 — Customer Credit Administration.
     *
     * Additive only — no existing column is changed or removed. These two
     * fields are needed for the Grant Credit dialog's "Effective Date" and
     * "Internal Comments" fields, per that phase's mission. `effective_date`
     * is nullable and purely informational in this phase (it does not
     * backdate `created_at` or change ledger ordering) — see
     * CustomerCreditService's docblock for why.
     */
    public function up(): void
    {
        Schema::table('customer_credits', function (Blueprint $table) {
            $table->date('effective_date')->nullable()->after('reason');
            $table->text('internal_comments')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('customer_credits', function (Blueprint $table) {
            $table->dropColumn(['effective_date', 'internal_comments']);
        });
    }
};
