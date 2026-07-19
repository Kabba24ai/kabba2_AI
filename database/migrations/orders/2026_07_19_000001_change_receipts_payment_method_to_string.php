<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Payment Architecture Finalization: receipts.payment_method is an
// audit-only snapshot written solely by ReceiptService::getOrCreateReceipt()
// (no production reader exists). Storing it as a native DB enum means every
// new payment method requires an ALTER TABLE in lockstep with
// ReceiptService::mapPaymentMethod() — and a missed one makes the receipt
// INSERT throw under strict mode, which getOrCreateReceipt() swallows into
// a null receipt and a broken Print Receipt. A nullable string removes that
// failure class permanently; the PHP layer (mapPaymentMethod + the
// OrderPaymentMethod enum) remains the single vocabulary authority.
//
// Follows the same raw-DB::statement convention as
// 2026_07_13_193529_add_canonical_payment_methods.php, which previously
// widened this same enum.
return new class extends Migration
{
    public function up(): void
    {
        // MySQL/MariaDB convert ENUM → VARCHAR in place, preserving every
        // stored value byte-for-byte — no data rewrite needed. 50 chars
        // comfortably fits every current and plausible method slug.
        DB::statement('ALTER TABLE receipts MODIFY COLUMN payment_method VARCHAR(50) NULL');
    }

    public function down(): void
    {
        // Restore the column exactly as the 2026_07_13 widening migration
        // left it (the state immediately prior to this migration). Any
        // value outside that vocabulary cannot survive the narrowing; it
        // is set to NULL ("method not recorded") first rather than being
        // silently misfiled as 'other'. With the current mapPaymentMethod()
        // this UPDATE matches zero rows.
        DB::table('receipts')
            ->whereNotNull('payment_method')
            ->whereNotIn('payment_method', [
                'cash', 'card', 'online', 'cheque', 'other',
                'tap_to_pay', 'store_credit', 'gift_card', 'zelle_venmo',
            ])
            ->update(['payment_method' => null]);

        DB::statement("
            ALTER TABLE receipts
            MODIFY COLUMN payment_method ENUM(
                'cash',
                'card',
                'online',
                'cheque',
                'other',
                'tap_to_pay',
                'store_credit',
                'gift_card',
                'zelle_venmo'
            ) NULL
        ");
    }
};
