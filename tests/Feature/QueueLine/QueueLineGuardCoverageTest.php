<?php

namespace Tests\Feature\QueueLine;

use App\Models\Orders\OrderProduct;
use App\Models\Orders\QueueLineFuelVerification;
use App\Services\QueueLine\QueueFuelVerificationService;
use App\Services\QueueLine\QueueLineService;

/**
 * Phase 4 §2 (amended by the 2026-07-20 regression audit) — the status-
 * transition controllers that record a Completed outbound delivery obey the
 * SAME canonical QueueLineReleaseGuard as the two checklist release paths.
 * Administrative statuses (Close as Completed, Reschedule, Pending) and
 * return legs are never guarded, and non-queue-managed items pass through
 * untouched.
 *
 * EXPLICITLY UNGUARDED: Orders\AssignEquipmentController. Equipment
 * assignment belongs to the ORDER workflow — Queue Line must never block,
 * require, or control it (approved architectural boundary). That inverse
 * contract is pinned by EquipmentAssignmentIndependenceTest.
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

    // ── Admin web: Orders\AssignEquipmentController — NEVER guarded ─────
    // (Assignment independence itself is pinned in depth by
    //  EquipmentAssignmentIndependenceTest; this test documents the guard's
    //  scope boundary from the guard suite's side.)

    public function test_admin_assign_equipment_is_never_guarded_by_queue_line(): void
    {
        // Queue-managed item, fuel NOT verified — the assignment workflow
        // still completes. Fuel verification is a release rule for the
        // checklist/dispatch paths only.
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

    public function test_manual_status_flip_to_completed_is_blocked_without_fuel(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row);

        $response = $this->putJson(
            route('admin.order-management.orders.update-product-schedule', [$row->order->unique_id, $row->unique_id]),
            ['type' => 'delivery', 'delivery_status' => 'Completed'],
        );

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'QUEUE_FUEL_VERIFICATION_REQUIRED');

        $this->assertSame('Pending', $row->fresh()->delivery_status);
    }

    public function test_manual_status_flip_passes_with_current_fuel_verification(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row);
        $this->verifyFuel($row);

        $this->putJson(
            route('admin.order-management.orders.update-product-schedule', [$row->order->unique_id, $row->unique_id]),
            ['type' => 'delivery', 'delivery_status' => 'Completed'],
        )->assertOk();

        $this->assertSame('Completed', $row->fresh()->delivery_status);
    }

    public function test_close_as_completed_remains_administrative_and_unguarded(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row); // staged, no fuel — administrative closure must still work

        $this->putJson(
            route('admin.order-management.orders.update-product-schedule', [$row->order->unique_id, $row->unique_id]),
            ['type' => 'delivery', 'delivery_status' => 'Close as Completed'],
        )->assertOk();

        $this->assertSame('Close as Completed', $row->fresh()->delivery_status);
    }

    public function test_reschedule_and_pending_remain_unguarded(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row);

        $this->putJson(
            route('admin.order-management.orders.update-product-schedule', [$row->order->unique_id, $row->unique_id]),
            ['type' => 'delivery', 'delivery_status' => 'Reschedule'],
        )->assertOk();

        $this->assertSame('Reschedule', $row->fresh()->delivery_status);
    }

    // ── Admin app API: Schedules\UpdateController ──

    public function test_api_schedule_update_to_completed_is_blocked_without_fuel(): void
    {
        $this->actingAs($this->admin, 'api_user');
        $row = $this->makeRow();
        $this->softAssign($row);

        $response = $this->postJson($this->api('orders/schedules/update'), [
            'order_product_unique_id' => $row->unique_id,
            'schedule_type' => 'Delivery',
            'schedule_status' => 'Completed',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('status', false)
            ->assertJsonPath('error.code', 'QUEUE_FUEL_VERIFICATION_REQUIRED');

        $this->assertSame('Pending', $row->fresh()->delivery_status);
    }

    public function test_api_schedule_update_return_leg_is_never_guarded(): void
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

    public function test_dispatch_mark_completed_is_blocked_without_fuel_and_passes_with_it(): void
    {
        $this->actingAs($this->admin, 'api_user');
        $row = $this->makeRow();
        $this->softAssign($row);

        $this->postJson($this->api('dispatch/update-status'), [
            'order_product_unique_id' => $row->unique_id,
            'schedule_type' => 'Delivery',
            'schedule_status' => 'Completed',
        ])->assertStatus(422)
            ->assertJsonPath('error.code', 'QUEUE_FUEL_VERIFICATION_REQUIRED');

        $this->verifyFuel($row);

        $this->postJson($this->api('dispatch/update-status'), [
            'order_product_unique_id' => $row->unique_id,
            'schedule_type' => 'Delivery',
            'schedule_status' => 'Completed',
        ])->assertOk();

        $this->assertSame('Completed', $row->fresh()->delivery_status);
    }

    public function test_dispatch_unassigned_item_is_blocked_with_equipment_required(): void
    {
        $this->actingAs($this->admin, 'api_user');
        $row = $this->makeRow(); // eligible, no machine selected

        $this->postJson($this->api('dispatch/update-status'), [
            'order_product_unique_id' => $row->unique_id,
            'schedule_type' => 'Delivery',
            'schedule_status' => 'Completed',
        ])->assertStatus(422)
            ->assertJsonPath('error.code', 'QUEUE_EQUIPMENT_REQUIRED');
    }

    // ── Admin app API: UpdateDeliveryPickupInputsController ──

    public function test_delivery_inputs_completion_is_blocked_on_a_queue_managed_pending_item(): void
    {
        $this->actingAs($this->admin, 'api_user');
        $row = $this->makeRow(null, ['delivery_by' => $this->admin->id]); // completion branch armed
        $this->softAssign($row);

        $this->postJson($this->api('orders/schedules/update-delivery-pickup-inputs'), [
            'order_product_unique_id' => $row->unique_id,
            'type' => 'delivery',
        ])->assertStatus(422)
            ->assertJsonPath('error.code', 'QUEUE_FUEL_VERIFICATION_REQUIRED');

        $this->assertSame('Pending', $row->fresh()->delivery_status);
    }

    public function test_delivery_inputs_replay_after_a_guarded_release_flows_freely(): void
    {
        $this->actingAs($this->admin, 'api_user');
        $row = $this->makeRow(null, ['delivery_by' => $this->admin->id]);
        $unit = $this->softAssign($row);

        // Simulate the item having left through a guarded path: completion latch set.
        QueueLineService::complete($row, QueueLineService::VIA_DISPATCH_STARTED, $unit->id);

        $this->postJson($this->api('orders/schedules/update-delivery-pickup-inputs'), [
            'order_product_unique_id' => $row->unique_id,
            'type' => 'delivery',
        ])->assertOk();

        $this->assertSame('Completed', $row->fresh()->delivery_status);
    }
}
