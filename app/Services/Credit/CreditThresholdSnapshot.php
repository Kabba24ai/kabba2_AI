<?php

namespace App\Services\Credit;

use App\Enums\Credit\CreditThresholdSourceType;
use Carbon\CarbonInterface;

/**
 * Immutable value object describing one qualifying credit-threshold crossing,
 * captured at posting time and carried on CreditThresholdExceededEvent. Pure
 * scalars — no Eloquent models — so it is safe to hold across the afterCommit
 * boundary.
 */
final class CreditThresholdSnapshot
{
    public function __construct(
        public readonly string $idempotencyKey,
        public readonly int $customerId,
        public readonly ?string $customerName,
        public readonly ?int $orderId,
        public readonly ?int $accountRowId,
        public readonly CreditThresholdSourceType $sourceType,
        public readonly ?string $sourceDetail,
        public readonly float $creditLimit,
        public readonly float $balanceBefore,
        public readonly float $exposureAdded,
        public readonly float $balanceAfter,
        public readonly float $amountOverLimit,
        public readonly ?int $responsibleUserId,
        public readonly ?string $responsibleContext,
        public readonly CarbonInterface $occurredAt,
    ) {
    }
}
