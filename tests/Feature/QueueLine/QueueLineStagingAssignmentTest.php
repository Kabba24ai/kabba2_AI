<?php

namespace Tests\Feature\QueueLine;

use App\Livewire\QueueLine\Board;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\MaintenanceManagement\EquipmentSoftAssign;
use App\Models\MaintenanceManagement\EquipmentSubstitutionLog;
use App\Models\Orders\OrderHistory;
use App\Models\Orders\OrderProduct;
use App\Models\Orders\QueueLineFuelVerification;
use App\Models\Orders\QueueLineKeyConfirmation;
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;
use App\Services\QueueLine\QueueLineOperationException;
use App\Services\QueueLine\QueueLineService;
use App\Services\QueueLine\QueueLineStagingService;
use Livewire\Livewire;

/**
 * Staging Assignment Integration (corrective mission 2026-07-20) — the Mark
 * as Staged modal is assignment-capable: it may assign or reassign equipment
 * THROUGH the canonical assignment operation (EquipmentReassignmentService's
 * delete-then-create soft assignment — the same record Order Details,
 * Schedule Assignment, Dispatch, Customer Checklist, and conflict detection
 * all read live). Assignment + fuel + key + staged latch are ONE atomic
 * business operation; a failure anywhere records nothing anywhere.
 */
class QueueLineStagingAssignmentTest extends QueueLineTestCase
{
    private User $employee;

    private ProductCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employee = User::create([
            'first_name' => 'Yard', 'last_name' => 'Tech',
            'email' => 'yard-tech@test.local', 'status' => 'Active',
        ]);

        $this->category = ProductCategory::create(['title' => 'Boom Lifts']);
    }

    /** A category-mapped unit the modal's Category → Equipment path can select. */
    private function makeCategoryUnit(array $overrides = []): Equipment
    {
        return $this->makeEquipment(array_merge([
            'assigned_product_id' => $this->orderedProduct->id,
            'product_category_id' => $this->category->id,
        ], $overrides));
    }

    /** Open the modal, drive the assign path, and submit the full checklist. */
    private function assignAndStageViaModal(OrderProduct $row, Equipment $target, ?string $reason = null)
    {
        $component = Livewire::test(Board::class)
            ->call('openStaging', $row->id)
            ->set('stagingMode', 'assign')
            ->set('stagingCategory', (string) $this->category->id)
            ->set('stagingEquipmentId', (string) $target->id)
            ->set('stagingPerformedBy', (string) $this->employee->id)
            ->set('stagingFuel', 'full')
            ->set('stagingKey', 'with_machine');

        if ($reason !== null) {
            $component->set('stagingReason', $reason);
        }

        return $component->call('confirmStaging');
    }

    private function sectionBlock(string $html, string $section): string
    {
        $start = strpos($html, 'data-queue-section="' . $section . '"');
        if ($start === false) {
            return '';
        }
        $end = strpos($html, 'data-queue-section="', $start + 1);

        return $end === false ? substr($html, $start) : substr($html, $start, $end - $start);
    }

    // ── Unassigned item: assign + stage atomically ───────────────────────

    public function test_an_unassigned_item_assigns_and_stages_in_one_submission(): void
    {
        $row = $this->makeRow(); // no equipment
        $unit = $this->makeCategoryUnit();

        $component = $this->assignAndStageViaModal($row, $unit);
        $component->assertSet('stagingItemId', null)
            ->assertSee('assigned and marked as staged');

        // The CANONICAL soft assignment — the same record every surface reads
        $live = EquipmentSoftAssign::where('order_product_id', $row->id)->sole();
        $this->assertEquals($unit->id, $live->equipment_id);
        $this->assertEquals($this->employee->id, $live->assigned_by);

        // Fuel + key are bound to that exact assignment episode
        $fuel = QueueLineFuelVerification::sole();
        $key = QueueLineKeyConfirmation::sole();
        $this->assertEquals($live->id, $fuel->equipment_soft_assign_id);
        $this->assertEquals($live->id, $key->equipment_soft_assign_id);
        $this->assertEquals($unit->id, $fuel->equipment_id);

        // Staged latch set; card rendered in the Staged section
        $this->assertNotNull($row->fresh('queueLineItem')->queueLineItem->staged_at);
        $this->assertStringContainsString(
            'data-order-product-id="' . $row->id . '"',
            $this->sectionBlock($component->html(), 'ready'),
        );

        // The canonical assignment audit identifies the entry point
        $history = OrderHistory::where('order_id', $row->order_id)
            ->where('action', 'equipment_reassigned')->sole();
        $this->assertStringContainsString('from unassigned', $history->description);
        $this->assertStringContainsString('Yard Tech', $history->description);
    }

    // ── Assigned item: accept current, or reassign ───────────────────────

    public function test_an_assigned_item_accepts_its_current_assignment_and_stages(): void
    {
        $row = $this->makeRow();
        $unit = $this->softAssign($row);
        $episodeBefore = $row->fresh()->softAssignment->id;

        Livewire::test(Board::class)
            ->call('openStaging', $row->id)
            ->assertSet('stagingMode', 'current')
            ->assertSee('Use Currently Assigned')
            ->set('stagingPerformedBy', (string) $this->employee->id)
            ->set('stagingFuel', 'full')
            ->set('stagingKey', 'with_machine')
            ->call('confirmStaging')
            ->assertSet('stagingItemId', null);

        // Same episode — accepting the current unit reassigns NOTHING
        $this->assertEquals($episodeBefore, $row->fresh()->softAssignment->id);
        $this->assertSame(0, OrderHistory::where('action', 'equipment_reassigned')->count());
        $this->assertEquals($unit->id, QueueLineFuelVerification::sole()->equipment_id);
        $this->assertNotNull($row->fresh('queueLineItem')->queueLineItem->staged_at);
    }

    public function test_an_assigned_item_reassigns_different_equipment_and_stages(): void
    {
        $row = $this->makeRow();
        $original = $this->softAssign($row);
        $replacement = $this->makeCategoryUnit();

        $this->assignAndStageViaModal($row, $replacement)
            ->assertSee('assigned and marked as staged');

        // Former assignment superseded exactly as the canonical workflow does:
        // one live row pointing at the replacement, the old row soft-deleted
        $live = EquipmentSoftAssign::where('order_product_id', $row->id)->sole();
        $this->assertEquals($replacement->id, $live->equipment_id);
        $this->assertSame(2, EquipmentSoftAssign::withTrashed()->where('order_product_id', $row->id)->count());

        // Fuel, key, and staged reference the NEW assignment episode
        $this->assertEquals($live->id, QueueLineFuelVerification::sole()->equipment_soft_assign_id);
        $this->assertEquals($live->id, QueueLineKeyConfirmation::sole()->equipment_soft_assign_id);
        $this->assertNotNull($row->fresh('queueLineItem')->queueLineItem->staged_at);

        // Dual-attribution audit for the reassignment
        $history = OrderHistory::where('action', 'equipment_reassigned')->sole();
        $this->assertStringContainsString($original->equipment_name, $history->description);
        $this->assertStringContainsString($replacement->equipment_name, $history->description);
    }

    // ── Every consumer reads the Queue Line selection ────────────────────

    public function test_every_canonical_consumer_reads_the_queue_line_selection(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row);
        $replacement = $this->makeCategoryUnit(['equipment_name' => 'Queue Selected Unit QSX']);

        $this->assignAndStageViaModal($row, $replacement);

        // The relation Order Details and the Assign Equipment modal render
        // ('products.softAssignment.equipment' / soft-assigned-equipment)
        $this->assertEquals($replacement->id, $row->fresh()->softAssignment->equipment_id);

        // Queue Line board
        Livewire::test(Board::class)->assertSee('Queue Selected Unit QSX');

        // Dispatch page
        $dispatchHtml = $this->get(
            route('admin.order-management.dispatch.index'),
            ['X-Requested-With' => 'XMLHttpRequest']
        )->assertOk()->json('html');
        $this->assertStringContainsString('Queue Selected Unit QSX', $dispatchHtml);

        // Schedule page still resolves the row through the live relation
        $scheduleHtml = $this->get(
            route('admin.order-management.schedules.index', ['schedule_type' => ['Delivery']]),
            ['X-Requested-With' => 'XMLHttpRequest']
        )->assertOk()->json('html');
        $this->assertStringContainsString($row->order->order_number, $scheduleHtml);

        // Customer Checklist mobile resource falls back to the LIVE soft unit
        $resource = \App\Http\Resources\Api\Admin\V1\OrderProducts\ListResource::make(
            $row->fresh(['softEquipment', 'equipment'])
        )->resolve();
        $this->assertStringContainsString('Queue Selected Unit QSX', json_encode($resource['equipment_details']));
    }

    // ── Atomicity: both directions ───────────────────────────────────────

    public function test_a_failed_assignment_records_no_readiness_state(): void
    {
        $row = $this->makeRow();
        $original = $this->softAssign($row);
        $rented = $this->makeCategoryUnit(['current_status' => 'rented']);

        // Drive the component directly — the UI disables rented options, so
        // this proves the SERVER guard, not the markup
        Livewire::test(Board::class)
            ->call('openStaging', $row->id)
            ->set('stagingMode', 'assign')
            ->set('stagingCategory', (string) $this->category->id)
            ->set('stagingEquipmentId', (string) $rented->id)
            ->set('stagingPerformedBy', (string) $this->employee->id)
            ->set('stagingFuel', 'full')
            ->set('stagingKey', 'with_machine')
            ->call('confirmStaging')
            ->assertSee('rented');

        // Nothing moved, nothing recorded
        $this->assertEquals($original->id, $row->fresh()->softAssignment->equipment_id);
        $this->assertSame(0, QueueLineFuelVerification::count());
        $this->assertSame(0, QueueLineKeyConfirmation::count());
        $this->assertNull($row->fresh('queueLineItem')->queueLineItem?->staged_at);
    }

    public function test_a_failed_readiness_step_rolls_back_the_assignment_change(): void
    {
        $row = $this->makeRow();
        $original = $this->softAssign($row);
        $replacement = $this->makeCategoryUnit();

        // The item leaves the Queue Line before the submission lands — the
        // reassignment itself would succeed, but the readiness step throws.
        QueueLineService::complete($row, QueueLineService::VIA_DISPATCH_STARTED, $original->id);
        $row = $row->fresh(['softAssignment.equipment', 'order', 'queueLineItem']);

        try {
            QueueLineStagingService::assignAndStage(
                orderProduct: $row,
                baselineAssignmentId: $row->softAssignment->id,
                target: $replacement,
                performedBy: $this->employee,
                actor: $this->admin,
                fuelFull: true,
                keyWithMachine: true,
            );
            $this->fail('Staging an item that left the Queue Line should be rejected.');
        } catch (QueueLineOperationException $e) {
            $this->assertStringContainsString('already left', $e->getMessage());
        }

        // The whole transaction rolled back: assignment untouched, no audit
        // row, no readiness records
        $this->assertEquals($original->id, $row->fresh()->softAssignment->equipment_id);
        $this->assertSame(1, EquipmentSoftAssign::withTrashed()->where('order_product_id', $row->id)->count());
        $this->assertSame(0, OrderHistory::where('action', 'equipment_reassigned')->count());
        $this->assertSame(0, QueueLineFuelVerification::count());
        $this->assertSame(0, QueueLineKeyConfirmation::count());
    }

    // ── Stale-state + idempotency ────────────────────────────────────────

    public function test_a_stale_modal_cannot_overwrite_a_newer_assignment(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row);
        $mine = $this->makeCategoryUnit();

        // My modal is open on the original unit…
        $component = Livewire::test(Board::class)
            ->call('openStaging', $row->id)
            ->set('stagingMode', 'assign')
            ->set('stagingCategory', (string) $this->category->id)
            ->set('stagingEquipmentId', (string) $mine->id)
            ->set('stagingPerformedBy', (string) $this->employee->id)
            ->set('stagingFuel', 'full')
            ->set('stagingKey', 'with_machine');

        // …while a colleague reassigns behind it
        $theirs = $this->makeEquipment([
            'assigned_product_id' => $row->product_id,
            'equipment_name' => 'Colleague Chosen Unit',
        ]);
        \App\Services\Equipment\EquipmentReassignmentService::switch(
            $row->fresh(['softAssignment.equipment', 'order']), $theirs, $this->employee, $this->admin,
        );

        $component->call('confirmStaging')->assertSee('assignment changed');

        // Their assignment survives; nothing partial recorded; the modal
        // refreshed to show the live assignment
        $this->assertEquals($theirs->id, $row->fresh()->softAssignment->equipment_id);
        $this->assertSame(0, QueueLineFuelVerification::count());
        $this->assertSame(0, QueueLineKeyConfirmation::count());
        $this->assertStringContainsString('Colleague Chosen Unit', $component->html());
    }

    public function test_a_repeated_submission_after_reassignment_is_idempotent(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row);
        $replacement = $this->makeCategoryUnit();

        $this->assignAndStageViaModal($row, $replacement);
        $staleBaseline = null; // double-click replays with a pre-change snapshot

        // Same physical outcome requested again → replayed, never duplicated
        $result = QueueLineStagingService::assignAndStage(
            orderProduct: $row->fresh(['softAssignment.equipment', 'order', 'queueLineItem']),
            baselineAssignmentId: $staleBaseline,
            target: $replacement,
            performedBy: $this->employee,
            actor: $this->admin,
            fuelFull: true,
            keyWithMachine: true,
        );

        $this->assertTrue($result['replayed']);
        $this->assertFalse($result['changed']);
        $this->assertSame(1, QueueLineFuelVerification::count());
        $this->assertSame(1, QueueLineKeyConfirmation::count());
        $this->assertSame(1, OrderHistory::where('action', 'equipment_reassigned')->count());
    }

    // ── Return to Pending preserves assignment ───────────────────────────

    public function test_return_to_pending_preserves_the_equipment_assignment(): void
    {
        $row = $this->makeRow(); // starts unassigned
        $unit = $this->makeCategoryUnit();
        $this->assignAndStageViaModal($row, $unit);

        Livewire::test(Board::class)
            ->call('openStagedStatus', $row->id)
            ->call('returnToPending', $row->id)
            ->assertSee('Returned to Pending');

        // Staging reversed — but the equipment stays assigned to the order
        $this->assertNull($row->fresh('queueLineItem')->queueLineItem->staged_at);
        $this->assertEquals($unit->id, $row->fresh()->softAssignment->equipment_id);
        $this->assertSame(1, EquipmentSoftAssign::where('order_product_id', $row->id)->count());
    }

    // ── Conflict philosophy + product-match rule parity ──────────────────

    public function test_conflict_capable_equipment_remains_selectable_and_is_reported_not_blocked(): void
    {
        // The replacement is already soft-assigned to ANOTHER overlapping rental
        $replacement = $this->makeCategoryUnit();
        $otherRow = $this->makeRow(null, ['pickup_date' => now()->addDays(3)->format('Y-m-d')]);
        $this->softAssign($otherRow, $replacement);

        $row = $this->makeRow(null, ['pickup_date' => now()->addDays(2)->format('Y-m-d')]);
        $this->softAssign($row);

        $component = $this->assignAndStageViaModal($row, $replacement);

        // Staged successfully AND the double booking is reported, not blocked
        $component->assertSee('scheduling conflict');
        $this->assertEquals($replacement->id, $row->fresh()->softAssignment->equipment_id);
        $this->assertNotNull($row->fresh('queueLineItem')->queueLineItem->staged_at);
    }

    public function test_a_non_direct_unit_requires_a_reason_and_logs_the_substitution(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row);
        $otherProduct = Product::create([
            'product_name' => 'Boom Lift 56ft', 'slug' => 'b56-' . uniqid(), 'product_type' => 'Rental',
        ]);
        $alternate = $this->makeCategoryUnit(['assigned_product_id' => $otherProduct->id]);

        // Without a reason the submit button never enables — force the call
        // to prove the server rule too
        $component = Livewire::test(Board::class)
            ->call('openStaging', $row->id)
            ->set('stagingMode', 'assign')
            ->set('stagingCategory', (string) $this->category->id)
            ->set('stagingEquipmentId', (string) $alternate->id)
            ->set('stagingPerformedBy', (string) $this->employee->id)
            ->set('stagingFuel', 'full')
            ->set('stagingKey', 'with_machine');

        $this->assertMatchesRegularExpression(
            '/data-staging-submit[^>]*disabled|disabled[^>]*data-staging-submit/s',
            $component->html(),
        );

        $component->call('confirmStaging')->assertSee('reason');
        $this->assertSame(0, QueueLineKeyConfirmation::count());

        // With a reason: allowed, recorded in the existing substitution structure
        $component->set('stagingReason', '40ft buried in back row')
            ->call('confirmStaging')
            ->assertSet('stagingItemId', null);

        $this->assertSame(1, EquipmentSubstitutionLog::count());
        $this->assertNotNull($row->fresh('queueLineItem')->queueLineItem->staged_at);
    }

    // ── Selector behavior ────────────────────────────────────────────────

    public function test_the_category_selection_narrows_the_equipment_options(): void
    {
        $row = $this->makeRow();
        $inCategory = $this->makeCategoryUnit(['equipment_name' => 'In Category Unit']);

        $otherCategory = ProductCategory::create(['title' => 'Skid Steers']);
        $this->makeEquipment([
            'equipment_name' => 'Other Category Unit',
            'product_category_id' => $otherCategory->id,
        ]);

        $component = Livewire::test(Board::class)
            ->call('openStaging', $row->id)
            ->set('stagingCategory', (string) $this->category->id);

        $html = $component->html();
        $this->assertStringContainsString('In Category Unit', $html);
        $this->assertStringNotContainsString('Other Category Unit', $html);

        // Both categories are offered, matching the canonical modal's list
        $this->assertStringContainsString('Boom Lifts', $html);
        $this->assertStringContainsString('Skid Steers', $html);
    }

    // ── Independence: normal assignment never requires Queue Line ────────

    public function test_normal_assignment_remains_independent_of_queue_line_staging(): void
    {
        // The Schedule Assignment writer — no staging, no Queue Line state
        $row = $this->makeRow();
        $unit = $this->makeCategoryUnit();

        $this->post(route('admin.order-management.schedules.assign-equipment'), [
            'order_product_unique_id' => $row->unique_id,
            'equipment_unique_id' => $unit->unique_id,
            'user_unique_id' => $this->admin->unique_id,
        ])->assertOk();

        $this->assertEquals($unit->id, $row->fresh()->softAssignment->equipment_id);
        $this->assertSame(0, QueueLineFuelVerification::count());
        $this->assertSame(0, QueueLineKeyConfirmation::count());
    }
}
