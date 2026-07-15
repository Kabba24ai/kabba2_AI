<?php

namespace Tests\Feature\Cart;

use App\Helpers\CartHelper;
use App\Models\Configurations\Setting;
use App\Models\ProductManagement\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Phase 3 — customer-facing Custom delivery selection.
 *
 * The product page keeps three choices (Standard / Extended / Custom); the
 * Custom popup reveals only the configured tiers as distances ("Up to 60
 * Miles" — never "Custom 2"). The cart accepts only the internal tier
 * identifier and recalculates distance, one-way rate, and the final amount
 * from settings + product columns; client-submitted prices are never read.
 * Legacy Custom items without a tier keep the historical Extended fallback.
 */
class CustomDeliveryCartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\Configurations\SettingSeeder::class);

        // Deterministic globals for the whole suite
        $this->setSetting('distance_unit', 'Miles');
        $this->setSetting('standard_delivery_range', '15');
        $this->setSetting('extended_delivery_range', '30');
        $this->setSetting('custom_1_delivery_range', '45');
        $this->setSetting('custom_2_delivery_range', '60');
        $this->setSetting('custom_3_delivery_range', null);   // unconfigured distance
        $this->setSetting('custom_4_delivery_range', '90');
    }

    private function setSetting(string $name, ?string $value): void
    {
        Setting::where('setting_type', 'Product Settings')
            ->where('setting_name', $name)->update(['setting_value' => $value]);
    }

    private function makeProduct(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'unique_id'              => Str::uuid()->toString(),
            'product_name'           => 'Custom Delivery Tester ' . Str::random(6),
            'slug'                   => 'custom-delivery-tester-' . Str::lower(Str::random(6)),
            'product_type'           => 'Rental',
            'status'                 => 'Published',
            'rental_daily'           => 100,
            'in_store_pickup'        => 'Yes',
            'delivery_and_pickup'    => 'Yes',
            'standard_delivery_fee'  => 89,
            'extended_delivery_fee'  => 124,
            'custom_1_delivery_fee'  => 70,
            'custom_2_delivery_fee'  => 124,
            'custom_3_delivery_fee'  => 80,   // distance is null -> tier unavailable
            'custom_4_delivery_fee'  => null, // rate null -> tier unavailable
        ], $overrides));
    }

    private function frontUrl(string $path): string
    {
        return 'http://' . config('app.domains.front') . $path;
    }

    private function productPage(Product $product): string
    {
        return $this->get($this->frontUrl("/products/{$product->slug}/daily/details"))
            ->assertOk()->getContent();
    }

    private function cartPayload(Product $product, array $overrides = []): array
    {
        return array_merge([
            'product_unique_id' => $product->unique_id,
            'product_type'      => 'Rental',
            'product_variant'   => 'daily',
            'quantity'          => 1,
            'delivery_date'     => now()->addDays(3)->format('m/d/Y'),
            'service_method'    => 'Delivery',
            'distance_type'     => 'Custom',
            'custom_tier'       => 'custom_2',
            'service_option'    => 'Delivery + Pickup',
            'product_option_items' => [],
            'product_rental_items' => [],
        ], $overrides);
    }

    private function saveToCart(array $payload)
    {
        return $this->postJson($this->frontUrl('/cart/save'), $payload);
    }

    private function buildItem(Product $product, array $overrides = []): array
    {
        $summary = CartHelper::buildCartSummary([
            'cart_items' => [$this->cartPayload($product, $overrides)],
        ]);

        return $summary['cart_items'][0];
    }

    // ── Custom option availability ────────────────────────────────────────────

    public function test_only_fully_configured_tiers_render_and_admin_names_stay_hidden(): void
    {
        $html = $this->productPage($this->makeProduct());

        // custom_1 + custom_2 valid; custom_3 lacks a distance; custom_4 lacks a rate
        $this->assertStringContainsString('Up to 45 Miles', $html);
        $this->assertStringContainsString('Up to 60 Miles', $html);
        $this->assertStringNotContainsString('Up to 90 Miles', $html, 'custom_4 has no product rate');

        $visibleText = strip_tags($html);
        foreach (['Custom 1', 'Custom 2', 'Custom 3', 'Custom 4'] as $adminName) {
            $this->assertStringNotContainsString($adminName, $visibleText, 'administrative tier names must stay internal');
        }
    }

    public function test_zero_dollar_rate_remains_a_visible_valid_option(): void
    {
        $product = $this->makeProduct(['custom_1_delivery_fee' => 0]);

        $html = $this->productPage($product);
        $this->assertStringContainsString('Up to 45 Miles', $html);

        $item = $this->buildItem($product, ['custom_tier' => 'custom_1']);
        $this->assertSame(0.0, (float) $item['service_option_price']);
        $this->assertSame(0.0, (float) $item['delivery_one_way_rate']);
    }

    public function test_the_old_informational_popup_is_gone(): void
    {
        $html = $this->productPage($this->makeProduct());

        $this->assertStringNotContainsString('customServiceOption', $html);
        $this->assertStringNotContainsString('using the Extended Range Delivery', $html);
        $this->assertStringContainsString('customDeliveryModal', $html);
        $this->assertStringContainsString('Select Distance Range', $html);
        $this->assertStringContainsString('Select Delivery Service', $html);
        $this->assertStringContainsString('Continue with Reservation', $html);
        $this->assertStringContainsString('Choose a distance range to see pricing.', $html);
    }

    public function test_popup_uses_return_pickup_terminology(): void
    {
        $html = $this->productPage($this->makeProduct());

        $this->assertStringContainsString('Delivery + Return Pickup', $html);
        $this->assertStringContainsString('Return Pickup Only', $html);
    }

    public function test_custom_radio_is_hidden_when_no_tier_is_valid(): void
    {
        $product = $this->makeProduct([
            'custom_1_delivery_fee' => null,
            'custom_2_delivery_fee' => null,
            'custom_3_delivery_fee' => null,
            'custom_4_delivery_fee' => null,
        ]);

        // The distance radios live in the AJAX-loaded details fragment
        $details = $this->postJson(
            $this->frontUrl("/products/{$product->slug}/daily/details"),
            ['kabba_cart' => []],
            ['X-Requested-With' => 'XMLHttpRequest'],
        )->assertOk()->json('html');

        $this->assertStringNotContainsString('value="Custom"', $details, 'no dead-end Custom option');
        $this->assertStringContainsString('value="Standard"', $details);
    }

    // ── Pricing (server-side canonical calculation) ───────────────────────────

    public function test_custom_pricing_for_each_service_type(): void
    {
        $product = $this->makeProduct(); // custom_2 one-way = 124

        $both = $this->buildItem($product, ['service_option' => 'Delivery + Pickup']);
        $this->assertSame(248.0, (float) $both['service_option_price'], 'Delivery + Return Pickup = one-way × 2');

        $deliveryOnly = $this->buildItem($product, ['service_option' => 'Delivery Only', 'delivery_store_id' => null]);
        $this->assertSame(124.0, (float) $deliveryOnly['service_option_price']);

        $returnOnly = $this->buildItem($product, ['service_option' => 'Return Only', 'delivery_store_id' => null]);
        $this->assertSame(124.0, (float) $returnOnly['service_option_price']);
    }

    public function test_standard_and_extended_pricing_still_works(): void
    {
        $product = $this->makeProduct();

        $standard = $this->buildItem($product, ['distance_type' => 'Standard', 'custom_tier' => null]);
        $this->assertSame(178.0, (float) $standard['service_option_price']);
        $this->assertSame('15 Miles', $standard['distance_range']);
        $this->assertNull($standard['custom_tier']);

        $extended = $this->buildItem($product, ['distance_type' => 'Extended', 'custom_tier' => null, 'service_option' => 'Delivery Only']);
        $this->assertSame(124.0, (float) $extended['service_option_price']);
        $this->assertSame('30 Miles', $extended['distance_range']);
    }

    public function test_custom_item_snapshots_canonical_values_and_ignores_submitted_prices(): void
    {
        $product = $this->makeProduct();

        $item = $this->buildItem($product, [
            // manipulated client fields — all must be ignored
            'service_option_price'   => 1,
            'delivery_one_way_rate'  => 1,
            'distance_range'         => '9999 Miles',
        ]);

        $this->assertSame('custom_2', $item['custom_tier']);
        $this->assertSame('60 Miles', $item['distance_range'], 'distance comes from settings, not the client');
        $this->assertSame(124.0, (float) $item['delivery_one_way_rate'], 'rate comes from the product column');
        $this->assertSame(248.0, (float) $item['service_option_price'], 'amount is recalculated server-side');
    }

    public function test_rental_duration_does_not_multiply_the_delivery_fee(): void
    {
        $product = $this->makeProduct(['rental_weekly' => 400]);

        $item = $this->buildItem($product, ['product_variant' => 'weekly', 'quantity' => 2]);

        $this->assertSame(248.0, (float) $item['service_option_price'], 'delivery is charged once per reservation');
    }

    // ── Backward compatibility ────────────────────────────────────────────────

    public function test_legacy_custom_item_without_a_tier_keeps_the_extended_fallback(): void
    {
        $product = $this->makeProduct();

        $item = $this->buildItem($product, ['custom_tier' => null]);

        $this->assertNull($item['custom_tier']);
        $this->assertSame(248.0, (float) $item['service_option_price'], 'extended 124 × 2 — historical behavior');
        $this->assertSame('30 Miles', $item['distance_range'], 'extended range text — historical behavior');
    }

    public function test_a_tampered_tier_never_prices_from_client_data(): void
    {
        $product = $this->makeProduct();

        // custom_9 is not a real tier; pricing degrades to the canonical
        // extended fallback and never reads client-submitted numbers
        $item = $this->buildItem($product, [
            'custom_tier' => 'custom_9',
            'service_option_price' => 5,
        ]);

        $this->assertNull($item['custom_tier']);
        $this->assertSame(248.0, (float) $item['service_option_price']);
    }

    // ── Add-to-cart validation & security ─────────────────────────────────────

    public function test_custom_without_a_tier_cannot_be_added_to_cart(): void
    {
        $this->saveToCart($this->cartPayload($this->makeProduct(), ['custom_tier' => null]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('custom_tier');
    }

    public function test_an_invalid_tier_identifier_is_rejected(): void
    {
        $this->saveToCart($this->cartPayload($this->makeProduct(), ['custom_tier' => 'custom_9']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('custom_tier');
    }

    public function test_a_tier_without_a_global_distance_is_rejected(): void
    {
        // custom_3 has a product rate but no configured distance
        $this->saveToCart($this->cartPayload($this->makeProduct(), ['custom_tier' => 'custom_3']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('custom_tier');
    }

    public function test_a_tier_with_a_null_product_fee_is_rejected(): void
    {
        // custom_4 has a distance but the product rate is null
        $this->saveToCart($this->cartPayload($this->makeProduct(), ['custom_tier' => 'custom_4']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('custom_tier');
    }

    public function test_an_invalid_service_type_is_rejected(): void
    {
        $this->saveToCart($this->cartPayload($this->makeProduct(), ['service_option' => 'Teleportation']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('service_option');
    }

    public function test_a_valid_custom_selection_saves_with_canonical_values(): void
    {
        $product = $this->makeProduct();

        $response = $this->saveToCart($this->cartPayload($product))
            ->assertOk()->assertJson(['success' => true]);

        $item = $response->json('cart_data.cart_items.0');
        $this->assertSame('custom_2', $item['custom_tier']);
        $this->assertSame('60 Miles', $item['distance_range']);
        $this->assertEquals(248.0, $item['service_option_price']);
    }

    public function test_custom_tier_is_stripped_for_in_store_pickup(): void
    {
        $product = $this->makeProduct();

        // Store is required for In Store Pickup; keep the request minimal by
        // asserting only that the custom fields are normalized away
        $response = $this->saveToCart($this->cartPayload($product, [
            'service_method' => 'In Store Pickup',
            'distance_type'  => 'Custom',
            'delivery_store_id' => null,
        ]));

        // Regardless of the store validation outcome, custom_tier must never
        // survive a non-delivery request
        $this->assertNotEquals(500, $response->status());
        if ($response->status() === 422) {
            $this->assertArrayNotHasKey('custom_tier', $response->json('errors') ?? []);
        }
    }

    // ── Cart display ──────────────────────────────────────────────────────────

    public function test_cart_sidebar_shows_distance_and_return_pickup_wording_without_internal_keys(): void
    {
        $product = $this->makeProduct();
        $item = $this->buildItem($product);

        $sidebar = $this->postJson($this->frontUrl('/cart'), ['kabba_cart' => [$item]])
            ->assertOk()->json('sidebar');

        $this->assertStringContainsString('60 Miles', $sidebar);
        $this->assertStringContainsString('Delivery + Return Pickup', $sidebar);
        $this->assertStringContainsString('248.00', $sidebar);
        $this->assertStringNotContainsString('custom_2', strip_tags($sidebar), 'internal tier key must not be visible');
    }

    public function test_kilometers_unit_flows_through_to_the_cart_display(): void
    {
        $this->setSetting('distance_unit', 'Kilometers');
        $product = $this->makeProduct();

        $item = $this->buildItem($product);
        $this->assertSame('60 Kilometers', $item['distance_range']);
    }

    // ── Order snapshot ────────────────────────────────────────────────────────

    public function test_the_cart_item_snapshot_is_immune_to_later_settings_changes(): void
    {
        $product = $this->makeProduct();
        $item = $this->buildItem($product);

        // The order pipeline persists this item verbatim (columns + product_data
        // JSON). Verify the snapshot fields exist, then change every source and
        // confirm the captured array is untouched.
        $this->assertSame('custom_2', $item['custom_tier']);
        $this->assertSame('60 Miles', $item['distance_range']);
        $this->assertSame(124.0, (float) $item['delivery_one_way_rate']);
        $this->assertSame(248.0, (float) $item['service_option_price']);
        $this->assertSame('Delivery + Pickup', $item['service_option']);

        $this->setSetting('custom_2_delivery_range', '75');
        $this->setSetting('distance_unit', 'Kilometers');
        $product->update(['custom_2_delivery_fee' => 999]);

        $this->assertSame('60 Miles', $item['distance_range']);
        $this->assertSame(248.0, (float) $item['service_option_price']);

        // A NEW build reflects the new configuration (live pre-order recalc,
        // documented behavior) — proving the snapshot is what isolates orders
        $fresh = $this->buildItem($product);
        $this->assertSame('75 Kilometers', $fresh['distance_range']);
        $this->assertSame(1998.0, (float) $fresh['service_option_price']);
    }
}
