<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index('order_date');
        });

        Schema::table('order_payments', function (Blueprint $table) {
            $table->index('payment_method');
            $table->index('status');
            $table->index(
                ['order_id', 'payment_method', 'status'],
                'op_order_payment_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['order_date']);
        });

        Schema::table('order_payments', function (Blueprint $table) {
            $table->dropIndex(['payment_method']);
            $table->dropIndex(['status']);
            $table->dropIndex('op_order_payment_status_idx');
        });
    }
};