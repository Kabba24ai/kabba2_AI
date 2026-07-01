<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── Backfill paid_at ────────────────────────────────────────────────
        //
        // Priority 1: extension charges linked to a child order — use the child
        // order's paid order_payment.payment_datetime as the canonical paid date.
        DB::statement("
            UPDATE billing_charges bc
            INNER JOIN (
                SELECT op.order_id, MAX(op.payment_datetime) AS paid_at
                FROM order_payments op
                WHERE op.status = 'Paid'
                GROUP BY op.order_id
            ) payments ON payments.order_id = bc.child_order_id
            SET bc.paid_at = payments.paid_at
            WHERE bc.status = 'paid'
              AND bc.child_order_id IS NOT NULL
              AND bc.paid_at IS NULL
        ");

        // Priority 2: all remaining paid records with no child order — fall back
        // to updated_at (the timestamp when markPaid() last saved the record).
        // This is a best-effort approximation for dashboard fuel/damage charges.
        DB::statement("
            UPDATE billing_charges
            SET paid_at = updated_at
            WHERE status = 'paid'
              AND paid_at IS NULL
        ");

        // ── Backfill store_id ───────────────────────────────────────────────
        //
        // Derive from parent order's first order_product.delivery_store_id.
        // Uses MIN() so the result is deterministic for multi-product orders.
        // NULL is kept when no delivery_store_id can be found (e.g. pickup-only).
        DB::statement("
            UPDATE billing_charges bc
            INNER JOIN (
                SELECT op.order_id, MIN(op.delivery_store_id) AS store_id
                FROM order_products op
                WHERE op.delivery_store_id IS NOT NULL
                  AND op.deleted_at IS NULL
                GROUP BY op.order_id
            ) stores ON stores.order_id = bc.parent_order_id
            SET bc.store_id = stores.store_id
            WHERE bc.store_id IS NULL
              AND bc.parent_order_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        // Nulling these out is safe — the columns themselves are dropped by
        // the companion migration's down() method when rolled back together.
        DB::statement("UPDATE billing_charges SET paid_at = NULL, store_id = NULL");
    }
};
