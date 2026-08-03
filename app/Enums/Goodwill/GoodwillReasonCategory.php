<?php

namespace App\Enums\Goodwill;

/**
 * Why the business gave something away — the distinction that matters at the
 * ledger level, not the operational detail.
 *
 * A concession granted because Kabba got something wrong is a COST OF FAILURE.
 * A concession granted to win or keep business is a COST OF SALE. They look
 * identical in the accounts — the same dollars leave through the same
 * mechanism — and they mean opposite things: one measures how often the
 * operation breaks, the other measures what the business chose to invest in a
 * relationship. Aggregating them produces a number that answers neither
 * question.
 *
 * This is stored as its own column rather than derived on read so a report can
 * group on it directly. Deriving it would mean every consumer re-implementing
 * the mapping, and the first one to fall out of step would do so silently.
 */
enum GoodwillReasonCategory: string
{
    /** Compensation for an operational failure. A cost of getting it wrong. */
    case ServiceRecovery = 'service_recovery';

    /** A deliberate commercial concession. A cost of winning the business. */
    case BusinessCourtesy = 'business_courtesy';

    /** Deliberately uncategorised — always accompanied by a written note. */
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::ServiceRecovery  => 'Service Recovery',
            self::BusinessCourtesy => 'Business Courtesy',
            self::Other            => 'Other',
        };
    }

    /**
     * What this category means, for a report header or a tooltip. Kept beside
     * the label so the two cannot describe different things.
     */
    public function description(): string
    {
        return match ($this) {
            self::ServiceRecovery  => 'Compensation for an operational problem.',
            self::BusinessCourtesy => 'A deliberate commercial concession.',
            self::Other            => 'Uncategorised — see the accompanying note.',
        };
    }

    /** Every reason belonging to this category, in declaration order. */
    public function reasons(): array
    {
        return array_values(array_filter(
            GoodwillReason::cases(),
            fn (GoodwillReason $reason): bool => $reason->category() === $this,
        ));
    }
}
