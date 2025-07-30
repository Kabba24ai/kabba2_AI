<?php

namespace App\Http\Requests\Api\Admin\V1\Orders;

use App\Http\Requests\ApiBaseFormRequest;

class ShowRequest extends ApiBaseFormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'unique_id' => 'required|string|exists:orders,unique_id', // Required unique ID parameter
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
            'unique_id' => [
                'description' => 'The unique ID of the order.',
                'example' => 'ORD123456',
                'type' => 'string',
            ],
        ];
    }
}
