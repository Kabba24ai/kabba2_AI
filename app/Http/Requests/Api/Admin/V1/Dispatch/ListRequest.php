<?php

namespace App\Http\Requests\Api\Admin\V1\Dispatch;

use App\Http\Requests\ApiBaseFormRequest;

class ListRequest extends ApiBaseFormRequest
{
    public function rules(): array
    {
        return [
            'order_number'          => ['nullable', 'string', 'max:100'],
            'customer_name'         => ['nullable', 'string', 'max:100'],
            'customer_company_name' => ['nullable', 'string', 'max:100'],
            'customer_phone'        => ['nullable', 'string', 'max:30'],
            'category'              => ['nullable', 'integer', 'exists:product_categories,id'],
            'payment_method'        => ['nullable', 'string', 'max:50'],
            'payment_status'        => ['nullable', 'string', 'max:50'],
            'schedule_type'         => ['nullable', 'array'],
            'schedule_type.*'       => ['in:Delivery,Return'],
            'show_all'              => ['nullable', 'boolean'],
            'store_location'        => ['nullable', 'array'],
            'store_location.*'      => ['integer', 'exists:stores,id'],
            'driver_id'             => ['nullable', 'integer', 'exists:users,id'],
            'date_filter'           => ['nullable', 'in:today,week,month'],
            'view_mode'             => ['nullable', 'in:combined,split'],
            'per_page'              => ['nullable'],
            'page'                  => ['nullable', 'integer', 'min:1'],
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
            'order_number' => [
                'description' => 'Filter by order number or reference order number.',
                'example'     => '',
                'type'        => 'string',
            ],
            'customer_name' => [
                'description' => 'Filter by customer first or last name (shipping address).',
                'example'     => '',
                'type'        => 'string',
            ],
            'customer_company_name' => [
                'description' => 'Filter by customer company name on the order.',
                'example'     => '',
                'type'        => 'string',
            ],
            'customer_phone' => [
                'description' => 'Filter by customer phone number (shipping address).',
                'example'     => '',
                'type'        => 'string',
            ],
            'category' => [
                'description' => 'Filter by product category ID.',
                'example'     => '',
                'type'        => 'integer',
            ],
            'payment_method' => [
                'description' => 'Filter by payment method (e.g. Credit Card). Pass "All Methods" or omit to skip.',
                'example'     => '',
                'type'        => 'string',
            ],
            'payment_status' => [
                'description' => 'Filter by payment status (e.g. Paid). Pass "All Status" or omit to skip.',
                'example'     => '',
                'type'        => 'string',
            ],
            'schedule_type' => [
                'description' => 'Filter by schedule type. Accepts an array of "Delivery", "Return", or both.',
                'example'     => ['Delivery'],
                'type'        => 'array',
            ],
            'show_all' => [
                'description' => 'When false (default) only pending/actionable rows are returned. Pass true to include completed rows.',
                'example'     => false,
                'type'        => 'boolean',
            ],
            'store_location' => [
                'description' => 'Filter by store ID(s). Matches delivery_store_id or pickup_store_id.',
                'example'     => [],
                'type'        => 'array',
            ],
            'driver_id' => [
                'description' => 'Filter by assigned driver user ID (matches delivery or return driver).',
                'example'     => '',
                'type'        => 'integer',
            ],
            'date_filter' => [
                'description' => 'Date range filter. Allowed values: today, week, month.',
                'example'     => '',
                'type'        => 'string',
            ],
            'view_mode' => [
                'description' => 'Response layout. "combined" returns a paginated single list; "split" returns separate deliveries and returns arrays.',
                'example'     => 'combined',
                'type'        => 'string',
            ],
            'per_page' => [
                'description' => 'Number of items per page (combined view only). Pass "all" to return every record.',
                'example'     => 30,
                'type'        => 'integer',
            ],
            'page' => [
                'description' => 'Page number for pagination (combined view only).',
                'example'     => 1,
                'type'        => 'integer',
            ],
        ];
    }
}
