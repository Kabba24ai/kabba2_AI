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
 * P3-1 / BUG-11: RentalReadyChecklists\IndexController threw
 * "Call to a member function filter() on null" when equipment had an order
 * product but no EquipmentRentalReadyTemplate (no prior order-scoped
 * inspection recorded yet) — optional() only proxies the first call in a
 * chain, so ->pluck()->filter() on a null relation still threw.
 * See docs/checklist-system-audit/P3_1_BUG11_NULL_GUARD.md.
 */
class Bug11NullGuardCharacterizationTest extends TestCase
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
            'email'      => 'bug11-inspector@example.com',
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

    private function makeEquipmentWithRentalReadyTemplate(string $status = 'available'): array
    {
        $category = RentalReadyChecklistCategory::create(['category_name' => 'Bug11 Test Category']);

        $requiredQuestion = RentalReadyChecklistQuestion::create([
            'question_name'      => 'Required question',
            'category_id'        => $category->id,
            'required_question'  => true,
        ]);
        $requiredAnswer = RentalReadyChecklistQuestionAnswer::create([
            'answer_name'  => 'Rental Ready',
            'question_id'  => $requiredQuestion->id,
            'type'         => 'Rental Ready',
            'index_number' => 1,
        ]);

        $template = RentalReadyChecklistTemplate::create([
            'template_name'   => 'Bug11 Test Rental Ready Template',
            'active_template' => true,
        ]);
        RentalReadyChecklistTemplateQuestion::create(['template_id' => $template->id, 'question_id' => $requiredQuestion->id, 'index_number' => 1]);

        $checklistMaster = ChecklistMaster::create([
            'checklist_system_name'    => 'Bug11 Test Checklist Master',
            'rental_ready_template_id' => $template->id,
        ]);

        $equipment = Equipment::create([
            'equipment_name'      => 'Bug11 Test Excavator',
            'equipment_id'        => 'EQP-BUG11-' . uniqid(),
            'brand'               => 'TestBrand',
            'current_status'      => $status,
            'checklist_master_id' => $checklistMaster->id,
        ]);

        return [$equipment, $requiredQuestion, $requiredAnswer];
    }

    private function attachOrderProduct(Equipment $equipment, string $status = 'rented'): OrderProduct
    {
        $customer = Customer::create(['first_name' => 'Test', 'last_name' => 'Customer', 'email' => 'bug11-customer-' . uniqid() . '@example.com']);
        $order = Order::create(['order_date' => now()->format('Y-m-d'), 'customer_name' => 'Test Customer', 'customer_id' => $customer->id]);
        $product = Product::create(['product_name' => 'Test Product', 'slug' => 'bug11-product-' . uniqid(), 'product_type' => 'Rental']);
        $orderProduct = OrderProduct::create([
            'order_id'     => $order->id,
            'product_id'   => $product->id,
            'product_name' => 'Test Product',
            'price'        => 100,
            'quantity'     => 1,
            'total'        => 100,
            'equipment_id' => $equipment->id,
            // These tests simulate an active/delivered rental's own inspection
            // data — the endpoint now only treats the order product as the
            // answered-data source once it's genuinely delivered (see
            // RentalReadyChecklists\IndexController's isRentedAndDelivered
            // gating), matching Equipment\IndexController's identical rule.
            'is_delivered' => 1,
        ]);

        // Equipment::orderProduct() is belongsTo(OrderProduct, 'current_order_product_id') —
        // must be set explicitly, it is not the inverse of OrderProduct.equipment_id.
        $equipment->current_order_id = $order->id;
        $equipment->current_order_product_id = $orderProduct->id;
        $equipment->current_status = $status;
        $equipment->saveQuietly();

        return $orderProduct;
    }

    // ── 1. Order product WITH a valid EquipmentRentalReadyTemplate — happy path ──

    public function test_order_product_with_valid_rental_ready_template_returns_questions(): void
    {
        [$equipment, $requiredQuestion] = $this->makeEquipmentWithRentalReadyTemplate('rented');
        $orderProduct = $this->attachOrderProduct($equipment, 'rented');

        $inspectionTemplate = EquipmentRentalReadyTemplate::create([
            'equipment_id'     => $equipment->id,
            'order_id'         => $orderProduct->order_id,
            'order_product_id' => $orderProduct->id,
            'employee_name'    => 'Test Inspector',
            'inspection_date'  => now()->format('Y-m-d'),
            'inspection_time'  => now()->format('H:i'),
        ]);
        EquipmentRentalReadyChecklistQuestion::create([
            'equipment_rental_ready_template_id' => $inspectionTemplate->id,
            'rental_ready_qa_json' => json_encode([
                'id'                 => $requiredQuestion->id,
                'unique_id'          => $requiredQuestion->unique_id,
                'question_name'      => $requiredQuestion->question_name,
                'required_question'  => true,
                'answers'            => [],
            ]),
        ]);

        $response = $this->callAs('POST', 'rental-ready-checklists', [
            'equipment_unique_id' => $equipment->unique_id,
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertNotEmpty($response->json('rental_ready_checklist_questions'));
    }

    // ── 2. Order product with NO EquipmentRentalReadyTemplate — the BUG-11 case ──

    public function test_order_product_with_no_rental_ready_template_does_not_throw(): void
    {
        [$equipment] = $this->makeEquipmentWithRentalReadyTemplate('rented');
        $this->attachOrderProduct($equipment, 'rented');

        // No EquipmentRentalReadyTemplate created for this order product at all —
        // before the fix, this threw "Call to a member function filter() on null".
        $response = $this->callAs('POST', 'rental-ready-checklists', [
            'equipment_unique_id' => $equipment->unique_id,
        ]);

        // Falls through to the existing empty-questions convention: 404 with the
        // same message the checklistMaster-path already uses for "no questions found" —
        // no new response envelope invented.
        $response->assertStatus(404)->assertJson([
            'success' => false,
            'message' => trans('messages.api.admin.v1.rental_ready_checklists.no_questions_found'),
        ]);
    }

    // ── 3. Available equipment, no previous Rental Ready result ──

    public function test_available_equipment_with_no_previous_result_returns_null_rental_ready_data(): void
    {
        [$equipment] = $this->makeEquipmentWithRentalReadyTemplate('available');

        $response = $this->callAs('POST', 'rental-ready-checklists', [
            'equipment_unique_id' => $equipment->unique_id,
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        // EquipmentRentalReadyChecklistListResource always renders its full field set
        // (each defaulted via `?? 0`/`?? ''`), even when built from a null model — so a
        // "no previous result" equipment renders as a zeroed-out object, not JSON null.
        // This is pre-existing resource behavior, unrelated to BUG-11; asserting on the
        // 'id' default (0) is the characterization for "no previous inspection exists".
        $this->assertSame(0, $response->json('equipment_rental_ready.id'));
        $this->assertNotEmpty($response->json('rental_ready_checklist_questions'));
    }

    // ── 4. Existing rented-equipment observability behavior from PR-B3 (regression) ──

    public function test_rented_equipment_with_order_product_and_template_still_logs_and_succeeds(): void
    {
        [$equipment, $requiredQuestion] = $this->makeEquipmentWithRentalReadyTemplate('rented');
        $orderProduct = $this->attachOrderProduct($equipment, 'rented');

        $inspectionTemplate = EquipmentRentalReadyTemplate::create([
            'equipment_id'     => $equipment->id,
            'order_id'         => $orderProduct->order_id,
            'order_product_id' => $orderProduct->id,
            'employee_name'    => 'Test Inspector',
            'inspection_date'  => now()->format('Y-m-d'),
            'inspection_time'  => now()->format('H:i'),
        ]);
        EquipmentRentalReadyChecklistQuestion::create([
            'equipment_rental_ready_template_id' => $inspectionTemplate->id,
            'rental_ready_qa_json' => json_encode([
                'id'                => $requiredQuestion->id,
                'unique_id'         => $requiredQuestion->unique_id,
                'question_name'     => $requiredQuestion->question_name,
                'required_question' => true,
                'answers'           => [],
            ]),
        ]);

        $response = $this->callAs('POST', 'rental-ready-checklists', [
            'equipment_unique_id' => $equipment->unique_id,
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertTrue($this->apiErrorsHandler->hasWarningThatContains('Rental Ready checklist questions listed for currently-rented equipment'));
    }
}
