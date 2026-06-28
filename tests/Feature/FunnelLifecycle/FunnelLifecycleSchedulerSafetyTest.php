<?php

namespace Tests\Feature\FunnelLifecycle;

use App\Jobs\SalesFunnelAfterEventJob;
use App\Jobs\SalesFunnelBeforeEventJob;
use App\Models\Configurations\Setting;
use App\Models\Customers\Customer;
use App\Models\Customers\SalesFunnel;
use App\Models\Customers\SalesFunnelSteps;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Models\Orders\OrderProductFunnelLog;
use App\Models\ProductManagement\Product;
use App\Services\TwilioService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Scenarios 9–11: Scheduler safety — race conditions and soft-deleted guard.
 *
 * 9.  Scheduler running during deletion → no SMS for deleted order
 * 10. Scheduler running during delivery completion → no delivery reminder for completed delivery
 * 11. Scheduler running during cancellation → no SMS for cancelled order
 *
 * The safety mechanism is two-fold:
 *   A. The base query includes `whereHas('order', fn => whereNull('deleted_at'))`
 *   B. The per-item loop checks `$op->order->deleted_at !== null` (race-condition guard)
 */
class FunnelLifecycleSchedulerSafetyTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private User     $user;
    private Product  $product;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('app.sales_funnel_flag', true);

        $this->customer = Customer::create([
            'first_name' => 'Scheduler',
            'last_name'  => 'Safety',
            'email'      => 'lifecycle-scheduler@example.com',
            'status'     => 'Active',
        ]);

        $this->user = User::create([
            'first_name' => 'Admin',
            'last_name'  => 'User',
            'email'      => 'admin-lifecycle-scheduler@example.com',
            'password'   => bcrypt('password'),
        ]);

        $this->product = Product::create([
            'product_name' => 'Telehandler',
            'slug'         => 'telehandler-lifecycle-scheduler',
        ]);

        Setting::firstOrCreate(
            ['setting_name' => 'twilio_timezone'],
            ['setting_value' => 'America/Chicago']
        );

        // TwilioService constructs itself by reading these encrypted settings.
        // We seed fake values so construction succeeds; no actual Twilio calls happen
        // in these tests because either (a) no products pass the query filter or
        // (b) 'Stopped' logs block the send before sendSms() is reached.
        // TwilioService does Crypt::decryptString($settings['twilio_xxx']).
        // Store pre-encrypted values with is_encrypted=0 to bypass the Setting mutator
        // (which would double-encrypt) and so getSetting() returns the already-encrypted string.
        $encrypted = \Illuminate\Support\Facades\Crypt::encryptString('FAKE_TEST_VALUE');
        foreach (['twilio_sid', 'twilio_auth_token', 'twilio_from_number', 'twilio_messaging_service_sid'] as $key) {
            Setting::firstOrCreate(
                ['setting_name' => $key],
                ['setting_type' => 'Communication Settings', 'setting_value' => $encrypted, 'is_encrypted' => 0]
            );
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Create a before-event funnel with one step timed to fire NOW
     * (offset puts delivery window inside the current 15-min scheduler window).
     */
    private function makeBeforeFunnelFiringNow(): SalesFunnel
    {
        $funnel = SalesFunnel::create([
            'funnel_name'       => 'Before-Event Safety Test',
            'trigger_type'      => 'rental_schedule',
            'status'            => 'Active',
            'funnel_order_type' => 'all',
        ]);

        $funnel->products()->attach($this->product->id);

        $now = Carbon::now('America/Chicago');

        // offset = time until delivery from now (just over 1 minute = step fires this window)
        SalesFunnelSteps::create([
            'sales_funnel_id'       => $funnel->id,
            'step_type'             => 'SMS',
            'timing_reference_type' => 'rental_delivery_datetime',
            'offset_direction'      => 'before',
            'offset_minutes'        => 2,
            'message'               => 'Your delivery is almost here!',
            'sort_order'            => 1,
            'is_active'             => true,
        ]);

        return $funnel->fresh(['steps']);
    }

    /**
     * Create an OrderProduct whose delivery falls within the current scheduler window
     * (delivery = now + offset_minutes of the funnel step).
     */
    private function makeOrderProductInWindow(int $offsetMinutes = 2): array
    {
        $order = Order::create([
            'order_number'    => 'ORD-LC-SCHED-' . uniqid(),
            'order_date'      => now()->toDateString(),
            'customer_id'     => $this->customer->id,
            'customer_name'   => 'Scheduler Safety Test',
            'created_by_id'   => $this->user->id,
            'created_by_type' => User::class,
            'updated_by_id'   => $this->user->id,
            'updated_by_type' => User::class,
        ]);

        // Shipping address with phone so the job can proceed past the phone check
        // Use customer_phone on the order so the scheduler phone check passes
        // without needing a full order_address row (which has many required fields)
        $order->update(['customer_phone' => '+15551234567']);

        $deliveryAt = Carbon::now('America/Chicago')->addMinutes($offsetMinutes);

        $op = OrderProduct::create([
            'order_id'        => $order->id,
            'product_id'      => $this->product->id,
            'product_name'    => 'Telehandler',
            'price'           => 600.00,
            'quantity'        => 1,
            'total'           => 600.00,
            'delivery_date'   => $deliveryAt->toDateString(),
            'delivery_time'   => $deliveryAt->format('H:i:s'),
            'delivery_status' => 'Pending',
        ]);

        return [$order, $op];
    }

    // ── Scenario 9: Scheduler runs while order is being deleted ──────────────

    public function test_scheduler_does_not_send_sms_for_soft_deleted_order(): void
    {
        $this->makeBeforeFunnelFiringNow();
        [$order, $op] = $this->makeOrderProductInWindow();

        // Soft-delete the order (simulates deletion during scheduler run)
        $order->delete();

        // The scheduler query must return zero products because the order is deleted
        $twilio = $this->createMock(TwilioService::class);
        $twilio->expects($this->never())->method('sendSms');

        // Resolve the mocked TwilioService in the container for the job
        $this->app->instance(TwilioService::class, $twilio);

        // The job itself constructs TwilioService directly (new TwilioService()) so we
        // verify the result indirectly: no Sent funnel log should exist after the job runs
        // (Stopped entries were created by the delete hook, which blocks the whereDoesntHave check)
        (new SalesFunnelBeforeEventJob())->handle();

        $this->assertEquals(0, OrderProductFunnelLog::where('status', 'Sent')->count());
    }

    public function test_before_job_skips_deleted_order_even_if_it_slips_into_chunk(): void
    {
        $this->makeBeforeFunnelFiringNow();
        [$order, $op] = $this->makeOrderProductInWindow();

        // Simulate race condition: the order is deleted AFTER the query but BEFORE chunk processing.
        // We replicate this by soft-deleting the order directly on the model without triggering
        // the full lifecycle (to test the per-item guard, not the query guard).
        Order::withoutEvents(function () use ($order) {
            $order->update(['deleted_at' => now()]);
        });

        // The per-item guard ($op->order->deleted_at !== null) must catch this
        (new SalesFunnelBeforeEventJob())->handle();

        $this->assertEquals(0, OrderProductFunnelLog::where('status', 'Sent')->count());
    }

    // ── Scenario 10: Scheduler runs during delivery completion ────────────────

    public function test_before_job_does_not_send_delivery_reminder_after_delivery_completed(): void
    {
        $funnel = $this->makeBeforeFunnelFiringNow();
        [$order, $op] = $this->makeOrderProductInWindow();

        // Delivery is marked complete BEFORE the scheduler runs
        $op->update(['delivery_status' => 'Completed']);

        // The lifecycle service created a 'Stopped' log for the step.
        // The scheduler's whereDoesntHave check will find this and skip.
        (new SalesFunnelBeforeEventJob())->handle();

        $this->assertEquals(0, OrderProductFunnelLog::where('status', 'Sent')->count());
        $this->assertEquals(1, OrderProductFunnelLog::where('status', 'Stopped')->count());
    }

    // ── Scenario 11: Scheduler runs during cancellation ───────────────────────

    public function test_before_job_does_not_send_sms_after_order_stops_are_created(): void
    {
        $funnel = $this->makeBeforeFunnelFiringNow();
        [$order, $op] = $this->makeOrderProductInWindow();

        // Simulate order cancellation by directly calling the service
        // (same call that would happen from an order-cancelled event or controller)
        \App\Services\FunnelLifecycleService::stopFunnelsForOrder(
            $order,
            \App\Services\FunnelLifecycleService::REASON_ORDER_CANCELLED
        );

        // Scheduler runs — the 'Stopped' log blocks this step
        (new SalesFunnelBeforeEventJob())->handle();

        $this->assertEquals(0, OrderProductFunnelLog::where('status', 'Sent')->count());
        $this->assertEquals(1, OrderProductFunnelLog::where('status', 'Stopped')->count());
    }

    public function test_after_job_does_not_send_for_soft_deleted_order(): void
    {
        $funnel = SalesFunnel::create([
            'funnel_name'       => 'After-Event Safety Test',
            'trigger_type'      => 'rental_schedule',
            'status'            => 'Active',
            'funnel_order_type' => 'all',
        ]);

        $funnel->products()->attach($this->product->id);

        // Step fires 2 minutes after delivery
        SalesFunnelSteps::create([
            'sales_funnel_id'       => $funnel->id,
            'step_type'             => 'SMS',
            'timing_reference_type' => 'rental_delivery_datetime',
            'offset_direction'      => 'after',
            'offset_minutes'        => 2,
            'message'               => 'How was your rental?',
            'sort_order'            => 1,
            'is_active'             => true,
        ]);

        $now = Carbon::now('America/Chicago');

        $order = Order::create([
            'order_number'    => 'ORD-LC-AFTER-' . uniqid(),
            'order_date'      => now()->toDateString(),
            'customer_id'     => $this->customer->id,
            'customer_name'   => 'Scheduler Safety Test',
            'created_by_id'   => $this->user->id,
            'created_by_type' => User::class,
            'updated_by_id'   => $this->user->id,
            'updated_by_type' => User::class,
        ]);

        $order->update(['customer_phone' => '+15551234567']);

        // Delivery was 2 minutes ago so after-event fires now
        $deliveryAt = $now->copy()->subMinutes(2);

        $op = OrderProduct::create([
            'order_id'        => $order->id,
            'product_id'      => $this->product->id,
            'product_name'    => 'Telehandler',
            'price'           => 600.00,
            'quantity'        => 1,
            'total'           => 600.00,
            'delivery_date'   => $deliveryAt->toDateString(),
            'delivery_time'   => $deliveryAt->format('H:i:s'),
            'delivery_status' => 'Pending',
        ]);

        // Delete the order
        $order->delete();

        (new SalesFunnelAfterEventJob())->handle();

        $this->assertEquals(0, OrderProductFunnelLog::where('status', 'Sent')->count());
    }

    // ── Funnel disabled flag ──────────────────────────────────────────────────

    public function test_job_exits_early_when_sales_funnel_flag_is_disabled(): void
    {
        Config::set('app.sales_funnel_flag', false);

        $this->makeBeforeFunnelFiringNow();
        $this->makeOrderProductInWindow();

        (new SalesFunnelBeforeEventJob())->handle();

        $this->assertEquals(0, OrderProductFunnelLog::count());
    }
}
