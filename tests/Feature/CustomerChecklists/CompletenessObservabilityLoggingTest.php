<?php

namespace Tests\Feature\CustomerChecklists;

use App\Models\ChecklistManagement\ChecklistMaster\ChecklistMaster;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminCategory;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminQuestion;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminQuestionAnswer;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminTemplate;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminTemplateQuestion;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Models\ProductManagement\Product;
use App\Models\Stores\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\TestHandler;
use Tests\TestCase;

/**
 * PR-A4: proves SaveDeliveryController/SaveReturnController log (via the 'api_errors'
 * channel) when delivery/return is marked 'Completed' despite the checklist actually
 * being incomplete — missing signature, unanswered required questions, or an empty/
 * omitted checklist array — and that they do NOT log on a genuinely complete submission.
 * Purely observational per CORRECTION_PHASE1_PLAN.md Issue #3: delivery_status/
 * pickup_status, and the HTTP response, are asserted unchanged in every case here.
 *
 * Uses a real Monolog TestHandler attached to the 'api_errors' channel rather than
 * mocking the Log facade — this avoids having to stub every other channel this flow
 * touches (billing_engine, equipment_status), since only the target channel's logger
 * is being listened to.
 */
class CompletenessObservabilityLoggingTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;
    private Order $order;
    private Store $store;
    private TestHandler $apiErrorsHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::create([
            'first_name' => 'Completeness',
            'last_name'  => 'Actor',
            'email'      => 'completeness-actor@example.com',
            'password'   => bcrypt('password'),
        ]);

        $this->store = Store::create(['store_name' => 'Test Store']);

        $customer = Customer::create([
            'first_name' => 'Completeness',
            'last_name'  => 'Customer',
            'email'      => 'completeness-customer@example.com',
        ]);

        $this->order = Order::create([
            'order_date'    => now()->format('Y-m-d'),
            'customer_name' => 'Completeness Customer',
            'customer_id'   => $customer->id,
        ]);

        $this->apiErrorsHandler = new TestHandler();
        Log::channel('api_errors')->getLogger()->pushHandler($this->apiErrorsHandler);
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

    /**
     * Builds a real ChecklistMaster -> CustomerAdminTemplate -> 2 template questions
     * (one required, one not) -> answers chain, and an Equipment row pointing at it —
     * the exact relation graph SaveDeliveryController reads to build order-level
     * checklist rows on delivery.
     */
    private function makeEquipmentWithTemplate(): array
    {
        $category = CustomerAdminCategory::create(['category_name' => 'Test Category']);

        $requiredQuestion = CustomerAdminQuestion::create([
            'question_name'          => 'Required question',
            'category_id'            => $category->id,
            'question_delivery_text' => 'Required at delivery?',
            'question_return_text'   => 'Required at return?',
            'required_question'      => true,
        ]);
        $requiredAnswer = CustomerAdminQuestionAnswer::create([
            'answer_delivery_text' => 'Yes',
            'answer_return_text'   => 'Yes',
            'question_id'          => $requiredQuestion->id,
        ]);

        $optionalQuestion = CustomerAdminQuestion::create([
            'question_name'          => 'Optional question',
            'category_id'            => $category->id,
            'question_delivery_text' => 'Optional at delivery?',
            'question_return_text'   => 'Optional at return?',
            'required_question'      => false,
        ]);
        $optionalAnswer = CustomerAdminQuestionAnswer::create([
            'answer_delivery_text' => 'Yes',
            'answer_return_text'   => 'Yes',
            'question_id'          => $optionalQuestion->id,
        ]);

        $template = CustomerAdminTemplate::create([
            'template_name'  => 'Test Template',
            'active_template' => true,
        ]);
        CustomerAdminTemplateQuestion::create(['template_id' => $template->id, 'question_id' => $requiredQuestion->id, 'index_number' => 1]);
        CustomerAdminTemplateQuestion::create(['template_id' => $template->id, 'question_id' => $optionalQuestion->id, 'index_number' => 2]);

        $checklistMaster = ChecklistMaster::create([
            'checklist_system_name'      => 'Test Checklist Master',
            'customer_admin_template_id' => $template->id,
        ]);

        $equipment = Equipment::create([
            'equipment_name'      => 'Test Excavator',
            'equipment_id'        => 'EQP-TEST-' . uniqid(),
            'brand'               => 'TestBrand',
            'current_status'      => 'available',
            'checklist_master_id' => $checklistMaster->id,
        ]);

        return [$equipment, $requiredQuestion, $requiredAnswer, $optionalQuestion, $optionalAnswer];
    }

    // ── SaveDeliveryController ──────────────────────────────────────────────

    public function test_delivery_logs_when_signature_missing(): void
    {
        [$equipment, $requiredQuestion, $requiredAnswer, $optionalQuestion, $optionalAnswer] = $this->makeEquipmentWithTemplate();
        $orderProduct = $this->makeOrderProduct();

        // Answer both questions (including the required one) but send no signature.
        $response = $this->callAs('POST', 'customer-checklists/save-delivery', [
            'order_product_unique_id' => $orderProduct->unique_id,
            'equipment_unique_id'     => $equipment->unique_id,
            'user_id'                 => (string) $this->actor->id,
            'checklist' => [
                ['question_unique_id' => $requiredQuestion->unique_id, 'answer_unique_id' => $requiredAnswer->unique_id],
                ['question_unique_id' => $optionalQuestion->unique_id, 'answer_unique_id' => $optionalAnswer->unique_id],
            ],
        ]);

        $response->assertOk();
        $this->assertEquals('Completed', $orderProduct->refresh()->delivery_status); // behavior unchanged

        $this->assertTrue($this->apiErrorsHandler->hasWarningThatContains('Delivery marked Completed despite incomplete checklist submission'));
        $record = $this->apiErrorsHandler->getRecords()[0];
        $this->assertFalse($record->context['signature_present']);
        $this->assertTrue($record->context['checklist_submitted']);
        $this->assertSame(0, $record->context['missing_required_question_count']);
    }

    public function test_delivery_logs_when_required_question_unanswered(): void
    {
        [$equipment, $requiredQuestion, , $optionalQuestion, $optionalAnswer] = $this->makeEquipmentWithTemplate();
        $orderProduct = $this->makeOrderProduct();

        $response = $this->callAs('POST', 'customer-checklists/save-delivery', [
            'order_product_unique_id' => $orderProduct->unique_id,
            'equipment_unique_id'     => $equipment->unique_id,
            'user_id'                 => (string) $this->actor->id,
            'signature_media'         => UploadedFile::fake()->image('signature.jpg'),
            // Only the optional question is answered — the required one is left out.
            'checklist' => [
                ['question_unique_id' => $optionalQuestion->unique_id, 'answer_unique_id' => $optionalAnswer->unique_id],
            ],
        ]);

        $response->assertOk();
        $this->assertEquals('Completed', $orderProduct->refresh()->delivery_status);

        $this->assertTrue($this->apiErrorsHandler->hasWarningThatContains('Delivery marked Completed despite incomplete checklist submission'));
        $record = $this->apiErrorsHandler->getRecords()[0];
        $this->assertTrue($record->context['signature_present']);
        $this->assertTrue($record->context['checklist_submitted']);
        $this->assertSame(1, $record->context['missing_required_question_count']);
    }

    public function test_delivery_logs_when_checklist_omitted(): void
    {
        [$equipment] = $this->makeEquipmentWithTemplate();
        $orderProduct = $this->makeOrderProduct();

        $response = $this->callAs('POST', 'customer-checklists/save-delivery', [
            'order_product_unique_id' => $orderProduct->unique_id,
            'equipment_unique_id'     => $equipment->unique_id,
            'user_id'                 => (string) $this->actor->id,
            'signature_media'         => UploadedFile::fake()->image('signature.jpg'),
            // no 'checklist' key at all
        ]);

        $response->assertOk();
        $this->assertEquals('Completed', $orderProduct->refresh()->delivery_status);

        $this->assertTrue($this->apiErrorsHandler->hasWarningThatContains('Delivery marked Completed despite incomplete checklist submission'));
        $record = $this->apiErrorsHandler->getRecords()[0];
        $this->assertTrue($record->context['signature_present']);
        $this->assertFalse($record->context['checklist_submitted']);
        $this->assertNull($record->context['missing_required_question_count']);
    }

    public function test_delivery_happy_path_does_not_log(): void
    {
        [$equipment, $requiredQuestion, $requiredAnswer, $optionalQuestion, $optionalAnswer] = $this->makeEquipmentWithTemplate();
        $orderProduct = $this->makeOrderProduct();

        $response = $this->callAs('POST', 'customer-checklists/save-delivery', [
            'order_product_unique_id' => $orderProduct->unique_id,
            'equipment_unique_id'     => $equipment->unique_id,
            'user_id'                 => (string) $this->actor->id,
            'signature_media'         => UploadedFile::fake()->image('signature.jpg'),
            'checklist' => [
                ['question_unique_id' => $requiredQuestion->unique_id, 'answer_unique_id' => $requiredAnswer->unique_id],
                ['question_unique_id' => $optionalQuestion->unique_id, 'answer_unique_id' => $optionalAnswer->unique_id],
            ],
        ]);

        $response->assertOk();
        $this->assertEquals('Completed', $orderProduct->refresh()->delivery_status);

        $this->assertFalse($this->apiErrorsHandler->hasWarningRecords());
    }

    // ── SaveReturnController ─────────────────────────────────────────────────

    /**
     * Builds a rented equipment + an order product with a pre-existing order-level
     * checklist batch (2 questions, one required) — the state a prior delivery would
     * have produced, since SaveReturnController operates on already-snapshotted rows.
     */
    private function makeRentedOrderProductWithChecklist(): array
    {
        $equipment = Equipment::create([
            'equipment_name' => 'Test Excavator',
            'equipment_id'   => 'EQP-TEST-' . uniqid(),
            'brand'          => 'TestBrand',
            'current_status' => 'rented',
        ]);
        $orderProduct = $this->makeOrderProduct($equipment->id);
        $equipment->current_order_id = $this->order->id;
        $equipment->current_order_product_id = $orderProduct->id;
        $equipment->saveQuietly();

        $category = CustomerAdminCategory::create(['category_name' => 'Test Category']);
        $requiredQuestion = CustomerAdminQuestion::create([
            'question_name'          => 'Required question',
            'category_id'            => $category->id,
            'question_delivery_text' => 'Required at delivery?',
            'question_return_text'   => 'Required at return?',
            'required_question'      => true,
        ]);
        $requiredMasterAnswer = CustomerAdminQuestionAnswer::create([
            'answer_delivery_text' => 'Yes',
            'answer_return_text'   => 'Yes',
            'question_id'          => $requiredQuestion->id,
        ]);
        $optionalQuestion = CustomerAdminQuestion::create([
            'question_name'          => 'Optional question',
            'category_id'            => $category->id,
            'question_delivery_text' => 'Optional at delivery?',
            'question_return_text'   => 'Optional at return?',
            'required_question'      => false,
        ]);
        $optionalMasterAnswer = CustomerAdminQuestionAnswer::create([
            'answer_delivery_text' => 'Yes',
            'answer_return_text'   => 'Yes',
            'question_id'          => $optionalQuestion->id,
        ]);

        $requiredChecklistQuestion = $orderProduct->checklistQuestions()->create([
            'order_id'      => $this->order->id,
            'question_id'   => $requiredQuestion->id,
            'question_name' => $requiredQuestion->question_name,
            'index_number'  => 1,
        ]);
        $requiredChecklistAnswer = $requiredChecklistQuestion->answers()->create([
            'order_id'     => $this->order->id,
            'question_id'  => $requiredQuestion->id,
            'answer_id'    => $requiredMasterAnswer->id,
            'index_number' => 1,
        ]);

        $optionalChecklistQuestion = $orderProduct->checklistQuestions()->create([
            'order_id'      => $this->order->id,
            'question_id'   => $optionalQuestion->id,
            'question_name' => $optionalQuestion->question_name,
            'index_number'  => 2,
        ]);
        $optionalChecklistAnswer = $optionalChecklistQuestion->answers()->create([
            'order_id'     => $this->order->id,
            'question_id'  => $optionalQuestion->id,
            'answer_id'    => $optionalMasterAnswer->id,
            'index_number' => 1,
        ]);

        return [$orderProduct, $requiredChecklistQuestion, $requiredChecklistAnswer, $optionalChecklistQuestion, $optionalChecklistAnswer];
    }

    public function test_return_logs_when_signature_missing(): void
    {
        [$orderProduct, $requiredQ, $requiredA, $optionalQ, $optionalA] = $this->makeRentedOrderProductWithChecklist();

        $response = $this->callAs('POST', 'customer-checklists/save-return', [
            'order_product_unique_id' => $orderProduct->unique_id,
            'store_id'                => (string) $this->store->id,
            'user_id'                 => (string) $this->actor->id,
            'checklist' => [
                ['question_unique_id' => $requiredQ->unique_id, 'answer_unique_id' => $requiredA->unique_id],
                ['question_unique_id' => $optionalQ->unique_id, 'answer_unique_id' => $optionalA->unique_id],
            ],
        ]);

        $response->assertOk();
        $this->assertEquals('Completed', $orderProduct->refresh()->pickup_status);

        $this->assertTrue($this->apiErrorsHandler->hasWarningThatContains('Return marked Completed despite incomplete checklist submission'));
        $record = $this->apiErrorsHandler->getRecords()[0];
        $this->assertFalse($record->context['signature_present']);
        $this->assertTrue($record->context['checklist_submitted']);
        $this->assertSame(0, $record->context['missing_required_question_count']);
    }

    public function test_return_logs_when_required_question_unanswered(): void
    {
        [$orderProduct, , , $optionalQ, $optionalA] = $this->makeRentedOrderProductWithChecklist();

        $response = $this->callAs('POST', 'customer-checklists/save-return', [
            'order_product_unique_id' => $orderProduct->unique_id,
            'store_id'                => (string) $this->store->id,
            'user_id'                 => (string) $this->actor->id,
            'signature_media'         => UploadedFile::fake()->image('signature.jpg'),
            // Only the optional question is answered.
            'checklist' => [
                ['question_unique_id' => $optionalQ->unique_id, 'answer_unique_id' => $optionalA->unique_id],
            ],
        ]);

        $response->assertOk();
        $this->assertEquals('Completed', $orderProduct->refresh()->pickup_status);

        $this->assertTrue($this->apiErrorsHandler->hasWarningThatContains('Return marked Completed despite incomplete checklist submission'));
        $record = $this->apiErrorsHandler->getRecords()[0];
        $this->assertTrue($record->context['signature_present']);
        $this->assertTrue($record->context['checklist_submitted']);
        $this->assertSame(1, $record->context['missing_required_question_count']);
    }

    public function test_return_logs_when_checklist_omitted(): void
    {
        [$orderProduct] = $this->makeRentedOrderProductWithChecklist();

        $response = $this->callAs('POST', 'customer-checklists/save-return', [
            'order_product_unique_id' => $orderProduct->unique_id,
            'store_id'                => (string) $this->store->id,
            'user_id'                 => (string) $this->actor->id,
            'signature_media'         => UploadedFile::fake()->image('signature.jpg'),
            // no 'checklist' key at all
        ]);

        $response->assertOk();
        $this->assertEquals('Completed', $orderProduct->refresh()->pickup_status);

        $this->assertTrue($this->apiErrorsHandler->hasWarningThatContains('Return marked Completed despite incomplete checklist submission'));
        $record = $this->apiErrorsHandler->getRecords()[0];
        $this->assertTrue($record->context['signature_present']);
        $this->assertFalse($record->context['checklist_submitted']);
        $this->assertNull($record->context['missing_required_question_count']);
    }

    public function test_return_happy_path_does_not_log(): void
    {
        [$orderProduct, $requiredQ, $requiredA, $optionalQ, $optionalA] = $this->makeRentedOrderProductWithChecklist();

        $response = $this->callAs('POST', 'customer-checklists/save-return', [
            'order_product_unique_id' => $orderProduct->unique_id,
            'store_id'                => (string) $this->store->id,
            'user_id'                 => (string) $this->actor->id,
            'signature_media'         => UploadedFile::fake()->image('signature.jpg'),
            'checklist' => [
                ['question_unique_id' => $requiredQ->unique_id, 'answer_unique_id' => $requiredA->unique_id],
                ['question_unique_id' => $optionalQ->unique_id, 'answer_unique_id' => $optionalA->unique_id],
            ],
        ]);

        $response->assertOk();
        $this->assertEquals('Completed', $orderProduct->refresh()->pickup_status);

        $this->assertFalse($this->apiErrorsHandler->hasWarningRecords());
    }
}
