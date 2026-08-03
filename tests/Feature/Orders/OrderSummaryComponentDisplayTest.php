<?php

namespace Tests\Feature\Orders;

use App\Models\Configurations\Setting;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Order Details financial summary — every charged component must be visible.
 *
 * THE DEFECT THESE GUARD AGAINST. `orders.special_tax_amount` and
 * `added_fees_amount` are charged and folded into `grand_total`, but the
 * summary rendered only Subtotal, Taxes and Grand Total. A $200 order with a
 * $4.00 added fee displayed "200.00 + 19.50 = 223.50" — arithmetic that does
 * not add up, on a page an operator uses to explain a bill to a customer.
 *
 * `test_the_visible_rows_reconcile_to_the_grand_total` is the load-bearing
 * test: it recomputes the total from what is actually on screen.
 *
 * Every figure is read from the order's canonical columns. `product_data` is
 * never reparsed by the view.
 */
class OrderSummaryComponentDisplayTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'Summary', 'last_name' => 'Probe',
            'email' => 'summary-probe@example.com', 'status' => 'Active',
        ]);

        $this->actingAs(User::create([
            'first_name' => 'Sam', 'last_name' => 'Admin',
            'email' => 'sam-summary-probe@example.com', 'status' => 'Active',
        ]));

        foreach (['payment_api_public_key', 'payment_api_key'] as $name) {
            Setting::create(['setting_type' => 'Payment Settings', 'setting_name' => $name, 'setting_value' => 'x']);
        }
    }

    // ── Individual components ──────────────────────────────────────────────

    public function test_special_tax_displays_alone(): void
    {
        $order = $this->order(specialTax: 4.00);

        $this->renderOrder($order)
            ->assertSee('Special Tax:', false)
            ->assertSee('$4.00', false)
            ->assertDontSee('Added Fees:', false);
    }

    public function test_added_fees_display_alone(): void
    {
        // The exact shape from the reported screenshot: a $4.00 fee charged
        // into the grand total and invisible on the page.
        $order = $this->order(addedFees: 4.00);

        $this->renderOrder($order)
            ->assertSee('Added Fees:', false)
            ->assertSee('$4.00', false)
            ->assertDontSee('Special Tax:', false);
    }

    public function test_both_display_together(): void
    {
        $order = $this->order(specialTax: 4.00, addedFees: 6.00);

        $response = $this->renderOrder($order);
        $response->assertSee('Special Tax:', false);
        $response->assertSee('$4.00', false);
        $response->assertSee('Added Fees:', false);
        $response->assertSee('$6.00', false);
    }

    public function test_zero_value_rows_stay_hidden(): void
    {
        $order = $this->order();

        $this->renderOrder($order)
            ->assertDontSee('Special Tax:', false)
            ->assertDontSee('Added Fees:', false)
            ->assertDontSee('Pre-Tax Discounts:', false);
    }

    public function test_store_credit_displays_alongside_both(): void
    {
        $order = $this->order(specialTax: 4.00, addedFees: 6.00, pretaxDiscount: 50.00);

        $response = $this->renderOrder($order);
        $response->assertSee('Pre-Tax Discounts:', false);
        $response->assertSee('$50.00', false);
        $response->assertSee('Special Tax:', false);
        $response->assertSee('Added Fees:', false);
    }

    public function test_the_pretax_discount_row_shows_the_cumulative_total_not_one_adjustment(): void
    {
        // The canonical column carries every stacked adjustment. Reading a
        // single ProductDiscount row would show only the most recent one.
        $order = $this->order(pretaxDiscount: 50.00);

        $this->renderOrder($order)->assertSee('$50.00', false);
    }

    // ── Reconciliation ─────────────────────────────────────────────────────

    public function test_the_visible_rows_reconcile_to_the_grand_total(): void
    {
        // 200.00 − 50.00 + 14.63 + 3.00 + 6.00 = 173.63
        $order = $this->order(
            subtotal: 200.00, tax: 14.63, specialTax: 3.00, addedFees: 6.00, pretaxDiscount: 50.00,
        );

        $this->renderOrder($order)->assertSee('$173.63', false);

        // The same arithmetic the operator performs by eye.
        $c = fn ($v) => (int) round(((float) $v) * 100);
        $this->assertSame(
            $c($order->grand_total),
            $c($order->subtotal) - $c($order->pretax_discount_total)
                + $c($order->tax_amount) + $c($order->special_tax_amount)
                + $c($order->added_fees_amount) - $c($order->discount_amount),
            'Every component on screen must sum to the grand total.'
        );
    }

    public function test_the_screenshot_case_now_adds_up(): void
    {
        // Reported: 200.00 + 19.50 shown, 223.50 charged. The missing 4.00 is
        // the added fee, which is now on screen.
        $order = $this->order(subtotal: 200.00, tax: 19.50, addedFees: 4.00);

        $this->assertSame('223.50', (string) $order->fresh()->grand_total);

        $response = $this->renderOrder($order);
        $response->assertSee('$200.00', false);
        $response->assertSee('$19.50', false);
        $response->assertSee('Added Fees:', false);
        $response->assertSee('$4.00', false);
        $response->assertSee('$223.50', false);
    }

    // ── Label change ───────────────────────────────────────────────────────

    public function test_an_ordinary_order_is_unchanged_except_the_tax_label(): void
    {
        $order = $this->order(subtotal: 200.00, tax: 19.50);

        $response = $this->renderOrder($order);
        $response->assertSee('Subtotal:', false);
        $response->assertSee('$200.00', false);
        $response->assertSee('Sales Tax:', false);      // renamed from "Taxes:"
        $response->assertSee('$19.50', false);
        $response->assertSee('Grand Total:', false);
        $response->assertSee('$219.50', false);

        $response->assertDontSee('>Taxes:<', false);
    }

    public function test_gross_subtotal_is_preserved_when_discounted(): void
    {
        // The concession is a separate line; the merchandise figure stays gross.
        $order = $this->order(subtotal: 200.00, tax: 14.63, pretaxDiscount: 50.00);

        $response = $this->renderOrder($order);
        $response->assertSee('$200.00', false);            // gross subtotal
        $response->assertSee('Discounted Product Value:', false);
        $response->assertSee('$150.00', false);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function renderOrder(Order $order)
    {
        return $this->get(route('admin.order-management.orders.edit', $order->unique_id))->assertOk();
    }

    private function order(
        float $subtotal = 200.00,
        float $tax = 19.50,
        float $specialTax = 0.0,
        float $addedFees = 0.0,
        float $pretaxDiscount = 0.0,
    ): Order {
        return Order::create([
            'order_date' => now()->format('Y-m-d'),
            'customer_id' => $this->customer->id,
            'customer_name' => 'Summary Probe',
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'special_tax_amount' => $specialTax,
            'added_fees_amount' => $addedFees,
            'pretax_discount_total' => $pretaxDiscount,
            'discount_amount' => 0,
            'grand_total' => $subtotal - $pretaxDiscount + $tax + $specialTax + $addedFees,
        ]);
    }
}
