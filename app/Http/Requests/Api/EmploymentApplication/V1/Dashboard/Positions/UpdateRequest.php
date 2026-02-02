<?php

namespace App\Http\Requests\Api\EmploymentApplication\V1\Dashboard\Positions;

use App\Http\Requests\ApiBaseFormRequest;

class UpdateRequest extends ApiBaseFormRequest
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
                'description' => 'Optional position description',
                'example' => 'Repairs and maintains equipment',
            ],
            'is_active' => [
                'description' => 'Whether the position is active',
                'example' => true,
            ],
        ];
    }
}
