<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds three clean refund-tracking columns to order_payments.
 *
 * refunded_at       — when the refund was processed (permanent replacement for the
 *                     COALESCE(payment_datetime, created_at) proxy used in Phase 0).
 * tax_refunded      — the tax portion returned to the customer (currently untracked).
 * gateway_refund_id — the gateway's refund transaction ID, separated from transaction_id
 *                     which currently stores both original charge IDs and refund IDs.
 *
 * transaction_id behavior is intentionally NOT changed here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_payments', function (Blueprint $table) {
            $table->timestamp('refunded_at')->nullable()->after('payment_datetime');
            $table->decimal('tax_refunded', 10, 2)->default(0)->after('refund_amount');
            $table->string('gateway_refund_id')->nullable()->after('transaction_id');
        });

        // Backfill refunded_at from payment_datetime for existing refund records.
        // payment_datetime was already set to now() at refund creation by RefundPaymentController,
        // so it is the most accurate refund timestamp available in historical data.
        DB::statement("
            UPDATE order_payments
            SET    refunded_at = payment_datetime
            WHERE  status IN ('Refunded', 'Partial Refund')
              AND  payment_datetime IS NOT NULL
              AND  refunded_at IS NULL
        ");

        // For any refund records where payment_datetime was not set, fall back to created_at.
        DB::statement("
            UPDATE order_payments
            SET    refunded_at = created_at
            WHERE  status IN ('Refunded', 'Partial Refund')
              AND  refunded_at IS NULL
        ");

        // Backfill gateway_refund_id from transaction_id for existing refund records.
        // transaction_id currently stores the gateway refund ID on refund-status rows.
        // Copying here lets us migrate cleanly to a dedicated field without changing
        // transaction_id's behavior.
        DB::statement("
            UPDATE order_payments
            SET    gateway_refund_id = transaction_id
            WHERE  status IN ('Refunded', 'Partial Refund')
              AND  transaction_id IS NOT NULL
              AND  gateway_refund_id IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('order_payments', function (Blueprint $table) {
            $table->dropColumn(['refunded_at', 'tax_refunded', 'gateway_refund_id']);
        });
    }
};
