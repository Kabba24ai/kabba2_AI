<?php

namespace Tests\Feature\BillingEngine;

use App\Enums\Billing\BillingChargeStatus;
use App\Enums\Billing\BillingChargeType;
use App\Enums\Billing\BillingSourceEvent;
use App\Enums\Billing\BillingSourceModule;
use App\Http\DataObjects\BillingChargeRequest;
use App\Models\Configurations\Setting;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\BillingCharge;
use App\Models\Orders\Order;
use App\Services\BillingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RentalExtensionBridgeTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private User $user;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'Extension',
            'last_name'  => 'Customer',
            'email'      => 'extension-bridge@example.com',
            'status'     => 'Active',
        ]);

        $this->user = User::create([
            'first_name' => 'Admin',
            'last_name'  => 'User',
            'email'      => 'admin-extension-bridge@example.com',
            'password'   => bcrypt('password'),
        ]);

        $this->order = Order::create([
            'order_number'    => 'ORD-EXT-001',
            'order_date'      => now()->toDateString(),
            'customer_id'     => $this->customer->id,
            'customer_name'   => $this->customer->first_name . ' ' . $this->customer->last_name,
            'created_by_id'   => $this->user->id,
            'created_by_type' => User::class,
            'updated_by_id'   => $this->user->id,
            'updated_by_type' => User::class,
        ]);
    }

    private function postExtension(array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->withoutMiddleware()
            ->actingAs($this->user)
            ->postJson(
                route('admin.order-management.orders.extension.store', ['unique_id' => $this->order->unique_id]),
                array_merge([
                    'description'        => 'Extra week rental',
                    'base_amount'        => '200.00',
                    'add_tax'            => false,
                    'responsible_person' => $this->user->id,
                    'notes'              => null,
                ], $overrides)
            );
    }

    // ── Suffix bug fix: soft-delete collision ─────────────────────────────

    public function test_suffix_uses_withTrashed_to_avoid_collision_with_soft_deleted_extension(): void
    {
        // Create and soft-delete an extension so it holds the 'ORD-EXT-001-A' UNIQUE slot
        $this->postExtension()->assertOk()->assertJson(['success' => true]);

        $extension = Order::where('reference_order_number', 'ORD-EXT-001')->first();
        $this->assertEquals('ORD-EXT-001-A', $extension->order_number);

        $extension->delete(); // soft-delete — still holds the UNIQUE slot

        // A second extension must get suffix B, not A (which would collide)
        $this->postExtension()->assertOk()->assertJson(['success' => true]);

        $second = Order::where('reference_order_number', 'ORD-EXT-001')
            ->where('order_number', 'ORD-EXT-001-B')
            ->first();

        $this->assertNotNull($second, 'Second extension should receive suffix B, not collide on A');
    }

    // ── Suffix bug fix: reorder exclusion ─────────────────────────────────

    public function test_suffix_excludes_reorders_from_count(): void
    {
        // Simulate a reorder row that sets reference_order_number but gets a NEW sequential number
        Order::create([
            'order_number'           => 'ORD-EXT-REORDER-999',
            'reference_order_number' => 'ORD-EXT-001', // shares reference but is NOT an extension
            'customer_id'            => $this->customer->id,
            'customer_name'          => $this->customer->customer_name ?? 'Extension Customer',
            'created_by_id'          => $this->user->id,
            'created_by_type'        => User::class,
            'updated_by_id'          => $this->user->id,
            'updated_by_type'        => User::class,
        ]);

        // Despite the reorder row, the first true extension should still get suffix A
        $this->postExtension()->assertOk()->assertJson(['success' => true]);

        $extension = Order::where('reference_order_number', 'ORD-EXT-001')
            ->where('order_number', 'like', 'ORD-EXT-001-%')
            ->first();

        $this->assertEquals('ORD-EXT-001-A', $extension->order_number);
    }

    // ── Legacy behavior: child order still created ────────────────────────

    public function test_extension_creates_child_order(): void
    {
        $this->postExtension()->assertOk()->assertJson(['success' => true]);

        $this->assertEquals(1, Order::where('reference_order_number', 'ORD-EXT-001')->count());
    }

    public function test_extension_child_order_has_correct_suffix(): void
    {
        $this->postExtension()->assertOk();

        $extension = Order::where('reference_order_number', 'ORD-EXT-001')->first();

        $this->assertEquals('ORD-EXT-001-A', $extension->order_number);
    }

    public function test_extension_response_is_json_with_success_true(): void
    {
        $response = $this->postExtension();

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertArrayHasKey('extension', $response->json());
    }

    // ── Bridge creates BillingCharge ──────────────────────────────────────

    public function test_extension_creates_billing_charge(): void
    {
        $this->postExtension();

        $this->assertEquals(1, BillingCharge::count());
    }

    public function test_billing_charge_has_correct_type_and_status(): void
    {
        $this->postExtension();

        $charge = BillingCharge::first();

        $this->assertEquals(BillingChargeType::Extension, $charge->billing_charge_type);
        $this->assertEquals(BillingChargeStatus::Pending, $charge->status);
    }

    public function test_billing_charge_parent_order_id_is_original_order(): void
    {
        $this->postExtension();

        $this->assertEquals($this->order->id, BillingCharge::first()->parent_order_id);
    }

    public function test_billing_charge_child_order_id_is_extension_order(): void
    {
        $this->postExtension();

        $extension = Order::where('reference_order_number', 'ORD-EXT-001')->first();

        $this->assertEquals($extension->id, BillingCharge::first()->child_order_id);
    }

    public function test_billing_charge_has_correct_customer_and_amount(): void
    {
        $this->postExtension(['base_amount' => '350.00', 'add_tax' => false]);

        $charge = BillingCharge::first();

        $this->assertEquals($this->customer->id, $charge->customer_id);
        $this->assertEquals(350.00, $charge->amount);
    }

    public function test_billing_charge_amount_includes_tax_when_add_tax_true(): void
    {
        // Tax rate from config; grand_total = base + tax
        $this->postExtension(['base_amount' => '100.00', 'add_tax' => true]);

        $extension = Order::where('reference_order_number', 'ORD-EXT-001')->first();
        $charge    = BillingCharge::first();

        // BillingCharge amount should match the extension order grand_total
        $this->assertEquals($extension->grand_total, $charge->amount);
    }

    public function test_billing_charge_tax_type_is_add_when_add_tax_true(): void
    {
        $this->postExtension(['add_tax' => true]);

        $this->assertEquals('add', BillingCharge::first()->tax_type);
    }

    public function test_billing_charge_tax_type_is_free_when_add_tax_false(): void
    {
        $this->postExtension(['add_tax' => false]);

        $this->assertEquals('free', BillingCharge::first()->tax_type);
    }

    public function test_billing_charge_stores_source_module_and_event(): void
    {
        $this->postExtension();

        $charge = BillingCharge::first();

        $this->assertEquals(BillingSourceModule::RentalExtension->value, $charge->source_module);
        $this->assertEquals(BillingSourceEvent::RentalExtensionCreated->value, $charge->source_event);
    }

    public function test_billing_charge_source_reference_is_extension_order(): void
    {
        $this->postExtension();

        $extension = Order::where('reference_order_number', 'ORD-EXT-001')->first();
        $charge    = BillingCharge::first();

        $this->assertEquals('Order', $charge->source_reference_type);
        $this->assertEquals($extension->id, $charge->source_reference_id);
    }

    public function test_billing_charge_metadata_stores_parent_and_child_order_numbers(): void
    {
        $this->postExtension(['description' => 'One more week']);

        $extension = Order::where('reference_order_number', 'ORD-EXT-001')->first();
        $charge    = BillingCharge::first();

        $this->assertIsArray($charge->metadata);
        $this->assertEquals('ORD-EXT-001', $charge->metadata['parent_order_number']);
        $this->assertEquals('ORD-EXT-001-A', $charge->metadata['child_order_number']);
        $this->assertEquals($this->order->id, $charge->metadata['parent_order_id']);
        $this->assertEquals($extension->id, $charge->metadata['child_order_id']);
        $this->assertEquals('One more week', $charge->metadata['description']);
        $this->assertTrue($charge->metadata['extension_context']);
    }

    public function test_billing_charge_has_blc_prefixed_unique_id(): void
    {
        $this->postExtension();

        $this->assertStringStartsWith('BLC', BillingCharge::first()->unique_id);
    }

    // ── Idempotency ────────────────────────────────────────────────────────

    public function test_idempotency_key_format_is_rental_extension_child_id(): void
    {
        $this->postExtension();

        $extension = Order::where('reference_order_number', 'ORD-EXT-001')->first();
        $charge    = BillingCharge::first();

        $this->assertEquals("rental_extension:{$extension->id}", $charge->idempotency_key);
    }

    public function test_duplicate_key_does_not_create_second_billing_charge(): void
    {
        $this->postExtension();

        $extension = Order::where('reference_order_number', 'ORD-EXT-001')->first();

        BillingEngine::charge(new BillingChargeRequest(
            type:           BillingChargeType::Extension->value,
            orderId:        $this->order->id,
            customerId:     $this->customer->id,
            amount:         200.00,
            idempotencyKey: "rental_extension:{$extension->id}",
            childOrderId:   $extension->id,
        ));

        $this->assertEquals(1, BillingCharge::count());
    }

    // ── Phase 5D: customer_account_id and tax_amount ──────────────────────

    public function test_billing_charge_customer_account_id_is_null_for_extensions(): void
    {
        // Extensions do not create a CustomerAccount row — customer_account_id must be null
        $this->postExtension();

        $this->assertNull(BillingCharge::first()->customer_account_id);
    }

    public function test_billing_charge_stores_tax_amount_when_add_tax_true(): void
    {
        // Set an 8% tax rate so the extension actually calculates a non-zero tax
        Setting::create(['setting_name' => 'sales_tax', 'setting_value' => '0.08']);

        $this->postExtension(['base_amount' => '100.00', 'add_tax' => true]);

        $extension = Order::where('reference_order_number', 'ORD-EXT-001')->first();
        $charge    = BillingCharge::first();

        // tax_amount on billing_charge must match what the extension order calculated (8.00)
        $this->assertEquals($extension->tax_amount, $charge->tax_amount);
        $this->assertGreaterThan(0, $charge->tax_amount);
    }

    public function test_billing_charge_tax_amount_is_zero_when_no_tax(): void
    {
        $this->postExtension(['add_tax' => false]);

        $this->assertEquals(0.0, BillingCharge::first()->tax_amount);
    }

    // ── Failure isolation ─────────────────────────────────────────────────

    public function test_billing_engine_failure_does_not_break_extension_creation(): void
    {
        Schema::drop('billing_charges');

        $response = $this->postExtension();

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertEquals(1, Order::where('reference_order_number', 'ORD-EXT-001')->count());
    }

    public function test_billing_engine_failure_is_logged_to_billing_engine_channel(): void
    {
        Log::shouldReceive('channel')->with('billing_engine')->andReturnSelf();
        Log::shouldReceive('error')->once();
        Log::shouldReceive('info')->andReturn(null);

        Schema::drop('billing_charges');

        $this->postExtension();
    }
}
