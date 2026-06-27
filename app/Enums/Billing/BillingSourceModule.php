<?php

namespace App\Enums\Billing;

enum BillingSourceModule: string
{
    case AdminFuelCharge    = 'admin_fuel_charge';
    case AdminDamageCharge  = 'admin_damage_charge';
    case MobileChecklist    = 'mobile_checklist';
    case MobileOfflineSync  = 'mobile_offline_sync';
    case RentalExtension    = 'rental_extension';
    case ServiceModule      = 'service_module';
    case ManualEntry        = 'manual_entry';
    case MaintenanceModule  = 'maintenance_module';
    case DeliveryModule     = 'delivery_module';
    case ReturnChecklist    = 'return_checklist';

    public function label(): string
    {
        return match($this) {
            self::AdminFuelCharge   => 'Admin — Fuel Charge',
            self::AdminDamageCharge => 'Admin — Damage Charge',
            self::MobileChecklist   => 'Mobile Checklist',
            self::MobileOfflineSync => 'Mobile Checklist (Offline Sync)',
            self::RentalExtension   => 'Rental Extension',
            self::ServiceModule     => 'Service Module',
            self::ManualEntry       => 'Manual Entry',
            self::MaintenanceModule => 'Maintenance Module',
            self::DeliveryModule    => 'Delivery Module',
            self::ReturnChecklist   => 'Return Checklist',
        };
    }

    /** True if this source may retry via offline sync. */
    public function isMobileOriginated(): bool
    {
        return in_array($this, [
            self::MobileChecklist,
            self::MobileOfflineSync,
            self::ReturnChecklist,
        ]);
    }
}
