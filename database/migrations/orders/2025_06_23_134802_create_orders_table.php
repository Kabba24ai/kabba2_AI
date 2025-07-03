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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('unique_id')->unique();
            $table->string('order_number')->unique();
            $table->date('order_date');
            $table->unsignedBigInteger('customer_id');
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->string('coupon_code')->nullable();
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('grand_total', 10, 2)->default(0);
            $table->enum('payment_type', ['COD', 'Account', 'Card'])->default('COD');
            $table->enum('status', ['Pending', 'In Progress', 'Completed', 'Cancelled'])->default('Pending');
            $table->enum('platform', ['Web', 'Android', 'iOS'])->default('Web');

            // Morphs for created_by and updated_by
            $table->morphs('created_by'); // creates created_by_id & created_by_type
            $table->morphs('updated_by'); // creates updated_by_id & updated_by_type
            $table->timestamps();


            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
