<?php

namespace Tests\Unit\Equipment;

use App\Enums\Equipments\EquipmentCurrentStatus;
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentStatusLog;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Models\ProductManagement\Product;
use App\Services\Equipment\EquipmentStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PR-A1: proves every EquipmentStatusService transition method now writes a
 * durable EquipmentStatusLog row (previously silently dropped because every
 * method calls Equipment::saveQuietly(), which suppresses EquipmentObserver).
 */
class EquipmentStatusServiceLogTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;
    private int $orderId;
    private int $orderProductId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::create([
            'first_name' => 'Test',
            'last_name'  => 'Actor',
            'email'      => 'equipment-status-actor@example.com',
            'password'   => bcrypt('password'),
        ]);

        // Real Order/OrderProduct rows so the FK constraints on
        // equipment.current_order_id / current_order_product_id are satisfied.
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
        ]);

        $this->orderId        = $order->id;
        $this->orderProductId = $orderProduct->id;
    }

    private function makeEquipment(string $status = 'available'): Equipment
    {
        return Equipment::create([
            'equipment_name' => 'Test Excavator',
            'equipment_id'   => 'EQP-TEST-' . uniqid(),
            'brand'          => 'TestBrand',
            'current_status' => $status,
        ]);
    }

    public function test_mark_rented_writes_equipment_status_log(): void
    {
        $equipment = $this->makeEquipment('available');

        EquipmentStatusService::markRented($equipment, orderId: $this->orderId, orderProductId: $this->orderProductId, actorId: $this->actor->id);

        $this->assertDatabaseHas('equipment_status_logs', [
            'equipment_id' => $equipment->id,
            'from_status'  => 'available',
            'to_status'    => 'rented',
            'changed_by'   => $this->actor->id,
        ]);
        $this->assertEquals(1, EquipmentStatusLog::where('equipment_id', $equipment->id)->count());
    }

    public function test_mark_returned_to_maintenance_writes_equipment_status_log(): void
    {
        $equipment = $this->makeEquipment('rented');

        EquipmentStatusService::markReturnedToMaintenance($equipment, orderId: $this->orderId, orderProductId: $this->orderProductId, actorId: $this->actor->id);

        $this->assertDatabaseHas('equipment_status_logs', [
            'equipment_id' => $equipment->id,
            'from_status'  => 'rented',
            'to_status'    => 'maintenance',
            'changed_by'   => $this->actor->id,
        ]);
    }

    public function test_mark_returned_damaged_writes_equipment_status_log(): void
    {
        $equipment = $this->makeEquipment('rented');

        EquipmentStatusService::markReturnedDamaged($equipment, orderId: $this->orderId, orderProductId: $this->orderProductId, actorId: $this->actor->id);

        $this->assertDatabaseHas('equipment_status_logs', [
            'equipment_id' => $equipment->id,
            'from_status'  => 'rented',
            'to_status'    => 'damaged',
            'changed_by'   => $this->actor->id,
        ]);
    }

    public function test_mark_available_on_checklist_remove_writes_equipment_status_log(): void
    {
        $equipment = $this->makeEquipment('rented');

        EquipmentStatusService::markAvailableOnChecklistRemove($equipment, orderId: $this->orderId, orderProductId: $this->orderProductId, actorId: $this->actor->id);

        $this->assertDatabaseHas('equipment_status_logs', [
            'equipment_id' => $equipment->id,
            'from_status'  => 'rented',
            'to_status'    => 'available',
            'changed_by'   => $this->actor->id,
        ]);
    }

    public function test_mark_available_from_rental_ready_writes_equipment_status_log(): void
    {
        $equipment = $this->makeEquipment('maintenance');

        EquipmentStatusService::markAvailableFromRentalReady($equipment, actorId: $this->actor->id);

        $this->assertDatabaseHas('equipment_status_logs', [
            'equipment_id' => $equipment->id,
            'from_status'  => 'maintenance',
            'to_status'    => 'available',
            'changed_by'   => $this->actor->id,
        ]);
    }

    public function test_mark_maintenance_from_rental_ready_writes_equipment_status_log(): void
    {
        $equipment = $this->makeEquipment('available');

        EquipmentStatusService::markMaintenanceFromRentalReady($equipment, actorId: $this->actor->id);

        $this->assertDatabaseHas('equipment_status_logs', [
            'equipment_id' => $equipment->id,
            'from_status'  => 'available',
            'to_status'    => 'maintenance',
            'changed_by'   => $this->actor->id,
        ]);
    }

    public function test_mark_damaged_from_rental_ready_writes_equipment_status_log(): void
    {
        $equipment = $this->makeEquipment('available');

        EquipmentStatusService::markDamagedFromRentalReady($equipment, actorId: $this->actor->id);

        $this->assertDatabaseHas('equipment_status_logs', [
            'equipment_id' => $equipment->id,
            'from_status'  => 'available',
            'to_status'    => 'damaged',
            'changed_by'   => $this->actor->id,
        ]);
    }

    // ── Duplicate-row prevention ────────────────────────────────────────────

    public function test_no_log_row_written_when_status_does_not_actually_change(): void
    {
        // Equipment is already 'rented'; marking it rented again is a no-op transition.
        $equipment = $this->makeEquipment('rented');

        EquipmentStatusService::markRented($equipment, orderId: $this->orderId, orderProductId: $this->orderProductId, actorId: $this->actor->id);

        $this->assertEquals(0, EquipmentStatusLog::where('equipment_id', $equipment->id)->count());
    }

    public function test_calling_a_transition_method_twice_with_a_real_change_each_time_writes_exactly_two_rows(): void
    {
        $equipment = $this->makeEquipment('available');

        EquipmentStatusService::markRented($equipment, orderId: $this->orderId, orderProductId: $this->orderProductId, actorId: $this->actor->id);
        EquipmentStatusService::markReturnedToMaintenance($equipment, orderId: $this->orderId, orderProductId: $this->orderProductId, actorId: $this->actor->id);

        $this->assertEquals(2, EquipmentStatusLog::where('equipment_id', $equipment->id)->count());
    }

    public function test_savequietly_transition_does_not_also_trigger_equipmentobserver_double_write(): void
    {
        // Regression guard: EquipmentObserver is still registered on Equipment and must
        // NOT also fire for saveQuietly()-driven transitions (saveQuietly suppresses all
        // Eloquent model events, including the observer) — otherwise this fix would
        // produce two log rows per transition instead of one.
        $equipment = $this->makeEquipment('available');

        EquipmentStatusService::markRented($equipment, orderId: $this->orderId, orderProductId: $this->orderProductId, actorId: $this->actor->id);

        $this->assertEquals(1, EquipmentStatusLog::where('equipment_id', $equipment->id)->count());
    }

    public function test_a_plain_non_quiet_save_still_produces_exactly_one_log_row_via_observer(): void
    {
        // Confirms EquipmentObserver itself is untouched by this fix: a normal save()
        // outside EquipmentStatusService still logs exactly once, not zero and not two.
        $equipment = $this->makeEquipment('available');

        $equipment->current_status = EquipmentCurrentStatus::Maintenance->value;
        $equipment->save();

        $this->assertEquals(1, EquipmentStatusLog::where('equipment_id', $equipment->id)->count());
        $this->assertDatabaseHas('equipment_status_logs', [
            'equipment_id' => $equipment->id,
            'from_status'  => 'available',
            'to_status'    => 'maintenance',
        ]);
    }
}
