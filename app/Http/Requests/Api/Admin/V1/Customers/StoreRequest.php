<?php

namespace App\Http\Requests\Api\Admin\V1\Customers;

use App\Http\Requests\ApiBaseFormRequest;

class StoreRequest extends ApiBaseFormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'required|email|max:255|unique:customers,email',
            'phone' => 'nullable|max:255|unique:customers,phone',
            'company_name' => 'nullable|string|max:255',
            'tags' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:255',
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
                'description' => 'The first name of the customer.',
                'example' => 'John',
                'type' => 'string',
            ],
            'last_name' => [
                'description' => 'The last name of the customer.',
                'example' => 'Doe',
                'type' => 'string',
            ],
            'email' => [
                'description' => 'The email address of the customer.',
                'example' => 'john.doe@example.com',
                'type' => 'string',
            ],
            'phone' => [
                'description' => 'The phone number of the customer.',
                'example' => '+1234567890',
                'type' => 'string',
            ],
            'company_name' => [
                'description' => 'The company name of the customer.',
                'example' => 'Acme Corp',
                'type' => 'string',
            ],
            'tags' => [
                'description' => 'Comma-separated tags associated with the customer.',
                'example' => '1,2',
                'type' => 'string',
            ],
            'note' => [
                'description' => 'Additional notes about the customer.',
                'example' => 'This customer prefers email communication.',
                'type' => 'string',
            ],
        ];
    }
}
