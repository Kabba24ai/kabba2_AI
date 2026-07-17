<?php

namespace Tests\Feature\Tasks;

use App\Jobs\PurgeCompletedTaskMediaJob;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerCallNeeded;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Supplier;
use App\Models\Tasks\Task;
use App\Models\Tasks\TaskMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Task Center unified "New Task" modal — one button, one modal, a type
 * toggle that routes to the two existing save paths (operational task vs
 * phone call reminder). Also pins the new optional related-party linkage
 * on operational tasks (customer / supplier / free-text other).
 */
class UnifiedTaskModalTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'unique_id' => 'ut-test-admin', 'employee_code' => '97',
            'first_name' => 'Unified', 'last_name' => 'Tester',
            'email' => 'unified-tester@test.local', 'password' => bcrypt('secret'), 'status' => 'Active',
        ]);
        $this->actingAs($this->admin);
    }

    // ─────────────────────────────────────────────────────────
    // Page shell
    // ─────────────────────────────────────────────────────────

    public function test_task_center_has_one_create_button_and_the_unified_modal(): void
    {
        $html = $this->get(route('admin.tasks.index'))->assertOk()->getContent();

        // Single entry point — the old Call Needed header button is gone
        $this->assertStringContainsString('+ New Task', $html);
        $this->assertStringNotContainsString('onclick="openCallNeededModal()"', $html);

        // Unified modal with the type toggle and both mode-specific slots
        $this->assertStringContainsString('UnifiedTaskModal', $html);
        $this->assertStringContainsString('Operational task', $html);
        $this->assertStringContainsString('Phone call', $html);
        $this->assertStringContainsString('ut_subject_task', $html);
        $this->assertStringContainsString('ut_subject_call', $html);
        $this->assertStringContainsString('ut_related_type', $html);

        // The old operational-only modal is no longer rendered here
        $this->assertStringNotContainsString('NewTaskModal"', $html);

        // The shared call modal partial must remain for edit/complete flows
        $this->assertStringContainsString('CallNeededModal', $html);
        $this->assertStringContainsString('CompleteCallModal', $html);
    }

    public function test_dashboard_uses_the_same_unified_modal(): void
    {
        $html = $this->get(route('admin.dashboard.index'))->assertOk()->getContent();

        // Same single entry point and same modal as the Task Center
        $this->assertStringContainsString('+ New Task', $html);
        $this->assertStringNotContainsString('onclick="openCallNeededModal()"', $html);
        $this->assertStringContainsString('UnifiedTaskModal', $html);
        $this->assertStringContainsString('ut_subject_task', $html);
        $this->assertStringContainsString('ut_subject_call', $html);

        // The retired operational-only modal never comes back
        $this->assertStringNotContainsString('NewTaskModal"', $html);

        // Call widget's edit/complete/reschedule modals still present
        $this->assertStringContainsString('CallNeededModal', $html);
        $this->assertStringContainsString('CompleteCallModal', $html);
    }

    // ─────────────────────────────────────────────────────────
    // Operational save path (+ new related-party linkage)
    // ─────────────────────────────────────────────────────────

    private function taskPayload(array $overrides = []): array
    {
        return array_merge([
            'category' => 'yard',
            'title'    => 'Restack pallet racks',
            'priority' => 'normal',
            'status'   => 'open',
        ], $overrides);
    }

    public function test_operational_task_saves_with_customer_link(): void
    {
        $customer = Customer::create([
            'first_name' => 'Linked', 'last_name' => 'Customer',
            'email' => 'linked@example.com', 'status' => 'Active', 'phone' => '6165550201',
        ]);

        $this->postJson(route('admin.tasks.store'), $this->taskPayload([
            'related_customer_id' => $customer->id,
        ]))->assertOk()->assertJson(['success' => true]);

        $task = Task::latest('id')->first();
        $this->assertEquals($customer->id, $task->related_customer_id);
        $this->assertNull($task->related_supplier_id);
        $this->assertNull($task->related_other);
    }

    public function test_operational_task_saves_with_supplier_link(): void
    {
        $supplier = Supplier::create(['name' => 'Acme Parts', 'status' => 'Active']);

        $this->postJson(route('admin.tasks.store'), $this->taskPayload([
            'related_supplier_id' => $supplier->id,
        ]))->assertOk()->assertJson(['success' => true]);

        $this->assertEquals($supplier->id, Task::latest('id')->first()->related_supplier_id);
    }

    public function test_operational_task_saves_with_free_text_other(): void
    {
        $this->postJson(route('admin.tasks.store'), $this->taskPayload([
            'related_other' => 'City inspector — north yard permit',
        ]))->assertOk()->assertJson(['success' => true]);

        $this->assertEquals(
            'City inspector — north yard permit',
            Task::latest('id')->first()->related_other,
        );
    }

    public function test_operational_task_still_saves_with_no_related_party(): void
    {
        $this->postJson(route('admin.tasks.store'), $this->taskPayload())
            ->assertOk()->assertJson(['success' => true]);

        $task = Task::latest('id')->first();
        $this->assertNull($task->related_customer_id);
        $this->assertNull($task->related_supplier_id);
        $this->assertNull($task->related_other);
    }

    public function test_invalid_related_ids_are_rejected(): void
    {
        $this->postJson(route('admin.tasks.store'), $this->taskPayload([
            'related_supplier_id' => 999999,
        ]))->assertStatus(422)->assertJsonValidationErrors('related_supplier_id');

        $this->postJson(route('admin.tasks.store'), $this->taskPayload([
            'related_customer_id' => 999999,
        ]))->assertStatus(422)->assertJsonValidationErrors('related_customer_id');

        $this->assertEquals(0, Task::count());
    }

    public function test_operational_required_fields_unchanged(): void
    {
        $this->postJson(route('admin.tasks.store'), [
            'title' => 'Missing everything else',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['category', 'priority', 'status']);
    }

    // ─────────────────────────────────────────────────────────
    // Phone call save path (regression — endpoint untouched)
    // ─────────────────────────────────────────────────────────

    public function test_call_reminder_save_path_is_unchanged(): void
    {
        $customer = Customer::create([
            'first_name' => 'Call', 'last_name' => 'Target',
            'email' => 'call-target@example.com', 'status' => 'Active', 'phone' => '6165550202',
        ]);

        $this->postJson(route('admin.dashboard.call-needed.store'), [
            'customer_id' => $customer->id,
            'assigned_to' => $this->admin->id,
            'reason'      => 'general_followup',
            'category'    => 'sales',
            'priority'    => 'high',
            'notes'       => 'Ask about the spring order.',
            'due_date'    => now()->addDay()->format('Y-m-d'),
        ])->assertOk()->assertJson(['success' => true]);

        $call = CustomerCallNeeded::latest('id')->first();
        $this->assertEquals($customer->id, $call->customer_id);
        $this->assertEquals($this->admin->id, $call->created_by); // assignee quirk preserved
        $this->assertEquals('general_followup', $call->reason);
        $this->assertEquals('active', $call->status);
    }

    public function test_call_reminder_manual_contact_still_works(): void
    {
        $this->postJson(route('admin.dashboard.call-needed.store'), [
            'contact_name'  => 'Walk-in Wayne',
            'contact_phone' => '(616) 555-0203',
            'assigned_to'   => $this->admin->id,
            'reason'        => 'returning_call',
            'category'      => 'admin',
        ])->assertOk()->assertJson(['success' => true]);

        $call = CustomerCallNeeded::latest('id')->first();
        $this->assertEquals('Walk-in Wayne', $call->contact_name);
        $this->assertNull($call->customer_id);
    }

    // ─────────────────────────────────────────────────────────
    // Show page displays the new linkage
    // ─────────────────────────────────────────────────────────

    public function test_show_page_displays_related_customer(): void
    {
        $customer = Customer::create([
            'first_name' => 'Shown', 'last_name' => 'Customer',
            'email' => 'shown@example.com', 'status' => 'Active', 'phone' => '6165550204',
        ]);
        $task = Task::create($this->taskPayload([
            'title' => 'Customer-linked task',
            'related_customer_id' => $customer->id,
            'created_by_user_id'  => $this->admin->id,
        ]));

        $html = $this->get(route('admin.tasks.show', $task))->assertOk()->getContent();

        $this->assertStringContainsString('Related To', $html);
        $this->assertStringContainsString('Shown Customer', $html);
    }

    public function test_show_page_displays_related_other(): void
    {
        $task = Task::create($this->taskPayload([
            'title' => 'Other-linked task',
            'related_other'      => 'DNR site visit',
            'created_by_user_id' => $this->admin->id,
        ]));

        $html = $this->get(route('admin.tasks.show', $task))->assertOk()->getContent();

        $this->assertStringContainsString('Related To', $html);
        $this->assertStringContainsString('DNR site visit', $html);
    }

    // ─────────────────────────────────────────────────────────
    // Attachments: create, comment, display, retention purge
    // ─────────────────────────────────────────────────────────

    public function test_modal_has_title_label_icons_and_attachment_input(): void
    {
        $html = $this->get(route('admin.tasks.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Title <span class="text-red-500">*</span>', $html);
        $this->assertStringContainsString('id="ut_media"', $html);
        // Both toggle buttons carry an icon
        $this->assertMatchesRegularExpression('/id="ut_toggle_task"[^>]*>\s*<svg/s', $html);
        $this->assertMatchesRegularExpression('/id="ut_toggle_call"[^>]*>\s*<svg/s', $html);
    }

    public function test_operational_task_saves_description_media(): void
    {
        Storage::fake('task_media');

        $this->post(route('admin.tasks.store'), $this->taskPayload([
            'media' => [
                UploadedFile::fake()->image('yard-photo.jpg'),
                UploadedFile::fake()->create('walkthrough.mp4', 1024, 'video/mp4'),
            ],
        ]), ['Accept' => 'application/json'])->assertOk()->assertJson(['success' => true]);

        $task = Task::latest('id')->first();
        $media = $task->media()->orderBy('id')->get();

        $this->assertCount(2, $media);
        $this->assertEquals(['image', 'video'], $media->pluck('media_type')->all());
        $this->assertEquals('yard-photo.jpg', $media[0]->original_filename);
        $this->assertTrue($media->every(fn ($m) => $m->task_comment_id === null));
        // Files live in the task's own folder on the segregated disk
        foreach ($media as $item) {
            $this->assertStringStartsWith($task->id . '/', $item->file_path);
            Storage::disk('task_media')->assertExists($item->file_path);
        }
    }

    public function test_comment_and_completion_comment_save_media(): void
    {
        Storage::fake('task_media');
        $task = Task::create($this->taskPayload(['created_by_user_id' => $this->admin->id]));

        $this->post(route('admin.tasks.comments.store', $task), [
            'comment' => 'Progress photo attached.',
            'media'   => [UploadedFile::fake()->image('progress.png')],
        ])->assertRedirect();

        $comment = $task->comments()->first();
        $this->assertCount(1, $comment->media);
        $this->assertEquals('image', $comment->media->first()->media_type);

        $this->post(route('admin.tasks.complete', $task), [
            'comment' => 'Done — see final clip.',
            'media'   => [UploadedFile::fake()->create('final.webm', 512, 'video/webm')],
        ])->assertRedirect();

        $task->refresh();
        $this->assertEquals('completed', $task->status->value);
        $completionComment = $task->comments()->latest('id')->first();
        $this->assertCount(1, $completionComment->media);
        $this->assertEquals('video', $completionComment->media->first()->media_type);
    }

    public function test_disallowed_file_types_are_rejected(): void
    {
        Storage::fake('task_media');

        $this->postJson(route('admin.tasks.store'), $this->taskPayload([
            'media' => [UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf')],
        ]))->assertStatus(422)->assertJsonValidationErrors('media.0');

        $this->assertEquals(0, Task::count());
    }

    public function test_media_is_purged_30_days_after_completion(): void
    {
        Storage::fake('task_media');

        $makeTaskWithMedia = function (string $title): Task {
            $task = Task::create($this->taskPayload(['title' => $title, 'created_by_user_id' => $this->admin->id]));
            $path = $task->id . '/evidence.jpg';
            Storage::disk('task_media')->put($path, 'fake-bytes');
            $task->media()->create([
                'media_type' => 'image', 'file_path' => $path,
                'original_filename' => 'evidence.jpg', 'uploaded_by' => $this->admin->id,
            ]);
            return $task;
        };

        $old   = $makeTaskWithMedia('Old completed');
        $young = $makeTaskWithMedia('Recently completed');
        $open  = $makeTaskWithMedia('Still open');

        $old->forceFill(['status' => 'completed', 'completed_at' => now()->subDays(31)])->save();
        $young->forceFill(['status' => 'completed', 'completed_at' => now()->subDays(5)])->save();

        (new PurgeCompletedTaskMediaJob())->handle();

        // 31 days post-completion: rows AND files flushed
        $this->assertEquals(0, TaskMedia::where('task_id', $old->id)->count());
        Storage::disk('task_media')->assertMissing($old->id . '/evidence.jpg');

        // 5 days post-completion and never-completed: untouched
        $this->assertEquals(1, TaskMedia::where('task_id', $young->id)->count());
        Storage::disk('task_media')->assertExists($young->id . '/evidence.jpg');
        $this->assertEquals(1, TaskMedia::where('task_id', $open->id)->count());
        Storage::disk('task_media')->assertExists($open->id . '/evidence.jpg');

        // Task, comments, and history survive — only media goes
        $this->assertDatabaseHas('daily_tasks', ['id' => $old->id, 'title' => 'Old completed']);
    }

    public function test_deleting_a_task_removes_its_media_files(): void
    {
        Storage::fake('task_media');

        $task = Task::create($this->taskPayload(['created_by_user_id' => $this->admin->id]));
        $path = $task->id . '/photo.jpg';
        Storage::disk('task_media')->put($path, 'bytes');
        $task->media()->create([
            'media_type' => 'image', 'file_path' => $path,
            'original_filename' => 'photo.jpg', 'uploaded_by' => $this->admin->id,
        ]);

        $this->delete(route('admin.tasks.destroy', $task))->assertRedirect();

        $this->assertDatabaseMissing('daily_tasks', ['id' => $task->id]);
        $this->assertEquals(0, TaskMedia::where('task_id', $task->id)->count());
        Storage::disk('task_media')->assertMissing($path);
    }

    public function test_show_page_displays_attachments(): void
    {
        Storage::fake('task_media');

        $task = Task::create($this->taskPayload(['title' => 'Media task', 'created_by_user_id' => $this->admin->id]));
        Storage::disk('task_media')->put($task->id . '/site.jpg', 'bytes');
        $task->media()->create([
            'media_type' => 'image', 'file_path' => $task->id . '/site.jpg',
            'original_filename' => 'site.jpg', 'mime_type' => 'image/jpeg',
            'uploaded_by' => $this->admin->id,
        ]);

        $html = $this->get(route('admin.tasks.show', $task))->assertOk()->getContent();

        $this->assertStringContainsString('Attachments', $html);
        $this->assertStringContainsString('site.jpg', $html);
        // Comment form can carry files
        $this->assertStringContainsString('enctype="multipart/form-data"', $html);
        $this->assertStringContainsString('name="media[]"', $html);
    }

    public function test_show_page_omits_related_panel_when_unlinked(): void
    {
        $task = Task::create($this->taskPayload([
            'title'              => 'Plain task',
            'created_by_user_id' => $this->admin->id,
        ]));

        $html = $this->get(route('admin.tasks.show', $task))->assertOk()->getContent();

        $this->assertStringNotContainsString('Related To', $html);
    }
}
