<?php

namespace App\Services;

use App\Models\Orders\Order;
use App\Models\Customers\Receipt;
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
            return $totalRefunded >= $grandTotal ? 'Refunded' : 'Partial Refund';
        }

        if ($order->is_paid && (float) $order->balance_due <= 0) {
            return 'Paid in Full';
        }

        if ((float) $order->total_paid > 0) {
            return 'Partial Payment';
        }

        if ($order->last_payment_status === \App\Enums\Orders\OrderPaymentStatus::Failed->value) {
            return 'Failed';
        }

        return 'Pending';
    }

    /**
     * Map last payment method to receipt payment method
     */
    private static function mapPaymentMethod(Order $order): string|null
    {
        $lastPayment = $order->lastPayment;

        if (!$lastPayment) {
            return null;
        }

        $method = $lastPayment->payment_method->value; // Enum value

        return match ($method) {
            'Card'     => 'card',
            'COD'      => 'cash',
            'Account'  => 'other',
            'Cash'     => 'cash',
            'Online'   => 'online',
            'Cheque'   => 'cheque',
            'Other'    => 'other',
            default    => 'other',
        };
    }
}
