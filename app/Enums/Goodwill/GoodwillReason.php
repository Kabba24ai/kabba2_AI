<?php

namespace App\Enums\Goodwill;

/**
 * The stated business reason for a Goodwill concession.
 *
 * ── CODES, NOT LABELS ─────────────────────────────────────────────────────
 *
 * The backing value is a STABLE CODE. It is what is written to
 * `order_goodwill_adjustments.reason_code`, what reports group on, and what
 * survives every rewording of the operator-facing dropdown. `label()` is
 * presentation and may change freely; the code may not. A report grouped on a
 * display string silently re-groups the day someone changes "Damaged Product"
 * to "Damaged or Defective Product", and the historical series quietly splits
 * in two with nothing to indicate it happened.
 *
 * ── THE CATEGORY IS THE POINT ─────────────────────────────────────────────
 *
 * Every reason maps to exactly one {@see GoodwillReasonCategory}. That mapping
 * lives here and nowhere else: it is denormalised onto the record at write time
 * by the model, never re-derived by a consumer. See the category enum for why
 * the split matters.
 *
 * Codes are uppercase because they are identifiers, deliberately unlike the
 * lowercase values used by the discount enums, which are operational states
 * rather than a reporting vocabulary.
 */
enum GoodwillReason: string
{
    // ── Service Recovery — compensation for an operational problem ─────────

    case ServiceFailure       = 'SERVICE_FAILURE';
    case EquipmentIssue       = 'EQUIPMENT_ISSUE';
    case DamagedProduct       = 'DAMAGED_PRODUCT';
    case BillingError         = 'BILLING_ERROR';
    case DeliveryPickupIssue  = 'DELIVERY_PICKUP_ISSUE';

    // ── Business Courtesy — a deliberate commercial decision ───────────────

    case RepeatCustomer       = 'REPEAT_CUSTOMER';
    case CustomerLoyalty      = 'CUSTOMER_LOYALTY';
    case CustomerRetention    = 'CUSTOMER_RETENTION';
    case MultipleItems        = 'MULTIPLE_ITEMS';
    case LargeOrder           = 'LARGE_ORDER';
    case PriceMatch           = 'PRICE_MATCH';
    case PromotionalCourtesy  = 'PROMOTIONAL_COURTESY';
    case ManagerCourtesy      = 'MANAGER_COURTESY';

    // ── Uncategorised ─────────────────────────────────────────────────────

    case Other                = 'OTHER';

    /** Operator-facing text. Presentation only — never stored, never grouped on. */
    public function label(): string
    {
        return match ($this) {
            self::ServiceFailure      => 'Service Failure',
            self::EquipmentIssue      => 'Equipment Issue',
            self::DamagedProduct      => 'Damaged Product',
            self::BillingError        => 'Billing Error',
            self::DeliveryPickupIssue => 'Delivery / Pickup Issue',

            self::RepeatCustomer      => 'Repeat Customer',
            self::CustomerLoyalty     => 'Customer Loyalty',
            self::CustomerRetention   => 'Customer Retention',
            self::MultipleItems       => 'Multiple Items',
            self::LargeOrder          => 'Large Order',
            self::PriceMatch          => 'Price Match',
            self::PromotionalCourtesy => 'Promotional Courtesy',
            self::ManagerCourtesy     => 'Manager Courtesy',

            self::Other               => 'Other',
        };
    }

    /**
     * The reporting dimension this reason belongs to. The single authoritative
     * mapping — `order_goodwill_adjustments.reason_category` is written from
     * here and from nothing else.
     */
    public function category(): GoodwillReasonCategory
    {
        return match ($this) {
            self::ServiceFailure,
            self::EquipmentIssue,
            self::DamagedProduct,
            self::BillingError,
            self::DeliveryPickupIssue  => GoodwillReasonCategory::ServiceRecovery,

            self::RepeatCustomer,
            self::CustomerLoyalty,
            self::CustomerRetention,
            self::MultipleItems,
            self::LargeOrder,
            self::PriceMatch,
            self::PromotionalCourtesy,
            self::ManagerCourtesy      => GoodwillReasonCategory::BusinessCourtesy,

            self::Other                => GoodwillReasonCategory::Other,
        };
    }

    /**
     * Does this reason require a written explanation?
     *
     * True only for OTHER. A concession recorded as "Other" with no note is
     * indistinguishable from an unexplained reduction in revenue, which is
     * precisely what this whole record exists to prevent.
     */
    public function requiresNote(): bool
    {
        return $this === self::Other;
    }

    /**
     * Reasons grouped by category, in declaration order — the shape an operator
     * dropdown needs, built from the same mapping the ledger uses.
     *
     * @return array<string, list<self>> keyed by category value
     */
    public static function groupedByCategory(): array
    {
        $grouped = [];

        foreach (GoodwillReasonCategory::cases() as $category) {
            $grouped[$category->value] = $category->reasons();
        }

        return $grouped;
    }
}
