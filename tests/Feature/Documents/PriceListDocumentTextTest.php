<?php

namespace Tests\Feature\Documents;

use App\Models\Configurations\Setting;
use App\Models\Documents\PriceListPreset;
use App\Models\Iam\Personnel\User;
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Price List module structure: one sidebar entry, internal sub-nav
 * (Generate / Industry Presets / Document Text), Price List document text
 * managed inside the module and used by generation.
 */
class PriceListDocumentTextTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private ProductCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'unique_id' => 'test-admin', 'employee_code' => '01',
            'first_name' => 'Admin', 'last_name' => 'User',
            'email' => 'admin@test.local', 'status' => 'Active',
        ]);

        $this->category = ProductCategory::create(['title' => 'Excavators', 'status' => 'Published', 'sort_order' => 1]);
        $product = Product::create(['product_name' => 'Mini Excavator', 'status' => 'Published', 'rental_daily' => 250]);
        DB::table('product_category_children')->insert([
            'product_id' => $product->id, 'product_category_id' => $this->category->id,
            'sort_order' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($this->admin);
    }

    // ── Sub-navigation: one module, three sections ─────────────────

    public function test_all_three_sections_share_the_module_sub_nav(): void
    {
        foreach ([
            route('admin.documents.price-list.form'),
            route('admin.documents.presets.index'),
            route('admin.documents.price-list.text'),
        ] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('Customer Price List')
                ->assertSee('Generate')
                ->assertSee('Industry Presets')
                ->assertSee('Document Text');
        }
    }

    public function test_sidebar_has_single_price_list_entry_and_no_presets_entry(): void
    {
        $html = $this->get(route('admin.documents.price-list.form'))->assertOk()->getContent();

        $this->assertStringNotContainsString('Price List Presets', $html);

        // Presets remain fully manageable through the module sub-nav
        $this->get(route('admin.documents.presets.index'))->assertOk();
        $this->assertStringContainsString(route('admin.documents.presets.index'), $html);
    }

    // ── Document Text section ──────────────────────────────────────

    public function test_document_text_page_shows_editor_and_merge_code_help(): void
    {
        $this->get(route('admin.documents.price-list.text'))
            ->assertOk()
            ->assertSee('Document Title')
            ->assertSee('Value Message')
            ->assertSee('Disclaimer / Final-Page Text')
            ->assertSee('Available Merge Codes')
            ->assertSee('{{ company_name }}')
            ->assertSee('{{ generated_datetime }}')
            ->assertSee('replaced', false); // explanation that codes resolve at generation time
    }

    public function test_document_text_saves_title_value_message_and_disclaimer(): void
    {
        $this->post(route('admin.documents.price-list.text.save'), [
            'price_list_title'         => 'Contractor Rate Sheet',
            'price_list_value_message' => 'Weekly beats daily.',
            'price_list_disclaimer'    => 'Pricing on {{ main_url }} governs.',
        ])->assertRedirect(route('admin.documents.price-list.text'));

        $get = fn (string $name) => Setting::where('setting_type', 'Price List Settings')
            ->where('setting_name', $name)->value('setting_value');

        $this->assertSame('Contractor Rate Sheet', $get('price_list_title'));
        $this->assertSame('Weekly beats daily.', $get('price_list_value_message'));
        // Raw merge codes stored untouched — resolved only at generation
        $this->assertSame('Pricing on {{ main_url }} governs.', $get('price_list_disclaimer'));
    }

    public function test_generated_document_uses_saved_price_list_text(): void
    {
        $this->post(route('admin.documents.price-list.text.save'), [
            'price_list_title'         => 'Contractor Rate Sheet',
            'price_list_value_message' => 'Ask about {{ company_name }} weekly rates.',
            'price_list_disclaimer'    => 'Official pricing lives on {{ main_url }}.',
        ]);

        $html = $this->get(route('admin.documents.price-list.generate', [
            'category_ids' => [$this->category->id],
        ]))->assertOk()->getContent();

        $this->assertStringContainsString('Contractor Rate Sheet', $html);
        $this->assertStringNotContainsString('Rental Price List', $html);
        // Merge codes resolved with seeded company values at generation time
        $this->assertStringContainsString('Ask about Rent &#039;n King weekly rates.', $html);
        $this->assertStringContainsString('Official pricing lives on RentnKing.com.', $html);
    }

    // ── Preset thumbnails ──────────────────────────────────────────

    public function test_preset_thumbnail_uploads_and_shows_on_generator_card(): void
    {
        Storage::fake('public_asset');

        $this->post(route('admin.documents.presets.store'), [
            'name'         => 'Land Clearing',
            'is_active'    => 1,
            'category_ids' => [$this->category->id],
            'thumbnail'    => UploadedFile::fake()->image('digger.jpg', 300, 200),
        ])->assertRedirect(route('admin.documents.presets.index'));

        $preset = PriceListPreset::where('name', 'Land Clearing')->firstOrFail();
        $this->assertNotNull($preset->media_id);
        $this->assertNotNull($preset->thumbnail_url);

        // Card on the Generate tab carries the image
        $this->get(route('admin.documents.price-list.form'))
            ->assertOk()
            ->assertSee($preset->thumbnail_url, false);

        // Removal clears the reference
        $this->put(route('admin.documents.presets.update', $preset), [
            'name'             => 'Land Clearing',
            'is_active'        => 1,
            'category_ids'     => [$this->category->id],
            'remove_thumbnail' => 1,
        ]);

        $this->assertNull($preset->refresh()->media_id);
    }
}
