<?php

namespace App\Services;

use App\Models\Customers\SalesFunnel;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProductFunnelLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class FunnelConversionService
{
    /**
     * Called when a COD/POD order receives a paid payment.
     *
     * Stops all unsent COD funnel steps for the order's products by writing
     * 'Stopped' log entries. This prevents the job from ever sending them,
     * because the deduplication check (whereDoesntHave funnelLogs) will now
     * find these entries and skip.
     *
     * Also logs which paid-funnel steps would have been in the past at
     * conversion time so the audit trail is complete.
     */
    public static function handleCodToPaidConversion(Order $order, ?string $convertedAt = null): void
    {
        $convertedAt = $convertedAt ?? now()->toDateTimeString();
        $orderProducts = $order->products()->get();

        if ($orderProducts->isEmpty()) {
            return;
        }

        $productIds = $orderProducts->pluck('product_id')->unique();

        Log::channel('sales_funnel')->info(
            "FunnelConversion: Order={$order->id} converted COD->Paid at {$convertedAt} | " .
            "Products=" . $productIds->implode(',')
        );

        // -- Stop all unsent COD funnel steps --
        $codFunnels = SalesFunnel::query()
            ->active()
            ->where('funnel_order_type', 'cod')
            ->whereHas('products', fn($q) => $q->whereIn('products.id', $productIds))
            ->with(['products:id', 'steps'])
            ->get();

        foreach ($codFunnels as $funnel) {
            foreach ($orderProducts as $op) {
                foreach ($funnel->steps as $step) {
                    $alreadySent = OrderProductFunnelLog::where('order_product_id', $op->id)
                        ->where('sales_funnel_id', $funnel->id)
                        ->where('sales_funnel_step_id', $step->id)
                        ->exists();

                    if (!$alreadySent) {
                        OrderProductFunnelLog::create([
                            'order_product_id'     => $op->id,
                            'sales_funnel_id'      => $funnel->id,
                            'sales_funnel_step_id' => $step->id,
                            'step_type'            => $step->step_type,
                            'step_name'            => $step->name,
                            'product_id'           => $op->product_id,
                            'message'              => null,
                            'notes'                => "Stopped: order converted COD->Paid at {$convertedAt}",
                            'status'               => 'Stopped',
                            'sent_at'              => now(),
                        ]);

                        Log::channel('sales_funnel')->info(
                            "FunnelConversion STOPPED | OP={$op->id} | Funnel={$funnel->id} " .
                            "({$funnel->funnel_name}) | Step={$step->id}"
                        );
                    }
                }
            }
        }

        // -- Log which paid-funnel steps are already in the past at conversion time --
        $paidFunnels = SalesFunnel::query()
            ->active()
            ->where('funnel_order_type', 'paid')
            ->whereHas('products', fn($q) => $q->whereIn('products.id', $productIds))
            ->with(['products:id', 'steps'])
            ->get();

        $convertedCarbon = Carbon::parse($convertedAt);

        foreach ($paidFunnels as $funnel) {
            foreach ($orderProducts as $op) {
                foreach ($funnel->steps as $step) {
                    $offsetMinutes = (int) ($step->offset_minutes ?? 0);

                    // Calculate when this step would fire
                    $deliveryAt = null;
                    if ($op->delivery_date && $op->delivery_time) {
                        $deliveryAt = Carbon::parse("{$op->delivery_date} {$op->delivery_time}");
                    }

                    if (!$deliveryAt) {
                        continue;
                    }

                    $direction   = $step->offset_direction ?? 'after';
                    $scheduledAt = $direction === 'before'
                        ? $deliveryAt->copy()->subMinutes($offsetMinutes)
                        : $deliveryAt->copy()->addMinutes($offsetMinutes);

                    if ($scheduledAt->lt($convertedCarbon)) {
                        // This paid-funnel step would have sent before conversion — mark Skipped
                        $alreadyLogged = OrderProductFunnelLog::where('order_product_id', $op->id)
                            ->where('sales_funnel_id', $funnel->id)
                            ->where('sales_funnel_step_id', $step->id)
                            ->exists();

                        if (!$alreadyLogged) {
                            OrderProductFunnelLog::create([
                                'order_product_id'     => $op->id,
                                'sales_funnel_id'      => $funnel->id,
                                'sales_funnel_step_id' => $step->id,
                                'step_type'            => $step->step_type,
                                'step_name'            => $step->name,
                                'product_id'           => $op->product_id,
                                'message'              => null,
                                'notes'                => "Skipped: step would have sent at {$scheduledAt->format('Y-m-d H:i:s')} but order converted at {$convertedAt}",
                                'status'               => 'Skipped',
                                'sent_at'              => now(),
                            ]);

                            Log::channel('sales_funnel')->info(
                                "FunnelConversion SKIPPED (past) | OP={$op->id} | Funnel={$funnel->id} " .
                                "({$funnel->funnel_name}) | Step={$step->id} | would-have-sent={$scheduledAt->format('Y-m-d H:i:s')}"
                            );
                        }
                    }
                }
            }
        }

        Log::channel('sales_funnel')->info("FunnelConversion complete for Order={$order->id}");
    }
}
