<?php

namespace App\Http\Requests\Api\Admin\V1\EquipmentRentalReady;

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
            'search'             => 'nullable|string|max:255',
            'category'           => 'nullable|string|max:255',
            'status'             => 'nullable|string|in:All,Available,Damaged,Maint. Hold,Rented,Service Due,Service OverDue',
            'store_id'           => 'nullable|integer|exists:stores,id',
            'currently_assigned' => 'nullable|in:0,1',
            'per_page'           => 'nullable|integer|min:1|max:100',
            'page'               => 'nullable|integer|min:1',
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
                'description' => 'Search by equipment name, model, serial number, or equipment ID.',
                'example' => 'Vermeer',
                'type' => 'string',
            ],
            'category' => [
                'description' => 'Filter by product category title. Omit or send "All Categories" for no filter.',
                'example' => 'Excavators',
                'type' => 'string',
            ],
            'status' => [
                'description' => 'Filter by rental-ready status.',
                'example' => 'Available',
                'type' => 'string',
                'enum' => ['All', 'Available', 'Damaged', 'Maint. Hold', 'Rented', 'Service Due', 'Service OverDue'],
            ],
            'store_id' => [
                'description' => 'Filter equipment by store location (stores.id).',
                'example' => 1,
                'type' => 'integer',
            ],
            'currently_assigned' => [
                'description' => 'Sort by assignment/revenue-protection priority (1 = on, 0 = off). Defaults to 1.',
                'example' => '1',
                'type' => 'string',
                'enum' => ['0', '1'],
            ],
            'per_page' => [
                'description' => 'Number of items per page. Defaults to 10 (matches the web rental-ready screen).',
                'example' => 10,
                'type' => 'integer',
            ],
            'page' => [
                'description' => 'Page number to fetch.',
                'example' => 1,
                'type' => 'integer',
            ],
        ];
    }
}
