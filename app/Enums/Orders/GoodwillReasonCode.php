<?php

namespace App\Enums\Orders;

/**
 * WHY a Goodwill Adjustment was authorised (FD-002).
 *
 * Recorded on every adjustment. `Other` additionally requires a note — a
 * reason nobody can read later is not an audit trail, and "Other" alone
 * tells a future reviewer nothing about what management actually approved.
 */
enum GoodwillReasonCode: string
{
    case CustomerServiceResolution = 'customer_service_resolution';
    case PricingMisunderstanding   = 'pricing_misunderstanding';
    case EquipmentIssue            = 'equipment_issue';
    case DeliveryOrPickupIssue     = 'delivery_or_pickup_issue';
    case ManagerCourtesy           = 'manager_courtesy';
    case Other                     = 'other';

    public function label(): string
    {
        return match ($this) {
            self::CustomerServiceResolution => 'Customer service resolution',
            self::PricingMisunderstanding   => 'Pricing misunderstanding',
            self::EquipmentIssue            => 'Equipment issue',
            self::DeliveryOrPickupIssue     => 'Delivery or pickup issue',
            self::ManagerCourtesy           => 'Manager courtesy',
            self::Other                     => 'Other',
        };
    }

    /** Only Other forces a note; a free-text note remains permitted for every reason. */
    public function requiresNote(): bool
    {
        return $this === self::Other;
    }

    /** @return array<string,string> value => label, for selects and validation. */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
