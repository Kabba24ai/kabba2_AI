<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3B — Refund Allocation Foundation.
 *
 * Links a Store Credit grant/redemption row to the specific order_payments
 * row it funded (redemption) or was produced by (a refund-to-Store-Credit
 * grant, linked to the refund's own order_payments row) — see
 * PaymentAllocationService and its call sites for how this is populated.
 *
 * Nullable and additive: every existing row, and every future
 * grant/redemption not tied to a specific order payment (e.g. Store Credit
 * applied on the CRM/CustomerAccount side, which has no order_payments row
 * at all), remains valid with no order payment reference.
 *
 * nullOnDelete(), not cascade or restrict: a Store Credit ledger row is
 * itself financial history and must never disappear just because the
 * order_payments row it references was later hard-deleted — it simply
 * loses the pointer, exactly as order_id already does on this table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_credits', function (Blueprint $table) {
            $table->unsignedBigInteger('order_payment_id')->nullable()->after('order_id');
            $table->foreign('order_payment_id')->references('id')->on('order_payments')->nullOnDelete();
            $table->index('order_payment_id');
        });
    }

    public function down(): void
    {
        Schema::table('customer_credits', function (Blueprint $table) {
            $table->dropForeign(['order_payment_id']);
            $table->dropColumn('order_payment_id');
        });
    }
};
