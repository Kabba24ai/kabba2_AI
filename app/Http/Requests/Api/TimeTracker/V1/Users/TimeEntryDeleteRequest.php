<?php

namespace App\Http\Requests\Api\TimeTracker\V1\Users;

use App\Http\Requests\ApiBaseFormRequest;

class TimeEntryDeleteRequest extends ApiBaseFormRequest
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
                'string',
                'in:clock_in,clock_out,lunch_out,lunch_in,unpaid_out,unpaid_in',
            ],

        ];
    }
}