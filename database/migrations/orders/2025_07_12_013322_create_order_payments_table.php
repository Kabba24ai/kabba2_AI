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
        Schema::create('order_payments', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->enum('payment_method', ['COD', 'Account', 'Card'])->default('COD');
            $table->dateTime('payment_datetime')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('card_number')->nullable();
            $table->string('card_first_name')->nullable();
            $table->string('card_last_name')->nullable();
            $table->string('auth_code')->nullable();
            $table->string('customer_profile_id')->nullable();
            $table->string('payment_profile_id')->nullable();
            $table->text('payment_note')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->enum('status', ['Pending', 'Completed', 'Failed'])->default('Pending');
            $table->decimal('refunded_amount', 10, 2)->default(0);
            $table->text('refund_note')->nullable();

            $table->nullableMorphs('created_by'); // creates nullable created_by_id & created_by_type
            $table->nullableMorphs('updated_by'); // creates nullable updated_by_id & updated_by_type
            $table->timestamps();
       });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_payments');
    }
};
