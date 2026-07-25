<?php

namespace Tests\Feature\Orders;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Services\Orders\OrderPaymentSummary;
use App\Services\PaymentDescriptionPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Payment & Accounts Consistency Initiative — Stage 1 (Presentation + Refund
 * eligibility). One canonical interpretation of an Account order everywhere:
 *   - status reads "On Account" (indigo), never "Pending"/"Unpaid"/"Unknown";
 *   - method reads "Account", never "Unknown";
 *   - it is NOT refundable at the order level (no money collected on the order).
 * Accounting is untouched — Account is still excluded from every settled total.
 */
class OnAccountPresentationTest extends TestCase
{
    use RefreshDatabase;

    private function customer(): Customer
    {
        return Customer::create([
            'first_name' => 'Acme', 'last_name' => 'Co',
            'email' => 'onacct-' . uniqid() . '@example.com', 'status' => 'Active',
        ]);
    }

    private function order(Customer $customer): Order
    {
        return Order::create([
            'order_number'  => 'OA' . random_int(1000, 999999),
            'order_date'    => now()->toDateString(),
            'customer_id'   => $customer->id,
            'customer_name' => $customer->full_name,
            'subtotal'      => 100,
            'grand_total'   => 100,
        ]);
    }

    private function accountOrder(): Order
    {
        $order = $this->order($this->customer());
        $order->payments()->create([
            'payment_datetime' => now(),
            'payment_method'   => OrderPaymentMethod::Account->value,
            'amount'           => 100,
            'status'           => OrderPaymentStatus::Account->value,
        ]);

        return $order->fresh();
    }

    private function paidCardOrder(): Order
    {
        $order = $this->order($this->customer());
        $order->payments()->create([
            'payment_datetime' => now(),
            'payment_method'   => OrderPaymentMethod::Card->value,
            'amount'           => 100,
            'status'           => OrderPaymentStatus::Paid->value,
            'transaction_id'   => 'txn-oa-1',
        ]);

        return $order->fresh();
    }

    public function test_account_order_summary_is_on_account_not_unpaid(): void
    {
        $summary = OrderPaymentSummary::for($this->accountOrder());

        $this->assertTrue($summary->isOnAccount);
        $this->assertSame(OrderPaymentSummary::COLLECTION_ON_ACCOUNT, $summary->collectionStatus);
        $this->assertSame('On Account', $summary->balanceStatusLabel());
    }

    public function test_account_order_method_reads_account_not_unknown(): void
    {
        $summary = OrderPaymentSummary::for($this->accountOrder());

        // Settled methods are empty for an on-account order; the display
        // helper surfaces Account instead of falling through to "Unknown".
        $this->assertTrue($summary->paymentMethodsUsed->isEmpty());
        $this->assertSame('Account', PaymentDescriptionPresenter::methodsUsedLabel($summary->methodsUsedForDisplay()));
    }

    public function test_account_status_label_and_badge_are_canonical(): void
    {
        $this->assertSame('On Account', PaymentDescriptionPresenter::statusLabel(OrderPaymentStatus::Account));
        $this->assertStringContainsString('indigo', PaymentDescriptionPresenter::statusBadgeClasses(OrderPaymentStatus::Account));

        $summary = OrderPaymentSummary::for($this->accountOrder());
        $this->assertStringContainsString('indigo', PaymentDescriptionPresenter::orderStatusBadgeClasses($summary));
    }

    public function test_account_order_is_not_refundable(): void
    {
        $summary = OrderPaymentSummary::for($this->accountOrder());

        $this->assertFalse($summary->canRefund());
        $this->assertNotNull($summary->refundableReason());
        $this->assertStringContainsStringIgnoringCase('account', $summary->refundableReason());
    }

    public function test_account_order_keeps_accounting_out_of_settled_totals(): void
    {
        $order = $this->accountOrder();

        // The single load-bearing accounting property: Account is never a
        // settled payment, so no cash/card/revenue total ever counts it.
        $this->assertEquals(0.0, (float) $order->total_paid);
        $this->assertFalse((bool) $order->is_paid);
    }

    public function test_paid_card_order_is_refundable(): void
    {
        $summary = OrderPaymentSummary::for($this->paidCardOrder());

        $this->assertFalse($summary->isOnAccount);
        $this->assertTrue($summary->canRefund());
        $this->assertNull($summary->refundableReason());
    }

    public function test_filter_providers_expose_account_but_canonical_does_not(): void
    {
        $this->assertContains(OrderPaymentMethod::Account, OrderPaymentMethod::filterOptions());
        $this->assertContains(OrderPaymentStatus::Account, OrderPaymentStatus::filterOptions());
        $this->assertNotContains(OrderPaymentMethod::Account, OrderPaymentMethod::canonical());
        $this->assertNotContains(OrderPaymentStatus::Account, OrderPaymentStatus::canonical());
    }
}
