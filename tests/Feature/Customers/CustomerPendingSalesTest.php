<?php

namespace Tests\Feature\Customers;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Customers\Customer;
use App\Models\Orders\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Payment Architecture Finalization — Customer::getPendingSalesAttribute()
 * had the same MAX(id) attribution flaw already fixed in
 * getPaidSalesAttribute(): it joined each order to only its single
 * highest-id order_payments row and summed the WHOLE order's grand_total
 * whenever that row was Pending, ignoring any amount already collected by
 * an earlier settled payment. Now sums each such order's actual
 * outstanding balance instead.
 */
class CustomerPendingSalesTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'Pending', 'last_name' => 'Sales',
            'email' => 'pending-sales@example.com', 'status' => 'Active',
        ]);
    }

    public function test_pending_sales_counts_only_the_outstanding_balance_not_the_full_grand_total(): void
    {
        $order = Order::create([
            'order_date' => now()->toDateString(),
            'customer_id' => $this->customer->id,
            'customer_name' => 'Pending Sales',
            'grand_total' => 1000,
        ]);

        // $600 already settled by an earlier payment...
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now()->subDay(),
            'amount' => 600, 'status' => OrderPaymentStatus::PartialPayment->value,
        ]);

        // ...then a LATER pending attempt for the remaining $400. The OLD
        // code would have counted the FULL $1000 grand_total as "pending"
        // because the pending row happened to be the highest-id row.
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value,
            'payment_datetime' => now(),
            'amount' => 400, 'status' => OrderPaymentStatus::Pending->value,
        ]);

        $this->assertSame(400.0, (float) $this->customer->fresh()->pending_sales);
    }

    public function test_order_with_no_pending_payment_contributes_nothing(): void
    {
        $order = Order::create([
            'order_date' => now()->toDateString(),
            'customer_id' => $this->customer->id,
            'customer_name' => 'Pending Sales',
            'grand_total' => 500,
        ]);

        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount' => 500, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $this->assertSame(0.0, (float) $this->customer->fresh()->pending_sales);
    }
}
