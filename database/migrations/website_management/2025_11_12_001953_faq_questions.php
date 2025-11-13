<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faq_questions', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();
            $table->string('question_name');
            $table->foreignId('category_id')->constrained('faq_categories')->onDelete('cascade');
            $table->text('answer')->nullable();
            $table->enum('status', ['Active', 'Inactive'])->default('Inactive');
            $table->integer('related_question_id')->nullable();
            $table->timestamps();

            $table->index('category_id', 'idx_faq_categories');
            $table->index('question_name', 'idx_faq_questions_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faq_questions');
    }
};

