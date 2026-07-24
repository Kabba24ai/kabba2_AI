<?php

namespace Tests\Feature\Service;

use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminQuestionAnswer;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\BillingCharge;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Models\ProductManagement\ProductCategory;
use App\Models\Service\CustomerDamageStaging;
use App\Models\Service\ServiceTicket;
use App\Services\Service\CustomerDamageStagingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Customer Damage Staging — baseline coverage. Pins: idempotent checklist
 * ingestion (one record per return event, retries are no-ops), correct
 * entity derivation, order/product integrity on manual creation, the
 * three dispositions running exclusively through the canonical downstream
 * paths (ChargeService / ServiceTicketIntakeService), disposition idempotency,
 * auth requirements, and truthful dashboard counts.
 */
class CustomerDamageStagingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'first_name' => 'Damage', 'last_name' => 'Reviewer',
            'email' => 'damage-reviewer@test.local', 'status' => 'Active',
        ]);
        $this->actingAs($this->admin);

        $this->customer = Customer::create([
            'first_name' => 'Dented', 'last_name' => 'Customer',
            'email' => 'dented@test.local', 'status' => 'Active',
        ]);
    }

    /** An order + product + equipment + return-checklist damage answer set. */
    private function makeDamagedReturn(): OrderProduct
    {
        $category = ProductCategory::create(['title' => 'Lifts', 'status' => 'Published', 'sort_order' => 1]);
        $equipment = Equipment::create([
            'unique_id' => 'cds-eq-' . Str::random(6), 'equipment_name' => 'Scissor Lift 19',
            'equipment_id' => 'SL-' . Str::random(4), 'brand' => 'Test',
            'product_category_id' => $category->id,
            'current_status' => 'available', 'not_for_rent' => 0,
        ]);

        $order = Order::create([
            'order_date' => now()->format('Y-m-d'),
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->full_name,
            'subtotal' => 100, 'tax_amount' => 0, 'grand_total' => 100,
        ]);

        $orderProduct = $order->products()->create([
            'product_name' => 'Scissor Lift 19',
            'equipment_id' => $equipment->id,
            'quantity' => 1, 'price' => 100,
            'pickup_notes' => 'Right rail bent at return.',
        ]);

        $masterAnswer = CustomerAdminQuestionAnswer::create([
            'answer_return_text' => 'Damaged — needs review',
            'question_id' => 1,
            'is_damaged' => true,
        ]);

        $question = $orderProduct->checklistQuestions()->create([
            'question_name' => 'Body condition',
            'return_question' => true,
        ]);

        $question->answers()->create([
            'order_id' => $order->id,
            'question_id' => 1,
            'answer_id' => $masterAnswer->id,
            'is_return_answer' => true,
            'user_return_amount' => 150.00,
        ]);

        return $orderProduct->fresh();
    }

    private function cycleKey(OrderProduct $op): string
    {
        return (string) $op->checklistQuestions()->min('id');
    }

    // ── Ingestion ───────────────────────────────────────────────────────

    public function test_damaged_checklist_creates_one_staging_record_with_derived_context(): void
    {
        $op = $this->makeDamagedReturn();

        $staging = CustomerDamageStagingService::ingestFromReturnChecklist($op, $this->cycleKey($op), $this->admin->id);

        $this->assertNotNull($staging);
        $this->assertSame(1, CustomerDamageStaging::count());
        $this->assertSame('checklist', $staging->source_type);
        $this->assertSame($op->order_id, $staging->order_id);
        $this->assertSame($this->customer->id, $staging->customer_id);
        $this->assertSame($op->id, $staging->order_product_id);
        $this->assertSame($op->equipment_id, $staging->equipment_id);
        $this->assertSame(CustomerDamageStaging::STATUS_NEW, $staging->status);
        $this->assertStringContainsString('Body condition', $staging->observation);
        $this->assertStringContainsString('150.00', $staging->observation);
        $this->assertStringContainsString('Right rail bent', $staging->observation);
    }

    public function test_non_damaged_checklist_creates_no_staging_record(): void
    {
        $op = $this->makeDamagedReturn();
        // Neutralize the damage flag on the master answer — a clean return.
        CustomerAdminQuestionAnswer::query()->update(['is_damaged' => false]);

        $result = CustomerDamageStagingService::ingestFromReturnChecklist($op, $this->cycleKey($op), $this->admin->id);

        $this->assertNull($result);
        $this->assertSame(0, CustomerDamageStaging::count());
    }

    public function test_reprocessing_the_same_checklist_does_not_duplicate(): void
    {
        $op = $this->makeDamagedReturn();
        $key = $this->cycleKey($op);

        $first  = CustomerDamageStagingService::ingestFromReturnChecklist($op, $key, $this->admin->id);
        $second = CustomerDamageStagingService::ingestFromReturnChecklist($op, $key, $this->admin->id);

        $this->assertSame(1, CustomerDamageStaging::count());
        $this->assertSame($first->id, $second->id);
    }

    public function test_ingestion_links_the_existing_checklist_billing_charge_not_a_new_one(): void
    {
        $op = $this->makeDamagedReturn();
        $key = $this->cycleKey($op);

        // The mobile bridge creates the damage BillingCharge under this
        // exact key BEFORE ingestion runs — simulate it.
        $charge = BillingCharge::create([
            'billing_charge_type' => 'damage',
            'status' => 'pending',
            'customer_id' => $this->customer->id,
            'parent_order_id' => $op->order_id,
            'order_product_id' => $op->id,
            'amount' => 0, 'tax_amount' => 0, 'tax_type' => 'free',
            'idempotency_key' => "mobile_checklist:{$op->id}:damage:{$key}",
        ]);

        $staging = CustomerDamageStagingService::ingestFromReturnChecklist($op, $key, $this->admin->id);

        $this->assertSame($charge->id, $staging->billing_charge_id);
        $this->assertSame(1, BillingCharge::count(), 'ingestion must never create a charge');
    }

    // ── Manual creation ────────────────────────────────────────────────

    public function test_manual_creation_rejects_cross_order_products(): void
    {
        $op = $this->makeDamagedReturn();
        $otherOrder = Order::create([
            'order_date' => now()->format('Y-m-d'),
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->full_name,
            'subtotal' => 50, 'tax_amount' => 0, 'grand_total' => 50,
        ]);

        $this->postJson(route('admin.service-management.customer-damage.store'), [
            'order_id' => $otherOrder->id,
            'order_product_id' => $op->id, // belongs to the FIRST order
            'observation' => 'Cross-order attempt',
        ])->assertStatus(422);

        $this->assertSame(0, CustomerDamageStaging::count());
    }

    public function test_manual_creation_derives_customer_equipment_and_reporter(): void
    {
        $op = $this->makeDamagedReturn();

        $this->postJson(route('admin.service-management.customer-damage.store'), [
            'order_id' => $op->order_id,
            'order_product_id' => $op->id,
            'observation' => 'Cracked window discovered during return handling.',
        ])->assertOk()->assertJsonPath('success', true);

        $staging = CustomerDamageStaging::firstOrFail();
        $this->assertSame('manual', $staging->source_type);
        $this->assertSame($this->customer->id, $staging->customer_id);
        $this->assertSame($op->equipment_id, $staging->equipment_id);
        $this->assertSame($this->admin->id, $staging->reported_by);
    }

    // ── Dispositions ───────────────────────────────────────────────────

    private function makeStaging(): CustomerDamageStaging
    {
        $op = $this->makeDamagedReturn();

        return CustomerDamageStagingService::ingestFromReturnChecklist($op, $this->cycleKey($op), $this->admin->id);
    }

    public function test_no_action_disposes_without_ticket_or_charge_and_requires_note(): void
    {
        $staging = $this->makeStaging();

        $this->postJson(route('admin.service-management.customer-damage.disposition', $staging->unique_id), [
            'action' => 'no_action',
        ])->assertStatus(422); // note required

        $this->postJson(route('admin.service-management.customer-damage.disposition', $staging->unique_id), [
            'action' => 'no_action', 'note' => 'Normal wear — no customer responsibility.',
        ])->assertOk();

        $staging->refresh();
        $this->assertSame(CustomerDamageStaging::STATUS_DISPOSED, $staging->status);
        $this->assertSame(CustomerDamageStaging::DISPOSITION_NO_ACTION, $staging->disposition);
        $this->assertNull($staging->service_ticket_id);
        $this->assertNull($staging->billing_charge_id);
        $this->assertSame(0, ServiceTicket::count());
        $this->assertSame(0, BillingCharge::count());
    }

    public function test_charge_disposition_uses_the_canonical_billing_path_and_links_the_charge(): void
    {
        $staging = $this->makeStaging();

        $this->postJson(route('admin.service-management.customer-damage.disposition', $staging->unique_id), [
            'action' => 'charge_customer', 'amount' => 250.00, 'sales_tax_type' => 'add',
        ])->assertOk();

        $staging->refresh();
        $this->assertSame(CustomerDamageStaging::STATUS_DISPOSED, $staging->status);
        $this->assertNotNull($staging->billing_charge_id);

        $charge = BillingCharge::findOrFail($staging->billing_charge_id);
        $this->assertSame('damage', $charge->billing_charge_type->value);
        // ChargeService::createManualCharge's canonical idempotency shape
        $this->assertStringStartsWith('manual_damage_charge:', (string) $charge->idempotency_key);
    }

    public function test_checklist_row_with_existing_charge_never_creates_a_second_one(): void
    {
        $op = $this->makeDamagedReturn();
        $key = $this->cycleKey($op);
        BillingCharge::create([
            'billing_charge_type' => 'damage', 'status' => 'pending',
            'customer_id' => $this->customer->id, 'parent_order_id' => $op->order_id,
            'amount' => 0, 'tax_amount' => 0, 'tax_type' => 'free',
            'idempotency_key' => "mobile_checklist:{$op->id}:damage:{$key}",
        ]);
        $staging = CustomerDamageStagingService::ingestFromReturnChecklist($op, $key, $this->admin->id);

        $this->postJson(route('admin.service-management.customer-damage.disposition', $staging->unique_id), [
            'action' => 'charge_customer',
        ])->assertOk();

        $this->assertSame(1, BillingCharge::count(), 'the existing checklist charge must be the only one');
        $this->assertSame(CustomerDamageStaging::STATUS_DISPOSED, $staging->fresh()->status);
    }

    public function test_service_ticket_disposition_uses_canonical_path_and_is_idempotent(): void
    {
        $staging = $this->makeStaging();
        $url = route('admin.service-management.customer-damage.disposition', $staging->unique_id);

        $this->postJson($url, ['action' => 'service_ticket'])->assertOk();
        $this->postJson($url, ['action' => 'service_ticket'])->assertOk(); // repeat click

        $this->assertSame(1, ServiceTicket::count(), 'repeat disposition must not duplicate the ticket');

        $ticket = ServiceTicket::firstOrFail();
        $staging->refresh();
        $this->assertSame($ticket->id, $staging->service_ticket_id);
        $this->assertSame('customer_damage_repair', $ticket->service_type->value);
        $this->assertTrue((bool) $ticket->customer_damage_possible);
        $this->assertSame($staging->order_id, $ticket->order_id);
        $this->assertSame($staging->customer_id, $ticket->customer_id);
        $this->assertSame($staging->equipment_id, $ticket->equipment_id);
        $this->assertStringContainsString($staging->unique_id, $ticket->customer_complaint);
    }

    public function test_disposing_an_already_disposed_record_is_guarded(): void
    {
        $staging = $this->makeStaging();
        $url = route('admin.service-management.customer-damage.disposition', $staging->unique_id);

        $this->postJson($url, ['action' => 'no_action', 'note' => 'Cosmetic only.'])->assertOk();

        // A stale second No Action gets a conflict, not a silent overwrite.
        $this->postJson($url, ['action' => 'no_action', 'note' => 'Duplicate click.'])->assertStatus(409);
    }

    // ── Auth + dashboard ───────────────────────────────────────────────

    public function test_guests_cannot_view_or_dispose(): void
    {
        $staging = $this->makeStaging();
        auth()->logout();
        $this->flushSession();

        $view = $this->get(route('admin.service-management.customer-damage.index'));
        $this->assertContains($view->getStatusCode(), [302, 401, 403]);

        $dispose = $this->postJson(route('admin.service-management.customer-damage.disposition', $staging->unique_id), [
            'action' => 'no_action', 'note' => 'nope',
        ]);
        $this->assertContains($dispose->getStatusCode(), [302, 401, 403]);
        $this->assertSame(CustomerDamageStaging::STATUS_NEW, $staging->fresh()->status);
    }

    public function test_dashboard_card_counts_reflect_staging_statuses(): void
    {
        $staging = $this->makeStaging();

        Livewire::test(\App\Livewire\Dashboard\AlertsSection::class)
            ->assertSet('damageSummary.outstanding', 1)
            ->assertSet('damageSummary.new_today', 1)
            ->assertSet('damageSummary.completed_today', 0);

        CustomerDamageStagingService::disposeNoAction($staging, $this->admin->id, 'Pre-existing damage.');

        Livewire::test(\App\Livewire\Dashboard\AlertsSection::class)
            ->assertSet('damageSummary.outstanding', 0)
            ->assertSet('damageSummary.completed_today', 1);
    }

    public function test_staging_page_renders_with_queue_and_filters(): void
    {
        $this->makeStaging();

        $this->get(route('admin.service-management.customer-damage.index'))
            ->assertOk()
            ->assertSee('Customer Damage')
            ->assertSee('Dented Customer')
            ->assertSee('Review');
    }
}
