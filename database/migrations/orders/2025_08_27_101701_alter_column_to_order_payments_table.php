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
            $table->unsignedBigInteger('parent_order_payment_id')->nullable()->after('id');
            $table->foreign('parent_order_payment_id')->references('id')->on('order_payments')->onDelete('set null')->onUpdate('cascade');
            $table->renameColumn('refunded_amount', 'refund_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_payments', function (Blueprint $table) {
            $table->dropColumn('parent_order_payment_id');
            $table->renameColumn('refund_amount', 'refunded_amount');
        });
    }
};
