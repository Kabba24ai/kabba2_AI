<?php

namespace App\Http\Requests\Admin\MaintenanceManagement\ServiceMaster\Category;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'required|string|size:7|starts_with:#',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Category name is required.',
            'name.max' => 'Category name must not exceed 255 characters.',
            'color.required' => 'Category color is required.',
            'color.regex' => 'Category color must be a valid hex color code.',
        ];
    }
}
