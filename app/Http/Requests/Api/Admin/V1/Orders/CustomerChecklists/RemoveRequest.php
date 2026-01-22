<?php

namespace App\Http\Requests\Api\Admin\V1\Orders\CustomerChecklists;

use App\Http\Requests\ApiBaseFormRequest;

class RemoveRequest extends ApiBaseFormRequest
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
                'description' => 'The unique identifier of the order.',
                'example' => 'ORD-UH4U-D19S',
            ],
        ];
    }
}
