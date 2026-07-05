<?php

namespace Tests\Feature\Service;

use App\Enums\Service\FinancialResponsibility;
use App\Enums\Service\FinancialStatus;
use App\Enums\Service\RepairStatus;
use App\Enums\Service\ServiceLocation;
use App\Enums\Service\ServicePriority;
use App\Enums\Service\ServiceType;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Service\ServiceTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ServiceTicketCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Equipment $equipment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'unique_id' => 'test-admin', 'employee_code' => '01',
            'first_name' => 'Admin', 'last_name' => 'User',
            'email' => 'admin@test.local', 'status' => 'Active',
        ]);

        $this->equipment = Equipment::create([
            'unique_id' => 'test-eq', 'equipment_name' => 'Test Lift',
            'equipment_id' => 'TL-1', 'brand' => 'Test',
        ]);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'service_type'             => ServiceType::CustomerDamageRepair->value,
            'service_location'         => ServiceLocation::InShop->value,
            'priority'                 => ServicePriority::Normal->value,
            'repair_status'            => RepairStatus::Open->value,
            'financial_responsibility' => FinancialResponsibility::CustomerPay->value,
            'financial_status'         => FinancialStatus::NotBillable->value,
            'equipment_id'             => $this->equipment->id,
            'opened_at'                => now()->format('Y-m-d'),
        ], $overrides);
    }

    private function makeTicket(array $overrides = []): ServiceTicket
    {
        return ServiceTicket::create($this->validPayload($overrides));
    }

    // 1. Ticket list page loads
    public function test_ticket_list_page_loads(): void
    {
        $ticket = $this->makeTicket();

        $this->actingAs($this->admin)
            ->get(route('admin.service-management.tickets.index'))
            ->assertOk()
            ->assertSee('Service Tickets')
            ->assertSee($ticket->fresh()->ticket_number);
    }

    // 1b. Create and edit forms load through the full middleware stack
    public function test_create_and_edit_forms_load(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.service-management.tickets.create'))
            ->assertOk()
            ->assertSee('New Service Ticket');

        $ticket = $this->makeTicket();
        $this->actingAs($this->admin)
            ->get(route('admin.service-management.tickets.edit', $ticket))
            ->assertOk()
            ->assertSee('Repair Documentation');
    }

    // 2. Ticket detail page loads
    public function test_ticket_detail_page_loads(): void
    {
        $ticket = $this->makeTicket(['customer_complaint' => 'Engine will not start.']);

        $this->actingAs($this->admin)
            ->get(route('admin.service-management.tickets.show', $ticket))
            ->assertOk()
            ->assertSee($ticket->fresh()->ticket_number)
            ->assertSee('Engine will not start.');
    }

    // 3. Ticket creation stores required fields
    public function test_ticket_creation_stores_required_fields(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.service-management.tickets.store'), $this->validPayload([
                'customer_complaint' => 'Hydraulic leak',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('service_tickets', [
            'service_type'       => ServiceType::CustomerDamageRepair->value,
            'priority'           => ServicePriority::Normal->value,
            'equipment_id'       => $this->equipment->id,
            'customer_complaint' => 'Hydraulic leak',
            'created_by'         => $this->admin->id,
        ]);
        $this->assertMatchesRegularExpression('/^SVC-\d{5}$/', ServiceTicket::first()->ticket_number);
    }

    // 4. Order association stores only references
    public function test_order_association_stores_only_references(): void
    {
        $customerId = DB::table('customers')->insertGetId([
            'unique_id' => 'test-cust', 'first_name' => 'Ref', 'last_name' => 'Customer',
        ]);
        $orderId = DB::table('orders')->insertGetId([
            'unique_id' => 'test-ord', 'order_number' => '#7001',
            'order_date' => now()->toDateString(), 'customer_name' => 'Ref Customer',
            'customer_id' => $customerId,
        ]);
        DB::table('order_products')->insert([
            'unique_id' => 'test-op', 'order_id' => $orderId, 'product_name' => 'Lift Rental',
            'price' => 100, 'quantity' => 1, 'total' => 100,
            'delivery_date' => '2026-07-01',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.service-management.tickets.store'), $this->validPayload([
                'order_id' => $orderId,
            ]))
            ->assertRedirect();

        // Only the reference fields are stored — rental_date derived from the order
        $this->assertDatabaseHas('service_tickets', [
            'order_id'    => $orderId,
            'customer_id' => $customerId,
            'rental_date' => '2026-07-01 00:00:00',
        ]);
    }

    // 5. Personnel assignment uses existing users
    public function test_personnel_assignment_uses_existing_users(): void
    {
        $tech = User::create([
            'unique_id' => 'test-tech', 'employee_code' => '02',
            'first_name' => 'Tech', 'last_name' => 'One',
            'email' => 'tech@test.local', 'status' => 'Active',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.service-management.tickets.store'), $this->validPayload([
                'personnel' => [$tech->id],
            ]))
            ->assertRedirect();

        $ticket = ServiceTicket::first();
        $this->assertDatabaseHas('service_ticket_personnel', [
            'service_ticket_id' => $ticket->id,
            'employee_id'       => $tech->id,
        ]);
        $this->assertTrue($ticket->personnel->contains('id', $tech->id));
    }

    // 6. Blocked status requires reason and expected action date
    public function test_blocked_status_requires_reason_and_expected_date(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.service-management.tickets.store'), $this->validPayload([
                'repair_status' => RepairStatus::WaitingOnParts->value,
            ]))
            ->assertSessionHasErrors(['blocked_reason', 'expected_action_date']);

        $this->assertDatabaseCount('service_tickets', 0);

        // Same rule on the quick status endpoint
        $ticket = $this->makeTicket();
        $this->actingAs($this->admin)
            ->post(route('admin.service-management.tickets.status', $ticket), [
                'repair_status' => RepairStatus::WaitingOnParts->value,
            ])
            ->assertSessionHasErrors(['blocked_reason', 'expected_action_date']);
    }

    // 7. Blocked ticket lands in blocked queue, not active queue
    public function test_blocked_ticket_in_blocked_queue_not_active(): void
    {
        $ticket = $this->makeTicket();

        $this->actingAs($this->admin)
            ->post(route('admin.service-management.tickets.status', $ticket), [
                'repair_status'        => RepairStatus::WaitingOnParts->value,
                'blocked_reason'       => 'Parts on backorder',
                'expected_action_date' => now()->addDays(5)->format('Y-m-d'),
            ])
            ->assertRedirect();

        $this->assertFalse(ServiceTicket::activeQueue()->pluck('id')->contains($ticket->id));
        $this->assertTrue(ServiceTicket::blockedQueue()->pluck('id')->contains($ticket->id));
        $this->assertSame('Parts on backorder', $ticket->fresh()->blocked_reason);

        // Returning to an active status clears the blocking context
        $this->actingAs($this->admin)
            ->post(route('admin.service-management.tickets.status', $ticket), [
                'repair_status' => RepairStatus::InProgress->value,
            ]);
        $fresh = $ticket->fresh();
        $this->assertNull($fresh->blocked_reason);
        $this->assertNull($fresh->expected_action_date);
        $this->assertTrue(ServiceTicket::activeQueue()->pluck('id')->contains($ticket->id));
    }

    // 8. Completed sets completed_at
    public function test_completed_ticket_sets_completed_at(): void
    {
        $ticket = $this->makeTicket();
        $this->assertNull($ticket->completed_at);

        $this->actingAs($this->admin)
            ->post(route('admin.service-management.tickets.status', $ticket), [
                'repair_status' => RepairStatus::Completed->value,
            ]);

        $this->assertNotNull($ticket->fresh()->completed_at);
    }

    // 9. Closed sets closed_at; reopening preserves timestamps
    public function test_closed_ticket_sets_closed_at_and_reopen_preserves(): void
    {
        $ticket = $this->makeTicket(['repair_status' => RepairStatus::Completed->value]);
        $ticket->transitionTo(RepairStatus::Completed);
        $completedAt = $ticket->fresh()->completed_at;

        $this->actingAs($this->admin)
            ->post(route('admin.service-management.tickets.status', $ticket), [
                'repair_status' => RepairStatus::Closed->value,
            ]);

        $fresh = $ticket->fresh();
        $this->assertNotNull($fresh->closed_at);

        // Reopen — prior timestamps must survive
        $this->actingAs($this->admin)
            ->post(route('admin.service-management.tickets.status', $ticket), [
                'repair_status' => RepairStatus::Open->value,
            ]);
        $reopened = $ticket->fresh();
        $this->assertEquals($completedAt, $reopened->completed_at);
        $this->assertNotNull($reopened->closed_at);
    }
}
