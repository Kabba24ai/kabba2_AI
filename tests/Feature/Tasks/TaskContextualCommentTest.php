<?php

namespace Tests\Feature\Tasks;

use App\Enums\Tasks\TaskCommentType;
use App\Models\Iam\Personnel\User;
use App\Models\Tasks\Task;
use App\Models\Tasks\TaskComment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Waiting reasons and Help Needed requests belong in the chronological
 * Comments conversation, not stranded in the Activity audit log. The status
 * change, its contextual comment, and any linked task move as one unit.
 */
class TaskContextualCommentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $teammate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'unique_id' => 'tcc-admin', 'employee_code' => '92',
            'first_name' => 'Gary', 'last_name' => 'Jezorski',
            'email' => 'tcc-admin@test.local', 'password' => bcrypt('secret'), 'status' => 'Active',
        ]);
        $this->teammate = User::create([
            'unique_id' => 'tcc-helper', 'employee_code' => '91',
            'first_name' => 'Ashley', 'last_name' => 'Mallard',
            'email' => 'tcc-helper@test.local', 'password' => bcrypt('secret'), 'status' => 'Active',
        ]);
        $this->actingAs($this->admin);
    }

    private function makeTask(array $overrides = []): Task
    {
        return Task::create(array_merge([
            'category' => 'admin', 'title' => 'Hose Payment',
            'priority' => 'normal', 'status' => 'in_progress',
            'created_by_user_id' => $this->admin->id,
        ], $overrides));
    }

    // ─────────────────────────── Waiting ───────────────────────────

    public function test_waiting_creates_a_waiting_comment_with_reason_and_author(): void
    {
        $task = $this->makeTask();

        $this->postJson(route('admin.tasks.status', $task), [
            'status' => 'waiting', 'waiting_reason' => 'Awaiting a reply from the customer.',
        ])->assertOk();

        $comment = $task->comments()->first();
        $this->assertEquals('Awaiting a reply from the customer.', $comment->comment);
        $this->assertEquals(TaskCommentType::Waiting, $comment->comment_type);
        $this->assertEquals($this->admin->id, $comment->user_id);
    }

    public function test_waiting_transition_is_still_audited_without_the_reason(): void
    {
        $task = $this->makeTask();

        $this->postJson(route('admin.tasks.status', $task), [
            'status' => 'waiting', 'waiting_reason' => 'Missing information from vendor.',
        ])->assertOk();

        $log = $task->activityLogs()->where('action', 'status_changed')->first();
        $this->assertNotNull($log);
        $this->assertEquals('In Progress', $log->old_value);
        $this->assertEquals('Waiting', $log->new_value);
        $this->assertStringNotContainsString('Missing information', (string) $log->new_value);
    }

    // ────────────────────────── Help Needed ──────────────────────────

    public function test_help_needed_creates_comment_linked_task_and_audit(): void
    {
        $task = $this->makeTask();

        $this->postJson(route('admin.tasks.status', $task), [
            'status'           => 'help_needed',
            'help_assigned_to' => $this->teammate->id,
            'help_description' => 'Please confirm whether the damaged hoses have been paid.',
        ])->assertOk();

        // Comment carries the request
        $comment = $task->comments()->where('comment_type', 'help_needed')->first();
        $this->assertNotNull($comment);
        $this->assertEquals('Please confirm whether the damaged hoses have been paid.', $comment->comment);
        $this->assertEquals($this->admin->id, $comment->user_id);

        // Linked task still created and audited
        $helpTask = $task->subTasks()->first();
        $this->assertNotNull($helpTask);
        $this->assertEquals($this->teammate->id, $helpTask->assigned_to_user_id);
        $this->assertNotNull($task->activityLogs()->where('action', 'help_requested')->first());
        $this->assertNotNull($helpTask->activityLogs()->where('action', 'task_created')->first());
    }

    public function test_help_request_text_is_not_duplicated_in_activity(): void
    {
        $task = $this->makeTask();

        $this->postJson(route('admin.tasks.status', $task), [
            'status'           => 'help_needed',
            'help_assigned_to' => $this->teammate->id,
            'help_description' => 'UNIQUE-HELP-STRING-42',
        ])->assertOk();

        $log = $task->activityLogs()->where('action', 'help_requested')->first();
        $this->assertStringNotContainsString('UNIQUE-HELP-STRING-42', (string) $log->new_value);
        $this->assertStringContainsString('Ashley Mallard', (string) $log->new_value); // audit fact only
    }

    // ─────────────────────── Transaction integrity ───────────────────────

    public function test_failure_during_the_operation_rolls_everything_back(): void
    {
        $task = $this->makeTask();

        // Force the post-status work to blow up: a model event listener that
        // throws when the help sub-task is created. The parent status update,
        // the activity log, and the comment must all roll back with it.
        Task::creating(function (Task $t) {
            if ($t->parent_task_id !== null) {
                throw new \RuntimeException('simulated failure creating linked task');
            }
        });

        try {
            $this->postJson(route('admin.tasks.status', $task), [
                'status'           => 'help_needed',
                'help_assigned_to' => $this->teammate->id,
                'help_description' => 'Should never persist.',
            ]);
        } catch (\Throwable $e) {
            // controller lets it bubble; the transaction has already rolled back
        } finally {
            Task::flushEventListeners();
        }

        $task->refresh();
        $this->assertEquals('in_progress', $task->status->value, 'status must not change');
        $this->assertEquals(0, $task->comments()->count(), 'no orphaned comment');
        $this->assertEquals(0, $task->subTasks()->count(), 'no orphaned sub-task');
        $this->assertEquals(0, $task->activityLogs()->where('action', 'status_changed')->count());
    }

    // ─────────────────── Chronology, count, and normal comments ───────────────────

    public function test_contextual_comments_interleave_chronologically_and_count(): void
    {
        $task = $this->makeTask();

        // An ordinary comment, then a waiting comment
        $this->post(route('admin.tasks.comments.store', $task), ['comment' => 'Ordinary note.'])->assertRedirect();
        $this->postJson(route('admin.tasks.status', $task), [
            'status' => 'waiting', 'waiting_reason' => 'On hold for parts.',
        ])->assertOk();

        // Both are real comments in one stream — count includes them, and the
        // contextual comment is not siphoned into a separate subsection.
        $this->assertEquals(2, $task->comments()->count());
        $types = $task->comments()->get()->pluck('comment_type')->all();
        $this->assertContains(TaskCommentType::Waiting, $types);
        $this->assertContains(null, $types);

        $html = $this->get(route('admin.tasks.show', $task))->assertOk()->getContent();
        $this->assertStringContainsString('Comments (2)', $html);
        $this->assertStringContainsString('On hold for parts.', $html);
        $this->assertStringContainsString('Ordinary note.', $html);
    }

    public function test_ordinary_comments_remain_untyped(): void
    {
        $task = $this->makeTask();

        $this->post(route('admin.tasks.comments.store', $task), ['comment' => 'Just a normal comment.'])
            ->assertRedirect();

        $comment = $task->comments()->first();
        $this->assertNull($comment->comment_type, 'normal comments carry no type');
    }

    public function test_open_and_in_progress_create_no_comment(): void
    {
        $task = $this->makeTask(['status' => 'open']);

        $this->postJson(route('admin.tasks.status', $task), ['status' => 'in_progress'])->assertOk();
        $this->postJson(route('admin.tasks.status', $task), ['status' => 'open'])->assertOk();

        $this->assertEquals(0, $task->comments()->count(), 'plain status moves add no comment');
    }

    public function test_waiting_and_help_require_their_explanation(): void
    {
        $task = $this->makeTask();

        $this->postJson(route('admin.tasks.status', $task), ['status' => 'waiting'])
            ->assertStatus(422)->assertJsonValidationErrors('waiting_reason');

        $this->postJson(route('admin.tasks.status', $task), ['status' => 'help_needed'])
            ->assertStatus(422)->assertJsonValidationErrors(['help_assigned_to', 'help_description']);

        $this->assertEquals(0, $task->comments()->count());
        $this->assertEquals('in_progress', $task->fresh()->status->value);
    }

    public function test_contextual_comment_renders_with_its_badge(): void
    {
        $task = $this->makeTask();
        $this->postJson(route('admin.tasks.status', $task), [
            'status' => 'waiting', 'waiting_reason' => 'Blocked by another task.',
        ])->assertOk();

        $html = $this->get(route('admin.tasks.show', $task))->assertOk()->getContent();

        // Subtle inline badge, not a separate panel or subsection
        $this->assertStringContainsString('Blocked by another task.', $html);
        $this->assertStringContainsString('bg-orange-100 text-orange-700', $html);
    }
}
