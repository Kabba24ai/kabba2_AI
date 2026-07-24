<?php

namespace App\Services\Orders;

use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * THE neutral order-domain definition of whether an order is still
 * OPERATIONALLY ACTIVE for scheduling purposes (Schedule Financial-Closure
 * Alignment, 2026-07-20). Schedule, Dispatch, and Queue Line all consume
 * this one rule — no surface owns its own copy.
 *
 * An order is financially INACTIVE when its money is conclusively gone:
 *
 *  - fully refunded, or refunded under the approved "Full Amount Less Card
 *    Processing Fee" closure-equivalent rule — decided by
 *    RefundedOrderScheduleCloser::isOperationallyCompleteRefund(), the
 *    EXACT predicate the schedule-closure engine itself uses; or
 *  - voided-out: payment rows exist, at least one is Voided, and nothing
 *    settled remains (void-then-recharge stays active automatically — the
 *    replacement payment settles).
 *
 * Everything else stays active: ordinary partial refunds, unpaid orders,
 *  pending payments, partial payments.
 *
 * Context: RefundedOrderScheduleCloser closes undelivered schedule rows
 * ('Close as Completed') at refund/void time, and every schedule surface
 * treats that closure as terminal. This service is the READ-SIDE safeguard
 * for rows the closure engine never touched (chiefly refunds/voids recorded
 * before the engine shipped) — it re-derives the same canonical
 * classification instead of inventing new refund arithmetic.
 *
 * Performance contract: the canonical predicate is query-heavy per order
 * (OrderPaymentSummary + allocation sums), so every batched entry point
 * pre-screens for SUSPECT payment rows first (Voided / Partial Refund /
 * Refunded — one indexed query or an already-loaded relation) and evaluates
 * only suspects, memoized per request. Boards and lists with no refund or
 * void activity pay zero additional queries.
 *
 * The three approved behavioral rules, in plain terms:
 *
 *  1. UNDELIVERED rows on a financially inactive order are excluded from
 *     every actionable schedule surface — they are not work.
 *  2. DELIVERED rows always remain visible: a refund or void after
 *     delivery is financial activity only, and the machine in the field
 *     must stay tracked through its return.
 *  3. A voided order REAPPEARS automatically the moment a replacement
 *     payment settles — nothing here writes state, so the void-to-recharge
 *     correction window is a temporary disappearance, never a closure.
 */
final class OrderFinancialActivity
{
    public const SUSPECT_PAYMENT_STATUSES = [
        OrderPaymentStatus::Voided->value,
        OrderPaymentStatus::PartialRefund->value,
        OrderPaymentStatus::Refund->value,
    ];

    /** @var array<int, bool> per-request memo of order id → isActive */
    private static array $memo = [];

    /** The canonical predicate. Never use for financial reporting/display. */
    public static function isActive(Order $order): bool
    {
        if (array_key_exists($order->id, self::$memo)) {
            return self::$memo[$order->id];
        }

        return self::$memo[$order->id] = self::evaluate($order);
    }

    private static function evaluate(Order $order): bool
    {
        $summary = OrderPaymentSummary::for($order);

        if (RefundedOrderScheduleCloser::isOperationallyCompleteRefund($order, $summary)) {
            return false; // fully refunded (or fee-retained equivalent)
        }

        if ($summary->totalSettledPayments <= 0.0 && $summary->voidedPayments->isNotEmpty()) {
            return false; // voided-out — nothing settled remains
        }

        return true;
    }

    /**
     * Exclude financially inactive orders from an ORDER-PRODUCT query
     * (Schedule list/assignment, Dispatch, mobile schedule feeds) without
     * breaking SQL pagination or counts: suspects are found with one
     * cloned indexed query scoped by the constraints applied so far, only
     * suspects are canonically evaluated (memoized), and the exclusion is
     * applied as a whereNotIn — visible rows and totals can never disagree.
     *
     * SAFETY RULE (mirrors RefundedOrderScheduleCloser exactly): rows with
     * DELIVERY EVIDENCE are never excluded. A refund/void after delivery is
     * financial activity only — the machine is in the field and must stay
     * operationally visible through return. The SQL below is the exact
     * counterpart of OrderProduct::hasBeenDelivered().
     */
    public static function excludeInactiveOrderProducts(Builder $query): Builder
    {
        // reorder() strips any ORDER BY the caller already applied to $query
        // (e.g. Orders\Schedules\IndexController orders by delivery_date
        // before calling this) — MySQL rejects DISTINCT + an ORDER BY column
        // absent from the SELECT list (error 3065), and ordering is
        // meaningless for a plucked list of ids anyway.
        $suspectOrderIds = (clone $query)
            ->reorder()
            ->whereHas('order.payments', fn ($p) => $p->whereIn('status', self::SUSPECT_PAYMENT_STATUSES))
            ->distinct()
            ->pluck('order_products.order_id');

        $inactive = self::inactiveIdsAmong($suspectOrderIds);

        if ($inactive === []) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($inactive) {
            $q->whereNotIn('order_products.order_id', $inactive)
                // hasBeenDelivered() in SQL — delivered rows stay visible
                ->orWhereIn('delivery_status', ['Completed', 'Close as Completed'])
                ->orWhere('is_delivered', true)
                ->orWhere('delivery_is_delivered', true)
                ->orWhereNotNull('delivery_arrived_at');
        });
    }

    /** Same exclusion for a query on ORDERS themselves (e.g. badge counts). */
    public static function excludeInactiveOrders(Builder $query): Builder
    {
        // See excludeInactiveOrderProducts() — same reorder() rationale.
        $suspectOrderIds = (clone $query)
            ->reorder()
            ->whereHas('payments', fn ($p) => $p->whereIn('status', self::SUSPECT_PAYMENT_STATUSES))
            ->distinct()
            ->pluck('orders.id');

        $inactive = self::inactiveIdsAmong($suspectOrderIds);

        return $inactive === []
            ? $query
            : $query->whereNotIn('orders.id', $inactive);
    }

    /**
     * Collection-path filter for already-loaded OrderProduct rows whose
     * order.payments relation is eager-loaded (Queue Line board): the
     * suspect pre-screen reads the loaded relation, so clean boards keep a
     * flat query budget.
     *
     * @param  Collection<int, OrderProduct>  $rows
     * @return Collection<int, OrderProduct>
     */
    public static function filterActiveOrderProducts(Collection $rows): Collection
    {
        return $rows->filter(function (OrderProduct $row) {
            $order = $row->order;

            if (array_key_exists($order->id, self::$memo)) {
                return self::$memo[$order->id];
            }

            $suspect = $order->relationLoaded('payments')
                ? $order->payments->contains(fn ($payment) => in_array(
                    $payment->status?->value ?? $payment->status,
                    self::SUSPECT_PAYMENT_STATUSES,
                    true,
                ))
                : $order->payments()->whereIn('status', self::SUSPECT_PAYMENT_STATUSES)->exists();

            return self::$memo[$order->id] = ($suspect ? self::evaluate($order) : true);
        })->values();
    }

    /** @return array<int, int> ids of financially inactive orders among $orderIds */
    public static function inactiveIdsAmong(Collection|array $orderIds): array
    {
        $ids = collect($orderIds)->unique()->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $unmemoized = $ids->reject(fn ($id) => array_key_exists((int) $id, self::$memo));

        if ($unmemoized->isNotEmpty()) {
            foreach (Order::findMany($unmemoized) as $order) {
                self::isActive($order);
            }
        }

        return $ids
            ->filter(fn ($id) => (self::$memo[(int) $id] ?? true) === false)
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /** Test hook: the memo must never leak between requests/tests. */
    public static function flushMemo(): void
    {
        self::$memo = [];
    }
}
