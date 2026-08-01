<?php

namespace Tests\Feature\Reports;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;
use App\Services\Reports\SalesTaxReportEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Payment Architecture Finalization — SalesTaxReportEngine::salesRows()
 * previously attributed a WHOLE order's subtotal/tax/grand_total to
 * Order::lastPayment (the single highest-id order_payments row, no status
 * filter), which silently misattributed revenue on any split-payment order
 * and overstated collection on any partially-paid order. This test proves
 * the rewrite — one row per qualifying payment, tax proportionally split —
 * fixes both without changing the single-payment case
 * (see SalesTaxExtensionReportingTest, which is unaffected by this rewrite).
 */
class SalesTaxSplitPaymentReportingTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'Split', 'last_name' => 'Payer',
            'email' => 'split-payer@example.com', 'status' => 'Active',
        ]);

        $this->actingAs(User::create([
            'first_name' => 'Tax', 'last_name' => 'Clerk',
            'email' => 'split-tax-clerk@example.com', 'password' => bcrypt('secret'), 'status' => 'Active',
        ]));

        $category = ProductCategory::create(['title' => 'Split Payment Category']);
        $product  = Product::create([
            'product_name' => 'Split Payment Product',
            'slug'         => 'split-payment-product-' . uniqid(),
            'product_type' => 'Rental',
        ]);
        $product->categories()->attach($category->id);

        // $1000 subtotal + $100 tax (10%) = $1100 grand total
        $this->order = Order::create([
            'order_number'  => 'SPLIT-1',
            'order_date'    => now()->toDateString(),
            'customer_id'   => $this->customer->id,
            'customer_name' => 'Split Payer',
            'subtotal'      => 1000,
            'tax_amount'    => 100,
            'grand_total'   => 1100,
        ]);

        OrderProduct::create([
            'order_id'     => $this->order->id,
            'product_id'   => $product->id,
            'product_name' => 'Split Payment Product',
            'price'        => 1000,
            'quantity'     => 1,
            'sub_total'    => 1000,
            'tax'          => 100,
            'total'        => 1100,
            'product_data' => ['product_type' => 'Rental'],
        ]);
    }

    private function filters(array $extra = []): array
    {
        return array_merge([
            'start_date' => now()->subDay()->toDateString(),
            'end_date'   => now()->addDay()->toDateString(),
        ], $extra);
    }

    public function test_split_payment_order_attributes_revenue_to_both_methods_not_just_the_last_one(): void
    {
        // $700 Cash entered first, then $400 Card — split payment, no single
        // row represents "the" order. lastPayment would be the Card row.
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now()->subMinute(),
            'amount' => 700, 'status' => OrderPaymentStatus::PartialPayment->value,
        ]);
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value,
            'payment_datetime' => now(),
            'amount' => 400, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $rows = app(SalesTaxReportEngine::class)->salesRows($this->filters());

        $this->assertCount(2, $rows, 'one row per qualifying payment, not one row for the whole order');

        $cashRow = $rows->firstWhere('payment_type', OrderPaymentMethod::Cash->label());
        $cardRow = $rows->firstWhere('payment_type', OrderPaymentMethod::Card->label());

        $this->assertNotNull($cashRow, 'Cash payment must appear — the OLD code would have attributed everything to Card (lastPayment)');
        $this->assertNotNull($cardRow);

        // grand_total per row = that row's own collected amount
        $this->assertSame(700.0, $cashRow->grand_total);
        $this->assertSame(400.0, $cardRow->grand_total);

        // Tax proportionally split by amount share: 700/1100*100=63.64, 400/1100*100=36.36
        $this->assertSame(63.64, $cashRow->tax_amount);
        $this->assertSame(36.36, $cardRow->tax_amount);

        // Rows reconstitute the order total exactly (no double-count, no loss)
        $this->assertSame(1100.0, round($cashRow->grand_total + $cardRow->grand_total, 2));
        $this->assertSame(100.0, round($cashRow->tax_amount + $cardRow->tax_amount, 2));
        $this->assertSame(1000.0, round($cashRow->subtotal + $cardRow->subtotal, 2));
    }

    public function test_partially_paid_order_counts_only_what_was_actually_collected(): void
    {
        // Only $300 of the $1100 order has actually been collected so far —
        // the OLD code would have counted the FULL $1100 grand_total the
        // moment any qualifying row existed, regardless of how much was
        // really paid.
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount' => 300, 'status' => OrderPaymentStatus::PartialPayment->value,
        ]);

        $rows = app(SalesTaxReportEngine::class)->salesRows($this->filters());

        $this->assertCount(1, $rows);
        $this->assertSame(300.0, $rows->first()->grand_total, 'must count only the actually-collected amount, not the full grand_total');
    }

    public function test_unpaid_order_with_only_a_pending_payment_is_excluded(): void
    {
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value,
            'payment_datetime' => now(),
            'amount' => 1100, 'status' => OrderPaymentStatus::Pending->value,
        ]);

        $rows = app(SalesTaxReportEngine::class)->salesRows($this->filters());

        $this->assertCount(0, $rows);
    }

    public function test_unpaid_cod_is_excluded_but_paid_cod_is_included(): void
    {
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::COD->value,
            'payment_datetime' => now(),
            'amount' => 1100, 'status' => OrderPaymentStatus::Pending->value,
        ]);

        $this->assertCount(0, app(SalesTaxReportEngine::class)->salesRows($this->filters()));

        $this->order->payments()->first()->update(['status' => OrderPaymentStatus::Paid->value]);

        $rows = app(SalesTaxReportEngine::class)->salesRows($this->filters());
        $this->assertCount(1, $rows);
        $this->assertSame(1100.0, $rows->first()->grand_total);
    }

    public function test_account_method_payments_are_excluded_from_stream_a(): void
    {
        // Account revenue is Stream C's (accountRows) — never Stream A's,
        // even though an Account-status row can technically be "settled."
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Account->value,
            'payment_datetime' => now(),
            'amount' => 1100, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $this->assertCount(0, app(SalesTaxReportEngine::class)->salesRows($this->filters()));
    }

    public function test_payment_method_filter_matches_the_specific_row_not_the_whole_order(): void
    {
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now()->subMinute(),
            'amount' => 700, 'status' => OrderPaymentStatus::PartialPayment->value,
        ]);
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value,
            'payment_datetime' => now(),
            'amount' => 400, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $cashOnly = app(SalesTaxReportEngine::class)->salesRows($this->filters(['payment_method' => 'Cash']));
        $this->assertCount(1, $cashOnly);
        $this->assertSame(700.0, $cashOnly->first()->grand_total);

        $cardOnly = app(SalesTaxReportEngine::class)->salesRows($this->filters(['payment_method' => 'Card']));
        $this->assertCount(1, $cardOnly);
        $this->assertSame(400.0, $cardOnly->first()->grand_total);
    }

    // ── Cash-basis / payment-date attribution ────────────────────────────────

    public function test_payment_is_credited_to_the_month_it_was_collected_not_the_order_month(): void
    {
        // Order created in June, paid in full in July. Under cash-basis the
        // whole sale must report in JULY, and June must show nothing.
        $this->order->update(['order_date' => '2026-06-24']);
        $this->order->payments()->create([
            'payment_method'   => OrderPaymentMethod::Card->value,
            'payment_datetime' => '2026-07-29 10:00:00',
            'amount'           => 1100, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $june = app(SalesTaxReportEngine::class)->salesRows([
            'start_date' => '2026-06-01', 'end_date' => '2026-06-30',
        ]);
        $this->assertCount(0, $june, 'the order was created in June but no money was collected in June');

        $july = app(SalesTaxReportEngine::class)->salesRows([
            'start_date' => '2026-07-01', 'end_date' => '2026-07-31',
        ]);
        $this->assertCount(1, $july);
        $this->assertSame('2026-07-29', $july->first()->date);
        $this->assertSame(1100.0, $july->first()->grand_total);
        $this->assertSame(100.0, $july->first()->tax_amount);
    }

    public function test_split_payment_across_two_months_reports_each_portion_in_its_collection_month(): void
    {
        // $700 collected in June, $400 in July, on a $1000 + $100 tax order.
        $this->order->update(['order_date' => '2026-06-24']);
        $this->order->payments()->create([
            'payment_method'   => OrderPaymentMethod::Cash->value,
            'payment_datetime' => '2026-06-24 09:00:00',
            'amount'           => 700, 'status' => OrderPaymentStatus::PartialPayment->value,
        ]);
        $this->order->payments()->create([
            'payment_method'   => OrderPaymentMethod::Card->value,
            'payment_datetime' => '2026-07-29 10:00:00',
            'amount'           => 400, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $june = app(SalesTaxReportEngine::class)->salesRows([
            'start_date' => '2026-06-01', 'end_date' => '2026-06-30',
        ]);
        $this->assertCount(1, $june);
        $this->assertSame('2026-06-24', $june->first()->date);
        $this->assertSame(700.0, $june->first()->grand_total);
        // Tax proportioned over the FULL collected set (700/1100 * 100), NOT
        // the whole order tax — otherwise the two months would double-count.
        $this->assertSame(63.64, $june->first()->tax_amount);

        $july = app(SalesTaxReportEngine::class)->salesRows([
            'start_date' => '2026-07-01', 'end_date' => '2026-07-31',
        ]);
        $this->assertCount(1, $july);
        $this->assertSame('2026-07-29', $july->first()->date);
        $this->assertSame(400.0, $july->first()->grand_total);
        $this->assertSame(36.36, $july->first()->tax_amount);

        // The two months together reconstitute the order exactly.
        $this->assertSame(100.0, round($june->first()->tax_amount + $july->first()->tax_amount, 2));
        $this->assertSame(1100.0, round($june->first()->grand_total + $july->first()->grand_total, 2));
    }

    public function test_recording_a_later_payment_does_not_restate_the_earlier_period(): void
    {
        // Temporal-stability invariant: a filed period must never change because
        // money was collected later. $1000 + $100 tax order (grand_total $1100).
        $this->order->update(['order_date' => '2026-06-24']);
        $engine = fn () => app(SalesTaxReportEngine::class);
        $juneFilters = ['start_date' => '2026-06-01', 'end_date' => '2026-06-30'];
        $julyFilters = ['start_date' => '2026-07-01', 'end_date' => '2026-07-31'];

        // Only the $700 June payment exists so far.
        $this->order->payments()->create([
            'payment_method'   => OrderPaymentMethod::Cash->value,
            'payment_datetime' => '2026-06-24 09:00:00',
            'amount'           => 700, 'status' => OrderPaymentStatus::PartialPayment->value,
        ]);

        $juneBefore = app(SalesTaxReportEngine::class)->salesRows($juneFilters);
        $this->assertCount(1, $juneBefore);
        $this->assertSame(63.64, $juneBefore->first()->tax_amount, 'proportional to the $1100 canonical total, NOT the $700 collected so far');

        // Now the remaining $400 is collected in July.
        $this->order->payments()->create([
            'payment_method'   => OrderPaymentMethod::Card->value,
            'payment_datetime' => '2026-07-29 10:00:00',
            'amount'           => 400, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        // June, re-run, must be byte-for-byte unchanged.
        $juneAfter = app(SalesTaxReportEngine::class)->salesRows($juneFilters);
        $this->assertCount(1, $juneAfter);
        $this->assertSame(63.64, $juneAfter->first()->tax_amount, 'June must not restate after July is recorded');

        // July carries the remainder; lifetime tax reconciles to the order.
        $july = app(SalesTaxReportEngine::class)->salesRows($julyFilters);
        $this->assertCount(1, $july);
        $this->assertSame(36.36, $july->first()->tax_amount);
        $this->assertSame(100.0, round($juneAfter->first()->tax_amount + $july->first()->tax_amount, 2), 'lifetime allocated tax = order tax');
    }
}
