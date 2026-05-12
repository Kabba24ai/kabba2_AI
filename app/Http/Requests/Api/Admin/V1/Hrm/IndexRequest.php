<?php

namespace App\Http\Requests\Api\Admin\V1\Hrm;

use App\Http\Requests\ApiBaseFormRequest;

class IndexRequest extends ApiBaseFormRequest
{
    /**
     * Validation Rules
     */
    public function rules(): array
    {
        return [

            'per_page' => 'nullable|integer|min:1',

            'page' => 'nullable|integer|min:1',

        ];
    }

    /**
     * API Documentation Parameters
     */
    public function bodyParameters(): array
    {
        return [

            'per_page' => [
                'description' => 'The number of HRM users to display per page.',
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