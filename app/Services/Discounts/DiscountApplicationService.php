<?php

namespace App\Services\Discounts;

use App\Enums\Discounts\DiscountCalculationType;
use App\Enums\Discounts\DiscountTargetType;
use App\Enums\Discounts\DiscountType;
use App\Models\Customers\Customer;
use App\Models\Discounts\ProductDiscount;
use App\Services\Discounts\Contracts\DiscountTarget;
use App\Services\CustomerCreditService;
use Illuminate\Support\Facades\DB;

/**
 * The canonical persistence + orchestration boundary for pre-tax product
 * discounts. Controllers call this; they never compute a discount/tax, redeem
 * Store Credit, or write a discount row themselves.
 *
 * Responsibilities: authorization gate (Phase-1 type enforcement), target +
 * customer-ownership validation, transaction boundary, row locking (Store
 * Credit balance via the customer row + the target obligation), revalidation
 * INSIDE the txn, stable idempotency, Store Credit balance reduction WITHOUT
 * any payment record, persistence + linkage of the discount and the redemption,
 * and atomic compensating reversal with balance restoration.
 *
 * Locked decisions: Store Credit is a discount (never a payment); pre-posting
 * obligations only (the target adapter enforces via isDiscountable()).
 */
class DiscountApplicationService
{
    public function __construct(
        private readonly DiscountCalculator $calculator,
        private ?DiscountTargetResolver $resolver = null,
    ) {
    }

    private function resolver(): DiscountTargetResolver
    {
        return $this->resolver ??= new DiscountTargetResolver();
    }

    /**
     * Operational Phase-1 entry point: apply a Store Credit discount to a
     * resolved target. Controllers call this.
     */
    public function applyStoreCredit(
        DiscountTargetType $targetType,
        int $targetId,
        float $amount,
        string $idempotencyKey,
        ?int $appliedBy,
        ?string $reason = null,
        ?string $sourceInterface = null,
        ?int $expectedCustomerId = null,
    ): ProductDiscount {
        $target = $this->resolver()->resolve($targetType, $targetId);

        return $this->apply(
            $target,
            DiscountType::StoreCredit,
            DiscountCalculationType::FixedAmount,
            $amount,
            null,
            $idempotencyKey,
            $appliedBy,
            $expectedCustomerId,
            $reason,
            $sourceInterface,
        );
    }

    /** Operational reversal: resolve the target from the stored discount and reverse. */
    public function reverseStoreCredit(ProductDiscount $discount, ?int $reversedBy = null, ?string $reason = null): ProductDiscount
    {
        $target = $this->resolver()->resolve($discount->target_type, $discount->target_id);

        return $this->reverse($discount, $target, $reversedBy, $reason);
    }

    // ── Creation-time discount (fuel / damage / extension) ───────────────────
    //
    // These charges post to A/R at creation (or, for extensions, are booked as
    // the obligation at creation), so there is no post-creation pre-posting
    // window. The discount must be applied AT creation: reduce the base BEFORE
    // tax + the obligation are booked, inside the CALLER's transaction, so the
    // Store Credit redemption, discount record, and charge creation are one
    // atomic unit. The caller: (1) checks existingCreationDiscount() for
    // idempotency, (2) computeAndRedeemForCreation() to validate+redeem+price,
    // (3) creates the discounted obligation, (4) recordCreationDiscount() to
    // link the discount to the created obligation — all within one DB txn.

    public function existingCreationDiscount(string $idempotencyKey): ?ProductDiscount
    {
        return ProductDiscount::where('idempotency_key', $idempotencyKey)->first();
    }

    /**
     * Charge-surface convenience (fuel/damage) built on computeAndRedeemForCreation:
     * resolves the eligible pre-tax base for the charge's tax treatment ('reverse'
     * stores a tax-inclusive amount → discount the backed-out base), validates +
     * redeems, and returns the discounted amount + the EXCLUSIVE treatment to store
     * so A/R + the BillingCharge recompute tax on the discounted base. MUST run in
     * the caller's charge-creation transaction.
     *
     * @return array{effective_amount: float, effective_treatment: string, result: DiscountResult, redemption_id: int}
     */
    public function computeChargeDiscount(
        int $customerId,
        float $amount,
        ?string $salesTaxType,
        float $requestedAmount,
        string $idempotencyKey,
        ?int $appliedBy,
        string $chargeType,
    ): array {
        $treatment = $salesTaxType ?? 'free';
        $rate = $treatment === 'free' ? 0.0 : \App\Services\ChargeTaxCalculator::currentRate();
        $eligibleBase = $treatment === 'reverse'
            ? (float) \App\Services\ChargeTaxCalculator::calculate($amount, 'reverse', $rate)['base_amount']
            : round($amount, 2);

        $cd = $this->computeAndRedeemForCreation(
            $customerId, $eligibleBase, $requestedAmount, $rate,
            $idempotencyKey, $appliedBy, "Store Credit discount — {$chargeType}",
        );

        return [
            'effective_amount' => $cd['result']->discountedProductValue,
            'effective_treatment' => $rate > 0 ? 'add' : 'free',
            'result' => $cd['result'],
            'redemption_id' => $cd['redemption_id'],
        ];
    }

    /**
     * Phase 1 of a creation-time Store Credit discount. MUST be called inside
     * the caller's charge-creation transaction. Locks the customer, revalidates
     * the balance, rejects over-available / over-eligible, computes the priced
     * result, and redeems the balance (balance-only — never a payment).
     *
     * @return array{result: DiscountResult, redemption_id: int}
     */
    public function computeAndRedeemForCreation(
        int $customerId,
        float $eligibleBase,
        float $requestedAmount,
        float $taxRate,
        string $idempotencyKey,
        ?int $appliedBy,
        ?string $reason = null,
    ): array {
        $eligibleBase = round($eligibleBase, 2);
        if ($eligibleBase <= 0) {
            throw new DiscountException('No eligible product value to discount.');
        }
        $requested = round($requestedAmount, 2);
        if ($requested <= 0) {
            throw new DiscountException('Store Credit amount must be greater than zero.');
        }

        // Serialize concurrent spends for this customer (revalidate under lock).
        Customer::where('id', $customerId)->lockForUpdate()->firstOrFail();

        $available = CustomerCreditService::remainingBalance($customerId);
        if ($requested > $available) {
            throw new DiscountException("Requested {$requested} exceeds available Store Credit {$available}.");
        }
        if ($requested > $eligibleBase) {
            throw new DiscountException("Requested {$requested} exceeds the eligible product value {$eligibleBase}.");
        }

        $result = $this->calculator->calculate(
            $eligibleBase, DiscountType::StoreCredit, DiscountCalculationType::FixedAmount, $requested, null, $taxRate,
        );

        $redemption = CustomerCreditService::redeem(
            customerId: $customerId,
            amount: $result->discountAmount,
            reason: $reason ?? 'Store Credit product discount',
            responsibleUserId: $appliedBy,
            idempotencyKey: 'scd_redeem:' . $idempotencyKey,
        );

        return ['result' => $result, 'redemption_id' => $redemption->id];
    }

    /**
     * Phase 2: persist the discount linked to the just-created obligation. MUST
     * run in the same transaction as computeAndRedeemForCreation() + the charge
     * write. Idempotent (firstOrCreate on the key).
     */
    public function recordCreationDiscount(
        DiscountResult $result,
        ?int $redemptionId,
        DiscountTargetType $targetType,
        int $targetId,
        int $customerId,
        string $idempotencyKey,
        ?int $appliedBy,
        ?float $requestedAmount = null,
        ?string $reason = null,
        ?string $sourceInterface = null,
    ): ProductDiscount {
        return ProductDiscount::firstOrCreate(
            ['idempotency_key' => $idempotencyKey],
            array_merge($result->toArray(), [
                'source_amount' => $requestedAmount,
                'percentage' => null,
                'target_type' => $targetType->value,
                'target_id' => $targetId,
                'customer_id' => $customerId,
                'store_credit_redemption_id' => $redemptionId,
                'applied_by' => $appliedBy,
                'applied_at' => now(),
                'source_interface' => $sourceInterface,
                'reason' => $reason,
                'status' => ProductDiscount::STATUS_APPLIED,
            ]),
        );
    }

    /**
     * Apply a discount to a resolved target. The core entry point.
     *
     * @throws DiscountException on any server-authoritative rejection.
     */
    public function apply(
        DiscountTarget $target,
        DiscountType $type,
        DiscountCalculationType $calculationType,
        ?float $sourceAmount,
        ?float $percentage,
        string $idempotencyKey,
        ?int $appliedBy,
        ?int $expectedCustomerId = null,
        ?string $reason = null,
        ?string $sourceInterface = null,
    ): ProductDiscount {
        // Phase-1 operational gate: only Store Credit may be applied today.
        if (! $type->isOperationalInPhase1()) {
            throw new DiscountException("Discount type '{$type->value}' is not operational in Phase 1.");
        }

        // Idempotency fast-path (pre-txn): same key → return the existing row.
        if ($existing = ProductDiscount::where('idempotency_key', $idempotencyKey)->first()) {
            return $existing;
        }

        $customerId = $target->customerId();
        if ($customerId === null) {
            throw new DiscountException('Discount target has no owning customer.');
        }
        if ($expectedCustomerId !== null && $expectedCustomerId !== $customerId) {
            throw new DiscountException('Discount target does not belong to the specified customer.');
        }

        return DB::transaction(function () use (
            $target, $type, $calculationType, $sourceAmount, $percentage,
            $idempotencyKey, $appliedBy, $customerId, $reason, $sourceInterface
        ) {
            // Serialize concurrent Store Credit spends for this customer.
            Customer::where('id', $customerId)->lockForUpdate()->firstOrFail();

            // Lock + refresh the obligation, then revalidate everything in-txn.
            $target->lockAndRefresh();

            // Re-check idempotency inside the lock (a racing request may have won).
            if ($existing = ProductDiscount::where('idempotency_key', $idempotencyKey)->first()) {
                return $existing;
            }

            if (! $target->isDiscountable()) {
                throw new DiscountException($target->ineligibleReason() ?? 'Target is not discountable.');
            }

            $eligibleBase = round($target->eligibleProductValue(), 2);
            if ($eligibleBase <= 0) {
                throw new DiscountException('Target has no eligible product value to discount.');
            }

            $appliedAmount = $sourceAmount;
            $redemptionId = null;

            if ($type->drawsDownStoreCredit()) {
                $requested = round((float) ($sourceAmount ?? 0), 2);
                if ($requested <= 0) {
                    throw new DiscountException('Store Credit amount must be greater than zero.');
                }
                // Server-authoritative rejections (mirror the UI guards).
                $available = CustomerCreditService::remainingBalance($customerId);
                if ($requested > $available) {
                    throw new DiscountException("Requested {$requested} exceeds available Store Credit {$available}.");
                }
                if ($requested > $eligibleBase) {
                    throw new DiscountException("Requested {$requested} exceeds the eligible product value {$eligibleBase}.");
                }
                $appliedAmount = $requested;
            }

            $result = $this->calculator->calculate(
                $eligibleBase, $type, $calculationType, $appliedAmount, $percentage, $target->taxRate(),
            );

            if ($type->drawsDownStoreCredit()) {
                // Balance reduction as a REDEMPTION — never a payment row. Keyed
                // off the same operation so a retry cannot double-redeem.
                $redemption = CustomerCreditService::redeem(
                    customerId: $customerId,
                    amount: $result->discountAmount,
                    reason: $reason ?? 'Store Credit product discount',
                    responsibleUserId: $appliedBy,
                    idempotencyKey: 'scd_redeem:' . $idempotencyKey,
                );
                $redemptionId = $redemption->id;
            }

            $discount = ProductDiscount::create(array_merge($result->toArray(), [
                'source_amount' => $sourceAmount,
                'percentage' => $percentage,
                'target_type' => $target->targetType()->value,
                'target_id' => $target->targetId(),
                'customer_id' => $customerId,
                'store_credit_redemption_id' => $redemptionId,
                'applied_by' => $appliedBy,
                'applied_at' => now(),
                'source_interface' => $sourceInterface,
                'reason' => $reason,
                'idempotency_key' => $idempotencyKey,
                'status' => ProductDiscount::STATUS_APPLIED,
            ]));

            // Re-price the obligation last, inside the same txn (all-or-nothing).
            $target->applyDiscount($result, $discount);

            return $discount;
        });
    }

    /**
     * Atomically reverse an applied discount: compensating append-only row,
     * Store Credit balance RESTORED (a grant — not a cash refund), obligation
     * pricing restored. Idempotent — a re-reversal is a no-op.
     */
    public function reverse(
        ProductDiscount $discount,
        DiscountTarget $target,
        ?int $reversedBy = null,
        ?string $reason = null,
    ): ProductDiscount {
        if ($discount->isReversed()) {
            return $discount; // already reversed — idempotent
        }

        return DB::transaction(function () use ($discount, $target, $reversedBy, $reason) {
            $discount->refresh();
            if ($discount->isReversed()) {
                return $discount;
            }

            if ($discount->customer_id) {
                Customer::where('id', $discount->customer_id)->lockForUpdate()->firstOrFail();
            }
            $target->lockAndRefresh();

            // Append-only compensating row (original is never deleted/mutated
            // except to link + flag it reversed).
            $reversalKey = 'reversal:' . $discount->idempotency_key;
            $reversalRow = ProductDiscount::firstOrCreate(
                ['idempotency_key' => $reversalKey],
                [
                    'discount_type' => $discount->discount_type->value,
                    'calculation_type' => $discount->calculation_type->value,
                    'calculated_discount_amount' => $discount->calculated_discount_amount,
                    'target_type' => $discount->target_type->value,
                    'target_id' => $discount->target_id,
                    'customer_id' => $discount->customer_id,
                    'original_product_value' => $discount->discounted_product_value,
                    'discounted_product_value' => $discount->original_product_value,
                    'taxable_value_before' => $discount->taxable_value_after,
                    'taxable_value_after' => $discount->taxable_value_before,
                    'tax_before' => $discount->tax_after,
                    'tax_after' => $discount->tax_before,
                    'applied_by' => $reversedBy,
                    'applied_at' => now(),
                    'reason' => $reason ?? 'Reversal of discount #' . $discount->id,
                    'status' => ProductDiscount::STATUS_REVERSED,
                    'metadata' => ['reversal_of' => $discount->id],
                ],
            );

            // Restore Store Credit balance (a grant linked to the reversal).
            if ($discount->discount_type->drawsDownStoreCredit() && $discount->calculated_discount_amount > 0) {
                CustomerCreditService::createFinancialCredit(
                    customerId: (int) $discount->customer_id,
                    amount: (float) $discount->calculated_discount_amount,
                    reason: 'Store Credit discount reversed (balance restored)',
                    responsibleUserId: $reversedBy,
                    idempotencyKey: 'scd_restore:' . $discount->idempotency_key,
                );
            }

            $discount->update([
                'status' => ProductDiscount::STATUS_REVERSED,
                'reversed_by_discount_id' => $reversalRow->id,
            ]);

            $target->reverseDiscount($discount);

            return $reversalRow;
        });
    }
}
