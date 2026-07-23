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
 *   delivery_date <= tomorrow + transport mode Truck or Store.
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
     * existing Schedule/Dispatch predicate except the tomorrow upper bound
     * (net-new by design).
     */
    public static function eligibleQuery(): Builder
    {
        return OrderProduct::query()
            ->where('product_data->product_type', 'Rental')
            ->whereHas('order')
            ->where('delivery_status', 'Pending')
            ->whereNotNull('delivery_date')
            ->whereDate('delivery_date', '<=', today()->addDay())
            ->whereIn('delivery_transport_mode', ['Truck', 'Store']);
    }

    /**
     * The active board feed: eligible rows minus suppressed/completed ones,
     * with every relation a card needs eager-loaded. Store filter uses the
     * canonical outbound store attribution (delivery_store_id).
     */
    public static function boardQuery(?int $storeId = null): Builder
    {
        return self::eligibleQuery()
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
                'softAssignment.equipment.assignedProduct:id,product_name',
                'softAssignment.equipment.assignedProduct.mediaChildren', // substitute cards show the ASSIGNED product's image (UI Iteration 1)
                'softAssignment.equipment.store:id,store_name',           // Wrong Location badge (display-only)
                'softAssignment.equipment.activeEquipmentRentalReadyTemplate', // RR badge
                'queueLineItem',
            ]);
    }

    /**
     * The mobile board's Completed section (2026-07-23) — same date/product/
     * transport window as boardQuery, but for items whose Queue Line row is
     * ALREADY completed. delivery_status is deliberately NOT filtered here
     * (a completed row is no longer 'Pending'). The web board never calls
     * this — it intentionally excludes completed items via boardQuery.
     */
    public static function completedQuery(?int $storeId = null): Builder
    {
        return OrderProduct::query()
            ->where('product_data->product_type', 'Rental')
            ->whereHas('order')
            ->whereNotNull('delivery_date')
            ->whereDate('delivery_date', '<=', today()->addDay())
            ->whereIn('delivery_transport_mode', ['Truck', 'Store'])
            ->when($storeId, fn (Builder $q) => $q->where('delivery_store_id', $storeId))
            ->whereHas('queueLineItem', fn (Builder $q) => $q->whereNotNull('completed_at'))
            ->with([
                'order.lastPayment',
                'order.payments',
                'product:id,unique_id,product_name',
                'product.mediaChildren',
                'deliveryStore:id,unique_id,store_name',
                'softAssignment.equipment.assignedProduct:id,product_name',
                'softAssignment.equipment.assignedProduct.mediaChildren',
                'softAssignment.equipment.store:id,store_name',
                'softAssignment.equipment.activeEquipmentRentalReadyTemplate',
                'queueLineItem',
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

    /** Overdue < today | Today = today | Tomorrow = today+1 (date-only, app timezone). */
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
        $equipment = $row->softAssignment?->equipment;

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
