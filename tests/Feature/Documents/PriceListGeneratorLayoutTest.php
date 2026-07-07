<?php

namespace Tests\Feature\Documents;

use App\Models\Documents\PriceListPreset;
use App\Models\Iam\Personnel\User;
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Generate tab layout per the approved mockup: header card with segmented
 * numbered tabs, preset thumbnail cards, four-column category grid,
 * live "Your Selection" sidebar, and the live-pricing info bar.
 * UI only — generation behavior is covered by the other suites.
 */
class PriceListGeneratorLayoutTest extends TestCase
{
    use RefreshDatabase;

    private ProductCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::create([
            'unique_id' => 'test-admin', 'employee_code' => '01',
            'first_name' => 'Admin', 'last_name' => 'User',
            'email' => 'admin@test.local', 'status' => 'Active',
        ]));

        $this->category = ProductCategory::create(['title' => 'Excavators', 'status' => 'Published', 'sort_order' => 1]);
        $product = Product::create(['product_name' => 'Mini Excavator', 'status' => 'Published', 'rental_daily' => 250]);
        DB::table('product_category_children')->insert([
            'product_id' => $product->id, 'product_category_id' => $this->category->id,
            'sort_order' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_generate_tab_renders_all_layout_sections(): void
    {
        $preset = PriceListPreset::create(['name' => 'Land Clearing', 'description' => 'Clearing gear']);
        $preset->categories()->sync([$this->category->id]);

        $this->get(route('admin.documents.price-list.form'))
            ->assertOk()
            // Header card + segmented tabs
            ->assertSee('Customer Price List')
            ->assertSee('website pricing always governs')
            ->assertSee('Generate')
            ->assertSee('Industry Presets')
            ->assertSee('Document Text')
            // Section 1
            ->assertSee('Choose an Industry Preset')
            ->assertSee('(optional)')
            ->assertSee('Select one or more presets to quickly load relevant categories', false)
            ->assertSee('Clear All Presets')
            // Section 2
            ->assertSee('Choose Categories')
            ->assertSee('Select the categories to include on the price list.')
            ->assertSee('Clear Selection')
            ->assertSee('Select All')
            ->assertSee('Generate Price List')
            ->assertSee('Opens in a new tab, ready to print.')
            // Right sidebar summary
            ->assertSee('Your Selection')
            ->assertSee('Presets Selected')
            ->assertSee('Categories Selected')
            ->assertSee('Products will print in the same order as on the website.')
            // Bottom info bar
            ->assertSee('The price list uses live pricing from your website.')
            ->assertSee('Prices and availability can change at any time.');
    }

    public function test_layout_uses_four_column_categories_and_summary_hooks(): void
    {
        $html = $this->get(route('admin.documents.price-list.form'))->assertOk()->getContent();

        // Four category columns on desktop; summary panel hooks for live JS
        $this->assertStringContainsString('xl:grid-cols-4', $html);
        $this->assertStringContainsString('id="pl-summary-presets"', $html);
        $this->assertStringContainsString('id="pl-summary-categories"', $html);
        $this->assertStringContainsString('id="pl-summary-count"', $html);
    }

    public function test_preset_cards_carry_thumbnail_area_and_selection_hooks(): void
    {
        $preset = PriceListPreset::create(['name' => 'Land Clearing', 'description' => 'Clearing gear']);
        $preset->categories()->sync([$this->category->id]);

        $html = $this->get(route('admin.documents.price-list.form'))->assertOk()->getContent();

        // Five-across grid, thumbnail area (placeholder when no image),
        // toggle state attribute, check indicator, and label for the summary
        $this->assertStringContainsString('xl:grid-cols-5', $html);
        $this->assertStringContainsString('pl-preset-thumb', $html);
        $this->assertStringContainsString('id="pl-clear-presets"', $html);
        $this->assertStringContainsString('pl-preset-check', $html);
        $this->assertStringContainsString('aria-pressed="false"', $html);
        $this->assertStringContainsString('data-label="Land Clearing"', $html);
        // Generation contract: card still carries the category ids
        $this->assertMatchesRegularExpression(
            '/data-preset="' . $preset->id . '"\s+data-category-ids="[^"]*\b' . $this->category->id . '\b[^"]*"/',
            $html
        );
    }
}
