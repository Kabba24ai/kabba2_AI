<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_charges', function (Blueprint $table) {
            // paid_at: the transaction date when this charge became paid.
            // Used as the date anchor for all Billing Engine revenue reporting
            // (mirrors how refunds use refunded_at and account payments use customer_accounts.date).
            // Set by BillingEngine::markPaid(); backfilled from linked order_payments for existing rows.
            $table->timestamp('paid_at')->nullable()->after('status');

            // store_id: the store this charge belongs to, used for store-filtered reports.
            // Populated at charge creation from the parent order's order_products.delivery_store_id.
            // Backfilled for existing rows via the companion backfill migration.
            $table->unsignedBigInteger('store_id')->nullable()->after('customer_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('billing_charges', function (Blueprint $table) {
            $table->dropColumn(['paid_at', 'store_id']);
        });
    }
};
