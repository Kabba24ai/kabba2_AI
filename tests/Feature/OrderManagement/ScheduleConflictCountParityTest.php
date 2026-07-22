<?php

namespace Tests\Feature\OrderManagement;

use App\Livewire\Dashboard\ScheduleSection;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Models\ProductManagement\Product;
use App\Models\Stores\Store;
use App\Services\Orders\ScheduleConflictService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The Dashboard Schedule Conflicts card and the Schedule Conflicts page must
 * always report the same "All" total. Both now consume the single canonical
 * ScheduleConflictService; this pins that parity and the screenshot
 * regression (1 overdue + 4 inventory-location = 5), which the old duplicate
 * dashboard query got wrong (it omitted Inventory Location entirely).
 */
class ScheduleConflictCountParityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Store $homeStore;
    private Store $otherStore;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'first_name' => 'Conflict', 'last_name' => 'Admin',
            'email' => 'conflict-admin@test.local', 'status' => 'Active',
        ]);
        $this->homeStore  = Store::create(['store_name' => 'Home Store', 'status' => 'Active']);
        $this->otherStore = Store::create(['store_name' => 'Other Store', 'status' => 'Active']);
        $this->product = Product::create([
            'product_name' => 'Conflict Excavator', 'slug' => 'conflict-exc-' . uniqid(), 'product_type' => 'Rental',
        ]);

        $this->actingAs($this->admin);
    }

    private function equipment(Store $home): Equipment
    {
        static $n = 0;
        $n++;
        return Equipment::create([
            'equipment_name' => 'Unit ' . $n,
            'equipment_id'   => 'EQP-CONF-' . $n . '-' . uniqid(),
            'brand'          => 'TestBrand',
            'current_status' => 'available',
            'store_id'       => $home->id,
        ]);
    }

    private function makeOrder(): Order
    {
        static $n = 9900;
        $n++;
        return Order::create([
            'order_number'  => (string) $n,
            'order_date'    => now()->format('Y-m-d'),
            'customer_name' => 'Conflict Customer',
            'grand_total'   => 100,
        ]);
    }

    private function line(array $overrides = []): OrderProduct
    {
        return OrderProduct::create(array_merge([
            'order_id'                => $this->makeOrder()->id,
            'product_id'              => $this->product->id,
            'product_name'            => $this->product->product_name,
            'price' => 100, 'quantity' => 1, 'total' => 100,
            'delivery_time'           => '09:00',
            'delivery_status'         => 'Pending',
            'pickup_status'           => 'Pending',
            'delivery_transport_mode' => 'Truck',
            'pickup_transport_mode'   => 'Truck',
            'product_data'            => ['product_type' => 'Rental', 'product_variant' => 'daily'],
        ], $overrides));
    }

    /** One upcoming hard-assigned line whose delivery store differs from the
     *  equipment's home store, with no prior rental → 1 Inventory Location conflict. */
    private function makeInventoryLocationConflict(): void
    {
        $equipment = $this->equipment($this->homeStore); // home = Home Store
        $this->line([
            'equipment_id'      => $equipment->id,
            'delivery_date'     => now()->addDays(3)->format('Y-m-d'),
            'pickup_date'       => now()->addDays(6)->format('Y-m-d'),
            'delivery_store_id' => $this->otherStore->id,   // required != expected (home)
            'pickup_store_id'   => $this->otherStore->id,
        ]);
    }

    /** One overdue piece with an imminent (within 3 days) reuse → 1 Overdue conflict.
     *  All stores equal so it does not also raise an inventory-location flag. */
    private function makeOverdueConflict(): void
    {
        $equipment = $this->equipment($this->homeStore);
        // Overdue: delivered, not yet returned, pickup date already past.
        $this->line([
            'equipment_id'      => $equipment->id,
            'delivery_date'     => now()->subDays(10)->format('Y-m-d'),
            'pickup_date'       => now()->subDays(2)->format('Y-m-d'),
            'delivery_status'   => 'Completed',
            'pickup_status'     => 'Pending',
            'delivery_store_id' => $this->homeStore->id,
            'pickup_store_id'   => $this->homeStore->id,
        ]);
        // Imminent reuse of the same equipment (delivery store = home → no location flag).
        $this->line([
            'equipment_id'      => $equipment->id,
            'delivery_date'     => now()->addDay()->format('Y-m-d'),
            'pickup_date'       => now()->addDays(4)->format('Y-m-d'),
            'delivery_store_id' => $this->homeStore->id,
            'pickup_store_id'   => $this->homeStore->id,
        ]);
    }

    private function service(): ScheduleConflictService
    {
        return app(ScheduleConflictService::class);
    }

    // ─────────────────────── The screenshot regression ───────────────────────

    public function test_one_overdue_plus_four_inventory_location_totals_five_everywhere(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $this->makeInventoryLocationConflict();
        }
        $this->makeOverdueConflict();

        $result = $this->service()->build();

        // Canonical breakdown matches the screenshot.
        $this->assertEquals(4, $result['counts']['inventory_location']);
        $this->assertEquals(1, $result['counts']['overdue']);
        $this->assertEquals(0, $result['counts']['double_bookings']);
        $this->assertEquals(0, $result['counts']['back_to_back']);
        $this->assertEquals(0, $result['counts']['damaged']);
        $this->assertEquals(0, $result['counts']['no_direct_assignment']);
        $this->assertEquals(5, $result['total']);

        // Dashboard card == canonical total (previously 2 — inventory location omitted).
        Livewire::test(ScheduleSection::class)->assertSet('scheduleConflictCount', 5);

        // Schedule Conflicts page "All" == 5 (the badge renders $totalConflicts).
        $html = $this->get(route('admin.order-management.schedule-conflicts.index'))
            ->assertOk()->getContent();
        $this->assertStringContainsString('All', $html);
        $this->assertStringContainsString('>5<', $html); // All badge count
    }

    // ─────────────────────── Parity is data-driven, not hardcoded ───────────────────────

    public function test_dashboard_count_equals_service_total_on_arbitrary_data(): void
    {
        $this->makeInventoryLocationConflict();
        $this->makeInventoryLocationConflict();
        $this->makeOverdueConflict();

        $serviceTotal = $this->service()->total();

        Livewire::test(ScheduleSection::class)->assertSet('scheduleConflictCount', $serviceTotal);
        $this->assertEquals(3, $serviceTotal);
    }

    public function test_inventory_location_conflicts_are_counted_by_the_dashboard(): void
    {
        // The exact category the old dashboard query dropped.
        $this->makeInventoryLocationConflict();

        $this->assertEquals(1, $this->service()->build()['counts']['inventory_location']);
        Livewire::test(ScheduleSection::class)->assertSet('scheduleConflictCount', 1);
    }

    public function test_zero_conflicts_reports_zero_on_both_surfaces(): void
    {
        // A clean upcoming line delivered from its own home store — no conflict.
        $equipment = $this->equipment($this->homeStore);
        $this->line([
            'equipment_id'      => $equipment->id,
            'delivery_date'     => now()->addDays(2)->format('Y-m-d'),
            'pickup_date'       => now()->addDays(5)->format('Y-m-d'),
            'delivery_store_id' => $this->homeStore->id,
            'pickup_store_id'   => $this->homeStore->id,
        ]);

        $this->assertEquals(0, $this->service()->total());
        Livewire::test(ScheduleSection::class)->assertSet('scheduleConflictCount', 0);
    }

    public function test_soft_deleted_orders_are_excluded_from_both_surfaces(): void
    {
        $this->makeInventoryLocationConflict();
        $this->assertEquals(1, $this->service()->total());

        // Soft-delete the underlying order → the conflict disappears everywhere.
        Order::latest('id')->first()->delete();

        $this->assertEquals(0, $this->service()->total());
        Livewire::test(ScheduleSection::class)->assertSet('scheduleConflictCount', 0);
    }

    public function test_view_all_link_targets_the_schedule_conflicts_page(): void
    {
        $html = Livewire::test(ScheduleSection::class)->html();

        $this->assertStringContainsString(
            route('admin.order-management.schedule-conflicts.index'),
            $html,
        );
    }
}
