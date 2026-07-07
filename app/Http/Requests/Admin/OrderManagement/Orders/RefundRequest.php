<?php

namespace App\Http\Requests\Admin\OrderManagement\Orders;

// Helpers
use App\Http\Requests\Admin\OrderManagement\Orders\Concerns\VerifiesProcessedBy;
use App\Http\Requests\ApiBaseFormRequest;

class RefundRequest extends ApiBaseFormRequest
{
    use VerifiesProcessedBy;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return array_merge([
            'amount' => ['required', 'numeric', 'min:0.01'],

            'payment_type' => ['required', 'string', 'in:' . implode(',', array_column(\App\Enums\Customers\PaymentMethod::cases(), 'value'))],

            'cheque_number' => ['required_if:payment_type,Cheque', 'nullable', 'string', 'max:255'],

            // 'reason' is now the structured reason dropdown (see
            // VerifiesProcessedBy) instead of the old free-text field.
        ], $this->processedByRules());
    }
}
