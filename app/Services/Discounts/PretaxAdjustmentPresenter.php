<?php

namespace App\Services\Discounts;

use App\Enums\Discounts\DiscountType;
use App\Models\Discounts\ProductDiscount;
use App\Models\Orders\Order;

/**
 * How a pre-tax adjustment is NAMED on a customer-facing document.
 *
 * Presentation only. This class reads no money and computes nothing — the
 * amount always comes from the receipt's own snapshot column. All it answers
 * is "what should this line be called?", which requires knowing WHICH kind of
 * adjustment was applied, and that is the one thing the receipt snapshot does
 * not record.
 *
 * ── THE DATA-MODEL GAP ────────────────────────────────────────────────────
 *
 * `receipts.pretax_discount_total` is a single cumulative figure. It carries
 * no attribution, so with two adjustments of DIFFERENT types stacked on one
 * order, the receipt cannot say how much of that total belonged to each.
 *
 * Type IDENTITY is therefore taken from the order's `product_discounts` rows —
 * the only place it exists. That is deliberately limited to identity: no
 * amount is ever read from them, because those are live values that can drift
 * from what the receipt captured.
 *
 * When exactly one type is active the label is exact. When several are, this
 * returns a truthful generic rather than attributing the whole sum to whichever
 * row happened to be found first. Splitting the line per type would require
 * per-type amounts on the receipt snapshot, which do not exist.
 */
class PretaxAdjustmentPresenter
{
    /** Used only when the adjustment genuinely cannot be identified. */
    public const UNIDENTIFIED = 'Pre-Tax Discount';

    /** Used when several DIFFERENT types are stacked and cannot be split. */
    public const MIXED = 'Pre-Tax Discounts';

    /**
     * The label for an order's pre-tax adjustment line.
     *
     * - one active type  → its explicit receipt label, e.g. "Store Credit - Pre-Tax"
     * - several types    → the plural generic; the sum cannot honestly be
     *                      attributed to any one of them
     * - none identifiable → the singular generic
     */
    public static function label(?Order $order): string
    {
        $types = self::activeTypes($order);

        if (count($types) === 1) {
            return $types[0]->receiptLabel();
        }

        return count($types) > 1 ? self::MIXED : self::UNIDENTIFIED;
    }

    /**
     * Every distinct adjustment type currently applied to the order.
     *
     * Returns the SET, never a single arbitrary row — reading one row would
     * mislabel a stacked order with whichever adjustment happened to sort
     * first.
     *
     * @return list<DiscountType>
     */
    public static function activeTypes(?Order $order): array
    {
        if ($order === null || $order->id === null) {
            return [];
        }

        return ProductDiscount::query()
            ->where('target_type', 'order')
            ->where('target_id', $order->id)
            ->where('status', ProductDiscount::STATUS_APPLIED)
            ->distinct()
            ->pluck('discount_type')
            // ProductDiscount casts this column to the enum, but a raw string
            // arrives from any query that bypasses the cast. Accept both.
            ->map(fn ($value) => $value instanceof DiscountType
                ? $value
                : DiscountType::tryFrom((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /** True when several different types are stacked and cannot be split. */
    public static function isMixed(?Order $order): bool
    {
        return count(self::activeTypes($order)) > 1;
    }
}
