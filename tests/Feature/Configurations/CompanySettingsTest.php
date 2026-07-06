<?php

namespace Tests\Feature\Configurations;

use App\Models\Configurations\Setting;
use App\Models\Iam\Personnel\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanySettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The System Configuration page renders every settings tab, so it
        // needs the full seeded settings set (this also proves the Company
        // Settings group is registered in SettingSeeder).
        $this->seed(\Database\Seeders\Configurations\SettingSeeder::class);

        $this->actingAs(User::create([
            'unique_id' => 'test-admin', 'employee_code' => '01',
            'first_name' => 'Admin', 'last_name' => 'User',
            'email' => 'admin@test.local', 'status' => 'Active',
        ]));
    }

    public function test_company_tab_renders_identity_fields_only(): void
    {
        $this->get(route('admin.configurations.index'))
            ->assertOk()
            ->assertSee('Company Identity')
            ->assertSee('Company Display Name')
            ->assertSee('Main URL / Website')
            ->assertSee('Store Hours (fallback text)')
            // Price List document text moved to Products → Price List → Document Text
            ->assertDontSee('price_list_disclaimer')
            ->assertDontSee('price_list_value_message')
            ->assertDontSee('Price List — Disclaimer');
    }

    public function test_saving_company_settings_updates_identity_values(): void
    {
        $this->post(route('admin.configurations.save-company-settings'), [
            'company_name'         => 'Acme Rentals',
            'main_url'             => 'Acme.example',
            'company_main_phone'   => '(555) 000-1111',
            'company_sales_phone'  => '(555) 000-2222',
            'store_hours_fallback' => "Daily: 8–5",
        ])->assertRedirect();

        $get = fn (string $name) => Setting::where('setting_type', 'Company Settings')
            ->where('setting_name', $name)->value('setting_value');

        $this->assertSame('Acme Rentals', $get('company_name'));
        $this->assertSame('Acme.example', $get('main_url'));
        $this->assertSame('(555) 000-1111', $get('company_main_phone'));
    }
}
