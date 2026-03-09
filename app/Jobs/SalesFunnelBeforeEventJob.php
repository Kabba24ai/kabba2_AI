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
use App\Enums\Communication\SmsType;
use Illuminate\Support\Facades\DB;

class SalesFunnelBeforeEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    public function handle(): void
    {
        \Log::channel('sales_funnel')->info(now()->format('Y-m-d H:i:s') . ' Sales Funnel Before Event Job start.');

        if (!config('app.sales_funnel_flag', false)) {
            \Log::channel('sales_funnel')->info('After-event funnels are disabled. Exiting job.');
            return;
        }

        $now = Carbon::now(config('app.timezone', 'UTC'));
        $windowStart = $now->copy()->floorMinutes(15);
        $windowEnd = $now->copy()->floorMinutes(15)->addMinutes(15);

        \Log::channel('sales_funnel')->info('Processing time window: ' . $windowStart->toDateTimeString() . ' to ' . $windowEnd->toDateTimeString());

        $twilio = new TwilioService();

        $funnels = SalesFunnel::query()
            ->active()
            ->beforeEvent()
            ->whereHas('products')
            ->with(['products:id', 'steps'])
            ->get();

        \Log::channel('sales_funnel')->info('Processing ' . $funnels->count() . ' active before-event funnels.');
        foreach ($funnels as $funnel) {
            $productIds = $funnel->products->pluck('id');
            $steps = $funnel->steps->where('step_type', 'SMS');

            if ($steps->isEmpty()) {
                $steps = collect([(object) [
                    'id' => null,
                    'step_type' => 'SMS',
                    'name' => null,
                    'message' => $funnel->description,
                    'delay_unit' => 'Minutes',
                    'delay_value' => ((int) ($funnel->date_value ?? 0) * 1440) + ((int) ($funnel->hour_value ?? 0) * 60) + (int) ($funnel->minute_value ?? 0),
                ]]);
            }

            foreach ($steps as $step) {
                $delayValue = (int) ($step->delay_value ?? 0);
                $offsetMinutes = match ($step->delay_unit) {
                    'Days' => $delayValue * 1440,
                    'Hours' => $delayValue * 60,
                    default => $delayValue,
                };

                // If send_at = delivery_at - offset
                // Then delivery_at must be between (nowWindow + offset)
                $startDelivery = $windowStart->copy()->addMinutes($offsetMinutes);
                $endDelivery = $windowEnd->copy()->addMinutes($offsetMinutes);

                OrderProduct::query()
                    ->with(['order.customer', 'order.shippingAddress'])
                    ->whereIn('product_id', $productIds)
                    ->where('delivery_status', 'Pending')

                    // not already processed for this funnel step
                    ->whereDoesntHave('funnelLogs', function ($q) use ($funnel, $step) {
                        $q->where('sales_funnel_id', $funnel->id);

                        if ($step->id) {
                            $q->where('sales_funnel_step_id', $step->id);
                        } else {
                            $q->whereNull('sales_funnel_step_id');
                        }
                    })

                    // THIS is the "before event" timing check
                    ->whereBetween(DB::raw('TIMESTAMP(order_products.delivery_date, order_products.delivery_time)'), [$startDelivery->toDateTimeString(), $endDelivery->toDateTimeString()])

                    ->chunkById(500, function ($orderProducts) use ($funnel, $step, $twilio) {
                        foreach ($orderProducts as $op) {
                            $customer = $op->order->customer;
                            $phoneNumber = $op->order->shippingAddress->phone ?? $op->order->customer_phone;

                            $message = $step->message ?: $funnel->description;

                            try {
                                $response = $twilio->sendSms($phoneNumber, $message, [], [
                                    'order_id'         => $op->order_id,
                                    'order_product_id' => $op->id,
                                    'customer_id'      => $customer?->id,
                                    'sms_type'         => SmsType::SALES_FUNNEL_BEFORE,
                                ]);

                                OrderProductFunnelLog::create([
                                    'order_product_id' => $op->id,
                                    'sales_funnel_id' => $funnel->id,
                                    'sales_funnel_step_id' => $step->id,
                                    'step_type' => $step->step_type,
                                    'step_name' => $step->name,
                                    'product_id' => $op->product_id,
                                    'message' => $message,
                                    'status' => ($response['success'] ?? false) ? 'Sent' : 'Failed',
                                    'sent_at' => Carbon::now(),
                                ]);

                                if (!($response['success'] ?? false)) {
                                    \Log::channel('sales_funnel')->warning("Failed to send SMS. OP={$op->id}, Funnel={$funnel->id}, Step=" . ($step->id ?? 'legacy') . ", Error=" . ($response['message'] ?? 'unknown'));
                                } else {
                                    \Log::channel('sales_funnel')->info("Sent SMS. OP={$op->id}, Funnel={$funnel->id}, Step=" . ($step->id ?? 'legacy'));
                                }
                            } catch (\Exception $e) {
                                OrderProductFunnelLog::create([
                                    'order_product_id' => $op->id,
                                    'sales_funnel_id' => $funnel->id,
                                    'sales_funnel_step_id' => $step->id,
                                    'step_type' => $step->step_type,
                                    'step_name' => $step->name,
                                    'product_id' => $op->product_id,
                                    'message' => $message,
                                    'status' => 'Failed',
                                    'sent_at' => Carbon::now(),
                                ]);

                                \Log::channel('sales_funnel')->error("Failed to send SMS. OP={$op->id}, Funnel={$funnel->id}, Step=" . ($step->id ?? 'legacy') . ", Error={$e->getMessage()}");
                                continue;
                            }
                        }
                    });
            }
        }

        \Log::channel('sales_funnel')->info(now()->format('Y-m-d H:i:s') . ' Sales Funnel Before Event Job end.');
    }
}
