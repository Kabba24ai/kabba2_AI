<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_tickets', function (Blueprint $table) {
            // Diagnostic-first lifecycle (Phase 2D)
            $table->string('diagnostic_status')->default('not_started')->after('repair_status'); // App\Enums\Service\DiagnosticStatus
            $table->timestamp('diagnostic_started_at')->nullable()->after('diagnostic_status');
            $table->timestamp('diagnostic_completed_at')->nullable()->after('diagnostic_started_at');

            // Diagnostic fee state only — payment processing stays in the
            // Order/payment modules; nothing here charges a card.
            $table->boolean('diagnostic_fee_required')->default(false)->after('diagnostic_completed_at');
            $table->decimal('diagnostic_fee_amount', 10, 2)->nullable()->after('diagnostic_fee_required');
            $table->boolean('diagnostic_fee_paid')->default(false)->after('diagnostic_fee_amount');
            $table->boolean('diagnostic_fee_creditable')->default(false)->after('diagnostic_fee_paid');
            $table->boolean('diagnostic_fee_credited')->default(false)->after('diagnostic_fee_creditable');

            // Diagnosis findings
            $table->boolean('warranty_possible')->default(false)->after('diagnostic_fee_credited');
            $table->boolean('customer_damage_possible')->default(false)->after('warranty_possible');
            $table->text('recommended_repair')->nullable()->after('customer_damage_possible');
            $table->decimal('estimated_labor_hours', 6, 2)->nullable()->after('recommended_repair');
            $table->decimal('estimated_parts_total', 10, 2)->nullable()->after('estimated_labor_hours');
            $table->decimal('estimated_repair_total', 10, 2)->nullable()->after('estimated_parts_total');

            // Responsibility decision — made after diagnosis, drives financial_responsibility
            $table->string('responsibility_decision')->default('pending')->after('estimated_repair_total'); // App\Enums\Service\ResponsibilityDecision
            $table->timestamp('responsibility_decided_at')->nullable()->after('responsibility_decision');
            $table->foreignId('responsibility_decided_by')->nullable()->after('responsibility_decided_at')
                ->constrained('users')->nullOnDelete();

            $table->index('diagnostic_status');
            $table->index('responsibility_decision');
        });
    }

    public function down(): void
    {
        Schema::table('service_tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('responsibility_decided_by');
            $table->dropIndex(['diagnostic_status']);
            $table->dropIndex(['responsibility_decision']);
            $table->dropColumn([
                'diagnostic_status', 'diagnostic_started_at', 'diagnostic_completed_at',
                'diagnostic_fee_required', 'diagnostic_fee_amount', 'diagnostic_fee_paid',
                'diagnostic_fee_creditable', 'diagnostic_fee_credited',
                'warranty_possible', 'customer_damage_possible', 'recommended_repair',
                'estimated_labor_hours', 'estimated_parts_total', 'estimated_repair_total',
                'responsibility_decision', 'responsibility_decided_at',
            ]);
        });
    }
};
