<?php

namespace Tests\Feature\Cart;

use App\Models\Configurations\Setting;
use App\Models\Locations\State;
use App\Models\Orders\Order;
use App\Models\ProductManagement\Product;
use App\Models\Stores\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Checkout must populate the CURRENT special-tax and added-fee columns.
 *
 * Before these columns existed, both components were computed at checkout,
 * folded into `orders.grand_total`, and then persisted ONLY inside the
 * per-line `product_data` JSON. Nothing in the schema recorded them, so an
 * order whose totals later moved for a legitimate reason could never
 * reconcile again.
 *
 * These are full HTTP checkouts on the gateway-free COD path — not unit tests
 * of `CartHelper` — because the failure being guarded against is a WRITER
 * omission. A test that called the helper directly would compute the right
 * numbers and still pass while `PostController` dropped them on the floor.
 *
 * `test_line_and_order_columns_reconcile_exactly` is the load-bearing one: it
 * asserts the identity every downstream financial path depends on,
 *
 *     grand_total = subtotal + tax + special_tax + added_fees - discount
 *
 * in integer cents, against columns rather than JSON.
 */
class CheckoutSpecialTaxColumnsTest extends TestCase
{
    use RefreshDatabase;

    // Both taxes are stored as DECIMAL RATES, not percentages.
    // ProductSettings\SaveController divides an operator's "9.75" / "2.00" by
    // 100 on save, and the settings form renders them back through
    // displayPercentage(). Writing a raw percentage here would bypass that
    // conversion and test a contract the application does not use.
    //
    // An earlier version of this file did exactly that — it stored 2.0 and
    // still expected $4.00, which only worked because CartHelper divided by
    // 100 a second time. Two errors cancelled, and the test passed while
    // production under-charged special tax by 100x.
    private const TAX_RATE     = 0.0975; // 9.75%
    private const SPECIAL_RATE = 0.02;   // 2%
    private const FEE_PER_UNIT = 5.0;    // flat dollars per unit

    private State $state;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\Configurations\SettingSeeder::class);

        $this->setSetting('sales_tax', self::TAX_RATE);
        $this->setSetting('special_taxes', self::SPECIAL_RATE);
        $this->setSetting('added_fees', self::FEE_PER_UNIT);

        $this->state = State::create(['name' => 'Tennessee', 'slug' => 'tennessee', 'abbreviation' => 'TN']);

        Store::create([
            'unique_id' => Str::uuid()->toString(), 'store_name' => 'Main Yard',
            'address' => '1 Yard Rd', 'state_id' => $this->state->id, 'city' => 'Nashville',
            'zip_code' => '37201', 'is_primary' => 'Yes', 'status' => 'Active',
        ]);
    }

    // ── Scenarios ──────────────────────────────────────────────────────────

    public function test_ordinary_order_with_neither_component_writes_explicit_zeros(): void
    {
        $product = $this->retailProduct('plain', 100.00);

        $order = $this->checkout([$this->cartLine($product)]);

        $this->assertCents(0, $order->special_tax_amount);
        $this->assertCents(0, $order->added_fees_amount);

        $line = $order->products()->firstOrFail();
        $this->assertCents(0, $line->special_tax);
        $this->assertCents(0, $line->added_fees);

        // Not merely zero — zero because nothing was owed.
        $this->assertReconciles($order);
    }

    public function test_special_tax_only_is_persisted_at_line_and_order_level(): void
    {
        $product = $this->retailProduct('special-only', 100.00, applySpecialTax: true);

        $order = $this->checkout([$this->cartLine($product)]);

        // 100.00 @ 2% = 2.00
        $this->assertCents(200, $order->special_tax_amount);
        $this->assertCents(0, $order->added_fees_amount);
        $this->assertCents(200, $order->products()->firstOrFail()->special_tax);

        $this->assertReconciles($order);
    }

    public function test_added_fees_only_are_persisted_at_line_and_order_level(): void
    {
        $product = $this->retailProduct('fees-only', 100.00, applyAddedFees: true);

        $order = $this->checkout([$this->cartLine($product, quantity: 3)]);

        // $5.00 per unit x 3
        $this->assertCents(1500, $order->added_fees_amount);
        $this->assertCents(0, $order->special_tax_amount);
        $this->assertCents(1500, $order->products()->firstOrFail()->added_fees);

        $this->assertReconciles($order);
    }

    public function test_both_components_on_one_line_are_persisted_independently(): void
    {
        $product = $this->retailProduct('both', 100.00, applySpecialTax: true, applyAddedFees: true);

        $order = $this->checkout([$this->cartLine($product, quantity: 2)]);

        // subtotal 200.00 -> special 4.00 ; fees 5.00 x 2 = 10.00
        $this->assertCents(400, $order->special_tax_amount);
        $this->assertCents(1000, $order->added_fees_amount);

        $line = $order->products()->firstOrFail();
        $this->assertCents(400, $line->special_tax);
        $this->assertCents(1000, $line->added_fees);

        $this->assertReconciles($order);
    }

    public function test_multiple_lines_with_different_applicability_aggregate_correctly(): void
    {
        $plain   = $this->retailProduct('multi-plain', 100.00);
        $special = $this->retailProduct('multi-special', 100.00, applySpecialTax: true);
        $fees    = $this->retailProduct('multi-fees', 100.00, applyAddedFees: true);

        $order = $this->checkout([
            $this->cartLine($plain),
            $this->cartLine($special),
            $this->cartLine($fees),
        ]);

        $this->assertCents(200, $order->special_tax_amount);
        $this->assertCents(500, $order->added_fees_amount);

        // The order total is the sum of its own lines — not a separately
        // computed number that happens to agree.
        $lines = $order->products()->orderBy('id')->get();
        $this->assertSame(3, $lines->count());
        $this->assertCents(
            (int) round($lines->sum(fn ($l) => (float) $l->special_tax) * 100),
            $order->special_tax_amount
        );
        $this->assertCents(
            (int) round($lines->sum(fn ($l) => (float) $l->added_fees) * 100),
            $order->added_fees_amount
        );

        $this->assertReconciles($order);
    }

    public function test_tax_free_merchandise_still_carries_its_special_tax(): void
    {
        // The two flags are independent: `is_tax_free_item` zeroes ORDINARY
        // tax only. A line can owe special tax while owing no sales tax, and
        // conflating the two would silently drop revenue.
        $product = $this->retailProduct('tax-free-special', 100.00, applySpecialTax: true, taxFree: true);

        $order = $this->checkout([$this->cartLine($product)]);

        $line = $order->products()->firstOrFail();
        $this->assertCents(0, $line->tax, 'Tax-free merchandise must carry no ordinary tax.');
        $this->assertCents(200, $line->special_tax, 'Special tax survives the tax-free flag.');
        $this->assertCents(0, $order->tax_amount);
        $this->assertCents(200, $order->special_tax_amount);

        $this->assertReconciles($order);
    }

    public function test_line_and_order_columns_reconcile_exactly(): void
    {
        $special = $this->retailProduct('recon-special', 137.49, applySpecialTax: true);
        $both    = $this->retailProduct('recon-both', 88.13, applySpecialTax: true, applyAddedFees: true);
        $free    = $this->retailProduct('recon-free', 42.07, taxFree: true);

        $order = $this->checkout([
            $this->cartLine($special),
            $this->cartLine($both, quantity: 2),
            $this->cartLine($free),
        ]);

        $this->assertReconciles($order);

        // Every order-level component equals the sum of its lines, to the cent.
        $lines = $order->products()->get();
        foreach ([['special_tax', 'special_tax_amount'], ['added_fees', 'added_fees_amount']] as [$lineCol, $orderCol]) {
            $this->assertCents(
                (int) round($lines->sum(fn ($l) => (float) $l->{$lineCol}) * 100),
                $order->{$orderCol},
                "orders.{$orderCol} must equal the sum of order_products.{$lineCol}."
            );
        }
    }

    public function test_frozen_snapshot_still_agrees_with_the_new_columns_at_creation(): void
    {
        // At creation the column and the snapshot must be identical — that
        // equality is what makes `product_data` usable as the ORIGINAL-state
        // record once a later adjustment moves the column away from it.
        $product = $this->retailProduct('snapshot-parity', 100.00, applySpecialTax: true, applyAddedFees: true);

        $order = $this->checkout([$this->cartLine($product)]);
        $line = $order->products()->firstOrFail();

        $this->assertCents((int) round(((float) $line->product_data['special_tax']) * 100), $line->special_tax);
        $this->assertCents((int) round(((float) $line->product_data['added_fees']) * 100), $line->added_fees);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function setSetting(string $name, $value): void
    {
        Setting::where('setting_type', 'Product Settings')
            ->where('setting_name', $name)
            ->update(['setting_value' => $value]);
    }

    private function retailProduct(
        string $slug,
        float $price,
        bool $applySpecialTax = false,
        bool $applyAddedFees = false,
        bool $taxFree = false,
    ): Product {
        return Product::create([
            'unique_id'         => Str::uuid()->toString(),
            'product_name'      => ucfirst($slug),
            'slug'              => $slug,
            'product_type'      => 'Retail',
            'status'            => 'Published',
            'retail_price'      => $price,
            'in_store_pickup'   => 'Yes',
            'apply_special_tax' => $applySpecialTax,
            'apply_added_fees'  => $applyAddedFees,
            'is_tax_free_item'  => $taxFree,
        ]);
    }

    /** @return array<string,mixed> */
    private function cartLine(Product $product, int $quantity = 1): array
    {
        return [
            'product_unique_id'    => $product->unique_id,
            'product_type'         => 'Retail',
            'quantity'             => $quantity,
            'service_method'       => 'In Store Pickup',
            'product_option_items' => [],
            'product_rental_items' => [],
        ];
    }

    /** @param array<int,array<string,mixed>> $cartLines */
    private function checkout(array $cartLines): Order
    {
        Event::fake([
            \App\Events\Front\Checkout\OrderPlacedEvent::class,
            \App\Events\Front\Checkout\OrderPlacedEmailEvent::class,
        ]);
        Queue::fake();

        $items = \App\Helpers\CartHelper::buildCartSummary(['cart_items' => $cartLines])['cart_items'];

        $response = $this->postJson('http://' . config('app.domains.front') . '/checkout', [
            'billingFirstName' => 'Cart', 'billingLastName' => 'Tester',
            'billingEmail' => 'special-tax@example.test', 'billingPhone' => '(615) 555-0101',
            'billingAddress' => '42 Test Ln', 'billingState' => $this->state->id,
            'billingCity' => 'Nashville', 'billingZip' => '37201',
            'sameAsBilling' => 'Yes',
            'payment' => 'COD',
            'cart' => json_encode($items),
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        return Order::latest('id')->firstOrFail();
    }

    /** grand_total = subtotal + tax + special_tax + added_fees - discount, in cents. */
    private function assertReconciles(Order $order): void
    {
        $c = fn ($v) => (int) round(((float) $v) * 100);

        $this->assertSame(
            $c($order->grand_total),
            $c($order->subtotal) + $c($order->tax_amount)
                + $c($order->special_tax_amount) + $c($order->added_fees_amount)
                - $c($order->discount_amount),
            'Order totals must reconcile from COLUMNS alone, with no appeal to frozen JSON.'
        );
    }

    private function assertCents(int $expected, $actual, string $message = ''): void
    {
        $this->assertSame($expected, (int) round(((float) $actual) * 100), $message);
    }
}
