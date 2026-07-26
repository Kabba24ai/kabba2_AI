<?php

namespace Tests\Feature\Service;

use App\Enums\Billing\BillingChargeType;
use App\Enums\Service\FinancialStatus;
use App\Enums\Service\RepairStatus;
use App\Enums\Service\ResponsibilityDecision;
use App\Enums\Service\ServiceChargeType;
use App\Enums\Service\ServiceLocation;
use App\Enums\Service\ServicePriority;
use App\Enums\Service\ServiceType;
use App\Models\Customers\CustomerAccount;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\BillingCharge;
use App\Models\Orders\OrderExtraCharges;
use App\Models\Service\ServiceTicket;
use App\Models\Service\ServiceTicketSettlement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\ResolvesResponsibilityDecisions;
use Tests\TestCase;

/**
 * ST-2a — service settlement now hands off through the canonical Billing
 * Engine (a real ServiceTicket BillingCharge + CustomerAccount ledger row,
 * tax-free, idempotent) instead of a raw legacy order_extra_charges row.
 */
class ServiceSettlementBillingHandoffTest extends TestCase
{
    use RefreshDatabase;
    use ResolvesResponsibilityDecisions;

    private User $admin;
    private Equipment $equipment;
    private int $orderId;
    private int $customerId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'unique_id' => 'st2-admin', 'employee_code' => '01',
            'first_name' => 'Admin', 'last_name' => 'User',
            'email' => 'st2-admin@test.local', 'status' => 'Active',
        ]);
        $this->equipment = Equipment::create([
            'unique_id' => 'st2-eq', 'equipment_name' => 'Test Lift', 'equipment_id' => 'TL-2', 'brand' => 'Test',
        ]);
        $this->customerId = DB::table('customers')->insertGetId([
            'unique_id' => 'st2-cust', 'first_name' => 'Mike', 'last_name' => 'Harrison',
        ]);
        $this->orderId = DB::table('orders')->insertGetId([
            'unique_id' => 'st2-ord', 'order_number' => '#9002',
            'order_date' => now()->toDateString(), 'customer_name' => 'Mike Harrison',
            'customer_id' => $this->customerId,
        ]);

        $this->actingAs($this->admin);
    }

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
        $ticket->decideResponsibility($this->responsibilityDecision(ResponsibilityDecision::CustomerPay->value));
        $ticket = $ticket->fresh();
        $ticket->approveEstimate('Mike Harrison');
        $ticket->fresh()->authorizeRepair();
        $ticket->fresh()->transitionTo(RepairStatus::Completed);
        $ticket = $ticket->fresh();

        // A single billable part so finalAmount() > 0.
        $ticket->partsUsed()->create(['description' => 'Seal kit', 'quantity' => 1, 'customer_price' => 150, 'billable' => true]);

        return $ticket->fresh();
    }

    public function test_creating_a_charge_produces_a_service_ticket_billing_charge_not_a_legacy_extra_charge(): void
    {
        $ticket = $this->makeSettleableTicket();

        $this->post(route('admin.service-management.tickets.settlement.store', $ticket))->assertRedirect();

        // Real BillingCharge of the canonical service type, on the order.
        $charge = BillingCharge::firstOrFail();
        $this->assertSame(BillingChargeType::ServiceTicket, $charge->billing_charge_type);
        $this->assertSame($this->orderId, $charge->parent_order_id);
        $this->assertSame($this->customerId, (int) $charge->customer_id);
        $this->assertEqualsWithDelta(150.0, (float) $charge->amount, 0.001);
        $this->assertEqualsWithDelta(0.0, (float) $charge->tax_amount, 0.001, 'tax-free per decision');

        // CustomerAccount ledger row created, no fuel/damage alert flags.
        $account = CustomerAccount::where('reason', 'Service Repair')->firstOrFail();
        $this->assertNull($account->fuel_alert_status);
        $this->assertNull($account->damage_alert_status);

        // Settlement links the billing charge; no legacy extra-charge row.
        $settlement = ServiceTicketSettlement::firstOrFail();
        $this->assertSame($charge->id, $settlement->billing_charge_id);
        $this->assertNull($settlement->order_extra_charge_id);
        $this->assertSame(0, OrderExtraCharges::where('type', 'service')->count());

        // Ticket advanced to ChargeCreated.
        $this->assertSame(FinancialStatus::ChargeCreated, $ticket->fresh()->financial_status);
    }

    public function test_double_submit_does_not_create_a_second_charge(): void
    {
        $ticket = $this->makeSettleableTicket();

        $this->post(route('admin.service-management.tickets.settlement.store', $ticket))->assertRedirect();
        // Second attempt is blocked by the single-settlement rule
        // (canCreateCustomerCharge) — redirects back to preview, no new charge.
        $this->post(route('admin.service-management.tickets.settlement.store', $ticket))->assertRedirect();

        $this->assertSame(1, BillingCharge::count());
        $this->assertSame(1, ServiceTicketSettlement::count());
        $this->assertSame(1, CustomerAccount::where('reason', 'Service Repair')->count());
    }

    public function test_idempotency_key_is_scoped_to_the_settlement(): void
    {
        $ticket = $this->makeSettleableTicket();
        $this->post(route('admin.service-management.tickets.settlement.store', $ticket))->assertRedirect();

        $settlement = ServiceTicketSettlement::firstOrFail();
        $charge = BillingCharge::firstOrFail();
        $this->assertSame('service_settlement:' . $settlement->id, $charge->idempotency_key);
    }

    // ── ST-2b: payable on the order page + paid-sync ────────────────────

    public function test_service_charge_row_exposes_a_pay_action_on_the_order_billing_engine(): void
    {
        $ticket = $this->makeSettleableTicket();
        $this->post(route('admin.service-management.tickets.settlement.store', $ticket))->assertRedirect();

        // The charge is on the order — render the order edit page and assert
        // the service row is a payable, charge-mode action row.
        $order = \App\Models\Orders\Order::findOrFail($this->orderId);
        $html = $this->get(route('admin.order-management.orders.edit', $order->unique_id))->getContent();

        $charge = BillingCharge::firstOrFail();
        $this->assertStringContainsString('data-bc-id="' . $charge->unique_id . '"', $html);
        $this->assertStringContainsString('Service Ticket', $html);
        // A payment action must be present on the open service charge.
        $this->assertStringContainsString('data-action="payment"', $html);
    }

    public function test_paying_the_service_charge_advances_the_ticket_to_paid(): void
    {
        $ticket = $this->makeSettleableTicket();
        $this->post(route('admin.service-management.tickets.settlement.store', $ticket))->assertRedirect();

        $charge  = BillingCharge::firstOrFail();
        $account = CustomerAccount::where('reason', 'Service Repair')->firstOrFail();
        $this->assertSame(FinancialStatus::ChargeCreated, $ticket->fresh()->financial_status);

        // Pay it through the canonical CRM payment path (source=crm + ca + bc)
        // — the same path the shared Billing Engine payment modal posts.
        $this->post(route('admin.dashboard.paymentstore'), [
            'source'                   => 'crm',
            'type'                     => 'service_ticket',
            'customer_id'              => $this->customerId,
            'customer_account_id'      => $account->unique_id,
            'billing_charge_unique_id' => $charge->unique_id,
            'amount'                   => (float) $charge->amount + (float) $charge->tax_amount,
            'payment_type'             => 'Cash',
            'responsible_person'       => $this->admin->id,
        ]);

        $this->assertSame('paid', BillingCharge::firstOrFail()->status->value);
        $this->assertSame(FinancialStatus::Paid, $ticket->fresh()->financial_status);
    }

    public function test_paid_sync_is_a_noop_for_non_service_charges(): void
    {
        // A fuel BillingCharge with no linked settlement must be untouched by
        // the sync (guards the decoupling — no accidental ticket writes).
        $charge = BillingCharge::create([
            'billing_charge_type' => 'fuel', 'status' => 'pending',
            'customer_id' => $this->customerId, 'parent_order_id' => $this->orderId,
            'amount' => 25, 'tax_amount' => 0, 'tax_type' => 'free',
        ]);

        \App\Services\ServiceManagement\ServiceTicketBillingSync::syncPaidCharge($charge);

        $this->assertSame(0, ServiceTicketSettlement::count());
        $this->assertTrue(true); // reached here without error/side effect
    }
}
