<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who changed a card's state, when, and why.
 *
 * ── WHY THESE COLUMNS EXIST AT ALL ────────────────────────────────────────
 *
 * Every operation that MOVES VALUE already audits itself: it appends a
 * `gift_card_transactions` row carrying `created_by_*`, `reason`, `note` and
 * `created_at`. The ledger is the audit trail, exactly as `customer_credits`
 * is for Store Credit — a separate audit table would be a second copy of the
 * same facts, free to disagree with the first.
 *
 * Suspension is the one exception. It changes what a card may DO without
 * changing what it is WORTH, so it writes no ledger row — and `amount <> 0`
 * on that table means a zero-value placeholder cannot be written either
 * (deliberately: a ledger of no-ops is a ledger nobody reads).
 *
 * Blocking a customer's money is not an action that may be anonymous, so the
 * three facts the ledger would have captured are recorded here instead.
 *
 * Cancellation and replacement DO move value — the remaining balance is
 * written off or transferred — so they are audited by their ledger rows and
 * need nothing here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gift_cards', function (Blueprint $table) {
            // Why the card was suspended, in the operator's words. Required by
            // the service — a suspension with no stated reason is
            // indistinguishable from a mistake when someone reviews it later.
            $table->text('suspension_reason')->nullable()->after('suspended_at');

            $table->foreignId('suspended_by')->nullable()->after('suspension_reason')
                ->constrained('users')->nullOnDelete();

            // Reinstatement is equally consequential — it puts spendable value
            // back in circulation — and is recorded with the same weight.
            $table->dateTime('reinstated_at')->nullable()->after('suspended_by');
            $table->text('reinstatement_reason')->nullable()->after('reinstated_at');
            $table->foreignId('reinstated_by')->nullable()->after('reinstatement_reason')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('gift_cards', function (Blueprint $table) {
            $table->dropConstrainedForeignKey('suspended_by');
            $table->dropConstrainedForeignKey('reinstated_by');
            $table->dropColumn([
                'suspension_reason',
                'reinstated_at',
                'reinstatement_reason',
            ]);
        });
    }
};
