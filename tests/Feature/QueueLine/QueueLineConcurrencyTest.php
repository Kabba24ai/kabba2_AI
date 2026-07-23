<?php

namespace Tests\Feature\QueueLine;

use App\Models\Orders\QueueLineFuelVerification;
use App\Services\Equipment\EquipmentReassignmentService;
use App\Services\QueueLine\QueueFuelVerificationService;
use App\Services\QueueLine\QueueLineOperationException;
use App\Services\QueueLine\QueueLineService;

/**
 * Phase 4 §10 — interleaved-action safety. True parallel execution is not
 * reproducible in-process, so each test pins the OUTCOME of an interleaving
 * (the canonical invariant: one current assignment, one coherent fuel state,
 * one meaningful completion latch). Row-level serialization of the fuel
 * ledger itself comes from lockForUpdate inside QueueFuelVerificationService.
 */
class QueueLineConcurrencyTest extends QueueLineTestCase
{
    public function test_two_users_switching_the_same_item_leave_one_canonical_assignment(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row);
        $unitX = $this->makeEquipment(['assigned_product_id' => $row->product_id]);
        $unitY = $this->makeEquipment(['assigned_product_id' => $row->product_id]);

        // User A and user B act on the same stale screen, milliseconds apart.
        EquipmentReassignmentService::switch($row->fresh(['softAssignment.equipment', 'order']), $unitX, $this->admin, $this->admin);
        EquipmentReassignmentService::switch($row->fresh(['softAssignment.equipment', 'order']), $unitY, $this->admin, $this->admin);

        $row->refresh();
        $this->assertSame($unitY->id, $row->softAssignment->equipment_id, 'last write wins');
        $this->assertSame(1, \App\Models\MaintenanceManagement\EquipmentSoftAssign::where('order_product_id', $row->id)->count(), 'never two live episodes');

        // BOTH swaps remain in the audit trail.
        $switchAudits = $row->order->history()
            ->where('action', \App\Enums\Orders\OrderHistoryAction::EquipmentReassigned)
            ->count();
        $this->assertSame(2, $switchAudits);
    }

    public function test_same_unit_staged_onto_two_items_is_allowed_and_never_blocks(): void
    {
        $rowA = $this->makeRow();
        $rowB = $this->makeRow();
        $this->softAssign($rowA);
        $this->softAssign($rowB);
        $shared = $this->makeEquipment(['assigned_product_id' => $rowA->product_id]);

        EquipmentReassignmentService::switch($rowA->fresh(['softAssignment.equipment', 'order']), $shared, $this->admin, $this->admin);
        EquipmentReassignmentService::switch($rowB->fresh(['softAssignment.equipment', 'order']), $shared, $this->admin, $this->admin);

        // Both technicians proceed; the overlap belongs to the existing
        // Schedule Conflicts workflow, never to a yard block.
        $this->assertSame($shared->id, $rowA->fresh()->softAssignment->equipment_id);
        $this->assertSame($shared->id, $rowB->fresh()->softAssignment->equipment_id);
    }

    public function test_verification_submitted_after_a_concurrent_switch_is_rejected(): void
    {
        $row = $this->makeRow();
        $original = $this->softAssign($row);
        $replacement = $this->makeEquipment(['assigned_product_id' => $row->product_id]);

        // The verify screen was opened showing $original; the switch lands first.
        EquipmentReassignmentService::switch($row->fresh(['softAssignment.equipment', 'order']), $replacement, $this->admin, $this->admin);

        try {
            QueueFuelVerificationService::verify(
                orderProduct: $row->fresh(['softAssignment.equipment', 'order', 'queueLineItem']),
                expected: $original,
                performedBy: $this->admin,
                actor: $this->admin,
                source: QueueLineFuelVerification::SOURCE_WEB,
            );
            $this->fail('stale verification must be rejected');
        } catch (QueueLineOperationException $e) {
            $this->assertSame('QUEUE_ASSIGNMENT_CHANGED', $e->errorCode);
        }

        $this->assertSame(0, QueueLineFuelVerification::where('order_product_id', $row->id)->count());
    }

    public function test_release_arriving_after_a_concurrent_reversal_still_completes_informationally(): void
    {
        // Staging is informational (2026-07-23): a fuel reversal landing while
        // the dispatch screen still showed verified no longer BLOCKS the
        // release — it proceeds and completes (the invalidated verification is
        // recorded, not enforced).
        $this->actingAs($this->admin, 'api_user');
        $row = $this->makeRow();
        $unit = $this->softAssign($row);

        $result = QueueFuelVerificationService::verify(
            orderProduct: $row->fresh(['softAssignment.equipment', 'order', 'queueLineItem']),
            expected: $unit,
            performedBy: $this->admin,
            actor: $this->admin,
            source: QueueLineFuelVerification::SOURCE_WEB,
        );

        QueueFuelVerificationService::reverse(
            verification: $result['verification'],
            performedBy: $this->admin,
            actor: $this->admin,
            reason: 'wrong machine signed off',
            source: QueueLineFuelVerification::SOURCE_WEB,
        );

        $this->postJson('http://' . config('app.domains.api') . '/api/admin/v1/dispatch/update-status', [
            'order_product_unique_id' => $row->unique_id,
            'schedule_type' => 'Delivery',
            'schedule_status' => 'Completed',
        ])->assertOk();

        $this->assertSame('Completed', $row->fresh()->delivery_status);
        $this->assertNotNull($row->fresh('queueLineItem')->queueLineItem?->completed_at);
    }

    public function test_double_completion_keeps_the_first_latch_untouched(): void
    {
        $row = $this->makeRow();
        $unit = $this->softAssign($row);

        QueueLineService::complete($row, QueueLineService::VIA_DISPATCH_STARTED, $unit->id);
        $first = $row->queueLineItem()->first();

        // The defensive second check (customer checklist sweep) fires later.
        QueueLineService::complete($row->fresh(), QueueLineService::VIA_CUSTOMER_CHECKLIST_COMPLETED, $unit->id);

        $second = $row->queueLineItem()->first();
        $this->assertSame(QueueLineService::VIA_DISPATCH_STARTED, $second->completed_via);
        $this->assertTrue($first->completed_at->equalTo($second->completed_at), 'the original stamp never moves');
    }

    public function test_reopen_immediately_after_completion_preserves_fuel_history(): void
    {
        $row = $this->makeRow();
        $unit = $this->softAssign($row);

        QueueFuelVerificationService::verify(
            orderProduct: $row->fresh(['softAssignment.equipment', 'order', 'queueLineItem']),
            expected: $unit,
            performedBy: $this->admin,
            actor: $this->admin,
            source: QueueLineFuelVerification::SOURCE_WEB,
        );

        QueueLineService::complete($row->fresh(), QueueLineService::VIA_CUSTOMER_CHECKLIST_COMPLETED, $unit->id);
        QueueLineService::reopen($row->fresh());

        $item = $row->queueLineItem()->first();
        $this->assertNull($item->completed_at);
        $this->assertNull($item->completed_via);
        $this->assertSame(1, QueueLineFuelVerification::where('order_product_id', $row->id)->count(), 'the append-only ledger survives reopen');
    }

    public function test_simultaneous_verifications_replay_instead_of_stacking(): void
    {
        $row = $this->makeRow();
        $unit = $this->softAssign($row);
        $fresh = fn () => $row->fresh(['softAssignment.equipment', 'order', 'queueLineItem']);

        $a = QueueFuelVerificationService::verify($fresh(), $unit, $this->admin, $this->admin, QueueLineFuelVerification::SOURCE_WEB);
        $b = QueueFuelVerificationService::verify($fresh(), $unit, $this->admin, $this->admin, QueueLineFuelVerification::SOURCE_WALL);

        $this->assertFalse($a['replayed']);
        $this->assertTrue($b['replayed'], 'second sign-off replays the existing current verification');
        $this->assertSame($a['verification']->id, $b['verification']->id);
        $this->assertSame(1, QueueLineFuelVerification::where('order_product_id', $row->id)->count());
    }

    public function test_verify_reverse_verify_interleaving_appends_and_derives_one_current_state(): void
    {
        $row = $this->makeRow();
        $unit = $this->softAssign($row);
        $fresh = fn () => $row->fresh(['softAssignment.equipment', 'order', 'queueLineItem']);

        $first = QueueFuelVerificationService::verify($fresh(), $unit, $this->admin, $this->admin, QueueLineFuelVerification::SOURCE_WEB);
        QueueFuelVerificationService::reverse($first['verification'], $this->admin, $this->admin, 'not actually full', QueueLineFuelVerification::SOURCE_WEB);
        $third = QueueFuelVerificationService::verify($fresh(), $unit, $this->admin, $this->admin, QueueLineFuelVerification::SOURCE_WEB);

        $this->assertFalse($third['replayed'], 'a fresh verification after reversal is a NEW event');
        $this->assertSame(3, QueueLineFuelVerification::where('order_product_id', $row->id)->count(), 'append-only: verify + reverse + verify');

        $current = QueueFuelVerificationService::currentVerification($fresh());
        $this->assertNotNull($current);
        $this->assertSame($third['verification']->id, $current->id);
    }
}
