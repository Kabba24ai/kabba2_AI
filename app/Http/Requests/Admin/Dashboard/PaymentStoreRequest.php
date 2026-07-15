<?php

namespace App\Http\Requests\Admin\Dashboard;
use Illuminate\Validation\Rule;
use App\Helpers\PurifyHelper;
use Illuminate\Foundation\Http\FormRequest;


class PaymentStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge(PurifyHelper::purify($this->all(),['content']));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
   public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'order_id' => ['nullable'],
            'order_product_id' => ['nullable'],

            'amount' => ['required'],
            'payment_type' => ['required', Rule::in(array_column(\App\Enums\Customers\PaymentMethod::canonical(), 'value'))],

            'responsible_person' => ['required'],
            // "Other" carries no inherent meaning on its own — require a
            // description of what was actually used, same as Receive Payment.
            'notes' => ['nullable', 'required_if:payment_type,Other'],

            // Cheque
            'cheque_number' => [ 'nullable', 'max:50'],

            // Card option (add this field in the form as you already have select name="card_option")
            'card_option' => ['nullable', Rule::in(['NewCard', 'CardOnFile'])],

            // Card on file
            'existing_card_id' => [
                
                'nullable',
                'exists:customer_cards,unique_id',
            ],

            // New card token (Authorize.Net Accept.js)
            'opaqueDataValue' => ['nullable'],
            'opaqueDataDescriptor' => [ 'nullable'],

            // Optional UI fields (not required if you tokenize)
            'firstName' => ['nullable', 'string', 'max:50'],
            'lastName' => ['nullable', 'string', 'max:50'],
            'cardNumber' => ['nullable', 'string'],
            'expiry' => ['nullable', 'regex:/^(0[1-9]|1[0-2])\/([0-9]{2})$/'],
            'cvc' => ['nullable', 'digits_between:3,4'],

            // If you really require this in controller:
            'type' => ['nullable'],

            'source'               => ['nullable', 'string', Rule::in(['order', 'crm'])],
            'customer_account_id'  => ['nullable', 'string', 'exists:customer_accounts,unique_id'],
            'billing_charge_unique_id' => ['nullable', 'string', 'exists:billing_charges,unique_id'],
        ];
    }


public function messages(): array
{
    return [
        'notes.required_if' => 'Describe the payment method used (e.g. Manufacturer Credit, Trade Credit).',
    ];
}

}
