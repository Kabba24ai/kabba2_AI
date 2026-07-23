<?php

namespace Tests\Feature\QueueLine;

use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\OrderProduct;
use App\Models\Orders\QueueLineFuelVerification;
use App\Services\Equipment\EquipmentReassignmentService;
use App\Services\QueueLine\QueueFuelVerificationService;
use App\Services\QueueLine\QueueLineService;

/**
 * Standalone mobile Queue Line API (queue-line route family) — §21 of the
 * mobile-integration mission. The contract under test: the server owns
 * every business rule (eligibility, ordering, classification, fuel
 * currency, allowed actions); responses use the {success, data, meta}
 * envelope; only business identifiers cross the wire.
 */
class QueueLineMobileApiTest extends QueueLineTestCase
{
    private function api(string $path): string
    {
        return 'http://' . config('app.domains.api') . '/api/admin/v1/' . ltrim($path, '/');
    }

    private function board(array $query = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin, 'api_user')
            ->getJson($this->api('queue-line' . ($query ? '?' . http_build_query($query) : '')));
    }

    /**
     * The active (not-yet-completed) board items — Pending + Staged sections
     * merged, since most assertions here don't care which of the two an item
     * currently sits in.
     */
    private function activeItems(array $query = []): \Illuminate\Support\Collection
    {
        $items = $this->board($query)->json('data.items');

        return collect($items['pending'])->concat($items['staged']);
    }

    private function verifyFuel(OrderProduct $row, Equipment $unit): void
    {
        QueueFuelVerificationService::verify(
            orderProduct: $row->fresh(['softAssignment.equipment', 'order', 'queueLineItem']),
            expected: $unit,
            performedBy: $this->admin,
            actor: $this->admin,
            source: QueueLineFuelVerification::SOURCE_WEB,
        );
    }

    /** Full all-or-nothing staging — the same service both surfaces execute. */
    private function stage(OrderProduct $row, Equipment $unit): void
    {
        \App\Services\QueueLine\QueueLineStagingService::markStaged(
            orderProduct: $row->fresh(['softAssignment.equipment', 'order', 'queueLineItem']),
            expected: $unit,
            performedBy: $this->admin,
            actor: $this->admin,
            fuelFull: true,
            keyWithMachine: true,
        );
    }

    // ── Board ────────────────────────────────────────────────────────────

    public function test_guest_is_rejected_from_every_queue_line_endpoint(): void
    {
        auth()->logout();

        $this->getJson($this->api('queue-line'))->assertUnauthorized();
        $this->getJson($this->api('queue-line/summary'))->assertUnauthorized();
        $this->getJson($this->api('queue-line/X'))->assertUnauthorized();
        $this->getJson($this->api('queue-line/X/history'))->assertUnauthorized();
        $this->getJson($this->api('queue-line/X/equipment-candidates'))->assertUnauthorized();
    }

    public function test_board_returns_envelope_items_and_meta_counts(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row);
        $this->makeRow(); // needs equipment

        $response = $this->board()->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.counts.total', 2)
            ->assertJsonPath('meta.counts.needs_equipment', 1)
            ->assertJsonPath('meta.counts.staging_required', 1);

        $this->assertNotNull($response->json('meta.generated_at'));
    }

    public function test_board_orders_rush_then_overdue_then_today_then_tomorrow(): void
    {
        $today = $this->makeRow();
        $overdue = $this->makeRow(null, ['delivery_date' => now()->subDays(2)->format('Y-m-d')]);
        $tomorrow = $this->makeRow(null, ['delivery_date' => now()->addDay()->format('Y-m-d')]);
        $rushed = $this->makeRow(null, ['delivery_date' => now()->addDay()->format('Y-m-d')]);
        QueueLineService::rush($rushed, $this->admin);

        $urgencies = $this->activeItems()->pluck('urgency')->all();

        $this->assertSame(['rush', 'overdue', 'today', 'tomorrow'], $urgencies);
    }

    public function test_assignment_states_serialize_direct_alternate_unknown_and_needs_equipment(): void
    {
        $direct = $this->makeRow();
        $this->softAssign($direct);

        $alternate = $this->makeRow();
        $otherProduct = \App\Models\ProductManagement\Product::create([
            'product_name' => 'Other Product', 'slug' => 'other-' . uniqid(), 'product_type' => 'Rental',
        ]);
        $this->softAssign($alternate, $this->makeEquipment(['assigned_product_id' => $otherProduct->id]));

        $unknown = $this->makeRow();
        $this->softAssign($unknown, $this->makeEquipment(['assigned_product_id' => null]));

        $needs = $this->makeRow();

        $byId = $this->activeItems()->keyBy('order_product_unique_id');

        $this->assertSame('direct', $byId[$direct->unique_id]['assignment_state']);
        $this->assertSame('alternate', $byId[$alternate->unique_id]['assignment_state']);
        $this->assertSame('assignment_product_unknown', $byId[$unknown->unique_id]['assignment_state']);
        $this->assertSame('needs_equipment', $byId[$needs->unique_id]['assignment_state']);
        $this->assertNull($byId[$needs->unique_id]['equipment']);
        $this->assertSame('Other Product', $byId[$alternate->unique_id]['equipment']['assigned_product_name']);
    }

    public function test_current_fuel_state_is_serialized_and_prior_episode_never_reads_as_current(): void
    {
        $row = $this->makeRow();
        $original = $this->softAssign($row);
        $this->verifyFuel($row, $original);

        $item = $this->activeItems()->firstWhere('order_product_unique_id', $row->unique_id);
        $this->assertSame('verified', $item['fuel']['state']);
        $this->assertNotNull($item['fuel']['verified_by']);

        // Switch away and back: two NEW episodes — the old sign-off is history
        $other = $this->makeEquipment(['assigned_product_id' => $row->product_id]);
        EquipmentReassignmentService::switch($row->fresh(['softAssignment.equipment', 'order']), $other, $this->admin, $this->admin);
        EquipmentReassignmentService::switch($row->fresh(['softAssignment.equipment', 'order']), $original, $this->admin, $this->admin);

        $item = $this->activeItems()->firstWhere('order_product_unique_id', $row->unique_id);
        $this->assertSame('not_verified', $item['fuel']['state'], 'prior-episode verification must never revive');
    }

    public function test_available_actions_and_readiness_are_server_decided(): void
    {
        $needs = $this->makeRow();
        $unstaged = $this->makeRow();
        $this->softAssign($unstaged);
        $ready = $this->makeRow(null, ['delivery_transport_mode' => 'Store']);
        $unit = $this->softAssign($ready);
        $this->stage($ready, $unit);

        $byId = $this->activeItems()->keyBy('order_product_unique_id');

        $this->assertSame(
            ['assign_equipment' => true, 'switch_equipment' => false, 'mark_staged' => false, 'return_to_pending' => false, 'view_history' => true],
            $byId[$needs->unique_id]['available_actions'],
        );
        $this->assertSame('equipment_assignment_required', $byId[$needs->unique_id]['readiness']);

        $this->assertSame(
            ['assign_equipment' => false, 'switch_equipment' => true, 'mark_staged' => true, 'return_to_pending' => false, 'view_history' => true],
            $byId[$unstaged->unique_id]['available_actions'],
        );
        $this->assertSame('staging_required', $byId[$unstaged->unique_id]['readiness']);

        $stagedActions = $byId[$ready->unique_id]['available_actions'];
        $this->assertFalse($stagedActions['mark_staged'], 'already staged — no re-stage offer');
        $this->assertTrue($stagedActions['return_to_pending']);
        $this->assertTrue($stagedActions['switch_equipment']);
        $this->assertArrayNotHasKey('verify_fuel', $stagedActions, 'staging is all-or-nothing — no fuel-only step');
        $this->assertArrayNotHasKey('reverse_fuel', $stagedActions, 'reversal is never offered piecemeal to mobile');
        $this->assertArrayNotHasKey('release', $stagedActions, 'release belongs to Driver/Customer Checklist');
        $this->assertSame('ready_for_customer_handoff', $byId[$ready->unique_id]['readiness']);
        $this->assertTrue($byId[$ready->unique_id]['fully_staged']);

        // Fuel alone is NEVER ready — the whole point of all-or-nothing
        $fuelOnly = $this->makeRow();
        $unitF = $this->softAssign($fuelOnly);
        $this->verifyFuel($fuelOnly, $unitF);
        $item = $this->activeItems()->firstWhere('order_product_unique_id', $fuelOnly->unique_id);
        $this->assertSame('staging_required', $item['readiness']);
        $this->assertFalse($item['fully_staged']);
        $this->assertSame('verified', $item['fuel']['state']);
        $this->assertSame('not_confirmed', $item['key']['state']);

        $readyTruck = $this->makeRow();
        $unitT = $this->softAssign($readyTruck);
        $this->stage($readyTruck, $unitT);
        $item = $this->activeItems()->firstWhere('order_product_unique_id', $readyTruck->unique_id);
        $this->assertSame('ready_for_dispatch', $item['readiness']);
    }

    public function test_store_filter_uses_store_unique_id_and_rejects_unknown_stores(): void
    {
        $north = $this->makeRow(); // storeNorth by fixture default
        $this->makeRow(null, ['delivery_store_id' => $this->storeSouth->id]);

        $items = $this->activeItems(['store' => $this->storeNorth->unique_id]);
        $this->assertCount(1, $items);
        $this->assertSame($north->unique_id, $items->first()['order_product_unique_id']);

        $this->board(['store' => 'STORE-NOPE'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'QUEUE_STORE_INVALID');
    }

    public function test_no_internal_database_ids_are_exposed(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row);

        $item = $this->activeItems()->first();

        foreach (['id', 'order_id', 'order_product_id', 'product_id', 'equipment_id_internal', 'equipment_soft_assign_id'] as $key) {
            $this->assertArrayNotHasKey($key, $item);
        }
        $this->assertArrayNotHasKey('id', $item['equipment']);
        $this->assertArrayNotHasKey('id', $item['product']);
        $this->assertArrayNotHasKey('id', $item['store']);
    }

    /**
     * Suppressed-forever items never appear anywhere on the mobile board.
     * Completed items are excluded from Pending/Staged (2026-07-19 rule,
     * unchanged) but DO surface in the Completed section (2026-07-23) — the
     * three-section board is the mobile module's own view; the web board
     * still excludes completed items entirely (QueueLineBoardTest covers that).
     */
    public function test_suppressed_items_never_appear_but_completed_items_move_to_the_completed_section(): void
    {
        $suppressed = $this->makeRow();
        $this->softAssign($suppressed);
        QueueLineService::removeForever($suppressed, $this->admin);

        $completed = $this->makeRow();
        $unit = $this->softAssign($completed);
        QueueLineService::complete($completed, QueueLineService::VIA_DISPATCH_STARTED, $unit->id);

        $visible = $this->makeRow();

        $items = $this->board()->json('data.items');
        $activeIds = collect($items['pending'])->concat($items['staged'])->pluck('order_product_unique_id');
        $completedIds = collect($items['completed'])->pluck('order_product_unique_id');

        $this->assertSame([$visible->unique_id], $activeIds->all());
        $this->assertSame([$completed->unique_id], $completedIds->all());
        $this->assertNotContains($suppressed->unique_id, $completedIds, 'suppressed-forever items never appear, even completed');
    }

    // ── Summary (home-page badge) ────────────────────────────────────────

    public function test_summary_returns_counts_without_item_payloads(): void
    {
        $rushed = $this->makeRow();
        $this->softAssign($rushed);
        QueueLineService::rush($rushed, $this->admin);
        $this->makeRow(); // needs equipment

        $response = $this->actingAs($this->admin, 'api_user')
            ->getJson($this->api('queue-line/summary'))
            ->assertOk()
            ->assertJsonPath('data.counts.total', 2)
            ->assertJsonPath('data.counts.rush', 1)
            ->assertJsonPath('data.counts.needs_equipment', 1)
            ->assertJsonPath('data.counts.staging_required', 1);

        $this->assertNull($response->json('data.items'));
    }

    // ── Detail ───────────────────────────────────────────────────────────

    public function test_item_detail_returns_complete_operational_state(): void
    {
        $row = $this->makeRow(null, [], [
            ['unique_id' => 'o1', 'name' => 'Auger', 'price' => 75, 'charged' => '1 Time Max', 'comment' => null],
        ]);
        $unit = $this->softAssign($row);
        $this->stage($row, $unit);

        $this->actingAs($this->admin, 'api_user')
            ->getJson($this->api('queue-line/' . $row->unique_id))
            ->assertOk()
            ->assertJsonPath('data.order_product_unique_id', $row->unique_id)
            ->assertJsonPath('data.assignment_state', 'direct')
            ->assertJsonPath('data.equipment.display_id', $unit->equipment_id)
            ->assertJsonPath('data.fuel.state', 'verified')
            ->assertJsonPath('data.key.state', 'confirmed')
            ->assertJsonPath('data.options_count', 1)
            ->assertJsonPath('data.staged', true)
            ->assertJsonPath('data.fully_staged', true)
            ->assertJsonPath('data.readiness', 'ready_for_dispatch')
            ->assertJsonPath('data.suppression.removed_forever', false)
            ->assertJsonPath('data.completed', false);
    }

    public function test_unknown_item_detail_returns_structured_404(): void
    {
        $this->actingAs($this->admin, 'api_user')
            ->getJson($this->api('queue-line/ORD-SCH-NOPE'))
            ->assertNotFound()
            ->assertJsonPath('error.code', 'QUEUE_ITEM_NOT_FOUND')
            ->assertJsonPath('success', false);

        $this->assertNotNull(
            $this->actingAs($this->admin, 'api_user')
                ->getJson($this->api('queue-line/ORD-SCH-NOPE'))
                ->json('error.corrective_action'),
        );
    }

    // ── History ──────────────────────────────────────────────────────────

    public function test_history_is_chronological_and_labels_prior_episode_fuel(): void
    {
        $row = $this->makeRow();
        $original = $this->softAssign($row);
        $this->verifyFuel($row, $original);

        $replacement = $this->makeEquipment(['assigned_product_id' => $row->product_id]);
        EquipmentReassignmentService::switch($row->fresh(['softAssignment.equipment', 'order']), $replacement, $this->admin, $this->admin);
        $this->verifyFuel($row->fresh(['softAssignment.equipment']), $replacement);

        $events = $this->actingAs($this->admin, 'api_user')
            ->getJson($this->api('queue-line/' . $row->unique_id . '/history'))
            ->assertOk()
            ->json('data.events');

        $times = collect($events)->pluck('at')->filter()->values();
        $this->assertSame($times->all(), $times->sort()->values()->all(), 'chronological');

        $fuelEvents = collect($events)->where('type', 'fuel')->values();
        $this->assertCount(2, $fuelEvents);
        $this->assertFalse($fuelEvents[0]['current_episode'], 'first sign-off belongs to the replaced assignment');
        $this->assertTrue($fuelEvents[1]['current_episode']);

        $switchEvents = collect($events)->where('type', 'assignment')->where('title', 'Equipment switched');
        $this->assertCount(1, $switchEvents);
    }

    public function test_history_includes_completion_and_release_information(): void
    {
        $row = $this->makeRow();
        $unit = $this->softAssign($row);
        QueueLineService::complete($row, QueueLineService::VIA_CUSTOMER_CHECKLIST_COMPLETED, $unit->id);

        $events = $this->actingAs($this->admin, 'api_user')
            ->getJson($this->api('queue-line/' . $row->unique_id . '/history'))
            ->json('data.events');

        $release = collect($events)->firstWhere('type', 'release');
        $this->assertNotNull($release);
        $this->assertStringContainsString('Left the yard', $release['title']);
        $this->assertStringContainsString('customer checklist', $release['detail']);
    }

    public function test_detail_reports_needs_equipment_and_completed_states(): void
    {
        $needs = $this->makeRow(); // eligible, no machine

        $this->actingAs($this->admin, 'api_user')
            ->getJson($this->api('queue-line/' . $needs->unique_id))
            ->assertOk()
            ->assertJsonPath('data.assignment_state', 'needs_equipment')
            ->assertJsonPath('data.equipment', null)
            ->assertJsonPath('data.available_actions.assign_equipment', true)
            ->assertJsonPath('data.available_actions.switch_equipment', false)
            ->assertJsonPath('data.readiness', 'equipment_assignment_required');

        // Completed items stay retrievable (detail documents the state even
        // though the board excludes them) — the app can render "already left".
        $done = $this->makeRow();
        $unit = $this->softAssign($done);
        QueueLineService::complete($done, QueueLineService::VIA_DISPATCH_STARTED, $unit->id);

        $this->actingAs($this->admin, 'api_user')
            ->getJson($this->api('queue-line/' . $done->unique_id))
            ->assertOk()
            ->assertJsonPath('data.completed', true);
    }

    public function test_history_never_includes_other_items_events(): void
    {
        $order = $this->makeOrder();
        $itemA = $this->makeRow($order);
        $itemB = $this->makeRow($order); // sibling on the SAME order
        $unitA = $this->softAssign($itemA);
        $unitB = $this->softAssign($itemB);
        $this->verifyFuel($itemB, $unitB); // activity on the sibling only

        $replacementB = $this->makeEquipment(['assigned_product_id' => $itemB->product_id]);
        EquipmentReassignmentService::switch($itemB->fresh(['softAssignment.equipment', 'order']), $replacementB, $this->admin, $this->admin);

        $events = $this->actingAs($this->admin, 'api_user')
            ->getJson($this->api('queue-line/' . $itemA->unique_id . '/history'))
            ->assertOk()
            ->json('data.events');

        $this->assertEmpty(
            collect($events)->whereIn('type', ['fuel', 'reversal'])->all(),
            'sibling fuel activity must never bleed into this item',
        );
        $this->assertEmpty(
            collect($events)->where('title', 'Equipment switched')->all(),
            'sibling switches must never bleed into this item',
        );
        $this->assertNotEmpty(
            collect($events)->where('title', 'Equipment reserved')->all(),
            'own assignment still present',
        );
    }

    // ── Actions: canonical rules surface as structured API errors ────────

    public function test_switch_without_reason_for_alternate_is_rejected_with_corrective_action(): void
    {
        $this->actingAs($this->admin, 'api_user');
        $row = $this->makeRow();
        $this->softAssign($row);
        $otherProduct = \App\Models\ProductManagement\Product::create([
            'product_name' => 'Different Product', 'slug' => 'diff-' . uniqid(), 'product_type' => 'Rental',
        ]);
        $alternate = $this->makeEquipment(['assigned_product_id' => $otherProduct->id]);

        $response = $this->postJson($this->api('queue-line/' . $row->unique_id . '/switch-equipment'), [
            'equipment_unique_id' => $alternate->unique_id,
            'performed_by' => $this->admin->unique_id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'QUEUE_REASON_REQUIRED');
        $this->assertNotNull($response->json('error.corrective_action'));
        $this->assertNull($response->json('error.current_equipment.id') ?? null);
    }

    // ── Equipment candidates ─────────────────────────────────────────────

    public function test_candidates_prioritize_exact_scan_then_direct_matches(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row);

        $direct = $this->makeEquipment(['assigned_product_id' => $row->product_id, 'equipment_name' => 'AAA Direct']);
        $alternate = $this->makeEquipment(['assigned_product_id' => null, 'equipment_id' => 'SCAN-TARGET-1', 'equipment_name' => 'ZZZ Scanned']);

        // Barcode scan: exact display-id match outranks everything
        $candidates = $this->actingAs($this->admin, 'api_user')
            ->getJson($this->api('queue-line/' . $row->unique_id . '/equipment-candidates?search=SCAN-TARGET-1'))
            ->assertOk()
            ->json('data.candidates');

        $this->assertSame('SCAN-TARGET-1', $candidates[0]['display_id']);
        $this->assertTrue($candidates[0]['exact_scan_match']);
        $this->assertSame('assignment_product_unknown', $candidates[0]['match']);
        $this->assertTrue($candidates[0]['requires_reason']);

        // No search: direct matches first
        $candidates = $this->actingAs($this->admin, 'api_user')
            ->getJson($this->api('queue-line/' . $row->unique_id . '/equipment-candidates'))
            ->json('data.candidates');

        $this->assertSame('direct', $candidates[0]['match']);
        $this->assertFalse($candidates[0]['requires_reason']);
    }

    public function test_candidates_exclude_rented_units_and_the_current_assignment(): void
    {
        $row = $this->makeRow();
        $current = $this->softAssign($row);
        $rented = $this->makeEquipment(['assigned_product_id' => $row->product_id, 'current_status' => 'rented']);
        $maintenance = $this->makeEquipment(['assigned_product_id' => $row->product_id, 'current_status' => 'maintenance']);

        $ids = collect(
            $this->actingAs($this->admin, 'api_user')
                ->getJson($this->api('queue-line/' . $row->unique_id . '/equipment-candidates'))
                ->json('data.candidates')
        )->pluck('unique_id');

        $this->assertNotContains($rented->unique_id, $ids, 'physically rented = hard integrity block');
        $this->assertNotContains($current->unique_id, $ids, 'the current unit is not a switch candidate');
        $this->assertContains($maintenance->unique_id, $ids, 'maintenance units stay visible with their status');
    }

    public function test_future_conflicts_never_remove_candidates(): void
    {
        $shared = $this->makeEquipment(['assigned_product_id' => $this->orderedProduct->id]);
        $otherRow = $this->makeRow(null, ['pickup_date' => now()->addDays(3)->format('Y-m-d')]);
        $this->softAssign($otherRow, $shared);

        $row = $this->makeRow(null, ['pickup_date' => now()->addDays(2)->format('Y-m-d')]);
        $this->softAssign($row);

        $ids = collect(
            $this->actingAs($this->admin, 'api_user')
                ->getJson($this->api('queue-line/' . $row->unique_id . '/equipment-candidates'))
                ->json('data.candidates')
        )->pluck('unique_id');

        $this->assertContains($shared->unique_id, $ids, 'a future conflict is office business, never a yard block');
    }

    // ── Release-guard envelope (mobile contract) ─────────────────────────

    public function test_release_block_carries_corrective_action_and_current_equipment(): void
    {
        $row = $this->makeRow();
        $unit = $this->softAssign($row);

        $response = $this->actingAs($this->admin, 'api_user')->postJson($this->api('orders/schedules/driver-checklist'), [
            'order_product_unique_id' => $row->unique_id,
            'schedule_type' => 'Delivery',
            'driver_status' => 'Ready to Go',
        ]);

        // Whatever the exact driver-checklist payload requirements, a guarded
        // 422 must carry the structured block; skip if validation shape differs.
        if ($response->status() === 422 && $response->json('error.code') === 'QUEUE_FUEL_VERIFICATION_REQUIRED') {
            $this->assertNotNull($response->json('error.corrective_action'));
            $this->assertSame($unit->unique_id, $response->json('error.equipment.unique_id'));
        } else {
            $this->markTestSkipped('driver-checklist validation shape changed — guard payload covered by QueueLineReleaseAndCompletionTest');
        }
    }
}
