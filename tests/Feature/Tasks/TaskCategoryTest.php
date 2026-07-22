<?php

namespace Tests\Feature\Tasks;

use App\Enums\Tasks\TaskCategory;
use App\Models\Iam\Personnel\User;
use App\Models\Tasks\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Task categories: Billing added 2026-07-21, and the display order is
 * business-defined — Sales | Admin | Billing | Yard | Shop. Enum case
 * order IS the display order (dropdowns and filter badges follow cases()).
 */
class TaskCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::create([
            'unique_id' => 'cat-test-admin', 'employee_code' => '93',
            'first_name' => 'Category', 'last_name' => 'Tester',
            'email' => 'category-tester@test.local', 'password' => bcrypt('secret'), 'status' => 'Active',
        ]));
    }

    public function test_category_display_order_is_sales_admin_billing_yard_shop(): void
    {
        $this->assertEquals(
            ['sales', 'admin', 'billing', 'yard', 'shop'],
            array_map(fn (TaskCategory $c) => $c->value, TaskCategory::cases()),
        );
    }

    public function test_a_billing_task_can_be_created(): void
    {
        $this->postJson(route('admin.tasks.store'), [
            'category' => 'billing',
            'title'    => 'Reconcile July card batch',
            'priority' => 'normal',
        ])->assertOk()->assertJson(['success' => true]);

        $task = Task::latest('id')->first();
        $this->assertEquals('billing', $task->category->value);
        $this->assertEquals('Billing', $task->category->label());
    }

    public function test_unknown_categories_are_still_rejected(): void
    {
        $this->postJson(route('admin.tasks.store'), [
            'category' => 'accounting',
            'title'    => 'Bad category',
            'priority' => 'normal',
        ])->assertStatus(422)->assertJsonValidationErrors('category');
    }

    public function test_task_center_renders_the_billing_filter_badge(): void
    {
        Task::create([
            'category' => 'billing', 'title' => 'Billing badge check',
            'priority' => 'normal', 'status' => 'open',
            'created_by_user_id' => auth()->id(),
        ]);

        $html = $this->get(route('admin.tasks.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Billing', $html);
    }
}
