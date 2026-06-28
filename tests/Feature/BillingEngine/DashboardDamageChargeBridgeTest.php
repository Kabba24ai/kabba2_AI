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

class DashboardDamageChargeBridgeTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'Damage',
            'last_name'  => 'Test',
            'email'      => 'dashboard-damage-bridge@example.com',
            'status'     => 'Active',
        ]);

        $this->user = User::create([
            'first_name' => 'Admin',
            'last_name'  => 'User',
            'email'      => 'admin-damage-bridge@example.com',
            'password'   => bcrypt('password'),
        ]);
    }

    private function postDamageCharge(array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->withoutMiddleware()
            ->actingAs($this->user)
            ->postJson(route('admin.dashboard.damage-charge.store'), array_merge([
                'customer_id'        => $this->customer->id,
                'amount'             => '250.00',
                'notes'              => 'Test dashboard damage charge',
                'responsible_person' => $this->user->id,
                'sales_tax_type'     => 'free',
            ], $overrides));
    }

    // ── Legacy behavior unchanged ─────────────────────────────────────────

    public function test_damage_charge_creates_customer_account_record(): void
    {
        $this->postDamageCharge()->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('customer_accounts', [
            'customer_id'         => $this->customer->id,
            'amount'              => 250.00,
            'reason'              => 'Damages',
            'type'                => 'charge',
            'damage_alert_status' => 'pending',
            'sales_tax_type'      => 'free',
            'sales_tax'           => 0,
        ]);
    }

    public function test_damage_charge_response_is_success(): void
    {
        $response = $this->postDamageCharge();

        $response->assertOk()->assertJson([
            'success' => true,
            'message' => 'Damage Alert created successfully.',
        ]);
    }

    public function test_damage_charge_creates_customer_note(): void
    {
        $this->postDamageCharge(['notes' => 'Forklift dent on cab']);

        $this->assertDatabaseHas('customer_notes', [
            'customer_id' => $this->customer->id,
        ]);
    }

    // ── Bridge creates BillingCharge ──────────────────────────────────────

    public function test_damage_charge_also_creates_billing_charge_record(): void
    {
        $this->postDamageCharge();

        $this->assertEquals(1, BillingCharge::count());
    }

    public function test_billing_charge_has_correct_type_and_status(): void
    {
        $this->postDamageCharge();

        $charge = BillingCharge::first();

        $this->assertEquals(BillingChargeType::Damage, $charge->billing_charge_type);
        $this->assertEquals(BillingChargeStatus::Pending, $charge->status);
    }

    public function test_billing_charge_has_correct_amount_and_customer(): void
    {
        $this->postDamageCharge(['amount' => '400.00']);

        $charge = BillingCharge::first();

        $this->assertEquals(400.00, $charge->amount);
        $this->assertEquals($this->customer->id, $charge->customer_id);
    }

    public function test_billing_charge_has_null_parent_order_id_for_dashboard_path(): void
    {
        $this->postDamageCharge();

        // Dashboard damage modal has no order context
        $this->assertNull(BillingCharge::first()->parent_order_id);
    }

    public function test_billing_charge_has_null_order_product_id(): void
    {
        $this->postDamageCharge();

        // Dashboard damage modal has no order product context
        $this->assertNull(BillingCharge::first()->order_product_id);
    }

    public function test_billing_charge_stores_source_module_and_event(): void
    {
        $this->postDamageCharge();

        $charge = BillingCharge::first();

        $this->assertEquals(BillingSourceModule::AdminDamageCharge->value, $charge->source_module);
        $this->assertEquals(BillingSourceEvent::AdminDamageChargeCreated->value, $charge->source_event);
    }

    public function test_billing_charge_stores_customer_account_as_source_reference(): void
    {
        $this->postDamageCharge();

        $ca     = CustomerAccount::first();
        $charge = BillingCharge::first();

        $this->assertEquals('CustomerAccount', $charge->source_reference_type);
        $this->assertEquals($ca->id, $charge->source_reference_id);
    }

    public function test_billing_charge_stores_metadata_with_legacy_context(): void
    {
        $this->postDamageCharge();

        $ca     = CustomerAccount::first();
        $charge = BillingCharge::first();

        $this->assertIsArray($charge->metadata);
        $this->assertEquals('DamageChargeStoreController', $charge->metadata['legacy_controller']);
        $this->assertEquals($ca->id, $charge->metadata['legacy_customer_account_id']);
        $this->assertEquals($this->customer->id, $charge->metadata['customer_id']);
        $this->assertEquals('free', $charge->metadata['sales_tax_type']);
        $this->assertTrue($charge->metadata['dashboard_context']);
    }

    public function test_billing_charge_has_blc_prefixed_unique_id(): void
    {
        $this->postDamageCharge();

        $this->assertStringStartsWith('BLC', BillingCharge::first()->unique_id);
    }

    public function test_billing_charge_idempotency_key_uses_dashboard_damage_prefix_and_ca_id(): void
    {
        $this->postDamageCharge();

        $ca     = CustomerAccount::first();
        $charge = BillingCharge::first();

        $this->assertEquals("admin_dashboard_damage_charge:{$ca->id}", $charge->idempotency_key);
    }

    // ── Idempotency ────────────────────────────────────────────────────────

    public function test_duplicate_key_does_not_create_second_billing_charge(): void
    {
        $this->postDamageCharge();

        $ca = CustomerAccount::first();

        BillingEngine::charge(new BillingChargeRequest(
            type:           BillingChargeType::Damage->value,
            orderId:        null,
            customerId:     $this->customer->id,
            amount:         250.00,
            idempotencyKey: "admin_dashboard_damage_charge:{$ca->id}",
        ));

        $this->assertEquals(1, BillingCharge::count());
    }

    // ── Legacy still works even if bridge fails ────────────────────────────

    public function test_legacy_charge_succeeds_even_when_billing_engine_bridge_fails(): void
    {
        Schema::drop('billing_charges');

        $response = $this->postDamageCharge();

        $response->assertOk()->assertJson(['success' => true]);

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

        $this->postDamageCharge();
    }

    // ── Tax type mapping ───────────────────────────────────────────────────

    public function test_tax_type_is_stored_on_billing_charge(): void
    {
        $this->postDamageCharge(['sales_tax_type' => 'add']);

        $this->assertEquals('add', BillingCharge::first()->tax_type);
    }

    public function test_tax_type_defaults_to_free_when_not_provided(): void
    {
        $this->postDamageCharge(['sales_tax_type' => null]);

        $this->assertEquals('free', BillingCharge::first()->tax_type);
    }

    // ── Phase 5D: customer_account_id ─────────────────────────────────────

    public function test_billing_charge_stores_customer_account_id(): void
    {
        $this->postDamageCharge();

        $ca     = CustomerAccount::first();
        $charge = BillingCharge::first();

        $this->assertEquals($ca->id, $charge->customer_account_id);
    }

    // ── No side effects ────────────────────────────────────────────────────

    public function test_billing_charge_does_not_interfere_with_damage_alert_query(): void
    {
        $this->postDamageCharge();

        $alertCount = CustomerAccount::where('reason', 'Damages')
            ->where('damage_alert_status', 'pending')
            ->count();

        $this->assertEquals(1, $alertCount);
        $this->assertEquals(1, BillingCharge::count());
    }
}
