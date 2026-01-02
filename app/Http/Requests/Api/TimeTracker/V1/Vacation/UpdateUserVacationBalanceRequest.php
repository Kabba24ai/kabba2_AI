<?php

namespace App\Http\Requests\Api\TimeTracker\V1\Vacation;

use App\Http\Requests\ApiBaseFormRequest;

class UpdateUserVacationBalanceRequest extends ApiBaseFormRequest
{
    public function authorize(): bool
    {
        return true; // add policy later
    }

    public function rules(): array
    {
        return [
            'vacation_allotment_hour_id' => [
                'required',
                'integer',
                'exists:vacation_hours,id',
            ],
            'used_hours' => [
                'nullable',
                'numeric',
                'min:0',
            ],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'vacation_allotment_hour_id' => [
                'description' => 'Vacation allotment hour option ID',
                'example' => 3,
            ],
            'used_hours' => [
                'description' => 'Optional admin override of used hours',
                'example' => 8,
            ],
        ];
    }
}
