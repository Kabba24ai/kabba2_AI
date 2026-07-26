<?php

namespace App\Enums\Credit;

/**
 * What happened to the management-review task when this event was recorded.
 * The event is ALWAYS persisted; this records the task side-effect's fate so
 * a failure (e.g. no Primary Billing Admin configured) is visible and
 * recoverable rather than silent.
 */
enum CreditReviewTaskOutcome: string
{
    case Created = 'created';                 // a new review task was created + assigned
    case Appended = 'appended';               // appended to the customer's existing open task
    case DeferredNoAdmin = 'deferred_no_admin'; // task created unassigned (no active Primary Billing Admin)
    case Failed = 'failed';                   // task creation threw; event preserved, needs recovery

    public function label(): string
    {
        return match ($this) {
            self::Created         => 'Task Created',
            self::Appended        => 'Appended to Open Task',
            self::DeferredNoAdmin => 'Unassigned — No Billing Admin',
            self::Failed          => 'Task Creation Failed',
        };
    }

    public function needsAttention(): bool
    {
        return in_array($this, [self::DeferredNoAdmin, self::Failed], true);
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Created         => 'bg-green-100 text-green-800',
            self::Appended        => 'bg-blue-100 text-blue-800',
            self::DeferredNoAdmin => 'bg-amber-100 text-amber-800',
            self::Failed          => 'bg-red-100 text-red-800',
        };
    }
}
