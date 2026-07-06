<?php

namespace App\Http\Requests\Admin\ServiceManagement;

use Illuminate\Foundation\Http\FormRequest;

class SaveLaborEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id'       => ['nullable', 'exists:users,id'],
            'labor_date'        => ['required', 'date'],
            'start_time'        => ['nullable', 'date_format:H:i'],
            'end_time'          => ['nullable', 'date_format:H:i', 'required_with:start_time'],
            // Hours may be omitted only when a time range is given to derive them from
            'hours'             => ['required_without:start_time', 'nullable', 'numeric', 'min:0.01', 'max:999'],
            'labor_description' => ['nullable', 'string', 'max:5000'],
            'internal_notes'    => ['nullable', 'string', 'max:5000'],
            'billable'          => ['nullable', 'boolean'],
            'labor_rate'        => ['nullable', 'numeric', 'min:0', 'max:99999'],
        ];
    }

    public function messages(): array
    {
        return [
            'hours.required_without' => 'Enter hours, or provide a start and end time to calculate them.',
            'end_time.required_with' => 'An end time is required when a start time is given.',
        ];
    }
}
