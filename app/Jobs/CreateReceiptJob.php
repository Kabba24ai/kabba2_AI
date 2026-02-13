<?php

namespace App\Jobs;

use App\Models\Orders\Order;
use App\Models\Customers\Receipt;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CreateReceiptJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $orderId,
        public string $paymentMethod = 'Card'
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Load only what we need
            $order = Order::query()
                ->with(['products'])
                ->findOrFail($this->orderId);

            $receipt = Receipt::create([
                'customer_id' => $order->customer_id,
                'order_id' => $order->id,
                'payment_method' => $this->paymentMethod,
                'receipt_date' => now(),
                'order_date' => $order->order_date,
                'payment_status' => 'paid',
                'subtotal' => $order->subtotal,
                'sales_tax' => $order->tax_amount,
                'total' => $order->grand_total,
            ]);

            // Build receipt items in bulk
            $receiptNow = now();
            $rows = [];

            foreach ($order->products as $invItem) {
                $rows[] = [
                    'receipt_id' => $receipt->id,
                    'type' => 'order',
                    'item_name' => $invItem->product_name,
                    'unit' => $invItem->price,
                    'qty' => $invItem->quantity,
                    'tax' => $invItem->tax,
                    'total' => $invItem->total,
                    'item_id' => $invItem->unique_id,
                    'created_at' => $receiptNow,
                    'updated_at' => $receiptNow,
                ];
            }

            if ($rows) {
                $receipt->items()->insert($rows);
            }

            // Mark order receipt as created
            $order->receipt_status = 'created';
            $order->saveQuietly();

            Log::info('Receipt Created for Order', [
                'order_id' => $order->id,
                'order_num' => $order->order_number,
                'receipt_id' => $receipt->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Receipt creation FAILED for order ' . $this->orderId, [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
