<?php

namespace Tests\Feature\BillingEngine;

use App\Enums\Billing\BillingChargeStatus;
use App\Enums\Billing\BillingChargeType;
use App\Enums\Billing\BillingSourceEvent;
use App\Enums\Billing\BillingSourceModule;
use App\Http\DataObjects\BillingChargeRequest;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAccount;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\BillingCharge;
use App\Models\Orders\Order;
use App\Services\BillingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FuelAlertChargeBridgeTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private User $user;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'Test',
            'last_name'  => 'Customer',
            'email'      => 'alert-bridge-test@example.com',
            'status'     => 'Active',
        ]);

        $this->user = User::create([
            'first_name' => 'Admin',
            'last_name'  => 'User',
            'email'      => 'admin-alert-bridge@example.com',
            'password'   => bcrypt('password'),
        ]);

        $this->order = Order::create([
            'order_number'    => 'ORD-ALERT-TEST-001',
            'order_date'      => now()->toDateString(),
            'customer_id'     => $this->customer->id,
            'customer_name'   => $this->customer->first_name . ' ' . $this->customer->last_name,
            'created_by_id'   => $this->user->id,
            'created_by_type' => User::class,
            'updated_by_id'   => $this->user->id,
            'updated_by_type' => User::class,
        ]);
    }

    private function postAlertCharge(array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->withoutMiddleware()
            ->actingAs($this->user)
            ->postJson(
                route('admin.order-management.orders.alert-charge', ['unique_id' => $this->order->unique_id]),
                array_merge([
                    'type'               => 'fuel',
                    'amount'             => '95.00',
                    'notes'              => 'Test fuel alert charge',
                    'responsible_person' => $this->user->id,
                    'sales_tax_type'     => 'free',
                ], $overrides)
            );
    }

    // ── Legacy behavior unchanged ─────────────────────────────────────────

    public function test_fuel_alert_charge_creates_customer_account_record(): void
    {
        $this->postAlertCharge()->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('customer_accounts', [
            'customer_id'       => $this->customer->id,
            'order_id'          => $this->order->id,
            'amount'            => 95.00,
            'reason'            => 'Fuel Charge',
            'type'              => 'charge',
            'fuel_alert_status' => 'pending',
            'sales_tax_type'    => 'free',
            'sales_tax'         => 0,
        ]);
    }

    public function test_fuel_alert_charge_response_is_success(): void
    {
        $response = $this->postAlertCharge();

        $response->assertOk()->assertJson([
            'success' => true,
            'message' => 'Fuel Charge created successfully.',
        ]);
    }

    public function test_damage_alert_charge_creates_customer_account_with_correct_fields(): void
    {
        $response = $this->postAlertCharge(['type' => 'damage', 'amount' => '200.00']);

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('customer_accounts', [
            'customer_id'          => $this->customer->id,
            'order_id'             => $this->order->id,
            'amount'               => 200.00,
            'reason'               => 'Damages',
            'type'                 => 'charge',
            'damage_alert_status'  => 'pending',
            'fuel_alert_status'    => null,
        ]);
    }

    public function test_damage_alert_charge_also_creates_billing_charge(): void
    {
        $this->postAlertCharge(['type' => 'damage']);

        // Phase 4C bridges the damage arm — BillingCharge is created with type=damage
        $this->assertEquals(1, BillingCharge::count());
        $this->assertEquals(BillingChargeType::Damage, BillingCharge::first()->billing_charge_type);
    }

    // ── Bridge creates BillingCharge (fuel only) ──────────────────────────

    public function test_fuel_alert_charge_also_creates_billing_charge_record(): void
    {
        $this->postAlertCharge();

        $this->assertEquals(1, BillingCharge::count());
    }

    public function test_billing_charge_has_correct_type_and_status(): void
    {
        $this->postAlertCharge();

        $charge = BillingCharge::first();

        $this->assertEquals(BillingChargeType::Fuel, $charge->billing_charge_type);
        $this->assertEquals(BillingChargeStatus::Pending, $charge->status);
    }

    public function test_billing_charge_has_correct_amount_and_customer(): void
    {
        $this->postAlertCharge(['amount' => '150.75']);

        $charge = BillingCharge::first();

        $this->assertEquals(150.75, $charge->amount);
        $this->assertEquals($this->customer->id, $charge->customer_id);
    }

    public function test_billing_charge_has_populated_parent_order_id(): void
    {
        $this->postAlertCharge();

        // Phase 3B key assertion: order_id IS available (unlike Phase 3A dashboard modal)
        $this->assertEquals($this->order->id, BillingCharge::first()->parent_order_id);
    }

    public function test_billing_charge_has_null_order_product_id(): void
    {
        $this->postAlertCharge();

        // AlertChargeController has no order_product_id context
        $this->assertNull(BillingCharge::first()->order_product_id);
    }

    public function test_billing_charge_stores_source_module_and_event(): void
    {
        $this->postAlertCharge();

        $charge = BillingCharge::first();

        $this->assertEquals(BillingSourceModule::AdminFuelCharge->value, $charge->source_module);
        $this->assertEquals(BillingSourceEvent::AdminFuelChargeCreated->value, $charge->source_event);
    }

    public function test_billing_charge_stores_legacy_customer_account_id(): void
    {
        $this->postAlertCharge();

        $ca     = CustomerAccount::first();
        $charge = BillingCharge::first();

        $this->assertEquals($ca->id, $charge->source_reference_id);
        $this->assertEquals('CustomerAccount', $charge->source_reference_type);
    }

    public function test_billing_charge_stores_order_context_in_metadata(): void
    {
        $this->postAlertCharge();

        $charge = BillingCharge::first();

        $this->assertIsArray($charge->metadata);
        $this->assertEquals('AlertChargeController', $charge->metadata['legacy_controller']);
        $this->assertEquals($this->order->id, $charge->metadata['order_id']);
        $this->assertEquals($this->order->unique_id, $charge->metadata['order_unique_id']);
        $this->assertArrayHasKey('legacy_customer_account_id', $charge->metadata);
    }

    public function test_billing_charge_has_blc_prefixed_unique_id(): void
    {
        $this->postAlertCharge();

        $this->assertStringStartsWith('BLC', BillingCharge::first()->unique_id);
    }

    public function test_billing_charge_idempotency_key_uses_customer_account_id(): void
    {
        $this->postAlertCharge();

        $ca     = CustomerAccount::first();
        $charge = BillingCharge::first();

        $this->assertEquals("admin_fuel_alert_charge:{$ca->id}", $charge->idempotency_key);
    }

    // ── Idempotency ────────────────────────────────────────────────────────

    public function test_duplicate_idempotency_key_does_not_create_second_billing_charge(): void
    {
        $this->postAlertCharge();

        $ca = CustomerAccount::first();

        // Simulate a retry with the same idempotency key
        BillingEngine::charge(new BillingChargeRequest(
            type:           BillingChargeType::Fuel->value,
            orderId:        $this->order->id,
            customerId:     $this->customer->id,
            amount:         95.00,
            idempotencyKey: "admin_fuel_alert_charge:{$ca->id}",
        ));

        $this->assertEquals(1, BillingCharge::count());
    }

    // ── Legacy still works even if bridge fails ────────────────────────────

    public function test_legacy_charge_succeeds_even_when_billing_engine_bridge_fails(): void
    {
        Schema::drop('billing_charges');

        $response = $this->postAlertCharge();

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('customer_accounts', [
            'customer_id' => $this->customer->id,
            'order_id'    => $this->order->id,
            'reason'      => 'Fuel Charge',
            'type'        => 'charge',
        ]);
    }

    public function test_billing_engine_failure_is_logged_to_billing_engine_channel(): void
    {
        Log::shouldReceive('channel')->with('billing_engine')->andReturnSelf();
        Log::shouldReceive('error')->once();
        Log::shouldReceive('info')->andReturn(null);

        Schema::drop('billing_charges');

        $this->postAlertCharge();
    }

    // ── Tax type mapping ───────────────────────────────────────────────────

    public function test_tax_type_is_stored_on_billing_charge(): void
    {
        $this->postAlertCharge(['sales_tax_type' => 'add']);

        $this->assertEquals('add', BillingCharge::first()->tax_type);
    }

    public function test_tax_type_defaults_to_free_when_not_provided(): void
    {
        $this->postAlertCharge(['sales_tax_type' => null]);

        $this->assertEquals('free', BillingCharge::first()->tax_type);
    }

    // ── Phase 5D: customer_account_id ─────────────────────────────────────

    public function test_billing_charge_stores_customer_account_id(): void
    {
        $this->postAlertCharge(['type' => 'fuel']);

        $ca     = CustomerAccount::first();
        $charge = BillingCharge::first();

        $this->assertEquals($ca->id, $charge->customer_account_id);
    }

    // ── No side effects ────────────────────────────────────────────────────

    public function test_billing_charge_does_not_interfere_with_legacy_alert_query(): void
    {
        $this->postAlertCharge();

        $alertCount = CustomerAccount::where('reason', 'Fuel Charge')
            ->where('fuel_alert_status', 'pending')
            ->count();

        $this->assertEquals(1, $alertCount);
        $this->assertEquals(1, BillingCharge::count());
    }
}
