<?php

namespace App\Enums\WaitList;

/**
 * Pre-canned reasons for placing a customer on the wait list. The create
 * form submits the code; the human-readable label is what gets stored in
 * equipment_wait_lists.reason so existing display/API surfaces keep
 * showing plain text. Extra explanation belongs in Internal Notes.
 */
enum WaitListReason: string
{
    case EquipmentFullyBooked   = 'equipment_fully_booked';
    case RequestedSpecificUnit  = 'customer_requested_specific_unit';
    case WaitingForNextReturn   = 'customer_waiting_for_next_available_return';
    case UnitInMaintenance      = 'current_unit_in_maintenance';
    case UnitDamaged            = 'current_unit_damaged';
    case Other                  = 'other';

    public function label(): string
    {
        return match ($this) {
            self::EquipmentFullyBooked  => 'Equipment fully booked',
            self::RequestedSpecificUnit => 'Customer requested specific unit',
            self::WaitingForNextReturn  => 'Customer waiting for next available return',
            self::UnitInMaintenance     => 'Current unit in maintenance',
            self::UnitDamaged           => 'Current unit damaged',
            self::Other                 => 'Other',
        };
    }

    /** value => label pairs for select fields. */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
