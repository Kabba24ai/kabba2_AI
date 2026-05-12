<?php

namespace App\Http\Requests\Api\TimeTracker\V1\Users;

use App\Http\Requests\ApiBaseFormRequest;

class CreateEmptyTimeEntryRequest extends ApiBaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'], 

            'date' => ['required', 'date'],
            'time' => ['required', 'date_format:H:i'],
            'entry_type' => ['required', 'in:clock_in,clock_out'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'User is required',
            'user_id.exists' => 'Invalid user',

            'date.required' => 'Date is required',
            'date.date' => 'Invalid date format',

            'time.required' => 'Time is required',
            'time.date_format' => 'Time must be in HH:mm format',

            'entry_type.required' => 'Entry type is required',
            'entry_type.in' => 'Entry type must be clock_in or clock_out',
        ];
    }
}