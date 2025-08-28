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
        Schema::create('customer_admin_questions', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();
            $table->string('question_name');
            $table->foreignId('category_id')->constrained('customer_admin_categories')->onDelete('cascade');
            $table->boolean('required_question')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_admin_questions');
    }
};
