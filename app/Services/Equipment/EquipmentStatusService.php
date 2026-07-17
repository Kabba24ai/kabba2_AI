<?php

namespace App\Services\Equipment;

use App\Enums\Equipments\EquipmentCurrentStatus;
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentStatusLog;
use App\Models\MaintenanceManagement\Equipment;
use App\Services\WaitList\WaitListMatcher;
use Illuminate\Support\Facades\Log;

/**
 * EquipmentStatusService — single authority for equipment status transitions
 * in the mobile API workflows (delivery, return, rental-ready, checklist-remove).
 *
 * Every call logs the old → new status with full context so transitions are
 * auditable without reading the Equipment table's audit columns directly, and
 * persists a matching EquipmentStatusLog row (via EquipmentStatusLog::recordTransition())
 * since saveQuietly() suppresses the EquipmentObserver that would otherwise have
 * written one.
 *
 * Other saveQuietly()-based current_status mutators exist outside this class —
 * UpdateProductScheduleController, AssignEquipmentController,
 * RemoveEquipmentController, and Order's deleting hook — each calls the same
 * shared EquipmentStatusLog::recordTransition() helper directly.
 */
class EquipmentStatusService
{
    private const CHANNEL = 'equipment_status';

    /**
     * Mark equipment as Rented when a mobile delivery checklist is submitted.
     * Sets current_order_id and current_order_product_id on the equipment row.
     * The caller is responsible for setting equipment_hours before this call.
     */
    public static function markRented(
        Equipment $equipment,
        int $orderId,
        int $orderProductId,
        ?int $actorId = null,
        string $source = 'mobile_delivery'
    ): void {
        $oldRaw = $equipment->current_status?->value;
        $old    = $oldRaw ?? 'unknown';

        $equipment->current_status              = EquipmentCurrentStatus::Rented->value;
        $equipment->current_status_updated_by   = $actorId;
        $equipment->current_status_changed_at   = now();
        $equipment->current_order_id            = $orderId;
        $equipment->current_order_product_id    = $orderProductId;
        $equipment->saveQuietly();

        self::log($equipment->id, $old, EquipmentCurrentStatus::Rented->value, $orderId, $orderProductId, $source, $actorId);
        EquipmentStatusLog::recordTransition($equipment->id, $oldRaw, EquipmentCurrentStatus::Rented->value, $actorId);
    }

    /**
     * Mark equipment as Maintenance after a normal (undamaged) mobile return.
     * Does NOT clear current_order_id — the order is still active at this point.
     * The caller is responsible for setting equipment_hours before this call.
     */
    public static function markReturnedToMaintenance(
        Equipment $equipment,
        int $orderId,
        int $orderProductId,
        ?int $storeId = null,
        ?int $actorId = null
    ): void {
        $oldRaw = $equipment->current_status?->value;
        $old    = $oldRaw ?? 'unknown';

        $equipment->current_status              = EquipmentCurrentStatus::Maintenance->value;
        $equipment->current_status_updated_by   = $actorId;
        $equipment->current_status_changed_at   = now();
        if ($storeId !== null) {
            $equipment->store_id = $storeId;
        }
        $equipment->saveQuietly();

        self::log($equipment->id, $old, EquipmentCurrentStatus::Maintenance->value, $orderId, $orderProductId, 'return_checklist', $actorId);

        self::evaluateWaitLists($equipment);
        EquipmentStatusLog::recordTransition($equipment->id, $oldRaw, EquipmentCurrentStatus::Maintenance->value, $actorId);
    }

    /**
     * Mark equipment as Damaged after a mobile return where checklist answers
     * indicate damage (is_damaged=true on the master answer record).
     * Does NOT clear current_order_id — staff must review the damage report.
     * The caller is responsible for setting equipment_hours before this call.
     */
    public static function markReturnedDamaged(
        Equipment $equipment,
        int $orderId,
        int $orderProductId,
        ?int $storeId = null,
        ?int $actorId = null
    ): void {
        $oldRaw = $equipment->current_status?->value;
        $old    = $oldRaw ?? 'unknown';

        $equipment->current_status              = EquipmentCurrentStatus::Damaged->value;
        $equipment->current_status_updated_by   = $actorId;
        $equipment->current_status_changed_at   = now();
        if ($storeId !== null) {
            $equipment->store_id = $storeId;
        }
        $equipment->saveQuietly();

        self::log($equipment->id, $old, EquipmentCurrentStatus::Damaged->value, $orderId, $orderProductId, 'return_checklist', $actorId);

        self::evaluateWaitLists($equipment);
        EquipmentStatusLog::recordTransition($equipment->id, $oldRaw, EquipmentCurrentStatus::Damaged->value, $actorId);
    }

    /**
     * Wait List integration (Phase 1): every completed return/check-in
     * evaluates active wait list demand, regardless of the equipment's
     * resulting status. Failures here must never break a return.
     */
    private static function evaluateWaitLists(Equipment $equipment): void
    {
        try {
            WaitListMatcher::evaluateReturn($equipment);
        } catch (\Throwable $e) {
            Log::channel(self::CHANNEL)->error('Wait list evaluation failed', [
                'equipment_id' => $equipment->id,
                'error'        => $e->getMessage(),
            ]);
        }
    }

    /**
     * Mark equipment as Available when a customer checklist is removed by staff.
     * Clears current_order_id and current_order_product_id — the checklist
     * remove resets the order product back to an unassigned state.
     */
    public static function markAvailableOnChecklistRemove(
        Equipment $equipment,
        int $orderId,
        int $orderProductId,
        ?int $actorId = null
    ): void {
        $oldRaw = $equipment->current_status?->value;
        $old    = $oldRaw ?? 'unknown';

        $equipment->current_status              = EquipmentCurrentStatus::Available->value;
        $equipment->current_status_updated_by   = $actorId;
        $equipment->current_status_changed_at   = now();
        $equipment->current_order_id            = null;
        $equipment->current_order_product_id    = null;
        $equipment->saveQuietly();

        self::log($equipment->id, $old, EquipmentCurrentStatus::Available->value, $orderId, $orderProductId, 'checklist_remove', $actorId);
        EquipmentStatusLog::recordTransition($equipment->id, $oldRaw, EquipmentCurrentStatus::Available->value, $actorId);
    }

    /**
     * Mark equipment as Available after a Rental Ready inspection where all
     * required answers are 'Rental Ready'. Clears current_order refs because
     * the equipment is ready to be assigned to a new rental.
     */
    public static function markAvailableFromRentalReady(
        Equipment $equipment,
        ?int $actorId = null
    ): void {
        $oldRaw         = $equipment->current_status?->value;
        $old            = $oldRaw ?? 'unknown';
        $orderId        = $equipment->current_order_id;         // capture before clearing
        $orderProductId = $equipment->current_order_product_id; // capture before clearing

        $equipment->current_status              = EquipmentCurrentStatus::Available->value;
        $equipment->current_status_updated_by   = $actorId;
        $equipment->current_status_changed_at   = now();
        $equipment->current_order_id            = null;
        $equipment->current_order_product_id    = null;
        $equipment->saveQuietly();

        self::log($equipment->id, $old, EquipmentCurrentStatus::Available->value, $orderId, $orderProductId, 'rental_ready', $actorId);
        EquipmentStatusLog::recordTransition($equipment->id, $oldRaw, EquipmentCurrentStatus::Available->value, $actorId);
    }

    /**
     * Mark equipment as Maintenance Hold after a Rental Ready inspection where
     * one or more answers require maintenance before the unit can go back out.
     */
    public static function markMaintenanceFromRentalReady(
        Equipment $equipment,
        ?int $actorId = null
    ): void {
        $oldRaw = $equipment->current_status?->value;
        $old    = $oldRaw ?? 'unknown';

        $equipment->current_status              = EquipmentCurrentStatus::Maintenance->value;
        $equipment->current_status_updated_by   = $actorId;
        $equipment->current_status_changed_at   = now();
        $equipment->saveQuietly();

        self::log(
            $equipment->id, $old, EquipmentCurrentStatus::Maintenance->value,
            $equipment->current_order_id,
            $equipment->current_order_product_id,
            'rental_ready', $actorId
        );
        EquipmentStatusLog::recordTransition($equipment->id, $oldRaw, EquipmentCurrentStatus::Maintenance->value, $actorId);
    }

    /**
     * Mark equipment as Damaged after a Rental Ready inspection where one or
     * more answers are flagged as Damaged. Keeps current_order refs intact so
     * staff can trace the damage back to the rental context.
     */
    public static function markDamagedFromRentalReady(
        Equipment $equipment,
        ?int $actorId = null
    ): void {
        $oldRaw = $equipment->current_status?->value;
        $old    = $oldRaw ?? 'unknown';

        $equipment->current_status              = EquipmentCurrentStatus::Damaged->value;
        $equipment->current_status_updated_by   = $actorId;
        $equipment->current_status_changed_at   = now();
        $equipment->saveQuietly();

        self::log(
            $equipment->id, $old, EquipmentCurrentStatus::Damaged->value,
            $equipment->current_order_id,
            $equipment->current_order_product_id,
            'rental_ready', $actorId
        );
        EquipmentStatusLog::recordTransition($equipment->id, $oldRaw, EquipmentCurrentStatus::Damaged->value, $actorId);
    }

    private static function log(
        int $equipmentId,
        string $from,
        string $to,
        int|null $orderId,
        int|null $orderProductId,
        string $source,
        int|null $actorId
    ): void {
        Log::channel(self::CHANNEL)->info(sprintf(
            'equipment_id=%d | %s → %s | order_id=%s | order_product_id=%s | source=%s | actor=%s',
            $equipmentId,
            $from,
            $to,
            $orderId    ?? 'null',
            $orderProductId ?? 'null',
            $source,
            $actorId    ?? 'null'
        ));
    }
}
