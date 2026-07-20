<?php

namespace Tests\Feature\Dashboard;

use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAccount;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\BillingCharge;
use App\Models\Orders\Order;
use App\Services\Alerts\ChargeAlertQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Shared New Fuel Charge modal (Dashboard V2) — the ONE normalized creation
 * contract every entry point (Dashboard card, Fuel Workspace, CRM page)
 * posts to. Pins: order-derived customer integrity, the consolidated
 * ChargeService::createManualCharge() write set (CustomerAccount + note +
 * BillingEngine bridge with order linkage), queue visibility of the new
 * charge, and the read-only typeahead lookups.
 */
class NewFuelChargeModalTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'first_name' => 'Charge', 'last_name' => 'Creator',
            'email' => 'nfc-admin@test.local', 'status' => 'Active',
        ]);
        $this->actingAs($this->admin);

        $this->customer = Customer::create([
            'first_name' => 'Jordan', 'last_name' => 'Fuelson',
            'email' => 'nfc-customer@test.local', 'status' => 'Active',
        ]);
    }

    private function makeOrder(?Customer $customer = null): Order
    {
        $customer ??= $this->customer;

        return Order::create([
            'order_date' => now()->format('Y-m-d'),
            'customer_id' => $customer->id,
            'customer_name' => $customer->full_name,
            'subtotal' => 100, 'tax_amount' => 0, 'grand_total' => 100,
        ]);
    }

    private function store(array $payload)
    {
        return $this->postJson(route('admin.dashboard.fuel-charge.store'), array_merge([
            'amount' => 60,
            'sales_tax_type' => 'free',
            'responsible_person' => $this->admin->id,
            'source_context' => 'fuel_workspace',
        ], $payload));
    }

    public function test_customer_only_charge_creates_the_full_canonical_record_set(): void
    {
        $response = $this->store(['customer_id' => $this->customer->id, 'notes' => 'Half tank short'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('charge.customer_id', $this->customer->id)
            // Billing Engine Commonization: the creation response IS the
            // post-charge payment handoff contract — every launcher hands
            // this same shape to the shared payment component.
            ->assertJsonPath('charge.type', 'fuel')
            ->assertJsonStructure(['charge' => [
                'type', 'customer_account_unique_id', 'billing_charge_unique_id',
                'customer_id', 'order_id', 'amount', 'amount_total', 'customer_cards',
            ]]);

        $this->assertNotNull($response->json('charge.billing_charge_unique_id'),
            'the bridge BillingCharge reference must accompany the handoff — it drives the exact-total guard and markPaid sync');

        $record = CustomerAccount::where('customer_id', $this->customer->id)
            ->where('reason', 'Fuel Charge')->firstOrFail();

        $this->assertSame('pending', $record->fuel_alert_status);
        $this->assertNull($record->order_id);
        $this->assertNull($record->order_product_id, 'Phase 1 never links manual charges to an OrderProduct');
        $this->assertEquals(60.0, (float) $record->amount);

        // BillingEngine bridge row, tied back to the CustomerAccount record.
        $bridge = BillingCharge::where('customer_account_id', $record->id)->firstOrFail();
        $this->assertSame('fuel', $bridge->billing_charge_type->value);
        $this->assertNull($bridge->order_id);

        // Customer note written by the canonical path.
        $this->assertSame(1, $this->customer->notes()->where('customer_account_id', $record->id)->count());
    }

    public function test_order_linked_charge_derives_the_customer_from_the_order(): void
    {
        $order = $this->makeOrder();

        // No customer_id submitted at all — the order supplies it.
        $this->store(['order_id' => $order->id])
            ->assertOk()
            ->assertJsonPath('charge.customer_id', $this->customer->id)
            ->assertJsonPath('charge.order_id', $order->id);

        $record = CustomerAccount::where('reason', 'Fuel Charge')->firstOrFail();
        $this->assertSame($order->id, (int) $record->order_id);

        $bridge = BillingCharge::where('customer_account_id', $record->id)->firstOrFail();
        $this->assertSame($order->id, (int) $bridge->order_id, 'order linkage must reach the Billing Engine row');
    }

    public function test_mismatched_customer_and_order_is_rejected(): void
    {
        $other = Customer::create([
            'first_name' => 'Other', 'last_name' => 'Person',
            'email' => 'nfc-other@test.local', 'status' => 'Active',
        ]);
        $order = $this->makeOrder(); // belongs to $this->customer

        $this->store(['order_id' => $order->id, 'customer_id' => $other->id])
            ->assertStatus(422);

        $this->assertSame(0, CustomerAccount::where('reason', 'Fuel Charge')->count());
    }

    public function test_neither_order_nor_customer_is_rejected(): void
    {
        $this->store([])->assertStatus(422);
    }

    public function test_new_manual_charge_appears_in_the_canonical_fuel_queue(): void
    {
        $order = $this->makeOrder();
        $this->store(['order_id' => $order->id])->assertOk();

        $queue = ChargeAlertQueue::fuelAlerts();

        $this->assertSame(1, $queue->count());
        $row = $queue->first();
        $this->assertSame('crm', $row['source'], 'manual charges surface through the CRM branch');
        $this->assertSame($order->order_number, $row['order_number'], 'order linkage carries into the queue row');
    }

    public function test_order_lookup_returns_distinguishing_context(): void
    {
        $order = $this->makeOrder();

        $this->getJson(route('admin.dashboard.charge-modal.orders', ['q' => $order->order_number]))
            ->assertOk()
            ->assertJsonPath('results.0.id', $order->id)
            ->assertJsonPath('results.0.customer_name', $this->customer->full_name)
            ->assertJsonStructure(['results' => [['id', 'order_number', 'customer_id', 'customer_name', 'order_date', 'status']]]);
    }

    public function test_lookups_require_a_minimum_query_length(): void
    {
        $this->getJson(route('admin.dashboard.charge-modal.orders', ['q' => 'a']))
            ->assertOk()->assertJsonCount(0, 'results');
        $this->getJson(route('admin.dashboard.charge-modal.customers', ['q' => 'a']))
            ->assertOk()->assertJsonCount(0, 'results');
    }

    public function test_customer_lookup_finds_by_name_and_company(): void
    {
        Customer::create([
            'first_name' => 'Acme', 'last_name' => 'Rentals',
            'company_name' => 'Acme Grading LLC',
            'email' => 'nfc-acme@test.local', 'status' => 'Active',
        ]);

        $this->getJson(route('admin.dashboard.charge-modal.customers', ['q' => 'Acme Grading']))
            ->assertOk()
            ->assertJsonPath('results.0.company', 'Acme Grading LLC');
    }
}
