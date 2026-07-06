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
 * Industry Presets are admin-managed (price_list_presets tables) and are a
 * selection convenience only — generation stays purely category-id based.
 */
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

    private function makePreset(string $name, array $categoryIds = [], bool $active = true): PriceListPreset
    {
        $preset = PriceListPreset::create(['name' => $name, 'description' => $name . ' description', 'is_active' => $active]);
        $preset->categories()->sync($categoryIds);

        return $preset;
    }

    // ── Admin management ───────────────────────────────────────────

    public function test_admin_can_view_preset_list_including_inactive_and_seeded(): void
    {
        $category = $this->makeCategory('Excavators');
        $this->makePreset('Custom Active', [$category->id]);
        $this->makePreset('Custom Inactive', [$category->id], active: false);

        $response = $this->get(route('admin.documents.presets.index'))->assertOk();

        $response->assertSee('Custom Active')
            ->assertSee('Custom Inactive')
            ->assertSee('Inactive')
            // The four Phase 1 config presets were carried over by migration
            ->assertSee('Dirt Work / Grading')
            ->assertSee('Tree Work / Arborist');
    }

    public function test_admin_can_create_preset_with_selected_categories(): void
    {
        $excavators = $this->makeCategory('Excavators');
        $chippers   = $this->makeCategory('Wood Chippers');

        $this->post(route('admin.documents.presets.store'), [
            'name'         => 'Land Clearing',
            'description'  => 'Excavators and chippers',
            'is_active'    => 1,
            'category_ids' => [$excavators->id, $chippers->id],
        ])->assertRedirect(route('admin.documents.presets.index'));

        $preset = PriceListPreset::where('name', 'Land Clearing')->firstOrFail();
        $this->assertTrue($preset->is_active);
        $this->assertSame('Excavators and chippers', $preset->description);
        $this->assertEqualsCanonicalizing(
            [$excavators->id, $chippers->id],
            $preset->categories()->pluck('product_categories.id')->all()
        );
    }

    public function test_create_requires_at_least_one_category(): void
    {
        $this->from(route('admin.documents.presets.create'))
            ->post(route('admin.documents.presets.store'), [
                'name'         => 'Empty Preset',
                'category_ids' => [],
            ])
            ->assertSessionHasErrors('category_ids');

        $this->assertDatabaseMissing('price_list_presets', ['name' => 'Empty Preset']);
    }

    public function test_admin_can_edit_title_description_status_and_categories(): void
    {
        $excavators = $this->makeCategory('Excavators');
        $chippers   = $this->makeCategory('Wood Chippers');
        $preset     = $this->makePreset('Old Name', [$excavators->id]);

        $this->put(route('admin.documents.presets.update', $preset), [
            'name'         => 'New Name',
            'description'  => 'New description',
            'is_active'    => 0,
            'sort_order'   => 5,
            'category_ids' => [$chippers->id],
        ])->assertRedirect(route('admin.documents.presets.index'));

        $preset->refresh();
        $this->assertSame('New Name', $preset->name);
        $this->assertSame('New description', $preset->description);
        $this->assertFalse($preset->is_active);
        $this->assertSame(5, $preset->sort_order);
        // Category assignment replaced, not appended
        $this->assertSame([$chippers->id], $preset->categories()->pluck('product_categories.id')->all());
    }

    public function test_admin_can_toggle_and_soft_delete_presets(): void
    {
        $category = $this->makeCategory('Excavators');
        $preset   = $this->makePreset('Toggle Me', [$category->id]);

        $this->patch(route('admin.documents.presets.toggle', $preset));
        $this->assertFalse($preset->refresh()->is_active);

        // Following the redirect consumes the "deleted" flash message so the
        // assertions below see only page content.
        $this->followingRedirects()->delete(route('admin.documents.presets.destroy', $preset))->assertOk();
        $this->assertSoftDeleted($preset);

        // Deleted presets disappear from admin list and generator form
        $this->get(route('admin.documents.presets.index'))->assertOk()->assertDontSee('Toggle Me');
        $this->get(route('admin.documents.price-list.form'))->assertOk()->assertDontSee('Toggle Me');
    }

    // ── Generator form behavior ────────────────────────────────────

    public function test_active_presets_appear_on_form_with_assigned_category_ids(): void
    {
        $excavators = $this->makeCategory('Excavators');
        $chippers   = $this->makeCategory('Wood Chippers');
        $preset     = $this->makePreset('Land Clearing', [$excavators->id, $chippers->id]);

        $response = $this->get(route('admin.documents.price-list.form'))->assertOk();
        $response->assertSee('Industry Presets')->assertSee('Land Clearing');

        $html = $response->getContent();
        $this->assertMatchesRegularExpression(
            '/data-preset="' . $preset->id . '"\s+data-category-ids="[^"]*\b' . $excavators->id . '\b[^"]*"/',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/data-preset="' . $preset->id . '"\s+data-category-ids="[^"]*\b' . $chippers->id . '\b[^"]*"/',
            $html
        );
    }

    public function test_inactive_presets_do_not_appear_on_form(): void
    {
        $category = $this->makeCategory('Excavators');
        $this->makePreset('Hidden Preset', [$category->id], active: false);

        $this->get(route('admin.documents.price-list.form'))
            ->assertOk()
            ->assertDontSee('Hidden Preset');
    }

    public function test_presets_with_no_selectable_categories_are_hidden(): void
    {
        $this->makeCategory('Party Tents'); // keeps the form itself rendering

        // Preset assigned only to a category with no published products
        $bare = ProductCategory::create(['title' => 'Empty Category', 'status' => 'Published', 'sort_order' => 9]);
        $this->makePreset('Useless Preset', [$bare->id]);

        $this->get(route('admin.documents.price-list.form'))
            ->assertOk()
            ->assertDontSee('Useless Preset');
    }

    public function test_form_lists_categories_alphabetically(): void
    {
        // Website display order (sort_order) deliberately different from alphabetical
        $this->makeCategory('Zebra Trailers', 1);
        $this->makeCategory('Mini Excavators', 2);
        $this->makeCategory('Aerial Lifts', 3);

        $html = $this->get(route('admin.documents.price-list.form'))->assertOk()->getContent();

        $aerial = strpos($html, 'Aerial Lifts');
        $mini   = strpos($html, 'Mini Excavators');
        $zebra  = strpos($html, 'Zebra Trailers');

        $this->assertNotFalse($aerial);
        $this->assertNotFalse($mini);
        $this->assertNotFalse($zebra);
        $this->assertLessThan($mini, $aerial);
        $this->assertLessThan($zebra, $mini);
    }

    // ── Generation stays category-id based ─────────────────────────

    public function test_generation_uses_final_selected_categories_only(): void
    {
        $excavators = $this->makeCategory('Excavators');
        $chippers   = $this->makeCategory('Wood Chippers', 2);
        $this->makePreset('Land Clearing', [$excavators->id, $chippers->id]);

        // Staff picked a preset, then removed one category and generated —
        // the request carries only the final selection; preset ids are
        // never sent or read by the generator.
        $this->get(route('admin.documents.price-list.generate', [
            'category_ids' => [$chippers->id],
        ]))
            ->assertOk()
            ->assertSee('Wood Chippers Demo Product')
            ->assertDontSee('Excavators Demo Product');
    }

    public function test_printed_document_keeps_website_display_order(): void
    {
        // Alphabetical would be Aerial → Zebra; website order is Zebra(1) → Aerial(2)
        $zebra  = $this->makeCategory('Zebra Trailers', 1);
        $aerial = $this->makeCategory('Aerial Lifts', 2);

        $html = $this->get(route('admin.documents.price-list.generate', [
            'category_ids' => [$aerial->id, $zebra->id],
        ]))->assertOk()->getContent();

        $this->assertLessThan(strpos($html, 'Aerial Lifts'), strpos($html, 'Zebra Trailers'));
    }
}
