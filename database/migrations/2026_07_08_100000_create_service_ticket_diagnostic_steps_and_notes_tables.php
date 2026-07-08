<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Structured diagnostic history — one row per step a technician took.
        Schema::create('service_ticket_diagnostic_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_ticket_id')
                ->constrained('service_tickets', indexName: 'st_diag_step_ticket_fk')->cascadeOnDelete();
            $table->string('step_type', 50);        // App\Enums\Service\DiagnosticStepType
            $table->text('description')->nullable(); // findings / what was observed
            $table->string('outcome', 50);           // App\Enums\Service\DiagnosticStepOutcome
            $table->foreignId('created_by')->nullable()
                ->constrained('users', indexName: 'st_diag_step_user_fk')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // General ticket notes (workbench left rail) — timestamped entries,
        // separate from the intake internal_notes text on the ticket itself.
        Schema::create('service_ticket_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_ticket_id')
                ->constrained('service_tickets', indexName: 'st_note_ticket_fk')->cascadeOnDelete();
            $table->text('note');
            $table->foreignId('created_by')->nullable()
                ->constrained('users', indexName: 'st_note_user_fk')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_ticket_notes');
        Schema::dropIfExists('service_ticket_diagnostic_steps');
    }
};
