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
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\BillingCharge;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Models\ProductManagement\Product;
use App\Models\Stores\Store;
use App\Services\BillingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileReturnFuelBridgeTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private User $user;
    private Store $store;
    private Equipment $equipment;
    private Order $order;
    private OrderProduct $orderProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'Mobile',
            'last_name'  => 'Test',
            'email'      => 'mobile-fuel-bridge@example.com',
            'status'     => 'Active',
        ]);

        $this->user = User::create([
            'first_name' => 'Driver',
            'last_name'  => 'User',
            'email'      => 'driver-fuel-bridge@example.com',
            'password'   => bcrypt('password'),
        ]);

        $this->store = Store::create([
            'store_name' => 'Test Store',
        ]);

        $product = Product::create([
            'product_name' => 'Test Skid Steer',
            'slug'         => 'test-skid-steer-fuel-bridge',
        ]);

        $this->equipment = Equipment::create([
            'equipment_name' => 'Test Skid Steer',
            'equipment_id'   => 'TEST-FUEL-001',
            'brand'          => 'TestBrand',
            'current_status' => 'rented',
        ]);

        $this->order = Order::create([
            'order_number'    => 'ORD-MOBILE-FUEL-001',
            'order_date'      => now()->toDateString(),
            'customer_id'     => $this->customer->id,
            'customer_name'   => 'Mobile Test',
            'created_by_id'   => $this->user->id,
            'created_by_type' => User::class,
            'updated_by_id'   => $this->user->id,
            'updated_by_type' => User::class,
        ]);

        $this->orderProduct = OrderProduct::create([
            'order_id'       => $this->order->id,
            'product_id'     => $product->id,
            'product_name'   => 'Test Skid Steer',
            'price'          => 200.00,
            'quantity'       => 1,
            'total'          => 200.00,
            'equipment_id'   => $this->equipment->id,
            'fuel_initial_reading' => '3/4',
        ]);
    }

    private function postSaveReturn(array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->withoutMiddleware()
            ->actingAs($this->user)
            ->postJson('http://api.kabba.local/api/admin/v1/orders/customer-checklists/save-return', array_merge([
                'order_product_unique_id' => $this->orderProduct->unique_id,
                'store_id'                => (string) $this->store->id,
                'user_id'                 => (string) $this->user->id,
                'fuel_final_reading'      => '1/4',
                'fuel_total_charge'       => '85.00',
            ], $overrides));
    }

    // ── Legacy behavior unchanged ─────────────────────────────────────────

    public function test_mobile_return_creates_customer_account_record(): void
    {
        $this->postSaveReturn()->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('customer_accounts', [
            'customer_id'       => $this->customer->id,
            'order_id'          => $this->order->id,
            'order_product_id'  => $this->orderProduct->id,
            'amount'            => 85.00,
            'reason'            => 'Fuel Charge',
            'type'              => 'charge',
            'fuel_alert_status' => 'pending',
            'sales_tax_type'    => 'free',
            'sales_tax'         => 0,
        ]);
    }

    public function test_mobile_return_response_is_success_json(): void
    {
        $this->postSaveReturn()->assertOk()->assertJson(['success' => true]);
    }

    public function test_no_fuel_charge_skips_both_legacy_and_bridge(): void
    {
        $this->postSaveReturn(['fuel_total_charge' => null]);

        $this->assertEquals(0, CustomerAccount::where('reason', 'Fuel Charge')->count());
        $this->assertEquals(0, BillingCharge::count());
    }

    public function test_charge_service_duplicate_guard_prevents_double_legacy_write(): void
    {
        // First submission creates the CA charge
        $this->postSaveReturn();
        $this->assertEquals(1, CustomerAccount::where('reason', 'Fuel Charge')->count());

        // Simulate a retry: re-submit (returnSignatureMedia guard fires a 409 in real flow,
        // but with withoutMiddleware and no actual media upload, we re-exercise the ChargeService
        // duplicate guard directly)
        $this->orderProduct->refresh();
        \App\Services\ChargeService::createFromOrderProduct(
            $this->orderProduct,
            'fuel',
            $this->user->id
        );

        // ChargeService duplicate guard prevents second CA record
        $this->assertEquals(1, CustomerAccount::where('reason', 'Fuel Charge')->count());
    }

    // ── Bridge creates BillingCharge ──────────────────────────────────────

    public function test_mobile_return_fuel_also_creates_billing_charge(): void
    {
        $this->postSaveReturn();

        $this->assertEquals(1, BillingCharge::count());
    }

    public function test_billing_charge_has_correct_type_and_status(): void
    {
        $this->postSaveReturn();

        $charge = BillingCharge::first();

        $this->assertEquals(BillingChargeType::Fuel, $charge->billing_charge_type);
        $this->assertEquals(BillingChargeStatus::Pending, $charge->status);
    }

    public function test_billing_charge_has_correct_amount_and_customer(): void
    {
        $this->postSaveReturn(['fuel_total_charge' => '120.50']);

        $charge = BillingCharge::first();

        $this->assertEquals(120.50, $charge->amount);
        $this->assertEquals($this->customer->id, $charge->customer_id);
    }

    public function test_billing_charge_has_populated_parent_order_id(): void
    {
        $this->postSaveReturn();

        // Mobile path: order_id is available on the OrderProduct
        $this->assertEquals($this->order->id, BillingCharge::first()->parent_order_id);
    }

    public function test_billing_charge_has_populated_order_product_id(): void
    {
        $this->postSaveReturn();

        // Mobile path: order_product_id is always populated — first path with non-null value
        $this->assertEquals($this->orderProduct->id, BillingCharge::first()->order_product_id);
    }

    public function test_billing_charge_stores_mobile_source_module_and_event(): void
    {
        $this->postSaveReturn();

        $charge = BillingCharge::first();

        $this->assertEquals(BillingSourceModule::MobileChecklist->value, $charge->source_module);
        $this->assertEquals(BillingSourceEvent::ReturnChecklistFuelCharge->value, $charge->source_event);
    }

    public function test_billing_charge_stores_order_product_as_source_reference(): void
    {
        $this->postSaveReturn();

        $charge = BillingCharge::first();

        $this->assertEquals('OrderProduct', $charge->source_reference_type);
        $this->assertEquals($this->orderProduct->id, $charge->source_reference_id);
    }

    public function test_billing_charge_stores_mobile_metadata(): void
    {
        $this->postSaveReturn([
            'fuel_final_reading' => '1/4',
            'fuel_total_charge'  => '85.00',
        ]);

        $charge   = BillingCharge::first();
        $legacyCa = CustomerAccount::first();

        $this->assertIsArray($charge->metadata);
        $this->assertEquals('SaveReturnController', $charge->metadata['legacy_controller']);
        $this->assertEquals('ChargeService::createFromOrderProduct', $charge->metadata['legacy_service']);
        $this->assertEquals($legacyCa->id, $charge->metadata['legacy_customer_account_id']);
        $this->assertEquals($this->order->id, $charge->metadata['order_id']);
        $this->assertEquals($this->orderProduct->id, $charge->metadata['order_product_id']);
        $this->assertEquals($this->customer->id, $charge->metadata['customer_id']);
        $this->assertEquals('1/4', $charge->metadata['fuel_final_reading']);
        $this->assertEquals('3/4', $charge->metadata['fuel_initial_reading']);
        $this->assertTrue($charge->metadata['mobile_source']);
    }

    public function test_billing_charge_stores_legacy_customer_account_id(): void
    {
        $this->postSaveReturn();

        $ca     = CustomerAccount::first();
        $charge = BillingCharge::first();

        $this->assertEquals($ca->id, $charge->metadata['legacy_customer_account_id']);
    }

    public function test_billing_charge_has_blc_prefixed_unique_id(): void
    {
        $this->postSaveReturn();

        $this->assertStringStartsWith('BLC', BillingCharge::first()->unique_id);
    }

    public function test_billing_charge_idempotency_key_uses_order_product_id_and_reading(): void
    {
        $this->postSaveReturn(['fuel_final_reading' => '1/4']);

        $charge = BillingCharge::first();

        // PR-A3: the key now also carries a rental-cycle disambiguator (the minimum id of
        // the order product's currently-active checklist question rows), so a second
        // legitimate rental cycle on the same OrderProduct doesn't collide with the first
        // cycle's charge. This test's OrderProduct has no checklist rows, so the cycle
        // key falls back to the literal 'nocycle' — see CORRECTION_PHASE1_PLAN.md Issue #1
        // and BillingChargeRequest::mobileReturnDamage()'s docblock for the same pattern.
        $this->assertEquals(
            "mobile_return_fuel:{$this->orderProduct->id}:1/4:nocycle",
            $charge->idempotency_key
        );
    }

    // ── Idempotency ────────────────────────────────────────────────────────

    public function test_idempotency_prevents_duplicate_billing_charge_on_retry(): void
    {
        $this->postSaveReturn(['fuel_final_reading' => '1/4', 'fuel_total_charge' => '85.00']);

        // Direct BillingEngine call with the same key — simulates a mobile retry.
        // Includes the ':nocycle' suffix (PR-A3) to match the real key SaveReturnController
        // now produces for an OrderProduct with no checklist rows.
        BillingEngine::charge(new BillingChargeRequest(
            type:           BillingChargeType::Fuel->value,
            orderId:        $this->order->id,
            customerId:     $this->customer->id,
            amount:         85.00,
            idempotencyKey: "mobile_return_fuel:{$this->orderProduct->id}:1/4:nocycle",
        ));

        $this->assertEquals(1, BillingCharge::count());
    }

    public function test_bridge_does_not_fire_when_legacy_write_was_skipped(): void
    {
        // First request creates the CA + BillingCharge
        $this->postSaveReturn();
        $this->assertEquals(1, BillingCharge::count());

        // ChargeService duplicate guard returns null on retry:
        // the bridge should not fire, so BillingCharge count stays at 1
        // (The real mobile 409 fires first, but here we verify the service-level guard)
        $this->orderProduct->refresh();
        $null = \App\Services\ChargeService::createFromOrderProduct(
            $this->orderProduct,
            'fuel',
            $this->user->id
        );

        $this->assertNull($null, 'ChargeService returns null on duplicate');
        $this->assertEquals(1, BillingCharge::count(), 'BillingCharge count stays at 1');
    }

    // ── Legacy still works even if bridge fails ────────────────────────────

    public function test_legacy_ca_succeeds_even_when_billing_engine_bridge_fails(): void
    {
        Schema::drop('billing_charges');

        $response = $this->postSaveReturn();

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('customer_accounts', [
            'customer_id'      => $this->customer->id,
            'order_id'         => $this->order->id,
            'order_product_id' => $this->orderProduct->id,
            'reason'           => 'Fuel Charge',
            'type'             => 'charge',
        ]);
    }

    public function test_billing_engine_failure_is_logged_to_billing_engine_channel(): void
    {
        Log::shouldReceive('channel')->with('billing_engine')->andReturnSelf();
        Log::shouldReceive('error')->once();
        Log::shouldReceive('info')->andReturn(null);

        Schema::drop('billing_charges');

        $this->postSaveReturn();
    }

    // ── Phase 5D: customer_account_id ─────────────────────────────────────

    public function test_billing_charge_stores_customer_account_id(): void
    {
        $this->postSaveReturn();

        $ca     = CustomerAccount::first();
        $charge = BillingCharge::first();

        $this->assertEquals($ca->id, $charge->customer_account_id);
    }

    // ── No side effects ────────────────────────────────────────────────────

    public function test_billing_charge_does_not_interfere_with_legacy_fuel_alert_query(): void
    {
        $this->postSaveReturn();

        $alertCount = CustomerAccount::where('reason', 'Fuel Charge')
            ->where('fuel_alert_status', 'pending')
            ->count();

        $this->assertEquals(1, $alertCount);
        $this->assertEquals(1, BillingCharge::count());
    }

    public function test_mobile_damage_charges_are_not_added(): void
    {
        $this->postSaveReturn();

        // No damage charges in the mobile path — damage not yet implemented
        $this->assertEquals(0, CustomerAccount::where('reason', 'Damages')->count());
    }
}
