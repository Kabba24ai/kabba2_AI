<?php

namespace App\Http\Requests\Api\TimeTracker\V1\Users;

use App\Http\Requests\ApiBaseFormRequest;

class UpdateUserVacationRequest extends ApiBaseFormRequest
{
    /**
     * Authorization
     */
    public function authorize(): bool
    {
        return true; // add policy later if needed
    }

    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [
            'vacation_eligible' => ['required', 'boolean'],

            'vacation_allotment_hour_id' => [
                'nullable',
                'exists:vacation_hours,id',
                'required_if:vacation_eligible,true',
            ],

            'vacation_start_day_id' => [
                'nullable',
                'exists:vacation_days,id',
                'required_if:vacation_eligible,true',
            ],
        ];
    }

    /**
     * Body parameters for API docs (optional)
     */
    public function bodyParameters(): array
    {
        return [
            'vacation_eligible' => [
                'description' => 'Whether the user is eligible for vacation',
                'example' => true,
                'type' => 'boolean',
            ],
            'vacation_allotment_hour_id' => [
                'description' => 'Vacation hours policy ID',
                'example' => 5,
                'type' => 'integer',
            ],
            'vacation_start_day_id' => [
                'description' => 'Vacation start day policy ID',
                'example' => 3,
                'type' => 'integer',
            ],
        ];
    }
}
