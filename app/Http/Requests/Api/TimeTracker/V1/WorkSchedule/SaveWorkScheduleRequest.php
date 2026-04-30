<?php

namespace App\Http\Requests\Api\TimeTracker\V1\WorkSchedule;

use App\Http\Requests\ApiBaseFormRequest;

class SaveWorkScheduleRequest extends ApiBaseFormRequest
{
    public function authorize(): bool
    {
        return true; // add policy later
    }

    public function rules(): array
    {
        return [
            'schedules' => ['required', 'array'],

            'schedules.*.employee_id' => ['required', 'integer', 'exists:users,id'],

            'schedules.*.date' => ['required', 'date'],

            'schedules.*.start_time' => ['nullable', 'date_format:H:i'],

            'schedules.*.end_time' => ['nullable', 'date_format:H:i'],

            'schedules.*.store_id' => ['nullable', 'integer', 'exists:stores,id'],

            'schedules.*.is_scheduled' => ['required', 'boolean'],

            'schedules.*.hours' => ['required', 'numeric', 'min:0'],

            'schedules.*.notes' => ['nullable', 'string'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'schedules' => [
                'description' => 'Array of work schedules for employees',
                'example' => [
                    [
                        'employee_id' => 1,
                        'date' => '2026-03-10',
                        'start_time' => '08:00',
                        'end_time' => '17:00',
                        'store_id' => 3,
                        'is_scheduled' => true,
                        'hours' => 8,
                    ],
                ],
            ],
        ];
    }
}
