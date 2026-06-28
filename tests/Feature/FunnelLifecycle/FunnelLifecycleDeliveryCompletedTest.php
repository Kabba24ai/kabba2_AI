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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Scenarios 6, 7, 12: Delivery completion stops delivery reminders immediately.
 *
 * 6. Delivery completed on schedule    → before-event steps stopped
 * 7. Delivery completed EARLY          → before-event steps stopped, reason = 'Delivery Completed Early'
 * 12. Verify no delivery reminder sends after equipment is already delivered
 *
 * Business rule: ONLY before-event funnel steps are stopped.
 * After-event funnels (review requests, return reminders) are NOT affected.
 */
class FunnelLifecycleDeliveryCompletedTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private User     $user;
    private Product  $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'Delivery',
            'last_name'  => 'Test',
            'email'      => 'lifecycle-delivery@example.com',
            'status'     => 'Active',
        ]);

        $this->user = User::create([
            'first_name' => 'Admin',
            'last_name'  => 'User',
            'email'      => 'admin-lifecycle-delivery@example.com',
            'password'   => bcrypt('password'),
        ]);

        $this->product = Product::create([
            'product_name' => 'Boom Lift',
            'slug'         => 'boom-lift-lifecycle-delivery',
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeBeforeFunnel(int $steps = 2): SalesFunnel
    {
        $funnel = SalesFunnel::create([
            'funnel_name'       => 'Before-Event Delivery Reminder',
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
                'offset_direction'      => 'before',
                'offset_minutes'        => $i * 60,
                'message'               => "Reminder {$i}: Your delivery is coming up",
                'sort_order'            => $i,
                'is_active'             => true,
            ]);
        }

        return $funnel->fresh(['steps']);
    }

    private function makeAfterFunnel(int $steps = 1): SalesFunnel
    {
        $funnel = SalesFunnel::create([
            'funnel_name'       => 'After-Event Review Request',
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
                'offset_direction'      => 'after',
                'offset_minutes'        => $i * 60 * 24,
                'message'               => "How was your rental experience?",
                'sort_order'            => $i,
                'is_active'             => true,
            ]);
        }

        return $funnel->fresh(['steps']);
    }

    private function makeOrderProduct(string $deliveryDate, string $deliveryTime = '09:00:00'): array
    {
        $order = Order::create([
            'order_number'    => 'ORD-LC-DEL-' . uniqid(),
            'order_date'      => now()->toDateString(),
            'customer_id'     => $this->customer->id,
            'customer_name'   => 'Delivery Test',
            'created_by_id'   => $this->user->id,
            'created_by_type' => User::class,
            'updated_by_id'   => $this->user->id,
            'updated_by_type' => User::class,
        ]);

        $op = OrderProduct::create([
            'order_id'        => $order->id,
            'product_id'      => $this->product->id,
            'product_name'    => 'Boom Lift',
            'price'           => 400.00,
            'quantity'        => 1,
            'total'           => 400.00,
            'delivery_date'   => $deliveryDate,
            'delivery_time'   => $deliveryTime,
            'delivery_status' => 'Pending',
        ]);

        return [$order, $op];
    }

    // ── Scenario 6: Delivery completed on schedule ────────────────────────────

    public function test_delivery_completed_on_schedule_stops_before_event_steps(): void
    {
        $beforeFunnel = $this->makeBeforeFunnel(2);
        [, $op] = $this->makeOrderProduct(now()->toDateString(), now()->addHour()->format('H:i:s'));

        // Mark delivery completed at the scheduled time
        $op->update(['delivery_status' => 'Completed']);

        $this->assertEquals(2, OrderProductFunnelLog::where('status', 'Stopped')->count());
    }

    public function test_delivery_completed_on_schedule_has_reason_delivery_completed(): void
    {
        $beforeFunnel = $this->makeBeforeFunnel(1);

        // Original delivery_date is in the past → now()->lt($scheduled) is false → on-schedule reason
        [, $op] = $this->makeOrderProduct(now()->toDateString(), now()->subHour()->format('H:i:s'));

        $op->update(['delivery_status' => 'Completed']);

        $log = OrderProductFunnelLog::where('status', 'Stopped')->first();
        $this->assertEquals(FunnelLifecycleService::REASON_DELIVERY_COMPLETED, $log->lifecycle_reason);
    }

    // ── Scenario 7: Delivery completed EARLY ──────────────────────────────────

    public function test_early_delivery_stops_before_event_steps_with_early_reason(): void
    {
        $beforeFunnel = $this->makeBeforeFunnel(2);

        // Schedule delivery for tomorrow at 9am, but mark complete now (early)
        $tomorrow = Carbon::tomorrow()->toDateString();
        [, $op] = $this->makeOrderProduct($tomorrow, '09:00:00');

        $op->update(['delivery_status' => 'Completed']);

        $logs = OrderProductFunnelLog::where('status', 'Stopped')->get();
        $this->assertCount(2, $logs);
        $this->assertEquals(FunnelLifecycleService::REASON_DELIVERY_COMPLETED_EARLY, $logs->first()->lifecycle_reason);
    }

    // ── Scenario 12: No delivery reminder SMS after delivery ──────────────────

    public function test_before_event_steps_blocked_after_delivery_completed(): void
    {
        $beforeFunnel = $this->makeBeforeFunnel(3);
        [, $op] = $this->makeOrderProduct(Carbon::tomorrow()->toDateString(), '09:00:00');

        // Mark delivery completed
        $op->update(['delivery_status' => 'Completed']);

        // The 'Stopped' entries now exist for all 3 before-event steps.
        // The scheduler's whereDoesntHave('funnelLogs', ...) check will find these and skip.
        // Verify the stopped logs block each step.
        foreach ($beforeFunnel->steps as $step) {
            $this->assertDatabaseHas('order_product_funnel_logs', [
                'order_product_id'     => $op->id,
                'sales_funnel_id'      => $beforeFunnel->id,
                'sales_funnel_step_id' => $step->id,
                'status'               => 'Stopped',
            ]);
        }
    }

    // ── After-event funnels are NOT stopped by delivery completion ────────────

    public function test_after_event_funnel_steps_are_not_stopped_when_delivery_completes(): void
    {
        $beforeFunnel = $this->makeBeforeFunnel(1);
        $afterFunnel  = $this->makeAfterFunnel(1);
        [, $op] = $this->makeOrderProduct(Carbon::tomorrow()->toDateString(), '09:00:00');

        $op->update(['delivery_status' => 'Completed']);

        // Only the before-event step is stopped
        $this->assertEquals(1, OrderProductFunnelLog::where('status', 'Stopped')->count());

        // The after-event step has NO log entry (it's still eligible to fire in the future)
        $afterStep = $afterFunnel->steps->first();
        $this->assertDatabaseMissing('order_product_funnel_logs', [
            'sales_funnel_id'      => $afterFunnel->id,
            'sales_funnel_step_id' => $afterStep->id,
        ]);
    }

    public function test_multiple_products_only_stops_the_delivered_product(): void
    {
        $funnel = $this->makeBeforeFunnel(1);

        $product2 = Product::create([
            'product_name' => 'Forklift',
            'slug'         => 'forklift-lifecycle-delivery',
        ]);
        $funnel->products()->attach($product2->id);

        $order = Order::create([
            'order_number'    => 'ORD-LC-MULTI-' . uniqid(),
            'order_date'      => now()->toDateString(),
            'customer_id'     => $this->customer->id,
            'customer_name'   => 'Delivery Test',
            'created_by_id'   => $this->user->id,
            'created_by_type' => User::class,
            'updated_by_id'   => $this->user->id,
            'updated_by_type' => User::class,
        ]);

        $op1 = OrderProduct::create([
            'order_id'        => $order->id,
            'product_id'      => $this->product->id,
            'product_name'    => 'Boom Lift',
            'price'           => 400.00,
            'quantity'        => 1,
            'total'           => 400.00,
            'delivery_date'   => Carbon::tomorrow()->toDateString(),
            'delivery_time'   => '09:00:00',
            'delivery_status' => 'Pending',
        ]);

        $op2 = OrderProduct::create([
            'order_id'        => $order->id,
            'product_id'      => $product2->id,
            'product_name'    => 'Forklift',
            'price'           => 500.00,
            'quantity'        => 1,
            'total'           => 500.00,
            'delivery_date'   => Carbon::tomorrow()->toDateString(),
            'delivery_time'   => '10:00:00',
            'delivery_status' => 'Pending',
        ]);

        // Only deliver the first product
        $op1->update(['delivery_status' => 'Completed']);

        // Only op1's step is stopped; op2 is untouched
        $this->assertEquals(1, OrderProductFunnelLog::where('status', 'Stopped')->count());
        $this->assertDatabaseHas('order_product_funnel_logs', ['order_product_id' => $op1->id]);
        $this->assertDatabaseMissing('order_product_funnel_logs', ['order_product_id' => $op2->id]);
    }

    // ── Already-sent step is not re-stopped ───────────────────────────────────

    public function test_sent_step_is_not_overwritten_by_delivery_completion(): void
    {
        $funnel = $this->makeBeforeFunnel(2);
        [, $op] = $this->makeOrderProduct(Carbon::tomorrow()->toDateString());

        $step1 = $funnel->steps->first();

        // Step 1 was already sent
        OrderProductFunnelLog::create([
            'order_product_id'     => $op->id,
            'order_id'             => $op->order_id,
            'customer_id'          => $this->customer->id,
            'sales_funnel_id'      => $funnel->id,
            'sales_funnel_step_id' => $step1->id,
            'step_type'            => 'SMS',
            'product_id'           => $this->product->id,
            'status'               => 'Sent',
            'sent_at'              => now()->subHour(),
        ]);

        $op->update(['delivery_status' => 'Completed']);

        // Step 1 remains Sent; only step 2 is Stopped
        $this->assertEquals(1, OrderProductFunnelLog::where('status', 'Sent')->count());
        $this->assertEquals(1, OrderProductFunnelLog::where('status', 'Stopped')->count());
        $this->assertEquals(2, OrderProductFunnelLog::count());
    }
}
