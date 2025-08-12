<?php

namespace App\Http\Requests\Api\Admin\V1\Orders;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
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
            'status' => 'required|string|in:All,' . implode(',', OrderPaymentStatus::getValues()),
            'per_page' => 'nullable|integer|min:1', // Optional pagination parameter
            'page' => 'nullable|integer|min:1', // Optional page parameter
            'search' => 'nullable|string|max:255', // Optional search parameter
            'category_id' => 'nullable|integer|exists:product_categories,id',
            'payment_method' => 'nullable|string|in:All,'. implode(',', OrderPaymentMethod::getValues()), // Enum validation for media type

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
                'example' => 'All',
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
            'search' => [
                'description' => 'The search term to filter orders upon the customer name.',
                'example' => 'Raj',
                'type' => 'string',
            ],
            'category_id' => [
                'description' => 'The ID of the category to filter orders.',
                'example' => 1,
                'type' => 'integer',
            ],
            'payment_method' => [
                'description' => 'The payment method to filter orders.',
                'example' => 'All',
                'type' => 'string',
            ],
            'payment_status' => [
                'description' => 'The payment status to filter orders.',
                'example' => 'Pending',
                'type' => 'string',
            ],
        ];
    }
}
