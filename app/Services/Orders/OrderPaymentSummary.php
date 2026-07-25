<?php

namespace App\Services\Orders;

use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Orders\Order;
use App\Models\Orders\OrderPayment;
use Illuminate\Support\Collection;

/**
 * Phase 3A — Payment Correctness Foundation.
 *
 * The canonical, order-level source of payment/refund truth. Every field
 * here is derived from the full payments() collection (never a single
 * "last" row) via Order's own aggregate accessors — this class does not
 * recompute those sums itself, it only assembles and names them.
 *
 * Collection Status and Refund Status are kept as two separate dimensions
 * rather than one fused status, deliberately avoiding the mistake already
 * made once in this codebase (OrderPaymentStatus's legacy Invoice* values
 * fuse status and method, and isSettled()/impliedMethod() exist purely to
 * unwind that fusion after the fact). balanceStatusLabel() combines them
 * for display only — it is not itself a stored or authoritative value.
 *
 * Immutable and side-effect free: constructing this class never writes
 * anything, and it has no Blade/HTTP dependency.
 */
final class OrderPaymentSummary
{
    public const COLLECTION_UNPAID = 'unpaid';

    public const COLLECTION_ON_ACCOUNT = 'on_account';

    public const COLLECTION_PARTIALLY_PAID = 'partially_paid';

    public const COLLECTION_PAID_IN_FULL = 'paid_in_full';

    public const REFUND_NONE = 'none';

    public const REFUND_PARTIAL = 'partially_refunded';

    public const REFUND_FULL = 'fully_refunded';

    public readonly float $grandTotal;

    public readonly float $totalSettledPayments;

    public readonly float $totalRefunded;

    public readonly float $netPaid;

    public readonly float $balanceDue;

    public readonly float $orderRefundableBalance;

    public readonly string $collectionStatus;

    public readonly string $refundStatus;

    /** @var Collection<int, \App\Enums\Orders\OrderPaymentMethod> distinct methods used by settled original payments */
    public readonly Collection $paymentMethodsUsed;

    /** @var Collection<int, OrderPayment> every settled original payment row, oldest first */
    public readonly Collection $originalSettledPayments;

    /** @var Collection<int, OrderPayment> settled original payments that still have a remaining refundable balance > 0 */
    public readonly Collection $refundablePayments;

    /**
     * Every Pending or Failed original (non-refund) payment row on the
     * order, oldest first, REGARDLESS of whether the order has since been
     * fully paid by some other combination of rows. This is the complete
     * HISTORICAL record — the right source for a payment-history/timeline
     * view, where an old declined card attempt or an abandoned COD
     * placeholder should still be visible for audit purposes even after
     * the order is fully paid.
     *
     * Do NOT use this collection by itself to decide the order's CURRENT
     * status or to drive a "needs attention" badge — its mere non-emptiness
     * says nothing about whether the order is still actually waiting on
     * anything. Use unresolvedPaymentAttempts below for that; see its
     * docblock for the precedence rule.
     *
     * @var Collection<int, OrderPayment>
     */
    public readonly Collection $pendingOrFailedPayments;

    /**
     * The subset of pendingOrFailedPayments that still represents an
     * ACTIVE, unresolved problem — i.e. the order has not since been fully
     * paid by some (possibly different) combination of rows. Empty
     * whenever collectionStatus is COLLECTION_PAID_IN_FULL, even if
     * pendingOrFailedPayments itself is not: a declined card attempt or an
     * abandoned/superseded Pending placeholder stops being "active" the
     * moment the order's balance is genuinely satisfied by something else
     * — it becomes purely historical at that point and must never
     * continue to present the order itself as PAYMENT FAILED / PENDING
     * PAYMENT.
     *
     * This is the field every "current status" consumer (Order Details
     * header, any future API/CRM alert) must use for a Pending/Failed
     * "needs attention" indicator — never pendingOrFailedPayments directly.
     *
     * @var Collection<int, OrderPayment>
     */
    public readonly Collection $unresolvedPaymentAttempts;

    /**
     * Every Voided payment row on the order, oldest first. A void is an
     * in-place reversal of the original payment row (status → Voided,
     * voided_at stamped — see VoidPaymentController); voided rows are
     * therefore excluded from settled() AND from pendingOrFailedPayments,
     * which made the summary blind to them before this field existed. A
     * voided row means money was authorized/captured and then cancelled
     * before settlement — it is not a refund (no money to return) and not
     * a decline (the charge did succeed before being cancelled), and it
     * must never contribute to totals, methods used, or refund state.
     *
     * @var Collection<int, OrderPayment>
     */
    public readonly Collection $voidedPayments;

    /**
     * Every Account-status payment row on the order, oldest first. An
     * Account row is the marker that the order's balance was transferred to
     * the customer's credit account (Accounts Receivable) — Account is not a
     * settled status, so these rows never contribute to totals/methods/refund
     * state (exactly like voided rows). They exist so the summary can present
     * the order as "On Account" instead of falling through to "Unpaid".
     *
     * @var Collection<int, OrderPayment>
     */
    public readonly Collection $accountPayments;

    /**
     * True when the order's balance sits on the customer's credit account:
     * nothing has been collected as real money (totalSettledPayments <= 0)
     * and at least one Account-status row exists. This is a presentation
     * dimension only — the receivable itself lives on customers.available_
     * credit_balance / customer_accounts, not here.
     */
    public readonly bool $isOnAccount;

    /**
     * The most recent settled original payment — exposed ONLY for
     * backward-compatible single-payment display (e.g. legacy call sites
     * not yet migrated off Order::lastPaidPayment). Never use this to
     * derive order-level totals, status, or refund eligibility — those
     * must always come from the aggregate fields above.
     */
    public readonly ?OrderPayment $latestSuccessfulPayment;

    private function __construct(Order $order)
    {
        $this->grandTotal = (float) $order->grand_total;
        $this->totalSettledPayments = (float) $order->total_paid;
        $this->totalRefunded = (float) $order->total_refunded;
        $this->netPaid = (float) $order->net_paid;
        $this->balanceDue = (float) $order->balance_due;
        $this->orderRefundableBalance = (float) $order->remaining_amount;

        $this->accountPayments = $order->payments()
            ->where('status', OrderPaymentStatus::Account->value)
            ->orderBy('id')
            ->get();

        $this->isOnAccount = $this->totalSettledPayments <= 0.0 && $this->accountPayments->isNotEmpty();

        $this->collectionStatus = match (true) {
            $this->totalSettledPayments <= 0.0 && $this->accountPayments->isNotEmpty() => self::COLLECTION_ON_ACCOUNT,
            $this->totalSettledPayments <= 0.0 => self::COLLECTION_UNPAID,
            (bool) $order->is_paid => self::COLLECTION_PAID_IN_FULL,
            default => self::COLLECTION_PARTIALLY_PAID,
        };

        $this->refundStatus = match (true) {
            $this->totalRefunded <= 0.0 => self::REFUND_NONE,
            ($this->totalRefunded + 0.005) >= $this->totalSettledPayments => self::REFUND_FULL,
            default => self::REFUND_PARTIAL,
        };

        $originalSettled = $order->payments()->settled()->orderBy('id')->get();

        $this->originalSettledPayments = $originalSettled;
        $this->paymentMethodsUsed = $originalSettled->pluck('payment_method')->unique(fn ($m) => $m?->value)->values();
        $this->refundablePayments = $originalSettled
            ->filter(fn (OrderPayment $payment) => $order->remainingRefundableForPayment($payment) > 0.0)
            ->values();
        $this->latestSuccessfulPayment = $originalSettled->last();

        $this->pendingOrFailedPayments = $order->payments()
            ->whereIn('status', [OrderPaymentStatus::Pending->value, OrderPaymentStatus::Failed->value])
            ->orderBy('id')
            ->get();

        $this->voidedPayments = $order->payments()
            ->where('status', OrderPaymentStatus::Voided->value)
            ->orderBy('id')
            ->get();

        $this->unresolvedPaymentAttempts = $this->collectionStatus === self::COLLECTION_PAID_IN_FULL
            ? new Collection()
            : $this->pendingOrFailedPayments;
    }

    public static function for(Order $order): self
    {
        return new self($order);
    }

    /**
     * Whether the refund flow can identify one unambiguous original
     * payment to refund from without asking the employee to choose —
     * i.e. exactly one settled payment exists on the order. On any order
     * with more than one, Phase 3A intentionally does not guess; source-
     * payment selection is Phase 3B/3C scope (see the Phase 3 payment
     * allocation architecture doc).
     */
    public function hasUnambiguousRefundSource(): bool
    {
        return $this->originalSettledPayments->count() === 1;
    }

    public function unambiguousRefundSource(): ?OrderPayment
    {
        return $this->hasUnambiguousRefundSource() ? $this->originalSettledPayments->first() : null;
    }

    /**
     * Display-only composite of the two stored dimensions above — never
     * itself persisted or treated as a source of truth.
     */
    public function balanceStatusLabel(): string
    {
        $collection = match ($this->collectionStatus) {
            self::COLLECTION_UNPAID => 'Unpaid',
            self::COLLECTION_ON_ACCOUNT => 'On Account',
            self::COLLECTION_PARTIALLY_PAID => 'Partially Paid',
            self::COLLECTION_PAID_IN_FULL => 'Paid in Full',
        };

        return match ($this->refundStatus) {
            self::REFUND_NONE => $collection,
            self::REFUND_PARTIAL => "{$collection} · Partially Refunded",
            self::REFUND_FULL => "{$collection} · Fully Refunded",
        };
    }

    /**
     * Methods to show for the ORDER's payment method, accounting for the
     * on-account case. paymentMethodsUsed only contains SETTLED methods, so a
     * pure Account order has none — which would render as "Unknown". This
     * surfaces "Account" instead, so the Payment Details modal and any
     * method display read correctly for an on-account order.
     *
     * @return Collection<int, \App\Enums\Orders\OrderPaymentMethod>
     */
    public function methodsUsedForDisplay(): Collection
    {
        if ($this->paymentMethodsUsed->isEmpty() && $this->isOnAccount) {
            return collect([\App\Enums\Orders\OrderPaymentMethod::Account]);
        }

        return $this->paymentMethodsUsed;
    }

    /**
     * THE canonical order-level refund predicate. An order is refundable
     * only when at least one settled original payment still has a remaining
     * refundable balance. This is deliberately status-driven, so it is
     * correct for every method by construction:
     *   - Card / POD settled with balance → refundable
     *   - Account (never a settled status) → NOT refundable (no money was
     *     collected on the order; account adjustments belong in the CRM
     *     account workflow)
     *   - Voided (remainingRefundable = 0) → NOT refundable
     *   - Fully refunded → NOT refundable
     * Every surface deciding "show the Refund button / accept a refund" must
     * consult this instead of re-deriving is_paid && remaining_amount.
     */
    public function canRefund(): bool
    {
        return $this->refundablePayments->isNotEmpty();
    }

    /** User-facing reason a refund is unavailable, or null when canRefund() is true. */
    public function refundableReason(): ?string
    {
        if ($this->canRefund()) {
            return null;
        }

        return match (true) {
            $this->isOnAccount => 'On-account orders have no order-level refund. Adjust the balance in the CRM account workflow.',
            $this->originalSettledPayments->isEmpty() && $this->voidedPayments->isNotEmpty() => 'This payment was voided and cannot be refunded.',
            $this->collectionStatus === self::COLLECTION_UNPAID => 'No settled payment to refund.',
            default => 'No refundable balance remains on this order.',
        };
    }
}
