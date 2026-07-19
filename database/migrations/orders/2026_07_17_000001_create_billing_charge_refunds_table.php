<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Billing Charge Refund Allocation — Safe Linked Refunds.
 *
 * The durable record of which BillingCharge a Customer Account refund
 * settled, and how much of that charge's base/tax it consumed. Mirrors
 * order_payment_refund_allocations' conventions (decimal(10,2) monetary
 * columns, explicit short constraint names, a bounded status column) — a
 * brand-new table with no prior partial-deployment history, so this
 * migration is a plain Schema::create(), not that migration's self-
 * repairing branch (that complexity exists there specifically because a
 * real server was found in a partially-migrated state; nothing has ever
 * run against this table yet).
 *
 * billing_charge_id is restrictOnDelete() — an audit trail row must never
 * be able to outlive the charge it describes being silently orphaned.
 * customer_account_id and responsible_person_id are nullOnDelete() — losing
 * either link degrades the row to a less-complete audit record rather than
 * blocking deletion of an unrelated ledger/user row.
 *
 * Additive only. No existing table is altered. No historical data is
 * touched or backfilled. down() drops only this table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_charge_refunds', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('billing_charge_id');
            $table->unsignedBigInteger('customer_account_id')->nullable();
            $table->unsignedBigInteger('responsible_person_id')->nullable();

            $table->decimal('base_amount', 10, 2);
            $table->decimal('tax_amount', 10, 2);
            $table->decimal('total_amount', 10, 2);

            $table->string('status', 20);
            $table->string('failure_reason')->nullable();
            $table->string('idempotency_key', 128)->nullable();

            $table->timestamps();

            $table->foreign('billing_charge_id', 'bcr_billing_charge_fk')
                ->references('id')->on('billing_charges')
                ->restrictOnDelete();

            $table->foreign('customer_account_id', 'bcr_customer_account_fk')
                ->references('id')->on('customer_accounts')
                ->nullOnDelete();

            $table->foreign('responsible_person_id', 'bcr_responsible_person_fk')
                ->references('id')->on('users')
                ->nullOnDelete();

            // Nullable + unique: MySQL permits any number of NULLs in a
            // unique index (NULL is never equal to NULL), so free-form
            // submissions with no idempotency token never collide with
            // each other — only a genuine repeated non-null key does.
            $table->unique('idempotency_key', 'bcr_idempotency_key_unique');

            $table->index(['billing_charge_id', 'status'], 'bcr_charge_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_charge_refunds');
    }
};
