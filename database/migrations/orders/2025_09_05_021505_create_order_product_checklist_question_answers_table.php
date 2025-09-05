<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_product_checklist_question_answers', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('order_product_checklist_question_id');
            $table->unsignedBigInteger('question_id')->nullable();
            $table->unsignedBigInteger('answer_id')->nullable();
            $table->string('delivery_answer')->nullable();
            $table->string('return_answer')->nullable();
            $table->decimal('delivery_amount', 10, 2)->nullable();
            $table->decimal('return_amount', 10, 2)->nullable();
            $table->decimal('user_delivery_amount', 10, 2)->nullable();
            $table->decimal('user_return_amount', 10, 2)->nullable();
            $table->boolean('is_delivery_answer')->default(false);
            $table->boolean('is_return_answer')->default(false);
            $table->boolean('is_sync')->default(false);
            $table->integer('index_number')->default(0);
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade')->onUpdate('cascade');
            $table
                ->foreign(
                    'order_product_checklist_question_id',
                    'opcq_answers_opcq_id_fk', // <-- short, manual name
                )
                ->references('id')
                ->on('order_product_checklist_questions')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('question_id')->references('id')->on('customer_admin_questions')->onDelete('set null')->onUpdate('cascade');
            $table->foreign('answer_id')->references('id')->on('customer_admin_question_answers')->onDelete('set null')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_product_checklist_question_answers');
    }
};
