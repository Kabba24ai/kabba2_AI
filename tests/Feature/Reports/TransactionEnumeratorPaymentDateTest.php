<?php

namespace Tests\Feature\Reports;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Models\ProductManagement\Product;
use App\Services\Reports\Transactions\TransactionEnumerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression lock for the Transaction Report / Authorize.Net Reconciliation
 * date window (TransactionEnumerator) — these surfaces were ALREADY
 * payment-date-based when the rest of financial reporting converted to
 * cash-basis. This test pins that behavior so a future refactor cannot
 * silently revert it to order-date, and proves POD placeholders (expected,
 * uncollected revenue) can never enter either surface.
 */
class TransactionEnumeratorPaymentDateTest extends TestCase
{
    use RefreshDatabase;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $customer = Customer::create([
            'first_name' => 'Enum', 'last_name' => 'Erator',
            'email' => 'enumerator@example.com', 'status' => 'Active',
        ]);

        $this->actingAs(User::create([
            'first_name' => 'Enum', 'last_name' => 'Clerk',
            'email' => 'enum-clerk@example.com', 'password' => bcrypt('secret'), 'status' => 'Active',
        ]));

        $product = Product::create([
            'product_name' => 'Enumerator Product',
            'slug'         => 'enumerator-product-' . uniqid(),
            'product_type' => 'Rental',
        ]);

        // June order, $1,000 + $100 tax.
        $this->order = Order::create([
            'order_number'  => 'ENUM-1',
            'order_date'    => '2026-06-24',
            'customer_id'   => $customer->id,
            'customer_name' => 'Enum Erator',
            'subtotal'      => 1000,
            'tax_amount'    => 100,
            'grand_total'   => 1100,
        ]);

        OrderProduct::create([
            'order_id'     => $this->order->id,
            'product_id'   => $product->id,
            'product_name' => 'Enumerator Product',
            'price'        => 1000,
            'quantity'     => 1,
            'sub_total'    => 1000,
            'tax'          => 100,
            'total'        => 1100,
            'product_data' => ['product_type' => 'Rental'],
        ]);
    }

    private function records(string $start, string $end): \Illuminate\Support\Collection
    {
        return app(TransactionEnumerator::class)->records([
            'date_range' => 'custom', 'start_date' => $start, 'end_date' => $end,
        ]);
    }

    public function test_a_charge_is_windowed_by_payment_date_not_order_date(): void
    {
        // June order, paid July 29 with a gateway transaction id.
        $this->order->payments()->create([
            'payment_method'   => OrderPaymentMethod::Card->value,
            'payment_datetime' => '2026-07-29 10:00:00',
            'transaction_id'   => 'TXN-JULY-1',
            'amount'           => 1100, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $this->assertCount(
            0,
            $this->records('2026-06-01', '2026-06-30'),
            'the order was created in June but the payment executed in July — June must be empty'
        );

        $july = $this->records('2026-07-01', '2026-07-31');
        $this->assertCount(1, $july);
        $this->assertSame('charge', $july->first()['transaction_type']);
        $this->assertTrue($july->first()['is_collected']);
        $this->assertStringStartsWith('2026-07-29', $july->first()['payment_date']);
        // order_date is carried for display only — never for windowing.
        $this->assertStringStartsWith('2026-06-24', $july->first()['order_date']);
    }

    public function test_pod_placeholders_never_reach_the_transaction_report_or_authnet_reconciliation(): void
    {
        // Unpaid COD placeholder: Pending status, no gateway transaction id —
        // expected revenue, not a transaction attempt.
        $this->order->payments()->create([
            'payment_method'   => OrderPaymentMethod::COD->value,
            'payment_datetime' => '2026-06-24 09:00:00',
            'amount'           => 1100, 'status' => OrderPaymentStatus::Pending->value,
        ]);

        $this->assertCount(
            0,
            $this->records('2026-06-01', '2026-07-31'),
            'a POD placeholder must be invisible to the Transaction Report and (through it) the Authorize.Net reconciliation'
        );
    }

    public function test_a_refund_is_windowed_by_its_refund_date_while_the_charge_stays_in_its_own_period(): void
    {
        $this->order->payments()->create([
            'payment_method'   => OrderPaymentMethod::Card->value,
            'payment_datetime' => '2026-06-24 09:00:00',
            'transaction_id'   => 'TXN-JUNE-1',
            'amount'           => 1100, 'status' => OrderPaymentStatus::Paid->value,
        ]);
        $this->order->payments()->create([
            'payment_method'   => OrderPaymentMethod::Card->value,
            'payment_datetime' => '2026-07-15 10:00:00',
            'refunded_at'      => '2026-07-15 10:00:00',
            'transaction_id'   => 'TXN-REFUND-1',
            'amount'           => 0,
            'refund_amount'    => 300,
            'status'           => OrderPaymentStatus::Refund->value,
        ]);

        $june = $this->records('2026-06-01', '2026-06-30');
        $this->assertCount(1, $june, 'June holds only the charge');
        $this->assertSame('charge', $june->first()['transaction_type']);
        $this->assertSame(1100.0, $june->first()['signed_amount']);

        $july = $this->records('2026-07-01', '2026-07-31');
        $this->assertCount(1, $july, 'July holds only the refund');
        $this->assertSame('refund', $july->first()['transaction_type']);
        $this->assertSame(-300.0, $july->first()['signed_amount']);
    }
}
