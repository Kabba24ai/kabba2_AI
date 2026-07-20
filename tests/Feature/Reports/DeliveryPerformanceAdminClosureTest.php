<?php

namespace Tests\Feature\Reports;

use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Models\ProductManagement\Product;
use App\Services\Reports\DeliveryPerformanceEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Delivery Performance driver metrics vs administrative closures.
 *
 * "Close as Completed" (manual admin closure OR the automatic
 * refund/void-before-delivery closure) sets is_delivered/is_returned
 * WITHOUT any physical driver run — so driver throughput must key off the
 * canonical status family, never the booleans alone. These tests pin that
 * behavior: real completions count, administrative closures never do.
 */
class DeliveryPerformanceAdminClosureTest extends TestCase
{
    use RefreshDatabase;

    private User $driver;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->driver = User::create([
            'first_name' => 'Dispatch', 'last_name' => 'Driver',
            'email' => 'perf-driver@test.local', 'status' => 'Active',
            'is_driver' => true,
        ]);

        $this->product = Product::create([
            'product_name' => 'Perf Test Excavator',
            'slug'         => 'perf-test-excavator-' . uniqid(),
            'product_type' => 'Rental',
        ]);
    }

    /** Truck-mode row assigned to the driver, order dated today (in range). */
    private function makeRow(array $overrides = []): OrderProduct
    {
        static $n = 9800;
        $n++;

        $order = Order::create([
            'order_number'  => (string) $n,
            'order_date'    => now()->format('Y-m-d'),
            'customer_name' => 'Perf Customer',
            'grand_total'   => 100,
        ]);

        return OrderProduct::create(array_merge([
            'order_id'                => $order->id,
            'product_id'              => $this->product->id,
            'product_name'            => $this->product->product_name,
            'price'                   => 100,
            'quantity'                => 1,
            'total'                   => 100,
            'delivery_date'           => now()->format('Y-m-d'),
            'delivery_transport_mode' => 'Truck',
            'pickup_transport_mode'   => 'Truck',
            'delivery_by'             => $this->driver->id,
            'pickup_by'               => $this->driver->id,
            'delivery_status'         => 'Pending',
            'pickup_status'           => 'Pending',
            'product_data'            => ['product_type' => 'Rental'],
        ], $overrides));
    }

    private function driverStats(): array
    {
        $data = app(DeliveryPerformanceEngine::class)->reportData([
            'date_range' => 'custom',
            'start_date' => now()->subDay()->toDateString(),
            'end_date'   => now()->addDay()->toDateString(),
        ]);

        foreach ($data['drivers'] as $row) {
            if ($row['id'] === $this->driver->id) {
                return $row;
            }
        }

        $this->fail('Driver missing from the performance report.');
    }

    public function test_real_completed_delivery_and_return_count(): void
    {
        // Physically delivered, then physically returned
        $this->makeRow([
            'delivery_status' => 'Completed', 'is_delivered' => true,
            'pickup_status'   => 'Completed', 'is_returned'  => true,
        ]);
        // Physically delivered, still in the field
        $this->makeRow([
            'delivery_status' => 'Completed', 'is_delivered' => true,
        ]);

        $stats = $this->driverStats();

        $this->assertSame(2, $stats['deliveries_completed']);
        $this->assertSame(1, $stats['returns_completed']);
        $this->assertSame(3, $stats['total_completed']);
    }

    public function test_close_as_completed_with_assigned_driver_counts_nothing(): void
    {
        // The exact shape both the manual admin closure and the automatic
        // refund/void closure produce: is_delivered/is_returned true,
        // driver assigned — but no physical run ever happened.
        $this->makeRow([
            'delivery_status' => 'Close as Completed',
            'pickup_status'   => 'Completed',
            'is_delivered'    => true,
            'is_returned'     => true,
        ]);

        $stats = $this->driverStats();

        $this->assertSame(0, $stats['deliveries_completed']);
        $this->assertSame(0, $stats['returns_completed']);
        $this->assertSame(0, $stats['total_completed']);
    }

    public function test_return_leg_admin_closure_keeps_the_real_delivery_count(): void
    {
        // Physically delivered, but the RETURN leg was administratively
        // closed (e.g. equipment written off) — the delivery was real work,
        // the return was not.
        $this->makeRow([
            'delivery_status' => 'Completed',
            'is_delivered'    => true,
            'pickup_status'   => 'Close as Completed',
            'is_returned'     => true,
        ]);

        $stats = $this->driverStats();

        $this->assertSame(1, $stats['deliveries_completed']);
        $this->assertSame(0, $stats['returns_completed']);
    }

    public function test_mixed_history_totals_match_only_real_work(): void
    {
        // 2 real deliveries (1 fully returned) + 1 administrative closure
        $this->makeRow([
            'delivery_status' => 'Completed', 'is_delivered' => true,
            'pickup_status'   => 'Completed', 'is_returned'  => true,
        ]);
        $this->makeRow([
            'delivery_status' => 'Completed', 'is_delivered' => true,
        ]);
        $this->makeRow([
            'delivery_status' => 'Close as Completed',
            'pickup_status'   => 'Completed',
            'is_delivered'    => true,
            'is_returned'     => true,
        ]);

        $stats = $this->driverStats();

        $this->assertSame(2, $stats['deliveries_completed']);
        $this->assertSame(1, $stats['returns_completed']);
        $this->assertSame(3, $stats['total_completed']);
        // Pending work = only the genuinely open return of the in-field row;
        // the administratively closed row contributes no pending work.
        $this->assertSame(1, $stats['total_pending']);
    }
}
