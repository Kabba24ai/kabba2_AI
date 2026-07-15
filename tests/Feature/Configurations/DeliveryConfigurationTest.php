<?php

namespace Tests\Feature\Configurations;

use App\Helpers\DeliveryTierHelper;
use App\Models\Configurations\Setting;
use App\Models\Iam\Personnel\User;
use App\Models\ProductManagement\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Phase 1 — Delivery Configuration Foundation.
 *
 * Six administrative delivery tiers (Standard, Extended, Custom 1–4), each
 * with a distance value and a per-equipment-size one-way rate. System
 * Configuration is the authoritative global source: saving a global rate
 * propagates (copies) it onto every product whose truck_fee_size_setting
 * matches, overwriting prior product-level values. Blank stays NULL (tier
 * not configured); an explicit 0 stays 0.00 (intentionally free).
 */
class DeliveryConfigurationTest extends TestCase
{
    use RefreshDatabase;

    private const SIZES = ['small', 'medium', 'large', 'x_large', '2x_large', 'commercial'];
    private const CUSTOM_TIERS = ['custom_1', 'custom_2', 'custom_3', 'custom_4'];

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

    private function saveSettings(array $payload): \Illuminate\Testing\TestResponse
    {
        return $this->post(route('admin.configurations.save-product-settings'), $payload);
    }

    private function setting(string $name): ?string
    {
        return Setting::where('setting_type', 'Product Settings')
            ->where('setting_name', $name)->value('setting_value');
    }

    private function makeProduct(string $size, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'unique_id'              => Str::uuid()->toString(),
            'product_name'           => "Test {$size} " . Str::random(6),
            'slug'                   => 'test-' . strtolower($size) . '-' . Str::lower(Str::random(6)),
            'product_type'           => 'Rental',
            'status'                 => 'Published',
            'truck_fee_size_setting' => $size,
        ], $overrides));
    }

    // ── Configuration persistence ─────────────────────────────────────────────

    public function test_standard_and_extended_values_continue_saving(): void
    {
        $this->saveSettings([
            'standard_delivery_range'    => '15',
            'extended_delivery_range'    => '30',
            'include_extended_range'     => '1',
            'distance_unit'              => 'Miles',
            'large_standard_delivery_fee' => '89',
            'large_extended_delivery_fee' => '124',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('15', $this->setting('standard_delivery_range'));
        $this->assertSame('30', $this->setting('extended_delivery_range'));
        $this->assertSame('Miles', $this->setting('distance_unit'));
        $this->assertSame('89', $this->setting('large_standard_delivery_fee'));
        $this->assertSame('124', $this->setting('large_extended_delivery_fee'));
    }

    public function test_custom_distance_values_save_and_reload(): void
    {
        $this->saveSettings([
            'custom_1_delivery_range' => '45',
            'custom_2_delivery_range' => '60',
            'custom_3_delivery_range' => '75',
            'custom_4_delivery_range' => '100',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('45', $this->setting('custom_1_delivery_range'));
        $this->assertSame('60', $this->setting('custom_2_delivery_range'));
        $this->assertSame('75', $this->setting('custom_3_delivery_range'));
        $this->assertSame('100', $this->setting('custom_4_delivery_range'));

        // Values reload into the configuration page
        $html = $this->get(route('admin.configurations.index'))->assertOk()->getContent();
        $this->assertStringContainsString('custom_2_delivery_range', $html);
        $this->assertStringContainsString('value="60"', $html);
    }

    public function test_custom_fees_save_and_reload_for_every_equipment_size(): void
    {
        $payload = [];
        $expected = [];
        $amount = 10;

        foreach (self::SIZES as $size) {
            foreach (self::CUSTOM_TIERS as $tier) {
                $key = "{$size}_{$tier}_delivery_fee";
                $payload[$key] = (string) $amount;
                $expected[$key] = (string) $amount;
                $amount += 5;
            }
        }

        $this->saveSettings($payload)->assertRedirect()->assertSessionHasNoErrors();

        foreach ($expected as $key => $value) {
            $this->assertSame($value, $this->setting($key), "setting {$key}");
        }
    }

    public function test_distance_unit_remains_limited_to_miles_and_kilometers(): void
    {
        $this->saveSettings(['distance_unit' => 'Furlongs'])
            ->assertSessionHasErrors('distance_unit');

        $this->saveSettings(['distance_unit' => 'Kilometers'])->assertSessionHasNoErrors();
        $this->assertSame('Kilometers', $this->setting('distance_unit'));
    }

    public function test_blank_custom_values_remain_null(): void
    {
        $this->saveSettings([
            'custom_3_delivery_range'      => '',
            'medium_custom_3_delivery_fee' => '',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertNull($this->setting('custom_3_delivery_range'));
        $this->assertNull($this->setting('medium_custom_3_delivery_fee'));
    }

    public function test_zero_dollar_custom_fees_remain_zero_and_are_not_converted_to_null(): void
    {
        $product = $this->makeProduct('Small');

        $this->saveSettings(['small_custom_1_delivery_fee' => '0'])
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('0', $this->setting('small_custom_1_delivery_fee'));

        $product->refresh();
        $this->assertNotNull($product->custom_1_delivery_fee, 'an intentional zero must not become NULL');
        $this->assertEquals(0.0, (float) $product->custom_1_delivery_fee);
    }

    // ── Existing-data safety ──────────────────────────────────────────────────

    public function test_settings_migration_is_idempotent_and_preserves_existing_values(): void
    {
        Setting::where('setting_type', 'Product Settings')
            ->where('setting_name', 'standard_delivery_range')->update(['setting_value' => '15']);
        Setting::where('setting_type', 'Product Settings')
            ->where('setting_name', 'custom_2_delivery_range')->update(['setting_value' => '60']);

        $migration = include base_path('database/migrations/configurations/2026_07_15_000001_add_custom_delivery_tier_settings.php');
        $migration->up();

        $this->assertSame('15', $this->setting('standard_delivery_range'));
        $this->assertSame('60', $this->setting('custom_2_delivery_range'));

        // All 28 new keys exist exactly once
        foreach (self::CUSTOM_TIERS as $tier) {
            $this->assertSame(1, Setting::where('setting_type', 'Product Settings')
                ->where('setting_name', "{$tier}_delivery_range")->count());

            foreach (self::SIZES as $size) {
                $this->assertSame(1, Setting::where('setting_type', 'Product Settings')
                    ->where('setting_name', "{$size}_{$tier}_delivery_fee")->count());
            }
        }
    }

    public function test_existing_product_pricing_is_not_erased_by_unrelated_saves(): void
    {
        $product = $this->makeProduct('Large', [
            'standard_delivery_fee' => 89,
            'extended_delivery_fee' => 124,
        ]);

        // A save that submits no delivery fee fields leaves products untouched
        $this->saveSettings(['sales_tax' => '9.75'])->assertSessionHasNoErrors();

        $product->refresh();
        $this->assertEquals(89.0, (float) $product->standard_delivery_fee);
        $this->assertEquals(124.0, (float) $product->extended_delivery_fee);
        $this->assertNull($product->custom_1_delivery_fee);
    }

    // ── Global propagation: global setting always wins ────────────────────────

    public function test_changing_a_global_standard_rate_updates_linked_products(): void
    {
        $large = $this->makeProduct('Large', ['standard_delivery_fee' => 10]);

        $this->saveSettings(['large_standard_delivery_fee' => '95'])->assertSessionHasNoErrors();

        $this->assertEquals(95.0, (float) $large->refresh()->standard_delivery_fee);
    }

    public function test_changing_a_global_extended_rate_updates_linked_products(): void
    {
        $medium = $this->makeProduct('Medium', ['extended_delivery_fee' => 10]);

        $this->saveSettings(['medium_extended_delivery_fee' => '99'])->assertSessionHasNoErrors();

        $this->assertEquals(99.0, (float) $medium->refresh()->extended_delivery_fee);
    }

    public function test_changing_each_custom_rate_updates_linked_products(): void
    {
        $product = $this->makeProduct('X-Large');

        $this->saveSettings([
            'x_large_custom_1_delivery_fee' => '101',
            'x_large_custom_2_delivery_fee' => '102',
            'x_large_custom_3_delivery_fee' => '103',
            'x_large_custom_4_delivery_fee' => '104',
        ])->assertSessionHasNoErrors();

        $product->refresh();
        $this->assertEquals(101.0, (float) $product->custom_1_delivery_fee);
        $this->assertEquals(102.0, (float) $product->custom_2_delivery_fee);
        $this->assertEquals(103.0, (float) $product->custom_3_delivery_fee);
        $this->assertEquals(104.0, (float) $product->custom_4_delivery_fee);
    }

    public function test_a_manual_product_value_is_overwritten_by_a_later_global_update(): void
    {
        // Temporary manual edits are not protected overrides
        $product = $this->makeProduct('Commercial', ['custom_2_delivery_fee' => 50]);

        $this->saveSettings(['commercial_custom_2_delivery_fee' => '124'])->assertSessionHasNoErrors();

        $this->assertEquals(124.0, (float) $product->refresh()->custom_2_delivery_fee);
    }

    public function test_unrelated_products_and_tiers_are_not_changed_by_propagation(): void
    {
        $large = $this->makeProduct('Large', ['standard_delivery_fee' => 89, 'custom_1_delivery_fee' => 70]);
        $small = $this->makeProduct('Small', ['standard_delivery_fee' => 49, 'custom_1_delivery_fee' => 30]);

        $this->saveSettings(['large_custom_2_delivery_fee' => '111'])->assertSessionHasNoErrors();

        $large->refresh();
        $small->refresh();

        // Same product, other tiers untouched
        $this->assertEquals(89.0, (float) $large->standard_delivery_fee);
        $this->assertEquals(70.0, (float) $large->custom_1_delivery_fee);
        $this->assertEquals(111.0, (float) $large->custom_2_delivery_fee);

        // Other size completely untouched
        $this->assertEquals(49.0, (float) $small->standard_delivery_fee);
        $this->assertEquals(30.0, (float) $small->custom_1_delivery_fee);
        $this->assertNull($small->custom_2_delivery_fee);
    }

    // ── New/updated products copy the current global Custom rates ─────────────

    public function test_helper_copies_current_custom_rates_for_a_size(): void
    {
        $this->saveSettings([
            'large_custom_1_delivery_fee' => '70',
            'large_custom_3_delivery_fee' => '0',
        ])->assertSessionHasNoErrors();

        $columns = DeliveryTierHelper::customFeeColumnsForSize('Large');

        $this->assertSame(70.0, $columns['custom_1_delivery_fee']);
        $this->assertNull($columns['custom_2_delivery_fee'], 'blank global rate stays NULL');
        $this->assertSame(0.0, $columns['custom_3_delivery_fee'], 'explicit zero stays 0.00');
        $this->assertNull($columns['custom_4_delivery_fee']);
    }

    public function test_helper_returns_nulls_for_a_missing_or_unknown_size(): void
    {
        $this->assertSame(
            [
                'custom_1_delivery_fee' => null,
                'custom_2_delivery_fee' => null,
                'custom_3_delivery_fee' => null,
                'custom_4_delivery_fee' => null,
            ],
            DeliveryTierHelper::customFeeColumnsForSize(null),
        );

        $this->assertSame(
            array_fill_keys(['custom_1_delivery_fee', 'custom_2_delivery_fee', 'custom_3_delivery_fee', 'custom_4_delivery_fee'], null),
            DeliveryTierHelper::customFeeColumnsForSize('Gigantic'),
        );
    }

    // ── Validation ─────────────────────────────────────────────────────────────

    public function test_negative_values_follow_the_existing_delivery_fee_rules(): void
    {
        $this->saveSettings(['small_custom_1_delivery_fee' => '-5'])
            ->assertSessionHasErrors('small_custom_1_delivery_fee');

        $this->saveSettings(['custom_1_delivery_range' => '-10'])
            ->assertSessionHasErrors('custom_1_delivery_range');
    }

    public function test_malformed_numeric_input_is_rejected(): void
    {
        $this->saveSettings(['medium_custom_2_delivery_fee' => 'abc'])
            ->assertSessionHasErrors('medium_custom_2_delivery_fee');

        $this->saveSettings(['custom_2_delivery_range' => 'ten miles'])
            ->assertSessionHasErrors('custom_2_delivery_range');
    }

    public function test_nullable_fields_can_be_cleared_safely(): void
    {
        $product = $this->makeProduct('Small');

        $this->saveSettings([
            'custom_4_delivery_range'     => '90',
            'small_custom_4_delivery_fee' => '40',
        ])->assertSessionHasNoErrors();
        $this->assertEquals(40.0, (float) $product->refresh()->custom_4_delivery_fee);

        $this->saveSettings([
            'custom_4_delivery_range'     => '',
            'small_custom_4_delivery_fee' => '',
        ])->assertSessionHasNoErrors();

        $this->assertNull($this->setting('custom_4_delivery_range'));
        $this->assertNull($this->setting('small_custom_4_delivery_fee'));
        $this->assertNull($product->refresh()->custom_4_delivery_fee);
    }

    // ── Unified configuration card ────────────────────────────────────────────

    public function test_configuration_page_renders_the_unified_card(): void
    {
        $html = $this->get(route('admin.configurations.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Delivery Range &amp; Delivery Fees', $html);
        $this->assertStringContainsString('Equipment Size', $html);

        // All six tier inputs exist for the range row and the fee matrix
        foreach (self::CUSTOM_TIERS as $tier) {
            $this->assertStringContainsString("id=\"{$tier}_delivery_range\"", $html);
        }
        foreach (self::SIZES as $size) {
            foreach (array_merge(['standard', 'extended'], self::CUSTOM_TIERS) as $tier) {
                $this->assertStringContainsString("id=\"{$size}_{$tier}_delivery_fee\"", $html);
            }
        }

        // The old standalone card is gone: the fee inputs appear exactly once
        $this->assertSame(1, substr_count($html, 'id="small_standard_delivery_fee"'));
    }
}
