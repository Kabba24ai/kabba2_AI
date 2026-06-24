<?php

namespace App\Jobs;

use App\Enums\Orders\PodPaymentLinkEvent;
use App\Enums\Orders\PodPaymentLinkStatus;
use App\Models\Orders\PodPaymentLink;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExpirePodPaymentLinksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private function log(string $level, string $message, array $context = []): void
    {
        Log::channel('jobs')->{$level}($message, $context);
    }

    public function handle(): void
    {
        $startedAt = now();
        $this->log('info', '[POD Expiry] Job started.', [
            'started_at' => $startedAt->toDateTimeString(),
            'job_class'  => self::class,
        ]);

        // Cutoff: delivery dates strictly before this date trigger expiry
        $cutoff = now()->subMonth()->toDateString();
        $this->log('info', '[POD Expiry] Expiry cutoff calculated.', [
            'cutoff'             => $cutoff,
            'excluded_statuses'  => [PodPaymentLinkStatus::Completed->value, PodPaymentLinkStatus::Expired->value],
        ]);

        $allActive = PodPaymentLink::with('order.products')
            ->whereNotIn('pod_status', [
                PodPaymentLinkStatus::Completed->value,
                PodPaymentLinkStatus::Expired->value,
            ])
            ->get();

        $this->log('info', '[POD Expiry] Active links fetched from database.', [
            'total_active' => $allActive->count(),
        ]);

        $links = $allActive->filter(function (PodPaymentLink $link) use ($cutoff) {
            $maxDeliveryDate = $link->order?->products?->max('delivery_date');
            return $maxDeliveryDate !== null && $maxDeliveryDate < $cutoff;
        });

        $skippedNoDelivery = $allActive->count() - $links->count();

        $this->log('info', '[POD Expiry] Links filtered for expiry.', [
            'eligible_for_expiry'    => $links->count(),
            'skipped_no_delivery'    => $skippedNoDelivery,
        ]);

        $expired = 0;
        $failed  = 0;

        foreach ($links as $link) {
            $orderId         = $link->order?->unique_id ?? $link->order_id;
            $maxDeliveryDate = $link->order?->products?->max('delivery_date');
            $productsCount   = $link->order?->products?->count() ?? 0;
            $statusBefore    = $link->pod_status instanceof PodPaymentLinkStatus
                ? $link->pod_status->value
                : $link->pod_status;

            $this->log('info', '[POD Expiry] Processing link.', [
                'pod_payment_link_id' => $link->id,
                'order_id'            => $orderId,
                'status_before'       => $statusBefore,
                'max_delivery_date'   => $maxDeliveryDate,
                'products_count'      => $productsCount,
                'cutoff'              => $cutoff,
            ]);

            try {
                $link->recordEvent(PodPaymentLinkEvent::OrderExpired, [
                    'reason'            => '1-month post-delivery expiry window exceeded',
                    'max_delivery_date' => $maxDeliveryDate,
                    'cutoff'            => $cutoff,
                ]);

                $this->log('info', '[POD Expiry] Link successfully expired.', [
                    'pod_payment_link_id' => $link->id,
                    'order_id'            => $orderId,
                    'status_before'       => $statusBefore,
                    'status_after'        => PodPaymentLinkStatus::Expired->value,
                    'max_delivery_date'   => $maxDeliveryDate,
                ]);

                $expired++;
            } catch (\Throwable $e) {
                $failed++;
                $this->log('error', '[POD Expiry] Failed to expire link.', [
                    'pod_payment_link_id' => $link->id,
                    'order_id'            => $orderId,
                    'max_delivery_date'   => $maxDeliveryDate,
                    'error'               => $e->getMessage(),
                    'exception_class'     => get_class($e),
                    'file'                => $e->getFile(),
                    'line'                => $e->getLine(),
                ]);
            }
        }

        $level = $failed > 0 ? 'warning' : 'info';
        $this->log($level, '[POD Expiry] Job completed.', [
            'expired'          => $expired,
            'failed'           => $failed,
            'skipped'          => $skippedNoDelivery,
            'total_active'     => $allActive->count(),
            'cutoff'           => $cutoff,
            'started_at'       => $startedAt->toDateTimeString(),
            'finished_at'      => now()->toDateTimeString(),
            'duration_ms'      => round(now()->diffInMilliseconds($startedAt)),
        ]);
    }
}
