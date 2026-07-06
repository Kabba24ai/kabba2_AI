<?php

namespace Tests\Feature\OrderManagement;

use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentStatusLog;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Models\ProductManagement\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PR-A1 follow-up: covers the 3 saveQuietly() equipment-status paths missed by
 * the original PR-A1 scope (flagged in PR-A1_REVIEW.md §3) —
 * AssignEquipmentController, RemoveEquipmentController, and Order's deleting
 * hook. All three mutate Equipment.current_status via saveQuietly(), which
 * bypasses EquipmentObserver, so each needed its own explicit
 * EquipmentStatusLog write just like EquipmentStatusService/
 * UpdateProductScheduleController already got in the original PR-A1.
 */
class EquipmentStatusLogAdditionalPathsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'first_name' => 'Assign',
            'last_name'  => 'Admin',
            'email'      => 'assign-admin@example.com',
            'password'   => bcrypt('password'),
        ]);
    }

    private function makeEquipment(string $status = 'available'): Equipment
    {
        return Equipment::create([
            'equipment_name' => 'Test Loader',
            'equipment_id'   => 'EQP-TEST-' . uniqid(),
            'brand'          => 'TestBrand',
            'current_status' => $status,
        ]);
    }

    private function makeOrderProduct(?int $equipmentId = null): array
    {
        $order = Order::create([
            'order_date'    => now()->format('Y-m-d'),
            'customer_name' => 'Test Customer',
        ]);

        $product = Product::create([
            'product_name' => 'Test Rental Product',
            'slug'         => 'test-rental-product-' . uniqid(),
            'product_type' => 'Rental',
        ]);

        $orderProduct = OrderProduct::create([
            'order_id'     => $order->id,
            'product_id'   => $product->id,
            'product_name' => 'Test Rental Product',
            'price'        => 100,
            'quantity'     => 1,
            'total'        => 100,
            'equipment_id' => $equipmentId,
        ]);

        return [$order, $orderProduct];
    }

    // ── AssignEquipmentController ───────────────────────────────────────────

    public function test_assign_equipment_writes_equipment_status_log_to_rented(): void
    {
        $equipment = $this->makeEquipment('available');
        [, $orderProduct] = $this->makeOrderProduct();

        $this->withoutMiddleware()
            ->actingAs($this->admin)
            ->postJson(route('admin.order-management.orders.assign-equipment'), [
                'order_product_unique_id' => $orderProduct->unique_id,
                'equipment_unique_id'     => $equipment->unique_id,
                'schedule_type'           => 'Delivery',
            ])
            ->assertOk();

        $this->assertDatabaseHas('equipment_status_logs', [
            'equipment_id' => $equipment->id,
            'from_status'  => 'available',
            'to_status'    => 'rented',
            'changed_by'   => $this->admin->id,
        ]);
        $this->assertEquals(1, EquipmentStatusLog::where('equipment_id', $equipment->id)->count());
    }

    // ── RemoveEquipmentController ───────────────────────────────────────────

    public function test_remove_equipment_writes_equipment_status_log_to_available(): void
    {
        $equipment = $this->makeEquipment('rented');
        [, $orderProduct] = $this->makeOrderProduct($equipment->id);
        $equipment->update(['current_order_product_id' => $orderProduct->id]);

        $this->withoutMiddleware()
            ->actingAs($this->admin)
            ->deleteJson(route('admin.order-management.orders.remove-equipment'), [
                'order_product_unique_id' => $orderProduct->unique_id,
            ])
            ->assertOk();

        $this->assertDatabaseHas('equipment_status_logs', [
            'equipment_id' => $equipment->id,
            'from_status'  => 'rented',
            'to_status'    => 'available',
            'changed_by'   => $this->admin->id,
        ]);
    }

    public function test_remove_equipment_writes_no_log_row_when_already_available(): void
    {
        // Equipment already 'available'; removal re-asserts the same status — no-op transition.
        $equipment = $this->makeEquipment('available');
        [, $orderProduct] = $this->makeOrderProduct($equipment->id);
        $equipment->update(['current_order_product_id' => $orderProduct->id]);

        $this->withoutMiddleware()
            ->actingAs($this->admin)
            ->deleteJson(route('admin.order-management.orders.remove-equipment'), [
                'order_product_unique_id' => $orderProduct->unique_id,
            ])
            ->assertOk();

        $this->assertEquals(0, EquipmentStatusLog::where('equipment_id', $equipment->id)->count());
    }

    // ── Order deleting hook ─────────────────────────────────────────────────

    public function test_deleting_order_writes_equipment_status_log_to_maintenance_for_each_assigned_equipment(): void
    {
        $this->actingAs($this->admin);

        $equipmentA = $this->makeEquipment('rented');
        $equipmentB = $this->makeEquipment('rented');
        [$order, $orderProductA] = $this->makeOrderProduct($equipmentA->id);
        $product = Product::first();
        $orderProductB = OrderProduct::create([
            'order_id'     => $order->id,
            'product_id'   => $product->id,
            'product_name' => 'Second Rental Product',
            'price'        => 50,
            'quantity'     => 1,
            'total'        => 50,
            'equipment_id' => $equipmentB->id,
        ]);

        $order->delete();

        $this->assertDatabaseHas('equipment_status_logs', [
            'equipment_id' => $equipmentA->id,
            'from_status'  => 'rented',
            'to_status'    => 'maintenance',
            'changed_by'   => $this->admin->id,
        ]);
        $this->assertDatabaseHas('equipment_status_logs', [
            'equipment_id' => $equipmentB->id,
            'from_status'  => 'rented',
            'to_status'    => 'maintenance',
            'changed_by'   => $this->admin->id,
        ]);
    }

    public function test_deleting_order_writes_no_log_row_for_equipment_already_in_maintenance(): void
    {
        $this->actingAs($this->admin);

        $equipment = $this->makeEquipment('maintenance');
        [$order, ] = $this->makeOrderProduct($equipment->id);

        $order->delete();

        $this->assertEquals(0, EquipmentStatusLog::where('equipment_id', $equipment->id)->count());
    }
}
