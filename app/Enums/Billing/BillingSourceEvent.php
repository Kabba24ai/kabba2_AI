<?php

namespace App\Enums\Billing;

enum BillingSourceEvent: string
{
    case ReturnChecklistFuelCharge       = 'return_checklist_fuel_charge';
    case ReturnChecklistDamageCharge     = 'return_checklist_damage_charge';
    case AdminFuelChargeCreated          = 'admin_fuel_charge_created';
    case AdminDamageChargeCreated        = 'admin_damage_charge_created';
    case RentalExtensionCreated          = 'rental_extension_created';
    case ServiceTicketChargeCreated      = 'service_ticket_charge_created';
    case ManualAdditionalChargeCreated   = 'manual_additional_charge_created';

    public function label(): string
    {
        return match($this) {
            self::ReturnChecklistFuelCharge     => 'Return Checklist — Fuel Charge',
            self::ReturnChecklistDamageCharge   => 'Return Checklist — Damage Charge',
            self::AdminFuelChargeCreated        => 'Admin Created Fuel Charge',
            self::AdminDamageChargeCreated      => 'Admin Created Damage Charge',
            self::RentalExtensionCreated        => 'Rental Extension Created',
            self::ServiceTicketChargeCreated    => 'Service Ticket Charge Created',
            self::ManualAdditionalChargeCreated => 'Manual Additional Charge',
        };
    }

    /** The module this event belongs to. */
    public function expectedModule(): BillingSourceModule
    {
        return match($this) {
            self::ReturnChecklistFuelCharge,
            self::ReturnChecklistDamageCharge   => BillingSourceModule::MobileChecklist,
            self::AdminFuelChargeCreated        => BillingSourceModule::AdminFuelCharge,
            self::AdminDamageChargeCreated      => BillingSourceModule::AdminDamageCharge,
            self::RentalExtensionCreated        => BillingSourceModule::RentalExtension,
            self::ServiceTicketChargeCreated    => BillingSourceModule::ServiceModule,
            self::ManualAdditionalChargeCreated => BillingSourceModule::ManualEntry,
        };
    }
}
