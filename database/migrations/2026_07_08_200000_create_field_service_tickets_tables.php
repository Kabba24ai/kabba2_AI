<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Field Service is a separate mission-based workflow — deliberately
        // NOT columns on service_tickets. Shop Service repairs machines in
        // the shop; Field Service dispatches a technician to a customer site.
        Schema::create('field_service_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->nullable()->unique();

            // Mission status (App\Enums\FieldService\FieldMissionStatus)
            $table->string('mission_status')->default('draft');

            // 1. Source / incident information (Order stays the source of truth)
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('equipment_id')->nullable()->constrained('equipment')->nullOnDelete();
            $table->string('serial_number')->nullable();
            $table->text('job_site_address');
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->dateTime('reported_at');
            $table->text('problem_summary');
            $table->text('diagnostic_summary')->nullable();
            // Future AI Technician handoff — reference only, module built later.
            $table->string('ai_session_reference')->nullable();

            // 2. Media review checklist (flags only in v1 — no upload handling)
            $table->boolean('photos_received')->default(false);
            $table->boolean('video_received')->default(false);
            $table->boolean('media_reviewed')->default(false);
            $table->boolean('additional_media_required')->default(false);
            $table->boolean('media_bypassed')->default(false);

            // 3. Dispatch assessment
            $table->string('priority')->default('normal');           // App\Enums\Service\ServicePriority (shared)
            $table->string('safety_concern')->default('unknown');    // FieldSafetyConcern
            $table->string('machine_status')->default('unknown');    // FieldMachineStatus
            $table->string('machine_stuck')->default('unknown');     // FieldYesNoUnknown
            $table->string('recovery_risk')->default('unknown');     // FieldRecoveryRisk
            $table->string('site_access')->default('unknown');       // FieldSiteAccess
            $table->text('site_notes')->nullable();

            // 4. Dispatch assignment
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('truck_id')->nullable()->constrained('dispatch_ai_trucks')->nullOnDelete();
            $table->dateTime('estimated_departure_at')->nullable();
            $table->dateTime('estimated_arrival_at')->nullable();
            $table->text('suggested_tools')->nullable();
            $table->text('suggested_parts')->nullable();
            $table->text('special_instructions')->nullable();

            // 5. Initial operational expectation (estimate, not outcome)
            $table->string('operational_expectation')->default('unknown'); // FieldOperationalExpectation

            // Field assessment result (captured when assessment completes)
            $table->text('assessment_summary')->nullable();

            // Operational Decision — the mandatory branching point. One of
            // FieldOperationalOutcome; permanent once recorded.
            $table->string('operational_outcome')->nullable();

            // Mission milestone timestamps
            $table->dateTime('ready_at')->nullable();
            $table->dateTime('assigned_at')->nullable();
            $table->dateTime('en_route_at')->nullable();
            $table->dateTime('on_site_at')->nullable();
            $table->dateTime('assessment_started_at')->nullable();
            $table->dateTime('assessment_completed_at')->nullable();
            $table->dateTime('operational_decision_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['mission_status', 'priority']);
        });

        Schema::create('field_service_ticket_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('field_service_ticket_id', 'fst_event_ticket_fk')
                ->constrained('field_service_tickets')->cascadeOnDelete();

            $table->string('event_type');   // App\Enums\FieldService\FieldTicketEventType
            $table->string('old_value')->nullable();
            $table->string('new_value')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            // Audit log — no soft deletes; events are never edited or removed.
            $table->timestamps();

            $table->index(['field_service_ticket_id', 'created_at'], 'fst_event_ticket_created_idx');
        });

        Schema::create('field_service_ticket_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('field_service_ticket_id', 'fst_note_ticket_fk')
                ->constrained('field_service_tickets')->cascadeOnDelete();

            $table->text('note');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['field_service_ticket_id', 'created_at'], 'fst_note_ticket_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_service_ticket_notes');
        Schema::dropIfExists('field_service_ticket_events');
        Schema::dropIfExists('field_service_tickets');
    }
};
