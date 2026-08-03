<?php

namespace Tests\Feature\Orders;

use App\Enums\Discounts\DiscountType;
use App\Models\Customers\Customer;
use App\Models\Discounts\ProductDiscount;
use App\Models\Orders\Order;
use App\Services\Discounts\PretaxAdjustmentPresenter;
use App\Services\ReceiptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Receipt adjustment labels and PDF-safe rendering.
 *
 * TWO PRESENTATION DEFECTS.
 *
 * 1. The adjustment line read "Pre-Tax Discounts" — too generic to tell a
 *    customer what was actually applied, and plural for a single adjustment.
 *
 * 2. The deduction rendered as "? $50.00". The HTML entity `&minus;` is
 *    U+2212 MINUS SIGN, which dompdf's default font does not contain, so it
 *    was substituted with "?" on every discounted receipt — a defect that
 *    predates the charge-component work.
 *
 * `test_the_amount_renders_with_a_plain_ascii_minus` is the load-bearing one:
 * it asserts on the ACTUAL rendered output rather than the template source, so
 * a re-introduced entity fails.
 *
 * Presentation only. No calculation, persistence or schema is touched.
 */
class ReceiptAdjustmentLabelTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'Label', 'last_name' => 'Probe',
            'email' => 'label-probe@example.com', 'status' => 'Active',
        ]);
    }

    // ── Labels ─────────────────────────────────────────────────────────────

    public function test_store_credit_renders_its_explicit_pretax_label(): void
    {
        $order = $this->discountedOrder(DiscountType::StoreCredit);

        $html = $this->render($order);

        $this->assertStringContainsString('Store Credit - Pre-Tax', $html);
    }

    public function test_the_generic_plural_label_is_absent_when_the_type_is_identifiable(): void
    {
        $order = $this->discountedOrder(DiscountType::StoreCredit);

        $html = $this->render($order);

        $this->assertStringNotContainsString('Pre-Tax Discounts', $html);
    }

    public function test_goodwill_maps_to_its_own_label_without_any_goodwill_behaviour(): void
    {
        // LABEL ARCHITECTURE ONLY. No Goodwill workflow, writer or endpoint
        // exists; this asserts the mapping is ready, nothing more.
        $this->assertSame('Goodwill - Pre-Tax', DiscountType::Goodwill->receiptLabel());
        $this->assertTrue(DiscountType::Goodwill->reducesTaxableBasis());
        $this->assertFalse(DiscountType::Goodwill->isOperationalInPhase1(), 'Still deferred.');
    }

    public function test_gift_card_is_not_a_pretax_adjustment_type(): void
    {
        // A Gift Card is PURCHASED VALUE — tender — and must never be grouped
        // with pre-tax adjustments or reduce the taxable merchandise basis.
        // It is deliberately absent from the enum entirely.
        $this->assertNull(DiscountType::tryFrom('gift_card'));

        foreach (DiscountType::cases() as $case) {
            $this->assertStringNotContainsStringIgnoringCase('gift card', $case->receiptLabel());
            $this->assertTrue($case->reducesTaxableBasis(), 'Every type in this enum is pre-tax by construction.');
        }
    }

    // ── The stray character ────────────────────────────────────────────────

    public function test_the_amount_renders_with_a_plain_ascii_minus(): void
    {
        $order = $this->discountedOrder(DiscountType::StoreCredit);

        $html = $this->render($order);

        $this->assertStringContainsString('-$50.00', $html);
        $this->assertStringNotContainsString('? $50.00', $html);
        $this->assertStringNotContainsString('&minus;', $html);
        $this->assertStringNotContainsString("\u{2212}", $html, 'U+2212 is absent from the PDF font.');
    }

    public function test_the_rendered_totals_block_is_ascii_safe(): void
    {
        // Guards the whole money region, not just the one line that broke:
        // any non-ASCII character in a figure is a candidate for the same
        // font-substitution failure.
        $order = $this->discountedOrder(DiscountType::StoreCredit);

        $html = $this->render($order);

        preg_match_all('/>\s*[-(]?\$[\d,]+\.\d{2}\)?\s*</u', $html, $matches);
        $this->assertNotEmpty($matches[0], 'Expected to find rendered currency figures.');

        foreach ($matches[0] as $figure) {
            $this->assertSame(
                $figure,
                mb_convert_encoding($figure, 'ASCII', 'UTF-8'),
                "Rendered figure is not ASCII-safe: {$figure}"
            );
        }
    }

    // ── Stacking ───────────────────────────────────────────────────────────

    public function test_two_types_stacked_fall_back_to_a_truthful_generic(): void
    {
        // The receipt stores ONE cumulative figure with no attribution, so the
        // sum cannot honestly be called Store Credit when part of it is not.
        $order = $this->discountedOrder(DiscountType::StoreCredit);
        $this->addDiscount($order, DiscountType::Goodwill, 20.00);

        $this->assertTrue(PretaxAdjustmentPresenter::isMixed($order));
        $this->assertSame(PretaxAdjustmentPresenter::MIXED, PretaxAdjustmentPresenter::label($order));

        $html = $this->render($order);
        $this->assertStringNotContainsString('Store Credit - Pre-Tax', $html, 'Must not attribute the whole sum to one type.');
    }

    public function test_two_adjustments_of_the_SAME_type_keep_the_specific_label(): void
    {
        // Stacked Store Credits are still all Store Credit — the label stays
        // exact. Only a MIX of types is ambiguous.
        $order = $this->discountedOrder(DiscountType::StoreCredit);
        $this->addDiscount($order, DiscountType::StoreCredit, 20.00);

        $this->assertFalse(PretaxAdjustmentPresenter::isMixed($order));
        $this->assertStringContainsString('Store Credit - Pre-Tax', $this->render($order));
    }

    public function test_the_presenter_returns_the_full_type_set_not_one_row(): void
    {
        $order = $this->discountedOrder(DiscountType::StoreCredit);
        $this->addDiscount($order, DiscountType::Goodwill, 20.00);

        $types = PretaxAdjustmentPresenter::activeTypes($order);

        $this->assertCount(2, $types);
        $this->assertContains(DiscountType::StoreCredit, $types);
        $this->assertContains(DiscountType::Goodwill, $types);
    }

    public function test_a_reversed_adjustment_no_longer_names_the_line(): void
    {
        $order = $this->discountedOrder(DiscountType::StoreCredit);
        ProductDiscount::where('target_id', $order->id)->update(['status' => ProductDiscount::STATUS_REVERSED]);

        $this->assertSame(PretaxAdjustmentPresenter::UNIDENTIFIED, PretaxAdjustmentPresenter::label($order->fresh()));
    }

    // ── Unchanged behaviour ────────────────────────────────────────────────

    public function test_a_receipt_without_an_adjustment_shows_no_adjustment_row(): void
    {
        $order = $this->order();
        ReceiptService::getOrCreateReceipt($order);

        $html = $this->render($order);

        $this->assertStringNotContainsString('Pre-Tax', $html);
        $this->assertStringNotContainsString('Discounted Product Value', $html);
        $this->assertStringNotContainsString('Store Credit', $html);
    }

    public function test_special_tax_and_added_fees_render_unchanged(): void
    {
        $order = $this->discountedOrder(DiscountType::StoreCredit);

        $html = $this->render($order);

        $this->assertStringContainsString('Special Tax', $html);
        $this->assertStringContainsString('$3.00', $html);
        $this->assertStringContainsString('Added Fees', $html);
        $this->assertStringContainsString('$4.00', $html);
    }

    public function test_the_persisted_totals_are_untouched(): void
    {
        $order = $this->discountedOrder(DiscountType::StoreCredit);
        $receipt = ReceiptService::getOrCreateReceipt($order->fresh());

        $before = \Illuminate\Support\Facades\DB::table('receipts')->where('id', $receipt->id)->first();
        $this->render($order);

        $this->assertEquals(
            $before,
            \Illuminate\Support\Facades\DB::table('receipts')->where('id', $receipt->id)->first(),
            'Rendering must not write.'
        );

        $this->assertSame('200.00', (string) $receipt->subtotal);
        $this->assertSame('50.00', (string) $receipt->pretax_discount_total);
        $this->assertSame('14.63', (string) $receipt->sales_tax);
        $this->assertSame('3.00', (string) $receipt->special_tax);
        $this->assertSame('4.00', (string) $receipt->added_fees);
        $this->assertSame('171.63', (string) $receipt->total);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function render(Order $order): string
    {
        $receipt = ReceiptService::getOrCreateReceipt($order->fresh());

        return view('admin.order_management.orders.print_receipt', [
            'receipt'   => $receipt->fresh(),
            'order'     => $order->fresh(),
            'customer'  => $this->customer->fresh(),
            'users'     => collect(),
            'sales_tax' => 0.0975,
        ])->render();
    }

    private function discountedOrder(DiscountType $type): Order
    {
        // 200.00 basis − 50.00 concession; 150.00 @ 9.75% = 14.63;
        // special tax 3.00; added fees 4.00 → 171.63.
        $order = $this->order(specialTax: 3.00, addedFees: 4.00, pretaxDiscount: 50.00, tax: 14.63);
        $this->addDiscount($order, $type, 50.00);

        return $order->fresh();
    }

    private function addDiscount(Order $order, DiscountType $type, float $amount): ProductDiscount
    {
        static $n = 0;
        $n++;

        return ProductDiscount::create([
            'discount_type' => $type->value,
            'calculation_type' => $type->defaultCalculationType()->value,
            'source_amount' => $amount,
            'percentage' => null,
            'calculated_discount_amount' => $amount,
            'target_type' => 'order',
            'target_id' => $order->id,
            'customer_id' => $order->customer_id,
            'original_product_value' => $order->subtotal,
            'discounted_product_value' => (float) $order->subtotal - $amount,
            'taxable_value_before' => $order->subtotal,
            'taxable_value_after' => (float) $order->subtotal - $amount,
            'tax_before' => 0, 'tax_after' => 0,
            'idempotency_key' => 'label-test-'.$order->id.'-'.$n,
            'applied_at' => now(),
            'status' => ProductDiscount::STATUS_APPLIED,
        ]);
    }

    private function order(
        float $specialTax = 0.0,
        float $addedFees = 0.0,
        float $pretaxDiscount = 0.0,
        float $tax = 19.50,
    ): Order {
        $subtotal = 200.00;

        $order = Order::create([
            'order_date' => now()->format('Y-m-d'),
            'customer_id' => $this->customer->id,
            'customer_name' => 'Label Probe',
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'special_tax_amount' => $specialTax,
            'added_fees_amount' => $addedFees,
            'pretax_discount_total' => $pretaxDiscount,
            'discount_amount' => 0,
            'grand_total' => $subtotal - $pretaxDiscount + $tax + $specialTax + $addedFees,
        ]);

        $order->products()->create([
            'unique_id' => 'ORD-LBL-'.$order->id,
            'product_name' => 'Label Probe Item',
            'price' => $subtotal, 'quantity' => 1,
            'sub_total' => $subtotal, 'tax' => $tax,
            'special_tax' => $specialTax, 'added_fees' => $addedFees,
            'total' => $subtotal + $tax + $specialTax + $addedFees,
            'product_data' => json_encode(['special_tax' => $specialTax, 'added_fees' => $addedFees]),
        ]);

        return $order->fresh();
    }
}
