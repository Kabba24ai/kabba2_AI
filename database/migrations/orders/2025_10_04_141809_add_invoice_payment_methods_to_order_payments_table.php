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
            $table->enum('payment_method', [
                'Card',
                'COD',
                'Account',
                'Cash',
                'Online',
            ])->default('COD')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_payments', function (Blueprint $table) {
            $table->enum('payment_method', [
                'Card',
                'COD',
                'Account',
            ])->default('COD')->change();
        });
    }

};
