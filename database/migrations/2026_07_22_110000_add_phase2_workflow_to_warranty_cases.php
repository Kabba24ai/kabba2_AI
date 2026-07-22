<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ST-4 (Warranty Phase 2) — the data captured by the mid-lifecycle work
 * panels the queue machinery already routes through: OEM submission,
 * decision + approved amount, customer decision, and reimbursement
 * tracking. Queue-entry timestamps (*_at) already exist; these are the
 * decision/amount values. Reimbursement is state-only (the Warranty module
 * has no billing/payment routes by design).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warranty_cases', function (Blueprint $table) {
            // Submit to Manufacturer
            $table->string('oem_submission_reference')->nullable()->after('warranty_registration_number');

            // Record OEM Decision — approved | partial | denied
            $table->string('oem_decision')->nullable()->after('oem_submission_reference');
            $table->decimal('oem_approved_amount', 10, 2)->nullable()->after('oem_decision');

            // Record Customer Decision — proceed | decline
            $table->string('customer_decision')->nullable()->after('oem_approved_amount');

            // Track Reimbursement (state-only)
            $table->decimal('reimbursement_expected_amount', 10, 2)->nullable()->after('customer_decision');
            $table->decimal('reimbursement_received_amount', 10, 2)->nullable()->after('reimbursement_expected_amount');
            $table->date('reimbursement_received_at')->nullable()->after('reimbursement_received_amount');
        });
    }

    public function down(): void
    {
        Schema::table('warranty_cases', function (Blueprint $table) {
            $table->dropColumn([
                'oem_submission_reference',
                'oem_decision',
                'oem_approved_amount',
                'customer_decision',
                'reimbursement_expected_amount',
                'reimbursement_received_amount',
                'reimbursement_received_at',
            ]);
        });
    }
};
