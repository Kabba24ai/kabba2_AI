<?php

namespace App\Http\Requests\Api\TimeTracker\V1\Users;

use App\Http\Requests\ApiBaseFormRequest;

class TimeEntryCreateRequest extends ApiBaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'entry_id' => ['required', 'integer', 'exists:time_entries,id'],
            'entry_type' => ['required', 'string'],
            'new_time' => ['required', 'date_format:H:i'],
        ];
    }
}