<?php

namespace App\Services\WaitList;

use App\Enums\WaitList\WaitListAlertDisposition;
use App\Models\WaitList\EquipmentWaitList;
use App\Models\WaitList\EquipmentWaitListAlert;

/**
 * Canonical Wait List counts. Every surface (index stat cards, the future
 * dashboard widget, reports) must read these methods instead of rebuilding
 * the logic — especially the distinction between raw match ALERTS and
 * customer-contact OPPORTUNITIES:
 *
 *   - one wait-list record matched by three returned units is ONE
 *     opportunity (one customer to call), and
 *   - one returned unit matching three records is THREE opportunities.
 *
 * "Open" alerts are Contact Needed + In Progress (a Contacted — No Answer
 * outcome keeps the match actionable); Resolved matches — including Keep
 * Waiting — never count, even though a Keep Waiting leaves the underlying
 * request active for future returns.
 */
class WaitListStats
{
    /** Records that still represent live customer demand (Active + Acknowledged). */
    public static function activeWaitingRecords(): int
    {
        return EquipmentWaitList::waiting()->count();
    }

    /**
     * THE future dashboard number: distinct active wait-list records with at
     * least one unresolved match requiring customer contact.
     */
    public static function contactOpportunities(): int
    {
        return EquipmentWaitList::waiting()
            ->whereHas('alerts', fn ($a) => $a->open())
            ->count();
    }

    /** Raw unresolved match alerts — only for detailed alert-level views. */
    public static function openMatchAlerts(): int
    {
        return EquipmentWaitListAlert::open()->count();
    }

    /** Distinct records whose first unresolved match appeared today. */
    public static function newOpportunitiesToday(): int
    {
        return EquipmentWaitList::waiting()
            ->whereHas('alerts', fn ($a) => $a->open()->whereDate('created_at', now()->toDateString()))
            ->count();
    }

    /** Customers who said yes but whose order has not been converted yet. */
    public static function acceptedAwaitingConversion(): int
    {
        return EquipmentWaitList::acceptedAwaitingConversion()->count();
    }
}
