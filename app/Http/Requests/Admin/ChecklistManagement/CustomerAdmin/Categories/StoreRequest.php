<?php

namespace App\Http\Requests\Admin\ChecklistManagement\CustomerAdmin\Categories;

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
            'category_name' => ['required'],
            'description'   => ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
        ];
    }
}
