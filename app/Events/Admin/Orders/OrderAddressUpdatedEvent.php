<?php

namespace App\Events\Admin\Orders;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Orders\Order;

class OrderAddressUpdatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;
    public $type;
    public $employee;

    public function __construct(Order $order, $employee, $type)
    {
        $this->order = $order;
        $this->type = $type;
        $this->employee = $employee;
    }
}
