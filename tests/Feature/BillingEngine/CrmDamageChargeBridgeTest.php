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
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CrmDamageChargeBridgeTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'CRM',
            'last_name'  => 'Damage',
            'email'      => 'crm-damage-bridge@example.com',
            'status'     => 'Active',
        ]);

        $this->user = User::create([
            'first_name' => 'Admin',
            'last_name'  => 'User',
            'email'      => 'admin-crm-damage@example.com',
            'password'   => bcrypt('password'),
        ]);
    }

    private function postCrmDamageCharge(array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->withoutMiddleware()
            ->actingAs($this->user)
            ->post(route('admin.crm.customers.customer-account.chargestore'), array_merge([
                'customer_id'        => $this->customer->id,
                'amount'             => '175.00',
                'reason'             => 'Damages',
                'responsible_person' => $this->user->id,
                'sales_tax'          => 'free',
                'notes'              => 'Test CRM damage charge',
            ], $overrides));
    }

    // ── Legacy behavior unchanged ─────────────────────────────────────────

    public function test_crm_damage_charge_creates_customer_account_record(): void
    {
        $this->postCrmDamageCharge()->assertRedirect();

        $this->assertDatabaseHas('customer_accounts', [
            'customer_id'         => $this->customer->id,
            'amount'              => 175.00,
            'reason'              => 'Damages',
            'type'                => 'charge',
            'damage_alert_status' => 'pending',
            'fuel_alert_status'   => null,
            'sales_tax_type'      => 'free',
            'sales_tax'           => 0,
        ]);
    }

    public function test_crm_damage_charge_returns_redirect(): void
    {
        $this->postCrmDamageCharge()->assertStatus(302);
    }

    // ── Bridge creates BillingCharge ──────────────────────────────────────

    public function test_crm_damage_charge_also_creates_billing_charge_record(): void
    {
        $this->postCrmDamageCharge();

        $this->assertEquals(1, BillingCharge::count());
    }

    public function test_billing_charge_has_correct_type_and_status(): void
    {
        $this->postCrmDamageCharge();

        $charge = BillingCharge::first();

        $this->assertEquals(BillingChargeType::Damage, $charge->billing_charge_type);
        $this->assertEquals(BillingChargeStatus::Pending, $charge->status);
    }

    public function test_billing_charge_has_correct_amount_and_customer(): void
    {
        $this->postCrmDamageCharge(['amount' => '320.00']);

        $charge = BillingCharge::first();

        $this->assertEquals(320.00, $charge->amount);
        $this->assertEquals($this->customer->id, $charge->customer_id);
    }

    public function test_billing_charge_has_null_parent_order_id_for_crm_path(): void
    {
        $this->postCrmDamageCharge();

        // CRM charge modal has no order context
        $this->assertNull(BillingCharge::first()->parent_order_id);
    }

    public function test_billing_charge_has_null_order_product_id(): void
    {
        $this->postCrmDamageCharge();

        $this->assertNull(BillingCharge::first()->order_product_id);
    }

    public function test_billing_charge_stores_source_module_and_event(): void
    {
        $this->postCrmDamageCharge();

        $charge = BillingCharge::first();

        $this->assertEquals(BillingSourceModule::AdminDamageCharge->value, $charge->source_module);
        $this->assertEquals(BillingSourceEvent::AdminDamageChargeCreated->value, $charge->source_event);
    }

    public function test_billing_charge_stores_customer_account_as_source_reference(): void
    {
        $this->postCrmDamageCharge();

        $ca     = CustomerAccount::first();
        $charge = BillingCharge::first();

        $this->assertEquals('CustomerAccount', $charge->source_reference_type);
        $this->assertEquals($ca->id, $charge->source_reference_id);
    }

    public function test_billing_charge_stores_crm_damage_context_in_metadata(): void
    {
        $this->postCrmDamageCharge();

        $ca     = CustomerAccount::first();
        $charge = BillingCharge::first();

        $this->assertIsArray($charge->metadata);
        $this->assertEquals('ChargeStoreController', $charge->metadata['legacy_controller']);
        $this->assertEquals($ca->id, $charge->metadata['legacy_customer_account_id']);
        $this->assertEquals($this->customer->id, $charge->metadata['customer_id']);
        $this->assertTrue($charge->metadata['crm_context']);
    }

    public function test_billing_charge_has_blc_prefixed_unique_id(): void
    {
        $this->postCrmDamageCharge();

        $this->assertStringStartsWith('BLC', BillingCharge::first()->unique_id);
    }

    public function test_billing_charge_idempotency_key_uses_crm_damage_prefix_and_ca_id(): void
    {
        $this->postCrmDamageCharge();

        $ca     = CustomerAccount::first();
        $charge = BillingCharge::first();

        $this->assertEquals("crm_damage_charge:{$ca->id}", $charge->idempotency_key);
    }

    // ── Idempotency ────────────────────────────────────────────────────────

    public function test_duplicate_key_does_not_create_second_billing_charge(): void
    {
        $this->postCrmDamageCharge();

        $ca = CustomerAccount::first();

        BillingEngine::charge(new BillingChargeRequest(
            type:           BillingChargeType::Damage->value,
            orderId:        null,
            customerId:     $this->customer->id,
            amount:         175.00,
            idempotencyKey: "crm_damage_charge:{$ca->id}",
        ));

        $this->assertEquals(1, BillingCharge::count());
    }

    // ── Legacy still works even if bridge fails ────────────────────────────

    public function test_legacy_charge_succeeds_even_when_billing_engine_bridge_fails(): void
    {
        Schema::drop('billing_charges');

        $response = $this->postCrmDamageCharge();

        $response->assertRedirect();

        $this->assertDatabaseHas('customer_accounts', [
            'customer_id'         => $this->customer->id,
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

        Schema::drop('billing_charges');

        $this->postCrmDamageCharge();
    }

    // ── Tax type mapping ───────────────────────────────────────────────────

    public function test_tax_type_is_stored_on_billing_charge(): void
    {
        $this->postCrmDamageCharge(['sales_tax' => 'add']);

        $this->assertEquals('add', BillingCharge::first()->tax_type);
    }

    public function test_tax_type_defaults_to_free_when_not_provided(): void
    {
        $this->postCrmDamageCharge(['sales_tax' => null]);

        $this->assertEquals('free', BillingCharge::first()->tax_type);
    }

    // ── Fuel arm regression ────────────────────────────────────────────────

    public function test_crm_fuel_bridge_still_creates_fuel_billing_charge(): void
    {
        // Verify Phase 3C fuel bridge is not broken by Phase 4D damage bridge addition
        $this->withoutMiddleware()
            ->actingAs($this->user)
            ->post(route('admin.crm.customers.customer-account.chargestore'), [
                'customer_id'        => $this->customer->id,
                'amount'             => '60.00',
                'reason'             => 'Fuel Charge',
                'responsible_person' => $this->user->id,
                'sales_tax'          => 'free',
            ])
            ->assertRedirect();

        $this->assertEquals(1, BillingCharge::count());
        $this->assertEquals(BillingChargeType::Fuel, BillingCharge::first()->billing_charge_type);
    }

    // ── Arbitrary reasons not bridged ─────────────────────────────────────

    public function test_arbitrary_crm_reason_does_not_create_billing_charge(): void
    {
        // Only 'Fuel Charge' and 'Damages' are bridged — all other reasons fall through
        $this->withoutMiddleware()
            ->actingAs($this->user)
            ->post(route('admin.crm.customers.customer-account.chargestore'), [
                'customer_id'        => $this->customer->id,
                'amount'             => '50.00',
                'reason'             => 'Late Return Fee',
                'responsible_person' => $this->user->id,
            ])
            ->assertRedirect();

        $this->assertEquals(1, CustomerAccount::count());
        $this->assertEquals(0, BillingCharge::count());
    }

    // ── Phase 5D: customer_account_id ─────────────────────────────────────

    public function test_billing_charge_stores_customer_account_id(): void
    {
        $this->postCrmDamageCharge();

        $ca     = CustomerAccount::first();
        $charge = BillingCharge::first();

        $this->assertEquals($ca->id, $charge->customer_account_id);
    }

    // ── No side effects ────────────────────────────────────────────────────

    public function test_billing_charge_does_not_interfere_with_damage_alert_query(): void
    {
        $this->postCrmDamageCharge();

        $alertCount = CustomerAccount::where('reason', 'Damages')
            ->where('damage_alert_status', 'pending')
            ->count();

        $this->assertEquals(1, $alertCount);
        $this->assertEquals(1, BillingCharge::count());
    }
}
