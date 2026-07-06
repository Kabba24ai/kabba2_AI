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
 * PR-A1: proves the third saveQuietly() site found during the Issue #5
 * investigation (UpdateProductScheduleController — independent of
 * EquipmentStatusService) now also writes a durable EquipmentStatusLog row
 * for every real equipment status transition it performs, without double
 * logging via the still-registered EquipmentObserver.
 */
class UpdateProductScheduleEquipmentStatusLogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Order $order;
    private OrderProduct $orderProduct;
    private Equipment $equipment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'first_name' => 'Schedule',
            'last_name'  => 'Admin',
            'email'      => 'schedule-admin@example.com',
            'password'   => bcrypt('password'),
        ]);

        $this->order = Order::create([
            'order_date'    => now()->format('Y-m-d'),
            'customer_name' => 'Test Customer',
        ]);

        $this->equipment = Equipment::create([
            'equipment_name' => 'Test Skid Steer',
            'equipment_id'   => 'EQP-TEST-' . uniqid(),
            'brand'          => 'TestBrand',
            'current_status' => 'available',
        ]);

        $product = Product::create([
            'product_name' => 'Test Rental Product',
            'slug'         => 'test-rental-product-' . uniqid(),
            'product_type' => 'Rental',
        ]);

        $this->orderProduct = OrderProduct::create([
            'order_id'     => $this->order->id,
            'product_id'   => $product->id,
            'product_name' => 'Test Rental Product',
            'price'        => 100,
            'quantity'     => 1,
            'total'        => 100,
            'equipment_id' => $this->equipment->id,
        ]);
    }

    /**
     * Seed the equipment's starting status for a test WITHOUT going through a normal
     * save() — a plain update() would fire EquipmentObserver and pre-create a log row,
     * polluting exact-count assertions with a setup artifact unrelated to the fix.
     */
    private function seedEquipmentStatus(string $status): void
    {
        $this->equipment->current_status = $status;
        $this->equipment->saveQuietly();
    }

    private function updateSchedule(array $payload): \Illuminate\Testing\TestResponse
    {
        return $this->withoutMiddleware()
            ->actingAs($this->admin)
            ->putJson(route('admin.order-management.orders.update-product-schedule', [
                $this->order->unique_id,
                $this->orderProduct->unique_id,
            ]), $payload);
    }

    public function test_delivery_status_completed_writes_equipment_status_log_available_to_rented(): void
    {
        $this->updateSchedule([
            'type'            => 'delivery',
            'delivery_status' => 'Completed',
        ])->assertOk();

        $this->assertDatabaseHas('equipment_status_logs', [
            'equipment_id' => $this->equipment->id,
            'from_status'  => 'available',
            'to_status'    => 'rented',
            'changed_by'   => $this->admin->id,
        ]);
    }

    public function test_close_as_completed_writes_equipment_status_log_to_maintenance(): void
    {
        $this->updateSchedule([
            'type'            => 'delivery',
            'delivery_status' => 'Close as Completed',
        ])->assertOk();

        $this->assertDatabaseHas('equipment_status_logs', [
            'equipment_id' => $this->equipment->id,
            'from_status'  => 'available',
            'to_status'    => 'maintenance',
            'changed_by'   => $this->admin->id,
        ]);
    }

    public function test_delivery_reschedule_writes_equipment_status_log_to_maintenance(): void
    {
        $this->updateSchedule([
            'type'            => 'delivery',
            'delivery_status' => 'Reschedule',
        ])->assertOk();

        $this->assertDatabaseHas('equipment_status_logs', [
            'equipment_id' => $this->equipment->id,
            'from_status'  => 'available',
            'to_status'    => 'maintenance',
            'changed_by'   => $this->admin->id,
        ]);
    }

    public function test_delivery_pending_writes_equipment_status_log_to_available(): void
    {
        $this->seedEquipmentStatus('maintenance');

        $this->updateSchedule([
            'type'            => 'delivery',
            'delivery_status' => 'Pending',
        ])->assertOk();

        $this->assertDatabaseHas('equipment_status_logs', [
            'equipment_id' => $this->equipment->id,
            'from_status'  => 'maintenance',
            'to_status'    => 'available',
            'changed_by'   => $this->admin->id,
        ]);
    }

    public function test_pickup_status_completed_writes_equipment_status_log_to_maintenance(): void
    {
        $this->seedEquipmentStatus('rented');

        $this->updateSchedule([
            'type'          => 'return',
            'pickup_status' => 'Completed',
        ])->assertOk();

        $this->assertDatabaseHas('equipment_status_logs', [
            'equipment_id' => $this->equipment->id,
            'from_status'  => 'rented',
            'to_status'    => 'maintenance',
            'changed_by'   => $this->admin->id,
        ]);
    }

    // ── Duplicate-row prevention ────────────────────────────────────────────

    public function test_no_log_row_written_when_delivery_status_change_does_not_change_equipment_status(): void
    {
        // Equipment already 'rented'; a redundant 'Completed' call re-asserts the same status.
        $this->seedEquipmentStatus('rented');

        $this->updateSchedule([
            'type'            => 'delivery',
            'delivery_status' => 'Completed',
        ])->assertOk();

        $this->assertEquals(0, EquipmentStatusLog::where('equipment_id', $this->equipment->id)->count());
    }

    public function test_store_only_change_does_not_write_an_equipment_status_log_row(): void
    {
        // pickup_store_id-only updates hit the separate saveQuietly() call at the end of
        // the controller that changes store_id, not current_status — must not log.
        $this->updateSchedule([
            'type'            => 'return',
            'pickup_store_id' => null,
        ])->assertOk();

        $this->assertEquals(0, EquipmentStatusLog::where('equipment_id', $this->equipment->id)->count());
    }

    public function test_delivery_then_return_writes_exactly_two_log_rows_not_more(): void
    {
        $this->updateSchedule([
            'type'            => 'delivery',
            'delivery_status' => 'Completed',
        ])->assertOk();

        $this->updateSchedule([
            'type'          => 'return',
            'pickup_status' => 'Completed',
        ])->assertOk();

        $this->assertEquals(2, EquipmentStatusLog::where('equipment_id', $this->equipment->id)->count());
    }
}
