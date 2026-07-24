<?php

namespace Tests\Feature\Tasks;

use App\Models\Iam\Personnel\User;
use App\Models\Tasks\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Task Center contextual New Task assignment (2026-07-23).
 *
 * Both task-creation entry points use the ONE unified modal:
 *   - Global "+ New Task" opens it blank (no assignee).
 *   - An employee lane's "+ Add task" opens the SAME modal with only that
 *     lane's employee preselected in Assigned To.
 *   - The Unassigned lane opens blank, like the global button.
 *
 * State isolation is structural: openNewTaskModal() clears the assignee
 * (utAssigneeChoices.removeActiveItems) on EVERY open, then optionally applies
 * a passed employee id — so no assignee carries across entry points.
 */
class TaskCenterContextualNewTaskTest extends TestCase
{
    use RefreshDatabase;

    private User $gary;
    private User $amber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gary = User::create([
            'unique_id' => 'tcx-gary', 'employee_code' => '80',
            'first_name' => 'Gary', 'last_name' => 'Jezorski',
            'email' => 'tcx-gary@test.local', 'password' => bcrypt('secret'), 'status' => 'Active',
        ]);
        $this->amber = User::create([
            'unique_id' => 'tcx-amber', 'employee_code' => '81',
            'first_name' => 'Amber', 'last_name' => 'Sanders',
            'email' => 'tcx-amber@test.local', 'password' => bcrypt('secret'), 'status' => 'Active',
        ]);
        $this->actingAs($this->gary);
    }

    private function task(?User $assignee, array $overrides = []): Task
    {
        return Task::create(array_merge([
            'category' => 'admin', 'title' => 'A task',
            'priority' => 'normal', 'status' => 'open',
            'assigned_to_user_id' => $assignee?->id,
            'created_by_user_id'  => $this->gary->id,
        ], $overrides));
    }

    private function index(): string
    {
        return $this->get(route('admin.tasks.index'))->assertOk()->getContent();
    }

    public function test_global_new_task_button_opens_the_modal_blank(): void
    {
        $this->task($this->amber);
        $html = $this->index();

        // Global button opens the modal with no context (blank), and carries
        // no employee hint of its own.
        $this->assertStringContainsString('onclick="openNewTaskModal()"', $html);
    }

    public function test_employee_lane_add_task_carries_that_lanes_employee_id(): void
    {
        $this->task($this->amber);
        $this->task($this->gary);
        $html = $this->index();

        // Each named lane's "+ Add task" is the declarative, delegated trigger
        // carrying its own employee id.
        $this->assertStringContainsString('data-open-task-modal', $html);
        $this->assertStringContainsString('data-assigned-to="' . $this->amber->id . '"', $html);
        $this->assertStringContainsString('data-assigned-to="' . $this->gary->id . '"', $html);
    }

    public function test_unassigned_lane_add_task_has_no_employee(): void
    {
        $this->task(null, ['category' => 'yard', 'title' => 'Nobody owns this']);
        $html = $this->index();

        $this->assertStringContainsString('Unassigned', $html);
        // The Unassigned lane's Add task button carries NO data-assigned-to,
        // so it opens blank exactly like the global + New Task button.
        $this->assertSame(0, substr_count($html, 'data-assigned-to="'));
    }

    public function test_only_named_lanes_get_an_assignee_hint(): void
    {
        $this->task($this->amber);
        $this->task(null, ['category' => 'yard', 'title' => 'Nobody owns this']);
        $html = $this->index();

        // One named lane (Amber) + one Unassigned lane → exactly one
        // data-assigned-to button, and it is Amber's.
        $this->assertSame(1, substr_count($html, 'data-assigned-to="'));
        $this->assertSame(1, substr_count($html, 'data-assigned-to="' . $this->amber->id . '"'));
    }

    public function test_same_unified_modal_and_reset_before_preselect(): void
    {
        $this->task($this->amber);
        $html = $this->index();

        // ONE modal, ONE canonical open function — no duplicate modal/logic.
        $this->assertSame(1, substr_count($html, 'id="UnifiedTaskModal"'));
        $this->assertStringContainsString('window.openNewTaskModal = function', $html);

        // The reset clears the assignee, then the contextual preselect applies —
        // reset MUST precede preselect so state can never leak between entry points.
        $resetPos     = strpos($html, 'utAssigneeChoices.removeActiveItems()');
        $preselectPos = strpos($html, 'setChoiceByValue(String(context.assignedToUserId))');
        $this->assertNotFalse($resetPos);
        $this->assertNotFalse($preselectPos);
        $this->assertLessThan($preselectPos, $resetPos, 'assignee reset must run before the contextual preselect');

        // The delegated trigger reads the lane button's employee id.
        $this->assertStringContainsString('parseInt(btn.dataset.assignedTo, 10)', $html);
    }
}
