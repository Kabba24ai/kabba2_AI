<?php

namespace App\Jobs;

use App\Enums\Orders\OrderTermsStatus;
use App\Models\Orders\Order;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendDailyPendingTermsReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $today = Carbon::now('America/Chicago')->toDateString();

        Order::query()
            ->where('terms_status', OrderTermsStatus::Pending)
            ->whereNull('terms_accepted_at')
            ->whereNull('reference_order_number')
            ->whereHas('products', function ($query) use ($today) {
                $query->where('product_data->product_type', 'Rental')
                    ->whereDate('delivery_date', $today);
            })
            ->select('id')
            ->chunkById(200, function ($orders) {
                foreach ($orders as $order) {
                    SendTermsRequestJob::dispatch($order->id, 3);
                }
            });
    }
}
