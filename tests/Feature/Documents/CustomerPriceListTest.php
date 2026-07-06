<?php

namespace Tests\Feature\Documents;

use App\Models\Iam\Personnel\User;
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;
use App\Services\DocumentGenerator\DocumentGenerator;
use App\Services\DocumentGenerator\Documents\CustomerPriceListDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CustomerPriceListTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private ProductCategory $excavators;
    private ProductCategory $attachments;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'unique_id' => 'test-admin', 'employee_code' => '01',
            'first_name' => 'Admin', 'last_name' => 'User',
            'email' => 'admin@test.local', 'status' => 'Active',
        ]);

        // Categories with explicit display order: Attachments first (sort 1),
        // Excavators second (sort 2)
        $this->attachments = ProductCategory::create(['title' => 'Attachments', 'status' => 'Published', 'sort_order' => 1]);
        $this->excavators  = ProductCategory::create(['title' => 'Excavators', 'status' => 'Published', 'sort_order' => 2]);

        // Excavator products — pivot sort order deliberately reversed from
        // creation order to prove website display order wins
        $mini = $this->makeProduct('Mini Excavator', ['rental_daily' => 250, 'rental_weekend' => 375, 'rental_weekly' => 750, 'rental_monthly' => 2250, 'standard_delivery_fee' => 95, 'extended_delivery_fee' => 175]);
        $big  = $this->makeProduct('Large Excavator', ['rental_daily' => 450]);
        $this->attach($this->excavators, $big, 1);
        $this->attach($this->excavators, $mini, 2);

        // Attachment product — treated like any other product
        $auger = $this->makeProduct('Auger Attachment', ['rental_daily' => 85, 'rental_weekly' => 255]);
        $this->attach($this->attachments, $auger, 1);

        // Draft product must never appear
        $draft = $this->makeProduct('Secret Prototype', ['rental_daily' => 999], 'Draft');
        $this->attach($this->excavators, $draft, 3);

        $this->actingAs($this->admin);
    }

    private function makeProduct(string $name, array $prices = [], string $status = 'Published'): Product
    {
        return Product::create(array_merge([
            'product_name' => $name,
            'status'       => $status,
        ], $prices));
    }

    private function attach(ProductCategory $category, Product $product, int $sortOrder): void
    {
        DB::table('product_category_children')->insert([
            'product_id'          => $product->id,
            'product_category_id' => $category->id,
            'sort_order'          => $sortOrder,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);
    }

    // Form lists selectable categories
    public function test_form_lists_categories(): void
    {
        $this->get(route('admin.documents.price-list.form'))
            ->assertOk()
            ->assertSee('Customer Price List')
            ->assertSee('Excavators')
            ->assertSee('Attachments')
            ->assertSee('Generate Price List');
    }

    // Generation requires at least one category
    public function test_generation_requires_categories(): void
    {
        $this->get(route('admin.documents.price-list.generate'))
            ->assertSessionHasErrors('category_ids');
    }

    // Products grouped by category, sorted by website display order, live prices
    public function test_price_list_groups_and_sorts_by_display_order(): void
    {
        $response = $this->get(route('admin.documents.price-list.generate', [
            'category_ids' => [$this->excavators->id, $this->attachments->id],
        ]))->assertOk();

        $html = $response->getContent();

        // Category order follows category sort_order: Attachments before Excavators
        $this->assertLessThan(strpos($html, 'Excavators'), strpos($html, 'Attachments'));

        // Product order inside Excavators follows pivot sort order: Large before Mini
        $this->assertLessThan(strpos($html, 'Mini Excavator'), strpos($html, 'Large Excavator'));

        // Live pricing straight from product records
        $response->assertSee('$250.00')   // mini daily
            ->assertSee('$375.00')        // weekend special
            ->assertSee('$2,250.00')      // monthly
            ->assertSee('$95.00')         // std delivery
            ->assertSee('$175.00')        // ext delivery
            ->assertSee('$450.00')        // large daily
            ->assertSee('$85.00');        // auger daily

        // Missing prices render as a dash, never invented
        $response->assertSee('—');

        // Abbreviated delivery headers
        $response->assertSee('Std Del')->assertSee('Ext Del');
    }

    // Draft products are excluded; availability is never consulted
    public function test_only_published_products_included(): void
    {
        $response = $this->get(route('admin.documents.price-list.generate', [
            'category_ids' => [$this->excavators->id],
        ]))->assertOk();

        $response->assertDontSee('Secret Prototype')->assertDontSee('$999');
    }

    // Selecting one category excludes the others
    public function test_only_selected_categories_included(): void
    {
        $this->get(route('admin.documents.price-list.generate', [
            'category_ids' => [$this->attachments->id],
        ]))
            ->assertOk()
            ->assertSee('Auger Attachment')
            ->assertDontSee('Mini Excavator');
    }

    // Branding, generated timestamp, governing notice, and disclaimer all present
    public function test_document_shell_contains_required_elements(): void
    {
        $response = $this->get(route('admin.documents.price-list.generate', [
            'category_ids' => [$this->excavators->id],
        ]))->assertOk();

        $response->assertSee("Rent 'n King")
            ->assertSee('RentnKing.com')
            ->assertSee('Generated ' . now()->format('M j, Y'))
            ->assertSee('current pricing on RentnKing.com governs', false)
            ->assertSee('This price list is provided for general informational purposes only', false)
            ->assertSee('not a quote, estimate, reservation, or price guarantee', false)
            ->assertSee('Monday–Friday: 7:00 AM–5:00 PM', false)
            ->assertSee('Saturday: 7:00 AM–12:00 PM', false)
            ->assertSee('Sunday: Closed', false)
            ->assertSee('three days of rental often gets you seven days of use', false);
    }

    // Framework registry resolves documents and rejects unknown keys
    public function test_document_generator_registry(): void
    {
        $document = DocumentGenerator::make(CustomerPriceListDocument::KEY);
        $this->assertInstanceOf(CustomerPriceListDocument::class, $document);
        $this->assertSame('1.0', $document->version());
        $this->assertArrayHasKey(CustomerPriceListDocument::KEY, DocumentGenerator::available());

        $this->expectException(\InvalidArgumentException::class);
        DocumentGenerator::make('nonexistent-document');
    }

    // Guardrail: generating documents writes nothing — read-only over live data
    public function test_generation_writes_no_records(): void
    {
        $countsBefore = [
            'products'            => DB::table('products')->count(),
            'order_extra_charges' => DB::table('order_extra_charges')->count(),
            'order_payments'      => DB::table('order_payments')->count(),
        ];

        $this->get(route('admin.documents.price-list.generate', [
            'category_ids' => [$this->excavators->id, $this->attachments->id],
        ]))->assertOk();

        foreach ($countsBefore as $table => $count) {
            $this->assertSame($count, DB::table($table)->count(), "$table changed");
        }
    }
}
