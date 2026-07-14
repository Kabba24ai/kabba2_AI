<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Refund idempotency: the client-generated UUID (one per modal-open,
// reused across retries of that same submission) is now persisted on the
// row it produced and enforced unique at the DB level — the same
// "check the ledger, not just disable the button" discipline
// customer_credits.idempotency_key already uses for Store Credit.
// Nullable and additive: legacy rows and any caller that omits the token
// (idempotency protection simply doesn't apply to that request) are
// unaffected.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_payments', function (Blueprint $table) {
            $table->string('idempotency_token')->nullable()->after('cc_fee_retained');
        });

        Schema::table('order_payments', function (Blueprint $table) {
            $table->unique('idempotency_token');
        });
    }

    public function down(): void
    {
        Schema::table('order_payments', function (Blueprint $table) {
            $table->dropUnique(['idempotency_token']);
            $table->dropColumn('idempotency_token');
        });
    }
};
