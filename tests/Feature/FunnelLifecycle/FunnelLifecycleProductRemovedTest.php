<?php

namespace Tests\Feature\FunnelLifecycle;

use App\Models\Customers\Customer;
use App\Models\Customers\SalesFunnel;
use App\Models\Customers\SalesFunnelSteps;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Models\Orders\OrderProductFunnelLog;
use App\Models\ProductManagement\Product;
use App\Services\FunnelLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Scenario 5: Order cancelled
 * Scenario 8: Product removed from order
 * Scenario 13: Communication history is permanently preserved
 * Scenario 14: Stopped records are created correctly
 */
class FunnelLifecycleProductRemovedTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private User     $user;
    private Product  $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'Product',
            'last_name'  => 'Removed',
            'email'      => 'lifecycle-product-removed@example.com',
            'status'     => 'Active',
        ]);

        $this->user = User::create([
            'first_name' => 'Admin',
            'last_name'  => 'User',
            'email'      => 'admin-lifecycle-product@example.com',
            'password'   => bcrypt('password'),
        ]);

        $this->product = Product::create([
            'product_name' => 'Excavator',
            'slug'         => 'excavator-lifecycle-product',
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeFunnel(string $direction = 'before', int $steps = 2): SalesFunnel
    {
        $funnel = SalesFunnel::create([
            'funnel_name'       => "Test Funnel ({$direction})",
            'trigger_type'      => 'rental_schedule',
            'status'            => 'Active',
            'funnel_order_type' => 'all',
        ]);

        $funnel->products()->attach($this->product->id);

        for ($i = 1; $i <= $steps; $i++) {
            SalesFunnelSteps::create([
                'sales_funnel_id'       => $funnel->id,
                'step_type'             => 'SMS',
                'timing_reference_type' => 'rental_delivery_datetime',
                'offset_direction'      => $direction,
                'offset_minutes'        => $i * 60,
                'message'               => "Step {$i} message",
                'sort_order'            => $i,
                'is_active'             => true,
            ]);
        }

        return $funnel->fresh(['steps']);
    }

    private function makeOrderWithProducts(int $count = 1): array
    {
        $order = Order::create([
            'order_number'    => 'ORD-LC-PR-' . uniqid(),
            'order_date'      => now()->toDateString(),
            'customer_id'     => $this->customer->id,
            'customer_name'   => 'Product Removed Test',
            'created_by_id'   => $this->user->id,
            'created_by_type' => User::class,
            'updated_by_id'   => $this->user->id,
            'updated_by_type' => User::class,
        ]);

        $ops = [];
        for ($i = 0; $i < $count; $i++) {
            $ops[] = OrderProduct::create([
                'order_id'        => $order->id,
                'product_id'      => $this->product->id,
                'product_name'    => 'Excavator',
                'price'           => 500.00,
                'quantity'        => 1,
                'total'           => 500.00,
                'delivery_date'   => now()->addDays(3)->toDateString(),
                'delivery_time'   => '08:00:00',
                'delivery_status' => 'Pending',
            ]);
        }

        return array_merge([$order], $ops);
    }

    // ── Scenario 8: Product removed from order ────────────────────────────────

    public function test_removing_product_stops_its_funnel_steps(): void
    {
        $funnel = $this->makeFunnel('before', 2);
        [$order, $op] = $this->makeOrderWithProducts(1);

        $op->delete(); // individual product removal

        $this->assertEquals(2, OrderProductFunnelLog::where('status', 'Stopped')->count());
    }

    public function test_removing_product_sets_reason_product_removed(): void
    {
        $funnel = $this->makeFunnel('before', 1);
        [$order, $op] = $this->makeOrderWithProducts(1);

        $op->delete();

        $log = OrderProductFunnelLog::first();
        $this->assertEquals('Stopped', $log->status);
        $this->assertEquals(FunnelLifecycleService::REASON_PRODUCT_REMOVED, $log->lifecycle_reason);
    }

    public function test_removing_one_product_does_not_affect_sibling_product(): void
    {
        $funnel  = $this->makeFunnel('before', 1);
        $product2 = Product::create(['product_name' => 'Forklift 2', 'slug' => 'forklift-2-lifecycle']);
        $funnel->products()->attach($product2->id);

        $order = Order::create([
            'order_number'    => 'ORD-LC-SIBLING-' . uniqid(),
            'order_date'      => now()->toDateString(),
            'customer_id'     => $this->customer->id,
            'customer_name'   => 'Product Removed Test',
            'created_by_id'   => $this->user->id,
            'created_by_type' => User::class,
            'updated_by_id'   => $this->user->id,
            'updated_by_type' => User::class,
        ]);

        $op1 = OrderProduct::create([
            'order_id'        => $order->id,
            'product_id'      => $this->product->id,
            'product_name'    => 'Excavator',
            'price'           => 500.00,
            'quantity'        => 1,
            'total'           => 500.00,
            'delivery_date'   => now()->addDays(3)->toDateString(),
            'delivery_time'   => '08:00:00',
            'delivery_status' => 'Pending',
        ]);

        $op2 = OrderProduct::create([
            'order_id'        => $order->id,
            'product_id'      => $product2->id,
            'product_name'    => 'Forklift 2',
            'price'           => 600.00,
            'quantity'        => 1,
            'total'           => 600.00,
            'delivery_date'   => now()->addDays(3)->toDateString(),
            'delivery_time'   => '08:00:00',
            'delivery_status' => 'Pending',
        ]);

        // Remove only op1
        $op1->delete();

        // Only op1 gets a Stopped entry; op2 is untouched
        $this->assertEquals(1, OrderProductFunnelLog::count());
        $this->assertDatabaseHas('order_product_funnel_logs', ['order_product_id' => $op1->id, 'status' => 'Stopped']);
        $this->assertDatabaseMissing('order_product_funnel_logs', ['order_product_id' => $op2->id]);
    }

    // ── Scenario 5: Order Cancelled ───────────────────────────────────────────

    public function test_manually_cancelling_order_via_service_stops_all_funnels(): void
    {
        $funnel = $this->makeFunnel('before', 2);
        [$order, $op] = $this->makeOrderWithProducts(1);

        // Order cancellation is currently expressed through a direct service call
        // (an order_status field or event would route here in production)
        FunnelLifecycleService::stopFunnelsForOrder($order, FunnelLifecycleService::REASON_ORDER_CANCELLED);

        $this->assertEquals(2, OrderProductFunnelLog::where('status', 'Stopped')->count());
        $this->assertEquals(
            FunnelLifecycleService::REASON_ORDER_CANCELLED,
            OrderProductFunnelLog::first()->lifecycle_reason
        );
    }

    // ── Scenario 13: Communication history permanently preserved ─────────────

    public function test_communication_history_preserved_after_product_soft_delete(): void
    {
        $funnel = $this->makeFunnel('before', 2);
        [$order, $op] = $this->makeOrderWithProducts(1);

        $step1 = $funnel->steps->first();

        // A message was already sent
        OrderProductFunnelLog::create([
            'order_product_id'     => $op->id,
            'order_id'             => $order->id,
            'customer_id'          => $this->customer->id,
            'sales_funnel_id'      => $funnel->id,
            'sales_funnel_step_id' => $step1->id,
            'step_type'            => 'SMS',
            'product_id'           => $this->product->id,
            'status'               => 'Sent',
            'sent_at'              => now()->subHour(),
        ]);

        // Remove the product
        $op->delete();

        // History: 1 Sent (preserved) + 1 Stopped (new) = 2 total
        $this->assertEquals(2, OrderProductFunnelLog::count());
        $this->assertDatabaseHas('order_product_funnel_logs', ['status' => 'Sent']);
        $this->assertDatabaseHas('order_product_funnel_logs', ['status' => 'Stopped']);
    }

    public function test_communication_history_preserved_after_order_deleted(): void
    {
        $funnel = $this->makeFunnel('before', 3);
        [$order, $op] = $this->makeOrderWithProducts(1);

        $steps = $funnel->steps;

        // Two steps were already sent
        foreach ([$steps[0], $steps[1]] as $step) {
            OrderProductFunnelLog::create([
                'order_product_id'     => $op->id,
                'order_id'             => $order->id,
                'customer_id'          => $this->customer->id,
                'sales_funnel_id'      => $funnel->id,
                'sales_funnel_step_id' => $step->id,
                'step_type'            => 'SMS',
                'product_id'           => $this->product->id,
                'status'               => 'Sent',
                'sent_at'              => now()->subHours(2),
            ]);
        }

        $order->delete();

        // 2 Sent + 1 Stopped = 3 total
        $this->assertEquals(3, OrderProductFunnelLog::count());
        $this->assertEquals(2, OrderProductFunnelLog::where('status', 'Sent')->count());
        $this->assertEquals(1, OrderProductFunnelLog::where('status', 'Stopped')->count());
    }

    // ── Scenario 14: Stopped records have complete audit fields ───────────────

    public function test_stopped_records_have_all_required_audit_fields(): void
    {
        $funnel = $this->makeFunnel('before', 1);
        [$order, $op] = $this->makeOrderWithProducts(1);

        $order->delete();

        $log = OrderProductFunnelLog::where('status', 'Stopped')->first();

        // Core lifecycle fields
        $this->assertNotNull($log->lifecycle_reason);
        $this->assertNotNull($log->stopped_at);

        // History preservation fields
        $this->assertEquals($order->id, $log->order_id);
        $this->assertEquals($this->customer->id, $log->customer_id);
        $this->assertEquals($this->product->id, $log->product_id);

        // Funnel traceability
        $this->assertEquals($funnel->id, $log->sales_funnel_id);
        $this->assertNotNull($log->sales_funnel_step_id);

        // Notes explaining why
        $this->assertNotNull($log->notes);
        $this->assertStringContainsString('lifecycle event', $log->notes);
    }

    public function test_stopped_records_include_order_id_even_after_product_nulled(): void
    {
        $funnel = $this->makeFunnel('before', 1);
        [$order, $op] = $this->makeOrderWithProducts(1);

        // Delete the order (which soft-deletes the product)
        $order->delete();

        $log = OrderProductFunnelLog::where('status', 'Stopped')->first();

        // order_id must be preserved on the log regardless of product FK state
        $this->assertEquals($order->id, $log->order_id);
        $this->assertEquals($this->customer->id, $log->customer_id);
    }
}
