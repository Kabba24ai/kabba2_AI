<?php

namespace Tests\Feature\Reports;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Services\Reports\EmployeePerformanceEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Payment Architecture Finalization — EmployeePerformanceEngine::podStats()
 * previously joined each order to only its single highest-id order_payments
 * row (a MAX(id) subquery) before checking payment_method='COD'. An order
 * whose COD row was NOT the most recent payment event on that order (e.g. a
 * later refund/adjustment row was added afterward) silently vanished from
 * both the total and converted COD counts, because the MAX(id) row itself
 * wasn't COD. Now filters directly on the COD row.
 */
class EmployeePerformancePodStatsTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'Pod', 'last_name' => 'Stats',
            'email' => 'pod-stats@example.com', 'status' => 'Active',
        ]);

        $this->employee = User::create([
            'first_name' => 'Pod', 'last_name' => 'Employee',
            'email' => 'pod-employee@example.com', 'password' => bcrypt('secret'), 'status' => 'Active',
        ]);

        $this->actingAs($this->employee);
    }

    private function podStatsFor(User $employee): array
    {
        $engine = app(EmployeePerformanceEngine::class);
        $result = $engine->reportData([
            'date_range' => 'custom',
            'start_date' => now()->subDay()->toDateString(),
            'end_date'   => now()->addDay()->toDateString(),
        ], [$employee->id], 'single');

        return collect($result['employees'])->firstWhere('id', $employee->id);
    }

    public function test_cod_order_is_counted_even_when_a_later_unrelated_payment_row_is_added(): void
    {
        $order = Order::create([
            'order_date' => now()->toDateString(),
            'created_by_id' => $this->employee->id,
            'created_by_type' => User::class,
            'customer_id' => $this->customer->id,
            'customer_name' => 'Pod Stats',
            'grand_total' => 200,
        ]);

        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::COD->value,
            'payment_datetime' => now()->subMinute(),
            'amount' => 200, 'status' => OrderPaymentStatus::Pending->value,
        ]);

        // A later, unrelated row (higher id) — under the OLD MAX(id) join
        // this becomes the row that decides the order's fate, and since
        // it's not COD, the order silently drops out of both counts.
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount' => 0, 'status' => OrderPaymentStatus::Voided->value,
        ]);

        $row = $this->podStatsFor($this->employee);

        $this->assertSame(1, $row['pod_total'], 'the COD order must still be counted even though a later row exists');
        $this->assertSame(0, $row['pod_converted'], 'still Pending — not yet converted');
    }

    public function test_converted_cod_order_is_counted_as_converted_despite_a_later_row(): void
    {
        $order = Order::create([
            'order_date' => now()->toDateString(),
            'created_by_id' => $this->employee->id,
            'created_by_type' => User::class,
            'customer_id' => $this->customer->id,
            'customer_name' => 'Pod Stats',
            'grand_total' => 150,
        ]);

        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::COD->value,
            'payment_datetime' => now()->subMinute(),
            'amount' => 150, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount' => 0, 'status' => OrderPaymentStatus::Voided->value,
        ]);

        $row = $this->podStatsFor($this->employee);

        $this->assertSame(1, $row['pod_total']);
        $this->assertSame(1, $row['pod_converted'], 'a converted COD order must count as converted regardless of later rows');
        $this->assertSame(100.0, $row['pod_rate']);
    }
}
