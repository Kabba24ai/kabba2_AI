<?php

namespace App\Http\Requests\Admin\ChecklistManagement\ChecklistMaster;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {

    }

    public function rules(): array
    {
         return [
            'checklist_system_name' => ['required'],
            'equipment_category_id' => ['required'],
            'rental_ready_template_id' => ['required'],
            'customer_admin_template_id' => ['required'],
            'equipment_ids' => ['nullable', 'string'],
        ];
    }       

    public function messages(): array
    {
        return [
        ];
    }
}
