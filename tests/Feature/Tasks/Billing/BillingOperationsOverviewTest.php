<?php

namespace Tests\Feature\Tasks\Billing;

use App\Models\Configurations\Setting;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAccount;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\BillingCharge;
use App\Models\Service\ServiceTicket;
use App\Models\Service\ServiceTicketSettlement;
use App\Services\Alerts\ChargeAlertQueue;
use App\Services\Billing\BillingOperationsSummary;
use App\Services\Billing\PrimaryBillingAdminResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Billing Operations Overview (/tasks/billing) — the entry point whose counts
 * come from the shared BillingOperationsSummary, the SAME dataset the Fuel and
 * Damage workspaces consume, so nothing can drift.
 */
class BillingOperationsOverviewTest extends TestCase
{
    use RefreshDatabase;

    private const OVERVIEW = 'admin.tasks.billing.index';
    private const DAMAGE   = 'admin.tasks.billing.damage-charges.index';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'first_name' => 'Over', 'last_name' => 'View',
            'email' => 'overview-admin@test.local', 'status' => 'Active',
        ]);
        $this->actingAs($this->admin);
    }

    private function customer(string $first): Customer
    {
        return Customer::create([
            'first_name' => $first, 'last_name' => 'Cust',
            'email' => Str::lower($first) . '-' . Str::random(4) . '@test.local', 'status' => 'Active',
        ]);
    }

    private function crmFuelCharge(Customer $c): void
    {
        $a = new CustomerAccount();
        $a->customer_id = $c->id; $a->amount = 40; $a->reason = 'Fuel Charge'; $a->type = 'charge';
        $a->date = now(); $a->sales_tax = 0; $a->sales_tax_type = 'free'; $a->fuel_alert_status = 'pending';
        $a->save();
    }

    private function crmDamageCharge(Customer $c, string $status = 'pending', float $amount = 120.0): void
    {
        $a = new CustomerAccount();
        $a->customer_id = $c->id; $a->amount = $amount; $a->reason = 'Damages'; $a->type = 'charge';
        $a->date = now(); $a->sales_tax = 0; $a->sales_tax_type = 'free'; $a->damage_alert_status = $status;
        $a->save();
    }

    private function serviceTicketDamageCharge(Customer $c): BillingCharge
    {
        $ticket = ServiceTicket::create([
            'ticket_number' => 'ST-' . Str::random(6),
            'service_type' => 'customer_damage_repair', 'service_location' => 'in_shop', 'priority' => 'normal',
            'repair_status' => 'open', 'financial_responsibility' => 'customer_pay',
            'financial_status' => 'ready_to_bill', 'opened_at' => now(), 'customer_id' => $c->id,
        ]);
        $charge = BillingCharge::create([
            'billing_charge_type' => 'service_ticket', 'status' => 'pending',
            'customer_id' => $c->id, 'amount' => 200, 'tax_amount' => 0, 'tax_type' => 'free',
        ]);
        ServiceTicketSettlement::create([
            'service_ticket_id' => $ticket->id, 'customer_id' => $c->id,
            'billing_charge_id' => $charge->id, 'status' => 'created', 'package' => [],
        ]);
        return $charge;
    }

    private function summary(): BillingOperationsSummary
    {
        return app(BillingOperationsSummary::class);
    }

    // ── Access ──────────────────────────────────────────────────────────────

    public function test_authorized_user_can_access_the_overview(): void
    {
        $this->get(route(self::OVERVIEW))
            ->assertOk()
            ->assertSee('Billing Operations');
    }

    public function test_route_does_not_collide_with_the_task_wildcard(): void
    {
        // /tasks/billing must resolve to the Overview, never to Task show({task}).
        $this->get(route(self::OVERVIEW))
            ->assertOk()
            ->assertSee('What billing work needs attention right now.');
    }

    public function test_guest_is_blocked(): void
    {
        Auth::logout();
        $this->get(route(self::OVERVIEW))->assertRedirect();
    }

    // ── Summary accuracy / shared dataset ──────────────────────────────────

    public function test_fuel_open_count_matches_the_fuel_canonical_dataset(): void
    {
        $this->crmFuelCharge($this->customer('Fa'));
        $this->crmFuelCharge($this->customer('Fb'));

        $this->assertSame(2, ChargeAlertQueue::fuelAlerts()->count());
        $this->assertSame(2, $this->summary()->metrics()['fuel_open']);
    }

    public function test_damage_open_matches_the_shared_dataset_including_service_tickets(): void
    {
        $this->crmDamageCharge($this->customer('Da'));
        $this->serviceTicketDamageCharge($this->customer('Db'));

        // Overview and Damage workspace both read damageActiveAlerts() — assert
        // the metric equals that shared count, and the workspace summary shows
        // the identical number.
        $shared = $this->summary()->damageActiveAlerts()->count();
        $this->assertSame(2, $shared);
        $this->assertSame($shared, $this->summary()->metrics()['damage_open']);

        $this->get(route(self::DAMAGE))
            ->assertOk()
            ->assertSee('data-metric="outstanding">' . $shared . '<', false);
    }

    public function test_service_ticket_charge_is_counted_once(): void
    {
        $this->serviceTicketDamageCharge($this->customer('Once'));

        // A single qualifying charge with its ticket + settlement must not
        // fan out into multiple rows.
        $this->assertSame(1, $this->summary()->damageActiveAlerts()->count());
        $this->assertSame(1, $this->summary()->metrics()['damage_open']);
    }

    public function test_needs_pricing_counts_only_unpriced_damage(): void
    {
        $this->crmDamageCharge($this->customer('Priced'), 'pending', 120.0);   // priced
        $this->crmDamageCharge($this->customer('Zero'), 'pending', 0.0);       // unpriced

        $m = $this->summary()->metrics();
        $this->assertSame(2, $m['damage_open']);
        $this->assertSame(1, $m['needs_pricing']);
    }

    public function test_resolved_records_are_excluded_from_open_counts(): void
    {
        $this->crmDamageCharge($this->customer('OpenOne'), 'pending');
        $this->crmDamageCharge($this->customer('DoneOne'), 'resolved');

        $this->assertSame(1, $this->summary()->metrics()['damage_open']);
    }

    // ── Primary Billing Admin ───────────────────────────────────────────────

    private function setPrimaryAdmin(?int $id): void
    {
        $s = Setting::firstOrNew([
            'setting_type' => PrimaryBillingAdminResolver::SETTING_TYPE,
            'setting_name' => PrimaryBillingAdminResolver::SETTING_NAME,
        ]);
        $s->setting_title = 'Primary Billing Admin';
        $s->value_type = 'text';
        $s->setting_value = $id;
        $s->save();
    }

    public function test_configured_primary_admin_is_displayed(): void
    {
        $emp = User::create([
            'first_name' => 'Designated', 'last_name' => 'Admin',
            'email' => 'designated@test.local', 'status' => 'Active',
        ]);
        $this->setPrimaryAdmin($emp->id);

        $this->get(route(self::OVERVIEW))
            ->assertOk()
            ->assertSee('Designated Admin');
    }

    public function test_unset_admin_shows_configuration_notice(): void
    {
        $this->get(route(self::OVERVIEW))
            ->assertOk()
            ->assertSee('No Primary Billing Admin configured')
            ->assertSee(route('admin.tasks.billing.settings.index'), false);
    }

    public function test_inactive_admin_resolves_safely(): void
    {
        $emp = User::create([
            'first_name' => 'Gone', 'last_name' => 'Admin',
            'email' => 'gone-admin@test.local', 'status' => 'Active',
        ]);
        $this->setPrimaryAdmin($emp->id);
        $emp->update(['status' => 'Inactive']);

        $this->get(route(self::OVERVIEW))
            ->assertOk()
            ->assertSee('inactive');
    }

    // ── Navigation ──────────────────────────────────────────────────────────

    public function test_navigation_exposes_overview_fuel_damage_and_settings(): void
    {
        $html = $this->get(route(self::OVERVIEW))->getContent();

        $this->assertStringContainsString(route(self::OVERVIEW), $html);
        $this->assertStringContainsString(route('admin.tasks.billing.fuel-charges.index'), $html);
        $this->assertStringContainsString(route('admin.tasks.billing.damage-charges.index'), $html);
        $this->assertStringContainsString(route('admin.tasks.billing.settings.index'), $html);
    }

    public function test_legacy_fuel_report_route_still_redirects(): void
    {
        $this->get(route('admin.reports.fuel-charge-workspace.index'))
            ->assertRedirect(route('admin.tasks.billing.fuel-charges.index'));
    }
}
