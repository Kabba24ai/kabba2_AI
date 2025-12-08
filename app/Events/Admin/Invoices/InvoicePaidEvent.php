<?php

namespace App\Events\Admin\Invoices;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Customers\Invoice;
use App\Models\Customers\Customer;
use App\Models\Customers\Receipt;
use App\Models\Orders\Order;

class InvoicePaidEvent
{
    use Dispatchable, SerializesModels;

     public $order;
    public $customer;
    public $payment;
    public $employee;


      public function __construct(Order $order, $customer, $payment, $employee = null)
    {
        $this->order = $order;
        $this->customer = $customer;
        $this->payment = $payment;
        $this->employee = $employee;
    }
}
