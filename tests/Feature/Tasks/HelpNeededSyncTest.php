<?php

namespace Tests\Feature\Tasks;

use App\Enums\Tasks\TaskCommentType;
use App\Models\Iam\Personnel\User;
use App\Models\Tasks\Task;
use App\Models\Tasks\TaskComment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Help Needed conversation sync: the child (delegated) task is the canonical
 * workspace; native child comments mirror UP to the parent as synchronized
 * projections so the original owner follows one thread. Reply to Linked Task
 * posts into the child and lets the same sync mirror it back — one canonical
 * conversation, no loops, no duplicates.
 */
class HelpNeededSyncTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $helper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::create([
            'unique_id' => 'hns-owner', 'employee_code' => '70',
            'first_name' => 'Gary', 'last_name' => 'Jezorski',
            'email' => 'hns-owner@test.local', 'password' => bcrypt('secret'), 'status' => 'Active',
        ]);
        $this->helper = User::create([
            'unique_id' => 'hns-helper', 'employee_code' => '71',
            'first_name' => 'Ashley', 'last_name' => 'Mallard',
            'email' => 'hns-helper@test.local', 'password' => bcrypt('secret'), 'status' => 'Active',
        ]);
        $this->actingAs($this->owner);
    }

    /** Parent task already in Help Needed with a child assigned to the helper. */
    private function parentWithHelpChild(): array
    {
        $parent = Task::create([
            'category' => 'admin', 'title' => 'Hose Payment',
            'priority' => 'normal', 'status' => 'in_progress',
            'created_by_user_id' => $this->owner->id,
        ]);

        $this->postJson(route('admin.tasks.status', $parent), [
            'status'           => 'help_needed',
            'help_assigned_to' => $this->helper->id,
            'help_description' => 'Please confirm the hoses were paid.',
        ])->assertOk();

        return [$parent->refresh(), $parent->subTasks()->first()];
    }

    // ─────────────────────── Child → parent sync ───────────────────────

    public function test_child_comment_synchronizes_to_parent_with_author_and_origin(): void
    {
        [$parent, $child] = $this->parentWithHelpChild();

        $this->actingAs($this->helper)
            ->post(route('admin.tasks.comments.store', $child), ['comment' => 'Customer confirmed the invoice is paid.'])
            ->assertRedirect();

        $projection = $parent->comments()->whereNotNull('source_comment_id')->first();
        $this->assertNotNull($projection, 'child comment must mirror to parent');
        $this->assertEquals('Customer confirmed the invoice is paid.', $projection->comment);
        $this->assertEquals(TaskCommentType::HelpNeededUpdate, $projection->comment_type);
        $this->assertEquals($this->helper->id, $projection->source_user_id); // shows real author
        $this->assertEquals($child->id, $projection->source_task_id);
        $this->assertTrue($projection->isSynchronized());
    }

    public function test_projection_is_never_re_synchronized_no_loop(): void
    {
        [$parent, $child] = $this->parentWithHelpChild();

        $this->actingAs($this->helper)
            ->post(route('admin.tasks.comments.store', $child), ['comment' => 'Update one.'])->assertRedirect();

        // Exactly one projection exists; the projection did not spawn another.
        $this->assertEquals(1, TaskComment::whereNotNull('source_comment_id')->count());
        // The projection carries no grandchild projection of itself.
        $projection = TaskComment::whereNotNull('source_comment_id')->first();
        $this->assertEquals(0, TaskComment::where('source_comment_id', $projection->id)->count());
    }

    public function test_duplicate_synchronization_of_the_same_comment_is_blocked(): void
    {
        [$parent, $child] = $this->parentWithHelpChild();
        $childComment = $child->comments()->create(['user_id' => $this->helper->id, 'comment' => 'One.']);

        // The created hook already made the projection; forcing a second by hand must fail (UNIQUE).
        $this->expectException(\Illuminate\Database\QueryException::class);
        TaskComment::create([
            'task_id' => $parent->id, 'user_id' => $this->helper->id, 'comment' => 'dupe',
            'comment_type' => TaskCommentType::HelpNeededUpdate,
            'source_task_id' => $child->id, 'source_comment_id' => $childComment->id,
            'source_user_id' => $this->helper->id,
        ]);
    }

    public function test_parent_native_comment_does_not_synchronize_downward(): void
    {
        [$parent, $child] = $this->parentWithHelpChild();
        $childCommentsBefore = $child->comments()->count();

        $this->post(route('admin.tasks.comments.store', $parent), ['comment' => 'Owner note on parent.'])
            ->assertRedirect();

        // The child gains nothing — sync is upward only.
        $this->assertEquals($childCommentsBefore, $child->comments()->count());
    }

    // ─────────────────────── Reply to Linked Task ───────────────────────

    public function test_reply_creates_native_child_comment_and_mirrors_back_once(): void
    {
        [$parent, $child] = $this->parentWithHelpChild();

        $this->actingAs($this->helper)
            ->post(route('admin.tasks.comments.store', $child), ['comment' => 'Need the account number.'])->assertRedirect();

        $projection = $parent->comments()->whereNotNull('source_comment_id')->first();

        // Owner replies from the parent via the synchronized comment.
        $this->actingAs($this->owner)
            ->post(route('admin.tasks.comments.reply', [$parent, $projection]), ['comment' => 'It is 4471.'])
            ->assertRedirect(route('admin.tasks.show', $parent));

        // A native child comment now exists, authored by the owner…
        $childReply = $child->comments()->whereNull('source_comment_id')->where('comment', 'It is 4471.')->first();
        $this->assertNotNull($childReply);
        $this->assertEquals($this->owner->id, $childReply->user_id);

        // …and it mirrored back to the parent exactly once.
        $this->assertEquals(1, $parent->comments()->where('source_comment_id', $childReply->id)->count());
    }

    public function test_reply_supports_attachments(): void
    {
        Storage::fake('task_media');
        [$parent, $child] = $this->parentWithHelpChild();
        $this->actingAs($this->helper)
            ->post(route('admin.tasks.comments.store', $child), ['comment' => 'See attached.'])->assertRedirect();
        $projection = $parent->comments()->whereNotNull('source_comment_id')->first();

        $this->actingAs($this->owner)->post(route('admin.tasks.comments.reply', [$parent, $projection]), [
            'comment' => 'Here is the doc.',
            'media'   => [UploadedFile::fake()->image('proof.jpg')],
        ])->assertRedirect();

        $childReply = $child->comments()->where('comment', 'Here is the doc.')->first();
        $this->assertCount(1, $childReply->media);
    }

    public function test_reply_rejects_a_non_synchronized_comment(): void
    {
        [$parent, $child] = $this->parentWithHelpChild();
        $native = $parent->comments()->create(['user_id' => $this->owner->id, 'comment' => 'plain']);

        $this->post(route('admin.tasks.comments.reply', [$parent, $native]), ['comment' => 'x'])
            ->assertNotFound();
    }

    // ─────────────────────── Waiting / Completion events ───────────────────────

    public function test_child_waiting_synchronizes_as_a_waiting_event(): void
    {
        [$parent, $child] = $this->parentWithHelpChild();

        $this->actingAs($this->helper)->postJson(route('admin.tasks.status', $child), [
            'status' => 'waiting', 'waiting_reason' => 'Waiting for customer to return photographs.',
        ])->assertOk();

        $projection = $parent->comments()->where('comment_type', TaskCommentType::Waiting->value)
            ->whereNotNull('source_comment_id')->first();
        $this->assertNotNull($projection);
        $this->assertEquals('Waiting for customer to return photographs.', $projection->comment);
        $this->assertEquals($this->helper->id, $projection->source_user_id);
    }

    public function test_child_completion_synchronizes_as_a_completed_event(): void
    {
        [$parent, $child] = $this->parentWithHelpChild();

        $this->actingAs($this->helper)->post(route('admin.tasks.complete', $child), [
            'comment' => 'Hoses were paid — done.',
        ])->assertRedirect();

        $projection = $parent->comments()->where('comment_type', TaskCommentType::Completed->value)
            ->whereNotNull('source_comment_id')->first();
        $this->assertNotNull($projection);
        $this->assertEquals('Hoses were paid — done.', $projection->comment);

        // Sync does not auto-close the parent.
        $this->assertNotEquals('completed', $parent->fresh()->status->value);
    }

    public function test_routine_status_change_does_not_create_a_synchronized_comment(): void
    {
        [$parent, $child] = $this->parentWithHelpChild();
        $before = $parent->comments()->count();

        // Open → In Progress on the child: Activity only, no comment anywhere.
        $this->actingAs($this->helper)
            ->postJson(route('admin.tasks.status', $child), ['status' => 'in_progress'])->assertOk();

        $this->assertEquals($before, $parent->comments()->count());
        $this->assertEquals(0, $child->comments()->count());
    }

    // ─────────────────────── Activity integrity ───────────────────────

    public function test_activity_logs_remain_on_the_correct_tasks(): void
    {
        [$parent, $child] = $this->parentWithHelpChild();

        // Parent recorded the help request + linked-task creation.
        $this->assertNotNull($parent->activityLogs()->where('action', 'help_requested')->first());
        // Child recorded its own creation.
        $this->assertNotNull($child->activityLogs()->where('action', 'task_created')->first());

        $this->actingAs($this->helper)
            ->post(route('admin.tasks.comments.store', $child), ['comment' => 'Working on it.'])->assertRedirect();

        // comment_added is logged on the child (the real comment), not fabricated on the parent.
        $this->assertNotNull($child->activityLogs()->where('action', 'comment_added')->first());
        $this->assertEquals(0, $parent->activityLogs()->where('action', 'comment_added')->count());
    }

    // ─────────────────────── Display + Flatpickr ───────────────────────

    public function test_parent_show_page_marks_synced_comment_and_offers_reply(): void
    {
        [$parent, $child] = $this->parentWithHelpChild();
        $this->actingAs($this->helper)
            ->post(route('admin.tasks.comments.store', $child), ['comment' => 'Customer confirmed payment.'])->assertRedirect();

        $html = $this->actingAs($this->owner)->get(route('admin.tasks.show', $parent))->assertOk()->getContent();

        $this->assertStringContainsString('Task #' . $child->id, $html);        // linked task ref
        $this->assertStringContainsString('Help Needed', $html);                 // origin prefix
        $this->assertStringContainsString('Customer confirmed payment.', $html);
        $this->assertStringContainsString('Reply to Linked Task', $html);
        $this->assertStringContainsString('Ashley Mallard', $html);              // original author shown
    }

    // ─────────────────────── Polish: source attachments on projection ───────────────────────

    public function test_child_comment_attachments_render_through_the_parent_projection(): void
    {
        Storage::fake('task_media');
        [$parent, $child] = $this->parentWithHelpChild();

        $this->actingAs($this->helper)->post(route('admin.tasks.comments.store', $child), [
            'comment' => 'Receipt attached.',
            'media'   => [UploadedFile::fake()->image('receipt.jpg')],
        ])->assertRedirect();

        $childComment = $child->comments()->where('comment', 'Receipt attached.')->first();
        $projection   = $parent->comments()->where('source_comment_id', $childComment->id)->first();

        // Files are NOT duplicated: media rows belong to the child comment only.
        $this->assertCount(1, $childComment->media);
        $this->assertCount(0, $projection->media, 'projection must not own its own media rows');

        // The parent page renders the child comment's media on the projection.
        $mediaUrl = $childComment->media->first()->url;
        $html = $this->actingAs($this->owner)->get(route('admin.tasks.show', $parent))->assertOk()->getContent();
        $this->assertStringContainsString($mediaUrl, $html);
    }

    public function test_reply_attachment_appears_on_child_and_through_parent_projection(): void
    {
        Storage::fake('task_media');
        [$parent, $child] = $this->parentWithHelpChild();
        $this->actingAs($this->helper)->post(route('admin.tasks.comments.store', $child), ['comment' => 'Need proof.'])->assertRedirect();
        $projection = $parent->comments()->whereNotNull('source_comment_id')->first();

        $this->actingAs($this->owner)->post(route('admin.tasks.comments.reply', [$parent, $projection]), [
            'comment' => 'Attached the signed form.',
            'media'   => [UploadedFile::fake()->image('form.jpg')],
        ])->assertRedirect();

        $childReply     = $child->comments()->where('comment', 'Attached the signed form.')->first();
        $replyProjection = $parent->comments()->where('source_comment_id', $childReply->id)->first();

        $this->assertCount(1, $childReply->media);          // canonical storage on the child
        $this->assertCount(0, $replyProjection->media);     // projection owns nothing

        $html = $this->actingAs($this->owner)->get(route('admin.tasks.show', $parent))->assertOk()->getContent();
        $this->assertStringContainsString($childReply->media->first()->url, $html);
    }

    // ─────────────────────── Polish: linked-task identification ───────────────────────

    public function test_projection_shows_and_links_the_child_task_number(): void
    {
        [$parent, $child] = $this->parentWithHelpChild();
        $this->actingAs($this->helper)
            ->post(route('admin.tasks.comments.store', $child), ['comment' => 'On it.'])->assertRedirect();

        $html = $this->actingAs($this->owner)->get(route('admin.tasks.show', $parent))->assertOk()->getContent();

        $this->assertStringContainsString('Task #' . $child->id, $html);
        $this->assertStringContainsString(route('admin.tasks.show', $child), $html);
    }

    public function test_multiple_help_children_stay_distinguishable_on_one_parent(): void
    {
        $parent = Task::create([
            'category' => 'admin', 'title' => 'Big job', 'priority' => 'normal',
            'status' => 'in_progress', 'created_by_user_id' => $this->owner->id,
        ]);
        // Two separate help requests from the same parent.
        $this->postJson(route('admin.tasks.status', $parent), [
            'status' => 'help_needed', 'help_assigned_to' => $this->helper->id, 'help_description' => 'Part A',
        ])->assertOk();
        $this->postJson(route('admin.tasks.status', $parent->refresh()), [
            'status' => 'help_needed', 'help_assigned_to' => $this->helper->id, 'help_description' => 'Part B',
        ])->assertOk();

        $children = $parent->subTasks()->orderBy('id')->get();
        $this->assertCount(2, $children);

        $this->actingAs($this->helper)->post(route('admin.tasks.comments.store', $children[0]), ['comment' => 'A done'])->assertRedirect();
        $this->actingAs($this->helper)->post(route('admin.tasks.comments.store', $children[1]), ['comment' => 'B done'])->assertRedirect();

        $html = $this->actingAs($this->owner)->get(route('admin.tasks.show', $parent))->assertOk()->getContent();
        $this->assertStringContainsString('Task #' . $children[0]->id, $html);
        $this->assertStringContainsString('Task #' . $children[1]->id, $html);
    }

    // ─────────────────────── Polish: completion wording ───────────────────────

    public function test_completion_projection_uses_help_request_completed_wording(): void
    {
        [$parent, $child] = $this->parentWithHelpChild();

        $this->actingAs($this->helper)->post(route('admin.tasks.complete', $child), [
            'comment' => 'Customer confirmed payment and uploaded the receipt.',
        ])->assertRedirect();

        $html = $this->actingAs($this->owner)->get(route('admin.tasks.show', $parent))->assertOk()->getContent();

        $this->assertStringContainsString('Help Request Completed', $html);
        $this->assertStringNotContainsString('>Completed<', $html); // not a bare "Completed" badge on the projection
        $this->assertStringContainsString('Task #' . $child->id, $html);
        $this->assertStringContainsString('Customer confirmed payment and uploaded the receipt.', $html);

        // Parent status is untouched by the child completing.
        $this->assertNotEquals('completed', $parent->fresh()->status->value);
    }

    // ─────────────────────── Polish: graceful missing media ───────────────────────

    public function test_missing_source_comment_does_not_break_the_parent_page(): void
    {
        [$parent, $child] = $this->parentWithHelpChild();
        $childComment = $child->comments()->create(['user_id' => $this->helper->id, 'comment' => 'temp']);
        $projection   = $parent->comments()->where('source_comment_id', $childComment->id)->first();
        $this->assertNotNull($projection);

        // Null the link (simulating a removed/detached source) and confirm the page still renders.
        $projection->forceFill(['source_comment_id' => null])->save();
        $childComment->delete();

        $this->actingAs($this->owner)->get(route('admin.tasks.show', $parent))->assertOk();
    }

    public function test_help_panel_uses_flatpickr_needed_by(): void
    {
        $task = Task::create([
            'category' => 'admin', 'title' => 'Flatpickr check',
            'priority' => 'normal', 'status' => 'open',
            'created_by_user_id' => $this->owner->id,
        ]);

        $html = $this->get(route('admin.tasks.show', $task))->assertOk()->getContent();

        // Canonical flatpickr wiring on the Needed By field, initialized once.
        $this->assertStringContainsString('flatpickr(\'#ts_help_due\'', $html);
        $this->assertStringContainsString('initHelpDuePicker', $html);
        $this->assertStringContainsString('id="ts_help_due"', $html);
    }
}
