<?php

namespace Tests\Feature\QueueLine;

use App\Events\Admin\Orders\OrderCustomerChecklistEvent;
use App\Livewire\QueueLine\Board;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\EquipmentSubstitutionLog;
use App\Models\Orders\OrderHistory;
use App\Models\Orders\QueueLineFuelVerification;
use App\Services\Equipment\EquipmentReassignmentService;
use App\Services\QueueLine\QueueFuelVerificationService;
use App\Services\QueueLine\QueueLineService;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

/**
 * Phase 3C — release enforcement, Queue Line completion, and the mobile
 * contracts. Governing sequence: select the machine → verify THAT machine
 * Fuel Full → release → complete and leave the board.
 */
class QueueLineReleaseAndCompletionTest extends QueueLineTestCase
{
    protected User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employee = User::create([
            'first_name' => 'Release', 'last_name' => 'Tech',
            'email' => 'release-tech@test.local', 'status' => 'Active',
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function api(string $path): string
    {
        return 'http://' . config('app.domains.api') . '/api/admin/v1/' . ltrim($path, '/');
    }

    private function verifyFuel($row): QueueLineFuelVerification
    {
        return QueueFuelVerificationService::verify(
            orderProduct: $row->fresh(['softAssignment.equipment', 'order', 'queueLineItem']),
            expected: $row->fresh('softAssignment')->softAssignment->equipment,
            performedBy: $this->employee,
            actor: $this->admin,
        )['verification'];
    }

    private function readyToGo($row): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin, 'api_user')->postJson($this->api('orders/schedules/driver-checklist'), [
            'order_product_unique_id' => $row->unique_id,
            'checklist_type' => 'delivery',
            'equipment_driver_status' => 'Ready to Go',
        ]);
    }

    private function saveDelivery($row, $equipment): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin, 'api_user')->postJson($this->api('orders/customer-checklists/save-delivery'), [
            'order_product_unique_id' => $row->unique_id,
            'equipment_unique_id' => $equipment->unique_id,
            'user_id' => (string) $this->admin->id,
        ]);
    }

    // ── Truck release enforcement (Ready to Go) ──────────────────────────

    public function test_ready_to_go_blocked_without_fuel_with_structured_422(): void
    {
        $row = $this->makeRow();
        $unit = $this->softAssign($row);

        $response = $this->readyToGo($row);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'QUEUE_FUEL_VERIFICATION_REQUIRED')
            ->assertJsonPath('error.order_product_unique_id', $row->unique_id)
            ->assertJsonPath('error.fuel_state', 'not_verified');
        $this->assertStringContainsString($unit->equipment_name, $response->json('error.message'));
        // No internal table ids exposed
        $this->assertArrayNotHasKey('id', $response->json('error.equipment'));
        // Nothing written — release never started
        $this->assertNull($row->fresh()->delivery_ready_to_go_at);
        $this->assertNull($row->fresh('queueLineItem')->queueLineItem?->completed_at);
    }

    public function test_ready_to_go_with_current_fuel_releases_and_completes(): void
    {
        $row = $this->makeRow();
        $unit = $this->softAssign($row);
        $this->verifyFuel($row);

        $this->readyToGo($row)->assertOk();

        $fresh = $row->fresh(['queueLineItem']);
        $this->assertNotNull($fresh->delivery_ready_to_go_at);
        $item = $fresh->queueLineItem;
        $this->assertNotNull($item->completed_at);
        $this->assertSame('dispatch_started', $item->completed_via);
        $this->assertEquals($unit->id, $item->completed_equipment_id);
    }

    public function test_prior_unit_prior_episode_and_reversed_verifications_never_satisfy_release(): void
    {
        // Verified ABC, then switched to XYZ → XYZ unverified → blocked
        $rowA = $this->makeRow();
        $abc = $this->softAssign($rowA);
        $this->verifyFuel($rowA);
        $xyz = $this->makeEquipment(['assigned_product_id' => $rowA->product_id]);
        EquipmentReassignmentService::switch($rowA->fresh(['softAssignment.equipment', 'order']), $xyz, $this->employee, $this->admin);
        $this->readyToGo($rowA)->assertStatus(422)->assertJsonPath('error.code', 'QUEUE_FUEL_VERIFICATION_REQUIRED');

        // Switched BACK to ABC (new episode) → old ABC sign-off dead → blocked
        EquipmentReassignmentService::switch($rowA->fresh(['softAssignment.equipment', 'order']), $abc, $this->employee, $this->admin);
        $this->readyToGo($rowA)->assertStatus(422)->assertJsonPath('error.code', 'QUEUE_FUEL_VERIFICATION_REQUIRED');

        // Reversed verification → blocked
        $rowB = $this->makeRow();
        $this->softAssign($rowB);
        $verification = $this->verifyFuel($rowB);
        QueueFuelVerificationService::reverse($verification, $this->employee, $this->admin, 'wrong machine');
        $this->readyToGo($rowB)->assertStatus(422)->assertJsonPath('error.code', 'QUEUE_FUEL_VERIFICATION_REQUIRED');
    }

    public function test_remove_today_does_not_waive_fuel_but_remove_forever_exits_management(): void
    {
        // Remove Today: hidden card, fuel still enforced
        $hidden = $this->makeRow();
        $this->softAssign($hidden);
        QueueLineService::removeToday($hidden, $this->admin);
        $this->readyToGo($hidden)->assertStatus(422)->assertJsonPath('error.code', 'QUEUE_FUEL_VERIFICATION_REQUIRED');

        // Remove Forever: outside Queue Line management (approved Phase 1
        // policy, incl. the fuel requirement) → release flows freely
        $removed = $this->makeRow();
        $this->softAssign($removed);
        QueueLineService::removeForever($removed, $this->admin);
        $this->readyToGo($removed)->assertOk();
    }

    public function test_needs_equipment_item_cannot_release(): void
    {
        $row = $this->makeRow(); // eligible, no assignment

        $this->readyToGo($row)
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'QUEUE_EQUIPMENT_REQUIRED');
    }

    public function test_non_queue_scope_is_never_blocked(): void
    {
        // Return leg (pickup Ready to Go) — never touched by the guard
        $delivered = $this->makeRow(null, ['delivery_status' => 'Completed', 'is_delivered' => true, 'pickup_date' => now()->format('Y-m-d')]);
        $this->actingAs($this->admin, 'api_user')->postJson($this->api('orders/schedules/driver-checklist'), [
            'order_product_unique_id' => $delivered->unique_id,
            'checklist_type' => 'pickup',
            'equipment_driver_status' => 'Ready to Go',
        ])->assertOk();

        // Outside the Queue Line window (early release, due in 5 days)
        $early = $this->makeRow(null, ['delivery_date' => now()->addDays(5)->format('Y-m-d')]);
        $this->softAssign($early);
        $this->readyToGo($early)->assertOk();

        // Sales product line
        $sales = $this->makeRow(null, ['product_data' => ['product_type' => 'Sales', 'product_option_items' => []]]);
        $this->readyToGo($sales)->assertOk();
    }

    public function test_repeated_ready_to_go_replays_and_keeps_the_original_completion(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row);
        $this->verifyFuel($row);

        $this->readyToGo($row)->assertOk();
        $original = $row->fresh('queueLineItem')->queueLineItem->completed_at;

        Carbon::setTestNow(now()->addMinutes(15));
        $this->readyToGo($row)->assertOk(); // already completed → guard skips, latch holds
        Carbon::setTestNow();

        $this->assertTrue($row->fresh('queueLineItem')->queueLineItem->completed_at->equalTo($original));
    }

    // ── In-store release enforcement (Customer Checklist) ────────────────

    public function test_save_delivery_blocked_without_fuel_before_any_destructive_work(): void
    {
        $row = $this->makeRow(null, ['delivery_transport_mode' => 'Store']);
        $unit = $this->softAssign($row);

        $this->saveDelivery($row, $unit)
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'QUEUE_FUEL_VERIFICATION_REQUIRED');

        // Fuel was evaluated BEFORE the soft assignment was deleted
        $fresh = $row->fresh(['softAssignment']);
        $this->assertNotNull($fresh->softAssignment);
        $this->assertSame('Pending', $fresh->delivery_status);
        $this->assertNull($fresh->equipment_id);
    }

    public function test_save_delivery_with_fuel_releases_hard_assigns_and_completes(): void
    {
        $row = $this->makeRow(null, ['delivery_transport_mode' => 'Store']);
        $unit = $this->softAssign($row);
        $this->verifyFuel($row);

        $this->saveDelivery($row, $unit)->assertOk();

        $fresh = $row->fresh(['queueLineItem']);
        $this->assertSame('Completed', $fresh->delivery_status);
        $this->assertEquals($unit->id, $fresh->equipment_id); // the verified unit left
        $item = $fresh->queueLineItem;
        $this->assertNotNull($item->completed_at);
        $this->assertSame('customer_checklist_completed', $item->completed_via);
        $this->assertEquals($unit->id, $item->completed_equipment_id);
        // Fuel + reassignment history survive completion
        $this->assertSame(1, QueueLineFuelVerification::count());
    }

    public function test_save_delivery_with_a_different_unit_than_staged_is_rejected(): void
    {
        $row = $this->makeRow(null, ['delivery_transport_mode' => 'Store']);
        $staged = $this->softAssign($row);
        $this->verifyFuel($row);
        $other = $this->makeEquipment(['assigned_product_id' => $row->product_id]);

        $this->saveDelivery($row, $other)
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'QUEUE_ASSIGNMENT_CHANGED');

        $this->assertSame('Pending', $row->fresh()->delivery_status);
        $this->assertEquals($staged->id, $row->fresh(['softAssignment'])->softAssignment->equipment_id);
    }

    public function test_truck_defensive_second_check_is_idempotent(): void
    {
        // Truck path: Ready to Go completes the item…
        $row = $this->makeRow();
        $unit = $this->softAssign($row);
        $this->verifyFuel($row);
        $this->readyToGo($row)->assertOk();
        $original = $row->fresh('queueLineItem')->queueLineItem;

        // …the later checklist save replays the completion, never rewrites it
        Carbon::setTestNow(now()->addHours(2));
        $this->saveDelivery($row, $unit)->assertOk();
        Carbon::setTestNow();

        $item = $row->fresh('queueLineItem')->queueLineItem;
        $this->assertSame('dispatch_started', $item->completed_via); // original source kept
        $this->assertTrue($item->completed_at->equalTo($original->completed_at));
        $this->assertSame(1, \App\Models\Orders\QueueLineItem::where('order_product_id', $row->id)->count());
    }

    // ── Completion & board behavior ──────────────────────────────────────

    public function test_completed_item_leaves_both_boards_and_siblings_remain(): void
    {
        $order = $this->makeOrder();
        $releasing = $this->makeRow($order);
        $sibling = $this->makeRow($order);
        $unit = $this->softAssign($releasing);
        $this->softAssign($sibling);
        $this->verifyFuel($releasing);

        $this->readyToGo($releasing)->assertOk();

        foreach ([[], ['wallboard' => true]] as $params) {
            $html = Livewire::test(Board::class, $params)->html();
            // A completed item leaves the ACTIVE segments and reappears as
            // the read-only Completed reference card (lifecycle pipeline
            // 2026-07-20) — it never renders as actionable work again.
            $this->assertStringContainsString('Queue Line — Completed', $html);
            $this->assertSame(1, substr_count($html, 'data-delivered="1"'));
            $this->assertStringContainsString('data-order-product-id="' . $releasing->id . '"', $html);
            $this->assertStringContainsString('Completed', $html);
            $this->assertStringContainsString('data-order-product-id="' . $sibling->id . '"', $html);
        }
    }

    public function test_completed_items_are_never_revived_by_lazy_rows_suppression_expiry_or_restore(): void
    {
        $row = $this->makeRow();
        $unit = $this->softAssign($row);
        $this->verifyFuel($row);
        $this->readyToGo($row)->assertOk();

        // Lazy row creation via later actions keeps the latch
        QueueLineService::rush($row->fresh(), $this->admin);
        $this->assertNotContains($row->id, $this->boardIds());

        // Remove Today expiry does not revive
        QueueLineService::removeToday($row->fresh(), $this->admin);
        Carbon::setTestNow(now()->addDay());
        $this->assertNotContains($row->id, $this->boardIds());
        Carbon::setTestNow();

        // Restore from Remove Forever does not revive
        QueueLineService::removeForever($row->fresh(), $this->admin);
        QueueLineService::restore($row->fresh(), $this->admin);
        $this->assertNotContains($row->id, $this->boardIds());
        $this->assertNotNull($row->fresh('queueLineItem')->queueLineItem->completed_at);
    }

    public function test_checklist_removal_reopens_the_completion_latch(): void
    {
        $row = $this->makeRow(null, ['delivery_transport_mode' => 'Store']);
        $unit = $this->softAssign($row);
        $this->verifyFuel($row);
        $this->saveDelivery($row, $unit)->assertOk();
        $this->assertNotNull($row->fresh('queueLineItem')->queueLineItem->completed_at);
        $fuelEvents = QueueLineFuelVerification::count();

        // The supported operational reversal (checklist removal) re-Pendings
        // the order's rows and fires the checklist_removed event
        event(new OrderCustomerChecklistEvent($row->order, $this->admin, 'checklist_removed'));

        $item = $row->fresh('queueLineItem')->queueLineItem;
        $this->assertNull($item->completed_at);
        $this->assertNull($item->completed_via);
        // Fuel history untouched — a fresh assignment episode will demand a
        // fresh verification anyway
        $this->assertSame($fuelEvents, QueueLineFuelVerification::count());
    }

    // ── Administrative paths ─────────────────────────────────────────────

    public function test_close_as_completed_needs_no_fuel_and_writes_no_release_history(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row); // unverified on purpose

        // Administrative closure through the existing web action
        $this->withoutMiddleware()->putJson(
            route('admin.order-management.orders.update-product-schedule', [$row->order->unique_id, $row->unique_id]),
            ['type' => 'delivery', 'delivery_status' => 'Close as Completed']
        )->assertOk();

        $fresh = $row->fresh(['queueLineItem']);
        $this->assertSame('Close as Completed', $fresh->delivery_status);
        // Off the board via the eligibility predicate — but NO Queue Line
        // completion latch and NO misleading dispatch history
        $this->assertNotContains($row->id, $this->boardIds());
        $this->assertNull($fresh->queueLineItem?->completed_at);
    }

    // ── Mobile equipment switch ──────────────────────────────────────────

    private function apiSwitch($row, $equipment, array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin, 'api_user')->postJson($this->api('queue-line/' . $row->unique_id . '/switch-equipment'), array_merge([
            'equipment_unique_id' => $equipment->unique_id,
            'performed_by' => $this->employee->unique_id,
        ], $overrides));
    }

    public function test_mobile_switch_changes_the_canonical_assignment_and_reports_fuel_state(): void
    {
        $row = $this->makeRow();
        $original = $this->softAssign($row);
        $this->verifyFuel($row); // verified on the ORIGINAL unit
        $replacement = $this->makeEquipment(['assigned_product_id' => $row->product_id]);

        $response = $this->apiSwitch($row, $replacement);

        $response->assertOk()
            ->assertJsonPath('data.classification', 'direct')
            ->assertJsonPath('data.replayed', false)
            ->assertJsonPath('data.current_equipment.unique_id', $replacement->unique_id)
            ->assertJsonPath('data.previous_equipment.unique_id', $original->unique_id)
            ->assertJsonPath('data.fuel_state', 'not_verified'); // fresh episode

        $this->assertEquals($replacement->id, $row->fresh(['softAssignment'])->softAssignment->equipment_id);
        $this->assertSame(1, OrderHistory::where('action', 'equipment_reassigned')->count());

        // Replay: same request again → canonical no-op, reported replayed
        $this->apiSwitch($row, $replacement, ['idempotency_token' => 'tok-1'])
            ->assertOk()
            ->assertJsonPath('data.replayed', true);
        $this->assertSame(1, OrderHistory::where('action', 'equipment_reassigned')->count());
    }

    public function test_mobile_switch_error_codes(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row);

        // Rented unit
        $rented = $this->makeEquipment(['current_status' => 'rented']);
        $this->apiSwitch($row, $rented)->assertStatus(422)->assertJsonPath('error.code', 'QUEUE_EQUIPMENT_RENTED');

        // Alternate without reason / with reason
        $otherProduct = \App\Models\ProductManagement\Product::create([
            'product_name' => 'Alt', 'slug' => 'alt-' . uniqid(), 'product_type' => 'Rental',
        ]);
        $alt = $this->makeEquipment(['assigned_product_id' => $otherProduct->id]);
        $this->apiSwitch($row, $alt)->assertStatus(422)->assertJsonPath('error.code', 'QUEUE_REASON_REQUIRED');
        $this->apiSwitch($row, $alt, ['reason' => '40ft buried'])->assertOk()->assertJsonPath('data.classification', 'alternate');
        $this->assertSame(1, EquipmentSubstitutionLog::count());

        // Delivered item
        $delivered = $this->makeRow(null, ['delivery_status' => 'Completed', 'is_delivered' => true]);
        $hard = $this->makeEquipment();
        $delivered->update(['equipment_id' => $hard->id]);
        $this->apiSwitch($delivered, $this->makeEquipment())->assertStatus(422)->assertJsonPath('error.code', 'QUEUE_ITEM_DELIVERED');

        // Unknown item / equipment
        $this->actingAs($this->admin, 'api_user')->postJson($this->api('queue-line/ORD-SCH-NOPE/switch-equipment'), [
            'equipment_unique_id' => $alt->unique_id,
            'performed_by' => $this->employee->unique_id,
        ])->assertStatus(404)->assertJsonPath('error.code', 'QUEUE_ITEM_NOT_FOUND');
    }

    public function test_mobile_switch_reports_conflicts_without_blocking(): void
    {
        $shared = $this->makeEquipment(['assigned_product_id' => $this->orderedProduct->id]);
        $otherRow = $this->makeRow(null, ['pickup_date' => now()->addDays(3)->format('Y-m-d')]);
        $this->softAssign($otherRow, $shared);

        $row = $this->makeRow(null, ['pickup_date' => now()->addDays(2)->format('Y-m-d')]);
        $this->softAssign($row);

        $this->apiSwitch($row, $shared)
            ->assertOk()
            ->assertJsonPath('data.conflict_count', 1)
            ->assertJsonPath('data.replayed', false);
    }

    // ── Mobile staging (all-or-nothing — replaces the fuel-only endpoint) ──

    private function apiMarkStaged($row, $equipment, array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin, 'api_user')->postJson($this->api('queue-line/' . $row->unique_id . '/mark-staged'), array_merge([
            'equipment_unique_id' => $equipment->unique_id,
            'performed_by' => $this->employee->unique_id,
            'fuel_full' => true,
            'key_with_machine' => true,
        ], $overrides));
    }

    public function test_mobile_mark_staged_records_the_full_set_with_mobile_source_and_replays_tokens(): void
    {
        $row = $this->makeRow();
        $unit = $this->softAssign($row);

        $this->apiMarkStaged($row, $unit, ['idempotency_token' => 'mob-9'])
            ->assertOk()
            ->assertJsonPath('data.fully_staged', true)
            ->assertJsonPath('data.replayed', false)
            ->assertJsonPath('data.staged_by', 'Release Tech');

        // The SAME canonical records the web modal writes — fuel + key + latch
        $v = QueueLineFuelVerification::firstOrFail();
        $this->assertSame('queue_line_mobile', $v->source);
        $this->assertEquals($this->employee->id, $v->performed_by);
        $this->assertEquals($this->admin->id, $v->created_by);
        $key = \App\Models\Orders\QueueLineKeyConfirmation::firstOrFail();
        $this->assertSame('queue_line_mobile', $key->source);
        $this->assertEquals($this->employee->id, $key->performed_by);
        $this->assertNotNull($row->fresh('queueLineItem')->queueLineItem->staged_at);

        // Token replay — nothing duplicated
        $this->apiMarkStaged($row, $unit, ['idempotency_token' => 'mob-9'])
            ->assertOk()->assertJsonPath('data.replayed', true);
        $this->assertSame(1, QueueLineFuelVerification::count());
        $this->assertSame(1, \App\Models\Orders\QueueLineKeyConfirmation::count());

        // Release now passes
        $this->readyToGo($row)->assertOk();
    }

    public function test_mobile_mark_staged_is_all_or_nothing(): void
    {
        $row = $this->makeRow();
        $unit = $this->softAssign($row);

        // Fuel not full → whole request rejected, nothing recorded
        $this->apiMarkStaged($row, $unit, ['fuel_full' => false])
            ->assertStatus(422)->assertJsonPath('error.code', 'QUEUE_FUEL_NOT_FULL');

        // Key missing → same
        $this->apiMarkStaged($row, $unit, ['key_with_machine' => false])
            ->assertStatus(422)->assertJsonPath('error.code', 'QUEUE_KEY_MISSING');

        $this->assertSame(0, QueueLineFuelVerification::count());
        $this->assertSame(0, \App\Models\Orders\QueueLineKeyConfirmation::count());
        $this->assertNull($row->fresh('queueLineItem')->queueLineItem?->staged_at);
    }

    public function test_mobile_return_to_pending_reverses_the_full_set(): void
    {
        $row = $this->makeRow();
        $unit = $this->softAssign($row);
        $this->apiMarkStaged($row, $unit)->assertOk();

        $this->actingAs($this->admin, 'api_user')
            ->postJson($this->api('queue-line/' . $row->unique_id . '/return-to-pending'), [
                'performed_by' => $this->employee->unique_id,
            ])
            ->assertOk()
            ->assertJsonPath('data.fully_staged', false);

        // Latch cleared; append-only reversals recorded; history preserved
        $this->assertNull($row->fresh('queueLineItem')->queueLineItem->staged_at);
        $this->assertSame(2, QueueLineFuelVerification::count());
        $this->assertSame(2, \App\Models\Orders\QueueLineKeyConfirmation::count());
    }

    public function test_mobile_mark_staged_rejects_stale_unassigned_and_ineligible_items(): void
    {
        // Stale equipment
        $row = $this->makeRow();
        $old = $this->softAssign($row);
        $new = $this->makeEquipment(['assigned_product_id' => $row->product_id]);
        EquipmentReassignmentService::switch($row->fresh(['softAssignment.equipment', 'order']), $new, $this->employee, $this->admin);
        $this->apiMarkStaged($row, $old)->assertStatus(422)->assertJsonPath('error.code', 'QUEUE_ASSIGNMENT_CHANGED');

        // Needs Equipment
        $bare = $this->makeRow();
        $this->apiMarkStaged($bare, $this->makeEquipment())->assertStatus(422)->assertJsonPath('error.code', 'QUEUE_EQUIPMENT_REQUIRED');

        // Ineligible (outside window)
        $far = $this->makeRow(null, ['delivery_date' => now()->addDays(9)->format('Y-m-d')]);
        $farUnit = $this->softAssign($far);
        $this->apiMarkStaged($far, $farUnit)->assertStatus(422)->assertJsonPath('error.code', 'QUEUE_ITEM_NOT_ELIGIBLE');

        // Partial state never leaks from a rejection
        $this->assertSame(0, \App\Models\Orders\QueueLineKeyConfirmation::count());
    }

    public function test_guest_api_requests_are_rejected(): void
    {
        auth()->logout();

        $this->postJson($this->api('queue-line/ANY-ID/mark-staged'), [])->assertUnauthorized();
        $this->postJson($this->api('queue-line/ANY-ID/return-to-pending'), [])->assertUnauthorized();
        $this->postJson($this->api('queue-line/ANY-ID/switch-equipment'), [])->assertUnauthorized();
        $this->getJson($this->api('queue-line'))->assertUnauthorized();
        $this->postJson($this->api('orders/schedules/driver-checklist'), [])->assertUnauthorized();
    }
}
