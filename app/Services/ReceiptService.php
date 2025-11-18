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
     * Map last payment status to receipt status
     */
    private static function mapPaymentStatus(Order $order): string
    {
        $lastPayment = $order->lastPayment;

        if (!$lastPayment) {
            return 'pending';
        }

        $status = $lastPayment->status->value; // Enum value

        return match ($status) {
            'Pending', 'Account' ,'Partial Refund', 'Refunded' => 'pending',
            'Failed'                               => 'failed',
            'Paid', 'Invoice Other', 'Invoice Cheque', 'Invoice Online', 'Invoice Cash', 'Invoice Card' => 'paid',
            default => 'paid',
        };
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
