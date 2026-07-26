<?php

namespace Tests\Feature\Tasks\Billing;

use App\Models\Iam\Personnel\User;
use App\Services\Billing\PrimaryBillingAdminResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Primary Billing Admin designation — one canonical settings-row FK to an
 * active employee, exposed through PrimaryBillingAdminResolver.
 */
class PrimaryBillingAdminTest extends TestCase
{
    use RefreshDatabase;

    private const INDEX_ROUTE = 'admin.tasks.billing.settings.index';
    private const SAVE_ROUTE  = 'admin.tasks.billing.settings.save';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'first_name' => 'Settings', 'last_name' => 'Admin',
            'email' => 'settings-admin@test.local', 'status' => 'Active',
        ]);
        $this->actingAs($this->admin);
    }

    private function activeEmployee(string $first = 'Active'): User
    {
        return User::create([
            'first_name' => $first, 'last_name' => 'Employee',
            'email' => strtolower($first) . '-emp@test.local', 'status' => 'Active',
        ]);
    }

    private function inactiveEmployee(): User
    {
        return User::create([
            'first_name' => 'Gone', 'last_name' => 'Employee',
            'email' => 'gone-emp@test.local', 'status' => 'Inactive',
        ]);
    }

    public function test_unset_designation_resolves_to_null_safely(): void
    {
        $this->assertNull(PrimaryBillingAdminResolver::designatedId());
        $this->assertNull(app(PrimaryBillingAdminResolver::class)->primary());
    }

    public function test_settings_page_renders(): void
    {
        $this->get(route(self::INDEX_ROUTE))
            ->assertOk()
            ->assertSee('Primary Billing Admin');
    }

    public function test_authorized_user_can_designate_an_active_employee(): void
    {
        $employee = $this->activeEmployee('Billy');

        $this->post(route(self::SAVE_ROUTE), ['primary_billing_admin_id' => $employee->id])
            ->assertRedirect(route(self::INDEX_ROUTE));

        $this->assertSame($employee->id, PrimaryBillingAdminResolver::designatedId());
        $this->assertTrue(app(PrimaryBillingAdminResolver::class)->primary()->is($employee));
    }

    public function test_designation_persists_and_is_shown(): void
    {
        $employee = $this->activeEmployee('Persisted');
        $this->post(route(self::SAVE_ROUTE), ['primary_billing_admin_id' => $employee->id]);

        $this->get(route(self::INDEX_ROUTE))
            ->assertOk()
            ->assertSee('Persisted Employee');
    }

    public function test_inactive_employee_is_rejected(): void
    {
        $inactive = $this->inactiveEmployee();

        $this->post(route(self::SAVE_ROUTE), ['primary_billing_admin_id' => $inactive->id])
            ->assertSessionHasErrors('primary_billing_admin_id');

        $this->assertNull(PrimaryBillingAdminResolver::designatedId());
    }

    public function test_nonexistent_employee_is_rejected(): void
    {
        $this->post(route(self::SAVE_ROUTE), ['primary_billing_admin_id' => 999999])
            ->assertSessionHasErrors('primary_billing_admin_id');

        $this->assertNull(PrimaryBillingAdminResolver::designatedId());
    }

    public function test_designation_can_be_cleared(): void
    {
        $employee = $this->activeEmployee('Clearme');
        $this->post(route(self::SAVE_ROUTE), ['primary_billing_admin_id' => $employee->id]);
        $this->assertSame($employee->id, PrimaryBillingAdminResolver::designatedId());

        $this->post(route(self::SAVE_ROUTE), ['primary_billing_admin_id' => '']);
        $this->assertNull(PrimaryBillingAdminResolver::designatedId());
    }

    public function test_resolver_returns_null_when_designee_becomes_inactive(): void
    {
        $employee = $this->activeEmployee('Wasactive');
        $this->post(route(self::SAVE_ROUTE), ['primary_billing_admin_id' => $employee->id]);

        $employee->update(['status' => 'Inactive']);

        // The raw id is still stored, but primary() re-filters through active().
        $this->assertSame($employee->id, PrimaryBillingAdminResolver::designatedId());
        $this->assertNull(app(PrimaryBillingAdminResolver::class)->primary());
    }

    public function test_guest_cannot_change_the_designation(): void
    {
        $employee = $this->activeEmployee('Guarded');
        Auth::logout();

        $this->post(route(self::SAVE_ROUTE), ['primary_billing_admin_id' => $employee->id])
            ->assertRedirect();

        $this->assertNull(PrimaryBillingAdminResolver::designatedId());
    }
}
