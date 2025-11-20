<?php
namespace App\Events\Admin\Orders;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Orders\Order;

class RefundInitiateEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;
    public $user;
    public $payment;

    public function __construct(Order $order, $user, $payment)
    {
        $this->order = $order;
        $this->user = $user;
        $this->payment = $payment;
    }
}
