<?php

namespace Tests\Feature\WebsiteManagement;

use App\Helpers\CommonFrontDataHelper;
use App\Models\Configurations\Setting;
use App\Models\Iam\Personnel\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Rental Agreement Header consolidation.
 *
 * The three header lines shown atop the customer signing page are managed
 * ONLY in Settings → Terms & Conditions. The Branding page no longer shows
 * or saves them; storage is unchanged (same 'Website Management Branding'
 * settings rows) so existing data and the front consumer keep working.
 */
class TermsHeaderConsolidationTest extends TestCase
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

    private function headerSetting(string $name): ?string
    {
        return Setting::where('setting_name', $name)
            ->where('setting_type', 'Website Management Branding')
            ->value('setting_value');
    }

    // ── Terms page: new structure ────────────────────────────────────────────

    public function test_terms_page_shows_header_card_and_terms_library(): void
    {
        $this->get(route('admin.terms-and-conditions.index'))
            ->assertOk()
            ->assertSee('Rental Agreement Header')
            ->assertSee('Terms &amp; Conditions Line 1', false)
            ->assertSee('Terms &amp; Conditions Line 3', false)
            ->assertSee('Preview Rental Agreement')
            ->assertSee('Save Header')
            ->assertSee('Terms Library')
            ->assertSee('Create Terms');
    }

    public function test_header_save_updates_the_settings_rows(): void
    {
        $this->from(route('admin.terms-and-conditions.index'))
            ->post(route('admin.terms-and-conditions.header.update'), [
                'terms_condition_text_1' => 'Rent n King Rental Agreement',
                'terms_condition_text_2' => 'www.RentnKing.com',
                'terms_condition_text_3' => 'Development 360, Inc',
            ])
            ->assertRedirect(route('admin.terms-and-conditions.index'));

        $this->assertSame('Rent n King Rental Agreement', $this->headerSetting('terms_condition_text_1'));
        $this->assertSame('www.RentnKing.com', $this->headerSetting('terms_condition_text_2'));
        $this->assertSame('Development 360, Inc', $this->headerSetting('terms_condition_text_3'));
    }

    public function test_saved_header_lines_reach_the_front_branding_settings(): void
    {
        $this->post(route('admin.terms-and-conditions.header.update'), [
            'terms_condition_text_1' => 'Front Visible Header',
            'terms_condition_text_2' => 'line-two.example',
            'terms_condition_text_3' => 'Line Three Co',
        ]);

        // Same accessor the customer signing page reads through
        $branding = CommonFrontDataHelper::brandingSettings();

        $this->assertSame('Front Visible Header', $branding['terms_condition_text_1'] ?? null);
        $this->assertSame('line-two.example', $branding['terms_condition_text_2'] ?? null);
        $this->assertSame('Line Three Co', $branding['terms_condition_text_3'] ?? null);
    }

    public function test_header_lines_can_be_cleared(): void
    {
        $this->post(route('admin.terms-and-conditions.header.update'), [
            'terms_condition_text_1' => 'Something',
            'terms_condition_text_2' => 'Else',
            'terms_condition_text_3' => 'Here',
        ]);

        $this->post(route('admin.terms-and-conditions.header.update'), [
            'terms_condition_text_1' => 'Only Line One',
            'terms_condition_text_2' => '',
            'terms_condition_text_3' => '',
        ]);

        $this->assertSame('Only Line One', $this->headerSetting('terms_condition_text_1'));
        $this->assertTrue(blank($this->headerSetting('terms_condition_text_2')));
        $this->assertTrue(blank($this->headerSetting('terms_condition_text_3')));
    }

    // ── Branding page: section fully retired ────────────────────────────────

    public function test_branding_page_no_longer_shows_terms_configuration(): void
    {
        $this->get(route('admin.website-management.branding.index'))
            ->assertOk()
            ->assertDontSee('Terms and Conditions (Order)')
            ->assertDontSee('terms_condition_text_1')
            ->assertSee('Branding Settings');
    }

    public function test_branding_save_cannot_change_the_header_lines(): void
    {
        Setting::updateOrCreate(
            ['setting_name' => 'terms_condition_text_1', 'setting_type' => 'Website Management Branding'],
            ['setting_value' => 'Canonical Header Line'],
        );

        $this->post(route('admin.website-management.branding.update'), [
            'site_name'              => 'Rent n King',
            'terms_condition_text_1' => 'Injected Via Branding',
        ])->assertRedirect();

        $this->assertSame(
            'Canonical Header Line',
            $this->headerSetting('terms_condition_text_1'),
            'Branding save must not write the Rental Agreement Header — Settings → Terms & Conditions is the only editor'
        );
    }

    // ── Single administrative location ───────────────────────────────────────

    public function test_only_the_terms_module_exposes_a_header_save_route(): void
    {
        $this->assertTrue(Route::has('admin.terms-and-conditions.header.update'));

        // The retired Branding editor had no dedicated route — the shared
        // branding update simply ignores the fields now (asserted above).
        $this->assertTrue(Route::has('admin.website-management.branding.update'));
    }
}
