<?php

namespace App\Events\Admin\Orders;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Orders\Order;

class OrderCustomerChecklistEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;
    public $employee;
    public $type;

    public function __construct(Order $order, $employee, $type)
    {
        $this->order = $order;
        $this->employee = $employee;
        $this->type = $type;
    }
}
