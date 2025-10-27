<?php

namespace App\Events\Admin\Orders;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Orders\Order;

class OrderProductScheduleUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;
    public $employee;
    public $data;

    public function __construct(Order $order, $employee, $data)
    {
        $this->order = $order;
        $this->employee = $employee;
        $this->data = $data;
    }
}
