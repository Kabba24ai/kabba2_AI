<?php

namespace Tests\Feature\Orders;

use App\Models\Customers\Customer;
use App\Models\Customers\Receipt;
use App\Models\Orders\Order;
use App\Services\ReceiptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Receipt charge components — the printed document must add up.
 *
 * THE DEFECT. `receipts` stored only subtotal, sales_tax and total, but
 * `total` is copied from `orders.grand_total`, which INCLUDES special tax,
 * added fees and any pre-tax discount. A receipt for a $200 order with a $4.00
 * added fee printed "200.00 + 19.50 = 223.50" — on the document the customer
 * keeps.
 *
 * The components now have their own snapshot columns, populated on both the
 * create and the refresh path.
 *
 * `test_a_receipt_reconciles_to_its_own_total` is the load-bearing test.
 * `test_refresh_updates_every_component_together` is the one that matters most
 * in practice: a receipt that resynced its total but not its parts would print
 * a breakdown that no longer sums to itself.
 */
class ReceiptChargeComponentsTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'Receipt', 'last_name' => 'Probe',
            'email' => 'receipt-probe@example.com', 'status' => 'Active',
        ]);
    }

    // ── Creation ───────────────────────────────────────────────────────────

    public function test_a_new_receipt_captures_every_component(): void
    {
        $order = $this->order(specialTax: 4.00, addedFees: 6.00, pretaxDiscount: 50.00);

        $receipt = ReceiptService::getOrCreateReceipt($order);

        $this->assertSame('200.00', (string) $receipt->subtotal, 'Gross merchandise.');
        $this->assertSame('4.00', (string) $receipt->special_tax);
        $this->assertSame('6.00', (string) $receipt->added_fees);
        $this->assertSame('50.00', (string) $receipt->pretax_discount_total);
    }

    public function test_an_ordinary_receipt_records_zeros(): void
    {
        $receipt = ReceiptService::getOrCreateReceipt($this->order());

        $this->assertSame('0.00', (string) $receipt->special_tax);
        $this->assertSame('0.00', (string) $receipt->added_fees);
        $this->assertSame('0.00', (string) $receipt->pretax_discount_total);
    }

    public function test_a_receipt_reconciles_to_its_own_total(): void
    {
        $order = $this->order(specialTax: 4.00, addedFees: 6.00, pretaxDiscount: 50.00);

        $receipt = ReceiptService::getOrCreateReceipt($order);
        $c = fn ($v) => (int) round(((float) $v) * 100);

        $this->assertSame(
            $c($receipt->total),
            $c($receipt->subtotal) - $c($receipt->pretax_discount_total)
                + $c($receipt->sales_tax) + $c($receipt->special_tax) + $c($receipt->added_fees),
            'subtotal − pretax discount + sales tax + special tax + added fees = total'
        );
    }

    // ── Refresh ────────────────────────────────────────────────────────────

    public function test_refresh_updates_every_component_together(): void
    {
        $order = $this->order(specialTax: 4.00);
        $receipt = ReceiptService::getOrCreateReceipt($order);
        $this->assertSame('4.00', (string) $receipt->special_tax);

        // The order is re-priced — as a pre-tax discount would do.
        $order->update([
            'special_tax_amount' => 3.00,
            'pretax_discount_total' => 50.00,
            'tax_amount' => 14.63,
            'grand_total' => 167.63,
        ]);

        $refreshed = ReceiptService::getOrCreateReceipt($order->fresh());

        $this->assertSame($receipt->id, $refreshed->id, 'Same document.');
        $this->assertSame('3.00', (string) $refreshed->special_tax);
        $this->assertSame('50.00', (string) $refreshed->pretax_discount_total);
        $this->assertSame('167.63', (string) $refreshed->total);
    }

    public function test_a_component_change_alone_triggers_a_refresh(): void
    {
        // The drift check must notice a component moving even when subtotal,
        // sales tax and total happen to be unchanged — otherwise a receipt
        // keeps printing a stale breakdown against a correct total.
        $order = $this->order(specialTax: 4.00);
        ReceiptService::getOrCreateReceipt($order);

        DB::table('orders')->where('id', $order->id)->update(['special_tax_amount' => 9.00]);

        $refreshed = ReceiptService::getOrCreateReceipt($order->fresh());

        $this->assertSame('9.00', (string) $refreshed->special_tax);
    }

    public function test_a_receipt_in_step_stays_untouched(): void
    {
        $order = $this->order(specialTax: 4.00, addedFees: 6.00);
        $receipt = ReceiptService::getOrCreateReceipt($order);

        $before = DB::table('receipts')->where('id', $receipt->id)->first();
        ReceiptService::getOrCreateReceipt($order->fresh());

        $this->assertEquals($before, DB::table('receipts')->where('id', $receipt->id)->first());
    }

    // ── Backfill ───────────────────────────────────────────────────────────

    public function test_backfill_derives_components_only_where_the_receipt_is_in_step(): void
    {
        // In step: total still equals the order's grand total.
        $inStep = $this->order(specialTax: 4.00, addedFees: 6.00);
        $matching = $this->legacyReceipt($inStep, total: (float) $inStep->grand_total);

        // Diverged: the order has moved on since the receipt was printed.
        $diverged = $this->order(specialTax: 4.00, addedFees: 6.00);
        $stale = $this->legacyReceipt($diverged, total: 999.99);

        $this->runBackfill();

        $this->assertSame('4.00', (string) $matching->fresh()->special_tax, 'Derived exactly.');
        $this->assertSame('6.00', (string) $matching->fresh()->added_fees);

        $this->assertSame('0.00', (string) $stale->fresh()->special_tax, 'Never invented.');
        $this->assertSame('0.00', (string) $stale->fresh()->added_fees);
        $this->assertSame('999.99', (string) $stale->fresh()->total, 'And the printed total is untouched.');
    }

    // ── Rendering ──────────────────────────────────────────────────────────

    public function test_the_printed_receipt_shows_each_component(): void
    {
        $order = $this->order(specialTax: 4.00, addedFees: 6.00, pretaxDiscount: 50.00);
        ReceiptService::getOrCreateReceipt($order);

        $html = $this->renderReceipt($order);

        $this->assertStringContainsString('Special Tax', $html);
        $this->assertStringContainsString('Added Fees', $html);
        // The adjustment line is now NAMED from its type. This fixture has a
        // discount total but no ProductDiscount row, so the type genuinely
        // cannot be identified and the singular generic is correct — see
        // ReceiptAdjustmentLabelTest for the identified cases.
        $this->assertStringContainsString('Pre-Tax Discount', $html);
        $this->assertStringContainsString('Discounted Product Value', $html);
    }

    public function test_zero_components_stay_off_the_printed_receipt(): void
    {
        $order = $this->order();
        ReceiptService::getOrCreateReceipt($order);

        $html = $this->renderReceipt($order);

        $this->assertStringNotContainsString('Special Tax', $html);
        $this->assertStringNotContainsString('Added Fees', $html);
        $this->assertStringNotContainsString('Pre-Tax Discounts', $html);
    }

    public function test_the_receipt_no_longer_reads_a_single_discount_row(): void
    {
        // Stacked adjustments: the cumulative column is the only correct
        // source. A single ProductDiscount row would show one of the two.
        $order = $this->order(pretaxDiscount: 50.00);
        ReceiptService::getOrCreateReceipt($order);

        $this->assertStringContainsString('$50.00', $this->renderReceipt($order));

        $view = file_get_contents(resource_path('views/admin/order_management/orders/print_receipt.blade.php'));
        $this->assertStringNotContainsString('receiptScDiscount', $view);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function renderReceipt(Order $order): string
    {
        $receipt = ReceiptService::getOrCreateReceipt($order->fresh());
        $salesTax = 0.0975;

        return view('admin.order_management.orders.print_receipt', [
            'receipt'   => $receipt->fresh(),
            'order'     => $order->fresh(),
            'customer'  => $this->customer->fresh(),
            'users'     => collect(),
            'sales_tax' => $salesTax,
        ])->render();
    }

    private function runBackfill(): void
    {
        DB::statement("
            UPDATE receipts r
            JOIN orders o ON o.id = r.order_id
            SET r.special_tax           = o.special_tax_amount,
                r.added_fees            = o.added_fees_amount,
                r.pretax_discount_total = o.pretax_discount_total
            WHERE r.order_id IS NOT NULL
              AND o.deleted_at IS NULL
              AND ABS(r.total - o.grand_total) < 0.005
        ");
    }

    /** A receipt as it existed before the component columns were added. */
    private function legacyReceipt(Order $order, float $total): Receipt
    {
        return Receipt::create([
            'customer_id' => $order->customer_id,
            'order_id' => $order->id,
            'receipt_date' => now(),
            'order_date' => $order->order_date,
            'payment_status' => 'pending',
            'subtotal' => $order->subtotal,
            'sales_tax' => $order->tax_amount,
            'total' => $total,
        ]);
    }

    private function order(
        float $specialTax = 0.0,
        float $addedFees = 0.0,
        float $pretaxDiscount = 0.0,
    ): Order {
        $subtotal = 200.00;
        $tax = $pretaxDiscount > 0 ? 14.63 : 19.50;

        $order = Order::create([
            'order_date' => now()->format('Y-m-d'),
            'customer_id' => $this->customer->id,
            'customer_name' => 'Receipt Probe',
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'special_tax_amount' => $specialTax,
            'added_fees_amount' => $addedFees,
            'pretax_discount_total' => $pretaxDiscount,
            'discount_amount' => 0,
            'grand_total' => $subtotal - $pretaxDiscount + $tax + $specialTax + $addedFees,
        ]);

        $order->products()->create([
            'unique_id' => 'ORD-RCPT-'.$order->id,
            'product_name' => 'Receipt Probe Item',
            'price' => $subtotal, 'quantity' => 1,
            'sub_total' => $subtotal, 'tax' => $tax,
            'special_tax' => $specialTax, 'added_fees' => $addedFees,
            'total' => $subtotal + $tax + $specialTax + $addedFees,
            'product_data' => json_encode(['special_tax' => $specialTax, 'added_fees' => $addedFees]),
        ]);

        return $order->fresh();
    }
}
