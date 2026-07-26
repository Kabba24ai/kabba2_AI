<?php

namespace Tests\Feature\Service;

use App\Enums\Service\ApprovalType;
use App\Enums\Service\FinancialResponsibility;
use App\Enums\Service\FinancialStatus;
use App\Enums\Service\RepairStatus;
use App\Enums\Service\ServiceLocation;
use App\Enums\Service\ServicePriority;
use App\Enums\Service\ServiceType;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Service\ServiceResponsibilityDecision;
use App\Models\Service\ServiceTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ResolvesResponsibilityDecisions;
use Tests\TestCase;

/**
 * Responsibility Decision master data — seeding, ordering, active/inactive
 * behavior, immutable keys, financial/approval mapping, historical integrity,
 * the in-use delete guard, and the Service Master Admin CRUD.
 */
class ServiceResponsibilityDecisionTest extends TestCase
{
    use RefreshDatabase;
    use ResolvesResponsibilityDecisions;

    private User $admin;
    private Equipment $equipment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'unique_id' => 'srd-admin', 'employee_code' => '01',
            'first_name' => 'Admin', 'last_name' => 'User',
            'email' => 'srd-admin@test.local', 'status' => 'Active',
        ]);
        $this->equipment = Equipment::create([
            'unique_id' => 'srd-eq', 'equipment_name' => 'Test Lift', 'equipment_id' => 'TL-9', 'brand' => 'Test',
        ]);

        $this->actingAs($this->admin);
    }

    // 1 + 2. The seven canonical decisions are seeded, including Damage Waiver.
    public function test_migration_seeds_the_canonical_decisions_including_damage_waiver(): void
    {
        $this->assertSame(7, ServiceResponsibilityDecision::count());

        $keys = ServiceResponsibilityDecision::pluck('key')->all();
        foreach (['customer_pay', 'damage_waiver', 'goodwill', 'internal_company_expense',
                  'no_problem_found', 'not_repairable', 'oem_warranty'] as $key) {
            $this->assertContains($key, $keys);
        }
    }

    // 3. Default order is alphabetical by name.
    public function test_default_order_is_alphabetical(): void
    {
        $names = ServiceResponsibilityDecision::ordered()->pluck('name')->all();

        $this->assertSame([
            'Customer Pay', 'Damage Waiver', 'Goodwill', 'Internal Expense',
            'No Problem Found', 'Not Repairable', 'OEM Warranty',
        ], $names);
    }

    // 4. Only active decisions are offered for a new selection.
    public function test_active_scope_excludes_deactivated_decisions(): void
    {
        $this->responsibilityDecision('goodwill')->update(['is_active' => false]);

        $activeKeys = ServiceResponsibilityDecision::active()->pluck('key')->all();
        $this->assertNotContains('goodwill', $activeKeys);
        $this->assertContains('customer_pay', $activeKeys);
    }

    // 7 + 10. Damage Waiver maps to its OWN financial identity + management approval.
    public function test_damage_waiver_has_its_own_financial_identity(): void
    {
        $dw = $this->responsibilityDecision('damage_waiver');

        $this->assertSame(FinancialResponsibility::DamageWaiver, $dw->financialResponsibility());
        $this->assertNotSame(FinancialResponsibility::InternalExpense, $dw->financialResponsibility());
        $this->assertSame(ApprovalType::InternalManagementApproval, $dw->approvalTypeEnum());
    }

    // 9 + 11. Customer Pay / OEM Warranty retain their mappings.
    public function test_customer_pay_and_oem_warranty_retain_their_mappings(): void
    {
        $this->assertSame(FinancialResponsibility::CustomerPay, $this->responsibilityDecision('customer_pay')->financialResponsibility());
        $this->assertSame(ApprovalType::CustomerApproval, $this->responsibilityDecision('customer_pay')->approvalTypeEnum());

        $this->assertSame(FinancialResponsibility::OemWarranty, $this->responsibilityDecision('oem_warranty')->financialResponsibility());
        $this->assertSame(ApprovalType::OemWarrantyApproval, $this->responsibilityDecision('oem_warranty')->approvalTypeEnum());

        // No-payer decisions have no financial path and no approval.
        $this->assertNull($this->responsibilityDecision('no_problem_found')->financialResponsibility());
        $this->assertNull($this->responsibilityDecision('not_repairable')->approvalTypeEnum());
    }

    // decideResponsibility writes the FK + the legacy key snapshot + the mapping.
    public function test_deciding_responsibility_sets_fk_snapshot_and_financial_responsibility(): void
    {
        $ticket = $this->makeDiagnosedTicket();
        $ticket->decideResponsibility($this->responsibilityDecision('customer_pay'));

        $fresh = $ticket->fresh();
        $this->assertTrue($fresh->isResponsibilityDecided());
        $this->assertSame('customer_pay', $fresh->responsibilityDecision->key);
        $this->assertSame('customer_pay', $fresh->responsibility_decision); // legacy key snapshot retained
        $this->assertSame(FinancialResponsibility::CustomerPay, $fresh->financial_responsibility);
    }

    // 5. A deactivated decision still displays on a ticket that already used it.
    public function test_deactivated_decision_still_displays_on_historical_tickets(): void
    {
        $ticket = $this->makeDiagnosedTicket();
        $ticket->decideResponsibility($this->responsibilityDecision('goodwill'));

        $this->responsibilityDecision('goodwill')->update(['is_active' => false]);

        $this->assertSame('Goodwill', $ticket->fresh()->responsibilityDecision->name);
    }

    // 7 (keys). Editing the display name never changes the stable key.
    public function test_editing_name_via_admin_keeps_the_stable_key(): void
    {
        $goodwill = $this->responsibilityDecision('goodwill');

        $this->put(route('admin.maintenance-management.service-master.responsibility-decisions.update', $goodwill->id), [
            'name' => 'Goodwill (Manager Approved)',
            'is_active' => true,
        ])->assertOk();

        $fresh = $goodwill->fresh();
        $this->assertSame('Goodwill (Manager Approved)', $fresh->name);
        $this->assertSame('goodwill', $fresh->key); // key unchanged
    }

    // 6. A decision used by a ticket cannot be deleted — deactivate instead.
    public function test_used_decision_cannot_be_deleted(): void
    {
        $ticket = $this->makeDiagnosedTicket();
        $decision = $this->responsibilityDecision('customer_pay');
        $ticket->decideResponsibility($decision);

        $this->delete(route('admin.maintenance-management.service-master.responsibility-decisions.destroy', $decision->id))
            ->assertStatus(422);

        $this->assertDatabaseHas('service_responsibility_decisions', ['id' => $decision->id]);
    }

    public function test_unused_custom_decision_can_be_created_with_explicit_key_and_deleted(): void
    {
        $this->post(route('admin.maintenance-management.service-master.responsibility-decisions.store'), [
            'name' => 'Rental Protection',
            'key'  => 'rental_protection',            // explicit, not derived from the name
            'financial_reporting_category' => 'Damage Waiver',
            'financial_path' => null,
            'is_active' => true,
        ])->assertOk();

        $created = ServiceResponsibilityDecision::where('name', 'Rental Protection')->firstOrFail();
        $this->assertSame('rental_protection', $created->key);
        $this->assertSame('Damage Waiver', $created->financial_reporting_category);
        $this->assertFalse($created->is_system);                 // custom → not canonical
        $this->assertSame($this->admin->id, $created->created_by);

        $this->delete(route('admin.maintenance-management.service-master.responsibility-decisions.destroy', $created->id))
            ->assertOk();
        $this->assertDatabaseMissing('service_responsibility_decisions', ['id' => $created->id]);
    }

    // 1. The system key is explicit and validated — required, unique, snake_case.
    public function test_create_requires_a_valid_unique_system_key(): void
    {
        // Missing key
        $this->post(route('admin.maintenance-management.service-master.responsibility-decisions.store'), [
            'name' => 'No Key', 'is_active' => true,
        ])->assertSessionHasErrors('key');

        // Bad format
        $this->post(route('admin.maintenance-management.service-master.responsibility-decisions.store'), [
            'name' => 'Bad Key', 'key' => 'Not Snake Case', 'is_active' => true,
        ])->assertSessionHasErrors('key');

        // Duplicate of a seeded key
        $this->post(route('admin.maintenance-management.service-master.responsibility-decisions.store'), [
            'name' => 'Dupe', 'key' => 'customer_pay', 'is_active' => true,
        ])->assertSessionHasErrors('key');
    }

    // 3. Canonical seeded concepts can never be physically deleted.
    public function test_canonical_decision_cannot_be_deleted_even_when_unused(): void
    {
        // Not Repairable is seeded (is_system) and unused in this test.
        $canonical = $this->responsibilityDecision('not_repairable');
        $this->assertTrue($canonical->is_system);
        $this->assertFalse($canonical->isDeletable());

        $this->delete(route('admin.maintenance-management.service-master.responsibility-decisions.destroy', $canonical->id))
            ->assertStatus(422);

        $this->assertDatabaseHas('service_responsibility_decisions', ['id' => $canonical->id]);
    }

    // 2. Financial reporting category is seeded as future reporting metadata.
    public function test_financial_reporting_category_is_seeded(): void
    {
        $this->assertSame('Damage Waiver', $this->responsibilityDecision('damage_waiver')->financial_reporting_category);
        $this->assertSame('Warranty', $this->responsibilityDecision('oem_warranty')->financial_reporting_category);
        $this->assertSame('Internal', $this->responsibilityDecision('internal_company_expense')->financial_reporting_category);
    }

    // 4 (enforcement). An inactive decision cannot be chosen at the stage.
    public function test_decide_controller_rejects_an_inactive_decision(): void
    {
        $ticket = $this->makeDiagnosedTicket();
        $decision = $this->responsibilityDecision('goodwill');
        $decision->update(['is_active' => false]);

        $this->post(route('admin.service-management.tickets.responsibility.decide', $ticket), [
            'responsibility_decision' => $decision->id,
        ])->assertSessionHasErrors('responsibility_decision');
    }

    /** A ticket that has completed diagnosis (ready for a responsibility decision). */
    private function makeDiagnosedTicket(): ServiceTicket
    {
        $ticket = ServiceTicket::create([
            'service_type'             => ServiceType::InspectionDiagnosis->value,
            'service_location'         => ServiceLocation::InShop->value,
            'priority'                 => ServicePriority::Normal->value,
            'repair_status'            => RepairStatus::Open->value,
            'financial_responsibility' => 'pending',
            'financial_status'         => FinancialStatus::NotBillable->value,
            'equipment_id'             => $this->equipment->id,
            'opened_at'                => now(),
        ]);

        $ticket->startDiagnostic();
        $ticket->completeDiagnostic();

        return $ticket->fresh();
    }
}
