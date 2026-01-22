<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_admin_template_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')
                  ->constrained('customer_admin_templates')
                  ->onDelete('cascade');

            $table->foreignId('question_id')
                  ->constrained('customer_admin_questions')
                  ->onDelete('cascade');

            $table->integer('index_number')->default(0);
            $table->string('unique_id')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_admin_template_questions');
    }
};

