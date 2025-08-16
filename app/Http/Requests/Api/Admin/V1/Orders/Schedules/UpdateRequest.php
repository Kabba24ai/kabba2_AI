<?php

namespace App\Http\Requests\Api\Admin\V1\Orders\Schedules;

use App\Http\Requests\ApiBaseFormRequest;

class UpdateRequest extends ApiBaseFormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'order_product_unique_id' => 'required|string|max:255|exists:order_products,unique_id',
            'schedule_type'   => ['required', 'in:Delivery,Return'],
            'schedule_status' => ['required', 'in:Pending,Completed'],
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
            'order_product_unique_id' => [
                'description' => 'The unique ID of the order product.',
                'example' => 'ORD-SCH-URXP-LX5R',
                'type' => 'string',
            ],
            'schedule_type' => [
                'description' => 'The type of schedule, either "Delivery" or "Return".',
                'example' => 'Delivery',
                'type' => 'string',
            ],
            'schedule_status' => [
                'description' => 'The status of the schedule, either "Pending" or "Completed".',
                'example' => 'Pending',
                'type' => 'string',
            ],
        ];
    }
}
