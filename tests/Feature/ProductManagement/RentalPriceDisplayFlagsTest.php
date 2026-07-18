<?php

namespace Tests\Feature\ProductManagement;

use App\Models\Iam\Personnel\User;
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;
use App\Models\ProductManagement\ProductCategoryChild;
use App\Services\AIVisibility\SchemaBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * "Do Not Display" flags for the four primary rental price levels.
 *
 * Presentation only: a checked flag removes that period from customer-facing
 * displays (category buttons, related-product buttons, schema offers, price
 * list document) while the stored price value stays intact and every
 * calculation keeps working. Unchecking restores display of the saved value.
 */
class RentalPriceDisplayFlagsTest extends TestCase
{
    use RefreshDatabase;

    private const FLAGS = ['hide_rental_daily', 'hide_rental_weekend', 'hide_rental_weekly', 'hide_rental_monthly'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\Configurations\SettingSeeder::class);

        $this->actingAs(User::create([
            'unique_id' => 'flags-admin', 'employee_code' => '02',
            'first_name' => 'Flags', 'last_name' => 'Admin',
            'email' => 'flags-admin@test.local', 'status' => 'Active',
        ]));
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function rentalPayload(array $overrides = []): array
    {
        return array_merge([
            'product_name'         => 'Display Flags Product ' . Str::random(8),
            'product_type'         => 'Rental',
            'is_general_term_type' => 1,
            'rental_daily'         => 184,
            'rental_weekend'       => 287,
            'rental_weekly'        => 547,
            'rental_monthly'       => 1557,
            'status'               => 'Published',
            'in_store_pickup'      => 'Yes',
            'delivery_and_pickup'  => 'Yes',
            'truck_fee_size_setting' => 'Large',
            'standard_delivery_fee'  => '49',
            'extended_delivery_fee'  => '69',
        ], $overrides);
    }

    private function storeProduct(array $payload): Product
    {
        $this->post(route('admin.product-management.products.create'), $payload)
            ->assertOk()
            ->assertJson(['success' => true]);

        return Product::where('product_name', $payload['product_name'])->firstOrFail();
    }

    private function updateProduct(Product $product, array $overrides = []): Product
    {
        $this->put(
            route('admin.product-management.products.edit', $product->unique_id),
            array_merge($this->rentalPayload([
                'product_name' => $product->product_name,
                'slug'         => $product->slug,
            ]), $overrides),
        )->assertOk()->assertJson(['success' => true]);

        return $product->refresh();
    }

    /** Publish the product on a category page and return that page's HTML. */
    private function categoryPageHtml(Product $product): string
    {
        $category = ProductCategory::firstOrCreate(
            ['slug' => 'flags-category'],
            ['title' => 'Flags Category', 'status' => 'Published']
        );
        ProductCategoryChild::firstOrCreate([
            'product_id'          => $product->id,
            'product_category_id' => $category->id,
        ], ['sort_order' => 1]);

        return $this->get(route('front.categories.index', $category->slug))
            ->assertOk()
            ->getContent();
    }

    private function assertPrices(Product $product): void
    {
        $this->assertEquals(184.0, (float) $product->rental_daily);
        $this->assertEquals(287.0, (float) $product->rental_weekend);
        $this->assertEquals(547.0, (float) $product->rental_weekly);
        $this->assertEquals(1557.0, (float) $product->rental_monthly);
    }

    // ── Persistence ──────────────────────────────────────────────────────

    public function test_defaults_to_displaying_all_four_prices(): void
    {
        $product = $this->storeProduct($this->rentalPayload());

        foreach (self::FLAGS as $flag) {
            $this->assertFalse($product->$flag, "$flag should default to false");
        }
        $this->assertSame(['daily', 'weekend', 'weekly', 'monthly'], $product->visibleRentalPeriods());
    }

    public function test_checking_a_flag_persists_without_touching_the_price(): void
    {
        $product = $this->storeProduct($this->rentalPayload());

        $product = $this->updateProduct($product, ['hide_rental_weekend' => 1]);

        $this->assertTrue($product->hide_rental_weekend);
        $this->assertFalse($product->hide_rental_daily);
        $this->assertFalse($product->hide_rental_weekly);
        $this->assertFalse($product->hide_rental_monthly);
        $this->assertPrices($product);
        $this->assertSame(['daily', 'weekly', 'monthly'], $product->visibleRentalPeriods());
    }

    public function test_unchecking_restores_display_with_the_saved_value(): void
    {
        $product = $this->storeProduct($this->rentalPayload(['hide_rental_weekly' => 1]));
        $this->assertTrue($product->hide_rental_weekly);

        // Unchecked checkbox = field absent from the submitted form
        $product = $this->updateProduct($product);

        $this->assertFalse($product->hide_rental_weekly);
        $this->assertPrices($product);
        $this->assertSame(['daily', 'weekend', 'weekly', 'monthly'], $product->visibleRentalPeriods());
    }

    public function test_unrelated_edit_keeps_the_resubmitted_flag_state(): void
    {
        $product = $this->storeProduct($this->rentalPayload(['hide_rental_monthly' => 1]));

        // Ordinary edit resubmits the form as displayed (checked box posts 1)
        $product = $this->updateProduct($product, [
            'product_name'        => $product->product_name . ' Renamed',
            'hide_rental_monthly' => 1,
        ]);

        $this->assertTrue($product->hide_rental_monthly);
        $this->assertStringEndsWith('Renamed', $product->product_name);
        $this->assertPrices($product);
    }

    public function test_flag_changes_do_not_trigger_price_recalculation(): void
    {
        $product = $this->storeProduct($this->rentalPayload());

        // Manual sources (the form default) + flag changes: prices verbatim
        $product = $this->updateProduct($product, [
            'hide_rental_daily'     => 1,
            'hide_rental_weekend'   => 1,
            'weekend_price_source'  => 'manual',
            'weekly_price_source'   => 'manual',
            'monthly_price_source'  => 'manual',
        ]);

        $this->assertPrices($product);
        $this->assertSame(['weekly', 'monthly'], $product->visibleRentalPeriods());
    }

    public function test_smart_recalculation_does_not_change_the_flags(): void
    {
        \App\Models\Configurations\Setting::where('setting_type', 'Price Rate Multiplier Settings')
            ->where('setting_name', 'weekend_multiplier')
            ->update(['setting_value' => '1.5']);

        $product = $this->storeProduct($this->rentalPayload(['hide_rental_weekend' => 1]));

        // Auto sources: server recalculates weekend/weekly/monthly from Daily
        $product = $this->updateProduct($product, [
            'rental_daily'          => 200,
            'hide_rental_weekend'   => 1,
            'weekend_price_source'  => 'auto',
            'weekly_price_source'   => 'auto',
            'monthly_price_source'  => 'auto',
        ]);

        $this->assertTrue($product->hide_rental_weekend, 'recalc must not clear the flag');
        $this->assertFalse($product->hide_rental_daily);
        $this->assertEquals(200.0, (float) $product->rental_daily);
        $this->assertNotEquals(287.0, (float) $product->rental_weekend, 'auto source recalculates as before');
    }

    // ── Customer-facing rendering ────────────────────────────────────────

    public function test_category_page_shows_all_four_buttons_by_default(): void
    {
        $product = $this->storeProduct($this->rentalPayload());

        $html = $this->categoryPageHtml($product);

        foreach (['Daily', 'Weekend Spcl.', 'Weekly', 'Monthly'] as $label) {
            $this->assertStringContainsString($label, $html);
        }
    }

    public function test_hiding_weekend_hides_only_weekend(): void
    {
        $product = $this->storeProduct($this->rentalPayload(['hide_rental_weekend' => 1]));

        $html = $this->categoryPageHtml($product);

        $this->assertStringNotContainsString('Weekend Spcl.', $html);
        $this->assertStringNotContainsString('/weekend/details', $html);
        foreach (['Daily', 'Weekly', 'Monthly'] as $label) {
            $this->assertStringContainsString($label, $html);
        }
    }

    public function test_product_can_show_only_weekend_special(): void
    {
        $product = $this->storeProduct($this->rentalPayload([
            'hide_rental_daily' => 1, 'hide_rental_weekly' => 1, 'hide_rental_monthly' => 1,
        ]));

        $html = $this->categoryPageHtml($product);

        $this->assertStringContainsString('Weekend Spcl.', $html);
        $this->assertStringNotContainsString('/daily/details', $html);
        $this->assertStringNotContainsString('/weekly/details', $html);
        $this->assertStringNotContainsString('/monthly/details', $html);
    }

    public function test_product_can_show_only_weekly_and_monthly(): void
    {
        $product = $this->storeProduct($this->rentalPayload([
            'hide_rental_daily' => 1, 'hide_rental_weekend' => 1,
        ]));

        $html = $this->categoryPageHtml($product);

        $this->assertStringNotContainsString('/daily/details', $html);
        $this->assertStringNotContainsString('/weekend/details', $html);
        $this->assertStringContainsString('/weekly/details', $html);
        $this->assertStringContainsString('/monthly/details', $html);
    }

    public function test_schema_offers_exclude_hidden_periods(): void
    {
        $product = $this->storeProduct($this->rentalPayload(['hide_rental_weekend' => 1]));

        $offers = app(SchemaBuilder::class)->buildOffers($product->fresh());
        $names  = array_column($offers, 'name');

        $this->assertNotEmpty($offers);
        foreach ($names as $name) {
            $this->assertStringNotContainsString('Weekend', $name);
        }
        $this->assertCount(3, array_filter($names, fn ($n) => str_contains($n, 'Rental per')));
    }

    public function test_price_list_document_prints_dash_for_hidden_periods(): void
    {
        $product  = $this->storeProduct($this->rentalPayload(['hide_rental_weekend' => 1]));
        $category = ProductCategory::firstOrCreate(
            ['slug' => 'flags-category'],
            ['title' => 'Flags Category', 'status' => 'Published']
        );
        ProductCategoryChild::firstOrCreate([
            'product_id'          => $product->id,
            'product_category_id' => $category->id,
        ], ['sort_order' => 1]);

        $doc  = app(\App\Services\DocumentGenerator\Documents\CustomerPriceListDocument::class);
        $data = $doc->build(['category_ids' => [$category->id]]);
        $html = view($doc->bodyView(), $data)->render();

        $this->assertStringContainsString('$184.00', $html);   // daily still prints
        $this->assertStringNotContainsString('$287.00', $html); // hidden weekend prints as dash
        $this->assertStringContainsString('$547.00', $html);   // weekly still prints
    }
}
