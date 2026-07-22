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
}
