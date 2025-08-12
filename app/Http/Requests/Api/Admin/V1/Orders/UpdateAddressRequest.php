<?php

namespace App\Http\Requests\Api\Admin\V1\Orders;

use App\Enums\Orders\OrderMediaType;
use App\Http\Requests\ApiBaseFormRequest;

class UpdateAddressRequest extends ApiBaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'order_unique_id' => 'required|string|exists:orders,unique_id',
            'type' => 'required|in:Billing,Shipping',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'state_id' => 'required|integer|exists:states,id',
            'state' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'zip_code' => 'required|string|max:20',

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
            'order_unique_id' => [
                'description' => 'The unique ID of the order.',
                'example' => 'ORD123456',
                'type' => 'string',
            ],
            'first_name' => [
                'description' => 'The first name of the recipient.',
                'example' => 'John',
                'type' => 'string',
            ],
            'last_name' => [
                'description' => 'The last name of the recipient.',
                'example' => 'Doe',
                'type' => 'string',
            ],
            'phone' => [
                'description' => 'The phone number of the recipient.',
                'example' => '+1234567890',
                'type' => 'string',
            ],
            'address' => [
                'description' => 'The address of the recipient.',
                'example' => '123 Main St',
                'type' => 'string',
            ],
            'state_id' => [
                'description' => 'The ID of the state.',
                'example' => 1,
                'type' => 'integer',
            ],
            'state' => [
                'description' => 'The name of the state.',
                'example' => 'California',
                'type' => 'string',
            ],
            'city' => [
                'description' => 'The name of the city.',
                'example' => 'Los Angeles',
                'type' => 'string',
            ],
            'zip_code' => [
                'description' => 'The ZIP code.',
                'example' => '90001',
                'type' => 'string',
            ],
            'type' => [
                'description' => 'The type of address.',
                'example' => 'Billing',
                'type' => 'string',
            ],

        ];
    }
}
