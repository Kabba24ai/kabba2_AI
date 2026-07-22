<?php

namespace Tests\Feature\Tasks;

use App\Models\Iam\Personnel\User;
use App\Models\Tasks\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Mobile/API task creation must obey the same rule as the web: a new task
 * always starts Open, and a submitted status is never honoured.
 */
class TaskApiCreationStatusTest extends TestCase
{
    use RefreshDatabase;

    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employee = User::create([
            'unique_id' => 'api-task-user', 'employee_code' => '55',
            'first_name' => 'Api', 'last_name' => 'Creator',
            'email' => 'api-task@test.local', 'password' => bcrypt('secret'), 'status' => 'Active',
        ]);
    }

    private function createTask(array $payload): \Illuminate\Testing\TestResponse
    {
        return $this->withoutMiddleware()
            ->actingAs($this->employee, 'api_user')
            ->json('POST', 'http://' . config('app.domains.api') . '/api/admin/v1/tasks', $payload);
    }

    public function test_api_created_task_starts_open_without_a_status_field(): void
    {
        $this->createTask([
            'category' => 'admin', 'title' => 'API task', 'priority' => 'normal',
        ])->assertStatus(201)->assertJson(['success' => true]);

        $this->assertEquals('open', Task::latest('id')->first()->status->value);
    }

    public function test_api_crafted_status_cannot_set_a_non_open_starting_status(): void
    {
        $this->createTask([
            'category' => 'admin', 'title' => 'Sneaky', 'priority' => 'normal',
            'status' => 'completed',
        ])->assertStatus(201);

        $task = Task::latest('id')->first();
        $this->assertEquals('open', $task->status->value);
        $this->assertNull($task->completed_at);
    }
}
