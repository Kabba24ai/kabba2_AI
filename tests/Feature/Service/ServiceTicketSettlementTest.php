<?php

namespace Tests\Feature\Service;

use App\Enums\Service\FinancialStatus;
use App\Enums\Service\RepairStatus;
use App\Enums\Service\ResponsibilityDecision;
use App\Enums\Service\ServiceChargeType;
use App\Enums\Service\ServiceLocation;
use App\Enums\Service\ServicePriority;
use App\Enums\Service\ServiceTicketEventType;
use App\Enums\Service\ServiceType;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Service\ServiceTicket;
use App\Services\ServiceManagement\SettlementPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ServiceTicketSettlementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Equipment $equipment;
    private int $orderId;
    private int $customerId;

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

        $this->customerId = DB::table('customers')->insertGetId([
            'unique_id' => 'test-cust', 'first_name' => 'Mike', 'last_name' => 'Harrison',
        ]);
        $this->orderId = DB::table('orders')->insertGetId([
            'unique_id' => 'test-ord', 'order_number' => '#9001',
            'order_date' => now()->toDateString(), 'customer_name' => 'Mike Harrison',
            'customer_id' => $this->customerId,
        ]);

        $this->actingAs($this->admin);
    }

    /** A completed, authorized, customer-pay ticket attached to an order. */
    private function makeSettleableTicket(): ServiceTicket
    {
        $ticket = ServiceTicket::create([
            'service_type'             => ServiceType::CustomerDamageRepair->value,
            'service_location'         => ServiceLocation::InShop->value,
            'priority'                 => ServicePriority::Normal->value,
            'repair_status'            => RepairStatus::Open->value,
            'financial_responsibility' => 'pending',
            'financial_status'         => FinancialStatus::NotBillable->value,
            'equipment_id'             => $this->equipment->id,
            'order_id'                 => $this->orderId,
            'customer_id'              => $this->customerId,
            'opened_at'                => now(),
        ]);

        $ticket->completeDiagnostic();
        $ticket->decideResponsibility(ResponsibilityDecision::CustomerPay);
        $ticket = $ticket->fresh();
        $ticket->approveEstimate('Mike Harrison');
        $ticket->fresh()->authorizeRepair();
        $ticket->fresh()->transitionTo(RepairStatus::Completed);

        return $ticket->fresh();
    }

    /** Standard line items: labor 190 + labor-charge 50, parts 200 (+80 non-billable), travel 75. */
    private function addStandardLines(ServiceTicket $ticket): void
    {
        $ticket->laborEntries()->create(['labor_date' => now()->toDateString(), 'hours' => 2, 'labor_rate' => 95, 'billable' => true]);
        $ticket->chargeLines()->create(['charge_type' => ServiceChargeType::Labor->value, 'description' => 'Diag labor flat', 'unit_amount' => 50, 'billable' => true]);
        $ticket->partsUsed()->create(['description' => 'Final drive seal kit', 'quantity' => 2, 'customer_price' => 100, 'billable' => true]);
        $ticket->partsUsed()->create(['description' => 'Shop-made bracket', 'quantity' => 1, 'customer_price' => 80, 'billable' => false]);
        $ticket->chargeLines()->create(['charge_type' => ServiceChargeType::Travel->value, 'description' => 'Site trip', 'unit_amount' => 75, 'billable' => true]);
    }

    // 1. Package calculates labor, parts, charges, credits, and final amount
    public function test_settlement_package_calculates_correctly(): void
    {
        $ticket = $this->makeSettleableTicket();
        $this->addStandardLines($ticket);

        // Creditable diagnostic fee: paid, creditable, not yet credited
        $ticket->fresh()->update([
            'diagnostic_fee_required' => true, 'diagnostic_fee_amount' => 125,
            'diagnostic_fee_paid' => true, 'diagnostic_fee_creditable' => true,
        ]);

        $package = SettlementPackage::fromTicket($ticket->fresh());

        $this->assertEquals(240.00, $package->laborTotal());   // 190 labor + 50 labor charge line
        $this->assertEquals(200.00, $package->partsTotal());   // 2 × 100; non-billable excluded
        $this->assertEquals(75.00, $package->otherTotal());    // travel
        $this->assertEquals(515.00, $package->subtotal());
        $this->assertEquals(125.00, $package->creditsTotal()); // diagnostic fee credit
        $this->assertEquals(390.00, $package->finalAmount());
    }

    // 2. Editing labor recalculates totals
    public function test_editing_labor_recalculates_totals(): void
    {
        $ticket = $this->makeSettleableTicket();
        $entry = $ticket->laborEntries()->create(['labor_date' => now()->toDateString(), 'hours' => 2, 'labor_rate' => 95, 'billable' => true]);

        $this->assertEquals(190.00, SettlementPackage::fromTicket($ticket->fresh())->finalAmount());

        $this->put(route('admin.service-management.tickets.labor.update', [$ticket, $entry]), [
            'labor_date' => now()->toDateString(), 'hours' => 3, 'labor_rate' => 100, 'from' => 'settlement',
        ])->assertRedirect(route('admin.service-management.tickets.settlement.preview', $ticket));

        $this->assertEquals(300.00, SettlementPackage::fromTicket($ticket->fresh())->finalAmount());
    }

    // 3. Editing parts recalculates totals (incl. Mark Non-Billable)
    public function test_editing_parts_recalculates_totals(): void
    {
        $ticket = $this->makeSettleableTicket();
        $part = $ticket->partsUsed()->create(['description' => 'Hose', 'quantity' => 1, 'customer_price' => 120, 'billable' => true]);

        $this->assertEquals(120.00, SettlementPackage::fromTicket($ticket->fresh())->partsTotal());

        // Mark non-billable — drops out of the settlement entirely
        $this->put(route('admin.service-management.tickets.parts.update', [$ticket, $part]), [
            'description' => 'Hose', 'quantity' => 1, 'customer_price' => 120, 'billable' => 0, 'from' => 'settlement',
        ]);
        $this->assertEquals(0.00, SettlementPackage::fromTicket($ticket->fresh())->partsTotal());

        // No inventory rows were touched
        $this->assertSame(0, DB::table('parts')->count());
    }

    // 4. Editing credits recalculates totals
    public function test_editing_credits_recalculates_totals(): void
    {
        $ticket = $this->makeSettleableTicket();
        $ticket->laborEntries()->create(['labor_date' => now()->toDateString(), 'hours' => 4, 'labor_rate' => 100, 'billable' => true]);
        $ticket->fresh()->update([
            'parts_deposit_required' => true, 'parts_deposit_amount' => 150,
            'parts_deposit_paid' => true, 'parts_deposit_creditable' => true,
        ]);

        $this->assertEquals(250.00, SettlementPackage::fromTicket($ticket->fresh())->finalAmount()); // 400 - 150

        // Remove the credit by marking the deposit non-creditable (settlement page action)
        $this->put(route('admin.service-management.tickets.deposit.update', $ticket), [
            'parts_deposit_creditable' => 0, 'from' => 'settlement',
        ]);

        $fresh = SettlementPackage::fromTicket($ticket->fresh());
        $this->assertEquals(0.00, $fresh->creditsTotal());
        $this->assertEquals(400.00, $fresh->finalAmount());
    }

    // 5. Users cannot manually edit calculated totals — forged amounts are ignored
    public function test_calculated_totals_cannot_be_manually_edited(): void
    {
        $ticket = $this->makeSettleableTicket();
        $ticket->laborEntries()->create(['labor_date' => now()->toDateString(), 'hours' => 2, 'labor_rate' => 95, 'billable' => true]);

        $this->post(route('admin.service-management.tickets.settlement.store', $ticket), [
            'final_amount' => 1.00, 'subtotal' => 1.00, 'credits_total' => 999.00,
        ]);

        $settlement = $ticket->fresh()->activeSettlement();
        $this->assertNotNull($settlement);
        // Stored amounts come from the package math, not the request
        $this->assertEquals(190.00, (float) $settlement->final_amount);
        $this->assertEquals(190.00, (float) $settlement->subtotal);
        $this->assertEquals(0.00, (float) $settlement->credits_total);
        $this->assertEquals(190.00, (float) $settlement->extraCharge->amount);
    }

    // 6. Charge only when all gates satisfied
    public function test_charge_requires_all_gates(): void
    {
        // Not completed
        $ticket = ServiceTicket::create([
            'service_type'             => ServiceType::CustomerDamageRepair->value,
            'service_location'         => ServiceLocation::InShop->value,
            'priority'                 => ServicePriority::Normal->value,
            'repair_status'            => RepairStatus::InProgress->value,
            'financial_responsibility' => 'customer_pay',
            'financial_status'         => FinancialStatus::NotBillable->value,
            'equipment_id'             => $this->equipment->id,
            'order_id'                 => $this->orderId,
            'customer_id'              => $this->customerId,
            'opened_at'                => now(),
        ]);
        $ticket->laborEntries()->create(['labor_date' => now()->toDateString(), 'hours' => 1, 'labor_rate' => 100, 'billable' => true]);

        $this->assertFalse($ticket->fresh()->canCreateCustomerCharge());
        $this->post(route('admin.service-management.tickets.settlement.store', $ticket));
        $this->assertNull($ticket->fresh()->activeSettlement());
        $this->assertDatabaseCount('order_extra_charges', 0);

        // Full gates satisfied → charge created
        $good = $this->makeSettleableTicket();
        $good->laborEntries()->create(['labor_date' => now()->toDateString(), 'hours' => 1, 'labor_rate' => 100, 'billable' => true]);
        $this->post(route('admin.service-management.tickets.settlement.store', $good));

        $settlement = $good->fresh()->activeSettlement();
        $this->assertNotNull($settlement);
        $this->assertSame(FinancialStatus::ChargeCreated, $good->fresh()->financial_status);
        $this->assertDatabaseHas('order_extra_charges', [
            'id'          => $settlement->order_extra_charge_id,
            'type'        => 'service',
            'order_id'    => $this->orderId,
            'customer_id' => $this->customerId,
            'source_type' => 'service_ticket_settlement',
            'source_id'   => $settlement->id,
        ]);
        // No payment recorded on the charge — that's the Financial Engine's job
        $this->assertNull($settlement->extraCharge->payment_type);
        $this->assertDatabaseCount('order_payments', 0);
    }

    // 6b. Non-customer-pay tickets can never create a charge
    public function test_warranty_ticket_cannot_create_customer_charge(): void
    {
        $ticket = $this->makeSettleableTicket();
        $ticket->fresh()->decideResponsibility(ResponsibilityDecision::OemWarranty);

        $this->assertFalse($ticket->fresh()->canCreateCustomerCharge());
        $this->post(route('admin.service-management.tickets.settlement.store', $ticket));
        $this->assertDatabaseCount('order_extra_charges', 0);
    }

    // 7. Settlement Package contains all required references
    public function test_settlement_package_contains_required_references(): void
    {
        $ticket = $this->makeSettleableTicket();
        $this->addStandardLines($ticket);
        $ticket->fresh()->update([
            'diagnostic_fee_required' => true, 'diagnostic_fee_amount' => 125,
            'diagnostic_fee_paid' => true, 'diagnostic_fee_creditable' => true,
        ]);

        $this->post(route('admin.service-management.tickets.settlement.store', $ticket));

        $settlement = $ticket->fresh()->activeSettlement();
        $package    = $settlement->package;

        $this->assertSame($ticket->id, $package['service_ticket_id']);
        $this->assertSame($this->orderId, $package['order_id']);
        $this->assertSame($this->customerId, $package['customer_id']);
        $this->assertSame($this->equipment->id, $package['equipment_id']);
        $this->assertSame($this->admin->id, $package['created_by']);
        $this->assertNotEmpty($package['created_at']);
        $this->assertCount(2, $package['labor_lines']);
        $this->assertCount(1, $package['parts_lines']); // non-billable part excluded
        $this->assertCount(1, $package['other_lines']);
        $this->assertCount(1, $package['credits']);
        $this->assertEquals(390.00, $package['final_amount']);

        // Line-level source tracking survives into the package
        $this->assertSame('service_ticket_labor_entry', $package['labor_lines'][0]['source_type']);

        // Consumed credits are marked applied on the ticket
        $fresh = $ticket->fresh();
        $this->assertTrue($fresh->diagnostic_fee_credited);

        // Consumed lines are stamped to prevent double billing
        $this->assertSame(0, $fresh->chargeLines()->where('billable', true)->whereNull('source_type')->count());
    }

    // 8. Duplicate charge creation is prevented
    public function test_duplicate_charge_creation_is_prevented(): void
    {
        $ticket = $this->makeSettleableTicket();
        $ticket->laborEntries()->create(['labor_date' => now()->toDateString(), 'hours' => 1, 'labor_rate' => 100, 'billable' => true]);

        $this->post(route('admin.service-management.tickets.settlement.store', $ticket));
        $this->assertDatabaseCount('order_extra_charges', 1);
        $this->assertDatabaseCount('service_ticket_settlements', 1);

        // Second attempt — clearly rejected, nothing new created
        $this->post(route('admin.service-management.tickets.settlement.store', $ticket));
        $this->assertDatabaseCount('order_extra_charges', 1);
        $this->assertDatabaseCount('service_ticket_settlements', 1);
        $this->assertTrue(
            $ticket->fresh()->chargeCreationBlockers()->contains('A customer settlement already exists for this ticket')
        );
    }

    // 9. Timeline records preview generation, updates, and charge creation
    public function test_timeline_records_settlement_events(): void
    {
        $ticket = $this->makeSettleableTicket();
        $entry = $ticket->laborEntries()->create(['labor_date' => now()->toDateString(), 'hours' => 1, 'labor_rate' => 100, 'billable' => true]);

        // Preview logged once, not on every visit
        $this->get(route('admin.service-management.tickets.settlement.preview', $ticket))->assertOk();
        $this->get(route('admin.service-management.tickets.settlement.preview', $ticket))->assertOk();
        $this->assertSame(1, $ticket->events()->where('event_type', ServiceTicketEventType::SettlementPreviewGenerated->value)->count());

        // Recalculation before creation logged
        $this->put(route('admin.service-management.tickets.labor.update', [$ticket, $entry]), [
            'labor_date' => now()->toDateString(), 'hours' => 2, 'labor_rate' => 100, 'from' => 'settlement',
        ]);
        $this->assertTrue(
            $ticket->events()->where('event_type', ServiceTicketEventType::SettlementUpdated->value)->exists()
        );

        // Charge creation logged with amount + reference
        $this->post(route('admin.service-management.tickets.settlement.store', $ticket));
        $event = $ticket->events()->where('event_type', ServiceTicketEventType::CustomerChargeCreated->value)->first();
        $this->assertNotNull($event);
        $this->assertSame($this->admin->id, $event->user_id);
        $this->assertStringContainsString('200.00', $event->notes);
        $this->assertNotNull($event->metadata['order_extra_charge_id'] ?? null);
    }

    // Settlement preview renders with summary and gate state
    public function test_settlement_preview_page_renders(): void
    {
        $ticket = $this->makeSettleableTicket();
        $this->addStandardLines($ticket);

        $this->get(route('admin.service-management.tickets.settlement.preview', $ticket))
            ->assertOk()
            ->assertSee('Customer Settlement')
            ->assertSee('Settlement Summary')
            ->assertSee('Amount to Create')
            ->assertSee('Create Customer Charge');

        // After creation the preview is read-only with a clear banner
        $this->post(route('admin.service-management.tickets.settlement.store', $ticket));
        $this->get(route('admin.service-management.tickets.settlement.preview', $ticket))
            ->assertOk()
            ->assertSee('Customer charge already created');

        // Ticket page shows the Financial Settlement card
        $this->get(route('admin.service-management.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Financial Settlement')
            ->assertSee('Customer Charge Created');
    }
}
