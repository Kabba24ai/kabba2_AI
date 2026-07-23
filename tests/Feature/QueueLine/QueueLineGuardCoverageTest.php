<?php

namespace Tests\Feature\QueueLine;

use App\Models\Orders\OrderProduct;
use App\Models\Orders\QueueLineFuelVerification;
use App\Services\QueueLine\QueueFuelVerificationService;
use App\Services\QueueLine\QueueLineService;

/**
 * Release enforcement is INFORMATIONAL, not restrictive (2026-07-23, approved
 * Fast Track mission). None of the status-transition controllers block a
 * Completed outbound delivery for lacking staging or fuel verification — an
 * item that leaves the yard without going through staging is a legitimate Fast
 * Track: it completes, lands in Equipment Delivered, and its skipped staging is
 * recorded (queue_line_items.staged_at stays null on the completion latch), but
 * it is never prevented.
 *
 * This suite pins that no release path returns 422 and that each canonical
 * delivery path records the completion. It replaces the former blocking
 * contract (QueueLineReleaseGuard used to return a structured 422 here).
 *
 * Assignment independence is still pinned by EquipmentAssignmentIndependenceTest.
 */
class QueueLineGuardCoverageTest extends QueueLineTestCase
{
    private function api(string $path): string
    {
        return 'http://' . config('app.domains.api') . '/api/admin/v1/' . ltrim($path, '/');
    }

    private function verifyFuel(OrderProduct $row): void
    {
        QueueFuelVerificationService::verify(
            orderProduct: $row->fresh(['softAssignment.equipment', 'order', 'queueLineItem']),
            expected: $row->softAssignment->equipment,
            performedBy: $this->admin,
            actor: $this->admin,
            source: QueueLineFuelVerification::SOURCE_WEB,
        );
    }

    /** Left the yard AND never went through staging = Fast Track. */
    private function assertFastTracked(OrderProduct $row): void
    {
        $item = $row->fresh('queueLineItem')->queueLineItem;
        $this->assertNotNull($item, 'expected a queue_line_items row');
        $this->assertNotNull($item->completed_at, 'expected a completion latch');
        $this->assertNull($item->staged_at, 'expected staging to have been skipped (Fast Track)');
    }

    // ── Admin web: Orders\AssignEquipmentController — NEVER guarded ─────

    public function test_admin_assign_equipment_is_never_guarded_by_queue_line(): void
    {
        $row = $this->makeRow();
        $unit = $this->softAssign($row);

        $response = $this->postJson(route('admin.order-management.orders.assign-equipment'), [
            'order_product_unique_id' => $row->unique_id,
            'equipment_unique_id' => $unit->unique_id,
            'schedule_type' => 'Delivery',
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $row->refresh();
        $this->assertSame('Completed', $row->delivery_status);
        $this->assertSame($unit->id, $row->equipment_id);
    }

    // ── Admin web: order edit schedule editor (UpdateProductScheduleController) ──

    public function test_manual_status_flip_to_completed_without_fuel_fast_tracks(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row);

        $this->putJson(
            route('admin.order-management.orders.update-product-schedule', [$row->order->unique_id, $row->unique_id]),
            ['type' => 'delivery', 'delivery_status' => 'Completed'],
        )->assertOk();

        $this->assertSame('Completed', $row->fresh()->delivery_status);
        $this->assertFastTracked($row);
    }

    public function test_manual_status_flip_with_fuel_verification_still_completes(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row);
        $this->verifyFuel($row);

        $this->putJson(
            route('admin.order-management.orders.update-product-schedule', [$row->order->unique_id, $row->unique_id]),
            ['type' => 'delivery', 'delivery_status' => 'Completed'],
        )->assertOk();

        $this->assertSame('Completed', $row->fresh()->delivery_status);
        $this->assertNotNull($row->fresh('queueLineItem')->queueLineItem?->completed_at);
    }

    public function test_close_as_completed_is_administrative_and_never_reaches_equipment_delivered(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row);

        $this->putJson(
            route('admin.order-management.orders.update-product-schedule', [$row->order->unique_id, $row->unique_id]),
            ['type' => 'delivery', 'delivery_status' => 'Close as Completed'],
        )->assertOk();

        $this->assertSame('Close as Completed', $row->fresh()->delivery_status);
        // Administrative closure (cancelled / never shipped) — NOT a yard departure.
        $this->assertNull($row->fresh('queueLineItem')->queueLineItem?->completed_at);
    }

    public function test_reschedule_removes_the_item_from_the_queue_and_requires_restaging(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row);
        QueueLineService::stage($row->fresh(['softAssignment.equipment']), $this->admin);

        $this->putJson(
            route('admin.order-management.orders.update-product-schedule', [$row->order->unique_id, $row->unique_id]),
            ['type' => 'delivery', 'delivery_status' => 'Reschedule'],
        )->assertOk();

        $this->assertSame('Reschedule', $row->fresh()->delivery_status);
        $item = $row->fresh('queueLineItem')->queueLineItem;
        $this->assertNull($item?->completed_at);
        $this->assertNull($item?->staged_at, 'reschedule must clear staging so it re-stages on return');
    }

    // ── Admin app API: Schedules\UpdateController ──

    public function test_api_schedule_update_to_completed_without_fuel_fast_tracks(): void
    {
        $this->actingAs($this->admin, 'api_user');
        $row = $this->makeRow();
        $this->softAssign($row);

        $this->postJson($this->api('orders/schedules/update'), [
            'order_product_unique_id' => $row->unique_id,
            'schedule_type' => 'Delivery',
            'schedule_status' => 'Completed',
        ])->assertOk();

        $this->assertSame('Completed', $row->fresh()->delivery_status);
        $this->assertFastTracked($row);
    }

    public function test_api_schedule_update_return_leg_completes(): void
    {
        $this->actingAs($this->admin, 'api_user');
        $row = $this->makeRow();
        $this->softAssign($row);

        $this->postJson($this->api('orders/schedules/update'), [
            'order_product_unique_id' => $row->unique_id,
            'schedule_type' => 'Return',
            'schedule_status' => 'Completed',
        ])->assertOk();

        $this->assertSame('Completed', $row->fresh()->pickup_status);
    }

    // ── Admin app API: Dispatch\UpdateStatusController ──

    public function test_dispatch_mark_completed_without_fuel_fast_tracks(): void
    {
        $this->actingAs($this->admin, 'api_user');
        $row = $this->makeRow();
        $this->softAssign($row);

        $this->postJson($this->api('dispatch/update-status'), [
            'order_product_unique_id' => $row->unique_id,
            'schedule_type' => 'Delivery',
            'schedule_status' => 'Completed',
        ])->assertOk();

        $this->assertSame('Completed', $row->fresh()->delivery_status);
        $this->assertFastTracked($row);
    }

    public function test_dispatch_unassigned_item_completes_without_equipment(): void
    {
        $this->actingAs($this->admin, 'api_user');
        $row = $this->makeRow(); // eligible, no machine selected

        $this->postJson($this->api('dispatch/update-status'), [
            'order_product_unique_id' => $row->unique_id,
            'schedule_type' => 'Delivery',
            'schedule_status' => 'Completed',
        ])->assertOk();

        $this->assertSame('Completed', $row->fresh()->delivery_status);
        $this->assertFastTracked($row);
    }

    // ── Admin app API: UpdateDeliveryPickupInputsController ──

    public function test_delivery_inputs_completion_without_fuel_fast_tracks(): void
    {
        $this->actingAs($this->admin, 'api_user');
        $row = $this->makeRow(null, ['delivery_by' => $this->admin->id]); // completion branch armed
        $this->softAssign($row);

        $this->postJson($this->api('orders/schedules/update-delivery-pickup-inputs'), [
            'order_product_unique_id' => $row->unique_id,
            'type' => 'delivery',
        ])->assertOk();

        $this->assertSame('Completed', $row->fresh()->delivery_status);
        $this->assertFastTracked($row);
    }

    public function test_delivery_inputs_replay_after_a_completion_flows_freely(): void
    {
        $this->actingAs($this->admin, 'api_user');
        $row = $this->makeRow(null, ['delivery_by' => $this->admin->id]);
        $unit = $this->softAssign($row);

        // Simulate the item having already left: completion latch set.
        QueueLineService::complete($row, QueueLineService::VIA_DISPATCH_STARTED, $unit->id);

        $this->postJson($this->api('orders/schedules/update-delivery-pickup-inputs'), [
            'order_product_unique_id' => $row->unique_id,
            'type' => 'delivery',
        ])->assertOk();

        $this->assertSame('Completed', $row->fresh()->delivery_status);
        $item = $row->fresh('queueLineItem')->queueLineItem;
        $this->assertSame(QueueLineService::VIA_DISPATCH_STARTED, $item->completed_via, 'replay must never move the original completion');
    }
}
