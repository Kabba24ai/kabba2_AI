<?php

namespace App\Services\Goodwill;

use App\Enums\Goodwill\GoodwillReason;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAccount;
use App\Models\Goodwill\OrderGoodwillAdjustment;
use App\Models\Orders\Order;
use App\Models\Orders\OrderPayment;
use App\Services\Discounts\DiscountApplicationService;
use App\Services\Discounts\Targets\OrderDiscountTarget;
use App\Services\ReceiptService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * The Goodwill workflow: decide whether a concession may be granted, how large
 * it must be, and record who authorized it.
 *
 * ── WHAT THIS CLASS DOES NOT DO ───────────────────────────────────────────
 *
 * It computes no tax, no special tax, no fee, no line allocation and no net
 * revenue. It asks {@see GoodwillConcessionSolver} how large the concession
 * must be — which in turn only asks the shared engine — and then hands that
 * figure to {@see DiscountApplicationService::applyGoodwill()}, which does all
 * the pricing. The only arithmetic here is comparing integers for staleness.
 *
 * ── GOODWILL IS NOT TENDER ────────────────────────────────────────────────
 *
 * No payment row is created, ever. The customer's real payment was recorded
 * before this ran and is untouched by both apply and reverse. Goodwill moves
 * the ORDER down to meet the money, never the other way round.
 *
 * ── LOCK ORDER ────────────────────────────────────────────────────────────
 *
 *     customer → order → order payments
 *
 * Customer before order is the sequence
 * `DiscountApplicationService::performApply()` uses; inverting it here would
 * deadlock against a concurrent Store Credit application on the same customer.
 *
 * ── CLOSING THE CONCURRENT-PAYMENT RACE ───────────────────────────────────
 *
 * The concession is sized against what has been collected, so a payment landing
 * between reading that total and committing would settle the order against a
 * figure that was already obsolete. Isolation alone does not prevent it: a
 * repeatable read gives a stable view, not exclusion from writers.
 *
 * Two distinct hazards, closed by two distinct locks:
 *
 *   1. A NEW payment row for this order. Blocked by the exclusive lock on the
 *      `orders` row — InnoDB takes a shared lock on the parent row when
 *      inserting a child, and `order_payments_order_id_foreign` makes this
 *      order that parent. No extra statement is needed; the lock we already
 *      hold does it.
 *
 *   2. An EXISTING payment transitioning into a settled state — Pending to
 *      Paid changes the total without inserting anything, so the parent lock
 *      cannot see it. Blocked by a locking read over the order's payment rows.
 *
 * ── WHY READ COMMITTED ────────────────────────────────────────────────────
 *
 * At REPEATABLE READ that second lock is a next-key lock on a secondary index,
 * and its gaps extend past this order. Measured against the deployed schema:
 * locking a mid-range order blocked an insert for a LOWER unrelated order, and
 * locking the highest order carrying payments blocked an insert for a NEWER
 * order with no rows at all — which is the common shape, since Goodwill runs on
 * recently-paid orders. Payments for unrelated customers would have queued
 * behind an unrelated concession.
 *
 * READ COMMITTED takes no gap locks, so the range lock covers exactly the rows
 * that exist for this order and nothing adjacent. Verified: same-order insert
 * blocks, same-order status update blocks, other orders' inserts and updates
 * proceed immediately.
 *
 * Losing repeatable reads costs nothing here. Every figure the decision rests
 * on — the order, its payments, the existing adjustments — is read under an
 * explicit lock inside this transaction and re-derived there, which is what the
 * stale-state check already requires.
 *
 * The statement is issued WITHOUT `SESSION` or `GLOBAL`, so MySQL applies it to
 * the next transaction only and the connection reverts to its default
 * afterwards. It is also skipped entirely when a transaction is already open,
 * because it would then silently apply to the wrong one.
 *
 * ── WHAT IS NOT IN HERE ───────────────────────────────────────────────────
 *
 * No gateway call, HTTP request or queue dispatch. The window a same-order
 * payment can wait for is a few local statements.
 */
final class GoodwillAdjustmentService
{
    public function __construct(
        private readonly DiscountApplicationService $discounts,
    ) {
    }

    // ── Preview ────────────────────────────────────────────────────────────

    /**
     * What a Goodwill concession would do to this order. Read-only: no lock, no
     * write, nothing persisted.
     *
     * Shares every guard and the whole solver with {@see self::apply()}, so an
     * operator is never offered an action the writer would refuse, and never
     * shown a figure the writer would recompute differently.
     *
     * @throws GoodwillException
     */
    public function preview(Order $order, ?Authenticatable $user): GoodwillSolution
    {
        // Fail closed: a null user is not a public preview.
        if (! GoodwillPermissions::canApply($user)) {
            throw GoodwillException::because(GoodwillFailure::Unauthorized);
        }

        $target = new OrderDiscountTarget((int) $order->id);

        // Deliberately UNLOCKED. A preview must not hold a payment range open
        // while an operator reads the screen and decides; it is advisory by
        // definition, and `apply()` re-derives everything under the lock and
        // refuses if anything moved.
        $acceptedCents = $this->settledCentsFrom($this->orderPayments((int) $order->id));

        $this->assertEligible($order->fresh(), $target, $acceptedCents);

        return GoodwillConcessionSolver::solve($target, $acceptedCents);
    }

    /**
     * Could a Goodwill adjustment be applied to this order right now?
     *
     * For DISPLAY ONLY — whether to offer the action, never whether to permit
     * it. Read-only, unlocked, and advisory in exactly the way `preview()` is;
     * `apply()` re-checks every one of these guards under the row lock and is
     * the only authority.
     *
     * It exists so a template does not have to re-implement eligibility. A view
     * that answered this question for itself would drift from the writer the
     * first time a rule changed, and would offer operators actions the server
     * then refused.
     */
    public function isAvailableFor(Order $order): bool
    {
        try {
            $fresh = $order->fresh();

            $this->assertEligible(
                $fresh,
                new OrderDiscountTarget((int) $fresh->id),
                $this->settledCentsFrom($this->orderPayments((int) $fresh->id)),
            );

            return true;
        } catch (GoodwillException) {
            return false;
        }
    }

    // ── Apply ──────────────────────────────────────────────────────────────

    /**
     * Grant a Goodwill concession so the order's revised total equals what was
     * actually collected.
     *
     * One transaction. The receipt refresh is deliberately outside it — see
     * below.
     *
     * @throws GoodwillException on any business refusal
     */
    public function apply(GoodwillApplyRequest $request): OrderGoodwillAdjustment
    {
        // 1. Authority, before anything is read. The token cannot be minted
        //    without a direct Spatie check on both the operator and the
        //    approving manager.
        $authorization = GoodwillAuthorization::grant($request->operator, $request->approver);

        // 2. Reason and note. Cheap, and independent of order state.
        $this->assertNoteAccompaniesReason($request->reason, $request->note);

        // 3. Idempotency, before opening a transaction: a replayed submission
        //    returns the original decision rather than re-doing any work.
        if ($existing = $this->findByKey($request->idempotencyKey)) {
            return $existing;
        }

        $adjustment = $this->inReadCommittedTransaction(function () use ($request, $authorization) {
            $orderId = (int) $request->order->id;

            // 4. Locks, customer before order (see the class docblock).
            if ($request->order->customer_id) {
                Customer::where('id', $request->order->customer_id)->lockForUpdate()->firstOrFail();
            }

            $target = new OrderDiscountTarget($orderId);
            $target->lockAndRefresh();

            $order = Order::where('id', $orderId)->firstOrFail();

            // 5. The order's payment rows, LOCKED. Everything downstream is
            //    derived from this collection rather than from a second,
            //    unlocked aggregate — an aggregate would re-open the very race
            //    this exists to close, because isolation alone gives neither
            //    insert exclusion nor a guarantee about what a later read sees.
            $payments = $this->lockOrderPayments($orderId);

            // 6. Idempotency again, inside the lock — a racing request may have
            //    won between the check above and here.
            if ($existing = $this->findByKey($request->idempotencyKey)) {
                return $existing;
            }

            // 7. Every eligibility guard, under the lock, against fresh state.
            $acceptedCents = $this->settledCentsFrom($payments);

            $this->assertEligible($order, $target, $acceptedCents);

            // 8. Stale state: refuse if the money moved since the preview.
            if ($acceptedCents !== $request->expectedAcceptedPaymentCents()) {
                throw GoodwillException::because(
                    GoodwillFailure::StaleState,
                    'Collected total is now '.number_format($acceptedCents / 100, 2)
                    .', not '.number_format($request->expectedAcceptedPaymentTotal, 2).'.'
                );
            }

            $before = $this->snapshot($order, $acceptedCents);

            // 9. Size the concession through the shared engine. The bound is
            //    enforced here, before any write — the model guard and the
            //    CHECK constraint are backstops, never control flow.
            $solution = GoodwillConcessionSolver::solve($target, $acceptedCents);

            if ($solution->concessionCents !== $request->expectedGoodwillCents()) {
                throw GoodwillException::because(
                    GoodwillFailure::StaleState,
                    'The required concession is now '.number_format($solution->concession(), 2)
                    .', not '.number_format($request->expectedGoodwillAmount, 2).'.'
                );
            }

            // 10. The shared engine does all the pricing: discount row, tax,
            //     special tax, protected fees, line allocations, reconciliation.
            $discount = $this->discounts->applyGoodwill(
                $target,
                $solution->concession(),
                'goodwill:'.$request->idempotencyKey,
                $authorization,
                $request->order->customer_id ? (int) $request->order->customer_id : null,
                'Goodwill — '.$request->reason->label(),
                $request->sourceInterface,
            );

            $order->refresh();

            // 11. The audit record, linked to the discount and the payment.
            //     The payment linkage comes from the LOCKED rows, so the audit
            //     names exactly the payments the concession was sized against.
            return $this->persist(
                $request, $authorization, $solution, $discount->id, $order,
                $before, $acceptedCents, $this->settledFrom($payments),
            );
        });

        // 12. Receipt refresh, AFTER commit and deliberately so.
        //     ReceiptService swallows its own write failures (Release 2 backlog
        //     item 8), so a failure inside the transaction would neither roll it
        //     back nor surface. The refresh is idempotent and self-heals on the
        //     next read; the ledger write does not.
        $this->refreshReceipt($request->order->fresh());

        return $adjustment;
    }

    // ── Reverse ────────────────────────────────────────────────────────────

    /**
     * Withdraw a Goodwill concession. Only this adjustment is reversed — Store
     * Credit and every other active discount on the order survive, because the
     * allocation ledger reverses by discount id.
     *
     * The order's balance re-opens. That is the intended consequence.
     *
     * @throws GoodwillException
     */
    public function reverse(
        OrderGoodwillAdjustment $adjustment,
        ?Authenticatable $operator,
        string $reason,
    ): OrderGoodwillAdjustment {
        $authorization = GoodwillAuthorization::grantReversal($operator);

        $reversed = $this->inReadCommittedTransaction(function () use ($adjustment, $authorization, $reason) {
            $orderId = (int) $adjustment->order_id;

            $order = Order::where('id', $orderId)->firstOrFail();

            if ($order->customer_id) {
                Customer::where('id', $order->customer_id)->lockForUpdate()->firstOrFail();
            }

            $target = new OrderDiscountTarget($orderId);
            $target->lockAndRefresh();

            $locked = OrderGoodwillAdjustment::where('id', $adjustment->id)->lockForUpdate()->firstOrFail();

            if ($locked->isReversed()) {
                throw GoodwillException::because(GoodwillFailure::AlreadyReversed);
            }

            $this->assertReversible($order->fresh(), $locked);

            $reversalRow = $this->discounts->reverseGoodwill(
                $locked->productDiscount,
                $authorization,
                $reason,
            );

            $locked->update([
                'status' => OrderGoodwillAdjustment::STATUS_REVERSED,
                'reversal_product_discount_id' => $reversalRow->id,
                'reversed_at' => now(),
                'reversed_by' => $authorization->actingUserId(),
                'reversal_reason' => $reason,
            ]);

            return $locked;
        });

        $this->refreshReceipt(Order::find($adjustment->order_id));

        return $reversed->fresh();
    }

    // ── Transaction ────────────────────────────────────────────────────────

    /** MySQL deadlock. The project's existing retry contract keys off this. */
    private const DEADLOCK = '40001';

    /** MySQL lock wait timeout — contention that outlasted the wait, not a deadlock. */
    private const LOCK_WAIT_TIMEOUT = 1205;

    /** Matches LedgerBalanceService's bound. Five attempts, then the caller is told. */
    private const MAX_DEADLOCK_RETRIES = 5;

    /**
     * Run $callback in a READ COMMITTED transaction.
     *
     * ISOLATION SCOPE. `SET TRANSACTION ISOLATION LEVEL` with no `SESSION` or
     * `GLOBAL` keyword applies to the NEXT transaction on this connection and
     * nothing after it, so the level cannot leak into later work — MySQL
     * reverts to the session default on commit or rollback.
     *
     * It is skipped when a transaction is already open. `DB::transaction()`
     * would then only open a savepoint, and the statement would silently
     * configure some later, unrelated transaction instead. Under an enclosing
     * transaction the caller's isolation governs, which is correct: they own
     * the boundary.
     *
     * RETRY. Deadlock (40001) is retried up to five times with a short pause,
     * the same policy `LedgerBalanceService` and `CustomHelper` already use.
     * The work is idempotent — keyed, and every figure re-derived under the
     * lock — so a retry re-decides rather than re-applying.
     *
     * A lock wait TIMEOUT is not retried. It means contention persisted for the
     * server's full wait, and hammering a financial write against a busy row is
     * how a queue becomes an outage. The operator is told plainly instead.
     */
    private function inReadCommittedTransaction(callable $callback)
    {
        $alreadyInTransaction = DB::transactionLevel() > 0;
        $attempt = 0;

        while (true) {
            if (! $alreadyInTransaction) {
                DB::statement('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
            }

            try {
                return DB::transaction($callback);
            } catch (QueryException $e) {
                if ($this->isLockWaitTimeout($e)) {
                    throw GoodwillException::because(GoodwillFailure::Contended);
                }

                if ($e->getCode() === self::DEADLOCK && ++$attempt <= self::MAX_DEADLOCK_RETRIES) {
                    usleep(100000);

                    continue;
                }

                throw $e;
            }
        }
    }

    private function isLockWaitTimeout(QueryException $e): bool
    {
        return ($e->errorInfo[1] ?? null) === self::LOCK_WAIT_TIMEOUT;
    }

    // ── Guards ─────────────────────────────────────────────────────────────

    private function assertNoteAccompaniesReason(GoodwillReason $reason, ?string $note): void
    {
        if ($reason->requiresNote() && trim((string) $note) === '') {
            throw GoodwillException::because(GoodwillFailure::NoteRequired);
        }
    }

    /**
     * Everything that makes an order ineligible, in the order an operator would
     * want to hear it.
     *
     * The A/R and balance checks duplicate a boolean the shared engine also
     * makes. That is deliberate: the engine's refusal is a single generic
     * string, and an operator needs to be told *which* condition stopped them
     * and what to do instead. No calculation is duplicated — only the question.
     */
    private function assertEligible(Order $order, OrderDiscountTarget $target, int $acceptedCents): void
    {
        if ($order->invoice_id !== null) {
            throw GoodwillException::because(GoodwillFailure::Invoiced);
        }

        if ($this->isPostedToAccount($order)) {
            throw GoodwillException::because(GoodwillFailure::PostedToAccount);
        }

        // A concession with no lines to attribute it to would be untracked by
        // the allocation ledger — see the allocator's line-less carve-out.
        if (! $order->products()->exists()) {
            throw GoodwillException::because(GoodwillFailure::NoMerchandiseLines);
        }

        if (OrderGoodwillAdjustment::activeFor((int) $order->id) !== null) {
            throw GoodwillException::because(GoodwillFailure::ActiveAdjustmentExists);
        }

        if ($acceptedCents <= 0) {
            throw GoodwillException::because(GoodwillFailure::NoSettledPayment);
        }

        if (round((float) $order->balance_due, 2) <= 0) {
            throw GoodwillException::because(GoodwillFailure::AlreadyPaidInFull);
        }

        if (! $target->isDiscountable()) {
            throw GoodwillException::because(
                GoodwillFailure::NotDiscountable,
                $target->ineligibleReason(),
            );
        }
    }

    /**
     * A concession may only be withdrawn while the order still stands as it did
     * when the concession was granted. Once money has been returned or the
     * balance has moved into another ledger, re-opening it here would leave two
     * records disagreeing.
     */
    private function assertReversible(Order $order, OrderGoodwillAdjustment $adjustment): void
    {
        if ($order->invoice_id !== null || $this->isPostedToAccount($order)) {
            throw GoodwillException::because(GoodwillFailure::ReversalBlocked);
        }

        $appliedAt = $adjustment->applied_at;

        $refundedSince = $order->payments()
            ->whereIn('status', [
                OrderPaymentStatus::PartialRefund->value,
                OrderPaymentStatus::Refund->value,
            ])
            ->where(function ($query) use ($appliedAt) {
                $query->where('created_at', '>=', $appliedAt)
                    ->orWhere('refunded_at', '>=', $appliedAt);
            })
            ->exists();

        if ($refundedSince) {
            throw GoodwillException::because(GoodwillFailure::ReversalBlocked);
        }
    }

    // ── Persistence ────────────────────────────────────────────────────────

    /**
     * Write the audit row, translating exactly one integrity violation.
     *
     * Only a duplicate on `oga_one_active_per_order` becomes a business
     * failure: it means a concurrent request won the race that the
     * application-level check could not see. Every other integrity error — a
     * foreign key, a null violation, the residual CHECK, a duplicate
     * idempotency key — is a real defect and is rethrown untouched. Collapsing
     * them all into one friendly message would hide the bugs worth finding.
     */
    private function persist(
        GoodwillApplyRequest $request,
        GoodwillAuthorization $authorization,
        GoodwillSolution $solution,
        int $productDiscountId,
        Order $order,
        array $before,
        int $acceptedCents,
        $settled,
    ): OrderGoodwillAdjustment {
        try {
            return OrderGoodwillAdjustment::create([
                'order_id' => $order->id,
                'product_discount_id' => $productDiscountId,
                'status' => OrderGoodwillAdjustment::STATUS_APPLIED,
                'reason_code' => $request->reason,
                'note' => $request->note,
                'approved_by' => $authorization->approvedBy,
                'approved_at' => now(),
                'applied_by' => $authorization->appliedBy,
                'accepted_payment_total' => $solution->acceptedPaymentTotal(),
                'rounding_residual' => $solution->residual(),
                // Convenience pointer only, and only when unambiguous — the same
                // convention order_payments.parent_order_payment_id documents.
                'payment_id' => $settled->count() === 1 ? $settled->first()->id : null,
                'settled_payments_snapshot' => $settled->map(fn ($payment) => [
                    'id' => $payment->id,
                    'unique_id' => $payment->unique_id,
                    'amount' => (float) $payment->amount,
                    'status' => $payment->status?->value,
                    'payment_datetime' => optional($payment->payment_datetime)->toDateTimeString(),
                ])->all(),
                'before_snapshot' => $before,
                'after_snapshot' => $this->snapshot($order, $acceptedCents),
                'idempotency_key' => $request->idempotencyKey,
                'applied_at' => now(),
            ]);
        } catch (QueryException $e) {
            if ($this->isActiveAdjustmentCollision($e)) {
                throw GoodwillException::because(GoodwillFailure::ActiveAdjustmentExists);
            }

            throw $e;
        }
    }

    /** True only for a duplicate key on the one-active index. Nothing else. */
    private function isActiveAdjustmentCollision(QueryException $e): bool
    {
        $isDuplicateKey = ($e->errorInfo[1] ?? null) === 1062;

        return $isDuplicateKey && str_contains($e->getMessage(), 'oga_one_active_per_order');
    }

    // ── Reads ──────────────────────────────────────────────────────────────

    /**
     * The order's position, for an audit snapshot.
     *
     * Reads the canonical columns the shared engine maintains; computes
     * nothing. `total_paid` is the LIVE settled total at this instant — the
     * stored `accepted_payment_total` is the immutable approval figure, and the
     * two are equal only at the moment of approval.
     */
    private function snapshot(Order $order, int $acceptedCents): array
    {
        return [
            'subtotal' => (float) $order->subtotal,
            'pretax_discount_total' => (float) $order->pretax_discount_total,
            'tax_amount' => (float) $order->tax_amount,
            'special_tax_amount' => (float) ($order->special_tax_amount ?? 0),
            'added_fees_amount' => (float) ($order->added_fees_amount ?? 0),
            'grand_total' => (float) $order->grand_total,
            'total_paid' => $acceptedCents / 100,
            'balance_due' => round(max(0.0, (float) $order->grand_total - ($acceptedCents / 100)), 2),
        ];
    }

    /**
     * The order's payment rows, held for the rest of the transaction.
     *
     * `WHERE order_id = ?` is an index lookup on `op_order_payment_status_idx`,
     * so this is a narrow order-scoped range lock. Every row is taken, not just
     * the settled ones: a Pending row can become Paid, and locking only what is
     * settled today would leave that transition free to happen underneath us.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int,OrderPayment>
     */
    private function lockOrderPayments(int $orderId)
    {
        return OrderPayment::query()
            ->where('order_id', $orderId)
            ->lockForUpdate()
            ->get();
    }

    /** The same rows without a lock, for the advisory preview path. */
    private function orderPayments(int $orderId)
    {
        return OrderPayment::query()->where('order_id', $orderId)->get();
    }

    /**
     * Settled rows, filtered in memory from an already-loaded collection.
     *
     * Uses `OrderPayment::isSettledPayment()`, which shares its status list
     * with `scopeSettled()` — so this can never disagree with the query form.
     */
    private function settledFrom($payments)
    {
        return $payments->filter->isSettledPayment()->sortBy('id')->values();
    }

    private function settledCentsFrom($payments): int
    {
        return $this->settledFrom($payments)
            ->reduce(fn (int $carry, OrderPayment $p): int => $carry + (int) round(((float) $p->amount) * 100), 0);
    }

    private function isPostedToAccount(Order $order): bool
    {
        return CustomerAccount::where('order_id', $order->id)->where('type', 'order')->exists();
    }

    private function findByKey(string $idempotencyKey): ?OrderGoodwillAdjustment
    {
        return OrderGoodwillAdjustment::where('idempotency_key', $idempotencyKey)->first();
    }

    private function refreshReceipt(?Order $order): void
    {
        if ($order !== null) {
            ReceiptService::getOrCreateReceipt($order);
        }
    }
}
