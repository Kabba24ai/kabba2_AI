<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update `status` ENUM to include Cheque and Other
        DB::statement("
            ALTER TABLE order_payments
            MODIFY COLUMN status ENUM(
                'Pending',
                'Paid',
                'Account',
                'Partial Refund',
                'Refunded',
                'Failed',
                'Invoice Card',
                'Invoice Cash',
                'Invoice Online',
                'Invoice Cheque',
                'Invoice Other'
            ) DEFAULT 'Pending'
        ");

        // Update `payment_method` ENUM to include Cheque and Other
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback ENUM to previous state
        DB::statement("
            ALTER TABLE order_payments
            MODIFY COLUMN status ENUM(
                'Pending',
                'Paid',
                'Account',
                'Partial Refund',
                'Refunded',
                'Failed',
                'Invoice Card',
                'Invoice Cash',
                'Invoice Online'
            ) DEFAULT 'Pending'
        ");

        DB::statement("
            ALTER TABLE order_payments
            MODIFY COLUMN payment_method ENUM(
                'Card',
                'COD',
                'Account',
                'Cash',
                'Online'
            ) DEFAULT 'COD'
        ");
    }
};
