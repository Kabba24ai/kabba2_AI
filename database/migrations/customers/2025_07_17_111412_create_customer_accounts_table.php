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
         Schema::create('customer_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();
             $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->decimal('balance')->default(0.00);
            $table->decimal('amount');
            $table->enum('payment_type', ['Cash', 'Cheque', 'CreditCard', 'BankTransfer' ])
                ->default('Cash');
            $table->string('responsible_person')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('date');
            $table->string('payment_number_id')->nullable();
            $table->text('reason')->nullable();
            $table->decimal('sales_tax')->default(0.00);
            $table->enum('type', ['charge', 'payment', 'discount', 'credit', 'debit','refund']);

            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_accounts');
    }
};
