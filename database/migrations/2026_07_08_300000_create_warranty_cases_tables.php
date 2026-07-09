<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Warranty is a sibling operational module: it manages the
        // administrative relationship with the manufacturer while the
        // linked Service Ticket manages the technical repair.
        Schema::create('warranty_cases', function (Blueprint $table) {
            $table->id();
            $table->string('case_number')->nullable()->unique();

            $table->string('path');                       // WarrantyPath (internal/external)
            $table->string('queue')->default('new_intake'); // WarrantyQueue

            // Who + what
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('equipment_id')->nullable()->constrained('equipment')->nullOnDelete();

            // Equipment identity — the claim's fingerprint. Stored as text
            // even for internal units so the case is self-contained.
            $table->string('manufacturer');
            $table->string('model');
            $table->string('serial_number');
            $table->string('engine_serial_number')->nullable();
            $table->boolean('has_hour_meter')->default(false);
            $table->unsignedInteger('hours')->nullable();

            // Ownership & warranty information (optional at intake)
            $table->date('purchase_date')->nullable();
            $table->string('selling_dealer')->nullable();
            $table->string('warranty_registration_number')->nullable();

            $table->text('complaint');
            $table->text('internal_notes')->nullable();

            // Diagnostic fee — state recording only in Phase 1 (no payment
            // processing; Billing Engine integration is a later phase).
            $table->decimal('diagnostic_fee_amount', 10, 2)->nullable();
            $table->boolean('diagnostic_fee_taxable')->default(true);
            $table->dateTime('diagnostic_fee_collected_at')->nullable();
            $table->foreignId('diagnostic_fee_waived_by')->nullable()->constrained('users')->nullOnDelete();

            // The hinge: warranty authorizes work, the ticket performs it.
            $table->foreignId('service_ticket_id')->nullable()->constrained('service_tickets')->nullOnDelete();

            // Queue milestone timestamps
            $table->dateTime('awaiting_diagnosis_at')->nullable();
            $table->dateTime('ready_to_submit_at')->nullable();
            $table->dateTime('waiting_on_manufacturer_at')->nullable();
            $table->dateTime('awaiting_customer_decision_at')->nullable();
            $table->dateTime('approved_for_repair_at')->nullable();
            $table->dateTime('awaiting_reimbursement_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->dateTime('queue_entered_at')->nullable(); // for days-in-queue

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['queue', 'path']);
        });

        Schema::create('warranty_case_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warranty_case_id', 'wc_event_case_fk')
                ->constrained('warranty_cases')->cascadeOnDelete();

            $table->string('event_type');   // App\Enums\Warranty\WarrantyCaseEventType
            $table->string('old_value')->nullable();
            $table->string('new_value')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            // Audit log — no soft deletes; events are never edited or removed.
            $table->timestamps();

            $table->index(['warranty_case_id', 'created_at'], 'wc_event_case_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warranty_case_events');
        Schema::dropIfExists('warranty_cases');
    }
};
