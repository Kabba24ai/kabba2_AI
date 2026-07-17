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
use Tests\TestCase;

class DamageAlertChargeBridgeTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private User $user;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'Damage',
            'last_name'  => 'Alert',
            'email'      => 'damage-alert-bridge@example.com',
            'status'     => 'Active',
        ]);

        $this->user = User::create([
            'first_name' => 'Admin',
            'last_name'  => 'User',
            'email'      => 'admin-damage-alert-bridge@example.com',
            'password'   => bcrypt('password'),
        ]);

        $this->order = Order::create([
            'order_number'    => 'ORD-DMG-ALERT-001',
            'order_date'      => now()->toDateString(),
            'customer_id'     => $this->customer->id,
            'customer_name'   => $this->customer->first_name . ' ' . $this->customer->last_name,
            'created_by_id'   => $this->user->id,
            'created_by_type' => User::class,
            'updated_by_id'   => $this->user->id,
            'updated_by_type' => User::class,
        ]);
    }

    private function postDamageAlertCharge(array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->withoutMiddleware()
            ->actingAs($this->user)
            ->postJson(
                route('admin.order-management.orders.alert-charge', ['unique_id' => $this->order->unique_id]),
                array_merge([
                    'type'               => 'damage',
                    'amount'             => '350.00',
                    'notes'              => 'Test damage alert charge',
                    'responsible_person' => $this->user->id,
                    'sales_tax_type'     => 'free',
                ], $overrides)
            );
    }

    /**
     * TD-16 (Phase 3 tech debt): force BillingEngine::charge()'s underlying
     * BillingCharge::create() insert to throw, without touching schema.
     * Schema::drop() mid-transaction causes MySQL to implicitly commit,
     * corrupting Laravel's transaction/savepoint bookkeeping — see
     * docs/checklist-system-audit/P3_TD16_TRANSACTION_SAFE_FAILURE_TESTS.md.
     * A one-shot Eloquent 'creating' listener produces the same forced
     * failure deterministically, with no effect on any other test (the flag
     * disarms itself after firing once).
     */
    private function forceBillingChargeCreationFailure(): void
    {
        $shouldThrow = true;
        BillingCharge::creating(function () use (&$shouldThrow) {
            if ($shouldThrow) {
                $shouldThrow = false;
                throw new \RuntimeException('Simulated BillingEngine charge failure (test-only, TD-16)');
            }
        });
    }

    // ── Legacy behavior unchanged ─────────────────────────────────────────

    public function test_damage_alert_charge_creates_customer_account_record(): void
    {
        $this->postDamageAlertCharge()->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('customer_accounts', [
            'customer_id'         => $this->customer->id,
            'order_id'            => $this->order->id,
            'amount'              => 350.00,
            'reason'              => 'Damages',
            'type'                => 'charge',
            'damage_alert_status' => 'pending',
            'fuel_alert_status'   => null,
            'sales_tax_type'      => 'free',
            'sales_tax'           => 0,
        ]);
    }

    public function test_damage_alert_charge_response_is_success(): void
    {
        $response = $this->postDamageAlertCharge();

        $response->assertOk()->assertJson([
            'success' => true,
            'message' => 'Damage Alert created successfully.',
        ]);
    }

    // ── Bridge creates BillingCharge ──────────────────────────────────────

    public function test_damage_alert_charge_also_creates_billing_charge(): void
    {
        $this->postDamageAlertCharge();

        $this->assertEquals(1, BillingCharge::count());
    }

    public function test_billing_charge_has_correct_type_and_status(): void
    {
        $this->postDamageAlertCharge();

        $charge = BillingCharge::first();

        $this->assertEquals(BillingChargeType::Damage, $charge->billing_charge_type);
        $this->assertEquals(BillingChargeStatus::Pending, $charge->status);
    }

    public function test_billing_charge_has_correct_amount_and_customer(): void
    {
        $this->postDamageAlertCharge(['amount' => '500.00']);

        $charge = BillingCharge::first();

        $this->assertEquals(500.00, $charge->amount);
        $this->assertEquals($this->customer->id, $charge->customer_id);
    }

    public function test_billing_charge_has_populated_parent_order_id(): void
    {
        $this->postDamageAlertCharge();

        // Phase 4C key assertion: order context IS available (unlike Phase 4B dashboard modal)
        $this->assertEquals($this->order->id, BillingCharge::first()->parent_order_id);
    }

    public function test_billing_charge_has_null_order_product_id(): void
    {
        $this->postDamageAlertCharge();

        // AlertChargeController has no order_product_id context
        $this->assertNull(BillingCharge::first()->order_product_id);
    }

    public function test_billing_charge_stores_source_module_and_event(): void
    {
        $this->postDamageAlertCharge();

        $charge = BillingCharge::first();

        $this->assertEquals(BillingSourceModule::AdminDamageCharge->value, $charge->source_module);
        $this->assertEquals(BillingSourceEvent::AdminDamageChargeCreated->value, $charge->source_event);
    }

    public function test_billing_charge_stores_customer_account_as_source_reference(): void
    {
        $this->postDamageAlertCharge();

        $ca     = CustomerAccount::first();
        $charge = BillingCharge::first();

        $this->assertEquals('CustomerAccount', $charge->source_reference_type);
        $this->assertEquals($ca->id, $charge->source_reference_id);
    }

    public function test_billing_charge_stores_order_context_in_metadata(): void
    {
        $this->postDamageAlertCharge();

        $ca     = CustomerAccount::first();
        $charge = BillingCharge::first();

        $this->assertIsArray($charge->metadata);
        $this->assertEquals('AlertChargeController', $charge->metadata['legacy_controller']);
        $this->assertEquals($ca->id, $charge->metadata['legacy_customer_account_id']);
        $this->assertEquals($this->order->id, $charge->metadata['order_id']);
        $this->assertEquals($this->order->unique_id, $charge->metadata['order_unique_id']);
        $this->assertEquals($this->customer->id, $charge->metadata['customer_id']);
        $this->assertTrue($charge->metadata['alert_context']);
    }

    public function test_billing_charge_has_blc_prefixed_unique_id(): void
    {
        $this->postDamageAlertCharge();

        $this->assertStringStartsWith('BLC', BillingCharge::first()->unique_id);
    }

    public function test_billing_charge_idempotency_key_uses_damage_alert_prefix(): void
    {
        $this->postDamageAlertCharge();

        $ca     = CustomerAccount::first();
        $charge = BillingCharge::first();

        $this->assertEquals("admin_damage_alert_charge:{$ca->id}", $charge->idempotency_key);
    }

    // ── Idempotency ────────────────────────────────────────────────────────

    public function test_duplicate_key_does_not_create_second_billing_charge(): void
    {
        $this->postDamageAlertCharge();

        $ca = CustomerAccount::first();

        BillingEngine::charge(new BillingChargeRequest(
            type:           BillingChargeType::Damage->value,
            orderId:        $this->order->id,
            customerId:     $this->customer->id,
            amount:         350.00,
            idempotencyKey: "admin_damage_alert_charge:{$ca->id}",
        ));

        $this->assertEquals(1, BillingCharge::count());
    }

    // ── Legacy still works even if bridge fails ────────────────────────────

    public function test_legacy_charge_succeeds_even_when_billing_engine_bridge_fails(): void
    {
        $this->forceBillingChargeCreationFailure();

        $response = $this->postDamageAlertCharge();

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('customer_accounts', [
            'customer_id'         => $this->customer->id,
            'order_id'            => $this->order->id,
            'reason'              => 'Damages',
            'type'                => 'charge',
            'damage_alert_status' => 'pending',
        ]);
    }

    public function test_billing_engine_failure_is_logged_to_billing_engine_channel(): void
    {
        Log::shouldReceive('channel')->with('billing_engine')->andReturnSelf();
        Log::shouldReceive('error')->once();
        Log::shouldReceive('info')->andReturn(null);

        $this->forceBillingChargeCreationFailure();

        $this->postDamageAlertCharge();
    }

    // ── Tax type mapping ───────────────────────────────────────────────────

    public function test_tax_type_is_stored_on_billing_charge(): void
    {
        $this->postDamageAlertCharge(['sales_tax_type' => 'add']);

        $this->assertEquals('add', BillingCharge::first()->tax_type);
    }

    public function test_tax_type_defaults_to_free_when_not_provided(): void
    {
        $this->postDamageAlertCharge(['sales_tax_type' => null]);

        $this->assertEquals('free', BillingCharge::first()->tax_type);
    }

    // ── Fuel arm regression ───────────────────────────────────────────────

    public function test_fuel_arm_still_creates_fuel_billing_charge(): void
    {
        // Verify Phase 3B fuel bridge is not broken by Phase 4C damage bridge addition
        $this->withoutMiddleware()
            ->actingAs($this->user)
            ->postJson(
                route('admin.order-management.orders.alert-charge', ['unique_id' => $this->order->unique_id]),
                [
                    'type'               => 'fuel',
                    'amount'             => '85.00',
                    'notes'              => 'Fuel regression check',
                    'responsible_person' => $this->user->id,
                    'sales_tax_type'     => 'free',
                ]
            )
            ->assertOk();

        $this->assertEquals(1, BillingCharge::count());
        $this->assertEquals(BillingChargeType::Fuel, BillingCharge::first()->billing_charge_type);
    }

    public function test_fuel_bridge_uses_fuel_source_module_and_event(): void
    {
        $this->withoutMiddleware()
            ->actingAs($this->user)
            ->postJson(
                route('admin.order-management.orders.alert-charge', ['unique_id' => $this->order->unique_id]),
                [
                    'type'               => 'fuel',
                    'amount'             => '85.00',
                    'responsible_person' => $this->user->id,
                ]
            );

        $charge = BillingCharge::first();

        $this->assertEquals(BillingSourceModule::AdminFuelCharge->value, $charge->source_module);
        $this->assertEquals(BillingSourceEvent::AdminFuelChargeCreated->value, $charge->source_event);
    }

    // ── Phase 5D: customer_account_id ─────────────────────────────────────

    public function test_billing_charge_stores_customer_account_id(): void
    {
        $this->postDamageAlertCharge();

        $ca     = CustomerAccount::first();
        $charge = BillingCharge::first();

        $this->assertEquals($ca->id, $charge->customer_account_id);
    }

    // ── No side effects ────────────────────────────────────────────────────

    public function test_billing_charge_does_not_interfere_with_damage_alert_query(): void
    {
        $this->postDamageAlertCharge();

        $alertCount = CustomerAccount::where('reason', 'Damages')
            ->where('damage_alert_status', 'pending')
            ->count();

        $this->assertEquals(1, $alertCount);
        $this->assertEquals(1, BillingCharge::count());
    }
}
