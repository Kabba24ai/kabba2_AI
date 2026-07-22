<?php

namespace Tests\Feature\Tasks;

use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Models\Tasks\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Clickable task status system (Task Status Bar design). Status is a
 * current-state selector, not a progress bar: the four working states move
 * freely in any direction. Waiting demands a reason; Help Needed spins up a
 * linked sub-task for a teammate; Completed/Cancelled stay on their existing
 * flows and reject changes through this endpoint.
 */
class TaskStatusControlTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $teammate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'unique_id' => 'tsc-test-admin', 'employee_code' => '95',
            'first_name' => 'Status', 'last_name' => 'Changer',
            'email' => 'status-changer@test.local', 'password' => bcrypt('secret'), 'status' => 'Active',
        ]);
        $this->teammate = User::create([
            'unique_id' => 'tsc-test-helper', 'employee_code' => '94',
            'first_name' => 'Helpful', 'last_name' => 'Teammate',
            'email' => 'helper@test.local', 'password' => bcrypt('secret'), 'status' => 'Active',
        ]);
        $this->actingAs($this->admin);
    }

    private function makeTask(array $overrides = []): Task
    {
        return Task::create(array_merge([
            'category' => 'admin',
            'title'    => 'Rent n King Emails',
            'priority' => 'normal',
            'status'   => 'open',
            'created_by_user_id' => $this->admin->id,
        ], $overrides));
    }

    // ─────────────────────────────────────────────────────────
    // Free movement between working states
    // ─────────────────────────────────────────────────────────

    public function test_status_moves_freely_in_any_direction(): void
    {
        $task = $this->makeTask();

        // open → in_progress → waiting → in_progress → help would be a
        // progress bar violation — exactly what must be allowed.
        $this->postJson(route('admin.tasks.status', $task), ['status' => 'in_progress'])
            ->assertOk()->assertJson(['success' => true, 'status' => 'in_progress']);

        $this->postJson(route('admin.tasks.status', $task), [
            'status' => 'waiting', 'waiting_reason' => 'Awaiting a reply',
        ])->assertOk()->assertJson(['status' => 'waiting']);

        $this->postJson(route('admin.tasks.status', $task), ['status' => 'in_progress'])
            ->assertOk()->assertJson(['status' => 'in_progress']);

        $this->postJson(route('admin.tasks.status', $task), ['status' => 'open'])
            ->assertOk()->assertJson(['status' => 'open']);

        $this->assertEquals('open', $task->fresh()->status->value);
    }

    public function test_status_changes_are_logged_to_activity_history(): void
    {
        $task = $this->makeTask();

        $this->postJson(route('admin.tasks.status', $task), ['status' => 'in_progress'])->assertOk();

        $log = $task->activityLogs()->where('action', 'status_changed')->first();
        $this->assertNotNull($log);
        $this->assertEquals('Open', $log->old_value);
        $this->assertEquals('In Progress', $log->new_value);
        $this->assertEquals($this->admin->id, $log->user_id);
    }

    // ─────────────────────────────────────────────────────────
    // Waiting requires a reason
    // ─────────────────────────────────────────────────────────

    public function test_waiting_without_a_reason_is_rejected(): void
    {
        $task = $this->makeTask();

        $this->postJson(route('admin.tasks.status', $task), ['status' => 'waiting'])
            ->assertStatus(422)->assertJsonValidationErrors('waiting_reason');

        $this->assertEquals('open', $task->fresh()->status->value);
    }

    public function test_waiting_stores_the_reason_and_leaving_clears_it(): void
    {
        $task = $this->makeTask();

        $this->postJson(route('admin.tasks.status', $task), [
            'status' => 'waiting', 'waiting_reason' => 'Vendor / third party',
        ])->assertOk();

        $task->refresh();
        $this->assertEquals('waiting', $task->status->value);
        $this->assertEquals('Vendor / third party', $task->waiting_reason);

        // The reason rides along in the activity log
        $log = $task->activityLogs()->where('action', 'status_changed')->first();
        $this->assertStringContainsString('Vendor / third party', $log->new_value);

        $this->postJson(route('admin.tasks.status', $task), ['status' => 'in_progress'])->assertOk();
        $this->assertNull($task->fresh()->waiting_reason);
    }

    // ─────────────────────────────────────────────────────────
    // Help Needed creates a linked task
    // ─────────────────────────────────────────────────────────

    public function test_help_needed_requires_an_assignee_and_a_description(): void
    {
        $task = $this->makeTask();

        $this->postJson(route('admin.tasks.status', $task), ['status' => 'help_needed'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['help_assigned_to', 'help_description']);

        $this->assertEquals('open', $task->fresh()->status->value);
        $this->assertEquals(1, Task::count());
    }

    public function test_help_needed_creates_a_linked_task_with_inherited_context(): void
    {
        $customer = Customer::create([
            'first_name' => 'Linked', 'last_name' => 'Customer',
            'email' => 'linked-help@example.com', 'status' => 'Active', 'phone' => '6165550401',
        ]);
        $order = Order::create(['customer_id' => $customer->id, 'customer_name' => $customer->full_name]);
        $task  = $this->makeTask([
            'related_customer_id' => $customer->id,
            'related_order_id'    => $order->id,
        ]);

        $this->postJson(route('admin.tasks.status', $task), [
            'status'           => 'help_needed',
            'help_assigned_to' => $this->teammate->id,
            'help_description' => 'Please send me the mailbox logins.',
            'help_due_date'    => now()->addDays(2)->format('Y-m-d'),
        ])->assertOk()->assertJson(['success' => true, 'status' => 'help_needed']);

        $task->refresh();
        $this->assertEquals('help_needed', $task->status->value);

        $helpTask = $task->subTasks()->first();
        $this->assertNotNull($helpTask, 'a linked sub-task must exist');
        $this->assertEquals('Help needed: Rent n King Emails', $helpTask->title);
        $this->assertEquals('Please send me the mailbox logins.', $helpTask->description);
        $this->assertEquals($this->teammate->id, $helpTask->assigned_to_user_id);
        $this->assertEquals('open', $helpTask->status->value);
        $this->assertEquals($task->id, $helpTask->parent_task_id);
        $this->assertNotNull($helpTask->due_date);

        // Relationship context follows the request to the helper
        $this->assertEquals($customer->id, $helpTask->related_customer_id);
        $this->assertEquals($order->id, $helpTask->related_order_id);

        // Both sides carry an audit trail
        $this->assertNotNull($task->activityLogs()->where('action', 'help_requested')->first());
        $this->assertNotNull($helpTask->activityLogs()->where('action', 'task_created')->first());
    }

    // ─────────────────────────────────────────────────────────
    // Boundaries
    // ─────────────────────────────────────────────────────────

    public function test_terminal_tasks_reject_status_changes(): void
    {
        $completed = $this->makeTask(['status' => 'completed', 'completed_at' => now()]);

        $this->postJson(route('admin.tasks.status', $completed), ['status' => 'open'])
            ->assertStatus(422)->assertJson(['success' => false]);

        $this->assertEquals('completed', $completed->fresh()->status->value);
    }

    public function test_completed_and_cancelled_cannot_be_set_through_this_endpoint(): void
    {
        $task = $this->makeTask();

        foreach (['completed', 'cancelled', 'bogus'] as $status) {
            $this->postJson(route('admin.tasks.status', $task), ['status' => $status])
                ->assertStatus(422)->assertJsonValidationErrors('status');
        }

        $this->assertEquals('open', $task->fresh()->status->value);
    }

    public function test_guests_cannot_change_status(): void
    {
        $task = $this->makeTask();
        auth()->logout();

        $this->post(route('admin.tasks.status', $task), ['status' => 'in_progress'])
            ->assertRedirect();
        $this->assertEquals('open', $task->fresh()->status->value);
    }

    // ─────────────────────────────────────────────────────────
    // Page rendering
    // ─────────────────────────────────────────────────────────

    public function test_show_page_renders_the_status_control_for_working_tasks(): void
    {
        $task = $this->makeTask();

        $html = $this->get(route('admin.tasks.show', $task))->assertOk()->getContent();

        $this->assertStringContainsString('Status — tap to update', $html);
        $this->assertStringContainsString('ts_bar', $html);
        $this->assertStringContainsString('help_needed', $html);
        $this->assertStringContainsString('Why is this on hold?', $html);
        $this->assertStringContainsString('Request help — creates a linked task', $html);
        $this->assertStringContainsString(route('admin.tasks.status', $task), $html);
    }

    public function test_show_page_hides_the_control_for_terminal_tasks(): void
    {
        $task = $this->makeTask(['status' => 'completed', 'completed_at' => now()]);

        $html = $this->get(route('admin.tasks.show', $task))->assertOk()->getContent();

        $this->assertStringNotContainsString('Status — tap to update', $html);
        $this->assertStringNotContainsString('ts_bar', $html);
    }

    public function test_show_page_links_parent_and_sub_task_both_ways(): void
    {
        $task = $this->makeTask();
        $this->postJson(route('admin.tasks.status', $task), [
            'status'           => 'help_needed',
            'help_assigned_to' => $this->teammate->id,
            'help_description' => 'Need the vendor contact.',
        ])->assertOk();
        $helpTask = $task->subTasks()->first();

        $parentHtml = $this->get(route('admin.tasks.show', $task))->assertOk()->getContent();
        $this->assertStringContainsString('Linked Tasks', $parentHtml);
        $this->assertStringContainsString($helpTask->title, $parentHtml);
        $this->assertStringContainsString(route('admin.tasks.show', $helpTask), $parentHtml);

        $childHtml = $this->get(route('admin.tasks.show', $helpTask))->assertOk()->getContent();
        $this->assertStringContainsString('Linked Tasks', $childHtml);
        $this->assertStringContainsString('Assists', $childHtml);
        $this->assertStringContainsString(route('admin.tasks.show', $task), $childHtml);
    }

    public function test_help_needed_tasks_stay_on_the_open_board(): void
    {
        $task = $this->makeTask(['title' => 'Board visibility check', 'status' => 'help_needed']);

        $html = $this->get(route('admin.tasks.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Board visibility check', $html);
        $this->assertStringContainsString('Help Needed', $html); // status filter option + badge
    }
}
