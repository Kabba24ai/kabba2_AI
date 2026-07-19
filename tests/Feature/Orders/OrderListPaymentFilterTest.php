<?php

namespace Tests\Feature\Orders;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Payment Architecture Finalization (Tier 2) — the Admin order list's
 * payment_method/payment_status filters previously matched only
 * Order::lastPayment (the single highest-id order_payments row). A
 * split-payment order paid partly Cash, partly Card would only ever match
 * a filter for whichever method was entered LAST — filtering by the
 * earlier method silently excluded a genuinely matching order. Now matches
 * when ANY of the order's payment rows qualifies.
 */
class OrderListPaymentFilterTest extends TestCase
{
    use RefreshDatabase;

    private Order $splitOrder;

    protected function setUp(): void
    {
        parent::setUp();

        $customer = Customer::create([
            'first_name' => 'Filter', 'last_name' => 'Test',
            'email' => 'order-list-filter-test@example.com', 'status' => 'Active',
        ]);

        $this->splitOrder = Order::create([
            'order_number'  => 'FILT-1',
            'order_date'    => now()->toDateString(),
            'customer_id'   => $customer->id,
            'customer_name' => 'Filter Test',
            'grand_total'   => 1000,
        ]);

        // Cash entered FIRST (lower id) — a filter for "Cash" must find this
        // order even though Cash is NOT the most recently entered payment.
        $this->splitOrder->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now()->subMinute(),
            'amount' => 600, 'status' => OrderPaymentStatus::PartialPayment->value,
        ]);
        // Card entered LAST (higher id) — Order::lastPayment resolves to this row.
        $this->splitOrder->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value,
            'payment_datetime' => now(),
            'amount' => 400, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $this->actingAs(User::create([
            'first_name' => 'Order', 'last_name' => 'Lister',
            'email' => 'order-list-filter-clerk@example.com', 'status' => 'Active',
        ]));
    }

    private function ajaxGet(array $query = [])
    {
        return $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('admin.order-management.orders.index', $query));
    }

    public function test_payment_method_filter_finds_split_payment_order_by_its_earlier_method(): void
    {
        // The OLD code (whereRelation('lastPayment', 'payment_method', 'Cash'))
        // would have matched only the Card row and excluded this order.
        $response = $this->ajaxGet(['payment_method' => 'Cash'])->assertOk();

        $response->assertJsonFragment(['success' => true]);
        $this->assertStringContainsString('FILT-1', $response->json('html'));
    }

    public function test_payment_method_filter_still_finds_split_payment_order_by_its_later_method(): void
    {
        $response = $this->ajaxGet(['payment_method' => 'Card'])->assertOk();

        $this->assertStringContainsString('FILT-1', $response->json('html'));
    }

    public function test_payment_status_filter_finds_split_payment_order_by_its_earlier_row_status(): void
    {
        // The OLD code (whereRelation('lastPayment', 'status', 'Partial Payment'))
        // would have matched only the Card=Paid row and excluded this order.
        $response = $this->ajaxGet(['payment_status' => 'Partial Payment'])->assertOk();

        $this->assertStringContainsString('FILT-1', $response->json('html'));
    }

    public function test_unrelated_payment_method_does_not_match(): void
    {
        $response = $this->ajaxGet(['payment_method' => 'Cheque'])->assertOk();

        $this->assertStringNotContainsString('FILT-1', $response->json('html'));
    }

    // ── Filter semantics hardening: "Pending"/"Failed" mean "still
    //    unresolved," not "ever had a row with that status" ─────────────

    public function test_failed_filter_excludes_an_order_whose_failed_attempt_was_later_fully_paid(): void
    {
        $resolved = Order::create([
            'order_number' => 'FILT-RESOLVED', 'order_date' => now()->toDateString(),
            'customer_id' => $this->splitOrder->customer_id, 'customer_name' => 'Filter Test',
            'grand_total' => 500,
        ]);
        $resolved->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now()->subMinute(),
            'amount' => 0, 'status' => OrderPaymentStatus::Failed->value,
        ]);
        $resolved->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'amount' => 500, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $response = $this->ajaxGet(['payment_status' => 'Failed'])->assertOk();

        $this->assertStringNotContainsString('FILT-RESOLVED', $response->json('html'), 'a resolved order must not show up under "Failed" just because it has a historical failed row');
    }

    public function test_failed_filter_includes_an_order_that_is_still_genuinely_unresolved(): void
    {
        $unresolved = Order::create([
            'order_number' => 'FILT-UNRESOLVED', 'order_date' => now()->toDateString(),
            'customer_id' => $this->splitOrder->customer_id, 'customer_name' => 'Filter Test',
            'grand_total' => 500,
        ]);
        $unresolved->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(),
            'amount' => 0, 'status' => OrderPaymentStatus::Failed->value,
        ]);

        $response = $this->ajaxGet(['payment_status' => 'Failed'])->assertOk();

        $this->assertStringContainsString('FILT-UNRESOLVED', $response->json('html'));
    }

    public function test_pending_filter_excludes_an_order_whose_pending_placeholder_was_superseded_by_a_completed_payment(): void
    {
        $resolved = Order::create([
            'order_number' => 'FILT-COD-RESOLVED', 'order_date' => now()->toDateString(),
            'customer_id' => $this->splitOrder->customer_id, 'customer_name' => 'Filter Test',
            'grand_total' => 300,
        ]);
        $resolved->payments()->create([
            'payment_method' => OrderPaymentMethod::COD->value, 'payment_datetime' => now()->subHour(),
            'amount' => 300, 'status' => OrderPaymentStatus::Pending->value,
        ]);
        $resolved->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(),
            'amount' => 300, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $response = $this->ajaxGet(['payment_status' => 'Pending'])->assertOk();

        $this->assertStringNotContainsString('FILT-COD-RESOLVED', $response->json('html'));
    }

    public function test_paid_filter_semantics_are_unaffected_still_means_any_row(): void
    {
        // Non-Pending/Failed statuses keep the simple "any row has this
        // status" meaning — confirming the scope didn't change behavior
        // for a value it isn't special-casing.
        $response = $this->ajaxGet(['payment_status' => 'Paid'])->assertOk();

        $this->assertStringContainsString('FILT-1', $response->json('html'));
    }
}
