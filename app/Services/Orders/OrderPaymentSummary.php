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

        $this->collectionStatus = match (true) {
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
            self::COLLECTION_PARTIALLY_PAID => 'Partially Paid',
            self::COLLECTION_PAID_IN_FULL => 'Paid in Full',
        };

        return match ($this->refundStatus) {
            self::REFUND_NONE => $collection,
            self::REFUND_PARTIAL => "{$collection} · Partially Refunded",
            self::REFUND_FULL => "{$collection} · Fully Refunded",
        };
    }
}
