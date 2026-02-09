<?php

namespace App\Events\Admin\Orders;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Orders\Order;
use App\Models\Orders\OrderExtraCharges;
use App\Models\Iam\Personnel\User;

class OrderExtraChargeEvent
{
    use Dispatchable, SerializesModels;

    public Order $order;
    public ?OrderExtraCharges $charge; 
    public User $employee;
    public string $action; // collected | uncollectable

    public function __construct(
        Order $order,
        ?OrderExtraCharges $charge,
        User $employee,
        string $action
    ) {
        $this->order = $order;
        $this->charge = $charge;
        $this->employee = $employee;
        $this->action = $action;
    }
}
