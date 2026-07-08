<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 3.2 — Order Entry Integration.
     *
     * Additive only. Records which order (if any) a grant or redemption is
     * tied to — needed so Order Entry can display "Store Credit Applied"
     * for a specific order and support "Remove Applied Credit" against it,
     * and so the audit trail records "Order" as the mission requires.
     * Nullable: every existing row (from Phase 3.0/3.1, none order-scoped)
     * and every future customer-initiated grant/redemption unrelated to a
     * specific order remains valid with no order reference at all.
     */
    public function up(): void
    {
        Schema::table('customer_credits', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->nullable()->after('customer_id');
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customer_credits', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropColumn('order_id');
        });
    }
};
