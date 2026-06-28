<?php

namespace App\Events\Admin\Billing;

use App\Models\Orders\BillingCharge;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BillingChargeCreatedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly BillingCharge $charge) {}
}
