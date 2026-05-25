<?php

namespace App\Http\Requests\Api\Admin\V1\Customers;

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
            'per_page' => 'nullable|integer|min:1', // Optional pagination parameter
            'page' => 'nullable|integer|min:1', // Optional page parameter
            'search_name' => 'nullable|string|max:255',
            'search_company_name' => 'nullable|string|max:255',
            'search_phone' => 'nullable|string|max:255',
            'tax_status' => 'nullable|string|max:255',
            'tag' => 'nullable|string|max:255',
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
            'search_name' => [
                'description' => 'Filter customers by full name.',
                'example' => 'John Doe',
                'type' => 'string',
            ],
            'search_company_name' => [
                'description' => 'Filter customers by company name.',
                'example' => 'Acme Corp',
                'type' => 'string',
            ],
            'search_phone' => [
                'description' => 'Filter customers by phone number.',
                'example' => '(555) 123-4567',
                'type' => 'string',
            ],
            'tax_status' => [
                'description' => 'Filter customers by tax status.',
                'example' => 'Exempt',
                'type' => 'string',
            ],
            'tag' => [
                'description' => 'Filter customers by tag id or stored tag value.',
                'example' => '3',
                'type' => 'string',
            ],
        ];
    }
}
