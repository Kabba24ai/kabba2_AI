<?php

namespace Tests\Feature\Discounts;

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
 * Increment 4 — admin Store Credit DISCOUNT endpoints (server-authoritative).
 * Apply reduces the order pre-tax + recomputes tax without a payment row;
 * reject on over-available/over-eligible; remove restores pricing + balance.
 */
class StoreCreditDiscountEndpointTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        \App\Models\Configurations\Setting::updateOrCreate(['setting_name' => 'sales_tax'], ['setting_type' => 'Product Settings', 'setting_value' => 0.0975]);
        $this->admin = User::create(['first_name' => 'UI', 'last_name' => 'Admin', 'email' => 'ui-admin@test.local', 'status' => 'Active']);
        $this->actingAs($this->admin);
    }

    private function customer(float $credit): Customer
    {
        $c = Customer::create(['first_name' => 'UI', 'last_name' => 'Cust', 'email' => 'ui-' . uniqid() . '@test.local', 'status' => 'Active']);
        if ($credit > 0) {
            CustomerCredit::create(['customer_id' => $c->id, 'type' => 'grant', 'amount' => $credit, 'reason' => 'seed']);
        }
        return $c;
    }

    private function order(Customer $c, float $sub, float $tax): Order
    {
        return Order::create([
            'order_date' => now()->format('Y-m-d'), 'customer_id' => $c->id, 'customer_name' => $c->full_name,
            'subtotal' => $sub, 'tax_amount' => $tax, 'grand_total' => $sub + $tax,
        ]);
    }

    private function applyUrl(Order $o): string
    {
        return route('admin.order-management.orders.store-credit-discount.apply', ['unique_id' => $o->unique_id]);
    }

    public function test_apply_reprices_order_and_returns_summary(): void
    {
        $c = $this->customer(1000);
        $o = $this->order($c, 1000, 97.50);

        $res = $this->postJson($this->applyUrl($o), ['store_credit_discount' => 400, 'responsible_person' => $this->admin->id]);
        $res->assertOk()->assertJsonPath('success', true)
            ->assertJsonPath('summary.original_product_value', 1000)
            ->assertJsonPath('summary.store_credit_applied', 400)
            ->assertJsonPath('summary.adjusted_product_value', 600)
            ->assertJsonPath('summary.sales_tax', 58.5)
            ->assertJsonPath('summary.final_amount_due', 658.5);

        $o->refresh();
        $this->assertEquals(1000.00, (float) $o->subtotal, 'gross untouched');
        $this->assertEquals(658.50, (float) $o->grand_total);
        $this->assertEquals(600.00, CustomerCreditService::remainingBalance($c->id));
        $this->assertEquals(0, DB::table('order_payments')->where('payment_method', 'StoreCredit')->count());
    }

    public function test_full_discount_zeroes_final_due(): void
    {
        $c = $this->customer(500);
        $o = $this->order($c, 500, 48.75);
        $this->postJson($this->applyUrl($o), ['store_credit_discount' => 500, 'responsible_person' => $this->admin->id])
            ->assertOk()->assertJsonPath('summary.final_amount_due', 0);
    }

    public function test_over_available_rejected(): void
    {
        $c = $this->customer(100);
        $o = $this->order($c, 1000, 97.50);
        $this->postJson($this->applyUrl($o), ['store_credit_discount' => 400, 'responsible_person' => $this->admin->id])
            ->assertStatus(422);
        $o->refresh();
        $this->assertEquals(1097.50, (float) $o->grand_total, 'unchanged');
        $this->assertEquals(100.00, CustomerCreditService::remainingBalance($c->id));
    }

    public function test_over_eligible_rejected(): void
    {
        $c = $this->customer(1000);
        $o = $this->order($c, 300, 29.25);
        $this->postJson($this->applyUrl($o), ['store_credit_discount' => 400, 'responsible_person' => $this->admin->id])
            ->assertStatus(422);
    }

    public function test_remove_restores_pricing_and_balance(): void
    {
        $c = $this->customer(1000);
        $o = $this->order($c, 1000, 97.50);
        $apply = $this->postJson($this->applyUrl($o), ['store_credit_discount' => 400, 'responsible_person' => $this->admin->id])->json();
        $discountId = $apply['discount_id'];

        $this->deleteJson(route('admin.order-management.orders.store-credit-discount.remove', ['unique_id' => $o->unique_id, 'discountId' => $discountId]), ['responsible_person' => $this->admin->id])
            ->assertOk();

        $o->refresh();
        $this->assertEquals(1097.50, (float) $o->grand_total, 'pricing restored');
        $this->assertEquals(97.50, (float) $o->tax_amount);
        $this->assertEquals(1000.00, CustomerCreditService::remainingBalance($c->id), 'balance restored');
        $this->assertEquals(ProductDiscount::STATUS_REVERSED, ProductDiscount::find($discountId)->status);
    }
}
