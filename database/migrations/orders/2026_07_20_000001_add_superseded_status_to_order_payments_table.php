<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_payments', function (Blueprint $table) {
            $table->enum('status', [
                'Pending',
                'Paid',
                'Account',
                'Partial Refund',
                'Refunded',
                'Failed',
                'Partial Payment',
                'Invoice Card',
                'Invoice Cash',
                'Invoice Online',
                'Invoice Cheque',
                'Invoice Other',
                'Voided',
                'Superseded',
            ])->default('Pending')->change();
        });
    }

    public function down(): void
    {
        Schema::table('order_payments', function (Blueprint $table) {
            $table->enum('status', [
                'Pending',
                'Paid',
                'Account',
                'Partial Refund',
                'Refunded',
                'Failed',
                'Partial Payment',
                'Invoice Card',
                'Invoice Cash',
                'Invoice Online',
                'Invoice Cheque',
                'Invoice Other',
                'Voided',
            ])->default('Pending')->change();
        });
    }
};
