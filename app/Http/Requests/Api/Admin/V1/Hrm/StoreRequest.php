<?php

namespace App\Http\Requests\Api\Admin\V1\Hrm;

use App\Http\Requests\ApiBaseFormRequest;

class StoreRequest extends ApiBaseFormRequest
{
    /**
     * Validation Rules
     */
    public function rules(): array
    {
        return [

            // Basic Info
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],

            // Contact
            'email' => ['required', 'email', 'unique:users,email'],
            'mobile_phone' => ['nullable', 'string', 'max:20'],
            'phone_number' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * API Documentation Parameters
     */
    public function bodyParameters(): array
    {
        return [

            'first_name' => [
                'description' => 'User first name',
                'example' => 'John',
                'type' => 'string',
            ],

            'middle_name' => [
                'description' => 'User middle name',
                'example' => 'A',
                'type' => 'string',
            ],

            'last_name' => [
                'description' => 'User last name',
                'example' => 'Doe',
                'type' => 'string',
            ],

            'email' => [
                'description' => 'User email address',
                'example' => 'john@example.com',
                'type' => 'string',
            ],

            'mobile_phone' => [
                'description' => 'Mobile phone number',
                'example' => '(123) 456-7890',
                'type' => 'string',
            ],

            'phone_number' => [
                'description' => 'Secondary phone number',
                'example' => '(123) 456-7890',
                'type' => 'string',
            ],
        ];
    }
}