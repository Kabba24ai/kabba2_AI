<?php

namespace App\Http\Requests\Admin\OrderManagement\Orders;

// Helpers

use App\Enums\Customers\PaymentMethod;
use App\Http\Requests\ApiBaseFormRequest;
use Illuminate\Validation\Rule;

class ReceivePaymentRequest extends ApiBaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'payment_type' => ['nullable', 'string', 'in:'.implode(',', array_keys(PaymentMethod::options()))],
            'cheque_number' => ['nullable', 'required_if:payment_type,Cheque', 'string', 'max:50'],
            'card_option' => ['nullable', 'required_if:payment_type,CreditCard', 'in:NewCard,CardOnFile'],
            'responsible_person' => ['required', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:500'],
            'customer_card' => ['nullable', 'required_if:card_option,CardOnFile', 'exists:customer_cards,unique_id'],
            'firstName' => ['nullable', 'required_if:card_option,NewCard', 'string', 'max:50'],
            'lastName' => ['nullable', 'required_if:card_option,NewCard', 'string', 'max:50'],
            'opaqueDataValue' => ['nullable', 'required_if:card_option,NewCard', 'string', 'max:255'],
            'opaqueDataDescriptor' => ['nullable', 'required_if:card_option,NewCard', 'string', 'max:255'],
            'payment_note' => ['nullable','max:255'],
            'card_number' => ['nullable','string','max:4'],
            'mm_yy' => ['nullable','string','max:6'],
        ];
    }
}
