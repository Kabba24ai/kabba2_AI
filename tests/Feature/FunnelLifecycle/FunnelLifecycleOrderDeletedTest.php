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
 * Scenarios 1–4: Order deletion immediately stops future CRM Funnel SMS.
 *
 * 1. POD deleted before first SMS → Stopped entries created, history preserved
 * 2. POD deleted after first SMS  → Remaining steps stopped, sent step preserved
 * 3. Paid order deleted before first SMS
 * 4. Paid order deleted after some SMS
 */
class FunnelLifecycleOrderDeletedTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private User     $user;
    private Product  $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'Lifecycle',
            'last_name'  => 'Test',
            'email'      => 'lifecycle-delete@example.com',
            'status'     => 'Active',
        ]);

        $this->user = User::create([
            'first_name' => 'Admin',
            'last_name'  => 'User',
            'email'      => 'admin-lifecycle-delete@example.com',
            'password'   => bcrypt('password'),
        ]);

        $this->product = Product::create([
            'product_name' => 'Skid Steer',
            'slug'         => 'skid-steer-lifecycle-delete',
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeFunnel(string $orderType = 'all', string $direction = 'before', int $steps = 2): SalesFunnel
    {
        $funnel = SalesFunnel::create([
            'funnel_name'       => "Test Funnel ({$orderType}/{$direction})",
            'trigger_type'      => 'rental_schedule',
            'status'            => 'Active',
            'funnel_order_type' => $orderType,
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

    private function makeOrderWithProduct(): array
    {
        $order = Order::create([
            'order_number'    => 'ORD-LC-DELETE-' . uniqid(),
            'order_date'      => now()->toDateString(),
            'customer_id'     => $this->customer->id,
            'customer_name'   => 'Lifecycle Test',
            'created_by_id'   => $this->user->id,
            'created_by_type' => User::class,
            'updated_by_id'   => $this->user->id,
            'updated_by_type' => User::class,
        ]);

        $op = OrderProduct::create([
            'order_id'       => $order->id,
            'product_id'     => $this->product->id,
            'product_name'   => 'Skid Steer',
            'price'          => 300.00,
            'quantity'       => 1,
            'total'          => 300.00,
            'delivery_date'  => now()->addDays(2)->toDateString(),
            'delivery_time'  => '09:00:00',
            'delivery_status'=> 'Pending',
        ]);

        return [$order, $op];
    }

    // ── Scenario 1: POD deleted BEFORE first SMS ──────────────────────────────

    public function test_deleting_order_before_any_sms_creates_stopped_entries(): void
    {
        $funnel = $this->makeFunnel('cod', 'before', 2);
        [$order, $op] = $this->makeOrderWithProduct();

        // No funnel logs exist yet
        $this->assertEquals(0, OrderProductFunnelLog::count());

        $order->delete();

        // Both unsent steps must be stopped
        $this->assertEquals(2, OrderProductFunnelLog::where('status', 'Stopped')->count());
    }

    public function test_stopped_entries_have_correct_lifecycle_reason_order_deleted(): void
    {
        $funnel = $this->makeFunnel('all', 'before', 1);
        [$order, $op] = $this->makeOrderWithProduct();

        $order->delete();

        $log = OrderProductFunnelLog::first();
        $this->assertEquals('Stopped', $log->status);
        $this->assertEquals(FunnelLifecycleService::REASON_ORDER_DELETED, $log->lifecycle_reason);
        $this->assertNotNull($log->stopped_at);
    }

    public function test_stopped_entries_preserve_order_id_and_customer_id(): void
    {
        $funnel = $this->makeFunnel('all', 'before', 1);
        [$order, $op] = $this->makeOrderWithProduct();

        $order->delete();

        $log = OrderProductFunnelLog::first();
        $this->assertEquals($order->id, $log->order_id);
        $this->assertEquals($this->customer->id, $log->customer_id);
    }

    public function test_deleting_order_stops_all_funnel_types_all_products(): void
    {
        // Two funnels: one before-event, one after-event
        $before = $this->makeFunnel('all', 'before', 2);
        $after  = $this->makeFunnel('paid', 'after', 1);
        [$order, $op] = $this->makeOrderWithProduct();

        $order->delete();

        // 3 steps total across both funnels
        $this->assertEquals(3, OrderProductFunnelLog::where('status', 'Stopped')->count());
    }

    // ── Scenario 2: POD deleted AFTER first SMS already sent ─────────────────

    public function test_deleting_order_after_first_sms_stops_remaining_steps(): void
    {
        $funnel = $this->makeFunnel('cod', 'before', 3);
        [$order, $op] = $this->makeOrderWithProduct();

        $steps = $funnel->steps;

        // Simulate step 1 already sent
        OrderProductFunnelLog::create([
            'order_product_id'     => $op->id,
            'order_id'             => $order->id,
            'customer_id'          => $this->customer->id,
            'sales_funnel_id'      => $funnel->id,
            'sales_funnel_step_id' => $steps[0]->id,
            'step_type'            => 'SMS',
            'product_id'           => $this->product->id,
            'status'               => 'Sent',
            'sent_at'              => now(),
        ]);

        $order->delete();

        // Steps 2 and 3 must be stopped; step 1 (already Sent) unchanged
        $this->assertEquals(1, OrderProductFunnelLog::where('status', 'Sent')->count());
        $this->assertEquals(2, OrderProductFunnelLog::where('status', 'Stopped')->count());
    }

    public function test_sent_log_survives_order_deletion(): void
    {
        $funnel = $this->makeFunnel('all', 'before', 2);
        [$order, $op] = $this->makeOrderWithProduct();

        $step1 = $funnel->steps->first();

        OrderProductFunnelLog::create([
            'order_product_id'     => $op->id,
            'order_id'             => $order->id,
            'customer_id'          => $this->customer->id,
            'sales_funnel_id'      => $funnel->id,
            'sales_funnel_step_id' => $step1->id,
            'step_type'            => 'SMS',
            'product_id'           => $this->product->id,
            'status'               => 'Sent',
            'sent_at'              => now()->subHours(3),
        ]);

        $order->delete();

        // Sent log must not be touched
        $sent = OrderProductFunnelLog::where('status', 'Sent')->first();
        $this->assertNotNull($sent);
        $this->assertNull($sent->lifecycle_reason);
    }

    // ── Scenario 3: Paid order deleted before first SMS ───────────────────────

    public function test_paid_order_deleted_before_any_sms(): void
    {
        $funnel = $this->makeFunnel('paid', 'before', 2);
        [$order, $op] = $this->makeOrderWithProduct();

        $order->delete();

        $this->assertEquals(2, OrderProductFunnelLog::where('status', 'Stopped')->count());
        $this->assertEquals(
            FunnelLifecycleService::REASON_ORDER_DELETED,
            OrderProductFunnelLog::first()->lifecycle_reason
        );
    }

    // ── Scenario 4: Paid order deleted after some SMS sent ────────────────────

    public function test_paid_order_deleted_after_some_sms_sent(): void
    {
        $funnel = $this->makeFunnel('paid', 'before', 3);
        [$order, $op] = $this->makeOrderWithProduct();

        $steps = $funnel->steps;

        // Steps 1 and 2 already sent
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
                'sent_at'              => now()->subHour(),
            ]);
        }

        $order->delete();

        $this->assertEquals(2, OrderProductFunnelLog::where('status', 'Sent')->count());
        $this->assertEquals(1, OrderProductFunnelLog::where('status', 'Stopped')->count());
        $this->assertEquals(3, OrderProductFunnelLog::count());
    }

    // ── Idempotency: double-stop prevention ───────────────────────────────────

    public function test_direct_service_call_is_idempotent(): void
    {
        $funnel = $this->makeFunnel('all', 'before', 2);
        [$order, $op] = $this->makeOrderWithProduct();

        FunnelLifecycleService::stopFunnelsForOrder($order, FunnelLifecycleService::REASON_ORDER_DELETED);
        FunnelLifecycleService::stopFunnelsForOrder($order, FunnelLifecycleService::REASON_ORDER_DELETED);

        // No duplicate entries
        $this->assertEquals(2, OrderProductFunnelLog::count());
    }

    // ── FK preservation: funnel logs survive order product deletion ───────────

    public function test_funnel_logs_are_not_hard_deleted_when_order_product_is_soft_deleted(): void
    {
        // Use 2 steps: step 1 already Sent, step 2 unsent → delete creates 1 Stopped
        $funnel = $this->makeFunnel('all', 'before', 2);
        [$order, $op] = $this->makeOrderWithProduct();

        // Create a Sent log for step 1
        $step1 = $funnel->steps->first();
        OrderProductFunnelLog::create([
            'order_product_id'     => $op->id,
            'order_id'             => $order->id,
            'customer_id'          => $this->customer->id,
            'sales_funnel_id'      => $funnel->id,
            'sales_funnel_step_id' => $step1->id,
            'step_type'            => 'SMS',
            'product_id'           => $this->product->id,
            'status'               => 'Sent',
            'sent_at'              => now(),
        ]);

        $order->delete(); // soft-deletes the order product too; step 2 gets Stopped

        // Both logs must still exist (FK is now SET NULL, not CASCADE on soft-delete)
        $this->assertEquals(2, OrderProductFunnelLog::count()); // 1 Sent + 1 Stopped
        $this->assertDatabaseHas('order_product_funnel_logs', ['status' => 'Sent']);
        $this->assertDatabaseHas('order_product_funnel_logs', ['status' => 'Stopped']);
    }
}
