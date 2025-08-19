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
        Schema::create('rental_ready_checklist_questions', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();
            $table->string('question_name');
            $table->foreignId('category_id')->constrained('rental_ready_checklist_categories')->onDelete('cascade');
            $table->boolean('required_question')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_ready_checklist_questions');
    }
};
