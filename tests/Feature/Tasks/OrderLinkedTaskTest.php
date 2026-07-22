<?php

namespace Tests\Feature\Tasks;

use App\Models\Customers\Customer;
use App\Models\Customers\CustomerCallNeeded;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Supplier;
use App\Models\Orders\Order;
use App\Models\Tasks\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Order-linked task creation — Order Details "Add Task" context, the
 * Task Manager order lookup, and the canonical relationship rules:
 * one order per task, the order's customer is authoritative, clearing
 * the order keeps the customer, and no free-text substitutes.
 */
class OrderLinkedTaskTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Customer $customer;
    private Customer $otherCustomer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'unique_id' => 'olt-test-admin', 'employee_code' => '96',
            'first_name' => 'Order', 'last_name' => 'Linker',
            'email' => 'order-linker@test.local', 'password' => bcrypt('secret'), 'status' => 'Active',
        ]);
        $this->actingAs($this->admin);

        $this->customer = Customer::create([
            'first_name' => 'Gary', 'last_name' => 'Jezorski',
            'email' => 'gary@example.com', 'status' => 'Active', 'phone' => '6165550301',
        ]);
        $this->otherCustomer = Customer::create([
            'first_name' => 'Wrong', 'last_name' => 'Person',
            'email' => 'wrong@example.com', 'status' => 'Active', 'phone' => '6165550302',
        ]);
    }

    private function makeOrder(?Customer $customer = null, array $overrides = []): Order
    {
        $customer ??= $this->customer;

        return Order::create(array_merge([
            'customer_id'   => $customer?->id,
            'customer_name' => $customer?->full_name,
        ], $overrides));
    }

    private function taskPayload(array $overrides = []): array
    {
        return array_merge([
            'category' => 'sales',
            'title'    => 'Follow up on rental',
            'priority' => 'normal',
            'status'   => 'open',
        ], $overrides);
    }

    // ─────────────────────────────────────────────────────────
    // Creation with an order link
    // ─────────────────────────────────────────────────────────

    public function test_task_with_valid_order_stores_order_and_its_customer(): void
    {
        $order = $this->makeOrder();

        // Only the order is submitted — the customer is derived server-side
        $this->postJson(route('admin.tasks.store'), $this->taskPayload([
            'related_order_id' => $order->id,
        ]))->assertOk()->assertJson(['success' => true]);

        $task = Task::latest('id')->first();
        $this->assertEquals($order->id, $task->related_order_id);
        $this->assertEquals($this->customer->id, $task->related_customer_id);
        $this->assertEquals($order->id, $task->order->id);
    }

    public function test_mismatched_customer_is_canonically_corrected(): void
    {
        $order = $this->makeOrder($this->customer);

        $this->postJson(route('admin.tasks.store'), $this->taskPayload([
            'related_order_id'    => $order->id,
            'related_customer_id' => $this->otherCustomer->id, // lies
        ]))->assertOk()->assertJson(['success' => true]);

        $task = Task::latest('id')->first();
        $this->assertEquals($this->customer->id, $task->related_customer_id, 'order customer must win');
        $this->assertEquals($order->id, $task->related_order_id);
    }

    public function test_order_without_customer_keeps_submitted_customer(): void
    {
        $order = Order::create(['customer_name' => 'Walk-in']); // no customer record
        $this->assertNull($order->customer_id);

        $this->postJson(route('admin.tasks.store'), $this->taskPayload([
            'related_order_id'    => $order->id,
            'related_customer_id' => $this->customer->id,
        ]))->assertOk();

        $task = Task::latest('id')->first();
        $this->assertEquals($order->id, $task->related_order_id);
        $this->assertEquals($this->customer->id, $task->related_customer_id);
    }

    public function test_invalid_order_id_is_rejected(): void
    {
        $this->postJson(route('admin.tasks.store'), $this->taskPayload([
            'related_order_id' => 999999,
        ]))->assertStatus(422)->assertJsonValidationErrors('related_order_id');

        $this->assertEquals(0, Task::count());
    }

    public function test_soft_deleted_order_cannot_be_linked(): void
    {
        $order = $this->makeOrder();
        $order->delete();

        $this->postJson(route('admin.tasks.store'), $this->taskPayload([
            'related_order_id' => $order->id,
        ]))->assertStatus(422)->assertJsonValidationErrors('related_order_id');

        $this->assertEquals(0, Task::count());
    }

    public function test_completed_and_extension_orders_remain_linkable(): void
    {
        // Follow-up tasks are exactly what terminal-state orders need —
        // no status excludes an order from linkage, only deletion does.
        $completed = $this->makeOrder($this->customer, ['status' => 'Completed']);
        $parent    = $this->makeOrder($this->customer, ['order_number' => '#900']);
        $extension = $this->makeOrder($this->customer, [
            'order_number'           => '#900-A',
            'reference_order_number' => '#900',
        ]);

        foreach ([$completed, $extension] as $order) {
            $this->postJson(route('admin.tasks.store'), $this->taskPayload([
                'related_order_id' => $order->id,
            ]))->assertOk()->assertJson(['success' => true]);
        }

        $this->assertEquals(
            [$completed->id, $extension->id],
            Task::orderBy('id')->pluck('related_order_id')->all(),
        );
    }

    // ─────────────────────────────────────────────────────────
    // Existing relationship levels stay intact
    // ─────────────────────────────────────────────────────────

    public function test_customer_task_without_order_still_works(): void
    {
        $this->postJson(route('admin.tasks.store'), $this->taskPayload([
            'related_customer_id' => $this->customer->id,
        ]))->assertOk();

        $task = Task::latest('id')->first();
        $this->assertEquals($this->customer->id, $task->related_customer_id);
        $this->assertNull($task->related_order_id);
    }

    public function test_general_task_without_any_relationship_still_works(): void
    {
        $this->postJson(route('admin.tasks.store'), $this->taskPayload())->assertOk();

        $task = Task::latest('id')->first();
        $this->assertNull($task->related_order_id);
        $this->assertNull($task->related_customer_id);
        $this->assertNull($task->related_supplier_id);
        $this->assertNull($task->related_other);
    }

    public function test_supplier_and_other_relationships_unchanged(): void
    {
        $supplier = Supplier::create(['name' => 'Acme Parts', 'status' => 'Active']);

        $this->postJson(route('admin.tasks.store'), $this->taskPayload([
            'related_supplier_id' => $supplier->id,
        ]))->assertOk();
        $this->assertEquals($supplier->id, Task::latest('id')->first()->related_supplier_id);

        $this->postJson(route('admin.tasks.store'), $this->taskPayload([
            'related_other' => 'City inspector',
        ]))->assertOk();
        $this->assertEquals('City inspector', Task::latest('id')->first()->related_other);
    }

    // ─────────────────────────────────────────────────────────
    // Phone calls link to orders too
    // ─────────────────────────────────────────────────────────

    public function test_phone_call_can_be_linked_to_an_order(): void
    {
        $order = $this->makeOrder();

        $this->postJson(route('admin.dashboard.call-needed.store'), [
            'customer_id' => $this->customer->id,
            'order_id'    => $order->id,
            'assigned_to' => $this->admin->id,
            'reason'      => 'order_review',
            'category'    => 'sales',
        ])->assertOk()->assertJson(['success' => true]);

        $call = CustomerCallNeeded::latest('id')->first();
        $this->assertEquals($order->id, $call->order_id);
        $this->assertEquals($this->customer->id, $call->customer_id);
    }

    public function test_phone_call_mismatched_customer_is_corrected_to_orders_customer(): void
    {
        $order = $this->makeOrder($this->customer);

        $this->postJson(route('admin.dashboard.call-needed.store'), [
            'customer_id' => $this->otherCustomer->id, // lies
            'order_id'    => $order->id,
            'assigned_to' => $this->admin->id,
            'reason'      => 'order_review',
        ])->assertOk();

        $call = CustomerCallNeeded::latest('id')->first();
        $this->assertEquals($this->customer->id, $call->customer_id);
        $this->assertEquals($order->id, $call->order_id);
    }

    public function test_phone_call_with_invalid_order_is_rejected(): void
    {
        $this->postJson(route('admin.dashboard.call-needed.store'), [
            'customer_id' => $this->customer->id,
            'order_id'    => 999999,
            'assigned_to' => $this->admin->id,
            'reason'      => 'order_review',
        ])->assertStatus(422)->assertJsonValidationErrors('order_id');

        $this->assertEquals(0, CustomerCallNeeded::count());
    }

    // ─────────────────────────────────────────────────────────
    // Order Details integration
    // ─────────────────────────────────────────────────────────

    public function test_order_details_has_add_task_with_prefill_and_no_call_needed(): void
    {
        // The Order Details blade reads live configuration settings
        $this->seed(\Database\Seeders\Configurations\SettingSeeder::class);

        $order = $this->makeOrder();

        $html = $this->get(route('admin.order-management.orders.edit', $order->unique_id))
            ->assertOk()->getContent();

        // Add Task opens the canonical modal with this order's context.
        // Context rides on data attributes — inline onclick JSON breaks the
        // attribute on quoted values like order numbers ("#3331") and names.
        $this->assertStringContainsString('Add Task', $html);
        $this->assertStringContainsString('data-open-task-modal', $html);
        $this->assertStringContainsString('data-order-id="' . $order->id . '"', $html);
        $this->assertStringContainsString('data-order-number="' . $order->order_number . '"', $html);
        $this->assertStringContainsString('data-customer-id="' . $this->customer->id . '"', $html);
        $this->assertStringContainsString('UnifiedTaskModal', $html);
        $this->assertStringContainsString('ut_order_search', $html);
        $this->assertStringNotContainsString('onclick="openNewTaskModal({', $html);

        // The old Call Needed workflow is gone from this page
        $this->assertStringNotContainsString('callNeededBtn', $html);
        $this->assertStringNotContainsString('orderCallReminderModal', $html);
        $this->assertStringNotContainsString('saveOrderCallReminder', $html);
    }

    public function test_task_center_modal_has_the_order_lookup_field(): void
    {
        $html = $this->get(route('admin.tasks.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Search by Order #', $html);
        $this->assertStringContainsString('ut_order_search', $html);
        $this->assertStringContainsString('ut_order_results', $html);
        $this->assertStringContainsString('ut_order_chip', $html);
        $this->assertStringContainsString(route('admin.dashboard.charge-modal.orders'), $html);
    }

    // ─────────────────────────────────────────────────────────
    // Display and navigation
    // ─────────────────────────────────────────────────────────

    public function test_show_page_displays_customer_and_working_order_link(): void
    {
        $order = $this->makeOrder();
        $task  = Task::create($this->taskPayload([
            'related_order_id'    => $order->id,
            'related_customer_id' => $this->customer->id,
            'created_by_user_id'  => $this->admin->id,
        ]));

        $html = $this->get(route('admin.tasks.show', $task))->assertOk()->getContent();

        $this->assertStringContainsString('Related To', $html);
        $this->assertStringContainsString('Gary Jezorski', $html);
        $this->assertStringContainsString($order->order_number, $html);
        $this->assertStringContainsString(
            route('admin.order-management.orders.edit', $order->unique_id),
            $html,
        );
    }

    public function test_task_board_shows_the_order_indicator_and_links_to_the_task(): void
    {
        $order = $this->makeOrder();
        $task  = Task::create($this->taskPayload([
            'related_order_id'    => $order->id,
            'related_customer_id' => $this->customer->id,
            'created_by_user_id'  => $this->admin->id,
        ]));

        $html = $this->get(route('admin.tasks.index'))->assertOk()->getContent();

        // The person-grouped board shows the order number as card context; the
        // whole card links to the task detail (where the order link is live).
        $this->assertStringContainsString($order->order_number, $html);
        $this->assertStringContainsString(route('admin.tasks.show', $task), $html);
    }

    public function test_call_show_page_displays_the_order_link(): void
    {
        $order = $this->makeOrder();
        $call  = CustomerCallNeeded::create([
            'customer_id' => $this->customer->id,
            'order_id'    => $order->id,
            'reason'      => 'order_review',
            'status'      => 'active',
            'created_by'  => $this->admin->id,
            'auth_by'     => $this->admin->id,
        ]);

        $html = $this->get(route('admin.tasks.call.show', $call->id))->assertOk()->getContent();

        $this->assertStringContainsString($order->order_number, $html);
        $this->assertStringContainsString(
            route('admin.order-management.orders.edit', $order->unique_id),
            $html,
        );
    }

    // ─────────────────────────────────────────────────────────
    // Clearing the order keeps the customer (server-side contract)
    // ─────────────────────────────────────────────────────────

    public function test_clearing_the_order_on_edit_keeps_the_customer(): void
    {
        $order = $this->makeOrder();
        $task  = Task::create($this->taskPayload([
            'related_order_id'    => $order->id,
            'related_customer_id' => $this->customer->id,
            'created_by_user_id'  => $this->admin->id,
        ]));

        $this->patch(route('admin.tasks.update', $task), $this->taskPayload([
            'related_order_id'    => null,
            'related_customer_id' => $this->customer->id,
        ]))->assertRedirect();

        $task->refresh();
        $this->assertNull($task->related_order_id);
        $this->assertEquals($this->customer->id, $task->related_customer_id);
    }

    // ─────────────────────────────────────────────────────────
    // Authorization
    // ─────────────────────────────────────────────────────────

    public function test_guests_cannot_create_tasks_or_look_up_orders(): void
    {
        auth()->logout();
        $order = $this->makeOrder();

        $this->post(route('admin.tasks.store'), $this->taskPayload([
            'related_order_id' => $order->id,
        ]))->assertRedirect();
        $this->assertEquals(0, Task::count());

        $this->get(route('admin.dashboard.charge-modal.orders', ['q' => '3151']))
            ->assertRedirect();
    }
}
