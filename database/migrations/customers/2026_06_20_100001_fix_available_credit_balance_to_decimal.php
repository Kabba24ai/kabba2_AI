<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Coerce any non-numeric NULL-ish values to 0 before type change
        DB::statement("UPDATE customers SET available_credit_balance = '0' WHERE available_credit_balance IS NULL OR available_credit_balance = ''");

        DB::statement("ALTER TABLE customers MODIFY COLUMN available_credit_balance DECIMAL(15,2) NOT NULL DEFAULT 0.00");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE customers MODIFY COLUMN available_credit_balance VARCHAR(255) NULL");
    }
};
