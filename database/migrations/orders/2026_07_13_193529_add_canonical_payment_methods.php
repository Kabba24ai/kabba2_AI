<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Phase 1 payment vocabulary standardization: adds the new canonical
// payment methods (Tap to Pay, Store Credit, Gift Card, Zelle/Venmo) to
// every table whose payment-method column is a native DB enum. Purely
// additive — no existing value is removed or renamed at the storage
// layer, only display labels change (see App\Enums\Orders\OrderPaymentMethod
// and App\Enums\Customers\PaymentMethod).
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE order_payments
            MODIFY COLUMN payment_method ENUM(
                'Card',
                'COD',
                'Account',
                'Cash',
                'Online',
                'Cheque',
                'Other',
                'TapToPay',
                'StoreCredit',
                'GiftCard',
                'ZelleVenmo'
            ) DEFAULT 'COD'
        ");

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

        DB::statement("
            ALTER TABLE customer_accounts
            MODIFY payment_type ENUM(
                'Cash',
                'Cheque',
                'CreditCard',
                'BankTransfer',
                'Other',
                'TapToPay',
                'StoreCredit',
                'GiftCard',
                'ZelleVenmo'
            )
        ");
    }

    public function down(): void
    {
        // Reversible only if no row has adopted a new value yet — matches
        // the existing convention for these enum-widening migrations.
        DB::statement("
            ALTER TABLE order_payments
            MODIFY COLUMN payment_method ENUM(
                'Card',
                'COD',
                'Account',
                'Cash',
                'Online',
                'Cheque',
                'Other'
            ) DEFAULT 'COD'
        ");

        DB::statement("
            ALTER TABLE receipts
            MODIFY COLUMN payment_method ENUM(
                'cash',
                'card',
                'online',
                'cheque',
                'other'
            ) NULL
        ");

        DB::statement("
            ALTER TABLE customer_accounts
            MODIFY payment_type ENUM(
                'Cash',
                'Cheque',
                'CreditCard',
                'BankTransfer',
                'Other'
            )
        ");
    }
};
