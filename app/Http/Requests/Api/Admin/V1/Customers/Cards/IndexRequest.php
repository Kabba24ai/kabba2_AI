<?php

namespace App\Http\Requests\Api\Admin\V1\Customers\Cards;

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
            'customer_unique_id' => 'required|exists:customers,unique_id',
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
            'customer_unique_id' => [
                'description' => 'The unique identifier of the customer whose cards are being requested.',
                'example' => 'CUST-123456',
            ],
        ];
    }
}
