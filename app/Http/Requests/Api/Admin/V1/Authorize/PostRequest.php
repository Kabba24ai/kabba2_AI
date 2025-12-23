<?php

namespace App\Http\Requests\Api\Admin\V1\Authorize;

use App\Http\Requests\ApiBaseFormRequest;

class PostRequest extends ApiBaseFormRequest
{

    protected function prepareForValidation(): void
    {
        $cardNumber = preg_replace('/\s+/', '', (string) $this->input('card_number'));
        $phoneNumber = preg_replace('/[^0-9+]/', '', (string) $this->input('phone_number'));

        $this->merge([
            'card_number' => $cardNumber,
            'phone_number' => $phoneNumber,
        ]);
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:191'],
            'last_name' => ['required', 'string', 'max:191'],
            'phone_number' => ['nullable', 'string', 'max:32'],
            'email' => ['required', 'email', 'max:191'],
            'password' => ['required', 'string', 'min:8'],
            'business_name' => ['nullable', 'string', 'max:191'],
            'street_address' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:191'],
            'state' => ['nullable', 'string', 'max:64'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'card_name' => ['required', 'string', 'max:191'],
            'card_number' => ['required', 'digits_between:13,19'],
            'expiry_date' => ['required', 'regex:/^(0[1-9]|1[0-2])\/[0-9]{2}$/'],
            'cvc' => ['required', 'digits_between:3,4'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'schedule_datetime' => ['nullable', 'date_format:Y-m-d H:i:s'],
        ];
    }

    public function messages(): array
    {
        return [
            'expiry_date.regex' => 'The expiry date must be provided in the MM/YY format.',
        ];
    }

    /**
     * Get the body parameters for the request documentation.
     *
     * @return array
     */
    public function bodyParameters(): array
    {
        return [
            'first_name' => [
                'description' => 'The first name of the user.',
                'example' => 'John',
            ],
            'last_name' => [
                'description' => 'The last name of the user.',
                'example' => 'Doe',
            ],
            'phone_number' => [
                'description' => 'The phone number of the user.',
                'example' => '+1234567890',
            ],
            'email' => [
                'description' => 'The email address of the user.',
                'example' => 'john.doe@example.com',
            ],
            'password' => [
                'description' => 'The password for the user account.',
                'example' => 'securePassword123',
            ],
            'business_name' => [
                'description' => 'The business name associated with the user.',
                'example' => 'John\'s Supplies',
            ],
            'street_address' => [
                'description' => 'The street address of the user.',
                'example' => '123 Main St',
            ],
            'city' => [
                'description' => 'The city of the user.',
                'example' => 'Springfield',
            ],
            'state' => [
                'description' => 'The state of the user.',
                'example' => 'IL',
            ],
            'zip_code' => [
                'description' => 'The zip code of the user.',
                'example' => '62701',
            ],
            'card_name' => [
                'description' => 'The name on the credit card.',
                'example' => 'John Doe',
            ],
            'card_number' => [
                'description' => 'The credit card number.',
                'example' => '4111111111111111',
            ],
            'expiry_date' => [
                'description' => 'The expiry date of the credit card in MM/YY format.',
                'example' => '12/26',
            ],
            'cvc' => [
                'description' => 'The CVC code of the credit card.',
                'example' => '123',
            ],
            'amount' => [
                'description' => 'The amount to authorize.',
                'example' => '4.00',
            ],
            'schedule_datetime' => [
                'description' => 'The date and time to schedule the authorization (optional).',
                'example' => '2025-01-12 14:30:00',
            ],

        ];
    }
}
