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
        'responsible_person' => ['required', 'string', 'max:255'],
        'notes' => ['nullable'],
    ];
}

public function messages(): array
{
    return [
    ];
}

}
