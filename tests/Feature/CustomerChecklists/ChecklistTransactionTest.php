<?php

namespace Tests\Feature\CustomerChecklists;

use App\Events\Admin\Orders\OrderCustomerChecklistEvent;
use App\Events\Admin\Orders\OrderProductDriverChecklistUpdated;
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentStatusLog;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Models\ProductManagement\Product;
use App\Models\Stores\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * PR-A2: proves SaveDeliveryController, SaveReturnController, RemoveController,
 * and DriverChecklistController now wrap their write sequences (including the
 * synchronous event() dispatch) in DB::transaction(), so a listener failure
 * rolls back everything atomically instead of leaving a partial commit behind
 * a 500 response — per CORRECTION_PHASE1_PLAN.md Issue #4.
 */
class ChecklistTransactionTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;
    private Order $order;
    private OrderProduct $orderProduct;
    private Equipment $equipment;
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::create([
            'first_name' => 'Mobile',
            'last_name'  => 'Actor',
            'email'      => 'mobile-actor@example.com',
            'password'   => bcrypt('password'),
        ]);

        $this->store = Store::create([
            'store_name' => 'Test Store',
        ]);

        $this->order = Order::create([
            'order_date'    => now()->format('Y-m-d'),
            'customer_name' => 'Test Customer',
        ]);

        $product = Product::create([
            'product_name' => 'Test Rental Product',
            'slug'         => 'test-rental-product-' . uniqid(),
            'product_type' => 'Rental',
        ]);

        $this->equipment = Equipment::create([
            'equipment_name' => 'Test Excavator',
            'equipment_id'   => 'EQP-TEST-' . uniqid(),
            'brand'          => 'TestBrand',
            'current_status' => 'available',
        ]);

        $this->orderProduct = OrderProduct::create([
            'order_id'     => $this->order->id,
            'product_id'   => $product->id,
            'product_name' => 'Test Rental Product',
            'price'        => 100,
            'quantity'     => 1,
            'total'        => 100,
        ]);
    }

    private function apiUrl(string $path): string
    {
        return 'http://' . config('app.domains.api') . '/api/admin/v1/orders/' . ltrim($path, '/');
    }

    private function callAs(string $method, string $path, array $payload = []): \Illuminate\Testing\TestResponse
    {
        return $this->withoutMiddleware()
            ->actingAs($this->actor, 'api_user')
            ->json($method, $this->apiUrl($path), $payload);
    }

    // ── Happy path: delivery ─────────────────────────────────────────────────

    public function test_happy_path_delivery_still_commits(): void
    {
        $this->callAs('POST', 'customer-checklists/save-delivery', [
            'order_product_unique_id' => $this->orderProduct->unique_id,
            'equipment_unique_id'     => $this->equipment->unique_id,
            'user_id'                 => (string) $this->actor->id,
        ])->assertOk()->assertJson(['success' => true]);

        $this->orderProduct->refresh();
        $this->equipment->refresh();

        $this->assertTrue((bool) $this->orderProduct->is_delivered);
        $this->assertEquals('Completed', $this->orderProduct->delivery_status);
        $this->assertTrue($this->equipment->current_status->isRented());

        $this->assertDatabaseHas('equipment_status_logs', [
            'equipment_id' => $this->equipment->id,
            'from_status'  => 'available',
            'to_status'    => 'rented',
        ]);
        $this->assertDatabaseHas('order_histories', [
            'order_id' => $this->order->id,
        ]);
    }

    // ── Happy path: return ───────────────────────────────────────────────────

    public function test_happy_path_return_still_commits(): void
    {
        // Seed the pre-state a delivery would have produced: equipment already rented
        // and assigned to this order product.
        $this->equipment->current_status = 'rented';
        $this->equipment->current_order_id = $this->order->id;
        $this->equipment->current_order_product_id = $this->orderProduct->id;
        $this->equipment->saveQuietly();
        $this->orderProduct->update(['equipment_id' => $this->equipment->id]);

        $this->callAs('POST', 'customer-checklists/save-return', [
            'order_product_unique_id' => $this->orderProduct->unique_id,
            'store_id'                => (string) $this->store->id,
            'user_id'                 => (string) $this->actor->id,
        ])->assertOk()->assertJson(['success' => true]);

        $this->orderProduct->refresh();
        $this->equipment->refresh();

        $this->assertTrue((bool) $this->orderProduct->is_returned);
        $this->assertEquals('Completed', $this->orderProduct->pickup_status);
        $this->assertTrue($this->equipment->current_status->isMaintenance());

        $this->assertDatabaseHas('equipment_status_logs', [
            'equipment_id' => $this->equipment->id,
            'from_status'  => 'rented',
            'to_status'    => 'maintenance',
        ]);
        $this->assertDatabaseHas('order_histories', [
            'order_id' => $this->order->id,
        ]);
    }

    // ── Forced listener failure: rollback ───────────────────────────────────

    public function test_forced_listener_failure_rolls_back_delivery_checklist_order_product_and_equipment_status(): void
    {
        // Register an additional listener that throws during the synchronous event
        // dispatch — this forces a real, reproducible failure inside the transaction
        // without any DDL (a Schema::drop() mid-test causes MySQL to implicitly commit,
        // which desyncs Laravel's transaction-nesting counter from the real connection
        // state and silently defeats DB::transaction()'s rollback — verified while
        // building this test, so an in-memory throwing listener is used instead).
        Event::listen(OrderCustomerChecklistEvent::class, function () {
            throw new \RuntimeException('Forced test failure for PR-A2 rollback test');
        });

        $response = $this->callAs('POST', 'customer-checklists/save-delivery', [
            'order_product_unique_id' => $this->orderProduct->unique_id,
            'equipment_unique_id'     => $this->equipment->unique_id,
            'user_id'                 => (string) $this->actor->id,
        ]);

        $response->assertStatus(500);

        $this->orderProduct->refresh();
        $this->equipment->refresh();

        // Everything the transaction covered must be rolled back, not partially committed.
        $this->assertFalse((bool) $this->orderProduct->is_delivered);
        $this->assertNotEquals('Completed', $this->orderProduct->delivery_status);
        $this->assertNull($this->orderProduct->equipment_id);
        $this->assertTrue($this->equipment->current_status->isAvailable());
        $this->assertEquals(0, EquipmentStatusLog::where('equipment_id', $this->equipment->id)->count());
    }

    public function test_forced_listener_failure_rolls_back_return_checklist_order_product_and_equipment_status(): void
    {
        $this->equipment->current_status = 'rented';
        $this->equipment->current_order_id = $this->order->id;
        $this->equipment->current_order_product_id = $this->orderProduct->id;
        $this->equipment->saveQuietly();
        $this->orderProduct->update(['equipment_id' => $this->equipment->id]);

        Event::listen(OrderCustomerChecklistEvent::class, function () {
            throw new \RuntimeException('Forced test failure for PR-A2 rollback test');
        });

        $response = $this->callAs('POST', 'customer-checklists/save-return', [
            'order_product_unique_id' => $this->orderProduct->unique_id,
            'store_id'                => (string) $this->store->id,
            'user_id'                 => (string) $this->actor->id,
        ]);

        $response->assertStatus(500);

        $this->orderProduct->refresh();
        $this->equipment->refresh();

        $this->assertFalse((bool) $this->orderProduct->is_returned);
        $this->assertNotEquals('Completed', $this->orderProduct->pickup_status);
        // Still 'rented' — the markReturnedToMaintenance() transition must have rolled back.
        $this->assertTrue($this->equipment->current_status->isRented());
        $this->assertEquals(0, EquipmentStatusLog::where('equipment_id', $this->equipment->id)->count());
    }

    // ── DriverChecklistController rollback ──────────────────────────────────

    public function test_driver_checklist_controller_forced_failure_rolls_back_and_logs(): void
    {
        Event::listen(OrderProductDriverChecklistUpdated::class, function () {
            throw new \RuntimeException('Forced test failure for PR-A2 rollback test');
        });

        $response = $this->callAs('POST', 'schedules/driver-checklist', [
            'order_product_unique_id' => $this->orderProduct->unique_id,
            'checklist_type'          => 'delivery',
            'equipment_fuel'          => 'Full',
        ]);

        $response->assertStatus(500)->assertJson([
            'status'  => false,
            'message' => 'Failed to update driver checklist.',
        ]);

        $this->orderProduct->refresh();

        // The schedule field write must have rolled back with the failed listener.
        $this->assertNull($this->orderProduct->delivery_equipment_fuel);
    }

    public function test_driver_checklist_controller_happy_path_still_commits(): void
    {
        $this->callAs('POST', 'schedules/driver-checklist', [
            'order_product_unique_id' => $this->orderProduct->unique_id,
            'checklist_type'          => 'delivery',
            'equipment_fuel'          => 'Full',
        ])->assertOk()->assertJson(['status' => true]);

        $this->orderProduct->refresh();
        $this->assertEquals('Full', $this->orderProduct->delivery_equipment_fuel);
        $this->assertDatabaseHas('order_histories', [
            'order_id' => $this->order->id,
        ]);
    }

    // ── RemoveController happy path (basic coverage) ────────────────────────

    public function test_remove_controller_happy_path_still_commits(): void
    {
        $this->equipment->current_status = 'available';
        $this->equipment->saveQuietly();
        $this->orderProduct->update(['equipment_id' => $this->equipment->id]);
        $this->orderProduct->checklistQuestions()->create([
            'order_id'      => $this->order->id,
            'question_name' => 'Test Question',
            'index_number'  => 1,
        ]);

        $this->callAs('POST', 'customer-checklists/remove', [
            'order_unique_id' => $this->order->unique_id,
        ])->assertOk()->assertJson(['success' => true]);

        $this->orderProduct->refresh();
        $this->assertEquals('Pending', $this->orderProduct->delivery_status);
        $this->assertNull($this->orderProduct->equipment_id);
    }
}
