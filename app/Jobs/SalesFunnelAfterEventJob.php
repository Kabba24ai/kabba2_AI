<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Queue\Queueable;

// Services
use App\Services\TwilioService;

// Models
use App\Models\Orders\OrderProduct;
use App\Models\Customers\SalesFunnel;
use App\Models\Orders\OrderProductFunnelLog;
use Illuminate\Support\Facades\DB;

class SalesFunnelAfterEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    public function handle(): void
    {
        \Log::channel('sales_funnel')->info(now()->format('Y-m-d H:i:s') . ' Sales Funnel After Event Job start.');

        $now = Carbon::now('America/Chicago');
        $windowStart = $now->copy()->floorMinutes(15);
        $windowEnd   = $windowStart->copy()->addMinutes(15);

        \Log::channel('sales_funnel')->info('Processing time window: ' . $windowStart->toDateTimeString() . ' to ' . $windowEnd->toDateTimeString());

        $twilio = new TwilioService();

        $funnels = SalesFunnel::query()
            ->active()
            ->afterEvent()
            ->whereHas('products')
            ->with(['products:id']) // load only product ids
            ->get();

        \Log::channel('sales_funnel')->info('Processing ' . $funnels->count() . ' active after-event funnels.');

        foreach ($funnels as $funnel) {
            $days = (int) ($funnel->date_value ?? 0);
            $hours = (int) ($funnel->hour_value ?? 0);
            $minutes = (int) ($funnel->minute_value ?? 0);

            $offsetMinutes = ($days * 1440) + ($hours * 60) + $minutes;

            // AFTER EVENT:
            // send_at = delivery_at + offset
            // send_at in [windowStart, windowEnd]
            // => delivery_at in [windowStart - offset, windowEnd - offset]
            $startDelivery = $windowStart->copy()->subMinutes($offsetMinutes);
            $endDelivery   = $windowEnd->copy()->subMinutes($offsetMinutes);

            $productIds = $funnel->products->pluck('id');

            OrderProduct::query()
                ->with(['order.customer', 'order.shippingAddress'])
                ->whereIn('product_id', $productIds)
                ->where('delivery_status', 'Pending')

                // not already processed for this funnel
                ->whereDoesntHave('funnelLogs', function ($q) use ($funnel) {
                    $q->where('sales_funnel_id', $funnel->id);
                })

                ->whereBetween(DB::raw('TIMESTAMP(order_products.delivery_date, order_products.delivery_time)'), [$startDelivery->toDateTimeString(), $endDelivery->toDateTimeString()])

                ->chunkById(500, function ($orderProducts) use ($funnel, $twilio) {
                    foreach ($orderProducts as $op) {
                        $customer = $op->order->customer;
                        $phoneNumber = $op->order->shippingAddress->phone ?? $op->order->customer_phone;

                        $message = $funnel->description;

                        try {
                            $twilio->sendSms($phoneNumber, $message);
                            OrderProductFunnelLog::create([
                                'order_product_id' => $op->id,
                                'sales_funnel_id' => $funnel->id,
                                'product_id' => $op->product_id,
                                'message' => $message,
                                'status' => 'Sent',
                                'sent_at' => Carbon::now(),
                            ]);

                        } catch (\Exception $e) {
                            OrderProductFunnelLog::create([
                                'order_product_id' => $op->id,
                                'sales_funnel_id' => $funnel->id,
                                'product_id' => $op->product_id,
                                'message' => $message,
                                'status' => 'Failed',
                                'sent_at' => Carbon::now(),
                            ]);
                            \Log::channel('sales_funnel')->error("Failed to send SMS. OP={$op->id}, Funnel={$funnel->id}, Error={$e->getMessage()}");
                            continue; // Skip logging if SMS fails
                        }

                        \Log::channel('sales_funnel')->info("Sent SMS. OP={$op->id}, Funnel={$funnel->id}");
                    }
                });
        }

        \Log::channel('sales_funnel')->info(now()->format('Y-m-d H:i:s') . ' Sales Funnel After Event Job end.');
    }
}
