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
        DB::statement("
            ALTER TABLE customer_accounts 
            MODIFY payment_type ENUM('Cash','Cheque','CreditCard','BankTransfer','Other') 
            NOT NULL DEFAULT 'Cash'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE customer_accounts 
            MODIFY payment_type ENUM('Cash','Cheque','CreditCard','BankTransfer') 
            NOT NULL DEFAULT 'Cash'
        ");
    }
};
