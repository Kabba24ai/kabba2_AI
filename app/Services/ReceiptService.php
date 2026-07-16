<?php

namespace App\Services;

use App\Enums\Orders\OrderPaymentRefundAllocationStatus;
use App\Models\Orders\Order;
use App\Models\Customers\Receipt;
use App\Services\PaymentDescriptionPresenter;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReceiptService
{
    public static function getOrCreateReceipt(Order $order)
    {
        try {
            // Try fetching latest receipt
            $receipt = Receipt::with([
                'invoice',
                'items',
                'items.orderProduct',
                'customer.addresses.state',
                'customer.billingAddress',
                'customer.shippingAddress',
            ])
                ->where('order_id', $order->id)
                ->latest()
                ->first();

            if ($receipt) {
                return $receipt;
            }

            // Map payment status & method from last payment
            $paymentStatus = self::mapPaymentStatus($order);
            $paymentMethod = self::mapPaymentMethod($order);

            // Create new receipt
            $receipt = Receipt::create([
                'customer_id'    => $order->customer_id,
                'order_id'       => $order->id,
                'payment_method' => $paymentMethod,
                'receipt_date'   => now(),
                'order_date'     => $order->order_date,
                'payment_status' => $paymentStatus,
                'subtotal'       => $order->subtotal,
                'sales_tax'      => $order->tax_amount,
                'total'          => $order->grand_total,
            ]);

            // Add receipt items from order products
            foreach ($order->products as $invItem) {
                $receipt->items()->create([
                    'type'      => 'order',
                    'item_name' => $invItem->product_name,
                    'unit'      => $invItem->price,
                    'qty'       => $invItem->quantity,
                    'tax'       => $invItem->tax,
                    'total'     => $invItem->total,
                    'item_id'   => $invItem->unique_id,
                ]);
            }

            // Mark order receipt as created
            $order->receipt_status = 'created';
            $order->saveQuietly();

            Log::info('Receipt Created for Order', [
                'order_id'   => $order->id,
                'order_num'  => $order->order_number,
                'receipt_id' => $receipt->id,
            ]);

            return $receipt;
        } catch (Throwable $e) {
            Log::error('Failed to get or create receipt', [
                'order_id' => $order->id,
                'order_num' => $order->order_number,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null; // Return null if failed
        }
    }

    /**
     * Map the order's current canonical financial state to the narrow
     * paid|pending|failed snapshot stored on the receipt row at creation
     * time. Refunds/partial payments collapse to 'pending' here since the
     * DB column only supports 3 values — the richer, always-live label
     * actually shown on the receipt comes from currentPaymentStatusLabel()
     * below, never from this stored snapshot.
     */
    private static function mapPaymentStatus(Order $order): string
    {
        if ($order->is_paid && (float) $order->balance_due <= 0 && (float) $order->total_refunded <= 0) {
            return 'paid';
        }

        if ($order->last_payment_status === \App\Enums\Orders\OrderPaymentStatus::Failed->value) {
            return 'failed';
        }

        return 'pending';
    }

    /**
     * The receipt's payment status as it should read RIGHT NOW — derived
     * live from the order's current financial state (Order::is_paid /
     * total_paid / total_refunded / balance_due, the same canonical
     * accessors the Order Details screen uses via $order->is_paid etc.)
     * rather than a value captured once when the receipt row was first
     * created. This is the single source of truth for what the receipt
     * displays; the print_receipt view calls this directly instead of
     * reading the (potentially stale) stored Receipt::payment_status.
     *
     * No independent balance math and no payment-method checks — refunds,
     * partial payments, and voids are read straight from the existing
     * payment ledger accessors, never re-derived here.
     */
    public static function currentPaymentStatusLabel(Order $order): string
    {
        $grandTotal    = (float) $order->grand_total;
        $totalRefunded = (float) $order->total_refunded;

        if ($totalRefunded > 0) {
            return $totalRefunded >= $grandTotal ? 'Refunded' : 'Partially Refunded';
        }

        if ($order->is_paid && (float) $order->balance_due <= 0) {
            return 'Paid in Full';
        }

        if ((float) $order->total_paid > 0) {
            return 'Partially Paid';
        }

        if ($order->last_payment_status === \App\Enums\Orders\OrderPaymentStatus::Failed->value) {
            return 'Failed';
        }

        return 'Pending';
    }

    /**
     * The receipt's payment method as it should read RIGHT NOW — live from
     * the order's most recent paid (or otherwise most recent) payment, the
     * same "always live, never the frozen creation-time snapshot" pattern
     * as currentPaymentStatusLabel(). This is what actually gets printed;
     * the stored Receipt::payment_method column is kept for audit only.
     *
     * Returns null when there's no payment to describe yet (e.g. a Pay on
     * Delivery order still pending) — callers should show Payment Terms
     * instead in that case, via PaymentDescriptionPresenter::termsLabel().
     */
    public static function currentPaymentMethodLabel(Order $order): ?string
    {
        $payment = $order->lastPaidPayment ?? $order->lastPayment;

        if (!$payment) {
            return null;
        }

        return PaymentDescriptionPresenter::methodLabel($payment->payment_method);
    }

    /**
     * Per-method amount breakdown across every Paid/PartialPayment row on
     * the order — prepared for a future multi-method receipt ("Cash
     * $100.00 / Credit Card $542.04") but NOT wired into the live receipt
     * view yet, per Phase 2 scope (architecture only, no split-payment UI
     * this round). Reads live from the existing payment ledger — no new
     * table, no duplicated data.
     *
     * @return array<int, array{method: string, amount: float}>
     */
    public static function paymentMethodBreakdown(Order $order): array
    {
        return $order->payments()
            ->whereIn('status', [
                \App\Enums\Orders\OrderPaymentStatus::Paid->value,
                \App\Enums\Orders\OrderPaymentStatus::PartialPayment->value,
            ])
            ->get()
            ->groupBy(fn ($p) => $p->payment_method?->value ?? 'unknown')
            ->map(fn ($rows) => [
                'method' => PaymentDescriptionPresenter::methodLabel($rows->first()->payment_method),
                'amount' => (float) $rows->sum('amount'),
            ])
            ->values()
            ->all();
    }

    /**
     * Phase 3D — the itemized refund list for the receipt's "Refunds"
     * section: one line per successfully-Allocated allocation, grouped by
     * the original payment's method (mission §5's "mixed payment methods
     * must display as separate lines"). Deliberately mirrors
     * paymentMethodBreakdown()'s shape above, but for refunds.
     *
     * Only Allocated allocations are ever included — Pending/Failed never
     * reach the customer-facing receipt, and no internal field (gateway
     * transaction id, failure_reason, allocation id) is exposed here.
     * Legacy refunds with no allocation rows yet fall back to one line per
     * refund row (whole-row date/amount/method), the same fallback shape
     * every other legacy path in PaymentAllocationService uses.
     *
     * @return array<int, array{method: string, date: ?\Carbon\Carbon, amount: float, calc_type: ?string, fee_retained: float, tax_refunded: float}>
     */
    public static function refundDetails(Order $order): array
    {
        $refunds = $order->payments()->refund()->with('refundAllocations.originalPayment')->get();

        $lines = [];

        foreach ($refunds as $refund) {
            $allocated = $refund->refundAllocations->where('status', OrderPaymentRefundAllocationStatus::Allocated);

            if ($allocated->isNotEmpty()) {
                foreach ($allocated as $allocation) {
                    $original = $allocation->originalPayment;
                    $lines[] = [
                        'method' => PaymentDescriptionPresenter::methodLabel($original?->payment_method),
                        'date' => $refund->refunded_at ?? $refund->created_at,
                        'amount' => (float) $allocation->allocated_amount - (float) ($allocation->processing_fee_retained ?? 0),
                        'calc_type' => $refund->refund_calculation_type?->value,
                        'fee_retained' => (float) ($allocation->processing_fee_retained ?? 0),
                        'tax_refunded' => (float) $allocation->allocated_tax_amount,
                    ];
                }
            } elseif ($refund->refundAllocations->isEmpty()) {
                $lines[] = [
                    'method' => PaymentDescriptionPresenter::methodLabel($refund->payment_method),
                    'date' => $refund->refunded_at ?? $refund->created_at,
                    'amount' => (float) $refund->refund_amount,
                    'calc_type' => $refund->refund_calculation_type?->value,
                    'fee_retained' => (float) ($refund->cc_fee_retained ?? 0),
                    'tax_refunded' => (float) $refund->tax_refunded,
                ];
            }
        }

        return $lines;
    }

    /**
     * Map last payment method to receipt payment method. Cash means cash:
     * COD (a payment-terms placeholder, never itself a completed method)
     * and Account (an Accounts Receivable workflow marker, not a way
     * funds were transferred) must never guess their way into 'cash' —
     * they resolve to null (method not yet actually known) instead.
     */
    private static function mapPaymentMethod(Order $order): string|null
    {
        $lastPayment = $order->lastPayment;

        if (!$lastPayment) {
            return null;
        }

        return match ($lastPayment->payment_method) {
            \App\Enums\Orders\OrderPaymentMethod::Card => 'card',
            \App\Enums\Orders\OrderPaymentMethod::Cash => 'cash',
            \App\Enums\Orders\OrderPaymentMethod::Online => 'online',
            \App\Enums\Orders\OrderPaymentMethod::Cheque => 'cheque',
            \App\Enums\Orders\OrderPaymentMethod::TapToPay => 'tap_to_pay',
            \App\Enums\Orders\OrderPaymentMethod::StoreCredit => 'store_credit',
            \App\Enums\Orders\OrderPaymentMethod::GiftCard => 'gift_card',
            \App\Enums\Orders\OrderPaymentMethod::ZelleVenmo => 'zelle_venmo',
            \App\Enums\Orders\OrderPaymentMethod::Other => 'other',
            \App\Enums\Orders\OrderPaymentMethod::COD, \App\Enums\Orders\OrderPaymentMethod::Account => null,
            default => 'other',
        };
    }
}
