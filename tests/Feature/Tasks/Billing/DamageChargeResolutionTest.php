<?php

namespace Tests\Feature\Tasks\Billing;

use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAccount;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\MaintenanceManagement\EquipmentSoftAssign;
use App\Models\Orders\BillingCharge;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Models\ProductManagement\ProductCategory;
use App\Models\Service\CustomerDamageStaging;
use App\Models\Service\ServiceTicket;
use App\Models\Service\ServiceTicketSettlement;
use App\Services\Alerts\ChargeAlertQueue;
use App\Services\Billing\DamageChargeSourceResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Damage Charge Resolution workspace — the fuel-emulating sibling under Task
 * Manager → Billing Operations. Separate queue (never mixed with fuel);
 * canonical BillingCharge-keyed actions; source links back to the originating
 * Customer Checklist / Service Ticket; absent source handled honestly.
 */
class DamageChargeResolutionTest extends TestCase
{
    use RefreshDatabase;

    private const DAMAGE_ROUTE = 'admin.tasks.billing.damage-charges.index';
    private const FUEL_ROUTE   = 'admin.tasks.billing.fuel-charges.index';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'first_name' => 'Bill', 'last_name' => 'Admin',
            'email' => 'billing-admin@test.local', 'status' => 'Active',
        ]);
        $this->actingAs($this->admin);
    }

    private function customer(string $first): Customer
    {
        return Customer::create([
            'first_name' => $first, 'last_name' => 'Cust',
            'email' => Str::lower($first) . '-' . Str::random(4) . '@test.local', 'status' => 'Active',
        ]);
    }

    private function crmDamageCharge(Customer $c, string $status = 'pending', float $amount = 120.0): CustomerAccount
    {
        $a = new CustomerAccount();
        $a->customer_id = $c->id;
        $a->amount = $amount;
        $a->reason = 'Damages';
        $a->type = 'charge';
        $a->date = now();
        $a->sales_tax = 0;
        $a->sales_tax_type = 'free';
        $a->damage_alert_status = $status;
        $a->save();

        return $a;
    }

    private function crmFuelCharge(Customer $c): CustomerAccount
    {
        $a = new CustomerAccount();
        $a->customer_id = $c->id;
        $a->amount = 40;
        $a->reason = 'Fuel Charge';
        $a->type = 'charge';
        $a->date = now();
        $a->sales_tax = 0;
        $a->sales_tax_type = 'free';
        $a->fuel_alert_status = 'pending';
        $a->save();

        return $a;
    }

    // ── Access / authorization ─────────────────────────────────────────────

    public function test_authorized_user_can_access_the_damage_workspace(): void
    {
        $this->get(route(self::DAMAGE_ROUTE))
            ->assertOk()
            ->assertSee('Damage Charge Resolution');
    }

    public function test_guest_is_blocked(): void
    {
        Auth::logout();

        $this->get(route(self::DAMAGE_ROUTE))->assertRedirect();
    }

    // ── Queue selection ────────────────────────────────────────────────────

    public function test_unresolved_damage_charge_appears(): void
    {
        $c = $this->customer('Danielle');
        $this->crmDamageCharge($c);

        $this->get(route(self::DAMAGE_ROUTE))
            ->assertOk()
            ->assertSee('Danielle Cust');
    }

    public function test_resolved_charge_is_excluded_from_the_active_queue(): void
    {
        $c = $this->customer('Rhonda');
        $this->crmDamageCharge($c, 'resolved');

        // Active queue must not show a resolved charge (consistent with fuel).
        $html = $this->get(route(self::DAMAGE_ROUTE))->getContent();
        $this->assertStringContainsString('No damage charge alerts found', $html);
    }

    public function test_fuel_and_damage_queues_remain_separate(): void
    {
        $damageCust = $this->customer('Damonly');
        $fuelCust   = $this->customer('Fuelonly');
        $this->crmDamageCharge($damageCust);
        $this->crmFuelCharge($fuelCust);

        // Damage workspace shows the damage customer, NOT the fuel-only one.
        $damageHtml = $this->get(route(self::DAMAGE_ROUTE))->getContent();
        $this->assertStringContainsString('Damonly Cust', $damageHtml);
        $this->assertStringNotContainsString('Fuelonly Cust', $damageHtml);

        // Fuel workspace shows the fuel customer, NOT the damage-only one.
        $fuelHtml = $this->get(route(self::FUEL_ROUTE))->getContent();
        $this->assertStringContainsString('Fuelonly Cust', $fuelHtml);
        $this->assertStringNotContainsString('Damonly Cust', $fuelHtml);
    }

    public function test_damage_summary_reconciles_with_canonical_service(): void
    {
        $this->crmDamageCharge($this->customer('Amy'));
        $this->crmDamageCharge($this->customer('Ben'));

        $this->assertSame(2, ChargeAlertQueue::damageAlerts()->count());

        $html = $this->get(route(self::DAMAGE_ROUTE))->getContent();
        $this->assertStringContainsString('data-metric="outstanding">2<', $html);
    }

    // ── Canonical BillingCharge actions ──────────────────────────────────────

    public function test_bridged_damage_row_targets_the_canonical_billing_charge(): void
    {
        $c = $this->customer('Bridged');
        $account = $this->crmDamageCharge($c);

        $bridge = BillingCharge::create([
            'billing_charge_type' => 'damage',
            'status' => 'pending',
            'customer_id' => $c->id,
            'amount' => 120.0,
            'tax_amount' => 0.0,
            'tax_type' => 'free',
            'customer_account_id' => $account->id,
        ]);

        $html = $this->get(route(self::DAMAGE_ROUTE))->getContent();

        // Actions operate on the canonical BillingCharge (charge-mode, keyed
        // by the bridge unique_id) — same contract as fuel.
        $this->assertStringContainsString('data-action-mode="charge"', $html);
        $this->assertStringContainsString('data-bc-id="' . $bridge->unique_id . '"', $html);
        $this->assertStringContainsString('data-type="damage"', $html);
        foreach (['notes', 'payment', 'resolve', 'uncollectible'] as $action) {
            $this->assertStringContainsString('data-action="' . $action . '"', $html, "missing {$action}");
        }
    }

    // ── Source resolution (Customer Checklist / Service Ticket / absent) ─────

    public function test_source_resolver_classifies_checklist_manual_and_absent(): void
    {
        $rows = collect([
            // Checklist origin: an order_product id with no staging bridge.
            ['source' => null, 'order_product' => ['id' => 999999], 'orderLink' => 'https://admin.example/order/abc'],
            // Manual origin: a CRM row.
            ['source' => 'crm', 'order_product' => null, 'orderLink' => null, 'crmLink' => 'https://admin.example/crm/xyz'],
            // No provable source: neither op nor crm.
            ['source' => null, 'order_product' => null, 'orderLink' => null, 'crmLink' => null],
        ]);

        $out = DamageChargeSourceResolver::enrich($rows);

        $this->assertSame('Customer Checklist', $out[0]['source_type']);
        $this->assertSame('https://admin.example/order/abc', $out[0]['source_link']);

        $this->assertSame('Manual', $out[1]['source_type']);
        $this->assertSame('https://admin.example/crm/xyz', $out[1]['source_link']);

        $this->assertNull($out[2]['source_type']);
        $this->assertNull($out[2]['source_link']);
    }

    public function test_source_resolver_links_service_ticket_backed_records(): void
    {
        $order = Order::create([
            'order_date' => now()->format('Y-m-d'),
            'customer_name' => 'Ticket Cust',
        ]);
        $op = $order->products()->create([
            'product_name' => 'Damaged Unit', 'quantity' => 1, 'price' => 100, 'total' => 100,
        ]);

        $ticket = ServiceTicket::create([
            'service_type' => 'customer_damage_repair', 'service_location' => 'in_shop',
            'priority' => 'normal', 'repair_status' => 'open',
            'financial_responsibility' => 'customer_pay', 'financial_status' => 'not_billable',
            'opened_at' => now(),
        ]);

        CustomerDamageStaging::create([
            'unique_id' => 'CDS-' . Str::random(8),
            'source_type' => 'checklist',
            'order_id' => $order->id,
            'order_product_id' => $op->id,
            'observation' => 'Cracked housing',
            'reported_at' => now(),
            'service_ticket_id' => $ticket->id,
        ]);

        $rows = collect([
            ['source' => null, 'order_product' => ['id' => $op->id], 'orderLink' => 'https://admin.example/order/abc'],
        ]);

        $out = DamageChargeSourceResolver::enrich($rows);

        $this->assertSame('Service Ticket', $out[0]['source_type']);
        $this->assertStringContainsString('/service-management/tickets/' . $ticket->id, $out[0]['source_link']);
    }

    // ── Service Ticket customer-damage charges in the queue ─────────────────

    /** A customer-damage Service Ticket charge (billed as service_ticket type). */
    private function serviceTicketDamageCharge(Customer $c, string $serviceType, float $amount = 200.0): BillingCharge
    {
        $ticket = ServiceTicket::create([
            'ticket_number' => 'ST-' . Str::random(6),
            'service_type' => $serviceType, 'service_location' => 'in_shop', 'priority' => 'normal',
            'repair_status' => 'open', 'financial_responsibility' => 'customer_pay',
            'financial_status' => 'ready_to_bill', 'opened_at' => now(), 'customer_id' => $c->id,
        ]);

        $charge = BillingCharge::create([
            'billing_charge_type' => 'service_ticket', 'status' => 'pending',
            'customer_id' => $c->id, 'amount' => $amount, 'tax_amount' => 0, 'tax_type' => 'free',
        ]);

        ServiceTicketSettlement::create([
            'service_ticket_id' => $ticket->id, 'customer_id' => $c->id,
            'billing_charge_id' => $charge->id, 'status' => 'created', 'package' => [],
        ]);

        return $charge;
    }

    public function test_customer_damage_service_ticket_charge_appears_even_as_service_ticket_type(): void
    {
        $c = $this->customer('Servicedmg');
        $this->serviceTicketDamageCharge($c, 'customer_damage_repair');

        // Not in the canonical damage-alert set (it is billing_charge_type=
        // 'service_ticket'), yet it belongs in Damage Charge Resolution.
        $this->assertSame(0, ChargeAlertQueue::damageAlerts()->count());

        $this->get(route(self::DAMAGE_ROUTE))
            ->assertOk()
            ->assertSee('Servicedmg Cust')
            ->assertSee('Service Ticket');
    }

    public function test_unrelated_service_ticket_charge_does_not_appear(): void
    {
        $c = $this->customer('Inspectonly');
        // Customer-pay but NOT damage (inspection) → excluded.
        $this->serviceTicketDamageCharge($c, 'inspection_diagnosis');

        $html = $this->get(route(self::DAMAGE_ROUTE))->getContent();

        $this->assertStringContainsString('No damage charge alerts found', $html);
        $this->assertStringNotContainsString('Inspectonly Cust', $html);
    }

    public function test_service_ticket_charge_links_to_the_ticket_and_targets_the_canonical_charge(): void
    {
        $c = $this->customer('Linked');
        $charge = $this->serviceTicketDamageCharge($c, 'customer_damage_repair');
        $settlement = ServiceTicketSettlement::where('billing_charge_id', $charge->id)->firstOrFail();

        $html = $this->get(route(self::DAMAGE_ROUTE))->getContent();

        // Source link back to the Service Ticket (its source of truth)…
        $this->assertStringContainsString('/service-management/tickets/' . $settlement->service_ticket_id, $html);
        // …and actions operate on the canonical BillingCharge (charge-mode).
        $this->assertStringContainsString('data-action-mode="charge"', $html);
        $this->assertStringContainsString('data-bc-id="' . $charge->unique_id . '"', $html);
    }

    // ── Unpriced ($0) checklist damage — Needs Pricing ──────────────────────

    /** An active, checklist-origin damage OrderProduct with NO established amount. */
    private function unpricedChecklistDamage(Customer $c): OrderProduct
    {
        $category = ProductCategory::create(['title' => 'Cat' . Str::random(4), 'status' => 'Published', 'sort_order' => 1]);

        $equipment = Equipment::create([
            'unique_id' => 'dmg-eq-' . Str::random(6), 'equipment_name' => 'Damaged Loader',
            'equipment_id' => 'DL-' . Str::random(4), 'brand' => 'Test',
            'product_category_id' => $category->id,
            'current_status' => 'damaged', 'not_for_rent' => 0,
        ]);

        $order = Order::create([
            'order_date' => now()->format('Y-m-d'),
            'customer_id' => $c->id, 'customer_name' => $c->full_name,
            'subtotal' => 100, 'tax_amount' => 0, 'grand_total' => 100,
        ]);

        $op = $order->products()->create([
            'product_name' => 'Damaged Loader', 'equipment_id' => $equipment->id,
            'damage_charge' => 0, 'quantity' => 1, 'price' => 100, 'total' => 100,
        ]);

        EquipmentSoftAssign::create([
            'equipment_id' => $equipment->id, 'order_id' => $order->id, 'order_product_id' => $op->id,
        ]);

        return $op;
    }

    public function test_unpriced_checklist_damage_shows_needs_pricing_and_is_not_collectible(): void
    {
        $c = $this->customer('Needspricing');
        $this->unpricedChecklistDamage($c);

        $response = $this->get(route(self::DAMAGE_ROUTE));
        $html = $response->getContent();

        $response->assertOk()->assertSee('Needs Pricing');
        $this->assertStringContainsString('data-needs-pricing', $html);
        // Source link retained (checklist → order).
        $this->assertStringContainsString('Customer Checklist', $html);
        // Not collectible while $0 — no payment action offered on the queue…
        $this->assertStringNotContainsString('data-action="payment"', $html);
        // …but the canonical adjustment action IS available to establish price.
        $this->assertStringContainsString('data-action="adjust"', $html);
    }

    public function test_pricing_via_adjustment_updates_canonical_charge_without_duplicating(): void
    {
        $c = $this->customer('Priceme');
        $op = $this->unpricedChecklistDamage($c);

        $opCountBefore = OrderProduct::count();
        $bcCountBefore = BillingCharge::count();

        // Establish the price through the EXISTING adjustment endpoint
        // (relative +150). This adds a change-log entry — it does not create a
        // new order product or a new BillingCharge.
        $this->post(route('admin.dashboard.amount.update', $op->unique_id), [
            'type' => 'damage', 'amount' => 150, 'note' => 'Establish price',
        ])->assertOk();

        $this->assertSame($opCountBefore, OrderProduct::count(), 'no duplicate order product');
        $this->assertSame($bcCountBefore, BillingCharge::count(), 'no duplicate billing charge');
        $this->assertSame(1, $op->damageChargeLogs()->count());

        // Now priced → collectible, the row's Needs Pricing badge is gone.
        $html = $this->get(route(self::DAMAGE_ROUTE))->getContent();
        $this->assertStringContainsString('$150.00', $html);
        $this->assertStringNotContainsString('data-needs-pricing', $html);
        $this->assertStringContainsString('data-action="payment"', $html);
    }

    // ── ?needs_pricing=1 filter (Overview deep-link) ────────────────────────

    public function test_needs_pricing_filter_shows_only_unpriced_active_records(): void
    {
        $this->crmDamageCharge($this->customer('Pricedone'), 'pending', 120.0);
        $this->crmDamageCharge($this->customer('Zeroone'), 'pending', 0.0);

        $html = $this->get(route(self::DAMAGE_ROUTE, ['needs_pricing' => 1]))->getContent();

        $this->assertStringContainsString('Zeroone Cust', $html);       // unpriced shown
        $this->assertStringNotContainsString('Pricedone Cust', $html);  // priced hidden
    }

    public function test_needs_pricing_filter_does_not_affect_terminal_history(): void
    {
        // A resolved, priced record must still show under the terminal view
        // even with needs_pricing set — the filter is active-queue only.
        $this->crmDamageCharge($this->customer('Resolvedpriced'), 'resolved', 120.0);

        $this->get(route(self::DAMAGE_ROUTE, ['status' => 'resolved', 'needs_pricing' => 1]))
            ->assertOk()
            ->assertSee('Resolvedpriced Cust');
    }

    public function test_needs_pricing_active_filter_is_visibly_indicated(): void
    {
        $this->crmDamageCharge($this->customer('Anyone'), 'pending', 0.0);

        $response = $this->get(route(self::DAMAGE_ROUTE, ['needs_pricing' => 1]))->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('data-active-filter="needs_pricing"', $html);
        $this->assertStringContainsString('Showing only damage items that need pricing', $html);
        $this->assertMatchesRegularExpression('/name="needs_pricing"[^>]*checked/', $html);
    }

    public function test_clearing_needs_pricing_restores_the_full_active_queue(): void
    {
        $this->crmDamageCharge($this->customer('Fullpriced'), 'pending', 120.0);
        $this->crmDamageCharge($this->customer('Fullzero'), 'pending', 0.0);

        // No filter → the complete active queue (both records), no banner.
        $html = $this->get(route(self::DAMAGE_ROUTE))->getContent();

        $this->assertStringContainsString('Fullpriced Cust', $html);
        $this->assertStringContainsString('Fullzero Cust', $html);
        // The active-filter banner text only renders when the filter is on.
        $this->assertStringNotContainsString('Showing only damage items that need pricing', $html);
    }
}
