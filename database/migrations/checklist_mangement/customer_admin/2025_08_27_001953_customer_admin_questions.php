<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_admin_questions', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();
            $table->string('question_name');
            $table->foreignId('category_id')->constrained('customer_admin_categories')->onDelete('cascade');
            $table->string('question_delivery_text');
            $table->string('question_return_text');

            $table->boolean('required_question')->default(0);

            $table->timestamps();

            $table->index('category_id', 'idx_customer_questions_category');
            $table->index('required_question', 'idx_customer_questions_required');
            $table->index('question_name', 'idx_customer_questions_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_admin_questions');
    }
};

