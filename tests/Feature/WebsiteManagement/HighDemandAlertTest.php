<?php

namespace Tests\Feature\WebsiteManagement;

use App\Models\Configurations\Setting;
use App\Models\Global\Media;
use App\Models\Iam\Personnel\User;
use App\Models\ProductManagement\Product;
use App\Services\Website\HighDemandAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Global High Demand Alert popup.
 *
 * The per-product has_high_demand_alert checkbox keeps its exact meaning
 * (it only decides WHETHER the shared popup applies). The popup's image,
 * heading, message, phone, and button text are global settings edited on
 * Website Management → High Demand Alert; with no configuration the popup
 * renders its original hard-coded content, and the phone falls back to
 * the legacy Branding site_phone.
 */
class HighDemandAlertTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\Configurations\SettingSeeder::class);
    }

    private function actingAsAdmin(): void
    {
        $this->actingAs(User::create([
            'unique_id' => 'test-admin', 'employee_code' => '01',
            'first_name' => 'Admin', 'last_name' => 'User',
            'email' => 'admin@test.local', 'status' => 'Active',
        ]));
    }

    private function makeProduct(bool $highDemand): Product
    {
        return Product::create([
            'unique_id'             => Str::uuid()->toString(),
            'product_name'          => 'Test Brush Cutter',
            'slug'                  => 'test-brush-cutter',
            'status'                => 'Published',
            'product_type'          => 'Rental',
            'has_high_demand_alert' => $highDemand,
        ]);
    }

    private function productUrl(): string
    {
        return 'http://' . config('app.domains.front') . '/products/test-brush-cutter/daily/details';
    }

    private function setAlertSetting(string $name, ?string $value): void
    {
        Setting::updateOrCreate(
            ['setting_name' => $name, 'setting_type' => HighDemandAlertService::SETTING_TYPE],
            ['setting_value' => $value],
        );
        HighDemandAlertService::clearCache();
    }

    private function alertSetting(string $name): ?string
    {
        return Setting::where('setting_name', $name)
            ->where('setting_type', HighDemandAlertService::SETTING_TYPE)
            ->value('setting_value');
    }

    // ── Product trigger: checkbox meaning unchanged ──────────────────────────

    public function test_enabled_product_arms_the_popup_and_renders_the_modal(): void
    {
        $this->makeProduct(true);

        $html = $this->get($this->productUrl())->assertOk()->getContent();

        $this->assertStringContainsString('hasHighDemandAlert: "1"', $html);
        $this->assertSame(1, substr_count($html, 'id="modalHighDemandAlert"'), 'exactly one modal instance');
        $this->assertStringContainsString('id="continueReservationBtn"', $html, 'continue button preserves the add-to-cart flow');
    }

    public function test_disabled_product_does_not_arm_the_popup(): void
    {
        $this->makeProduct(false);

        $html = $this->get($this->productUrl())->assertOk()->getContent();

        $this->assertStringContainsString('hasHighDemandAlert: ""', $html);
    }

    // ── Defaults: unconfigured installs render the original popup ────────────

    public function test_unconfigured_install_renders_the_original_hard_coded_content(): void
    {
        $this->makeProduct(true);

        $html = $this->get($this->productUrl())->assertOk()->getContent();

        $this->assertStringContainsString('High Demand Alert!', $html);
        $this->assertStringContainsString('spike in demand', $html);
        $this->assertStringContainsString('Continue Reservation', $html);
        $this->assertStringContainsString('storage/front/images/lady-image.webp', $html);
    }

    public function test_phone_falls_back_to_the_legacy_branding_site_phone(): void
    {
        Setting::updateOrCreate(
            ['setting_name' => 'site_phone', 'setting_type' => 'Website Management Branding'],
            ['setting_value' => '(615) 815-6734'],
        );
        HighDemandAlertService::clearCache();
        $this->makeProduct(true);

        $html = $this->get($this->productUrl())->assertOk()->getContent();

        $this->assertStringContainsString('href="tel:(615) 815-6734"', $html);
    }

    // ── Global configuration renders ─────────────────────────────────────────

    public function test_configured_values_render_in_the_public_popup(): void
    {
        $media = Media::create([
            'original_file_name' => 'custom-alert.png', 'asset_type' => 'Public Asset',
            'folder_name' => 'high_demand_alert', 'file_name' => 'custom-alert.png',
            'file_extension' => 'png', 'file_type' => 'image', 'mime_type' => 'image/png',
            'file_size' => 1000, 'is_used' => 'Yes',
        ]);

        $this->setAlertSetting('high_demand_alert_title', 'Custom Demand Heading');
        $this->setAlertSetting('high_demand_alert_message', "Line one of the alert.\nLine two after a break.");
        $this->setAlertSetting('high_demand_alert_phone', '(999) 123-4567');
        $this->setAlertSetting('high_demand_alert_button_text', 'Keep My Reservation');
        $this->setAlertSetting('high_demand_alert_image', (string) $media->id);

        $this->makeProduct(true);
        $html = $this->get($this->productUrl())->assertOk()->getContent();

        $this->assertStringContainsString('Custom Demand Heading', $html);
        $this->assertStringContainsString("Line one of the alert.<br />\nLine two after a break.", $html);
        $this->assertStringContainsString('href="tel:(999) 123-4567"', $html);
        $this->assertStringContainsString('(999) 123-4567', $html);
        $this->assertStringContainsString('Keep My Reservation', $html);
        $this->assertStringContainsString($media->url, $html);
        $this->assertStringNotContainsString('lady-image.webp', $html);
    }

    // ── Phone ownership: HD editor is the only editable source ───────────────

    public function test_configured_hd_phone_wins_over_the_company_phone(): void
    {
        Setting::updateOrCreate(
            ['setting_name' => 'site_phone', 'setting_type' => 'Website Management Branding'],
            ['setting_value' => '(615) 815-6734'],
        );
        $this->setAlertSetting('high_demand_alert_phone', '(888) 777-6666');

        $this->makeProduct(true);
        $html = $this->get($this->productUrl())->assertOk()->getContent();

        // The HD popup shows ITS phone; the Custom Range modal shows the company phone
        $this->assertStringContainsString('href="tel:(888) 777-6666"', $html);
        $this->assertStringContainsString('href="tel:(615) 815-6734"', $html);
    }

    public function test_branding_page_labels_the_phone_as_company_phone_only(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.website-management.branding.index'))
            ->assertOk()
            ->assertSee('Company Phone Number')
            ->assertDontSee('High Demand Alert / Custom Range Phone Number');
    }

    public function test_branding_save_cannot_alter_the_hd_alert_phone(): void
    {
        $this->actingAsAdmin();
        $this->setAlertSetting('high_demand_alert_phone', '(888) 777-6666');

        $this->post(route('admin.website-management.branding.update'), [
            'site_name'               => 'Rent n King',
            'high_demand_alert_phone' => '(111) 222-3333',
        ])->assertRedirect();

        $this->assertSame('(888) 777-6666', $this->alertSetting('high_demand_alert_phone'));
    }

    public function test_custom_range_modal_renders_the_company_phone(): void
    {
        Setting::updateOrCreate(
            ['setting_name' => 'site_phone', 'setting_type' => 'Website Management Branding'],
            ['setting_value' => '(615) 815-6734'],
        );
        $this->makeProduct(false);

        $html = $this->get($this->productUrl())->assertOk()->getContent();

        $this->assertStringContainsString('id="customServiceOption"', $html);
        $this->assertStringContainsString('(615) 815-6734', $html);
    }

    // ── Admin editor ──────────────────────────────────────────────────────────

    public function test_editor_page_renders_with_preview(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.website-management.high-demand-alert.index'))
            ->assertOk()
            ->assertSee('High Demand Product Alert')
            ->assertSee('Alert Heading')
            ->assertSee('Alert Message')
            ->assertSee('Phone Number')
            ->assertSee('Continue Button Text')
            ->assertSee('Live Preview')
            ->assertSee('Choose from Media Library');
    }

    public function test_save_persists_all_fields_and_clears_the_cache(): void
    {
        $this->actingAsAdmin();

        // Warm the cache with defaults, then save and confirm new values flow through
        app(HighDemandAlertService::class)->config();

        $this->post(route('admin.website-management.high-demand-alert.update'), [
            'high_demand_alert_title'       => 'Saved Heading',
            'high_demand_alert_message'     => 'Saved message body.',
            'high_demand_alert_phone'       => '(615) 555-1234',
            'high_demand_alert_button_text' => 'Saved Button',
        ])->assertRedirect(route('admin.website-management.high-demand-alert.index'));

        $this->assertSame('Saved Heading', $this->alertSetting('high_demand_alert_title'));
        $this->assertSame('Saved message body.', $this->alertSetting('high_demand_alert_message'));
        $this->assertSame('(615) 555-1234', $this->alertSetting('high_demand_alert_phone'));
        $this->assertSame('Saved Button', $this->alertSetting('high_demand_alert_button_text'));

        $config = app(HighDemandAlertService::class)->config();
        $this->assertSame('Saved Heading', $config->title, 'cache must be cleared on save');
    }

    public function test_image_upload_saves_and_library_pick_saves(): void
    {
        $this->actingAsAdmin();

        // Upload path
        $this->post(route('admin.website-management.high-demand-alert.update'), [
            'high_demand_alert_image' => UploadedFile::fake()->image('alert.png', 500, 650),
        ])->assertRedirect()->assertSessionHasNoErrors();
        $uploadedId = $this->alertSetting('high_demand_alert_image');
        $this->assertNotNull(Media::find($uploadedId));

        // Library-pick path
        $picked = Media::create([
            'original_file_name' => 'picked.png', 'asset_type' => 'Public Asset',
            'folder_name' => 'high_demand_alert', 'file_name' => 'picked.png',
            'file_extension' => 'png', 'file_type' => 'image', 'mime_type' => 'image/png',
            'file_size' => 500, 'is_used' => 'Yes',
        ]);
        $this->post(route('admin.website-management.high-demand-alert.update'), [
            'high_demand_alert_image_media_id' => $picked->id,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame((string) $picked->id, $this->alertSetting('high_demand_alert_image'));

        // Restore default
        $this->post(route('admin.website-management.high-demand-alert.update'), [
            'remove_image' => 1,
        ])->assertRedirect();
        $this->assertTrue(blank($this->alertSetting('high_demand_alert_image')));
        $this->assertTrue(app(HighDemandAlertService::class)->config()->usesDefaultImage);
    }

    // ── Security & validation ─────────────────────────────────────────────────

    public function test_unsafe_html_is_sanitized_from_the_message(): void
    {
        $this->actingAsAdmin();

        $this->post(route('admin.website-management.high-demand-alert.update'), [
            'high_demand_alert_message' => 'Hello <script>alert(1)</script><strong>world</strong>',
        ])->assertRedirect();

        $saved = $this->alertSetting('high_demand_alert_message');
        $this->assertStringNotContainsString('<script', $saved);
        $this->assertStringContainsString('<strong>world</strong>', $saved, 'safe emphasis tags survive');
    }

    public function test_invalid_image_types_are_rejected(): void
    {
        $this->actingAsAdmin();

        $this->post(route('admin.website-management.high-demand-alert.update'), [
            'high_demand_alert_image' => UploadedFile::fake()->create('evil.svg', 10, 'image/svg+xml'),
        ])->assertSessionHasErrors('high_demand_alert_image');
    }

    public function test_guests_cannot_view_or_edit_the_settings(): void
    {
        $this->get(route('admin.website-management.high-demand-alert.index'))->assertRedirect();

        $this->post(route('admin.website-management.high-demand-alert.update'), [
            'high_demand_alert_title' => 'Sneaky',
        ])->assertRedirect();

        $this->assertNull($this->alertSetting('high_demand_alert_title'));
    }
}
