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
        Schema::create('rental_ready_checklist_question_answers', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();
            $table->string('answer_name');
            $table->foreignId('question_id')->constrained('rental_ready_checklist_questions')->onDelete('cascade');
            $table->enum('type', ['Rental Ready', 'Maint. Hold', 'Damaged']);
            $table->integer('index_number')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_ready_checklist_question_answers');
    }
};
