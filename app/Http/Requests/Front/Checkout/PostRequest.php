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

            // 'showPassword' => ['nullable', 'in:Yes'],
            // 'password' => ['nullable', 'required_if:showPassword,Yes', 'string', 'min:8', 'confirmed'],
        ];

        // Delivery info
        $rules = array_merge($rules, [
            'sameAsBilling' => ['nullable', 'in:Yes,No'],
            // If not same as billing, require delivery fields
            'deliveryFirstName' => ['nullable', 'required_unless:sameAsBilling,Yes', 'string', 'max:100'],
            'deliveryLastName' => ['nullable', 'required_unless:sameAsBilling,Yes', 'string', 'max:100'],
            'deliveryEmail' => ['nullable', 'required_unless:sameAsBilling,Yes', 'email', 'max:200'],
            'deliveryPhone' => ['nullable', 'required_unless:sameAsBilling,Yes', 'string', 'max:20'],
            'deliveryAddress' => ['nullable', 'required_unless:sameAsBilling,Yes', 'string', 'max:200'],
            'deliveryState' => ['nullable', 'required_unless:sameAsBilling,Yes', 'exists:states,id'],
            'deliveryCity' => ['nullable', 'required_unless:sameAsBilling,Yes', 'string', 'max:100'],
            'deliveryZip' => ['nullable', 'required_unless:sameAsBilling,Yes', 'string', 'max:8'],
        ]);

        // Order notes (optional)
        $rules['orderNotes'] = ['nullable', 'string', 'max:500'];

        // Tax exempt (if checked, require admin code)
        $rules['taxExempt'] = ['nullable'];

        // cart
        $rules['cart'] = [
            'required',
            function ($attribute, $value, $fail) {
                $cart = json_decode($value, true);
                if (empty($cart) || !is_array($cart)) {
                    $fail('Your cart is empty. Please add products before placing an order.');
                }
            },
        ];

        // Payment
        $rules['payment'] = ['required', 'in:COD,Account,Card'];

        // If credit is selected, validate card fields
        if ($this->input('payment') === 'Card') {
            // If impersonated by admin: either customer_card OR card details

            if (session()->has('impersonated_by_admin')) {
                $rules['customer_card'] = ['nullable', 'required_without_all:firstName,lastName,cardNumber,expiry,cvc'];

                $rules = array_merge($rules, [
                    'firstName' => ['nullable', 'required_without:customer_card', 'string', 'max:50'],
                    'lastName' => ['nullable', 'required_without:customer_card', 'string', 'max:50'],
                    'cardNumber' => ['nullable', 'required_without:customer_card'],
                    'expiry' => ['nullable', 'required_without:customer_card', 'regex:/^(0[1-9]|1[0-2])\/?([0-9]{2})$/'],
                    'cvc' => ['nullable', 'required_without:customer_card', 'digits_between:3,4'],
                    'opaqueDataValue' => ['nullable', 'string', 'max:255'],
                    'opaqueDataDescriptor' => ['nullable', 'string', 'max:255'],
                ]);

            } else {
                $rules = array_merge($rules, [
                    'firstName' => ['required', 'string', 'max:50'],
                    'lastName' => ['required', 'string', 'max:50'],
                    'cardNumber' => ['required'],
                    'expiry' => ['required', 'regex:/^(0[1-9]|1[0-2])\/?([0-9]{2})$/'],
                    'cvc' => ['required', 'digits_between:3,4'],
                    'opaqueDataValue' => ['nullable', 'string', 'max:255'],
                    'opaqueDataDescriptor' => ['nullable', 'string', 'max:255'],
                ]);
            }
        }

        if (session()->has('impersonated_by_admin') || session()->has('master_passcode')) {
            $rules['employee_code'] = ['required', 'string', 'max:10', 'exists:users,employee_code'];
        }else{
            $rules['employee_code'] = ['nullable', 'string', 'max:10', 'exists:users,employee_code'];
        }
        return $rules;
    }

    public function withValidator($validator)
    {
        if (auth('customer')->check()) {
            $userEmail = auth('customer')->user()->email;

            $validator->after(function ($validator) use ($userEmail) {
                $billingEmail = $this->input('billingEmail');
                $deliveryEmail = $this->input('deliveryEmail');

                if ($billingEmail && $billingEmail !== $userEmail) {
                    $validator->errors()->add('billingEmail', 'The billing email must match your account email.');
                }
                if ($deliveryEmail && $deliveryEmail !== $userEmail) {
                    $validator->errors()->add('deliveryEmail', 'The delivery email must match your account email.');
                }
            });
        }
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
