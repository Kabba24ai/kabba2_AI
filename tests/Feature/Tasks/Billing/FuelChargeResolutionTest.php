<?php

namespace Tests\Feature\Tasks\Billing;

use App\Livewire\Dashboard\AlertsSection;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAccount;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Models\ProductManagement\ProductCategory;
use App\Services\Alerts\ChargeAlertQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Fuel Charge Resolution — RELOCATED to Task Manager → Billing Operations
 * (was admin.reports.fuel-charge-workspace). Same read-only shell, same
 * ChargeAlertQueue service, same canonical endpoints; only the route/URL/
 * view namespace changed. These tests pin the relocation (new route works,
 * old route redirects, dashboard/nav point to the new route) AND that the
 * preserved behavior still holds (dashboard-count reconciliation, the
 * state-based action sets).
 */
class FuelChargeResolutionTest extends TestCase
{
    use RefreshDatabase;

    private const NEW_ROUTE = 'admin.tasks.billing.fuel-charges.index';
    private const OLD_ROUTE = 'admin.reports.fuel-charge-workspace.index';

    private User $admin;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'first_name' => 'Work', 'last_name' => 'Space',
            'email' => 'fuel-workspace@test.local', 'status' => 'Active',
        ]);
        $this->actingAs($this->admin);

        $this->customer = Customer::create([
            'first_name' => 'Fuel', 'last_name' => 'Customer',
            'email' => 'fuel-customer@test.local', 'status' => 'Active',
        ]);
    }

    /** An OrderProduct-linked fuel alert that satisfies every canonical queue rule. */
    private function makeOpFuelAlert(float $charge = 75.0): Order
    {
        $category = ProductCategory::create(['title' => 'Excavators', 'status' => 'Published', 'sort_order' => 1]);

        $equipment = Equipment::create([
            'unique_id' => 'ws-eq-' . Str::random(6), 'equipment_name' => 'Mini Excavator',
            'equipment_id' => 'EX-' . Str::random(4), 'brand' => 'Test',
            'product_category_id' => $category->id,
            'current_status' => 'available', 'not_for_rent' => 0,
        ]);

        $order = Order::create([
            'order_date' => now()->format('Y-m-d'),
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->full_name,
            'subtotal' => 100, 'tax_amount' => 0, 'grand_total' => 100,
        ]);

        $order->products()->create([
            'product_name' => 'Mini Excavator',
            'equipment_id' => $equipment->id,
            'fuel_total_charge' => $charge,
            'quantity' => 1, 'price' => 100, 'total' => 100,
        ]);

        return $order;
    }

    /** A pure-CRM manual fuel charge (order_product_id NULL, pending). */
    private function makeCrmFuelCharge(float $amount = 40.0): CustomerAccount
    {
        $account = new CustomerAccount();
        $account->customer_id = $this->customer->id;
        $account->amount = $amount;
        $account->reason = 'Fuel Charge';
        $account->type = 'charge';
        $account->date = now();
        $account->sales_tax = 0;
        $account->sales_tax_type = 'free';
        $account->fuel_alert_status = 'pending';
        $account->save();

        return $account;
    }

    // ── Relocation ───────────────────────────────────────────────────────

    public function test_new_task_manager_route_renders(): void
    {
        $this->get(route(self::NEW_ROUTE))
            ->assertOk()
            ->assertSee('Fuel Charge Resolution');
    }

    public function test_old_reports_route_redirects_to_the_new_route(): void
    {
        $this->get(route(self::OLD_ROUTE))
            ->assertRedirect(route(self::NEW_ROUTE));
    }

    public function test_retired_fuel_alerts_alias_redirects_to_the_new_route(): void
    {
        $this->get(route('admin.reports.fuel-charge-alerts.index'))
            ->assertRedirect(route(self::NEW_ROUTE));
    }

    public function test_guest_is_blocked_from_the_workspace(): void
    {
        Auth::logout();

        $this->get(route(self::NEW_ROUTE))->assertRedirect();
    }

    public function test_dashboard_fuel_card_links_to_the_new_route(): void
    {
        $html = Livewire::test(AlertsSection::class)->html();

        $this->assertStringContainsString(route(self::NEW_ROUTE), $html);
        $this->assertStringNotContainsString(route(self::OLD_ROUTE), $html);
    }

    // ── Preserved behavior ─────────────────────────────────────────────────

    public function test_workspace_outstanding_reconciles_with_dashboard_summary(): void
    {
        $this->makeOpFuelAlert();
        $this->makeCrmFuelCharge();

        $queue = ChargeAlertQueue::fuelAlerts();
        $this->assertSame(2, $queue->count(), 'canonical queue should hold both sources');

        $summary = ChargeAlertQueue::summarize($queue, 'fuel');
        $this->assertSame(2, $summary['outstanding']);

        Livewire::test(AlertsSection::class)
            ->assertSet('fuelSummary.outstanding', 2);

        $response = $this->get(route(self::NEW_ROUTE));
        $response->assertOk();
        $this->assertStringContainsString('data-metric="outstanding">2<', $response->getContent());
    }

    public function test_op_row_carries_the_full_action_set(): void
    {
        $this->makeOpFuelAlert();

        $html = $this->get(route(self::NEW_ROUTE))->getContent();

        foreach (['history', 'notes', 'adjust', 'payment', 'resolve', 'uncollectible'] as $action) {
            $this->assertStringContainsString('data-action="' . $action . '"', $html, "missing {$action} action");
        }
    }

    public function test_crm_row_without_billing_bridge_is_link_only(): void
    {
        $this->makeCrmFuelCharge();

        $response = $this->get(route(self::NEW_ROUTE));
        $html = $response->getContent();

        $response->assertSee('Manual');
        $response->assertSee('Manage in CRM');
        $this->assertStringNotContainsString('data-action=', $html);
    }

    public function test_crm_row_with_billing_bridge_carries_charge_mode_actions(): void
    {
        $account = $this->makeCrmFuelCharge();

        $bridge = \App\Models\Orders\BillingCharge::create([
            'billing_charge_type' => 'fuel',
            'status' => 'pending',
            'customer_id' => $this->customer->id,
            'amount' => 40.0,
            'tax_amount' => 0.0,
            'tax_type' => 'free',
            'customer_account_id' => $account->id,
        ]);

        $html = $this->get(route(self::NEW_ROUTE))->getContent();

        $this->assertStringContainsString('data-action-mode="charge"', $html);
        $this->assertStringContainsString('data-bc-id="' . $bridge->unique_id . '"', $html);

        foreach (['notes', 'payment', 'resolve', 'uncollectible'] as $action) {
            $this->assertStringContainsString('data-action="' . $action . '"', $html, "missing {$action}");
        }
        $this->assertStringNotContainsString('data-action="adjust"', $html);
        $this->assertStringContainsString('Manage in CRM', $html);
    }

    public function test_filters_narrow_the_queue_without_changing_the_summary(): void
    {
        $this->makeOpFuelAlert();
        $this->makeCrmFuelCharge();

        $html = $this->get(route(self::NEW_ROUTE, ['search_name' => 'NoSuchCustomer']))->getContent();

        $this->assertStringContainsString('No fuel charge alerts found', $html);
        $this->assertStringContainsString('data-metric="outstanding">2<', $html);
    }

    public function test_fragment_request_returns_queue_and_summary_html(): void
    {
        $this->makeOpFuelAlert();

        $this->get(route(self::NEW_ROUTE, ['fragment' => 1]))
            ->assertOk()
            ->assertJsonStructure(['success', 'queue_html', 'summary_html']);
    }
}
