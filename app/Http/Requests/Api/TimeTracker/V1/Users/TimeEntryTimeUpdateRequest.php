<?php

namespace App\Http\Requests\Api\TimeTracker\V1\Users;

use App\Http\Requests\ApiBaseFormRequest;

class TimeEntryTimeUpdateRequest extends ApiBaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'entry_id' => [
                'required',
                'integer',
                'exists:time_entries,id',
            ],

            'break_id' => [
                'nullable',
                'integer',
                'exists:time_entry_breaks,id',
            ],

            'entry_type' => [
                'required',
            ],

            'new_time' => [
                'required',
            ],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'entry_id' => [
                'description' => 'Main time entry ID',
                'example' => 133,
                'type' => 'integer',
            ],
            'break_id' => [
                'description' => 'Break ID (if updating lunch/unpaid break)',
                'example' => 81,
                'type' => 'integer',
            ],
            'entry_type' => [
                'description' => 'Type of entry being updated',
                'example' => 'clock_in',
                'type' => 'string',
            ],
            'new_time' => [
                'description' => 'New time value (24h format)',
                'example' => '14:30',
                'type' => 'string',
            ],
        ];
    }
}
