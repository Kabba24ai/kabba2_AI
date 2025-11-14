<?php

namespace App\Http\Requests\Admin\WebsiteManagement\FaqPage;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'default_expand' => ['nullable', 'boolean'],
            'icon' => ['nullable', 'file', 'image'], // 1MB
        ];
    }

    public function messages(): array
    {
        return [
            'category_name.required' => 'Category name is required.',
        ];
    }
}
