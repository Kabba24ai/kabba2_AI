<?php

namespace Tests\Feature\Discounts;

use App\Enums\Discounts\DiscountTargetType;
use App\Models\Configurations\Setting;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerCredit;
use App\Models\Discounts\ProductDiscount;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Services\CustomerCreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Increment 2b — EXTENSION surface (discount at charge-creation time). The
 * discount reduces the base before tax + the obligation are booked, atomically.
 */
class DiscountExtensionIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private const RATE = 0.0975;
    private const ROUTE = 'admin.order-management.orders.extension.store';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::updateOrCreate(['setting_name' => 'sales_tax'], ['setting_type' => 'Product Settings', 'setting_value' => self::RATE]);
        $this->admin = User::create(['first_name' => 'Ext', 'last_name' => 'Admin', 'email' => 'ext-admin@test.local', 'status' => 'Active']);
        $this->actingAs($this->admin);
    }

    private function customer(float $credit): Customer
    {
        $c = Customer::create(['first_name' => 'Ext', 'last_name' => 'Cust', 'email' => 'ext-' . uniqid() . '@test.local', 'status' => 'Active']);
        if ($credit > 0) {
            CustomerCredit::create(['customer_id' => $c->id, 'type' => 'grant', 'amount' => $credit, 'reason' => 'seed']);
        }
        return $c;
    }

    private function parentOrder(Customer $c): Order
    {
        return Order::create([
            'order_number' => 'ORD-' . strtoupper(uniqid()),
            'order_date' => now()->format('Y-m-d'),
            'customer_id' => $c->id, 'customer_name' => $c->full_name,
            'subtotal' => 100, 'tax_amount' => 0, 'grand_total' => 100,
        ]);
    }

    private function submitExtension(Order $order, array $payload)
    {
        return $this->postJson(route(self::ROUTE, ['unique_id' => $order->unique_id]), $payload);
    }

    private function childOf(Order $parent): ?Order
    {
        return Order::where('reference_order_number', $parent->order_number)->first();
    }

    public function test_no_discount_extension_unchanged(): void
    {
        $c = $this->customer(0);
        $o = $this->parentOrder($c);
        $this->submitExtension($o, ['description' => 'Ext', 'base_amount' => 1000, 'add_tax' => true, 'responsible_person' => $this->admin->id])
            ->assertOk();

        $child = $this->childOf($o);
        $this->assertEquals(1000.00, (float) $child->subtotal);
        $this->assertEquals(97.50, (float) $child->tax_amount);
        $this->assertEquals(1097.50, (float) $child->grand_total);
        $this->assertEquals(0, ProductDiscount::count());
    }

    public function test_partial_store_credit_discount_at_creation(): void
    {
        $c = $this->customer(1000);
        $o = $this->parentOrder($c);
        $this->submitExtension($o, ['description' => 'Ext', 'base_amount' => 1000, 'add_tax' => true, 'responsible_person' => $this->admin->id, 'store_credit_discount' => 400])
            ->assertOk();

        $child = $this->childOf($o);
        $this->assertEquals(600.00, (float) $child->subtotal, 'base reduced pre-tax');
        $this->assertEquals(58.50, (float) $child->tax_amount, 'tax on discounted base');
        $this->assertEquals(658.50, (float) $child->grand_total);

        $d = ProductDiscount::where('target_type', DiscountTargetType::Extension->value)->first();
        $this->assertNotNull($d);
        $this->assertEquals($child->id, $d->target_id);
        $this->assertEquals(400.00, (float) $d->calculated_discount_amount);
        $this->assertNotNull($d->store_credit_redemption_id);
        $this->assertEquals(600.00, CustomerCreditService::remainingBalance($c->id), 'balance reduced once');
        $this->assertEquals(0, DB::table('order_payments')->where('payment_method', 'StoreCredit')->count(), 'no Store Credit payment row');
    }

    public function test_full_store_credit_discount_zero_obligation(): void
    {
        $c = $this->customer(500);
        $o = $this->parentOrder($c);
        $this->submitExtension($o, ['description' => 'Ext', 'base_amount' => 500, 'add_tax' => true, 'responsible_person' => $this->admin->id, 'store_credit_discount' => 500])
            ->assertOk();

        $child = $this->childOf($o);
        $this->assertEquals(0.00, (float) $child->subtotal);
        $this->assertEquals(0.00, (float) $child->tax_amount);
        $this->assertEquals(0.00, (float) $child->grand_total);
        $this->assertTrue((bool) $child->is_paid, 'zero obligation satisfied — no fabricated payment');
        $this->assertEquals(0, DB::table('order_payments')->where('payment_method', 'StoreCredit')->count());
        $this->assertEquals(0.00, CustomerCreditService::remainingBalance($c->id));
    }

    public function test_over_available_rejected_and_rolled_back(): void
    {
        $c = $this->customer(100);
        $o = $this->parentOrder($c);
        $this->submitExtension($o, ['description' => 'Ext', 'base_amount' => 1000, 'add_tax' => true, 'responsible_person' => $this->admin->id, 'store_credit_discount' => 400])
            ->assertStatus(422);

        $this->assertNull($this->childOf($o), 'extension NOT created on rejected discount (rollback)');
        $this->assertEquals(0, ProductDiscount::count());
        $this->assertEquals(100.00, CustomerCreditService::remainingBalance($c->id), 'balance untouched');
    }

    public function test_over_eligible_rejected_and_rolled_back(): void
    {
        $c = $this->customer(1000);
        $o = $this->parentOrder($c);
        $this->submitExtension($o, ['description' => 'Ext', 'base_amount' => 300, 'add_tax' => true, 'responsible_person' => $this->admin->id, 'store_credit_discount' => 400])
            ->assertStatus(422);

        $this->assertNull($this->childOf($o));
        $this->assertEquals(1000.00, CustomerCreditService::remainingBalance($c->id));
    }

    public function test_exempt_extension_discount_has_no_tax(): void
    {
        $c = $this->customer(1000);
        $o = $this->parentOrder($c);
        $this->submitExtension($o, ['description' => 'Ext', 'base_amount' => 1000, 'add_tax' => false, 'responsible_person' => $this->admin->id, 'store_credit_discount' => 400])
            ->assertOk();

        $child = $this->childOf($o);
        $this->assertEquals(600.00, (float) $child->subtotal);
        $this->assertEquals(0.00, (float) $child->tax_amount, 'no tax when add_tax false');
        $this->assertEquals(600.00, (float) $child->grand_total);
    }
}
