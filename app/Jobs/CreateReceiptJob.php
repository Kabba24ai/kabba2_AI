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

    protected $order;
    protected $paymentMethod;

    /**
     * Create a new job instance.
     */
    public function __construct(Order $order, string $paymentMethod = 'card')
    {
        $this->order = $order;
        $this->paymentMethod = $paymentMethod;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $receipt = Receipt::create([
                'customer_id' => $this->order->customer_id,
                'order_id' => $this->order->id,
                'payment_method' => $this->paymentMethod,
                'receipt_date' => now(),
                'order_date' => $this->order->order_date,
                'payment_status' => 'paid',
                'subtotal' => $this->order->subtotal,
                'sales_tax' => $this->order->tax_amount,
                'total' => $this->order->grand_total,
            ]);

            // Build receipt items in bulk
            $receiptItemRows = [];
            $receiptNow = now();

            foreach ($this->order->products as $invItem) {
                $receiptItemRows[] = [
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

            if (!empty($receiptItemRows)) {
                $receipt->items()->insert($receiptItemRows);
            }

            // Mark order receipt as created
            $this->order->receipt_status = 'created';
            $this->order->saveQuietly();

            Log::info('Receipt Created for Order', [
                'order_id' => $this->order->id,
                'order_num' => $this->order->order_number,
                'receipt_id' => $receipt->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Receipt creation FAILED for order ' . $this->order->id, [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
