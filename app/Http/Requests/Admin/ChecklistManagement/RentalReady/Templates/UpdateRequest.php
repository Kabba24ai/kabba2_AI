<?php

namespace App\Http\Requests\Admin\ChecklistManagement\RentalReady\Templates;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest  extends FormRequest
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
            'template_name' => 'required',
            'description' => 'nullable',
            'equipment_category' => 'required',
            'is_active' => 'nullable',
            'questions' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
        ];
    }
}
