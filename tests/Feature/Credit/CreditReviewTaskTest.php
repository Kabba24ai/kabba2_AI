<?php

namespace Tests\Feature\Credit;

use App\Enums\Credit\CreditReviewTaskOutcome;
use App\Enums\Tasks\TaskCategory;
use App\Enums\Tasks\TaskPriority;
use App\Helpers\CustomHelper;
use App\Models\Configurations\Setting;
use App\Models\Credit\CreditThresholdEvent;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAccount;
use App\Models\Iam\Personnel\User;
use App\Models\Tasks\Task;
use App\Services\Billing\PrimaryBillingAdminResolver;
use App\Services\Credit\SystemActorResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Credit Threshold Exception — management-review task behavior: assignment to
 * the Primary Billing Admin, the missing-admin fallback (event preserved, task
 * unassigned + system-owned + flagged, financial posting untouched), and
 * new-task-after-resolved.
 */
class CreditReviewTaskTest extends TestCase
{
    use RefreshDatabase;

    private function approvedCreditCustomer(float $limit = 1000): Customer
    {
        return Customer::create([
            'first_name' => 'Credit', 'last_name' => 'Account',
            'email' => 'credit-' . uniqid() . '@test.local', 'status' => 'Active',
            'is_credit_account' => 1, 'credit_limit' => $limit, 'available_credit_balance' => 0,
        ]);
    }

    private function clearPrimaryAdmin(): void
    {
        Setting::updateOrCreate(
            ['setting_name' => PrimaryBillingAdminResolver::SETTING_NAME],
            ['setting_type' => PrimaryBillingAdminResolver::SETTING_TYPE, 'setting_value' => null],
        );
    }

    private function setPrimaryAdmin(User $admin): void
    {
        Setting::updateOrCreate(
            ['setting_name' => PrimaryBillingAdminResolver::SETTING_NAME],
            ['setting_type' => PrimaryBillingAdminResolver::SETTING_TYPE, 'setting_value' => $admin->id],
        );
    }

    private function postCharge(Customer $customer, float $amount): void
    {
        $account = new CustomerAccount();
        $account->customer_id = $customer->id;
        $account->amount = $amount;
        $account->reason = 'Test Charge';
        $account->type = 'charge';
        $account->date = now();
        $account->sales_tax = 0;
        $account->sales_tax_type = 'free';
        $account->save();
        CustomHelper::updateCreditBalance($account);
    }

    public function test_task_is_created_and_assigned_to_primary_billing_admin(): void
    {
        $admin = User::create([
            'first_name' => 'Billing', 'last_name' => 'Admin',
            'email' => 'admin-' . uniqid() . '@test.local', 'status' => 'Active',
        ]);
        $this->setPrimaryAdmin($admin);

        $customer = $this->approvedCreditCustomer(1000);
        $this->postCharge($customer, 1200);

        $task = Task::where('related_customer_id', $customer->id)->first();
        $this->assertNotNull($task);
        $this->assertEquals($admin->id, $task->assigned_to_user_id);
        $this->assertEquals($admin->id, $task->created_by_user_id);
        $this->assertEquals(TaskCategory::Billing, $task->category);
        $this->assertEquals(TaskPriority::High, $task->priority);
        $this->assertStringContainsString('Credit Account Review', $task->title);

        $event = CreditThresholdEvent::first();
        $this->assertEquals(CreditReviewTaskOutcome::Created, $event->task_outcome);
        $this->assertEquals($task->id, $event->task_id);
    }

    public function test_missing_admin_preserves_event_and_creates_unassigned_system_task(): void
    {
        $this->clearPrimaryAdmin();
        $customer = $this->approvedCreditCustomer(1000);

        $this->postCharge($customer, 1200);

        // Financial posting must have succeeded regardless.
        $this->assertEquals(1200, (float) $customer->fresh()->available_credit_balance);

        // Durable event preserved.
        $event = CreditThresholdEvent::first();
        $this->assertNotNull($event);
        $this->assertEquals(CreditReviewTaskOutcome::DeferredNoAdmin, $event->task_outcome);
        $this->assertTrue($event->task_outcome->needsAttention());

        // Task created but unassigned, owned by the isolated System actor.
        $task = Task::where('related_customer_id', $customer->id)->first();
        $this->assertNotNull($task);
        $this->assertNull($task->assigned_to_user_id, 'no silent assignment to an arbitrary manager');
        $this->assertEquals(SystemActorResolver::id(), $task->created_by_user_id);
    }

    public function test_system_actor_is_isolated_and_inactive(): void
    {
        $system = SystemActorResolver::user();

        $this->assertEquals('Inactive', $system->status, 'system actor cannot log in / appear in active selectors');
        $this->assertEquals(SystemActorResolver::EMAIL, $system->email);
        // Idempotent — same row returned.
        $this->assertEquals($system->id, SystemActorResolver::id());
    }

    public function test_new_task_created_only_after_prior_is_resolved(): void
    {
        $admin = User::create([
            'first_name' => 'Billing', 'last_name' => 'Admin',
            'email' => 'admin-' . uniqid() . '@test.local', 'status' => 'Active',
        ]);
        $this->setPrimaryAdmin($admin);

        $customer = $this->approvedCreditCustomer(1000);
        $this->postCharge($customer, 1200); // task #1
        $firstTask = Task::where('related_customer_id', $customer->id)->firstOrFail();

        // Resolve the review task.
        $firstTask->update(['status' => 'completed', 'completed_at' => now()]);

        $this->postCharge($customer, 300); // over again → NEW task (prior resolved)

        $this->assertEquals(2, Task::where('related_customer_id', $customer->id)->count(),
            'a new task is created once the prior review is resolved');
        $this->assertEquals(2, CreditThresholdEvent::count(), 'every crossing still recorded');
    }
}
