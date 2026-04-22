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

            'bonus_vacation_hours' => ['nullable', 'numeric', 'min:0'],

            'bonus_vacation_hours_start_date' => [
                'nullable',
                'date',
                'required_with:bonus_hours'
            ],

            'bonus_vacation_hours_end_date' => [
                'nullable',
                'date',
                'after_or_equal:bonus_vacation_hours_start_date'
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

            'bonus_vacation_hours' => [
                'description' => 'Extra bonus hours assigned',
                'example' => 10,
                'type' => 'number',
            ],

            'bonus_vacation_hours_start_date' => [
                'description' => 'Bonus validity start date',
                'example' => '2026-01-01',
                'type' => 'string',
            ],

            'bonus_vacation_hours_end_date' => [
                'description' => 'Bonus validity end date',
                'example' => '2026-12-31',
                'type' => 'string',
            ],
        ];
    }
}
