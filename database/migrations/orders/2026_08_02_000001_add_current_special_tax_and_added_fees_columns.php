<?php

use App\Services\Orders\SpecialTaxColumnBackfill;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * First-class CURRENT columns for special tax and added fees.
 *
 * Until now these two components existed nowhere in the schema. Checkout
 * computed them (`CartHelper::buildCartItem()`), folded them into
 * `orders.grand_total`, and persisted them only inside the per-line
 * `order_products.product_data` JSON. `orders.tax_amount` never contained
 * them.
 *
 * That left `HistoricalTaxBasisResolver` reading an order's basis and tax from
 * MUTABLE columns while reading these two from an IMMUTABLE snapshot. Any
 * operation that legitimately moved the columns and the grand total — a
 * Goodwill Adjustment — could not move the JSON, because the JSON is the
 * frozen original by design. The reconciliation identity
 *
 *     grand_total = subtotal + tax + special_tax + added_fees - discount
 *
 * would then fail permanently, refusing every subsequent refund on that order.
 * That is why the Goodwill writer refused special-tax orders outright, and it
 * is what these columns fix.
 *
 * THE INVARIANT ESTABLISHED HERE:
 *   - the COLUMNS are the current, authoritative, mutable value;
 *   - `product_data` remains the immutable original checkout snapshot and is
 *     never written by this migration or by anything downstream of it.
 *
 * Reading these from a column rather than JSON does not weaken the resolver —
 * it makes it internally consistent, since it already sources basis and tax
 * from mutable columns and used JSON here only because no column existed.
 *
 * Money is decimal(10,2), matching `orders.tax_amount`, `order_products.tax`
 * and every other money column in this schema.
 *
 * BACKFILL. Delegated to {@see SpecialTaxColumnBackfill}, which refuses to
 * record a missing or malformed snapshot as a trustworthy zero whenever the
 * order's own arithmetic shows an unexplained residual. Rows it declines to
 * write keep the column default and are logged individually. Those orders
 * already fail the resolver today, and because the resolver reconciles
 * exactly, they keep failing for the same reason afterwards — the backfill
 * cannot make a broken order look sound.
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
     * Dropping these columns returns the schema to a state where special tax
     * and added fees exist only in frozen JSON. Any Goodwill Adjustment
     * applied to a special-tax order while they existed becomes
     * unreconcilable at that moment — the order's grand total will have moved
     * while the JSON did not. Reverse only if no such adjustment was ever
     * applied.
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
