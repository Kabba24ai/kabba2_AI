<?php

namespace App\Http\Requests\Api\Admin\V1\RentalReadyChecklists;

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
            'order_product_unique_id' => 'required|exists:order_products,unique_id',
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
                'example' => 'ORD-SCH-OTQ0-OBSO',
                'type' => 'string',
            ],
        ];
    }
}
