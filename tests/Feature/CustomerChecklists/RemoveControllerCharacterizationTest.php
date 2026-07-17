<?php

namespace Tests\Feature\CustomerChecklists;

use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminCategory;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminQuestion;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminQuestionAnswer;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Models\ProductManagement\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P3-8: baseline characterization tests for RemoveController — beyond PR-A2's single
 * transaction-rollback happy-path test (ChecklistTransactionTest), no other response
 * branch or side effect of this controller had dedicated coverage before this PR. This
 * suite also pins two known, not-yet-fixed defects (BUG-3, BUG-5) as they exist today,
 * so the eventual fix PRs (P3-11, P3-12) have a safety net proving the fix actually
 * changed the behavior. See docs/checklist-system-audit/P3_8_BASELINE_CHARACTERIZATION_TESTS.md.
 */
class RemoveControllerCharacterizationTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::create([
            'first_name' => 'Remove',
            'last_name'  => 'Actor',
            'email'      => 'remove-actor@example.com',
            'password'   => bcrypt('password'),
        ]);

        $this->order = Order::create([
            'order_date'    => now()->format('Y-m-d'),
            'customer_name' => 'Test Customer',
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

    private function makeOrderProduct(?int $equipmentId = null): OrderProduct
    {
        $product = Product::create([
            'product_name' => 'Test Rental Product',
            'slug'         => 'test-rental-product-' . uniqid(),
            'product_type' => 'Rental',
        ]);

        return OrderProduct::create([
            'order_id'     => $this->order->id,
            'product_id'   => $product->id,
            'product_name' => 'Test Rental Product',
            'price'        => 100,
            'quantity'     => 1,
            'total'        => 100,
            'equipment_id' => $equipmentId,
        ]);
    }

    // ── Not-found response branch ───────────────────────────────────────────

    public function test_order_not_found_returns_404(): void
    {
        $response = $this->callAs('POST', 'customer-checklists/remove', [
            'order_unique_id' => 'ORD-DOES-NOTEXIST',
        ]);

        $response->assertStatus(422); // order_unique_id fails exists:orders,unique_id first
    }

    public function test_order_soft_deleted_returns_404_from_controller(): void
    {
        $orderProduct = $this->makeOrderProduct();
        $orderUniqueId = $this->order->unique_id;
        $this->order->delete(); // soft delete — row still exists, just excluded from default scope

        $response = $this->callAs('POST', 'customer-checklists/remove', [
            'order_unique_id' => $orderUniqueId,
        ]);

        $response->assertStatus(404)->assertJson(['success' => false]);
    }

    // ── Products with no checklist rows are skipped entirely ────────────────

    public function test_products_with_no_checklist_questions_are_left_untouched(): void
    {
        $equipment = Equipment::create([
            'equipment_name' => 'Untouched Excavator',
            'equipment_id'   => 'EQP-TEST-' . uniqid(),
            'brand'          => 'TestBrand',
            'current_status' => 'available',
        ]);
        // equipment_id set, but no checklistQuestions() rows exist for this product —
        // the controller's `if ($orderProduct->checklistQuestions->isEmpty()) continue;`
        // guard means this product is skipped entirely, equipment_id included.
        $orderProduct = $this->makeOrderProduct($equipment->id);

        $this->callAs('POST', 'customer-checklists/remove', [
            'order_unique_id' => $this->order->unique_id,
        ])->assertOk()->assertJson(['success' => true]);

        $orderProduct->refresh();
        $this->assertEquals($equipment->id, $orderProduct->equipment_id, 'Untouched: no checklist rows means this product is skipped, not reverted.');
    }

    // ── Happy path: delivery-side fields reverted, equipment made available ─

    public function test_delivery_fields_are_reverted_and_equipment_made_available(): void
    {
        $equipment = Equipment::create([
            'equipment_name' => 'Test Excavator',
            'equipment_id'   => 'EQP-TEST-' . uniqid(),
            'brand'          => 'TestBrand',
            'current_status' => 'rented',
        ]);
        $orderProduct = $this->makeOrderProduct($equipment->id);
        $orderProduct->update([
            'delivery_by'     => $this->actor->id,
            'delivery_status' => 'Completed',
            'is_delivered'    => true,
            'equipment_details' => $equipment->toArray(),
            'assigned_by'     => $this->actor->id,
            'assigned_at'     => now(),
        ]);
        $orderProduct->checklistQuestions()->create([
            'order_id'      => $this->order->id,
            'question_name' => 'Test Question',
            'index_number'  => 1,
        ]);

        $this->callAs('POST', 'customer-checklists/remove', [
            'order_unique_id' => $this->order->unique_id,
        ])->assertOk()->assertJson(['success' => true]);

        $orderProduct->refresh();
        $equipment->refresh();

        $this->assertEquals('Pending', $orderProduct->delivery_status);
        $this->assertFalse((bool) $orderProduct->is_delivered);
        $this->assertNull($orderProduct->equipment_id);
        $this->assertNull($orderProduct->equipment_details);
        $this->assertNull($orderProduct->assigned_by);
        $this->assertTrue($equipment->current_status->isAvailable());
        $this->assertDatabaseHas('equipment_status_logs', [
            'equipment_id' => $equipment->id,
            'to_status'    => 'available',
        ]);
    }

    // ── BUG-5 (pre-existing, not fixed by this PR): the revert data set explicitly
    // resets is_returned to false, but leaves pickup_status/pickup_by (and every other
    // pickup_*/damage_status field) completely untouched — after a removal following a
    // return, the record ends up in a genuinely contradictory state: is_returned=false
    // alongside pickup_status still reading 'Completed' and pickup_by still populated.
    // See PHASE3_IMPLEMENTATION_PLAN.md BUG-5; P3-11's fix (pending a product decision)
    // should change this exact assertion.

    public function test_bug5_pickup_status_and_pickup_by_are_not_reverted_even_though_is_returned_is(): void
    {
        $equipment = Equipment::create([
            'equipment_name' => 'Test Excavator',
            'equipment_id'   => 'EQP-TEST-' . uniqid(),
            'brand'          => 'TestBrand',
            'current_status' => 'maintenance',
        ]);
        $orderProduct = $this->makeOrderProduct($equipment->id);
        $orderProduct->update([
            'delivery_status' => 'Completed',
            'is_delivered'    => true,
            'pickup_status'   => 'Completed',
            'is_returned'     => true,
            'pickup_by'       => $this->actor->id,
        ]);
        $orderProduct->checklistQuestions()->create([
            'order_id'      => $this->order->id,
            'question_name' => 'Test Question',
            'index_number'  => 1,
        ]);

        $this->callAs('POST', 'customer-checklists/remove', [
            'order_unique_id' => $this->order->unique_id,
        ])->assertOk()->assertJson(['success' => true]);

        $orderProduct->refresh();

        // Delivery side correctly reverted, and is_returned is explicitly reset...
        $this->assertEquals('Pending', $orderProduct->delivery_status);
        $this->assertFalse((bool) $orderProduct->is_delivered);
        $this->assertFalse((bool) $orderProduct->is_returned);
        // ...but pickup_status/pickup_by are left exactly as they were — a removal has
        // produced a genuinely contradictory state: is_returned=false alongside
        // pickup_status still reading 'Completed'. This is BUG-5's documented
        // partial-revert gap, pinned as-is.
        $this->assertEquals('Completed', $orderProduct->pickup_status);
        $this->assertEquals($this->actor->id, $orderProduct->pickup_by);
    }

    // ── BUG-3 (pre-existing, not fixed by this PR): soft-deleting checklist ──
    // questions leaves their child answer rows fully live — no cascading soft-delete
    // exists. See PHASE3_IMPLEMENTATION_PLAN.md BUG-3; a future fix should change this
    // exact assertion (answers should be soft-deleted alongside their parent question).

    public function test_bug3_soft_deleting_checklist_questions_leaves_answer_rows_live(): void
    {
        $equipment = Equipment::create([
            'equipment_name' => 'Test Excavator',
            'equipment_id'   => 'EQP-TEST-' . uniqid(),
            'brand'          => 'TestBrand',
            'current_status' => 'rented',
        ]);
        $orderProduct = $this->makeOrderProduct($equipment->id);

        $category = CustomerAdminCategory::create(['category_name' => 'Test Category']);
        $question = CustomerAdminQuestion::create([
            'question_name'          => 'Was it damaged?',
            'category_id'            => $category->id,
            'question_delivery_text' => 'Any damage at delivery?',
            'question_return_text'   => 'Any damage at return?',
        ]);
        $masterAnswer = CustomerAdminQuestionAnswer::create([
            'answer_delivery_text' => 'No',
            'answer_return_text'   => 'No',
            'question_id'          => $question->id,
        ]);

        $checklistQuestion = $orderProduct->checklistQuestions()->create([
            'order_id'      => $this->order->id,
            'question_id'   => $question->id,
            'question_name' => $question->question_name,
            'index_number'  => 1,
        ]);
        $checklistAnswer = $checklistQuestion->answers()->create([
            'order_id'     => $this->order->id,
            'question_id'  => $question->id,
            'answer_id'    => $masterAnswer->id,
            'index_number' => 1,
            'is_delivery_answer' => true,
        ]);

        $this->callAs('POST', 'customer-checklists/remove', [
            'order_unique_id' => $this->order->unique_id,
        ])->assertOk()->assertJson(['success' => true]);

        // The question row is soft-deleted (excluded from a default, non-trashed query)...
        $this->assertNull($orderProduct->checklistQuestions()->find($checklistQuestion->id));
        $this->assertNotNull($orderProduct->checklistQuestions()->withTrashed()->find($checklistQuestion->id));

        // ...but its child answer row is still fully live, still flagged as a selected
        // delivery answer, under a parent no longer in the active checklist. This is
        // BUG-3's documented orphaned-row gap, pinned as-is.
        $liveAnswer = $checklistAnswer->fresh();
        $this->assertNotNull($liveAnswer);
        $this->assertNull($liveAnswer->deleted_at);
        $this->assertTrue((bool) $liveAnswer->is_delivery_answer);
    }

    // ── Equipment status log recorded via markAvailableOnChecklistRemove ────

    public function test_equipment_status_log_records_the_revert_to_available(): void
    {
        $equipment = Equipment::create([
            'equipment_name' => 'Test Excavator',
            'equipment_id'   => 'EQP-TEST-' . uniqid(),
            'brand'          => 'TestBrand',
            'current_status' => 'damaged',
        ]);
        $orderProduct = $this->makeOrderProduct($equipment->id);
        $orderProduct->checklistQuestions()->create([
            'order_id'      => $this->order->id,
            'question_name' => 'Test Question',
            'index_number'  => 1,
        ]);

        $this->callAs('POST', 'customer-checklists/remove', [
            'order_unique_id' => $this->order->unique_id,
        ])->assertOk();

        $equipment->refresh();
        $this->assertTrue($equipment->current_status->isAvailable());
        $this->assertDatabaseHas('equipment_status_logs', [
            'equipment_id' => $equipment->id,
            'from_status'  => 'damaged',
            'to_status'    => 'available',
        ]);
    }
}
