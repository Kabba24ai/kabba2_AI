<?php

namespace Tests\Feature\OrderManagement;

use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Models\Orders\OrderProductChecklistQuestionAnswers;
use App\Models\ProductManagement\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P3-12A: BUG-3's cascade fix (soft-delete child checklist answers alongside
 * their parent checklist questions) extended beyond RemoveController (P3-12)
 * to the four other call sites that soft-delete `checklistQuestions` without
 * cascading to `OrderProductChecklistQuestionAnswers`:
 * `AssignEquipmentController`, `RemoveEquipmentController`, and both branches
 * of `UpdateProductScheduleController` ('Reschedule' and 'Pending'). See
 * docs/checklist-system-audit/P3_12A_BUG3_COMPLETE_SOFT_DELETE_CASCADE.md.
 */
class ChecklistAnswerCascadeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'first_name' => 'Cascade',
            'last_name'  => 'Admin',
            'email'      => 'cascade-admin@example.com',
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

    private function makeOrderProduct(?int $equipmentId = null, ?Order $order = null): OrderProduct
    {
        $order ??= Order::create([
            'order_date'    => now()->format('Y-m-d'),
            'customer_name' => 'Test Customer',
        ]);

        $product = Product::create([
            'product_name' => 'Test Rental Product',
            'slug'         => 'test-rental-product-' . uniqid(),
            'product_type' => 'Rental',
        ]);

        return OrderProduct::create([
            'order_id'     => $order->id,
            'product_id'   => $product->id,
            'product_name' => 'Test Rental Product',
            'price'        => 100,
            'quantity'     => 1,
            'total'        => 100,
            'equipment_id' => $equipmentId,
        ]);
    }

    /**
     * Seeds a single stale checklist question + answer directly on the order
     * product, bypassing any real delivery/template flow — these controllers
     * delete whatever checklistQuestions() rows already exist regardless of
     * how they were created, so a minimal direct seed is enough to prove the
     * cascade.
     */
    private function seedStaleChecklistAnswer(OrderProduct $orderProduct): OrderProductChecklistQuestionAnswers
    {
        $question = $orderProduct->checklistQuestions()->create([
            'order_id'      => $orderProduct->order_id,
            'question_name' => 'Stale Question',
            'index_number'  => 1,
        ]);

        return $question->answers()->create([
            'order_id'     => $orderProduct->order_id,
            'index_number' => 1,
        ]);
    }

    private function assertAnswerSoftDeleted(OrderProductChecklistQuestionAnswers $answer): void
    {
        $this->assertNull(OrderProductChecklistQuestionAnswers::find($answer->id));
        $trashed = OrderProductChecklistQuestionAnswers::withTrashed()->find($answer->id);
        $this->assertNotNull($trashed, 'Answer must still exist (soft-deleted), not hard-deleted.');
        $this->assertNotNull($trashed->deleted_at);
    }

    // ── AssignEquipmentController ───────────────────────────────────────────

    public function test_assign_equipment_soft_deletes_stale_checklist_answers(): void
    {
        $newEquipment = $this->makeEquipment('available');
        $orderProduct = $this->makeOrderProduct(); // no equipment_id yet — passes the "already assigned" guards
        $staleAnswer  = $this->seedStaleChecklistAnswer($orderProduct);

        $this->withoutMiddleware()
            ->actingAs($this->admin)
            ->postJson(route('admin.order-management.orders.assign-equipment'), [
                'order_product_unique_id' => $orderProduct->unique_id,
                'equipment_unique_id'     => $newEquipment->unique_id,
                'schedule_type'           => 'Delivery',
            ])
            ->assertOk();

        $this->assertAnswerSoftDeleted($staleAnswer);
    }

    // ── RemoveEquipmentController ────────────────────────────────────────────

    public function test_remove_equipment_soft_deletes_checklist_answers_with_no_orphans(): void
    {
        $equipment    = $this->makeEquipment('rented');
        $orderProduct = $this->makeOrderProduct($equipment->id);
        $equipment->update(['current_order_product_id' => $orderProduct->id]);
        $answer = $this->seedStaleChecklistAnswer($orderProduct);

        $this->withoutMiddleware()
            ->actingAs($this->admin)
            ->deleteJson(route('admin.order-management.orders.remove-equipment'), [
                'order_product_unique_id' => $orderProduct->unique_id,
            ])
            ->assertOk();

        $this->assertAnswerSoftDeleted($answer);
    }

    public function test_remove_equipment_does_not_affect_another_order_products_answers(): void
    {
        $equipmentA = $this->makeEquipment('rented');
        $equipmentB = $this->makeEquipment('rented');
        $orderProductA = $this->makeOrderProduct($equipmentA->id);
        $orderProductB = $this->makeOrderProduct($equipmentB->id);
        $equipmentA->update(['current_order_product_id' => $orderProductA->id]);
        $answerA = $this->seedStaleChecklistAnswer($orderProductA);
        $answerB = $this->seedStaleChecklistAnswer($orderProductB);

        $this->withoutMiddleware()
            ->actingAs($this->admin)
            ->deleteJson(route('admin.order-management.orders.remove-equipment'), [
                'order_product_unique_id' => $orderProductA->unique_id,
            ])
            ->assertOk();

        $this->assertAnswerSoftDeleted($answerA);
        $this->assertNotNull(OrderProductChecklistQuestionAnswers::find($answerB->id));
    }

    // ── UpdateProductScheduleController: 'Reschedule' branch ────────────────

    public function test_update_product_schedule_reschedule_branch_soft_deletes_checklist_answers(): void
    {
        $order        = Order::create(['order_date' => now()->format('Y-m-d'), 'customer_name' => 'Test Customer']);
        $equipment    = $this->makeEquipment('rented');
        $orderProduct = $this->makeOrderProduct($equipment->id, $order);
        $orderProduct->update(['delivery_status' => 'Completed', 'is_delivered' => true]);
        $answer = $this->seedStaleChecklistAnswer($orderProduct);

        $this->withoutMiddleware()
            ->actingAs($this->admin)
            ->putJson(route('admin.order-management.orders.update-product-schedule', [
                $order->unique_id,
                $orderProduct->unique_id,
            ]), [
                'type'            => 'delivery',
                'delivery_status' => 'Reschedule',
            ])
            ->assertOk();

        $this->assertAnswerSoftDeleted($answer);
    }

    // ── UpdateProductScheduleController: 'Pending' branch ───────────────────

    public function test_update_product_schedule_pending_branch_soft_deletes_checklist_answers(): void
    {
        $order        = Order::create(['order_date' => now()->format('Y-m-d'), 'customer_name' => 'Test Customer']);
        $equipment    = $this->makeEquipment('rented');
        $orderProduct = $this->makeOrderProduct($equipment->id, $order);
        $orderProduct->update(['delivery_status' => 'Completed', 'is_delivered' => true]);
        $answer = $this->seedStaleChecklistAnswer($orderProduct);

        $this->withoutMiddleware()
            ->actingAs($this->admin)
            ->putJson(route('admin.order-management.orders.update-product-schedule', [
                $order->unique_id,
                $orderProduct->unique_id,
            ]), [
                'type'            => 'delivery',
                'delivery_status' => 'Pending',
            ])
            ->assertOk();

        $this->assertAnswerSoftDeleted($answer);
    }

    public function test_update_product_schedule_does_not_affect_another_order_products_answers(): void
    {
        $orderA       = Order::create(['order_date' => now()->format('Y-m-d'), 'customer_name' => 'Customer A']);
        $orderB       = Order::create(['order_date' => now()->format('Y-m-d'), 'customer_name' => 'Customer B']);
        $equipmentA   = $this->makeEquipment('rented');
        $equipmentB   = $this->makeEquipment('rented');
        $orderProductA = $this->makeOrderProduct($equipmentA->id, $orderA);
        $orderProductB = $this->makeOrderProduct($equipmentB->id, $orderB);
        $orderProductA->update(['delivery_status' => 'Completed', 'is_delivered' => true]);
        $orderProductB->update(['delivery_status' => 'Completed', 'is_delivered' => true]);
        $answerA = $this->seedStaleChecklistAnswer($orderProductA);
        $answerB = $this->seedStaleChecklistAnswer($orderProductB);

        $this->withoutMiddleware()
            ->actingAs($this->admin)
            ->putJson(route('admin.order-management.orders.update-product-schedule', [
                $orderA->unique_id,
                $orderProductA->unique_id,
            ]), [
                'type'            => 'delivery',
                'delivery_status' => 'Pending',
            ])
            ->assertOk();

        $this->assertAnswerSoftDeleted($answerA);
        $this->assertNotNull(OrderProductChecklistQuestionAnswers::find($answerB->id));
    }
}
