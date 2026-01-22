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
        Schema::create('sales_funnel_steps', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();
            $table->unsignedBigInteger('sales_funnel_id');
            $table->enum('step_type', ['SMS', 'Email'])->default('SMS');
            $table->unsignedBigInteger('sms_category_id')->nullable();
            $table->unsignedBigInteger('sms_message_id')->nullable();
            $table->string('name')->nullable();
            $table->text('message')->nullable();
            $table->enum('delay_unit', ['Days', 'Hours', 'Minutes'])->default('Days');
            $table->integer('delay_value')->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('sales_funnel_id')->references('id')->on('sales_funnels')->onDelete('cascade');
            $table->foreign('sms_category_id')->references('id')->on('sms_categories')->onDelete('set null');
            $table->foreign('sms_message_id')->references('id')->on('sms_funnels')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_funnel_steps');
    }
};
