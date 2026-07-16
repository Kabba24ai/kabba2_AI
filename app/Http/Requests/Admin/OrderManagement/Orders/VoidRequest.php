<?php

namespace App\Http\Requests\Admin\OrderManagement\Orders;

use App\Http\Requests\Admin\OrderManagement\Orders\Concerns\VerifiesProcessedBy;
use App\Http\Requests\ApiBaseFormRequest;

class VoidRequest extends ApiBaseFormRequest
{
    use VerifiesProcessedBy;

    public function rules(): array
    {
        return array_merge([
            // Phase 3C: the specific payment being voided must be named
            // explicitly by the caller rather than re-derived server-side
            // via Order::lastPaidPayment — see VoidPaymentController, which
            // independently re-validates this id belongs to the order and
            // is actually eligible before trusting it.
            'order_payment_id' => ['required', 'integer'],
        ], $this->processedByRules());
    }
}
