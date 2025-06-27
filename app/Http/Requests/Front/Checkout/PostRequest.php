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
                'billingEmail' => ['required', 'email', 'max:200'],
                'billingPhone' => ['required', 'string', 'max:20'],
                'billingAddress' => ['required', 'string', 'max:200'],
                'billingState' => ['required', 'exists:states,id'],
                'billingCity' => ['required', 'string', 'max:100'],
                'billingZip' => ['required', 'string', 'max:8'],

                'showPassword' => ['nullable', 'in:Yes'],
                'password' => ['nullable','required_if:showPassword,Yes', 'string', 'min:8', 'confirmed'],
            ];
        }

        // Delivery info
        $rules = array_merge($rules, [
            'sameAsBilling' => ['nullable', 'in:Yes'],
            'deliveryFirstName' => ['required_unless:sameAsBilling,Yes', 'string', 'max:100'],
            'deliveryLastName' => ['required_unless:sameAsBilling,Yes', 'string', 'max:100'],
            'deliveryEmail' => ['required_unless:sameAsBilling,Yes', 'email', 'max:200'],
            'deliveryPhone' => ['required_unless:sameAsBilling,Yes', 'string', 'max:20'],
            'deliveryAddress' => ['required_unless:sameAsBilling,Yes', 'string', 'max:200'],
            'deliveryState' => ['required_unless:sameAsBilling,Yes', 'exists:states,id'],
            'deliveryCity' => ['required_unless:sameAsBilling,Yes', 'string', 'max:100'],
            'deliveryZip' => ['required_unless:sameAsBilling,Yes', 'string', 'max:8'],
        ]);

        // Order notes (optional)
        $rules['orderNotes'] = ['nullable', 'string', 'max:500'];

        // Tax exempt (if checked, require admin code)
        $rules['taxExempt'] = ['nullable'];

        // cart
        $rules['cart'] = ['nullable'];

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

    public function messages()
    {
        return [
            'password.required_if' => 'The password field is required when you choose to set a password.',
            // Delivery fields
            'deliveryFirstName.required_unless' => 'The delivery first name is required unless you use the billing address.',
            'deliveryLastName.required_unless' => 'The delivery last name is required unless you use the billing address.',
            'deliveryEmail.required_unless' => 'The delivery email is required unless you use the billing address.',
            'deliveryPhone.required_unless' => 'The delivery phone is required unless you use the billing address.',
            'deliveryAddress.required_unless' => 'The delivery address is required unless you use the billing address.',
            'deliveryState.required_unless' => 'The delivery state is required unless you use the billing address.',
            'deliveryCity.required_unless' => 'The delivery city is required unless you use the billing address.',
            'deliveryZip.required_unless' => 'The delivery ZIP code is required unless you use the billing address.',
        ];
    }
}
