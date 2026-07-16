<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3B — Refund Allocation Foundation.
 *
 * The durable record of which original settled payment funded a given
 * refund, and how much. Today's refund flow (Phase 3A) can only ever
 * target one unambiguous original payment, so one refund produces exactly
 * one allocation row — the schema itself does not enforce that 1:1
 * cardinality (no unique constraint on refund_order_payment_id) because a
 * future multi-source refund is expected to create several allocation rows
 * against the same refund.
 *
 * original_order_payment_id is restrictOnDelete(): an original payment with
 * allocations recorded against it can never be deleted out from under the
 * audit trail. refund_order_payment_id is cascadeOnDelete(): deleting a
 * refund row (soft-delete on order_payments does not fire this — only a
 * genuine hard delete does) takes its own allocation rows with it, since
 * they have no meaning without the refund they describe.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_payment_refund_allocations', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('refund_order_payment_id');
            $table->unsignedBigInteger('original_order_payment_id');

            $table->decimal('allocated_amount', 10, 2);
            $table->decimal('allocated_base_amount', 10, 2);
            $table->decimal('allocated_tax_amount', 10, 2);
            $table->decimal('processing_fee_retained', 10, 2)->nullable();

            $table->string('gateway_transaction_id')->nullable();
            $table->string('status', 20);
            $table->string('failure_reason')->nullable();

            $table->timestamps();

            $table->foreign('refund_order_payment_id')
                ->references('id')->on('order_payments')
                ->cascadeOnDelete();

            // Explicit names: the auto-generated identifiers for this table
            // exceed MySQL's 64-character limit and fail on any fresh migrate
            $table->foreign('original_order_payment_id', 'opra_original_payment_fk')
                ->references('id')->on('order_payments')
                ->restrictOnDelete();

            $table->index(['original_order_payment_id', 'status'], 'opra_original_payment_status_idx');
            $table->index('refund_order_payment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_payment_refund_allocations');
    }
};
