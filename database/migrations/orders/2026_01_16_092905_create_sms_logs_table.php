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
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->string('sms_type')->nullable()->index(); // 'delivery_day_before', 'delivery_same_day', 'return_day_before', 'return_same_day', 'sales_funnel_before', 'sales_funnel_after'
            $table->enum('status', ['sent', 'failed', 'pending'])->default('pending')->index();
            $table->string('phone')->index();
            $table->text('message');
            $table->dateTime('sms_sent_at')->nullable()->index();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
            $table->foreignId('order_product_id')->nullable()->constrained('order_products')->onDelete('set null');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->string('twilio_sid')->nullable()->unique(); // Twilio message SID
            $table->string('error_message')->nullable();
            $table->timestamps();
            $table->index(['order_product_id', 'order_id', 'customer_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};
