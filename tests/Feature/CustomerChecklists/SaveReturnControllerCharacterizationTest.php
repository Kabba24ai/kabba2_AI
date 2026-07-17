<?php

namespace Tests\Feature\CustomerChecklists;

use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminCategory;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminQuestion;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminQuestionAnswer;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\BillingCharge;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Models\ProductManagement\Product;
use App\Models\Stores\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * P3-8: baseline characterization tests for SaveReturnController — beyond PR-A2's
 * transaction-rollback coverage (ChecklistTransactionTest) and PR-A4's observability-
 * logging coverage (CompletenessObservabilityLoggingTest), none of this controller's
 * other response branches (not-found/conflict/already-submitted) or its damage/fuel
 * billing-charge side effects had dedicated tests before this PR. See
 * docs/checklist-system-audit/P3_8_BASELINE_CHARACTERIZATION_TESTS.md.
 */
class SaveReturnControllerCharacterizationTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;
    private Order $order;
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::create([
            'first_name' => 'Return',
            'last_name'  => 'Actor',
            'email'      => 'return-actor@example.com',
            'password'   => bcrypt('password'),
        ]);

        $this->store = Store::create(['store_name' => 'Test Store']);

        $customer = Customer::create([
            'first_name' => 'Return',
            'last_name'  => 'Customer',
            'email'      => 'return-customer@example.com',
        ]);

        $this->order = Order::create([
            'order_date'    => now()->format('Y-m-d'),
            'customer_name' => 'Return Customer',
            'customer_id'   => $customer->id,
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

    private function makeRentedEquipment(): Equipment
    {
        return Equipment::create([
            'equipment_name' => 'Test Excavator',
            'equipment_id'   => 'EQP-TEST-' . uniqid(),
            'brand'          => 'TestBrand',
            'current_status' => 'rented',
        ]);
    }

    /**
     * Rented equipment assigned to an order product, with a pre-existing order-level
     * checklist batch (2 questions, one flagged is_damaged on its master answer) — the
     * state a prior delivery would have produced, since SaveReturnController operates
     * on already-snapshotted checklistQuestions rows, not the master template directly.
     */
    private function makeRentedOrderProductWithChecklist(bool $secondAnswerIsDamaged = false): array
    {
        $equipment    = $this->makeRentedEquipment();
        $orderProduct = $this->makeOrderProduct($equipment->id);
        $equipment->current_order_id = $this->order->id;
        $equipment->current_order_product_id = $orderProduct->id;
        $equipment->saveQuietly();

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
            'is_damaged'           => $secondAnswerIsDamaged,
        ]);

        $checklistQuestion = $orderProduct->checklistQuestions()->create([
            'order_id'      => $this->order->id,
            'question_id'   => $question->id,
            'question_name' => $question->question_name,
            'index_number'  => 1,
        ]);
        $checklistAnswer = $checklistQuestion->answers()->create([
            'order_id'   => $this->order->id,
            'question_id' => $question->id,
            'answer_id'  => $masterAnswer->id,
            'index_number' => 1,
        ]);

        return [$equipment, $orderProduct, $checklistQuestion, $checklistAnswer];
    }

    // ── Not-found / conflict / already-submitted response branches ─────────

    public function test_order_product_not_found_returns_error(): void
    {
        $orderProduct = $this->makeOrderProduct();
        $this->order->delete(); // soft-deletes the order (and cascades to the product)

        $response = $this->callAs('POST', 'customer-checklists/save-return', [
            'order_product_unique_id' => $orderProduct->unique_id,
            'store_id'                => (string) $this->store->id,
            'user_id'                 => (string) $this->actor->id,
        ]);

        $response->assertStatus(404)->assertJson(['success' => false, 'error_code' => 'ORDER_PRODUCT_NOT_FOUND']);
    }

    public function test_equipment_missing_returns_error(): void
    {
        $orderProduct = $this->makeOrderProduct(); // no equipment_id at all

        $response = $this->callAs('POST', 'customer-checklists/save-return', [
            'order_product_unique_id' => $orderProduct->unique_id,
            'store_id'                => (string) $this->store->id,
            'user_id'                 => (string) $this->actor->id,
        ]);

        $response->assertStatus(404)->assertJson(['success' => false, 'error_code' => 'EQUIPMENT_NOT_FOUND']);
    }

    public function test_equipment_not_rented_returns_invalid_status_error(): void
    {
        $equipment = Equipment::create([
            'equipment_name' => 'Test Excavator',
            'equipment_id'   => 'EQP-TEST-' . uniqid(),
            'brand'          => 'TestBrand',
            'current_status' => 'available',
        ]);
        $orderProduct = $this->makeOrderProduct($equipment->id);

        $response = $this->callAs('POST', 'customer-checklists/save-return', [
            'order_product_unique_id' => $orderProduct->unique_id,
            'store_id'                => (string) $this->store->id,
            'user_id'                 => (string) $this->actor->id,
        ]);

        $response->assertStatus(403)->assertJson(['success' => false, 'error_code' => 'INVALID_EQUIPMENT_STATUS']);
    }

    public function test_second_return_submission_after_signature_already_uploaded_is_rejected_409(): void
    {
        [$equipment, $orderProduct] = $this->makeRentedOrderProductWithChecklist();

        $this->callAs('POST', 'customer-checklists/save-return', [
            'order_product_unique_id' => $orderProduct->unique_id,
            'store_id'                => (string) $this->store->id,
            'user_id'                 => (string) $this->actor->id,
            'signature_media'         => UploadedFile::fake()->image('signature.jpg'),
        ])->assertOk();

        // Equipment cycled to 'maintenance'/'damaged' by the first return, so a genuine
        // resubmission first needs it back in a rented state to reach the guard under test
        // rather than tripping the (different) InvalidEquipmentStatus branch instead.
        // refresh() first: the test's in-memory $equipment object still has its
        // creation-time 'rented' as Eloquent's "original" value, so re-assigning the
        // same string wouldn't be seen as dirty and saveQuietly() would silently skip
        // the write — refresh to pick up the controller's own DB change first.
        $equipment->refresh();
        $equipment->current_status = 'rented';
        $equipment->saveQuietly();

        $response = $this->callAs('POST', 'customer-checklists/save-return', [
            'order_product_unique_id' => $orderProduct->unique_id,
            'store_id'                => (string) $this->store->id,
            'user_id'                 => (string) $this->actor->id,
            'signature_media'         => UploadedFile::fake()->image('signature-2.jpg'),
        ]);

        $response->assertStatus(409)->assertJson(['success' => false, 'error_code' => 'CHECKLIST_ALREADY_SUBMITTED']);
    }

    public function test_checklist_submitted_but_no_prior_checklist_questions_returns_no_questions_found(): void
    {
        $equipment    = $this->makeRentedEquipment();
        $orderProduct = $this->makeOrderProduct($equipment->id);
        // No checklistQuestions created for this order product — none exist to select from.

        $response = $this->callAs('POST', 'customer-checklists/save-return', [
            'order_product_unique_id' => $orderProduct->unique_id,
            'store_id'                => (string) $this->store->id,
            'user_id'                 => (string) $this->actor->id,
            'checklist' => [
                ['question_unique_id' => 'ANY', 'answer_unique_id' => 'ANY'],
            ],
        ]);

        // Fails FormRequest validation first, since checklist.*.question_unique_id must
        // exist in order_product_checklist_questions — confirms the NoQuestionsFound
        // controller branch is unreachable with a well-formed-but-empty checklist array
        // once request-layer validation is in place.
        $response->assertStatus(422);
    }

    // ── Damage / fuel billing-charge side effects ───────────────────────────

    public function test_damaged_return_answer_creates_billing_charge_and_marks_equipment_returned_damaged(): void
    {
        [$equipment, $orderProduct, $checklistQuestion, $checklistAnswer] = $this->makeRentedOrderProductWithChecklist(secondAnswerIsDamaged: true);

        $response = $this->callAs('POST', 'customer-checklists/save-return', [
            'order_product_unique_id' => $orderProduct->unique_id,
            'store_id'                => (string) $this->store->id,
            'user_id'                 => (string) $this->actor->id,
            'checklist' => [
                ['question_unique_id' => $checklistQuestion->unique_id, 'answer_unique_id' => $checklistAnswer->unique_id, 'amount' => '75.00'],
            ],
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $orderProduct->refresh();
        $equipment->refresh();

        $this->assertTrue((bool) $orderProduct->is_returned);
        $this->assertEquals('pending', $orderProduct->damage_status->value);
        $this->assertTrue($equipment->current_status->isDamaged());
        $this->assertEquals(1, BillingCharge::where('order_product_id', $orderProduct->id)->count());
    }

    public function test_non_damaged_return_marks_equipment_returned_to_maintenance_and_creates_no_charge(): void
    {
        [$equipment, $orderProduct, $checklistQuestion, $checklistAnswer] = $this->makeRentedOrderProductWithChecklist(secondAnswerIsDamaged: false);

        $response = $this->callAs('POST', 'customer-checklists/save-return', [
            'order_product_unique_id' => $orderProduct->unique_id,
            'store_id'                => (string) $this->store->id,
            'user_id'                 => (string) $this->actor->id,
            'checklist' => [
                ['question_unique_id' => $checklistQuestion->unique_id, 'answer_unique_id' => $checklistAnswer->unique_id],
            ],
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $orderProduct->refresh();
        $equipment->refresh();

        $this->assertTrue((bool) $orderProduct->is_returned);
        $this->assertNull($orderProduct->damage_status);
        $this->assertTrue($equipment->current_status->isMaintenance());
        $this->assertEquals(0, BillingCharge::where('order_product_id', $orderProduct->id)->count());
    }

    public function test_fuel_total_charge_triggers_billing_engine_bridge_charge(): void
    {
        [$equipment, $orderProduct] = $this->makeRentedOrderProductWithChecklist();

        $response = $this->callAs('POST', 'customer-checklists/save-return', [
            'order_product_unique_id' => $orderProduct->unique_id,
            'store_id'                => (string) $this->store->id,
            'user_id'                 => (string) $this->actor->id,
            'fuel_final_reading'      => '50',
            'fuel_total_charge'       => '75.00',
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $orderProduct->refresh();
        $this->assertEquals(75.00, (float) $orderProduct->fuel_total_charge);
        $this->assertEquals(
            1,
            BillingCharge::where('order_product_id', $orderProduct->id)
                ->where('billing_charge_type', 'fuel')
                ->count()
        );
    }

    public function test_signature_media_upload_populates_pickup_signature_media_id(): void
    {
        [$equipment, $orderProduct] = $this->makeRentedOrderProductWithChecklist();

        $this->callAs('POST', 'customer-checklists/save-return', [
            'order_product_unique_id' => $orderProduct->unique_id,
            'store_id'                => (string) $this->store->id,
            'user_id'                 => (string) $this->actor->id,
            'signature_media'         => UploadedFile::fake()->image('signature.jpg'),
        ])->assertOk();

        $orderProduct->refresh();
        $this->assertNotNull($orderProduct->pickup_signature_media_id);
    }
}
