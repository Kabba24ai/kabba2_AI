<?php

use App\Services\Orders\SpecialTaxColumnBackfill;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * First-class CURRENT columns for special tax and added fees.
 *
 * These two components have never existed in the schema.
 * `CartHelper::buildCartItem()` computes them at checkout, folds them into
 * `orders.grand_total`, and persists them only inside the per-line
 * `order_products.product_data` JSON. `orders.tax_amount` never contains them.
 *
 * That gap is not cosmetic — it makes a live defect unfixable.
 * `OrderDiscountTarget::recompute()` reduces the merchandise basis on a
 * pre-tax discount and recomputes ordinary tax, then preserves everything
 * else intact as a single lumped residual:
 *
 *     $otherComponents = $origGrand - $subtotal - $baseTax;
 *
 * Special tax is BASIS-DERIVED, so when the basis shrinks it must shrink too.
 * It does not, and it cannot be made to, because that residual holds special
 * tax + added fees + delivery − coupon together and the engine cannot reduce
 * one while preserving another when it cannot tell them apart. Verified:
 * $200 basis with $4.00 special tax and a $50 discount retains $4.00 where
 * $3.00 is owed.
 *
 * THIS MIGRATION ONLY ADDS AND BACKFILLS STORAGE. It changes no calculation
 * and no Store Credit behaviour. The defect correction is a later increment
 * that depends on these columns existing.
 *
 * THE INVARIANT ESTABLISHED HERE:
 *   - the COLUMNS are the current, authoritative, mutable value;
 *   - `product_data` remains the original checkout snapshot and is never
 *     written by this migration or by anything downstream of it.
 *
 * Money is decimal(10,2), matching `orders.tax_amount` and
 * `order_products.tax`.
 *
 * BACKFILL. Delegated to {@see SpecialTaxColumnBackfill}, which refuses to
 * record a missing or malformed snapshot as a trustworthy zero whenever the
 * order's own arithmetic shows an unexplained residual. Rows it declines keep
 * the column default and are logged individually.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->decimal('special_tax', 10, 2)->default(0)->after('tax');
            $table->decimal('added_fees', 10, 2)->default(0)->after('special_tax');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('special_tax_amount', 10, 2)->default(0)->after('tax_amount');
            $table->decimal('added_fees_amount', 10, 2)->default(0)->after('special_tax_amount');
        });

        $audit = SpecialTaxColumnBackfill::apply();

        Log::warning('special_tax/added_fees backfill complete', [
            'orders_examined' => $audit['orders_examined'],
            'lines_examined'  => $audit['lines_examined'],
            'counts'          => $audit['counts'],
        ]);

        foreach ($audit['exceptions'] as $category => $rows) {
            foreach ($rows as $row) {
                Log::warning("special_tax/added_fees backfill SKIPPED ({$category})", $row);
            }
        }
    }

    /**
     * Dropping these returns special tax and added fees to JSON-only and
     * re-opens the defect they exist to make fixable. Any order whose special
     * tax was corrected while they existed becomes unreconcilable at that
     * moment. Reverse only before the correction increment ships.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['special_tax_amount', 'added_fees_amount']);
        });

        Schema::table('order_products', function (Blueprint $table) {
            $table->dropColumn(['special_tax', 'added_fees']);
        });
    }
};
