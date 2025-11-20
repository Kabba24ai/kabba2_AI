<?php

namespace App\Http\Requests\Admin\ChecklistManagement\RentalReady\Categories;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
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
            'category_name.required' => 'Category name is required.',
        ];
    }
}
