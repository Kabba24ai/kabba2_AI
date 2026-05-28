<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add 'resolved' and 'uncollectible' to the payment_type ENUM.
     *
     * The original ENUM only covered payment-method types (Cash, Cheque,
     * CreditCard, BankTransfer).  Dashboard alerts can now also be marked
     * as Resolved (settled outside the system) or Uncollectible.
     * Both statuses need to be persisted as audit records in this table.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE order_extra_charges
            MODIFY COLUMN payment_type
            ENUM('Cash','Cheque','CreditCard','BankTransfer','resolved','uncollectible')
            NULL
        ");
    }

    /**
     * Reverse the migrations.
     *
     * Note: MySQL will set any existing 'resolved' or 'uncollectible' rows
     * to NULL when the ENUM is narrowed back.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE order_extra_charges
            MODIFY COLUMN payment_type
            ENUM('Cash','Cheque','CreditCard','BankTransfer')
            NULL
        ");
    }
};
