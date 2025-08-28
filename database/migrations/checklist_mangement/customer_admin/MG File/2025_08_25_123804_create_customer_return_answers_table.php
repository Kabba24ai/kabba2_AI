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
        Schema::create('customer_return_answers', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();
            $table->string('category_id')->comment('References customer_questions.unique_id');
            $table->string('description');
            $table->decimal('dollar_value', 10, 2)->default(0.00)->comment('Cost value for return');
            $table->integer('sort_order')->default(1);
            $table->timestamps();

            $table->index('category_id', 'idx_return_answers_question');
            $table->index('sort_order', 'idx_return_answers_sort');

            $table->foreign('category_id', 'fk_return_answers_question')
                  ->references('unique_id')
                  ->on('customer_questions')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_return_answers');
    }
};
