<?php

namespace App\Services;

use App\Enums\Customers\PaymentMethod as CustomerPaymentMethod;
use App\Enums\Orders\OrderHistoryAction;
use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Enums\Orders\RefundCalculationType;
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
     * The sentence written into order history for a refund — routes on
     * the refund's own refund_calculation_type rather than re-deriving
     * "how" from the amount, matching the requirement that calculation
     * type stays explicit and auditable rather than inferred later.
     */
    public static function refundHistoryDescription(OrderPayment $payment): string
    {
        $calcType = $payment->refund_calculation_type;
        $refundAmount = (float) $payment->refund_amount;

        if ($calcType === RefundCalculationType::CardProcessingFeeRetained) {
            return sprintf(
                'Refund processed — $%s | Credit card processing fee retained — $%s',
                number_format($refundAmount, 2),
                number_format((float) $payment->cc_fee_retained, 2)
            );
        }

        if ($calcType === RefundCalculationType::SalesTaxOnly) {
            [$originalTax, $remainingAfter] = self::salesTaxRefundContext($payment);

            return sprintf(
                'Sales Tax Refund Processed | Original Sales Tax: $%s | Sales Tax Refunded: $%s | Remaining Refundable Sales Tax: $%s',
                number_format($originalTax, 2),
                number_format($refundAmount, 2),
                number_format($remainingAfter, 2)
            );
        }

        return $payment->status === OrderPaymentStatus::Refund ? 'Full refund processed' : 'Partial refund processed';
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
                OrderHistoryAction::PaymentUncollectable => 'bad',
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
