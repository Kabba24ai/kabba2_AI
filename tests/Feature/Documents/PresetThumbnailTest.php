<?php

namespace Tests\Feature\Documents;

use App\Helpers\ImageHelper;
use App\Models\Documents\PriceListPreset;
use App\Models\Global\Media;
use App\Models\Iam\Personnel\User;
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Industry Preset thumbnails: uploads are center-cropped/resized to a
 * standardized square (500px stored, 250px display area) and cards always
 * render a uniform square container — never stretched, placeholder when
 * missing. Admin UI only; the printed document never carries images.
 */
class PresetThumbnailTest extends TestCase
{
    use RefreshDatabase;

    private ProductCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public_asset');

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

    private function storePreset(array $overrides = [])
    {
        return $this->post(route('admin.documents.presets.store'), array_merge([
            'name'         => 'Land Clearing',
            'is_active'    => 1,
            'category_ids' => [$this->category->id],
        ], $overrides));
    }

    private function storedImageSize(PriceListPreset $preset): array
    {
        $media = Media::findOrFail($preset->media_id);
        $contents = Storage::disk('public_asset')->get($media->folder_name . '/' . $media->file_name);
        [$w, $h] = getimagesizefromstring($contents);

        return [$w, $h];
    }

    // ── Admin form guidance ────────────────────────────────────────

    public function test_upload_help_shows_design_guidance_not_file_size_first(): void
    {
        $this->get(route('admin.documents.presets.create'))
            ->assertOk()
            ->assertSee('Recommended: 500 × 500 px square image. JPG, PNG, or WebP.')
            ->assertSee('automatically center-cropped and resized')
            ->assertSee('Optional. If no image is uploaded, a placeholder will be shown.')
            ->assertSee('Maximum file size: 1 MB.')
            ->assertDontSee('4 MB');
    }

    // ── Validation ─────────────────────────────────────────────────

    public function test_accepts_jpg_png_and_webp_uploads(): void
    {
        foreach (['photo.jpg', 'photo.png', 'photo.webp'] as $i => $name) {
            $this->post(route('admin.documents.presets.store'), [
                'name'         => 'Preset ' . $i,
                'is_active'    => 1,
                'category_ids' => [$this->category->id],
                'thumbnail'    => UploadedFile::fake()->image($name, 600, 600),
            ])->assertSessionHasNoErrors();

            $this->assertNotNull(PriceListPreset::where('name', 'Preset ' . $i)->first()?->media_id, $name);
        }
    }

    public function test_rejects_non_image_uploads(): void
    {
        $this->storePreset([
            'thumbnail' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('thumbnail');

        $this->assertDatabaseMissing('price_list_presets', ['name' => 'Land Clearing']);
    }

    public function test_rejects_uploads_over_one_megabyte(): void
    {
        $this->storePreset([
            'thumbnail' => UploadedFile::fake()->image('huge.jpg', 500, 500)->size(2048),
        ])->assertSessionHasErrors('thumbnail');
    }

    // ── Processing: proportional scale + center-crop to square ─────

    public function test_landscape_upload_is_center_cropped_to_square(): void
    {
        $this->storePreset([
            'thumbnail' => UploadedFile::fake()->image('wide.jpg', 900, 450),
        ])->assertSessionHasNoErrors();

        $preset = PriceListPreset::where('name', 'Land Clearing')->firstOrFail();
        [$w, $h] = $this->storedImageSize($preset);

        // Shorter side (450) < 500 target → cropped square at 450, never upscaled
        $this->assertSame($w, $h, 'stored thumbnail must be square');
        $this->assertSame(450, $w);
    }

    public function test_large_upload_is_resized_down_to_500px_square(): void
    {
        $this->storePreset([
            'thumbnail' => UploadedFile::fake()->image('big.png', 1200, 800),
        ])->assertSessionHasNoErrors();

        $preset = PriceListPreset::where('name', 'Land Clearing')->firstOrFail();
        [$w, $h] = $this->storedImageSize($preset);

        $this->assertSame([500, 500], [$w, $h]);
    }

    public function test_square_thumbnail_helper_falls_back_to_original_on_unsupported_input(): void
    {
        $file = UploadedFile::fake()->create('weird.gif', 10, 'image/gif');

        // Unsupported format → original returned untouched, never an error
        $this->assertSame($file, ImageHelper::squareThumbnail($file));
    }

    // ── Display ────────────────────────────────────────────────────

    public function test_cards_render_fixed_square_cover_container_and_placeholder(): void
    {
        // One preset with an image, one without
        $this->storePreset(['thumbnail' => UploadedFile::fake()->image('digger.jpg', 600, 600)]);
        $bare = PriceListPreset::create(['name' => 'Bare Preset']);
        $bare->categories()->sync([$this->category->id]);

        $html = $this->get(route('admin.documents.price-list.form'))->assertOk()->getContent();

        // Uniform square area with cover behavior — images can never distort
        $this->assertStringContainsString('aspect-square', $html);
        $this->assertStringContainsString('object-cover object-center', $html);
        // Preset without an image renders the clean placeholder icon area
        $this->assertMatchesRegularExpression('/pl-preset-thumb[^>]*>\s*<span[^>]*grid place-items-center/', $html);
    }
}
