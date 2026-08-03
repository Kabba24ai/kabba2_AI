<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * First-class charge components on the receipt snapshot.
 *
 * `receipts` stored only `subtotal`, `sales_tax` and `total`. But `total` is
 * copied from `orders.grand_total`, which INCLUDES special tax, added fees and
 * any pre-tax discount — none of which had anywhere to live on the receipt. A
 * receipt for a $200 order with a $4.00 added fee printed
 *
 *     Subtotal 200.00 + Sales Tax 19.50 = Total 223.50
 *
 * which does not add up, on the document the customer actually keeps.
 *
 * WHY COLUMNS RATHER THAN LIVE ORDER READS. A receipt is a historical record
 * of what was presented at a moment in time. Rendering it from the order's
 * current values would make every past receipt silently restate itself
 * whenever the order changed. The components therefore get their own snapshot
 * fields, populated on the same paths that already maintain `subtotal`,
 * `sales_tax` and `total`.
 *
 * BACKFILL — DERIVED ONLY WHERE PROVABLE. An existing receipt's `total`
 * already contains these components, but the split was never recorded, so it
 * cannot be recovered from the receipt alone. It CAN be taken from the order —
 * but only where the receipt and the order are demonstrably in step, i.e.
 * where the stored `total` still equals the order's `grand_total`. Where they
 * have diverged, the order's present figures describe a different state than
 * the receipt captured, and copying them would fabricate a split that was
 * never presented.
 *
 * Those receipts keep zeros. Their printed subtotal and sales tax remain
 * exactly what they always were, and the components stay unstated rather than
 * invented — the same rule applied to legacy discount allocations.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->decimal('special_tax', 12, 2)->default(0)->after('sales_tax');
            $table->decimal('added_fees', 12, 2)->default(0)->after('special_tax');
            $table->decimal('pretax_discount_total', 12, 2)->default(0)->after('added_fees');
        });

        // Only receipts still in step with their order can be split exactly.
        DB::statement("
            UPDATE receipts r
            JOIN orders o ON o.id = r.order_id
            SET r.special_tax           = o.special_tax_amount,
                r.added_fees            = o.added_fees_amount,
                r.pretax_discount_total = o.pretax_discount_total
            WHERE r.order_id IS NOT NULL
              AND o.deleted_at IS NULL
              AND ABS(r.total - o.grand_total) < 0.005
        ");
    }

    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropColumn(['special_tax', 'added_fees', 'pretax_discount_total']);
        });
    }
};
