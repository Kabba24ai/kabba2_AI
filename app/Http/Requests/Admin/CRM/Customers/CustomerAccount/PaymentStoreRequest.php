<?php

namespace App\Http\Requests\Admin\Crm\Customers\CustomerAccount;
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
        'customer_id' => ['required'],
        'amount' => ['required'],
        'payment_type' => ['required', Rule::in(['Cash', 'Cheque', 'CreditCard', 'BankTransfer', 'Other'])],
        'responsible_person' => ['required'],
        'notes' => ['nullable'],
        'existing_card_id' => ['nullable', 'exists:customer_cards,unique_id'], 
        
        'opaqueDataValue' => ['nullable', 'string'],
        'opaqueDataDescriptor' => ['nullable', 'string'],

        'firstName' => ['nullable', 'string', 'max:50'],
        'lastName' => ['nullable', 'string', 'max:50'],
        'cardNumber' => ['nullable'],
        'expiry' => ['nullable', 'regex:/^(0[1-9]|1[0-2])\/?([0-9]{2})$/'],
        'cvc' => ['nullable', 'digits_between:3,4'],

    ];
}

public function messages(): array
{
    return [
    ];
}

}
