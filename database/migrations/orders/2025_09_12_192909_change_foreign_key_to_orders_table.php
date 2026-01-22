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
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['reference_order_number']);
            $table->unsignedBigInteger('customer_id')->nullable()->change();
            $table->foreign('customer_id')
                ->references('id')->on('customers')
                ->onDelete('set null')
                ->onUpdate('cascade');
            $table->foreign('reference_order_number')
                ->references('order_number')->on('orders')
                ->onDelete('set null')
                ->onUpdate('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['reference_order_number']);
            $table->foreign('customer_id')
                ->references('id')->on('customers')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('reference_order_number')
                ->references('order_number')->on('orders')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }
};
