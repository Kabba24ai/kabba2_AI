<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_rental_ready_checklist_questions', function (Blueprint $table) {
            $table->id();
            $table->uuid('unique_id')->unique();

            // FK to templates
            $table->foreignId('equipment_rental_ready_template_id')
                ->nullable()
                ->constrained('equipment_rental_ready_templates', 'id', 'eq_ready_tpl_fk')
                ->nullOnDelete();

            // FK to questions master
            $table->foreignId('rental_ready_checklist_questions_id')
                ->nullable()
                ->constrained('rental_ready_checklist_questions', 'id', 'rr_check_q_fk')
                ->nullOnDelete();

            // FK to answers
            $table->foreignId('selected_answer_id')
                ->nullable()
                ->constrained('rental_ready_checklist_question_answers', 'id', 'rr_check_ans_fk')
                ->nullOnDelete();

            $table->json('rental_ready_qa_json');
            $table->text('general_notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_rental_ready_checklist_questions');
    }
};
