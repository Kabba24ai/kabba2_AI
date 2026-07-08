<?php

namespace App\Enums\Service;

/**
 * Structured diagnostic step categories — technicians log each step taken
 * so diagnostics become searchable service intelligence instead of one
 * free-form blob. "Other" keeps technician flexibility.
 */
enum DiagnosticStepType: string
{
    case CalledOemSupport      = 'called_oem_support';
    case ReviewedServiceManual = 'reviewed_service_manual';
    case BatteryTest           = 'battery_test';
    case HydraulicPressureTest = 'hydraulic_pressure_test';
    case ElectricalInspection  = 'electrical_inspection';
    case SoftwareUpdate        = 'software_update';
    case VisualInspection      = 'visual_inspection';
    case Other                 = 'other';

    public function label(): string
    {
        return match ($this) {
            self::CalledOemSupport      => 'Called OEM Technical Support',
            self::ReviewedServiceManual => 'Reviewed Service Manual',
            self::BatteryTest           => 'Battery Test',
            self::HydraulicPressureTest => 'Hydraulic Pressure Test',
            self::ElectricalInspection  => 'Electrical Inspection',
            self::SoftwareUpdate        => 'Software Update',
            self::VisualInspection      => 'Visual Inspection',
            self::Other                 => 'Other',
        };
    }
}
