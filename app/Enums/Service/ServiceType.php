<?php

namespace App\Enums\Service;

enum ServiceType: string
{
    case CustomerDamageRepair = 'customer_damage_repair';
    case OemWarrantyRepair    = 'oem_warranty_repair';
    case InternalRepair       = 'internal_company_repair';
    case InspectionDiagnosis  = 'inspection_diagnosis';
    case FieldServiceCall     = 'field_service_call';

    public function label(): string
    {
        return match ($this) {
            self::CustomerDamageRepair => 'Customer Damage',
            self::OemWarrantyRepair    => 'OEM Warranty',
            self::InternalRepair       => 'Internal Repair',
            self::InspectionDiagnosis  => 'Inspection / Diagnosis',
            self::FieldServiceCall     => 'Field Service',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::CustomerDamageRepair => 'bg-orange-100 text-orange-700',
            self::OemWarrantyRepair    => 'bg-indigo-100 text-indigo-700',
            self::InternalRepair       => 'bg-gray-100 text-gray-600',
            self::InspectionDiagnosis  => 'bg-sky-100 text-sky-700',
            self::FieldServiceCall     => 'bg-purple-100 text-purple-700',
        };
    }
}
