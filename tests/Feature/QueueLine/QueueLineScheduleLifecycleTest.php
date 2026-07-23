<?php

namespace Tests\Feature\QueueLine;

use App\Services\QueueLine\QueueLineService;

/**
 * Schedule-driven Queue Line reconciliation (2026-07-23 enhancement,
 * SyncOnScheduleUpdate). "Equipment Delivered" follows the order's canonical
 * delivery status across every administrative path:
 *   • Pending → Completed ............. lands in Equipment Delivered (Fast
 *       Track when it never went through staging).
 *   • Completed → Pending ............. returns to Queue Line — Pending and
 *       must be fully re-staged, as though it never left the yard.
 *   • Pending → Reschedule ............ removed from the board entirely.
 *   • Reschedule → Pending ............ back on the board, unstaged.
 * An en-route truck (completed via dispatch) is never disturbed by unrelated
 * schedule edits on the same order.
 */
class QueueLineScheduleLifecycleTest extends QueueLineTestCase
{
    private function scheduleUpdate($row, array $payload): \Illuminate\Testing\TestResponse
    {
        return $this->putJson(
            route('admin.order-management.orders.update-product-schedule', [$row->order->unique_id, $row->unique_id]),
            $payload,
        );
    }

    private function driverStatus($row, string $status): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin, 'api_user')->postJson(
            'http://' . config('app.domains.api') . '/api/admin/v1/orders/schedules/driver-checklist',
            ['order_product_unique_id' => $row->unique_id, 'checklist_type' => 'delivery', 'equipment_driver_status' => $status],
        );
    }

    public function test_admin_completion_of_a_never_staged_item_fast_tracks_onto_equipment_delivered(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row);

        $this->scheduleUpdate($row, ['type' => 'delivery', 'delivery_status' => 'Completed'])->assertOk();

        $item = $row->fresh('queueLineItem')->queueLineItem;
        $this->assertNotNull($item->completed_at);
        $this->assertSame(QueueLineService::VIA_SCHEDULE_COMPLETED, $item->completed_via);
        $this->assertNull($item->staged_at, 'never staged → Fast Track');
        $this->assertNotContains($row->id, $this->boardIds(), 'off the active board');
    }

    public function test_completed_then_pending_returns_to_pending_and_requires_restaging(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row);
        QueueLineService::stage($row->fresh(['softAssignment.equipment']), $this->admin);

        // Deliver (admin), then reverse to Pending.
        $this->scheduleUpdate($row, ['type' => 'delivery', 'delivery_status' => 'Completed'])->assertOk();
        $this->assertNotNull($row->fresh('queueLineItem')->queueLineItem->completed_at);

        $this->scheduleUpdate($row, ['type' => 'delivery', 'delivery_status' => 'Pending'])->assertOk();

        $item = $row->fresh('queueLineItem')->queueLineItem;
        $this->assertNull($item->completed_at, 'completion latch cleared');
        $this->assertNull($item->staged_at, 'must be re-staged');
        $this->assertContains($row->id, $this->boardIds(), 'back on Queue Line — Pending');
    }

    public function test_reschedule_removes_from_board_then_returns_to_pending_unstaged(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row);
        QueueLineService::stage($row->fresh(['softAssignment.equipment']), $this->admin);

        // Reschedule → gone from the board completely.
        $this->scheduleUpdate($row, ['type' => 'delivery', 'delivery_status' => 'Reschedule'])->assertOk();
        $this->assertSame('Reschedule', $row->fresh()->delivery_status);
        $this->assertNotContains($row->id, $this->boardIds());
        $this->assertNull($row->fresh('queueLineItem')->queueLineItem->staged_at);

        // Assigning a new date auto-clears Reschedule → Pending → back on the board.
        $this->scheduleUpdate($row, ['type' => 'delivery', 'delivery_date' => now()->format('Y-m-d')])->assertOk();
        $this->assertSame('Pending', $row->fresh()->delivery_status);
        $this->assertContains($row->id, $this->boardIds());
        $this->assertNull($row->fresh('queueLineItem')->queueLineItem->staged_at, 'still needs staging');
    }

    public function test_completed_to_pending_to_completed_round_trip(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row);

        $this->scheduleUpdate($row, ['type' => 'delivery', 'delivery_status' => 'Completed'])->assertOk();
        $this->assertNotNull($row->fresh('queueLineItem')->queueLineItem->completed_at);

        $this->scheduleUpdate($row, ['type' => 'delivery', 'delivery_status' => 'Pending'])->assertOk();
        $this->assertNull($row->fresh('queueLineItem')->queueLineItem->completed_at);

        $this->scheduleUpdate($row, ['type' => 'delivery', 'delivery_status' => 'Completed'])->assertOk();
        $this->assertNotNull($row->fresh('queueLineItem')->queueLineItem->completed_at);
    }

    public function test_en_route_truck_is_not_reopened_by_an_unrelated_edit_on_the_same_order(): void
    {
        $order = $this->makeOrder();
        $truck = $this->makeRow($order);
        $sibling = $this->makeRow($order);
        $this->softAssign($truck);
        $this->softAssign($sibling);

        // Truck departs (On My Way): completed via dispatch, delivery_status stays Pending.
        $this->driverStatus($truck, 'On My Way')->assertOk();
        $completedAt = $truck->fresh('queueLineItem')->queueLineItem->completed_at;
        $this->assertNotNull($completedAt);
        $this->assertSame('Pending', $truck->fresh()->delivery_status, 'en route, not yet Completed');

        // An unrelated edit on the SIBLING fires OrderProductScheduleUpdated for
        // the whole order — the en-route truck must NOT be re-queued.
        $this->scheduleUpdate($sibling, ['type' => 'delivery', 'delivery_time' => '14:30'])->assertOk();

        $item = $truck->fresh('queueLineItem')->queueLineItem;
        $this->assertNotNull($item->completed_at, 'en-route truck still completed');
        $this->assertTrue($item->completed_at->equalTo($completedAt), 'latch untouched');
    }
}
