<?php

namespace App\Http\Requests\Front\Checkout;

use App\Helpers\PurifyHelper;
use App\Rules\Email\EmailShouldNotContainSelectedSpecialCharactersRule;
use Illuminate\Foundation\Http\FormRequest;

class PostRequest extends FormRequest
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
        $this->merge(PurifyHelper::purify($this->all(), []));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        // Only run these for guests, not auth users with stored addresses
        $rules = [];

        if (!auth()->check()) {

            $rules = [
                // Billing fields
                'billingFirstName' => ['required', 'string', 'max:100'],
                'billingLastName' => ['required', 'string', 'max:100'],
                'billingCompany' => ['nullable', 'string', 'max:100'],
                'billingEmail' => ['required', 'email'],
                'billingPhone' => ['required', 'string', 'max:20'],
                'billingAddress' => ['required', 'string', 'max:200'],
                'billingState' => ['required', 'exists:states,id'],
                'billingCity' => ['required', 'string', 'max:100'],
                'billingZip' => ['required', 'string', 'max:20'],

                // Password (if custom password is enabled)
                'password' => ['nullable', 'string', 'min:8', 'confirmed'],
                // If the field is always present, use 'required_with:showPassword' or your own logic
            ];
        }

        // Delivery info
        $rules = array_merge($rules, [
            'sameAsBilling' => ['nullable'],
            'deliveryFirstName' => ['required_unless:sameAsBilling,Yes', 'string', 'max:100'],
            'deliveryLastName' => ['required_unless:sameAsBilling,Yes', 'string', 'max:100'],
            'deliveryEmail' => ['required_unless:sameAsBilling,Yes', 'email'],
            'deliveryPhone' => ['required_unless:sameAsBilling,Yes', 'string', 'max:20'],
            'deliveryAddress' => ['required_unless:sameAsBilling,Yes', 'string', 'max:200'],
            'deliveryState' => ['required_unless:sameAsBilling,Yes', 'exists:states,id'],
            'deliveryCity' => ['required_unless:sameAsBilling,Yes', 'string', 'max:100'],
            'deliveryZip' => ['required_unless:sameAsBilling,Yes', 'string', 'max:20'],
        ]);

        // Order notes (optional)
        $rules['orderNotes'] = ['nullable', 'string', 'max:500'];

        // Tax exempt (if checked, require admin code)
        $rules['taxExempt'] = ['nullable'];

        // Payment
        $rules['payment'] = ['required', 'in:COD,Account,Card'];

        // If credit is selected, validate card fields
        if ($this->input('payment') === 'Card') {
            $rules = array_merge($rules, [
                'firstName' => ['required', 'string', 'max:50'],
                'lastName' => ['required', 'string', 'max:50'],
                'cardNumber' => ['required', 'digits_between:13,19'],
                'expiry' => ['required', 'regex:/^(0[1-9]|1[0-2])\/?([0-9]{2})$/'],
                'cvc' => ['required', 'digits_between:3,4'],
            ]);
        }

        return $rules;
    }
}
