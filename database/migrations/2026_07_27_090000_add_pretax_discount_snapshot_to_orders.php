<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pre-tax product-discount support on orders (Store Credit discount, Phase 1).
 *
 * `pretax_discount_total` is the running sum of pre-tax product discounts on
 * the order — kept SEPARATE from `discount_amount` (the existing POST-tax
 * coupon). `subtotal` stays = Σ order_products.sub_total (untouched), so gross
 * sales reporting is unaffected; taxable value = subtotal − pretax_discount_total.
 *
 * `*_before_discount` capture the order's original tax + grand total ONCE (on
 * the first discount) so tax can be recomputed with a blended effective rate
 * (preserving mixed taxable/exempt lines) and grand_total recomputed while
 * preserving the non-product components (special tax, added fees, delivery,
 * coupon) that are folded into grand_total and not separately stored.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('pretax_discount_total', 10, 2)->default(0)->after('discount_amount');
            $table->decimal('tax_amount_before_discount', 10, 2)->nullable()->after('pretax_discount_total');
            $table->decimal('grand_total_before_discount', 10, 2)->nullable()->after('tax_amount_before_discount');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['pretax_discount_total', 'tax_amount_before_discount', 'grand_total_before_discount']);
        });
    }
};
