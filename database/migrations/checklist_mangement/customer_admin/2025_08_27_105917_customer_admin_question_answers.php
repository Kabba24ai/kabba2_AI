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
        Schema::create('customer_admin_question_answers', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();
            $table->string('answer_delivery_text');
            $table->string('answer_return_text');
            $table->decimal('delivery_amt', 8, 2)->default(0);
            $table->decimal('return_amt', 8, 2)->default(0);
            $table->boolean('required')->default(false);
            $table->boolean('sync_texts')->default(true)->comment('If true, delivery and return texts are synced');
            $table->json('answer_sync_map')->nullable()->comment('Maps answer index to sync status {0: true, 1: false}');
            $table->foreignId('question_id')->constrained('customer_admin_questions')->onDelete('cascade');
            //$table->enum('type', ['Rental Ready', 'Maint. Hold', 'Damaged']);
            $table->integer('index_number')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_admin_question_answers');
    }
};
