<?php

namespace Tests\Feature\ProductManagement;

use App\Models\Configurations\Setting;
use App\Models\Iam\Personnel\User;
use App\Models\ProductManagement\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Phase 2 — Product Setup integration for the six delivery tiers.
 *
 * Products copy global rates for their truck_fee_size_setting size.
 * Precedence on an individual product save: a submitted value always wins
 * (blank = NULL, 0 = intentionally free); only fields absent from the
 * request entirely fall back to the current global rates. A later Product
 * Settings save still overwrites linked products via the Phase 1
 * propagation rule.
 */
class ProductDeliveryRatesTest extends TestCase
{
    use RefreshDatabase;

    private const CUSTOM_COLUMNS = ['custom_1_delivery_fee', 'custom_2_delivery_fee', 'custom_3_delivery_fee', 'custom_4_delivery_fee'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\Configurations\SettingSeeder::class);

        $this->actingAs(User::create([
            'unique_id' => 'test-admin', 'employee_code' => '01',
            'first_name' => 'Admin', 'last_name' => 'User',
            'email' => 'admin@test.local', 'status' => 'Active',
        ]));
    }

    private function setGlobalFee(string $name, ?string $value): void
    {
        Setting::where('setting_type', 'Product Settings')
            ->where('setting_name', $name)
            ->update(['setting_value' => $value]);
    }

    private function rentalPayload(array $overrides = []): array
    {
        return array_merge([
            'product_name'           => 'Delivery Rates Product ' . Str::random(8),
            'product_type'           => 'Rental',
            'is_general_term_type'   => 1,
            'rental_daily'           => 100,
            'rental_weekend'         => 150,
            'rental_weekly'          => 400,
            'rental_monthly'         => 1600,
            'status'                 => 'Published',
            'in_store_pickup'        => 'Yes',
            'delivery_and_pickup'    => 'Yes',
            'truck_fee_size_setting' => 'Large',
            'standard_delivery_fee'  => '89',
            'extended_delivery_fee'  => '124',
            'custom_1_delivery_fee'  => '70',
            'custom_2_delivery_fee'  => '80',
            'custom_3_delivery_fee'  => '90',
            'custom_4_delivery_fee'  => '100',
        ], $overrides);
    }

    private function storeProduct(array $payload): Product
    {
        // Product store/update respond with JSON (AJAX form): 200 + success
        // on save, 422 + errors on validation failure.
        $this->post(route('admin.product-management.products.create'), $payload)
            ->assertOk()
            ->assertJson(['success' => true]);

        return Product::where('product_name', $payload['product_name'])->firstOrFail();
    }

    private function updatePayloadFor(Product $product, array $overrides = []): array
    {
        return array_merge($this->rentalPayload([
            'product_name' => $product->product_name,
            'slug'         => $product->slug,
        ]), $overrides);
    }

    // ── Product creation ──────────────────────────────────────────────────────

    public function test_create_saves_all_six_submitted_rates(): void
    {
        $product = $this->storeProduct($this->rentalPayload());

        $this->assertEquals(89.0, (float) $product->standard_delivery_fee);
        $this->assertEquals(124.0, (float) $product->extended_delivery_fee);
        $this->assertEquals(70.0, (float) $product->custom_1_delivery_fee);
        $this->assertEquals(80.0, (float) $product->custom_2_delivery_fee);
        $this->assertEquals(90.0, (float) $product->custom_3_delivery_fee);
        $this->assertEquals(100.0, (float) $product->custom_4_delivery_fee);
    }

    public function test_create_falls_back_to_global_rates_when_custom_fields_are_absent(): void
    {
        $this->setGlobalFee('large_custom_1_delivery_fee', '70');
        $this->setGlobalFee('large_custom_3_delivery_fee', '0');

        $payload = $this->rentalPayload();
        unset(
            $payload['custom_1_delivery_fee'], $payload['custom_2_delivery_fee'],
            $payload['custom_3_delivery_fee'], $payload['custom_4_delivery_fee'],
        );
        $product = $this->storeProduct($payload);

        $this->assertEquals(70.0, (float) $product->custom_1_delivery_fee);
        $this->assertNull($product->custom_2_delivery_fee, 'null global rate stays null');
        $this->assertNotNull($product->custom_3_delivery_fee, 'zero global rate must not become null');
        $this->assertEquals(0.0, (float) $product->custom_3_delivery_fee);
        $this->assertNull($product->custom_4_delivery_fee);
    }

    public function test_create_preserves_submitted_blank_custom_values(): void
    {
        // Globals are configured, but the employee intentionally clears the fields
        $this->setGlobalFee('large_custom_1_delivery_fee', '70');
        $this->setGlobalFee('large_custom_2_delivery_fee', '80');

        $product = $this->storeProduct($this->rentalPayload([
            'custom_1_delivery_fee' => '',
            'custom_2_delivery_fee' => '',
            'custom_3_delivery_fee' => '',
            'custom_4_delivery_fee' => '',
        ]));

        foreach (self::CUSTOM_COLUMNS as $column) {
            $this->assertNull($product->{$column}, "{$column} must honor the submitted blank");
        }
    }

    public function test_create_saves_manual_values_different_from_the_globals(): void
    {
        $this->setGlobalFee('large_custom_2_delivery_fee', '124');

        $product = $this->storeProduct($this->rentalPayload(['custom_2_delivery_fee' => '139']));

        $this->assertEquals(139.0, (float) $product->custom_2_delivery_fee);
        $this->assertSame('124', Setting::where('setting_type', 'Product Settings')
            ->where('setting_name', 'large_custom_2_delivery_fee')->value('setting_value'), 'global rate unchanged');
    }

    public function test_retail_products_null_all_delivery_rate_columns(): void
    {
        $product = $this->storeProduct([
            'product_name' => 'Retail Delivery Rates ' . Str::random(8),
            'product_type' => 'Retail',
            'retail_price' => 50,
            'status'       => 'Published',
        ]);

        $this->assertNull($product->standard_delivery_fee);
        $this->assertNull($product->extended_delivery_fee);
        foreach (self::CUSTOM_COLUMNS as $column) {
            $this->assertNull($product->{$column});
        }
    }

    // ── Product editing ───────────────────────────────────────────────────────

    public function test_edit_page_loads_stored_custom_values(): void
    {
        $product = $this->storeProduct($this->rentalPayload(['custom_2_delivery_fee' => '139']));

        $html = $this->get(route('admin.product-management.products.edit', $product->unique_id))
            ->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/name="custom_2_delivery_fee"[^>]*value="139(\.00)?"/',
            $html,
            'saved manual value must display on edit',
        );
    }

    public function test_update_preserves_rates_when_only_unrelated_fields_change(): void
    {
        $product = $this->storeProduct($this->rentalPayload(['custom_2_delivery_fee' => '139']));

        // Ordinary edit: form resubmits the displayed values plus a name change
        $this->put(
            route('admin.product-management.products.edit', $product->unique_id),
            $this->updatePayloadFor($product, [
                'product_name'          => $product->product_name . ' Renamed',
                'custom_2_delivery_fee' => '139',
            ]),
        )->assertOk()->assertJson(['success' => true]);

        $product->refresh();
        $this->assertEquals(139.0, (float) $product->custom_2_delivery_fee);
        $this->assertEquals(70.0, (float) $product->custom_1_delivery_fee);
        $this->assertStringEndsWith('Renamed', $product->product_name);
    }

    public function test_update_saves_manual_edits_null_zero_and_decimals(): void
    {
        $product = $this->storeProduct($this->rentalPayload());

        $this->put(
            route('admin.product-management.products.edit', $product->unique_id),
            $this->updatePayloadFor($product, [
                'custom_1_delivery_fee' => '55.50',
                'custom_2_delivery_fee' => '',
                'custom_3_delivery_fee' => '0',
                'custom_4_delivery_fee' => '124.75',
            ]),
        )->assertOk()->assertJson(['success' => true]);

        $product->refresh();
        $this->assertEquals(55.5, (float) $product->custom_1_delivery_fee);
        $this->assertNull($product->custom_2_delivery_fee, 'clearing a field saves NULL');
        $this->assertNotNull($product->custom_3_delivery_fee, 'zero stays zero');
        $this->assertEquals(0.0, (float) $product->custom_3_delivery_fee);
        $this->assertEquals(124.75, (float) $product->custom_4_delivery_fee);
    }

    public function test_update_falls_back_to_globals_for_absent_custom_fields(): void
    {
        $this->setGlobalFee('medium_custom_1_delivery_fee', '61');

        $product = $this->storeProduct($this->rentalPayload());

        $payload = $this->updatePayloadFor($product, ['truck_fee_size_setting' => 'Medium']);
        unset(
            $payload['custom_1_delivery_fee'], $payload['custom_2_delivery_fee'],
            $payload['custom_3_delivery_fee'], $payload['custom_4_delivery_fee'],
        );

        $this->put(route('admin.product-management.products.edit', $product->unique_id), $payload)
            ->assertOk()->assertJson(['success' => true]);

        $product->refresh();
        $this->assertEquals(61.0, (float) $product->custom_1_delivery_fee, 'absent fields repopulate from the newly selected size');
        $this->assertNull($product->custom_2_delivery_fee);
    }

    // ── Global interaction ────────────────────────────────────────────────────

    public function test_a_later_global_save_overwrites_manual_product_values(): void
    {
        $large = $this->storeProduct($this->rentalPayload(['custom_2_delivery_fee' => '139']));
        $small = $this->storeProduct($this->rentalPayload([
            'truck_fee_size_setting' => 'Small',
            'custom_2_delivery_fee'  => '33',
        ]));

        $this->post(route('admin.configurations.save-product-settings'), [
            'large_custom_2_delivery_fee' => '149',
        ])->assertSessionHasNoErrors();

        $this->assertEquals(149.0, (float) $large->refresh()->custom_2_delivery_fee, 'global always wins');
        $this->assertEquals(33.0, (float) $small->refresh()->custom_2_delivery_fee, 'unrelated size untouched');
        $this->assertEquals(70.0, (float) $large->custom_1_delivery_fee, 'unrelated tier untouched');
    }

    // ── Configured-delivery-fee rule: all six tiers count, non-null (not >0) ──

    public function test_a_product_with_only_a_custom_rate_passes_the_fee_rule(): void
    {
        $product = $this->storeProduct($this->rentalPayload([
            'standard_delivery_fee' => '',
            'extended_delivery_fee' => '',
            'custom_1_delivery_fee' => '70',
            'custom_2_delivery_fee' => '',
            'custom_3_delivery_fee' => '',
            'custom_4_delivery_fee' => '',
        ]));

        $this->assertNull($product->standard_delivery_fee);
        $this->assertEquals(70.0, (float) $product->custom_1_delivery_fee);
    }

    public function test_a_product_with_only_a_zero_dollar_custom_rate_passes_the_fee_rule(): void
    {
        $product = $this->storeProduct($this->rentalPayload([
            'standard_delivery_fee' => '',
            'extended_delivery_fee' => '',
            'custom_1_delivery_fee' => '',
            'custom_2_delivery_fee' => '0',
            'custom_3_delivery_fee' => '',
            'custom_4_delivery_fee' => '',
        ]));

        $this->assertNotNull($product->custom_2_delivery_fee, 'zero is configured, not blank');
        $this->assertEquals(0.0, (float) $product->custom_2_delivery_fee);
    }

    public function test_all_six_rates_blank_fails_the_fee_rule_when_delivery_is_enabled(): void
    {
        $this->post(route('admin.product-management.products.create'), $this->rentalPayload([
            'standard_delivery_fee' => '',
            'extended_delivery_fee' => '',
            'custom_1_delivery_fee' => '',
            'custom_2_delivery_fee' => '',
            'custom_3_delivery_fee' => '',
            'custom_4_delivery_fee' => '',
        ]))->assertStatus(422)->assertJsonValidationErrors('standard_delivery_fee');
    }

    // ── Validation ────────────────────────────────────────────────────────────

    public function test_negative_and_malformed_custom_rates_are_rejected(): void
    {
        $this->post(route('admin.product-management.products.create'), $this->rentalPayload([
            'custom_1_delivery_fee' => '-5',
        ]))->assertStatus(422)->assertJsonValidationErrors('custom_1_delivery_fee');

        $this->post(route('admin.product-management.products.create'), $this->rentalPayload([
            'custom_2_delivery_fee' => 'abc',
        ]))->assertStatus(422)->assertJsonValidationErrors('custom_2_delivery_fee');
    }

    // ── Rendering ─────────────────────────────────────────────────────────────

    public function test_create_form_renders_all_six_fields_exactly_once(): void
    {
        $html = $this->get(route('admin.product-management.products.create'))->assertOk()->getContent();

        foreach (['standard_delivery_fee', 'extended_delivery_fee', ...self::CUSTOM_COLUMNS] as $field) {
            $this->assertSame(1, preg_match_all('/<input[^>]*name="' . $field . '"/', $html), "{$field} input must appear exactly once");
        }
        $this->assertStringContainsString('Custom 1', $html);
        $this->assertStringContainsString('Custom 4', $html);
        $this->assertStringContainsString('one-way rates', $html);
    }

    public function test_edit_form_renders_all_six_fields_exactly_once(): void
    {
        $product = $this->storeProduct($this->rentalPayload());

        $html = $this->get(route('admin.product-management.products.edit', $product->unique_id))
            ->assertOk()->getContent();

        foreach (['standard_delivery_fee', 'extended_delivery_fee', ...self::CUSTOM_COLUMNS] as $field) {
            $this->assertSame(1, preg_match_all('/<input[^>]*name="' . $field . '"/', $html), "{$field} input must appear exactly once");
        }
    }
}
