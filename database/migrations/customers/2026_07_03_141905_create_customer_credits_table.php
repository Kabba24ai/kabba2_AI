<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 3.0 — CustomerCreditService foundation.
     *
     * This table is deliberately separate from `customer_accounts` — see
     * docs/financial-engine-consolidation/PHASE_3_0_PRE_IMPLEMENTATION_CHECKLIST.md
     * for why: CustomerCreditService manages customer assets, LedgerBalanceService
     * manages customer debt, and this phase is explicitly forbidden from
     * modifying LedgerBalanceService or blurring that boundary.
     *
     * One row per credit event (a grant or a redemption) — the same
     * "the ledger is the audit trail" shape `customer_accounts` already uses,
     * applied to a wholly separate asset ledger. Financial Credit only, in
     * this phase; there is no column distinguishing Financial from
     * Promotional Credit, since Promotional Credit does not exist yet and
     * that schema question is an explicitly open, deferred decision
     * (CUSTOMER_CREDIT_ARCHITECTURE.md §11) — not answered speculatively here.
     */
    public function up(): void
    {
        Schema::create('customer_credits', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();
            $table->unsignedBigInteger('customer_id');
            $table->enum('type', ['grant', 'redemption']);
            $table->decimal('amount', 15, 2);
            $table->string('reason');
            $table->string('idempotency_key')->nullable()->unique();
            $table->unsignedBigInteger('responsible_person_id')->nullable();
            $table->string('responsible_person_name')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('responsible_person_id')->references('id')->on('users')->onDelete('set null');

            $table->index(['customer_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_credits');
    }
};
