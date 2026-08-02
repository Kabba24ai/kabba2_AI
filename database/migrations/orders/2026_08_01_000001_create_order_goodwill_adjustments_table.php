<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Goodwill Adjustment audit record (FD-002, Truth Table type 17).
 *
 * A Goodwill Adjustment reduces an order's taxable basis pre-tax so the
 * revised grand total equals the payments accepted as payment in full. The
 * ORDER columns hold the current authoritative state; THIS TABLE is the
 * audit history — it preserves the pre-adjustment financial state so what
 * management approved can always be reconstructed.
 *
 * Money is decimal(10,2), matching orders/order_products and every other
 * money column in this schema. Integer cents govern computation inside the
 * Goodwill domain (FD-002 Monetary Precision Rule); decimal(10,2) governs
 * storage. MySQL DECIMAL is exact, and a lone BIGINT-cents column here would
 * diverge from every existing join and report for no precision gain.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_goodwill_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();

            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();

            $table->decimal('goodwill_amount', 10, 2);

            $table->string('reason_code');
            $table->text('reason_note')->nullable();

            // Both identities, per FD-002 §7.3: the employee who processed the
            // money and the authenticated manager who authorised reducing
            // revenue are not necessarily the same person, and receiving a
            // payment must never imply authority to waive one.
            $table->foreignId('approved_by')->constrained('users');
            $table->foreignId('performed_by')->constrained('users');

            // Pre-adjustment snapshot.
            $table->decimal('original_subtotal', 10, 2);
            $table->decimal('original_tax', 10, 2);
            $table->decimal('original_special_tax', 10, 2)->default(0);
            $table->decimal('original_grand_total', 10, 2);

            // Post-adjustment snapshot.
            $table->decimal('revised_subtotal', 10, 2);
            $table->decimal('revised_tax', 10, 2);
            $table->decimal('revised_special_tax', 10, 2)->default(0);
            $table->decimal('revised_grand_total', 10, 2);

            // Per-line before/after, so reversal is exact rather than recomputed.
            $table->json('line_allocations')->nullable();

            // Which basis and rates were used, and which named source they came
            // from, so a future reader never has to re-derive them from data
            // that may since have changed.
            $table->json('basis_snapshot')->nullable();

            // Payment state either side of the adjustment.
            $table->decimal('total_paid_before', 10, 2);
            $table->decimal('total_paid_after', 10, 2);
            $table->string('payment_status_before')->nullable();
            $table->string('payment_status_after')->nullable();

            // The real tender recorded alongside. Null when Goodwill is applied
            // without a new payment in the same operation.
            $table->foreignId('order_payment_id')->nullable()->constrained('order_payments')->nullOnDelete();

            // The receipt this adjustment superseded, if one had been issued.
            $table->unsignedBigInteger('superseded_receipt_id')->nullable();

            // Matches order_payments.idempotency_token's convention: a retry
            // must never produce a second payment or a second adjustment.
            $table->string('idempotency_token')->nullable()->unique();

            // Reversal is recorded, never deleted.
            $table->timestamp('reversed_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users');
            $table->text('reversal_reason')->nullable();

            $table->timestamps();

            // order_id is already indexed by the foreign key; this composite
            // serves the hot lookup — "is there an ACTIVE adjustment on this
            // order?" — which both the resolver's ambiguity guard and the
            // one-active-adjustment invariant ask on every resolution.
            $table->index(['order_id', 'reversed_at']);
        });
    }

    /**
     * ONE-WAY DOOR IN PRODUCTION.
     *
     * Goodwill mutates orders/order_products in place; this table is the only
     * record of the pre-adjustment state. Dropping it does not restore those
     * orders — it destroys the ability to reverse any applied adjustment, and
     * leaves the reduced totals standing with no audit trail explaining them.
     * Safe on a fresh test database; never run against data that has adjustments.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_goodwill_adjustments');
    }
};
