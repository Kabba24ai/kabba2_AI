<?php

namespace App\Enums\Service;

/**
 * Capability keys stored on equipment.capabilities (JSON array). Complaint
 * types can require capabilities so machines only show complaints for
 * components they actually have (no cab complaints on an open-cab unit).
 *
 * Filtering rule: equipment with NO recorded capabilities (null) sees every
 * complaint — an unknown machine hides nothing. Once capabilities are
 * recorded (even an empty list), requirements are enforced.
 */
enum EquipmentCapability: string
{
    case EnclosedCab         = 'enclosed_cab';
    case AirConditioning     = 'air_conditioning';
    case Heater              = 'heater';
    case WindshieldWiper     = 'windshield_wiper';
    case GlassDoor           = 'glass_door';
    case AuxiliaryHydraulics = 'auxiliary_hydraulics';
    case RubberTracks        = 'rubber_tracks';
    case DieselEngine        = 'diesel_engine';
    case Outriggers          = 'outriggers';

    public function label(): string
    {
        return match ($this) {
            self::EnclosedCab         => 'Enclosed Cab',
            self::AirConditioning     => 'Air Conditioning',
            self::Heater              => 'Heater',
            self::WindshieldWiper     => 'Windshield Wiper',
            self::GlassDoor           => 'Glass Door',
            self::AuxiliaryHydraulics => 'Auxiliary Hydraulics',
            self::RubberTracks        => 'Rubber Tracks',
            self::DieselEngine        => 'Diesel Engine',
            self::Outriggers          => 'Outriggers',
        };
    }
}
