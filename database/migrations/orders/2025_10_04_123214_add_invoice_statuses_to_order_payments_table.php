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
        Schema::table('order_payments', function (Blueprint $table) {
            $table->enum('status', [
                'Pending',
                'Paid',
                'Account',
                'Partial Refund',
                'Refunded',
                'Failed',
                'Invoice Card',
                'Invoice Cash',
                'Invoice Online',
            ])->default('Pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
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
            ])->default('Pending')->change();
        });
    }
};
