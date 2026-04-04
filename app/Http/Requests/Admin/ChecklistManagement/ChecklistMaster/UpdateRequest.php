<?php

namespace App\Http\Requests\Admin\ChecklistManagement\ChecklistMaster;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation() {}

    public function rules(): array
    {
        return [
            'checklist_system_name' => ['required'],
            'equipment_category_id' => ['required'],
            'rental_ready_template_id' => ['required'],
            'customer_admin_template_id' => ['required'],
            'equipment_ids' => ['nullable', 'string'],
            
               'assign_equipment' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_name.required' => 'Category name is required.',
        ];
    }
}
