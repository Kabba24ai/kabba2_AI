<?php

namespace App\Services;

use App\Enums\Customers\PaymentMethod as CustomerPaymentMethod;
use App\Enums\Orders\OrderHistoryAction;
use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Enums\Orders\RefundCalculationType;
use App\Enums\Orders\RefundOperationStatus;
use App\Models\Orders\Order;
use App\Models\Orders\OrderHistory;
use App\Models\Orders\OrderPayment;

/**
 * The single source of every payment-related label shown to a user:
 * status, method, terms, and the sentence written into order history.
 * Everywhere a status/method needs to become words, it should ask this
 * class rather than re-deriving the mapping locally.
 *
 * Every method accepts either the real enum, its raw stored value, or
 * null/garbage, and always returns a safe display string — never throws.
 * This deliberately preserves every existing accessor's raw-value return
 * type elsewhere in the app (e.g. Order::last_payment_status) rather than
 * changing what they return; only what's shown to a user changes here.
 */
class PaymentDescriptionPresenter
{
    /**
     * Status label. Invoice* statuses (which fuse status with method,
     * e.g. "Invoice Cash" = paid, via cash, through the Invoice flow)
     * collapse to their status half only — call methodLabel() with
     * impliedMethod() for the method half. Account (an Accounts
     * Receivable workflow marker, not money actually collected) reads
     * as Pending, since that's the true state of the funds.
     */
    public static function statusLabel(OrderPaymentStatus|string|null $status): string
    {
        $status = self::resolveStatus($status);

        if ($status === null) {
            return 'Unknown';
        }

        if ($status->isInvoice()) {
            return OrderPaymentStatus::Paid->label();
        }

        if ($status === OrderPaymentStatus::Account) {
            return OrderPaymentStatus::Pending->label();
        }

        return $status->label();
    }

    /**
     * Tailwind badge classes for a status, covering every case (the
     * previous CustomHelper::statusBadge() map silently fell back to gray
     * for the five Invoice* cases and PartialPayment).
     */
    public static function statusBadgeClasses(OrderPaymentStatus|string|null $status): string
    {
        $status = self::resolveStatus($status);

        return match (true) {
            $status === null => 'bg-gray-200 text-gray-800',
            $status === OrderPaymentStatus::Pending, $status === OrderPaymentStatus::Account => 'bg-yellow-100 text-yellow-800',
            $status === OrderPaymentStatus::PartialPayment => 'bg-orange-100 text-orange-800',
            $status === OrderPaymentStatus::PartialRefund => 'bg-orange-100 text-orange-800',
            $status === OrderPaymentStatus::Refund => 'bg-purple-100 text-purple-800',
            $status === OrderPaymentStatus::Failed => 'bg-red-100 text-red-800',
            $status === OrderPaymentStatus::Voided => 'bg-gray-200 text-gray-800',
            $status->isPaid(), $status->isInvoice() => 'bg-green-100 text-green-800',
            default => 'bg-gray-200 text-gray-800',
        };
    }

    /**
     * Method label. Cash means cash: this must only ever say what was
     * actually selected at entry — never a channel, location, or workflow
     * guess. Accepts either payment-method enum, since two exist
     * (OrderPaymentMethod for stored payments, Customers\PaymentMethod for
     * entry forms) and both must render identically.
     */
    public static function methodLabel(OrderPaymentMethod|CustomerPaymentMethod|string|null $method): string
    {
        $resolved = self::resolveMethod($method);

        return $resolved?->label() ?? 'Unknown';
    }

    /**
     * Status + method, decomposed separately, for one OrderPayment row.
     * Prefers the status's impliedMethod() when the status is a legacy
     * Invoice* case, since payment_method may be stale/unset on those
     * older rows — the status is the more trustworthy source there.
     */
    public static function describe(OrderPayment $payment): array
    {
        $status = self::resolveStatus($payment->status);
        $method = $status?->impliedMethod() ?? self::resolveMethod($payment->payment_method);

        return [
            'status' => self::statusLabel($status),
            'method' => self::methodLabel($method),
        ];
    }

    /**
     * Payment terms — when payment is expected, not how it was made.
     * Null once the order is actually settled: operational screens should
     * emphasize current status over stale original terms at that point.
     * Derived from existing signals (no dedicated terms column exists
     * today); Pay Later is intentionally not derived here — it is only
     * ever set explicitly by the extension "Pay Later" flow, which
     * displays its own terms text at the point of choice.
     */
    public static function termsLabel(Order $order): ?string
    {
        if ($order->is_paid) {
            return null;
        }

        $lastPaymentMethod = self::resolveMethod($order->last_payment_type);

        if ($lastPaymentMethod === OrderPaymentMethod::COD) {
            return 'Pay on Delivery';
        }

        return null;
    }

    /**
     * The sentence written into order history for a payment attempt.
     * Replaces three previously-independent, hand-typed copies of this
     * logic (PaymentInitiateListener, CreateInvoiceActivityListener,
     * CreateOrderListener) — none of which reliably checked the actual
     * payment method before describing a completed payment as paid "via
     * Credit/Debit Card."
     *
     * Deliberately does not special-case a failed status: every listener
     * that calls this also logs a dedicated failureDescription() entry
     * right after, preserving the existing two-row audit trail (an
     * "initiated" row, then a separate "failed" row) rather than
     * collapsing it to one row.
     */
    public static function historyDescription(OrderPayment $payment): string
    {
        $status = self::resolveStatus($payment->status);
        $methodLabel = self::methodLabel($payment->payment_method);

        if ($status === OrderPaymentStatus::PartialPayment) {
            return "Partial payment received via {$methodLabel}";
        }

        if ($status?->isSettled()) {
            $settledMethodLabel = $status->impliedMethod()?->label() ?? $methodLabel;

            return "Paid in Full via {$settledMethodLabel}";
        }

        return "Payment initiated via {$methodLabel}";
    }

    /** The dedicated "outcome" row logged alongside historyDescription() when a payment fails. */
    public static function failureDescription(OrderPayment $payment): string
    {
        return 'Payment failed via ' . self::methodLabel($payment->payment_method);
    }

    /**
     * Final Phase — Refund Workflow Consistency: VoidPaymentController used
     * to hand-build this sentence inline (twice — once for the gateway-void
     * path, once for the sync-already-voided-at-gateway path), the one
     * remaining hardcoded history string outside this presenter. $payment
     * must already have processed_by_name/processed_reason_label saved on
     * it (VoidPaymentController does this in the same update() call before
     * calling this method) — $voidedByName is the acting logged-in user,
     * a separate audit dimension from processed_by.
     */
    public static function voidHistoryDescription(OrderPayment $payment, string $voidedByName): string
    {
        $reasonText = $payment->processed_reason_label
            . ($payment->processed_reason_other ? ' — ' . $payment->processed_reason_other : '');

        return sprintf(
            'Payment of $%s voided by %s. Processed by %s (Employee ID verified). Reason: %s.',
            number_format((float) $payment->amount, 2),
            $voidedByName,
            $payment->processed_by_name,
            $reasonText
        );
    }

    /**
     * The sentence(s) written into order history for a refund.
     *
     * Phase 3C: a refund can now draw from more than one original payment
     * and can partially or fully fail (see RefundOperationStatus). Three
     * shapes exist, checked in this order:
     *   1. refund_operation_status === Failed — nothing succeeded.
     *   2. refund_operation_status === PartiallyCompleted — a mix.
     *   3. Everything else — the ORIGINAL Phase 2/3A/3B single-source
     *      wording is preserved EXACTLY when there is at most one
     *      allocation (routes on refund_calculation_type, unchanged), so
     *      existing single-payment refunds read identically to before.
     *      Only once a SECOND allocation exists does the row switch to
     *      the multi-source "Sources:" breakdown — see
     *      refundMultiSourceDescription().
     */
    public static function refundHistoryDescription(OrderPayment $payment): string
    {
        $operationStatus = $payment->refund_operation_status;
        $allocations = $payment->refundAllocations()->with('originalPayment')->get();

        if ($operationStatus === RefundOperationStatus::Failed) {
            $description = self::refundFailureDescription($allocations);
        } elseif ($operationStatus === RefundOperationStatus::PartiallyCompleted) {
            $description = self::refundPartialDescription($allocations);
        } elseif ($allocations->count() > 1) {
            $description = self::refundMultiSourceDescription($payment, $allocations);
        } else {
            $calcType = $payment->refund_calculation_type;
            $refundAmount = (float) $payment->refund_amount;

            if ($calcType === RefundCalculationType::CardProcessingFeeRetained) {
                $description = sprintf(
                    'Refund processed — $%s | Credit card processing fee retained — $%s',
                    number_format($refundAmount, 2),
                    number_format((float) $payment->cc_fee_retained, 2)
                );
            } elseif ($calcType === RefundCalculationType::SalesTaxOnly) {
                [$originalTax, $remainingAfter] = self::salesTaxRefundContext($payment);

                $description = sprintf(
                    'Sales Tax Refund Processed | Original Sales Tax: $%s | Sales Tax Refunded: $%s | Remaining Refundable Sales Tax: $%s',
                    number_format($originalTax, 2),
                    number_format($refundAmount, 2),
                    number_format($remainingAfter, 2)
                );
            } else {
                $description = $payment->status === OrderPaymentStatus::Refund ? 'Full refund processed' : 'Partial refund processed';
            }
        }

        $processedBy = self::processedByLine($payment);

        return $processedBy ? "{$description}\n\n{$processedBy}" : $description;
    }

    /** "Processed by: {employee name}" — appended once, in this one place, to every refund history sentence. Null when the row predates processed_by_name (legacy rows). */
    private static function processedByLine(OrderPayment $payment): ?string
    {
        return $payment->processed_by_name ? "Processed by: {$payment->processed_by_name}" : null;
    }

    /**
     * "Method — $amount (original payment: date)", with a masked card
     * suffix when the original payment was a card charge — the one line
     * format every multi-source refund history entry reuses, so a source
     * is never described twice with different wording.
     */
    private static function allocationSourceLine(\App\Models\Orders\OrderPaymentRefundAllocation $allocation): string
    {
        $original = $allocation->originalPayment;
        $label = self::methodLabel($original?->payment_method);

        if ($original && $original->payment_method === OrderPaymentMethod::Card && $original->card_number) {
            $label .= ' •••• '.$original->card_number;
        }

        $line = "{$label} — \$".number_format((float) $allocation->allocated_amount, 2);

        $originalDate = $original?->payment_datetime ?? $original?->created_at;
        if ($originalDate) {
            $line .= ' (original payment: '.$originalDate->format('M j, Y').')';
        }

        return $line;
    }

    private static function refundMultiSourceDescription(OrderPayment $payment, \Illuminate\Support\Collection $allocations): string
    {
        $lines = [
            sprintf('Refund processed — $%s', number_format((float) $payment->refund_amount, 2)),
            '', 'Sources:',
        ];

        // Only the sources that actually funded this refund — a
        // Superseded allocation (an earlier attempt on this same refund
        // event that failed and was reallocated to a different source —
        // see PaymentAllocationService::supersedeAbandonedFailures())
        // never contributed money and would otherwise double-list the
        // same dollar amount against two sources here.
        foreach ($allocations->where('status', \App\Enums\Orders\OrderPaymentRefundAllocationStatus::Allocated) as $allocation) {
            $lines[] = self::allocationSourceLine($allocation);
        }

        $superseded = $allocations->where('status', \App\Enums\Orders\OrderPaymentRefundAllocationStatus::Superseded);
        if ($superseded->isNotEmpty()) {
            $lines[] = '';
            $lines[] = 'Reallocated (attempted, then moved to a different source):';
            foreach ($superseded as $allocation) {
                $lines[] = self::allocationSourceLine($allocation);
            }
        }

        if ((float) $payment->tax_refunded > 0) {
            $lines[] = '';
            $lines[] = 'Sales tax refunded — $'.number_format((float) $payment->tax_refunded, 2);
        }

        if ((float) ($payment->cc_fee_retained ?? 0) > 0) {
            $lines[] = 'Card processing fee retained — $'.number_format((float) $payment->cc_fee_retained, 2);
        }

        return rtrim(implode("\n", $lines));
    }

    private static function refundPartialDescription(\Illuminate\Support\Collection $allocations): string
    {
        $completed = $allocations->where('status', \App\Enums\Orders\OrderPaymentRefundAllocationStatus::Allocated);
        $failed = $allocations->where('status', \App\Enums\Orders\OrderPaymentRefundAllocationStatus::Failed);

        $lines = ['Refund partially completed', ''];

        if ($completed->isNotEmpty()) {
            $lines[] = 'Completed:';
            foreach ($completed as $allocation) {
                $lines[] = self::allocationSourceLine($allocation);
            }
            $lines[] = '';
        }

        if ($failed->isNotEmpty()) {
            $lines[] = 'Failed:';
            foreach ($failed as $allocation) {
                $lines[] = self::allocationSourceLine($allocation);
                if ($allocation->failure_reason) {
                    $lines[] = 'Reason: '.$allocation->failure_reason;
                }
            }
        }

        return rtrim(implode("\n", $lines));
    }

    private static function refundFailureDescription(\Illuminate\Support\Collection $allocations): string
    {
        $lines = ['Refund failed — no sources could be refunded.', ''];

        foreach ($allocations->where('status', \App\Enums\Orders\OrderPaymentRefundAllocationStatus::Failed) as $allocation) {
            $lines[] = self::allocationSourceLine($allocation);
            if ($allocation->failure_reason) {
                $lines[] = 'Reason: '.$allocation->failure_reason;
            }
        }

        return rtrim(implode("\n", $lines));
    }

    /**
     * [original sales tax charged, remaining refundable tax after this
     * row] — shared by refundHistoryDescription() and timelineEntry() so
     * both surfaces agree on the same figures.
     */
    private static function salesTaxRefundContext(OrderPayment $payment): array
    {
        $order = $payment->order;
        $originalTax = (float) ($order?->tax_amount ?? 0);

        $refundedSoFar = (float) ($order?->payments()
            ->whereIn('status', [OrderPaymentStatus::PartialRefund, OrderPaymentStatus::Refund])
            ->sum('tax_refunded') ?? $payment->tax_refunded);

        return [$originalTax, max(0.0, round($originalTax - $refundedSoFar, 2))];
    }

    /**
     * Structured Payment Timeline row: a plain-language headline plus
     * method/amount as distinct fields (never a hand-formatted sentence),
     * for one OrderHistory entry. Replaces the single free-text
     * `description` string every timeline row used to be reduced to.
     *
     * Falls back to the entry's stored `description` (today's exact
     * behavior) whenever the row has no linked OrderPayment — either
     * because it predates the `order_payment_id` column, or because it's
     * a non-payment event (address change, terms signed, etc.) that never
     * had one to begin with. Never throws.
     */
    public static function timelineEntry(OrderHistory $history): array
    {
        $action = $history->action instanceof OrderHistoryAction ? $history->action : null;
        $payment = $history->orderPayment;

        $entry = [
            'headline' => $history->description,
            'method' => null,
            'amount' => null,
            'sign' => null, // 'pos' | 'neg' | null — null means "don't show an amount"
            'dot' => 'neutral',
            'note' => null,
            'detail' => null, // secondary line: fee retained / remaining refundable tax
        ];

        if ($action !== null) {
            $entry['dot'] = match ($action) {
                OrderHistoryAction::OrderPaid, OrderHistoryAction::PaymentCollected => 'good',
                OrderHistoryAction::PartialPaymentReceived, OrderHistoryAction::PaymentInitiated,
                OrderHistoryAction::StoreCreditApplied, OrderHistoryAction::AddedToAccount => 'info',
                OrderHistoryAction::PaymentFailed, OrderHistoryAction::OrderRefunded,
                OrderHistoryAction::OrderPartialRefund, OrderHistoryAction::TransactionVoided,
                OrderHistoryAction::PaymentUncollectable, OrderHistoryAction::RefundPartiallyCompleted,
                OrderHistoryAction::RefundFailed => 'bad',
                default => 'neutral',
            };

            $isSalesTaxOnlyRefund = in_array($action, [OrderHistoryAction::OrderRefunded, OrderHistoryAction::OrderPartialRefund], true)
                && $history->orderPayment?->refund_calculation_type === RefundCalculationType::SalesTaxOnly;

            $entry['headline'] = match (true) {
                $action === OrderHistoryAction::OrderPaid => 'Payment received',
                $action === OrderHistoryAction::PartialPaymentReceived => 'Partial payment received',
                $action === OrderHistoryAction::PaymentInitiated => 'Payment initiated',
                $action === OrderHistoryAction::PaymentFailed => 'Payment failed',
                $action === OrderHistoryAction::StoreCreditApplied => 'Store Credit applied',
                $isSalesTaxOnlyRefund => 'Sales tax refund processed',
                $action === OrderHistoryAction::OrderRefunded, $action === OrderHistoryAction::OrderPartialRefund => 'Refund processed',
                $action === OrderHistoryAction::RefundPartiallyCompleted => 'Refund partially completed',
                $action === OrderHistoryAction::RefundFailed => 'Refund failed',
                $action === OrderHistoryAction::TransactionVoided => 'Payment voided',
                $action === OrderHistoryAction::AddedToAccount => 'Added to account',
                default => $history->description,
            };
        }

        if ($payment !== null) {
            // Method is shown next to a receive/initiate/fail event (it's
            // the whole point of that row); it's redundant on Store
            // Credit/refund/void rows, whose headline already says what
            // happened, so it's left out there — matches the operational
            // wording given for this feature.
            if (in_array($action, [
                OrderHistoryAction::OrderPaid,
                OrderHistoryAction::PartialPaymentReceived,
                OrderHistoryAction::PaymentInitiated,
                OrderHistoryAction::PaymentFailed,
            ], true)) {
                $entry['method'] = self::methodLabel($payment->payment_method);
            }

            // Refund creates a new row with the reversed amount on
            // refund_amount; Void reverses the original row in place, so
            // its amount is still on the plain `amount` column.
            if (in_array($action, [OrderHistoryAction::OrderRefunded, OrderHistoryAction::OrderPartialRefund], true)) {
                $entry['amount'] = (float) $payment->refund_amount;
                $entry['sign'] = 'neg';
            } elseif ($action === OrderHistoryAction::RefundPartiallyCompleted) {
                // The ACTUAL successful sub-total, never the originally
                // requested refund_amount — this row exists specifically
                // to avoid overstating what really left the business.
                $entry['amount'] = (float) $payment->refundAllocations()
                    ->where('status', \App\Enums\Orders\OrderPaymentRefundAllocationStatus::Allocated->value)
                    ->sum('allocated_amount');
                $entry['sign'] = 'neg';
                $entry['detail'] = 'Some sources failed — see description for details.';
            } elseif ($action === OrderHistoryAction::TransactionVoided) {
                $entry['amount'] = (float) $payment->amount;
                $entry['sign'] = 'neg';
            } elseif (in_array($action, [
                OrderHistoryAction::OrderPaid,
                OrderHistoryAction::PartialPaymentReceived,
                OrderHistoryAction::StoreCreditApplied,
            ], true)) {
                $entry['amount'] = (float) $payment->amount;
                $entry['sign'] = 'pos';
            }

            $entry['note'] = $payment->payment_note ?: null;

            if (in_array($action, [OrderHistoryAction::OrderRefunded, OrderHistoryAction::OrderPartialRefund], true)) {
                if ($payment->refund_calculation_type === RefundCalculationType::CardProcessingFeeRetained) {
                    $entry['detail'] = 'Credit card processing fee retained — $' . number_format((float) $payment->cc_fee_retained, 2);
                } elseif ($payment->refund_calculation_type === RefundCalculationType::SalesTaxOnly) {
                    [, $remainingAfter] = self::salesTaxRefundContext($payment);
                    $entry['detail'] = 'Remaining refundable sales tax — $' . number_format($remainingAfter, 2);
                }
            }
        }

        return $entry;
    }

    private static function resolveStatus(OrderPaymentStatus|string|null $status): ?OrderPaymentStatus
    {
        if ($status instanceof OrderPaymentStatus || $status === null) {
            return $status;
        }

        return OrderPaymentStatus::tryFrom($status);
    }

    private static function resolveMethod(OrderPaymentMethod|CustomerPaymentMethod|string|null $method): ?OrderPaymentMethod
    {
        if ($method instanceof OrderPaymentMethod) {
            return $method;
        }

        if ($method === null) {
            return null;
        }

        if ($method instanceof CustomerPaymentMethod) {
            $method = $method->value;
        }

        // Customers\PaymentMethod and OrderPaymentMethod share the exact
        // same value spelling for every case they have in common
        // (CreditCard maps separately below since OrderPaymentMethod's
        // case is named Card, not CreditCard).
        if ($method === CustomerPaymentMethod::CreditCard->value) {
            return OrderPaymentMethod::Card;
        }

        // Bank Transfer/Online is not an approved, selectable method (see
        // canonical() on both enums) — this bridge exists only so that IF
        // a historical row is ever found with this value (none were found
        // in the data searched at removal time), it still renders its
        // accurate historical label rather than "Unknown," matching how
        // every other retired label in this codebase preserves historical
        // meaning without offering the value for new selection.
        if ($method === 'BankTransfer') {
            return OrderPaymentMethod::Online;
        }

        return OrderPaymentMethod::tryFrom($method);
    }
}
