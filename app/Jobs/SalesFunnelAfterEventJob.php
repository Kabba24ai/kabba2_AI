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
use App\Models\Configurations\Setting;
use App\Enums\Communication\SmsType;
use Illuminate\Support\Facades\DB;

class SalesFunnelAfterEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        \Log::channel('sales_funnel')->info('=== Sales Funnel After Event Job START ===');

        if (!config('app.sales_funnel_flag', false)) {
            \Log::channel('sales_funnel')->info('After-event funnels are disabled (SALES_FUNNEL_ENABLED not set). Exiting.');
            return;
        }

        // Load timezone from the Time Zone Settings tab; fall back to America/Chicago
        $smsTimezone = Setting::where('setting_name', 'twilio_timezone')->value('setting_value') ?: 'America/Chicago';

        $now         = Carbon::now($smsTimezone);
        $windowStart = $now->copy()->floorMinutes(15);
        $windowEnd   = $windowStart->copy()->addMinutes(15);

        \Log::channel('sales_funnel')->info(
            "SMS Timezone: {$smsTimezone} | Evaluation time: {$now->format('Y-m-d H:i:s')} | " .
            "Window: {$windowStart->format('Y-m-d H:i:s')} - {$windowEnd->format('Y-m-d H:i:s')}"
        );

        $twilio = new TwilioService();

        $funnels = SalesFunnel::query()
            ->active()
            ->afterEvent()
            ->whereHas('products')
            ->with(['products:id', 'steps'])
            ->get();

        \Log::channel('sales_funnel')->info("Active after-event funnels found: {$funnels->count()}");

        foreach ($funnels as $funnel) {
            $productIds = $funnel->products->pluck('id');
            $steps      = $funnel->steps->where('step_type', 'SMS')->sortBy('id')->values();

            // Fallback: funnel-level message with no steps defined
            if ($steps->isEmpty()) {
                $legacyMinutes = ((int) ($funnel->date_value ?? 0) * 1440)
                              + ((int) ($funnel->hour_value ?? 0) * 60)
                              + (int) ($funnel->minute_value ?? 0);

                $steps = collect([(object) [
                    'id'             => null,
                    'step_type'      => 'SMS',
                    'name'           => null,
                    'message'        => $funnel->description,
                    'offset_minutes' => $legacyMinutes,
                ]]);
            }

            $cumulativeOffsetMinutes = 0;

            foreach ($steps as $step) {
                // offset_minutes is the single source of truth
                $stepOffsetMinutes = (int) ($step->offset_minutes ?? 0);

                // Steps are cumulative: step 2 fires at (step1_offset + step2_offset) after delivery
                $cumulativeOffsetMinutes += $stepOffsetMinutes;
                $offsetMinutes = $cumulativeOffsetMinutes;

                // AFTER EVENT: send_at = delivery_at + offset
                // => delivery_at must be in [windowStart - offset, windowEnd - offset]
                $startDelivery = $windowStart->copy()->subMinutes($offsetMinutes);
                $endDelivery   = $windowEnd->copy()->subMinutes($offsetMinutes);

                \Log::channel('sales_funnel')->info(
                    "Funnel={$funnel->id} ({$funnel->funnel_name}) | Step=" . ($step->id ?? 'legacy') .
                    " | offset_minutes={$offsetMinutes} | delivery window: " .
                    "{$startDelivery->format('Y-m-d H:i:s')} - {$endDelivery->format('Y-m-d H:i:s')}"
                );

                $baseQuery = OrderProduct::query()
                    ->with(['order.customer', 'order.shippingAddress'])
                    ->whereIn('product_id', $productIds)
                    ->where('delivery_status', 'Pending')
                    // Safety: skip products whose parent order has been soft-deleted
                    ->whereHas('order', fn($q) => $q->whereNull('deleted_at'))
                    ->whereDoesntHave('funnelLogs', function ($q) use ($funnel, $step) {
                        $q->where('sales_funnel_id', $funnel->id);
                        if ($step->id) {
                            $q->where('sales_funnel_step_id', $step->id);
                        } else {
                            $q->whereNull('sales_funnel_step_id');
                        }
                    })
                    ->whereBetween(
                        DB::raw('TIMESTAMP(order_products.delivery_date, order_products.delivery_time)'),
                        [$startDelivery->toDateTimeString(), $endDelivery->toDateTimeString()]
                    );

                // Apply payment-type filter based on funnel_order_type
                $orderType = $funnel->funnel_order_type ?? 'all';

                if ($orderType === 'cod') {
                    // Only fire for COD/POD orders that have not yet been paid
                    $baseQuery->whereHas('order.payments', function ($q) {
                        $q->where('payment_method', 'COD')->where('status', 'Pending');
                    })->whereDoesntHave('order.payments', function ($q) {
                        $q->where('status', 'Paid');
                    });
                } elseif ($orderType === 'paid') {
                    // Only fire for orders that have at least one paid payment
                    $baseQuery->whereHas('order.payments', function ($q) {
                        $q->where('status', 'Paid');
                    });
                }
                // 'all' (default): no payment filter

                $baseQuery->chunkById(500, function ($orderProducts) use ($funnel, $step, $twilio, $smsTimezone, $offsetMinutes, $startDelivery, $endDelivery) {
                    foreach ($orderProducts as $op) {
                        // Race-condition guard: order may have been soft-deleted between query and chunk processing
                        if (!$op->order || $op->order->deleted_at !== null) {
                            \Log::channel('sales_funnel')->warning(
                                "Skipping OP={$op->id}: order is missing or deleted (race condition guard)."
                            );
                            continue;
                        }

                        // Guard: customer must exist
                        if (!$op->order->customer) {
                            \Log::channel('sales_funnel')->warning(
                                "Skipping OP={$op->id}: customer not found on order {$op->order_id}."
                            );
                            continue;
                        }

                        $customer    = $op->order->customer;
                        $phoneNumber = $op->order->shippingAddress->phone ?? $op->order->customer_phone ?? null;
                        $message     = $step->message ?: $funnel->description;

                        \Log::channel('sales_funnel')->info(
                            "Processing OP={$op->id} | OrderID={$op->order_id} | Product={$op->product_id} | " .
                            "delivery_date={$op->delivery_date} delivery_time={$op->delivery_time} | " .
                            "offset={$offsetMinutes}min | timezone={$smsTimezone}"
                        );

                        if (!$phoneNumber) {
                            \Log::channel('sales_funnel')->warning("Skipping OP={$op->id}: no phone number found.");
                            continue;
                        }

                        try {
                            $response = $twilio->sendSms($phoneNumber, $message, [], [
                                'order_id'         => $op->order_id,
                                'order_product_id' => $op->id,
                                'customer_id'      => $customer?->id,
                                'sms_type'         => SmsType::SALES_FUNNEL_AFTER,
                            ]);

                            $success   = ($response['success'] ?? false);
                            $twilioSid = $response['sid'] ?? $response['twilio_sid'] ?? null;
                            $sentAt    = Carbon::now($smsTimezone);

                            OrderProductFunnelLog::create([
                                'order_product_id'     => $op->id,
                                'sales_funnel_id'      => $funnel->id,
                                'sales_funnel_step_id' => $step->id,
                                'step_type'            => $step->step_type,
                                'step_name'            => $step->name,
                                'product_id'           => $op->product_id,
                                'message'              => $message,
                                'status'               => $success ? 'Sent' : 'Failed',
                                'notes'                => $success
                                    ? "Sent {$sentAt->format('Y-m-d H:i:s')} {$smsTimezone} | offset={$offsetMinutes}min"
                                    : ($response['message'] ?? 'Send failed'),
                                'twilio_sid'           => $twilioSid,
                                'sms_timezone'         => $smsTimezone,
                                'sent_at'              => $sentAt,
                            ]);

                            if ($success) {
                                \Log::channel('sales_funnel')->info(
                                    "SENT | OP={$op->id} | Funnel={$funnel->id} | Step=" . ($step->id ?? 'legacy') .
                                    " | SID=" . ($twilioSid ?? 'n/a') . " | {$sentAt->format('Y-m-d H:i:s')}"
                                );
                            } else {
                                \Log::channel('sales_funnel')->warning(
                                    "FAILED | OP={$op->id} | Funnel={$funnel->id} | Step=" . ($step->id ?? 'legacy') .
                                    " | Error=" . ($response['message'] ?? 'unknown')
                                );
                            }
                        } catch (\Exception $e) {
                            OrderProductFunnelLog::create([
                                'order_product_id'     => $op->id,
                                'sales_funnel_id'      => $funnel->id,
                                'sales_funnel_step_id' => $step->id,
                                'step_type'            => $step->step_type,
                                'step_name'            => $step->name,
                                'product_id'           => $op->product_id,
                                'message'              => $message,
                                'status'               => 'Failed',
                                'notes'                => $e->getMessage(),
                                'sms_timezone'         => $smsTimezone,
                                'sent_at'              => Carbon::now($smsTimezone),
                            ]);

                            \Log::channel('sales_funnel')->error(
                                "EXCEPTION | OP={$op->id} | Funnel={$funnel->id} | Step=" . ($step->id ?? 'legacy') .
                                " | {$e->getMessage()}"
                            );
                        }
                    }
                });
            }
        }

        \Log::channel('sales_funnel')->info('=== Sales Funnel After Event Job END ===');
    }
}
