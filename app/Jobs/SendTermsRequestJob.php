<?php

namespace App\Jobs;

use App\Models\Orders\Order;
use App\Services\TermsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendTermsRequestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $orderId;
    public $sendNumber;
    public $source;

    public function __construct($orderId, $sendNumber, $source = 'automatic')
    {
        $this->orderId = $orderId;
        $this->sendNumber = $sendNumber;
        $this->source = $source;
    }

    public function handle(): void
    {
        $order = Order::find($this->orderId);
        if (! $order) return;
        if ($order->terms_signed_at) return;
        if ($order->reference_order_number) return;
        app(TermsService::class)->sendTermsRequest($order, $this->sendNumber, $this->source);
    }
}
