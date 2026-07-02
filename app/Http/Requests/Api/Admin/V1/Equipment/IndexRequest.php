<?php

namespace App\Http\Requests\Api\Admin\V1\Equipment;

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
            'type'               => 'nullable|string|in:RentalReady,Checklist',
            'search'             => 'nullable|string|max:255',
            'search_by_id'       => 'nullable|string|max:255',
            'currently_assigned' => 'nullable|in:0,1',
            'store_id'           => 'nullable|integer|exists:stores,id',
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
            'type' => [
                'description' => 'The type to filter equipment.',
                'example' => 'RentalReady',
                'type' => 'string',
                'enum' => ['RentalReady', 'Checklist'],
            ],
            'search' => [
                'description' => 'Search by equipment name, model, or serial number.',
                'example' => 'Vermeer',
                'type' => 'string',
            ],
            'search_by_id' => [
                'description' => 'Search by equipment ID (e.g. VER-WC-2).',
                'example' => 'VER-WC-2',
                'type' => 'string',
            ],
            'currently_assigned' => [
                'description' => 'Sort by assignment priority (1 = on, 0 = off). Defaults to 1.',
                'example' => '1',
                'type' => 'string',
                'enum' => ['0', '1'],
            ],
            'store_id' => [
                'description' => 'Filter equipment by store location (stores.id).',
                'example' => 1,
                'type' => 'integer',
            ],
        ];
    }
}
