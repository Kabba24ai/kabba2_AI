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
       Schema::create('opportunity_question_options', function (Blueprint $table) {
            $table->id();

            $table->foreignId('opportunity_question_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('value'); 
            // example: basic_home, diesel_1_3

            $table->string('label');

            $table->integer('display_order')->default(0);

            $table->boolean('status')->default(true);

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opportunity_question_options');
    }
};
