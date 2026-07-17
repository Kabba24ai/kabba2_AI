<?php

namespace App\Enums\WaitList;

/**
 * Outcome of working a wait list match alert. A disposition records what
 * happened with THIS match opportunity — it never automatically closes the
 * customer's overall wait list request except for CustomerNoLongerNeeds,
 * where the customer explicitly said the need itself is gone.
 */
enum WaitListAlertDisposition: string
{
    case ContactedNoAnswer     = 'contacted_no_answer';
    case CustomerAccepted      = 'customer_accepted';
    case CustomerDeclined      = 'customer_declined';
    case CustomerNoLongerNeeds = 'customer_no_longer_needs';
    case MatchUnavailable      = 'match_unavailable';
    case KeepWaiting           = 'keep_waiting';

    public function label(): string
    {
        return match ($this) {
            self::ContactedNoAnswer     => 'Contacted — No Answer',
            self::CustomerAccepted      => 'Customer Accepted',
            self::CustomerDeclined      => 'Customer Declined',
            self::CustomerNoLongerNeeds => 'Customer No Longer Needs Equipment',
            self::MatchUnavailable      => 'Match Unavailable',
            self::KeepWaiting           => 'Keep Waiting',
        };
    }

    /**
     * Whether this disposition resolves (closes) the alert. Contacted — No
     * Answer keeps the alert open for another attempt; everything else
     * settles the match opportunity.
     */
    public function resolvesAlert(): bool
    {
        return $this !== self::ContactedNoAnswer;
    }

    /**
     * Whether the customer's overall wait list request should be closed.
     * Only an explicit "no longer needs equipment" cancels the request;
     * Customer Accepted moves toward conversion via the existing manual
     * Convert workflow (staff creates the order, then links it).
     */
    public function closesWaitList(): bool
    {
        return $this === self::CustomerNoLongerNeeds;
    }

    /** Communication-history entry type that best describes this outcome. */
    public function communicationType(): WaitListCommunicationType
    {
        return match ($this) {
            self::ContactedNoAnswer     => WaitListCommunicationType::NoAnswer,
            self::CustomerAccepted      => WaitListCommunicationType::CustomerAccepted,
            self::CustomerDeclined      => WaitListCommunicationType::CustomerDeclined,
            self::CustomerNoLongerNeeds,
            self::MatchUnavailable,
            self::KeepWaiting           => WaitListCommunicationType::InternalNote,
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ContactedNoAnswer     => 'bg-amber-100 text-amber-700',
            self::CustomerAccepted      => 'bg-green-100 text-green-700',
            self::CustomerDeclined      => 'bg-orange-100 text-orange-700',
            self::CustomerNoLongerNeeds => 'bg-gray-100 text-gray-600',
            self::MatchUnavailable      => 'bg-red-100 text-red-700',
            self::KeepWaiting           => 'bg-sky-100 text-sky-700',
        };
    }

    /** value => label pairs for select fields. */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
