<?php

namespace App\Enums\FieldService;

/**
 * The Operational Decision — the architectural branching point of the
 * Field Service Module. The technician is not deciding how to repair the
 * machine; they are deciding what operational response the company takes.
 * Every field incident resolves to exactly one of these outcomes, and the
 * selected outcome drives every workflow that follows.
 */
enum FieldOperationalOutcome: string
{
    case RepairOnSite     = 'repair_on_site';
    case RecoverToShop    = 'recover_to_shop';
    case DealerService    = 'dealer_service';
    case ComplexRecovery  = 'complex_recovery';
    case NoRepairRequired = 'no_repair_required';

    public function label(): string
    {
        return match ($this) {
            self::RepairOnSite     => 'Repair On Site',
            self::RecoverToShop    => 'Recover to Shop',
            self::DealerService    => 'Dealer Service',
            self::ComplexRecovery  => 'Complex Recovery',
            self::NoRepairRequired => 'No Repair Required',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::RepairOnSite     => 'The technician can fix it at the customer site — the machine stays on rent.',
            self::RecoverToShop    => 'The machine comes back to the shop for repair on a standard truck or trailer.',
            self::DealerService    => 'The problem needs the manufacturer or dealer — warranty work, specialty tooling, or authorized service.',
            self::ComplexRecovery  => 'Recovery needs special resources — the machine is stuck, damaged, inaccessible, or unsafe to move normally.',
            self::NoRepairRequired => 'Nothing is wrong or the issue resolved on site — operator error, settings, or already working.',
        };
    }

    public function examples(): string
    {
        return match ($this) {
            self::RepairOnSite     => 'Battery swap, fuse, hydraulic hose, loose connection',
            self::RecoverToShop    => 'Engine teardown, major electrical, parts not carried on truck',
            self::DealerService    => 'Warranty engine claim, ECU replacement, dealer-only diagnostics',
            self::ComplexRecovery  => 'Stuck in mud, rollover, crane or heavy-wrecker needed',
            self::NoRepairRequired => 'Operator error, dead key fob, tripped disconnect switch',
        };
    }

    /** Heroicon name used on the decision card. */
    public function icon(): string
    {
        return match ($this) {
            self::RepairOnSite     => 'heroicon-o-wrench-screwdriver',
            self::RecoverToShop    => 'heroicon-o-truck',
            self::DealerService    => 'heroicon-o-building-storefront',
            self::ComplexRecovery  => 'heroicon-o-lifebuoy',
            self::NoRepairRequired => 'heroicon-o-hand-thumb-up',
        };
    }

    /** Accent classes for the decision card + outcome banner. */
    public function color(): string
    {
        return match ($this) {
            self::RepairOnSite     => 'text-green-600',
            self::RecoverToShop    => 'text-blue-600',
            self::DealerService    => 'text-indigo-600',
            self::ComplexRecovery  => 'text-red-600',
            self::NoRepairRequired => 'text-gray-500',
        };
    }
}
