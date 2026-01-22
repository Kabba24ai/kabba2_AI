<?php

namespace App\Events\Admin\Receipts;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Customers\Receipt;

class ReceiptEmailEvent
{
    use Dispatchable, SerializesModels;

    public $receipt;

    public function __construct(Receipt $receipt)
    {
        $this->receipt = $receipt;
    }
}
