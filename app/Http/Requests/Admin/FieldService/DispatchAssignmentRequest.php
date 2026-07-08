<?php

namespace App\Http\Requests\Admin\FieldService;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Updates the dispatch-assignment panel from the workbench: who goes,
 * in what truck, when, and with what preparation notes.
 */
class DispatchAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'technician_id'          => ['nullable', 'integer', 'exists:users,id'],
            'truck_id'               => ['nullable', 'integer', 'exists:dispatch_ai_trucks,id'],
            'estimated_departure_at' => ['nullable', 'date'],
            'estimated_arrival_at'   => ['nullable', 'date', 'after_or_equal:estimated_departure_at'],
            'suggested_tools'        => ['nullable', 'string', 'max:5000'],
            'suggested_parts'        => ['nullable', 'string', 'max:5000'],
            'special_instructions'   => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'technician_id' => 'assigned technician',
            'truck_id'      => 'service truck',
        ];
    }
}
