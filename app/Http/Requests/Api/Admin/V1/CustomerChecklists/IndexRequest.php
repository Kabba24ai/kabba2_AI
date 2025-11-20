<?php

namespace App\Http\Requests\Api\Admin\V1\CustomerChecklists;

use App\Http\Requests\ApiBaseFormRequest;

class IndexRequest extends ApiBaseFormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'type' => 'required|string|in:delivery,return',
            'equipment_unique_id' => 'nullable|required_if:type,delivery|string|exists:equipment,unique_id',
            'order_product_unique_id' => 'nullable|required_if:type,return|string|exists:order_products,unique_id', // only when the return checklist is needed
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
            'type' => [
                'description' => 'The type of checklist to retrieve. Use "delivery" for delivery checklists and "return" for return checklists.',
                'example' => 'delivery',
                'type' => 'string',
            ],
            'equipment_unique_id' => [
                'description' => 'The unique ID of the equipment.',
                'example' => 'EQ123456',
                'type' => 'string',
            ],
            'order_product_unique_id' => [
                'description' => 'The unique ID of the order product. Required only when fetching return checklist questions.',
                'example' => 'ORD-PROD-123456',
                'type' => 'string',
            ],
        ];
    }
}
