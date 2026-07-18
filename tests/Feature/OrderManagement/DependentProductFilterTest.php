<?php

namespace Tests\Feature\OrderManagement;

use App\Helpers\ProductFilterHelper;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dependent Category → Product filtering on the Orders and Schedule pages.
 *
 * The canonical relationship is the product_category_children pivot
 * (Product::categories()). The server validates the pair independently of
 * the client dropdown: a stale or mismatched product id degrades to the
 * wider (category-only) filter instead of applying a contradictory hidden
 * filter.
 */
class DependentProductFilterTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private ProductCategory $boomLifts;
    private ProductCategory $excavators;
    private Product $boomLift45;
    private Product $boomLift60;
    private Product $miniExcavator;
    private Order $boomOrder;
    private Order $excavatorOrder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::create([
            'first_name' => 'Filter', 'last_name' => 'Clerk',
            'email' => 'dependent-filter@test.local', 'password' => bcrypt('secret'),
        ]));

        $this->customer = Customer::create([
            'first_name' => 'Dep', 'last_name' => 'Filter',
            'email' => 'dep-filter@example.com', 'status' => 'Active',
        ]);

        $this->boomLifts  = ProductCategory::create(['title' => 'Boom Lifts']);
        $this->excavators = ProductCategory::create(['title' => 'Excavators']);

        $this->boomLift45 = $this->makeProduct('Boom Lift 45ft', $this->boomLifts);
        $this->boomLift60 = $this->makeProduct('Boom Lift 60ft', $this->boomLifts);
        $this->miniExcavator = $this->makeProduct('Mini Excavator', $this->excavators);

        $this->boomOrder      = $this->makeOrder('5100', [$this->boomLift45]);
        $this->excavatorOrder = $this->makeOrder('5200', [$this->miniExcavator]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function makeProduct(string $name, ProductCategory $category): Product
    {
        $product = Product::create([
            'product_name' => $name,
            'slug'         => str_replace(' ', '-', strtolower($name)) . '-' . uniqid(),
            'product_type' => 'Rental',
        ]);
        $product->categories()->attach($category->id);

        return $product;
    }

    /** @param Product[] $products */
    private function makeOrder(string $number, array $products): Order
    {
        $order = Order::create([
            'order_number'  => $number,
            'order_date'    => now()->toDateString(),
            'customer_id'   => $this->customer->id,
            'customer_name' => 'Dep Filter',
        ]);

        foreach ($products as $product) {
            OrderProduct::create([
                'order_id'                => $order->id,
                'product_id'              => $product->id,
                'product_name'            => $product->product_name,
                'price'                   => 100,
                'quantity'                => 1,
                'total'                   => 100,
                'delivery_date'           => now()->addDay(),
                'pickup_date'             => now()->addDays(3),
                'delivery_status'         => 'Pending',
                'pickup_status'           => 'Pending',
                'delivery_transport_mode' => 'Truck',
                'pickup_transport_mode'   => 'Truck',
                'product_data'            => [
                    'product_type'                => 'Rental',
                    'product_variant'             => 'daily',
                    'product_rental_items_prices' => [],
                    'product_option_items'        => [],
                ],
            ]);
        }

        return $order;
    }

    private function filterOrders(array $params): string
    {
        return $this->get(
            route('admin.order-management.orders.index', $params),
            ['X-Requested-With' => 'XMLHttpRequest']
        )->assertOk()->json('html');
    }

    private function filterSchedules(array $params): string
    {
        return $this->get(
            route('admin.order-management.schedules.index', $params),
            ['X-Requested-With' => 'XMLHttpRequest']
        )->assertOk()->json('html');
    }

    // ── Canonical helper ─────────────────────────────────────────────────

    public function test_category_product_map_comes_from_the_pivot(): void
    {
        $map = ProductFilterHelper::categoryProductMap();

        $this->assertEqualsCanonicalizing(
            [$this->boomLift45->id, $this->boomLift60->id],
            $map[$this->boomLifts->id]
        );
        $this->assertSame([$this->miniExcavator->id], $map[$this->excavators->id]);
    }

    public function test_category_normalization_drops_unknown_and_garbage_values(): void
    {
        $this->assertSame($this->boomLifts->id, ProductFilterHelper::normalizeCategoryId($this->boomLifts->id));
        $this->assertSame($this->boomLifts->id, ProductFilterHelper::normalizeCategoryId((string) $this->boomLifts->id));
        $this->assertNull(ProductFilterHelper::normalizeCategoryId(999999));
        $this->assertNull(ProductFilterHelper::normalizeCategoryId('abc'));
        $this->assertNull(ProductFilterHelper::normalizeCategoryId(null));
    }

    public function test_product_normalization_enforces_category_membership(): void
    {
        // No category: any existing product passes
        $this->assertSame($this->boomLift45->id, ProductFilterHelper::normalizeProductId($this->boomLift45->id, null));

        // Belongs to the category: passes
        $this->assertSame(
            $this->boomLift45->id,
            ProductFilterHelper::normalizeProductId($this->boomLift45->id, $this->boomLifts->id)
        );

        // Wrong category: dropped
        $this->assertNull(ProductFilterHelper::normalizeProductId($this->miniExcavator->id, $this->boomLifts->id));

        // Unknown / garbage: dropped
        $this->assertNull(ProductFilterHelper::normalizeProductId(999999, null));
        $this->assertNull(ProductFilterHelper::normalizeProductId('abc', null));
    }

    public function test_products_without_a_category_stay_eligible_but_in_no_category_list(): void
    {
        $uncategorized = Product::create([
            'product_name' => 'Uncategorized Widget',
            'slug'         => 'uncategorized-widget-' . uniqid(),
            'product_type' => 'Rental',
        ]);

        $this->assertArrayHasKey($uncategorized->id, ProductFilterHelper::productOptions()->all());

        foreach (ProductFilterHelper::categoryProductMap() as $ids) {
            $this->assertNotContains($uncategorized->id, $ids);
        }

        // Eligible with no category, dropped under any category
        $this->assertSame($uncategorized->id, ProductFilterHelper::normalizeProductId($uncategorized->id, null));
        $this->assertNull(ProductFilterHelper::normalizeProductId($uncategorized->id, $this->boomLifts->id));
    }

    // ── Orders page ──────────────────────────────────────────────────────

    public function test_orders_category_only_filtering(): void
    {
        $html = $this->filterOrders(['category' => $this->boomLifts->id]);

        $this->assertStringContainsString('5100', $html);
        $this->assertStringNotContainsString('5200', $html);
    }

    public function test_orders_product_only_filtering(): void
    {
        $html = $this->filterOrders(['product' => $this->miniExcavator->id]);

        $this->assertStringContainsString('5200', $html);
        $this->assertStringNotContainsString('5100', $html);
    }

    public function test_orders_category_and_product_together(): void
    {
        $html = $this->filterOrders([
            'category' => $this->boomLifts->id,
            'product'  => $this->boomLift45->id,
        ]);

        $this->assertStringContainsString('5100', $html);
        $this->assertStringNotContainsString('5200', $html);
    }

    public function test_orders_mismatched_pair_degrades_to_category_only(): void
    {
        // Stale combination (e.g. from a saved URL): Excavators + a Boom Lift
        // product. The product must be dropped — NOT applied as a hidden
        // filter that would contradict the category and return nothing.
        $html = $this->filterOrders([
            'category' => $this->excavators->id,
            'product'  => $this->boomLift45->id,
        ]);

        $this->assertStringContainsString('5200', $html, 'category-only results expected');
        $this->assertStringNotContainsString('5100', $html);
    }

    public function test_orders_unknown_ids_do_not_hide_everything(): void
    {
        $html = $this->filterOrders(['category' => 999999, 'product' => 888888]);

        // Both invalid values dropped → unfiltered list
        $this->assertStringContainsString('5100', $html);
        $this->assertStringContainsString('5200', $html);
    }

    public function test_order_with_multiple_matching_products_appears_once(): void
    {
        $multi = $this->makeOrder('5300', [$this->boomLift45, $this->boomLift60]);

        $html = $this->filterOrders(['category' => $this->boomLifts->id]);

        $this->assertSame(
            1,
            substr_count($html, 'order-row-' . $multi->unique_id),
            'an order matching on two products must render exactly one row'
        );
    }

    // ── Schedule page ────────────────────────────────────────────────────

    public function test_schedule_product_only_filtering(): void
    {
        $html = $this->filterSchedules(['product' => $this->boomLift45->id]);

        $this->assertStringContainsString('Boom Lift 45ft', $html);
        $this->assertStringNotContainsString('Mini Excavator', $html);
    }

    public function test_schedule_category_and_product_together(): void
    {
        $html = $this->filterSchedules([
            'category' => $this->boomLifts->id,
            'product'  => $this->boomLift45->id,
        ]);

        $this->assertStringContainsString('Boom Lift 45ft', $html);
        $this->assertStringNotContainsString('Mini Excavator', $html);
    }

    public function test_schedule_mismatched_pair_degrades_to_category_only(): void
    {
        $html = $this->filterSchedules([
            'category' => $this->excavators->id,
            'product'  => $this->boomLift45->id,
        ]);

        $this->assertStringContainsString('Mini Excavator', $html);
        $this->assertStringNotContainsString('Boom Lift 45ft', $html);
    }

    public function test_schedule_category_only_filtering_unchanged(): void
    {
        $html = $this->filterSchedules(['category' => $this->boomLifts->id]);

        $this->assertStringContainsString('Boom Lift 45ft', $html);
        $this->assertStringNotContainsString('Mini Excavator', $html);
    }

    public function test_schedule_product_filter_composes_with_existing_filters(): void
    {
        // Same filters the page sends by default (both schedule types, both
        // transport modes) plus the new product filter
        $html = $this->filterSchedules([
            'product'          => $this->boomLift45->id,
            'schedule_type'    => ['Delivery', 'Return'],
            'transport_mode'   => ['Truck', 'Store'],
            'order_number'     => '5100',
        ]);

        $this->assertStringContainsString('Boom Lift 45ft', $html);
        $this->assertStringNotContainsString('Mini Excavator', $html);
    }
}
