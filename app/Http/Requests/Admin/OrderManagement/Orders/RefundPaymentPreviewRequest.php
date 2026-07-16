<?php

namespace App\Http\Requests\Admin\OrderManagement\Orders;

use App\Http\Requests\ApiBaseFormRequest;

/**
 * Phase 3D — Server-Authoritative Confirmation Preview.
 *
 * Deliberately a smaller rule set than RefundRequest: a preview never
 * charges a gateway or writes anything, so it has no need for
 * processed_by/reason/cheque_number/payment_note — only what
 * PaymentAllocationService needs to compute an authoritative split.
 */
class RefundPaymentPreviewRequest extends ApiBaseFormRequest
{
    public function rules(): array
    {
        return [
            'amount' => ['nullable', 'numeric', 'min:0'],

            'payment_type' => ['required', 'string', 'in:' . implode(',', array_column(\App\Enums\Customers\PaymentMethod::canonical(), 'value'))],

            'refund_calculation_type' => [
                'nullable', 'string',
                'in:' . implode(',', array_column(\App\Enums\Orders\RefundCalculationType::cases(), 'value')),
            ],

            'idempotency_token' => ['nullable', 'string', 'max:64'],

            'allocations' => ['nullable', 'array', 'min:1'],
            'allocations.*.original_order_payment_id' => ['required_with:allocations', 'integer'],
            'allocations.*.amount' => ['required_with:allocations', 'numeric', 'min:0.01'],
        ];
    }
}
