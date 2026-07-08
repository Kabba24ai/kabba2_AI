<?php

namespace Tests\Feature\Service;

use App\Enums\Service\DiagnosticStatus;
use App\Enums\Service\FinancialResponsibility;
use App\Enums\Service\FinancialStatus;
use App\Enums\Service\RepairStatus;
use App\Enums\Service\ResponsibilityDecision;
use App\Enums\Service\ServiceLocation;
use App\Enums\Service\ServicePriority;
use App\Enums\Service\ServiceType;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Service\ServiceTicket;
use App\Models\Stores\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Rental-order intake flow: the create page is a fast intake form only
 * (Order → Equipment from that order → Store → Priority → Crew w/ Team
 * Leader → Complaint/Notes). Everything else — diagnosis, responsibility,
 * approval, repair — happens on the workbench after the ticket exists.
 */
class ServiceTicketIntakeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $techA;
    private User $techB;
    private Store $store;
    private Equipment $lift;
    private Equipment $excavator;
    private Equipment $unrelated;
    private int $customerId;
    private int $multiOrderId;
    private int $singleOrderId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'unique_id' => 'test-admin', 'first_name' => 'Admin', 'last_name' => 'User',
            'email' => 'admin@test.local', 'status' => 'Active',
        ]);
        $this->techA = User::create([
            'unique_id' => 'test-tech-a', 'first_name' => 'Alice', 'last_name' => 'Wrench',
            'email' => 'alice@test.local', 'status' => 'Active',
        ]);
        $this->techB = User::create([
            'unique_id' => 'test-tech-b', 'first_name' => 'Bob', 'last_name' => 'Torque',
            'email' => 'bob@test.local', 'status' => 'Active',
        ]);

        $this->store = Store::create(['store_name' => 'Main Shop', 'status' => 'Active']);

        $this->lift      = Equipment::create(['unique_id' => 'test-lift', 'equipment_name' => 'Scissor Lift', 'equipment_id' => 'SL-1', 'brand' => 'Test']);
        $this->excavator = Equipment::create(['unique_id' => 'test-exc', 'equipment_name' => 'Mini Excavator', 'equipment_id' => 'EX-1', 'brand' => 'Test']);
        $this->unrelated = Equipment::create(['unique_id' => 'test-oth', 'equipment_name' => 'Trencher', 'equipment_id' => 'TR-1', 'brand' => 'Test']);

        $this->customerId = DB::table('customers')->insertGetId([
            'unique_id' => 'test-cust', 'first_name' => 'Rental', 'last_name' => 'Customer',
        ]);

        $this->multiOrderId  = $this->makeOrder('#9001', [$this->lift->id, $this->excavator->id]);
        $this->singleOrderId = $this->makeOrder('#9002', [$this->lift->id]);

        $this->actingAs($this->admin);
    }

    private function makeOrder(string $number, array $equipmentIds): int
    {
        $orderId = DB::table('orders')->insertGetId([
            'unique_id' => 'test-ord-' . trim($number, '#'), 'order_number' => $number,
            'order_date' => now()->toDateString(), 'customer_name' => 'Rental Customer',
            'customer_id' => $this->customerId,
        ]);

        foreach ($equipmentIds as $i => $equipmentId) {
            DB::table('order_products')->insert([
                'unique_id' => 'test-op-' . trim($number, '#') . "-$i", 'order_id' => $orderId,
                'product_name' => 'Rental Line ' . ($i + 1), 'price' => 100, 'quantity' => 1, 'total' => 100,
                'equipment_id' => $equipmentId, 'delivery_date' => '2026-07-01',
            ]);
        }

        return $orderId;
    }

    private function intakePayload(array $overrides = []): array
    {
        return array_merge([
            'intake'       => 1,
            'order_id'     => $this->multiOrderId,
            'equipment_id' => $this->lift->id,
            'priority'     => ServicePriority::Normal->value,
        ], $overrides);
    }

    private function store(array $overrides = [])
    {
        return $this->post(route('admin.service-management.tickets.store'), $this->intakePayload($overrides));
    }

    // 1 + 9. Create page is a rental-order intake form — workflow fields gone
    public function test_create_page_defaults_to_rental_order_intake(): void
    {
        $response = $this->get(route('admin.service-management.tickets.create'))
            ->assertOk()
            ->assertSee('New Service Ticket')
            ->assertSee('Rental Order')
            ->assertSee('Assign Now')
            ->assertSee('Team Leader')
            ->assertSee('Complaint')
            ->assertSee('Service Store')
            ->assertDontSee('Technician Diagnosis')
            ->assertDontSee('Root Cause')
            ->assertDontSee('Repair Summary')
            ->assertDontSee('Financial Responsibility')
            ->assertDontSee('Financial Status')
            // The card heading says "Equipment & Service Location" — what must
            // be gone is the old workflow form's service_location dropdown.
            ->assertDontSee('name="service_location"', false);

        $content = $response->getContent();

        // Intake marker defaults the form to the order-related path
        $this->assertStringContainsString('name="intake" value="1"', $content);

        // Notes label replaces Internal Notes on this page
        $this->assertStringContainsString('>Notes<', $content);
        $this->assertStringNotContainsString('>Internal Notes<', $content);
    }

    // 2 + 6 + 10. Intake stores references + safe defaults, then redirects to workbench
    public function test_intake_stores_references_and_defaults_then_redirects_to_workbench(): void
    {
        $this->store([
            'service_store_id'   => $this->store->id,
            'customer_complaint' => 'Platform will not raise',
            'internal_notes'     => 'Reported at return check-in',
        ])->assertRedirect(route('admin.service-management.tickets.show', ServiceTicket::first()));

        $this->assertDatabaseHas('service_tickets', [
            // References only — the Order remains the source of truth
            'order_id'         => $this->multiOrderId,
            'customer_id'      => $this->customerId,
            'rental_date'      => '2026-07-01 00:00:00',
            'equipment_id'     => $this->lift->id,
            'service_store_id' => $this->store->id,
            // Safe intake defaults for the rental-order path
            'service_type'             => ServiceType::CustomerDamageRepair->value,
            'service_location'         => ServiceLocation::InShop->value,
            'repair_status'            => RepairStatus::Open->value,
            'diagnostic_status'        => DiagnosticStatus::NotStarted->value,
            'responsibility_decision'  => ResponsibilityDecision::Pending->value,
            'financial_responsibility' => FinancialResponsibility::Pending->value,
            'financial_status'         => FinancialStatus::NotBillable->value,
            'customer_complaint'       => 'Platform will not raise',
            'internal_notes'           => 'Reported at return check-in',
            'created_by'               => $this->admin->id,
        ]);
    }

    // 3. Equipment outside the selected order is rejected in this path
    public function test_intake_rejects_equipment_not_on_the_order(): void
    {
        $this->store(['equipment_id' => $this->unrelated->id])
            ->assertSessionHasErrors('equipment_id');

        $this->assertDatabaseCount('service_tickets', 0);
    }

    // 3b. Order itself is mandatory on the intake path
    public function test_intake_requires_an_order(): void
    {
        $this->store(['order_id' => null])->assertSessionHasErrors('order_id');
    }

    // 4. Create page ships the order → equipment map that drives the
    //    dependent dropdown (and single-equipment auto-select) client-side
    public function test_create_page_embeds_order_scoped_equipment_choices(): void
    {
        $content = $this->get(route('admin.service-management.tickets.create'))->getContent();

        // Single-equipment order lists exactly its one unit
        $this->assertStringContainsString('"label":"Scissor Lift (SL-1)"', $content);
        // Multi-equipment order carries both of its units
        $this->assertStringContainsString('"label":"Mini Excavator (EX-1)"', $content);
        // Equipment on no order is never offered
        $this->assertStringNotContainsString('Trencher (TR-1)', $content);
    }

    // 5. Multi-equipment order requires picking one of its units
    public function test_multi_equipment_order_requires_selection_from_that_order(): void
    {
        $this->store(['equipment_id' => null])->assertSessionHasErrors('equipment_id');

        // Both of the order's own units are accepted
        $this->store(['equipment_id' => $this->excavator->id])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('service_tickets', ['equipment_id' => $this->excavator->id]);
    }

    // 7 + 8. Crew from existing HRM users, with one marked Team Leader
    public function test_personnel_assignment_with_team_leader(): void
    {
        $this->store([
            'personnel'      => [$this->techA->id, $this->techB->id],
            'team_leader_id' => $this->techA->id,
        ])->assertSessionHasNoErrors();

        $ticket = ServiceTicket::firstOrFail();

        $this->assertDatabaseHas('service_ticket_personnel', [
            'service_ticket_id' => $ticket->id, 'employee_id' => $this->techA->id, 'is_team_leader' => 1,
        ]);
        $this->assertDatabaseHas('service_ticket_personnel', [
            'service_ticket_id' => $ticket->id, 'employee_id' => $this->techB->id, 'is_team_leader' => 0,
        ]);
        $this->assertSame($this->techA->id, $ticket->teamLeader()?->id);
    }

    public function test_team_leader_must_be_one_of_the_assigned_personnel(): void
    {
        $this->store([
            'personnel'      => [$this->techB->id],
            'team_leader_id' => $this->techA->id,
        ])->assertSessionHasErrors('team_leader_id');
    }

    // Editing personnel without a leader field keeps the existing leader
    public function test_edit_resync_preserves_existing_team_leader(): void
    {
        $this->store([
            'personnel'      => [$this->techA->id, $this->techB->id],
            'team_leader_id' => $this->techA->id,
        ]);

        $ticket = ServiceTicket::firstOrFail();
        $ticket->syncPersonnel([$this->techA->id, $this->techB->id]); // no leader passed

        $this->assertSame($this->techA->id, $ticket->fresh()->teamLeader()?->id);
    }
}
