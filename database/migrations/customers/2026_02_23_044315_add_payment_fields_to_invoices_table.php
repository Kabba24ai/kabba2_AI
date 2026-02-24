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
        Schema::table('invoices', function (Blueprint $table) {

              $table->decimal('paid_amount', 15, 2)->default(0)->after('total');
              $table->decimal('open_amount', 15, 2)->default(0)->after('paid_amount');

               $table->enum('invoice_status', [
                'pending',
                'partial_paid',
                'paid',
                'overdue'
            ])->default('pending')->change();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['paid_amount', 'open_amount']);

            $table->enum('invoice_status', [
                'pending',
                'paid',
                'overdue'
            ])->default('pending')->change();
        });
    }
};
