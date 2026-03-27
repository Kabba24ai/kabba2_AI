<?php

namespace App\Http\Requests\Api\TimeTracker\V1\WorkSchedule;

use App\Http\Requests\ApiBaseFormRequest;

class UpdateWorkScheduleRequest extends ApiBaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => [
                'required',
                'integer',
                'exists:users,id'
            ],

            'date' => [
                'required',
                'date'
            ],

          'start_time' => ['nullable'],
'end_time'   => ['nullable'],

            'store_id' => [
                'nullable',
                'integer',
                'exists:stores,id'
            ],

            'is_scheduled' => [
                'required',
                'boolean'
            ],

            'hours' => [
                'required',
                'numeric',
                'min:0'
            ],

            'notes' => [
                'nullable',
                'string'
            ],
        ];
    }
}