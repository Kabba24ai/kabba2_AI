<?php

namespace App\Http\Requests\Admin\ChecklistManagement\EquipmentManagement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        // Normalize status
        if ($this->has('equipment_status')) {
            $this->merge([
                'equipment_status' => strtolower($this->input('equipment_status')),
            ]);
        }

        // Trim general notes
        if ($this->has('general_notes')) {
            $this->merge([
                'general_notes' => trim($this->input('general_notes')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'equipment_id' => ['required', 'integer', 'exists:equipment,id'],
            'equipment_status' => ['required', Rule::in(['available', 'damaged', 'maintenance'])],
            'inspectorSelect' => ['required', 'integer', 'exists:users,id'],
            'equipmentHours' => ['nullable', 'numeric'],

            'inspection_date' => ['nullable'],
            'inspection_time' => ['nullable'],

            'general_notes' => ['nullable', 'string'],

            'rental_ready_all_qa_json' => ['required', 'json'],

            // Optional helpers
            'total_questions' => ['nullable'],
            'answer-*' => ['nullable', 'string'], 
        ];
    }

    public function messages(): array
    {
        return [
            'equipment_id.required' => 'Equipment ID is required.',
            'equipment_id.exists' => 'Selected equipment does not exist.',
            'equipment_status.in' => 'Equipment status must be available, damaged, or maintenance.',
            'inspectorSelect.exists' => 'Selected inspector does not exist.',
            'rental_ready_all_qa_json.json' => 'Checklist data must be valid JSON.',
        ];
    }
}
