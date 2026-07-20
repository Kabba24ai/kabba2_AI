<?php

namespace Tests\Feature\Reports;

use App\Livewire\Dashboard\AlertsSection;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAccount;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Models\ProductManagement\ProductCategory;
use App\Services\Alerts\ChargeAlertQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Dashboard V2 Phase 1A — Fuel Charge Workspace.
 *
 * The load-bearing guarantee: the workspace queue and the dashboard Fuel
 * card consume the IDENTICAL ChargeAlertQueue service, so their Outstanding
 * counts reconcile by construction. These tests pin that, plus the Phase 1
 * CRM rule (visible, clearly labeled, strictly read-only) and the presence
 * of the full OrderProduct action set.
 */
class FuelChargeWorkspaceTest extends TestCase
{
    use RefreshDatabase;

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
            'quantity' => 1, 'price' => 100,
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

    public function test_workspace_page_renders(): void
    {
        $this->get(route('admin.reports.fuel-charge-workspace.index'))
            ->assertOk()
            ->assertSee('Fuel Charge Workspace');
    }

    public function test_workspace_outstanding_reconciles_with_dashboard_summary(): void
    {
        $this->makeOpFuelAlert();
        $this->makeCrmFuelCharge();

        $queue = ChargeAlertQueue::fuelAlerts();
        $this->assertSame(2, $queue->count(), 'canonical queue should hold both sources');

        // The dashboard card and the workspace summary both derive from the
        // same service call — assert both read the same number.
        $summary = ChargeAlertQueue::summarize($queue, 'fuel');
        $this->assertSame(2, $summary['outstanding']);

        Livewire::test(AlertsSection::class)
            ->assertSet('fuelSummary.outstanding', 2);

        $response = $this->get(route('admin.reports.fuel-charge-workspace.index'));
        $response->assertOk();
        $this->assertStringContainsString('data-metric="outstanding">2<', $response->getContent());
    }

    public function test_op_row_carries_the_full_action_set(): void
    {
        $this->makeOpFuelAlert();

        $html = $this->get(route('admin.reports.fuel-charge-workspace.index'))->getContent();

        foreach (['history', 'notes', 'adjust', 'payment', 'resolve', 'uncollectible'] as $action) {
            $this->assertStringContainsString('data-action="' . $action . '"', $html, "missing {$action} action");
        }
    }

    public function test_crm_row_is_visible_but_strictly_read_only(): void
    {
        $this->makeCrmFuelCharge();

        $response = $this->get(route('admin.reports.fuel-charge-workspace.index'));
        $html = $response->getContent();

        $response->assertSee('Manual');
        $response->assertSee('Manage in CRM');
        // The CRM row must expose NO action buttons — Phase 2 owns its sync.
        $this->assertStringNotContainsString('data-action=', $html);
    }

    public function test_filters_narrow_the_queue_without_changing_the_summary(): void
    {
        $this->makeOpFuelAlert();
        $this->makeCrmFuelCharge();

        $html = $this->get(route('admin.reports.fuel-charge-workspace.index', [
            'search_name' => 'NoSuchCustomer',
        ]))->getContent();

        $this->assertStringContainsString('No fuel charge alerts found', $html);
        // Summary reflects the UNFILTERED canonical queue — dashboard parity.
        $this->assertStringContainsString('data-metric="outstanding">2<', $html);
    }

    public function test_fragment_request_returns_queue_and_summary_html(): void
    {
        $this->makeOpFuelAlert();

        $this->get(route('admin.reports.fuel-charge-workspace.index', ['fragment' => 1]))
            ->assertOk()
            ->assertJsonStructure(['success', 'queue_html', 'summary_html']);
    }

    public function test_dashboard_fuel_card_links_to_the_workspace(): void
    {
        $html = Livewire::test(AlertsSection::class)->html();

        $this->assertStringContainsString(route('admin.reports.fuel-charge-workspace.index'), $html);
    }
}
