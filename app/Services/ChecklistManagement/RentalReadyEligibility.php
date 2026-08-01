<?php

namespace App\Services\ChecklistManagement;

use App\Models\MaintenanceManagement\Equipment;

/**
 * Shared server-side eligibility rule for recording a Rental Ready inspection —
 * the SINGLE source used by both the web and mobile write paths (Phase 2A
 * brought the web path into line with the mobile guard).
 *
 * Rule: a Rental Ready inspection is a PRE-rental shop activity. It may run on
 * Available, Maintenance Hold, or Damaged equipment, but NEVER while the unit
 * is actively Rented to a customer (that would overwrite a live rental's
 * status from the shop floor).
 */
class RentalReadyEligibility
{
    public static function canInspect(Equipment $equipment): bool
    {
        return ! ($equipment->current_status?->isRented() ?? false);
    }

    /** @throws RentalReadyInspectionException */
    public static function assertCanInspect(Equipment $equipment): void
    {
        if (! self::canInspect($equipment)) {
            throw RentalReadyInspectionException::equipmentRented(
                (int) $equipment->id,
                $equipment->current_status?->value,
            );
        }
    }
}
