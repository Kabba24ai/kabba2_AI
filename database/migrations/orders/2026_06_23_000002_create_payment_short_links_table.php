<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_short_links', function (Blueprint $table) {
            $table->id();

            $table->string('token', 16)->unique();

            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->longText('original_url');

            $table->timestamp('expires_at')->nullable();
            $table->timestamp('used_at')->nullable();

            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('max_clicks')->nullable();

            $table->timestamp('last_clicked_at')->nullable();
            $table->string('last_clicked_ip', 45)->nullable();   // 45 chars covers IPv6
            $table->text('last_clicked_user_agent')->nullable();

            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            $table->index('order_id');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_short_links');
    }
};
