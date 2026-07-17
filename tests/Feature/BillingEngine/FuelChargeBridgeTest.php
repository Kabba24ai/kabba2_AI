<?php

namespace Tests\Feature\BillingEngine;

use App\Enums\Billing\BillingChargeStatus;
use App\Enums\Billing\BillingChargeType;
use App\Enums\Billing\BillingSourceEvent;
use App\Enums\Billing\BillingSourceModule;
use App\Models\Configurations\Setting;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAccount;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\BillingCharge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class FuelChargeBridgeTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'Test',
            'last_name'  => 'Customer',
            'email'      => 'bridge-test@example.com',
            'status'     => 'Active',
        ]);

        $this->user = User::create([
            'first_name' => 'Admin',
            'last_name'  => 'User',
            'email'      => 'admin-bridge-test@example.com',
            'password'   => bcrypt('password'),
        ]);
    }

    private function postFuelCharge(array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->withoutMiddleware()
            ->actingAs($this->user)
            ->postJson(route('admin.dashboard.fuel-charge.store'), array_merge([
                'customer_id'        => $this->customer->id,
                'amount'             => '75.00',
                'notes'              => 'Test fuel charge',
                'responsible_person' => $this->user->id,
                'sales_tax_type'     => 'free',
            ], $overrides));
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

    public function test_fuel_charge_creates_customer_account_record(): void
    {
        $this->postFuelCharge()->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('customer_accounts', [
            'customer_id'      => $this->customer->id,
            'amount'           => 75.00,
            'reason'           => 'Fuel Charge',
            'type'             => 'charge',
            'fuel_alert_status' => 'pending',
            'sales_tax_type'   => 'free',
            'sales_tax'        => 0,
        ]);
    }

    public function test_fuel_charge_response_is_success(): void
    {
        $response = $this->postFuelCharge();

        $response->assertOk()->assertJson([
            'success' => true,
            'message' => 'Fuel Charge created successfully.',
        ]);
    }

    // ── Bridge creates BillingCharge ──────────────────────────────────────

    public function test_fuel_charge_also_creates_billing_charge_record(): void
    {
        $this->postFuelCharge();

        $this->assertEquals(1, BillingCharge::count());
    }

    public function test_billing_charge_has_correct_type_and_status(): void
    {
        $this->postFuelCharge();

        $charge = BillingCharge::first();

        $this->assertEquals(BillingChargeType::Fuel, $charge->billing_charge_type);
        $this->assertEquals(BillingChargeStatus::Pending, $charge->status);
    }

    public function test_billing_charge_has_correct_amount_and_customer(): void
    {
        $this->postFuelCharge(['amount' => '123.45']);

        $charge = BillingCharge::first();

        $this->assertEquals(123.45, $charge->amount);
        $this->assertEquals($this->customer->id, $charge->customer_id);
    }

    public function test_billing_charge_has_null_parent_order_id_for_dashboard_path(): void
    {
        $this->postFuelCharge();

        $this->assertNull(BillingCharge::first()->parent_order_id);
    }

    public function test_billing_charge_stores_source_module_and_event(): void
    {
        $this->postFuelCharge();

        $charge = BillingCharge::first();

        $this->assertEquals(BillingSourceModule::AdminFuelCharge->value, $charge->source_module);
        $this->assertEquals(BillingSourceEvent::AdminFuelChargeCreated->value, $charge->source_event);
    }

    public function test_billing_charge_stores_legacy_customer_account_id(): void
    {
        $this->postFuelCharge();

        $ca     = CustomerAccount::first();
        $charge = BillingCharge::first();

        $this->assertEquals($ca->id, $charge->source_reference_id);
        $this->assertEquals('CustomerAccount', $charge->source_reference_type);
    }

    public function test_billing_charge_stores_legacy_controller_in_metadata(): void
    {
        $this->postFuelCharge();

        $charge = BillingCharge::first();

        $this->assertIsArray($charge->metadata);
        $this->assertEquals('FuelChargeStoreController', $charge->metadata['legacy_controller']);
        $this->assertArrayHasKey('legacy_customer_account_id', $charge->metadata);
    }

    public function test_billing_charge_has_blc_prefixed_unique_id(): void
    {
        $this->postFuelCharge();

        $this->assertStringStartsWith('BLC', BillingCharge::first()->unique_id);
    }

    public function test_billing_charge_idempotency_key_uses_customer_account_id(): void
    {
        $this->postFuelCharge();

        $ca     = CustomerAccount::first();
        $charge = BillingCharge::first();

        $this->assertEquals("admin_fuel_charge:{$ca->id}", $charge->idempotency_key);
    }

    // ── Idempotency ────────────────────────────────────────────────────────

    public function test_duplicate_request_does_not_create_duplicate_billing_charge(): void
    {
        // Simulate an exact duplicate by manually calling the bridge with the same
        // customer account ID (idempotency_key would be the same)
        $this->postFuelCharge();

        $ca = CustomerAccount::first();

        // Manually call BillingEngine with the same key — should return existing charge
        $chargeRequest = new \App\Http\DataObjects\BillingChargeRequest(
            type: BillingChargeType::Fuel->value,
            orderId: null,
            customerId: $this->customer->id,
            amount: 75.00,
            idempotencyKey: "admin_fuel_charge:{$ca->id}",
        );
        \App\Services\BillingEngine::charge($chargeRequest);

        // Still only 1 BillingCharge despite two calls with the same key
        $this->assertEquals(1, BillingCharge::count());
    }

    // ── Legacy still works even if bridge fails ────────────────────────────

    public function test_legacy_charge_succeeds_even_when_billing_engine_bridge_fails(): void
    {
        // Drop the billing_charges table to force a BillingEngine write failure
        $this->forceBillingChargeCreationFailure();

        $response = $this->postFuelCharge();

        // Response must still be successful
        $response->assertOk()->assertJson(['success' => true]);

        // Legacy CustomerAccount must still be created
        $this->assertDatabaseHas('customer_accounts', [
            'customer_id' => $this->customer->id,
            'reason'      => 'Fuel Charge',
            'type'        => 'charge',
        ]);
    }

    public function test_billing_engine_failure_is_logged_to_billing_engine_channel(): void
    {
        Log::shouldReceive('channel')->with('billing_engine')->andReturnSelf();
        Log::shouldReceive('error')->once();
        Log::shouldReceive('info')->andReturn(null);

        $this->forceBillingChargeCreationFailure();

        $this->postFuelCharge();
    }

    // ── Tax type mapping ───────────────────────────────────────────────────

    public function test_tax_type_is_stored_on_billing_charge(): void
    {
        $this->postFuelCharge(['sales_tax_type' => 'add']);

        $this->assertEquals('add', BillingCharge::first()->tax_type);
    }

    public function test_tax_type_defaults_to_free_when_not_provided(): void
    {
        $this->postFuelCharge(['sales_tax_type' => null]);

        $this->assertEquals('free', BillingCharge::first()->tax_type);
    }

    // ── Phase 5D: customer_account_id ─────────────────────────────────────

    public function test_billing_charge_stores_customer_account_id(): void
    {
        $this->postFuelCharge();

        $ca     = CustomerAccount::first();
        $charge = BillingCharge::first();

        $this->assertEquals($ca->id, $charge->customer_account_id);
    }

    // ── Tax amount calculation ─────────────────────────────────────────────

    public function test_billing_charge_stores_correct_tax_amount_for_add_type(): void
    {
        Setting::firstOrCreate(['setting_name' => 'sales_tax'], ['setting_value' => '0.0975']);

        $this->postFuelCharge(['amount' => '100.00', 'sales_tax_type' => 'add']);

        $charge = BillingCharge::first();

        $this->assertEquals(100.00, $charge->amount);
        $this->assertEquals(9.75, $charge->tax_amount);
    }

    public function test_billing_charge_stores_zero_tax_for_free_type(): void
    {
        Setting::firstOrCreate(['setting_name' => 'sales_tax'], ['setting_value' => '0.0975']);

        $this->postFuelCharge(['amount' => '100.00', 'sales_tax_type' => 'free']);

        $charge = BillingCharge::first();

        $this->assertEquals(100.00, $charge->amount);
        $this->assertEquals(0.0, $charge->tax_amount);
    }

    public function test_billing_charge_splits_base_and_tax_for_reverse_type(): void
    {
        Setting::firstOrCreate(['setting_name' => 'sales_tax'], ['setting_value' => '0.0975']);

        $this->postFuelCharge(['amount' => '100.00', 'sales_tax_type' => 'reverse']);

        $charge = BillingCharge::first();

        $this->assertEquals(91.12, $charge->amount);
        $this->assertEquals(8.88, $charge->tax_amount);
        $this->assertEquals(100.00, round($charge->amount + $charge->tax_amount, 2));
    }

    // ── No side effects ────────────────────────────────────────────────────

    public function test_reports_use_customer_accounts_not_billing_charges(): void
    {
        // Verify billing_charges row does NOT interfere with the fuel alert query
        // (Reports read from customer_accounts where reason='Fuel Charge')
        $this->postFuelCharge();

        $alertCount = CustomerAccount::where('reason', 'Fuel Charge')
            ->where('fuel_alert_status', 'pending')
            ->count();

        $this->assertEquals(1, $alertCount);
    }

    public function test_billing_charge_is_not_read_by_fuel_alert_query(): void
    {
        $this->postFuelCharge();

        // BillingCharge table should have 1 row, but it's invisible to the legacy alert query
        $this->assertEquals(1, BillingCharge::count());

        // The legacy alert query ONLY reads customer_accounts — BillingCharge doesn't interfere
        $legacyAlerts = CustomerAccount::where('reason', 'Fuel Charge')
            ->where('type', 'charge')
            ->where('fuel_alert_status', 'pending')
            ->get();

        $this->assertCount(1, $legacyAlerts);
        $this->assertEquals(75.00, $legacyAlerts->first()->amount);
    }
}
