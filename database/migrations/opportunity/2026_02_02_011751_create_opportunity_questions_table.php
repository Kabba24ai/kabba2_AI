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
        Schema::create('opportunity_questions', function (Blueprint $table) {
            $table->id();

            $table->uuid('unique_id')->unique();

            $table->enum('type', ['mechanical', 'driving', 'computer']);

            $table->string('question_key')->unique(); 
            // example: mechanical_experience, license_type

            $table->text('question_text');

            $table->boolean('required')->default(false);

            $table->enum('answer_type', [
                'radio',
                'checkbox',
                'text',
                'number',
                'textarea'
            ]);

            $table->string('sub_text')->nullable();

            $table->integer('display_order')->default(0);

            $table->enum('answer_grid', ['100', '50', '25'])->default('100');

            $table->boolean('status')->default(true);

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opportunity_questions');
    }
};
