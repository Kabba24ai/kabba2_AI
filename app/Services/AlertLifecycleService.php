<?php

namespace App\Services;

use App\Enums\Orders\OrderProductChargeStatus;
use App\Exceptions\AlertLifecycleConsistencyException;
use App\Models\Customers\CustomerAccount;
use App\Models\Orders\AlertStatusTransition;
use App\Models\Orders\OrderProduct;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Records Fuel/Damage alert lifecycle transitions and answers the
 * "Completed Today" metric (Dashboard V2 Phase 1B).
 *
 * Design:
 *  - OBSERVATIONAL only. record() is called from inside existing terminal
 *    pathways within their DB transaction; it never changes workflow behavior.
 *  - Source identity mirrors the outstanding-alert universe so Completed Today
 *    reconciles with Outstanding: the OrderProduct is the canonical source for
 *    order-linked alerts; a CustomerAccount is a source ONLY when it has no
 *    order_product_id (pure CRM alert). OP-linked CA rows are represented by
 *    their OrderProduct (matching the dashboard's whereNull('order_product_id')
 *    CRM list), so they are intentionally NOT recorded here — avoiding double
 *    counting. Mobile BillingCharges always carry an order_product_id and are
 *    likewise represented by their OrderProduct.
 *  - Idempotent PER OPERATION, not per status forever. Only the transition
 *    OUT of the queue is recorded (previous status outstanding → new status
 *    terminal). Dedup is keyed on a cycle-aware idempotency_key:
 *    {alert_type}:{source_type}:{source_id}:c{cycleSeq}, where cycleSeq is the
 *    source's prior terminal-transition count + 1. A retry of the same
 *    operation re-reads the (now terminal) live status and is skipped by the
 *    outstanding guard; a reopened alert that transitions again advances
 *    cycleSeq and is recorded as a NEW legitimate lifecycle event. The unique
 *    index on idempotency_key is the concurrency backstop within a cycle.
 */
class AlertLifecycleService
{
    public const TERMINAL_STATUSES = ['resolved', 'completed', 'uncollectible'];

    public const SOURCE_ORDER_PRODUCT   = 'order_product';
    public const SOURCE_CUSTOMER_ACCOUNT = 'customer_account';

    private const BUSINESS_TZ = 'America/Chicago';

    public static function isTerminal(?string $status): bool
    {
        return $status !== null && in_array($status, self::TERMINAL_STATUSES, true);
    }

    /** Outstanding = null OR any non-terminal status. */
    public static function isOutstanding(?string $status): bool
    {
        return ! self::isTerminal($status);
    }

    private static function enumValue($status): ?string
    {
        return $status instanceof OrderProductChargeStatus ? $status->value : $status;
    }

    /**
     * Atomically move an OrderProduct alert to a terminal status and record the
     * lifecycle transition. MUST run inside an open DB transaction: the source
     * row is SELECT … FOR UPDATE locked and its status is RE-READ after the
     * lock (never trusting a pre-loaded instance), so two concurrent terminal
     * operations serialize — the loser re-reads a non-outstanding status and is
     * skipped, guaranteeing the source status matches the recorded lifecycle.
     *
     * Returns the locked+updated OrderProduct, or null if it had already left
     * the queue (caller should stop via its existing already-closed flow).
     */
    public static function transitionOrderProduct(
        int $orderProductId,
        string $alertType,
        string $newStatus,
        ?int $userId = null,
    ): ?OrderProduct {
        $statusField = $alertType === 'fuel' ? 'fuel_charge_status' : 'damage_status';

        $op = OrderProduct::whereKey($orderProductId)->lockForUpdate()->first();
        if (! $op) {
            return null;
        }

        $previous = self::enumValue($op->$statusField);
        if (! self::isOutstanding($previous)) {
            return null; // already terminal — do not overwrite a competing outcome
        }

        $op->$statusField = $newStatus;
        $op->save();

        self::record($alertType, self::SOURCE_ORDER_PRODUCT, (int) $op->id, $previous, $newStatus, $op->order_id, $userId);

        return $op;
    }

    /**
     * Atomically move a CustomerAccount alert to a terminal status. Locks and
     * re-reads the row (same serialization guarantee as above). The lifecycle
     * transition is recorded ONLY for pure-CRM accounts (order_product_id NULL);
     * OP-linked accounts are represented by their OrderProduct. Returns the
     * locked+updated account, or null if it had already left the queue.
     */
    public static function transitionCustomerAccount(
        int $accountId,
        string $alertType,
        string $newStatus,
        ?int $userId = null,
    ): ?CustomerAccount {
        $alertField = $alertType === 'fuel' ? 'fuel_alert_status' : 'damage_alert_status';

        $account = CustomerAccount::whereKey($accountId)->lockForUpdate()->first();
        if (! $account) {
            return null;
        }

        $previous = $account->$alertField;
        if (! self::isOutstanding($previous)) {
            return null;
        }

        $account->$alertField = $newStatus;
        $account->save();

        if ($account->order_product_id === null) {
            self::record($alertType, self::SOURCE_CUSTOMER_ACCOUNT, (int) $account->id, $previous, $newStatus, $account->order_id, $userId);
        }

        return $account;
    }

    /**
     * Record an alert leaving the active queue. No-op unless the alert is
     * genuinely transitioning from outstanding → terminal.
     */
    public static function record(
        string $alertType,
        string $sourceType,
        int $sourceId,
        ?string $previousStatus,
        string $newStatus,
        ?int $orderId = null,
        ?int $userId = null,
        ?int $serviceTicketId = null,
    ): void {
        if (! self::isTerminal($newStatus)) {
            return; // not a terminal transition
        }
        if (! self::isOutstanding($previousStatus)) {
            return; // already terminal — it did not leave the queue now
        }

        // Cycle sequence = how many terminal transitions this source has already
        // logged + 1. First queue-exit → c1; after a reopen, the next queue-exit
        // → c2; and so on. This makes the idempotency key stable for a retry of
        // the current operation (which the outstanding guard already blocks) yet
        // distinct for a genuinely new cycle.
        $cycleSeq = AlertStatusTransition::query()
            ->where('alert_type', $alertType)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->count() + 1;

        $idempotencyKey = "{$alertType}:{$sourceType}:{$sourceId}:c{$cycleSeq}";

        // Under lockForUpdate the same-cycle race cannot happen, but we still
        // treat the unique key as a durable backstop. On collision, fetch the
        // existing row and verify it is the SAME transition (same terminal
        // status). If a row already exists for this cycle with a DIFFERENT
        // new_status, two competing outcomes raced — surface a consistency
        // error so the whole transaction rolls back rather than leaving source
        // status and lifecycle disagreeing. A matching status is a safe retry.
        try {
            $existing = AlertStatusTransition::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                self::assertConsistent($existing, $newStatus, $idempotencyKey);
                return;
            }

            AlertStatusTransition::create([
                'alert_type'        => $alertType,
                'source_type'       => $sourceType,
                'source_id'         => $sourceId,
                'previous_status'   => $previousStatus,
                'new_status'        => $newStatus,
                'idempotency_key'   => $idempotencyKey,
                'order_id'          => $orderId,
                'transitioned_by'   => $userId,
                'service_ticket_id' => $serviceTicketId,
                'transitioned_at'   => now(),
            ]);
        } catch (UniqueConstraintViolationException $e) {
            // A concurrent writer inserted first between our SELECT and INSERT.
            // Re-read and verify it is the same terminal outcome.
            $existing = AlertStatusTransition::where('idempotency_key', $idempotencyKey)->first();
            if (! $existing) {
                throw $e; // not our key — do not swallow
            }
            self::assertConsistent($existing, $newStatus, $idempotencyKey);
        }
    }

    private static function assertConsistent(AlertStatusTransition $existing, string $newStatus, string $key): void
    {
        if ($existing->new_status !== $newStatus) {
            throw new AlertLifecycleConsistencyException(
                "Lifecycle key {$key} already recorded new_status '{$existing->new_status}', "
                . "cannot also record '{$newStatus}' for the same cycle."
            );
        }
    }

    /** Record an OrderProduct-sourced fuel/damage transition. */
    public static function recordOrderProduct(
        OrderProduct $orderProduct,
        string $alertType,
        ?string $previousStatus,
        string $newStatus,
        ?int $userId = null,
    ): void {
        self::record(
            $alertType,
            self::SOURCE_ORDER_PRODUCT,
            (int) $orderProduct->id,
            $previousStatus,
            $newStatus,
            $orderProduct->order_id,
            $userId,
        );
    }

    /**
     * Record a pure-CRM CustomerAccount transition. OP-linked CA rows are
     * skipped — their OrderProduct is the canonical source.
     */
    public static function recordCustomerAccount(
        CustomerAccount $account,
        string $alertType,
        ?string $previousStatus,
        string $newStatus,
        ?int $userId = null,
    ): void {
        if ($account->order_product_id !== null) {
            return; // represented by its OrderProduct
        }

        self::record(
            $alertType,
            self::SOURCE_CUSTOMER_ACCOUNT,
            (int) $account->id,
            $previousStatus,
            $newStatus,
            $account->order_id,
            $userId,
        );
    }

    /**
     * Count of DISTINCT alert sources that left the queue today (business tz)
     * for the given alert type. Backs the "Completed Today" donut segment.
     */
    public static function completedTodayCount(string $alertType): int
    {
        // Stored timestamps use config('app.timezone'); express the business-tz
        // day boundaries in that zone so the comparison is correct regardless
        // of the app timezone setting.
        $storeTz = config('app.timezone') ?: 'UTC';
        $start = Carbon::now(self::BUSINESS_TZ)->startOfDay()->setTimezone($storeTz);
        $end   = Carbon::now(self::BUSINESS_TZ)->endOfDay()->setTimezone($storeTz);

        return (int) AlertStatusTransition::query()
            ->where('alert_type', $alertType)
            ->whereIn('new_status', self::TERMINAL_STATUSES)
            ->whereBetween('transitioned_at', [$start, $end])
            ->distinct()
            ->count(DB::raw("CONCAT(source_type, '-', source_id)"));
    }

    /**
     * Distinct alert sources that left the queue during the CURRENT calendar
     * week for the given alert type. The week runs Sunday 00:00 → Saturday
     * 23:59:59 in the business timezone (resets each Sunday). Companion to the
     * daily completedTodayCount; both count distinct sources, not raw rows.
     */
    public static function resolvedThisWeekCount(string $alertType): int
    {
        $storeTz = config('app.timezone') ?: 'UTC';
        $start = Carbon::now(self::BUSINESS_TZ)->startOfWeek(Carbon::SUNDAY)->setTimezone($storeTz);
        $end   = Carbon::now(self::BUSINESS_TZ)->endOfWeek(Carbon::SATURDAY)->setTimezone($storeTz);

        return (int) AlertStatusTransition::query()
            ->where('alert_type', $alertType)
            ->whereIn('new_status', self::TERMINAL_STATUSES)
            ->whereBetween('transitioned_at', [$start, $end])
            ->distinct()
            ->count(DB::raw("CONCAT(source_type, '-', source_id)"));
    }
}
