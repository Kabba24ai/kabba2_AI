<?php

namespace Tests\Feature\Dashboard;

use App\Enums\WaitList\WaitListAlertDisposition;
use App\Livewire\Dashboard\WaitListOpportunities;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;
use App\Models\WaitList\EquipmentWaitList;
use App\Models\WaitList\EquipmentWaitListAlert;
use App\Services\Equipment\EquipmentStatusService;
use App\Services\FirebaseService;
use App\Services\WaitList\WaitListStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;


/**
 * Dashboard "Wait List Opportunities" card — notification + navigation only.
 * The primary number is WaitListStats::contactOpportunities(): distinct
 * active wait-list records with an unresolved match, never raw alert rows.
 */
class WaitListOpportunitiesCardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Customer $customer;
    private ProductCategory $category;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-07-15 10:00:00'));

        $this->seed(\Database\Seeders\Configurations\SettingSeeder::class);

        $this->admin = User::create([
            'unique_id' => 'test-admin', 'employee_code' => '01',
            'first_name' => 'Admin', 'last_name' => 'User',
            'email' => 'admin@test.local', 'status' => 'Active',
        ]);
        $this->actingAs($this->admin);

        $this->customer = Customer::create([
            'first_name' => 'Mike', 'last_name' => 'Harrison',
            'company_name' => 'Harrison Grading LLC',
            'phone' => '555-0100', 'status' => 'Active',
        ]);

        $this->category = ProductCategory::create(['title' => 'Excavators', 'status' => 'Published', 'sort_order' => 1]);
        $this->product = Product::create([
            'unique_id' => Str::uuid()->toString(), 'product_name' => 'Mini Excavator 3.5T',
            'slug' => 'mini-excavator-3-5t', 'product_type' => 'Rental', 'status' => 'Published',
        ]);
        $this->product->categories()->sync([$this->category->id]);

        $this->instance(FirebaseService::class, $this->createMock(FirebaseService::class));
    }

    private function makeRecord(string $company = 'Harrison Grading LLC', array $unitIds = []): EquipmentWaitList
    {
        $waitList = EquipmentWaitList::create([
            'customer_id' => $this->customer->id, 'customer_name' => 'Mike Harrison',
            'company_name' => $company, 'request_type' => 'unified',
            'product_category_id' => $this->category->id,
            'store_preference' => 'any_store', 'created_by' => $this->admin->id,
        ]);

        // Canonical unit-level selection: exact assets by Equipment ID
        foreach ($unitIds as $id) {
            $waitList->items()->create(['equipment_id' => $id]);
        }

        return $waitList;
    }

    private function makeUnit(string $suffix): Equipment
    {
        return Equipment::create([
            'unique_id' => "unit-$suffix", 'equipment_name' => "Mini Excavator #$suffix",
            'equipment_id' => "EX-$suffix", 'brand' => 'Test',
            'product_category_id' => $this->category->id,
            'assigned_product_id' => $this->product->id,
            'current_status' => 'rented',
        ]);
    }

    private function returnUnit(Equipment $unit): void
    {
        EquipmentStatusService::markReturnedToMaintenance($unit, 1, 1, null, $this->admin->id);
    }

    // ── Layout: the Task Manager row (tests 1–3, 14, 16) ───────────────────────

    public function test_dashboard_renders_task_manager_beside_the_wait_list_card(): void
    {
        $html = $this->get(route('admin.dashboard.index'))->assertOk()->getContent();

        // Task Manager fully intact
        $this->assertStringContainsString('Task Manager Alerts', $html);
        $this->assertStringContainsString('Call Needed', $html);
        $this->assertStringContainsString('New Task', $html);

        // Two-thirds / one-third split that stacks on smaller widths
        $this->assertStringContainsString('grid-cols-1 xl:grid-cols-3', $html);
        $this->assertStringContainsString('xl:col-span-2', $html);

        // The new card and its single navigation action
        $this->assertStringContainsString('Wait List Opportunities', $html);
        $this->assertStringContainsString(route('admin.wait-list.index'), $html);

        // Every other section untouched
        $this->assertStringContainsString('Charge Alerts', $html);
        $this->assertStringContainsString('Fuel Charge Alerts', $html);
        $this->assertStringContainsString('Damage Alerts', $html);
    }

    // ── Zero state (test 4) ────────────────────────────────────────────────────

    public function test_zero_opportunities_shows_the_calm_zero_state(): void
    {
        Livewire::test(WaitListOpportunities::class)
            ->assertSet('contactOpportunities', 0)
            ->assertSee('No customers currently need contact')
            ->assertSee('Open Wait List');
    }

    // ── Opportunity counting (tests 5–7, 13) ───────────────────────────────────

    public function test_one_unresolved_alert_is_one_customer_opportunity(): void
    {
        $unit = $this->makeUnit('1');
        $this->makeRecord('Harrison Grading LLC', [$unit->id]);
        $this->returnUnit($unit);

        Livewire::test(WaitListOpportunities::class)
            ->assertSet('contactOpportunities', 1)
            ->assertSee('Harrison Grading LLC')
            ->assertSee('Match Ready');
    }

    public function test_one_record_with_three_matching_units_displays_one_opportunity(): void
    {
        $units = collect(['1', '2', '3'])->map(fn ($s) => $this->makeUnit($s));
        $this->makeRecord('Harrison Grading LLC', $units->pluck('id')->all());
        $units->each(fn ($unit) => $this->returnUnit($unit));

        $component = Livewire::test(WaitListOpportunities::class)
            ->assertSet('contactOpportunities', 1)
            ->assertSee('3 matching units');

        // The preview holds ONE distinct record, not three alert rows
        $this->assertCount(1, $component->get('preview'));
    }

    public function test_three_records_matched_by_one_unit_display_three_opportunities(): void
    {
        $unit = $this->makeUnit('1');
        foreach (range(1, 3) as $i) {
            $this->makeRecord("Customer Company $i", [$unit->id]);
        }
        $this->returnUnit($unit);

        $component = Livewire::test(WaitListOpportunities::class)
            ->assertSet('contactOpportunities', 3)
            ->assertSee('+1 more opportunity'); // preview capped at two rows

        $this->assertCount(2, $component->get('preview'));
    }

    // ── Canonical metric sources (tests 8–9) ───────────────────────────────────

    public function test_new_today_comes_from_the_canonical_stats_service(): void
    {
        $unit = $this->makeUnit('1');
        $this->makeRecord('Harrison Grading LLC', [$unit->id]);
        $this->returnUnit($unit);

        Livewire::test(WaitListOpportunities::class)
            ->assertSet('newToday', WaitListStats::newOpportunitiesToday())
            ->assertSet('newToday', 1);
    }

    public function test_awaiting_conversion_comes_from_the_canonical_stats_service(): void
    {
        $unit = $this->makeUnit('1');
        $this->makeRecord('Harrison Grading LLC', [$unit->id]);
        $this->returnUnit($unit);
        EquipmentWaitListAlert::firstOrFail()
            ->dispose(WaitListAlertDisposition::CustomerAccepted, $this->admin->id);

        Livewire::test(WaitListOpportunities::class)
            ->assertSet('awaitingConversion', WaitListStats::acceptedAwaitingConversion())
            ->assertSet('awaitingConversion', 1)
            ->assertSet('contactOpportunities', 0)
            ->assertSee('Save');
    }

    // ── Lifecycle semantics (tests 10–12) ──────────────────────────────────────

    public function test_contacted_no_answer_remains_in_customers_to_contact(): void
    {
        $unit = $this->makeUnit('1');
        $this->makeRecord('Harrison Grading LLC', [$unit->id]);
        $this->returnUnit($unit);
        EquipmentWaitListAlert::firstOrFail()
            ->dispose(WaitListAlertDisposition::ContactedNoAnswer, $this->admin->id);

        Livewire::test(WaitListOpportunities::class)
            ->assertSet('contactOpportunities', 1);
    }

    public function test_keep_waiting_removes_that_match_from_the_count(): void
    {
        $unit = $this->makeUnit('1');
        $this->makeRecord('Harrison Grading LLC', [$unit->id]);
        $this->returnUnit($unit);
        EquipmentWaitListAlert::firstOrFail()
            ->dispose(WaitListAlertDisposition::KeepWaiting, $this->admin->id);

        Livewire::test(WaitListOpportunities::class)
            ->assertSet('contactOpportunities', 0)
            ->assertSee('No customers currently need contact');
    }

    public function test_terminal_dispositions_do_not_count(): void
    {
        $unit = $this->makeUnit('1');
        $this->makeRecord('Harrison Grading LLC', [$unit->id]);
        $this->returnUnit($unit);
        EquipmentWaitListAlert::firstOrFail()
            ->dispose(WaitListAlertDisposition::CustomerNoLongerNeeds, $this->admin->id);

        Livewire::test(WaitListOpportunities::class)
            ->assertSet('contactOpportunities', 0);
    }

    // ── Authorization (canonical wait_list.view ability) ───────────────────────

    /**
     * Simulates the granular-roles configuration: replaces the Gate carrying
     * the global small-business Gate::before bypass with a fresh Gate that
     * grants nothing — exactly what a user without the wait_list.view
     * permission experiences once the bypass is removed.
     */
    private function enforceGranularPermissions(): void
    {
        $gate = new \Illuminate\Auth\Access\Gate($this->app, fn () => $this->app['auth']->user());
        $this->app->instance(\Illuminate\Contracts\Auth\Access\Gate::class, $gate);
        \Illuminate\Support\Facades\Facade::clearResolvedInstance(\Illuminate\Contracts\Auth\Access\Gate::class);
    }

    public function test_authorized_employee_sees_card_statistics_preview_and_link(): void
    {
        $unit = $this->makeUnit('1');
        $this->makeRecord('Harrison Grading LLC', [$unit->id]);
        $this->returnUnit($unit);

        // Under the current posture every signed-in employee holds the
        // ability via the global bypass — the full card renders.
        $html = $this->get(route('admin.dashboard.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Wait List Opportunities', $html);
        $this->assertStringContainsString('Contact Now', $html);
        $this->assertStringContainsString('Harrison Grading LLC', $html);
        $this->assertStringContainsString(route('admin.wait-list.index'), $html);
    }

    public function test_unauthorized_employee_gets_no_data_counts_or_navigation(): void
    {
        $unit = $this->makeUnit('1');
        $this->makeRecord('Harrison Grading LLC', [$unit->id]);
        $this->returnUnit($unit);
        $this->enforceGranularPermissions();

        $html = $this->get(route('admin.dashboard.index'))->assertOk()->getContent();

        // No card, no customer information, no counts, no wait-list URL —
        // and Task Manager takes the full row (restricted convention: omit)
        $this->assertStringNotContainsString('Wait List Opportunities', $html);
        $this->assertStringNotContainsString('Harrison Grading LLC', $html);
        $this->assertStringNotContainsString('Contact Now', $html);
        $this->assertStringNotContainsString(route('admin.wait-list.index'), $html);
        $this->assertStringNotContainsString('xl:grid-cols-3', $html);
        $this->assertStringContainsString('Task Manager Alerts', $html);
        $this->assertStringContainsString('Call Needed', $html);
    }

    public function test_direct_component_mount_by_unauthorized_employee_exposes_nothing(): void
    {
        $unit = $this->makeUnit('1');
        $this->makeRecord('Harrison Grading LLC', [$unit->id]);
        $this->returnUnit($unit);
        $this->enforceGranularPermissions();

        Livewire::test(WaitListOpportunities::class)
            ->assertSet('canAccess', false)
            ->assertSet('contactOpportunities', 0)
            ->assertSet('preview', [])
            ->assertDontSee('Harrison Grading LLC')
            ->assertDontSee('Open Wait List');
    }

    public function test_a_refresh_or_poll_cannot_bypass_authorization(): void
    {
        $unit = $this->makeUnit('1');
        $this->makeRecord('Harrison Grading LLC', [$unit->id]);
        $this->returnUnit($unit);

        // Mounted while authorized — the card holds live data
        $component = Livewire::test(WaitListOpportunities::class)
            ->assertSet('contactOpportunities', 1);

        // Access revoked between requests: the next poll re-resolves
        // authorization server-side and clears everything
        $this->enforceGranularPermissions();

        $component->call('refreshOpportunities')
            ->assertSet('canAccess', false)
            ->assertSet('contactOpportunities', 0)
            ->assertSet('preview', [])
            ->assertDontSee('Harrison Grading LLC');
    }

    public function test_client_updates_cannot_flip_the_access_flag(): void
    {
        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);

        Livewire::test(WaitListOpportunities::class)->set('canAccess', true);
    }

    public function test_authorization_comes_from_the_gate_not_route_existence(): void
    {
        $unit = $this->makeUnit('1');
        $this->makeRecord('Harrison Grading LLC', [$unit->id]);
        $this->returnUnit($unit);
        $this->enforceGranularPermissions();

        // The route is still registered — access is denied anyway, proving
        // the decision comes from the wait_list.view ability, not Route::has
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('admin.wait-list.index'));

        Livewire::test(WaitListOpportunities::class)->assertSet('canAccess', false);
    }

    public function test_wait_list_route_authorization_matches_the_card(): void
    {
        // Signed-in employee under the current posture: module accessible
        $this->get(route('admin.wait-list.index'))->assertOk();

        // Without the ability the module routes deny with the same gate
        $this->enforceGranularPermissions();
        $this->get(route('admin.wait-list.index'))->assertForbidden();

        // Guests never reach the module at all
        auth()->logout();
        $this->get(route('admin.wait-list.index'))->assertRedirect();
    }
}
