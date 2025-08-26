<?php

namespace App\Http\Requests\Admin\OrderManagement\Orders;

// Helpers
use App\Http\Requests\ApiBaseFormRequest;

class ChargeCreditCardRequest extends ApiBaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'customer_card' => ['nullable', 'required_without_all:firstName,lastName,cardNumber,expiry,cvc', 'exists:customer_cards,unique_id'],
            'firstName' => ['nullable', 'required_without:customer_card', 'string', 'max:50'],
            'lastName' => ['nullable', 'required_without:customer_card', 'string', 'max:50'],
            'opaqueDataValue' => ['nullable', 'string', 'max:255'],
            'opaqueDataDescriptor' => ['nullable', 'string', 'max:255'],
        ];
    }
}
