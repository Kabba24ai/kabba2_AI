<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Receipt supersession — a receipt is never edited, only replaced.
 *
 * `ReceiptService::getOrCreateReceipt()` freezes an order's totals into a
 * receipt row and thereafter returns that row without ever refreshing it. Any
 * later change to the order — a Goodwill Adjustment, or its reversal — left
 * the receipt permanently current and permanently wrong, which is why the
 * Goodwill writer refused orders that already had one (FD-002 Am.5).
 *
 * The fix is not to update the receipt. A receipt is a document that was
 * handed to a customer; rewriting it destroys the evidence of what they were
 * originally told. Instead a NEW receipt is issued that points back at the one
 * it replaces, and the newest receipt for an order is the current one.
 * Historical rows and their items are never touched.
 *
 * Columns:
 *   - `superseded_receipt_id` — the receipt THIS one replaces. Null on an
 *     original. Self-referential, so the chain is walkable in both directions.
 *   - `goodwill_adjustment_id` — why it was reissued.
 *   - `goodwill_amount` — the concession shown on the document.
 *
 * The identity every receipt satisfies, superseding or not:
 *
 *     subtotal − goodwill_amount + sales_tax = total
 *
 * On an ordinary receipt `goodwill_amount` is 0 and this reduces to the
 * existing `subtotal + sales_tax = total`, so no existing row changes meaning.
 * On a superseding receipt `subtotal` stays the ORIGINAL figure — it describes
 * the goods that were supplied, which the concession did not change — and the
 * reduction is stated on its own line rather than silently folded into the
 * merchandise total.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->foreignId('superseded_receipt_id')
                ->nullable()
                ->after('order_id')
                ->constrained('receipts')
                ->nullOnDelete();

            $table->foreignId('goodwill_adjustment_id')
                ->nullable()
                ->after('superseded_receipt_id')
                ->constrained('order_goodwill_adjustments')
                ->nullOnDelete();

            $table->decimal('goodwill_amount', 12, 2)->default(0)->after('sales_tax');
        });
    }

    /**
     * Dropping these collapses the supersession chain: every receipt in an
     * order's history becomes indistinguishable from an original, and
     * "which one is current?" degrades to "the newest row", which is only
     * accidentally correct. Reverse only if no adjustment has ever superseded
     * a receipt.
     */
    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('superseded_receipt_id');
            $table->dropConstrainedForeignId('goodwill_adjustment_id');
            $table->dropColumn('goodwill_amount');
        });
    }
};
