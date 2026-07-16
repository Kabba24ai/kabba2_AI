<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3C — Employee-Selected Refund Sources and Multi-Source Refund
 * Processing.
 *
 * A multi-source refund can now partially fail (some card allocations
 * succeed, others are declined), so a refund row's existing OrderPaymentStatus
 * (PartialRefund / Refund / Failed) is no longer enough on its own to
 * describe what happened to THIS refund attempt — that column already
 * carries a different meaning (the ORDER's aggregate refund completeness).
 *
 * refund_operation_status is a small, additive, nullable field that
 * answers a narrower question: did every allocation this refund row
 * attempted actually succeed? It is meaningless (and left null) for any
 * row that is not itself a refund/partial-refund row — deliberately not
 * reused on ordinary payment rows, so it can never be misread as a
 * transaction status on a charge.
 *
 * Values (see App\Enums\Orders\RefundOperationStatus): pending,
 * partially_completed, completed, failed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_payments', function (Blueprint $table) {
            $table->string('refund_operation_status')->nullable()->after('refund_calculation_type');
        });
    }

    public function down(): void
    {
        Schema::table('order_payments', function (Blueprint $table) {
            $table->dropColumn('refund_operation_status');
        });
    }
};
