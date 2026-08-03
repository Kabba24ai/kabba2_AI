<?php

namespace App\Services\Goodwill;

use App\Enums\Goodwill\GoodwillReason;
use App\Models\Orders\Order;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Everything needed to apply one Goodwill concession, gathered into a value.
 *
 * The two `expected*` fields are the stale-state contract. They carry what the
 * operator was SHOWN when they clicked approve. The service re-derives both
 * under the row lock and refuses if either has moved, so an approval can never
 * be applied against figures the approver never saw — a payment landing between
 * preview and submit would otherwise change the size of the concession without
 * anyone noticing.
 *
 * Amounts are dollars here because that is what crosses the wire from a form.
 * They are converted to cents once, at the boundary, and every comparison after
 * that is integer.
 */
final class GoodwillApplyRequest
{
    public function __construct(
        public readonly Order $order,
        public readonly GoodwillReason $reason,
        public readonly ?string $note,
        public readonly string $idempotencyKey,
        public readonly ?Authenticatable $operator,
        public readonly ?Authenticatable $approver,
        public readonly float $expectedGoodwillAmount,
        public readonly float $expectedAcceptedPaymentTotal,
        public readonly ?string $sourceInterface = null,
    ) {
    }

    public function expectedGoodwillCents(): int
    {
        return (int) round($this->expectedGoodwillAmount * 100);
    }

    public function expectedAcceptedPaymentCents(): int
    {
        return (int) round($this->expectedAcceptedPaymentTotal * 100);
    }
}
