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
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->uuid('unique_id')->unique();


            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('receipt_created_by')
                ->constrained('users')->cascadeOnDelete();

            $table->enum('payment_method', ['cash', 'card', 'online', 'cheque', 'other'])->nullable();

            $table->date('receipt_date')->nullable();
            $table->date('order_date')->nullable();

            $table->enum('payment_status', ['paid', 'pending', 'failed'])->default('pending');

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('sales_tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            $table->enum('is_email_status', ['send', 'unsend'])->default('unsend');
            $table->timestamp('mail_send_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
