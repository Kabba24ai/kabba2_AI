<?php

namespace Tests\Feature\Dashboard;

use App\Models\Iam\Personnel\User;
use App\Models\Tasks\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Per-employee "Completed Today" badge in the dashboard Task Manager Alerts.
 * Counts tasks by the employee who completed them (completed_by_user_id) whose
 * completed_at falls on today() in the app timezone — the same business-day
 * rule as Task::completedToday(). Zero is never shown, and the panel still
 * only surfaces employees who have pending work.
 */
class TeamWorkloadCompletedTodayTest extends TestCase
{
    use RefreshDatabase;

    private User $gary;
    private User $ashley;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gary = User::create([
            'unique_id' => 'twc-gary', 'employee_code' => '60',
            'first_name' => 'Gary', 'last_name' => 'Jezorski',
            'email' => 'twc-gary@test.local', 'password' => bcrypt('secret'), 'status' => 'Active',
        ]);
        $this->ashley = User::create([
            'unique_id' => 'twc-ashley', 'employee_code' => '61',
            'first_name' => 'Ashley', 'last_name' => 'Mallard',
            'email' => 'twc-ashley@test.local', 'password' => bcrypt('secret'), 'status' => 'Active',
        ]);
        $this->actingAs($this->gary);
    }

    /** An open task keeps a user on the alerts panel (the panel lists pending work). */
    private function openTaskFor(User $u, array $overrides = []): Task
    {
        return Task::create(array_merge([
            'category' => 'admin', 'title' => 'Open item', 'priority' => 'normal',
            'status' => 'open', 'assigned_to_user_id' => $u->id, 'created_by_user_id' => $u->id,
        ], $overrides));
    }

    private function completedTaskBy(User $completer, ?\Carbon\Carbon $at = null, array $overrides = []): Task
    {
        return Task::create(array_merge([
            'category' => 'admin', 'title' => 'Done item', 'priority' => 'normal',
            'status' => 'completed', 'created_by_user_id' => $completer->id,
            'completed_by_user_id' => $completer->id, 'completed_at' => $at ?? now(),
        ], $overrides));
    }

    private function dashboardHtml(): string
    {
        return $this->get(route('admin.dashboard.index'))->assertOk()->getContent();
    }

    public function test_one_completed_today_shows_singular_count(): void
    {
        $this->openTaskFor($this->gary);
        $this->completedTaskBy($this->gary);

        $this->assertStringContainsString('1 Completed Today', $this->dashboardHtml());
    }

    public function test_multiple_completed_today_shows_correct_count(): void
    {
        $this->openTaskFor($this->gary);
        $this->completedTaskBy($this->gary);
        $this->completedTaskBy($this->gary);
        $this->completedTaskBy($this->gary);

        $this->assertStringContainsString('3 Completed Today', $this->dashboardHtml());
    }

    public function test_zero_completed_shows_no_badge(): void
    {
        $this->openTaskFor($this->gary);

        // 'Completed Today' also appears as a chart label, so match the badge
        // pattern specifically (a count precedes it).
        $this->assertDoesNotMatchRegularExpression('/\\d+ Completed Today/', $this->dashboardHtml());
    }

    public function test_yesterdays_completions_are_excluded(): void
    {
        $this->openTaskFor($this->gary);
        $this->completedTaskBy($this->gary, now()->subDay());

        $this->assertDoesNotMatchRegularExpression('/\\d+ Completed Today/', $this->dashboardHtml());
    }

    public function test_business_day_midnight_boundary(): void
    {
        $this->openTaskFor($this->gary);
        // Just after midnight today counts; one second before midnight (yesterday) does not.
        $this->completedTaskBy($this->gary, today()->copy()->addMinutes(2));
        $this->completedTaskBy($this->gary, today()->copy()->subSecond());

        $this->assertStringContainsString('1 Completed Today', $this->dashboardHtml());
    }

    public function test_attribution_is_the_completer_not_the_assignee(): void
    {
        // Only Ashley has open work → only Ashley appears on the panel.
        $this->openTaskFor($this->ashley);
        // A task assigned to Gary but COMPLETED by Ashley.
        $this->completedTaskBy($this->ashley, now(), ['assigned_to_user_id' => $this->gary->id]);

        // The badge appears (credited to Ashley, who is on the panel). Had it
        // been credited to the assignee Gary, he isn't shown and no badge would render.
        $this->assertStringContainsString('1 Completed Today', $this->dashboardHtml());
    }

    public function test_reopened_task_no_longer_counts(): void
    {
        $this->openTaskFor($this->gary);
        $done = $this->completedTaskBy($this->gary);

        // Canonical reopen (as TaskController::update does): status leaves
        // completed and completed_at is cleared.
        $done->forceFill(['status' => 'open', 'completed_at' => null])->save();

        $this->assertDoesNotMatchRegularExpression('/\\d+ Completed Today/', $this->dashboardHtml());
    }

    public function test_deleted_completed_task_is_excluded(): void
    {
        $this->openTaskFor($this->gary);
        $this->completedTaskBy($this->gary);
        $extra = $this->completedTaskBy($this->gary);
        $extra->delete();

        $this->assertStringContainsString('1 Completed Today', $this->dashboardHtml());
    }

    public function test_parent_and_child_help_tasks_count_independently(): void
    {
        $this->openTaskFor($this->gary);
        $parent = $this->completedTaskBy($this->gary);
        $this->completedTaskBy($this->gary, now(), ['parent_task_id' => $parent->id, 'title' => 'Help child']);

        $this->assertStringContainsString('2 Completed Today', $this->dashboardHtml());
    }

    public function test_existing_indicators_remain_intact(): void
    {
        $this->openTaskFor($this->gary, ['priority' => 'urgent', 'due_date' => now()->subDays(2)]);
        $this->completedTaskBy($this->gary);

        $html = $this->dashboardHtml();
        $this->assertStringContainsString('1 Completed Today', $html);
        $this->assertStringContainsString('1 Urgent', $html);
        $this->assertStringContainsString('1 Overdue', $html);
        $this->assertStringContainsString('Gary Jezorski', $html);
    }

    public function test_completed_count_query_is_not_n_plus_one(): void
    {
        // Several employees, several completions each.
        foreach ([$this->gary, $this->ashley] as $u) {
            $this->openTaskFor($u);
            $this->completedTaskBy($u);
            $this->completedTaskBy($u);
        }

        $completedQueries = 0;
        DB::listen(function ($q) use (&$completedQueries) {
            if (str_contains($q->sql, 'completed_by_user_id') && str_contains(strtolower($q->sql), 'count(')) {
                $completedQueries++;
            }
        });

        $this->dashboardHtml();

        $this->assertEquals(1, $completedQueries, 'completed-today counts must come from a single aggregated query');
    }
}
