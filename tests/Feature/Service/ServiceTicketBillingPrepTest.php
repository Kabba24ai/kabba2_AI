<?php

namespace Tests\Feature\Service;

use App\Enums\Service\FinancialResponsibility;
use App\Enums\Service\FinancialStatus;
use App\Enums\Service\RepairStatus;
use App\Enums\Service\ServiceChargeType;
use App\Enums\Service\ServiceLocation;
use App\Enums\Service\ServicePriority;
use App\Enums\Service\ServiceType;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Service\ServiceTicket;
use App\Models\Service\ServiceTicketLaborEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ServiceTicketBillingPrepTest extends TestCase
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

    private function makeTicket(array $overrides = []): ServiceTicket
    {
        return ServiceTicket::create(array_merge([
            'service_type'             => ServiceType::CustomerDamageRepair->value,
            'service_location'         => ServiceLocation::InShop->value,
            'priority'                 => ServicePriority::Normal->value,
            'repair_status'            => RepairStatus::InProgress->value,
            'financial_responsibility' => FinancialResponsibility::CustomerPay->value,
            'financial_status'         => FinancialStatus::NotBillable->value,
            'equipment_id'             => $this->equipment->id,
            'opened_at'                => now(),
        ], $overrides));
    }

    // 1. Labor entry belongs to ticket and employee
    public function test_labor_entry_belongs_to_ticket_and_employee(): void
    {
        $ticket = $this->makeTicket();

        $this->actingAs($this->admin)
            ->post(route('admin.service-management.tickets.labor.store', $ticket), [
                'employee_id' => $this->admin->id,
                'labor_date'  => '2026-07-04',
                'hours'       => 2,
                'labor_rate'  => 95,
            ])
            ->assertRedirect();

        $entry = ServiceTicketLaborEntry::first();
        $this->assertSame($ticket->id, $entry->ticket->id);
        $this->assertSame($this->admin->id, $entry->employee->id);
        $this->assertTrue($ticket->laborEntries()->pluck('id')->contains($entry->id));
    }

    // 2. Labor total calculates correctly — manual hours and derived hours
    public function test_labor_total_calculates_correctly(): void
    {
        $ticket = $this->makeTicket();

        // Manual hours win even when a time range is present
        $manual = $ticket->laborEntries()->create([
            'labor_date' => '2026-07-04',
            'start_time' => '08:00', 'end_time' => '17:00',
            'hours'      => 2.5,
            'labor_rate' => 100,
        ]);
        $this->assertEquals(2.5, (float) $manual->hours);
        $this->assertEquals(250.00, (float) $manual->labor_total);

        // Hours derived from the time range when not supplied
        $derived = $ticket->laborEntries()->create([
            'labor_date' => '2026-07-04',
            'start_time' => '08:00', 'end_time' => '09:30',
            'labor_rate' => 80,
        ]);
        $this->assertEquals(1.5, (float) $derived->hours);
        $this->assertEquals(120.00, (float) $derived->labor_total);

        // No rate → no total, but hours retained
        $noRate = $ticket->laborEntries()->create([
            'labor_date' => '2026-07-04',
            'hours'      => 3,
        ]);
        $this->assertNull($noRate->labor_total);

        $this->assertEquals(370.00, $ticket->fresh()->labor_total);
    }

    // 3. Charge line total calculates correctly
    public function test_charge_line_total_calculates_correctly(): void
    {
        $ticket = $this->makeTicket();

        $line = $ticket->chargeLines()->create([
            'charge_type' => ServiceChargeType::Parts->value,
            'description' => 'Final drive assembly',
            'quantity'    => 2,
            'unit_amount' => 1249.99,
        ]);
        $this->assertEquals(2499.98, (float) $line->line_total);

        // Quantity defaults to 1
        $single = $ticket->chargeLines()->create([
            'charge_type' => ServiceChargeType::ShopSupplies->value,
            'description' => 'Shop supplies',
            'unit_amount' => 15.50,
        ]);
        $this->assertEquals(15.50, (float) $single->line_total);

        $this->assertEquals(2515.48, $ticket->fresh()->charge_line_total);
    }

    // 4. Customer-pay completed ticket with billable total becomes ready_to_bill
    public function test_customer_pay_completed_with_billable_total_becomes_ready_to_bill(): void
    {
        $ticket = $this->makeTicket();
        // Satisfy the Phase 2E.5 gate — the status endpoint enforces authorization
        $ticket->update(['repair_authorized' => true]);
        $ticket->laborEntries()->create([
            'labor_date' => '2026-07-04', 'hours' => 2, 'labor_rate' => 95, 'billable' => true,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.service-management.tickets.status', $ticket), [
                'repair_status' => RepairStatus::Completed->value,
            ]);

        $this->assertSame(FinancialStatus::ReadyToBill, $ticket->fresh()->financial_status);
    }

    // 4b. No billable amount → financial status untouched
    public function test_customer_pay_completed_without_billable_total_stays_put(): void
    {
        $ticket = $this->makeTicket();
        $ticket->laborEntries()->create([
            'labor_date' => '2026-07-04', 'hours' => 2, 'labor_rate' => 95, 'billable' => false,
        ]);

        $ticket->fresh()->transitionTo(RepairStatus::Completed);

        $this->assertSame(FinancialStatus::NotBillable, $ticket->fresh()->financial_status);
    }

    // 4c. Tickets already in the charge/payment pipeline are never overwritten
    public function test_paid_ticket_is_not_reset_to_ready_to_bill(): void
    {
        $ticket = $this->makeTicket(['financial_status' => FinancialStatus::Paid->value]);
        $ticket->chargeLines()->create([
            'charge_type' => ServiceChargeType::Labor->value,
            'description' => 'Repair labor', 'unit_amount' => 200, 'billable' => true,
        ]);

        $ticket->fresh()->transitionTo(RepairStatus::Completed);

        $this->assertSame(FinancialStatus::Paid, $ticket->fresh()->financial_status);
    }

    // 5. Internal ticket does not become ready_to_bill
    public function test_internal_ticket_does_not_become_ready_to_bill(): void
    {
        $ticket = $this->makeTicket([
            'financial_responsibility' => FinancialResponsibility::InternalExpense->value,
        ]);
        $ticket->laborEntries()->create([
            'labor_date' => '2026-07-04', 'hours' => 4, 'labor_rate' => 90, 'billable' => true,
        ]);

        $ticket->fresh()->transitionTo(RepairStatus::Completed);

        $fresh = $ticket->fresh();
        $this->assertSame(FinancialStatus::NotBillable, $fresh->financial_status);
        $this->assertEquals(360.00, $fresh->internal_cost_total);
    }

    // 5b. Internal repairs default labor to non-billable
    public function test_internal_ticket_labor_defaults_non_billable(): void
    {
        $ticket = $this->makeTicket([
            'financial_responsibility' => FinancialResponsibility::InternalExpense->value,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.service-management.tickets.labor.store', $ticket), [
                'labor_date' => '2026-07-04', 'hours' => 1,
            ]);

        $this->assertFalse(ServiceTicketLaborEntry::first()->billable);

        // Customer-pay tickets default to billable
        $cp = $this->makeTicket();
        $this->actingAs($this->admin)
            ->post(route('admin.service-management.tickets.labor.store', $cp), [
                'labor_date' => '2026-07-04', 'hours' => 1,
            ]);
        $this->assertTrue($cp->laborEntries()->first()->billable);
    }

    // 6. Warranty ticket keeps warranty financial status; billables feed the claim total
    public function test_warranty_ticket_does_not_create_customer_billing_status(): void
    {
        $ticket = $this->makeTicket([
            'financial_responsibility' => FinancialResponsibility::OemWarranty->value,
            'financial_status'         => FinancialStatus::WarrantyNotSubmitted->value,
        ]);
        $ticket->laborEntries()->create([
            'labor_date' => '2026-07-04', 'hours' => 3, 'labor_rate' => 110, 'billable' => true,
        ]);
        $ticket->chargeLines()->create([
            'charge_type' => ServiceChargeType::Parts->value,
            'description' => 'Warranty part', 'unit_amount' => 500, 'billable' => true,
        ]);

        $ticket->fresh()->transitionTo(RepairStatus::Completed);

        $fresh = $ticket->fresh();
        $this->assertSame(FinancialStatus::WarrantyNotSubmitted, $fresh->financial_status);
        $this->assertEquals(830.00, $fresh->warranty_claim_total);

        // Customer-pay tickets never carry a warranty claim total
        $this->assertEquals(0.0, $this->makeTicket()->warranty_claim_total);
    }

    // 7. Charge lines never touch Order Extra Payments / payment tables
    public function test_charge_lines_do_not_touch_order_payment_tables(): void
    {
        $extraChargesBefore = DB::table('order_extra_charges')->count();
        $paymentsBefore     = DB::table('order_payments')->count();

        $ticket = $this->makeTicket();
        $this->actingAs($this->admin)
            ->post(route('admin.service-management.tickets.charges.store', $ticket), [
                'charge_type' => ServiceChargeType::Miscellaneous->value,
                'description' => 'Prep-only charge',
                'unit_amount' => 99,
            ])
            ->assertRedirect();

        $ticket->fresh()->transitionTo(RepairStatus::Completed);

        $this->assertDatabaseCount('service_ticket_charge_lines', 1);
        $this->assertSame($extraChargesBefore, DB::table('order_extra_charges')->count());
        $this->assertSame($paymentsBefore, DB::table('order_payments')->count());
    }

    // 7b. Removing labor/charge lines works and is scoped to the ticket
    public function test_labor_and_charge_lines_can_be_removed_and_are_ticket_scoped(): void
    {
        $ticket = $this->makeTicket();
        $other  = $this->makeTicket();

        $entry = $ticket->laborEntries()->create(['labor_date' => '2026-07-04', 'hours' => 1]);
        $line  = $ticket->chargeLines()->create([
            'charge_type' => ServiceChargeType::Fuel->value, 'description' => 'Fuel', 'unit_amount' => 40,
        ]);

        // Wrong parent ticket → 404, nothing deleted
        $this->actingAs($this->admin)
            ->delete(route('admin.service-management.tickets.labor.destroy', [$other, $entry]))
            ->assertNotFound();

        $this->actingAs($this->admin)
            ->delete(route('admin.service-management.tickets.labor.destroy', [$ticket, $entry]))
            ->assertRedirect();
        $this->actingAs($this->admin)
            ->delete(route('admin.service-management.tickets.charges.destroy', [$ticket, $line]))
            ->assertRedirect();

        $this->assertSoftDeleted('service_ticket_labor_entries', ['id' => $entry->id]);
        $this->assertSoftDeleted('service_ticket_charge_lines', ['id' => $line->id]);
        $this->assertEquals(0.0, $ticket->fresh()->billable_total);
    }

    // 8. Existing dashboard still loads with billing-prep data present
    public function test_dashboard_still_loads(): void
    {
        $ticket = $this->makeTicket();
        $ticket->laborEntries()->create(['labor_date' => '2026-07-04', 'hours' => 2, 'labor_rate' => 95]);

        $this->actingAs($this->admin)
            ->get(route('admin.service-management.overview'))
            ->assertOk()
            ->assertSee('Service Operations');
    }

    // 8b. Ticket detail page renders labor and charge sections
    public function test_ticket_detail_renders_billing_prep_sections(): void
    {
        $ticket = $this->makeTicket();
        $ticket->laborEntries()->create([
            'employee_id' => $this->admin->id,
            'labor_date'  => '2026-07-04', 'hours' => 2, 'labor_rate' => 95,
        ]);
        $ticket->chargeLines()->create([
            'charge_type' => ServiceChargeType::Parts->value,
            'description' => 'Hydraulic hose', 'unit_amount' => 89.50,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.service-management.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Billing Preparation')
            ->assertSee('Hydraulic hose')
            ->assertSee('190.00');   // 2h × $95 labor total
    }
}
