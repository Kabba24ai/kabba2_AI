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

            'payment_type' => ['required', 'string', 'in:' . implode(',', array_column(\App\Enums\Customers\PaymentMethod::canonical(), 'value'))],

            // HOW the refund amount was calculated — separate from 'reason'
            // (why). Defaults to Standard (today's proportional behavior)
            // when omitted, so existing callers/tests are unaffected.
            'refund_calculation_type' => [
                'nullable', 'string',
                'in:' . implode(',', array_column(\App\Enums\Orders\RefundCalculationType::cases(), 'value')),
            ],

            'cheque_number' => ['required_if:payment_type,Cheque', 'nullable', 'string', 'max:255'],

            // "Other" carries no inherent meaning on its own — require a
            // description of what was actually used, same as Receive Payment.
            'payment_note' => ['required_if:payment_type,Other', 'nullable', 'string', 'max:255'],

            // 'reason' is now the structured reason dropdown (see
            // VerifiesProcessedBy) instead of the old free-text field.

            // Client-generated, one per modal-open — lets a Store Credit
            // refund recognize a duplicate double-click/network-retry of
            // the same submit rather than granting credit twice.
            'idempotency_token' => ['nullable', 'string', 'max:64'],
        ], $this->processedByRules());
    }

    public function messages(): array
    {
        return [
            'payment_note.required_if' => 'Describe the payment method used (e.g. Manufacturer Credit, Trade Credit).',
        ];
    }
}
