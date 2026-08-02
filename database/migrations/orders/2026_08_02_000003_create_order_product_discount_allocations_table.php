<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Per-line record of which pre-tax adjustment reduced which order line, by how
 * much.
 *
 * WHY A TABLE AND NOT ONE COLUMN. `orders.pretax_discount_total` says how much
 * was conceded; it cannot say from which lines, and a single
 * `order_products.pretax_discount_allocated` column cannot say by which
 * adjustment. Both are needed:
 *
 *   - product reporting must attribute the concession to the lines that bore
 *     it, so net revenue is per-product rather than order-wide;
 *   - reversing ONE adjustment among several must remove exactly that
 *     adjustment's share and leave the others untouched, which is impossible
 *     from a summed column.
 *
 * APPEND-ONLY. Rows are never deleted or rewritten. A reversal stamps
 * `reversed_at`, mirroring how `product_discounts` marks its own rows
 * `status = 'reversed'` rather than removing them. The history of what was
 * allocated, and when it stopped applying, survives.
 *
 * THE IDENTITY:
 *
 *     legacy_unallocated_pretax_discount
 *   + Σ active tracked allocations
 *   = orders.pretax_discount_total
 *
 * asserted to the cent at the write boundary. The legacy term exists because
 * an order discounted before this table existed has a total and no rows;
 * without it, adding a NEW tracked adjustment to such an order would compare
 * only the new rows against the ENTIRE total and refuse legitimate work — or,
 * worse, make the new rows appear to account for the historical concession.
 *
 * `order_products.pretax_discount_allocated` is the denormalized per-line sum
 * of that line's ACTIVE allocations — a convenience for reporting, always
 * derivable from this table, never the source of truth.
 *
 * GROSS IS UNTOUCHED. Neither this table nor the column it maintains ever
 * modifies `order_products.sub_total`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_product_discount_allocations', function (Blueprint $table) {
            $table->id();

            // The adjustment that produced this allocation. Cascade: an
            // adjustment that ceases to exist takes its allocations with it.
            $table->foreignId('product_discount_id')
                ->constrained('product_discounts')
                ->cascadeOnDelete();

            $table->foreignId('order_product_id')
                ->constrained('order_products')
                ->cascadeOnDelete();

            // Denormalized so an order's allocations can be summed without
            // joining through order_products on every read.
            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            $table->decimal('allocated_amount', 10, 2);

            // Deactivation, not deletion.
            $table->timestamp('reversed_at')->nullable();

            $table->timestamps();

            // One allocation per adjustment per line. This is also what makes
            // a retried apply idempotent at the storage layer.
            $table->unique(['product_discount_id', 'order_product_id'], 'opda_discount_line_unique');

            $table->index(['order_id', 'reversed_at'], 'opda_order_active_index');
        });

        Schema::table('order_products', function (Blueprint $table) {
            $table->decimal('pretax_discount_allocated', 10, 2)
                ->default(0)
                ->after('sub_total');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('legacy_unallocated_pretax_discount', 10, 2)
                ->default(0)
                ->after('pretax_discount_total');
        });

        // Everything discounted BEFORE this table existed is, by definition,
        // untracked. Recording it explicitly is what keeps the identity honest
        // once a NEW adjustment is added to such an order.
        DB::table('orders')
            ->where('pretax_discount_total', '>', 0)
            ->update(['legacy_unallocated_pretax_discount' => DB::raw('pretax_discount_total')]);

        // No per-line reconstruction is attempted for those orders. There is
        // no record of which lines bore the concession, and inventing a
        // distribution would produce per-line figures that look authoritative
        // and are not. The amount is parked at order level, visible and
        // separately reportable, until an explicit reviewed reconstruction is
        // approved. It must not happen silently here.
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('legacy_unallocated_pretax_discount');
        });

        Schema::table('order_products', function (Blueprint $table) {
            $table->dropColumn('pretax_discount_allocated');
        });

        Schema::dropIfExists('order_product_discount_allocations');
    }
};
