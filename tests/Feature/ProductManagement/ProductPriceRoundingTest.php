<?php

namespace Tests\Feature\ProductManagement;

use App\Helpers\RentalPriceHelper;
use App\Models\Configurations\Setting;
use App\Models\Iam\Personnel\User;
use App\Models\ProductManagement\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Smart Rental Price Rounding — Product Setup layer.
 *
 * The product form's browser calculation mirrors RentalPriceHelper via a
 * config injected into the page (allowedPriceEndings / hundredEntryThreshold),
 * but the SERVER is authoritative: each derived period carries a
 * {period}_price_source hidden field. 'auto' makes the save path discard the
 * browser-submitted amount and recalculate it through RentalPriceHelper;
 * 'manual' — and any legacy request without the field — persists the
 * submitted value verbatim, which is what preserves manual overrides.
 * Merely opening a product must never change its saved prices.
 */
class ProductPriceRoundingTest extends TestCase
{
    use RefreshDatabase;

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

    private function setMultiplierSetting(string $name, ?string $value): void
    {
        Setting::where('setting_type', 'Price Rate Multiplier Settings')
            ->where('setting_name', $name)
            ->update(['setting_value' => $value]);
    }

    private function rentalPayload(array $overrides = []): array
    {
        return array_merge([
            'product_name'         => 'Rounding Product ' . Str::random(8),
            'product_type'         => 'Rental',
            'is_general_term_type' => 1,
            'rental_daily'         => '424.00',
            'rental_weekend'       => '654.00',
            'rental_weekly'        => '1337.00',
            'rental_monthly'       => '3997.00',
            'status'               => 'Published',
            'in_store_pickup'      => 'Yes',
        ], $overrides);
    }

    private function storeProduct(array $payload): Product
    {
        $this->post(route('admin.product-management.products.create'), $payload)
            ->assertOk()
            ->assertJson(['success' => true]);

        return Product::where('product_name', $payload['product_name'])->firstOrFail();
    }

    // ── Browser mirror configuration ──────────────────────────────────────────

    public function test_create_page_injects_rounding_config_for_the_js_mirror(): void
    {
        $html = $this->get(route('admin.product-management.products.create'))
            ->assertOk()->getContent();

        $this->assertStringContainsString('const allowedPriceEndings = [4,7]', $html);
        $this->assertStringContainsString('const hundredEntryThreshold = 10', $html);
        $this->assertStringContainsString('function smartRoundDollars', $html);
    }

    public function test_edit_page_injects_updated_settings_after_a_change(): void
    {
        $product = $this->storeProduct($this->rentalPayload());

        $this->setMultiplierSetting(RentalPriceHelper::ENDINGS_SETTING, '9');
        $this->setMultiplierSetting(RentalPriceHelper::THRESHOLD_SETTING, '25');

        $html = $this->get(route('admin.product-management.products.edit', $product->unique_id))
            ->assertOk()->getContent();

        $this->assertStringContainsString('const allowedPriceEndings = [9]', $html);
        $this->assertStringContainsString('const hundredEntryThreshold = 25', $html);
    }

    public function test_blank_endings_disable_smart_rounding_in_the_page_config(): void
    {
        $this->setMultiplierSetting(RentalPriceHelper::ENDINGS_SETTING, '');

        $html = $this->get(route('admin.product-management.products.create'))
            ->assertOk()->getContent();

        $this->assertStringContainsString('const allowedPriceEndings = []', $html);
    }

    // ── Saved prices are never silently recalculated ──────────────────────────

    public function test_opening_the_edit_page_does_not_change_saved_prices(): void
    {
        // Prices deliberately do NOT match what current settings would derive.
        $product = $this->storeProduct($this->rentalPayload([
            'rental_weekend' => '650.00',
            'rental_weekly'  => '1300.00',
            'rental_monthly' => '4000.00',
        ]));

        $this->get(route('admin.product-management.products.edit', $product->unique_id))->assertOk();

        $product->refresh();
        $this->assertSame('650.00', (string) $product->rental_weekend);
        $this->assertSame('1300.00', (string) $product->rental_weekly);
        $this->assertSame('4000.00', (string) $product->rental_monthly);
    }

    public function test_manual_overrides_are_stored_verbatim(): void
    {
        // 424 × 1.54 would smart-round to 654; the admin typed 649 instead.
        $product = $this->storeProduct($this->rentalPayload([
            'rental_weekend' => '649.00',
        ]));

        $this->assertSame('649.00', (string) $product->rental_weekend);
    }

    public function test_changing_global_settings_does_not_rewrite_existing_products(): void
    {
        $product = $this->storeProduct($this->rentalPayload());
        $before = [$product->rental_weekend, $product->rental_weekly, $product->rental_monthly];

        $this->post(route('admin.configurations.save-product-settings'), [
            'price_endings' => ['9', '', ''],
            'hundred_entry_threshold' => '50',
            'weekend_multiplier' => '2.00',
        ])->assertRedirect();

        $product->refresh();
        $this->assertSame($before, [$product->rental_weekend, $product->rental_weekly, $product->rental_monthly]);
    }

    public function test_retail_products_are_unaffected(): void
    {
        $this->post(route('admin.product-management.products.create'), [
            'product_name' => 'Retail Rounding ' . Str::random(8),
            'product_type' => 'Retail',
            'retail_price' => '99.99',
            'status'       => 'Published',
            // Even a bogus auto claim changes nothing on a retail product
            'weekend_price_source' => 'auto',
        ])->assertOk()->assertJson(['success' => true]);

        $product = Product::where('product_type', 'Retail')->latest('id')->firstOrFail();
        $this->assertNull($product->rental_weekend);
        $this->assertNull($product->rental_weekly);
        $this->assertNull($product->rental_monthly);
    }

    // ── Server authority: 'auto' fields are recalculated, never trusted ──────

    private function setAllMultipliers(): void
    {
        $this->setMultiplierSetting('weekend_multiplier', '1.54');
        $this->setMultiplierSetting('weekly_multiplier', '3.15');
        $this->setMultiplierSetting('monthly_multiplier', '9.45');
    }

    public function test_tampered_auto_weekend_value_is_replaced_by_the_server_calculation(): void
    {
        $this->setAllMultipliers();

        // 424 × 1.54 = 652.96 → smart-rounds to 654; the browser lies with 999.99
        $product = $this->storeProduct($this->rentalPayload([
            'rental_weekend' => '999.99',
            'weekend_price_source' => 'auto',
        ]));

        $this->assertSame('654.00', (string) $product->rental_weekend);
    }

    public function test_tampered_auto_weekly_and_monthly_values_are_also_replaced(): void
    {
        $this->setAllMultipliers();

        $product = $this->storeProduct($this->rentalPayload([
            'rental_weekly' => '1.00',
            'rental_monthly' => '99999',
            'weekly_price_source' => 'auto',
            'monthly_price_source' => 'auto',
        ]));

        $this->assertSame('1337.00', (string) $product->rental_weekly);   // 1335.60 → 1337
        $this->assertSame('3997.00', (string) $product->rental_monthly);  // 4006.80 → entry zone → 3997
    }

    public function test_update_path_is_equally_authoritative(): void
    {
        $this->setAllMultipliers();
        $product = $this->storeProduct($this->rentalPayload());

        $this->put(
            route('admin.product-management.products.edit', $product->unique_id),
            array_merge($this->rentalPayload([
                'product_name' => $product->product_name,
                'slug' => $product->slug,
                'rental_weekend' => '888.88',
                'weekend_price_source' => 'auto',
            ])),
        )->assertOk()->assertJson(['success' => true]);

        $this->assertSame('654.00', (string) $product->refresh()->rental_weekend);
    }

    public function test_smart_rounding_off_still_uses_the_authoritative_raw_calculation(): void
    {
        $this->setAllMultipliers();
        $this->setMultiplierSetting(RentalPriceHelper::ENDINGS_SETTING, '');

        $product = $this->storeProduct($this->rentalPayload([
            'rental_weekend' => '777.77',
            'weekend_price_source' => 'auto',
        ]));

        // Legacy behavior: raw multiplier price to the cent — server-computed.
        $this->assertSame('652.96', (string) $product->rental_weekend);
    }

    // ── Manual overrides: independent per period ──────────────────────────────

    public function test_weekend_can_be_manual_while_weekly_and_monthly_are_server_calculated(): void
    {
        $this->setAllMultipliers();

        $product = $this->storeProduct($this->rentalPayload([
            'rental_weekend' => '649.00',
            'weekend_price_source' => 'manual',
            'rental_weekly' => '5.00',
            'weekly_price_source' => 'auto',
            'rental_monthly' => '5.00',
            'monthly_price_source' => 'auto',
        ]));

        $this->assertSame('649.00', (string) $product->rental_weekend);
        $this->assertSame('1337.00', (string) $product->rental_weekly);
        $this->assertSame('3997.00', (string) $product->rental_monthly);
    }

    public function test_weekly_can_be_manual_independently(): void
    {
        $this->setAllMultipliers();

        $product = $this->storeProduct($this->rentalPayload([
            'rental_weekend' => '5.00',
            'weekend_price_source' => 'auto',
            'rental_weekly' => '1250.00',
            'weekly_price_source' => 'manual',
            'rental_monthly' => '5.00',
            'monthly_price_source' => 'auto',
        ]));

        $this->assertSame('654.00', (string) $product->rental_weekend);
        $this->assertSame('1250.00', (string) $product->rental_weekly);
        $this->assertSame('3997.00', (string) $product->rental_monthly);
    }

    public function test_manual_override_equal_to_the_calculated_value_is_still_manual(): void
    {
        $this->setAllMultipliers();

        // 654.00 happens to equal the calculation; the manual flag decides,
        // never a value comparison.
        $product = $this->storeProduct($this->rentalPayload([
            'rental_weekend' => '654.00',
            'weekend_price_source' => 'manual',
        ]));

        $this->assertSame('654.00', (string) $product->rental_weekend);
    }

    // ── Disabled calculation and compatibility ────────────────────────────────

    public function test_blank_multiplier_preserves_the_submitted_value_even_when_marked_auto(): void
    {
        // weekend_multiplier stays NULL (SettingSeeder default); an auto claim
        // cannot be honored, so the submitted value persists as manual would.
        $this->setMultiplierSetting('weekly_multiplier', '3.15');

        $product = $this->storeProduct($this->rentalPayload([
            'rental_weekend' => '650.00',
            'weekend_price_source' => 'auto',
            'rental_weekly' => '2.00',
            'weekly_price_source' => 'auto',
        ]));

        $this->assertSame('650.00', (string) $product->rental_weekend);  // disabled period untouched
        $this->assertSame('1337.00', (string) $product->rental_weekly);  // configured period still authoritative
    }

    public function test_unexpected_source_state_is_rejected_by_validation(): void
    {
        $this->post(route('admin.product-management.products.create'), $this->rentalPayload([
            'weekend_price_source' => 'hacked',
        ]))->assertStatus(422)->assertJsonValidationErrors('weekend_price_source');
    }

    public function test_legacy_requests_without_source_fields_persist_values_verbatim(): void
    {
        $this->setAllMultipliers();

        // No *_price_source fields at all — an older form or API caller.
        $product = $this->storeProduct($this->rentalPayload([
            'rental_weekend' => '649.00',
        ]));

        $this->assertSame('649.00', (string) $product->rental_weekend);
    }

    public function test_saving_an_unchanged_product_preserves_all_prices_exactly(): void
    {
        $this->setAllMultipliers();
        $product = $this->storeProduct($this->rentalPayload([
            'rental_weekend' => '649.00', // manual override on file
        ]));

        // Re-save as the edit form would: current values, manual sources.
        $this->put(
            route('admin.product-management.products.edit', $product->unique_id),
            $this->rentalPayload([
                'product_name' => $product->product_name,
                'slug' => $product->slug,
                'rental_weekend' => '649.00',
                'weekend_price_source' => 'manual',
                'weekly_price_source' => 'manual',
                'monthly_price_source' => 'manual',
            ]),
        )->assertOk()->assertJson(['success' => true]);

        $product->refresh();
        $this->assertSame('649.00', (string) $product->rental_weekend);
        $this->assertSame('1337.00', (string) $product->rental_weekly);
        $this->assertSame('3997.00', (string) $product->rental_monthly);
    }

    public function test_form_renders_hidden_source_fields_defaulting_to_manual(): void
    {
        $html = $this->get(route('admin.product-management.products.create'))
            ->assertOk()->getContent();

        foreach (['weekend', 'weekly', 'monthly'] as $period) {
            $this->assertMatchesRegularExpression(
                '/<input type="hidden" name="' . $period . '_price_source"[^>]*value="manual"/',
                $html,
            );
        }
    }
}
