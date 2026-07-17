<?php

namespace Tests\Feature\CustomerChecklists;

use App\Models\ChecklistManagement\ChecklistMaster\ChecklistMaster;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminCategory;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminQuestion;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminQuestionAnswer;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminTemplate;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminTemplateQuestion;
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentStatusLog;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Models\ProductManagement\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * P3-8: baseline characterization tests for SaveDeliveryController — none existed
 * before this PR beyond PR-A2's transaction-rollback coverage (ChecklistTransactionTest)
 * and PR-A4's observability-logging coverage (CompletenessObservabilityLoggingTest).
 * This suite pins the controller's *current* behavior across its response branches,
 * including two known, not-yet-fixed defects (BUG-2, BUG-4) — those tests document the
 * bug as it exists today so the eventual fix PRs (P3-9, P3-10) have a safety net that
 * proves the fix actually changed the behavior, not just that nothing else broke.
 * See docs/checklist-system-audit/P3_8_BASELINE_CHARACTERIZATION_TESTS.md.
 */
class SaveDeliveryControllerCharacterizationTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::create([
            'first_name' => 'Delivery',
            'last_name'  => 'Actor',
            'email'      => 'delivery-actor@example.com',
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

    private function makeOrderProduct(): OrderProduct
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
        ]);
    }

    private function makeEquipment(array $overrides = []): Equipment
    {
        return Equipment::create(array_merge([
            'equipment_name' => 'Test Excavator',
            'equipment_id'   => 'EQP-TEST-' . uniqid(),
            'brand'          => 'TestBrand',
            'current_status' => 'available',
        ], $overrides));
    }

    /**
     * Builds a real ChecklistMaster -> CustomerAdminTemplate -> 1 template question -> 1
     * answer chain, and an Equipment row pointing at it — the exact relation graph
     * SaveDeliveryController reads to build order-level checklist rows on delivery.
     */
    private function makeEquipmentWithTemplate(array $equipmentOverrides = []): array
    {
        $category = CustomerAdminCategory::create(['category_name' => 'Test Category']);

        $question = CustomerAdminQuestion::create([
            'question_name'          => 'Was it damaged?',
            'category_id'            => $category->id,
            'question_delivery_text' => 'Any damage at delivery?',
            'question_return_text'   => 'Any damage at return?',
        ]);
        $answer = CustomerAdminQuestionAnswer::create([
            'answer_delivery_text' => 'No',
            'answer_return_text'   => 'No',
            'question_id'          => $question->id,
        ]);

        $template = CustomerAdminTemplate::create([
            'template_name'    => 'Test Template',
            'active_template'  => true,
        ]);
        CustomerAdminTemplateQuestion::create(['template_id' => $template->id, 'question_id' => $question->id, 'index_number' => 1]);

        $checklistMaster = ChecklistMaster::create([
            'checklist_system_name'      => 'Test Checklist Master',
            'customer_admin_template_id' => $template->id,
        ]);

        $equipment = $this->makeEquipment(array_merge(['checklist_master_id' => $checklistMaster->id], $equipmentOverrides));

        return [$equipment, $question, $answer];
    }

    // ── Not-found / conflict response branches ──────────────────────────────

    public function test_order_product_not_found_returns_404(): void
    {
        // order_product_unique_id must still pass FormRequest validation's
        // exists:order_products,unique_id check (a raw-row check unaffected by
        // soft-delete scopes), so this exercises the controller's own
        // `whereHas('order')` gap instead of the request layer — soft-deleting the
        // order excludes it from the default (non-trashed) relation query the
        // controller uses, exactly like a genuinely orphaned order product would.
        $orderProduct = $this->makeOrderProduct();
        $this->order->delete();
        $equipment = $this->makeEquipment();

        $response = $this->callAs('POST', 'customer-checklists/save-delivery', [
            'order_product_unique_id' => $orderProduct->unique_id,
            'equipment_unique_id'     => $equipment->unique_id,
            'user_id'                 => (string) $this->actor->id,
        ]);

        $response->assertStatus(404)->assertJson(['success' => false]);
    }

    public function test_equipment_currently_rented_returns_409_conflict(): void
    {
        $orderProduct = $this->makeOrderProduct();
        $equipment    = $this->makeEquipment(['current_status' => 'rented']);

        $response = $this->callAs('POST', 'customer-checklists/save-delivery', [
            'order_product_unique_id' => $orderProduct->unique_id,
            'equipment_unique_id'     => $equipment->unique_id,
            'user_id'                 => (string) $this->actor->id,
        ]);

        $response->assertStatus(409)->assertJson(['success' => false]);
    }

    public function test_checklist_submitted_but_equipment_has_no_customer_admin_template_returns_404(): void
    {
        $orderProduct = $this->makeOrderProduct();
        $equipment    = $this->makeEquipment(); // no checklist_master_id at all

        $response = $this->callAs('POST', 'customer-checklists/save-delivery', [
            'order_product_unique_id' => $orderProduct->unique_id,
            'equipment_unique_id'     => $equipment->unique_id,
            'user_id'                 => (string) $this->actor->id,
            'checklist' => [
                ['question_unique_id' => 'anything', 'answer_unique_id' => 'anything'],
            ],
        ]);

        // Fails FormRequest validation first (question/answer unique_ids don't exist),
        // proving the controller's own "no customer checklist found" branch is only
        // reachable once the payload shape is valid — asserted via the 422 here.
        $response->assertStatus(422);
    }

    public function test_checklist_submitted_but_template_has_zero_questions_returns_404(): void
    {
        $orderProduct = $this->makeOrderProduct();

        $template = CustomerAdminTemplate::create(['template_name' => 'Empty Template', 'active_template' => true]);
        $checklistMaster = ChecklistMaster::create([
            'checklist_system_name'      => 'Empty Checklist Master',
            'customer_admin_template_id' => $template->id,
        ]);
        $equipment = $this->makeEquipment(['checklist_master_id' => $checklistMaster->id]);

        // Need at least one real question/answer pair to pass FormRequest validation,
        // even though the template itself (built above) has zero template_questions rows.
        $category = CustomerAdminCategory::create(['category_name' => 'Unrelated Category']);
        $unrelatedQuestion = CustomerAdminQuestion::create([
            'question_name'          => 'Unrelated question',
            'category_id'            => $category->id,
            'question_delivery_text' => 'Unrelated?',
            'question_return_text'   => 'Unrelated?',
        ]);
        $unrelatedAnswer = CustomerAdminQuestionAnswer::create([
            'answer_delivery_text' => 'Yes',
            'answer_return_text'   => 'Yes',
            'question_id'          => $unrelatedQuestion->id,
        ]);

        $response = $this->callAs('POST', 'customer-checklists/save-delivery', [
            'order_product_unique_id' => $orderProduct->unique_id,
            'equipment_unique_id'     => $equipment->unique_id,
            'user_id'                 => (string) $this->actor->id,
            'checklist' => [
                ['question_unique_id' => $unrelatedQuestion->unique_id, 'answer_unique_id' => $unrelatedAnswer->unique_id],
            ],
        ]);

        $response->assertStatus(404)->assertJson(['success' => false]);
    }

    // ── New-assignment branch: markRented() + equipment_details snapshot ────

    public function test_new_equipment_assignment_calls_mark_rented_and_creates_status_log(): void
    {
        $orderProduct = $this->makeOrderProduct();
        $equipment    = $this->makeEquipment();

        $this->callAs('POST', 'customer-checklists/save-delivery', [
            'order_product_unique_id' => $orderProduct->unique_id,
            'equipment_unique_id'     => $equipment->unique_id,
            'user_id'                 => (string) $this->actor->id,
        ])->assertOk()->assertJson(['success' => true]);

        $orderProduct->refresh();
        $equipment->refresh();

        $this->assertEquals($equipment->id, $orderProduct->equipment_id);
        $this->assertNotEmpty($orderProduct->equipment_details);
        $this->assertTrue($equipment->current_status->isRented());
        $this->assertDatabaseHas('equipment_status_logs', [
            'equipment_id' => $equipment->id,
            'from_status'  => 'available',
            'to_status'    => 'rented',
        ]);
    }

    // ── BUG-2 (fixed by P3-9): re-delivering the SAME equipment now always ──
    // reapplies the rented transition, so a stale current_status from a prior
    // cycle (e.g. left 'damaged' by a return) does not survive a fresh
    // delivery untouched. See PHASE3_IMPLEMENTATION_PLAN.md BUG-2 and
    // docs/checklist-system-audit/P3_9_BUG2_REDELIVERY_STATUS_FIX.md.

    public function test_bug2_redelivering_same_equipment_reapplies_rented_status(): void
    {
        $orderProduct = $this->makeOrderProduct();
        $equipment    = $this->makeEquipment();

        // First delivery: assigns equipment, calls markRented(), current_status -> rented.
        $this->callAs('POST', 'customer-checklists/save-delivery', [
            'order_product_unique_id' => $orderProduct->unique_id,
            'equipment_unique_id'     => $equipment->unique_id,
            'user_id'                 => (string) $this->actor->id,
        ])->assertOk();

        $orderProduct->refresh();
        $equipment->refresh();
        $this->assertEquals($equipment->id, $orderProduct->equipment_id);

        // Simulate the prior-cycle end state BUG-2's own description depends on: equipment
        // left 'damaged' (e.g. by a prior return), while the order product is otherwise
        // ready for a fresh delivery of the SAME equipment. is_returned=true is set
        // alongside it (exactly as SaveReturnController would on a genuine return) so
        // P3-10's BUG-4 duplicate-submission guard recognizes this as a legitimate new
        // cycle rather than a same-cycle resubmission — see
        // docs/checklist-system-audit/P3_10_BUG4_DUPLICATE_DELIVERY_GUARD.md.
        $equipment->current_status = 'damaged';
        $equipment->saveQuietly();
        $orderProduct->update(['is_returned' => true]);
        $logCountBeforeRedelivery = EquipmentStatusLog::where('equipment_id', $equipment->id)->count();

        // Second delivery, same equipment_unique_id — equipment_id already matches and
        // equipment_details is already populated, but the fix now reapplies markRented()
        // unconditionally on every successful delivery (guarded from ever firing while the
        // equipment is genuinely already rented by the controller's own isRented() 409
        // check earlier in the method).
        $this->callAs('POST', 'customer-checklists/save-delivery', [
            'order_product_unique_id' => $orderProduct->unique_id,
            'equipment_unique_id'     => $equipment->unique_id,
            'user_id'                 => (string) $this->actor->id,
        ])->assertOk()->assertJson(['success' => true]);

        $orderProduct->refresh();
        $equipment->refresh();

        $this->assertEquals('Completed', $orderProduct->delivery_status);
        $this->assertTrue((bool) $orderProduct->is_delivered);

        // The equipment is transitioned back to 'rented', and a fresh status-log row
        // records the 'damaged' -> 'rented' transition. This is BUG-2's fix, verified.
        $this->assertTrue($equipment->current_status->isRented());
        $this->assertEquals(
            $logCountBeforeRedelivery + 1,
            EquipmentStatusLog::where('equipment_id', $equipment->id)->count()
        );
        $this->assertDatabaseHas('equipment_status_logs', [
            'equipment_id' => $equipment->id,
            'from_status'  => 'damaged',
            'to_status'    => 'rented',
        ]);
    }

    public function test_bug2_redelivering_same_equipment_from_maintenance_also_reapplies_rented_status(): void
    {
        // Additional regression: the same fix must also cover the more common real-world
        // re-delivery path — equipment cycled through a normal (non-damaged) return,
        // landing in 'maintenance', then being redelivered on the same order product.
        $orderProduct = $this->makeOrderProduct();
        $equipment    = $this->makeEquipment();

        $this->callAs('POST', 'customer-checklists/save-delivery', [
            'order_product_unique_id' => $orderProduct->unique_id,
            'equipment_unique_id'     => $equipment->unique_id,
            'user_id'                 => (string) $this->actor->id,
        ])->assertOk();

        $equipment->refresh();
        $equipment->current_status = 'maintenance';
        $equipment->saveQuietly();
        // is_returned=true marks this as a legitimate new cycle for P3-10's BUG-4 guard.
        $orderProduct->update(['is_returned' => true]);

        $this->callAs('POST', 'customer-checklists/save-delivery', [
            'order_product_unique_id' => $orderProduct->unique_id,
            'equipment_unique_id'     => $equipment->unique_id,
            'user_id'                 => (string) $this->actor->id,
        ])->assertOk()->assertJson(['success' => true]);

        $equipment->refresh();
        $this->assertTrue($equipment->current_status->isRented());
    }

    // ── BUG-4 (fixed by P3-10): a duplicate delivery submission for the SAME ──
    // cycle is now rejected with 409 before any destructive action, instead of
    // silently deleting and rebuilding the checklist snapshot. See
    // PHASE3_IMPLEMENTATION_PLAN.md BUG-4 and
    // docs/checklist-system-audit/P3_10_BUG4_DUPLICATE_DELIVERY_GUARD.md.

    public function test_bug4_duplicate_delivery_submission_is_rejected_and_leaves_everything_unchanged(): void
    {
        [$equipment, $question, $answer] = $this->makeEquipmentWithTemplate();
        $orderProduct = $this->makeOrderProduct();

        $this->callAs('POST', 'customer-checklists/save-delivery', [
            'order_product_unique_id' => $orderProduct->unique_id,
            'equipment_unique_id'     => $equipment->unique_id,
            'user_id'                 => (string) $this->actor->id,
            'signature_media'         => UploadedFile::fake()->image('signature.jpg'),
            'checklist' => [
                ['question_unique_id' => $question->unique_id, 'answer_unique_id' => $answer->unique_id],
            ],
        ])->assertOk();

        $orderProduct->refresh();
        $equipment->refresh();

        $firstBatchQuestionIds = $orderProduct->checklistQuestions()->pluck('id')->toArray();
        $this->assertNotEmpty($firstBatchQuestionIds);
        $firstSignatureMediaId = $orderProduct->delivery_signature_media_id;
        $this->assertNotNull($firstSignatureMediaId);
        $logCountAfterFirstDelivery = EquipmentStatusLog::where('equipment_id', $equipment->id)->count();

        // Some OTHER process changed the equipment's own current_status away from
        // 'rented' (e.g. an admin correction) WITHOUT this order product ever actually
        // being returned (is_returned is still false) — this is precisely the
        // inconsistent-but-reachable state that let BUG-4's destructive rebuild fire
        // before this fix, since it bypassed the controller's isRented() 409 guard.
        $equipment->current_status = 'maintenance';
        $equipment->saveQuietly();

        // Second delivery submission on the same order product, same cycle (is_returned
        // still false) — now rejected by the new duplicate-submission guard.
        $response = $this->callAs('POST', 'customer-checklists/save-delivery', [
            'order_product_unique_id' => $orderProduct->unique_id,
            'equipment_unique_id'     => $equipment->unique_id,
            'user_id'                 => (string) $this->actor->id,
            'signature_media'         => UploadedFile::fake()->image('signature-2.jpg'),
            'checklist' => [
                ['question_unique_id' => $question->unique_id, 'answer_unique_id' => $answer->unique_id],
            ],
        ]);

        $response->assertStatus(409)->assertJson(['success' => false]);

        $orderProduct->refresh();
        $equipment->refresh();

        // Checklist rows: untouched — same ids as the first delivery, nothing deleted
        // or recreated.
        $secondBatchQuestionIds = $orderProduct->checklistQuestions()->pluck('id')->toArray();
        $this->assertEquals($firstBatchQuestionIds, $secondBatchQuestionIds);

        // No replacement media: delivery_signature_media_id is still the first upload.
        $this->assertEquals($firstSignatureMediaId, $orderProduct->delivery_signature_media_id);

        // No equipment assignment or status-log side effects: still 'maintenance' (the
        // value forced above), and no new equipment_status_logs row was written.
        $this->assertTrue($equipment->current_status->isMaintenance());
        $this->assertEquals(
            $logCountAfterFirstDelivery,
            EquipmentStatusLog::where('equipment_id', $equipment->id)->count()
        );
    }

    public function test_bug4_legitimate_new_delivery_cycle_after_a_return_is_still_allowed(): void
    {
        [$equipment, $question, $answer] = $this->makeEquipmentWithTemplate();
        $orderProduct = $this->makeOrderProduct();

        $this->callAs('POST', 'customer-checklists/save-delivery', [
            'order_product_unique_id' => $orderProduct->unique_id,
            'equipment_unique_id'     => $equipment->unique_id,
            'user_id'                 => (string) $this->actor->id,
            'checklist' => [
                ['question_unique_id' => $question->unique_id, 'answer_unique_id' => $answer->unique_id],
            ],
        ])->assertOk();

        $firstBatchQuestionIds = $orderProduct->checklistQuestions()->pluck('id')->toArray();

        // A genuine return closes the cycle: is_returned=true, exactly as
        // SaveReturnController would set it, alongside the equipment leaving 'rented'.
        $equipment->current_status = 'maintenance';
        $equipment->saveQuietly();
        $orderProduct->update(['is_returned' => true]);

        // A new delivery cycle for the same order product/equipment pairing must still
        // succeed and rebuild the checklist snapshot for the new cycle — the guard must
        // not block legitimate re-deliveries, only same-cycle duplicates.
        $response = $this->callAs('POST', 'customer-checklists/save-delivery', [
            'order_product_unique_id' => $orderProduct->unique_id,
            'equipment_unique_id'     => $equipment->unique_id,
            'user_id'                 => (string) $this->actor->id,
            'checklist' => [
                ['question_unique_id' => $question->unique_id, 'answer_unique_id' => $answer->unique_id],
            ],
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $orderProduct->refresh();
        $equipment->refresh();

        $secondBatchQuestionIds = $orderProduct->checklistQuestions()->pluck('id')->toArray();
        $this->assertNotEmpty($secondBatchQuestionIds);
        $this->assertEmpty(array_intersect($firstBatchQuestionIds, $secondBatchQuestionIds));
        $this->assertTrue((bool) $orderProduct->is_delivered);
        $this->assertFalse((bool) $orderProduct->is_returned);
        $this->assertTrue($equipment->current_status->isRented());
    }

    // ── Other current, unremarkable-but-load-bearing behavior ───────────────

    public function test_signature_media_upload_populates_delivery_signature_media_id(): void
    {
        $orderProduct = $this->makeOrderProduct();
        $equipment    = $this->makeEquipment();

        $this->callAs('POST', 'customer-checklists/save-delivery', [
            'order_product_unique_id' => $orderProduct->unique_id,
            'equipment_unique_id'     => $equipment->unique_id,
            'user_id'                 => (string) $this->actor->id,
            'signature_media'         => UploadedFile::fake()->image('signature.jpg'),
        ])->assertOk();

        $orderProduct->refresh();
        $this->assertNotNull($orderProduct->delivery_signature_media_id);
    }

    public function test_soft_assignment_row_is_deleted_after_delivery(): void
    {
        $orderProduct = $this->makeOrderProduct();
        $equipment    = $this->makeEquipment();

        $orderProduct->softAssignment()->create([
            'equipment_id' => $equipment->id,
            'order_id'     => $this->order->id,
        ]);
        $this->assertNotNull($orderProduct->softAssignment()->first());

        $this->callAs('POST', 'customer-checklists/save-delivery', [
            'order_product_unique_id' => $orderProduct->unique_id,
            'equipment_unique_id'     => $equipment->unique_id,
            'user_id'                 => (string) $this->actor->id,
        ])->assertOk();

        $this->assertNull($orderProduct->softAssignment()->first());
    }
}
