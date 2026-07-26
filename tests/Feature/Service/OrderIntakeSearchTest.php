<?php

namespace Tests\Feature\Service;

use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Customer-Related intake order search. The regression this pins: the order
 * search must find an order whose equipment is only SOFT-assigned through the
 * Queue Line (no hard order_products.equipment_id), which the old client-side
 * preload silently excluded. Search is server-side across all orders and
 * resolves the serviceable unit from hard OR soft assignment.
 */
class OrderIntakeSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Equipment $hardUnit;
    private Equipment $softUnit;
    private int $hardOrderId;
    private int $softOrderId;
    private int $chargeOrderId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'unique_id' => 'ois-admin', 'first_name' => 'Admin', 'last_name' => 'User',
            'email' => 'ois-admin@test.local', 'status' => 'Active',
        ]);

        $this->hardUnit = Equipment::create([
            'unique_id' => 'ois-hard', 'equipment_name' => 'Hard Loader', 'equipment_id' => 'HARD-1',
            'brand' => 'Bobcat', 'current_status' => 'available',
        ]);
        $this->softUnit = Equipment::create([
            'unique_id' => 'ois-soft', 'equipment_name' => 'Soft Loader', 'equipment_id' => 'SOFT-1',
            'brand' => 'Kubota', 'current_status' => 'available',
        ]);

        $custId = DB::table('customers')->insertGetId([
            'unique_id' => 'ois-cust', 'first_name' => 'Dolly', 'last_name' => 'Parton',
        ]);

        // Order with a HARD-assigned unit
        $this->hardOrderId = $this->makeOrder('#8001', $custId, 'Dolly Parton');
        $this->addProduct($this->hardOrderId, '8001', $this->hardUnit->id, null);

        // Order with a SOFT-assigned unit only (hard equipment_id null)
        $this->softOrderId = $this->makeOrder('#8002', $custId, 'Dolly Parton');
        $softOpId = $this->addProduct($this->softOrderId, '8002', null, null);
        DB::table('equipment_soft_assigns')->insert([
            'equipment_id' => $this->softUnit->id, 'order_id' => $this->softOrderId,
            'order_product_id' => $softOpId, 'created_at' => now(), 'updated_at' => now(),
        ]);

        // Order with no serviceable unit (charge line only)
        $this->chargeOrderId = $this->makeOrder('#8003', $custId, 'Dolly Parton');
        $this->addProduct($this->chargeOrderId, '8003', null, 'Extension Charge');

        $this->actingAs($this->admin);
    }

    private function makeOrder(string $number, int $custId, string $custName): int
    {
        return DB::table('orders')->insertGetId([
            'unique_id' => 'ois-ord-' . trim($number, '#'), 'order_number' => $number,
            'order_date' => now()->toDateString(), 'customer_name' => $custName, 'customer_id' => $custId,
        ]);
    }

    private function addProduct(int $orderId, string $tag, ?int $equipmentId, ?string $name): int
    {
        return DB::table('order_products')->insertGetId([
            'unique_id' => 'ois-op-' . $tag, 'order_id' => $orderId,
            'product_name' => $name ?? 'Rental Line', 'price' => 100, 'quantity' => 1, 'total' => 100,
            'equipment_id' => $equipmentId, 'delivery_date' => '2026-07-01',
        ]);
    }

    private function searchOrders(string $term, string $by = 'order'): array
    {
        return $this->getJson(route('admin.service-management.tickets.order-search', [
            'search' => $term, 'by' => $by,
        ]))->assertOk()->json('data');
    }

    public function test_finds_order_with_hard_assigned_unit(): void
    {
        $data = $this->searchOrders('8001');
        $order = collect($data)->firstWhere('id', $this->hardOrderId);

        $this->assertNotNull($order);
        $this->assertSame([$this->hardUnit->id], collect($order['equipment'])->pluck('id')->all());
    }

    // The core regression: soft-assigned orders were invisible before.
    public function test_finds_order_with_soft_assigned_unit(): void
    {
        $data = $this->searchOrders('8002');
        $order = collect($data)->firstWhere('id', $this->softOrderId);

        $this->assertNotNull($order, 'Soft-assigned order must be findable by intake search.');
        $this->assertSame([$this->softUnit->id], collect($order['equipment'])->pluck('id')->all());
        $this->assertSame('Soft Loader (SOFT-1)', $order['equipment'][0]['label']);
    }

    public function test_excludes_orders_with_no_serviceable_unit(): void
    {
        $data = $this->searchOrders('8003');
        $this->assertNull(collect($data)->firstWhere('id', $this->chargeOrderId));
    }

    public function test_searches_by_customer_name(): void
    {
        $data = $this->searchOrders('Dolly', 'customer');
        $ids = collect($data)->pluck('id')->all();

        $this->assertContains($this->hardOrderId, $ids);
        $this->assertContains($this->softOrderId, $ids);
    }

    // The consolidated selector (by=any, also the default) matches an order by
    // its number OR its customer name in one field.
    public function test_consolidated_search_matches_order_or_customer(): void
    {
        // By order number.
        $byNumber = $this->searchOrders('8001', 'any');
        $this->assertNotNull(collect($byNumber)->firstWhere('id', $this->hardOrderId));

        // By customer name, same field.
        $byName = collect($this->searchOrders('Dolly', 'any'))->pluck('id')->all();
        $this->assertContains($this->hardOrderId, $byName);
        $this->assertContains($this->softOrderId, $byName);

        // Absent `by` defaults to the consolidated behavior (finds by customer).
        $default = collect($this->getJson(route('admin.service-management.tickets.order-search', ['search' => 'Dolly']))
            ->assertOk()->json('data'))->pluck('id')->all();
        $this->assertContains($this->hardOrderId, $default);
    }

    public function test_short_terms_return_nothing(): void
    {
        $this->getJson(route('admin.service-management.tickets.order-search', ['search' => 'a', 'by' => 'order']))
            ->assertOk()
            ->assertJson(['data' => []]);
    }
}
