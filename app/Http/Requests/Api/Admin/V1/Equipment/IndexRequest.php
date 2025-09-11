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
            'type' => 'nullable|string|in:RentalReady,Checklist',
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
        ];
    }
}
