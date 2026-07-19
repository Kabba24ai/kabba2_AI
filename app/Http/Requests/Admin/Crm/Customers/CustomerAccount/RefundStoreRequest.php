<?php
namespace App\Http\Requests\Admin\Crm\Customers\CustomerAccount;

use Illuminate\Foundation\Http\FormRequest;
use App\Helpers\PurifyHelper;
use Illuminate\Validation\Rule;

class RefundStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(PurifyHelper::purify($this->all(), ['content']));
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required'],
            'responsible_person' => ['required'],
            'notes' => ['nullable', 'string'],
            // Billing Charge Refund Allocation — Safe Linked Refunds:
            // optional link to the originating charge. Free-form refunds
            // (goodwill credits, refunds unrelated to any specific charge)
            // remain fully supported by simply omitting this field —
            // existing behavior is unchanged when it's absent. Existence is
            // the only thing validated here — ownership/eligibility/
            // remaining-balance are all re-derived server-side in
            // BillingChargeRefundService, never trusted from the client.
            'billing_charge_unique_id' => ['nullable', 'string', 'exists:billing_charges,unique_id'],

            // Client-generated, one per distinct submission attempt — same
            // convention as RefundPaymentController/PaymentStoreController's
            // idempotency_token. Only meaningful (locked/checked) when a
            // billing_charge_unique_id is also present — free-form refunds
            // have no allocation row to duplicate-guard.
            'idempotency_token' => ['nullable', 'string', 'max:64'],
        ];
    }

    public function messages(): array
    {
        return [
           
        ];
    }
}