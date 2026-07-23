<?php

namespace Tests\Feature\Tasks;

use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Models\ProductManagement\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The New Task modal's dependent "Customer Order" dropdown is fed by
 * ChargeModalLookupController@customerOrders. It must return ONLY the given
 * customer's orders, each labelled with its first product + an extra-count,
 * ordered active/open first then newest.
 */
class TaskCustomerOrderLookupTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'first_name' => 'Task', 'last_name' => 'Admin',
            'email' => 'task-order-admin@test.local', 'status' => 'Active',
        ]);
        $this->product = Product::create([
            'product_name' => 'Mini Excavator', 'slug' => 'mini-exc-' . uniqid(), 'product_type' => 'Rental',
        ]);

        $this->actingAs($this->admin);
    }

    private function customer(string $first): Customer
    {
        return Customer::create([
            'first_name' => $first, 'last_name' => 'Tester',
            'phone' => '555-0100', 'email' => strtolower($first) . '-' . uniqid() . '@test.local',
            'status' => 'Active',
        ]);
    }

    private function order(Customer $customer, string $date): Order
    {
        static $n = 3150;
        $n++;

        return Order::create([
            'order_number'  => (string) $n,
            'order_date'    => $date,
            'customer_id'   => $customer->id,
            'customer_name' => $customer->full_name,
            'grand_total'   => 100,
        ]);
    }

    private function addProduct(Order $order, string $name): void
    {
        OrderProduct::create([
            'order_id'                => $order->id,
            'product_id'              => $this->product->id,
            'product_name'            => $name,
            'price' => 100, 'quantity' => 1, 'total' => 100,
            'delivery_time'           => '09:00',
            'delivery_status'         => 'Pending',
            'pickup_status'           => 'Pending',
            'delivery_transport_mode' => 'Truck',
            'pickup_transport_mode'   => 'Truck',
            'product_data'            => ['product_type' => 'Rental', 'product_variant' => 'daily'],
        ]);
    }

    private function fetch(int $customerId)
    {
        return $this->getJson(route('admin.dashboard.charge-modal.customer-orders', ['customer_id' => $customerId]));
    }

    public function test_returns_only_the_given_customers_orders(): void
    {
        $a = $this->customer('Alice');
        $b = $this->customer('Bob');

        $oa = $this->order($a, now()->format('Y-m-d'));
        $this->addProduct($oa, 'Mini Excavator');
        $ob = $this->order($b, now()->format('Y-m-d'));
        $this->addProduct($ob, 'Boom Lift');

        $res = $this->fetch($a->id)->assertOk();

        $res->assertJsonCount(1, 'results');
        $res->assertJsonPath('results.0.order_number', $oa->order_number);
        $res->assertJsonPath('results.0.customer_id', $a->id);
    }

    public function test_row_carries_first_product_and_extra_count(): void
    {
        $c = $this->customer('Carol');
        $o = $this->order($c, now()->format('Y-m-d'));
        $this->addProduct($o, 'Mini Excavator');   // first
        $this->addProduct($o, "45' Boom Lift");
        $this->addProduct($o, 'Stump Grinder');

        $res = $this->fetch($c->id)->assertOk();

        $res->assertJsonPath('results.0.first_product', 'Mini Excavator');
        $res->assertJsonPath('results.0.extra_count', 2);
        // Order date rides along as secondary info.
        $this->assertNotEmpty($res->json('results.0.order_date'));
    }

    public function test_newest_order_date_first(): void
    {
        $c = $this->customer('Erin');
        $older = $this->order($c, now()->subDays(10)->format('Y-m-d'));
        $this->addProduct($older, 'Old');
        $newer = $this->order($c, now()->format('Y-m-d'));
        $this->addProduct($newer, 'New');

        $res = $this->fetch($c->id)->assertOk();

        $this->assertEquals($newer->order_number, $res->json('results.0.order_number'));
        $this->assertEquals($older->order_number, $res->json('results.1.order_number'));
    }

    public function test_missing_or_invalid_customer_returns_empty(): void
    {
        $this->getJson(route('admin.dashboard.charge-modal.customer-orders'))
            ->assertOk()->assertJsonCount(0, 'results');

        $this->fetch(0)->assertOk()->assertJsonCount(0, 'results');
        $this->fetch(999999)->assertOk()->assertJsonCount(0, 'results');
    }

    public function test_soft_deleted_orders_are_excluded(): void
    {
        $c = $this->customer('Frank');
        $o = $this->order($c, now()->format('Y-m-d'));
        $this->addProduct($o, 'Doomed');

        $this->fetch($c->id)->assertOk()->assertJsonCount(1, 'results');

        $o->delete();

        $this->fetch($c->id)->assertOk()->assertJsonCount(0, 'results');
    }
}
