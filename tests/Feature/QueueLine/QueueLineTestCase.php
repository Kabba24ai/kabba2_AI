<?php

namespace Tests\Feature\QueueLine;

use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\MaintenanceManagement\EquipmentSoftAssign;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Models\ProductManagement\Product;
use App\Models\Stores\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Shared production-shaped fixtures for the Queue Line Phase 1 suites.
 * Rows are built exactly the way checkout/admin build them (Rental
 * product_data snapshot, transport modes, delivery leg fields).
 */
abstract class QueueLineTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Product $orderedProduct;
    protected Store $storeNorth;
    protected Store $storeSouth;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'first_name' => 'Queue', 'last_name' => 'Admin',
            'email' => 'queue-admin@test.local', 'status' => 'Active',
        ]);

        $this->orderedProduct = Product::create([
            'product_name' => 'Boom Lift 40ft',
            'slug'         => 'boom-lift-40ft-' . uniqid(),
            'product_type' => 'Rental',
        ]);

        $this->storeNorth = Store::create(['store_name' => 'North Store', 'status' => 'Active']);
        $this->storeSouth = Store::create(['store_name' => 'South Store', 'status' => 'Active']);

        $this->actingAs($this->admin);
    }

    protected function makeOrder(array $overrides = []): Order
    {
        static $n = 9900;
        $n++;

        return Order::create(array_merge([
            'order_number'  => (string) $n,
            'order_date'    => now()->format('Y-m-d'),
            'customer_name' => 'Queue Customer',
            'grand_total'   => 100,
        ], $overrides));
    }

    /** Eligible outbound rental row: Pending, due today, Truck, North store. */
    protected function makeRow(?Order $order = null, array $overrides = [], array $optionItems = []): OrderProduct
    {
        $order ??= $this->makeOrder();

        return OrderProduct::create(array_merge([
            'order_id'                => $order->id,
            'product_id'              => $this->orderedProduct->id,
            'product_name'            => $this->orderedProduct->product_name,
            'price'                   => 100,
            'quantity'                => 1,
            'total'                   => 100,
            'delivery_date'           => now()->format('Y-m-d'),
            'delivery_time'           => '09:00',
            'delivery_status'         => 'Pending',
            'pickup_status'           => 'Pending',
            'delivery_transport_mode' => 'Truck',
            'pickup_transport_mode'   => 'Truck',
            'delivery_store_id'       => $this->storeNorth->id,
            'pickup_store_id'         => $this->storeNorth->id,
            'product_data'            => [
                'product_type'                => 'Rental',
                'product_variant'             => 'daily',
                'product_rental_items_prices' => [],
                'product_option_items'        => $optionItems,
            ],
        ], $overrides));
    }

    protected function makeEquipment(array $overrides = []): Equipment
    {
        return Equipment::create(array_merge([
            'equipment_name' => 'Unit ' . uniqid(),
            'equipment_id'   => 'EQP-QL-' . uniqid(),
            'brand'          => 'TestBrand',
            'current_status' => 'available',
        ], $overrides));
    }

    /** Soft-assign a unit to the row — the staged-outbound relationship. */
    protected function softAssign(OrderProduct $row, ?Equipment $equipment = null): Equipment
    {
        $equipment ??= $this->makeEquipment([
            'assigned_product_id' => $row->product_id, // direct by default
        ]);

        EquipmentSoftAssign::create([
            'equipment_id'     => $equipment->id,
            'order_id'         => $row->order_id,
            'order_product_id' => $row->id,
            'assigned_by'      => $this->admin->id,
        ]);

        return $equipment;
    }

    /** Ids of the rows the board would show (unsorted). */
    protected function boardIds(?int $storeId = null): array
    {
        return \App\Services\QueueLine\QueueLineEligibility::boardQuery($storeId)
            ->pluck('order_products.id')->all();
    }
}
