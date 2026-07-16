<?php

namespace Tests\Feature\Configurations;

use App\Helpers\RentalPriceHelper;
use App\Models\Configurations\Setting;
use App\Models\Iam\Personnel\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smart Rental Price Rounding — settings layer.
 *
 * The Configurations page posts up to three individual ending digits
 * (price_endings[]) plus a whole-dollar hundred-entry threshold; the
 * digits persist as one comma-separated 'allowed_price_endings' setting in
 * 'Price Rate Multiplier Settings'. Clearing every ending turns smart
 * rounding off. The migration seeds the initial configuration (4,7 / $10).
 */
class SmartPriceRoundingSettingsTest extends TestCase
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

    private function saveSettings(array $payload): \Illuminate\Testing\TestResponse
    {
        return $this->post(route('admin.configurations.save-product-settings'), $payload);
    }

    private function setting(string $name): ?string
    {
        return Setting::where('setting_type', 'Price Rate Multiplier Settings')
            ->where('setting_name', $name)->value('setting_value');
    }

    // ── Seeded defaults ───────────────────────────────────────────────────────

    public function test_migration_seeds_initial_endings_and_threshold(): void
    {
        $this->assertSame('4,7', $this->setting(RentalPriceHelper::ENDINGS_SETTING));
        $this->assertSame('10', $this->setting(RentalPriceHelper::THRESHOLD_SETTING));
    }

    public function test_configurations_page_renders_rounding_fields_with_current_values(): void
    {
        $html = $this->get(route('admin.configurations.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Allowed Price Endings', $html);
        $this->assertStringContainsString('Hundred-Entry Threshold', $html);
        $this->assertStringContainsString('price_endings[0]', $html);
        $this->assertStringContainsString('hundred_entry_threshold', $html);
    }

    // ── Persistence ───────────────────────────────────────────────────────────

    public function test_saving_endings_and_threshold_persists_csv_and_value(): void
    {
        $this->saveSettings([
            'price_endings' => ['9', '5', ''],
            'hundred_entry_threshold' => '25',
        ])->assertRedirect();

        $this->assertSame('9,5', $this->setting(RentalPriceHelper::ENDINGS_SETTING));
        $this->assertSame('25', $this->setting(RentalPriceHelper::THRESHOLD_SETTING));
    }

    public function test_clearing_all_endings_and_threshold_turns_smart_rounding_off(): void
    {
        $this->saveSettings([
            'price_endings' => ['', '', ''],
            'hundred_entry_threshold' => '',
        ])->assertRedirect();

        $this->assertSame('', $this->setting(RentalPriceHelper::ENDINGS_SETTING));
        $this->assertSame([], RentalPriceHelper::parseEndings($this->setting(RentalPriceHelper::ENDINGS_SETTING)));
    }

    // ── Validation ────────────────────────────────────────────────────────────

    public function test_duplicate_endings_are_rejected(): void
    {
        $this->saveSettings(['price_endings' => ['4', '4', '']])
            ->assertSessionHasErrors('price_endings.0');

        $this->assertSame('4,7', $this->setting(RentalPriceHelper::ENDINGS_SETTING));
    }

    public function test_ending_outside_single_digit_range_is_rejected(): void
    {
        $this->saveSettings(['price_endings' => ['12', '', '']])
            ->assertSessionHasErrors('price_endings.0');

        $this->saveSettings(['price_endings' => ['4.5', '', '']])
            ->assertSessionHasErrors('price_endings.0');
    }

    public function test_threshold_must_be_nonnegative_whole_dollars(): void
    {
        $this->saveSettings(['price_endings' => ['4', '7', ''], 'hundred_entry_threshold' => '-5'])
            ->assertSessionHasErrors('hundred_entry_threshold');

        $this->saveSettings(['price_endings' => ['4', '7', ''], 'hundred_entry_threshold' => '5.5'])
            ->assertSessionHasErrors('hundred_entry_threshold');

        $this->assertSame('10', $this->setting(RentalPriceHelper::THRESHOLD_SETTING));
    }

    public function test_threshold_without_any_ending_is_rejected(): void
    {
        $this->saveSettings(['price_endings' => ['', '', ''], 'hundred_entry_threshold' => '10'])
            ->assertSessionHasErrors('price_endings');
    }

    public function test_zero_multiplier_is_rejected(): void
    {
        $this->saveSettings(['weekend_multiplier' => '0'])
            ->assertSessionHasErrors('weekend_multiplier');

        $this->saveSettings(['weekend_multiplier' => '1.54'])->assertRedirect();
        $this->assertSame('1.54', $this->setting('weekend_multiplier'));
    }
}
