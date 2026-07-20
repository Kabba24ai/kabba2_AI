<?php

namespace Tests\Feature\QueueLine;

use App\Livewire\QueueLine\Board;
use App\Models\MaintenanceManagement\EquipmentSoftAssign;
use App\Models\MaintenanceManagement\EquipmentSubstitutionLog;
use App\Models\Orders\OrderHistory;
use App\Models\ProductManagement\Product;
use App\Services\Equipment\EquipmentReassignmentService;
use Livewire\Livewire;

/**
 * Phase 3A — canonical "Switch Equipment": the yard swaps the reserved unit
 * for the one actually being staged. One canonical soft assignment, every
 * surface reads it live, conflicts never block, full dual-attribution audit.
 */
class QueueLineSwitchEquipmentTest extends QueueLineTestCase
{
    private function switch($row, $replacement, array $overrides = []): array
    {
        return EquipmentReassignmentService::switch(
            orderProduct: $row->fresh(['softAssignment.equipment', 'order']),
            replacement: $replacement,
            performedBy: $overrides['performedBy'] ?? $this->employee ?? $this->admin,
            actor: $this->admin,
            source: $overrides['source'] ?? EquipmentReassignmentService::SOURCE_WEB,
            reason: $overrides['reason'] ?? null,
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->employee = \App\Models\Iam\Personnel\User::create([
            'first_name' => 'Yard', 'last_name' => 'Tech',
            'email' => 'yard-tech@test.local', 'status' => 'Active',
        ]);
    }

    protected \App\Models\Iam\Personnel\User $employee;

    // ── Canonical replacement ────────────────────────────────────────────

    public function test_same_product_switch_replaces_the_canonical_assignment(): void
    {
        $row = $this->makeRow();
        $original = $this->softAssign($row); // direct unit ABC
        $replacement = $this->makeEquipment(['assigned_product_id' => $row->product_id]); // XYZ

        $result = $this->switch($row, $replacement);

        $this->assertTrue($result['changed']);
        $this->assertSame('direct', $result['classification']);

        // Exactly ONE live soft assignment, pointing at the replacement
        $live = EquipmentSoftAssign::where('order_product_id', $row->id)->get();
        $this->assertCount(1, $live);
        $this->assertEquals($replacement->id, $live->first()->equipment_id);
        $this->assertEquals($this->employee->id, $live->first()->assigned_by);
        // The old row is soft-deleted history, not duplicated state
        $this->assertSame(2, EquipmentSoftAssign::withTrashed()->where('order_product_id', $row->id)->count());
    }

    public function test_every_surface_reads_the_replacement_live(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row);
        $replacement = $this->makeEquipment([
            'assigned_product_id' => $row->product_id,
            'equipment_name' => 'Replacement Unit XYZ',
        ]);

        $this->switch($row, $replacement);

        // Queue Line board
        Livewire::test(Board::class)->assertSee('Replacement Unit XYZ');

        // Dispatch page (soft-equipment chip / fallback name)
        $dispatchHtml = $this->get(
            route('admin.order-management.dispatch.index'),
            ['X-Requested-With' => 'XMLHttpRequest']
        )->assertOk()->json('html');
        $this->assertStringContainsString('Replacement Unit XYZ', $dispatchHtml);

        // Schedule page row still lists the item (store resolved via live relation)
        $scheduleHtml = $this->get(
            route('admin.order-management.schedules.index', ['schedule_type' => ['Delivery']]),
            ['X-Requested-With' => 'XMLHttpRequest']
        )->assertOk()->json('html');
        $this->assertStringContainsString($row->order->order_number, $scheduleHtml);

        // Customer Checklist mobile resource: equipment_details falls back to
        // the LIVE softEquipment when no hard snapshot exists
        $resource = \App\Http\Resources\Api\Admin\V1\OrderProducts\ListResource::make(
            $row->fresh(['softEquipment', 'equipment'])
        )->resolve();
        $this->assertStringContainsString(
            'Replacement Unit XYZ',
            json_encode($resource['equipment_details']),
        );
    }

    // ── Audit ────────────────────────────────────────────────────────────

    public function test_switch_writes_a_complete_dual_attribution_audit(): void
    {
        $row = $this->makeRow();
        $original = $this->softAssign($row);
        $replacement = $this->makeEquipment(['assigned_product_id' => $row->product_id]);

        $this->switch($row, $replacement);

        $entry = OrderHistory::where('order_id', $row->order_id)
            ->where('action', 'equipment_reassigned')->firstOrFail();

        $this->assertEquals($this->admin->id, $entry->user_id);
        $this->assertStringContainsString($original->equipment_name, $entry->description);
        $this->assertStringContainsString($replacement->equipment_name, $entry->description);
        $this->assertStringContainsString('Yard Tech', $entry->description);

        $extras = json_decode($entry->extras, true);
        $this->assertEquals($original->id, $extras['previous_equipment_id']);
        $this->assertEquals($replacement->id, $extras['replacement_equipment_id']);
        $this->assertEquals($row->product_id, $extras['ordered_product_id']);
        $this->assertSame('direct', $extras['classification']);
        $this->assertEquals($this->employee->id, $extras['performed_by_id']);
        $this->assertEquals($this->admin->id, $extras['authenticated_user_id']);
        $this->assertSame('web', $extras['source']);
    }

    // ── Product matching ─────────────────────────────────────────────────

    public function test_alternate_switch_requires_a_reason_and_logs_a_substitution(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row);
        $otherProduct = Product::create([
            'product_name' => 'Boom Lift 56ft', 'slug' => 'b56-' . uniqid(), 'product_type' => 'Rental',
        ]);
        $alternate = $this->makeEquipment(['assigned_product_id' => $otherProduct->id]);

        // Without a reason: rejected
        try {
            $this->switch($row, $alternate);
            $this->fail('Alternate switch without a reason should be rejected.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('reason', $e->getMessage());
        }
        $this->assertSame(0, EquipmentSubstitutionLog::count());

        // With a reason: allowed, recorded in the EXISTING substitution structure
        $result = $this->switch($row, $alternate, ['reason' => '40ft buried in back row']);

        $this->assertSame('alternate', $result['classification']);
        $log = EquipmentSubstitutionLog::firstOrFail();
        $this->assertEquals($row->id, $log->order_product_id);
        $this->assertEquals($alternate->id, $log->substitute_equipment_id);
        $this->assertEquals($this->admin->id, $log->evaluated_by);
        $this->assertSame('queue_line_switch', $log->evaluation_context['source']);
        $this->assertSame('40ft buried in back row', $log->evaluation_context['reason']);
    }

    public function test_unknown_mapping_is_allowed_with_reason_but_never_logged_as_substitution(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row);
        $unknown = $this->makeEquipment(['assigned_product_id' => null]);

        $result = $this->switch($row, $unknown, ['reason' => 'only unit on the lot']);

        $this->assertSame('unknown', $result['classification']);
        $this->assertSame(0, EquipmentSubstitutionLog::count()); // not a substitution — mapping unknown
        $extras = json_decode(OrderHistory::where('action', 'equipment_reassigned')->firstOrFail()->extras, true);
        $this->assertSame('unknown', $extras['classification']);
        $this->assertNull($extras['replacement_product_id']);
    }

    public function test_direct_switch_never_writes_a_substitution_log(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row);
        $this->switch($row, $this->makeEquipment(['assigned_product_id' => $row->product_id]));

        $this->assertSame(0, EquipmentSubstitutionLog::count());
    }

    // ── Conflict philosophy ──────────────────────────────────────────────

    public function test_conflicting_switch_is_allowed_and_flows_to_the_existing_workflow(): void
    {
        // The replacement is already soft-assigned to ANOTHER overlapping rental
        $replacement = $this->makeEquipment(['assigned_product_id' => $this->orderedProduct->id]);
        $otherRow = $this->makeRow(null, [
            'pickup_date' => now()->addDays(3)->format('Y-m-d'),
        ]);
        $this->softAssign($otherRow, $replacement);

        $row = $this->makeRow(null, [
            'pickup_date' => now()->addDays(2)->format('Y-m-d'),
        ]);
        $this->softAssign($row);

        $result = $this->switch($row, $replacement);

        // Allowed — technician is never blocked
        $this->assertTrue($result['changed']);
        // Existing detection reports the overlap
        $this->assertGreaterThan(0, $result['conflicts']->count());
        // …and the existing Schedule Conflicts page shows the double booking
        // automatically (computed live — no trigger, no second queue)
        $conflictsHtml = $this->get(route('admin.order-management.schedule-conflicts.index', ['section' => 'double_bookings']))
            ->assertOk()->getContent();
        $this->assertStringContainsString($replacement->equipment_name, $conflictsHtml);
    }

    // ── Validation ───────────────────────────────────────────────────────

    public function test_physically_rented_units_are_rejected(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row);
        $rented = $this->makeEquipment([
            'assigned_product_id' => $row->product_id, 'current_status' => 'rented',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/rented/');
        $this->switch($row, $rented);
    }

    public function test_delivered_items_cannot_be_switched(): void
    {
        $row = $this->makeRow(null, ['delivery_status' => 'Completed', 'is_delivered' => true]);
        $unit = $this->makeEquipment();
        $row->update(['equipment_id' => $unit->id]); // hard-assigned

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/already been delivered/');
        $this->switch($row, $this->makeEquipment(['assigned_product_id' => $row->product_id]));
    }

    public function test_maintenance_and_damaged_units_are_allowed(): void
    {
        // Rental Ready / condition never blocks staging (approved rule)
        $row = $this->makeRow();
        $this->softAssign($row);
        $holdUnit = $this->makeEquipment([
            'assigned_product_id' => $row->product_id, 'current_status' => 'maintenance',
        ]);

        $result = $this->switch($row, $holdUnit);

        $this->assertTrue($result['changed']);
    }

    // ── Idempotency / repeated submissions ───────────────────────────────

    public function test_switching_to_the_same_unit_is_a_silent_no_op(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row);
        $replacement = $this->makeEquipment(['assigned_product_id' => $row->product_id]);

        $first = $this->switch($row, $replacement);
        $second = $this->switch($row, $replacement); // double-click / repeat

        $this->assertTrue($first['changed']);
        $this->assertFalse($second['changed']);
        $this->assertSame(1, EquipmentSoftAssign::where('order_product_id', $row->id)->count());
        $this->assertSame(1, OrderHistory::where('action', 'equipment_reassigned')->count());
    }

    public function test_assigning_via_switch_works_for_unassigned_items(): void
    {
        $row = $this->makeRow(); // Needs Equipment Assignment
        $unit = $this->makeEquipment(['assigned_product_id' => $row->product_id]);

        $result = $this->switch($row, $unit);

        $this->assertTrue($result['changed']);
        $this->assertNull($result['previous']);
        $this->assertStringContainsString('from unassigned', OrderHistory::where('action', 'equipment_reassigned')->firstOrFail()->description);
        $this->assertEquals($unit->id, $row->fresh()->softAssignment->equipment_id);
    }

    // ── Livewire workflow ────────────────────────────────────────────────

    public function test_switch_modal_workflow_through_the_component(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row);
        $replacement = $this->makeEquipment([
            'assigned_product_id' => $row->product_id,
            'equipment_name' => 'Scanner Unit 900',
            'equipment_id' => 'SCAN-900',
        ]);

        $component = Livewire::test(Board::class)
            ->call('openSwitch', $row->id)
            ->assertSee('Confirm / Update Equipment') // UI Iteration 1 wording — same canonical switch behavior
            ->assertSee('Order #' . $row->order->order_number)
            // barcode-style exact search
            ->set('switchSearch', 'SCAN-900')
            ->assertSee('Scanner Unit 900')
            ->assertSee('Direct');

        // Confirm without selecting yourself → held in the modal with an error
        $component->call('confirmSwitch', $replacement->id)
            ->assertSee('Select yourself');
        $this->assertNotEquals($replacement->id, $row->fresh()->softAssignment->equipment_id);

        // With the employee selected → canonical switch + notice, modal closed
        $component->set('switchPerformedBy', (string) $this->employee->id)
            ->call('confirmSwitch', $replacement->id)
            ->assertSet('switchingItemId', null)
            ->assertSee('Equipment switched to Scanner Unit 900')
            ->assertSee('Scanner Unit 900'); // the card now shows the new unit

        $this->assertEquals($replacement->id, $row->fresh()->softAssignment->equipment_id);
    }

    public function test_component_switch_preserves_the_store_filter(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row);
        $replacement = $this->makeEquipment(['assigned_product_id' => $row->product_id]);

        Livewire::test(Board::class)
            ->set('store', (string) $this->storeNorth->id)
            ->call('openSwitch', $row->id)
            ->set('switchPerformedBy', (string) $this->employee->id)
            ->call('confirmSwitch', $replacement->id)
            ->assertSet('store', (string) $this->storeNorth->id);
    }

    public function test_rented_candidates_are_excluded_from_the_picker(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row);
        $this->makeEquipment([
            'assigned_product_id' => $row->product_id,
            'equipment_name' => 'Rented Out Unit', 'current_status' => 'rented',
        ]);

        Livewire::test(Board::class)
            ->call('openSwitch', $row->id)
            ->assertDontSee('Rented Out Unit');
    }
}
