<?php

namespace Tests\Feature\Orders;

use App\Models\Configurations\Setting;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Extension child-order visibility (Phase 1): a child order like "3153-A"
 * resolves to its parent through reference_order_number, renders the parent's
 * rental context read-only on its own page, and qualifies under the Orders
 * page Category / Product / Equipment-ID filters when the PARENT matches —
 * while keeping its own identity (order number "3153-A", no copied products).
 */
class ExtensionParentVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private User $employee;
    private Order $parent;
    private ProductCategory $category;
    private Product $product;
    private Equipment $equipment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'Parent', 'last_name' => 'Visibility',
            'email' => 'parent-visibility@example.com', 'status' => 'Active',
        ]);

        $this->employee = User::create([
            'first_name' => 'Filter', 'last_name' => 'Clerk',
            'email' => 'filter-clerk@example.com', 'password' => bcrypt('secret'),
        ]);

        $this->category = ProductCategory::create(['title' => 'Telehandlers']);

        $this->product = Product::create([
            'product_name' => 'Telehandler - 42ft',
            'slug'         => 'telehandler-42ft-' . uniqid(),
            'product_type' => 'Rental',
        ]);
        $this->product->categories()->attach($this->category->id);

        $this->equipment = Equipment::create([
            'unique_id'           => 'eq-jcb-th2',
            'equipment_name'      => 'JCB TH-2',
            'equipment_id'        => 'JCB-TH-2',
            'brand'               => 'JCB',
            'serial_number'       => 'SN-3153',
            'product_category_id' => $this->category->id,
        ]);

        $this->parent = Order::create([
            'order_number'  => '3153',
            'order_date'    => now()->toDateString(),
            'customer_id'   => $this->customer->id,
            'customer_name' => 'Parent Visibility',
        ]);

        OrderProduct::create([
            'order_id'     => $this->parent->id,
            'product_id'   => $this->product->id,
            'product_name' => 'Telehandler - 42ft',
            'price'        => 500,
            'quantity'     => 1,
            'total'        => 500,
            'equipment_id' => $this->equipment->id,
            'product_data' => [
                'product_type'                => 'Rental',
                'product_variant'             => 'daily',
                'product_rental_items_prices' => [],
                'product_option_items'        => [],
            ],
        ]);

        $this->actingAs($this->employee);
    }

    /** Create an extension child through the real endpoint. */
    private function createExtensionFor(Order $order): Order
    {
        // Full-middleware post: withoutMiddleware() would persist for the whole
        // test instance and strip the session later page renders depend on
        $this->postJson(
            route('admin.order-management.orders.extension.store', ['unique_id' => $order->unique_id]),
            [
                'description'        => 'One extra week',
                'base_amount'        => '150.00',
                'add_tax'            => false,
                'responsible_person' => $this->employee->id,
            ]
        )->assertOk()->assertJson(['success' => true]);

        return Order::where('order_number', 'like', $order->order_number . '-%')
            ->latest('id')->firstOrFail();
    }

    /** A second, unrelated parent (different category/product/equipment) with its own extension child. */
    private function createUnrelatedFamily(): array
    {
        $category = ProductCategory::create(['title' => 'Excavators']);
        $product  = Product::create([
            'product_name' => 'Mini Excavator',
            'slug'         => 'mini-excavator-' . uniqid(),
            'product_type' => 'Rental',
        ]);
        $product->categories()->attach($category->id);

        $equipment = Equipment::create([
            'unique_id'           => 'eq-kub-u35',
            'equipment_name'      => 'Kubota U35',
            'equipment_id'        => 'KUB-U35',
            'brand'               => 'Kubota',
            'serial_number'       => 'SN-4200',
            'product_category_id' => $category->id,
        ]);

        $parent = Order::create([
            'order_number'  => '4200',
            'order_date'    => now()->toDateString(),
            'customer_id'   => $this->customer->id,
            'customer_name' => 'Parent Visibility',
        ]);

        OrderProduct::create([
            'order_id'     => $parent->id,
            'product_id'   => $product->id,
            'product_name' => 'Mini Excavator',
            'price'        => 300,
            'quantity'     => 1,
            'total'        => 300,
            'equipment_id' => $equipment->id,
            'product_data' => ['product_type' => 'Rental', 'product_rental_items_prices' => [], 'product_option_items' => []],
        ]);

        return [$parent, $this->createExtensionFor($parent)];
    }

    /** Hit the Orders index as the ajax table request and return the rendered table HTML. */
    private function filterOrders(array $params): string
    {
        $response = $this->get(
            route('admin.order-management.orders.index', $params),
            ['X-Requested-With' => 'XMLHttpRequest']
        )->assertOk();

        return $response->json('html');
    }

    // ── Step 1: canonical relationship ─────────────────────────────────

    public function test_extension_child_resolves_to_parent_via_reference_order(): void
    {
        $child = $this->createExtensionFor($this->parent);

        $this->assertSame('3153-A', $child->order_number);
        $this->assertSame('3153', $child->reference_order_number);
        $this->assertTrue($child->referenceOrder->is($this->parent));
        $this->assertTrue($child->isExtensionChild());
        $this->assertFalse($this->parent->isExtensionChild());
    }

    public function test_reorder_with_reference_is_not_an_extension_child(): void
    {
        // Reorders carry reference_order_number but a fresh sequential number
        $reorder = Order::create([
            'order_number'           => '9000',
            'reference_order_number' => '3153',
            'order_date'             => now()->toDateString(),
            'customer_id'            => $this->customer->id,
            'customer_name'          => 'Parent Visibility',
        ]);

        $this->assertFalse($reorder->isExtensionChild());
        $this->assertTrue(Order::extensionChildren()->where('id', $reorder->id)->doesntExist());
    }

    // ── Step 2: Rental Equipment card on the child page ────────────────

    public function test_child_page_renders_parent_rental_equipment_card(): void
    {
        foreach (['payment_api_public_key', 'payment_api_key'] as $name) {
            Setting::create([
                'setting_type' => 'Payment Settings', 'value_type' => 'password',
                'setting_name' => $name, 'setting_title' => $name, 'setting_value' => 'test',
            ]);
        }

        $child = $this->createExtensionFor($this->parent);

        $response = $this->get(route('admin.order-management.orders.edit', $child->unique_id))
            ->assertOk()
            ->assertSee('Rental Equipment')
            ->assertSee('Telehandler - 42ft')
            ->assertSee('JCB TH-2')
            ->assertSee('JCB-TH-2')
            ->assertSee('Telehandlers')
            ->assertSee('#3153', false)
            ->assertSee(route('admin.order-management.orders.edit', $this->parent->unique_id), false);

        // Read-only: the card holds no form controls. Everything between the
        // card heading and the next section heading must be free of inputs.
        $html = $response->getContent();
        $cardStart = strpos($html, 'Rental Equipment');
        $cardEnd   = strpos($html, 'Equipment Orders', $cardStart);
        $cardHtml  = substr($html, $cardStart, $cardEnd - $cardStart);
        $this->assertStringNotContainsString('<input', $cardHtml);
        $this->assertStringNotContainsString('<select', $cardHtml);
        $this->assertStringNotContainsString('<textarea', $cardHtml);
    }

    public function test_card_absent_on_parent_and_no_parent_line_copied_to_child(): void
    {
        foreach (['payment_api_public_key', 'payment_api_key'] as $name) {
            Setting::create([
                'setting_type' => 'Payment Settings', 'value_type' => 'password',
                'setting_name' => $name, 'setting_title' => $name, 'setting_value' => 'test',
            ]);
        }

        $child = $this->createExtensionFor($this->parent);

        // Parent (non-extension) page: no Rental Equipment card
        $this->get(route('admin.order-management.orders.edit', $this->parent->unique_id))
            ->assertOk()
            ->assertDontSee('Rental Equipment');

        // Rendering the child page copies nothing onto the child's own rows
        $this->get(route('admin.order-management.orders.edit', $child->unique_id))->assertOk();
        $this->assertSame(0, $child->products()->count());
        $this->assertSame(1, $this->parent->products()->count());
    }

    // ── Step 3: parent-aware Orders filters ────────────────────────────

    public function test_category_filter_returns_parent_and_extension_child(): void
    {
        $child = $this->createExtensionFor($this->parent);
        [, $unrelatedChild] = $this->createUnrelatedFamily();

        $html = $this->filterOrders(['category' => $this->category->id]);

        $this->assertStringContainsString('3153', $html);
        $this->assertStringContainsString('3153-A', $html);
        $this->assertStringContainsString('Extension Charge', $html); // child keeps its own identity
        $this->assertStringNotContainsString('4200', $html);          // unrelated parent + child excluded
    }

    public function test_product_filter_returns_parent_and_extension_child(): void
    {
        $this->createExtensionFor($this->parent);
        $this->createUnrelatedFamily();

        $html = $this->filterOrders(['product' => $this->product->id]);

        $this->assertStringContainsString('3153', $html);
        $this->assertStringContainsString('3153-A', $html);
        $this->assertStringNotContainsString('4200', $html);
    }

    public function test_equipment_id_filter_returns_parent_and_extension_child(): void
    {
        $this->createExtensionFor($this->parent);
        $this->createUnrelatedFamily();

        $html = $this->filterOrders(['equipment_id_search' => 'JCB-TH-2']);

        $this->assertStringContainsString('3153', $html);
        $this->assertStringContainsString('3153-A', $html);
        $this->assertStringNotContainsString('4200', $html);
    }

    public function test_reorder_does_not_ride_along_on_parent_match(): void
    {
        // A reorder referencing 3153 but carrying a DIFFERENT product must not
        // appear when filtering by the parent's category (pre-existing behavior)
        $otherCategory = ProductCategory::create(['title' => 'Scissor Lifts']);
        $otherProduct  = Product::create([
            'product_name' => 'Scissor Lift 19ft',
            'slug'         => 'scissor-lift-19ft-' . uniqid(),
            'product_type' => 'Rental',
        ]);
        $otherProduct->categories()->attach($otherCategory->id);

        $reorder = Order::create([
            'order_number'           => '9000',
            'reference_order_number' => '3153',
            'order_date'             => now()->toDateString(),
            'customer_id'            => $this->customer->id,
            'customer_name'          => 'Parent Visibility',
        ]);
        OrderProduct::create([
            'order_id'     => $reorder->id,
            'product_id'   => $otherProduct->id,
            'product_name' => 'Scissor Lift 19ft',
            'price'        => 200,
            'quantity'     => 1,
            'total'        => 200,
            'product_data' => ['product_type' => 'Rental', 'product_rental_items_prices' => [], 'product_option_items' => []],
        ]);

        $html = $this->filterOrders(['category' => $this->category->id]);

        $this->assertStringContainsString('3153', $html);
        $this->assertStringNotContainsString('9000', $html);

        // …and it still matches on its OWN products, exactly as before
        $ownMatch = $this->filterOrders(['category' => $otherCategory->id]);
        $this->assertStringContainsString('9000', $ownMatch);
        $this->assertStringNotContainsString('3153-', $ownMatch);
    }

    public function test_non_extension_orders_filter_exactly_as_before(): void
    {
        [$unrelatedParent] = $this->createUnrelatedFamily();

        // Direct match still works, non-matching parent still excluded
        $html = $this->filterOrders(['category' => $this->category->id]);
        $this->assertStringContainsString('3153', $html);
        $this->assertStringNotContainsString('4200', $html);

        // No filters: everything is listed
        $all = $this->filterOrders([]);
        $this->assertStringContainsString('3153', $all);
        $this->assertStringContainsString('4200', $all);
    }
}
