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
        $this->log('info', '[POD Expiry] Job started at ' . $startedAt->toDateTimeString());

        // Cutoff: delivery dates strictly before this date trigger expiry
        $cutoff = now()->subMonth()->toDateString();

        $links = PodPaymentLink::with('order.products')
            ->whereNotIn('pod_status', [
                PodPaymentLinkStatus::Completed->value,
                PodPaymentLinkStatus::Expired->value,
            ])
            ->get()
            ->filter(function (PodPaymentLink $link) use ($cutoff) {
                $maxDeliveryDate = $link->order?->products?->max('delivery_date');
                return $maxDeliveryDate !== null && $maxDeliveryDate < $cutoff;
            });

        $this->log('info', '[POD Expiry] Links eligible for expiry: ' . $links->count());

        $expired = 0;

        foreach ($links as $link) {
            $maxDeliveryDate = $link->order?->products?->max('delivery_date');

            $link->recordEvent(PodPaymentLinkEvent::OrderExpired, [
                'reason'            => '1-month post-delivery expiry window exceeded',
                'max_delivery_date' => $maxDeliveryDate,
                'cutoff'            => $cutoff,
            ]);

            $this->log('info', '[POD Expiry] Expired link for order ' . ($link->order?->unique_id ?? $link->order_id), [
                'pod_payment_link_id' => $link->id,
                'max_delivery_date'   => $maxDeliveryDate,
            ]);

            $expired++;
        }

        $this->log('info', '[POD Expiry] Job completed.', [
            'expired'     => $expired,
            'cutoff'      => $cutoff,
            'started_at'  => $startedAt->toDateTimeString(),
            'finished_at' => now()->toDateTimeString(),
            'duration'    => round(now()->diffInMilliseconds($startedAt)) . 'ms',
        ]);
    }
}
