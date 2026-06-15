<?php

namespace App\Http\Requests\Api\Admin\V1\Orders\Schedules;

use App\Http\Requests\ApiBaseFormRequest;

class DispatchRequest extends ApiBaseFormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'per_page' => 'nullable|integer|min:1', // Optional pagination parameter
            'page' => 'nullable|integer|min:1', // Optional page parameter
            'search' => 'nullable|string|max:255', // Optional search parameter
            'category_id' => 'nullable|integer|exists:product_categories,id',
            'schedule_type'   => ['required', 'in:All,Delivery,Return'],
            'date_filter'   => ['nullable', 'in:All,Tomorrow,Today,This Week,This Month'],
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
                'example' => '',
                'type' => 'string',
            ],
            'category_id' => [
                'description' => 'The ID of the category to filter orders.',
                'example' => 1,
                'type' => 'integer',
            ],
            'schedule_type' => [
                'description' => 'The type of schedule, either "All", "Delivery", or "Return".',
                'example' => 'Delivery',
                'type' => 'string',
            ],
            'date_filter' => [
                'description' => 'The date filter for the schedules, either "All", "Tomorrow", "Today", "This Week", or "This Month".',
                'example' => 'All',
                'type' => 'string',
            ],
        ];
    }
}
