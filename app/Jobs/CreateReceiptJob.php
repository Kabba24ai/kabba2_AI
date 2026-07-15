<?php

namespace App\Jobs;

use App\Models\Orders\Order;
use App\Services\ReceiptService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CreateReceiptJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     *
     * @deprecated $paymentMethod is no longer used — ReceiptService derives
     * the receipt's payment method and status from the order's actual
     * payment record instead of trusting a caller-supplied guess (which
     * previously defaulted to 'Card'/'paid' regardless of how the order
     * was really paid). Kept as a constructor param only so existing
     * dispatch(...) call sites don't need to change.
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

            $receipt = ReceiptService::getOrCreateReceipt($order);

            if ($receipt) {
                Log::info('Receipt Created for Order', [
                    'order_id' => $order->id,
                    'order_num' => $order->order_number,
                    'receipt_id' => $receipt->id,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Receipt creation FAILED for order ' . $this->orderId, [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
