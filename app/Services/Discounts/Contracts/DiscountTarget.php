<?php

namespace App\Services\Discounts\Contracts;

use App\Enums\Discounts\DiscountTargetType;
use App\Models\Discounts\ProductDiscount;
use App\Services\Discounts\DiscountResult;

/**
 * Adapter over one discountable obligation (POD order, fuel/damage charge,
 * extension). Isolates the DiscountApplicationService from per-surface pricing
 * so the service owns auth/lock/balance/idempotency/persistence while each
 * surface owns "what is my eligible base, am I still discountable (pre-posting),
 * and how do I re-price myself after a discount / restore on reversal."
 */
interface DiscountTarget
{
    public function targetType(): DiscountTargetType;

    public function targetId(): int;

    /** Owning customer id (for ownership validation), or null if none. */
    public function customerId(): ?int;

    /** Acquire a row lock on the obligation and refresh its state INSIDE the txn. */
    public function lockAndRefresh(): void;

    /**
     * Whether the obligation may still be discounted. Phase 1 = PRE-POSTING only
     * (not yet paid / not yet posted to the A/R ledger / not finalized).
     */
    public function isDiscountable(): bool;

    /** Human reason the target is not discountable, or null when it is. */
    public function ineligibleReason(): ?string;

    /** The eligible PRE-TAX product base a discount may reduce (>= 0). */
    public function eligibleProductValue(): float;

    /** Effective tax rate for this surface (0.0 when the surface is exempt). */
    public function taxRate(): float;

    /** Re-price the obligation from the calculated result (discounted base + recalculated tax). */
    public function applyDiscount(DiscountResult $result, ProductDiscount $discount): void;

    /** Restore the obligation's pricing as part of a reversal of $original. */
    public function reverseDiscount(ProductDiscount $original): void;
}
