<?php

namespace App\Http\Requests\Api\EmploymentApplication\V1\Dashboard\Positions;

use App\Http\Requests\ApiBaseFormRequest;

class StoreRequest extends ApiBaseFormRequest
{
    public function authorize(): bool
    {
        return true; // add policy later
    }

    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'display_order' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'title' => [
                'description' => 'Employment position title',
                'example' => 'Mechanic',
            ],
            'description' => [
                'description' => 'Optional description for the position',
                'example' => 'Responsible for repairing rental equipment',
            ],
            'display_order' => [
                'description' => 'Order for sorting positions',
                'example' => 1,
            ],
            'is_active' => [
                'description' => 'Whether the position is active',
                'example' => true,
            ],
        ];
    }
}
