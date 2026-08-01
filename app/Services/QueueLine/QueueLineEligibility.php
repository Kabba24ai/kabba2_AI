<?php

namespace App\Services\QueueLine;

use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Services\Orders\OrderFinancialActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * THE single owner of Queue Line eligibility, buckets, ordering, and card
 * classification. No controller, Livewire component, Blade template,
 * listener, or JS may re-implement these rules.
 *
 * Eligibility is COMPUTED live from order_products — Queue Line never copies
 * schedule state. The approved predicate (audit §5):
 *   Rental product_type + order exists (soft-delete gate) +
 *   delivery_status='Pending' + delivery_date NOT NULL +
 *   delivery_date within the date window + transport mode Truck or Store.
 * The window is the web board's Show toggle (DispatchDateRangeMode:
 * today / 2 days / all, end-bound only; 2 Days = the original prep
 * window of today + tomorrow); range-less callers keep the same
 * <= tomorrow cap — see eligibleQuery().
 * Payment state NEVER affects eligibility (badge is display-only).
 * The canonical date is the scheduled delivery_date — dispatch_delivery_date
 * is a Dispatch-only override and is deliberately NOT consulted.
 */
final class QueueLineEligibility
{
    public const BUCKET_OVERDUE = 'overdue';

    public const BUCKET_TODAY = 'today';

    public const BUCKET_TOMORROW = 'tomorrow';

    public const ASSIGNMENT_DIRECT = 'direct';

    public const ASSIGNMENT_ALTERNATE = 'alternate';

    /** Equipment is soft-assigned but its assigned_product_id is NULL —
     *  never auto-classified as Alternate (approved rule). */
    public const ASSIGNMENT_UNKNOWN = 'unknown';

    public const ASSIGNMENT_UNASSIGNED = 'unassigned';

    public const STATUS_PENDING = 'pending';

    public const STATUS_STAGED = 'staged';

    public const STATUS_COMPLETED = 'completed';

    /**
     * The Phase 1 baseline eligibility predicate — every clause mirrors an
     * existing Schedule/Dispatch predicate except the upper date bound.
     *
     * Date window (2026-08-01): when a $range is given (the web board's
     * Show: All | 2 Days | Today Only toggle — the SAME DispatchDateRangeMode
     * the Dispatch and Schedule screens share), the bound is range-driven:
     * Today → <= today, 2 Days → <= today+1 (the original prep window of
     * today + tomorrow), All → no upper bound. End-bound only, so overdue
     * work surfaces in every mode. With NO range (fuel verification, release
     * guard, mobile presenter) the legacy hard cap of <= tomorrow applies
     * unchanged — identical to the 2 Days window.
     */
    public static function eligibleQuery(?\App\Enums\Dispatch\DispatchDateRangeMode $range = null): Builder
    {
        // null $end (range All) = unbounded; legacy default = through tomorrow.
        $end = $range !== null ? $range->endDate() : today()->addDay();

        return OrderProduct::query()
            ->where('product_data->product_type', 'Rental')
            ->whereHas('order')
            ->where('delivery_status', 'Pending')
            ->whereNotNull('delivery_date')
            ->when($end !== null, fn (Builder $q) => $q->whereDate('delivery_date', '<=', $end))
            ->whereIn('delivery_transport_mode', ['Truck', 'Store']);
    }

    /**
     * The active board feed: eligible rows minus suppressed/completed ones,
     * with every relation a card needs eager-loaded. Store filter uses the
     * canonical outbound store attribution (delivery_store_id). $range is the
     * board's date-window toggle (see eligibleQuery()); callers that omit it
     * keep the legacy through-tomorrow window.
     */
    public static function boardQuery(?int $storeId = null, ?\App\Enums\Dispatch\DispatchDateRangeMode $range = null): Builder
    {
        return self::eligibleQuery($range)
            ->when($storeId, fn (Builder $q) => $q->where('delivery_store_id', $storeId))
            ->whereDoesntHave('queueLineItem', function (Builder $q) {
                $q->where(function (Builder $q) {
                    $q->where('suppressed_forever', true)
                        ->orWhereDate('suppressed_on', today())
                        ->orWhereNotNull('completed_at');
                });
            })
            ->with([
                'order.lastPayment',
                'order.payments',                 // financial-activity pre-screen (filterFinanciallyActive)
                'product:id,unique_id,product_name',
                'product.mediaChildren',          // image_url accessor source (Phase 2 cards)
                'deliveryStore:id,unique_id,store_name',
                'queueLineItem.stagedBy:id,first_name,last_name',
                ...self::equipmentEagerLoads(),
            ]);
    }

    /**
     * Every relation a card needs off the assigned equipment — loaded under
     * BOTH softAssignment.equipment AND the hard equipment relation.
     *
     * Completion (SaveDeliveryController) deletes the soft assignment row
     * once an item is delivered — the physical unit lives on ONLY via
     * OrderProduct::equipment() (the hard FK) from that point on. Pending/
     * Staged items are soft-assigned only, Completed items are typically
     * hard-assigned only; loading both keeps a single serialize() code path
     * correct everywhere instead of branching on which one is populated.
     */
    public static function equipmentEagerLoads(): array
    {
        $suffixes = [
            'assignedProduct:id,product_name',
            'assignedProduct.mediaChildren', // substitute cards show the ASSIGNED product's image (UI Iteration 1)
            'store:id,store_name',           // Wrong Location badge (display-only)
            'activeEquipmentRentalReadyTemplate', // RR badge
            // Everything QueueLineMobilePresenter::equipmentResourceWithChecklists()
            // needs to attach checklistQA/rentalReadyQA without N+1 — the SAME
            // relation chains Equipment\IndexController and Orders\IndexController
            // eager-load for the identical computation.
            'checklistMaster.customerAdminTemplate.templateQuestions.question.answers',
            'checklistMaster.customerAdminTemplate.templateQuestions.question.category',
            'checklistMaster.rentalReadyTemplate.templateQuestions.question.answers',
            'checklistMaster.rentalReadyTemplate.templateQuestions.question.category',
            'orderProduct.checklistQuestions',
            'orderProduct.equipmentRentalReadyTemplate.checklistQuestions',
        ];

        $loads = [];
        foreach ($suffixes as $suffix) {
            $loads[] = "softAssignment.equipment.{$suffix}";
            $loads[] = "equipment.{$suffix}";
        }

        return $loads;
    }

    /**
     * The mobile board's Completed section (2026-07-23) — parity with the
     * web board's "Delivered Today" feed (Board::deliveredToday()): scoped to
     * completed_at = the CURRENT operational day, not the pending/staged
     * eligibility window (delivery_date <= tomorrow is irrelevant here — an
     * item can be completed today regardless of when it was due). No
     * financial-activity filter either, matching the web feed — a completed
     * hand-off is a historical fact, never retroactively hidden by a later
     * void/refund. The web board never calls this — it renders the same rows
     * directly from QueueLineItem via deliveredToday(); this exists solely
     * for the mobile module's three-section view.
     */
    public static function completedQuery(?int $storeId = null): Builder
    {
        return OrderProduct::query()
            ->whereHas('order')
            ->whereHas('queueLineItem', fn (Builder $q) => $q->whereDate('completed_at', today()))
            ->when($storeId, fn (Builder $q) => $q->where('delivery_store_id', $storeId))
            ->with([
                'order.lastPayment',
                'product:id,unique_id,product_name',
                'product.mediaChildren',
                'deliveryStore:id,unique_id,store_name',
                'queueLineItem.stagedBy:id,first_name,last_name',
                ...self::equipmentEagerLoads(),
            ]);
    }

    /** Pending (not staged) | Staged (on Queue Line, not yet completed) | Completed. */
    public static function boardStatus(OrderProduct $row): string
    {
        $item = $row->queueLineItem;

        return match (true) {
            $item?->completed_at !== null => self::STATUS_COMPLETED,
            $item?->isStaged() ?? false => self::STATUS_STAGED,
            default => self::STATUS_PENDING,
        };
    }

    /**
     * Active-order rule (refinement 2026-07-20; extracted to the neutral
     * order domain during the Schedule Financial-Closure Alignment):
     * voided-out and fully refunded orders are no longer active orders and
     * must never appear on Queue Line. The rule itself lives in
     * OrderFinancialActivity — the SAME definition Schedule and Dispatch
     * consume; Queue Line does not own it. These wrappers exist only so
     * queue callers keep one import.
     */
    public static function isOrderFinanciallyActive(Order $order): bool
    {
        return OrderFinancialActivity::isActive($order);
    }

    /**
     * Board-path batched wrapper (order.payments is eager-loaded by
     * boardQuery, so the suspect pre-screen costs zero queries — boards
     * with no refund/void activity keep their flat query budget).
     *
     * @param  Collection<int, OrderProduct>  $rows  boardQuery() results
     * @return Collection<int, OrderProduct>
     */
    public static function filterFinanciallyActive(Collection $rows): Collection
    {
        return OrderFinancialActivity::filterActiveOrderProducts($rows);
    }

    /**
     * Overdue < today | Today = today | Tomorrow = today+1 (date-only, app
     * timezone). Under the wider All / 2 Days windows, every date beyond
     * today falls in the Tomorrow bucket — harmless, because the bucket is
     * an ORDERING rank only (never displayed) and sortItems() breaks rank
     * ties by delivery_date, so later dates still sort correctly.
     */
    public static function bucketFor(OrderProduct $row): string
    {
        $date = Carbon::parse($row->delivery_date)->startOfDay();

        return match (true) {
            $date->lt(today()) => self::BUCKET_OVERDUE,
            $date->isToday() => self::BUCKET_TODAY,
            default => self::BUCKET_TOMORROW,
        };
    }

    /**
     * Three explicit assignment states (approved):
     *  direct    — assigned unit's assigned_product_id === ordered product_id
     *  alternate — both ids exist and differ
     *  unknown   — unit assigned but its assigned_product_id is NULL
     *  unassigned — no soft assignment (renders the Needs Equipment row)
     */
    public static function classifyAssignment(OrderProduct $row): string
    {
        // Completion (SaveDeliveryController) deletes the soft assignment —
        // a delivered item's unit lives on only via the hard equipment FK.
        // Pending/Staged rows are never hard-assigned yet, so this fallback
        // is a no-op for them; it only ever activates for Completed rows.
        $equipment = $row->softAssignment?->equipment ?? $row->equipment;

        if (! $equipment) {
            return self::ASSIGNMENT_UNASSIGNED;
        }

        if ($equipment->assigned_product_id === null) {
            return self::ASSIGNMENT_UNKNOWN;
        }

        return (int) $equipment->assigned_product_id === (int) $row->product_id
            ? self::ASSIGNMENT_DIRECT
            : self::ASSIGNMENT_ALTERNATE;
    }

    /**
     * Canonical Options count: every selected option entry in the frozen
     * order snapshot, INCLUDING $0 options (they are ordinary entries —
     * audit §12). quantity never multiplies this (it counts rental periods).
     */
    public static function optionsCount(OrderProduct $row): int
    {
        return count($row->product_data['product_option_items'] ?? []);
    }

    public static function deliveryTypeLabel(OrderProduct $row): string
    {
        return $row->delivery_transport_mode === 'Truck'
            ? 'Delivery Truck'
            : 'Delivery In Store';
    }

    public const RR_READY = 'Rental Ready';

    public const RR_DRAFT = 'Draft / Incomplete';

    public const RR_DAMAGED = 'Damaged';

    public const RR_NOT_INSPECTED = 'Not Inspected';

    /**
     * Rental Ready DISPLAY status for the soft-assigned unit — read from the
     * latest inspection record (activeEquipmentRentalReadyTemplate, the same
     * relation the Schedule page trusts). Display-only: Rental Ready never
     * gates eligibility or staging (audit §8/§13).
     */
    public static function rentalReadyLabel(OrderProduct $row): string
    {
        $template = $row->softAssignment?->equipment?->activeEquipmentRentalReadyTemplate;

        if (! $template) {
            return self::RR_NOT_INSPECTED;
        }

        return match ($template->status) {
            'Rental Ready' => self::RR_READY,
            'Damaged' => self::RR_DAMAGED,
            default => self::RR_DRAFT, // 'Draft' or any legacy value
        };
    }

    /**
     * Approved ordering: RUSH → Overdue → Today → Tomorrow → delivery_date →
     * delivery_time (nulls last) → order_product id (stable tie-breaker).
     */
    public static function sortItems(Collection $rows): Collection
    {
        $bucketRank = [
            self::BUCKET_OVERDUE => 0,
            self::BUCKET_TODAY => 1,
            self::BUCKET_TOMORROW => 2,
        ];

        return $rows->sortBy(function (OrderProduct $row) use ($bucketRank) {
            return [
                $row->queueLineItem?->isRushed() ? 0 : 1,
                $bucketRank[self::bucketFor($row)],
                $row->delivery_date,
                $row->delivery_time === null ? 1 : 0, // nulls after any time
                $row->delivery_time ?? '',
                $row->id,
            ];
        })->values();
    }
}
