<?php

namespace App\Enums\Discounts;

/**
 * The obligation a discount reduces. A constrained enum (NOT an arbitrary morph)
 * so a discount can only target a known, validated obligation type.
 */
enum DiscountTargetType: string
{
    case Order = 'order';                 // POD / rental order (order_products.sub_total base)
    case FuelCharge = 'fuel_charge';      // BillingCharge (fuel) base
    case DamageCharge = 'damage_charge';  // BillingCharge (damage) base
    case Extension = 'extension';         // rental extension charge base

    public function label(): string
    {
        return match ($this) {
            self::Order        => 'Order',
            self::FuelCharge   => 'Fuel Charge',
            self::DamageCharge => 'Damage Charge',
            self::Extension    => 'Extension',
        };
    }
}
