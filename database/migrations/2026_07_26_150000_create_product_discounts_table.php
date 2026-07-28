<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Canonical, append-only product-discount ledger. Every pre-tax discount
 * application (and every reversal) is a durable row; reversal is a compensating
 * row linked to the original via `reversed_by_discount_id` — the original is
 * never deleted or mutated. The target is a CONSTRAINED (type,id) pair, not an
 * arbitrary morph.
 *
 * The original selling value stays recoverable: original_product_value and the
 * full before/after pricing snapshot are stored so no screen or report has to
 * re-derive the math. Store Credit is represented here as a DISCOUNT — never as
 * a payment; the Store Credit balance draw-down is linked via
 * store_credit_redemption_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_discounts', function (Blueprint $table) {
            $table->id();

            $table->string('discount_type');      // App\Enums\Discounts\DiscountType
            $table->string('calculation_type');   // App\Enums\Discounts\DiscountCalculationType

            $table->decimal('source_amount', 15, 2)->nullable();  // fixed: requested $
            $table->decimal('percentage', 8, 4)->nullable();      // percentage: e.g. 10.0000
            $table->decimal('calculated_discount_amount', 15, 2); // actually applied (capped)

            $table->string('target_type');        // App\Enums\Discounts\DiscountTargetType (constrained)
            $table->unsignedBigInteger('target_id');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();

            // Full pricing snapshot (original value stays recoverable).
            $table->decimal('original_product_value', 15, 2);
            $table->decimal('discounted_product_value', 15, 2);
            $table->decimal('taxable_value_before', 15, 2);
            $table->decimal('taxable_value_after', 15, 2);
            $table->decimal('tax_before', 15, 2);
            $table->decimal('tax_after', 15, 2);

            // Store Credit linkage (only for discount_type = store_credit).
            $table->foreignId('store_credit_redemption_id')->nullable()->constrained('customer_credits')->nullOnDelete();

            $table->foreignId('applied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('applied_at');
            $table->string('source_interface')->nullable(); // e.g. admin_order_payment
            $table->text('reason')->nullable();

            $table->string('idempotency_key')->unique();
            $table->string('status')->default('applied'); // applied | reversed
            $table->foreignId('reversed_by_discount_id')->nullable()->constrained('product_discounts')->nullOnDelete();

            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['target_type', 'target_id'], 'pd_target_idx');
            $table->index(['customer_id', 'status'], 'pd_customer_status_idx');
            $table->index('discount_type', 'pd_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_discounts');
    }
};
