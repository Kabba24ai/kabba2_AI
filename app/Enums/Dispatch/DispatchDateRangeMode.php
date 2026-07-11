<?php

namespace App\Enums\Dispatch;

use Illuminate\Support\Carbon;

/**
 * The Dispatch page's "Show" date-range filter — drives both the Driver
 * Workload cards and the main table from a single control. Adding a future
 * range (7 Days, This Week, Custom Range, etc.) only requires a new case
 * plus a branch in endDate() — no changes to the query code that consumes
 * it, which only ever asks for an end date.
 */
enum DispatchDateRangeMode: string
{
    case All       = 'all';
    case ThreeDays = '3_days';
    case Today     = 'today';

    public static function fromRequest(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::Today;
    }

    public function label(): string
    {
        return match ($this) {
            self::All       => 'All',
            self::ThreeDays => '3 Days',
            self::Today     => 'Today',
        };
    }

    /**
     * End of the window, or null for no restriction. Deliberately end-only
     * (no start bound): the existing Today filter has always matched
     * "<= today" so overdue items keep surfacing alongside today's work
     * instead of silently dropping off the list. 3 Days reuses that exact
     * semantics with the end date pushed out two more days, rather than
     * introducing a lower bound — overdue items still show under both
     * modes exactly as they always have under Today.
     */
    public function endDate(): ?Carbon
    {
        return match ($this) {
            self::All       => null,
            self::Today     => today(),
            self::ThreeDays => today()->addDays(2),
        };
    }

    /**
     * Whether this range can span more than one calendar date — used by the
     * Driver Workload cards to decide whether to print each job's date
     * (redundant when everything shown is necessarily "today").
     */
    public function spansMultipleDates(): bool
    {
        return $this !== self::Today;
    }
}
