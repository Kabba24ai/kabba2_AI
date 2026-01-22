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
        Schema::create('order_product_checklist_questions', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('order_product_id');
            $table->unsignedBigInteger('question_id')->nullable();
            $table->unsignedBigInteger('question_category_id')->nullable();
            $table->string('question_name')->nullable();
            $table->string('delivery_question')->nullable();
            $table->string('return_question')->nullable();
            $table->integer('index_number')->default(0);
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('order_product_id')->references('id')->on('order_products')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('question_id')->references('id')->on('customer_admin_questions')->onDelete('set null')->onUpdate('cascade');
            $table->foreign('question_category_id')->references('id')->on('customer_admin_categories')->onDelete('set null')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_product_checklist_questions');
    }
};
