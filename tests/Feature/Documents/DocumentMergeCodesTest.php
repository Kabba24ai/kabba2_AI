<?php

namespace Tests\Feature\Documents;

use App\Models\Configurations\Setting;
use App\Models\Iam\Personnel\User;
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;
use App\Models\Stores\HoursOfOperation;
use App\Models\Stores\Store;
use App\Services\DocumentGenerator\DocumentMergeCodes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Company/store values in generated documents are merge codes resolved from
 * Company Settings and live Store records — never hardcoded tenant text.
 * Rent 'n King strings exist only as seeded default settings data.
 */
class DocumentMergeCodesTest extends TestCase
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

    private function setSetting(string $name, string $value): void
    {
        // setting_name is globally unique; company identity lives in
        // 'Company Settings', price list text in 'Price List Settings'.
        Setting::where('setting_name', $name)
            ->firstOrFail()
            ->update(['setting_value' => $value]);
    }

    private function generate(): string
    {
        return $this->get(route('admin.documents.price-list.generate', [
            'category_ids' => [$this->category->id],
        ]))->assertOk()->getContent();
    }

    // ── Values come from settings, not code ────────────────────────

    public function test_main_url_renders_from_system_settings(): void
    {
        $this->setSetting('main_url', 'AcmeRentals.example');

        $html = $this->generate();

        $this->assertStringContainsString('AcmeRentals.example', $html);
        // Page notice keeps its wording with the dynamic website value
        $this->assertStringContainsString('current pricing on AcmeRentals.example governs', $html);
        $this->assertStringNotContainsString('RentnKing.com', $html);
    }

    public function test_company_name_renders_from_system_settings(): void
    {
        $this->setSetting('company_name', 'Acme Rentals LLC');

        $html = $this->generate();

        $this->assertStringContainsString('Acme Rentals LLC', $html);
        $this->assertStringNotContainsString('Rent &#039;n King', $html);
    }

    public function test_main_phone_renders_dynamically(): void
    {
        $this->setSetting('company_main_phone', '(555) 123-9876');

        $this->assertStringContainsString('(555) 123-9876', $this->generate());
    }

    public function test_store_locations_merge_code_renders_active_stores(): void
    {
        Store::create(['store_name' => 'North Yard', 'phone' => '(555) 111-2222', 'address' => '1 North Rd', 'city' => 'Springfield', 'zip_code' => '11111', 'status' => 'Active', 'is_primary' => 'Yes']);
        Store::create(['store_name' => 'Closed Yard', 'phone' => '(555) 333-4444', 'status' => 'Inactive']);

        $this->setSetting('price_list_disclaimer', "Locations:\n{{ store_locations }}");

        $html = $this->generate();

        $this->assertStringContainsString('North Yard', $html);
        $this->assertStringContainsString('1 North Rd, Springfield 11111', $html);
        $this->assertStringNotContainsString('Closed Yard', $html);
    }

    public function test_store_hours_render_from_structured_hours_of_operation(): void
    {
        $store = Store::create(['store_name' => 'Main Yard', 'status' => 'Active', 'is_primary' => 'Yes']);

        foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'] as $day) {
            HoursOfOperation::create(['store_id' => $store->id, 'day_name' => $day, 'is_closed' => false, 'start_time' => '08:00:00', 'end_time' => '16:30:00']);
        }
        HoursOfOperation::create(['store_id' => $store->id, 'day_name' => 'Saturday', 'is_closed' => false, 'start_time' => '09:00:00', 'end_time' => '12:00:00']);
        HoursOfOperation::create(['store_id' => $store->id, 'day_name' => 'Sunday', 'is_closed' => true, 'start_time' => null, 'end_time' => null]);

        $html = $this->generate();

        // Consecutive days with identical hours compress into ranges
        $this->assertStringContainsString('Monday–Friday: 8:00 AM–4:30 PM', $html);
        $this->assertStringContainsString('Saturday: 9:00 AM–12:00 PM', $html);
        $this->assertStringContainsString('Sunday: Closed', $html);
        // Fallback text is NOT used when structured hours exist
        $this->assertStringNotContainsString('7:00 AM–5:00 PM', $html);
    }

    public function test_store_hours_use_configured_fallback_when_no_structured_hours(): void
    {
        $this->setSetting('store_hours_fallback', "Every Day: 6:00 AM–6:00 PM");

        $this->assertStringContainsString('Every Day: 6:00 AM–6:00 PM', $this->generate());
    }

    // ── Editable disclaimer + safety ───────────────────────────────

    public function test_editable_disclaimer_renders_with_merge_codes_replaced(): void
    {
        $this->setSetting('company_name', 'Acme Rentals');
        $this->setSetting('main_url', 'Acme.example');
        $this->setSetting(
            'price_list_disclaimer',
            'Pricing on {{ main_url }} governs. Call {{ company_name }} at {{ main_phone }}. Printed {{ generated_date }}.'
        );

        $html = $this->generate();

        $this->assertStringContainsString('Pricing on Acme.example governs.', $html);
        $this->assertStringContainsString('Call Acme Rentals at', $html);
        $this->assertStringContainsString('Printed ' . now()->format('M j, Y'), $html);
        $this->assertStringNotContainsString('{{ main_url }}', $html);
    }

    public function test_unknown_merge_codes_fail_safely(): void
    {
        $this->setSetting('price_list_disclaimer', 'Known: {{ main_url }}. Unknown: {{ not_a_real_code }}.');

        $html = $this->generate();

        // Unknown code stays visible (never crashes, never invents a value)
        $this->assertStringContainsString('{{ not_a_real_code }}', $html);

        $this->assertSame(
            'Hello {{ nope }}',
            DocumentMergeCodes::apply('Hello {{ nope }}')
        );
    }

    public function test_disclaimer_html_cannot_inject_script(): void
    {
        $this->setSetting('price_list_disclaimer', 'Safe text <script>alert("xss")</script> {{ company_name }}');

        $html = $this->generate();

        $this->assertStringNotContainsString('<script>alert', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_no_hardcoded_tenant_values_after_settings_change(): void
    {
        $this->setSetting('company_name', 'Acme Rentals');
        $this->setSetting('main_url', 'Acme.example');
        $this->setSetting('company_main_phone', '(555) 000-1111');
        $this->setSetting('price_list_disclaimer', 'See {{ main_url }} or contact {{ company_name }}.');
        $this->setSetting('price_list_value_message', 'Weekly rates available.');

        $html = $this->generate();

        $this->assertStringNotContainsString('RentnKing', $html);
        $this->assertStringNotContainsString('Rent &#039;n King', $html);
        $this->assertStringNotContainsString("Rent 'n King", $html);
        $this->assertStringNotContainsString('(615) 815-6734', $html);
    }

    // ── Service unit coverage ──────────────────────────────────────

    public function test_available_codes_all_resolve_to_values(): void
    {
        $values = DocumentMergeCodes::values();

        foreach (array_keys(DocumentMergeCodes::available()) as $code) {
            $this->assertArrayHasKey($code, $values);
        }
    }
}
