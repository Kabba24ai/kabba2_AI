<?php

namespace Tests\Feature\Cart;

use App\Models\Configurations\Setting;
use App\Models\Locations\State;
use App\Models\Orders\Order;
use App\Models\ProductManagement\Product;
use App\Models\Stores\Store;
use App\Services\Discounts\DiscountApplicationService;
use App\Services\Discounts\PretaxDiscountAllocator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The special-tax percentage contract.
 *
 * THE DEFECT THESE GUARD AGAINST. `special_taxes` is stored as a DECIMAL RATE
 * (0.02 = 2%) — `ProductSettings\SaveController` divides the operator's "2.00"
 * by 100 on save, exactly as it does for `sales_tax`, and the settings form
 * renders it back through `displayPercentage()`. `CartHelper` then divided by
 * 100 a SECOND time, so a 2% special tax on a $200 line charged $0.04 instead
 * of $4.00 — a 100x under-charge, live since the feature shipped.
 *
 * Ordinary sales tax was never affected: `$taxRate` reads `sales_tax` with no
 * division, which is the correct pattern.
 *
 * `test_two_percent_on_two_hundred_is_four_dollars` is the load-bearing test —
 * it fails by a factor of 100 against the defect.
 */
class SpecialTaxPercentageTest extends TestCase
{
    use RefreshDatabase;

    private State $state;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\Configurations\SettingSeeder::class);

        $this->state = State::create(['name' => 'Tennessee', 'slug' => 'tennessee', 'abbreviation' => 'TN']);

        Store::create([
            'unique_id' => Str::uuid()->toString(), 'store_name' => 'Main Yard',
            'address' => '1 Yard Rd', 'state_id' => $this->state->id, 'city' => 'Nashville',
            'zip_code' => '37201', 'is_primary' => 'Yes', 'status' => 'Active',
        ]);
    }

    // ── 1–4: the percentage contract ───────────────────────────────────────

    public function test_1_two_percent_on_two_hundred_is_four_dollars(): void
    {
        $order = $this->checkoutWithSpecialTax(percent: 2.00, price: 200.00);

        $this->assertSame('4.00', (string) $order->special_tax_amount, '200.00 x 2% = 4.00, not 0.04.');
    }

    public function test_2_zero_percent_produces_zero(): void
    {
        $order = $this->checkoutWithSpecialTax(percent: 0.00, price: 200.00);

        $this->assertSame('0.00', (string) $order->special_tax_amount);
    }

    public function test_3_two_and_a_half_percent_on_two_hundred_is_five_dollars(): void
    {
        $order = $this->checkoutWithSpecialTax(percent: 2.50, price: 200.00);

        $this->assertSame('5.00', (string) $order->special_tax_amount);
    }

    public function test_4_a_quarter_percent_on_two_hundred_is_fifty_cents(): void
    {
        // Guards the fix from over-correcting: a genuinely small percentage
        // must still produce a small amount, not be inflated 100x.
        $order = $this->checkoutWithSpecialTax(percent: 0.25, price: 200.00);

        $this->assertSame('0.50', (string) $order->special_tax_amount);
    }

    // ── 5–6: the neighbouring components must not move ─────────────────────

    public function test_5_ordinary_sales_tax_is_unchanged(): void
    {
        $order = $this->checkoutWithSpecialTax(percent: 2.00, price: 200.00);

        $this->assertSame('19.50', (string) $order->tax_amount, '200.00 x 9.75% — untouched by this fix.');
    }

    public function test_6_added_fees_are_unchanged(): void
    {
        // Added fees are a FLAT dollar amount and are neither divided on save
        // nor on read. The fix must not disturb that.
        $order = $this->checkoutWithSpecialTax(percent: 2.00, price: 200.00, addedFees: 6.00);

        $this->assertSame('6.00', (string) $order->added_fees_amount);
    }

    // ── 7–8: preview, persistence and the three storage locations ──────────

    public function test_7_the_checkout_preview_matches_the_persisted_order(): void
    {
        $this->setSetting('special_taxes', 0.02);   // stored as a RATE
        $product = $this->product('preview-parity', 200.00, applySpecialTax: true);

        // The same builder the checkout page renders from.
        $summary = \App\Helpers\CartHelper::buildCartSummary(['cart_items' => [$this->cartLine($product)]]);

        $order = $this->postCheckout($summary['cart_items']);

        $this->assertSame('4.00', number_format($summary['special_tax_total'], 2), 'Preview.');
        $this->assertSame('4.00', (string) $order->special_tax_amount, 'Persisted — identical.');
    }

    public function test_8_frozen_snapshot_line_column_and_order_aggregate_all_agree(): void
    {
        $order = $this->checkoutWithSpecialTax(percent: 2.00, price: 200.00);
        $line = $order->products()->firstOrFail();

        $this->assertSame('4.00', (string) $order->special_tax_amount, 'Order aggregate.');
        $this->assertSame('4.00', (string) $line->special_tax, 'Line current column.');
        $this->assertSame('4.00', number_format((float) $line->product_data['special_tax'], 2), 'Frozen snapshot.');
    }

    // ── 9: the Release 1 correction still applies on top ───────────────────

    public function test_9_a_store_credit_discount_reduces_four_dollars_to_three(): void
    {
        // The Release 1 smoke test that could not be completed until this
        // defect was fixed: $50 concession against a $200 basis leaves 75% of
        // the merchandise, so special tax falls from 4.00 to 3.00.
        $order = $this->checkoutWithSpecialTax(percent: 2.00, price: 200.00);
        $this->assertSame('4.00', (string) $order->special_tax_amount);

        \App\Models\Customers\CustomerCredit::create([
            'customer_id' => $order->customer_id, 'type' => 'grant',
            'amount' => 100.00, 'reason' => 'special tax fix test',
        ]);

        app(DiscountApplicationService::class)->applyStoreCredit(
            \App\Enums\Discounts\DiscountTargetType::Order, $order->id, 50.00,
            'sptax-fix-'.$order->id, null, 'test', null, $order->customer_id,
        );

        $order->refresh();
        $this->assertSame('3.00', (string) $order->special_tax_amount, '150.00 x 2% = 3.00.');
        $this->assertSame('200.00', (string) $order->subtotal, 'Gross untouched.');
        $this->assertNull(PretaxDiscountAllocator::reconcile($order));
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function setSetting(string $name, $value): void
    {
        Setting::where('setting_type', 'Product Settings')
            ->where('setting_name', $name)
            ->update(['setting_value' => $value]);
    }

    /**
     * Store a human percentage through the SAME conversion the settings
     * controller performs, so the test exercises the real storage contract
     * rather than hard-coding a rate.
     */
    private function setSpecialTaxPercent(float $percent): void
    {
        $this->setSetting('special_taxes', floatval($percent) / 100);
    }

    private function product(string $slug, float $price, bool $applySpecialTax = false, bool $applyAddedFees = false): Product
    {
        return Product::create([
            'unique_id' => Str::uuid()->toString(),
            'product_name' => ucfirst($slug), 'slug' => $slug,
            'product_type' => 'Retail', 'status' => 'Published',
            'retail_price' => $price, 'in_store_pickup' => 'Yes',
            'apply_special_tax' => $applySpecialTax,
            'apply_added_fees' => $applyAddedFees,
        ]);
    }

    /** @return array<string,mixed> */
    private function cartLine(Product $product): array
    {
        return [
            'product_unique_id' => $product->unique_id,
            'product_type' => 'Retail', 'quantity' => 1,
            'service_method' => 'In Store Pickup',
            'product_option_items' => [], 'product_rental_items' => [],
        ];
    }

    private function checkoutWithSpecialTax(float $percent, float $price, float $addedFees = 0.0): Order
    {
        $this->setSpecialTaxPercent($percent);

        if ($addedFees > 0) {
            $this->setSetting('added_fees', $addedFees);
        }

        $product = $this->product('sptax-'.str_replace('.', '-', (string) $percent).'-'.uniqid(), $price,
            applySpecialTax: true, applyAddedFees: $addedFees > 0);

        $items = \App\Helpers\CartHelper::buildCartSummary(['cart_items' => [$this->cartLine($product)]])['cart_items'];

        return $this->postCheckout($items);
    }

    /** @param array<int,array<string,mixed>> $items */
    private function postCheckout(array $items): Order
    {
        Event::fake([
            \App\Events\Front\Checkout\OrderPlacedEvent::class,
            \App\Events\Front\Checkout\OrderPlacedEmailEvent::class,
        ]);
        Queue::fake();

        $response = $this->postJson('http://'.config('app.domains.front').'/checkout', [
            'billingFirstName' => 'Tax', 'billingLastName' => 'Probe',
            'billingEmail' => 'sptax-'.uniqid().'@example.test', 'billingPhone' => '(615) 555-0101',
            'billingAddress' => '42 Test Ln', 'billingState' => $this->state->id,
            'billingCity' => 'Nashville', 'billingZip' => '37201',
            'sameAsBilling' => 'Yes', 'payment' => 'COD',
            'cart' => json_encode($items),
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        return Order::latest('id')->firstOrFail();
    }
}
