<?php

namespace Tests\Feature\Tasks;

use App\Models\Iam\Personnel\User;
use App\Models\Tasks\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Task Center "grouped by person" board — each teammate gets their own lane
 * so they only see their own work, with the standard due-date → priority sort
 * preserved inside every lane. Filters and focus reuse the existing filter
 * infrastructure (assigned_to = focus on one person).
 */
class TaskCenterByPersonTest extends TestCase
{
    use RefreshDatabase;

    private User $amber;
    private User $gary;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gary = User::create([
            'unique_id' => 'tcp-gary', 'employee_code' => '80',
            'first_name' => 'Gary', 'last_name' => 'Jezorski',
            'email' => 'tcp-gary@test.local', 'password' => bcrypt('secret'), 'status' => 'Active',
        ]);
        $this->amber = User::create([
            'unique_id' => 'tcp-amber', 'employee_code' => '81',
            'first_name' => 'Amber', 'last_name' => 'Sanders',
            'email' => 'tcp-amber@test.local', 'password' => bcrypt('secret'), 'status' => 'Active',
        ]);
        $this->actingAs($this->gary);
    }

    private function task(User $assignee, array $overrides = []): Task
    {
        return Task::create(array_merge([
            'category' => 'admin', 'title' => 'A task',
            'priority' => 'normal', 'status' => 'open',
            'assigned_to_user_id' => $assignee->id,
            'created_by_user_id'  => $this->gary->id,
        ], $overrides));
    }

    public function test_board_renders_a_lane_per_person_with_their_tasks(): void
    {
        $this->task($this->amber, ['title' => 'Amber only task']);
        $this->task($this->gary,  ['title' => 'Gary only task']);

        $html = $this->get(route('admin.tasks.index'))->assertOk()->getContent();

        // Both people's lanes appear with their own cards
        $this->assertStringContainsString('Amber Sanders', $html);
        $this->assertStringContainsString('Gary Jezorski', $html);
        $this->assertStringContainsString('Amber only task', $html);
        $this->assertStringContainsString('Gary only task', $html);
        // Lane header initials
        $this->assertStringContainsString('>AS<', $html);
        $this->assertStringContainsString('>GJ<', $html);
    }

    public function test_focusing_a_person_shows_only_their_lane(): void
    {
        $this->task($this->amber, ['title' => 'Amber only task']);
        $this->task($this->gary,  ['title' => 'Gary only task']);

        // The assigned_to filter is the design's "focus on one person"
        $html = $this->get(route('admin.tasks.index', ['assigned_to' => $this->amber->id]))
            ->assertOk()->getContent();

        $this->assertStringContainsString('Amber only task', $html);
        $this->assertStringNotContainsString('Gary only task', $html);
        // The "All people" un-focus control appears
        $this->assertStringContainsString('All people', $html);
    }

    public function test_unassigned_work_collects_in_its_own_lane(): void
    {
        Task::create([
            'category' => 'yard', 'title' => 'Nobody owns this yet',
            'priority' => 'normal', 'status' => 'open',
            'created_by_user_id' => $this->gary->id, // no assignee
        ]);

        $html = $this->get(route('admin.tasks.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Unassigned', $html);
        $this->assertStringContainsString('Nobody owns this yet', $html);
    }

    public function test_lane_preserves_due_then_priority_order(): void
    {
        // Same person, three tasks: due-date asc first, then priority within a date.
        $this->task($this->amber, ['title' => 'LATER-NORMAL', 'priority' => 'normal', 'due_date' => now()->addDays(5)]);
        $this->task($this->amber, ['title' => 'SOON-LOW',     'priority' => 'low',    'due_date' => now()->addDay()]);
        $this->task($this->amber, ['title' => 'SOON-URGENT',  'priority' => 'urgent', 'due_date' => now()->addDay()]);

        $html = $this->get(route('admin.tasks.index'))->assertOk()->getContent();

        $posUrgent = strpos($html, 'SOON-URGENT');
        $posLow    = strpos($html, 'SOON-LOW');
        $posLater  = strpos($html, 'LATER-NORMAL');

        // Same due date: urgent before low; earlier due date before later one.
        $this->assertLessThan($posLow, $posUrgent);
        $this->assertLessThan($posLater, $posLow);
    }

    public function test_category_filter_still_applies_within_the_board(): void
    {
        $this->task($this->amber, ['title' => 'ADMIN-CARD', 'category' => 'admin']);
        $this->task($this->amber, ['title' => 'SALES-CARD', 'category' => 'sales']);

        $html = $this->get(route('admin.tasks.index', ['category' => 'sales']))
            ->assertOk()->getContent();

        $this->assertStringContainsString('SALES-CARD', $html);
        $this->assertStringNotContainsString('ADMIN-CARD', $html);
    }

    public function test_empty_board_shows_a_message(): void
    {
        $html = $this->get(route('admin.tasks.index'))->assertOk()->getContent();

        $this->assertStringContainsString('No tasks match these filters.', $html);
    }

    // ─────────────────────── Focused-view refinements ───────────────────────

    public function test_focused_view_shows_task_description_but_summary_does_not(): void
    {
        $this->task($this->amber, ['title' => 'Rent n King Emails', 'description' => 'Need to set up email accounts for Ashley & Amber.']);

        // Summary (all people) stays compact — no description.
        $summary = $this->get(route('admin.tasks.index'))->assertOk()->getContent();
        $this->assertStringNotContainsString('Need to set up email accounts', $summary);

        // Focused on Amber — description appears beneath the title.
        $focused = $this->get(route('admin.tasks.index', ['assigned_to' => $this->amber->id]))->assertOk()->getContent();
        $this->assertStringContainsString('Need to set up email accounts for Ashley &amp; Amber.', $focused);
        // Uses the app's compiled line-clamp convention to cap height.
        $this->assertStringContainsString('line-clamp-2', $focused);
    }

    public function test_focused_task_without_description_renders_no_description_row(): void
    {
        $this->task($this->amber, ['title' => 'No desc task', 'description' => null]);

        $focused = $this->get(route('admin.tasks.index', ['assigned_to' => $this->amber->id]))->assertOk()->getContent();

        $this->assertStringContainsString('No desc task', $focused);
        $this->assertStringNotContainsString('line-clamp-2', $focused); // no empty description block
    }

    public function test_summary_and_focused_resolve_the_same_employee_theme(): void
    {
        $amberTheme = \App\Support\Tasks\EmployeeTheme::for($this->amber->id);

        $this->task($this->amber, ['title' => 'Amber task']);
        $this->task($this->gary,  ['title' => 'Gary task']); // ensures Amber isn't the only/first lane

        $summary = $this->get(route('admin.tasks.index'))->assertOk()->getContent();
        $focused = $this->get(route('admin.tasks.index', ['assigned_to' => $this->amber->id]))->assertOk()->getContent();

        // Amber's canonical accent colour appears in both views (not a
        // loop-position default that would differ when focused).
        $this->assertStringContainsString($amberTheme['color'], $summary);
        $this->assertStringContainsString($amberTheme['color'], $focused);
    }

    public function test_employee_theme_is_stable_regardless_of_filtering_or_order(): void
    {
        // The theme is keyed on the user id, so it never depends on how many
        // lanes render or their order.
        $this->assertEquals(
            \App\Support\Tasks\EmployeeTheme::for($this->gary->id),
            \App\Support\Tasks\EmployeeTheme::for($this->gary->id),
        );
        $this->assertEquals('#475569', \App\Support\Tasks\EmployeeTheme::for(null)['color']); // unassigned = slate

        // A focused single-lane view resolves Gary's own colour, never palette[0].
        $garyTheme = \App\Support\Tasks\EmployeeTheme::for($this->gary->id);
        $this->task($this->gary, ['title' => 'Solo']);
        $html = $this->get(route('admin.tasks.index', ['assigned_to' => $this->gary->id]))->assertOk()->getContent();
        $this->assertStringContainsString($garyTheme['color'], $html);
    }

    public function test_assigned_to_filter_pills_are_gone_from_both_views(): void
    {
        $this->task($this->amber, ['title' => 'Amber task']);

        $summary = $this->get(route('admin.tasks.index'))->assertOk()->getContent();
        $focused = $this->get(route('admin.tasks.index', ['assigned_to' => $this->amber->id]))->assertOk()->getContent();

        foreach ([$summary, $focused] as $html) {
            // The filter pill-row label is gone (the Completed Today table still
            // has an "Assigned To" column, so match the pill label markup).
            $this->assertStringNotContainsString('tracking-wide shrink-0">Assigned To', $html);
            $this->assertStringNotContainsString('Assigned To badge', $html);
        }
        // Focused mode keeps the "All people" return control.
        $this->assertStringContainsString('All people', $focused);
        $this->assertStringNotContainsString('All people', $summary);
    }

    // ─────────────────────── Layout: panel removal + capped grid ───────────────────────

    public function test_tasks_completed_side_panel_is_removed(): void
    {
        $this->task($this->amber, ['title' => 'Amber task']);

        $html = $this->get(route('admin.tasks.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('Tasks Completed -', $html);
        $this->assertStringNotContainsString('No tasks completed today.', $html);
    }

    public function test_overview_uses_a_width_capped_responsive_grid(): void
    {
        $this->task($this->amber, ['title' => 'Amber task']);

        $html = $this->get(route('admin.tasks.index'))->assertOk()->getContent();

        // Columns cap at 400px and the grid fits as many as the width allows.
        $this->assertStringContainsString('repeat(auto-fit, minmax(320px, 400px))', $html);
    }

    public function test_focused_mode_stays_full_width_single_column(): void
    {
        $this->task($this->amber, ['title' => 'Amber task']);

        $html = $this->get(route('admin.tasks.index', ['assigned_to' => $this->amber->id]))->assertOk()->getContent();

        // Focused view is one full-width lane, not the capped multi-column grid.
        $this->assertStringContainsString('minmax(0,1fr)', $html);
        $this->assertStringNotContainsString('repeat(auto-fit, minmax(320px, 400px))', $html);
    }

    public function test_filters_still_work_in_focused_mode(): void
    {
        $this->task($this->amber, ['title' => 'AMBER-ADMIN', 'category' => 'admin']);
        $this->task($this->amber, ['title' => 'AMBER-SALES', 'category' => 'sales']);

        // Focus on Amber + category filter both apply together.
        $html = $this->get(route('admin.tasks.index', ['assigned_to' => $this->amber->id, 'category' => 'sales']))
            ->assertOk()->getContent();

        $this->assertStringContainsString('AMBER-SALES', $html);
        $this->assertStringNotContainsString('AMBER-ADMIN', $html);
        $this->assertStringContainsString('All people', $html); // still focused
    }
}
