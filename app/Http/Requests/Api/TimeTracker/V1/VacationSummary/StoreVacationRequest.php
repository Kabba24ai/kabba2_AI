<?php

namespace App\Http\Requests\Api\TimeTracker\V1\VacationSummary;

use App\Http\Requests\ApiBaseFormRequest;

class StoreVacationRequest extends ApiBaseFormRequest
{
    /**
     * Authorization
     */
    public function authorize(): bool
    {
        return true; // Add policy later if needed
    }

    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [
            'start_date' => ['required'],
            'vacation_request_hour_id' => [
                'required',
                'integer',
                'exists:vacation_request_hours,id',
            ],
            'notes' => ['nullable'],
        ];
    }

    /**
     * API docs body parameters
     */
    public function bodyParameters(): array
    {
        return [
            'start_date' => [
                'description' => 'Vacation start date',
                'example' => '2025-01-15',
            ],
            'vacation_request_hour_id' => [
                'description' => 'Selected vacation duration option',
                'example' => 4,
            ],
            'notes' => [
                'description' => 'Optional notes from employee',
                'example' => 'Family trip',
            ],
        ];
    }
}
