<?php

namespace Tests\Feature\QueueLine;

use App\Models\MaintenanceManagement\EquipmentSoftAssign;
use App\Models\Orders\OrderProduct;
use App\Models\Configurations\Setting;
use App\Services\AutoAssignDirectService;
use App\Services\QueueLine\QueueLineService;

/**
 * THE architectural boundary (regression audit 2026-07-20, approved rules):
 *
 *   Equipment assignment belongs to the ORDER workflow, not Queue Line.
 *   - An order may receive equipment whether or not it is on Queue Line.
 *   - An order may be on Queue Line whether or not equipment is assigned.
 *   - Queue Line may EXPOSE missing equipment; it must never block,
 *     require, or control assignment.
 *   - Auto-assignment operates independently of Queue Line.
 *
 * All four states are valid: (queue no/yes) × (equipment no/yes).
 *
 * Queue Line release enforcement still exists — but only on true release
 * paths (driver checklist, customer checklist, dispatch/schedule status
 * transitions), covered by QueueLineGuardCoverageTest. It must never be
 * a prerequisite for assignment.
 */
class EquipmentAssignmentIndependenceTest extends QueueLineTestCase
{
    private function assignPayload(OrderProduct $row, $equipment): array
    {
        return [
            'order_product_unique_id' => $row->unique_id,
            'equipment_unique_id' => $equipment->unique_id,
            'schedule_type' => 'Delivery',
        ];
    }

    // ── Manual assignment independence ──────────────────────────────────

    public function test_equipment_assigns_with_no_queue_line_involvement(): void
    {
        // Queue-ELIGIBLE item (Pending, due today, Truck) with no Queue Line
        // record and no soft assignment — the exact state of a fresh order.
        $row = $this->makeRow();
        $unit = $this->makeEquipment(['assigned_product_id' => $row->product_id]);

        $this->postJson(route('admin.order-management.orders.assign-equipment'), $this->assignPayload($row, $unit))
            ->assertOk()
            ->assertJson(['success' => true]);

        $row->refresh();
        $this->assertEquals($unit->id, $row->equipment_id);
        $this->assertSame('Completed', $row->delivery_status);
        $this->assertSame('rented', $unit->fresh()->current_status?->value);
    }

    public function test_equipment_assigns_when_the_item_is_on_queue_line(): void
    {
        // Item under active Queue Line management (rushed → sidecar row
        // exists) — assignment through the order workflow must still work.
        $row = $this->makeRow();
        QueueLineService::rush($row, $this->admin);
        $unit = $this->makeEquipment(['assigned_product_id' => $row->product_id]);

        $this->postJson(route('admin.order-management.orders.assign-equipment'), $this->assignPayload($row, $unit))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertEquals($unit->id, $row->fresh()->equipment_id);
    }

    public function test_equipment_assigns_without_fuel_verification(): void
    {
        // Soft-assigned via the canonical staging path, fuel NOT verified —
        // fuel verification is a Queue Line release rule, never an
        // assignment prerequisite.
        $row = $this->makeRow();
        $unit = $this->softAssign($row);

        $this->postJson(route('admin.order-management.orders.assign-equipment'), $this->assignPayload($row, $unit))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertEquals($unit->id, $row->fresh()->equipment_id);
    }

    public function test_a_unit_other_than_the_staged_one_can_be_assigned(): void
    {
        // Auto-assign (or the yard) staged unit A; the counter assigns unit
        // B through the order workflow. The order workflow OWNS assignment —
        // Queue Line observes the change, it never vetoes it.
        $row = $this->makeRow();
        $this->softAssign($row); // staged unit A
        $unitB = $this->makeEquipment(['assigned_product_id' => $row->product_id]);

        $this->postJson(route('admin.order-management.orders.assign-equipment'), $this->assignPayload($row, $unitB))
            ->assertOk()
            ->assertJson(['success' => true]);

        $row->refresh();
        $this->assertEquals($unitB->id, $row->equipment_id);
        // The stale soft assignment was replaced, not left dangling
        $this->assertNull($row->softAssignment);
    }

    public function test_unassigned_item_enters_the_queue_line_board(): void
    {
        // Queue Line membership never requires equipment: an unassigned item
        // appears on the board (as Needs Equipment) and can carry queue
        // state (RUSH) with no assignment.
        $row = $this->makeRow();

        $this->assertContains($row->id, $this->boardIds());

        QueueLineService::rush($row, $this->admin);
        $this->assertTrue($row->fresh()->queueLineItem->isRushed());
        $this->assertNull($row->fresh()->softAssignment);
    }

    public function test_queue_line_removal_neither_removes_nor_blocks_assignment(): void
    {
        $row = $this->makeRow();
        $unitA = $this->softAssign($row);

        // Remove Forever exits Queue Line management…
        QueueLineService::removeForever($row->fresh('queueLineItem'), $this->admin);

        // …the soft assignment survives untouched…
        $this->assertEquals($unitA->id, $row->fresh()->softAssignment->equipment_id);

        // …and the order workflow can still assign (a different unit, even).
        $unitB = $this->makeEquipment(['assigned_product_id' => $row->product_id]);
        $this->postJson(route('admin.order-management.orders.assign-equipment'), $this->assignPayload($row, $unitB))
            ->assertOk();
        $this->assertEquals($unitB->id, $row->fresh()->equipment_id);
    }

    public function test_queue_line_presents_assignment_state_without_controlling_it(): void
    {
        // Assignment made by the canonical schedule staging endpoint (no
        // Queue Line involvement) is OBSERVED by the board on next render.
        $row = $this->makeRow();
        $unit = $this->makeEquipment(['assigned_product_id' => $row->product_id]);

        EquipmentSoftAssign::create([
            'equipment_id' => $unit->id,
            'order_id' => $row->order_id,
            'order_product_id' => $row->id,
            'assigned_by' => $this->admin->id,
        ]);

        $html = \Livewire\Livewire::test(\App\Livewire\QueueLine\Board::class)->html();
        $this->assertStringContainsString($unit->equipment_name, $html);
        $this->assertStringContainsString('data-assignment="direct"', $html);
    }

    // ── Auto-assignment independence ─────────────────────────────────────

    private function enableAutoAssign(): void
    {
        Setting::create([
            'setting_type' => 'Schedule Assignment',
            'setting_name' => 'auto_assign_enabled',
            'setting_title' => 'Auto Assign All Orders',
            'value_type' => 'boolean',
            'setting_value' => '1',
        ]);
    }

    public function test_auto_assign_succeeds_without_queue_line_membership(): void
    {
        $row = $this->makeRow();
        $unit = $this->makeEquipment(['assigned_product_id' => $row->product_id]);

        $result = app(AutoAssignDirectService::class)->assignSingle($row);

        $this->assertSame('assigned', $result['status']);
        $this->assertEquals($unit->id, $row->fresh()->softAssignment->equipment_id);
        // No queue guard was a prerequisite: no fuel, no queue row, no staging
        $this->assertDatabaseCount('queue_line_items', 0);
    }

    public function test_auto_assign_succeeds_with_queue_line_membership(): void
    {
        $row = $this->makeRow();
        QueueLineService::rush($row, $this->admin); // queue row exists
        $unit = $this->makeEquipment(['assigned_product_id' => $row->product_id]);

        $result = app(AutoAssignDirectService::class)->assignSingle($row->fresh('queueLineItem'));

        $this->assertSame('assigned', $result['status']);
        $this->assertEquals($unit->id, $row->fresh()->softAssignment->equipment_id);
    }

    public function test_auto_assign_result_is_identical_regardless_of_queue_membership(): void
    {
        // Two identical items, identical candidate pools — one on Queue Line,
        // one not. Queue Line presence must not change candidate selection.
        $plain = $this->makeRow();
        $queued = $this->makeRow();
        QueueLineService::rush($queued, $this->admin);

        // One dedicated direct-assignment unit per product keeps the pools
        // identical and independent.
        $productB = \App\Models\ProductManagement\Product::create([
            'product_name' => 'Independence Skid', 'slug' => 'ind-skid-' . uniqid(), 'product_type' => 'Rental',
        ]);
        $queued->update(['product_id' => $productB->id, 'product_name' => $productB->product_name]);

        $unitPlain = $this->makeEquipment(['assigned_product_id' => $plain->product_id]);
        $unitQueued = $this->makeEquipment(['assigned_product_id' => $productB->id]);

        $resultPlain = app(AutoAssignDirectService::class)->assignSingle($plain);
        $resultQueued = app(AutoAssignDirectService::class)->assignSingle($queued->fresh('queueLineItem'));

        $this->assertSame('assigned', $resultPlain['status']);
        $this->assertSame('assigned', $resultQueued['status']);
        $this->assertSame($resultPlain['priority'], $resultQueued['priority']);
        $this->assertEquals($unitPlain->id, $plain->fresh()->softAssignment->equipment_id);
        $this->assertEquals($unitQueued->id, $queued->fresh()->softAssignment->equipment_id);
    }

    public function test_observer_auto_assigns_on_creation_without_any_queue_records(): void
    {
        $this->enableAutoAssign();

        $unit = $this->makeEquipment(['assigned_product_id' => $this->orderedProduct->id]);

        // makeRow() → OrderProduct::create → observer fires (no bulk insert)
        $row = $this->makeRow();

        $this->assertNotNull($row->fresh()->softAssignment);
        $this->assertEquals($unit->id, $row->fresh()->softAssignment->equipment_id);
        $this->assertDatabaseCount('queue_line_items', 0);
    }

    public function test_auto_assigned_item_can_be_completed_through_the_order_workflow(): void
    {
        // End-to-end: auto-assign stages unit A, the counter completes the
        // order with unit A through the order workflow — no fuel
        // verification, no Queue Line action required.
        $this->enableAutoAssign();
        $unit = $this->makeEquipment(['assigned_product_id' => $this->orderedProduct->id]);
        $row = $this->makeRow(); // observer auto-assigns $unit

        $this->assertNotNull($row->fresh()->softAssignment);

        $this->postJson(route('admin.order-management.orders.assign-equipment'), $this->assignPayload($row, $unit))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertEquals($unit->id, $row->fresh()->equipment_id);
    }
}
