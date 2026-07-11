<?php

namespace App\Enums\Service;

/**
 * Logical system groups for the structured complaint library. Complaints are
 * DB rows (service_complaint_types) so they can be managed administratively;
 * the groups are a fixed taxonomy that keeps the intake list scannable.
 */
enum ComplaintSystemGroup: string
{
    case EngineStarting      = 'engine_starting';
    case Hydraulic           = 'hydraulic';
    case TracksUndercarriage = 'tracks_undercarriage';
    case Controls            = 'controls';
    case Cab                 = 'cab';
    case PhysicalDamage      = 'physical_damage';
    case Other               = 'other';

    public function label(): string
    {
        return match ($this) {
            self::EngineStarting      => 'Engine & Starting',
            self::Hydraulic           => 'Hydraulic System',
            self::TracksUndercarriage => 'Tracks & Undercarriage',
            self::Controls            => 'Controls',
            self::Cab                 => 'Cab',
            self::PhysicalDamage      => 'Physical Damage',
            self::Other               => 'Other',
        };
    }

    /** Display order of the groups on the intake checklist. */
    public function sortOrder(): int
    {
        return match ($this) {
            self::EngineStarting      => 1,
            self::Hydraulic           => 2,
            self::TracksUndercarriage => 3,
            self::Controls            => 4,
            self::Cab                 => 5,
            self::PhysicalDamage      => 6,
            self::Other               => 7,
        };
    }
}
