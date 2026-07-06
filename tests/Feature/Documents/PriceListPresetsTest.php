<?php

namespace Tests\Feature\Documents;

use App\Models\Iam\Personnel\User;
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;
use App\Services\DocumentGenerator\PriceListPresets;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PriceListPresetsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'unique_id' => 'test-admin', 'employee_code' => '01',
            'first_name' => 'Admin', 'last_name' => 'User',
            'email' => 'admin@test.local', 'status' => 'Active',
        ]);

        $this->actingAs($this->admin);
    }

    private function makeCategory(string $title, int $sort = 1): ProductCategory
    {
        $category = ProductCategory::create(['title' => $title, 'status' => 'Published', 'sort_order' => $sort]);

        $product = Product::create(['product_name' => $title . ' Demo Product', 'status' => 'Published', 'rental_daily' => 100]);
        DB::table('product_category_children')->insert([
            'product_id' => $product->id, 'product_category_id' => $category->id,
            'sort_order' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return $category;
    }

    // Preset resolution: exact slugs and slug keywords both map to categories
    public function test_presets_resolve_by_slug_and_keyword(): void
    {
        $excavators  = $this->makeCategory('Excavators');          // exact slug 'excavators'
        $miniExc     = $this->makeCategory('Mini Excavators');     // keyword 'excavator' → 'mini-excavators'
        $attachments = $this->makeCategory('Attachments');         // keyword 'attachment'
        $chippers    = $this->makeCategory('Wood Chippers');       // exact slug 'wood-chippers'
        $unrelated   = $this->makeCategory('Party Tents');         // matches nothing

        $categories = ProductCategory::get(['id', 'slug']);
        $presets    = PriceListPresets::resolve($categories);

        $dirtWork = $presets['dirt_work'];
        $this->assertContains($excavators->id, $dirtWork['category_ids']);
        $this->assertContains($miniExc->id, $dirtWork['category_ids']);
        $this->assertContains($attachments->id, $dirtWork['category_ids']);
        $this->assertNotContains($chippers->id, $dirtWork['category_ids']);
        $this->assertNotContains($unrelated->id, $dirtWork['category_ids']);

        $treeWork = $presets['tree_work'];
        $this->assertContains($chippers->id, $treeWork['category_ids']);
        $this->assertContains($attachments->id, $treeWork['category_ids']);
        $this->assertNotContains($excavators->id, $treeWork['category_ids']);

        // All four configured presets resolve
        $this->assertSame(
            ['dirt_work', 'tree_work', 'plumbing_utility', 'home_building'],
            $presets->keys()->all()
        );
    }

    // Form renders preset cards carrying the resolved category ids
    public function test_form_shows_presets_with_category_ids(): void
    {
        $excavators  = $this->makeCategory('Excavators');
        $attachments = $this->makeCategory('Attachments');

        $response = $this->get(route('admin.documents.price-list.form'))->assertOk();

        $response->assertSee('Industry Presets')
            ->assertSee('Dirt Work / Grading')
            ->assertSee('Tree Work / Arborist')
            ->assertSee('Plumbing / Utility')
            ->assertSee('Home Building / Construction')
            ->assertSee('Clear Selection');

        // The Dirt Work card carries both matching category ids for the JS to check
        $html = $response->getContent();
        $this->assertMatchesRegularExpression(
            '/data-preset="dirt_work"\s+data-category-ids="[^"]*\b' . $excavators->id . '\b[^"]*"/',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/data-preset="dirt_work"\s+data-category-ids="[^"]*\b' . $attachments->id . '\b[^"]*"/',
            $html
        );
    }

    // Presets that resolve to nothing in this environment are hidden
    public function test_presets_without_matches_are_hidden(): void
    {
        $this->makeCategory('Party Tents'); // matches no preset

        $this->get(route('admin.documents.price-list.form'))
            ->assertOk()
            ->assertDontSee('Industry Presets');
    }

    // Manual selection still works and generation only honors the final categories
    public function test_generation_uses_final_selected_categories_only(): void
    {
        $excavators = $this->makeCategory('Excavators');
        $chippers   = $this->makeCategory('Wood Chippers', 2);

        // Staff picked a preset, then removed one category and generated —
        // the request carries only the final selection.
        $this->get(route('admin.documents.price-list.generate', [
            'category_ids' => [$chippers->id],
        ]))
            ->assertOk()
            ->assertSee('Wood Chippers Demo Product')
            ->assertDontSee('Excavators Demo Product');
    }
}
