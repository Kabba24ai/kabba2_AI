<?php

namespace Tests\Feature\WaitList;

use App\Enums\WaitList\WaitListAlertStatus;
use App\Enums\WaitList\WaitListCommunicationType;
use App\Enums\WaitList\WaitListMatchType;
use App\Enums\WaitList\WaitListReason;
use App\Enums\WaitList\WaitListRequestType;
use App\Enums\WaitList\WaitListStatus;
use App\Enums\WaitList\WaitListStorePreference;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\ProductManagement\ProductCategory;
use App\Models\WaitList\EquipmentWaitList;
use App\Models\WaitList\EquipmentWaitListAlert;
use App\Services\Equipment\EquipmentStatusService;
use App\Services\FirebaseService;
use App\Services\WaitList\WaitListMatcher;
use App\Services\WaitList\WaitListPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EquipmentWaitListTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Customer $customer;
    private ProductCategory $category;
    private Equipment $excavator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'unique_id' => 'test-admin', 'employee_code' => '01',
            'first_name' => 'Admin', 'last_name' => 'User',
            'email' => 'admin@test.local', 'status' => 'Active',
        ]);
        $this->actingAs($this->admin);

        $this->customer = Customer::create([
            'first_name' => 'Mike', 'last_name' => 'Harrison',
            'company_name' => 'Harrison Grading LLC',
            'phone' => '555-0100', 'email' => 'mike@harrisongrading.test',
            'status' => 'Active',
        ]);

        $this->category = ProductCategory::create(['title' => 'Excavators', 'status' => 'Published', 'sort_order' => 1]);

        $this->excavator = Equipment::create([
            'unique_id' => 'test-exc', 'equipment_name' => 'Mini Excavator 3.5T',
            'equipment_id' => 'EX-100', 'brand' => 'Test',
            'product_category_id' => $this->category->id,
            'current_status' => 'rented',
        ]);

        // Firebase is never hit in tests — pushes are recorded via the mock.
        $this->instance(FirebaseService::class, $this->createMock(FirebaseService::class));
    }

    private function makeWaitList(array $overrides = [], array $equipmentIds = []): EquipmentWaitList
    {
        $waitList = EquipmentWaitList::create(array_merge([
            'customer_id'      => $this->customer->id,
            'customer_name'    => 'Mike Harrison',
            'company_name'     => 'Harrison Grading LLC',
            'phone'            => '555-0100',
            'email'            => 'mike@harrisongrading.test',
            'request_type'     => WaitListRequestType::Category->value,
            'product_category_id' => $this->category->id,
            'store_preference' => WaitListStorePreference::AnyStore->value,
            'reason'           => 'Job starting as soon as a unit frees up',
            'created_by'       => $this->admin->id,
        ], $overrides));

        foreach ($equipmentIds as $id) {
            $waitList->items()->create(['equipment_id' => $id]);
        }

        return $waitList;
    }

    private function completeReturn(Equipment $equipment, bool $damaged = false): void
    {
        $damaged
            ? EquipmentStatusService::markReturnedDamaged($equipment, 1, 1, null, $this->admin->id)
            : EquipmentStatusService::markReturnedToMaintenance($equipment, 1, 1, null, $this->admin->id);
    }

    // ── Creation ───────────────────────────────────────────────────

    public function test_wait_list_creation_pulls_crm_snapshot(): void
    {
        $this->post(route('admin.wait-list.store'), [
            'customer_id'         => $this->customer->id,
            'request_type'        => WaitListRequestType::Category->value,
            'product_category_id' => $this->category->id,
            'store_preference'    => WaitListStorePreference::AnyStore->value,
            'reason'              => WaitListReason::EquipmentFullyBooked->value,
            'priority_override'   => 2,
        ])->assertRedirect();

        $this->assertDatabaseHas('equipment_wait_lists', [
            'customer_id'   => $this->customer->id,
            'customer_name' => 'Mike Harrison',
            'company_name'  => 'Harrison Grading LLC',
            'phone'         => '555-0100',
            'email'         => 'mike@harrisongrading.test',
            'status'        => 'active',
            // Reason arrives as a code, is stored as its readable label
            'reason'        => 'Equipment fully booked',
            'priority_override' => 2,
            'created_by'    => $this->admin->id,
        ]);
    }

    public function test_reason_must_come_from_pre_canned_dropdown(): void
    {
        $payload = fn (string $reason) => [
            'customer_id'         => $this->customer->id,
            'request_type'        => WaitListRequestType::Category->value,
            'product_category_id' => $this->category->id,
            'store_preference'    => WaitListStorePreference::AnyStore->value,
            'reason'              => $reason,
        ];

        // Sentence-style free text is no longer accepted
        $this->post(route('admin.wait-list.store'), $payload('Needs an excavator ASAP'))
            ->assertSessionHasErrors('reason');

        $this->assertDatabaseCount('equipment_wait_lists', 0);

        // Every dropdown code is accepted and stored as its label
        foreach (WaitListReason::cases() as $reason) {
            $this->post(route('admin.wait-list.store'), $payload($reason->value))
                ->assertSessionHasNoErrors();

            $this->assertDatabaseHas('equipment_wait_lists', ['reason' => $reason->label()]);
        }
    }

    public function test_priority_override_allows_only_top_three_positions(): void
    {
        $payload = fn ($priority) => [
            'customer_id'         => $this->customer->id,
            'request_type'        => WaitListRequestType::Category->value,
            'product_category_id' => $this->category->id,
            'store_preference'    => WaitListStorePreference::AnyStore->value,
            'reason'              => WaitListReason::UnitDamaged->value,
            'priority_override'   => $priority,
        ];

        // Old free-entry values are rejected — only #1–#3 exist in the dropdown
        foreach ([0, 4, 5, 100, 'high'] as $invalid) {
            $this->post(route('admin.wait-list.store'), $payload($invalid))
                ->assertSessionHasErrors('priority_override');
        }
        $this->assertDatabaseCount('equipment_wait_lists', 0);

        foreach ([1, 2, 3] as $position) {
            $this->post(route('admin.wait-list.store'), $payload($position))
                ->assertSessionHasNoErrors();

            $this->assertDatabaseHas('equipment_wait_lists', ['priority_override' => $position]);
        }

        // "No priority override" submits blank and stores null
        $this->post(route('admin.wait-list.store'), $payload(''))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('equipment_wait_lists', ['priority_override' => null]);
    }

    public function test_optional_equipment_choices_may_be_left_blank(): void
    {
        $payload = fn (array $equipmentIds) => [
            'customer_id'      => $this->customer->id,
            'request_type'     => WaitListRequestType::SpecificEquipment->value,
            'equipment_ids'    => $equipmentIds,
            'store_preference' => WaitListStorePreference::AnyStore->value,
            'reason'           => WaitListReason::RequestedSpecificUnit->value,
        ];

        // Choice #1 picked, optional Choice #2/#3 submit as blanks — the blanks
        // must be dropped, not rejected as "must be an integer"
        $this->post(route('admin.wait-list.store'), $payload([(string) $this->excavator->id, '', '']))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $waitList = EquipmentWaitList::latest('id')->firstOrFail();
        $this->assertSame(
            [$this->excavator->id],
            $waitList->items()->pluck('equipment_id')->all()
        );

        // All three blank still fails the Choice #1 requirement
        $this->post(route('admin.wait-list.store'), $payload(['', '', '']))
            ->assertSessionHasErrors('equipment_ids');
    }

    public function test_create_form_sections_labels_and_category_filter_contract(): void
    {
        $skidCategory = ProductCategory::create(['title' => 'Skid Steers', 'status' => 'Published', 'sort_order' => 2]);
        $skid = Equipment::create([
            'unique_id' => 'test-skid', 'equipment_name' => 'Skid Steer S70',
            'equipment_id' => 'SS-210', 'brand' => 'Test',
            'product_category_id' => $skidCategory->id,
        ]);

        $response = $this->get(route('admin.wait-list.create'))->assertOk()
            ->assertSee('Equipment Request')
            ->assertSee('If Category Wait List')
            ->assertSee('If Specific Equipment Wait List')
            ->assertSee('Equipment Category Filter')
            ->assertSee('Choice #1')
            ->assertSee('Choice #2 (Optional)')
            ->assertSee('Choice #3 (Optional)')
            ->assertSee('No Priority Override')
            ->assertSee('Move to Position #1')
            ->assertSee('Move to Position #3');

        $content = $response->getContent();

        // All three equipment choice dropdowns ship disabled — they unlock
        // client-side only after an Equipment Category Filter is selected
        $this->assertSame(3, preg_match_all('/name="equipment_ids\[\]"[^>]*\bdisabled\b/', $content));

        // The embedded filter data maps every unit to its category, which is what
        // lets the dropdowns show only that category's equipment and drop
        // incompatible selections when the filter changes (@json hex-escapes quotes)
        $this->assertStringContainsString(sprintf(
            '"id":%d,"label":"Skid Steer S70 (SS-210)","category_id":%d',
            $skid->id, $skidCategory->id
        ), $content);
        $this->assertStringContainsString(sprintf(
            '"id":%d,"label":"Mini Excavator 3.5T (EX-100)","category_id":%d',
            $this->excavator->id, $this->category->id
        ), $content);
    }

    public function test_urgency_ordering_matches_dropdown_positions(): void
    {
        // Created oldest-first WITHOUT overrides, then given positions out of order
        $second = $this->makeWaitList(['priority_override' => 2]);
        $none   = $this->makeWaitList();
        $first  = $this->makeWaitList(['priority_override' => 1]);

        // "Make this #1" must list first, then #2, then unranked records
        $this->assertSame(
            [$first->id, $second->id, $none->id],
            EquipmentWaitList::byUrgency()->pluck('id')->all()
        );
    }

    public function test_specific_equipment_limited_to_three_and_structured_only(): void
    {
        $extra = collect(range(1, 4))->map(fn ($i) => Equipment::create([
            'unique_id' => "test-eq-$i", 'equipment_name' => "Unit $i", 'equipment_id' => "U-$i", 'brand' => 'Test',
        ]));

        // Four units → rejected
        $this->post(route('admin.wait-list.store'), [
            'customer_id'      => $this->customer->id,
            'request_type'     => WaitListRequestType::SpecificEquipment->value,
            'equipment_ids'    => $extra->pluck('id')->all(),
            'store_preference' => WaitListStorePreference::AnyStore->value,
            'reason'           => WaitListReason::EquipmentFullyBooked->value,
        ])->assertSessionHasErrors('equipment_ids');

        // Nonexistent CRM customer / equipment → rejected (no free-form entry)
        $this->post(route('admin.wait-list.store'), [
            'customer_id'      => 999999,
            'request_type'     => WaitListRequestType::SpecificEquipment->value,
            'equipment_ids'    => [999999],
            'store_preference' => WaitListStorePreference::AnyStore->value,
            'reason'           => WaitListReason::EquipmentFullyBooked->value,
        ])->assertSessionHasErrors(['customer_id', 'equipment_ids.0']);

        // Three units → accepted
        $this->post(route('admin.wait-list.store'), [
            'customer_id'      => $this->customer->id,
            'request_type'     => WaitListRequestType::SpecificEquipment->value,
            'equipment_ids'    => $extra->take(3)->pluck('id')->all(),
            'store_preference' => WaitListStorePreference::SpecificStore->value,
            'store_id'         => null,
            'reason'           => WaitListReason::RequestedSpecificUnit->value,
        ])->assertSessionHasErrors('store_id'); // specific store requires a store

        $this->post(route('admin.wait-list.store'), [
            'customer_id'      => $this->customer->id,
            'request_type'     => WaitListRequestType::SpecificEquipment->value,
            'equipment_ids'    => $extra->take(3)->pluck('id')->all(),
            'store_preference' => WaitListStorePreference::AnyStore->value,
            'reason'           => WaitListReason::RequestedSpecificUnit->value,
        ])->assertRedirect();

        $this->assertDatabaseCount('equipment_wait_list_items', 3);
    }

    // ── Index dashboard ────────────────────────────────────────────

    public function test_index_stat_cards_compute_from_existing_records(): void
    {
        // Two waiting (one 12 days old), one converted this month, one cancelled
        $this->makeWaitList()->forceFill(['created_at' => now()->subDays(12)])->save();
        $this->makeWaitList();
        $this->makeWaitList(['status' => WaitListStatus::Converted->value, 'converted_at' => now()->subDays(3)]);
        $this->makeWaitList(['status' => WaitListStatus::Cancelled->value]);

        // 1 conversion of 4 records created in the 30-day window = 25%
        $this->get(route('admin.wait-list.index'))
            ->assertOk()
            ->assertSee('Waiting Now')
            ->assertSee('Longest: 12 days')
            ->assertSee('Converted (30 Days)')
            ->assertSee('25% conversion rate')
            ->assertSee('Top Categories')
            ->assertSee('Excavators');
    }

    public function test_index_filters_narrow_results(): void
    {
        $fresh = $this->makeWaitList(['customer_name' => 'Fresh Record']);
        $old   = $this->makeWaitList(['customer_name' => 'Old Record']);
        $old->forceFill(['created_at' => now()->subDays(10)])->save();
        $top   = $this->makeWaitList(['customer_name' => 'Top Priority', 'priority_override' => 1]);

        // Age buckets
        $this->get(route('admin.wait-list.index', ['age' => '0-1']))
            ->assertOk()->assertSee('Fresh Record')->assertDontSee('Old Record');
        $this->get(route('admin.wait-list.index', ['age' => '8+']))
            ->assertOk()->assertSee('Old Record')->assertDontSee('Fresh Record');

        // Priority
        $this->get(route('admin.wait-list.index', ['priority' => 1]))
            ->assertOk()->assertSee('Top Priority')->assertDontSee('Fresh Record');
        $this->get(route('admin.wait-list.index', ['priority' => 'none']))
            ->assertOk()->assertSee('Fresh Record')->assertDontSee('Top Priority');

        // Multi-status: cancelled + converted together via status[]
        $fresh->update(['status' => WaitListStatus::Cancelled->value]);
        $old->update(['status' => WaitListStatus::Converted->value]);
        $this->get(route('admin.wait-list.index', ['status' => ['cancelled', 'converted']]))
            ->assertOk()->assertSee('Fresh Record')->assertSee('Old Record')->assertDontSee('Top Priority');
    }

    public function test_specific_equipment_card_falls_back_to_first_choice_product_image(): void
    {
        $media = \App\Models\Global\Media::create([
            'asset_type'  => 'Public Asset',
            'folder_name' => 'products',
            'file_name'   => 'skid-steer-hero.jpg',
        ]);
        $product = \App\Models\ProductManagement\Product::create([
            'product_name' => 'Skid Steer Rental',
            'slug'         => 'skid-steer-rental-' . uniqid(),
            'product_type' => 'Rental',
            'media_id'     => $media->id,
        ]);
        $this->excavator->update(['assigned_product_id' => $product->id]);

        // Equipment has no photos of its own → first choice's product image renders
        $this->makeWaitList(
            ['request_type' => WaitListRequestType::SpecificEquipment->value, 'product_category_id' => null],
            [$this->excavator->id],
        );

        $this->get(route('admin.wait-list.index'))
            ->assertOk()
            ->assertSee('skid-steer-hero.jpg');
    }

    public function test_index_queue_positions_follow_urgency_and_views_share_records(): void
    {
        $oldest = $this->makeWaitList(['customer_name' => 'Oldest NoOverride']);
        $oldest->forceFill(['created_at' => now()->subDays(5)])->save();
        $ranked = $this->makeWaitList(['customer_name' => 'Ranked First', 'priority_override' => 1]);

        // Grid (default): override #1 renders before the older unranked record
        $this->get(route('admin.wait-list.index'))
            ->assertOk()
            ->assertSeeInOrder(['Ranked First', 'Oldest NoOverride']);

        // List view shows the same records in the same order
        $this->get(route('admin.wait-list.index', ['view' => 'list']))
            ->assertOk()
            ->assertSeeInOrder(['Ranked First', 'Oldest NoOverride'])
            ->assertSee('Showing 1 to 2 of 2 results');
    }

    // ── Matching + alerts ──────────────────────────────────────────

    public function test_exact_equipment_match_fires_alert_on_return(): void
    {
        $waitList = $this->makeWaitList(
            ['request_type' => WaitListRequestType::SpecificEquipment->value, 'product_category_id' => null],
            [$this->excavator->id],
        );

        $this->completeReturn($this->excavator);

        $alert = EquipmentWaitListAlert::first();
        $this->assertNotNull($alert);
        $this->assertSame($waitList->id, $alert->equipment_wait_list_id);
        $this->assertSame($this->excavator->id, $alert->equipment_id);
        $this->assertSame(WaitListMatchType::ExactEquipment, $alert->match_type);
        $this->assertSame('maintenance', $alert->equipment_status_at_match);
    }

    public function test_category_match_fires_alert_and_regardless_of_damaged_status(): void
    {
        $waitList = $this->makeWaitList(); // category wait list

        // Damaged return still fires — managers decide what happens next
        $this->completeReturn($this->excavator, damaged: true);

        $alert = EquipmentWaitListAlert::first();
        $this->assertNotNull($alert);
        $this->assertSame(WaitListMatchType::Category, $alert->match_type);
        $this->assertSame($this->category->id, $alert->matched_category_id);
        $this->assertSame('damaged', $alert->equipment_status_at_match);
    }

    public function test_no_duplicate_open_alerts_and_closed_wait_lists_ignored(): void
    {
        $this->makeWaitList();

        $this->completeReturn($this->excavator);
        $this->completeReturn($this->excavator);
        $this->assertDatabaseCount('equipment_wait_list_alerts', 1);

        // Converted/cancelled wait lists never match
        EquipmentWaitList::first()->update(['status' => WaitListStatus::Cancelled]);
        EquipmentWaitListAlert::first()->update(['status' => WaitListAlertStatus::Dismissed]);

        $this->completeReturn($this->excavator);
        $this->assertDatabaseCount('equipment_wait_list_alerts', 1);
    }

    // ── Mobile API ─────────────────────────────────────────────────

    public function test_mobile_api_exposes_alert_payload_and_acknowledge(): void
    {
        $this->makeWaitList(['internal_notes' => 'VIP customer'], []);
        $this->completeReturn($this->excavator);
        $alert = EquipmentWaitListAlert::first();

        $response = $this->getJson('http://api.kabba.local/api/admin/v1/wait-list/alerts')->assertOk();
        $payload  = $response->json('alerts.0');

        $this->assertSame($alert->id, $payload['alert_id']);
        $this->assertSame($alert->equipment_wait_list_id, $payload['wait_list_id']);
        $this->assertSame('Mike Harrison', $payload['customer']['name']);
        $this->assertSame('Harrison Grading LLC', $payload['customer']['company']);
        $this->assertSame('555-0100', $payload['customer']['phone']);
        $this->assertSame('category', $payload['match']['type']);
        $this->assertSame('Mini Excavator 3.5T', $payload['match']['equipment_name']);
        $this->assertSame('Excavators', $payload['match']['matched_category']);
        $this->assertSame('any_store', $payload['store_preference']['preference']);
        $this->assertSame(0, $payload['wait_list_age_days']);
        $this->assertSame('VIP customer', $payload['internal_notes']);
        $this->assertSame('maintenance', $payload['match']['equipment_status_at_match']);

        // Acknowledge from mobile — user + timestamp recorded
        $this->postJson("http://api.kabba.local/api/admin/v1/wait-list/alerts/{$alert->id}/acknowledge")
            ->assertOk()->assertJson(['success' => true]);

        $fresh = $alert->fresh();
        $this->assertSame(WaitListAlertStatus::Acknowledged, $fresh->status);
        $this->assertSame($this->admin->id, $fresh->acknowledged_by);
        $this->assertNotNull($fresh->acknowledged_at);

        // Acknowledged alerts drop off the open feed but stay in history
        $this->assertCount(0, $this->getJson('http://api.kabba.local/api/admin/v1/wait-list/alerts')->json('alerts'));
        $this->assertCount(1, $this->getJson('http://api.kabba.local/api/admin/v1/wait-list/alerts?status=all')->json('alerts'));
    }

    // ── Admin actions ──────────────────────────────────────────────

    public function test_admin_acknowledge_updates_wait_list_status(): void
    {
        $waitList = $this->makeWaitList();
        $this->completeReturn($this->excavator);
        $alert = EquipmentWaitListAlert::first();

        $this->post(route('admin.wait-list.alerts.acknowledge', $alert))->assertRedirect();

        $this->assertSame(WaitListAlertStatus::Acknowledged, $alert->fresh()->status);
        $this->assertSame(WaitListStatus::Acknowledged, $waitList->fresh()->status);

        // Alert remains viewable in history view
        $this->get(route('admin.wait-list.alerts', ['view' => 'all']))
            ->assertOk()->assertSee('Harrison Grading');
    }

    public function test_communication_log_records_employee_type_and_note(): void
    {
        $waitList = $this->makeWaitList();

        $this->post(route('admin.wait-list.communications.store', $waitList), [
            'type' => WaitListCommunicationType::LeftVoicemail->value,
            'note' => 'Left message about the returned mini excavator.',
        ])->assertRedirect();

        $this->assertDatabaseHas('equipment_wait_list_communications', [
            'equipment_wait_list_id' => $waitList->id,
            'user_id'                => $this->admin->id,
            'type'                   => 'left_voicemail',
            'note'                   => 'Left message about the returned mini excavator.',
        ]);
    }

    public function test_conversion_links_order_and_marks_converted(): void
    {
        $orderId = DB::table('orders')->insertGetId([
            'unique_id' => 'test-ord', 'order_number' => '#7100',
            'order_date' => now()->toDateString(), 'customer_name' => 'Mike Harrison',
            'customer_id' => $this->customer->id,
        ]);
        $waitList = $this->makeWaitList();

        $this->post(route('admin.wait-list.convert', $waitList), [
            'converted_order_id' => $orderId,
        ])->assertRedirect();

        $fresh = $waitList->fresh();
        $this->assertSame(WaitListStatus::Converted, $fresh->status);
        $this->assertSame($orderId, $fresh->converted_order_id);
        $this->assertNotNull($fresh->converted_at);
    }

    public function test_cancellation_is_manual_and_stays_searchable(): void
    {
        $waitList = $this->makeWaitList();

        $this->post(route('admin.wait-list.cancel', $waitList))->assertRedirect();

        $fresh = $waitList->fresh();
        $this->assertSame(WaitListStatus::Cancelled, $fresh->status);
        $this->assertSame($this->admin->id, $fresh->cancelled_by);

        // Still searchable in the cancelled view
        $this->get(route('admin.wait-list.index', ['status' => 'cancelled', 'search' => 'Harrison']))
            ->assertOk()->assertSee('Mike Harrison');
    }

    // ── Banner ─────────────────────────────────────────────────────

    public function test_banner_visible_for_exact_and_category_demand(): void
    {
        // No demand → no banner
        $this->get(route('admin.maintenance-management.equipment.edit', $this->excavator->unique_id))
            ->assertOk()->assertDontSee('WAIT LIST: Active customer demand');

        // Category demand → banner on the equipment page (both directions)
        $this->makeWaitList();
        $this->get(route('admin.maintenance-management.equipment.edit', $this->excavator->unique_id))
            ->assertOk()->assertSee('WAIT LIST: Active customer demand');

        // Cancelled demand → banner disappears
        EquipmentWaitList::first()->update(['status' => WaitListStatus::Cancelled]);
        $this->get(route('admin.maintenance-management.equipment.edit', $this->excavator->unique_id))
            ->assertOk()->assertDontSee('WAIT LIST: Active customer demand');

        // Exact-equipment demand → banner again
        $this->makeWaitList(
            ['request_type' => WaitListRequestType::SpecificEquipment->value, 'product_category_id' => null],
            [$this->excavator->id],
        );
        $this->get(route('admin.maintenance-management.equipment.edit', $this->excavator->unique_id))
            ->assertOk()->assertSee('WAIT LIST: Active customer demand');
    }

    // ── Navigation ─────────────────────────────────────────────────

    public function test_sidebar_places_wait_list_under_orders_with_active_highlight(): void
    {
        $html = $this->get(route('admin.wait-list.index'))->assertOk()->getContent();

        // Entry lives inside the Orders dropdown (after Equipment Inventory,
        // before the Products section), exactly once
        $this->assertSame(1, substr_count($html, '> Wait List'));
        $ordersPos   = strpos($html, 'Equipment Inventory');
        $waitListPos = strpos($html, '> Wait List');
        $productsPos = strpos($html, 'Product Categories');
        $this->assertTrue($ordersPos < $waitListPos && $waitListPos < $productsPos,
            'Wait List must be the last item in the Orders dropdown');

        // Being on a wait-list page opens + highlights the Orders group
        $this->assertStringContainsString('x-data="{ open: true }"', $html);

        // The Wait List anchor itself carries the active class (inspect the
        // markup immediately preceding the label, within its own <a> tag)
        $anchorStart = strrpos(substr($html, 0, $waitListPos), '<a ');
        $anchor      = substr($html, $anchorStart, $waitListPos - $anchorStart);
        $this->assertStringContainsString('menu-dropdown-item-active', $anchor);
    }

    // ── Push timing ────────────────────────────────────────────────

    public function test_after_hours_alert_defers_push_until_business_opening(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-05 20:00:00')); // Sunday night — closed

        $this->makeWaitList();
        $this->completeReturn($this->excavator);

        $alert = EquipmentWaitListAlert::first();
        $this->assertNull($alert->first_push_sent_at);
        // Deferred to Monday 07:00 opening
        $this->assertSame('2026-07-06 07:00:00', $alert->push_deferred_until->format('Y-m-d H:i:s'));

        // Still closed → scheduler does nothing
        app(WaitListPushService::class)->processPending();
        $this->assertNull($alert->fresh()->first_push_sent_at);

        // Monday 07:05 — open → deferred push delivered
        Carbon::setTestNow(Carbon::parse('2026-07-06 07:05:00'));
        app(WaitListPushService::class)->processPending();
        $this->assertNotNull($alert->fresh()->first_push_sent_at);

        Carbon::setTestNow();
    }

    public function test_second_push_after_thirty_unacknowledged_minutes(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-06 09:00:00')); // Monday morning — open

        $this->makeWaitList();
        $this->completeReturn($this->excavator);

        $alert = EquipmentWaitListAlert::first();
        $this->assertNotNull($alert->first_push_sent_at); // immediate during business hours
        $this->assertNull($alert->second_push_sent_at);

        // 20 minutes later — not yet
        Carbon::setTestNow(Carbon::parse('2026-07-06 09:20:00'));
        app(WaitListPushService::class)->processPending();
        $this->assertNull($alert->fresh()->second_push_sent_at);

        // 31 minutes, still unacknowledged — second push fires
        Carbon::setTestNow(Carbon::parse('2026-07-06 09:31:00'));
        app(WaitListPushService::class)->processPending();
        $this->assertNotNull($alert->fresh()->second_push_sent_at);

        // Acknowledged alerts never get further pushes
        $ackAlert = EquipmentWaitListAlert::create([
            'equipment_wait_list_id' => $alert->equipment_wait_list_id,
            'equipment_id'           => $this->excavator->id,
            'match_type'             => WaitListMatchType::Category->value,
            'first_push_sent_at'     => now()->subHour(),
        ]);
        $ackAlert->acknowledge($this->admin->id);
        app(WaitListPushService::class)->processPending();
        $this->assertNull($ackAlert->fresh()->second_push_sent_at);

        Carbon::setTestNow();
    }

    // ── Guardrail: matcher failures never break a return ───────────

    public function test_matcher_errors_do_not_break_returns(): void
    {
        DB::statement('DROP TABLE equipment_wait_list_alerts'); // simulate module failure

        $this->completeReturn($this->excavator); // must not throw

        $this->assertSame('maintenance', $this->excavator->fresh()->current_status->value);
    }
}
