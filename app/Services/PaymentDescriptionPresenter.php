<?php

namespace App\Services;

use App\Enums\Customers\PaymentMethod as CustomerPaymentMethod;
use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Orders\Order;
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
