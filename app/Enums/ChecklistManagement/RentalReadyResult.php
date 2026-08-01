<?php

namespace App\Enums\ChecklistManagement;

use App\Enums\Equipments\EquipmentCurrentStatus;

/**
 * The RESULT (business outcome) of a COMPLETED Rental Ready inspection —
 * separate from its lifecycle (see RentalReadyLifecycleStatus). A draft has a
 * null result until it is finalized.
 *
 * "Rental Ready" is NOT the only immutable result: Maintenance Hold and
 * Damaged are equally finalized outcomes that must survive later reinspections.
 */
enum RentalReadyResult: string
{
    case RentalReady = 'rental_ready';
    case MaintenanceHold = 'maintenance_hold';
    case Damaged = 'damaged';

    /** The equipment.current_status this result drives (server-authoritative). */
    public function equipmentStatus(): EquipmentCurrentStatus
    {
        return match ($this) {
            self::RentalReady => EquipmentCurrentStatus::Available,
            self::MaintenanceHold => EquipmentCurrentStatus::Maintenance,
            self::Damaged => EquipmentCurrentStatus::Damaged,
        };
    }

    /** Legacy `status` enum string kept in sync for backward-compatible reads. */
    public function legacyStatus(): string
    {
        return match ($this) {
            self::RentalReady => 'Rental Ready',
            self::MaintenanceHold => 'Draft', // legacy conflated maintenance into 'Draft'
            self::Damaged => 'Damaged',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::RentalReady => 'Rental Ready',
            self::MaintenanceHold => 'Maintenance Hold',
            self::Damaged => 'Damaged',
        };
    }
}
