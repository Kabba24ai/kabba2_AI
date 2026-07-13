<?php

namespace Tests\Feature\RentalReadyChecklists;

use App\Models\ChecklistManagement\ChecklistMaster\ChecklistMaster;
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentRentalReadyChecklistQuestion;
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentRentalReadyTemplate;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistCategory;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestion;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestionAnswer;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistTemplate;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistTemplateQuestion;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Models\ProductManagement\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Monolog\Handler\TestHandler;
use Tests\TestCase;

/**
 * PR-B3 (Phase 2): proves the two disabled validation guards in
 * RentalReadyChecklists\SaveController and RentalReadyChecklists\IndexController were
 * replaced with observability-only logging (or removed, where confirmed obsolete)
 * without changing any HTTP response, status code, or saved data — per Phase 2
 * decision D3. See docs/checklist-system-audit/PR-B3_VALIDATION_GUARDS.md.
 */
class ValidationGuardObservabilityTest extends TestCase
{
    use RefreshDatabase;

    private User $employee;
    private TestHandler $apiErrorsHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employee = User::create([
            'first_name' => 'Inspector',
            'last_name'  => 'User',
            'email'      => 'inspector-user@example.com',
            'password'   => bcrypt('password'),
        ]);

        $this->apiErrorsHandler = new TestHandler();
        \Illuminate\Support\Facades\Log::channel('api_errors')->getLogger()->pushHandler($this->apiErrorsHandler);
    }

    private function apiUrl(string $path): string
    {
        return 'http://' . config('app.domains.api') . '/api/admin/v1/' . ltrim($path, '/');
    }

    private function callAs(string $method, string $path, array $payload = []): \Illuminate\Testing\TestResponse
    {
        return $this->withoutMiddleware()
            ->actingAs($this->employee, 'api_user')
            ->json($method, $this->apiUrl($path), $payload);
    }

    /**
     * Builds a real ChecklistMaster -> RentalReadyChecklistTemplate -> 2 template
     * questions (one required, one optional) -> answers chain, and Equipment pointing
     * at it — the exact relation graph both controllers under test read.
     */
    private function makeEquipmentWithRentalReadyTemplate(string $status = 'available'): array
    {
        $category = RentalReadyChecklistCategory::create(['category_name' => 'Test Category']);

        $requiredQuestion = RentalReadyChecklistQuestion::create([
            'question_name'     => 'Required question',
            'category_id'       => $category->id,
            'required_question' => true,
        ]);
        $requiredAnswer = RentalReadyChecklistQuestionAnswer::create([
            'answer_name' => 'Rental Ready',
            'question_id' => $requiredQuestion->id,
            'type'        => 'Rental Ready',
            'index_number' => 1,
        ]);

        $optionalQuestion = RentalReadyChecklistQuestion::create([
            'question_name'     => 'Optional question',
            'category_id'       => $category->id,
            'required_question' => false,
        ]);
        $optionalAnswer = RentalReadyChecklistQuestionAnswer::create([
            'answer_name' => 'Rental Ready',
            'question_id' => $optionalQuestion->id,
            'type'        => 'Rental Ready',
            'index_number' => 1,
        ]);

        $template = RentalReadyChecklistTemplate::create([
            'template_name'    => 'Test Rental Ready Template',
            'active_template'  => true,
        ]);
        RentalReadyChecklistTemplateQuestion::create(['template_id' => $template->id, 'question_id' => $requiredQuestion->id, 'index_number' => 1]);
        RentalReadyChecklistTemplateQuestion::create(['template_id' => $template->id, 'question_id' => $optionalQuestion->id, 'index_number' => 2]);

        $checklistMaster = ChecklistMaster::create([
            'checklist_system_name'   => 'Test Checklist Master',
            'rental_ready_template_id' => $template->id,
        ]);

        $equipment = Equipment::create([
            'equipment_name'      => 'Test Excavator',
            'equipment_id'        => 'EQP-TEST-' . uniqid(),
            'brand'               => 'TestBrand',
            'current_status'      => $status,
            'checklist_master_id' => $checklistMaster->id,
        ]);

        return [$equipment, $requiredQuestion, $requiredAnswer, $optionalQuestion, $optionalAnswer];
    }

    // ── SaveController: unanswered required question ───────────────────────

    public function test_save_logs_when_a_question_is_left_unanswered(): void
    {
        [$equipment, , , $optionalQuestion, $optionalAnswer] = $this->makeEquipmentWithRentalReadyTemplate();

        // Only the optional question is answered — the required one is left out entirely.
        $response = $this->callAs('POST', 'orders/rental-ready-checklists/save-rental-ready', [
            'equipment_unique_id' => $equipment->unique_id,
            'user_id'             => (string) $this->employee->id,
            'equipment_hours'     => '100',
            'checklist' => [
                ['question_unique_id' => $optionalQuestion->unique_id, 'answer_unique_id' => $optionalAnswer->unique_id],
            ],
        ]);

        // Behavior unchanged: still succeeds, still 200, despite the unanswered required question.
        $response->assertOk()->assertJson(['success' => true]);

        $this->assertTrue($this->apiErrorsHandler->hasWarningThatContains('Rental Ready checklist saved despite unanswered questions'));
        $record = $this->apiErrorsHandler->getRecords()[0];
        $this->assertEquals($equipment->id, $record->context['equipment_id']);
        $this->assertSame(1, $record->context['unanswered_count']);
    }

    public function test_save_happy_path_all_answered_does_not_log(): void
    {
        [$equipment, $requiredQuestion, $requiredAnswer, $optionalQuestion, $optionalAnswer] = $this->makeEquipmentWithRentalReadyTemplate();

        $response = $this->callAs('POST', 'orders/rental-ready-checklists/save-rental-ready', [
            'equipment_unique_id' => $equipment->unique_id,
            'user_id'             => (string) $this->employee->id,
            'equipment_hours'     => '100',
            'checklist' => [
                ['question_unique_id' => $requiredQuestion->unique_id, 'answer_unique_id' => $requiredAnswer->unique_id],
                ['question_unique_id' => $optionalQuestion->unique_id, 'answer_unique_id' => $optionalAnswer->unique_id],
            ],
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertFalse($this->apiErrorsHandler->hasWarningRecords());
    }

    // ── SaveController: equipment still rejected while rented (unchanged, live guard) ──

    public function test_save_still_rejects_currently_rented_equipment(): void
    {
        [$equipment, $requiredQuestion, $requiredAnswer] = $this->makeEquipmentWithRentalReadyTemplate('rented');

        $response = $this->callAs('POST', 'orders/rental-ready-checklists/save-rental-ready', [
            'equipment_unique_id' => $equipment->unique_id,
            'user_id'             => (string) $this->employee->id,
            'equipment_hours'     => '100',
            'checklist' => [
                ['question_unique_id' => $requiredQuestion->unique_id, 'answer_unique_id' => $requiredAnswer->unique_id],
            ],
        ]);

        // Untouched by PR-B3 — this is SaveController's own separate, live, enforced guard
        // (ApiErrorCode::EquipmentCurrentlyRented maps to 403, confirmed in app/Enums/Api/ApiErrorCode.php).
        $response->assertStatus(403);
    }

    // ── IndexController: currently-rented equipment is still listed, but logged ────

    private function makeRentedEquipmentWithOrderProduct(): array
    {
        [$equipment, $requiredQuestion, $requiredAnswer] = $this->makeEquipmentWithRentalReadyTemplate('rented');

        $customer = Customer::create(['first_name' => 'Test', 'last_name' => 'Customer', 'email' => 'guard-test-customer@example.com']);
        $order = Order::create(['order_date' => now()->format('Y-m-d'), 'customer_name' => 'Test Customer', 'customer_id' => $customer->id]);
        $product = Product::create(['product_name' => 'Test Product', 'slug' => 'test-product-' . uniqid(), 'product_type' => 'Rental']);
        $orderProduct = OrderProduct::create([
            'order_id'     => $order->id,
            'product_id'   => $product->id,
            'product_name' => 'Test Product',
            'price'        => 100,
            'quantity'     => 1,
            'total'        => 100,
            'equipment_id' => $equipment->id,
        ]);

        // Equipment::orderProduct() is belongsTo(OrderProduct, 'current_order_product_id'),
        // not the inverse of OrderProduct.equipment_id — must be set explicitly.
        $equipment->current_order_id = $order->id;
        $equipment->current_order_product_id = $orderProduct->id;
        $equipment->saveQuietly();

        // IndexController's isset($equipment->orderProduct) branch reads
        // $equipment->orderProduct->equipmentRentalReadyTemplate->checklistQuestions —
        // an order-product-scoped inspection record must exist or that (pre-existing,
        // unrelated to PR-B3) code path throws on a null relation. Seed one so the
        // request exercises the same success path a real prior inspection would.
        $inspectionTemplate = EquipmentRentalReadyTemplate::create([
            'equipment_id'      => $equipment->id,
            'order_id'          => $order->id,
            'order_product_id'  => $orderProduct->id,
            'employee_name'     => 'Test Inspector',
            'inspection_date'   => now()->format('Y-m-d'),
            'inspection_time'   => now()->format('H:i'),
        ]);
        EquipmentRentalReadyChecklistQuestion::create([
            'equipment_rental_ready_template_id' => $inspectionTemplate->id,
            'rental_ready_qa_json' => json_encode([
                'id' => $requiredQuestion->id,
                'unique_id' => $requiredQuestion->unique_id,
                'question_name' => $requiredQuestion->question_name,
                'required_question' => true,
                'answers' => [],
            ]),
        ]);

        return [$equipment, $requiredQuestion, $requiredAnswer, $orderProduct];
    }

    public function test_index_still_returns_questions_for_rented_equipment_but_logs(): void
    {
        [$equipment] = $this->makeRentedEquipmentWithOrderProduct();

        $response = $this->callAs('POST', 'rental-ready-checklists', [
            'equipment_unique_id' => $equipment->unique_id,
        ]);

        // Behavior unchanged from before this PR (guard was already disabled) — still 200.
        $response->assertOk()->assertJson(['success' => true]);

        $this->assertTrue($this->apiErrorsHandler->hasWarningThatContains('Rental Ready checklist questions listed for currently-rented equipment'));
        $record = $this->apiErrorsHandler->getRecords()[0];
        $this->assertEquals($equipment->id, $record->context['equipment_id']);
    }

    public function test_index_available_equipment_is_returned_normally_and_does_not_log(): void
    {
        [$equipment] = $this->makeEquipmentWithRentalReadyTemplate('available');

        $response = $this->callAs('POST', 'rental-ready-checklists', [
            'equipment_unique_id' => $equipment->unique_id,
        ]);

        // Confirms the obsolete isAvailable() half of the old guard is gone for good —
        // available equipment (the normal pre-inspection state) is listed successfully.
        $response->assertOk()->assertJson(['success' => true]);
        $this->assertFalse($this->apiErrorsHandler->hasWarningRecords());
    }

    public function test_index_non_rented_non_ordered_equipment_does_not_log(): void
    {
        [$equipment] = $this->makeEquipmentWithRentalReadyTemplate('maintenance');

        $response = $this->callAs('POST', 'rental-ready-checklists', [
            'equipment_unique_id' => $equipment->unique_id,
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertFalse($this->apiErrorsHandler->hasWarningRecords());
    }
}
