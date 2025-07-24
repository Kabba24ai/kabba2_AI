<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Temporarily expand enum to include both old and new values
        Schema::table('order_payments', function (Blueprint $table) {
            $table->enum('status', [
                'Pending',
                'Completed',
                'Paid',
                'Account',
                'Partial Refund',
                'Refunded',
                'Failed'
            ])->default('Pending')->change();
        });

        // Step 2: Update old values to new ones
        DB::table('order_payments')
            ->where('status', 'Completed')
            ->update(['status' => 'Paid']);

        // Step 3: Change enum column to final set
        Schema::table('order_payments', function (Blueprint $table) {
            $table->enum('status', [
                'Pending',
                'Paid',
                'Account',
                'Partial Refund',
                'Refunded',
                'Failed'
            ])->default('Pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback: Expand enum to allow old values for data conversion
        Schema::table('order_payments', function (Blueprint $table) {
            $table->enum('status', [
                'Pending',
                'Completed',
                'Paid',
                'Account',
                'Partial Refund',
                'Refunded',
                'Failed'
            ])->default('Pending')->change();
        });

        // Change values back
        DB::table('order_payments')
            ->where('status', 'Paid')
            ->update(['status' => 'Completed']);
        DB::table('order_payments')
            ->whereIn('status', ['Partial Refund', 'Refunded', 'Account'])
            ->update(['status' => 'Pending']); // Or however you want to handle

        // Restore enum to original
        Schema::table('order_payments', function (Blueprint $table) {
            $table->enum('status', [
                'Pending',
                'Completed',
                'Failed'
            ])->default('Pending')->change();
        });
    }
};
