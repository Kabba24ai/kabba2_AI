<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_extra_charges', function (Blueprint $table) {
            $table->id();

            // Unique identifier
            $table->uuid('unique_id')->unique();

            // Relations
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('order_product_id')->nullable();
            $table->unsignedBigInteger('customer_id');

            // Amount (SIGNED value: +charge / -payment)
            $table->decimal('amount', 12, 2);

            // Type
            $table->enum('type', [
                'fuel',
                'damage',
            ]);

            // Payment info
            $table->enum('payment_type', [
                'Cash',
                'Cheque',
                'CreditCard',
                'BankTransfer',
            ])->nullable();
            

            $table->string('payment_number_id')->nullable();
            $table->string('auth_code')->nullable();
            $table->string('customer_profile_id')->nullable();
            $table->string('payment_profile_id')->nullable();

            // Responsibility
            $table->unsignedBigInteger('responsible_person_id')->nullable();
            $table->string('responsible_person_name')->nullable();


            $table->text('notes')->nullable();

            $table->timestamps();

            /* ==========================
             | Foreign Keys
             ========================== */
            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('order_product_id')
                ->references('id')
                ->on('order_products')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('responsible_person_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /* ==========================
             | Indexes
             ========================== */
            $table->index(['order_id', 'type']);
            $table->index(['customer_id']);
            $table->index('order_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_extra_charges');
    }
};
