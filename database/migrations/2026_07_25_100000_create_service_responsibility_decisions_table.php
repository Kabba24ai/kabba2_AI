<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Responsibility Decision master data — converts the hard-coded
 * App\Enums\Service\ResponsibilityDecision into a business-managed list under
 * Service Master Admin. This becomes the single source of truth for the
 * Responsibility stage options, the financial-path mapping, and approval
 * routing.
 *
 * Two fields are ENFORCED this phase:
 *   - financial_path  → maps onto App\Enums\Service\FinancialResponsibility
 *   - approval_type   → maps onto App\Enums\Service\ApprovalType
 * The remaining behavior flags (allows_repair, requires_diagnostic_fee,
 * is_terminal_resolution, requires_customer_authorization,
 * requires_oem_authorization) are stored as documented configuration only and
 * are intentionally NOT enforced yet — reserved for a later workflow phase.
 *
 * "Pending" is NOT a master record — an undecided ticket has a NULL
 * responsibility_decision_id (see the companion service_tickets migration).
 *
 * Seeded here because the deploy pipeline runs migrations, never seeders.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('service_responsibility_decisions', function (Blueprint $table) {
            $table->id();
            // Immutable system identifier — behavior/reporting key off this,
            // never the editable name.
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color')->nullable();
            // ENFORCED mappings. financial_path = FinancialResponsibility value
            // (null = no payer path); approval_type = ApprovalType value
            // (null = no approval required).
            $table->string('financial_path')->nullable();
            $table->string('approval_type')->nullable();
            // Reporting dimension — metadata only, NEVER consumed by workflow.
            // Lets future analytics group decisions (Customer Damage, Warranty,
            // Internal, Goodwill, Damage Waiver, Other) without a schema change.
            $table->string('financial_reporting_category')->nullable();
            // Forward-looking configuration — stored, documented, NOT enforced
            // this phase.
            $table->boolean('allows_repair')->default(true);
            $table->boolean('requires_diagnostic_fee')->default(false);
            $table->boolean('is_terminal_resolution')->default(false);
            $table->boolean('requires_customer_authorization')->default(false);
            $table->boolean('requires_oem_authorization')->default(false);
            // Canonical seeded concepts are protected from physical deletion
            // (rename/recolor/reorder/deactivate only). Custom admin-created
            // decisions are is_system = false and may be deleted while unused.
            $table->boolean('is_system')->default(false);
            // Management
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        $this->seedResponsibilityDecisions();
    }

    public function down(): void
    {
        Schema::dropIfExists('service_responsibility_decisions');
    }

    /**
     * The seven canonical decisions, alphabetical sort_order. Keys match the
     * legacy ResponsibilityDecision enum values so the companion backfill can
     * map existing tickets one-to-one; damage_waiver is new.
     *
     * financial_path / approval_type carry the mapping that used to live in
     * ResponsibilityDecision::financialResponsibility() and
     * ApprovalType::forDecision(). Damage Waiver mirrors Internal Expense's
     * WORKFLOW behavior (management approval, no customer invoice, internal
     * processing) but keeps its OWN financial identity (financial_path =
     * damage_waiver) so its repair costs never merge with Internal Expense.
     */
    private function seedResponsibilityDecisions(): void
    {
        $now = now();

        $rows = [
            [
                'key' => 'customer_pay', 'name' => 'Customer Pay', 'sort_order' => 1,
                'description' => 'The customer is responsible for the repair and will be billed.',
                'color' => 'bg-orange-100 text-orange-700',
                'financial_path' => 'customer_pay', 'approval_type' => 'customer_approval',
                'financial_reporting_category' => 'Customer Damage',
                'allows_repair' => true, 'requires_customer_authorization' => true,
                'is_terminal_resolution' => false,
            ],
            [
                'key' => 'damage_waiver', 'name' => 'Damage Waiver', 'sort_order' => 2,
                'description' => 'Customer-caused damage covered by the Damage Waiver. Repair is processed internally with management approval; tracked as its own financial disposition, separate from Internal Expense.',
                'color' => 'bg-amber-100 text-amber-700',
                'financial_path' => 'damage_waiver', 'approval_type' => 'internal_management_approval',
                'financial_reporting_category' => 'Damage Waiver',
                'allows_repair' => true, 'is_terminal_resolution' => false,
            ],
            [
                'key' => 'goodwill', 'name' => 'Goodwill', 'sort_order' => 3,
                'description' => 'The company covers the repair as a goodwill gesture.',
                'color' => 'bg-teal-100 text-teal-700',
                'financial_path' => 'goodwill', 'approval_type' => 'goodwill_management_approval',
                'financial_reporting_category' => 'Goodwill',
                'allows_repair' => true, 'is_terminal_resolution' => false,
            ],
            [
                'key' => 'internal_company_expense', 'name' => 'Internal Expense', 'sort_order' => 4,
                'description' => 'An internal company cost — the company absorbs the repair.',
                'color' => 'bg-gray-100 text-gray-600',
                'financial_path' => 'internal_company_expense', 'approval_type' => 'internal_management_approval',
                'financial_reporting_category' => 'Internal',
                'allows_repair' => true, 'is_terminal_resolution' => false,
            ],
            [
                'key' => 'no_problem_found', 'name' => 'No Problem Found', 'sort_order' => 5,
                'description' => 'Diagnosis found no problem. No payer path; resolves the ticket.',
                'color' => 'bg-green-100 text-green-700',
                'financial_path' => null, 'approval_type' => null,
                'financial_reporting_category' => 'Other',
                'allows_repair' => false, 'is_terminal_resolution' => true,
            ],
            [
                'key' => 'not_repairable', 'name' => 'Not Repairable', 'sort_order' => 6,
                'description' => 'The unit cannot be repaired. No payer path; resolves the ticket.',
                'color' => 'bg-red-100 text-red-700',
                'financial_path' => null, 'approval_type' => null,
                'financial_reporting_category' => 'Other',
                'allows_repair' => false, 'is_terminal_resolution' => true,
            ],
            [
                'key' => 'oem_warranty', 'name' => 'OEM Warranty', 'sort_order' => 7,
                'description' => 'Covered under the manufacturer warranty; billed to the OEM claim.',
                'color' => 'bg-indigo-100 text-indigo-700',
                'financial_path' => 'oem_warranty', 'approval_type' => 'oem_warranty_approval',
                'financial_reporting_category' => 'Warranty',
                'allows_repair' => true, 'requires_oem_authorization' => true,
                'is_terminal_resolution' => false,
            ],
        ];

        // Every seeded row is a canonical, protected concept (is_system = true).
        $defaults = [
            'description' => null, 'color' => null,
            'financial_path' => null, 'approval_type' => null,
            'financial_reporting_category' => null,
            'allows_repair' => true, 'requires_diagnostic_fee' => false,
            'is_terminal_resolution' => false,
            'requires_customer_authorization' => false, 'requires_oem_authorization' => false,
            'is_system' => true, 'is_active' => true, 'created_by' => null, 'updated_by' => null,
        ];

        $rows = array_map(fn ($row) => array_merge($defaults, $row, [
            'created_at' => $now, 'updated_at' => $now,
        ]), $rows);

        DB::table('service_responsibility_decisions')->insert($rows);
    }
};
