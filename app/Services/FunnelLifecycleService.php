<?php

namespace App\Services;

use App\Models\Customers\SalesFunnel;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Models\Orders\OrderProductFunnelLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * FunnelLifecycleService — single authority for all CRM Funnel lifecycle transitions.
 *
 * Every lifecycle event that must stop, pause, or transition funnel SMS messages
 * passes through here. No stop logic is scattered across controllers, models, or listeners.
 *
 * Lifecycle reasons (public constants used by callers):
 *   REASON_ORDER_DELETED         — order hard/soft deleted
 *   REASON_ORDER_CANCELLED       — order status changed to cancelled
 *   REASON_DELIVERY_COMPLETED    — equipment delivered on schedule
 *   REASON_DELIVERY_COMPLETED_EARLY — equipment delivered ahead of schedule
 *   REASON_PRODUCT_REMOVED       — individual product removed from order
 *   REASON_COD_CONVERTED         — COD/POD order received a paid payment
 *   REASON_CUSTOMER_DELETED      — customer record deleted
 *
 * Architecture note:
 *   Creating a 'Stopped' (or 'Skipped') OrderProductFunnelLog entry for a given
 *   funnel+step combination is the single mechanism that prevents the scheduler jobs
 *   (SalesFunnelBeforeEventJob / SalesFunnelAfterEventJob) from ever sending that step.
 *   Both jobs use `whereDoesntHave('funnelLogs', ...)` as their deduplication guard.
 *   A Stopped entry satisfies that guard and permanently blocks the send.
 */
class FunnelLifecycleService
{
    public const REASON_ORDER_DELETED            = 'Order Deleted';
    public const REASON_ORDER_CANCELLED          = 'Order Cancelled';
    public const REASON_DELIVERY_COMPLETED       = 'Delivery Completed';
    public const REASON_DELIVERY_COMPLETED_EARLY = 'Delivery Completed Early';
    public const REASON_PRODUCT_REMOVED          = 'Product Removed';
    public const REASON_COD_CONVERTED            = 'POD Converted To Paid';
    public const REASON_CUSTOMER_DELETED         = 'Customer Deleted';

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Stop ALL funnel steps for every product on an order.
     *
     * Called when an order is deleted or cancelled. Processes all active
     * OrderProducts on the order before they are themselves deleted.
     */
    public static function stopFunnelsForOrder(Order $order, string $reason): void
    {
        $orderProducts = $order->products()->get();

        if ($orderProducts->isEmpty()) {
            Log::channel('sales_funnel')->info(
                "FunnelLifecycle [{$reason}]: Order={$order->id} has no products — nothing to stop."
            );
            return;
        }

        Log::channel('sales_funnel')->info(
            "FunnelLifecycle [{$reason}]: Stopping all funnels for Order={$order->id} | " .
            "Products=" . $orderProducts->pluck('id')->implode(',')
        );

        $stopped = 0;

        foreach ($orderProducts as $op) {
            $stopped += self::stopAllFunnelsForProduct($op, $reason);
        }

        Log::channel('sales_funnel')->info(
            "FunnelLifecycle [{$reason}]: Order={$order->id} — {$stopped} future step(s) stopped."
        );
    }

    /**
     * Stop ALL funnel steps (before-event and after-event) for a single OrderProduct.
     *
     * Called when a product is individually removed from an order, or as a sub-step
     * of stopFunnelsForOrder. Returns the count of new Stopped entries created.
     */
    public static function stopAllFunnelsForProduct(OrderProduct $op, string $reason): int
    {
        $funnels = self::getFunnelsForProduct($op->product_id);
        return self::createStoppedEntries($op, $funnels, null, $reason);
    }

    /**
     * Stop ONLY delivery-reminder funnel steps (before-event) for a single OrderProduct.
     *
     * Called when delivery is marked Completed. Post-delivery funnels (after-event,
     * review requests, return reminders) are intentionally NOT stopped here.
     *
     * "Delivery reminder" = any step where offset_direction = 'before'.
     */
    public static function stopDeliveryReminderFunnels(OrderProduct $op, string $reason): int
    {
        // Only before-event funnels qualify as delivery reminders
        $funnels = self::getFunnelsForProduct($op->product_id, 'before');
        return self::createStoppedEntries($op, $funnels, 'before', $reason);
    }

    /**
     * Handle COD/POD → Paid conversion.
     *
     * Stops all unsent COD funnel steps and marks already-past paid-funnel
     * steps as Skipped. This is the same logic previously in FunnelConversionService,
     * now centralized here. FunnelConversionService now delegates to this method.
     */
    public static function handleCodToPaidConversion(Order $order, ?string $convertedAt = null): void
    {
        $convertedAt   = $convertedAt ?? now()->toDateTimeString();
        $orderProducts = $order->products()->get();

        if ($orderProducts->isEmpty()) {
            return;
        }

        $productIds = $orderProducts->pluck('product_id')->unique();

        Log::channel('sales_funnel')->info(
            "FunnelLifecycle [" . self::REASON_COD_CONVERTED . "]: Order={$order->id} | " .
            "convertedAt={$convertedAt} | Products=" . $productIds->implode(',')
        );

        // -- Stop all unsent COD funnel steps --
        $codFunnels = SalesFunnel::query()
            ->active()
            ->where('funnel_order_type', 'cod')
            ->whereHas('products', fn($q) => $q->whereIn('products.id', $productIds))
            ->with(['products:id', 'steps'])
            ->get();

        $stopped = 0;

        foreach ($codFunnels as $funnel) {
            foreach ($orderProducts as $op) {
                foreach ($funnel->steps as $step) {
                    if (self::logExists($op->id, $funnel->id, $step->id)) {
                        continue;
                    }

                    self::writeLog($op, $funnel, $step, 'Stopped', self::REASON_COD_CONVERTED,
                        "Stopped: order converted COD→Paid at {$convertedAt}"
                    );
                    $stopped++;
                }
            }
        }

        Log::channel('sales_funnel')->info(
            "FunnelLifecycle [" . self::REASON_COD_CONVERTED . "]: Order={$order->id} — " .
            "{$stopped} COD step(s) stopped."
        );

        // -- Mark already-past paid-funnel steps as Skipped --
        $paidFunnels = SalesFunnel::query()
            ->active()
            ->where('funnel_order_type', 'paid')
            ->whereHas('products', fn($q) => $q->whereIn('products.id', $productIds))
            ->with(['products:id', 'steps'])
            ->get();

        $convertedCarbon = Carbon::parse($convertedAt);
        $skipped         = 0;

        foreach ($paidFunnels as $funnel) {
            foreach ($orderProducts as $op) {
                foreach ($funnel->steps as $step) {
                    if (self::logExists($op->id, $funnel->id, $step->id)) {
                        continue;
                    }

                    if (!$op->delivery_date || !$op->delivery_time) {
                        continue;
                    }

                    $deliveryAt  = Carbon::parse("{$op->delivery_date} {$op->delivery_time}");
                    $direction   = $step->offset_direction ?? 'after';
                    $scheduledAt = $direction === 'before'
                        ? $deliveryAt->copy()->subMinutes((int) ($step->offset_minutes ?? 0))
                        : $deliveryAt->copy()->addMinutes((int) ($step->offset_minutes ?? 0));

                    if ($scheduledAt->lt($convertedCarbon)) {
                        self::writeLog($op, $funnel, $step, 'Skipped', self::REASON_COD_CONVERTED,
                            "Skipped: step would have sent at {$scheduledAt->format('Y-m-d H:i:s')} " .
                            "but order converted at {$convertedAt}"
                        );
                        $skipped++;
                    }
                }
            }
        }

        Log::channel('sales_funnel')->info(
            "FunnelLifecycle [" . self::REASON_COD_CONVERTED . "]: Order={$order->id} — " .
            "{$skipped} paid step(s) marked Skipped. Conversion complete."
        );
    }

    // ── Internal helpers ──────────────────────────────────────────────────────

    /**
     * Load all active funnels that include a given product_id.
     * Optionally filter to only funnels containing steps of a specific offset_direction.
     */
    private static function getFunnelsForProduct(int $productId, ?string $offsetDirection = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = SalesFunnel::query()
            ->active()
            ->whereHas('products', fn($q) => $q->where('products.id', $productId))
            ->with([
                'steps' => function ($q) use ($offsetDirection) {
                    if ($offsetDirection) {
                        $q->where('offset_direction', $offsetDirection);
                    }
                },
            ]);

        return $query->get();
    }

    /**
     * Create 'Stopped' log entries for unsent funnel steps.
     * Skips steps that already have a log entry (deduplication).
     * Returns the count of new entries created.
     */
    private static function createStoppedEntries(
        OrderProduct $op,
        \Illuminate\Database\Eloquent\Collection $funnels,
        ?string $offsetDirectionFilter,
        string $reason
    ): int {
        $count = 0;

        foreach ($funnels as $funnel) {
            $steps = $funnel->steps;

            if ($offsetDirectionFilter) {
                $steps = $steps->where('offset_direction', $offsetDirectionFilter);
            }

            foreach ($steps as $step) {
                if (self::logExists($op->id, $funnel->id, $step->id)) {
                    continue;
                }

                self::writeLog($op, $funnel, $step, 'Stopped', $reason,
                    "Stopped by lifecycle event: {$reason} | OP={$op->id} | Order={$op->order_id}"
                );

                Log::channel('sales_funnel')->info(
                    "FunnelLifecycle [{$reason}] STOPPED | OP={$op->id} | " .
                    "Funnel={$funnel->id} ({$funnel->funnel_name}) | Step={$step->id}"
                );

                $count++;
            }
        }

        return $count;
    }

    /**
     * Check whether a funnel log already exists for this product+funnel+step combination.
     * Prevents duplicate Stopped entries and respects already-sent steps.
     */
    private static function logExists(int $orderProductId, int $funnelId, ?int $stepId): bool
    {
        $query = OrderProductFunnelLog::where('order_product_id', $orderProductId)
            ->where('sales_funnel_id', $funnelId);

        if ($stepId) {
            $query->where('sales_funnel_step_id', $stepId);
        } else {
            $query->whereNull('sales_funnel_step_id');
        }

        return $query->exists();
    }

    /**
     * Write a single funnel log entry with lifecycle metadata.
     */
    private static function writeLog(
        OrderProduct $op,
        SalesFunnel $funnel,
        object $step,
        string $status,
        string $lifecycleReason,
        string $notes
    ): void {
        OrderProductFunnelLog::create([
            'order_product_id'     => $op->id,
            'order_id'             => $op->order_id,
            'customer_id'          => $op->order?->customer_id,
            'sales_funnel_id'      => $funnel->id,
            'sales_funnel_step_id' => $step->id ?? null,
            'step_type'            => $step->step_type ?? null,
            'step_name'            => $step->name ?? null,
            'product_id'           => $op->product_id,
            'message'              => null,
            'status'               => $status,
            'notes'                => $notes,
            'lifecycle_reason'     => $lifecycleReason,
            'stopped_at'           => now(),
            'sent_at'              => now(),
        ]);
    }
}
