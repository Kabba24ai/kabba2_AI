<?php

namespace Tests\Feature\Tasks;

use App\Models\Iam\Personnel\User;
use App\Models\Tasks\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Task Center date filter (2026-07-23) — the old Due Today / Overdue checkboxes
 * were two independent booleans; checking both produced a contradictory
 * (due_date = today AND due_date < today) empty result. Replaced by ONE
 * exclusive segmented toggle with a single canonical param: due_filter =
 * all | today | overdue. Boundaries use the app-timezone today().
 */
class TaskCenterDueFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $gary;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gary = User::create([
            'unique_id' => 'tdf-gary', 'employee_code' => '80',
            'first_name' => 'Gary', 'last_name' => 'Jezorski',
            'email' => 'tdf-gary@test.local', 'password' => bcrypt('secret'), 'status' => 'Active',
        ]);
        $this->actingAs($this->gary);
    }

    private function task(string $title, $due, array $overrides = []): Task
    {
        return Task::create(array_merge([
            'category' => 'admin', 'title' => $title,
            'priority' => 'normal', 'status' => 'open',
            'assigned_to_user_id' => $this->gary->id,
            'created_by_user_id'  => $this->gary->id,
            'due_date' => $due,
        ], $overrides));
    }

    /** Seed one of each due-bucket. */
    private function seedAllBuckets(): void
    {
        $this->task('TODAY-TASK',   today());
        $this->task('OVERDUE-TASK', today()->subDays(3));
        $this->task('FUTURE-TASK',  today()->addDays(5));
        $this->task('NODUE-TASK',   null);
    }

    private function index(array $query = []): string
    {
        return $this->get(route('admin.tasks.index', $query))->assertOk()->getContent();
    }

    public function test_default_state_is_all_and_includes_every_bucket(): void
    {
        $this->seedAllBuckets();
        $html = $this->index(); // no due_filter

        foreach (['TODAY-TASK', 'OVERDUE-TASK', 'FUTURE-TASK', 'NODUE-TASK'] as $t) {
            $this->assertStringContainsString($t, $html);
        }
        // Default toggle state = All (empty hidden value, matching Type toggle).
        $this->assertStringContainsString('id="task-due-val" value=""', $html);
    }

    public function test_due_today_shows_only_todays_tasks(): void
    {
        $this->seedAllBuckets();
        $html = $this->index(['due_filter' => 'today']);

        $this->assertStringContainsString('TODAY-TASK', $html);
        $this->assertStringNotContainsString('OVERDUE-TASK', $html);
        $this->assertStringNotContainsString('FUTURE-TASK', $html);
        $this->assertStringNotContainsString('NODUE-TASK', $html);
        $this->assertStringContainsString('id="task-due-val" value="today"', $html);
    }

    public function test_overdue_shows_only_tasks_due_before_today(): void
    {
        $this->seedAllBuckets();
        $html = $this->index(['due_filter' => 'overdue']);

        $this->assertStringContainsString('OVERDUE-TASK', $html);
        $this->assertStringNotContainsString('TODAY-TASK', $html);   // excludes today
        $this->assertStringNotContainsString('FUTURE-TASK', $html);
        $this->assertStringNotContainsString('NODUE-TASK', $html);
        $this->assertStringContainsString('id="task-due-val" value="overdue"', $html);
    }

    public function test_invalid_due_filter_falls_back_to_all(): void
    {
        $this->seedAllBuckets();
        $html = $this->index(['due_filter' => 'garbage']);

        foreach (['TODAY-TASK', 'OVERDUE-TASK', 'FUTURE-TASK', 'NODUE-TASK'] as $t) {
            $this->assertStringContainsString($t, $html);
        }
    }

    public function test_date_filter_preserves_other_active_filters(): void
    {
        // Two tasks due today in different categories; category + due_filter both apply.
        $this->task('SALES-TODAY', today(), ['category' => 'sales']);
        $this->task('ADMIN-TODAY', today(), ['category' => 'admin']);

        $html = $this->index(['due_filter' => 'today', 'category' => 'sales']);

        $this->assertStringContainsString('SALES-TODAY', $html);
        $this->assertStringNotContainsString('ADMIN-TODAY', $html);
    }

    // ── Legacy parameter translation (bookmarked URLs) ───────────────────

    public function test_legacy_due_today_param_behaves_like_today(): void
    {
        $this->seedAllBuckets();
        $html = $this->index(['due_today' => '1']);

        $this->assertStringContainsString('TODAY-TASK', $html);
        $this->assertStringNotContainsString('OVERDUE-TASK', $html);
    }

    public function test_legacy_overdue_param_behaves_like_overdue(): void
    {
        $this->seedAllBuckets();
        $html = $this->index(['overdue' => '1']);

        $this->assertStringContainsString('OVERDUE-TASK', $html);
        $this->assertStringNotContainsString('TODAY-TASK', $html);
    }

    public function test_legacy_both_params_do_not_produce_a_contradictory_empty_query(): void
    {
        $this->seedAllBuckets();
        // The old bug: due_today=1 AND overdue=1 → zero results. Now → All.
        $html = $this->index(['due_today' => '1', 'overdue' => '1']);

        foreach (['TODAY-TASK', 'OVERDUE-TASK', 'FUTURE-TASK', 'NODUE-TASK'] as $t) {
            $this->assertStringContainsString($t, $html);
        }
    }

    public function test_old_checkboxes_are_gone_and_toggle_is_present(): void
    {
        $this->task('ANY', today());
        $html = $this->index();

        // The two independent checkboxes are removed…
        $this->assertStringNotContainsString('name="due_today"', $html);
        $this->assertStringNotContainsString('name="overdue"', $html);
        // …replaced by the single canonical param + a 3-way toggle whose
        // buttons set the one hidden value (mirroring the Type toggle).
        $this->assertStringContainsString('name="due_filter"', $html);
        $this->assertStringContainsString('id="task-due-val"', $html);
        $this->assertStringContainsString("task-due-val').value='today'", $html);
        $this->assertStringContainsString("task-due-val').value='overdue'", $html);
    }
}
