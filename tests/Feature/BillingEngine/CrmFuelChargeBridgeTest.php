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
use App\Services\BillingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CrmFuelChargeBridgeTest extends TestCase
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
            'email'      => 'crm-bridge-test@example.com',
            'status'     => 'Active',
        ]);

        $this->user = User::create([
            'first_name' => 'Admin',
            'last_name'  => 'User',
            'email'      => 'admin-crm-bridge@example.com',
            'password'   => bcrypt('password'),
        ]);
    }

    private function postCrmCharge(array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->withoutMiddleware()
            ->actingAs($this->user)
            ->post(route('admin.crm.customers.customer-account.chargestore'), array_merge([
                'customer_id'        => $this->customer->id,
                'amount'             => '80.00',
                'reason'             => 'Fuel Charge',
                'responsible_person' => $this->user->id,
                'sales_tax'          => 'free',
                'notes'              => 'Test CRM fuel charge',
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

    public function test_crm_fuel_charge_creates_customer_account_record(): void
    {
        $this->postCrmCharge()->assertRedirect();

        $this->assertDatabaseHas('customer_accounts', [
            'customer_id'       => $this->customer->id,
            'amount'            => 80.00,
            'reason'            => 'Fuel Charge',
            'type'              => 'charge',
            'fuel_alert_status' => 'pending',
            'sales_tax_type'    => 'free',
            'sales_tax'         => 0,
        ]);
    }

    public function test_crm_fuel_charge_returns_redirect(): void
    {
        $this->postCrmCharge()->assertStatus(302);
    }

    public function test_crm_damage_charge_creates_customer_account_with_correct_fields(): void
    {
        $this->postCrmCharge(['reason' => 'Damages', 'amount' => '300.00'])->assertRedirect();

        $this->assertDatabaseHas('customer_accounts', [
            'customer_id'          => $this->customer->id,
            'amount'               => 300.00,
            'reason'               => 'Damages',
            'type'                 => 'charge',
            'damage_alert_status'  => 'pending',
            'fuel_alert_status'    => null,
        ]);
    }

    public function test_crm_damage_charge_also_creates_billing_charge(): void
    {
        $this->postCrmCharge(['reason' => 'Damages']);

        // Phase 4D bridges the damage arm — BillingCharge is created with type=damage
        $this->assertEquals(1, BillingCharge::count());
        $this->assertEquals(BillingChargeType::Damage, BillingCharge::first()->billing_charge_type);
    }

    // ── Bridge creates BillingCharge (fuel only) ──────────────────────────

    public function test_crm_fuel_charge_also_creates_billing_charge_record(): void
    {
        $this->postCrmCharge();

        $this->assertEquals(1, BillingCharge::count());
    }

    public function test_billing_charge_has_correct_type_and_status(): void
    {
        $this->postCrmCharge();

        $charge = BillingCharge::first();

        $this->assertEquals(BillingChargeType::Fuel, $charge->billing_charge_type);
        $this->assertEquals(BillingChargeStatus::Pending, $charge->status);
    }

    public function test_billing_charge_has_correct_amount_and_customer(): void
    {
        $this->postCrmCharge(['amount' => '55.25']);

        $charge = BillingCharge::first();

        $this->assertEquals(55.25, $charge->amount);
        $this->assertEquals($this->customer->id, $charge->customer_id);
    }

    public function test_billing_charge_has_null_parent_order_id_for_crm_path(): void
    {
        $this->postCrmCharge();

        // CRM charge modal has no order context — parent_order_id is null
        $this->assertNull(BillingCharge::first()->parent_order_id);
    }

    public function test_billing_charge_has_null_order_product_id(): void
    {
        $this->postCrmCharge();

        $this->assertNull(BillingCharge::first()->order_product_id);
    }

    public function test_billing_charge_stores_source_module_and_event(): void
    {
        $this->postCrmCharge();

        $charge = BillingCharge::first();

        $this->assertEquals(BillingSourceModule::AdminFuelCharge->value, $charge->source_module);
        $this->assertEquals(BillingSourceEvent::AdminFuelChargeCreated->value, $charge->source_event);
    }

    public function test_billing_charge_stores_legacy_customer_account_id(): void
    {
        $this->postCrmCharge();

        $ca     = CustomerAccount::first();
        $charge = BillingCharge::first();

        $this->assertEquals($ca->id, $charge->source_reference_id);
        $this->assertEquals('CustomerAccount', $charge->source_reference_type);
    }

    public function test_billing_charge_stores_crm_context_in_metadata(): void
    {
        $this->postCrmCharge();

        $charge = BillingCharge::first();

        $this->assertIsArray($charge->metadata);
        $this->assertEquals('ChargeStoreController', $charge->metadata['legacy_controller']);
        $this->assertArrayHasKey('legacy_customer_account_id', $charge->metadata);
        $this->assertEquals($this->customer->id, $charge->metadata['customer_id']);
    }

    public function test_billing_charge_has_blc_prefixed_unique_id(): void
    {
        $this->postCrmCharge();

        $this->assertStringStartsWith('BLC', BillingCharge::first()->unique_id);
    }

    public function test_billing_charge_idempotency_key_uses_crm_prefix_and_ca_id(): void
    {
        $this->postCrmCharge();

        $ca     = CustomerAccount::first();
        $charge = BillingCharge::first();

        $this->assertEquals("crm_fuel_charge:{$ca->id}", $charge->idempotency_key);
    }

    // ── Idempotency ────────────────────────────────────────────────────────

    public function test_duplicate_idempotency_key_does_not_create_second_billing_charge(): void
    {
        $this->postCrmCharge();

        $ca = CustomerAccount::first();

        BillingEngine::charge(new BillingChargeRequest(
            type:           BillingChargeType::Fuel->value,
            orderId:        null,
            customerId:     $this->customer->id,
            amount:         80.00,
            idempotencyKey: "crm_fuel_charge:{$ca->id}",
        ));

        $this->assertEquals(1, BillingCharge::count());
    }

    // ── Legacy still works even if bridge fails ────────────────────────────

    public function test_legacy_charge_succeeds_even_when_billing_engine_bridge_fails(): void
    {
        $this->forceBillingChargeCreationFailure();

        $response = $this->postCrmCharge();

        $response->assertRedirect();

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

        $this->postCrmCharge();
    }

    // ── Tax type mapping ───────────────────────────────────────────────────

    public function test_tax_type_is_stored_on_billing_charge(): void
    {
        $this->postCrmCharge(['sales_tax' => 'add']);

        $this->assertEquals('add', BillingCharge::first()->tax_type);
    }

    public function test_tax_type_defaults_to_free_when_not_provided(): void
    {
        $this->postCrmCharge(['sales_tax' => null]);

        $this->assertEquals('free', BillingCharge::first()->tax_type);
    }

    // ── Phase 5D: customer_account_id ─────────────────────────────────────

    public function test_billing_charge_stores_customer_account_id(): void
    {
        $this->postCrmCharge();

        $ca     = CustomerAccount::first();
        $charge = BillingCharge::first();

        $this->assertEquals($ca->id, $charge->customer_account_id);
    }

    // ── No side effects ────────────────────────────────────────────────────

    public function test_billing_charge_does_not_interfere_with_legacy_crm_query(): void
    {
        $this->postCrmCharge();

        $alertCount = CustomerAccount::where('reason', 'Fuel Charge')
            ->where('fuel_alert_status', 'pending')
            ->count();

        $this->assertEquals(1, $alertCount);
        $this->assertEquals(1, BillingCharge::count());
    }
}
