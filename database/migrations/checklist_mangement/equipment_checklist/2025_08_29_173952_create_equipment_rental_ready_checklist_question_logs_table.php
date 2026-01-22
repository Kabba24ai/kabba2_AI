<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_rental_ready_checklist_question_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('unique_id')->unique();

            // FK to checklist questions
            $table->foreignId('equipment_checklist_question_id')->nullable()
                ->constrained('equipment_rental_ready_checklist_questions', 'id', 'eq_chk_q_fk')
                ->nullOnDelete();

            // FK to template
            $table->foreignId('equipment_rental_ready_template_id')->nullable()
                ->constrained('equipment_rental_ready_templates', 'id', 'eq_tpl_fk')
                ->nullOnDelete();

            $table->json('rental_ready_all_qa_json');

            // FK to users (who did the action)
            $table->foreignId('action_by')->nullable()
                ->constrained('users', 'id', 'eq_action_by_fk')
                ->nullOnDelete();

            $table->string('action_user_name');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_rental_ready_checklist_question_logs');
    }
};
