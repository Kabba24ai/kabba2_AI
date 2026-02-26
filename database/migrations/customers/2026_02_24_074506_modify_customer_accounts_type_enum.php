<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE customer_accounts 
            MODIFY COLUMN type ENUM(
                'charge', 
                'payment', 
                'discount', 
                'credit', 
                'debit', 
                'refund', 
                'order',
                'account_invoice'
            ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE customer_accounts 
            MODIFY COLUMN type ENUM(
                'charge', 
                'payment', 
                'discount', 
                'credit', 
                'debit', 
                'refund', 
                'order'
            ) NOT NULL");
    }
};