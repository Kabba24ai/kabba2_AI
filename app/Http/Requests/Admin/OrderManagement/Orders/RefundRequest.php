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

            // Phase 3C — explicit source selection. Nullable/optional for
            // backward compatibility: an omitted `allocations` array is
            // only accepted by the controller when the order has exactly
            // one eligible original payment (single-source auto-derived),
            // so an older/simpler caller submitting just `amount` still
            // works for the common single-payment case. Required in
            // practice for any genuinely multi-payment order — enforced
            // by the controller via PaymentAllocationService::validateAllocationSet(),
            // not here, since "required" depends on order state this
            // request class has no access to.
            'allocations' => ['nullable', 'array', 'min:1'],
            'allocations.*.original_order_payment_id' => ['required_with:allocations', 'integer'],
            'allocations.*.amount' => ['required_with:allocations', 'numeric', 'min:0.01'],
        ], $this->processedByRules());
    }

    public function messages(): array
    {
        return [
            'payment_note.required_if' => 'Describe the payment method used (e.g. Manufacturer Credit, Trade Credit).',
        ];
    }
}
