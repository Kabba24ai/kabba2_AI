<?php

namespace App\Http\Requests\Api\Admin\V1\Orders\Schedules;

// Helpers
use App\Http\Requests\ApiBaseFormRequest;
use Illuminate\Validation\Validator;

class AssignDriverRequest extends ApiBaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'user_delivery_id' => ['nullable', 'exists:users,id'],
            'user_pickup_id' => ['nullable', 'exists:users,id'],
            'order_product_unique_id' => ['required', 'exists:order_products,unique_id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if (empty($this->user_delivery_id) && empty($this->user_pickup_id)) {
                $validator->errors()->add('user_delivery_id', 'At least one of user_delivery_id or user_pickup_id is required.');
            }
        });
    }

    /**
     * Get the body parameters for the request documentation.
     *
     * @return array
     */
    public function bodyParameters(): array
    {
        return [
            'user_delivery_id' => [
                'description' => 'The ID of the user responsible for delivery.',
                'example' => 1,
            ],
            'user_pickup_id' => [
                'description' => 'The ID of the user responsible for pickup.',
                'example' => 2,
            ],
            'order_product_unique_id' => [
                'description' => 'The unique ID of the order product to be assigned.',
                'example' => 'abc123def456',
            ],
        ];
    }
}
