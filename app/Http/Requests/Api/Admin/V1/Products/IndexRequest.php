<?php

namespace App\Http\Requests\Api\Admin\V1\Products;

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
        ];
    }
}
