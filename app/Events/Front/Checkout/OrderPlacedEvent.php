<?php
namespace App\Events\Front\Checkout;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Orders\Order;

class OrderPlacedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;
    public $customer;
    public $payment;

    public function __construct(Order $order, $customer, $payment)
    {
        $this->order = $order;
        $this->customer = $customer;
        $this->payment = $payment;
    }
}
