<?php

namespace Tests\Feature\Cart;

use App\Helpers\CartHelper;
use App\Models\Configurations\Setting;
use App\Models\Locations\State;
use App\Models\Orders\Order;
use App\Models\ProductManagement\Product;
use App\Models\Stores\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Phase 3 hardening — full checkout on the gateway-free COD path proves the
 * Custom delivery selection survives order creation as a historical snapshot
 * (order_products columns + product_data JSON) that later configuration
 * changes cannot alter.
 */
class CheckoutCustomOrderSnapshotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\Configurations\SettingSeeder::class);

        foreach ([
            'distance_unit'           => 'Miles',
            'custom_2_delivery_range' => '60',
        ] as $name => $value) {
            Setting::where('setting_type', 'Product Settings')
                ->where('setting_name', $name)->update(['setting_value' => $value]);
        }
    }

    public function test_a_cod_checkout_snapshots_the_custom_selection_onto_the_order(): void
    {
        // Fake only the order events (mail/notifications); Eloquent model
        // events must keep running so unique_ids generate
        Event::fake([
            \App\Events\Front\Checkout\OrderPlacedEvent::class,
            \App\Events\Front\Checkout\OrderPlacedEmailEvent::class,
        ]);
        Queue::fake();

        $state = State::create(['name' => 'Tennessee', 'slug' => 'tennessee', 'abbreviation' => 'TN']);
        Store::create([
            'unique_id' => Str::uuid()->toString(), 'store_name' => 'Main Yard',
            'address' => '1 Yard Rd', 'state_id' => $state->id, 'city' => 'Nashville',
            'zip_code' => '37201', 'is_primary' => 'Yes', 'status' => 'Active',
        ]);

        $product = Product::create([
            'unique_id' => Str::uuid()->toString(),
            'product_name' => 'Checkout Snapshot Tester',
            'slug' => 'checkout-snapshot-tester',
            'product_type' => 'Rental', 'status' => 'Published',
            'rental_daily' => 100,
            'in_store_pickup' => 'Yes', 'delivery_and_pickup' => 'Yes',
            'standard_delivery_fee' => 89, 'extended_delivery_fee' => 124,
            'custom_2_delivery_fee' => 124,
        ]);

        // The cart stores the server-built item; checkout re-posts it verbatim
        $cartItem = CartHelper::buildCartSummary(['cart_items' => [[
            'product_unique_id' => $product->unique_id,
            'product_type' => 'Rental', 'product_variant' => 'daily',
            'quantity' => 1,
            // Day <= 12 keeps the date parseable under the app's d/m/Y display
            // format at every parse point in the pipeline
            'delivery_date' => now()->addYear()->startOfYear()->addDays(9)->format('d/m/Y'),
            'service_method' => 'Delivery',
            'distance_type' => 'Custom', 'custom_tier' => 'custom_2',
            'service_option' => 'Delivery + Pickup',
            'product_option_items' => [], 'product_rental_items' => [],
        ]]])['cart_items'][0];

        $response = $this->postJson('http://' . config('app.domains.front') . '/checkout', [
            'billingFirstName' => 'Cart', 'billingLastName' => 'Tester',
            'billingEmail' => 'cart-tester@example.test', 'billingPhone' => '(615) 555-0101',
            'billingAddress' => '42 Test Ln', 'billingState' => $state->id,
            'billingCity' => 'Nashville', 'billingZip' => '37201',
            'sameAsBilling' => 'Yes',
            'payment' => 'COD',
            'cart' => json_encode([$cartItem]),
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $order = Order::latest('id')->firstOrFail();
        $orderProduct = $order->products()->firstOrFail();

        // Snapshot columns
        $this->assertSame('Custom', $orderProduct->distance_type);
        $this->assertSame('60 Miles', $orderProduct->distance_range);
        $this->assertSame('Delivery + Pickup', $orderProduct->service_option);

        // Snapshot JSON: tier identifier, one-way rate, final amount
        $data = $orderProduct->product_data;
        $this->assertSame('custom_2', $data['custom_tier']);
        $this->assertEquals(124.0, $data['delivery_one_way_rate']);
        $this->assertEquals(248.0, $data['service_option_price']);

        // Later configuration changes must not alter the created order
        Setting::where('setting_type', 'Product Settings')
            ->where('setting_name', 'custom_2_delivery_range')->update(['setting_value' => '75']);
        Setting::where('setting_type', 'Product Settings')
            ->where('setting_name', 'distance_unit')->update(['setting_value' => 'Kilometers']);
        $product->update(['custom_2_delivery_fee' => 999]);

        $orderProduct->refresh();
        $this->assertSame('60 Miles', $orderProduct->distance_range);
        $this->assertEquals(248.0, $orderProduct->product_data['service_option_price']);
        $this->assertEquals(124.0, $orderProduct->product_data['delivery_one_way_rate']);
    }

    public function test_a_legacy_standard_order_row_remains_readable(): void
    {
        // Pre-feature order rows have no custom_tier anywhere; reading them
        // through the model must not error and displays stored values as-is
        $order = Order::create([
            'order_number' => 'LEGACY-1001',
            'customer_name' => 'Legacy Customer',
            'status' => 'Confirmed',
            'sub_total' => 100, 'tax_total' => 9.75, 'grand_total' => 109.75,
        ]);

        $legacyProduct = Product::create([
            'unique_id' => Str::uuid()->toString(),
            'product_name' => 'Legacy Product', 'slug' => 'legacy-product',
            'product_type' => 'Rental', 'status' => 'Published',
        ]);

        $order->products()->create([
            'unique_id' => 'ORD-SCH-LEGACY1',
            'product_id' => $legacyProduct->id,
            'product_name' => 'Legacy Product',
            'price' => 100, 'quantity' => 1,
            'sub_total' => 100, 'tax' => 9.75, 'total' => 109.75,
            'service_method' => 'Delivery',
            'service_option' => 'Delivery + Pickup',
            'distance_type' => 'Custom',
            'distance_range' => '30 Miles',
            'product_data' => ['service_option_price' => 248.0],
        ]);

        $legacy = $order->products()->first();
        $this->assertSame('30 Miles', $legacy->distance_range);
        $this->assertArrayNotHasKey('custom_tier', $legacy->product_data);
        $this->assertEquals(248.0, $legacy->product_data['service_option_price']);
    }
}
