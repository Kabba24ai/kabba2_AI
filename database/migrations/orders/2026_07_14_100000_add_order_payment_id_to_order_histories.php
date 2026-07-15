<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Phase 2 payment experience: links a history entry back to the exact
// OrderPayment it describes, so the timeline/Payment Details panel can
// read date/method/amount/notes/reference straight off the ledger row
// instead of re-deriving them from free text. Nullable and additive —
// existing rows stay null and the timeline falls back to `description`.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_histories', function (Blueprint $table) {
            $table->unsignedBigInteger('order_payment_id')->nullable()->after('order_id');
            $table->index('order_payment_id');
        });

        // Separate from the ADD COLUMN above: on SQLite, combining an add
        // column and an add foreign key in one Schema::table() call drives
        // Laravel's table-rebuild path to reconstruct the table with only
        // the new column, dropping every other one. Splitting into two
        // calls avoids that (MySQL — the real target — has no such issue
        // either way; this split is purely a SQLite-safety precaution).
        Schema::table('order_histories', function (Blueprint $table) {
            $table->foreign('order_payment_id')->references('id')->on('order_payments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_histories', function (Blueprint $table) {
            $table->dropForeign(['order_payment_id']);
            $table->dropColumn('order_payment_id');
        });
    }
};
