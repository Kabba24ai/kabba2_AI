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
        //  Modify ENUM to include new values without dropping data
        DB::statement("
            ALTER TABLE invoices
            MODIFY COLUMN payment_method
            ENUM('cash', 'card', 'online', 'cheque', 'other')
            NULL
            AFTER invoice_number;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //  Rollback to original values
        DB::statement("
            ALTER TABLE invoices
            MODIFY COLUMN payment_method
            ENUM('cash', 'card', 'online')
            NULL
            AFTER invoice_number;
        ");
    }
};
