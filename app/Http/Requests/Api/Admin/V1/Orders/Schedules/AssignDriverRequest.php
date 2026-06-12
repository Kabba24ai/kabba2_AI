<?php

namespace App\Http\Requests\Api\Admin\V1\Orders\Schedules;

// Helpers
use App\Http\Requests\ApiBaseFormRequest;

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
            'user_unique_id' => ['required', 'exists:users,unique_id'],
            'order_product_unique_id' => ['required', 'exists:order_products,unique_id'],
            'schedule_type' => ['required', 'in:Delivery,Pickup'],
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
            'user_unique_id' => [
                'description' => 'The unique ID of the user to be assigned as driver.',
                'example' => '',
                'type' => 'string',
            ],
            'order_product_unique_id' => [
                'description' => 'The unique ID of the order product for which the driver is being assigned.',
                'example' => '',
                'type' => 'string',
            ],
            'schedule_type' => [
                'description' => 'The type of schedule, either "Delivery" or "Pickup".',
                'example' => 'Delivery',
                'type' => 'string',
            ],
        ];
    }
}
