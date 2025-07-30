<?php

namespace App\Http\Requests\Api\Admin\V1\Orders;

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
            'status' => 'required|string|in:All,Pending,Paid,Account,Partial Refund,Refunded,Failed', // Required status parameter
            'per_page' => 'nullable|integer|min:1', // Optional pagination parameter
            'page' => 'nullable|integer|min:1', // Optional page parameter
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
            'status' => [
                'description' => 'The status to filter orders. Can be either "All", "Pending", "Paid", "Account", "Partial Refund", "Refunded", or "Failed".',
                'example' => 'Pending',
                'type' => 'string',
            ],
            'per_page' => [
                'description' => 'The number of items to display per page.',
                'example' => 10,
                'type' => 'integer',
            ],
            'page' => [
                'description' => 'The page number for pagination.',
                'example' => 1,
                'type' => 'integer',
            ],
        ];
    }
}
