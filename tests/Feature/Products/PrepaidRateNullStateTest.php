<?php

namespace Tests\Feature\Products;

use App\Models\Iam\Personnel\User;
use App\Models\ProductManagement\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Product Edit — Prepaid Cleaning / Prepaid Fuel null-state restore (2026-07-23).
 *
 * Each charge stores a preset reference (the Settings rate DESCRIPTION string)
 * in products.prepaid_{cleaning,fuel}_rate_setting plus a dollar amount in
 * products.rental_prepaid_{cleaning,fuel}. The dropdown's "Select Rate" option
 * was `disabled`, so a linked product could never return to the null/unlinked
 * state. It is now a genuine selectable empty option; an empty submission
 * normalizes to DB NULL (global ConvertEmptyStringsToNull) and the dollar
 * amount is preserved as a manual value.
 *
 * Sync semantics: ProductSettings\SaveController propagates a rate change via
 * Product::where('prepaid_*_rate_setting', $description)->update([...]) — so a
 * null reference is never matched and no longer follows Settings changes.
 */
class PrepaidRateNullStateTest extends TestCase
{
    use RefreshDatabase;

    private const CLEAN_RATE = 'Easy: Stump Grinder - 19';
    private const FUEL_RATE  = 'Gas: xSmall: 1 Gallon - 4.50';

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::create([
            'first_name' => 'Prod', 'last_name' => 'Editor',
            'email' => 'prepaid-null@test.local', 'status' => 'Active',
        ]));
    }

    private function linkedProduct(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'product_name' => 'Rotary Transit Full Kit',
            'slug'         => 'rotary-transit-full-kit',
            'product_type' => 'Rental',
            'status'       => 'Published',
            'rental_daily' => 60, 'rental_weekend' => 97, 'rental_weekly' => 188, 'rental_monthly' => 565,
            'prepaid_cleaning_rate_setting' => self::CLEAN_RATE, 'rental_prepaid_cleaning' => 19,
            'prepaid_fuel_rate_setting'     => self::FUEL_RATE,  'rental_prepaid_fuel'     => 4.50,
        ], $overrides));
    }

    /** Minimal valid Rental update payload; overrides tune the prepaid fields. */
    private function payload(Product $product, array $overrides = []): array
    {
        return array_merge([
            'action'               => 'save',
            'product_name'         => $product->product_name,
            'slug'                 => $product->slug,
            'product_type'         => 'Rental',
            'is_general_term_type' => 1,
            'status'               => 'Published',
            'in_store_pickup'      => 'Yes', // rental requires a pickup/delivery option
            'rental_daily'         => 60,
            'rental_weekend'       => 97,
            'rental_weekly'        => 188,
            'rental_monthly'       => 565,
        ], $overrides);
    }

    private function editUrl(Product $product): string
    {
        // GET and PUT share this URI; route() supplies the admin-domain URL.
        return route('admin.product-management.products.edit', $product->unique_id);
    }

    private function update(Product $product, array $overrides): \Illuminate\Testing\TestResponse
    {
        return $this->putJson($this->editUrl($product), $this->payload($product, $overrides));
    }

    public function test_clearing_a_preset_stores_null_and_preserves_the_amount(): void
    {
        $product = $this->linkedProduct();

        $this->update($product, [
            'prepaid_cleaning_rate_setting' => '',    // cleared
            'rental_prepaid_cleaning'       => '19',  // amount kept as manual value
            'prepaid_fuel_rate_setting'     => self::FUEL_RATE,
            'rental_prepaid_fuel'           => '4.50',
        ])->assertOk()->assertJsonPath('success', true);

        $product->refresh();
        $this->assertNull($product->prepaid_cleaning_rate_setting, 'preset reference cleared to NULL');
        $this->assertNotSame('', $product->prepaid_cleaning_rate_setting);
        $this->assertEquals(19, (float) $product->rental_prepaid_cleaning, 'amount preserved, not zeroed');
    }

    public function test_empty_submission_is_normalized_to_actual_null(): void
    {
        $product = $this->linkedProduct();

        $this->update($product, ['prepaid_cleaning_rate_setting' => '', 'prepaid_fuel_rate_setting' => ''])
            ->assertOk();

        $product->refresh();
        // Not '', not '0', not the literal 'null', not the previous id — real NULL.
        $this->assertNull($product->prepaid_cleaning_rate_setting);
        $this->assertNull($product->prepaid_fuel_rate_setting);
    }

    public function test_clearing_one_preset_does_not_affect_the_other(): void
    {
        $product = $this->linkedProduct();

        $this->update($product, [
            'prepaid_cleaning_rate_setting' => '',              // clear cleaning only
            'rental_prepaid_cleaning'       => '19',
            'prepaid_fuel_rate_setting'     => self::FUEL_RATE, // fuel stays linked
            'rental_prepaid_fuel'           => '4.50',
        ])->assertOk();

        $product->refresh();
        $this->assertNull($product->prepaid_cleaning_rate_setting);
        $this->assertSame(self::FUEL_RATE, $product->prepaid_fuel_rate_setting, 'fuel preset untouched');
    }

    public function test_selecting_a_preset_stores_it(): void
    {
        $product = $this->linkedProduct([
            'prepaid_cleaning_rate_setting' => null, 'rental_prepaid_cleaning' => null,
        ]);

        $this->update($product, [
            'prepaid_cleaning_rate_setting' => self::CLEAN_RATE,
            'rental_prepaid_cleaning'       => '19',
        ])->assertOk();

        $product->refresh();
        $this->assertSame(self::CLEAN_RATE, $product->prepaid_cleaning_rate_setting);
        $this->assertEquals(19, (float) $product->rental_prepaid_cleaning);
    }

    public function test_manual_override_amount_is_kept_with_a_linked_preset(): void
    {
        // Existing behavior must remain: a preset can carry an overridden amount.
        $product = $this->linkedProduct();

        $this->update($product, [
            'prepaid_cleaning_rate_setting' => self::CLEAN_RATE,
            'rental_prepaid_cleaning'       => '99', // overridden, not the 19 preset rate
        ])->assertOk();

        $product->refresh();
        $this->assertSame(self::CLEAN_RATE, $product->prepaid_cleaning_rate_setting);
        $this->assertEquals(99, (float) $product->rental_prepaid_cleaning);
    }

    public function test_cleared_product_no_longer_follows_the_former_settings_rate(): void
    {
        $cleared = $this->linkedProduct(['slug' => 'cleared-kit', 'product_name' => 'Cleared Kit']);
        $stillLinked = $this->linkedProduct(['slug' => 'linked-kit', 'product_name' => 'Linked Kit']);

        // Clear the first product's cleaning preset (amount preserved at 19).
        $this->update($cleared, ['prepaid_cleaning_rate_setting' => '', 'rental_prepaid_cleaning' => '19'])
            ->assertOk();
        $this->assertNull($cleared->refresh()->prepaid_cleaning_rate_setting);

        // Replicate ProductSettings\SaveController's propagation: the Settings
        // rate "Easy: Stump Grinder - 19" changes to 25.
        Product::where('prepaid_cleaning_rate_setting', self::CLEAN_RATE)
            ->update(['rental_prepaid_cleaning' => 25]);

        // The still-linked product follows; the cleared one does not.
        $this->assertEquals(25, (float) $stillLinked->refresh()->rental_prepaid_cleaning);
        $this->assertEquals(19, (float) $cleared->refresh()->rental_prepaid_cleaning);
    }

    public function test_a_cleared_selection_persists_as_null_across_reload(): void
    {
        // "Reload shows Select Rate": a re-fetched product keeps the null
        // reference, so the blade's @selected(empty(null)) picks Select Rate.
        $product = $this->linkedProduct();

        $this->update($product, ['prepaid_cleaning_rate_setting' => '', 'rental_prepaid_cleaning' => '19'])
            ->assertOk();

        $reloaded = Product::where('unique_id', $product->unique_id)->firstOrFail();
        $this->assertNull($reloaded->prepaid_cleaning_rate_setting);
        $this->assertEquals(19, (float) $reloaded->rental_prepaid_cleaning);
    }
}
