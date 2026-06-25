<?php

namespace App\Http\Requests\Api\Admin\V1\Orders\Schedules;

use App\Http\Requests\ApiBaseFormRequest;

class DispatchDriverRequest extends ApiBaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'search'        => 'nullable|string|max:255',
            'category_id'   => 'nullable|integer|exists:product_categories,id',
            'driver_id'     => 'nullable|integer|exists:users,id',
            'schedule_type' => ['required', 'in:All,Delivery,Return'],
            'date_filter'   => ['nullable', 'in:All,Today,Tomorrow,This Week,This Month'],
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
            'search' => [
                'description' => 'The search term to filter orders upon the customer name. Must not be greater than 255 characters.',
                'example'     => '',
                'type'        => 'string',
            ],
            'category_id' => [
                'description' => 'The ID of the category to filter orders. The id of an existing record in the product_categories table.',
                'example'     => null,
                'type'        => 'integer',
            ],
            'driver_id' => [
                'description' => 'The ID of the driver to filter orders. The id of an existing record in the users table.',
                'example'     => null,
                'type'        => 'integer',
            ],
            'schedule_type' => [
                'description' => 'The type of schedule, either "All", "Delivery", or "Return".',
                'example'     => 'All',
                'type'        => 'string',
            ],
            'date_filter' => [
                'description' => 'Date scope for jobs: All, Today, Tomorrow, This Week, This Month.',
                'example'     => 'Today',
                'type'        => 'string',
            ],
        ];
    }
}
